<?php
defined('SPADA') or exit;

/* Status sesi + token CSRF (dipanggil frontend saat halaman dibuka). */
route('GET', 'auth/me', function () {
    $u = me();
    out(['user' => $u ? public_user($u) : null, 'csrf' => csrf_token()]);
});

route('POST', 'auth/login', function () {
    $id = p_str('id', 100, true);
    $pw = (string)(input()['password'] ?? '');
    if ($pw === '') fail('Kata sandi wajib diisi.');

    // Pembatas sederhana: 5 kali gagal berturut-turut => tunggu 60 detik.
    $lock = $_SESSION['lock_until'] ?? 0;
    if ($lock > time()) fail('Terlalu banyak percobaan. Coba lagi dalam ' . ($lock - time()) . ' detik.', 429);

    $u = row('SELECT * FROM users WHERE nim_nip=? OR email=?', [$id, $id]);
    if (!$u || !password_verify($pw, $u['password'])) {
        $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
        if ($_SESSION['fails'] >= 5) { $_SESSION['lock_until'] = time() + 60; $_SESSION['fails'] = 0; }
        usleep(400000);
        fail('NIM/NIP atau kata sandi salah.', 401);
    }
    if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {
        run('UPDATE users SET password=? WHERE id=?', [password_hash($pw, PASSWORD_DEFAULT), $u['id']]);
    }
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    $_SESSION['fails'] = 0; unset($_SESSION['lock_until']);
    $_SESSION['csrf'] = bin2hex(random_bytes(24));
    out(['user' => public_user($u), 'csrf' => $_SESSION['csrf']]);
});

route('POST', 'auth/logout', function () {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $c = session_get_cookie_params();
        setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $c['path'], 'httponly' => true, 'samesite' => 'Lax']);
    }
    session_destroy();
    out(true);
});

/* Profil sendiri: nama, email, ganti kata sandi, foto. */
route('POST', 'profil/update', function () {
    $u = need_login();
    $nama  = p_str('nama', 100, true);
    $email = p_str('email', 100, true);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Format email tidak valid.');
    if (row('SELECT 1 FROM users WHERE email=? AND id<>?', [$email, $u['id']])) fail('Email sudah dipakai pengguna lain.');

    $baru = (string)(input()['password_baru'] ?? '');
    if ($baru !== '') {
        $lama = (string)(input()['password_lama'] ?? '');
        $cur = row('SELECT password FROM users WHERE id=?', [$u['id']]);
        if (!password_verify($lama, $cur['password'])) fail('Kata sandi lama salah.');
        if (strlen($baru) < 6) fail('Kata sandi baru minimal 6 karakter.');
        run('UPDATE users SET nama=?, email=?, password=? WHERE id=?', [$nama, $email, password_hash($baru, PASSWORD_DEFAULT), $u['id']]);
    } else {
        run('UPDATE users SET nama=?, email=? WHERE id=?', [$nama, $email, $u['id']]);
    }
    out(public_user(row('SELECT id,nama,email,nim_nip,role,foto FROM users WHERE id=?', [$u['id']])));
});

route('POST', 'profil/foto', function () {
    $u = need_login();
    $new = save_upload('foto', 'foto', true, 2, true);
    run('UPDATE users SET foto=? WHERE id=?', [$new, $u['id']]);
    delete_file('foto', $u['foto']);
    out(['has_foto' => true]);
});

route('POST', 'profil/foto_hapus', function () {
    $u = need_login();
    run('UPDATE users SET foto=NULL WHERE id=?', [$u['id']]);
    delete_file('foto', $u['foto']);
    out(['has_foto' => false]);
});
