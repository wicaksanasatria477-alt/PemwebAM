/* Login & logout (verifikasi di server: password_verify + session) */
function login() {
  $('#app').innerHTML = `<div class="login"><div class="logo" style="font-size:28px">SPADA Lite<small>Learning Management System</small></div>
  <form class="card" onsubmit="event.preventDefault();doLogin()"><h2>Masuk</h2>
  <label for="lu">NIM / NIP atau email</label><input type="text" id="lu" autocomplete="username" autofocus>
  <label for="lp">Kata sandi</label><input type="password" id="lp" autocomplete="current-password">
  <button class="btn" id="lb" style="width:100%;margin-top:10px">Masuk</button>
  <p class="mu" style="margin-bottom:0">Belum punya akun? Hubungi admin akademik.</p></form></div>`;
}

async function doLogin() {
  const b = $('#lb'); b.disabled = true;
  try {
    const r = await post('auth/login', { id: val('#lu'), password: $('#lp').value });
    U = r.user; CSRF = r.csrf; await loadCS(); readHash(); history.replaceState(null, '', '#' + (V.v === 'course' ? `course/${V.c}/${V.s}` : V.v));
    render();
  } catch (e) { toast(e.message, true); b.disabled = false; }
}

async function logout() {
  try { await post('auth/logout'); } catch { }
  U = null; CS = []; K = null; V = { v: 'dash' }; history.replaceState(null, '', '#dash');
  const r = await get('auth/me'); CSRF = r.csrf; render();
}

const loadCS = async () => { CS = await get('matkul/list'); };
