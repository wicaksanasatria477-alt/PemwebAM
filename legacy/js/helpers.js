/* Helper umum & state aplikasi */
const $=s=>document.querySelector(s),esc=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const toM=t=>{const[a,b]=t.split(':');return +a*60+ +b};
let U=null,V={v:'dash',pn:1},TOAST='';
const toast=m=>{const e=document.createElement('div');e.className='toast';e.textContent=m;document.body.appendChild(e);setTimeout(()=>e.remove(),2200)};
const mine=()=>U.role=='mhs'?C:C.filter(c=>c.dos==D1);
const cc=()=>C.find(c=>c.id==V.c);
const ex=(c,n)=>S.sub[c+'|'+n]=S.sub[c+'|'+n]||{};
const ea=(c,n)=>S.att[c+'|'+n]=S.att[c+'|'+n]||{};
const go=(v,c,s)=>{V={v,c,s,pn:V.pn||1};render()};
const fl=id=>$(id)?.files[0]?.name||'';
