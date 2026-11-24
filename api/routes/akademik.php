<?php
defined('SPADA') or exit;

/** Ambil pertemuan + mata kuliahnya, pastikan pengguna boleh mengelola. */
function pertemuan_manage(array $u, int $pid): array {
    $p = row('SELECT * FROM pertemuan WHERE id=?', [$pid]) ?? fail('Pertemuan tidak ditemukan.', 404);
    $m = matkul_or_404((int)$p['matkul_id']);
    guard_manage($u, $m);
    return [$p, $m];
}
function renumber_pertemuan(int $mid): void {
    $n = 1;
    foreach (rows('SELECT id FROM pertemuan WHERE matkul_id=? ORDER BY pertemuan_ke, id', [$mid]) as $r) {
        run('UPDATE pertemuan SET pertemuan_ke=? WHERE id=?', [$n++, $r['id']]);
    }
}

/* ================= PERTEMUAN ================= */
route('POST', 'pertemuan/save', function () {
    $u = need_role('dosen', 'admin');
    $id = p_int('id');
    $topik = p_str('topik', 150, true);
    $desk = p_str('deskripsi', 2000);
    $rawTgl = p_str('tanggal', 10);
    $tgl = $rawTgl === null ? null : (valid_date($rawTgl) ?? fail('Tanggal tidak valid (format YYYY-MM-DD).'));

    if ($id) {
        [$p] = pertemuan_manage($u, $id);
        $tgl ??= $p['tanggal'];
        run('UPDATE pertemuan SET topik=?, deskripsi=?, tanggal=? WHERE id=?', [$topik, $desk, $tgl, $id]);
    } else {
        $m = matkul_or_404(p_int('matkul_id', true));
        guard_manage($u, $m);
        $tgl ??= date('Y-m-d');
        $next = (int)row('SELECT COALESCE(MAX(pertemuan_ke),0)+1 AS n FROM pertemuan WHERE matkul_id=?', [$m['id']])['n'];
        if ($next > 127) fail('Jumlah pertemuan maksimum tercapai.');
        run('INSERT INTO pertemuan (matkul_id,pertemuan_ke,tanggal,topik,deskripsi) VALUES (?,?,?,?,?)', [$m['id'], $next, $tgl, $topik, $desk]);
        $id = last_id();
    }
    out(['id' => $id]);
});

route('POST', 'pertemuan/delete', function () {
    $u = need_role('dosen', 'admin');
    [$p, $m] = pertemuan_manage($u, p_int('id', true));
    $files = files_of_pertemuan([(int)$p['id']]);
    run('DELETE FROM pertemuan WHERE id=?', [$p['id']]);   // materi, tugas, pengumpulan, absensi ikut terhapus (cascade)
    renumber_pertemuan((int)$m['id']);
    purge_files($files);
    out(true);
});

/* ================= MATERI ================= */
route('POST', 'materi/upload', function () {
    $u = need_role('dosen', 'admin');
    [$p, $m] = pertemuan_manage($u, p_int('pertemuan_id', true));
    $stored = save_upload('file', 'materi', true);
    $judul = p_str('judul', 150) ?? preg_replace('/\.[^.]+$/', '', file_label($stored));
    run('INSERT INTO materi (matkul_id,pertemuan_id,judul,file) VALUES (?,?,?,?)', [$m['id'], $p['id'], $judul, $stored]);
    out(['id' => last_id()]);
});

route('POST', 'materi/delete', function () {
    $u = need_role('dosen', 'admin');
    $mt = row('SELECT * FROM materi WHERE id=?', [p_int('id', true)]) ?? fail('Materi tidak ditemukan.', 404);
    guard_manage($u, matkul_or_404((int)$mt['matkul_id']));
    run('DELETE FROM materi WHERE id=?', [$mt['id']]);
    delete_file('materi', $mt['file']);
    out(true);
});

/* ================= TUGAS ================= */
function tugas_manage(array $u, int $tid): array {
    $t = row('SELECT * FROM tugas WHERE id=?', [$tid]) ?? fail('Tugas tidak ditemukan.', 404);
    $m = matkul_or_404((int)$t['matkul_id']);
    guard_manage($u, $m);
    return [$t, $m];
}

