# SPADA Lite

SPADA Lite adalah aplikasi pembelajaran berbasis web dengan frontend JavaScript, backend PHP menggunakan PDO, dan database MySQL/MariaDB.

## Struktur repository

| Folder/file | Keterangan |
|---|---|
| index.html, css/, js/, api/, database/, storage/ | Versi utama SPADA Lite. Frontend, backend PHP, skema database, dan penyimpanan berkas. |
| legacy/ | Arsip prototipe frontend lama. Dapat dibuka melalui legacy/index.html; bukan versi utama. |
| pemweb-teori/ | Tidak tersedia pada branch ini. Folder tersebut merupakan proyek PHP terpisah dan belum dipulihkan. |

## Menjalankan aplikasi utama

1. Salin folder repository ke direktori web server, misalnya htdocs/NAMA_FOLDER pada XAMPP atau www/NAMA_FOLDER pada Laragon.
2. Buat database spada_mini melalui phpMyAdmin atau MySQL.
3. Import database/spada_mini.sql terlebih dahulu.
4. Setelah itu, import database/migrasi.sql. Migrasi ini dijalankan **satu kali saja**.
5. Periksa api/config.php dan sesuaikan host, nama database, username, serta password MySQL.
6. Jalankan Apache dan MySQL, kemudian buka http://localhost/NAMA_FOLDER/.

Aplikasi membutuhkan PHP 8.0 atau lebih baru dengan ekstensi pdo_mysql, fileinfo, dan mbstring, serta MariaDB 10.4+ atau MySQL 8.

## Akun contoh

Seluruh akun contoh menggunakan kata sandi password123.

| Peran | Username / email | Nama |
|---|---|---|
| Admin | ADM001 atau admin@spada.test | Admin |
| Dosen | 198001001 | Dr. Budi Santoso |
| Dosen | 198502002 | Siti Rahmawati |
| Mahasiswa | 123250001 | Andi |
| Mahasiswa | 123250002 | Dewi |
| Mahasiswa | 123250003 | Rizky |

Ganti kata sandi contoh setelah login melalui menu **Profil Saya** atau **Kelola Pengguna**.

## Catatan

- database/spada_mini.sql berisi skema dan data awal.
- database/migrasi.sql menambahkan tabel atau kolom yang dibutuhkan fitur aplikasi. Jangan menjalankannya berulang kali.
- Berkas unggahan disimpan di storage/. Aturan .htaccess membatasi akses langsung ke direktori tersebut.
- Konfigurasi database berada di api/config.php. Untuk penggunaan selain lokal, jangan memakai akun database bawaan root tanpa password.
