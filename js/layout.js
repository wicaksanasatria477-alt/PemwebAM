/* Kerangka halaman: sidebar, topbar, dan router render() */
let RID = 0;

async function render() {
  const my = ++RID;
  if (!U) return login();
  let body;
  try { body = await view(); }
  catch (e) {
    if (my !== RID || !U) return;
    body = `<div class="card"><h3>Gagal memuat halaman</h3><p>${esc(e.message)}</p><button class="btn g" onclick="go('dash')">Ke beranda</button></div>`;
  }
  if (my !== RID) return;           // ada render yang lebih baru
  $('#app').innerHTML = shell(body);
  afterRender();
}

async function view() {
  const v = V.v;
  if (v === 'dash') return dash();
  if (v === 'umum') return ann(null);
  if (v === 'profil') return profil();
  if (v === 'users') return adminUsers();
  if (v === 'kelola') return adminMatkul();
  // halaman kelas
  K = await get('matkul/get', { id: V.c });
  if (V.quiz) return quizView();
  const m = K.bisa_kelola, s = V.s;
  if (s === 'tugas') return m ? aTugas() : sTugas();
  if (s === 'presensi') return m ? aPres() : sPres();
  if (s === 'pengumuman') return ann(K.matkul.id);
  if (s === 'ulangan') return m ? aUlangan() : sUlangan();
  return peserta();
}

function shell(body) {
  const c = V.v === 'course' ? CS.find(x => x.id == V.c) || K?.matkul : null;
  const nav = (k, ic, l) => `<button class="nav ${V.v === k ? 'on' : ''}" onclick="go('${k}')">${ic} ${l}</button>`;
  const mgr = isMgr();
  const subs = mgr
    ? [['tugas', 'Pengaturan Tugas'], ['presensi', 'Pengaturan Presensi'], ['pengumuman', 'Pengumuman'], ['ulangan', 'Ulangan'], ['peserta', 'Participant']]
    : [['pengumuman', 'Pengumuman'], ['tugas', 'Materi & Tugas'], ['presensi', 'Presensi'], ['ulangan', 'Ulangan'], ['peserta', 'Participant']];
  let side = `<div class="logo">SPADA Lite<small>Universitas Pembangunan Nasional</small></div>`
    + nav('dash', '📅', mgr ? 'Jadwal Mengajar' : 'Jadwal Kuliah') + nav('umum', '📢', 'Pengumuman Umum');
  if (U.role === 'admin') side += `<div class="sec">ADMINISTRASI</div>` + nav('users', '👥', 'Kelola Pengguna') + nav('kelola', '🏫', 'Kelola Mata Kuliah');
  if (c) side += `<div class="sec">MATA KULIAH TERPILIH</div><div class="cur"><b>${esc(c.nama)} ${esc(c.kode)}</b>${subs.map(([k, l]) =>
    `<button class="nav sub ${V.s === k ? 'on' : ''}" onclick="go('course',${+c.id},'${k}')">${l}</button>`).join('')}</div>`;
  side += `<div class="sec">${U.role === 'dosen' ? 'MATA KULIAH YANG DIAMPU' : U.role === 'admin' ? 'SEMUA MATA KULIAH' : 'MATA KULIAH SEMESTER INI'}</div>`
    + (CS.filter(x => !c || x.id != c.id).map(x => `<button class="nav" onclick="go('course',${+x.id},'tugas')">🎓 ${esc(x.nama)} ${esc(x.kode)}</button>`).join('') || '<small class="mu" style="padding:0 10px">Belum ada.</small>')
    + `<div class="sec">AKUN</div>` + nav('profil', '👤', 'Profil Saya');
  const av = U.has_foto ? `<img src="${dl('foto', U.id)}&_=${U.fv || 0}" alt="">` : esc(U.nama[0] || '?');
  return `<div class="app"><aside>${side}</aside><main><div class="top"><span class="badge">${ROLE_LABEL[U.role]}</span><span>${esc(U.nama)}</span><span class="av">${av}</span><button class="btn g" onclick="logout()">Keluar</button></div>${body}</main></div>`;
}

function afterRender() { if (V.quiz) runQuizTimer(); }

/* Komponen bersama */
function head(c) {
  return `<div class="hero"><span class="badge">${esc(c.kode)} • ${+c.sks} SKS</span><h1>${esc(c.nama)} (${esc(c.kode)})</h1><p>Dosen: <b>${esc(c.dosen_nama)}</b></p>
  <p>${K.bisa_kelola ? 'Mode kelola' : 'Mode mahasiswa'} • ${K.pertemuan.length} pertemuan terdaftar • ${K.jumlah_peserta} mahasiswa</p></div>`;
}
const matChips = (list, canDel) => list.length ? `<div class="chips">${list.map(m =>
  `<span class="chip"><a href="${dl('materi', m.id)}" download>📄 ${esc(m.judul)}</a> <small>(${esc(m.file_nama)})</small>${canDel ? ` <button class="x" title="Hapus materi" onclick="delMateri(${+m.id})">×</button>` : ''}</span>`).join('')}</div>` : '';
const empty = t => `<div class="card mu">${esc(t)}</div>`;
/** Pemilih pertemuan; V.pn = id pertemuan terpilih */
function curP() {
  const ps = K.pertemuan; if (!ps.length) return null;
  if (!ps.find(p => p.id == V.pn)) {
    const today = new Date().toISOString().slice(0, 10);
    V.pn = ([...ps].reverse().find(p => p.tanggal <= today) || ps[0]).id;
  }
  return ps.find(p => p.id == V.pn);
}
const pickP = () => `<select onchange="V.pn=+this.value;render()" style="max-width:420px">${K.pertemuan.map(p =>
  `<option value="${p.id}" ${V.pn == p.id ? 'selected' : ''}>Pertemuan ${p.pertemuan_ke} – ${esc(p.topik)} (${fd_(p.tanggal)})</option>`).join('')}</select>`;
