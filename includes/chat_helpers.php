<?php
/**
 * TechZone - Helper Chat Produk (user <-> admin)
 *
 * Satu percakapan (thread) per PASANGAN user + produk. Pesan bisa berupa teks atau
 * "kartu produk" (gambar, nama, harga). Tabel dibuat OTOMATIS saat pertama kali dipakai
 * (sama seperti fitur lain di aplikasi ini); file database/migration_chat.sql hanya opsional.
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/product_helpers.php';

const TZ_CHAT_MAX_LEN   = 1000;  // panjang maksimal satu pesan
const TZ_CHAT_FLOOD_MAX = 30;    // maks. pesan user per thread per menit

/** Teks sapaan otomatis (dikirim chatbot saat percakapan dibuat). */
function tzChatGreeting(): string
{
    return 'Terima kasih telah menghubungi kami. Apa yang bisa saya bantu hari ini?';
}

/**
 * Pastikan tabel chat ada. Normalnya hanya 2 query SELECT ringan; DDL baru dijalankan
 * kalau tabelnya belum ada. Mengembalikan false bila tabel tidak bisa dibuat.
 */
function tzChatReady(PDO $pdo): bool
{
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $pdo->query('SELECT 1 FROM chat_threads LIMIT 1')->closeCursor();
        $pdo->query('SELECT 1 FROM chat_messages LIMIT 1')->closeCursor();
        return $ready = true;
    } catch (Throwable $e) {
        // tabel belum ada -> dibuat di bawah
    }
    try {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS chat_threads (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                product_id INT UNSIGNED NOT NULL,
                user_unread INT UNSIGNED NOT NULL DEFAULT 0,
                admin_unread INT UNSIGNED NOT NULL DEFAULT 0,
                last_sender VARCHAR(10) DEFAULT NULL,
                last_preview VARCHAR(190) DEFAULT NULL,
                last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_chat_user_product (user_id, product_id),
                INDEX idx_chat_last (last_message_at),
                INDEX idx_chat_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS chat_messages (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                thread_id INT UNSIGNED NOT NULL,
                sender VARCHAR(10) NOT NULL,
                sender_id INT UNSIGNED DEFAULT NULL,
                type VARCHAR(10) NOT NULL DEFAULT \'text\',
                body TEXT DEFAULT NULL,
                product_id INT UNSIGNED DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_chatmsg_thread (thread_id, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        return $ready = true;
    } catch (Throwable $e) {
        error_log('[TechZone][Chat Tables] ' . $e->getMessage());
        return $ready = false;
    }
}

/** Ringkasan satu pesan untuk daftar percakapan. */
function tzChatPreview(string $type, ?string $body): string
{
    if ($type === 'product') {
        return '[Produk]';
    }
    $t = trim(preg_replace('/\s+/u', ' ', (string) $body));
    return mb_strlen($t) > 90 ? mb_substr($t, 0, 90) . '…' : $t;
}

/** Label hari: Hari ini / Kemarin / 17 Oktober 2026. */
function tzChatDayLabel(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }
    $d = date('Y-m-d', $ts);
    if ($d === date('Y-m-d')) {
        return 'Hari ini';
    }
    if ($d === date('Y-m-d', strtotime('-1 day'))) {
        return 'Kemarin';
    }
    return formatTanggalIndo($datetime);
}

/** Waktu singkat untuk daftar: jam bila hari ini, selain itu "17 Okt". */
function tzChatShortTime(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }
    if (date('Y-m-d', $ts) === date('Y-m-d')) {
        return date('H:i', $ts);
    }
    $bln = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return date('j', $ts) . ' ' . $bln[(int) date('n', $ts)];
}

/** Data kartu produk (untuk tampilan) dari satu baris tabel products. */
function tzChatProductCard(?array $p): ?array
{
    if (!$p || empty($p['id'])) {
        return null;
    }
    $base = techzoneBaseUrl();
    return [
        'id'    => (int) $p['id'],
        'name'  => (string) $p['name'],
        'price' => formatRupiah(effectivePrice($p)),
        'old'   => (!empty($p['discount_price']) && effectivePrice($p) < (float) $p['price']) ? formatRupiah($p['price']) : '',
        'image' => productImageUrl($p),
        'url'   => $base . '/product-detail.php?slug=' . rawurlencode((string) $p['slug']),
    ];
}

