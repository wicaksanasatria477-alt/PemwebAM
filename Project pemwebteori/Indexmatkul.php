<?php
require_once __DIR__ . '/../includes/init.php';
wajib_login();

$role = $_SESSION['role'];
$uid  = (int) $_SESSION['user_id'];
$q    = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';

$stmt = $conn->prepare(
    "SELECT m.id, m.kode, m.nama, m.sks, m.dosen_id, u.nama AS dosen,
            (SELECT COUNT(*) FROM matkul_peserta p WHERE p.matkul_id = m.id) AS jml_peserta,
            (SELECT COUNT(*) FROM matkul_peserta p WHERE p.matkul_id = m.id AND p.user_id = ?) AS terdaftar
     FROM matkul m
     JOIN users u ON u.id = m.dosen_id
     WHERE m.nama LIKE ? OR m.kode LIKE ?
     ORDER BY m.nama"
);
$stmt->bind_param('iss', $uid, $like, $like);
$stmt->execute();
$daftar = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$judul_halaman = 'Mata kuliah';
$menu_aktif    = 'matkul';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Mata kuliah</h1>
        <p><?= count($daftar) ?> mata kuliah<?= $q !== '' ? ' cocok dengan pencarianmu' : ' tersedia semester ini' ?>.</p>
    </div>
    <?php if ($role === 'admin'): ?>
        <a class="btn" href="form.php">Tambah mata kuliah</a>
    <?php endif; ?>
</div>

<form method="get" class="bar-cari" role="search">
    <input type="search" class="input" name="q" value="<?= e($q) ?>"
           placeholder="Cari nama atau kode mata kuliah" aria-label="Cari mata kuliah">
    <button type="submit" class="btn">Cari</button>
    <?php if ($q !== ''): ?>
        <a class="btn btn-garis" href="index.php">Reset</a>
    <?php endif; ?>
</form>

<?php if (!$daftar): ?>
    <div class="kosong">
        <p>Belum ada mata kuliah<?= $q !== '' ? ' dengan kata kunci "' . e($q) . '"' : '' ?>.</p>
        <?php if ($role === 'admin' && $q === ''): ?>
            <a class="btn" href="form.php">Tambah mata kuliah pertama</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="grid-kartu">
        <?php foreach ($daftar as $m): ?>
            <article class="kartu-matkul">
                <div class="pita"><?= e($m['kode']) ?></div>
                <div class="isi">
                    <h3><?= e($m['nama']) ?></h3>
                    <p class="meta"><?= e($m['dosen']) ?></p>
                    <p class="meta"><?= (int) $m['sks'] ?> SKS, <?= (int) $m['jml_peserta'] ?> peserta</p>

                    <?php if ($role === 'mahasiswa' && $m['terdaftar']): ?>
                        <p><span class="badge badge-hijau">Terdaftar</span></p>
                    <?php elseif ($role === 'dosen' && (int) $m['dosen_id'] === $uid): ?>
                        <p><span class="badge">Kamu pengampu</span></p>
                    <?php endif; ?>

                    <div class="aksi">
                        <a class="btn btn-kecil" href="detail.php?id=<?= (int) $m['id'] ?>">Lihat detail</a>
                        <?php if ($role === 'admin'): ?>
                            <a class="btn btn-kecil btn-garis" href="form.php?id=<?= (int) $m['id'] ?>">Edit</a>
                            <form method="post" action="hapus.php" class="inline-form"
                                  data-konfirmasi="Hapus <?= e($m['nama']) ?>? Jadwal, tugas, dan data lain yang terkait ikut terhapus.">
                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                <button type="submit" class="btn btn-kecil btn-bahaya">Hapus</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>