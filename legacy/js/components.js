/* Komponen UI bersama */
function head(c){const dsn=U.role=='dsn',done=S.m[c.id].length;return `<div class="hero"><span class="badge">${c.k} • ${c.sks} SKS</span><h1>${esc(c.n)} (${c.k})</h1><p>Dosen: <b>${esc(c.dos)}</b></p><p>${dsn?'Mode kelola dosen':'Mode mahasiswa'} • ${done} pertemuan terdaftar</p></div>`}
const mats=p=>p.mat.length?`<div class="chips">${p.mat.map(m=>`<span class="chip">📄 ${esc(m)}</span>`).join('')}</div>`:'';
function pickP(c){return `<select onchange="V.pn=+this.value;render()" style="max-width:360px">${S.m[c.id].map(p=>`<option value="${p.n}" ${V.pn==p.n?'selected':''}>Pertemuan ${p.n} – ${esc(p.title)}</option>`).join('')}</select>`}
