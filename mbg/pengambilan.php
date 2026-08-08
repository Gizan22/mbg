<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/auth.php';
requireLogin();
requireRole(['admin','petugas','siswa']);
$pageTitle = 'Konfirmasi Pengambilan MBG';
$u = currentUser();

$msg = '';
$isSiswa = ($u['role'] === 'siswa');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm') {
    if ($isSiswa) {
        header('Location: pengambilan.php?denied=1');
        exit;
    }
    $id = (int)$_POST['id'];
    db_update('siswa', $id, ['status_ambil' => 1]);
    $s = db_find('siswa', $id);
    $nama = $s['nama'] ?? '';
    $waktu = date('d F Y - H:i');
    db_insert('pengambilan', [
        'siswa_id'=>$id, 'waktu'=>$waktu, 'petugas'=>$u['nama'], 'status'=>'Sudah Mengambil'
    ]);
    db_insert('aktivitas', [
        'waktu'=>date('H:i'), 'pengguna'=>$nama, 'aktivitas'=>'Mengambil MBG', 'keterangan'=>'Petugas: '.$u['nama']
    ]);
    header('Location: pengambilan.php?id=' . $id . '&done=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_confirm') {
    if ($isSiswa) {
        header('Location: pengambilan.php?denied=1');
        exit;
    }
    $ids = $_POST['ids'] ?? [];
    $count = 0;
    $waktu = date('d F Y - H:i');
    foreach ($ids as $sid) {
        $sid = (int)$sid;
        $row = db_find('siswa', $sid);
        if ($row && !(int)$row['status_ambil']) {
            db_update('siswa', $sid, ['status_ambil' => 1]);
            db_insert('pengambilan', [
                'siswa_id'=>$sid, 'waktu'=>$waktu, 'petugas'=>$u['nama'], 'status'=>'Sudah Mengambil'
            ]);
            db_insert('aktivitas', [
                'waktu'=>date('H:i'), 'pengguna'=>$row['nama'], 'aktivitas'=>'Mengambil MBG',
                'keterangan'=>'Petugas: '.$u['nama'].' (bulk)'
            ]);
            $count++;
        }
    }
    header('Location: pengambilan.php?bulk=1&done=' . $count);
    exit;
}

if ($isSiswa) {
    $siswa = null;
    foreach (db_all('siswa') as $s) {
        if ($s['nama'] === $u['nama']) { $siswa = $s; break; }
    }
    if (!$siswa) {
        $all = db_all('siswa');
        $siswa = $all[0] ?? null;
    }
} else {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $siswa = $id ? db_find('siswa', $id) : null;
    if (!$siswa) {
        $all = db_all('siswa');
        $siswa = $all[0] ?? null;
    }
}

$lastAmbil = null;
if ($siswa) {
    $list = db_filter('pengambilan', fn($r) => (int)$r['siswa_id'] === (int)$siswa['id']);
    usort($list, fn($a,$b) => $b['id'] <=> $a['id']);
    $lastAmbil = $list[0] ?? null;
}

$allSiswa = db_all('siswa');
usort($allSiswa, fn($a,$b) => strcmp($a['nama'], $b['nama']));

