-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 12 Nov 2026 pada 10.15
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `spada_mini`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `pertemuan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('hadir','izin','sakit','alpa') NOT NULL DEFAULT 'hadir',
  `waktu` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `hasil_ulangan`
--

CREATE TABLE `hasil_ulangan` (
  `id` int(11) NOT NULL,
  `ulangan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nilai` decimal(5,2) NOT NULL,
  `selesai_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal`
--

CREATE TABLE `jadwal` (
  `id` int(11) NOT NULL,
  `matkul_id` int(11) NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `ruang` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `jadwal`
--

INSERT INTO `jadwal` (`id`, `matkul_id`, `hari`, `jam_mulai`, `jam_selesai`, `ruang`) VALUES
(1, 1, 'Senin', '08:00:00', '10:30:00', 'Lab Komputer 1'),
(2, 2, 'Selasa', '10:00:00', '12:30:00', 'Ruang 204'),
(3, 3, 'Rabu', '13:00:00', '15:30:00', 'Ruang 301'),
(4, 4, 'Kamis', '08:00:00', '09:40:00', 'Ruang 105'),
(5, 1, 'Jumat', '13:00:00', '14:40:00', 'Lab Komputer 2');

-- --------------------------------------------------------

--
-- Struktur dari tabel `matkul`
--

CREATE TABLE `matkul` (
  `id` int(11) NOT NULL,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `sks` tinyint(4) NOT NULL DEFAULT 3,
  `deskripsi` text DEFAULT NULL,
  `dosen_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `matkul`
--

INSERT INTO `matkul` (`id`, `kode`, `nama`, `sks`, `deskripsi`, `dosen_id`, `created_at`) VALUES
(1, 'IF201', 'Pemrograman Web', 3, 'HTML, CSS, JavaScript, PHP, dan MySQL untuk membangun aplikasi web.', 2, '2026-11-10 06:31:05'),
(2, 'IF202', 'Basis Data', 3, 'Perancangan ERD, normalisasi, dan query SQL.', 2, '2026-11-10 06:31:05'),
(3, 'IF203', 'Pemrograman Berorientasi Objek', 3, 'Konsep class, object, inheritance, dan polymorphism.', 3, '2026-11-10 06:31:05'),
(4, 'IF204', 'Jaringan Komputer', 2, 'Dasar jaringan, model OSI, dan protokol TCP/IP.', 3, '2026-11-10 06:31:05');

-- --------------------------------------------------------

--
-- Struktur dari tabel `matkul_peserta`
--

