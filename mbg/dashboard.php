<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/auth.php';
requireLogin();
$pageTitle = 'Dashboard';
$u = currentUser();
$role = $u['role'];
$denied = isset($_GET['denied']);

$siswaAll = db_all('siswa');
$totalSiswa = count($siswaAll);
$stokRows = db_all('stok');
usort($stokRows, fn($a,$b) => $b['id'] <=> $a['id']);
$stok = $stokRows[0] ?? ['disediakan'=>0,'dibagikan'=>0,'jumlah_wadah'=>0,'wadah_kembali'=>0,'wadah_belum_kembali'=>0];
$sudahAmbil = count(array_filter($siswaAll, fn($s) => (int)$s['status_ambil'] === 1));
$belumAmbil = $totalSiswa - $sudahAmbil;
$sudahKembali = count(array_filter($siswaAll, fn($s) => (int)$s['status_kembali'] === 1));
$belumKembali = $totalSiswa - $sudahKembali;
$persen = ($stok['disediakan'] ?? 0) > 0 ? round($stok['dibagikan'] / $stok['disediakan'] * 100, 1) : 0;

$notifs = db_all('notifikasi');
usort($notifs, fn($a,$b) => $b['id'] <=> $a['id']);
$notifs = array_slice($notifs, 0, 5);

$aktivitas = db_all('aktivitas');
usort($aktivitas, fn($a,$b) => $b['id'] <=> $a['id']);
$aktivitas = array_slice($aktivitas, 0, 6);

$unreadNotif = count(array_filter(db_all('notifikasi'), fn($n) => (int)$n['dibaca'] === 0));

$byKelasMap = [];
foreach ($siswaAll as $s) {
    $k = $s['kelas'];
    if (!isset($byKelasMap[$k])) $byKelasMap[$k] = ['kelas'=>$k,'total'=>0,'ambil'=>0];
    $byKelasMap[$k]['total']++;
    if ((int)$s['status_ambil'] === 1) $byKelasMap[$k]['ambil']++;
}
ksort($byKelasMap);
$byKelas = array_values($byKelasMap);

$stokHistory = db_all('stok');
usort($stokHistory, fn($a,$b) => strcmp($b['tanggal'], $a['tanggal']));
$stokHistory = array_slice($stokHistory, 0, 7);
$stokHistory = array_reverse($stokHistory);

$mySiswa = null;
if ($role === 'siswa') {
    foreach ($siswaAll as $s) {
        if ($s['nama'] === $u['nama']) { $mySiswa = $s; break; }
    }
    if (!$mySiswa && $siswaAll) $mySiswa = $siswaAll[0];
}
$jadwalList = db_all('jadwal');
usort($jadwalList, fn($a,$b) => strcmp($a['tanggal'], $b['tanggal']));
$jadwalList = array_slice($jadwalList, 0, 5);

// Chart data
$chartStokLabels  = json_encode(array_map(fn($r) => date('d/m', strtotime($r['tanggal'])), $stokHistory ?: []));
$chartStokDisedia = json_encode(array_map(fn($r) => (int)$r['disediakan'], $stokHistory ?: []));
$chartStokDibagi  = json_encode(array_map(fn($r) => (int)$r['dibagikan'], $stokHistory ?: []));
$chartKelasLabels = json_encode(array_map(fn($r) => $r['kelas'], $byKelas ?: []));
$chartKelasAmbil  = json_encode(array_map(fn($r) => (int)$r['ambil'], $byKelas ?: []));
$chartKelasTotal  = json_encode(array_map(fn($r) => (int)$r['total'], $byKelas ?: []));

// Notifikasi tipe → style
$tipeMap = [
    'sukses'  => ['tone-green', '✓'],
    'success' => ['tone-green', '✓'],
    'warning' => ['tone-amber', '⚠'],
    'info'    => ['tone-blue',  'ℹ'],
    'error'   => ['tone-red',   '✕'],
    'bahaya'  => ['tone-red',   '✕'],
];


