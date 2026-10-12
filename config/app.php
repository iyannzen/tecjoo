<?php
/**
 * TechZone - Konfigurasi Umum Aplikasi
 */

// Mencegah file diakses langsung tanpa proses bootstrap.
if (!defined('TECHZONE_APP')) {
    define('TECHZONE_APP', true);
}

// ---------------------------------------------------------------------
// Base URL & Base Path
// Dibuat dinamis (relative terhadap request) agar project dapat
// dipindahkan dari XAMPP (http://localhost/techzone/) ke hosting
// (https://domainanda.com/) tanpa mengubah konfigurasi ini.
// ---------------------------------------------------------------------
function techzoneBaseUrl(): string
{
    static $baseUrl = null;

    if ($baseUrl !== null) {
        return $baseUrl;
    }

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $scheme = $isSecure ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Direktori root project dihitung dari lokasi file config/app.php ini,
    // relatif terhadap document root server.
    $scriptDir = str_replace('\\', '/', dirname(dirname(__FILE__)));
    $docRoot   = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/'));

    $basePath = '';
    if ($docRoot !== '' && strpos($scriptDir, $docRoot) === 0) {
        $basePath = substr($scriptDir, strlen($docRoot));
    }
    $basePath = '/' . trim($basePath, '/');
    if ($basePath === '/') {
        $basePath = '';
    }

    $baseUrl = $scheme . '://' . $host . $basePath;
    return $baseUrl;
}

define('TECHZONE_ENV', getenv('APP_ENV') ?: 'local'); // local | production

// Zona waktu default toko.
date_default_timezone_set('Asia/Jakarta');

// ---------------------------------------------------------------------
// Session
// Konfigurasi cookie session dibuat lebih aman sebelum session_start().
// Harus dipanggil sebelum output apapun.
// ---------------------------------------------------------------------
function techzoneStartSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    // ---------------------------------------------------------------------
    // Area ADMIN dan area USER memakai NAMA COOKIE SESSION YANG BERBEDA,
    // supaya keduanya bisa login BERSAMAAN di browser yang sama (mis. beda
    // tab) tanpa saling menimpa. Cookie berbeda nama = disimpan terpisah
    // oleh browser, dikirim otomatis sesuai halaman yang diakses.
    //
    // Terdeteksi sebagai area admin jika:
    //   1. Script yang diakses berada di dalam folder /admin/, ATAU
    //   2. Diminta eksplisit lewat query string ?scope=admin
    //      (dipakai oleh logout.php agar tahu sesi mana yang harus diakhiri
    //      saat tombol "Keluar" diklik dari dalam panel admin).
    // ---------------------------------------------------------------------
    $requestUri  = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName  = $_SERVER['SCRIPT_NAME'] ?? '';
    $isAdminArea = (strpos($scriptName, '/admin/') !== false)
        || (strpos($requestUri, '/admin/') !== false)
        || (($_GET['scope'] ?? '') === 'admin');

    session_name($isAdminArea ? 'techzone_admin_session' : 'techzone_session');
    session_start();
}

// ---------------------------------------------------------------------
// Error reporting
// Development: tampilkan error di layar.
// Production: sembunyikan error dari user, catat ke log saja.
// ---------------------------------------------------------------------
if (TECHZONE_ENV === 'production') {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__) . '/storage/logs/php_error.log');