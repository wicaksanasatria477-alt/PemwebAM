/* Kerangka halaman: sidebar, topbar, router render() */
function render(){if(!U)return login();const c=V.c?cc():null,cs=mine(),dsn=U.role=='dsn';
const subs=dsn?[['tugas','Pengaturan Tugas'],['presensi','Pengaturan Presensi'],['pengumuman','Pengumuman'],['peserta','Participant']]:[['pengumuman','Pengumuman'],['tugas','Tugas'],['presensi','Presensi'],['peserta','Participant']];
let side=`<div class="logo">SPADA Lite<small>Universitas Pembangunan Nasional</small></div><button class="nav ${V.v=='dash'?'on':''}" onclick="go('dash')">📅 ${dsn?'Jadwal Mengajar':'Jadwal Kuliah'}</button>`;
if(c)side+=`<div class="sec">MATA KULIAH TERPILIH</div><div class="cur"><b>${esc(c.n)} ${c.k}</b>${subs.map(([k,l])=>`<button class="nav sub ${V.s==k?'on':''}" onclick="go('course','${c.id}','${k}')">${l}</button>`).join('')}</div>`;
side+=`<div class="sec">${dsn?'MATA KULIAH DI KELAS (DOSEN PENGAMPU)':'MATA KULIAH SEMESTER INI'}</div>`+cs.filter(x=>!c||x.id!=c.id).map(x=>`<button class="nav" onclick="go('course','${x.id}','tugas')">🎓 ${esc(x.n)} ${x.k}</button>`).join('');
let body=V.v=='dash'?dash():V.s=='tugas'?(dsn?aTugas(c):sTugas(c)):V.s=='presensi'?(dsn?aPres(c):sPres(c)):V.s=='pengumuman'?ann(c):peserta(c);
$('#app').innerHTML=`<div class="app"><aside>${side}</aside><main><div class="top"><span class="badge">${dsn?'Portal Dosen & Admin':'Portal Mahasiswa'}</span><span>${esc(U.name)}</span><span class="av">${esc(U.name[0])}</span><button class="btn g" onclick="U=null;V={v:'dash',pn:1};render()">Keluar</button></div>${body}</main></div>`}
