<?php
/** @var list<array> $properties
 *  @var array<int,list<array>> $unitsByProperty
 *  @var array|null $booking
 */
use App\Core\Session;
$old = Session::get('_old', []);
$val = static fn(string $k, $d = '') => array_key_exists($k, $old) ? $old[$k] : $d;

$propJson  = json_encode(array_values($properties), JSON_UNESCAPED_UNICODE);
$unitsJson = json_encode($unitsByProperty, JSON_UNESCAPED_UNICODE);
?>

<div x-data="ownerBookingForm()" x-init="init()" class="max-w-2xl">
  <a href="<?= url('/owner/bookings') ?>" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-primary-600 mb-4">
    <i data-lucide="arrow-left" class="w-4 h-4"></i> กลับรายการจอง
  </a>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-6">
    <h2 class="text-lg font-bold text-slate-800 mb-5 flex items-center gap-2">
      <i data-lucide="calendar-plus" class="w-5 h-5 text-accent-600"></i>
      บันทึกการจองใหม่ (จากโทร / LINE)
    </h2>

    <form method="post" action="<?= url('/owner/bookings') ?>" class="space-y-5">
      <?= csrf() ?>

      <!-- ช่องทางการจอง -->
      <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">ช่องทางที่รับจอง</label>
        <div class="flex gap-3">
          <?php foreach ([['manual_phone', '📞 โทรศัพท์'], ['manual_line', '💬 LINE']] as [$v, $l]): ?>
          <label class="flex items-center gap-2 cursor-pointer px-4 py-2.5 rounded-xl border-2 transition"
                 :class="source === '<?= $v ?>' ? 'border-accent-500 bg-accent-50' : 'border-slate-200 hover:border-slate-300'">
            <input type="radio" name="source" value="<?= $v ?>" x-model="source" class="sr-only" <?= $val('source','manual_phone') === $v ? 'checked' : '' ?>>
            <span class="text-sm font-medium"><?= $l ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- ที่พัก + ยูนิต -->
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">ที่พัก <span class="text-rose-500">*</span></label>
          <select name="property_id" x-model="propertyId" @change="onPropertyChange()" required
                  class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-accent-500 focus:ring-2 focus:ring-accent-100 outline-none">
            <option value="">— เลือกที่พัก —</option>
            <?php foreach ($properties as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= (string)$val('property_id') === (string)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">ยูนิต / ห้อง <span class="text-rose-500">*</span></label>
          <select name="unit_id" x-model="unitId" @change="onUnitChange()" required
                  class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-accent-500 focus:ring-2 focus:ring-accent-100 outline-none">
            <option value="">— เลือกยูนิต —</option>
            <template x-for="u in units" :key="u.id">
              <option :value="u.id" x-text="u.name + ' (฿' + Number(u.price).toLocaleString('th-TH') + '/คืน)'"></option>
            </template>
          </select>
        </div>
      </div>

      <!-- วันเข้า-ออก + จำนวนคน -->
      <div class="grid sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">เช็คอิน <span class="text-rose-500">*</span></label>
          <input type="date" name="check_in" x-model="checkIn" @change="calcPrice()" required
                 value="<?= e($val('check_in')) ?>"
                 min="<?= date('Y-m-d') ?>"
                 class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-accent-500 focus:ring-2 focus:ring-accent-100 outline-none">
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">เช็คเอาท์ <span class="text-rose-500">*</span></label>
          <input type="date" name="check_out" x-model="checkOut" @change="calcPrice()" required
                 value="<?= e($val('check_out')) ?>"
                 class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-accent-500 focus:ring-2 focus:ring-accent-100 outline-none">
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">จำนวนผู้เข้าพัก <span class="text-rose-500">*</span></label>
          <input type="number" name="guest_count" x-model="guestCount" @change="calcPrice()" min="1" max="120" required
                 value="<?= e($val('guest_count', '2')) ?>"
                 class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-accent-500 focus:ring-2 focus:ring-accent-100 outline-none">
        </div>
      </div>

      <!-- ราคา / มัดจำ -->
      <div x-show="estimatedNights > 0" x-cloak class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
        <div class="flex items-center gap-2 text-sm text-slate-600">
          <i data-lucide="calculator" class="w-4 h-4 text-slate-400 shrink-0"></i>
          <span><strong x-text="estimatedNights"></strong> คืน — ราคาดึงจากอัตราค่าพัก (แก้ได้ตามตกลงหน้างาน)</span>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">ราคา (บาท)</label>
            <input type="number" name="total_price" min="0" step="1" x-model="totalPrice" @input="priceEdited = true"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-accent-500 focus:ring-2 focus:ring-accent-100 outline-none">
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">มัดจำ (บาท)</label>
            <input type="number" name="deposit_amount" min="0" step="1" x-model="deposit" placeholder="0"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-accent-500 focus:ring-2 focus:ring-accent-100 outline-none">
          </div>
        </div>
        <div class="flex items-center justify-between text-sm px-3 py-2 rounded-lg bg-white border border-slate-200">
          <span class="text-slate-600 font-medium">ยอดคงเหลือ</span>
          <span class="font-bold text-accent-700" x-text="formatMoney(balance)"></span>
        </div>
      </div>

      <!-- ข้อมูลลูกค้า -->
      <div class="border-t border-slate-100 pt-4 space-y-4">
        <h3 class="text-sm font-bold text-slate-600 uppercase tracking-wide">ข้อมูลผู้จอง</h3>
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">ชื่อผู้จอง <span class="text-rose-500">*</span></label>
            <input type="text" name="guest_name" required maxlength="120" x-model="guestName"
                   value="<?= e($val('guest_name')) ?>"
                   placeholder="ชื่อ-นามสกุล หรือชื่อเล่น"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-accent-500 focus:ring-2 focus:ring-accent-100 outline-none">
          </div>
          <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1">เบอร์โทร <span class="text-rose-500">*</span></label>
            <input type="tel" name="guest_phone" required maxlength="30" x-model="guestPhone"
                   value="<?= e($val('guest_phone')) ?>"
                   placeholder="08x-xxx-xxxx"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-accent-500 focus:ring-2 focus:ring-accent-100 outline-none">
          </div>
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">อีเมล <span class="text-slate-400 font-normal text-xs">(ไม่จำเป็น)</span></label>
          <input type="email" name="guest_email" maxlength="160"
                 value="<?= e($val('guest_email')) ?>"
                 class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-accent-500 focus:ring-2 focus:ring-accent-100 outline-none">
        </div>
        <input type="hidden" name="guest_line_user_id" :value="lineUserId">
        <div>
          <?php require __DIR__ . '/../partials/line_contact_picker.php'; ?>
        </div>
        <div>
          <label class="block text-sm font-semibold text-slate-700 mb-1">หมายเหตุ</label>
          <textarea name="notes" rows="2" maxlength="1000"
                    placeholder="เช่น ต้องการห้องติดน้ำ, มาตอนเช้า, โอนมัดจำแล้ว..."
                    class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-accent-500 focus:ring-2 focus:ring-accent-100 outline-none resize-y"><?= e($val('notes')) ?></textarea>
        </div>
      </div>

      <input type="hidden" name="send_line_confirm" :value="sendLine ? '1' : ''">

      <div class="flex items-center justify-between gap-3 pt-2 border-t border-slate-100">
        <a href="<?= url('/owner/bookings') ?>" class="px-4 py-2.5 text-sm text-slate-600 hover:text-slate-800 transition">ยกเลิก</a>
        <button type="submit"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-accent-600 hover:bg-accent-700 text-white font-semibold rounded-xl shadow transition">
          <i data-lucide="check" class="w-4 h-4"></i>
          บันทึกการจอง
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const __OB_PROPS__  = <?= $propJson ?>;
const __OB_UNITS__  = <?= $unitsJson ?>;

function ownerBookingForm() {
  return {
    source:         '<?= e($val('source','manual_phone')) ?>',
    propertyId:     '<?= e($val('property_id','')) ?>',
    unitId:         '<?= e($val('unit_id','')) ?>',
    checkIn:        '<?= e($val('check_in','')) ?>',
    checkOut:       '<?= e($val('check_out','')) ?>',
    guestCount:     <?= (int)$val('guest_count', 2) ?>,
    sendLine:       false,
    units:          [],
    guestName:      '<?= e($val('guest_name','')) ?>',
    guestPhone:     '<?= e($val('guest_phone','')) ?>',
    lineContacts:   [],
    lineContactsLoading: false,
    lineContactsSyncing: false,
    lineContactsSyncMsg: '',
    lineSearch:     '',
    showLineManual: false,
    lineUserId:     '<?= e($val('guest_line_user_id','')) ?>',
    selectedUnit:   null,
    estimatedNights: 0,
    estimatedTotal:  0,
    totalPrice:     '<?= e($val('total_price', '')) ?>',
    priceEdited:    false,
    deposit:        '<?= e($val('deposit_amount', '')) ?>',

    init() {
      this.onPropertyChange();
      this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    async onPropertyChange() {
      const pid = parseInt(this.propertyId);
      this.units = pid && __OB_UNITS__[pid] ? __OB_UNITS__[pid] : [];
      this.unitId = '';
      this.selectedUnit = null;
      this.estimatedNights = 0;
      this.estimatedTotal = 0;
      this.totalPrice = '';
      this.priceEdited = false;
      this.deposit = '';
      this.lineContacts = [];
      this.lineSearch = '';
      if (pid) await this.fetchLineContacts('');
      this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },

    async fetchLineContacts(search = '') {
      const pid = parseInt(this.propertyId);
      if (!pid) { this.lineContacts = []; return; }
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
      this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
    },
    searchLineContacts() {
      this.fetchLineContacts(this.lineSearch);
    },
    async syncLineContacts() {
      const pid = parseInt(this.propertyId);
      if (!pid || this.lineContactsSyncing) return;
      this.lineContactsSyncing = true;
      this.lineContactsSyncMsg = '';
      try {
        const r = await fetch(`<?= url('/owner/api/line-contacts/sync') ?>?property_id=${pid}`, { method: 'POST' });
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
      this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
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

    onUnitChange() {
      const uid = parseInt(this.unitId);
      this.selectedUnit = this.units.find(u => u.id === uid) || null;
      this.calcPrice();
    },

    formatMoney(n) {
      return '฿' + Number(n || 0).toLocaleString('th-TH');
    },
    get balance() {
      const t = parseFloat(this.totalPrice) || 0;
      const d = parseFloat(this.deposit) || 0;
      return Math.max(0, t - d);
    },
    async calcPrice() {
      if (!this.checkIn || !this.checkOut) return;
      const a = new Date(this.checkIn + 'T12:00:00');
      const b = new Date(this.checkOut + 'T12:00:00');
      const n = Math.round((b - a) / 86400000);
      this.estimatedNights = n > 0 ? n : 0;
      this.estimatedTotal = 0;
      if (!this.unitId || n <= 0) return;
      try {
        const q = new URLSearchParams({
          unit_id: this.unitId,
          check_in: this.checkIn,
          check_out: this.checkOut,
          guest_count: String(this.guestCount || 1),
        });
        const r = await fetch('<?= url('/owner/api/booking-quote') ?>?' + q);
        const data = await r.json();
        if (data.total != null) {
          this.estimatedTotal = data.total;
          if (!this.priceEdited) {
            this.totalPrice = String(Math.round(data.total));
          }
        }
      } catch (e) {}
    },
  };
}
</script>
