<?php
/**
 * TechZone - Chat dengan Admin (sisi pembeli)
 *   chat.php                 -> daftar percakapan milik user
 *   chat.php?product=SLUG    -> buka/buat percakapan untuk produk itu, lalu masuk ruang chat
 *   chat.php?thread=ID       -> ruang chat
 * Hanya untuk user yang sudah login (tamu hanya bisa melihat-lihat).
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/product_helpers.php';
require_once __DIR__ . '/includes/chat_helpers.php';

if (isAdminLoggedIn()) {
    redirectTo('admin/chat.php');
}
requireUserLogin();

$pdo    = getDbConnection();
$userId = (int) $_SESSION['user_id'];
$base   = techzoneBaseUrl();
$ready  = tzChatReady($pdo);

// Dari tombol "Chat" di halaman produk: buka/buat percakapan lalu pindah ke ruangnya.
if ($ready && isset($_GET['product'])) {
    $product = tzChatFindProduct($pdo, (string) $_GET['product']);
    if (!$product) {
        setFlashMessage('error', 'Produk tidak ditemukan.');
        redirectTo('products.php');
    }
    redirectTo('chat.php?thread=' . tzChatGetOrCreateThread($pdo, $userId, (int) $product['id']));
}

$thread = null;
if ($ready && isset($_GET['thread'])) {
    $thread = tzChatGetThread($pdo, (int) $_GET['thread']);
    if (!$thread || !tzChatCanAccess($thread, 'user', $userId)) {
        setFlashMessage('error', 'Percakapan tidak ditemukan.');
        redirectTo('chat.php');
    }
}

$threads   = ($ready && !$thread) ? tzChatListThreads($pdo, 'user', $userId) : [];
$pageTitle = ($thread ? 'Chat Admin' : 'Chat Saya') . ' | TechZone';
$bodyClass = $thread ? 'tz-chatpage' : '';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<main class="max-w-3xl mx-auto px-3 sm:px-6 py-4 sm:py-8">

<?php if (!$ready): ?>
  <div class="bg-white border border-gray-100 rounded-2xl p-10 text-center shadow-card">
    <i class="fa-regular fa-comments text-3xl text-gray-300 mb-3"></i>
    <p class="text-sm text-gray-600">Fitur chat sedang disiapkan. Silakan coba lagi beberapa saat lagi.</p>
  </div>

<?php elseif ($thread): $prod = $thread['product']; ?>
  <div class="tz-chat" id="tz-chat" data-role="user" data-thread="<?= (int) $thread['id'] ?>"
       data-api="<?= e($base) ?>/api/chat.php" data-csrf="<?= e(csrfToken()) ?>" data-max="<?= TZ_CHAT_MAX_LEN ?>">
    <header class="tz-chat__head">
      <a class="tz-chat__back" href="<?= e($prod ? $prod['url'] : $base . '/chat.php') ?>" aria-label="Kembali"><i class="fa-solid fa-arrow-left"></i></a>
      <span class="tz-chat__avatar"><i class="fa-solid fa-microchip"></i></span>
      <div class="tz-chat__who"><b>Admin TechZone</b><small>Tanya detail produk ke admin</small></div>
      <a class="tz-chat__iconlink" href="<?= e($base) ?>/chat.php" title="Semua percakapan" aria-label="Semua percakapan"><i class="fa-regular fa-comments"></i></a>
    </header>

    <div class="tz-chat__msgs" id="tz-chat-msgs" aria-live="polite"><p class="tz-chat__note">Memuat percakapan…</p></div>

    <?php if ($prod): ?>
    <div class="tz-chat__prod" id="tz-chat-prod">
      <a class="tz-chat__prodimg" href="<?= e($prod['url']) ?>"><img src="<?= e($prod['image']) ?>" alt=""></a>
      <div class="tz-chat__prodtxt">
        <b><?= e($prod['name']) ?></b>
        <span><?= e($prod['price']) ?><?php if ($prod['old'] !== ''): ?> <s><?= e($prod['old']) ?></s><?php endif; ?></span>
      </div>
      <button type="button" class="tz-chat__prodsend" id="tz-chat-sendprod">Kirim</button>
      <button type="button" class="tz-chat__prodx" id="tz-chat-prodx" aria-label="Sembunyikan kartu produk"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <?php endif; ?>

    <form class="tz-chat__form" id="tz-chat-form" autocomplete="off">
      <textarea id="tz-chat-input" rows="1" maxlength="<?= TZ_CHAT_MAX_LEN ?>" placeholder="Ketik pesan…" aria-label="Tulis pesan" enterkeyhint="send"></textarea>
      <button type="submit" class="tz-chat__send" aria-label="Kirim pesan"><i class="fa-solid fa-paper-plane"></i></button>
    </form>
  </div>

<?php else: ?>
  <div class="mb-5">
    <h1 class="text-xl font-extrabold text-navy-900">Chat Saya</h1>
    <p class="text-xs text-gray-500 mt-1">Percakapan dengan admin tentang produk. Buka sebuah produk lalu tekan tombol Chat untuk bertanya.</p>
  </div>
  <?php if (!$threads): ?>
    <div class="bg-white border border-gray-100 rounded-2xl p-10 text-center shadow-card">
      <i class="fa-regular fa-comments text-3xl text-gray-300 mb-3"></i>
      <p class="text-sm text-gray-600 mb-4">Belum ada percakapan.</p>
      <a href="<?= e($base) ?>/products.php" class="inline-flex items-center gap-2 bg-cyan-500 hover:bg-cyan-400 text-navy-900 text-sm font-semibold px-5 py-2.5 rounded-lg transition"><i class="fa-solid fa-microchip"></i> Lihat Produk</a>
    </div>
  <?php else: ?>
    <div class="tz-chatlist tz-chatlist--page">
      <?php foreach ($threads as $t): ?>
        <a href="<?= e($base) ?>/chat.php?thread=<?= (int) $t['id'] ?>" class="tz-chatrow<?= $t['unread'] > 0 ? ' is-unread' : '' ?>">
          <img src="<?= e($t['image']) ?>" alt="">
          <span class="tz-chatrow__txt">
            <b><?= e($t['product']) ?></b>
            <small><?= $t['sender'] === 'user' ? 'Anda: ' : '' ?><?= e($t['preview']) ?></small>
          </span>
          <span class="tz-chatrow__meta"><time><?= e($t['time']) ?></time><?php if ($t['unread'] > 0): ?><i><?= $t['unread'] > 9 ? '9+' : (int) $t['unread'] ?></i><?php endif; ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

</main>
<?php if ($ready && $thread): ?><script src="<?= e($base) ?>/assets/js/chat.js?v=<?= @filemtime(__DIR__ . '/assets/js/chat.js') ?: 1 ?>"></script><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
