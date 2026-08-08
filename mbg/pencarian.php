<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/auth.php';
requireLogin();
requireRole(['admin','petugas','guru']);
$pageTitle = 'Pencarian & Filter';

$q       = trim($_GET['q'] ?? '');
$kelas   = trim($_GET['kelas'] ?? '');
$jurusan = trim($_GET['jurusan'] ?? '');
$tanggal = trim($_GET['tanggal'] ?? '');
$statusA = trim($_GET['status_ambil'] ?? '');
$statusK = trim($_GET['status_kembali'] ?? '');
$hasFilter = $q!=='' || $kelas!=='' || $jurusan!=='' || $tanggal!=='' || $statusA!=='' || $statusK!=='';

$results = db_all('siswa');
if ($q !== '') {
    $results = array_values(array_filter($results, fn($s) =>
        stripos($s['nama'], $q) !== false || stripos($s['nis'], $q) !== false
    ));
}
if ($kelas !== '') $results = array_values(array_filter($results, fn($s) => $s['kelas'] === $kelas));
if ($jurusan !== '') $results = array_values(array_filter($results, fn($s) => $s['jurusan'] === $jurusan));
if ($statusA === 'sudah') $results = array_values(array_filter($results, fn($s) => (int)$s['status_ambil'] === 1));
elseif ($statusA === 'belum') $results = array_values(array_filter($results, fn($s) => (int)$s['status_ambil'] === 0));
if ($statusK === 'sudah') $results = array_values(array_filter($results, fn($s) => (int)$s['status_kembali'] === 1));
elseif ($statusK === 'belum') $results = array_values(array_filter($results, fn($s) => (int)$s['status_kembali'] === 0));
usort($results, fn($a,$b) => ($a['kelas'].$a['nama']) <=> ($b['kelas'].$b['nama']));

$hasil = $results; // alias untuk template

$all = db_all('siswa');
$kelasOptions = array_values(array_unique(array_column($all, 'kelas'))); sort($kelasOptions);
$jurusanOptions = array_values(array_unique(array_column($all, 'jurusan'))); sort($jurusanOptions);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
<title>Pencarian & Filter — MBG</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="content">

      <div class="grid-2" style="grid-template-columns:1fr 2.1fr;align-items:start">
        <div class="card">
          <div class="card-head"><h3>Filter</h3></div>
          <div class="card-pad filter-form">
            <form method="get">
              <div class="field"><label>Cari nama / NIS / NISN</label>
                <div class="search-box"><span>&#128269;</span><input type="text" name="q" value="<?= htmlspecialchars($q) ?>"></div>
              </div>
              <div class="field"><label>Pilih Kelas</label>
                <select name="kelas"><option value="">Semua Kelas</option>
                  <?php foreach ($kelasOptions as $k): ?><option value="<?= htmlspecialchars($k) ?>" <?= $kelas===$k?'selected':'' ?>><?= htmlspecialchars($k) ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="field"><label>Pilih Jurusan</label>
                <select name="jurusan"><option value="">Semua Jurusan</option>
                  <?php foreach ($jurusanOptions as $j): ?><option value="<?= htmlspecialchars($j) ?>" <?= $jurusan===$j?'selected':'' ?>><?= htmlspecialchars($j) ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="field"><label>Pilih Tanggal</label><input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>"></div>
              <div class="field"><label>Status Pengambilan</label>
                <select name="status_ambil"><option value="">Semua</option>
                  <option value="sudah" <?= $statusA==='sudah'?'selected':'' ?>>Sudah Mengambil</option>
                  <option value="belum" <?= $statusA==='belum'?'selected':'' ?>>Belum Mengambil</option>
                </select>
              </div>
              <div class="field"><label>Status Pengembalian</label>
                <select name="status_kembali"><option value="">Semua</option>
                  <option value="sudah" <?= $statusK==='sudah'?'selected':'' ?>>Sudah Dikembalikan</option>
                  <option value="belum" <?= $statusK==='belum'?'selected':'' ?>>Belum Dikembalikan</option>
                </select>
              </div>
              <div style="display:flex;gap:10px">
                <a href="pencarian.php" class="btn btn-outline" style="flex:1">Reset</a>
                <button class="btn btn-primary" style="flex:1" type="submit">Terapkan Filter</button>
              </div>
            </form>
          </div>
        </div>

        <div class="card">
          <div class="card-head"><h3>Hasil Pencarian</h3><span class="muted" style="font-size:12px"><?= count($hasil) ?> siswa ditemukan</span></div>
          <div class="table-wrap">
            <table class="tbl">
              <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Jurusan</th><th>Pengambilan</th><th>Pengembalian</th></tr></thead>
              <tbody>
                <?php if (!$hasFilter): ?>
                  <tr><td colspan="6" class="empty-state">Gunakan filter di sebelah kiri untuk mencari data siswa.</td></tr>
                <?php elseif (!$hasil): ?>
                  <tr><td colspan="6" class="empty-state">Tidak ditemukan data yang cocok.</td></tr>
                <?php endif; ?>
                <?php foreach ($hasil as $s): ?>
                  <tr>
                    <td><?= htmlspecialchars($s['nis']) ?></td>
                    <td><div class="name-cell"><div class="avatar-sm"><?= strtoupper(substr($s['nama'],0,1)) ?></div><?= htmlspecialchars($s['nama']) ?></div></td>
                    <td><?= htmlspecialchars($s['kelas']) ?></td>
                    <td><?= htmlspecialchars($s['jurusan']) ?></td>
                    <td><?= $s['status_ambil'] ? '<span class="badge badge-green">Sudah</span>' : '<span class="badge badge-amber">Belum</span>' ?></td>
                    <td><?= $s['status_kembali'] ? '<span class="badge badge-green">Sudah</span>' : '<span class="badge badge-amber">Belum</span>' ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
