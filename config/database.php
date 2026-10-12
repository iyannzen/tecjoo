<?php
function getDbConnection() {
    $host = getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?: 'mysql.railway.internal';
    $port = getenv('DB_PORT') ?: $_ENV['DB_PORT'] ?: '3306';
    $db   = getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?: 'railway';
    $user = getenv('DB_USER') ?: $_ENV['DB_USER'] ?: 'root';
    $pass = getenv('DB_PASS') ?: $_ENV['DB_PASS'] ?: ''; 

    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Samakan zona waktu sesi MySQL dengan PHP (Asia/Jakarta = +07:00).
        // Tanpa ini, NOW() di server Railway memakai UTC sehingga jam pesanan
        // tersimpan 7 jam lebih lambat dan urutan/tanggalnya tidak selaras.
        $pdo->exec("SET time_zone = '+07:00'");
        
        return $pdo; 
    } catch (\PDOException $e) {
        die("Koneksi Gagal: " . $e->getMessage());
    }
}
?>