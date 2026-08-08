<?php
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/auth.php';
require __DIR__ . '/config/validate.php';

if (isLoggedIn()) { header('Location: dashboard.php'); exit; }

$error = '';
$errors = [];
$selectedRole = $_POST['role'] ?? 'admin';
$oldUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'admin';
    $oldUsername = $username;
    $selectedRole = $role;

    $errors = v_errors([
        v_username($username),
        v_password($password, 1),
        v_role($role),
    ]);

    if (!$errors) {
        $user = null;
        foreach (db_all('users') as $u) {
            if ($u['username'] === $username && $u['role'] === $role) { $user = $u; break; }
        }

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'];
            db_insert('aktivitas', [
                'waktu' => date('H:i'),
                'pengguna' => $user['nama'],
                'aktivitas' => 'Login',
                'keterangan' => 'Masuk sebagai ' . $role,
            ]);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Username, password, atau peran tidak sesuai.';
        }
    } else {
        $error = implode(' ', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, viewport-fit=cover">
<title>Login — MBG</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
/* Login-only polish (no theme toggle) */
body.login-page {
  min-height: 100vh;
  background:
    radial-gradient(ellipse 80% 60% at 0% 0%, rgba(37,99,235,.12), transparent 50%),
    radial-gradient(ellipse 60% 50% at 100% 100%, rgba(29,78,216,.08), transparent 45%),
    var(--bg);
}
body.login-page .login-wrap {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 28px 16px;
}
body.login-page .login-box {
  display: grid;
  grid-template-columns: 1.05fr 0.95fr;
  max-width: 960px;
  width: 100%;
  border-radius: 20px;
  overflow: hidden;
  background: var(--card);
  border: 1px solid var(--line);
  box-shadow:
    0 4px 6px rgba(15,23,42,.03),
    0 16px 40px rgba(15,23,42,.08);
}
body.login-page .login-form {
  padding: 40px 40px 36px;
  display: flex;
  flex-direction: column;
}
body.login-page .login-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 28px;
}
body.login-page .login-brand .logo {
  width: 48px; height: 48px;
  border-radius: 14px;
  background: linear-gradient(145deg, #3b82f6, #2563eb);
  color: #fff;
  font-weight: 800;
  font-size: 15px;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 14px rgba(37,99,235,.35);
}
body.login-page .login-brand strong {
  display: block;
  font-size: 18px;
  font-weight: 800;
  letter-spacing: -.3px;
}
body.login-page .login-brand span {
  font-size: 11px;
  color: var(--ink-3);
  font-weight: 600;
  letter-spacing: .2px;
}
body.login-page .login-form h1 {
  font-size: 24px;
  font-weight: 800;
  letter-spacing: -.4px;
  margin-bottom: 6px;
  color: var(--ink);
}
body.login-page .login-sub {
  font-size: 13.5px;
  color: var(--ink-3);
  margin-bottom: 24px;
  font-weight: 500;
}
body.login-page .login-error {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
  padding: 12px 14px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 18px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
}
body.login-page .field label {
  font-size: 12.5px;
  font-weight: 700;
  color: var(--ink-2);
  margin-bottom: 6px;
}
body.login-page .field input {
  padding: 12px 14px;
  border-radius: 12px;
  border: 1.5px solid var(--line);
  font-size: 14px;
  transition: border-color .2s, box-shadow .2s;
}
body.login-page .field input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 4px rgba(37,99,235,.12);
}
body.login-page .field-pass .toggle {
  font-size: 13px;
  font-weight: 600;
  color: var(--ink-3);
  user-select: none;
}
body.login-page .field-pass .toggle:hover { color: #2563eb; }
body.login-page .btn-login {
  margin-top: 8px;
  padding: 13px 18px;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 700;
  background: linear-gradient(180deg, #3b82f6, #2563eb);
  box-shadow: 0 4px 14px rgba(37,99,235,.3);
  border: none;
  color: #fff;
  cursor: pointer;
  width: 100%;
  transition: transform .15s, box-shadow .2s, filter .15s;
}
body.login-page .btn-login:hover {
  filter: brightness(1.05);
  box-shadow: 0 6px 20px rgba(37,99,235,.4);
  transform: translateY(-1px);
}
body.login-page .btn-login:active { transform: translateY(0); }
body.login-page .login-divider {
  margin: 26px 0 16px;
  font-size: 11px;
  letter-spacing: .4px;
}
body.login-page .role-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
}
body.login-page .role-opt {
  border: 1.5px solid var(--line);
  border-radius: 14px;
  padding: 14px 8px 12px;
  text-align: center;
  cursor: pointer;
  background: var(--bg);
  transition: border-color .2s, background .2s, transform .15s, box-shadow .2s;
}
body.login-page .role-opt:hover {
  border-color: #93c5fd;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(37,99,235,.1);
}
body.login-page .role-opt.active {
  border-color: #2563eb;
  background: #eff6ff;
  box-shadow: 0 0 0 3px rgba(37,99,235,.12);
}
body.login-page .role-opt .ic {
  width: 40px; height: 40px;
  border-radius: 12px;
  background: #dbeafe;
  color: #2563eb;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 8px;
  font-size: 18px;
  transition: background .2s, color .2s;
}
body.login-page .role-opt.active .ic {
  background: #2563eb;
  color: #fff;
}
body.login-page .role-opt span {
  font-size: 12px;
  font-weight: 700;
  color: var(--ink-2);
}
body.login-page .login-hint {
  margin-top: auto;
  padding-top: 22px;
  font-size: 11.5px;
  color: var(--ink-3);
  font-weight: 500;
  line-height: 1.55;
  border-top: 1px solid var(--line);
}
body.login-page .login-hint code {
  background: #eff6ff;
  color: #1d4ed8;
  padding: 1px 6px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
}
body.login-page .login-hero {
  background: linear-gradient(155deg, #2563eb 0%, #1d4ed8 45%, #1e3a8a 100%);
  color: #fff;
  padding: 44px 40px;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  position: relative;
  overflow: hidden;
  min-height: 560px;
}
body.login-page .login-hero::before {
  content: '';
  position: absolute;
  width: 280px; height: 280px;
  border-radius: 50%;
  background: rgba(255,255,255,.08);
  top: -60px; right: -60px;
}
body.login-page .login-hero::after {
  content: '';
  position: absolute;
  width: 180px; height: 180px;
  border-radius: 50%;
  background: rgba(255,255,255,.06);
  bottom: 80px; left: -40px;
}
body.login-page .hero-badge {
  position: relative;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(255,255,255,.15);
  border: 1px solid rgba(255,255,255,.2);
  padding: 6px 12px;
  border-radius: 99px;
  font-size: 12px;
  font-weight: 600;
  width: fit-content;
  margin-bottom: 18px;
  backdrop-filter: blur(8px);
}
body.login-page .login-hero h2 {
  position: relative;
  z-index: 1;
  font-size: 30px;
  font-weight: 800;
  line-height: 1.2;
  letter-spacing: -.5px;
  margin-bottom: 12px;
}
body.login-page .login-hero p {
  position: relative;
  z-index: 1;
  font-size: 14.5px;
  color: rgba(255,255,255,.78);
  line-height: 1.65;
  max-width: 280px;
  margin-bottom: 28px;
}
body.login-page .hero-stats {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}
body.login-page .hero-stat {
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.15);
  border-radius: 14px;
  padding: 14px;
  backdrop-filter: blur(8px);
}
body.login-page .hero-stat strong {
  display: block;
  font-size: 20px;
  font-weight: 800;
  margin-bottom: 2px;
}
body.login-page .hero-stat span {
  font-size: 11.5px;
  color: rgba(255,255,255,.7);
  font-weight: 500;
}
@media (max-width: 800px) {
  body.login-page .login-box {
    grid-template-columns: 1fr;
    max-width: 440px;
  }
  body.login-page .login-hero {
    min-height: 220px;
    padding: 28px 24px;
    order: -1;
  }
  body.login-page .login-hero h2 { font-size: 22px; }
  body.login-page .hero-stats { display: none; }
  body.login-page .login-form { padding: 28px 24px; }
  body.login-page .role-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>
<body class="login-page">
<div class="page-loader" id="pageLoader">
  <div class="loader-spinner"></div>
  <div class="loader-text">Memuat...</div>
</div>
<div class="top-progress" id="topProgress"></div>
<div class="login-wrap">
  <div class="login-box">
    <div class="login-form">
      <div class="login-brand">
        <img src="assets/img/logo-bgn.jpg" alt="Badan Gizi Nasional" class="logo-img">
        <div>
          <strong>MBG Panel</strong>
          <span>BADAN GIZI NASIONAL</span>
        </div>
      </div>

      <h1>Selamat datang kembali</h1>
      <p class="login-sub">Masuk untuk mengelola distribusi MBG sekolah Anda.</p>

      <?php if ($error): ?>
        <div class="login-error" role="alert">
          <span>⚠️</span>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="post" id="loginForm" novalidate>
        <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($selectedRole) ?>">

        <div class="field">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" placeholder="Masukkan username"
                 value="<?= htmlspecialchars($oldUsername) ?>" autocomplete="username" required>
          <span class="field-error" id="err-username"></span>
        </div>

        <div class="field">
          <label for="passField">Password</label>
          <div class="field-pass">
            <input type="password" id="passField" name="password" placeholder="Masukkan password"
                   autocomplete="current-password" required>
            <span class="toggle" onclick="togglePass()" title="Tampilkan/sembunyikan">lihat</span>
          </div>
          <span class="field-error" id="err-password"></span>
        </div>

        <button type="submit" class="btn-login">Masuk ke Dashboard →</button>
      </form>

      <div class="login-divider">Pilih peran akun</div>

      <div class="role-grid">
        <div class="role-opt <?= $selectedRole==='admin'?'active':'' ?>" data-role="admin">
          <div class="ic">👤</div><span>Admin</span>
        </div>
        <div class="role-opt <?= $selectedRole==='petugas'?'active':'' ?>" data-role="petugas">
          <div class="ic">👷</div><span>Petugas</span>
        </div>
        <div class="role-opt <?= $selectedRole==='guru'?'active':'' ?>" data-role="guru">
          <div class="ic">🎓</div><span>Guru</span>
        </div>
        <div class="role-opt <?= $selectedRole==='siswa'?'active':'' ?>" data-role="siswa">
          <div class="ic">🎒</div><span>Siswa</span>
        </div>
      </div>

      <div class="login-hint">
        Akun demo:
        <code>admin</code>/<code>admin123</code> ·
        <code>petugas</code>/<code>petugas123</code> ·
        <code>guru</code>/<code>guru123</code> ·
        <code>siswa</code>/<code>siswa123</code>
      </div>
    </div>

    <div class="login-hero">
      <div class="hero-badge">🏫 Sistem Distribusi MBG</div>
      <h2>Makan Bergizi,<br>Prestasi Tinggi</h2>
      <p>Pantau stok, pengambilan, dan laporan MBG secara real-time untuk seluruh siswa.</p>
      <div class="hero-stats">
        <div class="hero-stat">
          <strong>4</strong>
          <span>Peran pengguna</span>
        </div>
        <div class="hero-stat">
          <strong>Live</strong>
          <span>Data real-time</span>
        </div>
        <div class="hero-stat">
          <strong>CSV</strong>
          <span>Import & laporan</span>
        </div>
        <div class="hero-stat">
          <strong>QR</strong>
          <span>Scan pengambilan</span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.role-opt').forEach(el=>{
  el.addEventListener('click', ()=>{
    document.querySelectorAll('.role-opt').forEach(o=>o.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('roleInput').value = el.dataset.role;
  });
});
function togglePass(){
  const f = document.getElementById('passField');
  f.type = f.type === 'password' ? 'text' : 'password';
}
document.getElementById('loginForm').addEventListener('submit', function(e){
  let ok = true;
  const u = document.getElementById('username');
  const p = document.getElementById('passField');
  const eu = document.getElementById('err-username');
  const ep = document.getElementById('err-password');
  eu.textContent = ''; ep.textContent = '';
  u.classList.remove('input-error'); p.classList.remove('input-error');

  if (!u.value.trim()) { eu.textContent = 'Username wajib diisi.'; u.classList.add('input-error'); ok = false; }
  else if (u.value.trim().length < 3) { eu.textContent = 'Username minimal 3 karakter.'; u.classList.add('input-error'); ok = false; }
  else if (!/^[a-zA-Z0-9._-]+$/.test(u.value.trim())) { eu.textContent = 'Format username tidak valid.'; u.classList.add('input-error'); ok = false; }

  if (!p.value) { ep.textContent = 'Password wajib diisi.'; p.classList.add('input-error'); ok = false; }

  if (!ok) e.preventDefault();
});
// Force light theme on login — no theme toggle
document.documentElement.setAttribute('data-theme', 'light');
</script>

<script>
(function(){
  var loader = document.getElementById('pageLoader');
  var bar = document.getElementById('topProgress');
  function hideLoader(){
    if (loader) loader.classList.add('hide');
    if (bar) { bar.classList.add('done'); bar.style.width='100%'; setTimeout(function(){ bar.style.display='none'; }, 500); }
  }
  if (bar) { bar.style.width='30%'; setTimeout(function(){ bar.style.width='65%'; }, 100); }
  window.addEventListener('load', function(){ setTimeout(hideLoader, 200); });
  setTimeout(hideLoader, 3000);
  document.getElementById('loginForm').addEventListener('submit', function(e){
    if (e.defaultPrevented) return;
    var btn = this.querySelector('.btn-login');
    if (btn) btn.classList.add('loading');
    if (loader) {
      loader.classList.remove('hide');
      loader.querySelector('.loader-text').textContent = 'Masuk...';
    }
  });
})();
</script>

</body>
</html>
