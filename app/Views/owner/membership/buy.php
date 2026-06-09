<?php /** @var array $plan @var array $bank */ ?>
<section class="max-w-5xl mx-auto space-y-6">
  <div class="flex flex-wrap items-center gap-3">
    <a href="<?= url('/owner/membership') ?>" class="text-sm text-primary-600 hover:underline inline-flex items-center gap-1"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับไปรายการแพ็กเกจ</a>
  </div>

  <form method="post" action="<?= url('/owner/membership/checkout') ?>" enctype="multipart/form-data"
        x-data="{ method: 'promptpay' }"
        class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <?= csrf() ?>
    <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">

    <div class="lg:col-span-2 space-y-5">
      <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <h1 class="font-bold text-xl flex items-center gap-2"><i data-lucide="award" class="w-6 h-6 text-amber-500"></i> สมัคร <?= e($plan['code']) ?></h1>
        <p class="text-sm text-slate-600 mt-2">
          <?= ($plan['tier'] ?? '') === 'vip' ? 'แพ็กเกจ VIP — รวมสิทธิ์รับลูกค้าจากฟอร์มหาที่พัก (เมื่อมีที่พักเผยแพร่และตรงเงื่อนไข)' : 'แพ็กเกจสมาชิกธรรมดา' ?>
        </p>
      </div>

      <div class="bg-white border border-slate-200 rounded-2xl p-5">
        <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="credit-card" class="w-5 h-5 text-primary-600"></i> วิธีการชำระเงิน</h2>
        <div class="grid grid-cols-2 gap-2 mt-3">
          <label class="flex items-center gap-2 px-4 py-3 border-2 rounded-xl cursor-pointer transition"
                 :class="method==='promptpay'?'border-accent-500 bg-accent-50':'border-slate-200'">
            <input type="radio" name="payment_method" value="promptpay" x-model="method" class="hidden">
            <i data-lucide="qr-code" class="w-5 h-5 text-accent-600"></i>
            <span class="font-semibold text-sm">PromptPay</span>
          </label>
          <label class="flex items-center gap-2 px-4 py-3 border-2 rounded-xl cursor-pointer transition"
                 :class="method==='bank_transfer'?'border-accent-500 bg-accent-50':'border-slate-200'">
            <input type="radio" name="payment_method" value="bank_transfer" x-model="method" class="hidden">
            <i data-lucide="landmark" class="w-5 h-5 text-accent-600"></i>
            <span class="font-semibold text-sm">โอนธนาคาร</span>
          </label>
        </div>

        <div class="mt-4 p-4 bg-slate-50 border border-slate-200 rounded-xl text-sm">
          <div x-show="method==='promptpay'">
            <div class="font-semibold mb-1">PromptPay</div>
            <div>พร้อมเพย์: <b><?= e($bank['promptpay']) ?></b></div>
            <div class="text-slate-600">ชื่อบัญชี: <?= e($bank['bank_holder']) ?></div>
          </div>
          <div x-show="method==='bank_transfer'">
            <div class="font-semibold mb-1"><?= e($bank['bank_name']) ?></div>
            <div>เลขที่บัญชี: <b><?= e($bank['bank_account']) ?></b></div>
            <div class="text-slate-600">ชื่อบัญชี: <?= e($bank['bank_holder']) ?></div>
          </div>
        </div>

        <div class="mt-4">
          <label class="text-sm font-medium text-slate-700 mb-1 block">อัปโหลดสลิป <span class="text-slate-400 font-normal">(ถ้ามี)</span></label>
          <input type="file" name="slip" accept="image/*"
                 class="w-full text-sm file:mr-3 file:px-4 file:py-2.5 file:rounded-lg file:border-0 file:bg-primary-600 file:text-white file:font-semibold file:hover:bg-primary-700 file:cursor-pointer">
          <p class="text-xs text-slate-500 mt-1">เมื่อแนบสลิป ระบบจะบันทึกเป็น &quot;ชำระแล้ว&quot; และเปิดสิทธิ์ทันที — หากไม่แนบ จะอยู่สถานะรอแอดมิน</p>
        </div>
      </div>
    </div>

    <aside class="lg:col-span-1">
      <div class="bg-white border border-slate-200 rounded-2xl shadow-soft p-5 lg:sticky lg:top-24">
        <h3 class="font-bold mb-3">สรุป</h3>
        <div class="flex justify-between text-sm py-2 border-b border-slate-100">
          <span>แพ็กเกจ</span>
          <span class="font-semibold"><?= e($plan['code']) ?></span>
        </div>
        <div class="flex justify-between font-bold text-xl text-primary-700 py-4">
          <span>ยอดชำระ</span>
          <span><?= format_money($plan['price']) ?></span>
        </div>
        <button type="submit" class="w-full py-3 bg-accent-500 hover:bg-accent-600 text-white font-bold rounded-xl inline-flex items-center justify-center gap-2">
          <i data-lucide="check-circle" class="w-4 h-4"></i> ยืนยันคำสั่งซื้อ
        </button>
      </div>
    </aside>
  </form>
</section>
