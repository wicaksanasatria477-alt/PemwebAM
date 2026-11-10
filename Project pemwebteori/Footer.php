<?php
/**
 * includes/footer.php
 * Menutup <main>, area utama, dan layout, lalu memuat JavaScript.
 * Pasangan dari includes/header.php.
 *
 * Opsional: kalau satu halaman butuh JS tambahan, isi sebelum include footer:
 *   $js_tambahan = ['assets/js/halaman-ini.js'];
 */
if (!defined('BASE_URL')) {
    // header.php belum dimuat (misalnya halaman tanpa sidebar), hitung ulang
    $root_proyek = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $root_web    = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
    define('BASE_URL', rtrim(substr($root_proyek, strlen($root_web)), '/') . '/');
}
$js_tambahan = $js_tambahan ?? [];
?>
        </main>

        <footer class="footer">
            &copy; <?= date('Y') ?> SPADA Mini. Tugas kelompok Pemrograman Web.
        </footer>
    </div><!-- /.main -->
</div><!-- /.app -->

<script src="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>assets/js/validasi.js"></script>
<script src="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>assets/js/app.js"></script>
<?php foreach ($js_tambahan as $berkas): ?>
<script src="<?= htmlspecialchars(BASE_URL . ltrim($berkas, '/'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endforeach; ?>
</body>
</html>