$belumList = array_values(array_filter(db_all('siswa'), fn($s) => !(int)$s['status_ambil']));
usort($belumList, fn($a,$b) => ($a['kelas'].$a['nama']) <=> ($b['kelas'].$b['nama']));

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
<title>Konfirmasi Pengambilan — MBG</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="content">

      <?php if (isset($_GET['done']) && !isset($_GET['bulk'])): ?><div class="alert alert-success">✓ Pengambilan MBG berhasil dikonfirmasi.</div><?php endif; ?>
      <?php if (isset($_GET['denied'])): ?><div class="alert alert-danger">⛔ Akses ditolak.</div><?php endif; ?>

      <?php if (!$isSiswa): ?>
      <form method="get" class="mb-2" style="max-width:320px">
        <div class="field" style="margin-bottom:0">
          <label>Pilih Siswa (simulasi scan QR)</label>
          <select name="id" onchange="this.form.submit()">
            <?php foreach ($allSiswa as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $siswa && $siswa['id']==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['nama']) ?> — <?= htmlspecialchars($s['nis']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
      <?php else: ?>
      <div class="alert alert-info mb-2">📱 Status pengambilan MBG Anda. Konfirmasi dilakukan oleh petugas.</div>
      <?php endif; ?>

      <div class="grid-2">
        <div class="card">
          <div class="card-head"><h3>Scan QR Siswa</h3></div>
          <div class="qr-box">
            <div class="qr-img">
              <svg width="120" height="120" viewBox="0 0 120 120"><rect width="120" height="120" fill="#fbfcff"/>
                <?php
                  // pola kotak QR dekoratif deterministik berdasar id siswa
                  srand($siswa['id'] ?? 1);
                  for ($r=0;$r<10;$r++) for ($c=0;$c<10;$c++) { if (rand(0,1)) echo "<rect x='".($c*12)."' y='".($r*12)."' width='10' height='10' fill='#16213f'/>"; }
                ?>
              </svg>
            </div>
            <span class="muted" style="font-size:12px">Arahkan kamera ke QR Code</span>
          </div>
        </div>

        <div class="card">
          <div class="card-head"><h3>Data Siswa</h3></div>
          <div class="card-pad">
            <?php if ($siswa): ?>
              <div class="profile-mini">
                <div class="avatar-lg"><?= strtoupper(substr($siswa['nama'],0,1)) ?></div>
                <div><strong><?= htmlspecialchars($siswa['nama']) ?></strong><br><span class="muted">NIS <?= htmlspecialchars($siswa['nis']) ?></span></div>
              </div>
              <div class="detail-row"><span>Kelas</span><span><?= htmlspecialchars($siswa['kelas']) ?></span></div>
              <div class="detail-row"><span>Waktu</span><span><?= $lastAmbil ? htmlspecialchars($lastAmbil['waktu']) : date('d F Y - H:i') ?></span></div>
              <div class="detail-row"><span>Petugas</span><span><?= htmlspecialchars($u['nama']) ?></span></div>
              <div class="detail-row" style="border-bottom:none">
                <span>Status</span>
                <span>
                  <?php if ($siswa['status_ambil']): ?>
                    <span class="badge badge-green">&#10003; Sudah Mengambil</span>
                  <?php else: ?>
                    <span class="badge badge-amber">Belum Mengambil</span>
                  <?php endif; ?>
                </span>
              </div>

              <?php if (!$isSiswa): ?>
              <form method="post" class="mt-2">
                <input type="hidden" name="action" value="confirm">
                <input type="hidden" name="id" value="<?= $siswa['id'] ?>">
                <button class="btn btn-primary btn-block" <?= $siswa['status_ambil'] ? 'disabled' : '' ?>>
                  <?= $siswa['status_ambil'] ? 'Sudah Dikonfirmasi' : 'Konfirmasi Pengambilan' ?>
                </button>
              </form>
              <p class="text-muted text-sm" style="text-align:center;margin-top:10px">Pastikan data siswa sudah sesuai sebelum konfirmasi.</p>
              <?php endif; ?>
            <?php else: ?>
              <p class="muted">Tidak ada data siswa.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>


      <?php if (!$isSiswa && isset($_GET['bulk'])): ?>
      <div class="card mt-2">
        <div class="card-head">
          <h3>⚡ Bulk Konfirmasi Pengambilan</h3>
        </div>
        <div class="card-pad">
          <?php if (isset($_GET['done'])): ?>
            <div class="alert alert-success">✓ <?= (int)$_GET['done'] ?> siswa berhasil dikonfirmasi.</div>
          <?php endif; ?>
          <form method="post">
            <input type="hidden" name="action" value="bulk_confirm">
            <div class="table-wrap">
              <table class="tbl">
                <thead>
                  <tr>
                    <th><input type="checkbox" id="checkAll" onclick="document.querySelectorAll('.chk').forEach(c=>c.checked=this.checked)"></th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (!$belumList): ?>
                    <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--ink-400)">Semua siswa sudah mengambil MBG 🎉</td></tr>
                  <?php else: foreach ($belumList as $s): ?>
                  <tr>
                    <td><input type="checkbox" class="chk" name="ids[]" value="<?= $s['id'] ?>"></td>
                    <td><?= htmlspecialchars($s['nis']) ?></td>
                    <td><?= htmlspecialchars($s['nama']) ?></td>
                    <td><?= htmlspecialchars($s['kelas']) ?></td>
                    <td><span class="badge badge-amber">Belum</span></td>
                  </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
            <?php if ($belumList): ?>
            <div class="mt-2">
              <button type="submit" class="btn btn-primary" onclick="return confirm('Konfirmasi pengambilan untuk siswa terpilih?')">✓ Konfirmasi yang Dipilih</button>
            </div>
            <?php endif; ?>
          </form>
        </div>
      </div>
      <?php elseif (!$isSiswa): ?>
      <div class="mt-2">
        <a href="pengambilan.php?bulk=1" class="btn btn-outline">⚡ Mode Bulk Konfirmasi</a>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>
</body>
</html>
