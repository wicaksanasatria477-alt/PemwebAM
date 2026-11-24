/* Admin: kelola pengguna, mata kuliah, dan jadwal */
const ROLE_NAME = { admin: 'Admin', dosen: 'Dosen', mahasiswa: 'Mahasiswa' };

/* ---------- Pengguna ---------- */
async function adminUsers() {
  const list = await get('users/list', { role: V.ur || '', q: V.uq || '' });
  const ed = V.eu === 0 ? {} : V.eu ? list.find(x => x.id === V.eu) || (await get('users/list')).find(x => x.id === V.eu) : null;
  const form = ed ? `<div class="card"><h3>${ed.id ? 'Ubah pengguna' : 'Tambah pengguna'}</h3><div class="grid2">
    <div><label>Nama lengkap</label><input type="text" id="un" value="${esc(ed.nama)}" maxlength="100"></div>
    <div><label>Email</label><input type="text" id="ue" value="${esc(ed.email)}" maxlength="100"></div>
    <div><label>NIM / NIP</label><input type="text" id="ui" value="${esc(ed.nim_nip)}" maxlength="30"></div>
    <div><label>Peran</label><select id="ur">${Object.entries(ROLE_NAME).map(([k, l]) => `<option value="${k}" ${(ed.role || 'mahasiswa') === k ? 'selected' : ''}>${l}</option>`).join('')}</select></div>
    <div><label>Kata sandi ${ed.id ? '<small>(kosongkan jika tidak diganti)</small>' : ''}</label><input type="password" id="up" autocomplete="new-password" placeholder="min. 6 karakter"></div></div>
    <div class="row" style="margin-top:10px"><button class="btn" onclick="saveUser(${ed.id || 0})">Simpan</button><button class="btn g" onclick="V.eu=null;render()">Batal</button></div></div>` : '';
  return `<div class="hero"><h1>Kelola Pengguna</h1><p>Admin, dosen, dan mahasiswa.</p></div>${form}
  <div class="card"><div class="row"><select id="uf" style="max-width:180px" onchange="V.ur=this.value;render()"><option value="">Semua peran</option>${Object.entries(ROLE_NAME).map(([k, l]) => `<option value="${k}" ${V.ur === k ? 'selected' : ''}>${l}</option>`).join('')}</select>
  <input type="text" id="uq" placeholder="Cari nama / NIM / email" style="max-width:260px" value="${esc(V.uq)}" onkeydown="if(event.key==='Enter'){V.uq=this.value.trim();render()}">
  <button class="btn" onclick="V.eu=0;render()">+ Tambah pengguna</button></div>
  <div class="wrap"><table><tr><th>Nama</th><th>NIM/NIP</th><th>Email</th><th>Peran</th><th></th></tr>${list.map(u => `<tr><td>${esc(u.nama)}</td><td>${esc(u.nim_nip)}</td><td>${esc(u.email)}</td><td><span class="badge">${ROLE_NAME[u.role]}</span></td>
  <td style="white-space:nowrap"><button class="btn g" onclick="V.eu=${u.id};render();scrollTo(0,0)">Ubah</button> ${u.id !== U.id ? `<button class="btn d" onclick="delUser(${u.id})">Hapus</button>` : ''}</td></tr>`).join('')
    || '<tr><td colspan="5" class="mu">Tidak ada data.</td></tr>'}</table></div></div>`;
}
const saveUser = id => act(async () => {
  await post('users/save', { id: id || undefined, nama: val('#un'), email: val('#ue'), nim_nip: val('#ui'), role: val('#ur'), password: $('#up').value });
  V.eu = null; await loadCS();
}, 'Pengguna disimpan');
const delUser = id => ask('Hapus pengguna ini? Seluruh data tugas, presensi, dan nilainya ikut terhapus.') && act(() => post('users/delete', { id }), 'Pengguna dihapus');

