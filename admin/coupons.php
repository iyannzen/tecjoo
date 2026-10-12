<?php
/**
 * TechZone Admin - Kelola Kupon
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/csrf.php';

$admin = currentAdmin();
$pdo   = getDbConnection();
$errors = [];
$editingCoupon = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? null);
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'toggle_status') {
        $id = (int) ($_POST['coupon_id'] ?? 0);
        $pdo->prepare("UPDATE coupons SET status = IF(status='active','inactive','active'), updated_at = NOW() WHERE id = :id")->execute([':id' => $id]);
        setFlashMessage('success', 'Status kupon berhasil diubah.');
        redirectTo('admin/coupons.php');
    }

    if ($formAction === 'delete') {
        $id = (int) ($_POST['coupon_id'] ?? 0);
        $pdo->prepare('DELETE FROM coupons WHERE id = :id')->execute([':id' => $id]);
        logActivity($admin['id'], 'delete_coupon', 'Menghapus kupon #' . $id);
        setFlashMessage('success', 'Kupon berhasil dihapus.');
        redirectTo('admin/coupons.php');
    }

    if ($formAction === 'save') {
        $couponId  = (int) ($_POST['coupon_id'] ?? 0);
        $code      = strtoupper(trim($_POST['code'] ?? ''));
        $type      = $_POST['type'] === 'fixed' ? 'fixed' : 'percentage';
        $value     = (float) ($_POST['value'] ?? 0);
        $minPurchase = (float) ($_POST['minimum_purchase'] ?? 0);
        $maxDiscount = $_POST['maximum_discount'] !== '' ? (float) $_POST['maximum_discount'] : null;
        $usageLimit  = $_POST['usage_limit'] !== '' ? (int) $_POST['usage_limit'] : null;
        $startDate   = $_POST['start_date'] !== '' ? $_POST['start_date'] : null;
        $endDate     = $_POST['end_date'] !== '' ? $_POST['end_date'] : null;
        $status      = ($_POST['status'] ?? 'active') === 'active' ? 'active' : 'inactive';

        if ($code === '' || !preg_match('/^[A-Z0-9]{3,30}$/', $code)) $errors[] = 'Kode kupon wajib diisi (huruf/angka, maks 30 karakter).';
        if ($value <= 0) $errors[] = 'Nilai kupon harus lebih dari 0.';
        if ($type === 'percentage' && $value > 100) $errors[] = 'Persentase kupon maksimal 100.';

        if (empty($errors)) {
            $dupCheck = $pdo->prepare('SELECT id FROM coupons WHERE code = :code AND id <> :id LIMIT 1');
            $dupCheck->execute([':code' => $code, ':id' => $couponId]);
            if ($dupCheck->fetch()) $errors[] = 'Kode kupon sudah digunakan.';
        }

        if (empty($errors)) {
            if ($couponId > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE coupons SET code=:code, type=:type, value=:value, minimum_purchase=:min, maximum_discount=:max,
                     usage_limit=:limit, start_date=:start, end_date=:end, status=:status, updated_at=NOW() WHERE id=:id'
                );
                $stmt->execute([':code'=>$code, ':type'=>$type, ':value'=>$value, ':min'=>$minPurchase, ':max'=>$maxDiscount, ':limit'=>$usageLimit, ':start'=>$startDate, ':end'=>$endDate, ':status'=>$status, ':id'=>$couponId]);
                setFlashMessage('success', 'Kupon berhasil diperbarui.');
                logActivity($admin['id'], 'update_coupon', 'Memperbarui kupon: ' . $code);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO coupons (code, type, value, minimum_purchase, maximum_discount, usage_limit, used_count, start_date, end_date, status, created_at, updated_at)
                     VALUES (:code, :type, :value, :min, :max, :limit, 0, :start, :end, :status, NOW(), NOW())'
                );
                $stmt->execute([':code'=>$code, ':type'=>$type, ':value'=>$value, ':min'=>$minPurchase, ':max'=>$maxDiscount, ':limit'=>$usageLimit, ':start'=>$startDate, ':end'=>$endDate, ':status'=>$status]);
                setFlashMessage('success', 'Kupon baru berhasil ditambahkan.');
                logActivity($admin['id'], 'create_coupon', 'Menambahkan kupon: ' . $code);
            }
            redirectTo('admin/coupons.php');
        }

        $editingCoupon = compact('couponId', 'code', 'type', 'value', 'minPurchase', 'maxDiscount', 'usageLimit', 'startDate', 'endDate', 'status');
    }
}

if (isset($_GET['edit']) && $editingCoupon === null) {
    $stmt = $pdo->prepare('SELECT * FROM coupons WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => (int) $_GET['edit']]);
    $row = $stmt->fetch();
    if ($row) {
        $editingCoupon = [
            'couponId' => $row['id'], 'code' => $row['code'], 'type' => $row['type'], 'value' => $row['value'],
            'minPurchase' => $row['minimum_purchase'], 'maxDiscount' => $row['maximum_discount'],
            'usageLimit' => $row['usage_limit'], 'startDate' => $row['start_date'], 'endDate' => $row['end_date'], 'status' => $row['status'],
        ];
    }
}
$showForm = isset($_GET['add']) || $editingCoupon !== null;

$coupons = $pdo->query('SELECT * FROM coupons ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Kelola Kupon | TechZone Admin';
require __DIR__ . '/../includes/header.php';
?>
<div class="min-h-screen flex bg-gray-50">
  <?php require __DIR__ . '/_sidebar.php'; ?>
  <div id="techzone-admin-content" class="flex-1">
    <?php require __DIR__ . '/_topbar.php'; ?>
    <main class="p-6">
      <div class="flex items-center justify-between mb-6">
        <h1 class="reveal text-2xl font-extrabold text-navy-900">Kelola Kupon</h1>
        <?php if (!$showForm): ?><a href="coupons.php?add=1" class="ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 text-sm font-semibold px-4 py-2 rounded-lg transition"><i class="fa-solid fa-plus mr-1"></i>Tambah Kupon</a><?php endif; ?>
      </div>

      <?php if ($showForm): ?>
      <div class="bg-white border border-gray-100 rounded-xl p-6 mb-6 max-w-2xl shadow-card transition-shadow hover:shadow-lg">
        <h2 class="text-sm font-bold text-navy-900 mb-4"><?= ($editingCoupon['couponId'] ?? 0) ? 'Edit Kupon' : 'Tambah Kupon' ?></h2>
        <?php if (!empty($errors)): ?>
          <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4"><ul class="list-disc list-inside space-y-1"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
          <?= csrfField() ?>
          <input type="hidden" name="form_action" value="save">
          <input type="hidden" name="coupon_id" value="<?= (int) ($editingCoupon['couponId'] ?? 0) ?>">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Kode Kupon</label>
              <input type="text" name="code" required value="<?= e($editingCoupon['code'] ?? '') ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Jenis</label>
              <select name="type" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
                <option value="percentage" <?= ($editingCoupon['type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Persentase (%)</option>
                <option value="fixed" <?= ($editingCoupon['type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Nominal (Rp)</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Nilai</label>
              <input type="number" name="value" required min="1" value="<?= e((string) ($editingCoupon['value'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Min. Pembelian (Rp)</label>
              <input type="number" name="minimum_purchase" min="0" value="<?= e((string) ($editingCoupon['minPurchase'] ?? '0')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Maks. Diskon (Rp, opsional)</label>
              <input type="number" name="maximum_discount" min="0" value="<?= e((string) ($editingCoupon['maxDiscount'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Batas Penggunaan (opsional)</label>
              <input type="number" name="usage_limit" min="1" value="<?= e((string) ($editingCoupon['usageLimit'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Mulai Berlaku</label>
              <input type="date" name="start_date" value="<?= e($editingCoupon['startDate'] ?? '') ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Berakhir</label>
              <input type="date" name="end_date" value="<?= e($editingCoupon['endDate'] ?? '') ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
            </div>
          </div>
          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" name="status" value="active" <?= ($editingCoupon['status'] ?? 'active') === 'active' ? 'checked' : '' ?>> Aktifkan kupon
          </label>
          <div class="flex gap-3">
            <button type="submit" class="ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm px-6 py-2.5 rounded-lg transition">Simpan</button>
            <a href="coupons.php" class="text-gray-500 text-sm font-medium px-6 py-2.5 hover:text-navy-900">Batal</a>
          </div>
        </form>
      </div>
      <?php endif; ?>

      <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
            <tr><th class="text-left px-4 py-3">Kode</th><th class="text-left px-4 py-3">Nilai</th><th class="text-left px-4 py-3">Min. Belanja</th><th class="text-center px-4 py-3">Terpakai</th><th class="text-left px-4 py-3">Berlaku</th><th class="text-center px-4 py-3">Status</th><th class="text-center px-4 py-3">Aksi</th></tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($coupons as $c): ?>
            <tr>
              <td class="px-4 py-3 font-mono font-semibold text-navy-900"><?= e($c['code']) ?></td>
              <td class="px-4 py-3 text-gray-600"><?= $c['type'] === 'percentage' ? (int) $c['value'] . '%' : e(formatRupiah($c['value'])) ?></td>
              <td class="px-4 py-3 text-gray-600"><?= e(formatRupiah($c['minimum_purchase'])) ?></td>
              <td class="px-4 py-3 text-center text-gray-600"><?= (int) $c['used_count'] ?><?= $c['usage_limit'] ? ' / ' . (int) $c['usage_limit'] : '' ?></td>
              <td class="px-4 py-3 text-xs text-gray-500"><?= $c['start_date'] ? e(formatTanggalIndo($c['start_date'])) : '-' ?> s/d <?= $c['end_date'] ? e(formatTanggalIndo($c['end_date'])) : '-' ?></td>
              <td class="px-4 py-3 text-center"><span class="text-[11px] font-semibold px-2 py-1 rounded-full <?= $c['status'] === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' ?>"><?= $c['status'] === 'active' ? 'Aktif' : 'Nonaktif' ?></span></td>
              <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-2 text-xs">
                  <a href="coupons.php?edit=<?= (int) $c['id'] ?>" class="text-cyan-600 hover:underline">Edit</a>
                  <form method="POST"><?= csrfField() ?><input type="hidden" name="form_action" value="toggle_status"><input type="hidden" name="coupon_id" value="<?= (int) $c['id'] ?>"><button type="submit" class="text-gray-500 hover:underline"><?= $c['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                  <form method="POST" onsubmit="return confirm('Hapus kupon ini?');"><?= csrfField() ?><input type="hidden" name="form_action" value="delete"><input type="hidden" name="coupon_id" value="<?= (int) $c['id'] ?>"><button type="submit" class="text-red-500 hover:underline">Hapus</button></form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</div>
</body>
</html>