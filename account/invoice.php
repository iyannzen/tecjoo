<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/payment.php';

requireUserLogin();
$userId = (int) $_SESSION['user_id'];
$pdo    = getDbConnection();
$user   = currentUser();

$orderNumber = trim($_GET['order'] ?? '');
$orderStmt   = $pdo->prepare('SELECT * FROM orders WHERE order_number = :num AND user_id = :uid LIMIT 1');
$orderStmt->execute([':num' => $orderNumber, ':uid' => $userId]);
$order = $orderStmt->fetch();

if (!$order) {
    setFlashMessage('error', 'Pesanan tidak ditemukan.');
    redirectTo('account/orders.php');
}

$itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :id');
$itemsStmt->execute([':id' => $order['id']]);
$orderItems = $itemsStmt->fetchAll();

$storeName    = getSetting('store_name', 'TechZone');
$storeAddress = getSetting('store_address', '');
$storeEmail   = getSetting('store_email', '');
$storeWhatsapp = getSetting('store_whatsapp', '');

$invoiceNumber = 'INV/' . date('Ymd', strtotime($order['created_at'])) . '/' . $order['order_number'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Invoice <?= e($order['order_number']) ?> | TechZone</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="<?= e(techzoneBaseUrl()) ?>/assets/css/tailwind.static.css">
<style>
  @media print {
    .no-print { display: none !important; }
    body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
  }
</style>
</head>
<body class="bg-gray-100 font-sans">
  <div class="max-w-3xl mx-auto py-8 px-4">

    <div class="no-print flex justify-end mb-4 gap-2">
      <button onclick="window.print()" class="bg-navy-900 text-white text-sm font-semibold px-5 py-2.5 rounded-lg hover:opacity-90" style="background:#0a0a0f;">
        <i class="fa-solid fa-print"></i> Cetak / Simpan sebagai PDF
      </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-8">
      <div class="flex justify-between items-start border-b border-gray-200 pb-6 mb-6">
        <div>
          <h1 class="text-2xl font-extrabold" style="color:#0a0a0f;">Tech<span style="color:#9c7625;">Zone</span></h1>
          <p class="text-xs text-gray-500 mt-1"><?= e($storeAddress) ?></p>
          <p class="text-xs text-gray-500"><?= e($storeEmail) ?> &middot; <?= e($storeWhatsapp) ?></p>
        </div>
        <div class="text-right">
          <p class="text-lg font-bold text-gray-800">INVOICE</p>
          <p class="text-xs text-gray-500 mt-1">No: <?= e($invoiceNumber) ?></p>
          <p class="text-xs text-gray-500">Order: <?= e($order['order_number']) ?></p>
          <p class="text-xs text-gray-500">Tanggal: <?= e(formatTanggalIndo($order['created_at'])) ?></p>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-6 mb-8 text-sm">
        <div>
          <p class="text-xs font-bold text-gray-400 uppercase mb-1">Ditagihkan Kepada</p>
          <p class="font-semibold text-gray-800"><?= e($order['recipient_name']) ?></p>
          <p class="text-gray-500"><?= e($order['recipient_phone']) ?></p>
          <p class="text-gray-500"><?= e($order['shipping_address']) ?>, <?= e($order['shipping_city']) ?>, <?= e($order['shipping_province']) ?> <?= e($order['shipping_postal_code']) ?></p>
        </div>
        <div class="text-right">
          <p class="text-xs font-bold text-gray-400 uppercase mb-1">Detail Pembayaran</p>
          <p class="text-gray-500">Metode: <span class="font-semibold text-gray-800 uppercase"><?= e($order['payment_method']) ?></span></p>
          <p class="text-gray-500">Status Pembayaran: <span class="font-semibold text-gray-800"><?= e(paymentStatusLabel($order['payment_status'], $order['payment_method'])) ?></span></p>
          <p class="text-gray-500">Status Pesanan: <span class="font-semibold text-gray-800"><?= e(orderStatusLabel($order['order_status'])) ?></span></p>
        </div>
      </div>

      <table class="w-full text-sm mb-6">
        <thead>
          <tr class="border-b-2 border-gray-800 text-left text-xs uppercase text-gray-500">
            <th class="py-2">Produk</th>
            <th class="py-2 text-center">SKU</th>
            <th class="py-2 text-center">Qty</th>
            <th class="py-2 text-right">Harga</th>
            <th class="py-2 text-right">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orderItems as $item): ?>
          <tr class="border-b border-gray-100">
            <td class="py-2.5"><?= e($item['product_name']) ?></td>
            <td class="py-2.5 text-center text-gray-500"><?= e($item['sku']) ?></td>
            <td class="py-2.5 text-center"><?= (int) $item['quantity'] ?></td>
            <td class="py-2.5 text-right"><?= e(formatRupiah($item['price'])) ?></td>
            <td class="py-2.5 text-right font-medium"><?= e(formatRupiah($item['subtotal'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="flex justify-end">
        <div class="w-64 text-sm space-y-1.5">
          <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span class="font-medium"><?= e(formatRupiah($order['subtotal'])) ?></span></div>
          <div class="flex justify-between"><span class="text-gray-500">Ongkos Kirim</span><span class="font-medium"><?= e(formatRupiah($order['shipping_cost'])) ?></span></div>
          <?php if ((float) $order['discount'] > 0): ?>
            <div class="flex justify-between"><span class="text-gray-500">Diskon<?= $order['coupon_code'] ? ' (' . e($order['coupon_code']) . ')' : '' ?></span><span class="font-medium">-<?= e(formatRupiah($order['discount'])) ?></span></div>
          <?php endif; ?>
          <div class="flex justify-between border-t-2 border-gray-800 pt-2 mt-2 text-base font-extrabold">
            <span>Total</span><span><?= e(formatRupiah($order['total'])) ?></span>
          </div>
        </div>
      </div>

      <p class="text-center text-xs text-gray-400 mt-10 pt-6 border-t border-gray-100">
        Terima kasih telah berbelanja di TechZone &middot; Dokumen ini dibuat otomatis oleh sistem dan sah tanpa tanda tangan basah.
      </p>
    </div>
  </div>
</body>
</html>
