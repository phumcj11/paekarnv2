<?php
/** @var array<string,mixed>|null $provider */
/** @var array<string,int|float> $stats */
/** @var bool $isActive */
/** @var array<string,mixed>|null $subscription */
/** @var bool $hasSettlement */
use App\Models\ActivityProvider;
use App\Models\ActivityProviderSubscription;
?>
<?php if (!$isActive): ?>
<div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 flex gap-3">
  <i data-lucide="info" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"></i>
  <div class="text-sm text-amber-900">
    <p class="font-semibold">บัญชี<?= e(ActivityProvider::partnerStatusLabel((string)($provider['partner_status'] ?? 'pending'))) ?></p>
    <p class="mt-1 text-amber-800/90">เมื่อได้รับการอนุมัติแล้ว คุณจะสามารถสร้างสินค้า ส่งตรวจ และรับออเดอร์ได้</p>
    <?php if (($provider['partner_status'] ?? '') === 'active'): ?>
    <?php else: ?>
    <ol class="mt-2 list-decimal list-inside space-y-0.5 text-amber-800">
      <li>สร้างสินค้าแรก</li>
      <li>กดส่งตรวจสอบ</li>
      <li>รอทีมงานเผยแพร่บน /activities</li>
    </ol>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <?php
  $cards = [
    ['orders_new', 'คำสั่งซื้อใหม่', 'ticket', 'text-sky-600 bg-sky-50'],
    ['orders_confirm', 'รอยืนยัน / redeem', 'clock', 'text-amber-600 bg-amber-50'],
    ['published', 'สินค้าเผยแพร่', 'check-circle', 'text-emerald-600 bg-emerald-50'],
    ['revenue_month', 'ยอด voucher เดือนนี้', 'wallet', 'text-teal-600 bg-teal-50'],
  ];
  foreach ($cards as [$key, $label, $icon, $cls]):
    $val = $stats[$key] ?? 0;
    if ($key === 'revenue_month') $val = format_money((float)$val);
  ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4">
    <div class="flex items-center justify-between">
      <span class="text-xs font-semibold text-slate-500 uppercase"><?= e($label) ?></span>
      <span class="w-8 h-8 rounded-lg grid place-items-center <?= $cls ?>"><i data-lucide="<?= $icon ?>" class="w-4 h-4"></i></span>
    </div>
    <div class="mt-2 text-2xl font-bold text-slate-800"><?= e((string)$val) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<?php if ($hasSettlement): ?>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
  <div class="bg-white rounded-2xl border border-amber-200 shadow-soft p-5">
    <div class="text-xs font-semibold text-amber-700 uppercase">รอรับจากแพกาญ</div>
    <div class="mt-1 text-2xl font-bold text-amber-800"><?= format_money((float)($stats['payout_pending'] ?? 0)) ?></div>
    <p class="text-xs text-slate-500 mt-2">ยอดที่แพกาญยังไม่ได้โอน (ออเดอร์ paid+)</p>
  </div>
  <div class="bg-white rounded-2xl border border-emerald-200 shadow-soft p-5">
    <div class="text-xs font-semibold text-emerald-700 uppercase">โอนแล้ว</div>
    <div class="mt-1 text-2xl font-bold text-emerald-800"><?= format_money((float)($stats['payout_paid'] ?? 0)) ?></div>
    <p class="text-xs text-slate-500 mt-2">ยอดที่แพกาญบันทึกการโอนแล้ว</p>
  </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
    <h2 class="font-bold text-slate-800 mb-3">สินค้าของฉัน</h2>
    <dl class="grid grid-cols-2 gap-3 text-sm">
      <div><dt class="text-slate-500">ทั้งหมด</dt><dd class="font-bold text-lg"><?= (int)($stats['products'] ?? 0) ?></dd></div>
      <div><dt class="text-slate-500">ฉบับร่าง</dt><dd class="font-bold text-lg text-amber-600"><?= (int)($stats['draft'] ?? 0) ?></dd></div>
      <div><dt class="text-slate-500">รอตรวจ</dt><dd class="font-bold text-lg text-sky-600"><?= (int)($stats['pending_review'] ?? 0) ?></dd></div>
      <div><dt class="text-slate-500">เผยแพร่</dt><dd class="font-bold text-lg text-emerald-600"><?= (int)($stats['published'] ?? 0) ?></dd></div>
    </dl>
    <?php if ($isActive): ?>
    <a href="<?= url('/provider/products/create') ?>" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-xl text-sm font-semibold hover:bg-teal-700">
      <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มสินค้า
    </a>
    <?php endif; ?>
  </div>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
    <div>
      <h2 class="font-bold text-slate-800 mb-3">ทางลัด</h2>
      <div class="space-y-2">
        <a href="<?= url('/provider/orders') ?>" class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50">
          <i data-lucide="ticket" class="w-5 h-5 text-teal-600"></i>
          <span class="text-sm font-medium">ดูคำสั่งซื้อทั้งหมด</span>
        </a>
        <a href="<?= url('/provider/redeem') ?>" class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50">
          <i data-lucide="scan-line" class="w-5 h-5 text-teal-600"></i>
          <span class="text-sm font-medium">Redeem voucher วันใช้บริการ</span>
        </a>
        <a href="<?= url('/provider/profile') ?>" class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50">
          <i data-lucide="landmark" class="w-5 h-5 text-teal-600"></i>
          <span class="text-sm font-medium">อัปเดตบัญชีธนาคารสำหรับรับเงิน</span>
        </a>
      </div>
    </div>
    <?php if (($stats['leads_total'] ?? 0) > 0 || ($stats['leads_month'] ?? 0) > 0): ?>
    <div class="border-t border-slate-100 pt-4">
      <h3 class="font-semibold text-sm text-slate-700 mb-2">Lead (LINE/โทร)</h3>
      <p class="text-sm text-slate-600">เดือนนี้ <span class="font-bold"><?= (int)($stats['leads_month'] ?? 0) ?></span> · ทั้งหมด <span class="font-bold"><?= (int)($stats['leads_total'] ?? 0) ?></span></p>
    </div>
    <?php endif; ?>
    <?php if ($subscription): ?>
    <div class="border-t border-slate-100 pt-4">
      <h3 class="font-semibold text-sm text-slate-700 mb-1">แพ็กสมาชิก</h3>
      <p class="text-sm font-medium text-teal-700"><?= e(ActivityProviderSubscription::PLANS[$subscription['plan_key']] ?? $subscription['plan_key']) ?></p>
      <?php if ($subscription['commission_override'] !== null): ?>
        <p class="text-xs text-slate-500 mt-1">คอมมิชชันพิเศษ <?= e((string)$subscription['commission_override']) ?>%</p>
      <?php endif; ?>
      <?php if (!empty($subscription['ends_at'])): ?>
        <p class="text-xs text-slate-500">ถึง <?= e((string)$subscription['ends_at']) ?></p>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
