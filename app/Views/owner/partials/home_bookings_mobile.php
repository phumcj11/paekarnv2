<?php
/** @var list<array> $todayBookings @var list<array> $upcomingBookings */
$todayBookings    = $todayBookings ?? [];
$upcomingBookings = $upcomingBookings ?? [];

$statusLabels = [
    'pending'   => ['รอยืนยัน', 'amber'],
    'confirmed' => ['ยืนยันแล้ว', 'emerald'],
    'completed' => ['เสร็จสิ้น', 'blue'],
    'cancelled' => ['ยกเลิก', 'slate'],
    'rejected'  => ['ปฏิเสธ', 'rose'],
    'no_show'   => ['ไม่มา', 'slate'],
];

$renderCard = static function (array $b) use ($statusLabels): void {
    $st   = $statusLabels[$b['status']] ?? [$b['status'], 'slate'];
    $total = (float)($b['total_price'] ?? 0);
    $paid  = (float)($b['paid_amount'] ?? 0);
    $balance = max(0, $total - $paid);
    $detailUrl = url('/owner/bookings/' . (int)$b['id']);
    ?>
    <div class="ow-card p-3">
      <a href="<?= e($detailUrl) ?>" class="block hover:opacity-90 transition">
        <div class="flex items-start justify-between gap-2">
          <div class="min-w-0 flex-1">
            <div class="font-semibold text-sm text-slate-800 truncate"><?= e($b['guest_name']) ?></div>
            <div class="text-[10px] text-slate-500 font-mono mt-0.5"><?= e($b['code']) ?></div>
          </div>
          <span class="text-[10px] font-bold bg-<?= $st[1] ?>-100 text-<?= $st[1] ?>-700 px-2 py-0.5 rounded-full shrink-0"><?= e($st[0]) ?></span>
        </div>
        <div class="mt-2 text-xs text-slate-600 space-y-0.5">
          <?php if (!empty($b['unit_name'])): ?>
          <div class="flex items-center gap-1.5">
            <i data-lucide="bed-double" class="w-3 h-3 text-slate-400 shrink-0"></i>
            <span class="truncate"><?= e($b['unit_name']) ?></span>
          </div>
          <?php endif; ?>
          <div class="flex items-center gap-1.5">
            <i data-lucide="calendar" class="w-3 h-3 text-slate-400 shrink-0"></i>
            <span><?= format_date_th($b['check_in']) ?> → <?= format_date_th($b['check_out']) ?> (<?= (int)$b['nights'] ?> คืน)</span>
          </div>
          <div class="flex items-center justify-between gap-2 pt-1">
            <span class="font-semibold text-core-700"><?= format_money($total) ?></span>
            <?php if ($balance > 0 && $balance < $total): ?>
            <span class="text-[10px] text-slate-500">คงเหลือ <?= format_money($balance) ?></span>
            <?php elseif ($balance <= 0 && $total > 0): ?>
            <span class="text-[10px] text-emerald-600 font-medium">ชำระครบ</span>
            <?php endif; ?>
          </div>
        </div>
      </a>
      <?php if (!empty($b['guest_phone'])): ?>
      <div class="mt-2 pt-2 border-t border-slate-100 flex justify-end">
        <a href="tel:<?= e(preg_replace('/\s+/', '', $b['guest_phone'])) ?>"
           class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg">
          <i data-lucide="phone" class="w-3.5 h-3.5"></i> โทร
        </a>
      </div>
      <?php endif; ?>
    </div>
    <?php
};
?>

<div class="lg:hidden space-y-4 mb-5">

  <!-- จองวันนี้ -->
  <section>
    <div class="ow-section-head mb-2">
      <h3 class="ow-section-title text-sm">
        <i data-lucide="sun" class="w-4 h-4 text-amber-500"></i>
        รายการจองวันนี้
      </h3>
      <span class="text-[10px] text-slate-400"><?= count($todayBookings) ?> รายการ</span>
    </div>
    <?php if (empty($todayBookings)): ?>
    <div class="ow-card p-4 text-center">
      <i data-lucide="calendar-check" class="w-8 h-8 text-slate-300 mx-auto mb-1"></i>
      <p class="text-xs text-slate-500">วันนี้ยังไม่มีลูกค้าเข้าพัก</p>
    </div>
    <?php else: ?>
    <div class="space-y-2">
      <?php foreach ($todayBookings as $b) { $renderCard($b); } ?>
    </div>
    <?php endif; ?>
  </section>

  <!-- จองใกล้มาถึง -->
  <section>
    <div class="ow-section-head mb-2">
      <h3 class="ow-section-title text-sm">
        <i data-lucide="calendar-clock" class="w-4 h-4 text-core-600"></i>
        รายการจองใกล้มาถึง
      </h3>
      <span class="text-[10px] text-slate-400">14 วันข้างหน้า</span>
    </div>
    <?php if (empty($upcomingBookings)): ?>
    <div class="ow-card p-4 text-center">
      <i data-lucide="calendar-plus" class="w-8 h-8 text-slate-300 mx-auto mb-1"></i>
      <p class="text-xs text-slate-500">ยังไม่มีการจองใน 2 สัปดาห์ข้างหน้า</p>
      <a href="<?= url('/owner/bookings/create') ?>" class="inline-block mt-2 text-xs font-semibold text-core-600">+ เพิ่มการจอง</a>
    </div>
    <?php else: ?>
    <div class="space-y-2">
      <?php foreach ($upcomingBookings as $b) { $renderCard($b); } ?>
    </div>
    <a href="<?= url('/owner/bookings') ?>" class="block text-center text-xs font-semibold text-core-600 mt-2">ดูการจองทั้งหมด</a>
    <?php endif; ?>
  </section>

</div>
