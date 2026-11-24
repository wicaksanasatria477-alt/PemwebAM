/* Participant (admin dapat menambah/mengeluarkan mahasiswa dari kelas) */
let PES = [];
async function peserta() {
  const d = await get('peserta/list', { matkul_id: V.c });
  const adm = U.role === 'admin';
  PES = d.mahasiswa;
  let add = '';
  if (adm) {
    const all = await get('users/list', { role: 'mahasiswa' });
    const have = new Set(d.mahasiswa.map(x => x.id)), cand = all.filter(x => !have.has(x.id));
    add = `<div class="card"><h3>Tambah mahasiswa ke kelas</h3><div class="row"><select id="pu" style="max-width:340px"><option value="">– pilih mahasiswa –</option>${cand.map(x => `<option value="${x.id}">${esc(x.nama)} (${esc(x.nim_nip)})</option>`).join('')}</select>
    <button class="btn" onclick="addPeserta(false)">Tambah</button></div>
    <p class="mu" style="margin:10px 0 2px">Atau tempel banyak NIM sekaligus (pisahkan dengan spasi, koma, atau baris baru):</p><textarea id="pl" placeholder="123250001, 123250002"></textarea>
    <button class="btn g" onclick="addPeserta(true)">Tambah dari daftar NIM</button></div>`;
  }
  return head(K.matkul) + add + `<div class="card"><h3>Dosen</h3><p><span class="pill">${esc(d.dosen.nama)}</span></p><h3>Mahasiswa (${d.mahasiswa.length})</h3>
  <table>${d.mahasiswa.map(x => `<tr><td>${esc(x.nama)}</td><td class="mu">${esc(x.nim_nip)}</td>${adm ? `<td style="width:90px"><button class="btn d" onclick="delPeserta(${x.id})">Keluarkan</button></td>` : ''}</tr>`).join('')
    || '<tr><td class="mu">Belum ada mahasiswa.</td></tr>'}</table></div>`;
}
const addPeserta = bulk => act(async () => {
  const r = await post('peserta/add', bulk ? { matkul_id: V.c, nim_list: val('#pl') } : { matkul_id: V.c, user_id: val('#pu') || undefined });
  toast(`${r.ditambah} mahasiswa ditambahkan` + (r.tidak_ditemukan.length ? `. NIM tidak ditemukan: ${r.tidak_ditemukan.join(', ')}` : ''), r.tidak_ditemukan.length > 0);
  await loadCS();
});
const delPeserta = uid => ask(`Keluarkan ${PES.find(x => x.id === uid)?.nama || 'mahasiswa ini'} dari kelas ini? Nilai tugas, presensi, dan hasil ulangannya di kelas ini ikut terhapus.`)
  && act(async () => { await post('peserta/remove', { matkul_id: V.c, user_id: uid }); await loadCS(); }, 'Mahasiswa dikeluarkan');
