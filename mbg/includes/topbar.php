<?php
$user = currentUser();
$parts = explode(' ', $user['nama'] ?? 'U');
$initials = '';
foreach ($parts as $w) { $initials .= strtoupper(mb_substr($w, 0, 1)); }
$initials = mb_substr($initials, 0, 2);
?>
<header class="topbar">
  <div class="topbar-left">
    <button type="button" class="sidebar-toggle" id="menuBtn" onclick="toggleMenu()" aria-label="Menu">
      <span id="menuBtnIcon">☰</span>
    </button>
    <h1><?= htmlspecialchars($pageTitle ?? 'MBG') ?></h1>
  </div>
  <div class="topbar-right">
    <button type="button" class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Ganti tema">🌙</button>
    <div class="user">
      <div class="avatar"><?= htmlspecialchars($initials) ?></div>
      <div>
        <div style="font-weight:700;font-size:13px"><?= htmlspecialchars($user['nama'] ?? '') ?></div>
        <div style="font-size:11px;color:var(--ink-3)"><?= roleLabel($user['role'] ?? '') ?></div>
      </div>
    </div>
  </div>
</header>
<script>
(function(){
  var saved = localStorage.getItem('mbg-theme') || 'light';
  document.documentElement.setAttribute('data-theme', saved);
  var btn = document.getElementById('themeToggle');
  if (btn) btn.textContent = saved === 'dark' ? '☀️' : '🌙';

  // Restore menu open state across pages
  if (sessionStorage.getItem('mbg-menu-open') === '1') {
    openMenu(true);
  }
})();

function toggleTheme(){
  var html = document.documentElement;
  var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  html.setAttribute('data-theme', next);
  localStorage.setItem('mbg-theme', next);
  document.getElementById('themeToggle').textContent = next === 'dark' ? '☀️' : '🌙';
}

function openMenu(silent){
  var sb = document.getElementById('sidebar');
  var ov = document.getElementById('sidebarOverlay');
  var icon = document.getElementById('menuBtnIcon');
  if (!sb) return;
  sb.classList.add('open');
  if (ov) ov.classList.add('show');
  document.body.classList.add('menu-open');
  sessionStorage.setItem('mbg-menu-open', '1');
  if (icon) icon.textContent = '✕';
}

function closeMenu(){
  var sb = document.getElementById('sidebar');
  var ov = document.getElementById('sidebarOverlay');
  var icon = document.getElementById('menuBtnIcon');
  if (!sb) return;
  sb.classList.remove('open');
  if (ov) ov.classList.remove('show');
  document.body.classList.remove('menu-open');
  sessionStorage.setItem('mbg-menu-open', '0');
  if (icon) icon.textContent = '☰';
}

function toggleMenu(){
  var sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open')) closeMenu();
  else openMenu();
}

// backward compat
function toggleSidebar(){ toggleMenu(); }

document.addEventListener('keydown', function(e){
  if (e.key === 'Escape') closeMenu();
});
</script>

<?php include __DIR__ . '/loader.php'; ?>
