<?php
/** @var array|null $homeCalendar @var list<array> $calProperties */
if (!$homeCalendar || empty($calProperties)) return;

$thaiMonths   = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
$month        = (int)$homeCalendar['month'];
$year         = (int)$homeCalendar['year'];
$pid          = (int)$homeCalendar['property_id'];
$unitId       = (int)$homeCalendar['unit_id'];
$dayMeta      = $homeCalendar['dayMeta'];
$bookings     = $homeCalendar['bookingsByDate'];
$daysInMonth  = (int)$homeCalendar['daysInMonth'];
$startWeekday = (int)$homeCalendar['startWeekday'];
$viewMode     = $homeCalendar['view_mode'] ?? 'all';       // 'all' | 'unit'
$multiUnit    = (bool)($homeCalendar['multi_unit'] ?? false);
$selUnitName  = $homeCalendar['selected_unit_name'] ?? '';
$units        = $homeCalendar['units'] ?? [];

$prevM = $month === 1 ? 12 : $month - 1;
$prevY = $month === 1 ? $year - 1 : $year;
$nextM = $month === 12 ? 1 : $month + 1;
$nextY = $month === 12 ? $year + 1 : $year;

// baseQ preserves view + unit across pagination/property changes
$baseQ = static fn(array $extra = []) => url('/owner/dashboard') . '?' . http_build_query(array_merge([
    'cal_p'    => $pid,
    'cal_u'    => $unitId,
    'cal_m'    => $month,
    'cal_y'    => $year,
    'cal_view' => $viewMode,
], $extra));

// "จัดการเต็ม" links to the specific unit in unit mode
$fullCalUrl = url('/owner/properties/' . $pid . '/availability')
    . '?unit=' . $unitId . '&month=' . $month . '&year=' . $year;

$unitsJson    = json_encode(array_values($units), JSON_UNESCAPED_UNICODE);
$bookingsJson = json_encode($bookings, JSON_UNESCAPED_UNICODE);
$csrfToken    = \App\Core\Csrf::token();
?>

