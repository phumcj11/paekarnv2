<?php
/** @var array $property @var array $units @var int $unitId @var int $month @var int $year @var array $availMap @var array $dayMeta @var int $totalUnits @var array $bookingsByDate */
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
  <a href="<?= url('/owner/properties/' . $pid . '/line') ?>" class="text-sm text-slate-500 hover:text-core-600 inline-flex items-center gap-1">
    <i data-lucide="arrow-left" class="w-4 h-4"></i> ตั้งค่า LINE
  </a>
  <h2 class="font-bold flex items-center gap-2"><i data-lucide="calendar" class="w-5 h-5 text-core-600"></i> ปฏิทินวันว่าง — <?= e($property['name']) ?></h2>
  <a href="<?= url('/owner/properties/' . $pid . '/edit') ?>" class="text-xs text-slate-500 hover:text-core-600">แก้ไขที่พัก</a>
</div>

<?php if (empty($units)): ?>
<div class="ow-card p-12 text-center">
  <i data-lucide="bed-double" class="w-12 h-12 mx-auto text-slate-400"></i>
  <h3 class="mt-3 font-semibold">ยังไม่มีห้องพัก</h3>
  <a href="<?= url('/owner/properties/' . $pid . '/units/create') ?>" class="ow-btn-primary mt-4">เพิ่มห้องพัก</a>
