/* Helper umum, klien API, dan state aplikasi */
const API = 'api/index.php';
const $ = s => document.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const toM = t => { const [a, b] = t.split(':'); return +a * 60 + +b };

/* State: U = pengguna login, CS = daftar mata kuliah, V = halaman aktif, K = data kelas aktif */
let U = null, CSRF = '', CS = [], K = null;
let V = { v: 'dash' };
const isMgr = () => U && U.role !== 'mahasiswa';
const ROLE_LABEL = { admin: 'Portal Admin', dosen: 'Portal Dosen', mahasiswa: 'Portal Mahasiswa' };

const toast = (m, err) => {
  const e = document.createElement('div');
  e.className = 'toast' + (err ? ' err' : ''); e.textContent = m;
  document.body.appendChild(e); setTimeout(() => e.remove(), err ? 4000 : 2200);
};

/* ---- Klien API ---- */
async function api(route, { method = 'GET', body = null, query = null } = {}) {
  let url = API + '?r=' + route;
  for (const k in (query || {})) if (query[k] !== undefined && query[k] !== null) url += '&' + k + '=' + encodeURIComponent(query[k]);
  const opt = { method, credentials: 'same-origin', headers: {} };
  if (method !== 'GET') {
    opt.headers['X-CSRF-Token'] = CSRF;
    if (body instanceof FormData) opt.body = body;
    else { opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(body || {}); }
  }
  let res, j;
  try { res = await fetch(url, opt); } catch { throw new Error('Tidak bisa terhubung ke server.'); }
  try { j = await res.json(); } catch { throw new Error('Respon server tidak valid (' + res.status + '). Pastikan PHP & database berjalan.'); }
  if (!j.ok) {
    if (res.status === 401 && U) { U = null; render(); }
    const e = new Error(j.error || 'Terjadi kesalahan'); e.status = res.status; throw e;
  }
  return j.data;
}
const get = (r, q) => api(r, { query: q });
const post = (r, b) => api(r, { method: 'POST', body: b });
/** Kirim form multipart: data = objek biasa, files = { namaField: selectorInputFile } */
function postForm(r, data, files = {}) {
  const fd = new FormData();
  for (const k in data) if (data[k] !== undefined && data[k] !== null) fd.append(k, data[k]);
  for (const k in files) { const f = $(files[k])?.files?.[0]; if (f) fd.append(k, f); }
  return api(r, { method: 'POST', body: fd });
}
/** Jalankan aksi; tampilkan toast sukses/gagal; render ulang bila sukses. */
async function act(fn, okMsg, rerender = true) {
  try { await fn(); if (okMsg) toast(okMsg); if (rerender) await render(); }
  catch (e) { toast(e.message, true); }
}
const ask = m => confirm(m);

/* ---- Form helpers ---- */
const val = id => ($(id)?.value ?? '').trim();
const chk = id => !!$(id)?.checked;
const dl = (t, id) => `${API}?r=file&t=${t}&id=${+id}`;
const fileLink = (t, id, nama) => nama ? `<a class="chip" href="${dl(t, id)}" download>📎 ${esc(nama)}</a>` : '';

/* ---- Tanggal (nilai dari server berupa waktu lokal WIB tanpa zona) ---- */
const pd = s => s ? new Date(String(s).replace(' ', 'T')) : null;
const fdt = s => { const d = pd(s); return d ? d.toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '–' };
const fd_ = s => { const d = pd(s + (String(s).length === 10 ? 'T00:00' : '')); return d ? d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '–' };
const dtLocal = s => s ? String(s).slice(0, 16).replace(' ', 'T') : '';

/* ---- Navigasi (disimpan di hash agar refresh tidak kembali ke awal) ---- */
function go(v, c, s) {
  stopQuiz(); V = { v, c, s, pn: null };
  const h = v === 'course' ? `course/${c}/${s}` : v;
  if (location.hash !== '#' + h) history.replaceState(null, '', '#' + h);
  render();
}
function readHash() {
  const h = location.hash.slice(1).split('/');
  const ok = ['dash', 'umum', 'profil', 'users', 'kelola'];
  if (h[0] === 'course' && +h[1] && ['tugas', 'presensi', 'pengumuman', 'peserta', 'ulangan'].includes(h[2])) V = { v: 'course', c: +h[1], s: h[2], pn: null };
  else V = { v: ok.includes(h[0]) ? h[0] : 'dash' };
  if (U && U.role !== 'admin' && ['users', 'kelola'].includes(V.v)) V = { v: 'dash' };
}
