<?php
require_once __DIR__ . '/../includes/init.php';
wajib_role('admin');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$data = ['kode' => '', 'nama' => '', 'sks' => 3, 'deskripsi' => '', 'dosen_id' => 0];

/* ---------- Mode edit: ambil data lama ---------- */
if ($id > 0) {
    $stmt = $conn->prepare("SELECT kode, nama, sks, deskripsi, dosen_id FROM matkul WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $lama = $stmt->get_result()->fetch_assoc();
    if (!$lama) {
        set_flash('error', 'Mata kuliah tidak ditemukan.');
        redirect('index.php');
    }
    $data = $lama;
}

/* ---------- Proses simpan ---------- */
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();

    $data['kode']      = strtoupper(trim($_POST['kode'] ?? ''));
    $data['nama']      = trim($_POST['nama'] ?? '');
    $data['sks']       = (int) ($_POST['sks'] ?? 0);
    $data['deskripsi'] = trim($_POST['deskripsi'] ?? '');
    $data['dosen_id']  = (int) ($_POST['dosen_id'] ?? 0);

    if ($data['kode'] === '') {
        $errors[] = 'Kode mata kuliah wajib diisi.';
    } elseif (!preg_match('/^[A-Z0-9\- ]{2,20}$/', $data['kode'])) {
        $errors[] = 'Kode hanya boleh huruf, angka, spasi, dan tanda minus (2 sampai 20 karakter).';
    }

    if ($data['nama'] === '') {
        $errors[] = 'Nama mata kuliah wajib diisi.';
    } elseif (mb_strlen($data['nama']) > 100) {
        $errors[] = 'Nama mata kuliah maksimal 100 karakter.';
    }

    if ($data['sks'] < 1 || $data['sks'] > 6) {
        $errors[] = 'SKS harus antara 1 sampai 6.';
    }

    // dosen harus benar-benar ada dan berperan dosen
    $s = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'dosen'");
    $s->bind_param('i', $data['dosen_id']);
    $s->execute();
    if (!$s->get_result()->fetch_assoc()) {
        $errors[] = 'Pilih dosen pengampu dari daftar.';
    }

    // kode tidak boleh kembar
    if (!$errors) {
        $s = $conn->prepare("SELECT id FROM matkul WHERE kode = ? AND id <> ?");
        $s->bind_param('si', $data['kode'], $id);
        $s->execute();
        if ($s->get_result()->fetch_assoc()) {
            $errors[] = 'Kode ' . $data['kode'] . ' sudah dipakai mata kuliah lain.';
        }
    }

    if (!$errors) {
        if ($id > 0) {
            $s = $conn->prepare("UPDATE matkul SET kode = ?, nama = ?, sks = ?, deskripsi = ?, dosen_id = ? WHERE id = ?");
            $s->bind_param('ssisii', $data['kode'], $data['nama'], $data['sks'], $data['deskripsi'], $data['dosen_id'], $id);
            $s->execute();
            set_flash('sukses', 'Mata kuliah berhasil diperbarui.');
        } else {
            $s = $conn->prepare("INSERT INTO matkul (kode, nama, sks, deskripsi, dosen_id) VALUES (?, ?, ?, ?, ?)");
            $s->bind_param('ssisi', $data['kode'], $data['nama'], $data['sks'], $data['deskripsi'], $data['dosen_id']);
            $s->execute();
            set_flash('sukses', 'Mata kuliah berhasil ditambahkan.');
        }
        redirect('index.php');
    }
}

$daftar_dosen = $conn->query("SELECT id, nama FROM users WHERE role = 'dosen' ORDER BY nama")->fetch_all(MYSQLI_ASSOC);

$mode          = $id > 0 ? 'Edit' : 'Tambah';
$judul_halaman = $mode . ' mata kuliah';
$menu_aktif    = 'matkul';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1><?= $mode ?> mata kuliah</h1>
        <p><?= $id > 0 ? 'Ubah data mata kuliah lalu simpan.' : 'Isi data mata kuliah baru.' ?></p>
    </div>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error form-kartu" role="alert">
        <div>
            <strong>Data belum bisa disimpan:</strong>
            <ul style="margin: 6px 0 0 18px;">
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<form method="post" class="kartu form-kartu" data-validate>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="form-baris">
        <div class="form-group">
            <label for="kode">Kode mata kuliah</label>
            <input type="text" id="kode" name="kode" class="input" maxlength="20"
                   value="<?= e($data['kode']) ?>" required data-label="Kode mata kuliah" placeholder="Contoh: IF201">
        </div>
        <div class="form-group">
            <label for="sks">SKS</label>
            <input type="number" id="sks" name="sks" class="input" min="1" max="6"
                   value="<?= (int) $data['sks'] ?>" required data-label="SKS">
        </div>
    </div>

    <div class="form-group">
        <label for="nama">Nama mata kuliah</label>
        <input type="text" id="nama" name="nama" class="input" maxlength="100"
               value="<?= e($data['nama']) ?>" required data-label="Nama mata kuliah" placeholder="Contoh: Pemrograman Web">
    </div>

    <div class="form-group">
        <label for="dosen_id">Dosen pengampu</label>
        <select id="dosen_id" name="dosen_id" required data-label="Dosen pengampu">
            <option value="">Pilih dosen</option>
            <?php foreach ($daftar_dosen as $d): ?>
                <option value="<?= (int) $d['id'] ?>" <?= (int) $data['dosen_id'] === (int) $d['id'] ? 'selected' : '' ?>>
                    <?= e($d['nama']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (!$daftar_dosen): ?>
            <p class="bantuan">Belum ada akun dosen. Tambahkan dulu lewat data users.</p>
        <?php endif; ?>
    </div>

    <div class="form-group">
        <label for="deskripsi">Deskripsi</label>
        <textarea id="deskripsi" name="deskripsi" placeholder="Ringkasan isi mata kuliah"><?= e($data['deskripsi']) ?></textarea>
    </div>

    <div class="form-aksi">
        <button type="submit" class="btn">Simpan</button>
        <a class="btn btn-garis" href="index.php">Batal</a>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>