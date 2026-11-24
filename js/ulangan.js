/* Ulangan / kuis: dosen (kelola ulangan, soal, hasil) & mahasiswa (mengerjakan, nilai otomatis) */
let QT = null;   // interval timer kuis
const stopQuiz = () => { if (QT) clearInterval(QT); QT = null; if (typeof V !== 'undefined' && V) V.quiz = null; };

/* ---------- Mahasiswa ---------- */
async function sUlangan() {
  const d = await get('ulangan/list', { matkul_id: V.c });
  return head(K.matkul) + `<div class="card"><h2>Ulangan</h2>${d.daftar.map(u => `<div class="tg"><div class="row"><b>${esc(u.judul)}</b>
  <span class="badge">${u.durasi_menit} menit</span><span class="badge">${u.jumlah_soal} soal</span>
  ${u.nilai !== null ? `<span class="badge ok">Nilai: ${u.nilai}</span>` : `<button class="btn" ${u.jumlah_soal ? '' : 'disabled'} onclick="startQuiz(${u.id},${u.durasi_menit})">Mulai kerjakan</button>`}</div></div>`).join('')
    || '<p class="mu">Belum ada ulangan.</p>'}</div>`;
}

async function startQuiz(id, durasi) {
  if (!ask(`Mulai ulangan? Waktu ${durasi} menit langsung berjalan dan hasil hanya bisa dikirim sekali.`)) return;
  try {
    const q = await get('ulangan/mulai', { ulangan_id: id });
    V.quiz = { id, judul: q.ulangan.judul, soal: q.soal, end: Date.now() + q.sisa_detik * 1000, ans: {}, sending: false };
    render();
  } catch (e) { toast(e.message, true); }
}

function quizView() {
  const q = V.quiz;
  return head(K.matkul) + `<div class="card quiz"><div class="row" style="justify-content:space-between"><h2 style="margin:0">${esc(q.judul)}</h2><span class="badge bad" id="qt">--:--</span></div>
  ${q.soal.map((s, i) => `<div class="tg"><b>${i + 1}. ${esc(s.pertanyaan)}</b>${['a', 'b', 'c', 'd'].map(o => `<label class="opt"><input type="radio" name="q${s.id}" value="${o}" ${q.ans[s.id] === o ? 'checked' : ''} onchange="V.quiz.ans[${s.id}]='${o}'"> ${o.toUpperCase()}. ${esc(s['opsi_' + o])}</label>`).join('')}</div>`).join('')}
  <div class="row" style="margin-top:10px"><button class="btn" onclick="sendQuiz(false)">Kirim jawaban</button></div></div>`;
}

function runQuizTimer() {
  if (QT) clearInterval(QT);
  const tick = () => {
    const q = V.quiz; if (!q) return clearInterval(QT);
    const s = Math.max(0, Math.round((q.end - Date.now()) / 1000)), el = $('#qt');
    if (el) el.textContent = String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
    if (s <= 0) sendQuiz(true);
  };
  tick(); QT = setInterval(tick, 500);
}

async function sendQuiz(auto) {
  const q = V.quiz; if (!q || q.sending) return;
  if (!auto && !ask('Kirim jawaban sekarang? Jawaban tidak bisa diubah setelah dikirim.')) return;
  q.sending = true; clearInterval(QT);
  try {
    const r = await post('ulangan/kirim', { ulangan_id: q.id, jawaban: q.ans });
    V.quiz = null;
    toast(r.telat ? 'Waktu habis — nilai 0' : `Nilai Anda: ${r.nilai} (${r.benar}/${r.total} benar)`, r.telat);
    await render();
  } catch (e) { q.sending = false; toast(e.message, true); if (V.quiz) runQuizTimer(); }
}

