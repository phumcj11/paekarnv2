<?php
/** @var array $property @var array $units @var int $unitId @var int $month @var int $year @var array $availMap @var array $dayMeta @var int $totalUnits */
$thaiMonths = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
$firstDay     = mktime(0,0,0,$month,1,$year);
$daysInMonth  = (int)date('t', $firstDay);
$startWeekday = (int)date('w', $firstDay);
$prevMonth = $month==1 ? 12 : $month-1; $prevYear = $month==1 ? $year-1 : $year;
$nextMonth = $month==12 ? 1 : $month+1;  $nextYear = $month==12 ? $year+1 : $year;
$pid = (int)$property['id'];

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
      <h3 class="font-bold text-sm mb-1">โหมดเลือก</h3>
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="radio" value="range" x-model="mode" @change="clearSel()" class="accent-accent-600">
        <span><strong>ช่วงวัน</strong> — เช็คอิน → เช็คเอาท์</span>
      </label>
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="radio" value="single" x-model="mode" @change="clearSel()" class="accent-accent-600">
        <span><strong>ทีละวัน</strong> — คลิกหลายวัน</span>
      </label>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-4 text-xs space-y-2">
      <h3 class="font-bold text-sm mb-1">สีในปฏิทิน</h3>
      <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-emerald-100 border border-emerald-300"></span> ว่าง</div>
      <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-rose-100 border border-rose-300"></span> เต็ม</div>
      <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-slate-300 border border-slate-400"></span> ปิด</div>
      <div class="flex items-center gap-2"><span class="w-4 h-4 rounded ring-2 ring-indigo-500 bg-indigo-50"></span> ช่วงที่เลือก</div>
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

      <!-- ช่วงที่เลือก -->
      <div class="mb-3 p-3 rounded-xl border text-sm"
           :class="selected.length ? 'bg-indigo-50 border-indigo-200 text-indigo-900' : 'bg-slate-50 border-slate-200 text-slate-500'">
        <template x-if="mode === 'range'">
          <div>
            <span class="font-semibold">ช่วงพัก: </span>
            <span x-text="rangeSummary()"></span>
          </div>
        </template>
        <template x-if="mode === 'single'">
          <div>
            <span class="font-semibold">เลือกแล้ว: </span>
            <span x-text="selected.length ? selected.length + ' วัน' : 'ยังไม่ได้เลือก'"></span>
          </div>
        </template>
      </div>

      <div class="flex flex-wrap gap-2 mb-4 p-3 bg-slate-50 rounded-xl border border-slate-100">
        <span class="text-xs text-slate-500 w-full" x-show="mode==='range'">
          คลิกวันแรก = เช็คอิน · คลิกวันที่สอง = เช็คเอาท์ (ระบบเลือกทุกคืนในช่วงให้)
        </span>
        <span class="text-xs text-slate-500 w-full" x-show="mode==='single'">
          คลิกวันที่ทีละวันเพื่อเลือกหลายวัน
        </span>
        <button type="button" @click="apply('closed')" :disabled="selected.length===0"
                class="px-3 py-1.5 text-xs font-semibold bg-slate-600 text-white rounded-lg disabled:opacity-40">ปิดช่วงที่เลือก</button>
        <button type="button" @click="apply('open')" :disabled="selected.length===0"
                class="px-3 py-1.5 text-xs font-semibold bg-emerald-600 text-white rounded-lg disabled:opacity-40">เปิดช่วงที่เลือก</button>
        <button type="button" @click="selectWeekends()" class="px-3 py-1.5 text-xs font-semibold border border-slate-300 rounded-lg hover:bg-white">เลือกเสาร์-อาทิตย์</button>
        <button type="button" @click="clearSel()" class="px-3 py-1.5 text-xs text-slate-600 rounded-lg hover:bg-white">ล้าง</button>
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
                  @click="pick('<?= $date ?>')"
                  :class="cellClass('<?= $date ?>')"
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
        💡 โหมด <strong>ช่วงวัน</strong> เหมือนลูกค้าเลือกใน LINE — เช่น เช็คอิน 15 มิ.ย. เช็คเอาท์ 18 มิ.ย. = ปิด/เปิด 3 คืน (15–17 มิ.ย.)
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

  const thaiShort = (ymd) => {
    const [y, m, d] = ymd.split('-').map(Number);
    const months = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    return `${d} ${months[m]}`;
  };

  const addDays = (ymd, n) => {
    const dt = new Date(ymd + 'T12:00:00');
    dt.setDate(dt.getDate() + n);
    return dt.toISOString().slice(0, 10);
  };

  const nightsBetween = (checkIn, checkOut) => {
    if (!checkIn) return [];
    if (!checkOut || checkOut <= checkIn) return [checkIn];
    const out = [];
    let d = checkIn;
    while (d < checkOut) {
      out.push(d);
      d = addDays(d, 1);
    }
    return out;
  };

  return {
    mode: 'range',
    rangeStart: null,
    rangeEnd: null,
    selected: [],
    pendingStatus: 'open',
    init() {},
    pick(date) {
      if (this.mode === 'single') {
        const i = this.selected.indexOf(date);
        if (i >= 0) this.selected.splice(i, 1);
        else this.selected.push(date);
        this.rangeStart = null;
        this.rangeEnd = null;
        return;
      }
      // ช่วงวัน: คลิก 1 = เช็คอิน, คลิก 2 = เช็คเอาท์
      if (!this.rangeStart || (this.rangeStart && this.rangeEnd)) {
        this.rangeStart = date;
        this.rangeEnd = null;
      } else if (date <= this.rangeStart) {
        this.rangeEnd = addDays(this.rangeStart, 1);
        this.rangeStart = date;
      } else {
        this.rangeEnd = date;
      }
      this.syncRange();
    },
    syncRange() {
      this.selected = nightsBetween(this.rangeStart, this.rangeEnd);
    },
    cellClass(date) {
      if (!this.isHighlighted(date)) return '';
      if (this.mode === 'range' && this.rangeStart === date) {
        return 'ring-2 ring-indigo-600 ring-offset-1 bg-indigo-100 scale-[1.03]';
      }
      if (this.mode === 'range' && this.rangeEnd === date) {
        return 'ring-2 ring-violet-600 ring-offset-1 bg-violet-100 scale-[1.03]';
      }
      return 'ring-2 ring-indigo-400 ring-offset-1 bg-indigo-50 scale-[1.02]';
    },
    isHighlighted(date) {
      if (this.mode === 'single') return this.selected.includes(date);
      if (!this.rangeStart) return false;
      if (!this.rangeEnd) return date === this.rangeStart;
      return date >= this.rangeStart && date < this.rangeEnd;
    },
    rangeSummary() {
      if (!this.rangeStart) return 'คลิกวันแรก (เช็คอิน)';
      if (!this.rangeEnd) return `เช็คอิน ${thaiShort(this.rangeStart)} — คลิกวันเช็คเอาท์`;
      const n = this.selected.length;
      return `${thaiShort(this.rangeStart)} → ${thaiShort(this.rangeEnd)} (${n} คืน)`;
    },
    clearSel() {
      this.selected = [];
      this.rangeStart = null;
      this.rangeEnd = null;
    },
    selectWeekends() {
      this.mode = 'single';
      this.rangeStart = null;
      this.rangeEnd = null;
      this.selected = [...weekendDates];
    },
    apply(status) {
      if (!this.selected.length) return;
      this.pendingStatus = status;
      this.$nextTick(() => document.getElementById('avForm').submit());
    },
  };
}
</script>
<?php endif; ?>
