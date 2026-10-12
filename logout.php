<?php
/**
 * TechZone - Logout
 * Menangani logout untuk user biasa maupun admin (dibedakan tujuan redirect-nya).
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$wasAdmin = ($_SESSION['role'] ?? '') === 'admin';
$userId   = $_SESSION['user_id'] ?? null;

logActivity($userId, 'logout', $wasAdmin ? 'Admin logout.' : 'User logout.');

logoutCurrentSession();

// Mulai session baru hanya untuk menampung flash message di halaman berikutnya.
techzoneStartSession();
setFlashMessage('success', 'Anda telah berhasil keluar.');

redirectTo($wasAdmin ? 'admin/login.php' : 'login.php');
