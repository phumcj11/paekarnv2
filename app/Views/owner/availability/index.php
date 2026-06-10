<?php
/** @var array $property @var array $units @var int $unitId @var int $month @var int $year @var array $availMap @var array $dayMeta @var int $totalUnits */
$thaiMonths = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
$firstDay     = mktime(0,0,0,$month,1,$year);
$daysInMonth  = (int)date('t', $firstDay);
$startWeekday = (int)date('w', $firstDay);
$prevMonth = $month==1 ? 12 : $month-1; $prevYear = $month==1 ? $year-1 : $year;
$nextMonth = $month==12 ? 1 : $month+1;  $nextYear = $month==12 ? $year+1 : $year;
$pid = (int)$property['id'];

// วันที่ในเดือน (สำหรับ JS)
$futureDates = [];
for ($d = 1; $d <= $daysInMonth; $d++) {
    $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
    if ($date >= date('Y-m-d')) $futureDates[] = $date;
}
?>

<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
  <a href="<?= url('/owner/properties/' . $pid . '/line') ?>" class="text-sm text-slate-500 hover:text-accent-700 inline-flex items-center gap-1">
    <i data-lucide="arrow-left" class="w-4 h-4"></i> ตั้งค่า LINE
  </a>
  <h2 class="font-bold flex items-center gap-2"><i data-lucide="calendar" class="w-5 h-5 text-accent-600"></i> ปฏิทินวันว่าง — <?= e($property['name']) ?></h2>
  <a href="<?= url('/owner/properties/' . $pid . '/edit') ?>" class="text-xs text-slate-500 hover:text-accent-700">แก้ไขที่พัก</a>
</div>

<?php if (empty($units)): ?>
<div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
  <i data-lucide="bed-double" class="w-12 h-12 mx-auto text-slate-400"></i>
  <h3 class="mt-3 font-semibold">ยังไม่มีห้องพัก</h3>
  <a href="<?= url('/owner/properties/' . $pid . '/units/create') ?>" class="mt-4 inline-flex items-center gap-1.5 px-5 py-2.5 bg-accent-500 text-white rounded-xl font-semibold">เพิ่มห้องพัก</a>
