<?php
/**
 * TechZone - Helper Otomasi
 *
 *  - Riwayat status pesanan (untuk linimasa pelacakan)
 *  - Pembatalan otomatis pesanan yang tidak dibayar (batas waktu pembayaran)
 *  - Notifikasi ke admin + peringatan stok menipis / habis
 *  - Pengingat keranjang yang ditinggalkan
 *  - Penjadwal ringan (tanpa cron) + tautan lacak paket
 *
 * Semua fungsi aman dipanggil berulang (idempotent) dan tidak melempar error ke pengguna.
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/notification_helpers.php';

/** Baca setting angka; bila kosong/tidak valid pakai nilai bawaan (bukan 0). */
function tzIntSetting(string $key, int $default, int $min = 1): int
{
    $raw = trim(getSetting($key, ''));
    if ($raw === '' || !ctype_digit($raw)) {
        return $default;
    }
    return max($min, (int) $raw);
}

/* ======================================================================
 * 1. RIWAYAT STATUS PESANAN
 * ====================================================================== */

/**
 * Membuat tabel riwayat status jika belum ada.
 * PENTING: panggil SEBELUM beginTransaction() -- perintah DDL di MySQL memaksa commit.
 */
function tzEnsureOrderHistoryTable(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS order_status_history (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_id INT UNSIGNED NOT NULL,
                status VARCHAR(40) NOT NULL,
                note VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_osh_order (order_id)
            ) ENGINE=InnoDB'
        );
    } catch (Throwable $e) {
        error_log('[TechZone][History Table] ' . $e->getMessage());
    }
}

/** Mencatat satu perubahan status pesanan. Aman dipanggil di dalam transaksi. */
function recordOrderStatus(PDO $pdo, int $orderId, string $status, ?string $note = null): void
{
    try {
        $pdo->prepare('INSERT INTO order_status_history (order_id, status, note, created_at) VALUES (:oid, :st, :note, NOW())')
            ->execute([':oid' => $orderId, ':st' => $status, ':note' => $note]);
    } catch (Throwable $e) {
        error_log('[TechZone][History] ' . $e->getMessage());
    }
}

/** Mengambil riwayat status pesanan, urut dari yang paling awal. */
function getOrderHistory(PDO $pdo, int $orderId): array
{
    try {
        $stmt = $pdo->prepare('SELECT status, note, created_at FROM order_status_history WHERE order_id = :oid ORDER BY id ASC');
        $stmt->execute([':oid' => $orderId]);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return []; // tabel belum ada -> tampilan tetap berjalan tanpa waktu per langkah
    }
}

/** Tautan pencarian resi (pencarian Google menampilkan widget lacak untuk kurir besar). */
function tzTrackingUrl(?string $courier, ?string $trackingNumber): ?string
{
    $resi = trim((string) $trackingNumber);
    if ($resi === '') {
        return null;
    }
    return 'https://www.google.com/search?q=' . rawurlencode('lacak resi ' . trim((string) $courier) . ' ' . $resi);
}

/**
 * Menyusun langkah linimasa untuk satu pesanan.
 * @return array{steps: array, special: ?string}
 */
function buildOrderTimeline(array $order, array $history): array
{
    $rankMap = ['waiting_payment' => 0, 'payment_verified' => 1, 'processing' => 2, 'packed' => 3, 'shipped' => 4, 'completed' => 5];
    $status  = (string) $order['order_status'];

    // waktu pertama kali sebuah status tercatat
    $times = [];
    foreach ($history as $h) {
        if (!isset($times[$h['status']])) {
            $times[$h['status']] = $h['created_at'];
        }
    }
    $times['waiting_payment'] = $times['waiting_payment'] ?? $order['created_at'];

    $special = in_array($status, ['cancelled', 'refund_requested', 'returned'], true) ? $status : null;
    $rank    = $rankMap[$status] ?? -1;
    if ($special !== null) {
        // untuk status khusus, tentukan sejauh mana pesanan sempat berjalan berdasarkan riwayat
        $rank = 0;
        foreach ($history as $h) {
            if (isset($rankMap[$h['status']])) {
                $rank = max($rank, $rankMap[$h['status']]);
            }
        }
    }

    $isCod = $order['payment_method'] === 'cod';
    $steps = [
        ['key' => 'created',    'label' => 'Pesanan Dibuat',                          'icon' => 'fa-receipt',      'reached' => true,                                                        'time' => $order['created_at']],
        ['key' => 'paid',       'label' => $isCod ? 'Dikonfirmasi' : 'Dibayar',       'icon' => 'fa-wallet',       'reached' => $rank >= 1 || ($order['payment_status'] === 'paid' && $special === null) || $rank >= 2, 'time' => $times['payment_verified'] ?? ($rank >= 2 ? ($times['processing'] ?? null) : null)],
        ['key' => 'processing', 'label' => 'Diproses',                                'icon' => 'fa-gears',        'reached' => $rank >= 2,                                                  'time' => $times['processing'] ?? null],
        ['key' => 'packed',     'label' => 'Dikemas',                                 'icon' => 'fa-box',          'reached' => $rank >= 3,                                                  'time' => $times['packed'] ?? null],
        ['key' => 'shipped',    'label' => 'Dikirim',                                 'icon' => 'fa-truck-fast',   'reached' => $rank >= 4,                                                  'time' => $times['shipped'] ?? null],
        ['key' => 'completed',  'label' => 'Selesai',                                 'icon' => 'fa-circle-check', 'reached' => $rank >= 5,                                                  'time' => $times['completed'] ?? null],
    ];

    // langkah terakhir yang tercapai ditandai "aktif"
    $current = 0;
    foreach ($steps as $i => $s) {
        if ($s['reached']) {
            $current = $i;
        }
    }
    foreach ($steps as $i => &$s) {
        $s['current'] = ($i === $current) && $special === null;
    }
    unset($s);

    return ['steps' => $steps, 'special' => $special];
}

