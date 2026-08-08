<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/auth.php';
requireLogin();
requireRole(['admin','petugas']);
$pageTitle = 'Riwayat Aktivitas';

$q = trim($_GET['q'] ?? '');
$log = db_all('aktivitas');
if ($q !== '') {
    $log = array_values(array_filter($log, fn($r) =>
        stripos($r['pengguna'] ?? '', $q) !== false || stripos($r['aktivitas'] ?? '', $q) !== false
    ));
}
usort($log, fn($a,$b) => $b['id'] <=> $a['id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
<title>Riwayat Aktivitas — MBG</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="content">

      <div class="toolbar">
        <h3 style="font-size:15px">Log Aktivitas Sistem</h3>
        <form method="get" class="search-box" style="max-width:280px">
          <span>&#128269;</span>
          <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari pengguna / aktivitas...">
        </form>
      </div>

      <div class="card">
        <div class="table-wrap">
          <table class="tbl">
            <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aktivitas</th><th>Keterangan</th></tr></thead>
            <tbody>
              <?php if (!$log): ?><tr><td colspan="4" class="empty-state">Belum ada aktivitas tercatat.</td></tr><?php endif; ?>
              <?php foreach ($log as $a): ?>
                <tr>
                  <td><?= htmlspecialchars($a['waktu']) ?></td>
                  <td><div class="name-cell"><div class="avatar-sm"><?= strtoupper(substr($a['pengguna'],0,1)) ?></div><?= htmlspecialchars($a['pengguna']) ?></div></td>
                  <td><?= htmlspecialchars($a['aktivitas']) ?></td>
                  <td class="muted"><?= htmlspecialchars($a['keterangan']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
