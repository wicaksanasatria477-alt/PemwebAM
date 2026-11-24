<?php
declare(strict_types=1);
defined('SPADA') or exit;

/* ---------- Konfigurasi, zona waktu, sesi ---------- */
$GLOBALS['CFG'] = require (getenv('SPADA_CONFIG') ?: __DIR__ . '/config.php');
function cfg(string $k, $d = null) { return $GLOBALS['CFG'][$k] ?? $d; }

date_default_timezone_set(cfg('timezone', 'Asia/Jakarta'));
mb_internal_encoding('UTF-8');

session_name('SPADASESS');
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']),
]);
session_start();

/* ---------- Error & respons JSON ---------- */
final class HttpError extends Exception {
    public function __construct(string $msg, public int $status = 400) { parent::__construct($msg); }
}
function fail(string $msg, int $status = 400): never { throw new HttpError($msg, $status); }

function out($data = null, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $status < 400, 'data' => $data],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

set_exception_handler(function (Throwable $e) {
    if ($e instanceof HttpError) {
        http_response_code($e->status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        return;
    }
    error_log('[SPADA] ' . $e);
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    $msg = 'Terjadi kesalahan di server.';
    if ($e instanceof PDOException) $msg .= ' Pastikan database sudah di-import dan migrasi.sql sudah dijalankan.';
    echo json_encode(['ok' => false, 'error' => cfg('debug') ? $e->getMessage() : $msg], JSON_UNESCAPED_UNICODE);
});

/* ---------- Database ---------- */
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO(cfg('dsn'), cfg('user'), cfg('pass'), [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    foreach (cfg('init', []) as $sql) $pdo->exec($sql);
    return $pdo;
}
/** Ambil semua baris. */
function rows(string $sql, array $p = []): array { $s = db()->prepare($sql); $s->execute($p); return $s->fetchAll(); }
/** Ambil satu baris atau null. */
function row(string $sql, array $p = []): ?array { $s = db()->prepare($sql); $s->execute($p); $r = $s->fetch(); return $r ?: null; }
/** Jalankan perintah tulis, kembalikan jumlah baris terpengaruh. */
function run(string $sql, array $p = []): int { $s = db()->prepare($sql); $s->execute($p); return $s->rowCount(); }
function last_id(): int { return (int)db()->lastInsertId(); }
/** Placeholder "?,?,?" untuk klausa IN. */
function ph(array $a): string { return implode(',', array_fill(0, count($a), '?')); }

/* ---------- Input ---------- */
function input(): array {
    static $in = null;
    if ($in !== null) return $in;
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        $j = json_decode(file_get_contents('php://input') ?: '', true);
        return $in = is_array($j) ? $j : [];
    }
    return $in = $_POST;
}
function p_str(string $k, int $max = 255, bool $req = false): ?string {
    $v = input()[$k] ?? null;
    $v = is_scalar($v) ? trim((string)$v) : '';
    if ($v === '') { if ($req) fail("Kolom '$k' wajib diisi."); return null; }
    if (mb_strlen($v) > $max) fail("Kolom '$k' maksimal $max karakter.");
    return $v;
}
function p_int(string $k, bool $req = false): ?int {
    $v = input()[$k] ?? null;
    if ($v === null || $v === '') { if ($req) fail("Kolom '$k' wajib diisi."); return null; }
    if (is_bool($v) || !is_numeric($v) || (float)$v != (int)$v) fail("Kolom '$k' harus berupa angka bulat.");
    return (int)$v;
}
function p_bool(string $k, bool $default = false): bool {
    $v = input()[$k] ?? null;
    if ($v === null) return $default;
    return in_array($v, [true, 1, '1', 'true', 'on'], true);
}
function g_int(string $k, bool $req = true): ?int {
    $v = $_GET[$k] ?? null;
    if ($v === null || $v === '' || !ctype_digit((string)$v)) { if ($req) fail("Parameter '$k' tidak valid."); return null; }
    return (int)$v;
}
function valid_date(?string $s): ?string {
    if ($s === null) return null;
    $d = DateTime::createFromFormat('!Y-m-d', $s);
    return ($d && $d->format('Y-m-d') === $s) ? $s : null;
}
function valid_time(?string $s): ?string {
    if ($s !== null && preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $s)) return substr($s, 0, 5) . ':00';
    return null;
}
/** Terima 'Y-m-d\TH:i', 'Y-m-d H:i' atau dengan detik; kembalikan 'Y-m-d H:i:s'. */
function valid_datetime(?string $s): ?string {
    if ($s === null) return null;
    foreach (['Y-m-d\TH:i', 'Y-m-d H:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s'] as $f) {
        $d = DateTime::createFromFormat('!' . $f, $s);
        if ($d && $d->format($f) === $s) return $d->format('Y-m-d H:i:s');
    }
    return null;
}
function now_str(): string { return date('Y-m-d H:i:s'); }

