<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/auth.php';
requireLogin();
requireRole(['admin','petugas']);
$pageTitle = 'Konfirmasi Pengembalian MBG';
$u = currentUser();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    $siswaId = (int)$_POST['siswa_id'];
    $status  = $_POST['status_pengembalian'];
    $kondisi = $_POST['kondisi_wadah'];

    $rows = db_filter('pengembalian', fn($r) => (int)$r['siswa_id'] === $siswaId);
    usort($rows, fn($a,$b) => $b['id'] <=> $a['id']);
    $row = $rows[0] ?? null;

    if ($row) {
        db_update('pengembalian', $row['id'], [
            'status_pengembalian'=>$status, 'kondisi_wadah'=>$kondisi, 'petugas'=>$u['nama']
        ]);
    } else {
        db_insert('pengembalian', [
            'siswa_id'=>$siswaId, 'waktu_ambil'=>date('d F Y - H:i'),
            'status_pengembalian'=>$status, 'kondisi_wadah'=>$kondisi, 'petugas'=>$u['nama']
        ]);
    }
    db_update('siswa', $siswaId, ['status_kembali' => $status==='Sudah Dikembalikan' ? 1 : 0]);

    $s = db_find('siswa', $siswaId);
    db_insert('aktivitas', [
        'waktu'=>date('H:i'), 'pengguna'=>$s['nama'] ?? '',
        'aktivitas'=>'Konfirmasi Pengembalian',
        'keterangan'=>'Status: '.$status.' — Kondisi: '.$kondisi
    ]);

    header('Location: pengembalian.php?siswa_id=' . $siswaId . '&done=1');
    exit;
}

$siswaId = (int)($_GET['siswa_id'] ?? 1);
$siswa = db_find('siswa', $siswaId);
if (!$siswa) {
    $all = db_all('siswa');
    $siswa = $all[0] ?? null;
    $siswaId = $siswa['id'] ?? 0;
}

$pengRows = db_filter('pengembalian', fn($r) => (int)$r['siswa_id'] === (int)$siswaId);
usort($pengRows, fn($a,$b) => $b['id'] <=> $a['id']);
$peng = $pengRows[0] ?? null;

// Alias $p dipakai di template (default jika belum ada data)
$p = $peng ?? [
    'waktu_ambil' => date('d F Y - H:i'),
    'status_pengembalian' => 'Belum Dikembalikan',
    'kondisi_wadah' => 'Baik',
    'petugas' => '',
];

$allSiswa = db_all('siswa');
usort($allSiswa, fn($a,$b) => strcmp($a['nama'], $b['nama']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
<title>Konfirmasi Pengembalian — MBG</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="content">

      <?php if (isset($_GET['done'])): ?><div class="alert alert-success">Data pengembalian berhasil disimpan.</div><?php endif; ?>

      <form method="get" class="mb16" style="max-width:320px">
        <div class="field" style="margin-bottom:0">
          <label>Pilih Siswa</label>
          <select name="siswa_id" onchange="this.form.submit()">
            <?php foreach ($allSiswa as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $siswaId==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['nama']) ?> — <?= htmlspecialchars($s['nis']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>

      <div class="grid-3">
        <div class="card">
          <div class="card-head"><h3>Data Pengembalian</h3></div>
          <div class="card-pad">
            <div class="profile-mini">
              <div class="avatar-lg"><?= strtoupper(substr($siswa['nama'],0,1)) ?></div>
              <div><strong><?= htmlspecialchars($siswa['nama']) ?></strong></div>
            </div>
            <div class="detail-row"><span>NIS</span><span><?= htmlspecialchars($siswa['nis']) ?></span></div>
            <div class="detail-row"><span>Kelas</span><span><?= htmlspecialchars($siswa['kelas']) ?></span></div>
            <div class="detail-row" style="border-bottom:none"><span>Waktu Ambil</span><span><?= htmlspecialchars($p['waktu_ambil']) ?></span></div>
          </div>
        </div>

        <div class="card">
          <div class="card-head"><h3>Konfirmasi Pengembalian</h3></div>
          <div class="card-pad">
            <form method="post" id="pengembalianForm">
              <input type="hidden" name="action" value="save">
              <input type="hidden" name="siswa_id" value="<?= $siswa['id'] ?>">

              <label style="display:block;font-size:12.5px;font-weight:700;color:var(--ink-600);margin-bottom:8px">Status Pengembalian</label>
              <div class="radio-row" style="flex-direction:column;gap:10px;margin-bottom:18px">
                <label class="radio-opt"><input type="radio" name="status_pengembalian" value="Sudah Dikembalikan" <?= $p['status_pengembalian']==='Sudah Dikembalikan'?'checked':'' ?>> Sudah Dikembalikan</label>
                <label class="radio-opt"><input type="radio" name="status_pengembalian" value="Belum Dikembalikan" <?= $p['status_pengembalian']==='Belum Dikembalikan'?'checked':'' ?>> Belum Dikembalikan</label>
                <label class="radio-opt"><input type="radio" name="status_pengembalian" value="Terlambat" <?= $p['status_pengembalian']==='Terlambat'?'checked':'' ?>> Terlambat</label>
              </div>

              <label style="display:block;font-size:12.5px;font-weight:700;color:var(--ink-600);margin-bottom:8px">Kondisi Wadah</label>
              <div class="radio-row" style="margin-bottom:18px">
                <label class="radio-opt"><input type="radio" name="kondisi_wadah" value="Baik" <?= $p['kondisi_wadah']==='Baik'?'checked':'' ?>> Baik</label>
                <label class="radio-opt"><input type="radio" name="kondisi_wadah" value="Rusak" <?= $p['kondisi_wadah']==='Rusak'?'checked':'' ?>> Rusak</label>
                <label class="radio-opt"><input type="radio" name="kondisi_wadah" value="Hilang" <?= $p['kondisi_wadah']==='Hilang'?'checked':'' ?>> Hilang</label>
              </div>

              <div class="detail-row" style="border-bottom:none;padding-bottom:14px"><span>Petugas</span><span><?= htmlspecialchars($u['nama']) ?></span></div>

              <button type="submit" class="btn btn-primary btn-block">Simpan</button>
            </form>
          </div>
        </div>

        <div class="card">
          <div class="card-head"><h3>Riwayat Pengembalian</h3></div>
          <div class="card-pad">
            <div class="timeline">
              <div class="timeline-item done"><strong>MBG Diambil</strong><span><?= htmlspecialchars($p['waktu_ambil']) ?></span></div>
              <div class="timeline-item <?= $p['status_pengembalian']==='Sudah Dikembalikan'?'done':'' ?>"><strong>Wadah Dikembalikan</strong><span>Kondisi: <?= htmlspecialchars($p['kondisi_wadah']) ?></span></div>
              <div class="timeline-item <?= $p['status_pengembalian']==='Sudah Dikembalikan'?'done':'' ?>"><strong>Dikonfirmasi Petugas</strong><span><?= htmlspecialchars($p['petugas'] ?? '-') ?></span></div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
