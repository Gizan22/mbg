<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/auth.php';
require __DIR__ . '/config/validate.php';
requireLogin();
requireRole(['admin', 'petugas', 'guru']);
$pageTitle = 'Data Siswa';
$JURUSAN_LIST = ['AKL', 'MPLB', 'RPL'];

// ---------- HANDLE CRUD ----------
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        if (!hasRole(['admin', 'petugas'])) {
            header('Location: data_siswa.php?msg=forbidden');
            exit;
        }
        $nis     = trim($_POST['nis'] ?? '');
        $nama    = trim($_POST['nama'] ?? '');
        $kelas   = trim($_POST['kelas'] ?? '');
        $jurusan = strtoupper(trim($_POST['jurusan'] ?? ''));
        $ttl     = trim($_POST['ttl'] ?? '');
        $alamat  = trim($_POST['alamat'] ?? '');

        $errors = array_values(array_filter([
            v_nis($nis),
            v_nama($nama),
            v_kelas($kelas),
            v_jurusan($jurusan),
            (mb_strlen($ttl) > 100) ? 'TTL maksimal 100 karakter.' : null,
            (mb_strlen($alamat) > 255) ? 'Alamat maksimal 255 karakter.' : null,
        ]));

        if ($errors) {
            v_fail_redirect('data_siswa.php?msg=validation', $errors, compact('nis','nama','kelas','jurusan','ttl','alamat'));
        }

        // cek NIS unik
        $dup = false;
        foreach (db_all('siswa') as $s) {
            if ($s['nis'] === $nis && ($action === 'add' || (int)$s['id'] !== (int)($_POST['id'] ?? 0))) {
                $dup = true; break;
            }
        }
        if ($dup) {
            $msg = 'duplicate';
        } elseif ($action === 'add') {
            db_insert('siswa', [
                'nis'=>$nis,'nama'=>$nama,'kelas'=>$kelas,'jurusan'=>$jurusan,
                'ttl'=>$ttl,'alamat'=>$alamat,'status_ambil'=>0,'status_kembali'=>0,
            ]);
            $msg = 'added';
        } else {
            $id = (int)($_POST['id'] ?? 0);
            if ($id < 1) { $msg = 'invalid'; }
            else {
                db_update('siswa', $id, [
                    'nis'=>$nis,'nama'=>$nama,'kelas'=>$kelas,'jurusan'=>$jurusan,
                    'ttl'=>$ttl,'alamat'=>$alamat,
                ]);
                $msg = 'edited';
            }
        }
    } elseif ($action === 'delete') {
        if (!hasRole(['admin'])) {
            header('Location: data_siswa.php?msg=forbidden');
            exit;
        }
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            db_delete_where('pengambilan', fn($r) => (int)$r['siswa_id'] === $id);
            db_delete_where('pengembalian', fn($r) => (int)$r['siswa_id'] === $id);
            db_delete('siswa', $id);
            $msg = 'deleted';
        } else {
            $msg = 'invalid';
        }
    } elseif ($action === 'import' && isset($_FILES['csvfile'])) {
        $file = $_FILES['csvfile'];
        if ($file['error'] === UPLOAD_ERR_OK && ($handle = fopen($file['tmp_name'], 'r'))) {
            $header = fgetcsv($handle);
            $count = 0;
            $existingNis = array_column(db_all('siswa'), 'nis');
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= 4) {
                    $j = strtoupper(trim($row[3]));
                    if (!in_array($j, ['AKL','MPLB','RPL'], true)) continue;
                    $nis = trim($row[0]);
                    if (in_array($nis, $existingNis, true)) continue;
                    db_insert('siswa', [
                        'nis'=>$nis,
                        'nama'=>trim($row[1]),
                        'kelas'=>trim($row[2]),
                        'jurusan'=>$j,
                        'ttl'=>trim($row[4] ?? ''),
                        'alamat'=>trim($row[5] ?? ''),
                        'status_ambil'=>0,
                        'status_kembali'=>0,
                    ]);
                    $existingNis[] = $nis;
                    $count++;
                }
            }
            fclose($handle);
            $msg = 'imported&count=' . $count;
        } else {
            $msg = 'import_error';
        }
    }
    header('Location: data_siswa.php?msg=' . $msg);
    exit;
}
$msg = $_GET['msg'] ?? '';

