<?php
require_once __DIR__ . '/../includes/init.php';
wajib_login();

$role = $_SESSION['role'];
$uid  = (int) $_SESSION['user_id'];

/* ---------- Matkul yang boleh dilihat user ini (untuk filter dan hak akses) ---------- */
if ($role === 'admin') {
    $matkul_terlihat = $conn->query("SELECT id, nama FROM matkul ORDER BY nama")->fetch_all(MYSQLI_ASSOC);
} else {
    $sql = ($role === 'dosen')
        ? "SELECT id, nama FROM matkul WHERE dosen_id = ? ORDER BY nama"
        : "SELECT m.id, m.nama FROM matkul m
           JOIN matkul_peserta p ON p.matkul_id = m.id
           WHERE p.user_id = ? ORDER BY m.nama";
    $s = $conn->prepare($sql);
    $s->bind_param('i', $uid);
    $s->execute();
    $matkul_terlihat = $s->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* ---------- Susun query pengumuman ---------- */
$where = [];
$tipe  = '';
$param = [];

if ($role !== 'admin') {
    // pengumuman umum + pengumuman matkul milik user
    $ids = array_map('intval', array_column($matkul_terlihat, 'id'));
    $in  = $ids ? implode(',', $ids) : '0';
    $where[] = "(p.matkul_id IS NULL OR p.matkul_id IN ($in))";
}

$filter = $_GET['matkul'] ?? '';
if ($filter === 'umum') {
    $where[] = "p.matkul_id IS NULL";
} elseif ((int) $filter > 0) {
    $where[] = "p.matkul_id = ?";
    $tipe   .= 'i';
    $param[] = (int) $filter;
}

$sql = "SELECT p.id, p.judul, p.isi, p.created_at, p.penulis_id, p.matkul_id,
               u.nama AS penulis, m.nama AS nama_matkul
        FROM pengumuman p
        JOIN users u ON u.id = p.penulis_id
        LEFT JOIN matkul m ON m.id = p.matkul_id"
     . ($where ? " WHERE " . implode(' AND ', $where) : "")
     . " ORDER BY p.created_at DESC, p.id DESC LIMIT 50";

$stmt = $conn->prepare($sql);
if ($param) {
    $stmt->bind_param($tipe, ...$param);
}
$stmt->execute();
$daftar = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$boleh_tulis = in_array($role, ['admin', 'dosen'], true);

$judul_halaman = 'Pengumuman';
$menu_aktif    = 'pengumuman';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Pengumuman</h1>
        <p>Informasi terbaru dari dosen dan admin.</p>
    </div>
    <?php if ($boleh_tulis): ?>
        <a class="btn" href="form.php">Tambah pengumuman</a>
    <?php endif; ?>
</div>

<form method="get" class="bar-cari" role="search">
    <select name="matkul" aria-label="Filter pengumuman">
        <option value="">Semua pengumuman</option>
        <option value="umum" <?= $filter === 'umum' ? 'selected' : '' ?>>Pengumuman umum</option>
        <?php foreach ($matkul_terlihat as $mk): ?>
            <option value="<?= (int) $mk['id'] ?>" <?= (string) $filter === (string) $mk['id'] ? 'selected' : '' ?>>
                <?= e($mk['nama']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Terapkan</button>
    <?php if ($filter !== ''): ?>
        <a class="btn btn-garis" href="index.php">Reset</a>
    <?php endif; ?>
</form>

<?php if (!$daftar): ?>
    <div class="kosong">
        <p>Belum ada pengumuman<?= $filter !== '' ? ' untuk pilihan ini' : '' ?>.</p>
        <?php if ($boleh_tulis): ?>
            <a class="btn" href="form.php">Tulis pengumuman</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="daftar-pengumuman">
        <?php foreach ($daftar as $p): ?>
            <?php $boleh_ubah = ($role === 'admin') || ($role === 'dosen' && (int) $p['penulis_id'] === $uid); ?>
            <article class="pengumuman-item">
                <h2><?= e($p['judul']) ?></h2>
                <p class="meta">
                    Oleh <?= e($p['penulis']) ?>, <?= e(tanggal_id($p['created_at'])) ?>
                    <?php if ($p['nama_matkul']): ?>
                        <span class="badge"><?= e($p['nama_matkul']) ?></span>
                    <?php else: ?>
                        <span class="badge badge-kuning">Umum</span>
                    <?php endif; ?>
                </p>
                <p><?= nl2br(e($p['isi'])) ?></p>

                <?php if ($boleh_ubah): ?>
                    <div class="form-aksi mt-2">
                        <a class="btn btn-kecil btn-garis" href="form.php?id=<?= (int) $p['id'] ?>">Edit</a>
                        <form method="post" action="hapus.php" class="inline-form"
                              data-konfirmasi="Hapus pengumuman &quot;<?= e($p['judul']) ?>&quot;?">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <button type="submit" class="btn btn-kecil btn-bahaya">Hapus</button>
                        </form>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