route('POST', 'tugas/save', function () {
    $u = need_role('dosen', 'admin');
    $id = p_int('id');
    if ($id) { [$t, $m] = tugas_manage($u, $id); }
    else { $m = matkul_or_404(p_int('matkul_id', true)); guard_manage($u, $m); }

    $pid = p_int('pertemuan_id');
    if ($pid !== null && !row('SELECT 1 FROM pertemuan WHERE id=? AND matkul_id=?', [$pid, $m['id']])) fail('Pertemuan tidak valid.');
    $judul = p_str('judul', 150, true);
    $desk = p_str('deskripsi', 5000);
    $dl = valid_datetime(p_str('deadline', 20, true)) ?? fail('Batas waktu tidak valid.');
    $teks = (int)p_bool('terima_teks', true); $file = (int)p_bool('terima_file', true); $buka = (int)p_bool('dibuka', true);
    if (!$teks && !$file) fail('Pilih minimal satu jenis jawaban (teks atau file).');

    if ($id) {
        run('UPDATE tugas SET pertemuan_id=?,judul=?,deskripsi=?,deadline=?,terima_teks=?,terima_file=?,dibuka=? WHERE id=?',
            [$pid, $judul, $desk, $dl, $teks, $file, $buka, $id]);
    } else {
        run('INSERT INTO tugas (matkul_id,pertemuan_id,judul,deskripsi,deadline,terima_teks,terima_file,dibuka) VALUES (?,?,?,?,?,?,?,?)',
            [$m['id'], $pid, $judul, $desk, $dl, $teks, $file, $buka]);
        $id = last_id();
    }
    out(['id' => $id]);
});

route('POST', 'tugas/delete', function () {
    $u = need_role('dosen', 'admin');
    [$t] = tugas_manage($u, p_int('id', true));
    $files = files_of_tugas((int)$t['id']);
    run('DELETE FROM tugas WHERE id=?', [$t['id']]);
    purge_files($files);
    out(true);
});

