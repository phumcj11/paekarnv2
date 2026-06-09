<?php
/** @var array $row @var array|null $order @var array|null $booking @var array $usages @var bool $canHardDelete */
$colors = ['unused'=>'emerald','reserved'=>'amber','used'=>'blue','expired'=>'rose','revoked'=>'rose','cancelled'=>'slate'];
$cl = $colors[$row['status']] ?? 'slate';
?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
  <a href="<?= url('/admin/coupons') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1"><i data-lucide="arrow-left" class="w-4 h-4"></i> รายการคูปอง</a>
  <div class="flex flex-wrap gap-2">
    <a href="<?= url('/admin/coupons/' . (int)$row['id'] . '/edit') ?>" class="px-4 py-2 border border-slate-300 rounded-lg text-sm inline-flex items-center gap-1"><i data-lucide="pencil" class="w-4 h-4"></i> แก้ไข</a>
    <form method="post" action="<?= url('/admin/coupons/' . (int)$row['id'] . '/delete') ?>" onsubmit="return confirm('เพิกถอนคูปองนี้?')"><?= csrf() ?>
      <button type="submit" class="px-4 py-2 border border-rose-200 text-rose-700 rounded-lg text-sm">เพิกถอน</button>
    </form>
    <?php if ($canHardDelete): ?>
    <form method="post" action="<?= url('/admin/coupons/' . (int)$row['id'] . '/delete') ?>" onsubmit="return confirm('ลบถาวร?')"><?= csrf() ?>
      <input type="hidden" name="hard_delete" value="1">
      <button type="submit" class="px-4 py-2 bg-rose-600 text-white rounded-lg text-sm">ลบถาวร</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<div class="grid lg:grid-cols-2 gap-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
    <div class="font-mono text-2xl font-bold text-primary-700"><?= e($row['code']) ?></div>
    <span class="inline-block mt-2 text-xs font-semibold bg-<?= $cl ?>-100 text-<?= $cl ?>-700 px-2 py-1 rounded-full"><?= e($row['status']) ?></span>

    <dl class="mt-4 space-y-2 text-sm">
      <div class="flex justify-between"><dt class="text-slate-500">เบอร์</dt><dd class="font-medium"><?= e($row['phone']) ?></dd></div>
      <div class="flex justify-between"><dt class="text-slate-500">มูลค่า</dt><dd><?= format_money($row['face_value']) ?></dd></div>
      <div class="flex justify-between"><dt class="text-slate-500">ราคาขาย</dt><dd><?= format_money($row['sale_price']) ?></dd></div>
      <div class="flex justify-between"><dt class="text-slate-500">ออกเมื่อ</dt><dd><?= format_date_th($row['issued_at']) ?></dd></div>
      <div class="flex justify-between"><dt class="text-slate-500">หมดอายุ</dt><dd><?= format_date_th($row['expires_at']) ?></dd></div>
      <?php if (!empty($row['used_at'])): ?>
      <div class="flex justify-between"><dt class="text-slate-500">ใช้เมื่อ</dt><dd><?= format_date_th($row['used_at']) ?></dd></div>
      <?php endif; ?>
    </dl>

    <?php if ($order): ?>
    <hr class="my-4">
    <h3 class="font-bold text-sm mb-2">คำสั่งซื้อ</h3>
    <a href="<?= url('/admin/coupons/orders/' . (int)$order['id']) ?>" class="text-primary-700 font-mono text-sm hover:underline"><?= e($order['order_no']) ?></a>
    <?php endif; ?>

    <?php if ($booking): ?>
    <hr class="my-4">
    <h3 class="font-bold text-sm mb-2">การจองที่ใช้</h3>
    <a href="<?= url('/admin/bookings/' . (int)$booking['id']) ?>" class="text-primary-700 font-mono text-sm hover:underline"><?= e($booking['code']) ?></a>
    <span class="text-xs text-slate-500"> — <?= e($booking['guest_name']) ?> (<?= e($booking['status']) ?>)</span>
    <?php endif; ?>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
    <h3 class="font-bold mb-3">ประวัติการใช้</h3>
    <?php if ($usages === []): ?>
    <p class="text-sm text-slate-500">ยังไม่มีประวัติ</p>
    <?php else: ?>
    <ul class="space-y-2 text-sm">
      <?php foreach ($usages as $u): ?>
      <li class="border border-slate-100 rounded-lg p-3">
        <?= format_money($u['amount']) ?> · <?= e($u['property_name'] ?? '-') ?>
        <?php if (!empty($u['booking_id'])): ?>
        · <a href="<?= url('/admin/bookings/' . (int)$u['booking_id']) ?>" class="text-primary-700">#<?= (int)$u['booking_id'] ?></a>
        <?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
</div>
