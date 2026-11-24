<?php
defined('SPADA') or exit;

/* ---------- Admin: kelola pengguna ---------- */
route('GET', 'users/list', function () {
    need_role('admin');
    $role = $_GET['role'] ?? '';
    $q = trim((string)($_GET['q'] ?? ''));
    $sql = 'SELECT id,nama,email,nim_nip,role,created_at FROM users WHERE 1=1';
    $p = [];
    if (in_array($role, ['admin', 'dosen', 'mahasiswa'], true)) { $sql .= ' AND role=?'; $p[] = $role; }
    if ($q !== '') {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
        $sql .= ' AND (nama LIKE ? OR nim_nip LIKE ? OR email LIKE ?)'; array_push($p, $like, $like, $like);
    }
    $sql .= " ORDER BY CASE role WHEN 'admin' THEN 1 WHEN 'dosen' THEN 2 ELSE 3 END, nama";
    $list = rows($sql . ' LIMIT 500', $p);
    foreach ($list as &$x) $x['id'] = (int)$x['id'];
    out($list);
});

route('POST', 'users/save', function () {
    $admin = need_role('admin');
    $id = p_int('id');
    $nama = p_str('nama', 100, true);
    $email = p_str('email', 100, true);
    $nim = p_str('nim_nip', 30, true);
    $role = p_str('role', 20, true);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Format email tidak valid.');
    if (!in_array($role, ['admin', 'dosen', 'mahasiswa'], true)) fail('Peran tidak valid.');
    $pw = (string)(input()['password'] ?? '');
    if (row('SELECT 1 FROM users WHERE email=? AND id<>?', [$email, $id ?? 0])) fail('Email sudah dipakai.');
    if (row('SELECT 1 FROM users WHERE nim_nip=? AND id<>?', [$nim, $id ?? 0])) fail('NIM/NIP sudah dipakai.');

    if ($id) {
        $old = row('SELECT * FROM users WHERE id=?', [$id]) ?? fail('Pengguna tidak ditemukan.', 404);
        if ($id === (int)$admin['id'] && $role !== 'admin') fail('Anda tidak bisa mengubah peran akun Anda sendiri.');
        if ($old['role'] === 'dosen' && $role !== 'dosen' && row('SELECT 1 FROM matkul WHERE dosen_id=?', [$id])) {
            fail('Dosen ini masih mengampu mata kuliah. Pindahkan pengampunya dulu.');
        }
        if ($pw !== '') {
            if (strlen($pw) < 6) fail('Kata sandi minimal 6 karakter.');
            run('UPDATE users SET nama=?,email=?,nim_nip=?,role=?,password=? WHERE id=?', [$nama, $email, $nim, $role, password_hash($pw, PASSWORD_DEFAULT), $id]);
        } else {
            run('UPDATE users SET nama=?,email=?,nim_nip=?,role=? WHERE id=?', [$nama, $email, $nim, $role, $id]);
        }
        // Jika bukan mahasiswa lagi, lepas dari semua kelas.
        if ($role !== 'mahasiswa') run('DELETE FROM matkul_peserta WHERE user_id=?', [$id]);
    } else {
        if (strlen($pw) < 6) fail('Kata sandi wajib diisi (minimal 6 karakter).');
        run('INSERT INTO users (nama,email,nim_nip,password,role) VALUES (?,?,?,?,?)', [$nama, $email, $nim, password_hash($pw, PASSWORD_DEFAULT), $role]);
        $id = last_id();
    }
    out(['id' => $id]);
});

route('POST', 'users/delete', function () {
    $admin = need_role('admin');
    $id = p_int('id', true);
    if ($id === (int)$admin['id']) fail('Anda tidak bisa menghapus akun Anda sendiri.');
    $u = row('SELECT * FROM users WHERE id=?', [$id]) ?? fail('Pengguna tidak ditemukan.', 404);
    if (row('SELECT 1 FROM matkul WHERE dosen_id=?', [$id])) fail('Dosen ini masih mengampu mata kuliah. Pindahkan pengampunya dulu.');

    // Kumpulkan file miliknya sebelum cascade menghapus barisnya.
    $files = [['foto', $u['foto']]];
    foreach (rows("SELECT file FROM pengumpulan WHERE file<>'' AND user_id=?", [$id]) as $r) $files[] = ['tugas', $r['file']];
    foreach (rows('SELECT bukti FROM absensi WHERE bukti IS NOT NULL AND user_id=?', [$id]) as $r) $files[] = ['absensi', $r['bukti']];
    foreach (rows('SELECT file FROM pengumuman WHERE file IS NOT NULL AND penulis_id=?', [$id]) as $r) $files[] = ['pengumuman', $r['file']];
    run('DELETE FROM users WHERE id=?', [$id]);
    purge_files($files);
    out(true);
});

