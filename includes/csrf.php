<?php
/**
 * TechZone - Proteksi CSRF (Cross-Site Request Forgery)
 *
 * Digunakan pada semua form penting: login, register, checkout,
 * tambah/edit/hapus produk, update profil, ganti password,
 * konfirmasi pembayaran, update status order, dsb.
 */

require_once __DIR__ . '/functions.php';

/**
 * Menghasilkan (atau mengambil yang sudah ada) CSRF token untuk session ini.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Mencetak hidden input CSRF token, siap dipakai di dalam <form>.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/**
 * Memvalidasi CSRF token yang dikirim dari form.
 * Menghentikan eksekusi (403) jika token tidak valid.
 */
function verifyCsrfToken(?string $token): void
{
    if (empty($_SESSION['csrf_token']) || empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        error_log('[TechZone][CSRF] Token tidak valid dari IP ' . ($_SERVER['REMOTE_ADDR'] ?? '-'));
        die('Permintaan ditolak: token keamanan (CSRF) tidak valid atau kedaluwarsa. Silakan muat ulang halaman dan coba lagi.');
    }
}
