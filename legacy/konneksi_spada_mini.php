<?php
// config/koneksi.php
// Koneksi MySQLi ke database spada_mini

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';            // XAMPP default kosong. Isi kalau MySQL kamu pakai password.
$db_name = 'spada_mini';

// Tampilkan error MySQLi sebagai exception supaya mudah dilacak saat development
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die('Koneksi database gagal. Pastikan MySQL menyala dan nama database "' . $db_name . '" sudah dibuat.');
}