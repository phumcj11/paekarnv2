<?php /** @var array $order @var array $coupons */ ?>
<section class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
  <?php \App\Core\View::partial('partials/checkout-steps', ['active' => 3]); ?>

  <div class="text-center mb-8">
    <div class="w-20 h-20 mx-auto rounded-full bg-emerald-500 grid place-items-center text-white shadow-lg">
      <i data-lucide="check" class="w-10 h-10"></i>
    </div>
    <h1 class="mt-4 text-2xl md:text-3xl font-bold">ขอบคุณสำหรับการสั่งซื้อ!</h1>
    <p class="text-slate-600">หมายเลขคำสั่งซื้อ: <b><?= e($order['order_no']) ?></b></p>
    <?php if ($order['status'] === 'pending'): ?>
      <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-amber-100 text-amber-700 rounded-full text-sm font-semibold">
        <i data-lucide="clock" class="w-4 h-4"></i> รอตรวจสอบสลิป
      </div>
    <?php else: ?>
      <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-full text-sm font-semibold">
        <i data-lucide="check-circle" class="w-4 h-4"></i> ชำระเงินสำเร็จ
      </div>
    <?php endif; ?>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-5">
    <h3 class="font-bold mb-3">รหัสคูปองของคุณ (<?= count($coupons) ?> ใบ)</h3>
    <div class="space-y-3">
      <?php foreach ($coupons as $c):
        $qr = \App\Services\CouponQrImageService::pngBase64((string)$c['code']);
      ?>
      <div class="grid grid-cols-1 sm:grid-cols-[100px_1fr_auto] gap-3 items-center p-4 rounded-xl border-2 border-dashed border-accent-300 bg-accent-50/30">
        <?php if ($qr): ?>
          <div class="bg-white rounded-lg p-1.5 border border-slate-200 w-24 h-24 mx-auto sm:mx-0">
            <img src="data:image/png;base64,<?= $qr ?>" alt="QR คูปอง" class="w-full h-full object-contain">
          </div>
        <?php else: ?>
          <div class="w-24 h-24 mx-auto sm:mx-0 grid place-items-center bg-white rounded-lg border border-slate-200">
            <i data-lucide="ticket" class="w-10 h-10 text-accent-400"></i>
          </div>
        <?php endif; ?>
        <div>
          <div class="text-xs text-slate-500">CODE</div>
          <div class="font-mono font-bold text-lg text-primary-700 break-all"><?= e($c['code']) ?></div>
          <div class="text-xs text-slate-500 mt-1 flex items-center gap-1"><i data-lucide="calendar" class="w-3.5 h-3.5"></i> หมดอายุ <?= format_date_th($c['expires_at']) ?></div>
        </div>
        <div class="text-right">
          <div class="text-3xl font-extrabold text-accent-600">฿<?= number_format($c['face_value']) ?></div>
          <div class="text-xs text-slate-500">มูลค่าใช้จริง</div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php $shareText = 'รับคูปองเงินสด ' . count($coupons) . ' ใบจาก ' . \App\Models\Setting::get('site_name', 'แพกาญ.com'); ?>
    <div class="flex flex-wrap gap-2 mt-4">
      <a href="https://line.me/R/share?text=<?= urlencode($shareText) ?>" target="_blank" rel="noopener"
         class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold">
        <i data-lucide="message-circle" class="w-4 h-4"></i> แชร์ไป LINE
      </a>
    </div>
  </div>

  <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
    <div class="font-semibold mb-1 flex items-center gap-1.5"><i data-lucide="info" class="w-4 h-4"></i> วิธีใช้งาน</div>
    <ol class="list-decimal list-inside space-y-0.5">
      <li>เลือกที่พักที่ใช้คูปองได้ <a href="<?= url('/properties?coupon=1') ?>" class="text-primary-700 underline">คลิกดูรายการ</a></li>
      <li>กด <strong>จองที่พัก</strong> แล้วเลือกคูปองจากกระเป๋าหรือกรอกรหัสก่อนชำระเงิน</li>
      <li>เจ้าของแพจะ verify รหัสตอนเช็คอิน</li>
    </ol>
  </div>

  <div class="mt-5 flex flex-wrap gap-3 justify-center">
    <a href="<?= url('/properties?coupon=1') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent-500 hover:bg-accent-600 text-white rounded-xl font-semibold">
      <i data-lucide="hotel" class="w-4 h-4"></i> ค้นหาที่พัก
    </a>
    <a href="<?= url('/account/coupons') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 border border-slate-300 hover:bg-slate-50 rounded-xl font-semibold">
      <i data-lucide="ticket" class="w-4 h-4"></i> คูปองของฉัน
    </a>
    <button onclick="window.print()" class="inline-flex items-center gap-2 px-5 py-2.5 border border-slate-300 hover:bg-slate-50 rounded-xl font-semibold">
      <i data-lucide="printer" class="w-4 h-4"></i> พิมพ์
    </button>
  </div>
</section>
