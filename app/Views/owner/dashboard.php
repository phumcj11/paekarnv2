<?php /** @var array $stats @var array $recentBookings @var array $myProperties @var array $chart
           @var array|null $membership_owner @var bool $membership_benefits_active @var bool $membership_line_linked @var bool $membership_is_vip
           @var array|null $homeCalendar @var list<array> $calProperties */

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

<!-- ==================== MOBILE LAYOUT ==================== -->
<div class="lg:hidden">
  <?php \App\Core\View::partial('owner/partials/home_calendar_mobile', [
    'homeCalendar' => $homeCalendar ?? null,
    'calProperties' => $calProperties ?? [],
  ]); ?>

  <?php if ($stats['properties'] === 0): ?>
  <div class="ow-card p-5 mb-5 bg-gradient-to-br from-core-600 to-core-800 text-white border-0">
    <h2 class="text-lg font-bold flex items-center gap-2"><i data-lucide="rocket" class="w-5 h-5"></i> เริ่มต้นใช้งาน</h2>
    <p class="text-white/85 mt-1 text-sm">เพิ่มที่พักแรกของคุณ เพื่อเริ่มรับการจอง</p>
    <a href="<?= url('/owner/properties/create') ?>" class="inline-flex items-center gap-2 bg-white text-core-700 px-5 py-2.5 rounded-xl font-semibold mt-4">
      <i data-lucide="plus-circle" class="w-4 h-4"></i> เพิ่มที่พัก
    </a>
  </div>
  <?php endif; ?>

  <div class="grid grid-cols-2 gap-3 mb-5">
    <a href="<?= url('/owner/bookings/create') ?>" class="ow-quick-btn group">
      <div class="ow-quick-btn__icon bg-blue-50 text-blue-600"><i data-lucide="plus" class="w-6 h-6"></i></div>
      <span class="text-sm font-semibold text-slate-700">เพิ่มการจอง</span>
    </a>
    <a href="<?= url('/owner/bookings') ?>" class="ow-quick-btn group">
      <div class="ow-quick-btn__icon bg-emerald-50 text-emerald-600"><i data-lucide="clipboard-list" class="w-6 h-6"></i></div>
      <span class="text-sm font-semibold text-slate-700">รายการจอง</span>
    </a>
    <a href="<?= e($scheduleUrl) ?>" class="ow-quick-btn group">
      <div class="ow-quick-btn__icon bg-sky-50 text-sky-600"><i data-lucide="calendar-range" class="w-6 h-6"></i></div>
      <span class="text-sm font-semibold text-slate-700">ตารางที่พัก</span>
    </a>
    <a href="<?= url('/owner/coupons/verify') ?>" class="ow-quick-btn group">
      <div class="ow-quick-btn__icon bg-rose-50 text-rose-600"><i data-lucide="ticket" class="w-6 h-6"></i></div>
      <span class="text-sm font-semibold text-slate-700">ตรวจคูปอง</span>
    </a>
  </div>

  <div class="grid grid-cols-2 gap-3 mb-5">
    <?php
    $mobileMetrics = [
      ['hotel', 'ที่พัก', $stats['properties'], 'bg-blue-50 text-blue-600'],
      ['calendar-check', 'จอง', $stats['bookings_total'], 'bg-amber-50 text-amber-600'],
      ['banknote', 'รายได้', '฿' . number_format($stats['revenue']), 'bg-emerald-50 text-emerald-600'],
      ['star', 'คะแนน', number_format($stats['rating_avg'], 1), 'bg-violet-50 text-violet-600'],
    ];
    foreach ($mobileMetrics as $m): ?>
    <div class="ow-card p-4">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-lg <?= $m[3] ?> grid place-items-center"><i data-lucide="<?= $m[0] ?>" class="w-4 h-4"></i></div>
        <div>
          <div class="text-lg font-bold text-slate-900 leading-tight"><?= $m[2] ?></div>
          <div class="text-[10px] text-slate-500"><?= e($m[1]) ?></div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($membership_owner): ?>
  <div class="ow-membership mb-5">
    <h3 class="font-bold">สถานะสมาชิก</h3>
    <p class="text-sm text-white/80 mt-1"><?= $membership_benefits_active ? 'สิทธิ์ใช้งานอยู่' : 'ต่ออายุเพื่อเปิดฟีเจอร์สมาชิก' ?></p>
    <a href="<?= url('/owner/membership') ?>" class="ow-btn-primary w-full mt-3"><i data-lucide="award" class="w-4 h-4"></i> จัดการสมาชิก</a>
  </div>
  <?php endif; ?>

  <section class="mb-5">
    <div class="ow-section-head">
      <h3 class="ow-section-title"><i data-lucide="clock" class="w-5 h-5 text-core-600"></i> การจองล่าสุด</h3>
      <a href="<?= url('/owner/bookings') ?>" class="ow-btn-ghost text-xs">ดูทั้งหมด</a>
    </div>
    <?php if (empty($recentBookings)): ?>
    <div class="ow-empty py-6">
      <i data-lucide="inbox" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
      <p class="text-sm text-slate-500">ยังไม่มีการจอง</p>
    </div>
    <?php else: ?>
    <div class="space-y-2">
      <?php foreach (array_slice($recentBookings, 0, 5) as $b):
        $c = $bookingColors[$b['status']] ?? 'slate';
      ?>
      <a href="<?= url('/owner/bookings/' . $b['id']) ?>" class="ow-card block p-3">
        <div class="flex justify-between items-start gap-2">
          <div class="min-w-0">
            <div class="font-semibold text-sm truncate"><?= e($b['guest_name']) ?></div>
            <div class="text-xs text-slate-500"><?= format_date_th($b['check_in']) ?> → <?= format_date_th($b['check_out']) ?></div>
          </div>
          <span class="text-[10px] font-bold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2 py-0.5 rounded-full shrink-0"><?= e($b['status']) ?></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <div class="text-center text-xs text-slate-500 pt-2 pb-1 space-y-2">
    <p>© <?= date('Y') ?> paekarn.com — Owner Portal</p>
    <div class="flex justify-center gap-4">
      <a href="<?= url('/contact') ?>" class="hover:text-core-600">ติดต่อเรา</a>
    </div>
  </div>
