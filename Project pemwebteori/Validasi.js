/* =====================================================
   SPADA MINI - validasi.js
   Validasi form di sisi browser. Aktifkan dengan menambah
   atribut data-validate pada <form>.

   Atribut yang didukung pada input:
     required              wajib diisi (juga untuk checkbox)
     type="email"          cek format email
     data-label="Nama"     nama kolom untuk pesan error
     data-min="6"          minimal jumlah karakter
     data-match="#sandi"   isi harus sama dengan input lain
     min / max             untuk type="number"
   Pada pembungkus grup checkbox:
     data-min-centang="1"  minimal berapa checkbox harus dicentang

   Contoh:
     <form method="post" data-validate>
       <div class="form-group">
         <label for="sandi">Password</label>
         <input type="password" id="sandi" name="password" class="input"
                required data-min="6" data-label="Password">
       </div>
       <div class="form-group">
         <label for="sandi2">Ulangi password</label>
         <input type="password" id="sandi2" name="password2" class="input"
                required data-match="#sandi" data-label="Konfirmasi password">
       </div>
     </form>

   Validasi ini hanya membantu pengguna. Server (PHP) tetap
   harus memvalidasi ulang data yang masuk.
   ===================================================== */
(function () {
  'use strict';

  var EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  /* ---------- Tampilkan / hapus pesan error ---------- */
  function wadahPesan(el) {
    var induk = el.closest('.form-group') || el.parentNode;
    var span = induk.querySelector('.pesan-error');
    if (!span) {
      span = document.createElement('span');
      span.className = 'pesan-error';
      span.setAttribute('role', 'alert');
      induk.appendChild(span);
    }
    return span;
  }

  function tampilkan(el, teks) {
    wadahPesan(el).textContent = teks;
    if (el.classList) el.classList.toggle('tidak-valid', teks !== '');
    if (el.setAttribute) el.setAttribute('aria-invalid', teks !== '' ? 'true' : 'false');
  }

  /* ---------- Aturan untuk satu input ---------- */
  function cekInput(el) {
    var label = el.getAttribute('data-label') || el.getAttribute('name') || 'Kolom ini';
    var nilai = el.value.trim();

    if (el.type === 'checkbox') {
      return (el.required && !el.checked) ? label + ' harus dicentang.' : '';
    }
    if (el.type === 'radio') return '';   // radio dicek per grup di bawah

    if (el.required && nilai === '') return label + ' wajib diisi.';
    if (nilai === '') return '';

    if (el.type === 'email' && !EMAIL.test(nilai)) {
      return 'Format email tidak valid.';
    }
    var min = el.getAttribute('data-min');
    if (min && nilai.length < parseInt(min, 10)) {
      return label + ' minimal ' + min + ' karakter.';
    }
    if (el.type === 'number') {
      var angka = parseFloat(nilai);
      if (isNaN(angka)) return label + ' harus berupa angka.';
      if (el.min !== '' && angka < parseFloat(el.min)) return label + ' minimal ' + el.min + '.';
      if (el.max !== '' && angka > parseFloat(el.max)) return label + ' maksimal ' + el.max + '.';
    }
    var cocok = el.getAttribute('data-match');
    if (cocok) {
      var target = document.querySelector(cocok);
      if (target && target.value !== el.value) return 'Isi kolom ini tidak sama dengan ' +
        (target.getAttribute('data-label') || 'kolom sebelumnya') + '.';
    }
    return '';
  }

  /* ---------- Grup radio (wajib pilih salah satu) ---------- */
  function cekRadio(form, nama) {
    var grup = form.querySelectorAll('input[type="radio"][name="' + nama + '"]');
    if (!grup.length || !grup[0].required) return '';
    for (var i = 0; i < grup.length; i++) if (grup[i].checked) return '';
    return (grup[0].getAttribute('data-label') || 'Pilihan') + ' wajib dipilih.';
  }

  /* ---------- Grup checkbox (minimal n dicentang) ---------- */
  function cekGrupCentang(grup) {
    var min = parseInt(grup.getAttribute('data-min-centang'), 10) || 1;
    var jumlah = grup.querySelectorAll('input[type="checkbox"]:checked').length;
    return jumlah < min ? 'Pilih minimal ' + min + ' opsi.' : '';
  }

  /* ---------- Validasi seluruh form ---------- */
  function validasiForm(form) {
    var pertamaSalah = null;
    var sudahRadio = {};

    form.querySelectorAll('input, select, textarea').forEach(function (el) {
      if (el.disabled || el.type === 'submit' || el.type === 'button' || el.type === 'hidden') return;

      var pesan;
      if (el.type === 'radio') {
        if (sudahRadio[el.name]) return;
        sudahRadio[el.name] = true;
        pesan = cekRadio(form, el.name);
      } else if (el.closest('[data-min-centang]')) {
        return;  // dicek lewat grupnya
      } else {
        pesan = cekInput(el);
      }
      tampilkan(el, pesan);
      if (pesan && !pertamaSalah) pertamaSalah = el;
    });

    form.querySelectorAll('[data-min-centang]').forEach(function (grup) {
      var pesan = cekGrupCentang(grup);
      var span = grup.querySelector('.pesan-error');
      if (!span) {
        span = document.createElement('span');
        span.className = 'pesan-error';
        span.setAttribute('role', 'alert');
        grup.appendChild(span);
      }
      span.textContent = pesan;
      if (pesan && !pertamaSalah) pertamaSalah = grup.querySelector('input');
    });

    return pertamaSalah;
  }

  /* ---------- Pasang ke semua form data-validate ---------- */
  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    form.noValidate = true;   // pakai pesan sendiri, bukan balon bawaan browser

    form.addEventListener('submit', function (e) {
      var salah = validasiForm(form);
      if (salah) {
        e.preventDefault();
        salah.focus();
      }
    });

    // cek ulang saat pengguna mengetik / berpindah kolom
    form.addEventListener('input', function (e) {
      var el = e.target;
      if (!el.matches || !el.matches('input, select, textarea')) return;
      if (el.closest('[data-min-centang]')) return;
      if (el.classList.contains('tidak-valid')) tampilkan(el, cekInput(el));

      // kalau password utama berubah, cek ulang kolom konfirmasinya
      if (el.id) {
        form.querySelectorAll('[data-match="#' + el.id + '"]').forEach(function (konf) {
          if (konf.value !== '') tampilkan(konf, cekInput(konf));
        });
      }
    });

    form.addEventListener('blur', function (e) {
      var el = e.target;
      if (!el.matches || !el.matches('input, select, textarea')) return;
      if (el.type === 'radio' || el.type === 'checkbox' || el.type === 'submit') return;
      tampilkan(el, cekInput(el));
    }, true);

    form.addEventListener('change', function (e) {
      var el = e.target;
      if (el.type === 'checkbox' && !el.closest('[data-min-centang]')) tampilkan(el, cekInput(el));
    });
  });
})();