/** Ambil produk aktif berdasarkan slug atau id. */
function tzChatFindProduct(PDO $pdo, string $slugOrId): ?array
{
    $slugOrId = trim($slugOrId);
    if ($slugOrId === '') {
        return null;
    }
    if (ctype_digit($slugOrId)) {
        $st = $pdo->prepare("SELECT * FROM products WHERE id = :v AND status = 'active' LIMIT 1");
    } else {
        $st = $pdo->prepare("SELECT * FROM products WHERE slug = :v AND status = 'active' LIMIT 1");
    }
    $st->execute([':v' => $slugOrId]);
    return $st->fetch() ?: null;
}

/** Ambil satu thread lengkap dengan data user & produk. */
function tzChatGetThread(PDO $pdo, int $threadId): ?array
{
    $st = $pdo->prepare(
        'SELECT t.*, u.name AS user_name, u.email AS user_email,
                p.id AS p_id, p.name, p.slug, p.price, p.discount_price, p.main_image
         FROM chat_threads t
         LEFT JOIN users u ON u.id = t.user_id
         LEFT JOIN products p ON p.id = t.product_id
         WHERE t.id = :id LIMIT 1'
    );
    $st->execute([':id' => $threadId]);
    $row = $st->fetch();
    if (!$row) {
        return null;
    }
    $row['product'] = tzChatProductCard($row['p_id'] ? [
        'id' => $row['p_id'], 'name' => $row['name'], 'slug' => $row['slug'], 'price' => $row['price'],
        'discount_price' => $row['discount_price'], 'main_image' => $row['main_image'],
    ] : null);
    return $row;
}

/** Boleh akses? Admin: semua thread. User: hanya miliknya. */
function tzChatCanAccess(array $thread, string $role, int $uid): bool
{
    return $role === 'admin' || ((int) $thread['user_id'] === $uid && $role === 'user');
}

/**
 * Ambil (atau buat) thread untuk pasangan user + produk. Saat dibuat, chatbot langsung
 * mengirim sapaan otomatis. Mengembalikan id thread.
 */
function tzChatGetOrCreateThread(PDO $pdo, int $userId, int $productId): int
{
    $find = $pdo->prepare('SELECT id FROM chat_threads WHERE user_id = :u AND product_id = :p LIMIT 1');
    $find->execute([':u' => $userId, ':p' => $productId]);
    $id = (int) $find->fetchColumn();
    if ($id > 0) {
        return $id;
    }

    $ins = $pdo->prepare('INSERT IGNORE INTO chat_threads (user_id, product_id, last_message_at, created_at) VALUES (:u, :p, NOW(), NOW())');
    $ins->execute([':u' => $userId, ':p' => $productId]);
    $created = $ins->rowCount() > 0;

    $find->execute([':u' => $userId, ':p' => $productId]);
    $id = (int) $find->fetchColumn();
    if ($id > 0 && $created) {
        tzChatAddMessage($pdo, $id, 'bot', null, 'text', tzChatGreeting(), null);
    }
    return $id;
}

/**
 * Tambah satu pesan dan perbarui thread (pratinjau, waktu, penghitung belum dibaca).
 * $sender: 'user' | 'admin' | 'bot'. Pesan chatbot tidak menambah penghitung.
 */
function tzChatAddMessage(PDO $pdo, int $threadId, string $sender, ?int $senderId, string $type, ?string $body, ?int $productId): int
{
    $pdo->prepare(
        'INSERT INTO chat_messages (thread_id, sender, sender_id, type, body, product_id, created_at)
         VALUES (:t, :s, :sid, :ty, :b, :p, NOW())'
    )->execute([':t' => $threadId, ':s' => $sender, ':sid' => $senderId, ':ty' => $type, ':b' => $body, ':p' => $productId]);
    $msgId = (int) $pdo->lastInsertId();

    $inc = '';
    if ($sender === 'user') {
        $inc = ', admin_unread = admin_unread + 1';
    } elseif ($sender === 'admin') {
        $inc = ', user_unread = user_unread + 1';
    }
    $pdo->prepare(
        "UPDATE chat_threads SET last_sender = :s, last_preview = :pv, last_message_at = NOW() $inc WHERE id = :id"
    )->execute([':s' => $sender, ':pv' => tzChatPreview($type, $body), ':id' => $threadId]);

    return $msgId;
}