/* ---------- Autentikasi, CSRF, hak akses ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function csrf_check(): void {
    $t = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($t) || !hash_equals(csrf_token(), $t)) fail('Sesi tidak valid, muat ulang halaman.', 419);
}
function me(): ?array {
    static $u = false;
    if ($u !== false) return $u;
    $id = $_SESSION['uid'] ?? null;
    return $u = $id ? row('SELECT id,nama,email,nim_nip,role,foto FROM users WHERE id=?', [$id]) : null;
}
function public_user(array $u): array {
    return ['id' => (int)$u['id'], 'nama' => $u['nama'], 'email' => $u['email'], 'nim_nip' => $u['nim_nip'],
            'role' => $u['role'], 'has_foto' => !empty($u['foto'])];
}
function need_login(): array { $u = me(); if (!$u) fail('Silakan masuk terlebih dahulu.', 401); return $u; }
function need_role(string ...$roles): array {
    $u = need_login();
    if (!in_array($u['role'], $roles, true)) fail('Anda tidak berhak mengakses fitur ini.', 403);
    return $u;
}

function matkul_or_404(int $id): array { return row('SELECT * FROM matkul WHERE id=?', [$id]) ?? fail('Mata kuliah tidak ditemukan.', 404); }
function is_enrolled(int $uid, int $mid): bool { return (bool)row('SELECT 1 FROM matkul_peserta WHERE matkul_id=? AND user_id=?', [$mid, $uid]); }
function can_manage(array $u, array $m): bool { return $u['role'] === 'admin' || ($u['role'] === 'dosen' && (int)$m['dosen_id'] === (int)$u['id']); }
function can_access(array $u, array $m): bool { return can_manage($u, $m) || ($u['role'] === 'mahasiswa' && is_enrolled((int)$u['id'], (int)$m['id'])); }
function guard_manage(array $u, array $m): void { if (!can_manage($u, $m)) fail('Anda bukan pengampu mata kuliah ini.', 403); }
function guard_access(array $u, array $m): void { if (!can_access($u, $m)) fail('Anda tidak terdaftar di mata kuliah ini.', 403); }

/* ---------- Routing ---------- */
function route(string $method, string $path, callable $fn): void { $GLOBALS['ROUTES'][$method][$path] = $fn; }

/* ---------- Upload & penyimpanan file ---------- */
const UPLOAD_TYPES = [   // ekstensi => daftar awalan/isi MIME yang diterima (hasil finfo)
    'pdf'  => ['application/pdf'],
    'doc'  => ['application/msword', 'application/vnd.ms-office', 'application/cdfv2', 'application/octet-stream'],
    'xls'  => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/cdfv2', 'application/octet-stream'],
    'ppt'  => ['application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/cdfv2', 'application/octet-stream'],
    'docx' => ['application/vnd.openxmlformats', 'application/zip', 'application/octet-stream'],
    'xlsx' => ['application/vnd.openxmlformats', 'application/zip', 'application/octet-stream'],
    'pptx' => ['application/vnd.openxmlformats', 'application/zip', 'application/octet-stream'],
    'zip'  => ['application/zip', 'application/x-zip', 'application/octet-stream'],
    'rar'  => ['application/x-rar', 'application/vnd.rar', 'application/octet-stream'],
    '7z'   => ['application/x-7z-compressed', 'application/octet-stream'],
    'txt'  => ['text/'], 'csv' => ['text/', 'application/csv'], 'md' => ['text/'],
    'jpg'  => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'],
    'gif'  => ['image/gif'], 'webp' => ['image/webp'],
];
const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
const DOWNLOAD_MIME = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
                       'gif' => 'image/gif', 'webp' => 'image/webp', 'txt' => 'text/plain; charset=utf-8'];
const FILE_CATS = ['materi', 'tugas', 'absensi', 'pengumuman', 'foto'];

function storage_dir(string $cat): string {
    if (!in_array($cat, FILE_CATS, true)) fail('Kategori file tidak valid.', 500);
    $d = rtrim((string)cfg('storage'), '/\\') . '/' . $cat;
    if (!is_dir($d) && !@mkdir($d, 0750, true) && !is_dir($d)) fail('Folder penyimpanan tidak bisa dibuat. Periksa izin folder storage/.', 500);
    return $d;
}
/** Nama tampil dari nama tersimpan "<hex>__<nama asli>". */
function file_label(?string $stored): ?string {
    if (!$stored) return null;
    $p = strpos($stored, '__');
    return $p === false ? $stored : substr($stored, $p + 2);
}
/**
 * Simpan upload dari $_FILES[$field]. Kembalikan nama tersimpan, atau null bila tidak ada file
 * (dan $required false). Validasi: error upload, ukuran, ekstensi (allowlist), isi MIME.
 */