/* ======================================================================
 * 2. NOTIFIKASI ADMIN + PERINGATAN STOK
 * ====================================================================== */

/** Kirim notifikasi ke semua admin aktif. */
function notifyAdmins(PDO $pdo, string $title, string $message, string $type = 'info', ?string $link = null): void
{
    try {
        foreach ($pdo->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active'")->fetchAll() as $admin) {
            createNotification($pdo, (int) $admin['id'], $title, $message, $type, $link);
        }
    } catch (Throwable $e) {
        error_log('[TechZone][NotifyAdmins] ' . $e->getMessage());
    }
}

function tzLowStockThreshold(): int
{
    return tzIntSetting('low_stock_threshold', 10);
}

/**
 * Memeriksa stok satu produk; bila menipis/habis, beri tahu admin.
 * Anti-spam: bila sudah ada peringatan BELUM DIBACA untuk SKU yang sama, isinya diperbarui
 * (bukan membuat notifikasi baru).
 */
function alertLowStock(PDO $pdo, int $productId): void
{
    try {
        $stmt = $pdo->prepare("SELECT id, name, sku, stock FROM products WHERE id = :id AND status = 'active' LIMIT 1");
        $stmt->execute([':id' => $productId]);
        $p = $stmt->fetch();
        if (!$p || (int) $p['stock'] > tzLowStockThreshold()) {
            return;
        }

        $stock   = (int) $p['stock'];
        $title   = $stock <= 0 ? 'Stok Habis' : 'Stok Menipis';
        $tag     = '[' . $p['sku'] . ']';
        $message = $tag . ' ' . $p['name'] . ($stock <= 0 ? ' kini habis.' : ' tersisa ' . $stock . ' unit.') . ' Segera lakukan restok.';

        foreach ($pdo->query("SELECT id FROM users WHERE role = 'admin' AND status = 'active'")->fetchAll() as $admin) {
            $find = $pdo->prepare("SELECT id, message FROM notifications WHERE user_id = :uid AND type = 'stock' AND is_read = 0 AND LEFT(message, :len) = :tag LIMIT 1");
            $find->execute([':uid' => (int) $admin['id'], ':len' => strlen($tag), ':tag' => $tag]);
            $existing = $find->fetch();

            if ($existing) {
                if ($existing['message'] !== $message) {
                    $pdo->prepare('UPDATE notifications SET title = :t, message = :m, created_at = NOW() WHERE id = :id')
                        ->execute([':t' => $title, ':m' => $message, ':id' => $existing['id']]);
                }
            } else {
                createNotification($pdo, (int) $admin['id'], $title, $message, 'stock');
            }
        }
    } catch (Throwable $e) {
        error_log('[TechZone][LowStock] ' . $e->getMessage());
    }
}

/** Memindai seluruh produk aktif yang stoknya di bawah ambang batas. */
function scanLowStock(PDO $pdo): void
{
    try {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE status = 'active' AND stock <= :t LIMIT 200");
        $stmt->execute([':t' => tzLowStockThreshold()]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            alertLowStock($pdo, (int) $id);
        }
    } catch (Throwable $e) {
        error_log('[TechZone][ScanStock] ' . $e->getMessage());
    }
}

/* ======================================================================
 * 3. PEMBATALAN OTOMATIS PESANAN TIDAK DIBAYAR
 * ====================================================================== */

function tzPaymentExpiryHours(): int
{
    return tzIntSetting('payment_expiry_hours', 24);
}

