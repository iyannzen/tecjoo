<?php
/**
 * TechZone Admin - Chat dengan pembeli (per produk)
 * Desktop: daftar percakapan di kiri, ruang chat di kanan. Layar kecil: daftar ATAU ruang chat.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/chat_helpers.php';

$admin = currentAdmin();
$pdo   = getDbConnection();
$base  = techzoneBaseUrl();
$ready = tzChatReady($pdo);

$thread = null;
if ($ready && isset($_GET['thread'])) {
    $thread = tzChatGetThread($pdo, (int) $_GET['thread']);
    if (!$thread) {
        setFlashMessage('error', 'Percakapan tidak ditemukan.');
        redirectTo('admin/chat.php');
    }
}

$pageTitle = 'Chat | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>

  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>

    <main class="p-3 sm:p-6">
      <div class="mb-4">
        <h1 class="text-xl font-extrabold text-navy-900">Chat Pembeli</h1>
        <p class="text-xs text-gray-500 mt-1">Pertanyaan pembeli tentang produk. Setiap percakapan terhubung ke satu produk.</p>
      </div>

      <?php if (!$ready): ?>
        <div class="bg-white border border-gray-100 rounded-2xl p-10 text-center shadow-card">
          <i class="fa-regular fa-comments text-3xl text-gray-300 mb-3"></i>
          <p class="text-sm text-gray-600">Tabel chat belum bisa dibuat otomatis. Jalankan <code>database/migration_chat.sql</code> di database Anda, lalu muat ulang halaman ini.</p>
        </div>
      <?php else: ?>
      <div class="tz-chatsplit<?= $thread ? ' has-thread' : '' ?>">
        <aside class="tz-chatpane">
          <div class="tz-chatpane__search"><i class="fa-solid fa-magnifying-glass"></i><input type="text" id="tz-chatsearch" placeholder="Cari pembeli atau produk…" autocomplete="off"></div>
          <div class="tz-chatlist" id="tz-chatlist" data-api="<?= e($base) ?>/api/chat.php?scope=admin" data-active="<?= $thread ? (int) $thread['id'] : '' ?>">
            <p class="tz-chatlist__empty">Memuat…</p>
          </div>
        </aside>

        <section class="tz-chatmain">
          <?php if ($thread): $prod = $thread['product']; ?>
          <div class="tz-chat tz-chat--admin" id="tz-chat" data-role="admin" data-thread="<?= (int) $thread['id'] ?>"
               data-api="<?= e($base) ?>/api/chat.php?scope=admin" data-csrf="<?= e(csrfToken()) ?>" data-max="<?= TZ_CHAT_MAX_LEN ?>">
            <header class="tz-chat__head">
              <a class="tz-chat__back tz-chat__back--list" href="<?= e($base) ?>/admin/chat.php" aria-label="Kembali ke daftar"><i class="fa-solid fa-arrow-left"></i></a>
              <span class="tz-chat__avatar"><?= e(strtoupper(mb_substr($thread['user_name'] ?? 'P', 0, 1))) ?></span>
              <div class="tz-chat__who"><b><?= e($thread['user_name'] ?? 'Pengguna') ?></b><small><?= e($thread['user_email'] ?? '') ?></small></div>
            </header>

            <?php if ($prod): ?>
            <a class="tz-chat__aboutprod" href="<?= e($base) ?>/admin/product-edit.php?id=<?= (int) $prod['id'] ?>">
              <img src="<?= e($prod['image']) ?>" alt="">
              <span class="tz-chat__prodtxt"><small>Menanyakan produk</small><b><?= e($prod['name']) ?></b><span><?= e($prod['price']) ?></span></span>
              <i class="fa-solid fa-chevron-right"></i>
            </a>
            <?php endif; ?>

            <div class="tz-chat__msgs" id="tz-chat-msgs" aria-live="polite"><p class="tz-chat__note">Memuat percakapan…</p></div>

            <form class="tz-chat__form" id="tz-chat-form" autocomplete="off">
              <textarea id="tz-chat-input" rows="1" maxlength="<?= TZ_CHAT_MAX_LEN ?>" placeholder="Tulis balasan…" aria-label="Tulis balasan" enterkeyhint="send"></textarea>
              <button type="submit" class="tz-chat__send" aria-label="Kirim balasan"><i class="fa-solid fa-paper-plane"></i></button>
            </form>
          </div>
          <?php else: ?>
          <div class="tz-chatmain__empty"><i class="fa-regular fa-comments"></i><p>Pilih percakapan di sebelah kiri untuk mulai membalas.</p></div>
          <?php endif; ?>
        </section>
      </div>
      <script src="<?= e($base) ?>/assets/js/chat.js?v=<?= @filemtime(__DIR__ . '/../assets/js/chat.js') ?: 1 ?>"></script>
      <?php endif; ?>
    </main>
  </div>
</div>
</body>
</html>
