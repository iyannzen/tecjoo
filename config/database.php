<?php
/**
 * TechZone - Konfigurasi Koneksi Database (PDO)
 *
 * Untuk development di XAMPP, nilai default berikut biasanya sudah benar.
 * Untuk hosting production, JANGAN hardcode di sini secara langsung.
 * Disarankan menimpa nilai berikut lewat environment variable pada server
 * hosting (DB_HOST, DB_NAME, DB_USER, DB_PASS), sehingga project ini bisa
 * dipindahkan dari localhost ke hosting tanpa mengubah kode.
 */

// Ambil dari environment variable jika tersedia (untuk hosting),
// jika tidak tersedia gunakan default XAMPP lokal.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'techzone_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Mengembalikan instance PDO singleton.
 * Menggunakan prepared statement secara default (emulasi dimatikan)
 * agar benar-benar memakai prepared statement native MySQL.
 *
 * @return PDO
 */
function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Jangan tampilkan detail koneksi/database ke pengguna.
        error_log('[TechZone][DB Connection Error] ' . $e->getMessage());

        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        echo 'Terjadi kesalahan pada server. Silakan coba beberapa saat lagi.';
        exit;
    }

    return $pdo;
}
