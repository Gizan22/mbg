<?php
// config/auth.php — session helpers & role-based protection
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require one of the allowed roles.
 * Example: requireRole(['admin','petugas']);
 */
function requireRole(array $allowed) {
    requireLogin();
    $role = $_SESSION['user_role'] ?? '';
    if (!in_array($role, $allowed, true)) {
        // Redirect to a safe page for their role
        $fallback = roleHome($role);
        header('Location: ' . $fallback . '?denied=1');
        exit;
    }
}

function currentUser() {
    return [
        'id'   => $_SESSION['user_id']   ?? null,
        'nama' => $_SESSION['user_nama'] ?? '',
        'role' => $_SESSION['user_role'] ?? '',
    ];
}

function roleLabel($role) {
    $map = [
        'admin'   => 'Admin',
        'petugas' => 'Petugas MBG',
        'guru'    => 'Guru',
        'siswa'   => 'Siswa',
    ];
    return $map[$role] ?? ucfirst($role);
}

/** Default landing page per role */
function roleHome($role) {
    return match ($role) {
        'siswa'   => 'dashboard.php',
        'guru'    => 'dashboard.php',
        'petugas' => 'dashboard.php',
        default   => 'dashboard.php',
    };
}

/** Check if current user has any of the roles */
function hasRole(array $roles) {
    $role = $_SESSION['user_role'] ?? '';
    return in_array($role, $roles, true);
}
