<?php
defined('SPADA') or exit;

function ulangan_or_404(int $id): array { return row('SELECT * FROM ulangan WHERE id=?', [$id]) ?? fail('Ulangan tidak ditemukan.', 404); }
function ulangan_manage(array $u, int $uid): array {
    $ul = ulangan_or_404($uid);
    guard_manage($u, matkul_or_404((int)$ul['matkul_id']));
    return $ul;
}

route('GET', 'ulangan/list', function () {
    $u = need_login();
    $m = matkul_or_404(g_int('matkul_id'));
    guard_access($u, $m);
    $manage = can_manage($u, $m);
    $list = rows('SELECT id,judul,durasi_menit,
                    (SELECT COUNT(*) FROM soal s WHERE s.ulangan_id=ulangan.id) AS jumlah_soal,
                    (SELECT COUNT(*) FROM hasil_ulangan h WHERE h.ulangan_id=ulangan.id) AS jumlah_selesai
                  FROM ulangan WHERE matkul_id=? ORDER BY id', [$m['id']]);
    $nilai = [];
    if ($u['role'] === 'mahasiswa') {
        foreach (rows('SELECT ulangan_id,nilai FROM hasil_ulangan WHERE user_id=?', [$u['id']]) as $h) $nilai[$h['ulangan_id']] = (float)$h['nilai'];
    }
    out(['bisa_kelola' => $manage, 'daftar' => array_map(fn($r) => ['id' => (int)$r['id'], 'judul' => $r['judul'],
        'durasi_menit' => (int)$r['durasi_menit'], 'jumlah_soal' => (int)$r['jumlah_soal'], 'jumlah_selesai' => (int)$r['jumlah_selesai'],
        'nilai' => $nilai[$r['id']] ?? null], $list)]);
});

route('POST', 'ulangan/save', function () {
    $u = need_role('dosen', 'admin');
    $id = p_int('id');
    $judul = p_str('judul', 150, true);
    $durasi = p_int('durasi_menit', true);
    if ($durasi < 1 || $durasi > 600) fail('Durasi harus 1-600 menit.');
    if ($id) {
        ulangan_manage($u, $id);
        run('UPDATE ulangan SET judul=?, durasi_menit=? WHERE id=?', [$judul, $durasi, $id]);
    } else {
        $m = matkul_or_404(p_int('matkul_id', true));
        guard_manage($u, $m);
        run('INSERT INTO ulangan (matkul_id,judul,durasi_menit) VALUES (?,?,?)', [$m['id'], $judul, $durasi]);
        $id = last_id();
    }
    out(['id' => $id]);
});

route('POST', 'ulangan/delete', function () {
    $u = need_role('dosen', 'admin');
    $ul = ulangan_manage($u, p_int('id', true));
    run('DELETE FROM ulangan WHERE id=?', [$ul['id']]);
    out(true);
});

/* ---------- Soal (dosen) ---------- */
route('GET', 'soal/list', function () {
    $u = need_role('dosen', 'admin');
    $ul = ulangan_manage($u, g_int('ulangan_id'));
    out(['ulangan' => ['id' => (int)$ul['id'], 'judul' => $ul['judul'], 'durasi_menit' => (int)$ul['durasi_menit']],
         'soal' => rows('SELECT id,pertanyaan,opsi_a,opsi_b,opsi_c,opsi_d,jawaban_benar FROM soal WHERE ulangan_id=? ORDER BY id', [$ul['id']])]);
});

route('POST', 'soal/save', function () {
    $u = need_role('dosen', 'admin');
    $id = p_int('id');
    $f = [p_str('pertanyaan', 5000, true), p_str('opsi_a', 255, true), p_str('opsi_b', 255, true), p_str('opsi_c', 255, true), p_str('opsi_d', 255, true)];
    $kunci = p_str('jawaban_benar', 1, true);
    if (!in_array($kunci, ['a', 'b', 'c', 'd'], true)) fail('Kunci jawaban harus a, b, c, atau d.');
    if ($id) {
        $s = row('SELECT * FROM soal WHERE id=?', [$id]) ?? fail('Soal tidak ditemukan.', 404);
        ulangan_manage($u, (int)$s['ulangan_id']);
        run('UPDATE soal SET pertanyaan=?,opsi_a=?,opsi_b=?,opsi_c=?,opsi_d=?,jawaban_benar=? WHERE id=?', [...$f, $kunci, $id]);
    } else {
        $ul = ulangan_manage($u, p_int('ulangan_id', true));
        run('INSERT INTO soal (ulangan_id,pertanyaan,opsi_a,opsi_b,opsi_c,opsi_d,jawaban_benar) VALUES (?,?,?,?,?,?,?)', [$ul['id'], ...$f, $kunci]);
        $id = last_id();
    }
    out(['id' => $id]);
});

route('POST', 'soal/delete', function () {
    $u = need_role('dosen', 'admin');
    $s = row('SELECT * FROM soal WHERE id=?', [p_int('id', true)]) ?? fail('Soal tidak ditemukan.', 404);
    ulangan_manage($u, (int)$s['ulangan_id']);
    run('DELETE FROM soal WHERE id=?', [$s['id']]);
    out(true);
});

/* ---------- Hasil (dosen) ---------- */
route('GET', 'ulangan/hasil', function () {
    $u = need_role('dosen', 'admin');
    $ul = ulangan_manage($u, g_int('ulangan_id'));
    $list = rows('SELECT us.id AS user_id, us.nama, us.nim_nip, h.nilai, h.selesai_at
                  FROM matkul_peserta mp JOIN users us ON us.id=mp.user_id
                  LEFT JOIN hasil_ulangan h ON h.user_id=us.id AND h.ulangan_id=?
                  WHERE mp.matkul_id=? ORDER BY us.nama', [$ul['id'], $ul['matkul_id']]);
    out(array_map(fn($r) => ['user_id' => (int)$r['user_id'], 'nama' => $r['nama'], 'nim_nip' => $r['nim_nip'],
        'nilai' => $r['nilai'] === null ? null : (float)$r['nilai'], 'selesai_at' => $r['selesai_at']], $list));
});

/** Dosen mengizinkan mahasiswa mengulang (hapus hasilnya). */
route('POST', 'ulangan/reset', function () {
    $u = need_role('dosen', 'admin');
    $ul = ulangan_manage($u, p_int('ulangan_id', true));
    run('DELETE FROM hasil_ulangan WHERE ulangan_id=? AND user_id=?', [$ul['id'], p_int('user_id', true)]);
    unset($_SESSION['quiz'][$ul['id']]);
    out(true);
});

/* ---------- Mengerjakan (mahasiswa) ---------- */
route('GET', 'ulangan/mulai', function () {
    $u = need_role('mahasiswa');
    $ul = ulangan_or_404(g_int('ulangan_id'));
    guard_access($u, matkul_or_404((int)$ul['matkul_id']));
    if (row('SELECT 1 FROM hasil_ulangan WHERE ulangan_id=? AND user_id=?', [$ul['id'], $u['id']])) fail('Anda sudah mengerjakan ulangan ini.');
    $soal = rows('SELECT id,pertanyaan,opsi_a,opsi_b,opsi_c,opsi_d FROM soal WHERE ulangan_id=? ORDER BY id', [$ul['id']]);
    if (!$soal) fail('Ulangan ini belum memiliki soal.');

    $durasi = (int)$ul['durasi_menit'] * 60;
    $mulai = $_SESSION['quiz'][$ul['id']] ?? null;          // lanjut bila halaman di-refresh, waktu tidak diulang
    if ($mulai && $mulai + $durasi + 60 < time()) {   // waktu habis & tidak dikirim => dicatat 0, tidak bisa mulai ulang
        run('INSERT INTO hasil_ulangan (ulangan_id,user_id,nilai,selesai_at) VALUES (?,?,?,?)', [$ul['id'], $u['id'], 0, now_str()]);
        unset($_SESSION['quiz'][$ul['id']]);
        fail('Waktu ulangan sudah habis dan tidak dikirim. Nilai dicatat 0; hubungi dosen bila perlu mengulang.');
    }
    if (!$mulai) { $mulai = time(); $_SESSION['quiz'][$ul['id']] = $mulai; }
    out(['ulangan' => ['id' => (int)$ul['id'], 'judul' => $ul['judul']], 'sisa_detik' => max(0, $mulai + $durasi - time()), 'soal' => $soal]);
});

route('POST', 'ulangan/kirim', function () {
    $u = need_role('mahasiswa');
    $ul = ulangan_or_404(p_int('ulangan_id', true));
    guard_access($u, matkul_or_404((int)$ul['matkul_id']));
    if (row('SELECT 1 FROM hasil_ulangan WHERE ulangan_id=? AND user_id=?', [$ul['id'], $u['id']])) fail('Anda sudah mengerjakan ulangan ini.');
    $mulai = $_SESSION['quiz'][$ul['id']] ?? null;
    if (!$mulai) fail('Ulangan belum dimulai.');

    $jaw = input()['jawaban'] ?? [];
    if (!is_array($jaw)) $jaw = [];
    $soal = rows('SELECT id,jawaban_benar FROM soal WHERE ulangan_id=?', [$ul['id']]);
    $benar = 0;
    foreach ($soal as $s) if (($jaw[$s['id']] ?? null) === $s['jawaban_benar']) $benar++;
    $nilai = $soal ? round($benar / count($soal) * 100, 2) : 0;

    $telat = time() > $mulai + (int)$ul['durasi_menit'] * 60 + 60;   // toleransi 60 detik
    if ($telat) $nilai = 0;
    run('INSERT INTO hasil_ulangan (ulangan_id,user_id,nilai,selesai_at) VALUES (?,?,?,?)', [$ul['id'], $u['id'], $nilai, now_str()]);
    unset($_SESSION['quiz'][$ul['id']]);
    out(['nilai' => (float)$nilai, 'benar' => $benar, 'total' => count($soal), 'telat' => $telat]);
});
