<?php
/**
 * TechZone - API Chat Produk (JSON). Dipakai oleh user (chat.php) dan admin (admin/chat.php).
 *
 *   GET  ?action=poll&thread=ID&after=LASTID   pesan baru (sekaligus menandai dibaca)
 *   GET  ?action=threads[&q=kata]              daftar percakapan
 *   GET  ?action=counts                        jumlah pesan belum dibaca
 *   POST action=send&thread=ID&body=...        kirim teks
 *   POST action=send_product&thread=ID         kirim kartu produk
 * Semua POST wajib membawa csrf_token. User hanya boleh mengakses percakapannya sendiri.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/chat_helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function tzChatJson(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$isAdmin = isAdminLoggedIn();
$isUser  = isUserLoggedIn();
if (!$isAdmin && !$isUser) {
    tzChatJson(['error' => 'unauthorized'], 401);
}
$role = $isAdmin ? 'admin' : 'user';
$uid  = (int) ($_SESSION['user_id'] ?? 0);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = (string) ($_POST['action'] ?? $_GET['action'] ?? '');
$csrfOk = !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''));
$csrfOk = $method === 'POST' ? $csrfOk : true;

try {
    $pdo = getDbConnection();

    // Akun yang dinonaktifkan tidak boleh memakai chat.
    $st = $pdo->prepare('SELECT status FROM users WHERE id = :id LIMIT 1');
    $st->execute([':id' => $uid]);
    if ($st->fetchColumn() !== 'active') {
        tzChatJson(['error' => 'forbidden'], 403);
    }

    if (!tzChatReady($pdo)) {
        tzChatJson(['error' => 'unavailable'], 503);
    }

    // ---------- Aksi baca (GET): lepas kunci sesi supaya tidak menahan request lain ----------
    if ($method === 'GET') {
        if ($action === 'counts') {
            session_write_close();
            tzChatJson(['unread' => tzChatUnreadTotal($pdo, $role, $uid)]);
        }
        if ($action === 'threads') {
            session_write_close();
            tzChatJson(['threads' => tzChatListThreads($pdo, $role, $uid, (string) ($_GET['q'] ?? '')),
                        'unread'  => tzChatUnreadTotal($pdo, $role, $uid)]);
        }
        if ($action === 'poll') {
            $thread = tzChatGetThread($pdo, (int) ($_GET['thread'] ?? 0));
            if (!$thread || !tzChatCanAccess($thread, $role, $uid)) {
                tzChatJson(['error' => 'not_found'], 404);
            }
            session_write_close();
            $messages = tzChatFetchMessages($pdo, (int) $thread['id'], max(0, (int) ($_GET['after'] ?? 0)), $role);
            tzChatMarkRead($pdo, (int) $thread['id'], $role);
            tzChatJson(['messages' => $messages]);
        }
        tzChatJson(['error' => 'bad_request'], 400);
    }

    // ---------- Aksi tulis (POST) ----------
    if ($method !== 'POST' || !in_array($action, ['send', 'send_product'], true)) {
        tzChatJson(['error' => 'bad_request'], 400);
    }
    if (!$csrfOk) {
        tzChatJson(['error' => 'csrf'], 403);
    }
    $thread = tzChatGetThread($pdo, (int) ($_POST['thread'] ?? 0));
    if (!$thread || !tzChatCanAccess($thread, $role, $uid)) {
        tzChatJson(['error' => 'not_found'], 404);
    }
    $threadId = (int) $thread['id'];
    session_write_close();

    // Batasi banjir pesan dari user.
    if ($role === 'user') {
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE thread_id = :t AND sender = 'user' AND created_at > (NOW() - INTERVAL 1 MINUTE)");
        $cnt->execute([':t' => $threadId]);
        if ((int) $cnt->fetchColumn() >= TZ_CHAT_FLOOD_MAX) {
            tzChatJson(['error' => 'too_many'], 429);
        }
    }

    if ($action === 'send_product') {
        if ($role !== 'user' || empty($thread['p_id'])) {
            tzChatJson(['error' => 'bad_request'], 400);
        }
        $msgId = tzChatAddMessage($pdo, $threadId, 'user', $uid, 'product', null, (int) $thread['p_id']);
    } else {
        $body = trim((string) ($_POST['body'] ?? ''));
        $body = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $body) ?? '';
        $body = preg_replace("/\n{3,}/", "\n\n", str_replace("\r\n", "\n", $body)) ?? '';
        if ($body === '') {
            tzChatJson(['error' => 'empty'], 422);
        }
        if (mb_strlen($body) > TZ_CHAT_MAX_LEN) {
            $body = mb_substr($body, 0, TZ_CHAT_MAX_LEN);
        }
        $msgId = tzChatAddMessage($pdo, $threadId, $role, $uid, 'text', $body, null);
    }

    $mine = null;
    foreach (tzChatFetchMessages($pdo, $threadId, max(1, $msgId - 1), $role) as $m) {
        if ($m['id'] === $msgId) {
            $mine = $m;
        }
    }
    tzChatJson(['ok' => true, 'message' => $mine]);
} catch (Throwable $e) {
    error_log('[TechZone][Chat API] ' . $e->getMessage());
    tzChatJson(['error' => 'server'], 500);
}
