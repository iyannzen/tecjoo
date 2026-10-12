<?php
/**
 * TechZone Admin - Verifikasi Pembayaran
 * Admin dapat melihat seluruh pembayaran dan mengonfirmasi pembayaran
 * QRIS/Transfer yang berstatus "Menunggu Verifikasi". Setelah dikonfirmasi:
 *   payment.status         -> paid
 *   order.payment_status   -> paid
 *   order.order_status     -> processing
 * Semua perubahan dilakukan lewat backend PHP (bukan parameter URL/status dari klien).
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/notification_helpers.php';
require_once __DIR__ . '/../includes/automation_helpers.php';
require_once __DIR__ . '/../config/payment.php';

$admin = currentAdmin();
$pdo   = getDbConnection();

// ---------------------------------------------------------------------
// Proses konfirmasi / tolak pembayaran
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $formAction = $_POST['form_action'] ?? '';
    $paymentId  = (int) ($_POST['payment_id'] ?? 0);

    $paymentStmt = $pdo->prepare('SELECT * FROM payments WHERE id = :id LIMIT 1');
    $paymentStmt->execute([':id' => $paymentId]);
    $payment = $paymentStmt->fetch();

    if (!$payment) {
        setFlashMessage('error', 'Data pembayaran tidak ditemukan.');
        redirectTo('admin/payments.php');
    }

    if ($formAction === 'confirm') {
        if ($payment['status'] !== 'waiting_verification') {
            setFlashMessage('error', 'Pembayaran ini tidak dalam status menunggu verifikasi.');
            redirectTo('admin/payments.php');
        }

        try {
            tzEnsureOrderHistoryTable($pdo);
            $pdo->beginTransaction();

            $pdo->prepare("UPDATE payments SET status = 'paid', verified_by = :admin_id, verified_at = NOW(), updated_at = NOW() WHERE id = :id")
                ->execute([':admin_id' => $admin['id'], ':id' => $paymentId]);

            $pdo->prepare("UPDATE orders SET payment_status = 'paid', order_status = 'processing', updated_at = NOW() WHERE id = :id")
                ->execute([':id' => $payment['order_id']]);
            recordOrderStatus($pdo, (int) $payment['order_id'], 'processing', 'Pembayaran diverifikasi admin');

            $pdo->commit();

            $orderRow = $pdo->prepare('SELECT order_number, user_id FROM orders WHERE id = :id');
            $orderRow->execute([':id' => $payment['order_id']]);
            $orderInfo = $orderRow->fetch();
            if ($orderInfo) {
                tzMarkOrderNotificationsRead($pdo, (int) $admin['id'], $orderInfo['order_number']);
                createNotification($pdo, (int) $orderInfo['user_id'], 'Pembayaran Dikonfirmasi',
                    'Pembayaran untuk pesanan ' . $orderInfo['order_number'] . ' telah diverifikasi. Pesanan Anda kini diproses.', 'payment');
            }

            logActivity($admin['id'], 'confirm_payment', 'Admin mengonfirmasi pembayaran #' . $payment['transaction_id']);
            setFlashMessage('success', 'Pembayaran berhasil dikonfirmasi. Pesanan kini berstatus Diproses.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[TechZone][Confirm Payment Error] ' . $e->getMessage());
            setFlashMessage('error', 'Terjadi kesalahan saat mengonfirmasi pembayaran.');
        }
    } elseif ($formAction === 'reject') {
        if ($payment['status'] !== 'waiting_verification') {
            setFlashMessage('error', 'Pembayaran ini tidak dalam status menunggu verifikasi.');
            redirectTo('admin/payments.php');
        }

        try {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE payments SET status = 'failed', verified_by = :admin_id, verified_at = NOW(), updated_at = NOW() WHERE id = :id")
                ->execute([':admin_id' => $admin['id'], ':id' => $paymentId]);
            $pdo->prepare("UPDATE orders SET payment_status = 'failed', updated_at = NOW() WHERE id = :id")
                ->execute([':id' => $payment['order_id']]);
            $pdo->commit();

            $orderRow = $pdo->prepare('SELECT order_number, user_id FROM orders WHERE id = :id');
            $orderRow->execute([':id' => $payment['order_id']]);
            $orderInfo = $orderRow->fetch();
            if ($orderInfo) {
                tzMarkOrderNotificationsRead($pdo, (int) $admin['id'], $orderInfo['order_number']);
                createNotification($pdo, (int) $orderInfo['user_id'], 'Pembayaran Ditolak',
                    'Pembayaran untuk pesanan ' . $orderInfo['order_number'] . ' ditolak/gagal. Silakan hubungi kami jika ini kesalahan.', 'payment');
            }

            logActivity($admin['id'], 'reject_payment', 'Admin menolak pembayaran #' . $payment['transaction_id']);
            setFlashMessage('success', 'Pembayaran ditandai gagal/ditolak.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('[TechZone][Reject Payment Error] ' . $e->getMessage());
            setFlashMessage('error', 'Terjadi kesalahan saat menolak pembayaran.');
        }
    }

    redirectTo('admin/payments.php');
}

// ---------------------------------------------------------------------
// Filter & daftar pembayaran
// ---------------------------------------------------------------------
$statusFilter = $_GET['status'] ?? 'waiting_verification';
$where  = [];
$params = [];

if ($statusFilter !== '' && in_array($statusFilter, getValidPaymentStatuses(), true)) {
    $where[] = 'p.status = :status';
    $params[':status'] = $statusFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare(
    "SELECT p.*, o.order_number, o.total AS order_total, u.name AS user_name, u.email AS user_email
     FROM payments p
     JOIN orders o ON o.id = p.order_id
     JOIN users u ON u.id = o.user_id
     $whereSql
     ORDER BY p.created_at DESC
     LIMIT 100"
);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$pageTitle = 'Verifikasi Pembayaran | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>

    <main class="p-6">
      <h1 class="reveal text-2xl font-extrabold text-navy-900 mb-1">Verifikasi Pembayaran</h1>
      <p class="text-sm text-gray-500 mb-6">Konfirmasi pembayaran QRIS &amp; Transfer Bank yang diklaim oleh pelanggan.</p>

      <div class="flex flex-wrap gap-2 mb-5">
        <?php
        $tabs = [
            'waiting_verification' => 'Menunggu Verifikasi',
            'paid'                 => 'Lunas',
            'failed'               => 'Ditolak/Gagal',
            'expired'              => 'Kadaluarsa',
            'cod'                  => 'COD',
            ''                     => 'Semua',
        ];
        foreach ($tabs as $key => $label):
        ?>
          <a href="payments.php<?= $key !== '' ? '?status=' . $key : '?status=' ?>"
             class="text-xs font-medium px-3 py-1.5 rounded-full transition <?= $statusFilter === $key ? 'bg-navy-900 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-cyan-300' ?>">
            <?= e($label) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if (empty($payments)): ?>
        <div class="bg-white border border-gray-100 rounded-xl p-12 text-center text-gray-500 text-sm shadow-card transition-shadow hover:shadow-lg">
          Tidak ada data pembayaran untuk filter ini.
        </div>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($payments as $p): ?>
          <div class="bg-white border border-gray-100 rounded-xl p-5 shadow-card transition-shadow hover:shadow-lg">
            <div class="flex flex-wrap items-start justify-between gap-4">
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <p class="text-sm font-bold text-navy-900"><?= e($p['order_number']) ?></p>
                  <span class="text-[10px] uppercase font-bold text-gray-400 bg-gray-100 px-2 py-0.5 rounded"><?= e($p['payment_method']) ?></span>
                </div>
                <p class="text-xs text-gray-500"><?= e($p['user_name']) ?> &middot; <?= e($p['user_email']) ?></p>
                <p class="text-xs text-gray-400 mt-1">Transaksi: <?= e($p['transaction_id']) ?> &middot; <?= e(formatTanggalIndo($p['created_at'], true)) ?></p>

                <?php if ($p['payment_proof']): ?>
                  <a href="<?= e(techzoneBaseUrl() . '/uploads/payments/' . $p['payment_proof']) ?>" target="_blank"
                     class="inline-block mt-2 text-xs text-cyan-600 hover:underline"><i class="fa-solid fa-paperclip mr-1"></i>Lihat Bukti Transfer</a>
                <?php endif; ?>
              </div>

              <div class="text-right shrink-0">
                <p class="text-base font-extrabold text-navy-900 mb-2"><?= e(formatRupiah($p['amount'])) ?></p>
                <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700"><?= e(paymentStatusLabel($p['status'], $p['payment_method'])) ?></span>

                <?php if ($p['status'] === 'waiting_verification'): ?>
                <div class="flex gap-2 mt-3">
                  <form method="POST" onsubmit="return confirm('Konfirmasi pembayaran ini sebagai Lunas?');">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_action" value="confirm">
                    <input type="hidden" name="payment_id" value="<?= (int) $p['id'] ?>">
                    <button type="submit" class="bg-emerald-500 hover:bg-emerald-400 text-white text-xs font-semibold px-3 py-2 rounded-lg transition">
                      <i class="fa-solid fa-check mr-1"></i>Konfirmasi
                    </button>
                  </form>
                  <form method="POST" onsubmit="return confirm('Tolak/gagalkan pembayaran ini?');">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_action" value="reject">
                    <input type="hidden" name="payment_id" value="<?= (int) $p['id'] ?>">
                    <button type="submit" class="bg-white border border-red-200 text-red-600 hover:bg-red-50 text-xs font-semibold px-3 py-2 rounded-lg transition">
                      Tolak
                    </button>
                  </form>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>
</body>
</html>