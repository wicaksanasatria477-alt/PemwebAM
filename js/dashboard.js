/* Dashboard: statistik + jadwal mingguan dari tabel jadwal */
const DAY = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
const HOUR_PX = 48;

async function dash() {
  const d = await get('dashboard');
  CS = d.matkul;
  const mgr = isMgr(), st = d.stat;
  const items = [];
  d.matkul.forEach(c => c.jadwal.forEach(j => items.push({ c, j })));

  // rentang jam mengikuti data jadwal (min. 07.00-17.00)
  let h0 = 7, h1 = 17;
  items.forEach(({ j }) => { h0 = Math.min(h0, Math.floor(toM(j.jam_mulai) / 60)); h1 = Math.max(h1, Math.ceil(toM(j.jam_selesai) / 60)); });
  const n = h1 - h0, today = (new Date().getDay() + 6) % 7;

  let g = `<div class="tm"><div style="height:36px"></div>${Array.from({ length: n }, (_, i) => `<div>${String(h0 + i).padStart(2, '0')}:00</div>`).join('')}</div>`;
  DAY.forEach((day, i) => {
    g += `<div><div class="dh ${i === today ? 'td' : ''}">${day}</div><div class="dc" style="height:${n * HOUR_PX}px">${items.filter(x => x.j.hari_no === i + 1).map(({ c, j }) => {
      const t = (toM(j.jam_mulai) - h0 * 60) / 60 * HOUR_PX, h = (toM(j.jam_selesai) - toM(j.jam_mulai)) / 60 * HOUR_PX - 3;
      return `<div class="bl" style="top:${t}px;height:${h}px" onclick="go('course',${+c.id},'tugas')"><b>${esc(c.nama)} (${esc(c.kode)})</b>${j.jam_mulai}–${j.jam_selesai} • ${esc(j.ruang)}<br><span class="pill">${esc(c.dosen_nama)}</span></div>`;
    }).join('')}</div></div>`;
  });

  const stats = U.role === 'admin'
    ? `<div><b>${d.matkul.length}</b>Mata kuliah</div><div><b>${st.butuh}</b>${st.label}</div><div><b>${items.length}</b>Sesi jadwal / minggu</div>`
    : `<div><b>${st.sks}</b>${mgr ? 'Total beban SKS' : 'SKS diambil'}</div><div><b>${st.matkul}</b>Mata kuliah</div><div><b>${st.butuh}</b>${st.label}</div>`;
  const umum = d.pengumuman.length ? `<div class="card"><h2>Pengumuman umum</h2>${d.pengumuman.map(a => `<div style="margin-bottom:8px"><small>${fdt(a.created_at)} • ${esc(a.penulis)}</small><h3>${esc(a.judul)}</h3><p style="white-space:pre-wrap;margin:0">${esc(a.isi)}</p></div>`).join('')}</div>` : '';

  return `<div class="hero"><small style="color:#cfe0ff">Semester Ganjil 2026/2027 • Informatika S1</small><h1>Selamat datang, ${esc(U.nama)}</h1><div class="stats">${stats}</div></div>
  ${umum}<div class="card"><h2>${mgr ? 'Jadwal Mengajar' : 'Jadwal Kuliah Mingguan'}</h2><small>Klik kotak mata kuliah untuk membuka kelas.</small>
  ${items.length ? `<div class="wrap"><div class="sg">${g}</div></div>` : '<p class="mu">Belum ada jadwal.</p>'}</div>`;
}
