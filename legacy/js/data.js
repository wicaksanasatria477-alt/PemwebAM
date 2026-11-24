/* Data contoh: mata kuliah, mahasiswa, akun, pertemuan, pengumuman */
const DAY=['Senin','Selasa','Rabu','Kamis','Jumat'];
const D1='Mangaras Yanu F, S.T., M.Eng.';
const C=[
{id:'sd',n:'Struktur Data',k:'IF-F',d:1,s:'07:30',e:'10:00',sks:3,dos:'Uning Lestari, S.T., M.Kom.'},
{id:'sdg',n:'Sistem Digital',k:'IF-B',d:1,s:'10:00',e:'11:45',sks:2,dos:'Budi Santosa, S.Si., M.T.'},
{id:'imk',n:'Interaksi Manusia dan Komputer',k:'IF-B',d:1,s:'15:00',e:'16:45',sks:2,dos:'Dosen Pengampu IMK'},
{id:'ppw',n:'Praktikum Pemrograman Web',k:'IF-D',d:2,s:'08:00',e:'10:00',sks:1,dos:D1},
{id:'ro',n:'Riset Operasi',k:'IF-E',d:2,s:'10:00',e:'11:45',sks:2,dos:'Bambang Yuwono, S.T., M.T.'},
{id:'bn',n:'Bela Negara dan Widya Mwat Yasa',k:'IF-C',d:2,s:'15:00',e:'16:45',sks:2,dos:'Tim Pembina'},
{id:'kn',n:'Komputasi Numerik',k:'IF-C',d:3,s:'07:30',e:'09:15',sks:2,dos:'Dhimas Arief D, S.T., Ph.D.'},
{id:'bi',n:'Bahasa Indonesia',k:'IF-D',d:3,s:'15:00',e:'16:45',sks:2,dos:'Hermanto, S.Pd., M.Hum.'},
{id:'pw',n:'Pemrograman Web',k:'IF-F',d:5,s:'10:00',e:'11:45',sks:2,dos:D1},
{id:'geo',n:'Geoinformatika',k:'IF-E',d:5,s:'13:00',e:'15:30',sks:3,dos:'Andiko Putro Suryotomo, S.Kom., M.Cs.'},
{id:'kdm',n:'Komputer dan Masyarakat',k:'IF-F',d:5,s:'15:30',e:'17:15',sks:2,dos:'Dosen Pengampu KdM'}];
const ST=[['123210045','M. Fikri Pratama'],['123210046','Aulia Rahma'],['123210047','Bagas Arya'],['123210048','Putri Sekar'],['123210049','Dimas Prakoso'],['123210050','Nadia Safitri']];
const USERS=[{id:'123210045',pw:'mhs123',role:'mhs',name:'M. Fikri Pratama'},{id:'198402122010121004',pw:'dosen123',role:'dsn',name:D1}];
const mk=(n,t,d,m,tg)=>({n,title:t,desc:d,mat:m,tg});
const S={m:{},ann:{},sub:{},att:{}};
C.forEach(c=>{S.m[c.id]=[mk(1,'Pengantar & Kontrak Kuliah','Gambaran umum mata kuliah dan aturan perkuliahan.',['Modul-01.pdf'],{t:'Tugas 1: Rangkuman Materi',x:'Buat rangkuman satu halaman.',dl:'20 Maret 2026, 23:59',text:1,file:1,open:1}),mk(2,'Konsep Dasar','Teori dasar dan contoh kasus.',['Modul-02.pdf'],null)];
S.ann[c.id]=[{d:'10 Nov 2026',t:'Selamat datang di kelas '+c.n,x:'Silakan baca kontrak kuliah dan siapkan perangkat.',f:''}]});
S.m.ppw=[mk(1,'Pengantar HTML5, Semantic Web & Setup','Arsitektur client-server, HTML5, VS Code, Git.',['Modul-01-HTML5-Dasar.pdf'],{t:'Latihan Membuat CV Web Statis',x:'Buat CV dengan tag semantik.',dl:'15 Feb, 19:40',text:1,file:1,open:1}),mk(2,'CSS3 Modern, Flexbox & Grid Layout','Responsive design dengan media query dan Grid.',['Modul-02-CSS3.pdf','Slide-CSS-Modern.pdf'],{t:'Layouting Landing Page Responsive',x:'Landing page responsif tanpa framework.',dl:'22 Feb, 21:15',text:1,file:1,open:1}),mk(3,'JavaScript DOM Manipulation & Event Handling','Query selector, event bubbling, validasi form.',['Modul-03-DOM-Events.pdf','Starter-Code-JS.zip'],{t:'Tugas 03: Interactive Todo List & Calculator',x:'Buat SPA sederhana dengan event click, keydown, dan validasi form. Unggah link GitHub atau ZIP.',dl:'28 Feb, 23:59',text:1,file:1,open:1}),mk(4,'Asynchronous JavaScript (AJAX & Fetch API)','Promise, async/await, Fetch API.',[],null)];
S.sub['ppw|3']={'123210046':{text:'github.com/aulia/todo',file:'',nilai:null},'123210047':{text:'',file:'todo.zip',nilai:88}};
S.att['ppw|1']={'123210045':{st:'Hadir'},'123210046':{st:'Izin',note:'Sakit, surat dokter terlampir',file:'surat-dokter.jpg'}};
