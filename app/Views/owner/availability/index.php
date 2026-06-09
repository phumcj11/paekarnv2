<?php
/** @var array $property @var array $units @var int $unitId @var int $month @var int $year @var array $availMap */
$thaiMonths = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
$firstDay   = mktime(0,0,0,$month,1,$year);
$daysInMonth= (int)date('t', $firstDay);
$startWeekday = (int)date('w', $firstDay); // 0=Sun
$prevMonth = $month==1 ? 12 : $month-1; $prevYear = $month==1 ? $year-1 : $year;
$nextMonth = $month==12 ? 1 : $month+1;  $nextYear = $month==12 ? $year+1 : $year;
?>

<div class="flex items-center justify-between mb-4">
  <a href="<?= url('/owner/properties/' . $property['id'] . '/edit') ?>" class="text-sm text-slate-500 hover:text-accent-700 inline-flex items-center gap-1"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</a>
  <h2 class="font-bold flex items-center gap-2"><i data-lucide="calendar" class="w-5 h-5 text-accent-600"></i> ปฏิทินวันว่าง — <?= e($property['name']) ?></h2>
  <div></div>
</div>

<?php if (empty($units)): ?>
<div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
  <i data-lucide="bed-double" class="w-12 h-12 mx-auto text-slate-400"></i>
  <h3 class="mt-3 font-semibold">ยังไม่มีห้องพัก</h3>
  <p class="text-sm text-slate-500 mt-1">เพิ่มห้องพักก่อนใช้งานปฏิทินวันว่าง</p>
  <a href="<?= url('/owner/properties/' . $property['id'] . '/units/create') ?>" class="mt-4 inline-flex items-center gap-1.5 px-5 py-2.5 bg-accent-500 text-white rounded-xl font-semibold"><i data-lucide="plus-circle" class="w-4 h-4"></i> เพิ่มห้องพัก</a>
</div>
<?php else: ?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
  <!-- Side controls -->
  <div class="lg:col-span-1 space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4">
      <h3 class="font-bold text-sm mb-2">เลือกห้อง</h3>
      <form method="get">
        <input type="hidden" name="month" value="<?= $month ?>">
        <input type="hidden" name="year"  value="<?= $year ?>">
        <select name="unit" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <?php foreach ($units as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $unitId==$u['id']?'selected':'' ?>><?= e($u['name']) ?> (รวม <?= $u['total_units'] ?> หลัง)</option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4 text-xs space-y-1.5">
      <h3 class="font-bold text-sm mb-2">คำอธิบายสี</h3>
      <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-emerald-100 border border-emerald-300"></span> เปิด — ว่าง</div>
      <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-amber-100 border border-amber-300"></span> มีจองแล้ว</div>
      <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-rose-100 border border-rose-300"></span> เต็ม / ปิด</div>
      <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-slate-100 border border-slate-300"></span> ยังไม่ตั้งค่า</div>
    </div>
  </div>

  <!-- Calendar -->
  <div class="lg:col-span-3">
    <form id="avForm" method="post" action="<?= url('/owner/properties/' . $property['id'] . '/availability/save') ?>" class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <?= csrf() ?>
      <input type="hidden" name="unit_id" value="<?= $unitId ?>">
      <input type="hidden" name="month" value="<?= $month ?>">
      <input type="hidden" name="year"  value="<?= $year ?>">

      <div class="flex items-center justify-between mb-4">
        <a href="?unit=<?= $unitId ?>&month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="px-3 py-1.5 border border-slate-300 rounded-lg hover:bg-slate-50 inline-flex items-center gap-1 text-sm"><i data-lucide="chevron-left" class="w-4 h-4"></i></a>
        <h3 class="text-lg font-bold"><?= $thaiMonths[$month] ?> <?= $year + 543 ?></h3>
        <a href="?unit=<?= $unitId ?>&month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="px-3 py-1.5 border border-slate-300 rounded-lg hover:bg-slate-50 inline-flex items-center gap-1 text-sm"><i data-lucide="chevron-right" class="w-4 h-4"></i></a>
      </div>

      <div class="grid grid-cols-7 gap-1 mb-1 text-center text-xs font-semibold text-slate-500">
        <?php foreach (['อา','จ','อ','พ','พฤ','ศ','ส'] as $d): ?><div><?= $d ?></div><?php endforeach; ?>
      </div>
      <div class="grid grid-cols-7 gap-1">
        <?php for ($i = 0; $i < $startWeekday; $i++): ?><div></div><?php endfor; ?>
        <?php for ($d = 1; $d <= $daysInMonth; $d++):
          $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
          $row = $availMap[$date] ?? null;
          $status = $row['status'] ?? null;
          $booked = $row['booked'] ?? 0;
          $cls = 'bg-slate-100 border-slate-300 text-slate-700'; // default unset
          if ($status === 'open') $cls = 'bg-emerald-100 border-emerald-300 text-emerald-800';
          if ($status === 'closed' || $status === 'blocked') $cls = 'bg-slate-300 border-slate-400 text-slate-700';
          if ($status === 'fully_booked' || $booked > 0) $cls = 'bg-amber-100 border-amber-300 text-amber-800';
          $isPast = strtotime($date) < strtotime(date('Y-m-d'));
          if ($isPast) $cls = 'bg-slate-50 border-slate-200 text-slate-400';
          ?>
          <label class="aspect-square <?= $isPast?'cursor-not-allowed opacity-60':'cursor-pointer hover:ring-2 ring-accent-400' ?> rounded-lg border-2 <?= $cls ?> flex flex-col items-center justify-center relative">
            <?php if (!$isPast): ?><input type="checkbox" name="dates[]" value="<?= $date ?>" class="absolute top-1 left-1 w-3 h-3"><?php endif; ?>
            <span class="text-base font-bold"><?= $d ?></span>
            <?php if ($booked > 0): ?>
              <span class="text-[9px]"><?= $booked ?> จอง</span>
            <?php elseif ($status): ?>
              <span class="text-[9px]"><?= $status ?></span>
            <?php endif; ?>
          </label>
        <?php endfor; ?>
      </div>

      <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <label class="text-sm font-medium mb-1 block">ตั้งสถานะ</label>
          <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300">
            <option value="open">เปิดให้จอง</option>
            <option value="closed">ปิด (พักรับจอง)</option>
            <option value="fully_booked">เต็ม</option>
            <option value="blocked">บล็อก (เช่น ใช้ส่วนตัว)</option>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">จำนวนหลังว่าง</label>
          <input type="number" min="0" name="available_units" value="1" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div class="md:flex md:items-end">
          <button type="submit" class="w-full py-2.5 bg-accent-500 hover:bg-accent-600 text-white font-semibold rounded-lg inline-flex items-center justify-center gap-2"><i data-lucide="save" class="w-4 h-4"></i> บันทึก</button>
        </div>
      </div>
      <p class="text-xs text-slate-500 mt-2">เลือกวันที่ (ติ๊ก checkbox) ที่ต้องการเปลี่ยนสถานะแล้วกดบันทึก</p>
    </form>
  </div>
</div>
<?php endif; ?>
