<?php
/** @var array|null $homeCalendar @var list<array> $calProperties */
if (!$homeCalendar || empty($calProperties)) return;

$thaiMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
$month    = (int)$homeCalendar['month'];
$year     = (int)$homeCalendar['year'];
$pid      = (int)$homeCalendar['property_id'];
$unitId   = (int)$homeCalendar['unit_id'];
$dayMeta  = $homeCalendar['dayMeta'];
$bookings = $homeCalendar['bookingsByDate'];
$daysInMonth  = (int)$homeCalendar['daysInMonth'];
$startWeekday = (int)$homeCalendar['startWeekday'];
$prevM = $month === 1 ? 12 : $month - 1;
$prevY = $month === 1 ? $year - 1 : $year;
$nextM = $month === 12 ? 1 : $month + 1;
$nextY = $month === 12 ? $year + 1 : $year;
$baseQ = static fn(array $extra = []) => url('/owner/dashboard') . '?' . http_build_query(array_merge([
    'cal_p' => $pid, 'cal_u' => $unitId, 'cal_m' => $month, 'cal_y' => $year,
], $extra));
$fullCalUrl = url('/owner/properties/' . $pid . '/availability') . '?unit=' . $unitId . '&month=' . $month . '&year=' . $year;
?>

<section class="ow-card p-4 mb-5 lg:hidden">
  <div class="flex items-start justify-between gap-2 mb-3">
    <div class="min-w-0">
      <h3 class="font-bold text-slate-800 flex items-center gap-2">
        <i data-lucide="calendar-range" class="w-5 h-5 text-core-600 shrink-0"></i>
        ตารางที่พัก
      </h3>
      <p class="text-xs text-slate-500 mt-0.5 truncate"><?= e($homeCalendar['property_name']) ?></p>
    </div>
    <a href="<?= e($fullCalUrl) ?>" class="ow-btn-primary !py-1.5 !px-3 !text-xs shrink-0">จัดการ</a>
  </div>

  <?php if (count($calProperties) > 1): ?>
  <form method="get" class="mb-3">
    <input type="hidden" name="cal_m" value="<?= $month ?>">
    <input type="hidden" name="cal_y" value="<?= $year ?>">
    <select name="cal_p" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm mb-2">
      <?php foreach ($calProperties as $cp): ?>
      <option value="<?= (int)$cp['id'] ?>" <?= (int)$cp['id'] === $pid ? 'selected' : '' ?>><?= e($cp['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($homeCalendar['units']) && count($homeCalendar['units']) > 1): ?>
    <select name="cal_u" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
      <?php foreach ($homeCalendar['units'] as $u): ?>
      <option value="<?= (int)$u['id'] ?>" <?= (int)$u['id'] === $unitId ? 'selected' : '' ?>><?= e($u['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
  </form>
  <?php elseif (!empty($homeCalendar['units']) && count($homeCalendar['units']) > 1): ?>
  <form method="get" class="mb-3">
    <input type="hidden" name="cal_p" value="<?= $pid ?>">
    <input type="hidden" name="cal_m" value="<?= $month ?>">
    <input type="hidden" name="cal_y" value="<?= $year ?>">
    <select name="cal_u" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
      <?php foreach ($homeCalendar['units'] as $u): ?>
      <option value="<?= (int)$u['id'] ?>" <?= (int)$u['id'] === $unitId ? 'selected' : '' ?>><?= e($u['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <?php endif; ?>

  <div class="flex items-center justify-between mb-2">
    <a href="<?= e($baseQ(['cal_m' => $prevM, 'cal_y' => $prevY])) ?>" class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-50"><i data-lucide="chevron-left" class="w-4 h-4"></i></a>
    <span class="text-sm font-bold"><?= $thaiMonths[$month] ?> <?= $year + 543 ?></span>
    <a href="<?= e($baseQ(['cal_m' => $nextM, 'cal_y' => $nextY])) ?>" class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-50"><i data-lucide="chevron-right" class="w-4 h-4"></i></a>
  </div>

  <div class="flex flex-wrap gap-2 text-[10px] text-slate-500 mb-2">
    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-emerald-100 border border-emerald-300"></span>ว่าง</span>
    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-amber-100 border border-amber-300"></span>จอง</span>
    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-rose-100 border border-rose-300"></span>เต็ม</span>
    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-slate-300 border border-slate-400"></span>ปิด</span>
  </div>

  <div class="grid grid-cols-7 gap-0.5 mb-0.5 text-center text-[10px] font-semibold text-slate-400">
    <?php foreach (['อา','จ','อ','พ','พฤ','ศ','ส'] as $d): ?><div><?= $d ?></div><?php endforeach; ?>
  </div>
  <div class="grid grid-cols-7 gap-1">
    <?php for ($i = 0; $i < $startWeekday; $i++): ?><div></div><?php endfor; ?>
    <?php for ($d = 1; $d <= $daysInMonth; $d++):
      $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
      $meta = $dayMeta[$date] ?? ['key'=>'open','label'=>'ว่าง','cls'=>'bg-emerald-100 border-emerald-300 text-emerald-800'];
      $isPast = ($meta['key'] ?? '') === 'past';
      $dayBookings = $bookings[$date] ?? [];
      $firstBookingId = !empty($dayBookings) ? (int)$dayBookings[0]['id'] : 0;
      $href = $firstBookingId
        ? url('/owner/bookings/' . $firstBookingId)
        : $fullCalUrl;
    ?>
    <?php if ($isPast): ?>
    <div class="aspect-square rounded-lg border border-slate-100 bg-slate-50 flex flex-col items-center justify-center opacity-50">
      <span class="text-xs font-bold text-slate-400"><?= $d ?></span>
    </div>
    <?php else: ?>
    <a href="<?= e($href) ?>"
       class="aspect-square rounded-lg border <?= e($meta['cls']) ?> flex flex-col items-center justify-center hover:shadow-sm transition active:scale-95">
      <span class="text-xs font-bold leading-none"><?= $d ?></span>
      <?php if (!empty($meta['label'])): ?>
      <span class="text-[8px] font-medium leading-tight mt-0.5"><?= e($meta['label']) ?></span>
      <?php endif; ?>
    </a>
    <?php endif; ?>
    <?php endfor; ?>
  </div>

  <p class="text-[10px] text-slate-500 mt-3 text-center">แตะวัน «จอง» ดูรายละเอียด · แตะวันอื่นเพื่อจัดการปฏิทิน</p>
</section>
