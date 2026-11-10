<?php
require_once __DIR__ . '/../includes/init.php';
wajib_role('admin');

// hanya boleh lewat POST, supaya tidak terhapus lewat link atau URL yang diketik
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
csrf_cek();

$id = (int) ($_POST['id'] ?? 0);

try {
    $stmt = $conn->prepare("DELETE FROM matkul WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        set_flash('sukses', 'Mata kuliah berhasil dihapus.');
    } else {
        set_flash('error', 'Mata kuliah tidak ditemukan atau sudah dihapus.');
    }
} catch (mysqli_sql_exception $ex) {
    set_flash('error', 'Mata kuliah tidak bisa dihapus karena masih dipakai data lain.');
}

redirect('index.php');