<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/auth.php';
requireLogin();
requireRole(['admin','petugas','guru']);
$pageTitle = 'Laporan';

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_mbg_' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['NIS', 'Nama', 'Kelas', 'Jurusan', 'Status Ambil', 'Status Kembali']);
    $rows = db_all('siswa');
    usort($rows, fn($a,$b) => ($a['kelas'].$a['nama']) <=> ($b['kelas'].$b['nama']));
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['nis'], $r['nama'], $r['kelas'], $r['jurusan'],
            $r['status_ambil'] ? 'Sudah' : 'Belum',
            $r['status_kembali'] ? 'Sudah' : 'Belum'
        ]);
    }
    fclose($out);
    exit;
}

$siswaAll = db_all('siswa');
$totalSiswa = count($siswaAll);
$sudahAmbil = count(array_filter($siswaAll, fn($s) => (int)$s['status_ambil'] === 1));
$belumAmbil = $totalSiswa - $sudahAmbil;
$sudahKembali = count(array_filter($siswaAll, fn($s) => (int)$s['status_kembali'] === 1));
$belumKembali = $totalSiswa - $sudahKembali;

$byKelasMap = [];
foreach ($siswaAll as $s) {
    $k = $s['kelas'];
    if (!isset($byKelasMap[$k])) $byKelasMap[$k] = ['kelas'=>$k,'total'=>0,'ambil'=>0,'kembali'=>0];
    $byKelasMap[$k]['total']++;
    if ((int)$s['status_ambil']===1) $byKelasMap[$k]['ambil']++;
    if ((int)$s['status_kembali']===1) $byKelasMap[$k]['kembali']++;
}
ksort($byKelasMap);
$byKelas = array_values($byKelasMap);

$byJurusanMap = [];
foreach ($siswaAll as $s) {
    $j = $s['jurusan'];
    if (!isset($byJurusanMap[$j])) $byJurusanMap[$j] = ['jurusan'=>$j,'total'=>0,'ambil'=>0];
    $byJurusanMap[$j]['total']++;
    if ((int)$s['status_ambil']===1) $byJurusanMap[$j]['ambil']++;
}
ksort($byJurusanMap);
$byJurusan = array_values($byJurusanMap);

$stokRows = db_all('stok');
usort($stokRows, fn($a,$b) => $b['id'] <=> $a['id']);
$stok = $stokRows[0] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
<title>Laporan — MBG</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="content">

      <div class="page-header">
        <h2>📊 Laporan Distribusi MBG</h2>
        <div class="actions">
          <a href="?export=csv<?= $filterKelas ? '&kelas='.urlencode($filterKelas) : '' ?>" class="btn btn-primary">⬇ Export CSV</a>
          <button onclick="window.print()" class="btn btn-outline">🖨 Cetak</button>
        </div>
      </div>

      <!-- Summary cards -->
      <div class="stat-grid" style="grid-template-columns:repeat(4,1fr)">
        <div class="stat-card">
          <div class="ico tone-blue">👥</div>
          <div><div class="num"><?= $totalSiswa ?></div><div class="lbl">Total Siswa</div></div>
        </div>
        <div class="stat-card">
          <div class="ico tone-green">✓</div>
          <div><div class="num"><?= $sudahAmbil ?></div><div class="lbl">Sudah Ambil (<?= $totalSiswa ? round($sudahAmbil/$totalSiswa*100) : 0 ?>%)</div></div>
        </div>
        <div class="stat-card">
          <div class="ico tone-green">📦</div>
          <div><div class="num"><?= $sudahKembali ?></div><div class="lbl">Wadah Kembali</div></div>
        </div>
        <div class="stat-card">
          <div class="ico tone-amber">🍱</div>
          <div><div class="num"><?= number_format($stok['dibagikan']) ?>/<?= number_format($stok['disediakan']) ?></div><div class="lbl">MBG Dibagikan</div></div>
        </div>
      </div>

      <!-- Per kelas summary -->
      <div class="card mb-2">
        <div class="card-head"><h3>Ringkasan per Kelas</h3></div>
        <div class="table-wrap">
          <table class="tbl">
            <thead>
              <tr>
                <th>Kelas</th>
                <th>Total Siswa</th>
                <th>Sudah Ambil</th>
                <th>Belum Ambil</th>
                <th>Wadah Kembali</th>
                <th>Persentase</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($byKelas as $k):
                $pct = $k['total'] > 0 ? round($k['ambil'] / $k['total'] * 100) : 0;
              ?>
              <tr>
                <td><strong><?= htmlspecialchars($k['kelas']) ?></strong></td>
                <td><?= $k['total'] ?></td>
                <td><span class="badge badge-green"><?= $k['ambil'] ?></span></td>
                <td><span class="badge badge-amber"><?= $k['total'] - $k['ambil'] ?></span></td>
                <td><?= $k['kembali'] ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px">
                    <div class="progress" style="width:80px;margin:0"><div style="width:<?= $pct ?>%"></div></div>
                    <span class="fw-700"><?= $pct ?>%</span>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Detail siswa -->
      <div class="card">
        <div class="card-head">
          <h3>Detail Siswa</h3>
          <form method="get" style="display:flex;gap:8px">
            <select name="kelas" onchange="this.form.submit()" style="padding:7px 12px;border-radius:8px;border:1px solid var(--line);background:var(--card);color:var(--ink-900)">
              <option value="">Semua Kelas</option>
              <?php foreach ($kelasList as $kl): ?>
                <option value="<?= htmlspecialchars($kl) ?>" <?= $filterKelas === $kl ? 'selected' : '' ?>><?= htmlspecialchars($kl) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>
        <div class="table-wrap">
          <table class="tbl">
            <thead>
              <tr>
                <th>#</th>
                <th>NIS</th>
                <th>Nama</th>
                <th>Kelas</th>
                <th>Status Ambil</th>
                <th>Status Kembali</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($siswaList)): ?>
                <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--ink-400)">Tidak ada data</td></tr>
              <?php else: $i=1; foreach ($siswaList as $s): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($s['nis']) ?></td>
                <td>
                  <div class="name-cell">
                    <div class="avatar-sm"><?= strtoupper(mb_substr($s['nama'],0,1)) ?></div>
                    <?= htmlspecialchars($s['nama']) ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($s['kelas']) ?></td>
                <td>
                  <?php if ($s['status_ambil']): ?>
                    <span class="badge badge-green">✓ Sudah</span>
                  <?php else: ?>
                    <span class="badge badge-amber">Belum</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($s['status_kembali']): ?>
                    <span class="badge badge-green">✓ Sudah</span>
                  <?php else: ?>
                    <span class="badge badge-red">Belum</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
