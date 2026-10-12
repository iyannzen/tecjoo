<?php
/**
 * TechZone - Seeder Akun Admin Development
 *
 * Jalankan SEKALI setelah schema.sql & seed.sql berhasil di-import,
 * baik lewat browser: http://localhost/techzone/database/seed_admin.php
 * atau lewat CLI: php database/seed_admin.php
 *
 * Script ini idempotent: aman dijalankan berkali-kali, tidak akan membuat
 * duplikat admin. Password di-hash menggunakan password_hash() bawaan PHP,
 * BUKAN hash yang ditulis manual di file .sql.
 *
 * KEAMANAN: setelah admin berhasil dibuat dan Anda sudah bisa login,
 * SANGAT DISARANKAN untuk menghapus atau memindahkan file ini keluar dari
 * folder yang bisa diakses publik, atau menambahkan proteksi tambahan,
 * agar tidak disalahgunakan di lingkungan production.
 */

require_once __DIR__ . '/../includes/functions.php';

const ADMIN_EMAIL    = 'iyann.zen@gmail.com';
const ADMIN_PASSWORD = 'techzone';
const ADMIN_NAME     = 'Administrator TechZone';
const ADMIN_PHONE    = '0881025277204';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = getDbConnection();

    $check = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $check->execute([':email' => ADMIN_EMAIL]);
    $existing = $check->fetch();

    $hashedPassword = password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT);

    if ($existing) {
        $update = $pdo->prepare("UPDATE users SET password = :password, role = 'admin', status = 'active', updated_at = NOW() WHERE id = :id");
        $update->execute([':password' => $hashedPassword, ':id' => $existing['id']]);
        echo "Akun admin sudah ada. Password telah disinkronkan ulang ke: " . ADMIN_PASSWORD . "\n";
    } else {
        $insert = $pdo->prepare(
            "INSERT INTO users (name, email, phone, password, role, status, created_at)
             VALUES (:name, :email, :phone, :password, 'admin', 'active', NOW())"
        );
        $insert->execute([
            ':name'     => ADMIN_NAME,
            ':email'    => ADMIN_EMAIL,
            ':phone'    => ADMIN_PHONE,
            ':password' => $hashedPassword,
        ]);
        echo "Akun admin berhasil dibuat.\n";
    }

    echo "-----------------------------------------\n";
    echo "Email    : " . ADMIN_EMAIL . "\n";
    echo "Password : " . ADMIN_PASSWORD . "\n";
    echo "-----------------------------------------\n";
    echo "PENTING: Ganti password ini sebelum website digunakan production.\n";
    echo "Silakan login melalui: /techzone/admin/login.php\n";
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[TechZone][Seed Admin Error] ' . $e->getMessage());
    echo "Gagal membuat akun admin. Pastikan schema.sql sudah di-import terlebih dahulu.\n";
    echo "Detail error dicatat di storage/logs/php_error.log\n";
}
