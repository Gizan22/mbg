<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/auth.php';
require __DIR__ . '/config/validate.php';
requireLogin();
requireRole(['admin','petugas','guru','siswa']);
$pageTitle = 'Jadwal Pembagian MBG';

// ---------- CRUD (hanya admin/petugas) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hasRole(['admin','petugas'])) {
        header('Location: jadwal.php?denied=1');
        exit;
    }
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $tanggal    = trim($_POST['tanggal'] ?? '');
        $jam_mulai  = trim($_POST['jam_mulai'] ?? '');
        $jam_selesai= trim($_POST['jam_selesai'] ?? '');
        $kelas      = trim($_POST['kelas'] ?? '');
        $lokasi     = trim($_POST['lokasi'] ?? '');
        $petugas    = trim($_POST['petugas'] ?? '');

        $errors = v_errors([
            v_date($tanggal),
            v_time($jam_mulai, 'Jam mulai'),
            v_time($jam_selesai, 'Jam selesai'),
            v_kelas($kelas),
            v_required($lokasi, 'Lokasi'),
            v_required($petugas, 'Petugas'),
        ]);
        if (!$errors && $jam_mulai >= $jam_selesai) {
            $errors[] = 'Jam selesai harus setelah jam mulai.';
        }
        if ($errors) {
            v_fail_redirect('jadwal.php?bulan=' . urlencode($_POST['bulan'] ?? date('Y-m')) . '&tanggal=' . urlencode($tanggal ?: date('Y-m-d')) . '&err=1', $errors);
        }
        db_insert('jadwal', [
            'tanggal'=>$tanggal, 'jam_mulai'=>$jam_mulai, 'jam_selesai'=>$jam_selesai,
            'kelas'=>$kelas, 'lokasi'=>$lokasi, 'petugas'=>$petugas
        ]);
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) db_delete('jadwal', $id);
    }
    header('Location: jadwal.php?bulan=' . ($_POST['bulan'] ?? date('Y-m')) . '&tanggal=' . ($_POST['tanggal'] ?? date('Y-m-d')));
    exit;
}

$bulanParam = $_GET['bulan'] ?? date('Y-m');
[$tahun, $bulan] = explode('-', $bulanParam);
$tahun = (int)$tahun; $bulan = (int)$bulan;
$tglTerpilih = $_GET['tanggal'] ?? date('Y-m-d');

$jumlahHari = (int)date('t', mktime(0,0,0,$bulan,1,$tahun));
$hariPertama = (int)date('N', mktime(0,0,0,$bulan,1,$tahun));

$eventDates = array_column(db_all('jadwal'), 'tanggal');

$namaBulan = ['', 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

$prevMonth = mktime(0,0,0,$bulan-1,1,$tahun);
$nextMonth = mktime(0,0,0,$bulan+1,1,$tahun);

$jadwalHari = db_filter('jadwal', fn($r) => $r['tanggal'] === $tglTerpilih);
usort($jadwalHari, fn($a,$b) => strcmp($a['jam_mulai'], $b['jam_mulai']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
<title>Jadwal Pembagian — MBG</title>
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

      <div class="grid-2">
        <div class="card">
          <div class="card-pad">
            <div class="cal-head">
              <h3><?= $namaBulan[$bulan] ?> <?= $tahun ?></h3>
              <div class="cal-nav">
                <a class="btn-icon" href="?bulan=<?= date('Y-m', $prevMonth) ?>">&#8249;</a>
                <a class="btn-icon" href="?bulan=<?= date('Y-m', $nextMonth) ?>">&#8250;</a>
              </div>
            </div>
            <div class="cal-grid">
              <div class="dow">Sen</div><div class="dow">Sel</div><div class="dow">Rab</div><div class="dow">Kam</div><div class="dow">Jum</div><div class="dow">Sab</div><div class="dow">Min</div>
              <?php for ($i=1;$i<$hariPertama;$i++): ?><div class="day muted"></div><?php endfor; ?>
              <?php for ($d=1;$d<=$jumlahHari;$d++):
                  $dateStr = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                  $isActive = $dateStr === $tglTerpilih;
                  $hasEvent = in_array($dateStr, $eventDates);
                  $cls = 'day' . ($isActive ? ' active' : ($hasEvent ? ' has-event' : ''));
              ?>
                <a class="<?= $cls ?>" href="?bulan=<?= $bulanParam ?>&tanggal=<?= $dateStr ?>"><?= $d ?></a>
              <?php endfor; ?>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-head"><h3>Detail Jadwal</h3><button class="btn btn-sm btn-primary" onclick="openModal('addModal')">+ Tambah</button></div>
          <div class="card-pad">
            <?php if (!$jadwalHari): ?>
              <p class="muted">Belum ada jadwal pada tanggal ini.</p>
            <?php endif; ?>
            <?php foreach ($jadwalHari as $j): ?>
              <div class="detail-row"><span>Tanggal</span><span><?= date('d F Y', strtotime($j['tanggal'])) ?></span></div>
              <div class="detail-row"><span>Jam</span><span><?= htmlspecialchars($j['jam_mulai']) ?> - <?= htmlspecialchars($j['jam_selesai']) ?></span></div>
              <div class="detail-row"><span>Kelas</span><span><?= htmlspecialchars($j['kelas']) ?></span></div>
              <div class="detail-row"><span>Lokasi</span><span><?= htmlspecialchars($j['lokasi']) ?></span></div>
              <div class="detail-row" style="border-bottom:none"><span>Petugas</span><span><?= htmlspecialchars($j['petugas']) ?></span></div>
              <div style="display:flex;gap:10px;margin-top:14px">
                <button class="btn btn-outline btn-sm" style="flex:1">Edit</button>
                <form method="post" style="flex:1" onsubmit="return confirm('Hapus jadwal ini?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $j['id'] ?>">
                  <input type="hidden" name="bulan" value="<?= $bulanParam ?>">
                  <input type="hidden" name="tanggal" value="<?= $tglTerpilih ?>">
                  <button class="btn btn-danger btn-sm btn-block" type="submit">Hapus</button>
                </form>
              </div>
              <hr style="border:none;border-top:1px dashed var(--line);margin:16px 0">
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<div class="modal-bg" id="addModal">
  <div class="modal">
    <div class="modal-head"><h3>Tambah Jadwal</h3><button class="x" onclick="closeModal('addModal')">&times;</button></div>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="bulan" value="<?= $bulanParam ?>">
      <div class="modal-body">
        <div class="field"><label>Tanggal</label><input type="date" name="tanggal" value="<?= $tglTerpilih ?>" required></div>
        <div class="field"><label>Jam Mulai</label><input type="time" name="jam_mulai" value="11:00" required></div>
        <div class="field"><label>Jam Selesai</label><input type="time" name="jam_selesai" value="12:30" required></div>
        <div class="field"><label>Kelas</label><input type="text" name="kelas" placeholder="cth. IX RPL 3" required></div>
        <div class="field"><label>Lokasi</label><input type="text" name="lokasi" value="Ruang MBG" required></div>
        <div class="field"><label>Petugas</label><input type="text" name="petugas" value="Admin" required></div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>
<script>
function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-bg').forEach(bg=>{ bg.addEventListener('click', e=>{ if(e.target===bg) bg.classList.remove('open'); }); });
</script>
</body>
</html>