?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
<title>Dashboard — MBG</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="content">

      <?php if ($denied): ?>
        <div class="alert alert-danger mb-2">⛔ Anda tidak memiliki akses ke halaman tersebut.</div>
      <?php endif; ?>

      <!-- ===================== SISWA ===================== -->
      <?php if ($role === 'siswa'): ?>
        <div class="alert alert-info mb-2">
          👋 Halo, <strong><?= htmlspecialchars($u['nama']) ?></strong>! Ini status MBG Anda hari ini.
        </div>
        <?php if ($mySiswa): ?>
        <div class="stat-grid" style="grid-template-columns:repeat(3,1fr)">
          <div class="stat-card">
            <div class="ico tone-blue">🎒</div>
            <div><div class="num" style="font-size:16px"><?= htmlspecialchars($mySiswa['kelas']) ?></div><div class="lbl">Kelas Anda</div></div>
          </div>
          <div class="stat-card">
            <div class="ico <?= $mySiswa['status_ambil'] ? 'tone-green' : 'tone-amber' ?>"><?= $mySiswa['status_ambil'] ? '✓' : '⏳' ?></div>
            <div><div class="num" style="font-size:16px"><?= $mySiswa['status_ambil'] ? 'Sudah Ambil' : 'Belum Ambil' ?></div><div class="lbl">Status Pengambilan</div></div>
          </div>
          <div class="stat-card">
            <div class="ico <?= $mySiswa['status_kembali'] ? 'tone-green' : 'tone-red' ?>"><?= $mySiswa['status_kembali'] ? '📦' : '🔒' ?></div>
            <div><div class="num" style="font-size:16px"><?= $mySiswa['status_kembali'] ? 'Sudah Kembali' : 'Belum Kembali' ?></div><div class="lbl">Status Wadah</div></div>
          </div>
        </div>
        <div class="grid-2">
          <div class="card">
            <div class="card-head"><h3>Status MBG Saya</h3></div>
            <div class="card-pad">
              <div class="profile-mini">
                <div class="avatar-lg"><?= strtoupper(mb_substr($mySiswa['nama'],0,1)) ?></div>
                <div>
                  <strong style="font-size:16px"><?= htmlspecialchars($mySiswa['nama']) ?></strong>
                  <div class="text-muted text-sm">NIS: <?= htmlspecialchars($mySiswa['nis']) ?> · <?= htmlspecialchars($mySiswa['kelas']) ?></div>
                </div>
              </div>
              <div class="detail-row"><span>Status Ambil</span><span><?= $mySiswa['status_ambil'] ? '<span class="badge badge-green">✓ Sudah</span>' : '<span class="badge badge-amber">Belum</span>' ?></span></div>
              <div class="detail-row"><span>Status Wadah</span><span><?= $mySiswa['status_kembali'] ? '<span class="badge badge-green">✓ Dikembalikan</span>' : '<span class="badge badge-red">Belum</span>' ?></span></div>
              <div class="mt-2"><a href="pengambilan.php" class="btn btn-primary btn-block">📱 Lihat Status Pengambilan</a></div>
            </div>
          </div>
          <div class="card">
            <div class="card-head"><h3>Jadwal Terdekat</h3><a class="link" href="jadwal.php">Semua →</a></div>
            <div class="card-pad">
              <?php foreach ($jadwalList as $j): ?>
                <div class="notif-item">
                  <div class="notif-ic tone-blue">📅</div>
                  <div class="notif-txt">
                    <strong><?= htmlspecialchars($j['kelas']) ?> · <?= htmlspecialchars($j['lokasi']) ?></strong>
                    <span><?= htmlspecialchars($j['tanggal']) ?> · <?= htmlspecialchars($j['jam_mulai']) ?>–<?= htmlspecialchars($j['jam_selesai']) ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

      <!-- ===================== GURU ===================== -->
      <?php elseif ($role === 'guru'): ?>
        <div class="alert alert-info mb-2">👋 Selamat datang, <strong><?= htmlspecialchars($u['nama']) ?></strong>!</div>
        <div class="stat-grid" style="grid-template-columns:repeat(4,1fr)">
          <div class="stat-card"><div class="ico tone-blue">👥</div><div><div class="num"><?= $totalSiswa ?></div><div class="lbl">Total Siswa</div></div></div>
          <div class="stat-card"><div class="ico tone-green">✓</div><div><div class="num"><?= $sudahAmbil ?></div><div class="lbl">Sudah Mengambil</div></div></div>
          <div class="stat-card"><div class="ico tone-amber">⏳</div><div><div class="num"><?= $belumAmbil ?></div><div class="lbl">Belum Mengambil</div></div></div>
          <div class="stat-card"><div class="ico tone-green">📈</div><div><div class="num"><?= $persen ?>%</div><div class="lbl">Persentase Ambil</div><div class="progress"><div style="width:<?= min($persen,100) ?>%"></div></div></div></div>
        </div>
        <div class="grid-2">
          <div class="card">
            <div class="card-head"><h3>Pengambilan per Kelas</h3></div>
            <div class="card-pad" style="position:relative;height:260px"><canvas id="chartKelas"></canvas></div>
          </div>
          <div class="card">
            <div class="card-head"><h3>Status Distribusi</h3></div>
            <div class="card-pad" style="position:relative;height:260px"><canvas id="chartDonut"></canvas></div>
          </div>
        </div>
        <div class="card mt-2">
          <div class="card-head"><h3>Akses Cepat</h3></div>
          <div class="card-pad" style="display:flex;gap:12px;flex-wrap:wrap">
            <a href="data_siswa.php" class="btn btn-outline">👥 Data Siswa</a>
            <a href="laporan.php" class="btn btn-outline">📈 Laporan</a>
            <a href="jadwal.php" class="btn btn-outline">📅 Jadwal</a>
          </div>
        </div>

      <!-- ===================== PETUGAS ===================== -->
      <?php elseif ($role === 'petugas'): ?>
        <div class="alert alert-info mb-2">👋 Halo Petugas, <strong><?= htmlspecialchars($u['nama']) ?></strong>!</div>
        <div class="stat-grid">
          <div class="stat-card"><div class="ico tone-blue">🍱</div><div><div class="num"><?= number_format($stok['disediakan']) ?></div><div class="lbl">MBG Disediakan</div></div></div>
          <div class="stat-card"><div class="ico tone-green">✓</div><div><div class="num"><?= $sudahAmbil ?></div><div class="lbl">Sudah Mengambil</div></div></div>
          <div class="stat-card"><div class="ico tone-amber">⏳</div><div><div class="num"><?= $belumAmbil ?></div><div class="lbl">Belum Mengambil</div></div></div>
          <div class="stat-card"><div class="ico tone-red">🔒</div><div><div class="num"><?= $belumKembali ?></div><div class="lbl">Wadah Belum Kembali</div></div></div>
        </div>
        <div class="grid-2 mb-2">
          <div class="card">
            <div class="card-head"><h3>Tren 7 Hari</h3></div>
            <div class="card-pad" style="position:relative;height:260px"><canvas id="chartStok"></canvas></div>
          </div>
          <div class="card">
            <div class="card-head"><h3>Aksi Cepat</h3></div>
            <div class="card-pad" style="display:flex;flex-direction:column;gap:12px">
              <a href="pengambilan.php" class="btn btn-primary btn-block">📱 Konfirmasi Pengambilan</a>
              <a href="pengembalian.php" class="btn btn-outline btn-block">📦 Konfirmasi Pengembalian</a>
              <a href="stok.php" class="btn btn-outline btn-block">📋 Update Stok</a>
              <a href="pengambilan.php?bulk=1" class="btn btn-success btn-block">⚡ Bulk Konfirmasi</a>
            </div>
          </div>
        </div>
        <div class="card">
          <div class="card-head"><h3>Aktivitas Terbaru</h3><a class="link" href="riwayat.php">Semua →</a></div>
          <div class="card-pad">
            <div class="timeline">
              <?php foreach ($aktivitas as $a): ?>
                <div class="timeline-item done">
                  <strong><?= htmlspecialchars($a['aktivitas']) ?></strong>
                  <span><?= htmlspecialchars($a['pengguna']) ?> · <?= htmlspecialchars($a['waktu']) ?> · <?= htmlspecialchars($a['keterangan']) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

      <!-- ===================== ADMIN ===================== -->
      <?php else: ?>
        <div class="alert alert-info mb-2">
          👋 Selamat datang, <strong><?= htmlspecialchars($u['nama']) ?></strong>!
          <?php if ($unreadNotif > 0): ?> · <a href="notifikasi.php" style="color:inherit;text-decoration:underline"><?= $unreadNotif ?> notifikasi belum dibaca</a><?php endif; ?>
        </div>
        <div class="stat-grid">
          <div class="stat-card"><div class="ico tone-blue">👥</div><div><div class="num"><?= $totalSiswa ?></div><div class="lbl">Total Siswa</div></div></div>
          <div class="stat-card"><div class="ico tone-blue">🍱</div><div><div class="num"><?= number_format($stok['disediakan']) ?></div><div class="lbl">MBG Disediakan</div></div></div>
          <div class="stat-card"><div class="ico tone-green">✓</div><div><div class="num"><?= $sudahAmbil ?></div><div class="lbl">Sudah Mengambil</div></div></div>
          <div class="stat-card"><div class="ico tone-amber">⏳</div><div><div class="num"><?= $belumAmbil ?></div><div class="lbl">Belum Mengambil</div></div></div>
          <div class="stat-card"><div class="ico tone-green">📦</div><div><div class="num"><?= $sudahKembali ?></div><div class="lbl">Wadah Kembali</div></div></div>
          <div class="stat-card"><div class="ico tone-red">🔒</div><div><div class="num"><?= $belumKembali ?></div><div class="lbl">Belum Kembali</div></div></div>
          <div class="stat-card"><div class="ico tone-blue">🔔</div><div><div class="num"><?= $unreadNotif ?></div><div class="lbl">Notif Belum Dibaca</div></div></div>
          <div class="stat-card"><div class="ico tone-green">📈</div><div><div class="num"><?= $persen ?>%</div><div class="lbl">Persentase Ambil</div><div class="progress"><div style="width:<?= min($persen,100) ?>%"></div></div></div></div>
        </div>

        <div class="grid-2 mb-2">
          <div class="card">
            <div class="card-head"><h3>Tren Distribusi 7 Hari</h3></div>
            <div class="card-pad" style="position:relative;height:280px"><canvas id="chartStok"></canvas></div>
          </div>
          <div class="card">
            <div class="card-head"><h3>Pengambilan per Kelas</h3></div>
            <div class="card-pad" style="position:relative;height:280px"><canvas id="chartKelas"></canvas></div>
          </div>
        </div>

        <div class="grid-2 mb-2">
          <div class="card">
            <div class="card-head"><h3>Status Distribusi</h3></div>
            <div class="card-pad" style="position:relative;height:240px"><canvas id="chartDonut"></canvas></div>
          </div>
          <div class="card">
            <div class="card-head"><h3>Notifikasi Terbaru</h3><a class="link" href="notifikasi.php">Lihat Semua →</a></div>
            <div class="card-pad">
              <?php foreach ($notifs as $n):
                $t = $tipeMap[$n['tipe'] ?? 'info'] ?? $tipeMap['warning']; ?>
                <div class="notif-item">
                  <div class="notif-ic <?= $t[0] ?>"><?= $t[1] ?></div>
                  <div class="notif-txt">
                    <strong><?= htmlspecialchars($n['pesan']) ?></strong>
                    <span><?= htmlspecialchars($n['waktu']) ?><?= empty($n['dibaca']) ? ' · <span class="badge badge-blue">Baru</span>' : '' ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-head"><h3>Aktivitas Terbaru</h3><a class="link" href="riwayat.php">Lihat Semua →</a></div>
          <div class="card-pad">
            <div class="timeline">
              <?php foreach ($aktivitas as $a): ?>
                <div class="timeline-item done">
                  <strong><?= htmlspecialchars($a['aktivitas']) ?></strong>
                  <span><?= htmlspecialchars($a['pengguna']) ?> · <?= htmlspecialchars($a['waktu']) ?> · <?= htmlspecialchars($a['keterangan']) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  if (typeof Chart === 'undefined') {
    console.error('Chart.js gagal dimuat. Cek koneksi internet.');
    return;
  }

  const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { labels: { color: getComputedStyle(document.documentElement).getPropertyValue('--ink-600').trim() || '#475569' } }
    }
  };

  // Tren stok
  const elStok = document.getElementById('chartStok');
  if (elStok) {
    new Chart(elStok, {
      type: 'line',
      data: {
        labels: <?= $chartStokLabels ?>,
        datasets: [
          {
            label: 'Disediakan',
            data: <?= $chartStokDisedia ?>,
            borderColor: '#94a3b8',
            backgroundColor: 'rgba(148,163,184,.1)',
            fill: false, tension: .35, pointRadius: 4
          },
          {
            label: 'Dibagikan',
            data: <?= $chartStokDibagi ?>,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37,99,235,.12)',
            fill: true, tension: .35, pointRadius: 4,
            pointBackgroundColor: '#2563eb'
          }
        ]
      },
      options: {
        ...chartDefaults,
        scales: {
          y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,.15)' } },
          x: { grid: { display: false } }
        }
      }
    });
  }

  // Per kelas
  const elKelas = document.getElementById('chartKelas');
  if (elKelas) {
    new Chart(elKelas, {
      type: 'bar',
      data: {
        labels: <?= $chartKelasLabels ?>,
        datasets: [
          { label: 'Sudah Ambil', data: <?= $chartKelasAmbil ?>, backgroundColor: '#2563eb', borderRadius: 6, barPercentage: .65 },
          { label: 'Total Siswa', data: <?= $chartKelasTotal ?>, backgroundColor: 'rgba(37,99,235,.2)', borderRadius: 6, barPercentage: .65 }
        ]
      },
      options: {
        ...chartDefaults,
        scales: {
          y: { beginAtZero: true, grid: { color: 'rgba(148,163,184,.15)' } },
          x: { grid: { display: false } }
        }
      }
    });
  }

  // Donut status
  const elDonut = document.getElementById('chartDonut');
  if (elDonut) {
    new Chart(elDonut, {
      type: 'doughnut',
      data: {
        labels: ['Sudah Ambil', 'Belum Ambil', 'Wadah Kembali', 'Belum Kembali'],
        datasets: [{
          data: [<?= $sudahAmbil ?>, <?= $belumAmbil ?>, <?= $sudahKembali ?>, <?= $belumKembali ?>],
          backgroundColor: ['#2563eb', '#f59e0b', '#059669', '#dc2626'],
          borderWidth: 0,
          hoverOffset: 6
        }]
      },
      options: {
        ...chartDefaults,
        cutout: '62%',
        plugins: {
          legend: { position: 'bottom', labels: { boxWidth: 12, padding: 14, font: { size: 12 } } }
        }
      }
    });
  }
});
</script>
</body>
</html>
