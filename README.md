# SPADA Lite (prototipe LMS)

Buka `index.html` di browser (tidak perlu server).

Akun contoh:
- Mahasiswa : 123210045 / mhs123
- Dosen     : 198402122010121004 / dosen123

## Struktur
- css/style.css            tampilan
- js/data.js               data contoh (mata kuliah, mahasiswa, akun, pertemuan)
- js/helpers.js            helper & state (U = user, V = halaman aktif)
- js/components.js         komponen bersama (head, mats, pickP)
- js/layout.js             render(): sidebar, topbar, pemilih halaman
- js/auth.js               login
- js/dashboard.js          jadwal mingguan
- js/tugas-mahasiswa.js    tugas & submit (mahasiswa)
- js/tugas-dosen.js        pengaturan tugas, materi, nilai (dosen)
- js/presensi-mahasiswa.js hadir / absen / izin + bukti
- js/presensi-dosen.js     ubah keterangan presensi
- js/pengumuman.js         pengumuman
- js/peserta.js            daftar participant
- js/app.js                titik masuk

Catatan: data masih di memori (hilang saat refresh) dan upload file baru mencatat nama file.
