<?php
/**
 * includes/init.php
 * Dimuat PALING AWAL di setiap halaman (sebelum header.php):
 *   require_once __DIR__ . '/../includes/init.php';
 *
 * Isi: session, BASE_URL, koneksi database, dan helper dasar.
 * Semua fungsi dibungkus function_exists(), jadi kalau anggota 1 sudah
 * punya fungsi dengan nama yang sama di fungsi.php, versi mereka yang dipakai.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('BASE_URL')) {
    $root_proyek = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $root_web    = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    define('BASE_URL', rtrim(substr($root_proyek, strlen($root_web)), '/') . '/');
}

require_once __DIR__ . '/../config/koneksi.php';

// helper milik anggota 1 (kalau sudah ada)
if (is_file(__DIR__ . '/fungsi.php')) {
    require_once __DIR__ . '/fungsi.php';
}

/* ---------- Helper dasar (fallback) ---------- */
if (!function_exists('e')) {
    function e($teks)
    {
        return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('set_flash')) {
    /** $tipe: sukses | error | info. Ditampilkan sekali oleh header.php */
    function set_flash($tipe, $pesan)
    {
        $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
    }
}

/* ---------- Login dan role ---------- */
if (!function_exists('wajib_login')) {
    function wajib_login()
    {
        if (empty($_SESSION['user_id'])) {
            redirect(BASE_URL . 'login.php');
        }
    }
}

if (!function_exists('wajib_role')) {
    /** wajib_role('admin') atau wajib_role(['admin', 'dosen']) */
    function wajib_role($izin)
    {
        wajib_login();
        if (!in_array($_SESSION['role'] ?? '', (array) $izin, true)) {
            set_flash('error', 'Kamu tidak punya akses ke halaman itu.');
            redirect(BASE_URL . 'dashboard.php');
        }
    }
}

/* ---------- CSRF untuk form POST ---------- */
if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }
}

if (!function_exists('csrf_cek')) {
    function csrf_cek()
    {
        $kirim = $_POST['csrf'] ?? '';
        if (!hash_equals($_SESSION['csrf'] ?? '', $kirim)) {
            set_flash('error', 'Sesi formulir tidak valid. Silakan coba lagi.');
            redirect(BASE_URL . 'dashboard.php');
        }
    }
}

/* ---------- Format tanggal Indonesia: 10 November 2026 ---------- */
if (!function_exists('tanggal_id')) {
    function tanggal_id($waktu)
    {
        $t = strtotime((string) $waktu);
        if (!$t) {
            return '';
        }
        $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return date('j', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);
    }
}