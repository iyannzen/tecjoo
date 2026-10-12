<?php
/**
 * TechZone Admin - Form Produk (partial, dipakai oleh product-add.php & product-edit.php)
 * Variabel yang harus disediakan oleh pemanggil:
 *   $product (array|null), $categories (array), $errors (array), $formTitle (string)
 *   $specsText, $featuresText, $applicationsText, $compatText, $packageText (string, hasil konversi JSON->teks untuk edit)
 */
?>
<div class="bg-white border border-gray-100 rounded-xl p-6 max-w-4xl shadow-card transition-shadow hover:shadow-lg">
  <h2 class="text-sm font-bold text-navy-900 mb-4"><?= e($formTitle) ?></h2>

  <?php if (!empty($errors)): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg p-3 mb-4">
      <ul class="list-disc list-inside space-y-1"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data" class="space-y-5">
    <?= csrfField() ?>
    <input type="hidden" name="form_action" value="save">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Nama Produk</label>
        <input type="text" name="name" required value="<?= e($product['name'] ?? '') ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">SKU</label>
        <input type="text" name="sku" required value="<?= e($product['sku'] ?? '') ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Kategori</label>
        <select name="category_id" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['id'] ?>" <?= (int) ($product['category_id'] ?? 0) === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Brand</label>
        <input type="text" name="brand" value="<?= e($product['brand'] ?? '') ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
      </div>
    </div>

    <div>
      <label class="block text-xs font-medium text-gray-700 mb-1">Deskripsi Singkat</label>
      <input type="text" name="short_description" maxlength="500" value="<?= e($product['short_description'] ?? '') ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
    </div>

    <div>
      <label class="block text-xs font-medium text-gray-700 mb-1">Deskripsi Lengkap</label>
      <textarea name="description" rows="4" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200"><?= e($product['description'] ?? '') ?></textarea>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Spesifikasi <span class="text-gray-400">(format: Kunci: Nilai, satu per baris)</span></label>
        <textarea name="specifications_text" rows="5" placeholder="Chip: ESP32-WROOM-32&#10;Tegangan: 5V" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200"><?= e($specsText ?? '') ?></textarea>
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Fitur <span class="text-gray-400">(satu per baris)</span></label>
        <textarea name="features_text" rows="5" placeholder="WiFi&#10;Bluetooth" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200"><?= e($featuresText ?? '') ?></textarea>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Kegunaan <span class="text-gray-400">(satu per baris)</span></label>
        <textarea name="applications_text" rows="4" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200"><?= e($applicationsText ?? '') ?></textarea>
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Kompatibilitas <span class="text-gray-400">(satu per baris)</span></label>
        <textarea name="compatibility_text" rows="4" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200"><?= e($compatText ?? '') ?></textarea>
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Isi Paket <span class="text-gray-400">(satu per baris)</span></label>
        <textarea name="package_text" rows="4" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200"><?= e($packageText ?? '') ?></textarea>
      </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Harga (Rp)</label>
        <input type="number" name="price" min="0" required value="<?= e((string) ($product['price'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Harga Diskon (Rp)</label>
        <input type="number" name="discount_price" min="0" value="<?= e((string) ($product['discount_price'] ?? '')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Stok</label>
        <input type="number" name="stock" min="0" required value="<?= e((string) ($product['stock'] ?? '0')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Berat (gram)</label>
        <input type="number" name="weight" min="0" required value="<?= e((string) ($product['weight'] ?? '0')) ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Dimensi</label>
        <input type="text" name="dimensions" placeholder="Contoh: 55 x 28 x 13 mm" value="<?= e($product['dimensions'] ?? '') ?>" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all duration-200">
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Gambar Utama <?= !empty($product['main_image']) ? '(kosongkan jika tidak ingin mengganti)' : '' ?></label>
        <input type="file" name="main_image" accept=".jpg,.jpeg,.png,.webp" <?= empty($product['main_image']) ? 'required' : '' ?> class="w-full text-xs border border-gray-300 rounded-lg px-3 py-2">
      </div>
    </div>

    <?php if (!empty($product['main_image'])): ?>
      <img src="<?= e(techzoneBaseUrl() . '/' . ltrim($product['main_image'], '/')) ?>" alt="<?= e($product['name'] ?? 'Gambar produk') ?>" class="w-24 h-24 object-cover rounded-lg border border-gray-100">
    <?php endif; ?>

    <div class="flex items-center gap-6">
      <label class="flex items-center gap-2 text-sm cursor-pointer">
        <input type="checkbox" name="status" value="active" <?= ($product['status'] ?? 'active') === 'active' ? 'checked' : '' ?>> Status Aktif
      </label>
      <label class="flex items-center gap-2 text-sm cursor-pointer">
        <input type="checkbox" name="featured" value="1" <?= !empty($product['featured']) ? 'checked' : '' ?>> Produk Unggulan
      </label>
    </div>

    <div class="flex gap-3 pt-2">
      <button type="submit" class="ripple-wrap btn-glow bg-cyan-500 hover:bg-cyan-400 text-navy-900 font-semibold text-sm px-6 py-2.5 rounded-lg transition">Simpan Produk</button>
      <a href="products.php" class="text-gray-500 text-sm font-medium px-6 py-2.5 hover:text-navy-900">Batal</a>
    </div>
  </form>
</div>
