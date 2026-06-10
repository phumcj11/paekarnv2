<?php /** @var array $stats @var array $recentBookings @var array $myProperties @var array $chart
           @var array|null $membership_owner @var bool $membership_benefits_active @var bool $membership_line_linked @var bool $membership_is_vip */

$propStatusLabels = [
  'published' => ['published', 'ow-status-pill--published'],
  'draft'     => ['draft', 'ow-status-pill--draft'],
  'pending'   => ['pending', 'ow-status-pill--pending'],
  'rejected'  => ['rejected', 'ow-status-pill--rejected'],
  'archived'  => ['archived', 'ow-status-pill--draft'],
];
$bookingStatusIcons = ['pending'=>'clock','confirmed'=>'check-circle','rejected'=>'x-circle','cancelled'=>'ban','completed'=>'flag','no_show'=>'user-x'];
$bookingColors = ['pending'=>'amber','confirmed'=>'emerald','rejected'=>'rose','cancelled'=>'slate','completed'=>'blue','no_show'=>'slate'];
$scheduleUrl = !empty($myProperties[0]['id'])
  ? url('/owner/properties/' . (int)$myProperties[0]['id'] . '/availability')
  : url('/owner/properties');
?>

<?php if ($stats['properties'] === 0): ?>
<div class="ow-card p-5 mb-5 bg-gradient-to-br from-core-600 to-core-800 text-white border-0">
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h2 class="text-lg font-bold flex items-center gap-2"><i data-lucide="rocket" class="w-5 h-5"></i> เริ่มต้นใช้งาน</h2>
      <p class="text-white/85 mt-1 text-sm">เพิ่มที่พักแรกของคุณ เพื่อเริ่มรับการจอง</p>
    </div>
    <a href="<?= url('/owner/properties/create') ?>" class="inline-flex items-center justify-center gap-2 bg-white text-core-700 px-5 py-2.5 rounded-xl font-semibold hover:bg-core-50 whitespace-nowrap shrink-0">
      <i data-lucide="plus-circle" class="w-4 h-4"></i> เพิ่มที่พัก
    </a>
  </div>
</div>
<?php endif; ?>

<!-- Quick actions -->
<div class="grid grid-cols-2 gap-3 mb-5">
  <a href="<?= url('/owner/bookings/create') ?>" class="ow-quick-btn group">
    <div class="ow-quick-btn__icon bg-blue-50 text-blue-600 group-hover:bg-blue-100"><i data-lucide="plus" class="w-6 h-6"></i></div>
    <span class="text-sm font-semibold text-slate-700">เพิ่มการจอง</span>
  </a>
  <a href="<?= url('/owner/bookings') ?>" class="ow-quick-btn group">
    <div class="ow-quick-btn__icon bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100"><i data-lucide="clipboard-list" class="w-6 h-6"></i></div>
    <span class="text-sm font-semibold text-slate-700">รายการจอง</span>
  </a>
  <a href="<?= e($scheduleUrl) ?>" class="ow-quick-btn group">
    <div class="ow-quick-btn__icon bg-sky-50 text-sky-600 group-hover:bg-sky-100"><i data-lucide="calendar-range" class="w-6 h-6"></i></div>
    <span class="text-sm font-semibold text-slate-700">ตารางที่พัก</span>
  </a>
  <a href="<?= url('/owner/coupons/verify') ?>" class="ow-quick-btn group">
    <div class="ow-quick-btn__icon bg-rose-50 text-rose-600 group-hover:bg-rose-100"><i data-lucide="ticket" class="w-6 h-6"></i></div>
    <span class="text-sm font-semibold text-slate-700">ตรวจคูปอง</span>
  </a>
</div>

