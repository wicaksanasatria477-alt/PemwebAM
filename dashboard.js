/* Dashboard jadwal (hero setelah login) */
function dash(){const cs=mine(),dsn=U.role=='dsn',sks=cs.reduce((a,c)=>a+c.sks,0);let n3=0;
cs.forEach(c=>S.m[c.id].forEach(p=>{if(!p.tg)return;const s=ex(c.id,p.n);if(dsn)Object.values(s).forEach(v=>{if(v.nilai==null)n3++});else if(p.tg.open&&!s[U.id])n3++}));
const today=new Date().getDay()-1;
let g=`<div class="tm"><div style="height:36px"></div>${Array.from({length:12},(_,i)=>`<div>${String(7+i).padStart(2,'0')}:00</div>`).join('')}</div>`;
DAY.forEach((d,i)=>{g+=`<div><div class="dh ${i==today?'td':''}">${d}</div><div class="dc">${cs.filter(c=>c.d==i+1).map(c=>{const t=(toM(c.s)-420)/60*48,h=(toM(c.e)-toM(c.s))/60*48-3;return `<div class="bl" style="top:${t}px;height:${h}px" onclick="go('course','${c.id}','tugas')"><b>${esc(c.n)} (${c.k})</b>${c.s}–${c.e} (${c.sks} SKS)<br><span class="pill">${esc(c.dos)}</span></div>`}).join('')}</div></div>`});
return `<div class="hero"><small style="color:#cfe0ff">Semester Genap 2025/2026 • Informatika S1</small><h1>${dsn?'Selamat datang, Bapak Mangaras':'Selamat datang kembali, M. Fikri Pratama'}</h1><div class="stats"><div><b>${sks}</b>${dsn?'Total beban SKS':'SKS diambil'}</div><div><b>${cs.length}</b>Mata kuliah</div><div><b>${n3}</b>${dsn?'Submisi perlu dinilai':'Tugas belum dikumpulkan'}</div></div></div>
<div class="card"><h2>${dsn?'Jadwal Mengajar':'Jadwal Kuliah Mingguan'}</h2><small>Klik kotak mata kuliah untuk membuka kelas.</small><div class="wrap"><div class="sg">${g}</div></div></div>`}