/* ---------- Mata kuliah + jadwal ---------- */
async function adminMatkul() {
  const [list, dosen] = await Promise.all([get('matkul/list'), get('users/list', { role: 'dosen' })]);
  CS = list;
  const ed = V.em === 0 ? {} : V.em ? list.find(x => x.id === V.em) : null;
  let form = '';
  if (ed) {
    form = `<div class="card"><h3>${ed.id ? 'Ubah mata kuliah' : 'Tambah mata kuliah'}</h3><div class="grid2">
    <div><label>Kode</label><input type="text" id="mk" value="${esc(ed.kode)}" maxlength="20"></div>
    <div><label>Nama</label><input type="text" id="mn" value="${esc(ed.nama)}" maxlength="100"></div>
    <div><label>SKS</label><input type="number" id="ms" min="1" max="6" value="${ed.sks ?? 3}"></div>
    <div><label>Dosen pengampu</label><select id="md"><option value="">– pilih dosen –</option>${dosen.map(d => `<option value="${d.id}" ${ed.dosen_id === d.id ? 'selected' : ''}>${esc(d.nama)}</option>`).join('')}</select></div></div>
    <label>Deskripsi</label><textarea id="mx" maxlength="2000">${esc(ed.deskripsi)}</textarea>
    <div class="row" style="margin-top:8px"><button class="btn" onclick="saveMk(${ed.id || 0})">Simpan</button><button class="btn g" onclick="V.em=null;render()">Batal</button></div>
    ${ed.id ? jadwalEditor(ed) : '<p class="mu">Jadwal dapat ditambahkan setelah mata kuliah disimpan.</p>'}</div>`;
  }
  return `<div class="hero"><h1>Kelola Mata Kuliah</h1><p>Mata kuliah, dosen pengampu, jadwal, dan peserta kelas.</p></div>${form}
  <div class="card"><button class="btn" onclick="V.em=0;render();scrollTo(0,0)">+ Tambah mata kuliah</button>
  <div class="wrap"><table><tr><th>Kode</th><th>Mata kuliah</th><th>SKS</th><th>Dosen</th><th>Peserta</th><th>Jadwal</th><th></th></tr>${list.map(m => `<tr><td>${esc(m.kode)}</td><td>${esc(m.nama)}</td><td>${m.sks}</td><td>${esc(m.dosen_nama)}</td><td>${m.jumlah_peserta}</td>
  <td><small>${m.jadwal.map(j => `${j.hari} ${j.jam_mulai}–${j.jam_selesai} (${esc(j.ruang)})`).join('<br>') || '–'}</small></td>
  <td style="white-space:nowrap"><button class="btn g" onclick="V.em=${m.id};render();scrollTo(0,0)">Ubah</button> <button class="btn g" onclick="go('course',${m.id},'peserta')">Peserta</button> <button class="btn d" onclick="delMk(${m.id})">Hapus</button></td></tr>`).join('')
    || '<tr><td colspan="7" class="mu">Belum ada mata kuliah.</td></tr>'}</table></div></div>`;
}

function jadwalEditor(m) {
  return `<h3 style="margin-top:16px">Jadwal</h3>${m.jadwal.map(j => `<div class="row" style="margin-bottom:4px"><span class="pill">${j.hari}</span> ${j.jam_mulai}–${j.jam_selesai} • ${esc(j.ruang)} <button class="btn d" onclick="delJadwal(${j.id})">Hapus</button></div>`).join('') || '<p class="mu">Belum ada jadwal.</p>'}
  <div class="row" style="margin-top:8px"><select id="jh" style="max-width:130px">${DAY.map(h => `<option>${h}</option>`).join('')}</select>
  <input type="time" id="jm" value="08:00" style="max-width:130px"><input type="time" id="js" value="10:00" style="max-width:130px">
  <input type="text" id="jr" placeholder="Ruang" maxlength="50" style="max-width:180px"><button class="btn g" onclick="saveJadwal(${m.id})">+ Tambah jadwal</button></div>`;
}
const saveMk = id => act(async () => {
  const r = await post('matkul/save', { id: id || undefined, kode: val('#mk'), nama: val('#mn'), sks: val('#ms'), dosen_id: val('#md'), deskripsi: val('#mx') });
  V.em = r.id; await loadCS();      // tetap di form agar jadwal bisa langsung ditambahkan
}, 'Mata kuliah disimpan');
const delMk = id => ask('Hapus mata kuliah ini beserta seluruh pertemuan, materi, tugas, nilai, presensi, dan pengumumannya?') && act(async () => { await post('matkul/delete', { id }); V.em = null; await loadCS(); }, 'Mata kuliah dihapus');
const saveJadwal = mid => act(() => post('jadwal/save', { matkul_id: mid, hari: val('#jh'), jam_mulai: val('#jm'), jam_selesai: val('#js'), ruang: val('#jr') }), 'Jadwal ditambahkan');
const delJadwal = id => ask('Hapus jadwal ini?') && act(() => post('jadwal/delete', { id }), 'Jadwal dihapus');
