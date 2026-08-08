<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/auth.php';
require __DIR__ . '/config/validate.php';
requireLogin();
requireRole(['admin','petugas','guru','siswa']);
$pageTitle = 'Notifikasi';
$u = currentUser();

// Mark as read / add
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'read_one') {
        $id = (int)($_POST['id'] ?? 0);
        db_update('notifikasi', $id, ['dibaca' => 1]);
    } elseif ($action === 'read_all') {
        foreach (db_all('notifikasi') as $n) {
            if (!(int)$n['dibaca']) db_update('notifikasi', $n['id'], ['dibaca' => 1]);
        }
    } elseif ($action === 'add' && hasRole(['admin','petugas'])) {
        $pesan = trim($_POST['pesan'] ?? '');
        $tipe  = $_POST['tipe'] ?? 'sukses';
        $errors = v_errors([
            v_required($pesan, 'Pesan'),
            (mb_strlen($pesan) > 255) ? 'Pesan maksimal 255 karakter.' : null,
            v_tipe_notif($tipe),
        ]);
        if ($errors) {
            v_fail_redirect('notifikasi.php?err=1', $errors);
        }
        db_insert('notifikasi', [
            'judul' => 'Pengumuman',
            'pesan' => $pesan,
            'waktu' => date('H:i'),
            'tipe' => $tipe,
            'dibaca' => 0,
        ]);
    }
    header('Location: notifikasi.php');
    exit;
}

$notifs = db_all('notifikasi');
usort($notifs, fn($a,$b) => $b['id'] <=> $a['id']);
$unread = count(array_filter($notifs, fn($n) => empty($n['dibaca'])));
$tipeMap = [
    'sukses'  => ['tone-green', '✓', 'Sukses'],
    'success' => ['tone-green', '✓', 'Sukses'],
    'warning' => ['tone-amber', '⚠', 'Peringatan'],
    'info'    => ['tone-blue',  'ℹ', 'Info'],
    'error'   => ['tone-red',   '✕', 'Error'],
    'bahaya'  => ['tone-red',   '✕', 'Bahaya'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
<title>Notifikasi — MBG</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="content">
      <?php if (isset($_GET['err'])): $ferr = v_flash_errors(); if ($ferr): ?>
        <div class="alert alert-danger"><strong>Validasi gagal:</strong>
          <ul style="margin:6px 0 0 18px"><?php foreach ($ferr as $fe): ?><li><?= htmlspecialchars($fe) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; endif; ?>

      <div class="page-header">
        <h2>🔔 Notifikasi <?php if ($unread): ?><span class="badge badge-red"><?= $unread ?> baru</span><?php endif; ?></h2>
        <div class="actions">
          <?php if ($unread > 0): ?>
          <form method="post" style="display:inline">
            <input type="hidden" name="action" value="read_all">
            <button type="submit" class="btn btn-outline btn-sm">✓ Tandai Semua Dibaca</button>
          </form>
          <?php endif; ?>
          <?php if (hasRole(['admin','petugas'])): ?>
          <button class="btn btn-primary btn-sm" onclick="openModal('addNotif')">＋ Buat Notifikasi</button>
          <?php endif; ?>
        </div>
      </div>

      <div class="card" style="max-width:700px">
        <div class="card-pad">
          <?php if (!$notifs): ?>
            <div class="empty-state"><div class="ic">🔔</div><h3>Belum ada notifikasi</h3></div>
          <?php endif; ?>
          <?php foreach ($notifs as $n):
            $t = $tipeMap[$n['tipe'] ?? 'info'] ?? $tipeMap['info'];
            $isNew = empty($n['dibaca']);
          ?>
            <div class="notif-item" style="<?= $isNew ? 'background:var(--blue-50);border-radius:10px;padding:12px;margin-bottom:4px' : '' ?>">
              <div class="notif-ic <?= $t[0] ?>"><?= $t[1] ?></div>
              <div class="notif-txt" style="flex:1">
                <strong><?= htmlspecialchars($n['pesan']) ?></strong>
                <span><?= htmlspecialchars($t[2]) ?> · <?= htmlspecialchars($n['waktu']) ?>
                  <?php if ($isNew): ?> · <span class="badge badge-blue">Baru</span><?php endif; ?>
                </span>
              </div>
              <?php if ($isNew): ?>
              <form method="post">
                <input type="hidden" name="action" value="read_one">
                <input type="hidden" name="id" value="<?= $n['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline">Dibaca</button>
              </form>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<?php if (hasRole(['admin','petugas'])): ?>
<div class="modal-bg" id="addNotif">
  <div class="modal" style="max-width:420px">
    <div class="modal-head"><h3>Buat Notifikasi</h3><button class="x" onclick="closeModal('addNotif')">&times;</button></div>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="field"><label>Pesan</label><input type="text" name="pesan" required minlength="3" maxlength="255" placeholder="Isi pesan notifikasi..."></div>
        <div class="field">
          <label>Tipe</label>
          <select name="tipe">
            <option value="sukses">Info / Sukses</option>
            <option value="warning">Peringatan</option>
            <option value="bahaya">Penting / Bahaya</option>
          </select>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('addNotif')">Batal</button>
        <button type="submit" class="btn btn-primary">Kirim</button>
      </div>
    </form>
  </div>
</div>
<script>
function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-bg').forEach(bg=>{
  bg.addEventListener('click', e=>{ if(e.target===bg) bg.classList.remove('open'); });
});
</script>
<?php endif; ?>
</body>
</html>
