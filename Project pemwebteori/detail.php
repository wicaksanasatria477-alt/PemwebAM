<?php
require_once __DIR__ . '/../includes/init.php';
wajib_login();

$role = $_SESSION['role'];
$uid  = (int) $_SESSION['user_id'];
$id   = (int) ($_GET['id'] ?? 0);

/* ---------- Data matkul + dosen pengampu ---------- */
$stmt = $conn->prepare(
    "SELECT m.*, u.nama AS dosen, u.email AS email_dosen, u.nim_nip AS nip_dosen
     FROM matkul m
     JOIN users u ON u.id = m.dosen_id
     WHERE m.id = ?"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$m = $stmt->get_result()->fetch_assoc();

if (!$m) {
    set_flash('error', 'Mata kuliah tidak ditemukan.');
    redirect('index.php');
}

$boleh_kelola = ($role === 'admin') || ($role === 'dosen' && (int) $m['dosen_id'] === $uid);

/* ---------- Aksi POST: gabung, keluar, keluarkan peserta ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'gabung' && $role === 'mahasiswa') {
        $s = $conn->prepare("INSERT IGNORE INTO matkul_peserta (matkul_id, user_id) VALUES (?, ?)");
        $s->bind_param('ii', $id, $uid);
        $s->execute();
        set_flash('sukses', 'Kamu sekarang terdaftar di ' . $m['nama'] . '.');

    } elseif ($aksi === 'keluar' && $role === 'mahasiswa') {
        $s = $conn->prepare("DELETE FROM matkul_peserta WHERE matkul_id = ? AND user_id = ?");
        $s->bind_param('ii', $id, $uid);
        $s->execute();
        set_flash('info', 'Kamu keluar dari ' . $m['nama'] . '.');

    } elseif ($aksi === 'keluarkan' && $boleh_kelola) {
        $target = (int) ($_POST['user_id'] ?? 0);
        $s = $conn->prepare("DELETE FROM matkul_peserta WHERE matkul_id = ? AND user_id = ?");
        $s->bind_param('ii', $id, $target);
        $s->execute();
        set_flash('info', 'Peserta dikeluarkan dari mata kuliah.');
    }
    redirect('detail.php?id=' . $id);
}

/* ---------- Peserta ---------- */
$stmt = $conn->prepare(
    "SELECT u.id, u.nama, u.nim_nip
     FROM matkul_peserta p
     JOIN users u ON u.id = p.user_id
     WHERE p.matkul_id = ?
     ORDER BY u.nama"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$peserta = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$sudah_daftar = in_array($uid, array_column($peserta, 'id'), false);

/* ---------- Jadwal matkul ini ---------- */
$stmt = $conn->prepare(
    "SELECT hari, jam_mulai, jam_selesai, ruang
     FROM jadwal
     WHERE matkul_id = ?
     ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), jam_mulai"
);
$stmt->bind_param('i', $id);
$stmt->execute();
$jadwal = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$judul_halaman = $m['nama'];
$menu_aktif    = 'matkul';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1><?= e($m['nama']) ?></h1>
        <p><?= e($m['kode']) ?>, <?= (int) $m['sks'] ?> SKS</p>
    </div>
    <div class="form-aksi">
        <a class="btn btn-garis" href="index.php">Kembali</a>

        <?php if ($role === 'mahasiswa'): ?>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <?php if ($sudah_daftar): ?>
                    <input type="hidden" name="aksi" value="keluar">
                    <button type="submit" class="btn btn-bahaya"
                            data-konfirmasi="Keluar dari mata kuliah ini?">Keluar dari matkul</button>
                <?php else: ?>
                    <input type="hidden" name="aksi" value="gabung">
                    <button type="submit" class="btn">Ikuti mata kuliah</button>
                <?php endif; ?>
            </form>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
            <a class="btn" href="form.php?id=<?= (int) $m['id'] ?>">Edit</a>
        <?php endif; ?>
    </div>
</div>

<div class="dua-kolom">
    <div>
        <section class="kartu">
            <h2>Deskripsi</h2>
            <p class="mt-1"><?= $m['deskripsi'] !== '' && $m['deskripsi'] !== null
                ? nl2br(e($m['deskripsi']))
                : '<span class="teks-pudar">Belum ada deskripsi.</span>' ?></p>
        </section>

        <section class="mt-2">
            <h2 class="mb-2">Peserta (<?= count($peserta) ?>)</h2>
            <?php if (!$peserta): ?>
                <div class="kosong"><p>Belum ada mahasiswa yang terdaftar.</p></div>
            <?php else: ?>
                <div class="tabel-bungkus">
                    <table class="tabel">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>NIM</th>
                                <?php if ($boleh_kelola): ?><th>Aksi</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($peserta as $i => $p): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= e($p['nama']) ?></td>
                                    <td><?= e($p['nim_nip']) ?></td>
                                    <?php if ($boleh_kelola): ?>
                                        <td>
                                            <form method="post" class="inline-form"
                                                  data-konfirmasi="Keluarkan <?= e($p['nama']) ?> dari mata kuliah ini?">
                                                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                                <input type="hidden" name="aksi" value="keluarkan">
                                                <input type="hidden" name="user_id" value="<?= (int) $p['id'] ?>">
                                                <button type="submit" class="btn btn-kecil btn-bahaya">Keluarkan</button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <aside>
        <section class="kartu">
            <h2>Dosen pengampu</h2>
            <dl class="info-daftar mt-1">
                <dt>Nama</dt><dd><?= e($m['dosen']) ?></dd>
                <dt>NIP</dt><dd><?= e($m['nip_dosen']) ?></dd>
                <dt>Email</dt><dd><a href="mailto:<?= e($m['email_dosen']) ?>"><?= e($m['email_dosen']) ?></a></dd>
            </dl>
        </section>

        <section class="kartu">
            <h2>Jadwal kuliah</h2>
            <?php if (!$jadwal): ?>
                <p class="teks-pudar mt-1">Jadwal belum diatur.</p>
            <?php else: ?>
                <dl class="info-daftar mt-1">
                    <?php foreach ($jadwal as $j): ?>
                        <dt><?= e($j['hari']) ?></dt>
                        <dd><?= e(substr($j['jam_mulai'], 0, 5)) ?> sampai <?= e(substr($j['jam_selesai'], 0, 5)) ?>, <?= e($j['ruang']) ?></dd>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
        </section>
    </aside>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>