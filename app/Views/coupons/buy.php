<?php /** @var int $face @var int $sale @var array $bank @var ?array $user @var list<array> $campaigns */
$u = $user ?? [];
$campaigns = $campaigns ?? [];
$campaignJson = htmlspecialchars(json_encode(array_map(static fn ($c) => [
    'code' => $c['code'],
    'name' => $c['name'],
    'face_value' => (float)$c['face_value'],
    'sale_price' => (float)$c['sale_price'],
], $campaigns), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
?>
<section class="max-w-5xl mx-auto px-4 sm:px-6 py-10 pb-32 lg:pb-10">
  <h1 class="text-2xl md:text-3xl font-bold flex items-center gap-2"><i data-lucide="gift" class="w-7 h-7 text-accent-600"></i> ซื้อคูปองเงินสด</h1>
  <p class="text-slate-600">ซื้อ ฿<?= $sale ?> รับคูปองมูลค่าใช้จริง ฿<?= $face ?></p>

  <?php \App\Core\View::partial('partials/checkout-steps', ['active' => 2]); ?>

  <form method="post" action="<?= url('/coupons/checkout') ?>" enctype="multipart/form-data"
        x-data="{
          qty: 1,
          sale: <?= $sale ?>,
          face: <?= $face ?>,
          baseSale: <?= $sale ?>,
          baseFace: <?= $face ?>,
          campaigns: <?= $campaignJson ?>,
          method: 'promptpay',
          submitting: false,
          applyCampaign(code) {
            if (!code) { this.sale = this.baseSale; this.face = this.baseFace; return; }
            var c = this.campaigns.find(function(x){ return x.code === code; });
            if (c) { this.sale = c.sale_price; this.face = c.face_value; }
          },
          get totalSale(){ return this.sale * this.qty; },
          get totalFace(){ return this.face * this.qty; },
          get savings(){ return (this.face - this.sale) * this.qty; }
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
            <label class="text-sm font-medium text-slate-700 mb-1 block">เบอร์โทรศัพท์ <span class="text-rose-500">*</span></label>
            <input type="tel" name="phone" required value="<?= old('phone', $u['phone'] ?? '') ?>"
                   inputmode="tel" pattern="[0-9\-+ ]{9,15}" placeholder="08x-xxx-xxxx"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
            <div class="text-xs text-slate-500 mt-1 flex items-center gap-1"><i data-lucide="info" class="w-3 h-3"></i> คูปองจะผูกกับเบอร์โทรนี้</div>
          </div>
          <div class="md:col-span-2">
            <label class="text-sm font-medium text-slate-700 mb-1 block">อีเมล (ไม่บังคับ)</label>
            <input type="email" name="email" value="<?= old('email', $u['email'] ?? '') ?>"
                   class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:border-primary-500 outline-none">
          </div>
        </div>
      </div>

      <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="ticket" class="w-5 h-5 text-primary-600"></i> จำนวนคูปอง</h2>
        <?php if ($campaigns !== []): ?>
        <div class="mt-3">
          <label class="text-sm font-medium text-slate-700 mb-1 block">แคมเปญ (ถ้ามี)</label>
          <select name="campaign_code" @change="applyCampaign($event.target.value)" class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-sm">
            <option value="">ราคามาตรฐาน</option>
            <?php foreach ($campaigns as $camp): ?>
            <option value="<?= e($camp['code']) ?>"><?= e($camp['name']) ?> — ซื้อ ฿<?= number_format((float)$camp['sale_price']) ?> ใช้ ฿<?= number_format((float)$camp['face_value']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="flex items-center gap-3 mt-3">
          <button type="button" @click="qty=Math.max(1,qty-1)" :disabled="qty<=1"
                  class="w-10 h-10 rounded-lg border border-slate-300 grid place-items-center hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">
            <i data-lucide="minus" class="w-4 h-4"></i>
          </button>
          <input type="number" name="qty" min="1" max="10" x-model.number="qty"
                 class="w-20 text-center px-2 py-2.5 rounded-lg border border-slate-300 font-bold">
          <button type="button" @click="qty=Math.min(10,qty+1)" :disabled="qty>=10"
                  class="w-10 h-10 rounded-lg border border-slate-300 grid place-items-center hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">
            <i data-lucide="plus" class="w-4 h-4"></i>
          </button>
          <span class="text-sm text-slate-500">ใบ (สูงสุด 10 ใบ/คำสั่งซื้อ)</span>
        </div>
        <div class="mt-3 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2 inline-flex items-center gap-1.5">
          <i data-lucide="sparkles" class="w-4 h-4"></i>
          <span>ประหยัด <b>฿<span x-text="savings.toLocaleString()"></span></b> · ใช้ได้จริง <b>฿<span x-text="totalFace.toLocaleString()"></span></b></span>
        </div>
      </div>

      <?php \App\Core\View::partial('partials/payment-block', [
        'bank' => $bank,
        'amountVar' => 'totalSale',
        'showGatewaySlot' => true,
      ]); ?>
    </div>

    <aside class="lg:col-span-1">
      <div class="bg-white border border-slate-200 rounded-2xl shadow-soft p-5 lg:sticky lg:top-24">
        <h3 class="font-bold mb-3">สรุปคำสั่งซื้อ</h3>
        <div class="flex justify-between text-sm py-2 border-b border-slate-100">
          <span>คูปอง ฿<?= $face ?> × <span x-text="qty"></span></span>
          <span>฿<span x-text="totalSale.toLocaleString()"></span></span>
        </div>
        <div class="flex justify-between text-sm py-2 border-b border-slate-100 text-emerald-600">
          <span class="inline-flex items-center gap-1"><i data-lucide="sparkles" class="w-4 h-4"></i> ประหยัด</span>
          <span>-฿<span x-text="savings.toLocaleString()"></span></span>
        </div>
        <div class="flex justify-between text-xs text-slate-500 py-1">
          <span>มูลค่าใช้จริงรวม</span>
          <span>฿<span x-text="totalFace.toLocaleString()"></span></span>
        </div>
        <div class="flex justify-between font-bold text-xl text-primary-700 py-3 border-t border-slate-200 mt-2">
          <span>รวมทั้งสิ้น</span>
          <span>฿<span x-text="totalSale.toLocaleString()"></span></span>
        </div>
        <button type="submit" :disabled="submitting"
                class="w-full mt-2 py-3 bg-accent-500 hover:bg-accent-600 disabled:bg-accent-300 text-white font-bold rounded-xl inline-flex items-center justify-center gap-2 transition">
          <i data-lucide="loader-2" class="w-5 h-5 animate-spin" x-show="submitting"></i>
          <i data-lucide="check-circle" class="w-5 h-5" x-show="!submitting"></i>
          <span x-show="!submitting">ยืนยันการซื้อ</span>
          <span x-show="submitting">กำลังส่ง...</span>
        </button>
        <p class="text-xs text-slate-500 mt-3 text-center">การกดยืนยันถือว่าคุณยอมรับเงื่อนไขการให้บริการ</p>
      </div>

      <?php \App\Core\View::partial('partials/checkout-trust'); ?>
    </aside>

    <!-- Mobile sticky CTA -->
    <div class="fixed bottom-0 inset-x-0 lg:hidden bg-white border-t border-slate-200 px-4 py-3 z-40 shadow-[0_-4px_12px_rgba(0,0,0,0.06)]">
      <div class="flex items-center gap-3">
        <div class="flex-1">
          <div class="text-xs text-slate-500">รวม (<span x-text="qty"></span> ใบ)</div>
          <div class="font-bold text-lg text-primary-700">฿<span x-text="totalSale.toLocaleString()"></span></div>
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
