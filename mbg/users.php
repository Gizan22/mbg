<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/auth.php';
require __DIR__ . '/config/validate.php';
requireLogin();
requireRole(['admin']);
$pageTitle = 'Kelola Pengguna';
$u = currentUser();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? 'petugas';
        $nama     = trim($_POST['nama'] ?? '');
        $errors = v_errors([v_nama($nama), v_username($username), v_password($password, 6), v_role($role)]);
        if ($errors) {
            v_fail_redirect('users.php?msg=validation', $errors);
        }
        $exists = false;
        foreach (db_all('users') as $usr) {
            if ($usr['username'] === $username) { $exists = true; break; }
        }
        if ($exists) {
            $msg = 'exists';
        } else {
            db_insert('users', [
                'username'=>$username,
                'password'=>password_hash($password, PASSWORD_DEFAULT),
                'role'=>$role,
                'nama'=>$nama,
                'created_at'=>date('Y-m-d H:i:s'),
            ]);
            db_insert('aktivitas', [
                'waktu'=>date('H:i'), 'pengguna'=>$u['nama'],
                'aktivitas'=>'Tambah Pengguna', 'keterangan'=>$username.' ('.$role.')'
            ]);
            $msg = 'added';
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id < 1) {
            $msg = 'invalid';
        } elseif ($id === (int)$u['id']) {
            $msg = 'self';
        } else {
            db_delete('users', $id);
            $msg = 'deleted';
        }
    } elseif ($action === 'resetpw') {
        $id = (int)($_POST['id'] ?? 0);
        $newpw = $_POST['new_password'] ?? '';
        $err = v_password($newpw, 6);
        if ($id < 1 || $err) {
            $msg = 'invalid';
            if ($err) v_fail_redirect('users.php?msg=validation', [$err]);
        } else {
            db_update('users', $id, ['password' => password_hash($newpw, PASSWORD_DEFAULT)]);
            $msg = 'reset';
        }
    }
    header('Location: users.php?msg=' . $msg);
    exit;
}
$msg = $_GET['msg'] ?? '';
$users = db_all('users');
usort($users, fn($a,$b) => $a['id'] <=> $b['id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
<title>Kelola Pengguna — MBG</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main">
    <?php include __DIR__ . '/includes/topbar.php'; ?>
    <div class="content">

      <?php if ($msg === 'added'): ?><div class="alert alert-success">✓ Pengguna berhasil ditambahkan.</div><?php endif; ?>
      <?php if ($msg === 'deleted'): ?><div class="alert alert-success">✓ Pengguna berhasil dihapus.</div><?php endif; ?>
      <?php if ($msg === 'reset'): ?><div class="alert alert-success">✓ Password berhasil direset.</div><?php endif; ?>
      <?php if ($msg === 'exists'): ?><div class="alert alert-danger">Username sudah digunakan.</div><?php endif; ?>
      <?php if ($msg === 'self'): ?><div class="alert alert-danger">Tidak bisa menghapus akun sendiri.</div><?php endif; ?>
      <?php if ($msg === 'invalid'): ?><div class="alert alert-danger">Data tidak valid. Password minimal 6 karakter.</div><?php endif; ?>
      <?php if ($msg === 'validation'): $ferr = v_flash_errors(); if ($ferr): ?>
        <div class="alert alert-danger"><strong>Validasi gagal:</strong>
          <ul style="margin:6px 0 0 18px"><?php foreach ($ferr as $fe): ?><li><?= htmlspecialchars($fe) ?></li><?php endforeach; ?></ul>
        </div>
      <?php endif; endif; ?>

      <div class="page-header">
        <h2>👤 Kelola Pengguna</h2>
        <div class="actions">
          <button class="btn btn-primary" onclick="openModal('addModal')">＋ Tambah Pengguna</button>
        </div>
      </div>

      <div class="stat-grid" style="grid-template-columns:repeat(4,1fr)">
        <div class="stat-card"><div class="ico tone-blue">👤</div><div><div class="num"><?= $roleCounts['admin'] ?? 0 ?></div><div class="lbl">Admin</div></div></div>
        <div class="stat-card"><div class="ico tone-green">👷</div><div><div class="num"><?= $roleCounts['petugas'] ?? 0 ?></div><div class="lbl">Petugas</div></div></div>
        <div class="stat-card"><div class="ico tone-amber">🎓</div><div><div class="num"><?= $roleCounts['guru'] ?? 0 ?></div><div class="lbl">Guru</div></div></div>
        <div class="stat-card"><div class="ico tone-blue">🎒</div><div><div class="num"><?= $roleCounts['siswa'] ?? 0 ?></div><div class="lbl">Siswa</div></div></div>
      </div>

      <div class="card">
        <div class="table-wrap">
          <table class="tbl">
            <thead>
              <tr><th>#</th><th>Nama</th><th>Username</th><th>Peran</th><th>Dibuat</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <?php $i=1; foreach ($users as $usr): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td>
                  <div class="name-cell">
                    <div class="avatar-sm"><?= strtoupper(mb_substr($usr['nama'],0,1)) ?></div>
                    <?= htmlspecialchars($usr['nama']) ?>
                    <?php if ($usr['id'] == $u['id']): ?><span class="badge badge-blue">Anda</span><?php endif; ?>
                  </div>
                </td>
                <td><?= htmlspecialchars($usr['username']) ?></td>
                <td>
                  <?php
                    $badge = match($usr['role']) {
                      'admin' => 'badge-red', 'petugas' => 'badge-green',
                      'guru' => 'badge-amber', default => 'badge-blue'
                    };
                  ?>
                  <span class="badge <?= $badge ?>"><?= roleLabel($usr['role']) ?></span>
                </td>
                <td class="text-muted text-sm"><?= htmlspecialchars($usr['created_at'] ?? '-') ?></td>
                <td>
                  <button class="btn-icon" title="Reset Password" onclick="openReset(<?= $usr['id'] ?>, '<?= htmlspecialchars($usr['nama'], ENT_QUOTES) ?>')">🔑</button>
                  <?php if ($usr['id'] != $u['id']): ?>
                  <button class="btn-icon danger" title="Hapus" onclick="openDelete(<?= $usr['id'] ?>, '<?= htmlspecialchars($usr['nama'], ENT_QUOTES) ?>')">🗑</button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Modal Add -->
<div class="modal-bg" id="addModal">
  <div class="modal">
    <div class="modal-head"><h3>Tambah Pengguna</h3><button class="x" onclick="closeModal('addModal')">&times;</button></div>
    <form method="post">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="field"><label>Nama Lengkap</label><input type="text" name="nama" required minlength="2" maxlength="100"></div>
        <div class="field"><label>Username</label><input type="text" name="username" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9._\-]+" title="Huruf, angka, titik, underscore, strip"></div>
        <div class="field"><label>Password</label><input type="password" name="password" required minlength="6" maxlength="128"></div>
        <div class="field">
          <label>Peran</label>
          <select name="role" required>
            <option value="petugas">Petugas MBG</option>
            <option value="guru">Guru</option>
            <option value="siswa">Siswa</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Reset PW -->
<div class="modal-bg" id="resetModal">
  <div class="modal" style="max-width:380px">
    <div class="modal-head"><h3>Reset Password</h3><button class="x" onclick="closeModal('resetModal')">&times;</button></div>
    <form method="post">
      <input type="hidden" name="action" value="resetpw">
      <input type="hidden" name="id" id="reset_id">
      <div class="modal-body">
        <p class="mb-2">Reset password untuk <strong id="reset_nama"></strong></p>
        <div class="field"><label>Password Baru</label><input type="password" name="new_password" required minlength="6" maxlength="128" placeholder="Minimal 6 karakter"></div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('resetModal')">Batal</button>
        <button type="submit" class="btn btn-primary">Reset</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Delete -->
<div class="modal-bg" id="deleteModal">
  <div class="modal" style="max-width:380px">
    <div class="modal-head"><h3>Hapus Pengguna</h3><button class="x" onclick="closeModal('deleteModal')">&times;</button></div>
    <form method="post">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" id="delete_id">
      <div class="modal-body">
        <p>Yakin hapus pengguna <strong id="delete_nama"></strong>?</p>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('deleteModal')">Batal</button>
        <button type="submit" class="btn btn-danger">Hapus</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
function openReset(id, nama){
  document.getElementById('reset_id').value = id;
  document.getElementById('reset_nama').textContent = nama;
  openModal('resetModal');
}
function openDelete(id, nama){
  document.getElementById('delete_id').value = id;
  document.getElementById('delete_nama').textContent = nama;
  openModal('deleteModal');
}
document.querySelectorAll('.modal-bg').forEach(bg=>{
  bg.addEventListener('click', e=>{ if(e.target===bg) bg.classList.remove('open'); });
});
</script>
</body>
</html>
