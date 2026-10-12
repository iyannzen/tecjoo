<?php
/**
 * TechZone Admin - Detail & Kelola Pesanan
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/automation_helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/notification_helpers.php';
require_once __DIR__ . '/../config/payment.php';

$admin = currentAdmin();
$pdo   = getDbConnection();

$orderNumber = trim($_GET['order'] ?? '');
$orderStmt   = $pdo->prepare('SELECT * FROM orders WHERE order_number = :num LIMIT 1');
$orderStmt->execute([':num' => $orderNumber]);
$order = $orderStmt->fetch();

if (!$order) {
    setFlashMessage('error', 'Pesanan tidak ditemukan.');
    redirectTo('admin/orders.php');
}

// Admin sudah membuka pesanan ini -> notifikasi terkait dianggap sudah dilihat.
tzMarkOrderNotificationsRead($pdo, (int) $admin['id'], $orderNumber);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);

    $newOrderStatus = $_POST['order_status'] ?? $order['order_status'];
    $courier        = trim($_POST['courier'] ?? '');
    $trackingNumber = trim($_POST['tracking_number'] ?? '');

    if (!in_array($newOrderStatus, getValidOrderStatuses(), true)) {
        setFlashMessage('error', 'Status pesanan tidak valid.');
        redirectTo('admin/order-detail.php?order=' . urlencode($orderNumber));
    }

    $newPaymentStatus = $order['payment_status'];
    // Aturan khusus COD: saat admin menandai pesanan Selesai, status pembayaran otomatis
    // menjadi "paid" yang ditampilkan sebagai "Dibayar di Tempat" (lihat paymentStatusLabel()).
    if ($order['payment_method'] === 'cod' && $newOrderStatus === 'completed' && $order['payment_status'] === 'cod') {
        $newPaymentStatus = 'paid';
    }

    $stmt = $pdo->prepare(
        'UPDATE orders SET order_status = :status, payment_status = :pstatus, courier = :courier,
         tracking_number = :resi, updated_at = NOW() WHERE id = :id'
    );
    $stmt->execute([
        ':status' => $newOrderStatus, ':pstatus' => $newPaymentStatus, ':courier' => $courier ?: null,
        ':resi' => $trackingNumber ?: null, ':id' => $order['id'],
    ]);

    if ($newOrderStatus !== $order['order_status']) {
        tzEnsureOrderHistoryTable($pdo);
        recordOrderStatus($pdo, (int) $order['id'], $newOrderStatus, $trackingNumber !== '' ? 'Resi: ' . $trackingNumber . ($courier !== '' ? ' (' . $courier . ')' : '') : null);
    }

    logActivity($admin['id'], 'update_order_status', 'Mengubah status order ' . $orderNumber . ' menjadi ' . $newOrderStatus);

    if (in_array($newOrderStatus, ['shipped', 'completed', 'cancelled', 'returned', 'refund_requested'], true)) {
        $statusNotifTitles = [
            'shipped'          => 'Pesanan Dikirim',
            'completed'        => 'Pesanan Selesai',
            'cancelled'        => 'Pesanan Dibatalkan',
            'returned'         => 'Dana Dikembalikan',
            'refund_requested' => 'Pengembalian Dana Diajukan',
        ];
        $trackingInfo = $trackingNumber !== '' ? ' Nomor resi: ' . $trackingNumber . ' (' . $courier . ').' : '';
        createNotification(
            $pdo, (int) $order['user_id'], $statusNotifTitles[$newOrderStatus],
            'Pesanan ' . $orderNumber . ' kini berstatus "' . orderStatusLabel($newOrderStatus) . '".' . $trackingInfo,
            'order'
        );
    }

    setFlashMessage('success', 'Pesanan berhasil diperbarui.');
    redirectTo('admin/order-detail.php?order=' . urlencode($orderNumber));
}

$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :id');
$itemsStmt->execute([':id' => $order['id']]);
$orderItems = $itemsStmt->fetchAll();

$paymentStmt = $pdo->prepare('SELECT * FROM payments WHERE order_id = :id ORDER BY id DESC LIMIT 1');
$paymentStmt->execute([':id' => $order['id']]);
$payment = $paymentStmt->fetch();

$userStmt = $pdo->prepare('SELECT name, email, phone FROM users WHERE id = :id LIMIT 1');
$userStmt->execute([':id' => $order['user_id']]);
$orderUser = $userStmt->fetch();

$pageTitle = 'Detail Pesanan ' . e($orderNumber) . ' | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>
    <main class="p-6">
      <nav class="text-xs text-gray-500 mb-4"><a href="orders.php" class="hover:text-cyan-600">Kelola Pesanan</a> / <span class="text-navy-900 font-medium"><?= e($orderNumber) ?></span></nav>

      <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="reveal text-2xl font-extrabold text-navy-900"><?= e($orderNumber) ?></h1>
        <div class="flex gap-2">
          <span class="text-xs font-semibold px-3 py-1.5 rounded-full <?= orderStatusBadgeClass($order['order_status']) ?>"><?= e(orderStatusLabel($order['order_status'])) ?></span>
          <span class="text-xs font-semibold px-3 py-1.5 rounded-full bg-amber-50 text-amber-700"><?= e(paymentStatusLabel($order['payment_status'], $order['payment_method'])) ?></span>
          <?php if (!empty($order['payment_channel'])): ?>
            <span class="text-xs font-semibold px-3 py-1.5 rounded-full bg-blue-50 text-blue-700"><i class="fa-solid fa-wallet mr-1"></i><?= e($order['payment_channel']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($order['order_status'] === 'refund_requested'): ?>
        <div class="reveal bg-orange-50 border border-orange-200 text-orange-800 text-sm rounded-lg p-4 mb-6 flex items-center justify-between flex-wrap gap-3">
          <span><i class="fa-solid fa-hand-holding-dollar mr-2"></i>Pembeli mengajukan pengembalian dana.<?php if (!empty($order['refund_reason'])): ?> Alasan: <strong><?= e($order['refund_reason']) ?></strong><?php endif; ?></span>
          <span class="text-xs text-orange-600">Ubah status ke "Dana Dikembalikan" di bawah untuk menyetujui, atau kembalikan ke status sebelumnya untuk menolak.</span>
        </div>
      <?php elseif ($order['order_status'] === 'cancelled' && !empty($order['cancel_reason'])): ?>
        <div class="reveal bg-gray-50 border border-gray-200 text-gray-600 text-sm rounded-lg p-4 mb-6">
          <i class="fa-solid fa-ban mr-2"></i>Dibatalkan oleh pembeli. Alasan: <strong><?= e($order['cancel_reason']) ?></strong>
        </div>
      <?php endif; ?>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-card transition-shadow hover:shadow-lg">
            <h2 class="text-sm font-bold text-navy-900 mb-4">Produk Dipesan</h2>
            <?php foreach ($orderItems as $item): ?>
            <div class="flex items-center justify-between text-sm border-b border-gray-50 pb-3 mb-3 last:border-0 last:pb-0 last:mb-0">
              <div>
                <p class="font-medium text-navy-900"><?= e($item['product_name']) ?></p>
                <p class="text-xs text-gray-400">SKU: <?= e($item['sku']) ?> &middot; <?= (int) $item['quantity'] ?> x <?= e(formatRupiah($item['price'])) ?></p>
              </div>
              <p class="font-semibold text-navy-900"><?= e(formatRupiah($item['subtotal'])) ?></p>
            </div>
            <?php endforeach; ?>
          </div>

          <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-card transition-shadow hover:shadow-lg">
            <h2 class="text-sm font-bold text-navy-900 mb-3">Pelanggan &amp; Alamat</h2>
            <p class="text-sm text-navy-900 font-medium"><?= e($orderUser['name'] ?? '-') ?> <span class="text-gray-400 font-normal">(<?= e($orderUser['email'] ?? '-') ?>)</span></p>
            <p class="text-sm text-gray-600 mt-2"><?= e($order['recipient_name']) ?> &middot; <?= e($order['recipient_phone']) ?></p>
            <p class="text-sm text-gray-600"><?= e($order['shipping_address']) ?>, <?= e($order['shipping_city']) ?>, <?= e($order['shipping_province']) ?> <?= e($order['shipping_postal_code']) ?></p>
            <?php if ($order['notes']): ?><p class="text-xs text-gray-400 mt-2">Catatan: <?= e($order['notes']) ?></p><?php endif; ?>
          </div>

          <?php if ($payment): ?>
          <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-card transition-shadow hover:shadow-lg">
            <h2 class="text-sm font-bold text-navy-900 mb-3">Info Pembayaran</h2>
            <p class="text-sm text-gray-600">Transaksi: <span class="font-medium text-navy-900"><?= e($payment['transaction_id']) ?></span></p>
            <p class="text-sm text-gray-600">Metode: <span class="font-medium text-navy-900 uppercase"><?= e($payment['payment_method']) ?></span></p>
            <?php if (!empty($order['bank_account_id'])):
              $selectedBankRow = getBankAccountById($pdo, (int) $order['bank_account_id']);
            ?>
              <?php if ($selectedBankRow): ?>
                <div class="mt-3 flex items-center gap-3 bg-blue-50/60 border border-blue-100 rounded-lg p-3">
                  <span class="w-9 h-9 rounded-lg flex items-center justify-center font-extrabold text-[10px] shrink-0 overflow-hidden border border-white" style="background:<?= e($selectedBankRow['logo_bg']) ?>;color:<?= e($selectedBankRow['logo_fg']) ?>;">
                    <?php if (!empty($selectedBankRow['logo_image'])): ?>
                      <img src="<?= e(techzoneBaseUrl() . '/' . ltrim($selectedBankRow['logo_image'], '/')) ?>" class="w-full h-full object-contain p-1" alt="">
                    <?php else: ?>
                      <?= e(mb_strtoupper(mb_substr($selectedBankRow['bank_name'], 0, 4))) ?>
                    <?php endif; ?>
                  </span>
                  <div class="text-xs">
                    <p class="font-semibold text-navy-900"><?= e($selectedBankRow['bank_name']) ?> — <?= e($selectedBankRow['account_number']) ?></p>
                    <p class="text-gray-500">a.n. <?= e($selectedBankRow['account_holder']) ?></p>
                  </div>
                </div>
              <?php endif; ?>
            <?php elseif (!empty($order['payment_channel'])): ?>
              <p class="text-sm text-gray-600">E-Wallet: <span class="font-medium text-navy-900"><?= e($order['payment_channel']) ?></span></p>
            <?php endif; ?>
            <?php if ($payment['payment_proof']): ?>
              <a href="<?= e(techzoneBaseUrl() . '/uploads/payments/' . $payment['payment_proof']) ?>" target="_blank" class="inline-block mt-2 text-xs text-cyan-600 hover:underline"><i class="fa-solid fa-paperclip mr-1"></i>Lihat Bukti Transfer</a>
            <?php endif; ?>
            <?php if ($payment['status'] === 'waiting_verification'): ?>
              <a href="payments.php" class="inline-block mt-3 bg-emerald-500 hover:bg-emerald-400 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">Verifikasi di Halaman Pembayaran &rarr;</a>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>

        <div class="space-y-6">
          <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-card transition-shadow hover:shadow-lg">
            <h2 class="text-sm font-bold text-navy-900 mb-3">Ringkasan</h2>
            <div class="space-y-1.5 text-sm text-gray-600">
              <div class="flex justify-between"><span>Subtotal</span><span class="font-medium text-navy-900"><?= e(formatRupiah($order['subtotal'])) ?></span></div>
              <div class="flex justify-between"><span>Ongkir</span><span class="font-medium text-navy-900"><?= e(formatRupiah($order['shipping_cost'])) ?></span></div>
              <?php if ((float) $order['discount'] > 0): ?><div class="flex justify-between text-emerald-600"><span>Diskon</span><span>-<?= e(formatRupiah($order['discount'])) ?></span></div><?php endif; ?>
            </div>
            <div class="flex justify-between border-t border-gray-100 mt-3 pt-3">
              <span class="text-sm font-semibold text-navy-900">Total</span>
              <span class="text-base font-extrabold text-navy-900"><?= e(formatRupiah($order['total'])) ?></span>
            </div>
          </div>

          <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-card transition-shadow hover:shadow-lg">
            <h2 class="text-sm font-bold text-navy-900 mb-4">Update Status Pesanan</h2>
            <form method="POST" class="space-y-3">
              <?= csrfField() ?>
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Status Pesanan</label>
                <select name="order_status" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
                  <?php foreach (getValidOrderStatuses() as $s): ?>
                    <option value="<?= e($s) ?>" <?= $order['order_status'] === $s ? 'selected' : '' ?>><?= e(orderStatusLabel($s)) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Nama Kurir</label>
                <input type="text" name="courier" value="<?= e($order['courier'] ?? '') ?>" placeholder="JNE, J&T, SiCepat, dll." class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Nomor Resi</label>
                <input type="text" name="tracking_number" value="<?= e($order['tracking_number'] ?? '') ?>" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2">
              </div>
              <button type="submit" class="w-full ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm py-2.5 rounded-lg transition">Simpan Perubahan</button>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
</body>
</html>