<?php
// Konfigurasi SPADA LMS. Default ini cocok untuk XAMPP/Laragon (user root tanpa password).
// Di server sungguhan: ganti user/pass, dan JANGAN pakai root.
return [
    'dsn'           => 'mysql:host=127.0.0.1;dbname=spada_mini;charset=utf8mb4',
    'user'          => 'root',
    'pass'          => '',
    'init'          => ["SET time_zone = '+07:00'"],   // zona waktu sesi MySQL = WIB
    'timezone'      => 'Asia/Jakarta',
    'max_upload_mb' => 10,                              // batas aplikasi (php.ini juga membatasi)
    'storage'       => __DIR__ . '/../storage',         // di luar akses web langsung (lihat storage/.htaccess)
    'debug'         => false,                           // true = tampilkan pesan error asli di JSON
];
