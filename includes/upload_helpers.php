<?php
/**
 * TechZone - Helper Upload Gambar (Admin)
 * Validasi ketat: ekstensi & MIME whitelist, ukuran maksimal, nama file
 * di-generate ulang. Dipakai untuk upload gambar produk, kategori, banner.
 */
require_once __DIR__ . '/functions.php';

function getAllowedImageExtensions(): array
{
    return ['jpg', 'jpeg', 'png', 'webp'];
}

function getAllowedImageMimeTypes(): array
{
    return ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
}

/**
 * Memvalidasi & memindahkan file gambar upload ke folder tujuan.
 * Mengembalikan nama file baru jika sukses, atau melempar RuntimeException jika gagal.
 */
function handleImageUpload(array $file, string $subfolder, int $maxSizeBytes = 2097152): string
{
    if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Tidak ada file yang diunggah.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Terjadi kesalahan saat mengunggah file.');
    }
    if ($file['size'] > $maxSizeBytes) {
        throw new RuntimeException('Ukuran file maksimal ' . round($maxSizeBytes / 1048576, 1) . 'MB.');
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, getAllowedImageExtensions(), true)) {
        throw new RuntimeException('Tipe file tidak didukung. Gunakan JPG, PNG, atau WEBP.');
    }

    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, getAllowedImageMimeTypes(), true)) {
        throw new RuntimeException('Isi file tidak sesuai dengan ekstensi yang diizinkan.');
    }

    $newFilename = 'img_' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    $destDir     = __DIR__ . '/../uploads/' . trim($subfolder, '/') . '/';
    $destination = $destDir . $newFilename;

    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Gagal menyimpan file gambar.');
    }

    return $newFilename;
}
