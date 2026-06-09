<?php
/** @var array<string,mixed> $order */
/** @var array<string,string> $statuses */
/** @var bool $isActive */
?>
<a href="<?= url('/provider/orders') ?>" class="text-sm text-slate-500 hover:text-teal-700 inline-flex items-center gap-1 mb-4"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</a>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-2">
      <div>
        <h2 class="text-xl font-bold"><?= e($order['order_no']) ?></h2>
        <p class="text-sm text-slate-500"><?= e($order['product_title'] ?? '') ?></p>
      </div>
      <span class="px-3 py-1 rounded-full bg-slate-100 text-sm font-semibold"><?= e($statuses[$order['status']] ?? $order['status']) ?></span>
    </div>

    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
      <div><dt class="text-slate-500">ลูกค้า</dt><dd class="font-medium"><?= e($order['buyer_name']) ?></dd></div>
      <div><dt class="text-slate-500">โทร</dt><dd class="font-medium"><?= e($order['buyer_phone']) ?></dd></div>
      <div><dt class="text-slate-500">อีเมล</dt><dd class="font-medium"><?= e($order['buyer_email'] ?: '—') ?></dd></div>
      <div><dt class="text-slate-500">วันใช้บริการ</dt><dd class="font-medium"><?= e($order['travel_date'] ?: '—') ?> <?= e($order['time_slot'] ?? '') ?></dd></div>
      <div><dt class="text-slate-500">จำนวน</dt><dd class="font-medium"><?= (int)$order['quantity'] ?> × <?= format_money($order['unit_price']) ?></dd></div>
      <div><dt class="text-slate-500">รายได้ (โดยประมาณ)</dt><dd class="font-medium text-teal-700"><?= format_money($order['provider_payout'] ?? 0) ?></dd></div>
    </dl>

    <?php if (!empty($order['voucher_code'])): ?>
    <div class="rounded-xl border border-dashed border-teal-300 bg-teal-50 p-4">
      <div class="text-xs text-teal-700 font-semibold uppercase">Voucher Code</div>
      <div class="font-mono text-lg font-bold text-teal-900 mt-1"><?= e($order['voucher_code']) ?></div>
    </div>
    <?php endif; ?>

    <?php if (!empty($order['notes'])): ?>
    <div class="text-sm"><span class="text-slate-500">หมายเหตุ:</span> <?= e($order['notes']) ?></div>
    <?php endif; ?>
  </div>

  <aside class="space-y-4">
    <?php if ($isActive && $order['status'] === 'paid'): ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold mb-2">ยืนยันรอบบริการ</h3>
      <p class="text-xs text-slate-500 mb-3">เมื่อลูกค้าชำระเงินแล้ว (Admin ตรวจสลิป) ให้ยืนยันรอบก่อนวันใช้บริการ</p>
      <form method="post" action="<?= url('/provider/orders/' . $order['id'] . '/confirm') ?>"><?= csrf() ?>
        <button class="w-full py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-semibold text-sm">ยืนยันออเดอร์</button>
      </form>
    </div>
    <?php endif; ?>

    <?php if ($isActive && in_array($order['status'], ['paid', 'confirmed'], true)): ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <a href="<?= url('/provider/redeem?code=' . urlencode((string)$order['voucher_code'])) ?>" class="w-full inline-flex justify-center py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl font-semibold text-sm">
        ไปหน้า Redeem
      </a>
    </div>
    <?php endif; ?>
  </aside>
</div>
