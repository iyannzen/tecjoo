<?php
/**
 * TechZone - Proses Checkout (backend)
 *
 * Mengikuti alur wajib:
 *   1. Validasi user & keranjang
 *   2. Ambil harga dari DATABASE (bukan dari frontend)
 *   3. Validasi stok (dengan row-lock untuk mencegah race condition)
 *   4. Validasi kupon (jika ada)
 *   5. Hitung subtotal, ongkir, diskon, total
 *   6. BEGIN TRANSACTION -> buat order, order_items, kurangi stok, buat payment -> COMMIT
 *   7. Jika terjadi error di titik mana pun -> ROLLBACK, tidak ada data setengah jadi.
 *
 * Proteksi double-order: CSRF token + order_token sekali pakai (session) + status server-side.
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/cart_helpers.php';
require_once __DIR__ . '/../includes/notification_helpers.php';
require_once __DIR__ . '/../includes/automation_helpers.php';
require_once __DIR__ . '/../config/payment.php';
require_once __DIR__ . '/../config/midtrans.php';

requireUserLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('checkout.php');
}

verifyCsrfToken($_POST['csrf_token'] ?? null);

$userId = (int) $_SESSION['user_id'];
$pdo    = getDbConnection();

// ---------------------------------------------------------------------
// 1. Proteksi double-submit via order_token sekali pakai.
// ---------------------------------------------------------------------
$submittedToken = $_POST['order_token'] ?? '';
$sessionToken   = $_SESSION['checkout_order_token'] ?? '';

if ($sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
    setFlashMessage('error', 'Sesi checkout tidak valid atau pesanan sudah diproses sebelumnya.');
    redirectTo('cart.php');
}
// Konsumsi token SEKARANG (sebelum proses berat) agar submit ganda tidak lolos keduanya.
unset($_SESSION['checkout_order_token']);

// ---------------------------------------------------------------------
// 2. Validasi input dasar
// ---------------------------------------------------------------------
$paymentMethod  = $_POST['payment_method'] ?? '';
$shippingMethod = $_POST['shipping_method'] ?? 'reguler';
$notes          = trim($_POST['notes'] ?? '');
$addressIdInput = $_POST['address_id'] ?? '';
$walletChoice   = trim($_POST['wallet_choice'] ?? '');
$bankAccountId  = (int) ($_POST['bank_account_id'] ?? 0);

if (!in_array($paymentMethod, getValidPaymentMethods(), true)) {
    setFlashMessage('error', 'Metode pembayaran tidak valid.');
    redirectTo('checkout.php');
}
if (!in_array($shippingMethod, ['reguler', 'express'], true)) {
    setFlashMessage('error', 'Metode pengiriman tidak valid.');
    redirectTo('checkout.php');
}

// Tentukan channel (e-wallet) & rekening bank tujuan yang dipilih user.
// Rekening bank divalidasi terhadap tabel bank_accounts (harus ada & aktif)
// agar tidak ada ID sembarangan yang tersimpan di order.
$paymentChannel   = null;
$selectedBankRow  = null;
if ($paymentMethod === 'qris' && $walletChoice !== '' && isValidPaymentChannel($walletChoice, 'qris')) {
    $paymentChannel = $walletChoice;
} elseif ($paymentMethod === 'transfer' && $bankAccountId > 0) {
    $selectedBankRow = getBankAccountById($pdo, $bankAccountId);
    if (!$selectedBankRow || (int) $selectedBankRow['is_active'] !== 1) {
        setFlashMessage('error', 'Bank tujuan transfer yang dipilih tidak tersedia. Silakan pilih ulang.');
        redirectTo('checkout.php');
    }
    $paymentChannel = $selectedBankRow['bank_name'];
}
if ($paymentMethod === 'transfer' && !$selectedBankRow) {
    setFlashMessage('error', 'Silakan pilih bank tujuan transfer terlebih dahulu.');
    redirectTo('checkout.php');
}

$paymentConfig = getPaymentConfig();
$activeFlag = [
    'qris'     => $paymentConfig['payment_qris_active'] === '1',
    'transfer' => $paymentConfig['payment_transfer_active'] === '1',
    'cod'      => $paymentConfig['payment_cod_active'] === '1',
    'midtrans' => $paymentConfig['payment_midtrans_active'] === '1' && midtransIsConfigured(),
];
if (empty($activeFlag[$paymentMethod])) {
    setFlashMessage('error', 'Metode pembayaran yang dipilih sedang tidak tersedia.');
    redirectTo('checkout.php');
}

$shippingCost = $shippingMethod === 'express'
    ? (float) $paymentConfig['shipping_cost_express']
    : (float) $paymentConfig['shipping_cost_reguler'];

// ---------------------------------------------------------------------
// 3. Resolusi alamat pengiriman (existing atau baru)
// ---------------------------------------------------------------------
$addressSnapshot = null;
$newAddressIdToSave = null;

if ($addressIdInput !== '' && $addressIdInput !== 'new') {
    $addrStmt = $pdo->prepare('SELECT * FROM addresses WHERE id = :id AND user_id = :uid LIMIT 1');
    $addrStmt->execute([':id' => (int) $addressIdInput, ':uid' => $userId]);
    $addr = $addrStmt->fetch();

    if (!$addr) {
        setFlashMessage('error', 'Alamat yang dipilih tidak ditemukan.');
        redirectTo('checkout.php');
    }
    $addressSnapshot = [
        'address_id' => (int) $addr['id'],
        'name'       => $addr['recipient_name'],
        'phone'      => $addr['phone'],
        'address'    => $addr['address'],
        'city'       => $addr['city'],
        'province'   => $addr['province'],
        'postal'     => $addr['postal_code'],
    ];
} else {
    $name     = trim($_POST['new_recipient_name'] ?? '');
    $phone    = trim($_POST['new_phone'] ?? '');
    $address  = trim($_POST['new_address'] ?? '');
    $city     = trim($_POST['new_city'] ?? '');
    $province = trim($_POST['new_province'] ?? '');
    $postal   = trim($_POST['new_postal_code'] ?? '');

    if ($name === '' || $phone === '' || $address === '' || $city === '' || $province === '' || !preg_match('/^[0-9]{5}$/', $postal)) {
        setFlashMessage('error', 'Data alamat baru belum lengkap atau kode pos tidak valid (5 digit).');
        $_SESSION['checkout_order_token'] = bin2hex(random_bytes(24)); // beri token baru agar bisa coba lagi
        redirectTo('checkout.php');
    }

    $addressSnapshot = [
        'address_id' => null, 'name' => $name, 'phone' => $phone, 'address' => $address,
        'city' => $city, 'province' => $province, 'postal' => $postal,
    ];

    if (!empty($_POST['save_new_address'])) {
        $newAddressIdToSave = true; // ditangani setelah validasi cart, di dalam transaksi
    }
}

// ---------------------------------------------------------------------
// 4. Ambil keranjang & validasi tidak kosong
// ---------------------------------------------------------------------
$cartId = getOrCreateCartId($pdo);
$items  = getCartItems($pdo, $cartId);

if (empty($items)) {
    setFlashMessage('error', 'Keranjang Anda kosong.');
    redirectTo('cart.php');
}

// ---------------------------------------------------------------------
// 5. Validasi kupon (re-check terhadap subtotal aktual di bawah)
// ---------------------------------------------------------------------
$appliedCoupon = $_SESSION['applied_coupon'] ?? null;

// ---------------------------------------------------------------------
// 6. TRANSAKSI DATABASE
// ---------------------------------------------------------------------
tzEnsureOrderHistoryTable($pdo); // DDL harus di luar transaksi

try {
    $pdo->beginTransaction();

    $subtotal      = 0.0;
    $orderItemsData = [];

    foreach ($items as $item) {
        // Row-lock produk untuk mencegah race condition stok saat checkout bersamaan.
        $lockStmt = $pdo->prepare('SELECT id, name, sku, price, discount_price, stock, status FROM products WHERE id = :id FOR UPDATE');
        $lockStmt->execute([':id' => $item['product_id']]);
        $liveProduct = $lockStmt->fetch();

        if (!$liveProduct || $liveProduct['status'] !== 'active') {
            throw new RuntimeException('Produk "' . $item['name'] . '" sudah tidak tersedia.');
        }

        $qty = (int) $item['quantity'];
        if ($qty > (int) $liveProduct['stock']) {
            throw new RuntimeException('Jumlah produk melebihi stok yang tersedia untuk "' . $liveProduct['name'] . '".');
        }

        // Harga DIAMBIL DARI DATABASE saat ini, bukan dari cart_items/frontend.
        $livePrice = (!empty($liveProduct['discount_price']) && (float) $liveProduct['discount_price'] > 0)
            ? (float) $liveProduct['discount_price'] : (float) $liveProduct['price'];

        $lineSubtotal = $livePrice * $qty;
        $subtotal    += $lineSubtotal;

        $orderItemsData[] = [
            'product_id' => $liveProduct['id'],
            'name'       => $liveProduct['name'],
            'sku'        => $liveProduct['sku'],
            'price'      => $livePrice,
            'quantity'   => $qty,
            'subtotal'   => $lineSubtotal,
        ];

        // Kurangi stok dengan guard WHERE stock >= qty (mencegah stok negatif walau ada race condition residual).
        $decrementStmt = $pdo->prepare('UPDATE products SET stock = stock - :qty WHERE id = :id AND stock >= :qty2');
        $decrementStmt->execute([':qty' => $qty, ':id' => $liveProduct['id'], ':qty2' => $qty]);
        if ($decrementStmt->rowCount() === 0) {
            throw new RuntimeException('Stok produk "' . $liveProduct['name'] . '" berubah. Silakan coba lagi.');
        }
    }

    // Validasi ulang kupon terhadap subtotal aktual.
    $discountAmount = 0.0;
    $couponRow      = null;
    if ($appliedCoupon) {
        $couponStmt = $pdo->prepare("SELECT * FROM coupons WHERE id = :id AND status = 'active' LIMIT 1 FOR UPDATE");
        $couponStmt->execute([':id' => $appliedCoupon['id']]);
        $couponRow = $couponStmt->fetch();

        $today = date('Y-m-d');
        if ($couponRow
            && (!$couponRow['start_date'] || $couponRow['start_date'] <= $today)
            && (!$couponRow['end_date'] || $couponRow['end_date'] >= $today)
            && ($couponRow['usage_limit'] === null || (int) $couponRow['used_count'] < (int) $couponRow['usage_limit'])
            && $subtotal >= (float) $couponRow['minimum_purchase']
        ) {
            $discountAmount = $couponRow['type'] === 'percentage'
                ? $subtotal * ((float) $couponRow['value'] / 100)
                : (float) $couponRow['value'];
            if (!empty($couponRow['maximum_discount']) && (float) $couponRow['maximum_discount'] > 0) {
                $discountAmount = min($discountAmount, (float) $couponRow['maximum_discount']);
            }
            $discountAmount = min($discountAmount, $subtotal);
        } else {
            $couponRow = null; // kupon tidak lagi valid, abaikan diskon.
        }
    }

    $total = max(0, $subtotal + $shippingCost - $discountAmount);

    // Simpan alamat baru jika diminta (setelah validasi cart berhasil).
    if ($newAddressIdToSave) {
        $existingCountStmt = $pdo->prepare('SELECT COUNT(*) FROM addresses WHERE user_id = :uid');
        $existingCountStmt->execute([':uid' => $userId]);
        $isFirst = ((int) $existingCountStmt->fetchColumn()) === 0;

        $insertAddr = $pdo->prepare(
            'INSERT INTO addresses (user_id, recipient_name, phone, address, city, province, postal_code, is_default, created_at, updated_at)
             VALUES (:uid, :name, :phone, :address, :city, :province, :postal, :is_default, NOW(), NOW())'
        );
        $insertAddr->execute([
            ':uid' => $userId, ':name' => $addressSnapshot['name'], ':phone' => $addressSnapshot['phone'],
            ':address' => $addressSnapshot['address'], ':city' => $addressSnapshot['city'],
            ':province' => $addressSnapshot['province'], ':postal' => $addressSnapshot['postal'],
            ':is_default' => $isFirst ? 1 : 0,
        ]);
        $addressSnapshot['address_id'] = (int) $pdo->lastInsertId();
    }

    // Generate nomor order unik: TZ-YYYYMMDD-XXXXX
    $orderNumber = generateOrderNumber($pdo);

    $isCod = $paymentMethod === 'cod';
    $orderStatus   = $isCod ? 'processing' : 'waiting_payment';
    $paymentStatus = $isCod ? 'cod' : 'unpaid';

    $orderStmt = $pdo->prepare(
        'INSERT INTO orders
            (order_number, user_id, address_id, recipient_name, recipient_phone, shipping_address, shipping_city,
             shipping_province, shipping_postal_code, shipping_method, subtotal, shipping_cost, discount, total,
             coupon_code, payment_method, payment_channel, bank_account_id, payment_status, order_status, notes, order_token, created_at, updated_at)
         VALUES
            (:order_number, :user_id, :address_id, :recipient_name, :recipient_phone, :shipping_address, :shipping_city,
             :shipping_province, :shipping_postal_code, :shipping_method, :subtotal, :shipping_cost, :discount, :total,
             :coupon_code, :payment_method, :payment_channel, :bank_account_id, :payment_status, :order_status, :notes, :order_token, NOW(), NOW())'
    );
    $orderStmt->execute([
        ':order_number'         => $orderNumber,
        ':user_id'              => $userId,
        ':address_id'           => $addressSnapshot['address_id'],
        ':recipient_name'       => $addressSnapshot['name'],
        ':recipient_phone'      => $addressSnapshot['phone'],
        ':shipping_address'     => $addressSnapshot['address'],
        ':shipping_city'        => $addressSnapshot['city'],
        ':shipping_province'    => $addressSnapshot['province'],
        ':shipping_postal_code' => $addressSnapshot['postal'],
        ':shipping_method'      => $shippingMethod,
        ':subtotal'             => $subtotal,
        ':shipping_cost'        => $shippingCost,
        ':discount'             => $discountAmount,
        ':total'                => $total,
        ':coupon_code'          => $couponRow['code'] ?? null,
        ':payment_method'       => $paymentMethod,
        ':payment_channel'      => $paymentChannel,
        ':bank_account_id'      => $selectedBankRow['id'] ?? null,
        ':payment_status'       => $paymentStatus,
        ':order_status'         => $orderStatus,
        ':notes'                => $notes !== '' ? $notes : null,
        ':order_token'          => bin2hex(random_bytes(16)), // token unik permanen milik order (bukan session token)
    ]);
    $orderId = (int) $pdo->lastInsertId();
    recordOrderStatus($pdo, $orderId, $orderStatus, 'Pesanan dibuat');

    // Simpan snapshot item pesanan.
    $itemStmt = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, product_name, sku, price, quantity, subtotal)
         VALUES (:order_id, :product_id, :name, :sku, :price, :qty, :subtotal)'
    );
    foreach ($orderItemsData as $oi) {
        $itemStmt->execute([
            ':order_id' => $orderId, ':product_id' => $oi['product_id'], ':name' => $oi['name'],
            ':sku' => $oi['sku'], ':price' => $oi['price'], ':qty' => $oi['quantity'], ':subtotal' => $oi['subtotal'],
        ]);
    }

    // Catat penggunaan kupon.
    if ($couponRow) {
        $pdo->prepare(
            'INSERT INTO coupon_usages (coupon_id, user_id, order_id, discount_amount, created_at)
             VALUES (:coupon_id, :user_id, :order_id, :discount, NOW())'
        )->execute([
            ':coupon_id' => $couponRow['id'], ':user_id' => $userId, ':order_id' => $orderId, ':discount' => $discountAmount,
        ]);
        $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = :id')->execute([':id' => $couponRow['id']]);
    }

    // Buat record payment.
    $transactionId = generateTransactionId($orderNumber);
    $qrisDuration   = (int) $paymentConfig['qris_payment_duration_minutes'];
    $expiresAt      = $paymentMethod === 'qris' ? date('Y-m-d H:i:s', strtotime('+' . $qrisDuration . ' minutes')) : null;

    $pdo->prepare(
        'INSERT INTO payments (order_id, transaction_id, payment_method, amount, status, expires_at, created_at, updated_at)
         VALUES (:order_id, :txn, :method, :amount, :status, :expires_at, NOW(), NOW())'
    )->execute([
        ':order_id' => $orderId, ':txn' => $transactionId, ':method' => $paymentMethod,
        ':amount' => $total, ':status' => $paymentStatus, ':expires_at' => $expiresAt,
    ]);

    // Kosongkan keranjang setelah order berhasil dibuat.
    $pdo->prepare('DELETE FROM cart_items WHERE cart_id = :cid')->execute([':cid' => $cartId]);

    $pdo->commit();

    // Peringatan stok menipis/habis ke admin (setelah commit agar tidak mengganggu transaksi).
    foreach ($orderItemsData as $oi) {
        if (!empty($oi['product_id'])) {
            alertLowStock($pdo, (int) $oi['product_id']);
        }
    }
    notifyAdmins($pdo, 'Pesanan Baru', 'Pesanan ' . $orderNumber . ' senilai ' . formatRupiah($total) . ' masuk (' . strtoupper($paymentMethod) . ').', 'order');

    unset($_SESSION['applied_coupon']);

    logActivity($userId, 'checkout', 'Membuat pesanan ' . $orderNumber . ' (metode: ' . $paymentMethod . ').');

    $notifMessage = $isCod
        ? 'Pesanan ' . $orderNumber . ' telah dibuat dan sedang diproses (COD).'
        : 'Pesanan ' . $orderNumber . ' telah dibuat. Silakan selesaikan pembayaran Anda.';
    createNotification($pdo, $userId, 'Pesanan Berhasil Dibuat', $notifMessage, 'order');

    setFlashMessage('success', 'Pesanan ' . $orderNumber . ' berhasil dibuat.');
    if ($paymentMethod === 'midtrans') {
        redirectTo('payment/midtrans.php?order=' . urlencode($orderNumber)); // langsung ke pembayaran
    }
    redirectTo('account/order-detail.php?order=' . urlencode($orderNumber));

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[TechZone][Checkout Error] ' . $e->getMessage());

    // Beri token checkout baru agar user bisa mencoba lagi.
    $_SESSION['checkout_order_token'] = bin2hex(random_bytes(24));

    $message = $e instanceof RuntimeException ? $e->getMessage() : 'Terjadi kesalahan saat memproses pesanan. Silakan coba lagi.';
    setFlashMessage('error', $message);
    redirectTo('cart.php');
}
