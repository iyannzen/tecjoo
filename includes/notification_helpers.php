<?php
/**
 * TechZone - Helper Notifikasi In-App
 */
require_once __DIR__ . '/functions.php';

/**
 * Pastikan kolom `link` (tujuan saat notifikasi diklik) ada di tabel notifications.
 * Dijalankan hanya saat membuat notifikasi BERTUJUAN; hasilnya di-cache per request.
 */
function tzNotificationsHaveLink(PDO $pdo): bool
{
    static $has = null;
    if ($has !== null) {
        return $has;
    }
    try {
        $pdo->query('SELECT link FROM notifications LIMIT 0')->closeCursor();
        return $has = true;
    } catch (Throwable $e) {
        // kolom belum ada -> ditambahkan di bawah
    }
    try {
        $pdo->exec('ALTER TABLE notifications ADD COLUMN link VARCHAR(255) DEFAULT NULL');
        return $has = true;
    } catch (Throwable $e) {
        error_log('[TechZone][Notification Link Column] ' . $e->getMessage());
        return $has = false;
    }
}

/**
 * Membuat notifikasi baru untuk seorang user.
 * $link = tujuan saat diklik, RELATIF terhadap root situs (mis. "admin/chat.php?thread=5").
 */
function createNotification(PDO $pdo, int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
{
    try {
        if ($link !== null && $link !== '' && tzNotificationsHaveLink($pdo)) {
            $stmt = $pdo->prepare(
                'INSERT INTO notifications (user_id, title, message, type, link, is_read, created_at)
                 VALUES (:uid, :title, :message, :type, :link, 0, NOW())'
            );
            $stmt->execute([':uid' => $userId, ':title' => $title, ':message' => $message, ':type' => $type, ':link' => $link]);
            return;
        }
        $stmt = $pdo->prepare(
            'INSERT INTO notifications (user_id, title, message, type, is_read, created_at)
             VALUES (:uid, :title, :message, :type, 0, NOW())'
        );
        $stmt->execute([':uid' => $userId, ':title' => $title, ':message' => $message, ':type' => $type]);
    } catch (Throwable $e) {
        error_log('[TechZone][Notification Error] ' . $e->getMessage());
    }
}

/**
 * Seperti createNotification, tetapi bila sudah ada notifikasi BELUM DIBACA dengan tujuan yang sama
 * untuk user ini, notifikasi itu diperbarui (isi + waktu) dan tidak dibuat baris baru.
 * Dipakai untuk chat agar satu percakapan = satu notifikasi, bukan satu per pesan.
 */
function tzNotifyOrBump(PDO $pdo, int $userId, string $title, string $message, string $type, string $link): void
{
    try {
        if (tzNotificationsHaveLink($pdo)) {
            $find = $pdo->prepare('SELECT id FROM notifications WHERE user_id = :u AND type = :t AND link = :l AND is_read = 0 ORDER BY id DESC LIMIT 1');
            $find->execute([':u' => $userId, ':t' => $type, ':l' => $link]);
            $id = (int) $find->fetchColumn();
            if ($id > 0) {
                $pdo->prepare('UPDATE notifications SET title = :ti, message = :m, created_at = NOW() WHERE id = :id')
                    ->execute([':ti' => $title, ':m' => $message, ':id' => $id]);
                return;
            }
        }
    } catch (Throwable $e) {
        error_log('[TechZone][NotifyBump] ' . $e->getMessage());
    }
    createNotification($pdo, $userId, $title, $message, $type, $link);
}

/** Tandai notifikasi (belum dibaca) dengan tujuan tertentu sebagai dibaca, mis. saat percakapan chat dibuka. */
function tzMarkNotificationsReadByLink(PDO $pdo, string $type, string $link): void
{
    try {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE type = :t AND link = :l AND is_read = 0')
            ->execute([':t' => $type, ':l' => $link]);
    } catch (Throwable $e) {
        // kolom `link` belum ada -> belum ada notifikasi bertujuan, abaikan
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
        'chat'    => ['fa-comments', 'bg-cyan-50 text-cyan-600'],
        'user'    => ['fa-user-plus', 'bg-purple-50 text-purple-600'],
        'review'  => ['fa-star', 'bg-amber-50 text-amber-600'],
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
    // Tujuan eksplisit (kolom link) selalu didahulukan.
    $explicit = trim((string) ($n['link'] ?? ''));
    if ($explicit !== '' && strpos($explicit, '..') === false && !preg_match('#^[a-z]+:|^//#i', $explicit)) {
        return $base . '/' . ltrim($explicit, '/');
    }
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

/** Teks tautan di daftar notifikasi, sesuai jenisnya. */
function tzNotificationLinkLabel(array $n): string
{
    switch ((string) ($n['type'] ?? '')) {
        case 'chat':   return 'Buka chat';
        case 'user':   return 'Lihat pengguna';
        case 'review': return 'Lihat review';
        case 'stock':  return 'Kelola stok';
        case 'cart':   return 'Buka keranjang';
        case 'order':
        case 'payment': return 'Lihat pesanan';
        default:       return 'Buka';
    }
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
