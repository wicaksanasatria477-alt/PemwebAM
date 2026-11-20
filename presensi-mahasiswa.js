/* Mahasiswa: Presensi hadir/absen/izin */
function sPres(c){const n=V.pn,a=ea(c.id,n)[U.id]||{},st=a.st||'';
return head(c)+`<div class="card"><h2>Presensi</h2>${pickP(c)}<p>Status saat ini: <span class="badge ${st=='Hadir'?'ok':st?'bad':''}">${st||'Belum diisi'}</span></p>
<div class="row">${['Hadir','Absen','Izin'].map(s=>`<label><input type="radio" name="st" value="${s}" ${st==s?'checked':''} onchange="V.draft=this.value;pv()"> ${s}</label>`).join('')}</div>
<div id="iz" style="display:${st=='Izin'?'block':'none'}"><textarea id="pn" placeholder="Alasan izin">${esc(a.note)}</textarea><label>Foto / file bukti</label><input type="file" id="pf">${a.file?`<small>Terkirim: ${esc(a.file)}</small>`:''}</div>
<div style="margin-top:8px"><button class="btn" onclick="savePres('${c.id}')">Simpan presensi</button></div></div>`}
const pv=()=>{$('#iz').style.display=document.querySelector('input[name=st]:checked')?.value=='Izin'?'block':'none'};
function savePres(c){const s=document.querySelector('input[name=st]:checked')?.value;if(!s)return toast('Pilih Hadir, Absen, atau Izin');const o=ea(c,V.pn)[U.id]||{};ea(c,V.pn)[U.id]=s=='Izin'?{st:s,note:$('#pn').value,file:fl('#pf')||o.file||''}:{st:s};render();toast('Presensi tersimpan')}