</div>
<?php else: ?>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4" x-data="availabilityCal()" x-init="init()">
  <div class="lg:col-span-1 space-y-4">
    <div class="ow-card p-4">
      <h3 class="font-bold text-sm mb-2">เลือกห้อง/ยูนิต</h3>
      <form method="get">
        <input type="hidden" name="month" value="<?= $month ?>">
        <input type="hidden" name="year" value="<?= $year ?>">
        <select name="unit" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-core-500 focus:ring-2 focus:ring-core-100 outline-none">
          <?php foreach ($units as $u): ?>
          <option value="<?= $u['id'] ?>" <?= $unitId==$u['id']?'selected':'' ?>><?= e($u['name']) ?> (<?= (int)$u['total_units'] ?> หลัง)</option>
          <?php endforeach; ?>
        </select>
      </form>
      <p class="text-[11px] text-slate-500 mt-2">LINE แสดง «ว่าง» ถ้ามียูนิตใดยูนิตหนึ่งว่างในวันนั้น</p>
    </div>

    <div class="ow-card p-4 text-xs space-y-2">
      <h3 class="font-bold text-sm mb-1">โหมดเลือก</h3>
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="radio" value="range" x-model="mode" @change="clearSel()" class="accent-core-600">
        <span><strong>ช่วงวัน</strong> — เช็คอิน → เช็คเอาท์</span>
      </label>
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="radio" value="single" x-model="mode" @change="clearSel()" class="accent-core-600">
        <span><strong>ทีละวัน</strong> — คลิกหลายวัน</span>
      </label>
    </div>

    <div class="ow-card p-4 text-xs space-y-2">
      <h3 class="font-bold text-sm mb-1">สีในปฏิทิน</h3>
      <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-emerald-100 border border-emerald-300"></span> ว่าง</div>
      <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-amber-100 border border-amber-300"></span> จอง</div>
      <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-rose-100 border border-rose-300"></span> เต็ม</div>
      <div class="flex items-center gap-2"><span class="w-4 h-4 rounded bg-slate-300 border border-slate-400"></span> ปิด</div>
      <div class="flex items-center gap-2"><span class="w-4 h-4 rounded ring-2 ring-indigo-500 bg-indigo-50"></span> ช่วงที่เลือก</div>
    </div>
  </div>

  <div class="lg:col-span-3">
    <form id="avForm" method="post" action="<?= url('/owner/properties/' . $pid . '/availability/save') ?>" class="ow-card p-5">
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
        <a href="?unit=<?= $unitId ?>&month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="px-3 py-1.5 border border-slate-200 rounded-xl text-sm hover:bg-slate-50"><i data-lucide="chevron-left" class="w-4 h-4"></i></a>
        <h3 class="text-lg font-bold"><?= $thaiMonths[$month] ?> <?= $year + 543 ?></h3>
        <a href="?unit=<?= $unitId ?>&month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="px-3 py-1.5 border border-slate-200 rounded-xl text-sm hover:bg-slate-50"><i data-lucide="chevron-right" class="w-4 h-4"></i></a>
      </div>

      <div class="mb-3 p-3 rounded-xl border text-sm"
           :class="selected.length ? 'bg-indigo-50 border-indigo-200 text-indigo-900' : 'bg-slate-50 border-slate-200 text-slate-500'">
        <template x-if="mode === 'range'">
          <div><span class="font-semibold">ช่วงพัก: </span><span x-text="rangeSummary()"></span></div>
        </template>
        <template x-if="mode === 'single'">
          <div><span class="font-semibold">เลือกแล้ว: </span><span x-text="selected.length ? selected.length + ' วัน' : 'ยังไม่ได้เลือก'"></span></div>
        </template>
      </div>

      <div class="flex flex-wrap gap-2 mb-4 p-3 bg-slate-50 rounded-xl border border-slate-100">
        <span class="text-xs text-slate-500 w-full" x-show="mode==='range'">
          คลิกวันแรก = เช็คอิน · คลิกวันที่สอง = เช็คเอาท์ · คลิกวัน «จอง» = ดูรายละเอียด
        </span>
        <button type="button" @click="openCloseModal()" :disabled="selected.length===0"
                class="px-3 py-1.5 text-xs font-semibold bg-slate-600 text-white rounded-xl disabled:opacity-40">ปิดช่วงที่เลือก</button>
        <button type="button" @click="apply('open')" :disabled="selected.length===0"
                class="px-3 py-1.5 text-xs font-semibold bg-emerald-600 text-white rounded-xl disabled:opacity-40">เปิดช่วงที่เลือก</button>
        <button type="button" @click="selectWeekends()" class="px-3 py-1.5 text-xs font-semibold border border-slate-300 rounded-xl hover:bg-white">เลือกเสาร์-อาทิตย์</button>
        <button type="button" @click="clearSel()" class="px-3 py-1.5 text-xs text-slate-600 rounded-xl hover:bg-white">ล้าง</button>
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
          $hasBooking = ($meta['key'] ?? '') === 'booked' || ($meta['key'] ?? '') === 'full';
          ?>
          <button type="button"
                  <?php if (!$isPast): ?>
                  @click="pick('<?= $date ?>', <?= $hasBooking ? 'true' : 'false' ?>)"
                  :class="cellClass('<?= $date ?>')"
                  <?php else: ?>disabled<?php endif; ?>
                  class="aspect-square rounded-xl border-2 <?= e($meta['cls']) ?> flex flex-col items-center justify-center relative transition <?= $isPast ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer hover:shadow-md' ?>">
            <span class="text-base font-bold"><?= $d ?></span>
            <?php if (!$isPast): ?>
            <span class="text-[9px] font-medium"><?= e($meta['label']) ?></span>
            <?php endif; ?>
          </button>
        <?php endfor; ?>
      </div>

      <p class="text-xs text-slate-500 mt-4">
        💡 กด <strong>ปิดช่วงที่เลือก</strong> แล้วเลือก «มีลูกค้าจอง» เพื่อบันทึกการจองทันที หรือ «ปิดเฉยๆ» เพื่อบล็อกวัน
      </p>
    </form>
  </div>

  <!-- Modal: เลือกเหตุผลปิด -->
  <div x-show="showCloseModal" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-slate-900/40" @keydown.escape.window="showCloseModal=false">
    <div @click.outside="showCloseModal=false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-5">
      <h3 class="font-bold text-lg mb-1">ปิดช่วงที่เลือก</h3>
      <p class="text-sm text-slate-500 mb-4" x-text="rangeSummary()"></p>
      <p class="text-sm font-medium text-slate-700 mb-3">ปิดเพราะอะไร?</p>
      <div class="space-y-2">
        <button type="button" @click="chooseBooking()"
                class="w-full flex items-center gap-3 p-4 rounded-xl border-2 border-core-200 bg-core-50 hover:bg-core-100 text-left transition">
          <div class="w-10 h-10 rounded-xl bg-core-600 text-white grid place-items-center shrink-0"><i data-lucide="user-check" class="w-5 h-5"></i></div>
          <div>
            <div class="font-semibold text-slate-800">มีลูกค้าจอง</div>
            <div class="text-xs text-slate-500">บันทึกรายละเอียดการจองลงระบบ</div>
          </div>
        </button>
        <button type="button" @click="closeOnly()"
                class="w-full flex items-center gap-3 p-4 rounded-xl border border-slate-200 hover:bg-slate-50 text-left transition">
          <div class="w-10 h-10 rounded-xl bg-slate-200 text-slate-600 grid place-items-center shrink-0"><i data-lucide="ban" class="w-5 h-5"></i></div>
          <div>
            <div class="font-semibold text-slate-800">ปิดเฉยๆ</div>
            <div class="text-xs text-slate-500">บล็อกวันโดยไม่มีการจอง</div>
          </div>
        </button>
      </div>
      <button type="button" @click="showCloseModal=false" class="w-full mt-3 py-2 text-sm text-slate-500 hover:text-slate-700">ยกเลิก</button>
    </div>
  </div>

  <!-- Modal: ฟอร์มจอง -->
  <div x-show="showBookingModal" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-slate-900/40" @keydown.escape.window="showBookingModal=false">
    <div @click.outside="showBookingModal=false" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-5 max-h-[90vh] overflow-y-auto">
      <h3 class="font-bold text-lg flex items-center gap-2"><i data-lucide="calendar-plus" class="w-5 h-5 text-core-600"></i> บันทึกการจอง</h3>
      <p class="text-sm text-slate-500 mt-1 mb-4" x-text="bookingDateSummary()"></p>

      <form method="post" action="<?= url('/owner/properties/' . $pid . '/availability/booking') ?>" class="space-y-4">
        <?= csrf() ?>
        <input type="hidden" name="month" value="<?= $month ?>">
        <input type="hidden" name="year" value="<?= $year ?>">
        <input type="hidden" name="check_in" :value="bookingCheckIn">
        <input type="hidden" name="check_out" :value="bookingCheckOut">

        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">ยูนิต / หลังที่จอง <span class="text-rose-500">*</span></label>
          <select name="unit_id" x-model="bookingUnitId" required class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-core-500 outline-none">
            <?php foreach ($units as $u): ?>
            <option value="<?= $u['id'] ?>"><?= e($u['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">ชื่อผู้จอง <span class="text-rose-500">*</span></label>
          <input type="text" name="guest_name" x-model="guestName" required placeholder="ชื่อ-นามสกุล"
                 class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-core-500 outline-none">
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">เบอร์โทร <span class="text-rose-500">*</span></label>
          <input type="tel" name="guest_phone" x-model="guestPhone" required placeholder="08x-xxx-xxxx"
                 class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-core-500 outline-none">
        </div>
        <div class="flex gap-2 pt-1">
          <button type="button" @click="showBookingModal=false" class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50">ยกเลิก</button>
          <button type="submit" :disabled="!canSubmitBooking()"
                  class="flex-1 py-2.5 rounded-xl bg-core-600 hover:bg-core-700 text-white text-sm font-semibold disabled:opacity-40">
            บันทึกการจอง
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: รายละเอียดจอง -->
  <div x-show="showDetailModal" x-cloak class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 bg-slate-900/40" @keydown.escape.window="showDetailModal=false">
    <div @click.outside="showDetailModal=false" class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-5">
      <h3 class="font-bold text-lg flex items-center gap-2"><i data-lucide="clipboard-list" class="w-5 h-5 text-amber-600"></i> รายละเอียดการจอง</h3>
      <template x-if="detailBookings.length === 1">
        <div class="mt-4 space-y-3 text-sm">
          <div class="flex justify-between"><span class="text-slate-500">รหัส</span><span class="font-mono font-semibold text-core-600" x-text="detailBookings[0].code"></span></div>
          <div class="flex justify-between"><span class="text-slate-500">ผู้จอง</span><span class="font-semibold" x-text="detailBookings[0].guest_name"></span></div>
          <div class="flex justify-between"><span class="text-slate-500">โทร</span><a :href="'tel:'+detailBookings[0].guest_phone" class="text-core-600 font-medium" x-text="detailBookings[0].guest_phone"></a></div>
          <div class="flex justify-between"><span class="text-slate-500">ยูนิต</span><span x-text="detailBookings[0].unit_name || '—'"></span></div>
          <div class="flex justify-between"><span class="text-slate-500">วันพัก</span><span x-text="formatStay(detailBookings[0])"></span></div>
          <div class="flex justify-between"><span class="text-slate-500">รวม</span><span class="font-bold text-core-700" x-text="formatMoney(detailBookings[0].total_price)"></span></div>
          <div class="flex justify-between items-center"><span class="text-slate-500">สถานะ</span><span class="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800" x-text="detailBookings[0].status"></span></div>
          <a :href="'<?= url('/owner/bookings') ?>/' + detailBookings[0].id" class="ow-btn-primary w-full mt-2">ดูรายละเอียดเต็ม</a>
        </div>
      </template>
      <template x-if="detailBookings.length > 1">
        <div class="mt-4 space-y-3">
          <template x-for="b in detailBookings" :key="b.id">
            <a :href="'<?= url('/owner/bookings') ?>/' + b.id" class="block p-3 rounded-xl border border-slate-200 hover:border-core-200 transition">
              <div class="font-semibold text-sm" x-text="b.guest_name"></div>
              <div class="text-xs text-slate-500 mt-0.5" x-text="b.code + ' · ' + formatStay(b)"></div>
            </a>
          </template>
        </div>
      </template>
      <button type="button" @click="showDetailModal=false" class="w-full mt-4 py-2 text-sm text-slate-500 hover:text-slate-700">ปิด</button>
    </div>
  </div>
</div>

<script>
function availabilityCal() {
  const futureDates = <?= json_encode($futureDates) ?>;
  const bookingsOnDate = <?= json_encode($bookingsByDate, JSON_UNESCAPED_UNICODE) ?>;
  const defaultUnitId = <?= (int)$unitId ?>;
  const weekendDates = futureDates.filter(d => {
    const dow = new Date(d + 'T12:00:00').getDay();
    return dow === 0 || dow === 6;
  });

  const thaiShort = (ymd) => {
    const [, m, d] = ymd.split('-').map(Number);
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
    while (d < checkOut) { out.push(d); d = addDays(d, 1); }
    return out;
  };

  const uniqueBookings = (list) => {
    const seen = new Set();
    return (list || []).filter(b => {
      if (seen.has(b.id)) return false;
      seen.add(b.id);
      return true;
    });
  };

  return {
    mode: 'range',
    rangeStart: null,
    rangeEnd: null,
    selected: [],
    pendingStatus: 'open',
    showCloseModal: false,
    showBookingModal: false,
    showDetailModal: false,
    detailBookings: [],
    bookingsOnDate,
    bookingUnitId: String(defaultUnitId),
    guestName: '',
    guestPhone: '',
    init() {
      this.$watch('showCloseModal', v => v && this.$nextTick(() => lucide.createIcons()));
      this.$watch('showBookingModal', v => v && this.$nextTick(() => lucide.createIcons()));
      this.$watch('showDetailModal', v => v && this.$nextTick(() => lucide.createIcons()));
    },
    pick(date, hasBooking) {
      if (hasBooking && bookingsOnDate[date]) {
        this.detailBookings = uniqueBookings(bookingsOnDate[date]);
        this.showDetailModal = true;
        return;
      }
      if (this.mode === 'single') {
        const i = this.selected.indexOf(date);
        if (i >= 0) this.selected.splice(i, 1);
        else this.selected.push(date);
        this.rangeStart = null;
        this.rangeEnd = null;
        return;
      }
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
      if (!this.rangeStart && !this.selected.length) return 'ยังไม่ได้เลือก';
      if (this.mode === 'single' && this.selected.length) {
        return `เลือก ${this.selected.length} วัน`;
      }
      if (!this.rangeStart) return 'คลิกวันแรก (เช็คอิน)';
      if (!this.rangeEnd) return `เช็คอิน ${thaiShort(this.rangeStart)} — คลิกวันเช็คเอาท์`;
      const n = this.selected.length;
      return `${thaiShort(this.rangeStart)} → ${thaiShort(this.rangeEnd)} (${n} คืน)`;
    },
    bookingDateSummary() {
      if (this.mode === 'range' && this.rangeStart && this.rangeEnd) {
        return `เช็คอิน ${thaiShort(this.rangeStart)} · เช็คเอาท์ ${thaiShort(this.rangeEnd)} (${this.selected.length} คืน)`;
      }
      if (this.selected.length) {
        const sorted = [...this.selected].sort();
        return `วันที่เลือก ${sorted.length} วัน — ใช้ ${thaiShort(sorted[0])} เป็นวันเช็คอิน`;
      }
      return '';
    },
    get bookingCheckIn() {
      if (this.mode === 'range' && this.rangeStart) return this.rangeStart;
      if (this.selected.length) return [...this.selected].sort()[0];
      return '';
    },
    get bookingCheckOut() {
      if (this.mode === 'range' && this.rangeEnd) return this.rangeEnd;
      if (this.selected.length) {
        const sorted = [...this.selected].sort();
        return addDays(sorted[sorted.length - 1], 1);
      }
      return '';
    },
    openCloseModal() {
      if (!this.selected.length) return;
      this.showCloseModal = true;
    },
    closeOnly() {
      this.showCloseModal = false;
      this.apply('closed');
    },
    chooseBooking() {
      this.showCloseModal = false;
      if (this.mode === 'range' && this.rangeStart && !this.rangeEnd) {
        alert('กรุณาเลือกวันเช็คเอาท์ก่อนบันทึกการจอง');
        return;
      }
      this.bookingUnitId = String(defaultUnitId);
      this.showBookingModal = true;
    },
    canSubmitBooking() {
      return this.guestName.trim() && this.guestPhone.trim() && this.bookingCheckIn && this.bookingCheckOut;
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
    formatStay(b) {
      return `${thaiShort(b.check_in)} → ${thaiShort(b.check_out)} (${b.nights} คืน)`;
    },
    formatMoney(n) {
      return '฿' + Number(n).toLocaleString('th-TH');
    },
  };
}
</script>
<?php endif; ?>
