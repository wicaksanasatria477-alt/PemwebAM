/* Titik masuk: cek sesi di server, lalu tampilkan login atau aplikasi */
(async function boot() {
  try {
    const r = await get('auth/me');
    CSRF = r.csrf; U = r.user;
    if (U) { await loadCS(); readHash(); }
  } catch (e) {
    $('#app').innerHTML = `<div class="login"><div class="card"><h2>Server tidak dapat dihubungi</h2><p>${esc(e.message)}</p>
    <p class="mu">Buka lewat server PHP (mis. <code>http://localhost/spada-lms/</code>), bukan dengan membuka file index.html langsung.</p></div></div>`;
    return;
  }
  render();
})();
window.addEventListener('hashchange', () => { if (U) { const before = JSON.stringify([V.v, V.c, V.s]); readHash(); if (JSON.stringify([V.v, V.c, V.s]) !== before) render(); } });
