<?php
/**
 * Tab navigasi untuk halaman Rekening Bank & E-Wallet.
 * Set $tzTab = 'bank' | 'ewallet' sebelum include file ini.
 */
$tzTab = $tzTab ?? 'bank';
?>
<div class="tz-tabs" role="tablist" aria-label="Metode pembayaran">
  <a href="bank-accounts.php" role="tab" class="tz-tab <?= $tzTab === 'bank' ? 'is-active' : '' ?>"><i class="fa-solid fa-building-columns"></i> Rekening Bank</a>
  <a href="ewallets.php" role="tab" class="tz-tab <?= $tzTab === 'ewallet' ? 'is-active' : '' ?>"><i class="fa-solid fa-wallet"></i> E-Wallet</a>
</div>
