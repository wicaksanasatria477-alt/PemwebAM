/* Dosen/Admin: pengaturan presensi per pertemuan (disimpan ke tabel absensi) */
async function aPres() {
  const p = curP();
  if (!p) return head(K.matkul) + empty('Belum ada pertemuan.');
  const rows = await get('presensi/list', { pertemuan_id: p.id });
  const cnt = s => rows.filter(r => r.status === s).length;
  return head(K.matkul) + `<div class="card"><h2>Pengaturan Presensi</h2>${pickP()}
  <p><small>Hadir ${cnt('hadir')} • Izin ${cnt('izin')} • Sakit ${cnt('sakit')} • Alpa ${cnt('alpa')} • Belum diisi ${rows.filter(r => !r.status).length}</small></p>
  <div class="wrap"><table><tr><th>Mahasiswa</th><th>Keterangan</th><th>Bukti</th></tr>${rows.map(r => `<tr><td>${esc(r.nama)}<br><small>${esc(r.nim_nip)}</small></td>
  <td><select onchange="setAtt(${p.id},${r.user_id},this.value)">${['', 'hadir', 'izin', 'sakit', 'alpa'].map(s => `<option value="${s}" ${(r.status || '') === s ? 'selected' : ''}>${s ? ST_LABEL[s] : '– belum diisi –'}</option>`).join('')}</select></td>
  <td>${r.status === 'izin' || r.status === 'sakit' ? `${esc(r.catatan) || '<small>(tanpa catatan)</small>'}<div class="chips">${fileLink('absensi', r.absensi_id, r.bukti_nama)}</div>` : '<small>–</small>'}</td></tr>`).join('')
    || '<tr><td colspan="3" class="mu">Belum ada mahasiswa terdaftar.</td></tr>'}</table></div></div>`;
}
const setAtt = (pid, uid, status) => act(() => post('presensi/set', { pertemuan_id: pid, user_id: uid, status }), 'Presensi diperbarui');
