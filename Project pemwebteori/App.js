/* =====================================================
   SPADA MINI - app.js
   1. Menu mobile (buka/tutup sidebar)
   2. Tampilkan / sembunyikan password
   3. Konfirmasi sebelum hapus
   4. Timer hitung mundur (ulangan)
   5. Alert yang menutup sendiri
   ===================================================== */
(function () {
  'use strict';

  /* ---------- 1. Menu mobile ---------- */
  var tombolMenu = document.getElementById('btnMenu');
  var overlay = document.getElementById('overlay');

  function setMenu(buka) {
    document.body.classList.toggle('menu-terbuka', buka);
    if (tombolMenu) tombolMenu.setAttribute('aria-expanded', buka ? 'true' : 'false');
  }
  if (tombolMenu) {
    tombolMenu.addEventListener('click', function () {
      setMenu(!document.body.classList.contains('menu-terbuka'));
    });
  }
  if (overlay) overlay.addEventListener('click', function () { setMenu(false); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') setMenu(false);
  });
  window.addEventListener('resize', function () {
    if (window.innerWidth > 900) setMenu(false);
  });

  /* ---------- 2. Toggle password ----------
     <button type="button" class="btn-lihat" data-toggle-password="#sandi">Lihat</button> */
  document.addEventListener('click', function (e) {
    var tombol = e.target.closest('[data-toggle-password]');
    if (!tombol) return;
    var input = document.querySelector(tombol.getAttribute('data-toggle-password'));
    if (!input) return;
    var tampil = input.type === 'password';
    input.type = tampil ? 'text' : 'password';
    tombol.textContent = tampil ? 'Sembunyikan' : 'Lihat';
  });

  /* ---------- 3. Konfirmasi hapus ----------
     Form : <form method="post" action="hapus.php" data-konfirmasi="Hapus matkul ini?">
     Link : <a href="..." data-konfirmasi="Yakin?">
     Tombol di dalam form: <button data-konfirmasi="Yakin?"> */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.matches && form.matches('form[data-konfirmasi]')) {
      if (!window.confirm(form.getAttribute('data-konfirmasi'))) e.preventDefault();
    }
  });
  document.addEventListener('click', function (e) {
    var el = e.target.closest('a[data-konfirmasi], button[data-konfirmasi]');
    if (!el) return;
    if (!window.confirm(el.getAttribute('data-konfirmasi'))) e.preventDefault();
  });

  /* ---------- 4. Timer hitung mundur ----------
     <div class="timer" data-timer="900" data-timer-form="#formUlangan">
       Sisa waktu: <span class="timer-teks">15:00</span>
     </div>
     data-timer       : durasi dalam detik
     data-timer-form  : form yang dikirim otomatis saat waktu habis */
  document.querySelectorAll('[data-timer]').forEach(function (kotak) {
    var sisa = parseInt(kotak.getAttribute('data-timer'), 10);
    if (isNaN(sisa) || sisa <= 0) return;

    var teks = kotak.querySelector('.timer-teks') || kotak;
    var form = document.querySelector(kotak.getAttribute('data-timer-form') || 'form');

    function tampil() {
      var m = Math.floor(sisa / 60);
      var d = sisa % 60;
      teks.textContent = (m < 10 ? '0' : '') + m + ':' + (d < 10 ? '0' : '') + d;
      kotak.classList.toggle('hampir-habis', sisa <= 60);
    }
    tampil();

    var jam = setInterval(function () {
      sisa--;
      tampil();
      if (sisa <= 0) {
        clearInterval(jam);
        window.alert('Waktu habis. Jawabanmu dikirim otomatis.');
        if (form) form.submit();
      }
    }, 1000);
  });

  /* ---------- 5. Alert ---------- */
  document.addEventListener('click', function (e) {
    var tutup = e.target.closest('.alert-tutup');
    if (tutup) tutup.closest('.alert').remove();
  });
  document.querySelectorAll('.alert[data-otomatis]').forEach(function (a) {
    setTimeout(function () { a.remove(); }, 5000);
  });
})();