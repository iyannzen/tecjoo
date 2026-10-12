<?php
// Mengambil nilai variabel dari tab Variables di Railway
$host = getenv('DB_HOST') ?: $_ENV['DB_HOST'];
$port = getenv('DB_PORT') ?: $_ENV['DB_PORT'];
$db   = getenv('DB_NAME') ?: $_ENV['DB_NAME'];
$user = getenv('DB_USER') ?: $_ENV['DB_USER'];
$pass = getenv('DB_PASS') ?: $_ENV['DB_PASS'];

try {
    // Membuat string koneksi (DSN)
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    
    // Inisialisasi PDO
    $pdo = new PDO($dsn, $user, $pass);
    
    // Set error mode ke exception agar error mudah dilacak
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Koneksi ke database MySQL Railway Berhasil!";
} catch (\PDOException $e) {
    // Menangkap error jika koneksi gagal (misal salah password/host)
    echo "Koneksi Gagal: " . $e->getMessage();
} catch (\Error $e) {
    // Menangkap fatal error (misal ekstensi PDO belum aktif di server)
    echo "Fatal Error Server: " . $e->getMessage();
}
?>