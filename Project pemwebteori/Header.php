<?php
/**
 * includes/header.php
 * Membuka halaman: <head>, sidebar, topbar, dan pembuka <main>.
 * Ditutup oleh includes/footer.php.
 *
 * Cara pakai di setiap halaman (sebelum include header):
 *   $judul_halaman = 'Mata Kuliah';   // judul di tab dan topbar
 *   $menu_aktif    = 'matkul';        // dashboard | matkul | pengumuman | jadwal | absen | tugas | ulangan | profil
 *   require_once __DIR__ . '/../includes/header.php';
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* BASE_URL otomatis, misalnya "/spada-mini/". Dipakai di semua link
   supaya jalan dari root maupun dari subfolder (matkul/, jadwal/, dst). */
if (!defined('BASE_URL')) {
    $root_proyek = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $root_web    = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    define('BASE_URL', rtrim(substr($root_proyek, strlen($root_web)), '/') . '/');
}

/* Pengaman: kalau fungsi.php milik anggota 1 belum ada, header tetap jalan */
if (!function_exists('e')) {
    function e($teks) {
        return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
    }
}

$judul_halaman = $judul_halaman ?? 'SPADA Mini';
$menu_aktif    = $menu_aktif ?? '';

$nama     = $_SESSION['nama'] ?? 'Pengguna';
$role     = $_SESSION['role'] ?? 'mahasiswa';
$inisial  = strtoupper(mb_substr($nama, 0, 1));
$nama_role = ['admin' => 'Admin', 'dosen' => 'Dosen', 'mahasiswa' => 'Mahasiswa'][$role] ?? ucfirst($role);

/* kunci => [label, tujuan] */
$menu_utama = [
    'dashboard'  => ['Dashboard',   'dashboard.php'],
    'matkul'     => ['Mata kuliah', 'matkul/index.php'],
    'pengumuman' => ['Pengumuman',  'pengumuman/index.php'],
    'jadwal'     => ['Jadwal',      'jadwal/index.php'],
];
$menu_belajar = [
    'absen'      => ['Absen',       'absen/index.php'],
    'tugas'      => ['Tugas',       'tugas/index.php'],
    'ulangan'    => ['Ulangan',     'ulangan/index.php'],
];
$menu_akun = [
    'profil'     => ['Profil saya', 'profil.php'],
];

function render_menu(array $daftar, string $aktif): void
{
    foreach ($daftar as $kunci => $item) {
        $kelas = ($kunci === $aktif) ? ' class="aktif" aria-current="page"' : '';
        echo '<li><a href="' . e(BASE_URL . $item[1]) . '"' . $kelas . '>' . e($item[0]) . '</a></li>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul_halaman) ?> | SPADA Mini</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/css/responsive.css">
</head>
<body>
<div class="app">

    <!-- ===== Sidebar ===== -->
    <aside class="sidebar" id="sidebar" aria-label="Menu utama">
        <a class="brand" href="<?= e(BASE_URL) ?>dashboard.php">
            <span class="brand-logo">S</span>
            <span>SPADA Mini</span>
        </a>

        <p class="menu-grup">Akademik</p>
        <ul class="menu"><?php render_menu($menu_utama, $menu_aktif); ?></ul>

        <p class="menu-grup">Kegiatan belajar</p>
        <ul class="menu"><?php render_menu($menu_belajar, $menu_aktif); ?></ul>

        <p class="menu-grup">Akun</p>
        <ul class="menu">
            <?php render_menu($menu_akun, $menu_aktif); ?>
            <li><a href="<?= e(BASE_URL) ?>logout.php">Keluar</a></li>
        </ul>

        <div class="sidebar-bawah">Masuk sebagai <?= e($nama_role) ?></div>
    </aside>

    <div class="overlay" id="overlay"></div>

    <!-- ===== Area utama ===== -->
    <div class="main">
        <header class="topbar">
            <div class="topbar-kiri">
                <button type="button" class="btn-menu" id="btnMenu" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">
                    <span></span><span></span><span></span>
                </button>
                <h2 class="topbar-judul"><?= e($judul_halaman) ?></h2>
            </div>
            <div class="user-info">
                <div class="user-teks">
                    <div class="user-nama"><?= e($nama) ?></div>
                    <div class="user-role"><?= e($nama_role) ?></div>
                </div>
                <div class="avatar" aria-hidden="true"><?= e($inisial) ?></div>
            </div>
        </header>

        <main class="konten">
<?php
/* Flash message. Memakai tampil_flash() milik anggota 1 kalau ada.
   Kalau belum ada, baca $_SESSION['flash'] = ['tipe' => 'sukses|error|info', 'pesan' => '...'] */
if (function_exists('tampil_flash')) {
    tampil_flash();
} elseif (!empty($_SESSION['flash']) && is_array($_SESSION['flash'])) {
    $tipe_flash = in_array($_SESSION['flash']['tipe'] ?? '', ['sukses', 'error', 'info'], true)
        ? $_SESSION['flash']['tipe'] : 'info';
    echo '<div class="alert alert-' . e($tipe_flash) . '" data-otomatis role="status">'
       . '<span>' . e($_SESSION['flash']['pesan'] ?? '') . '</span>'
       . '<button type="button" class="alert-tutup" aria-label="Tutup">&times;</button></div>';
    unset($_SESSION['flash']);
}