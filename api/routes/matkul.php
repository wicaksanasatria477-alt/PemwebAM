<?php
defined('SPADA') or exit;

/** Mata kuliah milik/akses pengguna + jadwal + jumlah peserta. */
function matkul_list_for(array $u): array {
    $base = 'SELECT m.id,m.kode,m.nama,m.sks,m.deskripsi,m.dosen_id,d.nama AS dosen_nama,
                    (SELECT COUNT(*) FROM matkul_peserta x WHERE x.matkul_id=m.id) AS jumlah_peserta
             FROM matkul m JOIN users d ON d.id=m.dosen_id';
    if ($u['role'] === 'mahasiswa') $list = rows("$base JOIN matkul_peserta p ON p.matkul_id=m.id WHERE p.user_id=? ORDER BY m.kode", [$u['id']]);
    elseif ($u['role'] === 'dosen') $list = rows("$base WHERE m.dosen_id=? ORDER BY m.kode", [$u['id']]);
    else $list = rows("$base ORDER BY m.kode");

    $jad = [];
    if ($list) {
        $ids = array_column($list, 'id');
        foreach (rows('SELECT * FROM jadwal WHERE matkul_id IN (' . ph($ids) . ') ORDER BY jam_mulai', $ids) as $j) {
            $jad[$j['matkul_id']][] = ['id' => (int)$j['id'], 'hari' => $j['hari'], 'hari_no' => array_search($j['hari'], HARI, true) + 1,
                'jam_mulai' => hhmm($j['jam_mulai']), 'jam_selesai' => hhmm($j['jam_selesai']), 'ruang' => $j['ruang']];
        }
    }
    foreach ($list as &$m) {
        $m['id'] = (int)$m['id']; $m['sks'] = (int)$m['sks']; $m['dosen_id'] = (int)$m['dosen_id'];
        $m['jumlah_peserta'] = (int)$m['jumlah_peserta']; $m['jadwal'] = $jad[$m['id']] ?? [];
    }
    unset($m);
    return $list;
}

route('GET', 'matkul/list', function () { out(matkul_list_for(need_login())); });

