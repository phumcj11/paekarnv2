<?php /** @var array $property @var array $units @var ?array $unit @var ?array $user @var string $booking_intent @var array $wallet_coupons @var string $prefill_coupon */
use App\Support\UnitPricing;
use App\Support\PropertyBookingCapabilities;
$custUser = $user ?? [];
$bookingIntent = 'book';
$walletCoupons = is_array($wallet_coupons ?? null) ? $wallet_coupons : [];
$prefillCoupon = trim((string)($prefill_coupon ?? ''));
$bank = [
    'bank_name'    => \App\Models\Setting::get('bank_name', ''),
    'bank_account' => \App\Models\Setting::get('bank_account', ''),
    'bank_holder'  => \App\Models\Setting::get('bank_holder', ''),
    'promptpay'    => \App\Models\Setting::get('promptpay_id', ''),
];
$showPayment = PropertyBookingCapabilities::showPayment($property, $bookingIntent);
$pageHeading = 'จองที่พัก';
$pageIcon = 'calendar-plus';
?><section class="max-w-6xl mx-auto px-4 sm:px-6 py-8 pb-32 lg:pb-8">
  <a href="<?= url('/property/' . $property['slug']) ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3">
    <i data-lucide="arrow-left" class="w-4 h-4"></i> กลับไปหน้าที่พัก
  </a>
  <h1 class="text-2xl md:text-3xl font-bold flex items-center gap-2"><i data-lucide="<?= e($pageIcon) ?>" class="w-7 h-7 text-accent-600"></i> <?= e($pageHeading) ?> <?= e($property['name']) ?></h1>
  <?php if ((int)($property['coupon_enabled'] ?? 0) === 1): ?>
  <p class="mt-2 text-sm text-slate-600">มีคูปองอยู่แล้ว? เลือกจากกระเป๋าหรือกรอกรหัสด้านล่างก่อนชำระเงิน</p>
  <?php endif; ?>
  <?php \App\Core\View::partial('partials/checkout-steps', ['active' => $showPayment ? 2 : 1]); ?>

  <form method="post" action="<?= url('/booking') ?>" enctype="multipart/form-data"
        x-data="{
          unit: <?= (int)($unit['id'] ?? 0) ?>,
          unitData: {price: <?= (float)($unit['price'] ?? 0) ?>, weekend: <?= (float)($unit['price_weekend'] ?? 0) ?>, priceIncludes: <?= (int)UnitPricing::includedGuests($unit ?? ['capacity_max' => 1]) ?>, capacityMax: <?= (int)($unit['capacity_max'] ?? 1) ?>, extraFee: <?= (float)($unit['extra_person_fee'] ?? 0) ?>},
          checkIn: '<?= date('Y-m-d', strtotime('+3 day')) ?>',
          checkOut: '<?= date('Y-m-d', strtotime('+4 day')) ?>',
          guests: 2,
          coupon: '',
          discount: 0,
          couponMsg: '',
          couponOk: false,
          couponChecking: false,
          walletCoupons: <?= json_encode(array_map(static fn(array $c): array => [
              'code' => (string)$c['code'],
              'face_value' => (float)$c['face_value'],
              'status' => (string)$c['status'],
          ], $walletCoupons), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
          method: 'promptpay',
          submitting: false,
          get nights(){ const d=(new Date(this.checkOut)-new Date(this.checkIn))/86400000; return Math.max(1, Math.round(d)); },
          get subtotal(){
            let total=0;
            for(let i=0;i<this.nights;i++){
              const d=new Date(this.checkIn); d.setDate(d.getDate()+i);
              const dow=d.getDay();
              const p=(dow===5||dow===6) && this.unitData.weekend>0 ? this.unitData.weekend : this.unitData.price;
              total += p;
            }
            const extra = Math.max(0, this.guests - this.unitData.priceIncludes) * this.unitData.extraFee * this.nights;
            return total + extra;
          },
          get total(){ return Math.max(0, this.subtotal - this.discount); },
          validateCoupon(){
            if (!this.coupon) { this.discount=0; this.couponMsg=''; this.couponOk=false; return; }
            this.couponChecking = true; this.couponMsg = '';
            fetch('<?= url('/api-validate-coupon') ?>?code=' + encodeURIComponent(this.coupon))
              .then(r=>r.json()).then(d=>{
                this.couponChecking = false;
                if (d.ok) { this.discount = parseFloat(d.value); this.couponOk = true; this.couponMsg = 'ใช้คูปองได้ ลด ฿' + this.discount.toLocaleString(); }
                else { this.discount = 0; this.couponOk = false; this.couponMsg = d.msg || 'คูปองใช้ไม่ได้'; }
              }).catch(()=>{ this.couponChecking=false; this.couponOk=false; this.couponMsg='ตรวจสอบไม่สำเร็จ'; });
          },
          selectWalletCoupon(code){
            this.coupon = code;
            this.validateCoupon();
          },
          init(){
            const prefill = <?= json_encode($prefillCoupon, JSON_UNESCAPED_UNICODE) ?>;
            if (prefill) {
              this.coupon = prefill;
              this.$nextTick(() => this.validateCoupon());
            } else if (this.walletCoupons.length === 1) {
              this.coupon = this.walletCoupons[0].code;
              this.$nextTick(() => this.validateCoupon());
            }
          }
        }"
        x-init="init()"
        @submit="submitting = true"
        class="mt-2 grid grid-cols-1 lg:grid-cols-3 gap-6">
    <?= csrf() ?>
    <input type="hidden" name="property_id" value="<?= $property['id'] ?>">
    <input type="hidden" name="booking_intent" value="<?= e($bookingIntent) ?>">

    <div class="lg:col-span-2 space-y-5">

      <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="bed-double" class="w-5 h-5 text-primary-600"></i> เลือกห้องพัก</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
          <?php foreach ($units as $u): ?>
          <label class="cursor-pointer">
            <input type="radio" name="unit_id" value="<?= $u['id'] ?>" <?= $unit && $u['id']==$unit['id']?'checked':'' ?>
                   x-model.number="unit"
                   @change="unitData={price: <?= (float)$u['price'] ?>, weekend: <?= (float)$u['price_weekend'] ?>, priceIncludes: <?= (int)UnitPricing::includedGuests($u) ?>, capacityMax: <?= (int)$u['capacity_max'] ?>, extraFee: <?= (float)$u['extra_person_fee'] ?>}"
                   class="hidden">
            <div class="border-2 rounded-xl p-3 flex gap-3 transition" :class="unit===<?= $u['id'] ?>?'border-accent-500 bg-accent-50':'border-slate-200 hover:border-slate-300'">
              <img src="<?= e(upload_url($u['cover_image']) ?: 'https://placehold.co/200x150') ?>" class="w-20 h-20 object-cover rounded-lg">
              <div class="flex-1">
                <div class="font-semibold text-sm"><?= e($u['name']) ?></div>
                <div class="text-xs text-slate-500 mt-0.5"><?= $u['capacity_min'] ?>–<?= $u['capacity_max'] ?> ท่าน · <?= $u['bedrooms'] ?>BR</div>
                <div class="text-primary-700 font-bold mt-1"><?= format_money($u['price']) ?> <span class="text-xs font-normal">/ คืน</span></div>
              </div>
            </div>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="calendar" class="w-5 h-5 text-primary-600"></i> วันที่ & จำนวนผู้เข้าพัก</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3">
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block"><?= __('check_in') ?></label>
            <input type="date" name="check_in" required x-model="checkIn" min="<?= date('Y-m-d') ?>"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block"><?= __('check_out') ?></label>
            <input type="date" name="check_out" required x-model="checkOut" :min="checkIn"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block"><?= __('guests') ?></label>
            <input type="number" name="guest_count" required x-model.number="guests" :min="1" :max="unitData.capacityMax || 30"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
          </div>
        </div>
      </div>

      <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="user" class="w-5 h-5 text-primary-600"></i> ข้อมูลผู้จอง</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">ชื่อ-นามสกุล</label>
            <input type="text" name="guest_name" required value="<?= e($custUser['name'] ?? '') ?>"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">เบอร์โทรศัพท์</label>
            <input type="tel" name="guest_phone" required value="<?= e($custUser['phone'] ?? '') ?>"
                   inputmode="tel" pattern="[0-9\-+ ]{9,15}" placeholder="08x-xxx-xxxx"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
          </div>
          <div class="md:col-span-2">
            <label class="text-sm font-medium text-slate-700 mb-1 block">อีเมล (ไม่บังคับ)</label>
            <input type="email" name="guest_email" value="<?= e($custUser['email'] ?? '') ?>"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
          </div>
          <div class="md:col-span-2">
            <label class="text-sm font-medium text-slate-700 mb-1 block">หมายเหตุเพิ่มเติม</label>
            <textarea name="notes" rows="3" placeholder="ขอเตียงเสริม / มาถึงดึก ฯลฯ" class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none"></textarea>
          </div>
        </div>
      </div>

      <?php if ((int)($property['coupon_enabled'] ?? 0) === 1): ?>
      <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="ticket" class="w-5 h-5 text-rose-600"></i> ใช้คูปองส่วนลด</h2>

        <?php if ($walletCoupons !== []): ?>
        <div class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50/60 p-3">
          <p class="text-sm font-semibold text-emerald-800">คุณมีคูปอง <?= count($walletCoupons) ?> ใบที่ใช้ได้</p>
          <div class="mt-2 flex flex-wrap gap-2">
            <?php foreach ($walletCoupons as $wc): ?>
            <button type="button"
                    @click="selectWalletCoupon(<?= json_encode((string)$wc['code'], JSON_UNESCAPED_UNICODE) ?>)"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-white px-3 py-2 text-xs font-bold text-emerald-800 hover:bg-emerald-50">
              <i data-lucide="ticket" class="h-3.5 w-3.5"></i>
              <?= e((string)$wc['code']) ?> · ฿<?= number_format((float)$wc['face_value']) ?>
            </button>
            <?php endforeach; ?>
          </div>
        </div>
        <?php else: ?>
        <p class="mt-2 text-sm text-slate-600">ยังไม่มีคูปองในกระเป๋า — <a href="<?= url('/coupons/buy') ?>" class="font-semibold text-rose-600 underline">ซื้อคูปอง</a> ก่อนจองเพื่อรับส่วนลด</p>
        <?php endif; ?>

        <div class="flex gap-2 mt-3">
          <input type="text" name="coupon_code" x-model="coupon" placeholder="PKAN-XXXX-XXXX"
                 class="flex-1 px-3 py-2.5 rounded-lg border border-slate-300 font-mono uppercase focus:border-rose-500 outline-none">
          <button type="button" @click="validateCoupon()" :disabled="!coupon || couponChecking"
                  class="px-4 py-2.5 bg-rose-500 hover:bg-rose-600 disabled:bg-slate-300 text-white rounded-lg font-semibold inline-flex items-center gap-1.5">
            <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="couponChecking"></i>
            <span x-show="!couponChecking">ตรวจสอบ</span>
            <span x-show="couponChecking">กำลังตรวจ...</span>
          </button>
        </div>
        <div x-show="couponMsg" x-transition class="mt-2 text-sm font-semibold inline-flex items-center gap-1"
             :class="couponOk ? 'text-emerald-600' : 'text-rose-600'">
          <i :data-lucide="couponOk ? 'check-circle' : 'x-circle'" class="w-4 h-4"></i>
          <span x-text="couponMsg"></span>
        </div>
        <p class="text-xs text-slate-500 mt-2">* ระบบจะตรวจสอบความถูกต้องอีกครั้งตอนส่งฟอร์ม</p>
      </div>
      <?php endif; ?>
      <?php if ($showPayment): ?>
        <?php \App\Core\View::partial('partials/payment-block', [
          'bank' => $bank,
          'amountVar' => 'total',
          'showGatewaySlot' => true,
        ]); ?>
      <?php endif; ?>
    </div>

    <aside class="lg:col-span-1">
      <div class="bg-white border border-slate-200 rounded-2xl shadow-soft p-5 lg:sticky lg:top-24">
        <h3 class="font-bold mb-3">สรุปการจอง</h3>
        <div class="text-sm space-y-1.5">
          <div class="flex justify-between"><span class="text-slate-500">เช็คอิน</span><span x-text="checkIn"></span></div>
          <div class="flex justify-between"><span class="text-slate-500">เช็คเอาท์</span><span x-text="checkOut"></span></div>
          <div class="flex justify-between"><span class="text-slate-500">จำนวนคืน</span><span><span x-text="nights"></span> คืน</span></div>
          <div class="flex justify-between"><span class="text-slate-500">ผู้เข้าพัก</span><span><span x-text="guests"></span> ท่าน</span></div>
        </div>
        <hr class="my-3 border-slate-200">
        <div class="text-sm space-y-1.5">
          <div class="flex justify-between"><span class="text-slate-500">ค่าที่พัก</span><span>฿<span x-text="subtotal.toLocaleString()"></span></span></div>
          <div class="flex justify-between text-rose-600" x-show="discount>0"><span>ส่วนลดคูปอง</span><span>-฿<span x-text="discount.toLocaleString()"></span></span></div>
        </div>
        <hr class="my-3 border-slate-200">
        <div class="flex justify-between font-bold text-xl text-primary-700">
          <span>รวมทั้งสิ้น</span><span>฿<span x-text="total.toLocaleString()"></span></span>
        </div>
        <button type="submit" :disabled="submitting"
                class="w-full mt-4 py-3 bg-accent-500 hover:bg-accent-600 disabled:bg-accent-300 text-white font-bold rounded-xl inline-flex items-center justify-center gap-2 transition">
          <i data-lucide="loader-2" class="w-5 h-5 animate-spin" x-show="submitting"></i>
          <i data-lucide="check-circle" class="w-5 h-5" x-show="!submitting"></i>
          <span x-show="!submitting">ยืนยันการจอง</span>
          <span x-show="submitting">กำลังส่ง...</span>
        </button>
        <p class="text-xs text-slate-500 mt-2 text-center">ฟรี ไม่มีค่าธรรมเนียมเพิ่ม · ทางที่พักจะติดต่อภายใน 24 ชม.</p>
      </div>

      <?php \App\Core\View::partial('partials/checkout-trust'); ?>
    </aside>

    <!-- Mobile sticky CTA -->
    <div class="fixed bottom-0 inset-x-0 lg:hidden bg-white border-t border-slate-200 px-4 py-3 z-40 shadow-[0_-4px_12px_rgba(0,0,0,0.06)]">
      <div class="flex items-center gap-3">
        <div class="flex-1">
          <div class="text-xs text-slate-500">รวมทั้งสิ้น</div>
          <div class="font-bold text-lg text-primary-700">฿<span x-text="total.toLocaleString()"></span></div>
        </div>
        <button type="submit" :disabled="submitting"
                class="px-5 py-3 bg-accent-500 hover:bg-accent-600 disabled:bg-accent-300 text-white font-bold rounded-xl inline-flex items-center gap-2">
          <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="submitting"></i>
          <span x-show="!submitting">ยืนยันการจอง</span>
          <span x-show="submitting">กำลังส่ง...</span>
        </button>
      </div>
    </div>
  </form>
</section>