// ---------- FILTER / SEARCH ----------
$q       = trim($_GET['q'] ?? '');
$kelas   = trim($_GET['kelas'] ?? '');
$jurusan = trim($_GET['jurusan'] ?? '');

$siswaList = db_all('siswa');
if ($q !== '') {
    $siswaList = array_values(array_filter($siswaList, function($s) use ($q) {
        return stripos($s['nama'], $q) !== false || stripos($s['nis'], $q) !== false;
    }));
}
if ($kelas !== '') {
    $siswaList = array_values(array_filter($siswaList, fn($s) => $s['kelas'] === $kelas));
}
if ($jurusan !== '') {
    $siswaList = array_values(array_filter($siswaList, fn($s) => $s['jurusan'] === $jurusan));
}
usort($siswaList, fn($a,$b) => $a['id'] <=> $b['id']);

$allSiswa = db_all('siswa');
$kelasOptions = array_values(array_unique(array_column($allSiswa, 'kelas')));
sort($kelasOptions);
$jurusanOptions = array_values(array_unique(array_column($allSiswa, 'jurusan')));
sort($jurusanOptions);

// ---------- DETAIL SELECTED ----------
$detail = null;
if (isset($_GET['id'])) {
    $detail = db_find('siswa', (int)$_GET['id']);
}
if (!$detail && count($siswaList)) { $detail = $siswaList[0]; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
<title>Data Siswa — MBG</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="content">

      <?php if ($msg === 'added'): ?><div class="alert alert-success">✓ Data siswa berhasil ditambahkan.</div><?php endif; ?>
      <?php if ($msg === 'edited'): ?><div class="alert alert-success">✓ Data siswa berhasil diperbarui.</div><?php endif; ?>
      <?php if ($msg === 'deleted'): ?><div class="alert alert-success">✓ Data siswa berhasil dihapus.</div><?php endif; ?>
      <?php if ($msg === 'delete_error'): ?><div class="alert alert-danger">Gagal menghapus siswa. Coba lagi.</div><?php endif; ?>
      <?php if ($msg === 'forbidden'): ?><div class="alert alert-danger">Anda tidak memiliki izin untuk melakukan aksi ini.</div><?php endif; ?>
      <?php if ($msg === 'duplicate'): ?><div class="alert alert-danger">NIS sudah terdaftar. Gunakan NIS lain.</div><?php endif; ?>
      <?php if ($msg === 'validation'): $ferr = v_flash_errors(); if ($ferr): ?>
        <div class="alert alert-danger">
          <strong>Validasi gagal:</strong>
          <ul style="margin:6px 0 0 18px"><?php foreach ($ferr as $fe): ?><li><?= htmlspecialchars($fe) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; endif; ?>
      <?php if ($msg === 'error'): ?><div class="alert alert-danger">Terjadi kesalahan saat menyimpan data.</div><?php endif; ?>
      <?php if (str_starts_with($msg ?? '', 'imported')): $c = (int)($_GET['count'] ?? 0); ?>
        <div class="alert alert-success">✓ Berhasil mengimpor <?= $c ?> data siswa dari CSV.</div>
      <?php endif; ?>
      <?php if ($msg === 'import_error'): ?><div class="alert alert-danger">Gagal mengimpor file. Pastikan format CSV benar.</div><?php endif; ?>

      <div class="toolbar">
        <div class="toolbar-actions">
          <?php if (hasRole(['admin','petugas'])): ?>
          <button class="btn btn-primary" onclick="openModal('addModal')">＋ Tambah Siswa</button>
          <button class="btn btn-outline" onclick="openModal('importModal')">↑ Import CSV</button>
          <?php endif; ?>
        </div>
        <form method="get" class="search-box" style="max-width:280px">
          <span>&#128269;</span>
          <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Cari nama / NIS / NISN...">
        </form>
      </div>

      <div class="grid-2" style="grid-template-columns:2.1fr 1fr">
        <div class="card">
          <div class="table-wrap">
            <table class="tbl">
              <thead><tr><th>No</th><th>NIS / NISN</th><th>Nama</th><th>Kelas</th><th>Jurusan</th><th>Aksi</th></tr></thead>
              <tbody>
                <?php if (!$siswaList): ?>
                  <tr><td colspan="6" class="empty-state">Tidak ada data siswa yang cocok.</td></tr>
                <?php endif; ?>
                <?php foreach ($siswaList as $i => $s): ?>
                <tr>
                  <td><?= $i+1 ?></td>
                  <td><?= htmlspecialchars($s['nis']) ?></td>
                  <td>
                    <div class="name-cell">
                      <div class="avatar-sm"><?= strtoupper(substr($s['nama'],0,1)) ?></div>
                      <?= htmlspecialchars($s['nama']) ?>
                    </div>
                  </td>
                  <td><?= htmlspecialchars($s['kelas']) ?></td>
                  <td><?= htmlspecialchars($s['jurusan']) ?></td>
                  <td>
                    <a class="btn-icon" href="data_siswa.php?id=<?= $s['id'] ?>" title="Lihat">👁</a>
                    <?php if (hasRole(['admin','petugas'])): ?>
                    <button class="btn-icon" title="Edit" onclick='openEdit(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)'>✎</button>
                    <button class="btn-icon danger" title="Hapus" onclick="openDelete(<?= $s['id'] ?>, <?= htmlspecialchars(json_encode($s['nama']), ENT_QUOTES) ?>)">🗑</button>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div>
          <div class="card mb16">
            <div class="card-head"><h3>Detail Siswa</h3></div>
            <div class="card-pad">
              <?php if ($detail): ?>
                <div class="profile-mini">
                  <div class="avatar-lg"><?= strtoupper(substr($detail['nama'],0,1)) ?></div>
                  <div><strong><?= htmlspecialchars($detail['nama']) ?></strong><br><span class="muted"><?= htmlspecialchars($detail['nis']) ?></span></div>
                </div>
                <div class="detail-row"><span>Nama</span><span><?= htmlspecialchars($detail['nama']) ?></span></div>
                <div class="detail-row"><span>NIS / NISN</span><span><?= htmlspecialchars($detail['nis']) ?></span></div>
                <div class="detail-row"><span>Kelas</span><span><?= htmlspecialchars($detail['kelas']) ?></span></div>
                <div class="detail-row"><span>Jurusan</span><span><?= htmlspecialchars($detail['jurusan']) ?></span></div>
                <div class="detail-row"><span>Tempat, Tgl Lahir</span><span><?= htmlspecialchars($detail['ttl']) ?></span></div>
                <div class="detail-row"><span>Alamat</span><span><?= htmlspecialchars($detail['alamat']) ?></span></div>
              <?php else: ?>
                <p class="muted">Pilih siswa untuk melihat detail.</p>
              <?php endif; ?>
            </div>
          </div>

          <div class="card">
            <div class="card-head"><h3>Filter</h3></div>
            <div class="card-pad filter-form">
              <form method="get">
                <div class="field">
                  <label>Pilih Kelas</label>
                  <select name="kelas">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($kelasOptions as $k): ?>
                      <option value="<?= htmlspecialchars($k) ?>" <?= $kelas===$k?'selected':'' ?>><?= htmlspecialchars($k) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="field">
                  <label>Pilih Jurusan</label>
                  <select name="jurusan">
                    <option value="">Semua Jurusan</option>
                    <?php foreach ($JURUSAN_LIST as $j): ?>
                      <option value="<?= $j ?>" <?= $jurusan===$j?'selected':'' ?>><?= $j ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Terapkan</button>
              </form>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Modal Tambah -->
<div class="modal-bg" id="addModal">
  <div class="modal">
    <div class="modal-head"><h3>Tambah Siswa</h3><button class="x" onclick="closeModal('addModal')">&times;</button></div>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="field">
          <label>NIS / NISN</label>
          <input type="text" name="nis" required pattern="[0-9]{5,20}" minlength="5" maxlength="20"
                 placeholder="5–20 digit angka" title="NIS harus 5–20 digit angka saja">
        </div>
        <div class="field">
          <label>Nama</label>
          <input type="text" name="nama" required minlength="2" maxlength="100" placeholder="Nama lengkap">
        </div>
        <div class="field">
          <label>Kelas</label>
          <input type="text" name="kelas" placeholder="cth. IX RPL 3" required maxlength="30">
        </div>
        <div class="field">
          <label>Jurusan</label>
          <select name="jurusan" required>
            <option value="">— Pilih Jurusan —</option>
            <option value="AKL">AKL (Akuntansi dan Keuangan Lembaga)</option>
            <option value="MPLB">MPLB (Manajemen Perkantoran dan Layanan Bisnis)</option>
            <option value="RPL">RPL (Rekayasa Perangkat Lunak)</option>
          </select>
        </div>
        <div class="field">
          <label>Tempat, Tgl Lahir</label>
          <input type="text" name="ttl" maxlength="100" placeholder="cth. Bandung, 12-05-2011">
        </div>
        <div class="field">
          <label>Alamat</label>
          <input type="text" name="alamat" maxlength="255" placeholder="Alamat lengkap">
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal-bg" id="editModal">
  <div class="modal">
    <div class="modal-head"><h3>Edit Siswa</h3><button class="x" onclick="closeModal('editModal')">&times;</button></div>
    <form method="post" id="editForm">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-body">
        <div class="field">
          <label>NIS / NISN</label>
          <input type="text" name="nis" id="edit_nis" required pattern="[0-9]{5,20}" minlength="5" maxlength="20"
                 title="NIS harus 5–20 digit angka saja">
        </div>
        <div class="field">
          <label>Nama</label>
          <input type="text" name="nama" id="edit_nama" required minlength="2" maxlength="100">
        </div>
        <div class="field">
          <label>Kelas</label>
          <input type="text" name="kelas" id="edit_kelas" required maxlength="30">
        </div>
        <div class="field">
          <label>Jurusan</label>
          <select name="jurusan" id="edit_jurusan" required>
            <option value="AKL">AKL (Akuntansi dan Keuangan Lembaga)</option>
            <option value="MPLB">MPLB (Manajemen Perkantoran dan Layanan Bisnis)</option>
            <option value="RPL">RPL (Rekayasa Perangkat Lunak)</option>
          </select>
        </div>
        <div class="field">
          <label>Tempat, Tgl Lahir</label>
          <input type="text" name="ttl" id="edit_ttl" maxlength="100">
        </div>
        <div class="field">
          <label>Alamat</label>
          <input type="text" name="alamat" id="edit_alamat" maxlength="255">
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Delete -->
<div class="modal-bg" id="deleteModal">
  <div class="modal" style="max-width:380px">
    <div class="modal-head"><h3>Hapus Siswa</h3><button class="x" onclick="closeModal('deleteModal')">&times;</button></div>
    <form method="post">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" id="delete_id">
      <div class="modal-body">
        <p>Yakin ingin menghapus data <strong id="delete_nama"></strong>? Tindakan ini tidak dapat dibatalkan.</p>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('deleteModal')">Batal</button>
        <button type="submit" class="btn btn-danger">Hapus</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Import -->
<div class="modal-bg" id="importModal">
  <div class="modal" style="max-width:420px">
    <div class="modal-head"><h3>Import Data Siswa (CSV)</h3><button class="x" onclick="closeModal('importModal')">&times;</button></div>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="import">
      <div class="modal-body">
        <div class="field">
          <label>Pilih File CSV</label>
          <input type="file" name="csvfile" accept=".csv" required>
        </div>
        <p class="text-muted text-sm">
          Format kolom: <strong>NIS, Nama, Kelas, Jurusan, TTL, Alamat</strong><br>
          Baris pertama dianggap header dan akan dilewati.
        </p>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('importModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Import Sekarang</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
function openEdit(s){
  document.getElementById('edit_id').value = s.id;
  document.getElementById('edit_nis').value = s.nis;
  document.getElementById('edit_nama').value = s.nama;
  document.getElementById('edit_kelas').value = s.kelas;
  document.getElementById('edit_jurusan').value = s.jurusan;
  document.getElementById('edit_ttl').value = s.ttl;
  document.getElementById('edit_alamat').value = s.alamat;
  openModal('editModal');
}
function openDelete(id, nama){
  document.getElementById('delete_id').value = id;
  document.getElementById('delete_nama').textContent = nama;
  openModal('deleteModal');
}
document.querySelectorAll('.modal-bg').forEach(bg=>{
  bg.addEventListener('click', e=>{ if(e.target === bg) bg.classList.remove('open'); });
});
</script>
</body>
</html>
