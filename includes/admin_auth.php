<?php
/**
 * TechZone - Autentikasi & Proteksi Halaman Admin
 *
 * WAJIB di-include paling atas pada SETIAP file di dalam folder /admin/
 * (kecuali admin/login.php itu sendiri). Ini bukan sekadar menyembunyikan
 * menu admin di navigasi, melainkan benar-benar memblokir akses langsung
 * ke URL /admin/*.php bagi siapa pun yang bukan admin yang sudah login.
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// isAdminLoggedIn() didefinisikan di includes/auth.php agar bisa dipakai
// bersama oleh admin/login.php maupun file ini tanpa duplikasi fungsi.

/**
 * Wajibkan akses admin. Jika bukan admin yang login, tolak akses dan
 * redirect ke halaman login admin.
 */
function requireAdminLogin(): void
{
    if (!isAdminLoggedIn()) {
        setFlashMessage('error', 'Silakan login sebagai admin untuk mengakses halaman ini.');
        redirectTo('admin/login.php');
    }
}

/** Ambil data admin yang sedang login. */
function currentAdmin(): ?array
{
    if (!isAdminLoggedIn()) {
        return null;
    }

    static $admin = null;
    if ($admin !== null) {
        return $admin;
    }

    $pdo  = getDbConnection();
    $stmt = $pdo->prepare("SELECT id, name, email, role, status FROM users WHERE id = :id AND role = 'admin' LIMIT 1");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $admin = $stmt->fetch() ?: null;

    if (!$admin || $admin['status'] !== 'active') {
        session_unset();
        session_destroy();
        redirectTo('admin/login.php');
    }

    return $admin;
}

// Jalankan proteksi otomatis begitu file ini di-include.
requireAdminLogin();