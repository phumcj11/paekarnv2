<?php
/** @var array $customer @var array $bookings @var array $coupons */
$statusLabels = ['active' => 'ใช้งาน', 'suspended' => 'ระงับ', 'pending' => 'รอยืนยัน'];
$statusBadge = match ((string)($customer['status'] ?? 'active')) {
    'active'    => 'bg-emerald-100 text-emerald-800',
    'suspended' => 'bg-rose-100 text-rose-800',
    'pending'   => 'bg-amber-100 text-amber-800',
    default     => 'bg-slate-100 text-slate-700',
};
$genderLabels = ['male' => 'ชาย', 'female' => 'หญิง', 'other' => 'อื่น ๆ'];
?>
<a href="<?= url('/admin/customers') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> ทั้งหมด</a>
<div class="flex flex-wrap gap-2 mb-4">
  <a href="<?= url('/admin/customers/' . (int)$customer['id'] . '/edit') ?>" class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm inline-flex items-center gap-1.5"><i data-lucide="pencil" class="w-4 h-4"></i> แก้ไข</a>
  <form method="post" action="<?= url('/admin/customers/' . (int)$customer['id'] . '/delete') ?>" class="inline" onsubmit="return confirm('ลบลูกค้าและบัญชีผู้ใช้นี้ — ยืนยัน?');"><?= csrf() ?>
    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm inline-flex items-center gap-1.5"><i data-lucide="trash-2" class="w-4 h-4"></i> ลบ</button>
  </form>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
    <div class="w-16 h-16 rounded-full bg-teal-100 text-teal-700 grid place-items-center font-bold text-2xl"><?= e(str_first_char((string)($customer['name'] ?? ''))) ?></div>
    <h2 class="mt-3 font-bold text-lg"><?= e($customer['name']) ?></h2>
    <p class="text-xs text-slate-500">Customer ID #<?= (int)$customer['id'] ?> · User #<?= (int)$customer['user_id'] ?></p>
    <hr class="my-3">
    <div class="space-y-1.5 text-sm">
      <div><i data-lucide="mail" class="w-4 h-4 inline text-slate-400"></i> <?= e($customer['email']) ?></div>
      <div><i data-lucide="phone" class="w-4 h-4 inline text-slate-400"></i> <?= e($customer['phone'] ?? '-') ?></div>
      <?php if (!empty($customer['line_id'])): ?>
      <div><i data-lucide="message-circle" class="w-4 h-4 inline text-slate-400"></i> LINE: <?= e($customer['line_id']) ?></div>
      <?php endif; ?>
      <?php if (!empty($customer['line_user_id'])): ?>
      <div class="text-emerald-700 text-xs"><i data-lucide="link" class="w-4 h-4 inline"></i> เชื่อม LINE Login แล้ว</div>
      <?php endif; ?>
      <?php if (!empty($customer['gender'])): ?>
      <div><i data-lucide="user" class="w-4 h-4 inline text-slate-400"></i> <?= e($genderLabels[$customer['gender']] ?? $customer['gender']) ?></div>
      <?php endif; ?>
      <?php if (!empty($customer['birthdate'])): ?>
      <div><i data-lucide="cake" class="w-4 h-4 inline text-slate-400"></i> <?= e(format_date_th($customer['birthdate'])) ?></div>
      <?php endif; ?>
      <?php if (!empty($customer['address']) || !empty($customer['province'])): ?>
      <div><i data-lucide="map-pin" class="w-4 h-4 inline text-slate-400"></i> <?= e(trim(($customer['address'] ?? '') . ' ' . ($customer['province'] ?? ''))) ?></div>
      <?php endif; ?>
    </div>
    <hr class="my-3">
    <div class="text-sm space-y-1">
      <div class="text-xs text-slate-500">สถานะบัญชี</div>
      <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold <?= e($statusBadge) ?>"><?= e($statusLabels[$customer['status'] ?? 'active'] ?? $customer['status']) ?></span>
    </div>
    <hr class="my-3">
    <div class="text-xs text-slate-500 space-y-1">
      <div>สมัครเมื่อ <?= e(format_date_th($customer['user_created_at'] ?? '')) ?></div>
      <?php if (!empty($customer['last_login_at'])): ?>
      <div>เข้าใช้ล่าสุด <?= e(format_date_th($customer['last_login_at'])) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="lg:col-span-2 space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold mb-3 flex items-center gap-2"><i data-lucide="calendar-check" class="w-5 h-5 text-accent-600"></i> การจองล่าสุด (<?= count($bookings) ?>)</h3>
      <?php if ($bookings === []): ?>
        <p class="text-sm text-slate-500 py-4 text-center">ยังไม่มีการจอง</p>
      <?php else: ?>
      <div class="space-y-2">
        <?php foreach ($bookings as $b): ?>
        <a href="<?= url('/admin/bookings/' . (int)$b['id']) ?>" class="flex items-center gap-3 p-2 rounded-lg hover:bg-slate-50">
          <div class="flex-1 min-w-0">
            <div class="font-semibold font-mono text-sm"><?= e($b['code']) ?></div>
            <div class="text-xs text-slate-500 truncate"><?= e($b['property_name'] ?? '-') ?> · <?= e(format_date_th($b['check_in'] ?? '')) ?></div>
          </div>
          <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100"><?= e($b['status']) ?></span>
          <div class="text-sm font-bold text-primary-700 shrink-0"><?= format_money($b['total_price'] ?? 0) ?></div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold mb-3 flex items-center gap-2"><i data-lucide="ticket" class="w-5 h-5 text-accent-600"></i> คูปอง (<?= count($coupons) ?>)</h3>
      <?php if ($coupons === []): ?>
        <p class="text-sm text-slate-500 py-4 text-center">ยังไม่มีคูปอง</p>
      <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="text-xs uppercase text-slate-500">
            <tr>
              <th class="text-left py-2">รหัส</th>
              <th class="text-left py-2">มูลค่า</th>
              <th class="text-left py-2">สถานะ</th>
              <th class="text-left py-2">หมดอายุ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <?php foreach ($coupons as $cp): ?>
            <tr>
              <td class="py-2"><a href="<?= url('/admin/coupons/' . (int)$cp['id']) ?>" class="font-mono text-primary-700 hover:underline"><?= e($cp['code']) ?></a></td>
              <td class="py-2"><?= format_money($cp['face_value'] ?? 0) ?></td>
              <td class="py-2"><?= e($cp['status']) ?></td>
              <td class="py-2 text-xs"><?= e(format_date_th($cp['expires_at'] ?? '')) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
