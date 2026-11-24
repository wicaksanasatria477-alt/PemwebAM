<?php
defined('SPADA') or exit;

/**
 * Unduh file upload: api/index.php?r=file&t=<materi|tugas|absensi|pengumuman|foto>&id=<id>
 * Hak akses diperiksa per jenis file; file disimpan di storage/ yang tidak bisa diakses langsung.
 */
route('GET', 'file', function () {
    $u = need_login();
    $t = (string)($_GET['t'] ?? '');
    $id = g_int('id');
    $stored = null;

    switch ($t) {
        case 'materi':
            $r = row('SELECT * FROM materi WHERE id=?', [$id]) ?? fail('File tidak ditemukan.', 404);
            guard_access($u, matkul_or_404((int)$r['matkul_id']));
            $stored = $r['file']; break;
        case 'tugas':
            $r = row('SELECT g.file,g.user_id,t.matkul_id FROM pengumpulan g JOIN tugas t ON t.id=g.tugas_id WHERE g.id=?', [$id]) ?? fail('File tidak ditemukan.', 404);
            if ((int)$r['user_id'] !== (int)$u['id']) guard_manage($u, matkul_or_404((int)$r['matkul_id']));
            $stored = $r['file']; break;
        case 'absensi':
            $r = row('SELECT a.bukti,a.user_id,p.matkul_id FROM absensi a JOIN pertemuan p ON p.id=a.pertemuan_id WHERE a.id=?', [$id]) ?? fail('File tidak ditemukan.', 404);
            if ((int)$r['user_id'] !== (int)$u['id']) guard_manage($u, matkul_or_404((int)$r['matkul_id']));
            $stored = $r['bukti']; break;
        case 'pengumuman':
            $r = row('SELECT file,matkul_id FROM pengumuman WHERE id=?', [$id]) ?? fail('File tidak ditemukan.', 404);
            if ($r['matkul_id'] !== null) guard_access($u, matkul_or_404((int)$r['matkul_id']));
            $stored = $r['file']; break;
        case 'foto':   // foto profil boleh dilihat semua pengguna yang sudah masuk
            $r = row('SELECT foto FROM users WHERE id=?', [$id]) ?? fail('File tidak ditemukan.', 404);
            $stored = $r['foto']; break;
        default: fail('Jenis file tidak dikenal.', 404);
    }
    if (!$stored || basename($stored) !== $stored) fail('File tidak ditemukan.', 404);
    $path = storage_dir($t) . '/' . $stored;
    if (!is_file($path)) fail('File tidak ada di server.', 404);

    $ext = strtolower(pathinfo($stored, PATHINFO_EXTENSION));
    $inline = ($t === 'foto');                         // hanya foto profil yang ditampilkan langsung
    $name = str_replace(['"', '\\', "\r", "\n"], '_', file_label($stored));
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: ' . ($inline ? (DOWNLOAD_MIME[$ext] ?? 'application/octet-stream') : 'application/octet-stream'));
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $name . '"');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header("Content-Security-Policy: default-src 'none'; sandbox");
    readfile($path);
    exit;
});
