<?php
/**
 * @var array<string,mixed> $product
 * @var array<int,array<string,mixed>> $options
 * @var ?array<string,mixed> $user
 * @var array<string,string> $bank
 */
$u = $user ?? [];
$first = $options[0] ?? null;
$basePrice = $first ? (float)$first['price'] : (float)$product['base_price'];
$firstName = $first ? (string)$first['name'] : '';
$cover = \App\Models\ActivityProduct::coverImageUrl($product);
$providerName = trim((string)($product['provider_name'] ?? ''));
$cancellation = trim((string)($product['cancellation_policy'] ?? ''));
?>
<section class="max-w-6xl mx-auto px-4 sm:px-6 py-10 pb-32 lg:pb-10">
  <a href="<?= url('/activities/' . $product['slug']) ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3">
    <i data-lucide="arrow-left" class="w-4 h-4"></i> กลับไปหน้ากิจกรรม
  </a>
  <h1 class="text-2xl md:text-3xl font-bold">ซื้อ Voucher: <?= e($product['title']) ?></h1>

  <?php \App\Core\View::partial('partials/checkout-steps', ['active' => 2]); ?>

  <form method="post" action="<?= url('/activity/checkout/' . $product['id']) ?>" enctype="multipart/form-data"
        x-data="{
          qty: 1,
          price: <?= $basePrice ?>,
          optionName: <?= json_encode($firstName, JSON_UNESCAPED_UNICODE) ?>,
          method: 'promptpay',
          submitting: false,
          get total(){ return this.price * this.qty; }
        }"
        @submit="submitting = true"
        class="mt-2 grid grid-cols-1 lg:grid-cols-3 gap-6">
    <?= csrf() ?>

    <div class="lg:col-span-2 space-y-5">

      <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="user" class="w-5 h-5 text-primary-600"></i> ข้อมูลผู้ซื้อ</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">ชื่อ-นามสกุล</label>
            <input type="text" name="name" required value="<?= old('name', $u['name'] ?? '') ?>"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">เบอร์โทรศัพท์</label>
            <input type="tel" name="phone" required value="<?= old('phone', $u['phone'] ?? '') ?>"
                   inputmode="tel" pattern="[0-9\-+ ]{9,15}" placeholder="08x-xxx-xxxx"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
          </div>
          <div class="md:col-span-2">
            <label class="text-sm font-medium text-slate-700 mb-1 block">อีเมล (ไม่บังคับ)</label>
            <input type="email" name="email" value="<?= old('email', $u['email'] ?? '') ?>"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
          </div>
        </div>
      </div>

      <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="calendar-range" class="w-5 h-5 text-primary-600"></i> เลือกตัวเลือก & วันใช้บริการ</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
          <?php if ($options !== []): ?>
          <div class="md:col-span-2">
            <label class="text-sm font-medium text-slate-700 mb-1 block">ตัวเลือก</label>
            <select name="option_id"
                    @change="
                      const opt = $event.target.selectedOptions[0];
                      price = parseFloat(opt.dataset.price || 0);
                      optionName = opt.dataset.name || '';
                    "
                    class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
              <?php foreach ($options as $op): ?>
                <option value="<?= (int)$op['id'] ?>"
                        data-price="<?= (float)$op['price'] ?>"
                        data-name="<?= e($op['name']) ?>">
                  <?= e($op['name']) ?> — <?= format_money($op['price']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">จำนวน</label>
            <div class="flex items-center gap-2">
              <button type="button" @click="qty=Math.max(1,qty-1)" :disabled="qty<=1"
                      class="w-10 h-10 rounded-lg border border-slate-300 grid place-items-center hover:bg-slate-50 disabled:opacity-40">
                <i data-lucide="minus" class="w-4 h-4"></i>
              </button>
              <input type="number" name="quantity" min="1" max="20" x-model.number="qty"
                     class="w-20 text-center px-2 py-2.5 rounded-lg border border-slate-300 font-bold">
              <button type="button" @click="qty=Math.min(20,qty+1)" :disabled="qty>=20"
                      class="w-10 h-10 rounded-lg border border-slate-300 grid place-items-center hover:bg-slate-50 disabled:opacity-40">
                <i data-lucide="plus" class="w-4 h-4"></i>
              </button>
            </div>
          </div>
          <div>
            <label class="text-sm font-medium text-slate-700 mb-1 block">วันใช้บริการ</label>
            <input type="date" name="travel_date" min="<?= date('Y-m-d') ?>"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
          </div>
          <div class="md:col-span-2">
            <label class="text-sm font-medium text-slate-700 mb-1 block">ช่วงเวลา (ถ้ามี)</label>
            <input type="text" name="time_slot" placeholder="เช่น 09:00 - 12:00"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
          </div>
          <div class="md:col-span-2">
            <label class="text-sm font-medium text-slate-700 mb-1 block">หมายเหตุเพิ่มเติม</label>
            <textarea name="notes" rows="3" placeholder="เช่น มาเป็นกลุ่ม / มีเด็ก / แพ้อาหาร"
                      class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none"></textarea>
          </div>
        </div>
      </div>

      <?php \App\Core\View::partial('partials/payment-block', [
        'bank' => $bank,
        'amountVar' => 'total',
        'showGatewaySlot' => true,
      ]); ?>
    </div>

    <aside class="lg:col-span-1">
      <div class="bg-white border border-slate-200 rounded-2xl shadow-soft overflow-hidden lg:sticky lg:top-24">
        <img src="<?= e($cover) ?>" class="w-full h-32 object-cover" alt="<?= e($product['title']) ?>">
        <div class="p-5">
          <h3 class="font-bold leading-snug mb-1"><?= e($product['title']) ?></h3>
          <?php if ($providerName !== ''): ?>
            <div class="text-xs text-slate-500 mb-2 flex items-center gap-1"><i data-lucide="briefcase" class="w-3.5 h-3.5"></i> <?= e($providerName) ?></div>
          <?php endif; ?>

          <div class="text-sm py-2 border-t border-slate-100">
            <div class="flex justify-between"><span class="text-slate-500">ตัวเลือก</span><span class="font-semibold text-right" x-text="optionName || '-'"></span></div>
            <div class="flex justify-between mt-1"><span class="text-slate-500">ราคา/หน่วย</span><span>฿<span x-text="price.toLocaleString()"></span></span></div>
            <div class="flex justify-between mt-1"><span class="text-slate-500">จำนวน</span><span x-text="qty"></span></div>
          </div>

          <div class="flex justify-between font-bold text-xl text-primary-700 border-t border-slate-200 mt-3 pt-3">
            <span>รวม</span><span>฿<span x-text="total.toLocaleString()"></span></span>
          </div>
          <button type="submit" :disabled="submitting"
                  class="w-full mt-4 py-3 bg-accent-500 hover:bg-accent-600 disabled:bg-accent-300 text-white font-bold rounded-xl inline-flex items-center justify-center gap-2 transition">
            <i data-lucide="loader-2" class="w-5 h-5 animate-spin" x-show="submitting"></i>
            <i data-lucide="check-circle" class="w-5 h-5" x-show="!submitting"></i>
            <span x-show="!submitting">ยืนยันการซื้อ</span>
            <span x-show="submitting">กำลังส่ง...</span>
          </button>
          <p class="text-xs text-slate-500 mt-3 text-center">หลังตรวจสอบการชำระเงิน ทีมงานจะยืนยัน voucher กับผู้ให้บริการ</p>

          <?php if ($cancellation !== ''): ?>
            <details class="mt-4 text-xs">
              <summary class="cursor-pointer text-slate-600 hover:text-slate-900 flex items-center gap-1.5">
                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> นโยบายยกเลิก
              </summary>
              <div class="mt-2 text-slate-600 whitespace-pre-line"><?= e($cancellation) ?></div>
            </details>
          <?php endif; ?>
        </div>
      </div>

      <?php \App\Core\View::partial('partials/checkout-trust'); ?>
    </aside>

    <!-- Mobile sticky CTA -->
    <div class="fixed bottom-0 inset-x-0 lg:hidden bg-white border-t border-slate-200 px-4 py-3 z-40 shadow-[0_-4px_12px_rgba(0,0,0,0.06)]">
      <div class="flex items-center gap-3">
        <div class="flex-1">
          <div class="text-xs text-slate-500">รวม</div>
          <div class="font-bold text-lg text-primary-700">฿<span x-text="total.toLocaleString()"></span></div>
        </div>
        <button type="submit" :disabled="submitting"
                class="px-5 py-3 bg-accent-500 hover:bg-accent-600 disabled:bg-accent-300 text-white font-bold rounded-xl inline-flex items-center gap-2">
          <i data-lucide="loader-2" class="w-4 h-4 animate-spin" x-show="submitting"></i>
          <span x-show="!submitting">ยืนยันการซื้อ</span>
          <span x-show="submitting">กำลังส่ง...</span>
        </button>
      </div>
    </div>
  </form>
</section>
