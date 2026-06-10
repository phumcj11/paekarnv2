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
$unitsJson = json_encode(array_values($homeCalendar['units'] ?? []), JSON_UNESCAPED_UNICODE);
$bookingsJson = json_encode($bookings, JSON_UNESCAPED_UNICODE);
$csrfToken = \App\Core\Csrf::token();
?>

<section class="ow-card p-4 mb-5 lg:hidden" x-data="homeCalManage()" x-init="init()">
  <div class="flex items-start justify-between gap-2 mb-3">
    <div class="min-w-0">
      <h3 class="font-bold text-slate-800 flex items-center gap-2">
        <i data-lucide="calendar-range" class="w-5 h-5 text-core-600 shrink-0"></i>
        ตารางที่พัก
      </h3>
      <p class="text-xs text-slate-500 mt-0.5 truncate"><?= e($homeCalendar['property_name']) ?></p>
    </div>
    <a href="<?= e($fullCalUrl) ?>" class="ow-btn-primary !py-1.5 !px-3 !text-xs shrink-0">จัดการเต็ม</a>
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
    ?>
    <?php if ($isPast): ?>
    <div class="aspect-square rounded-lg border border-slate-100 bg-slate-50 flex flex-col items-center justify-center opacity-50">
      <span class="text-xs font-bold text-slate-400"><?= $d ?></span>
    </div>
    <?php else: ?>
    <button type="button" @click="openDay('<?= $date ?>', '<?= e($meta['label']) ?>', '<?= e($meta['key']) ?>')"
            class="aspect-square rounded-lg border <?= e($meta['cls']) ?> flex flex-col items-center justify-center hover:shadow-sm transition active:scale-95">
      <span class="text-xs font-bold leading-none"><?= $d ?></span>
      <?php if (!empty($meta['label'])): ?>
      <span class="text-[8px] font-medium leading-tight mt-0.5"><?= e($meta['label']) ?></span>
      <?php endif; ?>
    </button>
    <?php endif; ?>
    <?php endfor; ?>
  </div>

  <p class="text-[10px] text-slate-500 mt-3 text-center">แตะวันที่เพื่อจัดการ — ปิดการจอง หรือ เพิ่มการจอง</p>

  <!-- Modal จัดการวัน -->
  <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50" @keydown.escape.window="closeModal()">
    <div @click.outside="closeModal()" class="bg-white rounded-t-2xl sm:rounded-2xl shadow-xl w-full max-w-md max-h-[92vh] overflow-y-auto p-5 pb-8">

      <!-- Step: เลือกการทำงาน -->
      <template x-if="step === 'choose'">
        <div>
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="font-bold text-lg">จัดการที่พัก</h3>
              <p class="text-sm text-slate-500" x-text="dayLabel"></p>
            </div>
            <button type="button" @click="closeModal()" class="p-2 rounded-lg hover:bg-slate-100"><i data-lucide="x" class="w-5 h-5"></i></button>
          </div>

          <template x-if="dayBookings.length">
            <div class="mb-4 p-3 rounded-xl bg-amber-50 border border-amber-200 text-sm space-y-2">
              <p class="font-semibold text-amber-900">มีการจองในวันนี้</p>
              <template x-for="b in dayBookings" :key="b.id">
                <div class="bg-white/80 rounded-xl px-3 py-2.5 space-y-2">
                  <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                      <a :href="'<?= url('/owner/bookings') ?>/' + b.id" class="text-sm font-semibold text-core-700 truncate block" x-text="b.guest_name"></a>
                      <p class="text-[10px] text-slate-500 font-mono mt-0.5" x-text="b.code"></p>
                    </div>
                    <a x-show="b.guest_phone" :href="'tel:' + b.guest_phone"
                       class="shrink-0 w-9 h-9 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white grid place-items-center"
                       title="โทรหาลูกค้า">
                      <i data-lucide="phone" class="w-4 h-4"></i>
                    </a>
                  </div>
                  <div class="grid grid-cols-2 gap-2 text-xs">
                    <div class="rounded-lg bg-slate-50 px-2 py-1.5">
                      <div class="text-slate-500">ยอดทั้งหมด</div>
                      <div class="font-semibold text-slate-800" x-text="formatMoney(b.total_price)"></div>
                    </div>
                    <div class="rounded-lg bg-core-50 px-2 py-1.5">
                      <div class="text-slate-500">คงเหลือ</div>
                      <div class="font-bold text-core-700" x-text="formatMoney(bookingBalance(b))"></div>
                    </div>
                  </div>
                  <div class="flex justify-end">
                    <button type="button" @click="confirmCancel(b)" class="text-[10px] font-semibold text-rose-600">ยกเลิกการจอง</button>
                  </div>
                </div>
              </template>
            </div>
          </template>

          <p class="text-sm font-medium text-slate-700 mb-3">ต้องการทำอะไร?</p>
          <div class="space-y-2">
            <button type="button" @click="step='book'"
                    class="w-full flex items-center gap-3 p-4 rounded-xl border-2 border-core-200 bg-core-50 hover:bg-core-100 text-left">
              <div class="w-10 h-10 rounded-xl bg-core-600 text-white grid place-items-center shrink-0"><i data-lucide="calendar-plus" class="w-5 h-5"></i></div>
              <div>
                <div class="font-semibold">เพิ่มการจอง</div>
                <div class="text-xs text-slate-500">บันทึกลูกค้าที่จองวันนี้</div>
              </div>
            </button>
            <button type="button" @click="step='close'"
                    class="w-full flex items-center gap-3 p-4 rounded-xl border border-slate-200 hover:bg-slate-50 text-left">
              <div class="w-10 h-10 rounded-xl bg-slate-200 text-slate-600 grid place-items-center shrink-0"><i data-lucide="ban" class="w-5 h-5"></i></div>
              <div>
                <div class="font-semibold">ปิดการจอง</div>
                <div class="text-xs text-slate-500">บล็อกวันนี้ไม่รับจอง</div>
              </div>
            </button>
          </div>
        </div>
      </template>

      <!-- Step: เพิ่มการจอง -->
      <template x-if="step === 'book'">
        <div>
          <button type="button" @click="step='choose'" class="text-sm text-slate-500 mb-3 inline-flex items-center gap-1"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</button>
          <h3 class="font-bold text-lg mb-1">เพิ่มการจอง</h3>
          <p class="text-sm text-slate-500 mb-4" x-text="dayLabel"></p>

          <div class="space-y-3">
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">ยูนิต / หลัง <span class="text-rose-500">*</span></label>
              <select x-model="formUnitId" @change="fetchQuote()" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
                <template x-for="u in units" :key="u.id">
                  <option :value="String(u.id)" x-text="u.name"></option>
                </template>
              </select>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">ชื่อผู้จอง <span class="text-rose-500">*</span></label>
              <input type="text" x-model="guestName" placeholder="ชื่อ-นามสกุล" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">เบอร์โทร <span class="text-rose-500">*</span></label>
              <input type="tel" x-model="guestPhone" placeholder="08x-xxx-xxxx" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">จำนวนคืน</label>
              <select x-model="nights" @change="syncCheckOut(); fetchQuote()" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
                <template x-for="n in 7" :key="n">
                  <option :value="n" x-text="n + ' คืน'"></option>
                </template>
              </select>
              <p class="text-xs text-slate-500 mt-1" x-text="'เช็คเอาท์ ' + formatDate(checkOut)"></p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">ราคา (บาท)</label>
              <div class="relative">
                <input type="number" min="0" step="1" x-model="totalPrice" @input="priceEdited = true"
                       class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm pr-10"
                       placeholder="ดึงอัตโนมัติจากอัตราค่าพัก">
                <span x-show="priceLoading" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">...</span>
              </div>
              <p class="text-xs text-slate-500 mt-1">ดึงราคาจากระบบก่อน — แก้ได้ตามตกลงหน้างาน</p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">มัดจำ (บาท)</label>
              <input type="number" min="0" step="1" x-model="deposit" placeholder="0"
                     class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
            </div>
            <div class="flex items-center justify-between px-3 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-sm">
              <span class="text-slate-600 font-medium">ยอดคงเหลือ</span>
              <span class="font-bold text-core-700" x-text="formatMoney(balance)"></span>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">หมายเหตุ</label>
              <textarea x-model="notes" rows="2" maxlength="1000" placeholder="เช่น ตกลงราคาพิเศษ, โอนมัดจำแล้ว..."
                        class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm resize-y"></textarea>
            </div>
            <button type="button" @click="confirmBook()" :disabled="!canBook()"
                    class="ow-btn-primary w-full disabled:opacity-40">บันทึกการจอง</button>
          </div>
        </div>
      </template>

      <!-- Step: ปิดการจอง -->
      <template x-if="step === 'close'">
        <div>
          <button type="button" @click="step='choose'" class="text-sm text-slate-500 mb-3 inline-flex items-center gap-1"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</button>
          <h3 class="font-bold text-lg mb-1">ปิดการจอง</h3>
          <p class="text-sm text-slate-500 mb-4" x-text="dayLabel"></p>

          <div class="space-y-3">
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">ยูนิต / หลัง <span class="text-rose-500">*</span></label>
              <select x-model="formUnitId" @change="fetchQuote()" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
                <template x-for="u in units" :key="u.id">
                  <option :value="String(u.id)" x-text="u.name"></option>
                </template>
              </select>
            </div>
            <p class="text-xs text-slate-500 p-3 bg-slate-50 rounded-xl">ระบบจะปิดรับจองวันนี้สำหรับยูนิตที่เลือก</p>
            <button type="button" @click="confirmClose()" class="w-full py-2.5 rounded-xl bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold">ปิดการจองวันนี้</button>
          </div>
        </div>
      </template>
    </div>
  </div>

  <!-- Hidden forms (ค่าถูกเติมด้วย JS ก่อน submit) -->
  <form id="homeCalCloseForm" method="post" action="<?= url('/owner/properties/' . $pid . '/availability/save') ?>" class="hidden">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="month" value="<?= $month ?>">
    <input type="hidden" name="year" value="<?= $year ?>">
    <input type="hidden" name="status" value="closed">
    <input type="hidden" name="return_to" value="dashboard">
  </form>
  <form id="homeCalBookForm" method="post" action="<?= url('/owner/properties/' . $pid . '/availability/booking') ?>" class="hidden">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="month" value="<?= $month ?>">
    <input type="hidden" name="year" value="<?= $year ?>">
    <input type="hidden" name="return_to" value="dashboard">
  </form>
  <form id="homeCalCancelForm" method="post" action="" class="hidden">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="status" value="cancelled">
    <input type="hidden" name="return_to" value="dashboard">
    <input type="hidden" name="cal_p" value="<?= $pid ?>">
    <input type="hidden" name="cal_u" value="<?= $unitId ?>">
    <input type="hidden" name="cal_m" value="<?= $month ?>">
    <input type="hidden" name="cal_y" value="<?= $year ?>">
  </form>