/** Seluruh isi halaman kelas: pertemuan, materi, tugas (+ pengumpulan/absensi milik mahasiswa). */
route('GET', 'matkul/get', function () {
    $u = need_login();
    $m = matkul_or_404(g_int('id'));
    guard_access($u, $m);
    $mid = (int)$m['id']; $mhs = $u['role'] === 'mahasiswa'; $manage = can_manage($u, $m);

    $pert = rows('SELECT id,pertemuan_ke,tanggal,topik,deskripsi FROM pertemuan WHERE matkul_id=? ORDER BY pertemuan_ke', [$mid]);
    $materi = rows('SELECT id,pertemuan_id,judul,file,created_at FROM materi WHERE matkul_id=? ORDER BY id', [$mid]);
    $tugas = rows('SELECT id,pertemuan_id,judul,deskripsi,deadline,terima_teks,terima_file,dibuka FROM tugas WHERE matkul_id=? ORDER BY deadline,id', [$mid]);
    $tids = array_column($tugas, 'id'); $pids = array_column($pert, 'id');

    $mine = []; $cnt = []; $abs = [];
    if ($tids && $mhs) {
        foreach (rows('SELECT id,tugas_id,file,jawaban,nilai,catatan,dikumpul_at FROM pengumpulan WHERE user_id=? AND tugas_id IN (' . ph($tids) . ')', array_merge([$u['id']], $tids)) as $g) {
            $mine[$g['tugas_id']] = ['id' => (int)$g['id'], 'file_nama' => file_label($g['file'] ?: null), 'jawaban' => $g['jawaban'],
                'nilai' => $g['nilai'] === null ? null : (int)$g['nilai'], 'catatan' => $g['catatan'], 'dikumpul_at' => $g['dikumpul_at']];
        }
    }
    if ($tids && $manage) {
        foreach (rows('SELECT tugas_id, COUNT(*) AS n, SUM(CASE WHEN nilai IS NULL THEN 1 ELSE 0 END) AS belum FROM pengumpulan WHERE tugas_id IN (' . ph($tids) . ') GROUP BY tugas_id', $tids) as $c) {
            $cnt[$c['tugas_id']] = ['n' => (int)$c['n'], 'belum' => (int)$c['belum']];
        }
    }
    if ($pids && $mhs) {
        foreach (rows('SELECT id,pertemuan_id,status,catatan,bukti FROM absensi WHERE user_id=? AND pertemuan_id IN (' . ph($pids) . ')', array_merge([$u['id']], $pids)) as $a) {
            $abs[$a['pertemuan_id']] = ['id' => (int)$a['id'], 'status' => $a['status'], 'catatan' => $a['catatan'], 'bukti_nama' => file_label($a['bukti'])];
        }
    }
    $now = now_str();
    $T = [];
    foreach ($tugas as $t) {
        $T[] = ['id' => (int)$t['id'], 'pertemuan_id' => $t['pertemuan_id'] === null ? null : (int)$t['pertemuan_id'],
            'judul' => $t['judul'], 'deskripsi' => $t['deskripsi'], 'deadline' => $t['deadline'],
            'terima_teks' => (bool)$t['terima_teks'], 'terima_file' => (bool)$t['terima_file'], 'dibuka' => (bool)$t['dibuka'],
            'bisa_kumpul' => (bool)$t['dibuka'] && $t['deadline'] >= $now,
            'pengumpulan' => $mine[$t['id']] ?? null, 'rekap' => $cnt[$t['id']] ?? ['n' => 0, 'belum' => 0]];
    }
    $M = array_map(fn($x) => ['id' => (int)$x['id'], 'pertemuan_id' => (int)$x['pertemuan_id'], 'judul' => $x['judul'], 'file_nama' => file_label($x['file'])], $materi);
    $P = array_map(fn($p) => ['id' => (int)$p['id'], 'pertemuan_ke' => (int)$p['pertemuan_ke'], 'tanggal' => $p['tanggal'], 'topik' => $p['topik'],
        'deskripsi' => $p['deskripsi'], 'absensi' => $abs[$p['id']] ?? null], $pert);

    $dosen = row('SELECT nama FROM users WHERE id=?', [$m['dosen_id']]);
    out(['matkul' => ['id' => $mid, 'kode' => $m['kode'], 'nama' => $m['nama'], 'sks' => (int)$m['sks'], 'deskripsi' => $m['deskripsi'],
            'dosen_id' => (int)$m['dosen_id'], 'dosen_nama' => $dosen['nama'] ?? ''],
         'pertemuan' => $P, 'materi' => $M, 'tugas' => $T, 'bisa_kelola' => $manage,
         'jumlah_peserta' => (int)row('SELECT COUNT(*) c FROM matkul_peserta WHERE matkul_id=?', [$mid])['c']]);
});

/* ---------- Admin: kelola mata kuliah ---------- */
route('POST', 'matkul/save', function () {
    need_role('admin');
    $id = p_int('id');
    $kode = strtoupper((string)p_str('kode', 20, true));
    $nama = p_str('nama', 100, true);
    $sks  = p_int('sks', true);
    if ($sks < 1 || $sks > 6) fail('SKS harus antara 1 dan 6.');
    $desk = p_str('deskripsi', 2000);
    $dosen = p_int('dosen_id', true);
    if (!row("SELECT 1 FROM users WHERE id=? AND role='dosen'", [$dosen])) fail('Dosen pengampu tidak valid.');
    if (row('SELECT 1 FROM matkul WHERE kode=? AND id<>?', [$kode, $id ?? 0])) fail('Kode mata kuliah sudah dipakai.');

    if ($id) {
        matkul_or_404($id);
        run('UPDATE matkul SET kode=?,nama=?,sks=?,deskripsi=?,dosen_id=? WHERE id=?', [$kode, $nama, $sks, $desk, $dosen, $id]);
    } else {
        run('INSERT INTO matkul (kode,nama,sks,deskripsi,dosen_id) VALUES (?,?,?,?,?)', [$kode, $nama, $sks, $desk, $dosen]);
        $id = last_id();
    }
    out(['id' => $id]);
});