</div>
<?php else: ?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4" x-data="availabilityCal()" x-init="init()">
  <div class="lg:col-span-1 space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4">
      <h3 class="font-bold text-sm mb-2">เลือกห้อง/ยูนิต</h3>
      <form method="get">
        <input type="hidden" name="month" value="<?= $month ?>">
        <input type="hidden" name="year" value="<?= $year ?>">
        <select name="unit" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
          <?php foreach ($units as $u): ?>
          <option value="<?= $u['id'] ?>" <?= $unitId==$u['id']?'selected':'' ?>><?= e($u['name']) ?> (<?= (int)$u['total_units'] ?> หลัง)</option>
          <?php endforeach; ?>
        </select>
      </form>
      <p class="text-[11px] text-slate-500 mt-2">LINE แสดง «ว่าง» ถ้ามียูนิตใดยูนิตหนึ่งว่างในวันนั้น</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4 text-xs space-y-2">
      <h3 class="font-bold text-sm mb-1">สีในปฏิทิน</h3>
      <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-emerald-100 border border-emerald-300"></span> ว่าง — รับจองได้</div>
      <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-rose-100 border border-rose-300"></span> เต็ม — จองครบแล้ว</div>
      <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-slate-300 border border-slate-400"></span> ปิด — บล็อกโดยเจ้าของ</div>
      <div class="flex items-center gap-2"><span class="w-4 h-4 rounded ring-2 ring-accent-500 bg-white"></span> วันที่เลือกอยู่</div>
    </div>
  </div>

  <div class="lg:col-span-3">
    <form id="avForm" method="post" action="<?= url('/owner/properties/' . $pid . '/availability/save') ?>"
          class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <?= csrf() ?>
      <input type="hidden" name="unit_id" value="<?= $unitId ?>">
      <input type="hidden" name="month" value="<?= $month ?>">
      <input type="hidden" name="year" value="<?= $year ?>">
      <input type="hidden" name="status" :value="pendingStatus">
      <input type="hidden" name="available_units" value="1">
      <template x-for="d in selected" :key="d">
        <input type="hidden" name="dates[]" :value="d">
      </template>

      <div class="flex items-center justify-between mb-3">
        <a href="?unit=<?= $unitId ?>&month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="px-3 py-1.5 border rounded-lg text-sm hover:bg-slate-50"><i data-lucide="chevron-left" class="w-4 h-4"></i></a>
        <h3 class="text-lg font-bold"><?= $thaiMonths[$month] ?> <?= $year + 543 ?></h3>
        <a href="?unit=<?= $unitId ?>&month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="px-3 py-1.5 border rounded-lg text-sm hover:bg-slate-50"><i data-lucide="chevron-right" class="w-4 h-4"></i></a>
      </div>

      <!-- Quick actions -->
      <div class="flex flex-wrap gap-2 mb-4 p-3 bg-slate-50 rounded-xl border border-slate-100">
        <span class="text-xs text-slate-500 w-full mb-0.5">คลิกวันที่เพื่อเลือก แล้วกดปุ่มด้านล่าง</span>
        <button type="button" @click="apply('closed')" :disabled="selected.length===0"
                class="px-3 py-1.5 text-xs font-semibold bg-slate-600 text-white rounded-lg disabled:opacity-40">ปิดวันที่เลือก</button>
        <button type="button" @click="apply('open')" :disabled="selected.length===0"
                class="px-3 py-1.5 text-xs font-semibold bg-emerald-600 text-white rounded-lg disabled:opacity-40">เปิดวันที่เลือก</button>
        <button type="button" @click="selectWeekends()" class="px-3 py-1.5 text-xs font-semibold border border-slate-300 rounded-lg hover:bg-white">เลือกเสาร์-อาทิตย์</button>
        <button type="button" @click="clearSel()" class="px-3 py-1.5 text-xs text-slate-600 rounded-lg hover:bg-white">ล้าง (<span x-text="selected.length"></span>)</button>
      </div>

      <div class="grid grid-cols-7 gap-1 mb-1 text-center text-xs font-semibold text-slate-500">
        <?php foreach (['อา','จ','อ','พ','พฤ','ศ','ส'] as $d): ?><div><?= $d ?></div><?php endforeach; ?>
      </div>
      <div class="grid grid-cols-7 gap-1.5">
        <?php for ($i = 0; $i < $startWeekday; $i++): ?><div></div><?php endfor; ?>
        <?php for ($d = 1; $d <= $daysInMonth; $d++):
          $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
          $meta = $dayMeta[$date] ?? ['key'=>'open','label'=>'ว่าง','cls'=>'bg-emerald-100 border-emerald-300 text-emerald-800'];
          $isPast = $meta['key'] === 'past';
          $booked = (int)($availMap[$date]['booked'] ?? 0);
          ?>
          <button type="button"
                  <?php if (!$isPast): ?>
                  @click="toggle('<?= $date ?>')"
                  :class="isSelected('<?= $date ?>') ? 'ring-2 ring-accent-500 ring-offset-1 scale-[1.02]' : ''"
                  <?php else: ?>disabled<?php endif; ?>
                  class="aspect-square rounded-xl border-2 <?= e($meta['cls']) ?> flex flex-col items-center justify-center relative transition <?= $isPast ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:shadow-md' ?>">
            <span class="text-base font-bold"><?= $d ?></span>
            <?php if (!$isPast): ?>
            <span class="text-[9px] font-medium"><?= e($meta['label']) ?></span>
            <?php if ($booked > 0): ?><span class="text-[8px] opacity-80"><?= $booked ?> จอง</span><?php endif; ?>
            <?php endif; ?>
          </button>
        <?php endfor; ?>
      </div>

      <p class="text-xs text-slate-500 mt-4">
        💡 <strong>ง่ายๆ:</strong> ไม่ต้องตั้งทุกวัน — วันที่ไม่ได้ปิดและยังไม่จองเต็ม ลูกค้าจะเห็นว่า «ว่าง» ใน LINE อัตโนมัติ
      </p>
    </form>
  </div>
</div>

<script>
function availabilityCal() {
  const futureDates = <?= json_encode($futureDates) ?>;
  const weekendDates = futureDates.filter(d => {
    const dow = new Date(d + 'T12:00:00').getDay();
    return dow === 0 || dow === 6;
  });
  return {
    selected: [],
    pendingStatus: 'open',
    init() {},
    toggle(date) {
      const i = this.selected.indexOf(date);
      if (i >= 0) this.selected.splice(i, 1);
      else this.selected.push(date);
    },
    isSelected(date) { return this.selected.includes(date); },
    clearSel() { this.selected = []; },
    selectWeekends() { this.selected = [...weekendDates]; },
    apply(status) {
      if (!this.selected.length) return;
      this.pendingStatus = status;
      this.$nextTick(() => document.getElementById('avForm').submit());
    },
  };
}
</script>
<?php endif; ?>