CREATE TABLE `matkul_peserta` (
  `id` int(11) NOT NULL,
  `matkul_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `matkul_peserta`
--

INSERT INTO `matkul_peserta` (`id`, `matkul_id`, `user_id`) VALUES
(1, 1, 4),
(2, 1, 5),
(3, 1, 6),
(4, 2, 4),
(5, 2, 5),
(6, 3, 5),
(7, 3, 6),
(8, 4, 4),
(9, 4, 6);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengumpulan`
--

CREATE TABLE `pengumpulan` (
  `id` int(11) NOT NULL,
  `tugas_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `nilai` tinyint(3) UNSIGNED DEFAULT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  `dikumpul_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengumuman`
--

CREATE TABLE `pengumuman` (
  `id` int(11) NOT NULL,
  `matkul_id` int(11) DEFAULT NULL,
  `judul` varchar(150) NOT NULL,
  `isi` text NOT NULL,
  `penulis_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pengumuman`
--

INSERT INTO `pengumuman` (`id`, `matkul_id`, `judul`, `isi`, `penulis_id`, `created_at`) VALUES
(1, NULL, 'Selamat Datang di SPADA Mini', 'Silakan lengkapi profil kalian dan cek jadwal kuliah minggu ini.', 1, '2026-11-10 06:31:05'),
(2, 1, 'Tugas Kelompok Web', 'Tugas kelompok dikumpulkan paling lambat akhir bulan. Tema bebas sesuai arahan di kelas.', 2, '2026-11-10 06:31:05'),
(3, 2, 'Kuis Normalisasi', 'Kuis singkat tentang normalisasi akan dilaksanakan pada pertemuan berikutnya.', 2, '2026-11-10 06:31:05'),
(4, 3, 'Materi Pertemuan 5', 'Slide materi inheritance sudah tersedia. Harap dibaca sebelum kelas.', 3, '2026-11-10 06:31:05');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pertemuan`
--

CREATE TABLE `pertemuan` (
  `id` int(11) NOT NULL,
  `matkul_id` int(11) NOT NULL,
  `pertemuan_ke` tinyint(4) NOT NULL,
  `tanggal` date NOT NULL,
  `topik` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pertemuan`
--

INSERT INTO `pertemuan` (`id`, `matkul_id`, `pertemuan_ke`, `tanggal`, `topik`) VALUES
(1, 1, 1, '2026-09-07', 'Pengantar Web'),
(2, 1, 2, '2026-09-14', 'HTML dan CSS');

-- --------------------------------------------------------

--
-- Struktur dari tabel `soal`
--

CREATE TABLE `soal` (
  `id` int(11) NOT NULL,
  `ulangan_id` int(11) NOT NULL,
  `pertanyaan` text NOT NULL,
  `opsi_a` varchar(255) NOT NULL,
  `opsi_b` varchar(255) NOT NULL,
  `opsi_c` varchar(255) NOT NULL,
  `opsi_d` varchar(255) NOT NULL,
  `jawaban_benar` enum('a','b','c','d') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `soal`
--

INSERT INTO `soal` (`id`, `ulangan_id`, `pertanyaan`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `jawaban_benar`) VALUES
(1, 1, 'Tag untuk membuat tautan adalah...', '<a>', '<link>', '<href>', '<url>', 'a'),
(2, 1, 'Properti CSS untuk mengubah warna teks adalah...', 'font-color', 'text-color', 'color', 'foreground', 'c');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tugas`
--

CREATE TABLE `tugas` (
  `id` int(11) NOT NULL,
  `matkul_id` int(11) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `deadline` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `tugas`
--

INSERT INTO `tugas` (`id`, `matkul_id`, `judul`, `deskripsi`, `deadline`) VALUES
(1, 1, 'Tugas 1: Halaman Profil', 'Buat halaman profil pribadi memakai HTML dan CSS.', '2026-12-01 23:59:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ulangan`
--

CREATE TABLE `ulangan` (
  `id` int(11) NOT NULL,
  `matkul_id` int(11) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `durasi_menit` smallint(6) NOT NULL DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `ulangan`
--

INSERT INTO `ulangan` (`id`, `matkul_id`, `judul`, `durasi_menit`) VALUES
(1, 1, 'Kuis 1 HTML Dasar', 15);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nim_nip` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','dosen','mahasiswa') NOT NULL DEFAULT 'mahasiswa',
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `nim_nip`, `password`, `role`, `foto`, `created_at`) VALUES
(1, 'Administrator', 'admin@spada.test', 'ADM001', '$2y$10$XQBbhQpblPbJFOJc81ISSOg3QU8wl37Pj7LQ3BMbofo2bWlWxlGGu', 'admin', NULL, '2026-11-10 06:31:05'),
(2, 'Dr. Budi Santoso', 'budi@spada.test', '198001001', '$2y$10$XQBbhQpblPbJFOJc81ISSOg3QU8wl37Pj7LQ3BMbofo2bWlWxlGGu', 'dosen', NULL, '2026-11-10 06:31:05'),
(3, 'Siti Rahmawati, M.Kom', 'siti@spada.test', '198502002', '$2y$10$XQBbhQpblPbJFOJc81ISSOg3QU8wl37Pj7LQ3BMbofo2bWlWxlGGu', 'dosen', NULL, '2026-11-10 06:31:05'),
(4, 'Andi Pratama', 'andi@spada.test', '123250001', '$2y$10$XQBbhQpblPbJFOJc81ISSOg3QU8wl37Pj7LQ3BMbofo2bWlWxlGGu', 'mahasiswa', NULL, '2026-11-10 06:31:05'),
(5, 'Dewi Lestari', 'dewi@spada.test', '123250002', '$2y$10$XQBbhQpblPbJFOJc81ISSOg3QU8wl37Pj7LQ3BMbofo2bWlWxlGGu', 'mahasiswa', NULL, '2026-11-10 06:31:05'),
(6, 'Rizky Maulana', 'rizky@spada.test', '123250003', '$2y$10$XQBbhQpblPbJFOJc81ISSOg3QU8wl37Pj7LQ3BMbofo2bWlWxlGGu', 'mahasiswa', NULL, '2026-11-10 06:31:05');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_absen` (`pertemuan_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `hasil_ulangan`
--
ALTER TABLE `hasil_ulangan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_hasil` (`ulangan_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `matkul_id` (`matkul_id`);

--
-- Indeks untuk tabel `matkul`
--
ALTER TABLE `matkul`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`),
  ADD KEY `dosen_id` (`dosen_id`);

--
-- Indeks untuk tabel `matkul_peserta`
--
ALTER TABLE `matkul_peserta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_peserta` (`matkul_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `pengumpulan`
--
ALTER TABLE `pengumpulan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_kumpul` (`tugas_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `matkul_id` (`matkul_id`),
  ADD KEY `penulis_id` (`penulis_id`);

--
-- Indeks untuk tabel `pertemuan`
--
ALTER TABLE `pertemuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `matkul_id` (`matkul_id`);

--
-- Indeks untuk tabel `soal`
--
ALTER TABLE `soal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ulangan_id` (`ulangan_id`);

--
-- Indeks untuk tabel `tugas`
--
ALTER TABLE `tugas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `matkul_id` (`matkul_id`);

--
-- Indeks untuk tabel `ulangan`
--
ALTER TABLE `ulangan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `matkul_id` (`matkul_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `nim_nip` (`nim_nip`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `hasil_ulangan`
--
ALTER TABLE `hasil_ulangan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `matkul`
--
ALTER TABLE `matkul`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `matkul_peserta`
--
ALTER TABLE `matkul_peserta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `pengumpulan`
--
ALTER TABLE `pengumpulan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `pertemuan`
--
ALTER TABLE `pertemuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `soal`
--
ALTER TABLE `soal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `tugas`
--
ALTER TABLE `tugas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `ulangan`
--
ALTER TABLE `ulangan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`pertemuan_id`) REFERENCES `pertemuan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `absensi_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `hasil_ulangan`
--
ALTER TABLE `hasil_ulangan`
  ADD CONSTRAINT `hasil_ulangan_ibfk_1` FOREIGN KEY (`ulangan_id`) REFERENCES `ulangan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hasil_ulangan_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`matkul_id`) REFERENCES `matkul` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `matkul`
--
ALTER TABLE `matkul`
  ADD CONSTRAINT `matkul_ibfk_1` FOREIGN KEY (`dosen_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `matkul_peserta`
--
ALTER TABLE `matkul_peserta`
  ADD CONSTRAINT `matkul_peserta_ibfk_1` FOREIGN KEY (`matkul_id`) REFERENCES `matkul` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matkul_peserta_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengumpulan`
--
ALTER TABLE `pengumpulan`
  ADD CONSTRAINT `pengumpulan_ibfk_1` FOREIGN KEY (`tugas_id`) REFERENCES `tugas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengumpulan_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD CONSTRAINT `pengumuman_ibfk_1` FOREIGN KEY (`matkul_id`) REFERENCES `matkul` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengumuman_ibfk_2` FOREIGN KEY (`penulis_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pertemuan`
--
ALTER TABLE `pertemuan`
  ADD CONSTRAINT `pertemuan_ibfk_1` FOREIGN KEY (`matkul_id`) REFERENCES `matkul` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `soal`
--
ALTER TABLE `soal`
  ADD CONSTRAINT `soal_ibfk_1` FOREIGN KEY (`ulangan_id`) REFERENCES `ulangan` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tugas`
--
ALTER TABLE `tugas`
  ADD CONSTRAINT `tugas_ibfk_1` FOREIGN KEY (`matkul_id`) REFERENCES `matkul` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `ulangan`
--
ALTER TABLE `ulangan`
  ADD CONSTRAINT `ulangan_ibfk_1` FOREIGN KEY (`matkul_id`) REFERENCES `matkul` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
