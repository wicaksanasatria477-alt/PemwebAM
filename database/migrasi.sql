-- ============================================================
-- SPADA LMS - migrasi tambahan di atas spada_mini.sql
-- Jalankan SEKALI, SETELAH spada_mini.sql di-import (phpMyAdmin > tab SQL / Import).
-- Tidak mengubah data yang sudah ada.
-- ============================================================
USE `spada_mini`;

-- 1) Pertemuan: deskripsi singkat (tampil di halaman kelas)
ALTER TABLE `pertemuan`
  ADD COLUMN IF NOT EXISTS `deskripsi` text DEFAULT NULL AFTER `topik`;

-- 2) Materi: file materi per pertemuan (sebelumnya tidak ada tabelnya)
CREATE TABLE IF NOT EXISTS `materi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `matkul_id` int(11) NOT NULL,
  `pertemuan_id` int(11) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `file` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `matkul_id` (`matkul_id`),
  KEY `pertemuan_id` (`pertemuan_id`),
  CONSTRAINT `materi_ibfk_1` FOREIGN KEY (`matkul_id`) REFERENCES `matkul` (`id`) ON DELETE CASCADE,
  CONSTRAINT `materi_ibfk_2` FOREIGN KEY (`pertemuan_id`) REFERENCES `pertemuan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Tugas: terkait pertemuan + opsi jenis jawaban + buka/tutup pengumpulan
ALTER TABLE `tugas`
  ADD COLUMN IF NOT EXISTS `pertemuan_id` int(11) DEFAULT NULL AFTER `matkul_id`,
  ADD COLUMN IF NOT EXISTS `terima_teks` tinyint(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `terima_file` tinyint(1) NOT NULL DEFAULT 1,
  ADD COLUMN IF NOT EXISTS `dibuka` tinyint(1) NOT NULL DEFAULT 1,
  ADD KEY `pertemuan_id` (`pertemuan_id`),
  ADD CONSTRAINT `tugas_ibfk_2` FOREIGN KEY (`pertemuan_id`) REFERENCES `pertemuan` (`id`) ON DELETE CASCADE;

-- Tugas contoh dikaitkan ke pertemuan "HTML dan CSS"
UPDATE `tugas` SET `pertemuan_id` = 2 WHERE `id` = 1 AND `pertemuan_id` IS NULL;

-- 4) Pengumpulan: jawaban teks (kolom `catatan` dipakai untuk komentar dosen)
ALTER TABLE `pengumpulan`
  ADD COLUMN IF NOT EXISTS `jawaban` text DEFAULT NULL AFTER `file`;

-- 5) Absensi: catatan alasan + bukti izin/sakit
ALTER TABLE `absensi`
  ADD COLUMN IF NOT EXISTS `catatan` varchar(255) DEFAULT NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `bukti` varchar(255) DEFAULT NULL AFTER `catatan`;

-- 6) Pengumuman: lampiran opsional
ALTER TABLE `pengumuman`
  ADD COLUMN IF NOT EXISTS `file` varchar(255) DEFAULT NULL AFTER `isi`;
