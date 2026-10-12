<?php
/**
 * TechZone - Helper Notifikasi In-App
 */
require_once __DIR__ . '/functions.php';

/** Membuat notifikasi baru untuk seorang user. */
function createNotification(PDO $pdo, int $userId, string $title, string $message, string $type = 'info'): void
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
             VALUES (:uid, :title, :message, :type, 0, NOW())'
        );
        $stmt->execute([':uid' => $userId, ':title' => $title, ':message' => $message, ':type' => $type]);
    } catch (Throwable $e) {
        error_log('[TechZone][Notification Error] ' . $e->getMessage());
    }
}

/** Menghitung jumlah notifikasi belum dibaca milik user, untuk badge navbar. */
function getUnreadNotificationCount(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0');
    $stmt->execute([':uid' => $userId]);
    return (int) $stmt->fetchColumn();
}


/** Ikon & warna per tipe notifikasi: [ikon FontAwesome, kelas warna Tailwind]. */
function tzNotificationMeta(string $type): array
{
    $map = [
        'order'   => ['fa-box', 'bg-blue-50 text-blue-600'],
        'payment' => ['fa-money-check-dollar', 'bg-emerald-50 text-emerald-600'],
        'stock'   => ['fa-triangle-exclamation', 'bg-amber-50 text-amber-600'],
        'cart'    => ['fa-cart-shopping', 'bg-cyan-50 text-cyan-600'],
        'info'    => ['fa-bell', 'bg-gray-100 text-gray-500'],
    ];
    return $map[$type] ?? $map['info'];
}

/**
 * Tujuan saat sebuah notifikasi diklik. Nomor pesanan (TZ-YYYYMMDD-NNNNN) di judul/isi
 * notifikasi diubah menjadi tautan ke detail pesanan; tipe "stock" (admin) ke inventori,
 * tipe "cart" (pembeli) ke keranjang. Mengembalikan null bila tidak ada tujuan.
 */
function tzNotificationLink(array $n, bool $isAdmin = false): ?string
{
    $base = techzoneBaseUrl();
    $hay  = ($n['title'] ?? '') . ' ' . ($n['message'] ?? '');
    if (preg_match('/\bTZ-\d{8}-\d{3,}\b/', $hay, $m)) {
        return $base . ($isAdmin ? '/admin/order-detail.php' : '/account/order-detail.php') . '?order=' . rawurlencode($m[0]);
    }
    if ($isAdmin && ($n['type'] ?? '') === 'stock') {
        return $base . '/admin/inventory.php';
    }
    if (!$isAdmin && ($n['type'] ?? '') === 'cart') {
        return $base . '/cart.php';
    }
    return null;
}

/** Notifikasi terbaru milik user (untuk dropdown lonceng). */
function getRecentNotifications(PDO $pdo, int $userId, int $limit = 6): array
{
    try {
        $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC, id DESC LIMIT ' . (int) $limit);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Dipanggil dari ?open=ID di halaman notifikasi: tandai dibaca lalu arahkan ke tujuannya.
 * Selalu mengakhiri request (redirect).
 */
function tzOpenNotification(PDO $pdo, int $userId, int $notificationId, bool $isAdmin, string $fallbackPath): void
{
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE id = :id AND user_id = :uid LIMIT 1');
    $stmt->execute([':id' => $notificationId, ':uid' => $userId]);
    $n = $stmt->fetch();
    if ($n) {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id')->execute([':id' => $notificationId]);
        $link = tzNotificationLink($n, $isAdmin);
        if ($link) {
            header('Location: ' . $link);
            exit;
        }
    }
    redirectTo($fallbackPath);
}
