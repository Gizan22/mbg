<?php
$role = $_SESSION['user_role'] ?? 'admin';
$user = currentUser();
$initials = '';
foreach (explode(' ', $user['nama'] ?? 'U') as $w) {
    $initials .= strtoupper(mb_substr($w, 0, 1));
}
$initials = mb_substr($initials, 0, 2);

$allMenu = [
    'dashboard.php'    => ['📊', 'Dashboard',         ['admin','petugas','guru','siswa']],
    'data_siswa.php'   => ['👥', 'Data Siswa',        ['admin','petugas','guru']],
    'pengambilan.php'  => ['📱', 'Pengambilan MBG',   ['admin','petugas','siswa']],
    'pengembalian.php' => ['📦', 'Pengembalian MBG',  ['admin','petugas']],
    'stok.php'         => ['📋', 'Stok MBG',          ['admin','petugas']],
    'jadwal.php'       => ['📅', 'Jadwal Pembagian',  ['admin','petugas','guru','siswa']],
    'laporan.php'      => ['📈', 'Laporan',           ['admin','petugas','guru']],
    'users.php'        => ['👤', 'Kelola Pengguna',   ['admin']],
    'notifikasi.php'   => ['🔔', 'Notifikasi',        ['admin','petugas','guru','siswa']],
    'pencarian.php'    => ['🔍', 'Pencarian',         ['admin','petugas','guru']],
    'riwayat.php'      => ['🕐', 'Riwayat Aktivitas', ['admin','petugas']],
    'keamanan.php'     => ['⚙️', 'Pengaturan',        ['admin','petugas','guru','siswa']],
];
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <img src="assets/img/logo-bgn.jpg" alt="Badan Gizi Nasional" class="logo-img">
    <div>
      <strong>MBG</strong>
      <small>Badan Gizi Nasional</small>
    </div>
    <button type="button" class="sidebar-close" onclick="closeMenu()" title="Tutup menu">×</button>
  </div>

  <nav>
    <div class="nav-label">Menu</div>
    <?php foreach ($allMenu as $file => $item):
      if (!in_array($role, $item[2], true)) continue;
    ?>
      <a href="<?= $file ?>" class="<?= $current === $file ? 'active' : '' ?>">
        <span class="ic"><?= $item[0] ?></span>
        <span class="nav-text"><?= $item[1] ?></span>
        <?php if ($current === $file): ?><span class="nav-dot"></span><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="su-avatar"><?= htmlspecialchars($initials) ?></div>
      <div class="su-info">
        <strong><?= htmlspecialchars($user['nama'] ?? '') ?></strong>
        <span><?= roleLabel($role) ?></span>
      </div>
    </div>
    <a href="logout.php" class="logout-btn">↪ Keluar</a>
  </div>
</aside>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeMenu()"></div>
