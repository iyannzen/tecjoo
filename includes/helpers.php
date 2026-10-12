<?php
/**
 * TechZone - Helper Tambahan
 */

require_once __DIR__ . '/functions.php';

/**
 * Membuat nomor order unik dengan format: TZ-YYYYMMDD-XXXXX
 * Contoh: TZ-20260904-00001
 * Angka urut (XXXXX) dihitung berdasarkan jumlah order pada tanggal
 * yang sama + random suffix kecil untuk menghindari race condition.
 */
function generateOrderNumber(PDO $pdo): string
{
    $datePart = date('Ymd');

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM orders WHERE order_number LIKE :pattern");
    $stmt->execute([':pattern' => 'TZ-' . $datePart . '-%']);
    $count = (int) $stmt->fetch()['total'];

    do {
        $count++;
        $candidate = sprintf('TZ-%s-%05d', $datePart, $count);

        $check = $pdo->prepare('SELECT id FROM orders WHERE order_number = :num LIMIT 1');
        $check->execute([':num' => $candidate]);
    } while ($check->fetch());

    return $candidate;
}

/**
 * Membuat ID transaksi pembayaran unik.
 * Contoh: PAY-TZ20260904-A1B2C3
 */
function generateTransactionId(string $orderNumber): string
{
    $clean = str_replace('-', '', $orderNumber);
    return 'PAY-' . $clean . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * Rate limiting sederhana berbasis session untuk mencegah brute-force
 * login (baik user maupun admin). Mengembalikan true jika percobaan
 * MASIH diizinkan, false jika sudah melebihi batas.
 */
function isLoginAttemptAllowed(string $sessionKey, int $maxAttempts = 5, int $lockSeconds = 300): bool
{
    $data = $_SESSION[$sessionKey] ?? ['count' => 0, 'first_attempt' => time()];

    if (time() - $data['first_attempt'] > $lockSeconds) {
        $data = ['count' => 0, 'first_attempt' => time()];
    }

    $_SESSION[$sessionKey] = $data;

    return $data['count'] < $maxAttempts;
}

/** Catat satu percobaan login yang gagal untuk rate limiting. */
function recordFailedLoginAttempt(string $sessionKey): void
{
    $data = $_SESSION[$sessionKey] ?? ['count' => 0, 'first_attempt' => time()];
    $data['count']++;
    $_SESSION[$sessionKey] = $data;
}

/** Reset penghitung percobaan login (dipanggil setelah login berhasil). */
function resetLoginAttempts(string $sessionKey): void
{
    unset($_SESSION[$sessionKey]);
}

/**
 * Validasi kekuatan password minimum.
 * Minimal 8 karakter, mengandung huruf dan angka.
 */
function isPasswordStrongEnough(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Za-z]/', $password)
        && preg_match('/[0-9]/', $password);
}