/** Batas waktu pembayaran untuk sebuah pesanan (DateTime) -- dipakai untuk hitung mundur. */
function tzOrderPaymentDeadline(array $order): ?DateTimeImmutable
{
    if (($order['payment_status'] ?? '') !== 'unpaid' || ($order['order_status'] ?? '') !== 'waiting_payment'
        || !in_array($order['payment_method'] ?? '', ['qris', 'transfer', 'midtrans'], true)) {
        return null;
    }
    try {
        return (new DateTimeImmutable($order['created_at']))->modify('+' . tzPaymentExpiryHours() . ' hours');
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * Membatalkan pesanan QRIS/Transfer yang belum dibayar melewati batas waktu,
 * mengembalikan stok, dan memberi tahu pembeli.
 * @return int jumlah pesanan yang dibatalkan
 */
function expireUnpaidOrders(PDO $pdo): int
{
    $hours = tzPaymentExpiryHours();
    $count = 0;

    try {
        tzEnsureOrderHistoryTable($pdo);

        $ids = $pdo->query(
            "SELECT id FROM orders
             WHERE order_status = 'waiting_payment' AND payment_status = 'unpaid'
               AND payment_method IN ('qris','transfer','midtrans')
               AND created_at < (NOW() - INTERVAL " . (int) $hours . " HOUR)
             ORDER BY id ASC LIMIT 50"
        )->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        error_log('[TechZone][Expire] ' . $e->getMessage());
        return 0;
    }

    foreach ($ids as $orderId) {
        // Pesanan Midtrans: tanya Midtrans dulu. Bila ternyata sudah dibayar -> jangan dibatalkan.
        $mtRow = $pdo->prepare("SELECT order_number FROM orders WHERE id = :id AND payment_method = 'midtrans'");
        $mtRow->execute([':id' => $orderId]);
        $mtNumber = $mtRow->fetchColumn();
        if ($mtNumber) {
            require_once __DIR__ . '/midtrans_helpers.php';
            if (midtransIsConfigured()) {
                $mtResult = midtransSyncOrder($pdo, (string) $mtNumber);
                if (in_array($mtResult, ['paid', 'late_paid'], true)) {
                    continue; // sudah dibayar
                }
                if ($mtResult === 'cancelled') {
                    $count++;  // sudah dibatalkan oleh sinkronisasi
                    continue;
                }
                midtransCancelTransaction((string) $mtNumber); // tutup transaksi pending agar tidak bisa dibayar belakangan
            }
        }
        try {
            $pdo->beginTransaction();

            $lock = $pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1 FOR UPDATE');
            $lock->execute([':id' => $orderId]);
            $order = $lock->fetch();

            // cek ulang: bisa jadi pembeli baru saja membayar / membatalkan
            if (!$order || $order['order_status'] !== 'waiting_payment' || $order['payment_status'] !== 'unpaid') {
                $pdo->rollBack();
                continue;
            }

            $items = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = :id');
            $items->execute([':id' => $orderId]);
            foreach ($items->fetchAll() as $item) {
                if ($item['product_id']) {
                    $pdo->prepare('UPDATE products SET stock = stock + :qty WHERE id = :id')
                        ->execute([':qty' => (int) $item['quantity'], ':id' => $item['product_id']]);
                }
            }

            $reason = 'Batas waktu pembayaran (' . $hours . ' jam) telah habis.';
            $pdo->prepare("UPDATE orders SET order_status = 'cancelled', payment_status = 'expired', cancel_reason = :r, updated_at = NOW() WHERE id = :id")
                ->execute([':r' => $reason, ':id' => $orderId]);
            $pdo->prepare("UPDATE payments SET status = 'expired', updated_at = NOW() WHERE order_id = :id AND status = 'unpaid'")
                ->execute([':id' => $orderId]);
            recordOrderStatus($pdo, (int) $orderId, 'cancelled', $reason);

            $pdo->commit();

            createNotification(
                $pdo, (int) $order['user_id'], 'Pesanan Dibatalkan Otomatis',
                'Pesanan ' . $order['order_number'] . ' dibatalkan karena pembayaran tidak diterima dalam ' . $hours . ' jam. Stok sudah dikembalikan; Anda bisa memesan kembali kapan saja.',
                'order'
            );
            $count++;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[TechZone][Expire Order] ' . $e->getMessage());
        }
    }

    if ($count > 0) {
        notifyAdmins($pdo, 'Pesanan Kedaluwarsa', $count . ' pesanan dibatalkan otomatis karena melewati batas waktu pembayaran.', 'order');
    }
    return $count;
}

/* ======================================================================
 * 4. PENGINGAT KERANJANG
 * ====================================================================== */

/**
 * Mengirim notifikasi ke user yang meninggalkan barang di keranjang.
 * Satu pengingat per perubahan keranjang (tidak berulang sampai keranjang berubah lagi).
 */
function sendCartReminders(PDO $pdo): int
{
    $hours = tzIntSetting('cart_reminder_hours', 24);
    $sent  = 0;

    try {
        $rows = $pdo->query(
            "SELECT c.user_id, COUNT(ci.id) AS total_items, MAX(ci.updated_at) AS last_change
             FROM carts c
             JOIN cart_items ci ON ci.cart_id = c.id
             JOIN users u ON u.id = c.user_id AND u.role = 'user' AND u.status = 'active'
             WHERE c.user_id IS NOT NULL
             GROUP BY c.id, c.user_id
             HAVING MAX(ci.updated_at) < (NOW() - INTERVAL " . (int) $hours . " HOUR)
                AND MAX(ci.updated_at) > (NOW() - INTERVAL 14 DAY)
             LIMIT 100"
        )->fetchAll();

        foreach ($rows as $row) {
            $check = $pdo->prepare("SELECT id FROM notifications WHERE user_id = :uid AND type = 'cart' AND created_at >= :last LIMIT 1");
            $check->execute([':uid' => $row['user_id'], ':last' => $row['last_change']]);
            if ($check->fetch()) {
                continue;
            }
            createNotification(
                $pdo, (int) $row['user_id'], 'Barang di Keranjang Menunggu Anda',
                'Anda masih memiliki ' . (int) $row['total_items'] . ' produk di keranjang. Selesaikan pesanan sebelum stok habis!',
                'cart'
            );
            $sent++;
        }
    } catch (Throwable $e) {
        error_log('[TechZone][CartReminder] ' . $e->getMessage());
    }
    return $sent;
}

/* ======================================================================
 * 5. PENJADWAL RINGAN (tanpa cron)
 * ====================================================================== */

/**
 * Menjalankan tugas berkala paling sering 1x tiap 5 menit.
 * Dipanggil otomatis dari includes/header.php; juga bisa dari cron/run-tasks.php.
 */
function runScheduledTasks(?PDO $pdo = null, bool $force = false): array
{
    $lock = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'techzone_tasks_' . md5(__DIR__) . '.lock';
    if (!$force && is_file($lock) && (time() - (int) @filemtime($lock)) < 300) {
        return [];
    }
    @touch($lock);

    $result = [];
    try {
        $pdo = $pdo ?: getDbConnection();
        $result['expired']  = expireUnpaidOrders($pdo);
        $result['reminded'] = sendCartReminders($pdo);
        scanLowStock($pdo);
        $result['stock_scanned'] = true;
    } catch (Throwable $e) {
        error_log('[TechZone][Scheduler] ' . $e->getMessage());
    }
    return $result;
}


/* ======================================================================
 * 6. ANGKA PEMBERITAHUAN DI SIDEBAR ADMIN
 * ====================================================================== */

/**
 * Jumlah hal yang butuh perhatian admin:
 *  - notifications : notifikasi belum dibaca
 *  - orders        : pesanan baru / permintaan refund yang BELUM dibuka admin
 *  - payments      : pembayaran menunggu verifikasi (berkurang setelah dikonfirmasi/ditolak)
 *  - stock         : produk aktif dengan stok menipis/habis
 */
function tzAdminBadgeCounts(PDO $pdo, int $adminId): array
{
    $c = ['notifications' => 0, 'orders' => 0, 'payments' => 0, 'stock' => 0, 'chat' => 0];
    try {
        $c['notifications'] = getUnreadNotificationCount($pdo, $adminId);

        $st = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :u AND is_read = 0 AND type = 'order'
                             AND title IN ('Pesanan Baru', 'Permintaan Pengembalian Dana')");
        $st->execute([':u' => $adminId]);
        $c['orders'] = (int) $st->fetchColumn();

        $c['payments'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'waiting_verification'")->fetchColumn();

        $st = $pdo->prepare("SELECT COUNT(*) FROM products WHERE status = 'active' AND stock <= :t");
        $st->execute([':t' => tzLowStockThreshold()]);
        $c['stock'] = (int) $st->fetchColumn();
    } catch (Throwable $e) {
        error_log('[TechZone][AdminCounts] ' . $e->getMessage());
    }
    // Pesan chat belum dibaca (0 bila tabel chat belum ada; tidak dicatat sebagai error).
    require_once __DIR__ . '/chat_helpers.php';
    $c['chat'] = tzChatUnreadTotal($pdo, 'admin');
    return $c;
}

/** Tandai notifikasi admin yang menyebut nomor pesanan ini sebagai sudah dibaca (admin sudah melihatnya). */
function tzMarkOrderNotificationsRead(PDO $pdo, int $adminId, string $orderNumber): void
{
    try {
        $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :u AND is_read = 0 AND message LIKE :num')
            ->execute([':u' => $adminId, ':num' => '%' . $orderNumber . '%']);
    } catch (Throwable $e) {
        error_log('[TechZone][MarkRead] ' . $e->getMessage());
    }
}
