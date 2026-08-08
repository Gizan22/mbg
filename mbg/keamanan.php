<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/auth.php';
require __DIR__ . '/config/validate.php';
requireLogin();
requireRole(['admin','petugas','guru','siswa']);
$pageTitle = 'Keamanan Sistem';
$u = currentUser();

$pwMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lama  = $_POST['password_lama'] ?? '';
    $baru  = $_POST['password_baru'] ?? '';
    $ulang = $_POST['password_ulang'] ?? '';

    $errs = [];
    if ($lama === '') $errs[] = 'Password lama wajib diisi.';
    $pe = v_password($baru, 6);
    if ($pe) $errs[] = $pe;
    if ($baru !== $ulang) $errs[] = 'Konfirmasi password tidak cocok.';
    if ($baru !== '' && $baru === $lama) $errs[] = 'Password baru harus berbeda dari password lama.';

    if ($errs) {
        $pwMsg = 'error:' . implode(' ', $errs);
    } else {
        $row = db_find('users', $u['id']);
        if (!$row || !password_verify($lama, $row['password'])) {
            $pwMsg = 'error:Password lama tidak sesuai.';
        } else {
            db_update('users', $u['id'], ['password' => password_hash($baru, PASSWORD_DEFAULT)]);
            $pwMsg = 'success:Password berhasil diperbarui.';
        }
    }
}
[$pwType, $pwText] = $pwMsg ? explode(':', $pwMsg, 2) : ['',''];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
<title>Keamanan Sistem — MBG</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="content">

      <div class="grid-2">
        <div class="card">
          <div class="card-head"><h3>Keamanan &amp; Akses Sistem</h3></div>
          <div class="card-pad">
            <div class="sec-grid">
              <div class="sec-item"><div class="ic tone-blue">&#128274;</div><div><strong>Password Terenkripsi</strong><span class="muted" style="font-size:11.5px">bcrypt hashing</span></div></div>
              <div class="sec-item"><div class="ic tone-green">&#128101;</div><div><strong>Hak Akses Berdasarkan Role</strong><span class="muted" style="font-size:11.5px">Admin, Petugas, Guru, Siswa</span></div></div>
              <div class="sec-item"><div class="ic tone-amber">&#9888;</div><div><strong>Konfirmasi Sebelum Hapus Data</strong><span class="muted" style="font-size:11.5px">Dialog konfirmasi aktif</span></div></div>
              <div class="sec-item"><div class="ic tone-blue">&#9201;</div><div><strong>Session Login</strong><span class="muted" style="font-size:11.5px">Session PHP aman</span></div></div>
              <div class="sec-item"><div class="ic tone-blue">&#128203;</div><div><strong>Log Aktivitas Admin</strong><span class="muted" style="font-size:11.5px">Tercatat di Riwayat Aktivitas</span></div></div>
              <div class="sec-item"><div class="ic tone-red">&#9202;</div><div><strong>Logout Otomatis</strong><span class="muted" style="font-size:11.5px">Setelah sesi tidak aktif</span></div></div>

              <div class="sec-shield">
                <div class="ic">&#128737;</div>
                <strong style="font-size:15px">Sistem Aman &amp; Terlindungi</strong>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-head"><h3>Ubah Password</h3></div>
          <div class="card-pad">
            <?php if ($pwText): ?><div class="alert <?= $pwType==='success'?'alert-success':'alert-error' ?>"><?= htmlspecialchars($pwText) ?></div><?php endif; ?>
            <form method="post">
              <div class="field"><label>Password Lama</label><input type="password" name="password_lama" required autocomplete="current-password" required></div>
              <div class="field"><label>Password Baru</label><input type="password" name="password_baru" required minlength="6" maxlength="128" autocomplete="new-password" required></div>
              <div class="field"><label>Ulangi Password Baru</label><input type="password" name="password_ulang" required minlength="6" maxlength="128" autocomplete="new-password" required></div>
              <button class="btn btn-primary btn-block" type="submit">Simpan Password</button>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