<section class="ow-card p-4 mb-5 lg:hidden" x-data="homeCalManage()" x-init="init()">

  <!-- Header row -->
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
  <!-- Property selector (multi-property owners) -->
  <form method="get" class="mb-3">
    <input type="hidden" name="cal_m"    value="<?= $month ?>">
    <input type="hidden" name="cal_y"    value="<?= $year ?>">
    <input type="hidden" name="cal_view" value="<?= e($viewMode) ?>">
    <input type="hidden" name="cal_u"    value="<?= $unitId ?>">
    <select name="cal_p" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
      <?php foreach ($calProperties as $cp): ?>
      <option value="<?= (int)$cp['id'] ?>" <?= (int)$cp['id'] === $pid ? 'selected' : '' ?>><?= e($cp['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <?php endif; ?>

  <?php if ($multiUnit): ?>
  <!-- Segmented control: ภาพรวม / รายยูนิต -->
  <div class="flex rounded-xl overflow-hidden border border-slate-200 mb-3 text-sm font-semibold">
    <a href="<?= e($baseQ(['cal_view' => 'all'])) ?>"
       class="flex-1 py-2 text-center transition <?= $viewMode === 'all' ? 'bg-core-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50' ?>">
      ภาพรวม
    </a>
    <a href="<?= e($baseQ(['cal_view' => 'unit', 'cal_u' => $unitId])) ?>"
       class="flex-1 py-2 text-center border-l border-slate-200 transition <?= $viewMode === 'unit' ? 'bg-core-600 text-white' : 'bg-white text-slate-600 hover:bg-slate-50' ?>">
      รายยูนิต
    </a>
  </div>

  <?php if ($viewMode === 'unit'): ?>
  <!-- Unit chip scroller -->
  <div class="flex gap-2 overflow-x-auto pb-1 mb-3 scrollbar-hide">
    <?php foreach ($units as $u): ?>
    <?php $isActive = (int)$u['id'] === $unitId; ?>
    <a href="<?= e($baseQ(['cal_view' => 'unit', 'cal_u' => (int)$u['id']])) ?>"
       class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition
              <?= $isActive ? 'bg-core-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
      <?= e($u['name']) ?>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <!-- Month navigation -->
  <div class="flex items-center justify-between mb-2">
    <a href="<?= e($baseQ(['cal_m' => $prevM, 'cal_y' => $prevY])) ?>" class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-50"><i data-lucide="chevron-left" class="w-4 h-4"></i></a>
    <span class="text-sm font-bold"><?= $thaiMonths[$month] ?> <?= $year + 543 ?></span>
    <a href="<?= e($baseQ(['cal_m' => $nextM, 'cal_y' => $nextY])) ?>" class="p-1.5 rounded-lg border border-slate-200 hover:bg-slate-50"><i data-lucide="chevron-right" class="w-4 h-4"></i></a>
  </div>

  <!-- Legend -->
  <div class="flex flex-wrap gap-2 text-[10px] text-slate-500 mb-2">
    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-emerald-100 border border-emerald-300"></span>ว่าง</span>
    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-amber-100 border border-amber-300"></span>จอง</span>
    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-rose-100 border border-rose-300"></span>เต็ม</span>
    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-slate-100 border border-slate-300"></span>ยกเลิก</span>
    <span class="inline-flex items-center gap-1"><span class="w-3 h-3 rounded bg-slate-300 border border-slate-400"></span>ปิด</span>
  </div>

  <!-- Calendar grid -->
  <div class="grid grid-cols-7 gap-0.5 mb-0.5 text-center text-[10px] font-semibold text-slate-400">
    <?php foreach (['อา','จ','อ','พ','พฤ','ศ','ส'] as $d): ?><div><?= $d ?></div><?php endforeach; ?>
  </div>
  <div class="grid grid-cols-7 gap-1">
    <?php for ($i = 0; $i < $startWeekday; $i++): ?><div></div><?php endfor; ?>
    <?php for ($d = 1; $d <= $daysInMonth; $d++):
      $date  = sprintf('%04d-%02d-%02d', $year, $month, $d);
      $meta  = $dayMeta[$date] ?? ['key'=>'open','label'=>'ว่าง','cls'=>'bg-emerald-100 border-emerald-300 text-emerald-800'];
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

  <!-- Footer hint -->
  <p class="text-[10px] text-slate-500 mt-3 text-center">
    <?php if ($viewMode === 'unit' && $selUnitName !== ''): ?>
    แสดงเฉพาะ <?= e($selUnitName) ?> · แตะวันที่เพื่อจัดการ
    <?php else: ?>
    สถานะรวมทุกยูนิต · แตะวันที่เพื่อจัดการ
    <?php endif; ?>
  </p>

  <!-- ==================== Modal จัดการวัน ==================== -->
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
            <div class="mb-4 p-3 rounded-xl border text-sm space-y-2"
                 :class="dayBookings.some(b => ['pending','confirmed','completed'].includes(b.status)) ? 'bg-amber-50 border-amber-200' : 'bg-slate-50 border-slate-200'">
              <p class="font-semibold"
                 :class="dayBookings.some(b => ['pending','confirmed','completed'].includes(b.status)) ? 'text-amber-900' : 'text-slate-700'"
                 x-text="dayBookings.some(b => ['pending','confirmed','completed'].includes(b.status)) ? 'มีการจองในวันนี้' : 'มีการจองที่ยกเลิกแล้ว'"></p>
              <template x-for="b in dayBookings" :key="b.id">
                <div class="bg-white/80 rounded-xl px-3 py-2.5 space-y-2">
                  <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                      <a :href="'<?= url('/owner/bookings') ?>/' + b.id" class="text-sm font-semibold text-core-700 truncate block" x-text="b.guest_name"></a>
                      <p class="text-[10px] text-slate-500 font-mono mt-0.5" x-text="b.code + (b.unit_name ? ' · ' + b.unit_name : '') + ' · ' + bookingStatusLabel(b.status)"></p>
                    </div>
                    <a x-show="b.guest_phone" :href="'tel:' + b.guest_phone"
                       class="shrink-0 w-9 h-9 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white grid place-items-center"
                       title="โทรหาลูกค้า">
                      <i data-lucide="phone" class="w-4 h-4"></i>
                    </a>
                  </div>
                  <div class="grid grid-cols-3 gap-1.5 text-xs">
                    <div class="rounded-lg bg-slate-50 px-2 py-1.5">
                      <div class="text-slate-500">ยอดทั้งหมด</div>
                      <div class="font-semibold text-slate-800" x-text="formatMoney(b.total_price)"></div>
                    </div>
                    <div class="rounded-lg bg-emerald-50 px-2 py-1.5">
                      <div class="text-emerald-600">มัดจำ</div>
                      <div class="font-semibold text-emerald-800" x-text="formatMoney(bookingDeposit(b))"></div>
                    </div>
                    <div class="rounded-lg bg-core-50 px-2 py-1.5">
                      <div class="text-slate-500">คงเหลือ</div>
                      <div class="font-bold text-core-700" x-text="formatMoney(bookingBalance(b))"></div>
                    </div>
                  </div>
                  <div class="flex items-center justify-end gap-2 pt-0.5 flex-wrap">
                    <template x-if="canEditBooking(b)">
                      <button type="button" @click="openReschedule(b)"
                              class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-semibold text-amber-800 bg-amber-50 border border-amber-200 hover:bg-amber-100">
                        <i data-lucide="calendar-clock" class="w-3 h-3"></i> เลื่อนวัน
                      </button>
                    </template>
                    <template x-if="canEditBooking(b)">
                      <button type="button" @click="openEdit(b)"
                              class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-semibold text-core-700 bg-core-50 border border-core-200 hover:bg-core-100">
                        <i data-lucide="pencil" class="w-3 h-3"></i> แก้ไข
                      </button>
                    </template>
                    <template x-if="canCancelBooking(b)">
                      <button type="button" @click="confirmCancel(b)"
                              class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100">
                        <i data-lucide="ban" class="w-3 h-3"></i> ยกเลิก
                      </button>
                    </template>
                    <template x-if="canDeleteBooking(b)">
                      <button type="button" @click="confirmDelete(b)"
                              class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-semibold text-white bg-rose-600 border border-rose-700 hover:bg-rose-700">
                        <i data-lucide="trash-2" class="w-3 h-3"></i> ลบถาวร
                      </button>
                    </template>
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
            <?php require __DIR__ . '/line_contact_picker.php'; ?>
            <button type="button" @click="confirmBook()" :disabled="!canBook()"
                    class="ow-btn-primary w-full disabled:opacity-40">บันทึกการจอง</button>
          </div>
        </div>
      </template>

      <!-- Step: แก้ไขการจอง -->
      <template x-if="step === 'edit'">
        <div>
          <button type="button" @click="step='choose'" class="text-sm text-slate-500 mb-3 inline-flex items-center gap-1"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</button>
          <h3 class="font-bold text-lg mb-1">แก้ไขการจอง</h3>
          <p class="text-sm text-slate-500 mb-1" x-text="editCode"></p>
          <p class="text-xs text-slate-400 mb-4" x-text="dayLabel"></p>

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
              <input type="text" x-model="guestName" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">เบอร์โทร <span class="text-rose-500">*</span></label>
              <input type="tel" x-model="guestPhone" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">วันเช็คอิน</label>
              <input type="date" x-model="editCheckIn" @change="syncCheckOutFromCheckIn(); fetchQuote()"
                     class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">จำนวนคืน</label>
              <select x-model="nights" @change="syncCheckOutFromCheckIn(); fetchQuote()" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
                <template x-for="n in 14" :key="n">
                  <option :value="n" x-text="n + ' คืน'"></option>
                </template>
              </select>
              <p class="text-xs text-slate-500 mt-1" x-text="'เช็คเอาท์ ' + formatDate(checkOut)"></p>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">ราคา (บาท)</label>
              <input type="number" min="0" step="1" x-model="totalPrice" @input="priceEdited = true"
                     class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
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
              <textarea x-model="notes" rows="2" maxlength="1000"
                        class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm resize-y"></textarea>
            </div>
            <button type="button" @click="confirmEdit()" :disabled="!canBook()"
                    class="ow-btn-primary w-full disabled:opacity-40">บันทึกการแก้ไข</button>
          </div>
        </div>
      </template>

      <!-- Step: เลื่อนการจอง (เปลี่ยนวัน) -->
      <template x-if="step === 'reschedule'">
        <div>
          <button type="button" @click="step='choose'" class="text-sm text-slate-500 mb-3 inline-flex items-center gap-1"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</button>
          <h3 class="font-bold text-lg mb-1">เลื่อนการจอง</h3>
          <p class="text-sm text-slate-500 mb-1" x-text="editCode + ' · ' + (guestName || '')"></p>
          <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-4">
            เดิม: <span class="font-semibold" x-text="formatDate(rescheduleFromIn) + ' → ' + formatDate(rescheduleFromOut)"></span>
            (<span x-text="rescheduleFromNights + ' คืน'"></span>)
          </p>

          <div class="space-y-3">
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">วันเช็คอินใหม่ <span class="text-rose-500">*</span></label>
              <input type="date" x-model="editCheckIn" @change="onRescheduleDateChange()"
                     class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
              <button type="button" x-show="selectedDate && selectedDate !== editCheckIn"
                      @click="editCheckIn = selectedDate; onRescheduleDateChange()"
                      class="mt-2 w-full text-xs font-semibold text-core-700 bg-core-50 border border-core-200 rounded-lg py-2 hover:bg-core-100">
                ใช้วันที่แตะในปฏิทิน: <span x-text="formatDate(selectedDate)"></span>
              </button>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">จำนวนคืน</label>
              <select x-model="nights" @change="syncCheckOutFromCheckIn(); fetchQuote()" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
                <template x-for="n in 14" :key="n">
                  <option :value="n" x-text="n + ' คืน'"></option>
                </template>
              </select>
            </div>
            <div class="rounded-xl bg-slate-50 border border-slate-200 px-3 py-2.5 text-sm">
              <div class="text-slate-500 text-xs">ช่วงใหม่</div>
              <div class="font-bold text-slate-800" x-text="formatDate(editCheckIn) + ' → ' + formatDate(checkOut)"></div>
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-1">ราคา (บาท)</label>
              <div class="relative">
                <input type="number" min="0" step="1" x-model="totalPrice" @input="priceEdited = true"
                       class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm pr-10">
                <span x-show="priceLoading" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">...</span>
              </div>
              <p class="text-xs text-slate-500 mt-1">ปรับราคาอัตโนมัติเมื่อเปลี่ยนวัน — แก้ได้ตามตกลง</p>
            </div>
            <button type="button" @click="confirmReschedule()" :disabled="!canReschedule()"
                    class="ow-btn-primary w-full disabled:opacity-40">บันทึกเลื่อนวัน</button>
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
              <select x-model="formUnitId" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
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
    <input type="hidden" name="cal_view" value="<?= e($viewMode) ?>">
  </form>
  <form id="homeCalBookForm" method="post" action="<?= url('/owner/properties/' . $pid . '/availability/booking') ?>" class="hidden">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="month" value="<?= $month ?>">
    <input type="hidden" name="year" value="<?= $year ?>">
    <input type="hidden" name="return_to" value="dashboard">
    <input type="hidden" name="cal_view" value="<?= e($viewMode) ?>">
  </form>
  <form id="homeCalEditForm" method="post" action="" class="hidden">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="return_to" value="dashboard">
    <input type="hidden" name="cal_p" value="<?= $pid ?>">
    <input type="hidden" name="cal_u" value="<?= $unitId ?>">
    <input type="hidden" name="cal_m" value="<?= $month ?>">
    <input type="hidden" name="cal_y" value="<?= $year ?>">
    <input type="hidden" name="cal_view" value="<?= e($viewMode) ?>">
  </form>
  <form id="homeCalCancelForm" method="post" action="" class="hidden">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="status" value="cancelled">
    <input type="hidden" name="return_to" value="dashboard">
    <input type="hidden" name="cal_p" value="<?= $pid ?>">
    <input type="hidden" name="cal_u" value="<?= $unitId ?>">
    <input type="hidden" name="cal_m" value="<?= $month ?>">
    <input type="hidden" name="cal_y" value="<?= $year ?>">
    <input type="hidden" name="cal_view" value="<?= e($viewMode) ?>">
  </form>
  <form id="homeCalDeleteForm" method="post" action="" class="hidden">
    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="return_to" value="dashboard">
    <input type="hidden" name="cal_p" value="<?= $pid ?>">
    <input type="hidden" name="cal_u" value="<?= $unitId ?>">
    <input type="hidden" name="cal_m" value="<?= $month ?>">
    <input type="hidden" name="cal_y" value="<?= $year ?>">
    <input type="hidden" name="cal_view" value="<?= e($viewMode) ?>">
  </form>
</section>

<script>
function homeCalManage() {
  const units         = <?= $unitsJson ?>;
  const bookingsOnDate = <?= $bookingsJson ?>;
  // In unit mode, default to the selected unit; in all mode, default to first unit
  const defaultUnitId = String(<?= $unitId ?>) || (units[0] ? String(units[0].id) : '');

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
    lineUserId: '',
    sendLine: false,
    lineContacts: [],
    lineContactsLoaded: false,
    lineContactsLoading: false,
    lineContactsSyncing: false,
    lineContactsSyncMsg: '',
    lineSearch: '',
    showLineManual: false,
    editBookingId: 0,
    editCode: '',
    editCheckIn: '',
    rescheduleFromIn: '',
    rescheduleFromOut: '',
    rescheduleFromNights: 0,
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
          if (!this.lineContactsLoaded) this.fetchLineContacts();
        }
        if (v === 'edit' || v === 'reschedule') {
          this.priceEdited = v === 'reschedule' ? false : true;
          this.$nextTick(() => this.fetchQuote());
        }
      });
    },
    async fetchLineContacts(search = '') {
      const pid = <?= $pid ?>;
      if (!pid) return;
      this.lineContactsLoading = true;
      try {
        const q = new URLSearchParams({ property_id: String(pid), limit: '10' });
        const s = String(search !== undefined ? search : this.lineSearch).trim();
        if (s) {
          q.set('q', s);
          q.set('limit', '20');
        }
        const r = await fetch(`<?= url('/owner/api/line-contacts') ?>?` + q);
        this.lineContacts = await r.json();
      } catch(e) { this.lineContacts = []; }
      this.lineContactsLoading = false;
      this.lineContactsLoaded = true;
      this.$nextTick(() => lucide.createIcons());
    },
    searchLineContacts() {
      this.fetchLineContacts(this.lineSearch);
    },
    async syncLineContacts() {
      const pid = <?= $pid ?>;
      if (!pid || this.lineContactsSyncing) return;
      this.lineContactsSyncing = true;
      this.lineContactsSyncMsg = '';
      try {
        const fd = new FormData();
        fd.append('_csrf', window.__PAEKAN_CSRF__ || '');
        const r = await fetch(`<?= url('/owner/api/line-contacts/sync') ?>?property_id=${pid}`, { method: 'POST', body: fd });
        const d = await r.json();
        if (d.ok) {
          this.lineContactsSyncMsg = `ซิงค์สำเร็จ — นำเข้าใหม่ ${d.imported} คน, อัปเดต ${d.skipped} คน`;
          await this.fetchLineContacts('');
        } else {
          this.lineContactsSyncMsg = 'ซิงค์ไม่สำเร็จ: ' + (d.error || 'ไม่ทราบสาเหตุ');
        }
      } catch(e) { this.lineContactsSyncMsg = 'เกิดข้อผิดพลาด กรุณาลองใหม่'; }
      this.lineContactsSyncing = false;
    },
    selectedLineContact() {
      return this.lineContacts.find(c => c.line_user_id === this.lineUserId) || null;
    },
    pickLineContact(c) {
      if (!c) return;
      this.lineUserId = c.line_user_id;
      this.lineSearch = '';
      if (c.display_name && !this.guestName.trim()) this.guestName = c.display_name;
      if (c.phone && !this.guestPhone.trim()) this.guestPhone = c.phone;
      if (c.line_user_id) this.sendLine = true;
      this.$nextTick(() => lucide.createIcons());
    },
    clearLineContact() {
      this.lineUserId = '';
      this.sendLine = false;
    },
    formatLineLastSeen(ymd) {
      if (!ymd) return 'ทักแชทผ่าน OA';
      const d = new Date(String(ymd).replace(' ', 'T'));
      if (isNaN(d.getTime())) return 'ทักแชทผ่าน OA';
      const diff = Math.floor((Date.now() - d.getTime()) / 86400000);
      if (diff <= 0) return 'ทักวันนี้';
      if (diff === 1) return 'ทักเมื่อวาน';
      if (diff < 7) return 'ทัก ' + diff + ' วันที่แล้ว';
      return 'ทักแชทผ่าน OA';
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
      this.lineUserId = '';
      this.sendLine = false;
      this.lineSearch = '';
      this.showLineManual = false;
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
    syncCheckOutFromCheckIn() {
      const base = this.editCheckIn || this.selectedDate;
      this.checkOut = addDays(base, parseInt(this.nights, 10) || 1);
    },
    bookingDeposit(b) {
      return parseFloat(b.paid_amount) || 0;
    },
    canEditBooking(b) {
      return ['pending', 'confirmed'].includes(b.status);
    },
    canCancelBooking(b) {
      return ['pending', 'confirmed'].includes(b.status);
    },
    canDeleteBooking(b) {
      return !!b.can_hard_delete;
    },
    loadBookingForEdit(b) {
      this.editBookingId = b.id;
      this.editCode = '#' + b.code;
      this.editCheckIn = b.check_in;
      this.formUnitId = String(b.unit_id || defaultUnitId);
      this.guestName = b.guest_name || '';
      this.guestPhone = b.guest_phone || '';
      this.nights = b.nights || 1;
      this.checkOut = b.check_out;
      this.totalPrice = String(Math.round(parseFloat(b.total_price) || 0));
      this.deposit = String(Math.round(this.bookingDeposit(b)));
      this.notes = b.notes || '';
      this.rescheduleFromIn = b.check_in;
      this.rescheduleFromOut = b.check_out;
      this.rescheduleFromNights = b.nights || 1;
    },
    openEdit(b) {
      this.loadBookingForEdit(b);
      this.priceEdited = true;
      this.step = 'edit';
    },
    openReschedule(b) {
      this.loadBookingForEdit(b);
      if (this.selectedDate && this.selectedDate !== b.check_in) {
        this.editCheckIn = this.selectedDate;
        this.syncCheckOutFromCheckIn();
      }
      this.priceEdited = false;
      this.step = 'reschedule';
    },
    onRescheduleDateChange() {
      this.syncCheckOutFromCheckIn();
      this.priceEdited = false;
      this.fetchQuote();
    },
    canReschedule() {
      return this.editBookingId && this.editCheckIn && this.checkOut
        && (this.editCheckIn !== this.rescheduleFromIn || this.checkOut !== this.rescheduleFromOut);
    },
    formatDate(ymd) { return thaiShort(ymd); },
    bookingStatusLabel(status) {
      const m = { pending: 'รอยืนยัน', confirmed: 'ยืนยันแล้ว', cancelled: 'ยกเลิกแล้ว', rejected: 'ปฏิเสธ', completed: 'เสร็จสิ้น', no_show: 'ไม่มา' };
      return m[status] || status;
    },
    formatMoney(n) {
      return '฿' + Number(n || 0).toLocaleString('th-TH');
    },
    bookingBalance(b) {
      if (b.balance != null) return b.balance;
      const total = parseFloat(b.total_price) || 0;
      const paid  = parseFloat(b.paid_amount) || 0;
      return Math.max(0, total - paid);
    },
    get balance() {
      const t = parseFloat(this.totalPrice) || 0;
      const d = parseFloat(this.deposit) || 0;
      return Math.max(0, t - d);
    },
    async fetchQuote() {
      const checkIn = (this.step === 'edit' || this.step === 'reschedule') ? this.editCheckIn : this.selectedDate;
      if (!checkIn || !this.checkOut || !this.formUnitId) return;
      this.priceLoading = true;
      try {
        const q = new URLSearchParams({
          unit_id:     this.formUnitId,
          check_in:    checkIn,
          check_out:   this.checkOut,
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
      const checkIn = (this.step === 'edit' || this.step === 'reschedule') ? this.editCheckIn : this.selectedDate;
      return this.guestName.trim() && this.guestPhone.trim() && checkIn && this.checkOut;
    },
    fillForm(formId, fields) {
      const f = document.getElementById(formId);
      Object.entries(fields).forEach(([name, val]) => {
        let input = f.querySelector(`[name="${name}"]`);
        if (!input) {
          input = document.createElement('input');
          input.type = 'hidden';
          input.name = name;
          f.appendChild(input);
        }
        input.value = val;
      });
    },
    async confirmBook() {
      if (!this.canBook()) return;
      this.syncCheckOut();
      const unitName  = (this.units.find(u => String(u.id) === this.formUnitId) || {}).name || '';
      const price     = parseFloat(this.totalPrice) || 0;
      const dep       = parseFloat(this.deposit) || 0;
      const lineUid   = this.lineUserId.trim();
      const lineLabel = lineUid
        ? ((this.lineContacts.find(c => c.line_user_id === lineUid) || {}).display_name || lineUid)
        : '';
      const html = `<div class="text-left text-sm space-y-1">
        <p><strong>ยูนิต:</strong> ${unitName}</p>
        <p><strong>ผู้จอง:</strong> ${this.guestName}</p>
        <p><strong>โทร:</strong> ${this.guestPhone}</p>
        <p><strong>พัก:</strong> ${thaiShort(this.selectedDate)} → ${thaiShort(this.checkOut)} (${this.nights} คืน)</p>
        <p><strong>ราคา:</strong> ${this.formatMoney(price)}</p>
        ${dep > 0 ? `<p><strong>มัดจำ:</strong> ${this.formatMoney(dep)} · <strong>คงเหลือ:</strong> ${this.formatMoney(this.balance)}</p>` : ''}
        ${this.notes.trim() ? `<p><strong>หมายเหตุ:</strong> ${this.notes.trim()}</p>` : ''}
        ${lineLabel ? `<p><strong>LINE:</strong> ${lineLabel}${this.sendLine ? ' · ส่งใบยืนยันทันที' : ''}</p>` : ''}
      </div>`;
      const ok = await this.swalConfirm('ยืนยันเพิ่มการจอง?', html, 'question');
      if (!ok) return;
      const fields = {
        unit_id:             this.formUnitId,
        check_in:            this.selectedDate,
        check_out:           this.checkOut,
        guest_name:          this.guestName.trim(),
        guest_phone:         this.guestPhone.trim(),
        total_price:         this.totalPrice,
        deposit_amount:      this.deposit,
        notes:               this.notes.trim(),
        guest_line_user_id:  lineUid,
      };
      if (lineUid && this.sendLine) fields['send_line_confirm'] = '1';
      this.fillForm('homeCalBookForm', fields);
      document.getElementById('homeCalBookForm').submit();
    },
    async confirmEdit() {
      if (!this.canBook() || !this.editBookingId) return;
      this.syncCheckOutFromCheckIn();
      const unitName = (this.units.find(u => String(u.id) === this.formUnitId) || {}).name || '';
      const price    = parseFloat(this.totalPrice) || 0;
      const dep      = parseFloat(this.deposit) || 0;
      const html = `<div class="text-left text-sm space-y-1">
        <p><strong>รหัส:</strong> ${this.editCode}</p>
        <p><strong>ยูนิต:</strong> ${unitName}</p>
        <p><strong>ผู้จอง:</strong> ${this.guestName}</p>
        <p><strong>โทร:</strong> ${this.guestPhone}</p>
        <p><strong>พัก:</strong> ${thaiShort(this.editCheckIn)} → ${thaiShort(this.checkOut)} (${this.nights} คืน)</p>
        <p><strong>ราคา:</strong> ${this.formatMoney(price)}</p>
        <p><strong>มัดจำ:</strong> ${this.formatMoney(dep)} · <strong>คงเหลือ:</strong> ${this.formatMoney(this.balance)}</p>
        ${this.notes.trim() ? `<p><strong>หมายเหตุ:</strong> ${this.notes.trim()}</p>` : ''}
      </div>`;
      const ok = await this.swalConfirm('ยืนยันแก้ไขการจอง?', html, 'question');
      if (!ok) return;
      const f = document.getElementById('homeCalEditForm');
      f.action = '<?= url('/owner/bookings') ?>/' + this.editBookingId;
      this.fillForm('homeCalEditForm', {
        unit_id:        this.formUnitId,
        check_in:       this.editCheckIn,
        check_out:      this.checkOut,
        guest_name:     this.guestName.trim(),
        guest_phone:    this.guestPhone.trim(),
        total_price:    this.totalPrice,
        deposit_amount: this.deposit,
        notes:          this.notes.trim(),
      });
      f.submit();
    },
    async confirmReschedule() {
      if (!this.canReschedule()) return;
      this.syncCheckOutFromCheckIn();
      const html = `<div class="text-left text-sm space-y-1">
        <p><strong>รหัส:</strong> ${this.editCode}</p>
        <p><strong>เดิม:</strong> ${thaiShort(this.rescheduleFromIn)} → ${thaiShort(this.rescheduleFromOut)}</p>
        <p><strong>ใหม่:</strong> ${thaiShort(this.editCheckIn)} → ${thaiShort(this.checkOut)} (${this.nights} คืน)</p>
        <p><strong>ราคา:</strong> ${this.formatMoney(parseFloat(this.totalPrice) || 0)}</p>
      </div>`;
      const ok = await this.swalConfirm('ยืนยันเลื่อนการจอง?', html, 'question');
      if (!ok) return;
      const f = document.getElementById('homeCalEditForm');
      f.action = '<?= url('/owner/bookings') ?>/' + this.editBookingId;
      this.fillForm('homeCalEditForm', {
        unit_id:        this.formUnitId,
        check_in:       this.editCheckIn,
        check_out:      this.checkOut,
        guest_name:     this.guestName.trim(),
        guest_phone:    this.guestPhone.trim(),
        total_price:    this.totalPrice,
        deposit_amount: this.deposit,
        notes:          this.notes.trim(),
      });
      f.submit();
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
        unit_id:   this.formUnitId,
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
    async confirmDelete(b) {
      const html = `<div class="text-left text-sm"><p>ลบถาวรการจองของ <strong>${b.guest_name}</strong> (${b.code})?<br><span class="text-slate-500 text-xs">ไม่สามารถกู้คืนได้</span></p></div>`;
      const ok = await this.swalConfirm('ยืนยันลบถาวร?', html, 'error');
      if (!ok) return;
      const f = document.getElementById('homeCalDeleteForm');
      f.action = '<?= url('/owner/bookings') ?>/' + b.id + '/delete';
      f.submit();
    },
    async swalConfirm(title, html, icon) {
      if (!window.Swal) return confirm(title);
      const r = await Swal.fire({
        title, html, icon,
        showCancelButton:    true,
        confirmButtonColor:  '#0e7490',
        cancelButtonColor:   '#94a3b8',
        confirmButtonText:   'ยืนยัน',
        cancelButtonText:    'ยกเลิก',
        reverseButtons:      true,
      });
      return r.isConfirmed;
    },
  };
}
</script>
