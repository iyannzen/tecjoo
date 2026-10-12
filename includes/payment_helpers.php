<?php
/**
 * TechZone - Helper Pembayaran (Simulasi Lokal)
 *
 * PENTING: renderFakeQrSvg() HANYA menghasilkan grafik dekoratif berpola
 * mirip QR code untuk keperluan TAMPILAN simulasi pembayaran. Ini BUKAN
 * QR code asli yang dapat dipindai/divalidasi oleh aplikasi pembayaran
 * apa pun. Status "Lunas" pada sistem ini HANYA berubah melalui proses
 * backend (klaim user -> verifikasi admin), sebagaimana diwajibkan pada
 * spesifikasi proyek.
 */

require_once __DIR__ . '/functions.php';

/**
 * Menghasilkan SVG berpola menyerupai QR code (dekoratif, deterministik
 * berdasarkan $seed) tanpa memanggil API/layanan eksternal mana pun.
 */
function renderFakeQrSvg(string $seed, int $modules = 21, int $pixelSize = 8): string
{
    $hash = hash('sha256', $seed);
    $bits = '';
    foreach (str_split($hash) as $char) {
        $bits .= str_pad(decbin(hexdec($char)), 4, '0', STR_PAD_LEFT);
    }
    // Ulangi bit agar cukup mengisi seluruh grid modul x modul.
    while (strlen($bits) < $modules * $modules) {
        $bits .= $bits;
    }

    $size = $modules * $pixelSize;
    $svg  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $size . ' ' . $size . '" class="w-full h-full">';
    $svg .= '<rect width="' . $size . '" height="' . $size . '" fill="#ffffff"/>';

    for ($row = 0; $row < $modules; $row++) {
        for ($col = 0; $col < $modules; $col++) {
            // Zona finder pattern di 3 sudut (ciri khas visual QR code).
            $inFinder = ($row < 7 && $col < 7)
                || ($row < 7 && $col >= $modules - 7)
                || ($row >= $modules - 7 && $col < 7);

            if ($inFinder) {
                continue; // digambar terpisah di bawah agar rapi
            }

            $bitIndex = $row * $modules + $col;
            if ($bits[$bitIndex % strlen($bits)] === '1') {
                $x = $col * $pixelSize;
                $y = $row * $pixelSize;
                $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $pixelSize . '" height="' . $pixelSize . '" fill="#0a1128"/>';
            }
        }
    }

    // Gambar 3 finder pattern (kotak bersarang khas QR code) di sudut.
    $corners = [[0, 0], [($modules - 7) * $pixelSize, 0], [0, ($modules - 7) * $pixelSize]];
    foreach ($corners as [$fx, $fy]) {
        $svg .= '<rect x="' . $fx . '" y="' . $fy . '" width="' . (7 * $pixelSize) . '" height="' . (7 * $pixelSize) . '" fill="#0a1128"/>';
        $svg .= '<rect x="' . ($fx + $pixelSize) . '" y="' . ($fy + $pixelSize) . '" width="' . (5 * $pixelSize) . '" height="' . (5 * $pixelSize) . '" fill="#ffffff"/>';
        $svg .= '<rect x="' . ($fx + 2 * $pixelSize) . '" y="' . ($fy + 2 * $pixelSize) . '" width="' . (3 * $pixelSize) . '" height="' . (3 * $pixelSize) . '" fill="#0a1128"/>';
    }

    $svg .= '</svg>';
    return $svg;
}

/** Mengecek apakah sebuah payment (berdasarkan expires_at) sudah lewat batas waktu. */
function isPaymentExpired(?string $expiresAt): bool
{
    if (!$expiresAt) {
        return false;
    }
    return strtotime($expiresAt) < time();
}

/**
 * Auto-expire: jika payment QRIS/Transfer masih 'unpaid' tapi sudah melewati
 * expires_at, tandai sebagai kadaluarsa di database (dipanggil saat halaman
 * pembayaran diakses, bukan via cron, agar tetap simpel untuk XAMPP).
 */
function autoExpirePaymentIfNeeded(PDO $pdo, array $order, array $payment): array
{
    if ($payment['status'] === 'unpaid' && isPaymentExpired($payment['expires_at'])) {
        $pdo->prepare("UPDATE payments SET status = 'expired', updated_at = NOW() WHERE id = :id")
            ->execute([':id' => $payment['id']]);
        $pdo->prepare("UPDATE orders SET payment_status = 'expired', updated_at = NOW() WHERE id = :id")
            ->execute([':id' => $order['id']]);

        $payment['status'] = 'expired';
        $order['payment_status'] = 'expired';
    }
    return [$order, $payment];
}

/** Label & warna badge untuk validasi tipe file bukti transfer. */
function getAllowedProofExtensions(): array
{
    return ['jpg', 'jpeg', 'png', 'pdf'];
}

function getAllowedProofMimeTypes(): array
{
    return [
        'image/jpeg', 'image/jpg', 'image/png', 'application/pdf',
    ];
}
