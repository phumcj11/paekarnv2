<?php
/** @var array<string,mixed> $order */
/** @var array<string,string> $statuses */
/** @var bool $hasSettlement */
$eligible = in_array($order['status'], ['paid','confirmed','redeemed'], true);
$paidOut = $hasSettlement && !empty($order['provider_paid_at']);
?>
<a href="<?= url('/admin/activity-orders') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</a>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <div class="flex items-start justify-between gap-3">
        <div>
          <div class="text-xs text-slate-500">Order No.</div>
          <h1 class="text-xl font-bold font-mono"><?= e($order['order_no']) ?></h1>
          <p class="text-sm text-slate-600 mt-1"><?= e($order['product_title']) ?><?= !empty($order['option_name']) ? ' · ' . e($order['option_name']) : '' ?></p>
          <?php if (!empty($order['provider_name'])): ?>
            <p class="text-xs text-slate-500 mt-1">Provider: <?= e($order['provider_name']) ?></p>
          <?php endif; ?>
        </div>
        <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-sm font-semibold"><?= e($statuses[$order['status']] ?? $order['status']) ?></span>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h2 class="font-bold mb-3">ข้อมูลลูกค้า</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
        <div><div class="text-slate-500">ชื่อ</div><div class="font-semibold"><?= e($order['buyer_name']) ?></div></div>
        <div><div class="text-slate-500">โทร</div><div class="font-semibold"><?= e($order['buyer_phone']) ?></div></div>
        <div><div class="text-slate-500">อีเมล</div><div><?= e($order['buyer_email'] ?: '—') ?></div></div>
        <div><div class="text-slate-500">วันที่ใช้บริการ</div><div><?= e($order['travel_date'] ?: '—') ?> <?= e($order['time_slot'] ?: '') ?></div></div>
      </div>
      <?php if (!empty($order['notes'])): ?><div class="mt-4 text-sm bg-slate-50 border border-slate-200 rounded-xl p-3 whitespace-pre-wrap"><?= e($order['notes']) ?></div><?php endif; ?>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h2 class="font-bold mb-3">การเงิน</h2>
      <div class="space-y-2 text-sm">
        <div class="flex justify-between"><span>ราคา/หน่วย</span><span><?= format_money($order['unit_price']) ?></span></div>
        <div class="flex justify-between"><span>จำนวน</span><span><?= (int)$order['quantity'] ?></span></div>
        <div class="flex justify-between text-emerald-700"><span>รายได้แพกาญ (คอม)</span><span class="font-semibold"><?= format_money($order['commission_amount']) ?></span></div>
        <div class="flex justify-between text-sky-700"><span>จ่าย provider</span><span class="font-semibold"><?= format_money($order['provider_payout']) ?></span></div>
        <div class="flex justify-between border-t border-slate-100 pt-2 font-bold text-primary-700"><span>รวม (ลูกค้าจ่าย)</span><span><?= format_money($order['total_price']) ?></span></div>
      </div>
    </div>
  </div>

  <aside class="space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h2 class="font-bold mb-3">Voucher</h2>
      <div class="rounded-xl border-2 border-dashed border-accent-300 bg-accent-50 p-4 text-center">
        <div class="text-xs text-slate-500">CODE</div>
        <div class="font-mono text-lg font-bold text-primary-700"><?= e($order['voucher_code']) ?></div>
      </div>
    </div>

    <?php if ($hasSettlement && $eligible): ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
      <h2 class="font-bold">Settlement provider</h2>
      <?php if ($paidOut): ?>
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-3 text-sm">
          <div class="font-semibold text-emerald-800">โอนแล้ว</div>
          <div class="text-emerald-700 mt-1"><?= e($order['provider_paid_at']) ?></div>
          <?php if (!empty($order['provider_payout_ref'])): ?>
            <div class="text-xs text-emerald-600 mt-1 font-mono">ref: <?= e($order['provider_payout_ref']) ?></div>
          <?php endif; ?>
        </div>
        <form method="post" action="<?= url('/admin/activity-orders/' . $order['id'] . '/clear-payout') ?>" onsubmit="return confirm('ยกเลิกสถานะโอน?')">
          <?= csrf() ?>
          <button class="w-full py-2 border border-slate-300 rounded-lg text-sm text-slate-600 hover:bg-slate-50">ยกเลิกสถานะโอน</button>
        </form>
      <?php else: ?>
        <p class="text-xs text-slate-500">บันทึกเมื่อโอน <?= format_money($order['provider_payout']) ?> ให้ provider แล้ว</p>
        <form method="post" action="<?= url('/admin/activity-orders/' . $order['id'] . '/mark-payout') ?>" class="space-y-2">
          <?= csrf() ?>
          <input type="text" name="provider_payout_ref" placeholder="เลขอ้างอิงการโอน (optional)" maxlength="120" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
          <button class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold text-sm">บันทึกโอนให้ provider แล้ว</button>
        </form>
      <?php endif; ?>
    </div>
    <?php elseif ($hasSettlement): ?>
    <div class="bg-slate-50 rounded-2xl border border-slate-200 p-4 text-xs text-slate-500">
      Settlement ใช้ได้เมื่อสถานะเป็น paid / confirmed / redeemed
    </div>
    <?php endif; ?>

    <form method="post" action="<?= url('/admin/activity-orders/' . $order['id'] . '/status') ?>" class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
      <?= csrf() ?>
      <h2 class="font-bold">อัปเดตสถานะ</h2>
      <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        <?php foreach ($statuses as $k => $label): ?><option value="<?= e($k) ?>" <?= $order['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
      </select>
      <button class="w-full py-2 bg-accent-500 hover:bg-accent-600 text-white rounded-lg font-semibold">บันทึก</button>
    </form>
    <?php if (!empty($order['payment_slip'])): ?>
      <a href="<?= e(upload_url($order['payment_slip'])) ?>" target="_blank" class="block bg-white rounded-2xl border border-slate-200 shadow-soft p-5 text-sm font-semibold text-primary-700 hover:underline">เปิดสลิป</a>
    <?php endif; ?>
  </aside>
</div>
