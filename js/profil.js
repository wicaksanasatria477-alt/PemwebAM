/* Profil: ubah nama/email, kata sandi, foto */
async function profil() {
  const av = U.has_foto ? `<img src="${dl('foto', U.id)}&_=${U.fv || 0}" alt="" style="width:96px;height:96px;border-radius:50%;object-fit:cover">` : `<span class="av" style="width:96px;height:96px;font-size:38px">${esc(U.nama[0])}</span>`;
  return `<div class="hero"><h1>Profil Saya</h1><p>${ROLE_NAME[U.role]} • ${esc(U.nim_nip)}</p></div>
  <div class="card"><h3>Foto profil</h3><div class="row">${av}<div><input type="file" id="pfoto" accept="image/png,image/jpeg,image/webp,image/gif"><div class="row" style="margin-top:6px"><button class="btn g" onclick="upFoto()">Unggah foto</button>${U.has_foto ? '<button class="btn d" onclick="delFoto()">Hapus foto</button>' : ''}</div><small>JPG/PNG/WEBP/GIF, maks. 2 MB.</small></div></div></div>
  <div class="card"><h3>Data akun</h3><div class="grid2"><div><label>Nama lengkap</label><input type="text" id="pnm" value="${esc(U.nama)}" maxlength="100"></div>
  <div><label>Email</label><input type="text" id="pem" value="${esc(U.email)}" maxlength="100"></div></div>
  <h3 style="margin-top:14px">Ganti kata sandi <small>(opsional)</small></h3><div class="grid2"><div><label>Kata sandi lama</label><input type="password" id="pl0" autocomplete="current-password"></div>
  <div><label>Kata sandi baru</label><input type="password" id="pl1" autocomplete="new-password" placeholder="min. 6 karakter"></div></div>
  <div style="margin-top:10px"><button class="btn" onclick="saveProfil()">Simpan perubahan</button></div></div>`;
}
const saveProfil = () => act(async () => {
  U = { ...U, ...(await post('profil/update', { nama: val('#pnm'), email: val('#pem'), password_lama: $('#pl0').value, password_baru: $('#pl1').value })) };
}, 'Profil disimpan');
const upFoto = () => {
  if (!$('#pfoto').files[0]) return toast('Pilih foto terlebih dahulu', true);
  return act(async () => { await postForm('profil/foto', {}, { foto: '#pfoto' }); U.has_foto = true; U.fv = Date.now(); }, 'Foto diperbarui');
};
const delFoto = () => act(async () => { await post('profil/foto_hapus'); U.has_foto = false; }, 'Foto dihapus');
