<?php
/**
 * TechZone - Penyimpanan permanen file upload (foto produk, banner, kategori, logo bank/e-wallet, bukti transfer).
 *
 * MASALAH: di Railway, disk server bersifat sementara. Setiap deploy ulang / restart, semua file di
 * folder uploads/ yang tidak ada di Git ikut hilang, padahal alamat gambarnya tetap tersimpan di database
 * -> foto "hilang".
 *
 * SOLUSI: setiap file yang diunggah disalin juga ke tabel `stored_files` (database = permanen).
 * Saat server baru menyala, tzFileStoreSync() mengembalikan semua file dari database ke folder uploads/
 * dan sekaligus mengamankan file lama yang sudah ada di disk. Alamat gambar tidak berubah sama sekali.
 */

/** Folder di dalam uploads/ yang diamankan. */
function tzFileStoreFolders(): array
{
    return ['products', 'categories', 'banners', 'banks', 'ewallets', 'payments'];
}

function tzFileStoreEnsureTable(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS stored_files (
            path       VARCHAR(255) NOT NULL PRIMARY KEY,
            mime_type  VARCHAR(100) DEFAULT NULL,
            size       INT UNSIGNED NOT NULL DEFAULT 0,
            data       LONGBLOB NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

/** Path relatif yang valid (uploads/<folder>/<namafile>), tanpa traversal. */
function tzFileStoreValidPath(string $rel): bool
{
    $rel = ltrim(str_replace('\\', '/', $rel), '/');
    if (strpos($rel, '..') !== false || strncmp($rel, 'uploads/', 8) !== 0) {
        return false;
    }
    $parts = explode('/', $rel);
    return count($parts) === 3 && in_array($parts[1], tzFileStoreFolders(), true) && preg_match('/^[A-Za-z0-9._-]+$/', $parts[2]) === 1;
}

/** Salin file di disk ke database. @return bool berhasil */
function tzFileStoreSave(string $relPath, ?string $absPath = null): bool
{
    $relPath = ltrim(str_replace('\\', '/', $relPath), '/');
    if (!tzFileStoreValidPath($relPath)) {
        return false;
    }
    $absPath = $absPath ?? (__DIR__ . '/../' . $relPath);
    try {
        $bytes = @file_get_contents($absPath);
        if ($bytes === false) {
            return false;
        }
        $mime = null;
        if (function_exists('finfo_open') && ($fi = finfo_open(FILEINFO_MIME_TYPE))) {
            $mime = finfo_file($fi, $absPath) ?: null;
            finfo_close($fi);
        }
        $pdo = getDbConnection();
        tzFileStoreEnsureTable($pdo);
        $pdo->prepare(
            'INSERT INTO stored_files (path, mime_type, size, data) VALUES (:p, :m, :s, :d)
             ON DUPLICATE KEY UPDATE mime_type = VALUES(mime_type), size = VALUES(size), data = VALUES(data)'
        )->execute([':p' => $relPath, ':m' => $mime, ':s' => strlen($bytes), ':d' => $bytes]);
        return true;
    } catch (Throwable $e) {
        error_log('[TechZone][FileStore Save] ' . $relPath . ': ' . $e->getMessage());
        return false;
    }
}

/** Hapus file dari database (dipanggil bersama unlink saat admin menghapus gambar). */
function tzFileStoreDelete(string $relPath): void
{
    $relPath = ltrim(str_replace('\\', '/', $relPath), '/');
    if (!tzFileStoreValidPath($relPath)) {
        return;
    }
    try {
        getDbConnection()->prepare('DELETE FROM stored_files WHERE path = :p')->execute([':p' => $relPath]);
    } catch (Throwable $e) {
        error_log('[TechZone][FileStore Delete] ' . $e->getMessage());
    }
}

/** Hapus file di disk + database sekaligus. $relPath contoh: uploads/products/img_xxx.jpg */
function tzDeleteUploadedFile(string $relPath): void
{
    $relPath = ltrim(str_replace('\\', '/', $relPath), '/');
    if (!tzFileStoreValidPath($relPath)) {
        return;
    }
    @unlink(__DIR__ . '/../' . $relPath);
    tzFileStoreDelete($relPath);
}

/**
 * Sinkronisasi sekali per "umur server" (penanda disimpan di folder temp yang ikut hilang saat deploy ulang):
 *   1. file ada di database tetapi hilang di disk  -> dikembalikan ke disk
 *   2. file ada di disk tetapi belum di database   -> disimpan ke database (mengamankan file lama)
 */
function tzFileStoreSync(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }
    $marker = rtrim(sys_get_temp_dir(), '/\\') . '/tz_filestore_' . md5(__DIR__) . '.done';
    if (is_file($marker)) {
        return;
    }
    $lock = @fopen($marker . '.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        return; // proses lain sedang menyinkronkan
    }

    try {
        @set_time_limit(120);
        $pdo = getDbConnection();
        tzFileStoreEnsureTable($pdo);
        $root = realpath(__DIR__ . '/..') . '/';

        // 1. database -> disk
        $paths = $pdo->query('SELECT path FROM stored_files')->fetchAll(PDO::FETCH_COLUMN);
        $have  = [];
        $get   = $pdo->prepare('SELECT data FROM stored_files WHERE path = :p');
        foreach ($paths as $rel) {
            $have[$rel] = true;
            if (!tzFileStoreValidPath($rel)) {
                continue;
            }
            $abs = $root . $rel;
            if (is_file($abs)) {
                continue;
            }
            $get->execute([':p' => $rel]);
            $data = $get->fetchColumn();
            if ($data !== false) {
                if (!is_dir(dirname($abs))) {
                    @mkdir(dirname($abs), 0755, true);
                }
                @file_put_contents($abs, $data);
            }
        }

        // 2. disk -> database
        foreach (tzFileStoreFolders() as $folder) {
            $dir = $root . 'uploads/' . $folder . '/';
            if (!is_dir($dir)) {
                continue;
            }
            foreach (scandir($dir) ?: [] as $name) {
                $rel = 'uploads/' . $folder . '/' . $name;
                if ($name[0] === '.' || isset($have[$rel]) || !tzFileStoreValidPath($rel)) {
                    continue;
                }
                tzFileStoreSave($rel, $dir . $name);
            }
        }

        @file_put_contents($marker, (string) time());
    } catch (Throwable $e) {
        error_log('[TechZone][FileStore Sync] ' . $e->getMessage());
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