/** Dosen: seluruh mahasiswa kelas + status pengumpulan masing-masing. */
route('GET', 'tugas/pengumpulan', function () {
    $u = need_role('dosen', 'admin');
    [$t, $m] = tugas_manage($u, g_int('tugas_id'));
    $list = rows('SELECT us.id AS user_id, us.nama, us.nim_nip, g.id, g.file, g.jawaban, g.nilai, g.catatan, g.dikumpul_at
                  FROM matkul_peserta mp JOIN users us ON us.id=mp.user_id
                  LEFT JOIN pengumpulan g ON g.user_id=us.id AND g.tugas_id=?
                  WHERE mp.matkul_id=? ORDER BY us.nama', [$t['id'], $m['id']]);
    out(array_map(fn($r) => ['user_id' => (int)$r['user_id'], 'nama' => $r['nama'], 'nim_nip' => $r['nim_nip'],
        'id' => $r['id'] === null ? null : (int)$r['id'], 'file_nama' => file_label($r['file'] ?: null), 'jawaban' => $r['jawaban'],
        'nilai' => $r['nilai'] === null ? null : (int)$r['nilai'], 'catatan' => $r['catatan'], 'dikumpul_at' => $r['dikumpul_at']], $list));
});

route('POST', 'tugas/nilai', function () {
    $u = need_role('dosen', 'admin');
    $g = row('SELECT * FROM pengumpulan WHERE id=?', [p_int('pengumpulan_id', true)]) ?? fail('Pengumpulan tidak ditemukan.', 404);
    tugas_manage($u, (int)$g['tugas_id']);
    $nilai = p_int('nilai');
    if ($nilai !== null && ($nilai < 0 || $nilai > 100)) fail('Nilai harus antara 0 dan 100.');
    run('UPDATE pengumpulan SET nilai=?, catatan=? WHERE id=?', [$nilai, p_str('catatan', 255), $g['id']]);
    out(true);
});

/** Mahasiswa: kumpulkan / perbarui tugas (jawaban teks dan/atau file). */
route('POST', 'tugas/kumpul', function () {
    $u = need_role('mahasiswa');
    $t = row('SELECT * FROM tugas WHERE id=?', [p_int('tugas_id', true)]) ?? fail('Tugas tidak ditemukan.', 404);
    $m = matkul_or_404((int)$t['matkul_id']);
    guard_access($u, $m);
    if (!$t['dibuka']) fail('Pengumpulan tugas ini sudah ditutup.');
    if ($t['deadline'] < now_str()) fail('Batas waktu pengumpulan sudah lewat.');

    $old = row('SELECT * FROM pengumpulan WHERE tugas_id=? AND user_id=?', [$t['id'], $u['id']]);
    if ($old && $old['nilai'] !== null) fail('Tugas sudah dinilai dan tidak dapat diubah lagi.');

    $jawaban = $t['terima_teks'] ? p_str('jawaban', 10000) : null;
    $new = $t['terima_file'] ? save_upload('file', 'tugas') : null;
    $file = $new ?? ($old['file'] ?? '');
    if ($jawaban === null && $file === '') {
        fail($t['terima_teks'] && $t['terima_file'] ? 'Isi jawaban atau pilih file terlebih dahulu.'
            : ($t['terima_file'] ? 'Pilih file terlebih dahulu.' : 'Isi jawaban terlebih dahulu.'));
    }
    if ($old) {
        run('UPDATE pengumpulan SET file=?, jawaban=?, dikumpul_at=? WHERE id=?', [$file, $jawaban, now_str(), $old['id']]);
        if ($new && $old['file']) delete_file('tugas', $old['file']);
    } else {
        run('INSERT INTO pengumpulan (tugas_id,user_id,file,jawaban,dikumpul_at) VALUES (?,?,?,?,?)', [$t['id'], $u['id'], $file, $jawaban, now_str()]);
    }
    out(true);
});

/* ================= PRESENSI ================= */
const STATUS_ABSENSI = ['hadir', 'izin', 'sakit', 'alpa'];

route('GET', 'presensi/list', function () {
    $u = need_role('dosen', 'admin');
    [$p, $m] = pertemuan_manage($u, g_int('pertemuan_id'));
    $list = rows('SELECT us.id AS user_id, us.nama, us.nim_nip, a.id AS absensi_id, a.status, a.catatan, a.bukti
                  FROM matkul_peserta mp JOIN users us ON us.id=mp.user_id
                  LEFT JOIN absensi a ON a.user_id=us.id AND a.pertemuan_id=?
                  WHERE mp.matkul_id=? ORDER BY us.nama', [$p['id'], $m['id']]);
    out(array_map(fn($r) => ['user_id' => (int)$r['user_id'], 'nama' => $r['nama'], 'nim_nip' => $r['nim_nip'],
        'absensi_id' => $r['absensi_id'] === null ? null : (int)$r['absensi_id'],
        'status' => $r['status'], 'catatan' => $r['catatan'], 'bukti_nama' => file_label($r['bukti'])], $list));
});

/** Dosen menetapkan/mengubah/menghapus status presensi satu mahasiswa. */
route('POST', 'presensi/set', function () {
    $u = need_role('dosen', 'admin');
    [$p, $m] = pertemuan_manage($u, p_int('pertemuan_id', true));
    $uid = p_int('user_id', true);
    if (!is_enrolled($uid, (int)$m['id'])) fail('Mahasiswa tidak terdaftar di kelas ini.');
    $status = p_str('status', 10);
    $old = row('SELECT * FROM absensi WHERE pertemuan_id=? AND user_id=?', [$p['id'], $uid]);

    if ($status === null) {   // kosongkan
        if ($old) { run('DELETE FROM absensi WHERE id=?', [$old['id']]); delete_file('absensi', $old['bukti']); }
        out(true);
    }
    if (!in_array($status, STATUS_ABSENSI, true)) fail('Status presensi tidak valid.');
    if ($old) run('UPDATE absensi SET status=? WHERE id=?', [$status, $old['id']]);
    else run('INSERT INTO absensi (pertemuan_id,user_id,status,waktu) VALUES (?,?,?,?)', [$p['id'], $uid, $status, now_str()]);
    out(true);
});

/** Mahasiswa mengisi presensinya sendiri (hadir / izin / sakit + bukti). */
route('POST', 'presensi/isi', function () {
    $u = need_role('mahasiswa');
    $p = row('SELECT * FROM pertemuan WHERE id=?', [p_int('pertemuan_id', true)]) ?? fail('Pertemuan tidak ditemukan.', 404);
    guard_access($u, matkul_or_404((int)$p['matkul_id']));
    $status = p_str('status', 10, true);
    if (!in_array($status, ['hadir', 'izin', 'sakit'], true)) fail('Pilih Hadir, Izin, atau Sakit.');
    $old = row('SELECT * FROM absensi WHERE pertemuan_id=? AND user_id=?', [$p['id'], $u['id']]);
    if ($old && $old['status'] === 'alpa') fail('Presensi ini ditetapkan dosen sebagai alpa. Hubungi dosen untuk mengubahnya.');

    $catatan = $status === 'hadir' ? null : p_str('catatan', 255);
    $bukti = $status === 'hadir' ? null : save_upload('bukti', 'absensi');
    if ($old) {
        $keep = $status === 'hadir' ? null : ($bukti ?? $old['bukti']);
        run('UPDATE absensi SET status=?, catatan=?, bukti=?, waktu=? WHERE id=?', [$status, $catatan, $keep, now_str(), $old['id']]);
        if ($old['bukti'] && $old['bukti'] !== $keep) delete_file('absensi', $old['bukti']);
    } else {
        run('INSERT INTO absensi (pertemuan_id,user_id,status,catatan,bukti,waktu) VALUES (?,?,?,?,?,?)', [$p['id'], $u['id'], $status, $catatan, $bukti, now_str()]);
    }
    out(true);
});
