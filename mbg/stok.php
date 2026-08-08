<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/auth.php';
require __DIR__ . '/config/validate.php';
requireLogin();
requireRole(['admin','petugas']);
$pageTitle = 'Kelola Stok MBG';
$u = currentUser();

$stokErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $disediakan = $_POST['disediakan'] ?? '';
    $dibagikan  = $_POST['dibagikan'] ?? '';
    $wadah      = $_POST['jumlah_wadah'] ?? '';
    $kembali    = $_POST['wadah_kembali'] ?? '';

    $stokErrors = v_errors([
        v_int_range($disediakan, 0, 100000, 'Jumlah disediakan'),
        v_int_range($dibagikan, 0, 100000, 'Jumlah dibagikan'),
        v_int_range($wadah, 0, 100000, 'Jumlah wadah'),
        v_int_range($kembali, 0, 100000, 'Wadah kembali'),
    ]);
    if (!$stokErrors && (int)$dibagikan > (int)$disediakan) {
        $stokErrors[] = 'Jumlah dibagikan tidak boleh melebihi yang disediakan.';
    }
    if (!$stokErrors && (int)$kembali > (int)$wadah) {
        $stokErrors[] = 'Wadah kembali tidak boleh melebihi jumlah wadah.';
    }

    if (!$stokErrors) {
        $disediakan = (int)$disediakan;
        $dibagikan  = (int)$dibagikan;
        $wadah      = (int)$wadah;
        $kembali    = (int)$kembali;
        $belum      = max(0, $wadah - $kembali);

        db_insert('stok', [
            'tanggal'=>date('Y-m-d'),
            'disediakan'=>$disediakan,
            'dibagikan'=>$dibagikan,
            'jumlah_wadah'=>$wadah,
            'wadah_kembali'=>$kembali,
            'wadah_belum_kembali'=>$belum,
        ]);
        db_insert('aktivitas', [
            'waktu'=>date('H:i'), 'pengguna'=>$u['nama'],
            'aktivitas'=>'Update Stok',
            'keterangan'=>"Disediakan $disediakan, dibagikan $dibagikan"
        ]);
        header('Location: stok.php?done=1');
        exit;
    }
}

$stokList = db_all('stok');
usort($stokList, fn($a,$b) => $b['id'] <=> $a['id']);
$stok = $stokList[0] ?? ['disediakan'=>0,'dibagikan'=>0,'jumlah_wadah'=>0,'wadah_kembali'=>0,'wadah_belum_kembali'=>0];
$sisa = max(0, (int)$stok['disediakan'] - (int)$stok['dibagikan']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
<title>Stok MBG</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="content">

      <?php if ($stokErrors): ?>
        <div class="alert alert-danger"><strong>Validasi gagal:</strong>
          <ul style="margin:6px 0 0 18px"><?php foreach ($stokErrors as $fe): ?><li><?= htmlspecialchars($fe) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; ?>
      <?php if (isset($_GET['done'])): ?><div class="alert alert-success">Data stok berhasil diperbarui.</div><?php endif; ?>

      <div class="grid-2" style="grid-template-columns:1.6fr 1fr">
        <div class="card">
          <div class="card-head"><h3>Stok Hari Ini</h3></div>
          <div class="card-pad">
            <div class="stok-grid mb16">
              <div class="stok-card tone-blue"><div class="lbl">MBG Disediakan</div><div class="num"><?= $stok['disediakan'] ?></div></div>
              <div class="stok-card tone-green"><div class="lbl">MBG Dibagikan</div><div class="num"><?= $stok['dibagikan'] ?></div></div>
              <div class="stok-card tone-amber"><div class="lbl">Sisa MBG</div><div class="num"><?= $sisa ?></div></div>
              <div class="stok-card tone-blue"><div class="lbl">Jumlah Wadah</div><div class="num"><?= $stok['jumlah_wadah'] ?></div></div>
              <div class="stok-card tone-green"><div class="lbl">Wadah Kembali</div><div class="num"><?= $stok['wadah_kembali'] ?></div></div>
              <div class="stok-card tone-red"><div class="lbl">Wadah Belum Kembali</div><div class="num"><?= $stok['wadah_belum_kembali'] ?></div></div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-head"><h3>Perbarui Stok</h3></div>
          <div class="card-pad">
            <form method="post">
              <div class="field"><label>MBG Disediakan</label><input type="text" name="disediakan" value="<?= $stok['disediakan'] ?>" required></div>
              <div class="field"><label>MBG Dibagikan</label><input type="text" name="dibagikan" value="<?= $stok['dibagikan'] ?>" required></div>
              <div class="field"><label>Jumlah Wadah</label><input type="text" name="jumlah_wadah" value="<?= $stok['jumlah_wadah'] ?>" required></div>
              <div class="field"><label>Wadah Kembali</label><input type="text" name="wadah_kembali" value="<?= $stok['wadah_kembali'] ?>" required></div>
              <button class="btn btn-primary btn-block" type="submit">Simpan Perubahan</button>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
