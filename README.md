# SPADA Lite (LMS) — PHP + MySQL

Frontend JavaScript (tampilan sama seperti prototipe) kini terhubung ke backend PHP + MySQL yang mengikuti skema `spada_mini.sql`.

## Cara menjalankan (XAMPP / Laragon)

1. Salin folder `spada-lms` ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Buka phpMyAdmin → buat/import database dengan urutan:
   1. `database/spada_mini.sql` (skema + data awal Anda, tidak diubah)
   2. `database/migrasi.sql` (tambahan kolom/tabel; **jalankan sekali saja**)
3. Cek `api/config.php` (default: host 127.0.0.1, user `root`, tanpa password, database `spada_mini`).
4. Buka `http://localhost/spada-lms/`.

Butuh PHP 8.0+ dengan ekstensi `pdo_mysql`, `fileinfo`, `mbstring` (aktif di XAMPP default) dan MariaDB 10.4+/MySQL 8.

### Akun contoh
Semua akun di `spada_mini.sql` memakai kata sandi **`password123`**:

| Peran | NIM/NIP (atau email) |
|---|---|
| Admin | `ADM001` / admin@spada.test |
| Dosen | `198001001` (Dr. Budi Santoso), `198502002` (Siti Rahmawati) |
| Mahasiswa | `123250001` (Andi), `123250002` (Dewi), `123250003` (Rizky) |

Segera ganti kata sandi lewat menu **Profil Saya** atau **Kelola Pengguna**.

## Yang sudah diselesaikan

| Celah | Penyelesaian |
|---|---|
| Tanpa backend/database | `api/` (PHP + PDO) membaca/menulis ke tabel `spada_mini`. Data tetap ada setelah refresh. |
| Login palsu | Tabel `users`, `password_hash()`/`password_verify()`, session (ID diganti saat login), token CSRF di setiap aksi tulis, pembatas 5 kali gagal. Tidak ada akun/sandi di JavaScript. |
| Pembatasan per peran | Server memeriksa peran **dan** kepemilikan: mahasiswa tidak bisa memakai fitur dosen/admin, dosen hanya mengelola mata kuliah yang diampunya, mahasiswa hanya melihat kelas yang diikutinya. Frontend hanya menyembunyikan menu; keputusan ada di server. |
| Upload file nyata | `move_uploaded_file()` + validasi ekstensi (allowlist), isi file (finfo), ukuran (10 MB; foto 2 MB). File disimpan di `storage/` (diblokir `.htaccess`) dengan nama acak, diunduh lewat `api/index.php?r=file` dengan pengecekan hak akses. |
| CRUD lengkap | Tambah/ubah/**hapus** pertemuan, materi, tugas, pengumuman, ulangan, soal. Admin: kelola pengguna, mata kuliah, jadwal (cek bentrok ruang), dan peserta kelas (satu per satu atau tempel daftar NIM). File di disk ikut dibersihkan saat data dihapus. |

Tambahan agar sesuai tabel di SQL: presensi (`absensi`: hadir/izin/sakit/alpa + bukti), ulangan/kuis dengan penilaian otomatis (`ulangan`, `soal`, `hasil_ulangan`), pengumuman umum & per kelas, profil + foto.

## Penyesuaian skema (`database/migrasi.sql`)
Tabel/kolom yang belum ada di `spada_mini.sql` tetapi dibutuhkan fitur lama:
`materi` (tabel baru), `pertemuan.deskripsi`, `tugas.pertemuan_id/terima_teks/terima_file/dibuka`, `pengumpulan.jawaban`, `absensi.catatan/bukti`, `pengumuman.file`. Tugas contoh di-link ke pertemuan 2.

## Catatan
- **Batas upload PHP**: bawaan XAMPP `upload_max_filesize = 2M`. Naikkan di `php.ini` (`upload_max_filesize`, `post_max_size`) bila perlu 10 MB, lalu restart Apache.
- Untuk produksi: pakai HTTPS, buat user MySQL khusus (jangan `root`), dan pastikan `storage/` tidak terbuka lewat web server selain Apache (Nginx perlu aturan `deny` sendiri).
- Aturan: tugas tidak bisa dikumpulkan setelah batas waktu atau saat ditutup; tugas yang sudah dinilai terkunci; ulangan hanya bisa dikerjakan sekali (dosen bisa mengizinkan ulang).

## Struktur
```
index.html, css/, js/      frontend (helpers.js = klien API; satu file per fitur)
api/index.php              front controller  (?r=modul/aksi)
api/bootstrap.php          DB, sesi, CSRF, validasi, upload
api/routes/*.php           auth, matkul, users(+peserta), akademik, pengumuman, ulangan, file
api/config.php             konfigurasi database
database/                  spada_mini.sql + migrasi.sql
storage/                   hasil upload (tidak bisa diakses langsung)
```