/** Bentuk satu pesan menjadi array siap-JSON. $viewerRole menentukan "mine". */
function tzChatFormatMessage(array $m, string $viewerRole): array
{
    $card = null;
    if (($m['type'] ?? '') === 'product') {
        $card = tzChatProductCard(!empty($m['p_id']) ? [
            'id' => $m['p_id'], 'name' => $m['p_name'], 'slug' => $m['p_slug'], 'price' => $m['p_price'],
            'discount_price' => $m['p_discount'], 'main_image' => $m['p_image'],
        ] : null);
    }
    $ts = strtotime($m['created_at']);
    return [
        'id'      => (int) $m['id'],
        'sender'  => $m['sender'],
        'mine'    => $m['sender'] === $viewerRole,
        'type'    => $m['type'],
        'body'    => (string) ($m['body'] ?? ''),
        'product' => $card,
        'day'     => date('Y-m-d', $ts ?: time()),
        'dayText' => tzChatDayLabel($m['created_at']),
        'time'    => date('H:i', $ts ?: time()),
    ];
}

/** Pesan baru setelah $afterId (atau 200 pesan terakhir bila $afterId = 0), urut lama -> baru. */
function tzChatFetchMessages(PDO $pdo, int $threadId, int $afterId, string $viewerRole): array
{
    $sel = 'SELECT m.*, p.id AS p_id, p.name AS p_name, p.slug AS p_slug, p.price AS p_price,
                   p.discount_price AS p_discount, p.main_image AS p_image
            FROM chat_messages m LEFT JOIN products p ON p.id = m.product_id';
    if ($afterId > 0) {
        $st = $pdo->prepare("$sel WHERE m.thread_id = :t AND m.id > :a ORDER BY m.id ASC LIMIT 100");
        $st->execute([':t' => $threadId, ':a' => $afterId]);
        $rows = $st->fetchAll();
    } else {
        $st = $pdo->prepare("SELECT * FROM ($sel WHERE m.thread_id = :t ORDER BY m.id DESC LIMIT 200) x ORDER BY id ASC");
        $st->execute([':t' => $threadId]);
        $rows = $st->fetchAll();
    }
    return array_map(static fn ($r) => tzChatFormatMessage($r, $viewerRole), $rows);
}

/** Tandai percakapan sudah dibaca oleh peran ini. */
function tzChatMarkRead(PDO $pdo, int $threadId, string $role): void
{
    $col = $role === 'admin' ? 'admin_unread' : 'user_unread';
    $pdo->prepare("UPDATE chat_threads SET $col = 0 WHERE id = :id AND $col > 0")->execute([':id' => $threadId]);
}

/**
 * Total pesan belum dibaca untuk badge. Sengaja TIDAK membuat tabel dan diam-diam
 * mengembalikan 0 bila tabel belum ada (dipanggil di setiap halaman).
 */
function tzChatUnreadTotal(PDO $pdo, string $role, int $uid = 0): int
{
    try {
        if ($role === 'admin') {
            return (int) $pdo->query('SELECT COALESCE(SUM(admin_unread), 0) FROM chat_threads')->fetchColumn();
        }
        $st = $pdo->prepare('SELECT COALESCE(SUM(user_unread), 0) FROM chat_threads WHERE user_id = :u');
        $st->execute([':u' => $uid]);
        return (int) $st->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/** Daftar percakapan untuk admin (semua) atau user (miliknya), terbaru di atas. */
function tzChatListThreads(PDO $pdo, string $role, int $uid, string $q = ''): array
{
    $where  = [];
    $params = [];
    if ($role === 'user') {
        $where[] = 't.user_id = :u';
        $params[':u'] = $uid;
    }
    $q = trim($q);
    if ($q !== '') {
        $where[] = '(u.name LIKE :q1 OR u.email LIKE :q2 OR p.name LIKE :q3)';
        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $q) . '%';
        $params[':q1'] = $params[':q2'] = $params[':q3'] = $like;
    }
    $sql = 'SELECT t.id, t.user_unread, t.admin_unread, t.last_sender, t.last_preview, t.last_message_at,
                   u.name AS user_name, p.name AS product_name, p.main_image
            FROM chat_threads t
            LEFT JOIN users u ON u.id = t.user_id
            LEFT JOIN products p ON p.id = t.product_id'
         . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
         . ' ORDER BY t.last_message_at DESC, t.id DESC LIMIT 100';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $out = [];
    foreach ($st->fetchAll() as $r) {
        $out[] = [
            'id'      => (int) $r['id'],
            'user'    => (string) ($r['user_name'] ?? 'Pengguna'),
            'product' => (string) ($r['product_name'] ?? 'Produk tidak tersedia'),
            'image'   => productImageUrl(['main_image' => $r['main_image'] ?? '']),
            'preview' => (string) ($r['last_preview'] ?? ''),
            'sender'  => (string) ($r['last_sender'] ?? ''),
            'time'    => tzChatShortTime($r['last_message_at']),
            'unread'  => (int) ($role === 'admin' ? $r['admin_unread'] : $r['user_unread']),
        ];
    }
    return $out;
}
