/* Mahasiswa: presensi hadir / izin / sakit + bukti (upload nyata) */
const ST_LABEL = { hadir: 'Hadir', izin: 'Izin', sakit: 'Sakit', alpa: 'Alpa' };
const stBadge = s => `<span class="badge ${s === 'hadir' ? 'ok' : s ? 'bad' : ''}">${s ? ST_LABEL[s] : 'Belum diisi'}</span>`;

function sPres() {
  const p = curP();
  if (!p) return head(K.matkul) + empty('Belum ada pertemuan.');
  const a = p.absensi || {}, st = a.status || '', locked = st === 'alpa';
  const hadir = K.pertemuan.filter(x => x.absensi?.status === 'hadir').length;
  const extra = st === 'izin' || st === 'sakit';
  return head(K.matkul) + `<div class="card"><h2>Presensi</h2>${pickP()}
  <p>Status saat ini: ${stBadge(st)}${locked ? ' <small>(ditetapkan dosen; hubungi dosen untuk mengubah)</small>' : ''}</p>
  <div class="row">${['hadir', 'izin', 'sakit'].map(s => `<label><input type="radio" name="st" value="${s}" ${st === s ? 'checked' : ''} ${locked ? 'disabled' : ''} onchange="pv()"> ${ST_LABEL[s]}</label>`).join('')}</div>
  <div id="iz" style="display:${extra ? 'block' : 'none'}"><textarea id="pn" placeholder="Alasan izin / sakit" maxlength="255">${esc(a.catatan)}</textarea>
  <label>Foto / file bukti (pdf, jpg, png, maks. 10 MB)</label><input type="file" id="pf">${a.bukti_nama ? `<div class="chips">${fileLink('absensi', a.id, a.bukti_nama)}</div>` : ''}</div>
  <div style="margin-top:8px"><button class="btn" onclick="savePres(${p.id})" ${locked ? 'disabled' : ''}>Simpan presensi</button></div></div>
  <div class="card"><h3>Rekap kehadiran <small>(${hadir}/${K.pertemuan.length} hadir)</small></h3><div class="wrap"><table><tr><th>Pertemuan</th><th>Tanggal</th><th>Status</th></tr>${K.pertemuan.map(x =>
    `<tr><td>${x.pertemuan_ke}. ${esc(x.topik)}</td><td>${fd_(x.tanggal)}</td><td>${stBadge(x.absensi?.status)}</td></tr>`).join('')}</table></div></div>`;
}

const pv = () => { const s = document.querySelector('input[name=st]:checked')?.value; $('#iz').style.display = (s === 'izin' || s === 'sakit') ? 'block' : 'none'; };
const savePres = pid => {
  const s = document.querySelector('input[name=st]:checked')?.value;
  if (!s) return toast('Pilih Hadir, Izin, atau Sakit', true);
  return act(() => postForm('presensi/isi', { pertemuan_id: pid, status: s, catatan: val('#pn') }, { bukti: '#pf' }), 'Presensi tersimpan');
};