<!-- KPI metrics -->
<div class="space-y-3 mb-5">
  <?php
  $metrics = [
    ['hotel', 'ที่พักทั้งหมด', $stats['properties'], 'เผยแพร่ ' . $stats['published'] . ' แห่ง', 'bg-blue-50 text-blue-600'],
    ['calendar-check', 'การจองทั้งหมด', $stats['bookings_total'], 'รอเช็คอิน ' . $stats['bookings_pending'] . ' รายการ', 'bg-amber-50 text-amber-600'],
    ['banknote', 'รายได้รวม', '฿' . number_format($stats['revenue']), 'จากยอดที่ชำระแล้ว', 'bg-emerald-50 text-emerald-600'],
    ['star', 'คะแนนเฉลี่ย', number_format($stats['rating_avg'], 2), 'จากรีวิวทั้งหมด', 'bg-violet-50 text-violet-600'],
  ];
  foreach ($metrics as $m): ?>
  <div class="ow-metric">
    <div class="flex items-start gap-3 relative z-10">
      <div class="ow-metric__icon <?= $m[4] ?>"><i data-lucide="<?= $m[0] ?>" class="w-5 h-5"></i></div>
      <div class="min-w-0 flex-1">
        <div class="text-xs text-slate-500 font-medium"><?= e($m[1]) ?></div>
        <div class="text-3xl font-bold text-slate-900 mt-0.5 leading-tight"><?= $m[2] ?></div>
        <div class="text-xs text-core-600 font-medium mt-1"><?= e($m[3]) ?></div>
      </div>
    </div>
    <i data-lucide="<?= $m[0] ?>" class="ow-metric__watermark text-slate-900"></i>
  </div>
  <?php endforeach; ?>
</div>

<?php if ($membership_owner): ?>
<div class="ow-membership mb-5">
  <div class="flex items-start gap-3">
    <div class="w-10 h-10 rounded-xl bg-white/10 grid place-items-center shrink-0">
      <i data-lucide="<?= $membership_is_vip && $membership_benefits_active ? 'crown' : 'badge-check' ?>" class="w-5 h-5"></i>
    </div>
    <div class="min-w-0">
      <h3 class="font-bold text-base">
        <?php if ($membership_is_vip && $membership_benefits_active): ?>
          สมาชิก VIP ใช้งานอยู่
        <?php elseif ($membership_benefits_active): ?>
          สมาชิกธรรมดาใช้งานอยู่
        <?php else: ?>
          สถานะสมาชิก / สิทธิ์พิเศษ
        <?php endif; ?>
      </h3>
      <?php if ($membership_is_vip && $membership_benefits_active): ?>
        <p class="text-sm text-white/80 mt-1 leading-relaxed">รับแจ้งเตือน Lead จากลูกค้าที่ตรงโซนและงบของที่พักคุณ — แนะนำผูก LINE และเปิดการแจ้งเตือน</p>
      <?php elseif (!$membership_benefits_active): ?>
        <p class="text-sm text-white/80 mt-1">สิทธิ์แพ็กเกจหมดอายุหรืออยู่นอกระยะใช้งาน — ต่ออายุเพื่อเปิดฟีเจอร์สมาชิก</p>
      <?php else: ?>
        <p class="text-sm text-white/80 mt-1">จัดการแพ็กเกจและสิทธิ์พิเศษสำหรับเจ้าของแพ</p>
      <?php endif; ?>
      <?php if ($membership_benefits_active && !$membership_line_linked): ?>
        <p class="text-xs text-rose-200 mt-2 font-medium">ยังไม่ได้ผูก LINE — อาจพลาดการแจ้งเตือน</p>
      <?php endif; ?>
    </div>
  </div>
  <a href="<?= url('/owner/membership') ?>" class="ow-btn-primary w-full mt-4">
    <i data-lucide="award" class="w-4 h-4"></i> จัดการสมาชิก
  </a>
</div>
<?php endif; ?>