route('POST', 'matkul/delete', function () {
    need_role('admin');
    $m = matkul_or_404(p_int('id', true));
    $files = files_of_matkul((int)$m['id']);   // kumpulkan dulu, baris DB ikut terhapus (cascade)
    run('DELETE FROM matkul WHERE id=?', [$m['id']]);
    purge_files($files);
    out(true);
});

/* ---------- Admin: jadwal ---------- */
route('POST', 'jadwal/save', function () {
    need_role('admin');
    $id = p_int('id');
    $m = matkul_or_404(p_int('matkul_id', true));
    $hari = p_str('hari', 10, true);
    if (!in_array($hari, HARI, true)) fail('Hari tidak valid.');
    $mulai = valid_time(p_str('jam_mulai', 8, true)) ?? fail('Jam mulai tidak valid.');
    $selesai = valid_time(p_str('jam_selesai', 8, true)) ?? fail('Jam selesai tidak valid.');
    if ($mulai >= $selesai) fail('Jam selesai harus setelah jam mulai.');
    $ruang = p_str('ruang', 50, true);

    $bentrok = row('SELECT j.id, m.nama FROM jadwal j JOIN matkul m ON m.id=j.matkul_id
                    WHERE j.hari=? AND j.ruang=? AND j.jam_mulai < ? AND j.jam_selesai > ? AND j.id<>?',
                   [$hari, $ruang, $selesai, $mulai, $id ?? 0]);
    if ($bentrok) fail("Ruang $ruang pada jam tersebut sudah dipakai {$bentrok['nama']}.");

    if ($id) {
        if (!row('SELECT 1 FROM jadwal WHERE id=? AND matkul_id=?', [$id, $m['id']])) fail('Jadwal tidak ditemukan.', 404);
        run('UPDATE jadwal SET hari=?,jam_mulai=?,jam_selesai=?,ruang=? WHERE id=?', [$hari, $mulai, $selesai, $ruang, $id]);
    } else {
        run('INSERT INTO jadwal (matkul_id,hari,jam_mulai,jam_selesai,ruang) VALUES (?,?,?,?,?)', [$m['id'], $hari, $mulai, $selesai, $ruang]);
        $id = last_id();
    }
    out(['id' => $id]);
});

route('POST', 'jadwal/delete', function () {
    need_role('admin');
    run('DELETE FROM jadwal WHERE id=?', [p_int('id', true)]);
    out(true);
});

/* ---------- Dashboard ---------- */
route('GET', 'dashboard', function () {
    $u = need_login();
    $list = matkul_list_for($u);
    $ids = array_column($list, 'id');
    $stat = ['sks' => array_sum(array_column($list, 'sks')), 'matkul' => count($list)];

    if ($u['role'] === 'mahasiswa') {
        $stat['label'] = 'Tugas belum dikumpulkan';
        $stat['butuh'] = $ids ? (int)row('SELECT COUNT(*) c FROM tugas t WHERE t.matkul_id IN (' . ph($ids) . ') AND t.dibuka=1 AND t.deadline>=?
              AND NOT EXISTS (SELECT 1 FROM pengumpulan g WHERE g.tugas_id=t.id AND g.user_id=?)', array_merge($ids, [now_str(), $u['id']]))['c'] : 0;
    } elseif ($u['role'] === 'dosen') {
        $stat['label'] = 'Submisi perlu dinilai';
        $stat['butuh'] = $ids ? (int)row('SELECT COUNT(*) c FROM pengumpulan g JOIN tugas t ON t.id=g.tugas_id
              WHERE g.nilai IS NULL AND t.matkul_id IN (' . ph($ids) . ')', $ids)['c'] : 0;
    } else {
        $stat['label'] = 'Mahasiswa terdaftar';
        $stat['butuh'] = (int)row("SELECT COUNT(*) c FROM users WHERE role='mahasiswa'")['c'];
    }
    $umum = rows('SELECT p.id,p.judul,p.isi,p.created_at,u.nama AS penulis FROM pengumuman p JOIN users u ON u.id=p.penulis_id
                  WHERE p.matkul_id IS NULL ORDER BY p.created_at DESC, p.id DESC LIMIT 3');
    out(['matkul' => $list, 'stat' => $stat, 'pengumuman' => $umum]);
});
