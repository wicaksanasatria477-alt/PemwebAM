<?php
defined('SPADA') or exit;

/** matkul_id 0/kosong = pengumuman umum (hanya admin yang boleh menulis). */
route('GET', 'pengumuman/list', function () {
    $u = need_login();
    $mid = g_int('matkul_id', false) ?: null;
    if ($mid) {
        $m = matkul_or_404($mid);
        guard_access($u, $m);
        $manage = can_manage($u, $m);
        $list = rows('SELECT p.id,p.judul,p.isi,p.file,p.created_at,p.penulis_id,u.nama AS penulis FROM pengumuman p JOIN users u ON u.id=p.penulis_id
                      WHERE p.matkul_id=? ORDER BY p.created_at DESC, p.id DESC', [$mid]);
    } else {
        $manage = $u['role'] === 'admin';
        $list = rows('SELECT p.id,p.judul,p.isi,p.file,p.created_at,p.penulis_id,u.nama AS penulis FROM pengumuman p JOIN users u ON u.id=p.penulis_id
                      WHERE p.matkul_id IS NULL ORDER BY p.created_at DESC, p.id DESC');
    }
    out(['bisa_kelola' => $manage, 'daftar' => array_map(fn($r) => ['id' => (int)$r['id'], 'judul' => $r['judul'], 'isi' => $r['isi'],
        'file_nama' => file_label($r['file']), 'created_at' => $r['created_at'], 'penulis' => $r['penulis']], $list)]);
});

function pengumuman_guard(array $u, ?int $mid): void {
    if ($mid) guard_manage($u, matkul_or_404($mid));
    elseif ($u['role'] !== 'admin') fail('Hanya admin yang dapat membuat pengumuman umum.', 403);
}

route('POST', 'pengumuman/save', function () {
    $u = need_role('dosen', 'admin');
    $id = p_int('id');
    $judul = p_str('judul', 150, true);
    $isi = p_str('isi', 10000, true);
    $hapusFile = p_bool('hapus_file');

    if ($id) {
        $old = row('SELECT * FROM pengumuman WHERE id=?', [$id]) ?? fail('Pengumuman tidak ditemukan.', 404);
        pengumuman_guard($u, $old['matkul_id'] === null ? null : (int)$old['matkul_id']);
        $new = save_upload('file', 'pengumuman');
        $file = $new ?? ($hapusFile ? null : $old['file']);
        run('UPDATE pengumuman SET judul=?, isi=?, file=? WHERE id=?', [$judul, $isi, $file, $id]);
        if ($old['file'] && $old['file'] !== $file) delete_file('pengumuman', $old['file']);
    } else {
        $mid = p_int('matkul_id') ?: null;
        pengumuman_guard($u, $mid);
        $file = save_upload('file', 'pengumuman');
        run('INSERT INTO pengumuman (matkul_id,judul,isi,file,penulis_id) VALUES (?,?,?,?,?)', [$mid, $judul, $isi, $file, $u['id']]);
        $id = last_id();
    }
    out(['id' => $id]);
});

route('POST', 'pengumuman/delete', function () {
    $u = need_role('dosen', 'admin');
    $a = row('SELECT * FROM pengumuman WHERE id=?', [p_int('id', true)]) ?? fail('Pengumuman tidak ditemukan.', 404);
    pengumuman_guard($u, $a['matkul_id'] === null ? null : (int)$a['matkul_id']);
    run('DELETE FROM pengumuman WHERE id=?', [$a['id']]);
    delete_file('pengumuman', $a['file']);
    out(true);
});
