<?php
/**
 * config/validate.php — helper validasi input formulir
 */

function v_str(string $value, int $min = 1, int $max = 255): ?string {
    $value = trim($value);
    $len = mb_strlen($value);
    if ($len < $min) return "Minimal {$min} karakter.";
    if ($len > $max) return "Maksimal {$max} karakter.";
    return null;
}

function v_required(string $value, string $label = 'Field'): ?string {
    if (trim($value) === '') return "{$label} wajib diisi.";
    return null;
}

function v_username(string $value): ?string {
    $value = trim($value);
    if ($value === '') return 'Username wajib diisi.';
    if (mb_strlen($value) < 3) return 'Username minimal 3 karakter.';
    if (mb_strlen($value) > 50) return 'Username maksimal 50 karakter.';
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $value)) {
        return 'Username hanya boleh huruf, angka, titik, underscore, atau strip.';
    }
    return null;
}

function v_password(string $value, int $min = 6): ?string {
    if ($value === '') return 'Password wajib diisi.';
    if (mb_strlen($value) < $min) return "Password minimal {$min} karakter.";
    if (mb_strlen($value) > 128) return 'Password terlalu panjang.';
    return null;
}

function v_nis(string $value): ?string {
    $value = trim($value);
    if ($value === '') return 'NIS wajib diisi.';
    if (!preg_match('/^[0-9]{5,20}$/', $value)) {
        return 'NIS harus berupa angka 5–20 digit.';
    }
    return null;
}

function v_nama(string $value): ?string {
    $value = trim($value);
    if ($value === '') return 'Nama wajib diisi.';
    if (mb_strlen($value) < 2) return 'Nama minimal 2 karakter.';
    if (mb_strlen($value) > 100) return 'Nama maksimal 100 karakter.';
    if (!preg_match('/^[\p{L}\s.\'-]+$/u', $value)) {
        return 'Nama hanya boleh huruf, spasi, titik, apostrof, atau strip.';
    }
    return null;
}

function v_kelas(string $value): ?string {
    $value = trim($value);
    if ($value === '') return 'Kelas wajib diisi.';
    if (mb_strlen($value) > 30) return 'Kelas maksimal 30 karakter.';
    return null;
}

function v_role(string $value): ?string {
    $allowed = ['admin', 'petugas', 'guru', 'siswa'];
    if (!in_array($value, $allowed, true)) return 'Peran tidak valid.';
    return null;
}

function v_int_range($value, int $min = 0, int $max = 999999, string $label = 'Nilai'): ?string {
    if ($value === '' || $value === null) return "{$label} wajib diisi.";
    if (!is_numeric($value) || (int)$value != $value) return "{$label} harus berupa angka bulat.";
    $n = (int)$value;
    if ($n < $min || $n > $max) return "{$label} harus antara {$min}–{$max}.";
    return null;
}

function v_date(string $value, string $label = 'Tanggal'): ?string {
    $value = trim($value);
    if ($value === '') return "{$label} wajib diisi.";
    $d = DateTime::createFromFormat('Y-m-d', $value);
    if (!$d || $d->format('Y-m-d') !== $value) return "{$label} tidak valid (format: YYYY-MM-DD).";
    return null;
}

function v_time(string $value, string $label = 'Jam'): ?string {
    $value = trim($value);
    if ($value === '') return "{$label} wajib diisi.";
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $value)) {
        return "{$label} tidak valid (format: HH:MM).";
    }
    return null;
}

function v_tipe_notif(string $value): ?string {
    if (!in_array($value, ['sukses', 'warning', 'bahaya'], true)) {
        return 'Tipe notifikasi tidak valid.';
    }
    return null;
}

/**
 * Kumpulkan error; return array of error messages (kosong = valid).
 * @param array<string,?string> $checks  hasil tiap v_* (null = ok)
 */

function v_jurusan(string $value): ?string {
    $value = strtoupper(trim($value));
    $allowed = ['AKL', 'MPLB', 'RPL'];
    if ($value === '') return 'Jurusan wajib diisi.';
    if (!in_array($value, $allowed, true)) {
        return 'Jurusan harus AKL, MPLB, atau RPL.';
    }
    return null;
}

function v_errors(array $checks): array {
    return array_values(array_filter($checks, fn($e) => $e !== null));
}

/** Redirect dengan pesan error (disimpan di session flash) */
function v_fail_redirect(string $url, array $errors, array $old = []): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash_errors'] = $errors;
    $_SESSION['flash_old'] = $old;
    header('Location: ' . $url);
    exit;
}

function v_flash_errors(): array {
    $e = $_SESSION['flash_errors'] ?? [];
    unset($_SESSION['flash_errors']);
    return is_array($e) ? $e : [];
}

function v_flash_old(): array {
    $o = $_SESSION['flash_old'] ?? [];
    unset($_SESSION['flash_old']);
    return is_array($o) ? $o : [];
}

/** Escape untuk value attribute */
function old(string $key, string $default = ''): string {
    static $old = null;
    if ($old === null) $old = v_flash_old();
    return htmlspecialchars($old[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}
