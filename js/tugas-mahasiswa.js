/* Mahasiswa: Materi & Tugas, kumpul tugas (teks + file sungguhan) */
function tugasM(t) {
  const g = t.pengumpulan, k = 't' + t.id, graded = g && g.nilai !== null;
  const badges = (g ? `<span class="badge ok">Sudah dikumpulkan</span>` : `<span class="badge bad">Belum dikumpulkan</span>`)
    + (graded ? `<span class="badge ok">Nilai: ${g.nilai}/100</span>` : '')
    + (!t.dibuka ? `<span class="badge bad">Ditutup</span>` : '');
  let form = '';
  if (graded) form = g.catatan ? `<p><small>Catatan dosen:</small> ${esc(g.catatan)}</p>` : '';
  else if (t.bisa_kumpul) {
    form = `${t.terima_teks ? `<textarea id="t-${k}" placeholder="Tulis jawaban atau tempel link repositori">${esc(g?.jawaban)}</textarea>` : ''}
    ${t.terima_file ? `<input type="file" id="f-${k}">${g?.file_nama ? `<small>File terkirim: ${esc(g.file_nama)} (pilih file baru untuk mengganti)</small>` : ''}` : ''}
    <div style="margin-top:8px"><button class="btn" onclick="kumpul(${t.id})">${g ? 'Perbarui pengumpulan' : 'Kumpulkan tugas'}</button></div>`;
  } else if (!t.dibuka) form = '<p><span class="badge bad">Pengumpulan ditutup</span></p>';
  else form = '<p><span class="badge bad">Batas waktu terlewat</span></p>';
  const sent = g ? `<div class="chips">${fileLink('tugas', g.id, g.file_nama)}${g.dikumpul_at ? `<small>Dikumpulkan ${fdt(g.dikumpul_at)}</small>` : ''}</div>` : '';
  return `<div class="tg"><div class="row"><b>${esc(t.judul)}</b>${badges}</div><p style="white-space:pre-wrap">${esc(t.deskripsi)}</p><small>Batas: ${fdt(t.deadline)}</small>${(graded || !t.bisa_kumpul) ? sent : ''}${form}</div>`;
}

function sTugas() {
  const m = K.materi, t = K.tugas;
  const cards = K.pertemuan.map(p => `<div class="card"><div class="mh"><b class="no">${p.pertemuan_ke}</b><div><small>Pertemuan ${p.pertemuan_ke} • ${fd_(p.tanggal)}</small><h3>${esc(p.topik)}</h3></div></div>
    ${p.deskripsi ? `<p class="mu">${esc(p.deskripsi)}</p>` : ''}${matChips(m.filter(x => x.pertemuan_id === p.id))}${t.filter(x => x.pertemuan_id === p.id).map(tugasM).join('')}</div>`).join('');
  const lain = t.filter(x => x.pertemuan_id === null);
  return head(K.matkul) + (cards || empty('Belum ada pertemuan.')) + (lain.length ? `<div class="card"><h3>Tugas lainnya</h3>${lain.map(tugasM).join('')}</div>` : '');
}

const kumpul = id => act(async () => {
  const k = 't' + id;
  const data = { tugas_id: id }, ta = $('#t-' + k);
  if (ta) data.jawaban = ta.value;
  await postForm('tugas/kumpul', data, { file: '#f-' + k });
}, 'Tugas dikumpulkan');
