<?php
/** @var ?array<string,mixed> $order */
/** @var ?array<string,mixed> $check */
/** @var string $code */
/** @var bool $isActive */
?>
<div class="max-w-lg mx-auto space-y-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
    <h2 class="font-bold flex items-center gap-2 text-lg"><i data-lucide="scan-line" class="w-6 h-6 text-teal-600"></i> Redeem Voucher</h2>
    <p class="text-sm text-slate-500 mt-1">กรอกรหัส voucher หรือสแกน QR ของลูกค้า</p>

    <form method="post" action="<?= url('/provider/redeem') ?>" class="mt-4 flex gap-2">
      <?= csrf() ?>
      <input type="text" name="code" value="<?= e($code) ?>" placeholder="PAK-ACT-XXXXXX" required
             class="flex-1 px-3 py-2.5 rounded-lg border border-slate-300 font-mono uppercase">
      <button class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-semibold text-sm">ค้นหา</button>
    </form>

    <div id="qr-reader" class="mt-4 rounded-xl overflow-hidden bg-slate-900 min-h-[220px]"></div>
    <p id="scan-status" class="text-xs text-slate-500 mt-2 text-center">เปิดกล้องเพื่อสแกน QR (ถ้ารองรับ)</p>
  </div>

  <?php if ($check !== null): ?>
    <?php if (!empty($check['ok']) && $order): ?>
      <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 space-y-3">
        <div class="font-bold text-emerald-800">พร้อม Redeem</div>
        <dl class="text-sm grid grid-cols-2 gap-2">
          <div><dt class="text-slate-500">ออเดอร์</dt><dd class="font-mono"><?= e($order['order_no']) ?></dd></div>
          <div><dt class="text-slate-500">สินค้า</dt><dd><?= e($order['product_title'] ?? '') ?></dd></div>
          <div><dt class="text-slate-500">ลูกค้า</dt><dd><?= e($order['buyer_name']) ?></dd></div>
          <div><dt class="text-slate-500">วันใช้บริการ</dt><dd><?= e($order['travel_date'] ?: '—') ?></dd></div>
        </dl>
        <?php if ($isActive): ?>
        <form method="post" action="<?= url('/provider/redeem/use') ?>"><?= csrf() ?>
          <input type="hidden" name="code" value="<?= e($code) ?>">
          <button class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold">ยืนยัน Redeem</button>
        </form>
        <?php else: ?>
        <p class="text-sm text-amber-700">บัญชียังไม่ได้รับการอนุมัติ — ไม่สามารถ redeem ได้</p>
        <?php endif; ?>
      </div>
    <?php elseif ($order && ($order['status'] ?? '') === 'redeemed'): ?>
      <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
        <div class="font-bold text-slate-700">ใช้งานแล้ว</div>
        <p class="text-sm text-slate-600 mt-1">Voucher <?= e($code) ?> redeem แล้ว<?= !empty($order['redeemed_at']) ? ' เมื่อ ' . e($order['redeemed_at']) : '' ?></p>
      </div>
    <?php else: ?>
      <div class="bg-rose-50 border border-rose-200 rounded-2xl p-5 text-rose-800 text-sm">
        <?= e($check['msg'] ?? 'ไม่สามารถ redeem ได้') ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function(){
  const statusEl = document.getElementById('scan-status');
  const codeInput = document.querySelector('input[name="code"]');
  if (!window.Html5Qrcode || !document.getElementById('qr-reader')) return;
  const scanner = new Html5Qrcode('qr-reader');
  const config = { fps: 8, qrbox: { width: 200, height: 200 } };
  let done = false;
  function onScan(text) {
    if (done) return;
    done = true;
    const m = text.match(/PAK-ACT-[A-F0-9]+/i);
    codeInput.value = m ? m[0].toUpperCase() : text.trim().toUpperCase();
    statusEl.textContent = 'อ่าน QR แล้ว — กดค้นหา';
    scanner.stop().catch(function(){});
  }
  scanner.start({ facingMode: 'environment' }, config, onScan, function() {})
    .catch(function() {
      statusEl.textContent = 'เปิดกล้องไม่ได้ — กรอกรหัสด้วยมือได้';
    })
    .then(function() { if (!done) statusEl.textContent = 'สแกน QR ได้'; });
})();
</script>
