/* Participant */
function peserta(c){return head(c)+`<div class="card"><h3>Dosen</h3><p><span class="pill">${esc(c.dos)}</span></p><h3>Mahasiswa (${ST.length})</h3><table>${ST.map(([i,n])=>`<tr><td>${esc(n)}</td><td class="mu">${i}</td></tr>`).join('')}</table></div>`}