<!-- My properties -->
<section class="mb-5">
  <div class="ow-section-head">
    <h3 class="ow-section-title"><i data-lucide="hotel" class="w-5 h-5 text-core-600"></i> ที่พักของฉัน</h3>
    <a href="<?= url('/owner/properties') ?>" class="ow-btn-ghost">ดูทั้งหมด <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
  </div>
  <div class="space-y-3">
    <?php foreach ($myProperties as $p):
      $st = $propStatusLabels[$p['status']] ?? ['draft', 'ow-status-pill--draft'];
    ?>
    <div class="ow-prop-card">
      <img src="<?= e(upload_url($p['cover_image']) ?: 'https://placehold.co/120x120') ?>" alt="" class="w-16 h-16 rounded-xl object-cover shrink-0 bg-slate-100">
      <div class="min-w-0 flex-1">
        <div class="font-semibold text-slate-800 truncate"><?= e($p['name']) ?></div>
        <span class="ow-status-pill <?= $st[1] ?> mt-1"><span class="ow-status-dot"></span><?= e($st[0]) ?></span>
        <div class="flex items-center gap-3 mt-1.5 text-xs text-slate-500">
          <span class="inline-flex items-center gap-1"><i data-lucide="bed-double" class="w-3.5 h-3.5"></i><?= (int)($p['booking_count'] ?? 0) ?></span>
          <span class="inline-flex items-center gap-1"><i data-lucide="star" class="w-3.5 h-3.5 text-amber-500 fill-amber-400"></i><?= number_format((float)($p['rating_avg'] ?? 0), 1) ?></span>
        </div>
      </div>
      <a href="<?= url('/owner/properties/' . $p['id'] . '/edit') ?>" class="w-9 h-9 rounded-xl bg-slate-50 hover:bg-core-50 text-slate-600 hover:text-core-600 grid place-items-center shrink-0 transition" aria-label="แก้ไข">
        <i data-lucide="pencil" class="w-4 h-4"></i>
      </a>
    </div>
    <?php endforeach; ?>
    <?php if (empty($myProperties)): ?>
    <div class="ow-empty">
      <i data-lucide="hotel" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
      <p class="text-sm">ยังไม่มีที่พัก</p>
      <a href="<?= url('/owner/properties/create') ?>" class="ow-btn-primary mt-3">เพิ่มที่พักแรก</a>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- Chart -->
<section class="ow-card p-5 mb-5">
  <div class="ow-section-head mb-4">
    <h3 class="ow-section-title"><i data-lucide="bar-chart-3" class="w-5 h-5 text-core-600"></i> การจองใน 14 วันล่าสุด</h3>
  </div>
  <?php $maxC = max(1, max(array_column($chart, 'count'))); ?>
  <div class="grid gap-1.5 h-36 items-end" style="grid-template-columns: repeat(14,minmax(0,1fr));">
    <?php foreach ($chart as $pt):
      $h = max(6, (int)round(($pt['count'] / $maxC) * 100));
      $active = $pt['count'] > 0;
    ?>
    <div class="flex flex-col items-center justify-end h-full group">
      <div class="text-[9px] text-slate-500 font-semibold mb-0.5 <?= $active ? 'text-core-600' : '' ?>"><?= $pt['count'] ?></div>
      <div class="w-full rounded-t-md transition <?= $active ? 'bg-core-600' : 'bg-slate-200' ?>" style="height: <?= $h ?>%"></div>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="grid gap-1.5 mt-2 text-[9px] text-slate-400 text-center" style="grid-template-columns: repeat(14,minmax(0,1fr));">
    <?php foreach ($chart as $pt): ?><div><?= $pt['date'] ?></div><?php endforeach; ?>
  </div>
</section>

