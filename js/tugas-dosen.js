/* Dosen/Admin: pertemuan, materi (upload nyata), tugas, penilaian — lengkap tambah/ubah/hapus */
async function aTugas() {
  let sub = null;
  if (V.open) { try { sub = await get('tugas/pengumpulan', { tugas_id: V.open }); } catch { V.open = null; } }
  const cards = K.pertemuan.map(p => pertemuanCard(p, sub)).join('');
  return head(K.matkul) + `<div class="row" style="margin-bottom:10px"><button class="btn" onclick="addP()">+ Tambah pertemuan</button></div>`
    + (cards || empty('Belum ada pertemuan. Klik "Tambah pertemuan".'))
    + (K.tugas.some(t => t.pertemuan_id === null) ? `<div class="card"><h3>Tugas tanpa pertemuan</h3>${K.tugas.filter(t => t.pertemuan_id === null).map(t => tugasForm(t, null, sub)).join('')}</div>` : '');
}

function pertemuanCard(p, sub) {
  const k = p.id;
  return `<div class="card"><div class="mh"><b class="no">${p.pertemuan_ke}</b><div style="flex:1">
    <input type="text" id="pt-${k}" value="${esc(p.topik)}" placeholder="Topik pertemuan" maxlength="150">
    <input type="text" id="pd-${k}" value="${esc(p.deskripsi)}" placeholder="Deskripsi singkat" maxlength="2000">
    <input type="date" id="pl-${k}" value="${esc(p.tanggal)}" style="max-width:200px"></div></div>
  <div class="row" style="margin:6px 0"><button class="btn" onclick="saveP(${k})">Simpan pertemuan</button><button class="btn d" onclick="delP(${k})">Hapus pertemuan</button></div>
  ${matChips(K.materi.filter(x => x.pertemuan_id === p.id), true)}
  <div class="row"><input type="file" id="mf-${k}" style="max-width:260px"><input type="text" id="mj-${k}" placeholder="Judul materi (opsional)" style="max-width:240px"><button class="btn g" onclick="addMat(${k})">Unggah materi</button></div>
  ${K.tugas.filter(t => t.pertemuan_id === p.id).map(t => tugasForm(t, p.id, sub)).join('')}
  ${V.nt === p.id ? tugasForm(null, p.id, null) : `<div style="margin-top:8px"><button class="btn g" onclick="V.nt=${k};render()">+ Tambah tugas</button></div>`}</div>`;
}

function tugasForm(t, pid, sub) {
  const k = t ? 't' + t.id : 'n' + pid, x = t || { judul: '', deskripsi: '', deadline: '', terima_teks: true, terima_file: true, dibuka: true, rekap: { n: 0, belum: 0 } };
  const defDl = t ? dtLocal(x.deadline) : (() => { const d = new Date(Date.now() + 7 * 864e5); d.setMinutes(0); return new Date(d - d.getTimezoneOffset() * 6e4).toISOString().slice(0, 16); })();
  const panel = t && V.open === t.id && sub ? subTable(t, sub) : '';
  return `<div class="tg"><b>${t ? 'Tugas' : 'Tugas baru'}</b>
  <input type="text" id="tj-${k}" placeholder="Judul tugas" value="${esc(x.judul)}" maxlength="150">
  <textarea id="tx-${k}" placeholder="Instruksi tugas">${esc(x.deskripsi)}</textarea>
  <label class="mu">Batas waktu</label><input type="datetime-local" id="td-${k}" value="${defDl}" style="max-width:250px">
  <div class="row"><label><input type="checkbox" id="tb-${k}" ${x.terima_teks ? 'checked' : ''}> Jawaban teks</label><label><input type="checkbox" id="tf-${k}" ${x.terima_file ? 'checked' : ''}> Unggah file</label><label><input type="checkbox" id="to-${k}" ${x.dibuka ? 'checked' : ''}> Pengumpulan dibuka</label></div>
  <div class="row" style="margin-top:8px"><button class="btn" onclick="saveT(${t ? t.id : 0},${pid ?? 'null'},'${k}')">${t ? 'Simpan tugas' : 'Buat tugas'}</button>
  ${t ? `<button class="btn d" onclick="delT(${t.id})">Hapus tugas</button><button class="btn g" onclick="V.open=${V.open === t.id ? 'null' : t.id};render()">${V.open === t.id ? 'Tutup' : 'Lihat'} pengumpulan (${x.rekap.n}/${K.jumlah_peserta}${x.rekap.belum ? `, ${x.rekap.belum} belum dinilai` : ''})</button>`
    : `<button class="btn g" onclick="V.nt=null;render()">Batal</button>`}</div>${panel}</div>`;
}

function subTable(t, rows) {
  return `<div class="wrap" style="margin-top:10px"><table><tr><th>Mahasiswa</th><th>Jawaban</th><th style="width:230px">Nilai & catatan</th></tr>${rows.map(r => `<tr>
    <td>${esc(r.nama)}<br><small>${esc(r.nim_nip)}</small></td>
    <td>${r.id ? `${r.jawaban ? `<div style="white-space:pre-wrap">${esc(r.jawaban)}</div>` : ''}${fileLink('tugas', r.id, r.file_nama)}<br><small>${fdt(r.dikumpul_at)}</small>` : '<span class="badge bad">Belum mengumpulkan</span>'}</td>
    <td>${r.id ? `<input type="number" min="0" max="100" id="nl-${r.id}" placeholder="Nilai 0-100" value="${r.nilai ?? ''}"><input type="text" id="ct-${r.id}" placeholder="Catatan" maxlength="255" value="${esc(r.catatan)}"><button class="btn g" onclick="grade(${r.id})">Simpan nilai</button>` : '–'}</td></tr>`).join('')}</table></div>`;
}

/* ---- Aksi ---- */
const addP = () => act(() => post('pertemuan/save', { matkul_id: V.c, topik: 'Pertemuan baru' }), 'Pertemuan ditambahkan');
const saveP = id => act(() => post('pertemuan/save', { id, topik: val('#pt-' + id), deskripsi: val('#pd-' + id), tanggal: val('#pl-' + id) }), 'Pertemuan disimpan');
const delP = id => ask('Hapus pertemuan ini beserta materi, tugas, pengumpulan, dan presensinya? Tindakan ini tidak bisa dibatalkan.') && act(() => post('pertemuan/delete', { id }), 'Pertemuan dihapus');
const addMat = id => act(() => postForm('materi/upload', { pertemuan_id: id, judul: val('#mj-' + id) }, { file: '#mf-' + id }), 'Materi diunggah');
const delMateri = id => ask('Hapus materi ini?') && act(() => post('materi/delete', { id }), 'Materi dihapus');
const saveT = (id, pid, k) => act(async () => {
  await post('tugas/save', { id: id || undefined, matkul_id: V.c, pertemuan_id: pid, judul: val('#tj-' + k), deskripsi: val('#tx-' + k), deadline: val('#td-' + k),
    terima_teks: chk('#tb-' + k), terima_file: chk('#tf-' + k), dibuka: chk('#to-' + k) });
  V.nt = null;
}, 'Tugas disimpan');
const delT = id => ask('Hapus tugas ini beserta semua pengumpulan mahasiswa?') && act(async () => { await post('tugas/delete', { id }); if (V.open === id) V.open = null; }, 'Tugas dihapus');
const grade = id => act(() => post('tugas/nilai', { pengumpulan_id: id, nilai: val('#nl-' + id), catatan: val('#ct-' + id) }), 'Nilai tersimpan');