</div>

<!-- ==================== DESKTOP LAYOUT ==================== -->
<div class="hidden lg:block">
  <?php if ($stats['properties'] === 0): ?>
  <div class="ow-card p-5 mb-5 bg-gradient-to-br from-core-600 to-core-800 text-white border-0">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h2 class="text-xl font-bold flex items-center gap-2"><i data-lucide="rocket" class="w-6 h-6"></i> เริ่มต้นใช้งาน</h2>
        <p class="text-white/90 mt-1 text-sm">เพิ่มที่พักแรกของคุณ เพื่อเริ่มรับการจอง</p>
      </div>
      <a href="<?= url('/owner/properties/create') ?>" class="inline-flex items-center gap-2 bg-white text-core-700 px-5 py-2.5 rounded-xl font-semibold hover:bg-core-50">
        <i data-lucide="plus-circle" class="w-4 h-4"></i> เพิ่มที่พัก
      </a>
    </div>
  </div>
  <?php endif; ?>

  <div class="grid grid-cols-4 gap-3 mb-5">
    <a href="<?= url('/owner/bookings/create') ?>" class="ow-quick-btn group !flex-row !text-left !p-4">
      <div class="ow-quick-btn__icon bg-blue-50 text-blue-600 !w-10 !h-10"><i data-lucide="plus" class="w-5 h-5"></i></div>
      <span class="text-sm font-semibold">เพิ่มการจอง</span>
    </a>
    <a href="<?= url('/owner/bookings') ?>" class="ow-quick-btn group !flex-row !text-left !p-4">
      <div class="ow-quick-btn__icon bg-emerald-50 text-emerald-600 !w-10 !h-10"><i data-lucide="clipboard-list" class="w-5 h-5"></i></div>
      <span class="text-sm font-semibold">รายการจอง</span>
    </a>
    <a href="<?= e($scheduleUrl) ?>" class="ow-quick-btn group !flex-row !text-left !p-4">
      <div class="ow-quick-btn__icon bg-sky-50 text-sky-600 !w-10 !h-10"><i data-lucide="calendar-range" class="w-5 h-5"></i></div>
      <span class="text-sm font-semibold">ตารางที่พัก</span>
    </a>
    <a href="<?= url('/owner/coupons/verify') ?>" class="ow-quick-btn group !flex-row !text-left !p-4">
      <div class="ow-quick-btn__icon bg-rose-50 text-rose-600 !w-10 !h-10"><i data-lucide="ticket" class="w-5 h-5"></i></div>
      <span class="text-sm font-semibold">ตรวจคูปอง</span>
    </a>
  </div>

  <div class="grid grid-cols-3 gap-4 mb-5">
    <div class="ow-card p-4"><div class="text-xs text-slate-500">ยอดจองเดือนนี้</div><div class="text-2xl font-bold text-emerald-700 mt-1"><?= format_money($stats['revenue_month']) ?></div></div>
    <div class="ow-card p-4"><div class="text-xs text-slate-500">คูปองที่ใช้เดือนนี้</div><div class="text-2xl font-bold text-rose-700 mt-1"><?= format_money($stats['coupon_face_month']) ?></div></div>
    <div class="ow-card p-4"><div class="text-xs text-slate-500">Lead จากเว็บเดือนนี้</div><div class="text-2xl font-bold text-core-700 mt-1"><?= number_format($stats['leads_month']) ?></div></div>
  </div>

  <div class="grid grid-cols-4 gap-4 mb-6">
    <?php
    $metrics = [
      ['hotel', 'ที่พักทั้งหมด', $stats['properties'], 'เผยแพร่ ' . $stats['published'] . ' แห่ง', 'bg-blue-50 text-blue-600'],
      ['calendar-check', 'การจองทั้งหมด', $stats['bookings_total'], 'รอเช็คอิน ' . $stats['bookings_pending'], 'bg-amber-50 text-amber-600'],
      ['banknote', 'รายได้รวม', '฿' . number_format($stats['revenue']), 'จากยอดชำระแล้ว', 'bg-emerald-50 text-emerald-600'],
      ['star', 'คะแนนเฉลี่ย', number_format($stats['rating_avg'], 2), 'จากรีวิวทั้งหมด', 'bg-violet-50 text-violet-600'],
    ];
    foreach ($metrics as $m): ?>
    <div class="ow-card p-5 relative overflow-hidden">
      <div class="ow-metric__icon <?= $m[4] ?> mb-3"><i data-lucide="<?= $m[0] ?>" class="w-5 h-5"></i></div>
      <div class="text-3xl font-bold text-slate-900"><?= $m[2] ?></div>
      <div class="text-xs text-slate-500 mt-0.5"><?= e($m[1]) ?></div>
      <div class="text-xs text-core-600 font-medium mt-1"><?= e($m[3]) ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if ($membership_owner): ?>
  <div class="ow-membership mb-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h3 class="font-bold text-lg">สถานะสมาชิก / สิทธิ์พิเศษ</h3>
        <p class="text-sm text-white/80 mt-1"><?= $membership_benefits_active ? 'สิทธิ์ใช้งานอยู่' : 'ต่ออายุเพื่อเปิดฟีเจอร์สมาชิก' ?></p>
      </div>
      <a href="<?= url('/owner/membership') ?>" class="ow-btn-primary shrink-0"><i data-lucide="award" class="w-4 h-4"></i> จัดการสมาชิก</a>
    </div>
  </div>
  <?php endif; ?>

  <div class="grid grid-cols-3 gap-5 mb-6">
    <section class="col-span-2 ow-card p-5">
      <h3 class="ow-section-title mb-4"><i data-lucide="bar-chart-3" class="w-5 h-5 text-core-600"></i> การจองใน 14 วันล่าสุด</h3>
      <?php $maxC = max(1, max(array_column($chart, 'count'))); ?>
      <div class="grid gap-1.5 h-40 items-end" style="grid-template-columns: repeat(14,minmax(0,1fr));">
        <?php foreach ($chart as $pt):
          $h = max(6, (int)round(($pt['count'] / $maxC) * 100));
          $active = $pt['count'] > 0;
        ?>
        <div class="flex flex-col items-center justify-end h-full">
          <div class="text-[9px] font-semibold mb-0.5 <?= $active ? 'text-core-600' : 'text-slate-400' ?>"><?= $pt['count'] ?></div>
          <div class="w-full rounded-t-md <?= $active ? 'bg-core-600' : 'bg-slate-200' ?>" style="height: <?= $h ?>%"></div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="grid gap-1.5 mt-2 text-[9px] text-slate-400 text-center" style="grid-template-columns: repeat(14,minmax(0,1fr));">
        <?php foreach ($chart as $pt): ?><div><?= $pt['date'] ?></div><?php endforeach; ?>
      </div>
    </section>

    <section>
      <div class="ow-section-head">
        <h3 class="ow-section-title"><i data-lucide="hotel" class="w-5 h-5 text-core-600"></i> ที่พักของฉัน</h3>
        <a href="<?= url('/owner/properties') ?>" class="ow-btn-ghost text-xs">ดูทั้งหมด</a>
      </div>
      <div class="space-y-2">
        <?php foreach ($myProperties as $p):
          $st = $propStatusLabels[$p['status']] ?? ['draft', 'ow-status-pill--draft'];
        ?>
        <div class="ow-prop-card">
          <img src="<?= e(upload_url($p['cover_image']) ?: 'https://placehold.co/80x80') ?>" alt="" class="w-12 h-12 rounded-lg object-cover shrink-0">
          <div class="min-w-0 flex-1">
            <div class="font-semibold text-sm truncate"><?= e($p['name']) ?></div>
            <span class="ow-status-pill <?= $st[1] ?> mt-0.5"><span class="ow-status-dot"></span><?= e($st[0]) ?></span>
          </div>
          <a href="<?= url('/owner/properties/' . $p['id'] . '/edit') ?>" class="text-slate-400 hover:text-core-600"><i data-lucide="pencil" class="w-4 h-4"></i></a>
        </div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>

  <section>
    <div class="ow-section-head">
      <h3 class="ow-section-title"><i data-lucide="clock" class="w-5 h-5 text-core-600"></i> การจองล่าสุด</h3>
      <a href="<?= url('/owner/bookings') ?>" class="ow-btn-ghost">ดูทั้งหมด</a>
    </div>
    <?php if (empty($recentBookings)): ?>
    <div class="ow-empty">ยังไม่มีรายการจอง</div>
    <?php else: ?>
    <div class="ow-card overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
          <tr>
            <th class="text-left px-5 py-3">รหัส</th>
            <th class="text-left px-5 py-3">ผู้จอง</th>
            <th class="text-left px-5 py-3">ที่พัก</th>
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
            <td class="px-5 py-3"><?= e($b['property_name']) ?></td>
            <td class="px-5 py-3 text-xs whitespace-nowrap"><?= format_date_th($b['check_in']) ?> → <?= format_date_th($b['check_out']) ?></td>
            <td class="px-5 py-3 font-semibold text-core-700"><?= format_money($b['total_price']) ?></td>
            <td class="px-5 py-3"><span class="inline-flex items-center gap-1 text-xs font-semibold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2 py-1 rounded-full"><i data-lucide="<?= e($sti) ?>" class="w-3.5 h-3.5"></i><?= e($b['status']) ?></span></td>
            <td class="px-5 py-3 text-right"><a href="<?= url('/owner/bookings/' . $b['id']) ?>" class="ow-btn-primary !py-1.5 !px-3 !text-xs">ดู</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>
</div>