</section>

<script>
function homeCalManage() {
  const units = <?= $unitsJson ?>;
  const bookingsOnDate = <?= $bookingsJson ?>;
  const defaultUnitId = String(<?= $unitId ?>);
  const thaiShort = (ymd) => {
    if (!ymd) return '';
    const [, m, d] = ymd.split('-').map(Number);
    const months = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    return `${d} ${months[m]}`;
  };
  const addDays = (ymd, n) => {
    const dt = new Date(ymd + 'T12:00:00');
    dt.setDate(dt.getDate() + n);
    return dt.toISOString().slice(0, 10);
  };
  const uniqueBookings = (list) => {
    const seen = new Set();
    return (list || []).filter(b => { if (seen.has(b.id)) return false; seen.add(b.id); return true; });
  };

  return {
    open: false,
    step: 'choose',
    selectedDate: '',
    dayLabel: '',
    dayBookings: [],
    units,
    formUnitId: defaultUnitId,
    guestName: '',
    guestPhone: '',
    nights: 1,
    checkOut: '',
    totalPrice: '',
    suggestedTotal: 0,
    priceEdited: false,
    priceLoading: false,
    deposit: '',
    notes: '',
    init() {
      this.$watch('open', v => { if (v) this.$nextTick(() => lucide.createIcons()); });
      this.$watch('step', v => {
        this.$nextTick(() => lucide.createIcons());
        if (v === 'book') {
          this.priceEdited = false;
          this.deposit = '';
          this.notes = '';
          this.totalPrice = '';
          this.$nextTick(() => this.fetchQuote());
        }
      });
    },
    openDay(date, label, key) {
      this.selectedDate = date;
      this.dayLabel = thaiShort(date) + (label ? ' · ' + label : '');
      this.dayBookings = uniqueBookings(bookingsOnDate[date] || []);
      this.formUnitId = defaultUnitId;
      this.guestName = '';
      this.guestPhone = '';
      this.nights = 1;
      this.checkOut = addDays(date, 1);
      this.step = 'choose';
      this.open = true;
    },
    closeModal() {
      this.open = false;
      this.step = 'choose';
    },
    syncCheckOut() {
      this.checkOut = addDays(this.selectedDate, parseInt(this.nights, 10) || 1);
    },
    formatDate(ymd) { return thaiShort(ymd); },
    formatMoney(n) {
      return '฿' + Number(n || 0).toLocaleString('th-TH');
    },
    bookingBalance(b) {
      if (b.balance != null) return b.balance;
      const total = parseFloat(b.total_price) || 0;
      const paid = parseFloat(b.paid_amount) || 0;
      return Math.max(0, total - paid);
    },
    get balance() {
      const t = parseFloat(this.totalPrice) || 0;
      const d = parseFloat(this.deposit) || 0;
      return Math.max(0, t - d);
    },
    async fetchQuote() {
      if (!this.selectedDate || !this.checkOut || !this.formUnitId) return;
      this.priceLoading = true;
      try {
        const q = new URLSearchParams({
          unit_id: this.formUnitId,
          check_in: this.selectedDate,
          check_out: this.checkOut,
          guest_count: '1',
        });
        const r = await fetch('<?= url('/owner/api/booking-quote') ?>?' + q);
        const data = await r.json();
        if (data.total != null && !this.priceEdited) {
          this.suggestedTotal = data.total;
          this.totalPrice = String(Math.round(data.total));
        }
      } catch (e) {}
      this.priceLoading = false;
    },
    canBook() {
      return this.guestName.trim() && this.guestPhone.trim() && this.selectedDate && this.checkOut;
    },
    fillForm(formId, fields) {
      const f = document.getElementById(formId);
      Object.entries(fields).forEach(([name, val]) => {
        const isArray = name.endsWith('[]');
        const key = isArray ? name : name;
        let input = f.querySelector(`[name="${key}"]`);
        if (!input) {
          input = document.createElement('input');
          input.type = 'hidden';
          input.name = key;
          f.appendChild(input);
        }
        input.value = val;
      });
    },
    async confirmBook() {
      if (!this.canBook()) return;
      this.syncCheckOut();
      const unitName = (this.units.find(u => String(u.id) === this.formUnitId) || {}).name || '';
      const price = parseFloat(this.totalPrice) || 0;
      const dep = parseFloat(this.deposit) || 0;
      const html = `<div class="text-left text-sm space-y-1">
        <p><strong>ยูนิต:</strong> ${unitName}</p>
        <p><strong>ผู้จอง:</strong> ${this.guestName}</p>
        <p><strong>โทร:</strong> ${this.guestPhone}</p>
        <p><strong>พัก:</strong> ${thaiShort(this.selectedDate)} → ${thaiShort(this.checkOut)} (${this.nights} คืน)</p>
        <p><strong>ราคา:</strong> ${this.formatMoney(price)}</p>
        ${dep > 0 ? `<p><strong>มัดจำ:</strong> ${this.formatMoney(dep)} · <strong>คงเหลือ:</strong> ${this.formatMoney(this.balance)}</p>` : ''}
        ${this.notes.trim() ? `<p><strong>หมายเหตุ:</strong> ${this.notes.trim()}</p>` : ''}
      </div>`;
      const ok = await this.swalConfirm('ยืนยันเพิ่มการจอง?', html, 'question');
      if (!ok) return;
      this.fillForm('homeCalBookForm', {
        unit_id: this.formUnitId,
        check_in: this.selectedDate,
        check_out: this.checkOut,
        guest_name: this.guestName.trim(),
        guest_phone: this.guestPhone.trim(),
        total_price: this.totalPrice,
        deposit_amount: this.deposit,
        notes: this.notes.trim(),
      });
      document.getElementById('homeCalBookForm').submit();
    },
    async confirmClose() {
      const unitName = (this.units.find(u => String(u.id) === this.formUnitId) || {}).name || '';
      const html = `<div class="text-left text-sm space-y-1">
        <p><strong>ยูนิต:</strong> ${unitName}</p>
        <p><strong>วันที่:</strong> ${thaiShort(this.selectedDate)}</p>
        <p class="text-slate-500">วันนี้จะถูกปิดไม่รับจอง</p>
      </div>`;
      const ok = await this.swalConfirm('ยืนยันปิดการจอง?', html, 'warning');
      if (!ok) return;
      this.fillForm('homeCalCloseForm', {
        unit_id: this.formUnitId,
        'dates[]': this.selectedDate,
      });
      document.getElementById('homeCalCloseForm').submit();
    },
    async confirmCancel(b) {
      const html = `<div class="text-left text-sm"><p>ยกเลิกการจองของ <strong>${b.guest_name}</strong> (${b.code})?</p></div>`;
      const ok = await this.swalConfirm('ยืนยันยกเลิกการจอง?', html, 'warning');
      if (!ok) return;
      const f = document.getElementById('homeCalCancelForm');
      f.action = '<?= url('/owner/bookings') ?>/' + b.id + '/status';
      f.submit();
    },
    async swalConfirm(title, html, icon) {
      if (!window.Swal) return confirm(title);
      const r = await Swal.fire({
        title, html, icon,
        showCancelButton: true,
        confirmButtonColor: '#0e7490',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true,
      });
      return r.isConfirmed;
    },
  };
}
</script>
