/* Pengumuman: lihat, buat, ubah, hapus (+ lampiran). mid = null => pengumuman umum */
async function ann(mid) {
  const d = await get('pengumuman/list', { matkul_id: mid || 0 });
  const mgr = d.bisa_kelola, ed = mgr && V.ea ? d.daftar.find(a => a.id === V.ea) : null;
  const form = mgr ? `<div class="card"><h3>${ed ? 'Ubah pengumuman' : 'Buat pengumuman'}</h3><input type="text" id="at" placeholder="Judul" maxlength="150" value="${esc(ed?.judul)}">
    <textarea id="ax" placeholder="Isi pengumuman">${esc(ed?.isi)}</textarea><input type="file" id="af">
    ${ed?.file_nama ? `<label><input type="checkbox" id="ah"> Hapus lampiran saat ini (${esc(ed.file_nama)})</label>` : ''}
    <div class="row" style="margin-top:8px"><button class="btn" onclick="saveA(${mid || 0},${ed ? ed.id : 0})">${ed ? 'Simpan perubahan' : 'Terbitkan'}</button>${ed ? '<button class="btn g" onclick="V.ea=null;render()">Batal</button>' : ''}</div></div>` : '';
  const list = d.daftar.map(a => `<div class="card"><small>${fdt(a.created_at)} • ${esc(a.penulis)}</small><h3>${esc(a.judul)}</h3><p style="white-space:pre-wrap">${esc(a.isi)}</p>
    <div class="chips">${fileLink('pengumuman', a.id, a.file_nama)}</div>
    ${mgr ? `<div class="row"><button class="btn g" onclick="V.ea=${a.id};render();scrollTo(0,0)">Ubah</button><button class="btn d" onclick="delA(${a.id})">Hapus</button></div>` : ''}</div>`).join('');
  return (mid ? head(K.matkul) : `<div class="hero"><h1>Pengumuman Umum</h1><p>Informasi untuk seluruh civitas SPADA Lite.</p></div>`) + form + (list || empty('Belum ada pengumuman.'));
}
const saveA = (mid, id) => {
  if (!val('#at') || !val('#ax')) return toast('Judul dan isi wajib diisi', true);
  const data = { judul: val('#at'), isi: val('#ax') };
  if (id) { data.id = id; if (chk('#ah')) data.hapus_file = 1; } else if (mid) data.matkul_id = mid;
  return act(async () => { await postForm('pengumuman/save', data, { file: '#af' }); V.ea = null; }, id ? 'Pengumuman diperbarui' : 'Pengumuman terbit');
};
const delA = id => ask('Hapus pengumuman ini?') && act(() => post('pengumuman/delete', { id }), 'Pengumuman dihapus');