/* ---------- Dosen / Admin ---------- */
async function aUlangan() {
  const d = await get('ulangan/list', { matkul_id: V.c });
  const ed = V.eu ? d.daftar.find(u => u.id === V.eu) : null;
  let detail = '';
  if (V.us) {                       // kelola soal
    const s = await get('soal/list', { ulangan_id: V.us });
    const es = V.es ? s.soal.find(x => x.id === V.es) : null;
    detail = `<div class="card"><h3>Soal: ${esc(s.ulangan.judul)}</h3>${s.soal.map((x, i) => `<div class="tg"><b>${i + 1}. ${esc(x.pertanyaan)}</b>${['a', 'b', 'c', 'd'].map(o => `<div ${x.jawaban_benar === o ? 'class="ok-t"' : ''}>${o.toUpperCase()}. ${esc(x['opsi_' + o])}${x.jawaban_benar === o ? ' ✔' : ''}</div>`).join('')}
    <div class="row" style="margin-top:6px"><button class="btn g" onclick="V.es=${x.id};render()">Ubah</button><button class="btn d" onclick="delSoal(${x.id})">Hapus</button></div></div>`).join('') || '<p class="mu">Belum ada soal.</p>'}
    <div class="tg"><b>${es ? 'Ubah soal' : 'Tambah soal'}</b><textarea id="sq" placeholder="Pertanyaan">${esc(es?.pertanyaan)}</textarea>
    ${['a', 'b', 'c', 'd'].map(o => `<input type="text" id="so-${o}" placeholder="Opsi ${o.toUpperCase()}" maxlength="255" value="${esc(es?.['opsi_' + o])}">`).join('')}
    <label class="mu">Jawaban benar</label><select id="sk" style="max-width:140px">${['a', 'b', 'c', 'd'].map(o => `<option value="${o}" ${es?.jawaban_benar === o ? 'selected' : ''}>${o.toUpperCase()}</option>`).join('')}</select>
    <div class="row" style="margin-top:8px"><button class="btn" onclick="saveSoal(${V.us},${es ? es.id : 0})">${es ? 'Simpan soal' : 'Tambah soal'}</button>${es ? '<button class="btn g" onclick="V.es=null;render()">Batal</button>' : ''}<button class="btn g" onclick="V.us=null;V.es=null;render()">Tutup</button></div></div></div>`;
  } else if (V.uh) {                // hasil
    const h = await get('ulangan/hasil', { ulangan_id: V.uh });
    detail = `<div class="card"><h3>Hasil: ${esc(d.daftar.find(u => u.id === V.uh)?.judul)}</h3><div class="wrap"><table><tr><th>Mahasiswa</th><th>Nilai</th><th>Selesai</th><th></th></tr>${h.map(r => `<tr><td>${esc(r.nama)}<br><small>${esc(r.nim_nip)}</small></td>
    <td>${r.nilai === null ? '<span class="badge bad">Belum</span>' : r.nilai}</td><td>${r.selesai_at ? fdt(r.selesai_at) : '–'}</td><td>${r.nilai !== null ? `<button class="btn g" onclick="resetHasil(${V.uh},${r.user_id})">Izinkan mengulang</button>` : ''}</td></tr>`).join('')}</table></div>
    <button class="btn g" style="margin-top:8px" onclick="V.uh=null;render()">Tutup</button></div>`;
  }
  return head(K.matkul) + `<div class="card"><h2>Ulangan</h2>${d.daftar.map(u => `<div class="tg"><div class="row"><b>${esc(u.judul)}</b><span class="badge">${u.durasi_menit} menit</span><span class="badge">${u.jumlah_soal} soal</span><span class="badge">${u.jumlah_selesai}/${K.jumlah_peserta} selesai</span></div>
  <div class="row" style="margin-top:6px"><button class="btn g" onclick="V.us=${u.id};V.uh=null;V.es=null;render()">Kelola soal</button><button class="btn g" onclick="V.uh=${u.id};V.us=null;render()">Hasil</button>
  <button class="btn g" onclick="V.eu=${u.id};render()">Ubah</button><button class="btn d" onclick="delUl(${u.id})">Hapus</button></div></div>`).join('') || '<p class="mu">Belum ada ulangan.</p>'}
  <div class="tg"><b>${ed ? 'Ubah ulangan' : 'Buat ulangan'}</b><input type="text" id="ut" placeholder="Judul ulangan" maxlength="150" value="${esc(ed?.judul)}">
  <label class="mu">Durasi (menit)</label><input type="number" id="ud" min="1" max="600" value="${ed?.durasi_menit ?? 30}" style="max-width:140px">
  <div class="row" style="margin-top:8px"><button class="btn" onclick="saveUl(${ed ? ed.id : 0})">${ed ? 'Simpan' : 'Buat ulangan'}</button>${ed ? '<button class="btn g" onclick="V.eu=null;render()">Batal</button>' : ''}</div></div></div>${detail}`;
}
const saveUl = id => act(async () => { await post('ulangan/save', { id: id || undefined, matkul_id: V.c, judul: val('#ut'), durasi_menit: val('#ud') }); V.eu = null; }, 'Ulangan disimpan');
const delUl = id => ask('Hapus ulangan beserta semua soal dan hasilnya?') && act(async () => { await post('ulangan/delete', { id }); V.us = V.uh = null; }, 'Ulangan dihapus');
const saveSoal = (uid, id) => act(async () => {
  await post('soal/save', { id: id || undefined, ulangan_id: uid, pertanyaan: val('#sq'), opsi_a: val('#so-a'), opsi_b: val('#so-b'), opsi_c: val('#so-c'), opsi_d: val('#so-d'), jawaban_benar: val('#sk') });
  V.es = null;
}, 'Soal disimpan');
const delSoal = id => ask('Hapus soal ini?') && act(() => post('soal/delete', { id }), 'Soal dihapus');
const resetHasil = (ul, uid) => ask('Hapus hasil mahasiswa ini agar bisa mengerjakan ulang?') && act(() => post('ulangan/reset', { ulangan_id: ul, user_id: uid }), 'Hasil direset');