<!-- Recent bookings -->
<section class="mb-4">
  <div class="ow-section-head">
    <h3 class="ow-section-title"><i data-lucide="clock" class="w-5 h-5 text-core-600"></i> การจองล่าสุด</h3>
    <a href="<?= url('/owner/bookings') ?>" class="ow-btn-ghost">ดูทั้งหมด <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
  </div>

  <?php if (empty($recentBookings)): ?>
  <div class="ow-empty">
    <div class="w-14 h-14 rounded-2xl bg-slate-100 grid place-items-center mx-auto mb-3">
      <i data-lucide="inbox" class="w-7 h-7 text-slate-400"></i>
    </div>
    <p class="font-semibold text-slate-700">ยังไม่มีรายการจองในตอนนี้</p>
    <p class="text-xs text-slate-500 mt-1">เมื่อมีลูกค้าจอง รายการจะแสดงที่นี่ทันที</p>
  </div>
  <?php else: ?>

  <!-- Mobile cards -->
  <div class="space-y-3 lg:hidden">
    <?php foreach ($recentBookings as $b):
      $c = $bookingColors[$b['status']] ?? 'slate';
      $sti = $bookingStatusIcons[$b['status']] ?? 'circle-dot';
    ?>
    <a href="<?= url('/owner/bookings/' . $b['id']) ?>" class="ow-card block p-4 hover:border-core-200 border border-transparent transition">
      <div class="flex items-start justify-between gap-2">
        <div>
          <div class="font-mono text-xs text-core-600 font-semibold"><?= e($b['code']) ?></div>
          <div class="font-semibold text-slate-800 mt-0.5"><?= e($b['guest_name']) ?></div>
          <div class="text-xs text-slate-500 mt-0.5"><?= e($b['property_name']) ?></div>
        </div>
        <span class="inline-flex items-center gap-1 text-[10px] font-bold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2 py-1 rounded-full shrink-0">
          <i data-lucide="<?= e($sti) ?>" class="w-3 h-3"></i><?= e($b['status']) ?>
        </span>
      </div>
      <div class="flex items-center justify-between mt-3 text-xs">
        <span class="text-slate-500"><?= format_date_th($b['check_in']) ?> → <?= format_date_th($b['check_out']) ?></span>
        <span class="font-bold text-core-700"><?= format_money($b['total_price']) ?></span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Desktop table -->
  <div class="ow-card overflow-hidden hidden lg:block">
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
          <tr>
            <th class="text-left px-5 py-3">รหัส</th>
            <th class="text-left px-5 py-3">ผู้จอง</th>
            <th class="text-left px-5 py-3">ที่พัก / ห้อง</th>
            <th class="text-left px-5 py-3">วันที่</th>
            <th class="text-left px-5 py-3">รวม</th>
            <th class="text-left px-5 py-3">สถานะ</th>
            <th class="text-right px-5 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        <?php foreach ($recentBookings as $b):
          $c = $bookingColors[$b['status']] ?? 'slate';
          $sti = $bookingStatusIcons[$b['status']] ?? 'circle-dot';
        ?>
          <tr class="hover:bg-slate-50/80">
            <td class="px-5 py-3 font-mono text-xs text-core-600"><?= e($b['code']) ?></td>
            <td class="px-5 py-3"><?= e($b['guest_name']) ?><div class="text-xs text-slate-500"><?= e($b['guest_phone']) ?></div></td>
            <td class="px-5 py-3"><?= e($b['property_name']) ?><div class="text-xs text-slate-500"><?= e($b['unit_name']) ?></div></td>
            <td class="px-5 py-3 text-xs whitespace-nowrap"><?= format_date_th($b['check_in']) ?> → <?= format_date_th($b['check_out']) ?></td>
            <td class="px-5 py-3 font-semibold text-core-700"><?= format_money($b['total_price']) ?></td>
            <td class="px-5 py-3">
              <span class="inline-flex items-center gap-1 text-xs font-semibold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2 py-1 rounded-full">
                <i data-lucide="<?= e($sti) ?>" class="w-3.5 h-3.5"></i><?= e($b['status']) ?>
              </span>
            </td>
            <td class="px-5 py-3 text-right">
              <a href="<?= url('/owner/bookings/' . $b['id']) ?>" class="ow-btn-primary !py-1.5 !px-3 !text-xs"><i data-lucide="eye" class="w-3.5 h-3.5"></i> ดู</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</section>

<!-- Mobile footer links -->
<div class="lg:hidden text-center text-xs text-slate-500 pt-2 pb-1 space-y-2">
  <p>© <?= date('Y') ?> paekarn.com — Owner Portal</p>
  <div class="flex justify-center gap-4">
    <a href="<?= url('/privacy') ?>" class="hover:text-core-600">นโยบายความเป็นส่วนตัว</a>
    <a href="<?= url('/contact') ?>" class="hover:text-core-600">ติดต่อเรา</a>
  </div>
</div>

<!-- Desktop extras: month stats -->
<div class="hidden lg:grid grid-cols-3 gap-4 mt-2">
  <div class="ow-card p-4">
    <div class="text-xs text-slate-500">ยอดจองเดือนนี้</div>
    <div class="text-xl font-bold text-emerald-700 mt-1"><?= format_money($stats['revenue_month']) ?></div>
  </div>
  <div class="ow-card p-4">
    <div class="text-xs text-slate-500">คูปองที่ใช้เดือนนี้</div>
    <div class="text-xl font-bold text-rose-700 mt-1"><?= format_money($stats['coupon_face_month']) ?></div>
  </div>
  <div class="ow-card p-4">
    <div class="text-xs text-slate-500">Lead จากเว็บเดือนนี้</div>
    <div class="text-xl font-bold text-core-700 mt-1"><?= number_format($stats['leads_month']) ?> <span class="text-sm font-normal text-slate-500">รายการ</span></div>
  </div>
</div>