/* ---------- Peserta kelas ---------- */
route('GET', 'peserta/list', function () {
    $u = need_login();
    $m = matkul_or_404(g_int('matkul_id'));
    guard_access($u, $m);
    $list = rows('SELECT u.id,u.nama,u.nim_nip FROM matkul_peserta p JOIN users u ON u.id=p.user_id WHERE p.matkul_id=? ORDER BY u.nama', [$m['id']]);
    foreach ($list as &$x) $x['id'] = (int)$x['id'];
    $dosen = row('SELECT nama,nim_nip FROM users WHERE id=?', [$m['dosen_id']]);
    out(['dosen' => $dosen, 'mahasiswa' => $list]);
});

route('POST', 'peserta/add', function () {
    need_role('admin');
    $m = matkul_or_404(p_int('matkul_id', true));
    $added = 0; $missing = [];
    $targets = [];
    if (($uid = p_int('user_id')) !== null) {
        $r = row("SELECT id FROM users WHERE id=? AND role='mahasiswa'", [$uid]) ?? fail('Mahasiswa tidak ditemukan.');
        $targets[] = (int)$r['id'];
    }
    $list = p_str('nim_list', 20000);
    if ($list !== null) {
        foreach (preg_split('/[\s,;]+/', $list, -1, PREG_SPLIT_NO_EMPTY) as $nim) {
            $r = row("SELECT id FROM users WHERE nim_nip=? AND role='mahasiswa'", [$nim]);
            if ($r) $targets[] = (int)$r['id']; else $missing[] = $nim;
        }
    }
    if (!$targets && !$missing) fail('Pilih mahasiswa atau isi daftar NIM.');
    foreach (array_unique($targets) as $t) {
        if (!is_enrolled($t, (int)$m['id'])) { run('INSERT INTO matkul_peserta (matkul_id,user_id) VALUES (?,?)', [$m['id'], $t]); $added++; }
    }
    out(['ditambah' => $added, 'tidak_ditemukan' => $missing]);
});

route('POST', 'peserta/remove', function () {
    need_role('admin');
    $m = matkul_or_404(p_int('matkul_id', true));
    $uid = p_int('user_id', true);
    // Data nilai/absensi mahasiswa di kelas ini ikut dibersihkan agar tidak tertinggal.
    $files = [];
    foreach (rows("SELECT g.file FROM pengumpulan g JOIN tugas t ON t.id=g.tugas_id WHERE g.file<>'' AND g.user_id=? AND t.matkul_id=?", [$uid, $m['id']]) as $r) $files[] = ['tugas', $r['file']];
    foreach (rows('SELECT a.bukti FROM absensi a JOIN pertemuan p ON p.id=a.pertemuan_id WHERE a.bukti IS NOT NULL AND a.user_id=? AND p.matkul_id=?', [$uid, $m['id']]) as $r) $files[] = ['absensi', $r['bukti']];
    db()->beginTransaction();
    run('DELETE FROM pengumpulan WHERE user_id=? AND tugas_id IN (SELECT id FROM tugas WHERE matkul_id=?)', [$uid, $m['id']]);
    run('DELETE FROM absensi WHERE user_id=? AND pertemuan_id IN (SELECT id FROM pertemuan WHERE matkul_id=?)', [$uid, $m['id']]);
    run('DELETE FROM hasil_ulangan WHERE user_id=? AND ulangan_id IN (SELECT id FROM ulangan WHERE matkul_id=?)', [$uid, $m['id']]);
    run('DELETE FROM matkul_peserta WHERE matkul_id=? AND user_id=?', [$m['id'], $uid]);
    db()->commit();
    purge_files($files);
    out(true);
});