function save_upload(string $field, string $cat, bool $required = false, ?int $maxMb = null, bool $imageOnly = false): ?string {
    $f = $_FILES[$field] ?? null;
    if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        if ($required) fail('Pilih file terlebih dahulu.');
        return null;
    }
    if (is_array($f['error'])) fail('Unggah satu file saja.');
    switch ($f['error']) {
        case UPLOAD_ERR_OK: break;
        case UPLOAD_ERR_INI_SIZE: case UPLOAD_ERR_FORM_SIZE:
            fail('Ukuran file melebihi batas server (upload_max_filesize = ' . ini_get('upload_max_filesize') . ').');
        default: fail('Unggahan gagal (kode ' . (int)$f['error'] . '). Coba lagi.');
    }
    $maxMb ??= (int)cfg('max_upload_mb', 10);
    if ($f['size'] > $maxMb * 1048576) fail("Ukuran file maksimal $maxMb MB.");
    if ($f['size'] <= 0) fail('File kosong.');
    if (!is_uploaded_file($f['tmp_name'])) fail('Unggahan tidak valid.');

    $orig = basename(str_replace('\\', '/', (string)$f['name']));
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    $allowed = $imageOnly ? IMAGE_EXT : array_keys(UPLOAD_TYPES);
    if (!in_array($ext, $allowed, true)) {
        fail('Tipe file tidak diizinkan. Diizinkan: ' . implode(', ', $allowed) . '.');
    }
    $mime = strtolower((string)(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']));
    $ok = false;
    foreach (UPLOAD_TYPES[$ext] as $prefix) if (str_starts_with($mime, $prefix)) { $ok = true; break; }
    if (!$ok) fail("Isi file tidak sesuai dengan ekstensi .$ext.");

    $base = preg_replace('/[^A-Za-z0-9._-]+/', '_', pathinfo($orig, PATHINFO_FILENAME)) ?: 'file';
    $base = ltrim(substr($base, 0, 100), '._') ?: 'file';
    $stored = bin2hex(random_bytes(8)) . '__' . $base . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], storage_dir($cat) . '/' . $stored)) fail('Gagal menyimpan file.', 500);
    return $stored;
}
function delete_file(string $cat, ?string $stored): void {
    if (!$stored || basename($stored) !== $stored) return;
    $p = storage_dir($cat) . '/' . $stored;
    if (is_file($p)) @unlink($p);
}
/** Hapus banyak file: daftar pasangan [kategori, nama]. */
function purge_files(array $list): void { foreach ($list as [$cat, $name]) delete_file($cat, $name); }

/** Kumpulkan file terkait sebelum baris DB dihapus (cascade tidak menghapus file di disk). */
function files_of_pertemuan(array $pids): array {
    if (!$pids) return [];
    $in = ph($pids); $out = [];
    foreach (rows("SELECT file FROM materi WHERE pertemuan_id IN ($in)", $pids) as $r) $out[] = ['materi', $r['file']];
    foreach (rows("SELECT bukti FROM absensi WHERE bukti IS NOT NULL AND pertemuan_id IN ($in)", $pids) as $r) $out[] = ['absensi', $r['bukti']];
    foreach (rows("SELECT g.file FROM pengumpulan g JOIN tugas t ON t.id=g.tugas_id WHERE g.file<>'' AND t.pertemuan_id IN ($in)", $pids) as $r) $out[] = ['tugas', $r['file']];
    return $out;
}
function files_of_tugas(int $tid): array {
    return array_map(fn($r) => ['tugas', $r['file']], rows("SELECT file FROM pengumpulan WHERE file<>'' AND tugas_id=?", [$tid]));
}
function files_of_matkul(int $mid): array {
    $out = files_of_pertemuan(array_column(rows('SELECT id FROM pertemuan WHERE matkul_id=?', [$mid]), 'id'));
    foreach (rows("SELECT g.file FROM pengumpulan g JOIN tugas t ON t.id=g.tugas_id WHERE g.file<>'' AND t.matkul_id=? AND t.pertemuan_id IS NULL", [$mid]) as $r) $out[] = ['tugas', $r['file']];
    foreach (rows('SELECT file FROM pengumuman WHERE file IS NOT NULL AND matkul_id=?', [$mid]) as $r) $out[] = ['pengumuman', $r['file']];
    return $out;
}

/* ---------- Format ---------- */
function hhmm(?string $t): ?string { return $t === null ? null : substr($t, 0, 5); }
const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
