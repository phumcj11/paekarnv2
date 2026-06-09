<?php
/** @var array<string,mixed> $order */
$voucher = (string) ($order['voucher_code'] ?? '');
$qrBase64 = $voucher !== '' ? \App\Services\CouponQrImageService::pngBase64($voucher) : null;
$shareUrl = url('/track-order?voucher=' . urlencode($voucher));
$lineShare = 'https://line.me/R/share?text=' . urlencode(
    'รับ Voucher: ' . ($order['product_title'] ?? '') . ' รหัส ' . $voucher . ' — ' . $shareUrl
);
?>
<section class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
  <?php \App\Core\View::partial('partials/checkout-steps', ['active' => 3]); ?>

  <div class="text-center mb-8">
    <div class="w-20 h-20 mx-auto rounded-full bg-emerald-500 grid place-items-center text-white shadow-lg">
      <i data-lucide="check" class="w-10 h-10"></i>
    </div>
    <h1 class="mt-4 text-2xl md:text-3xl font-bold">รับคำสั่งซื้อกิจกรรมแล้ว</h1>
    <p class="text-slate-600">หมายเลขคำสั่งซื้อ: <b><?= e($order['order_no']) ?></b></p>
    <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-amber-100 text-amber-700 rounded-full text-sm font-semibold">
      <i data-lucide="clock" class="w-4 h-4"></i> รอตรวจสอบ / ยืนยันกับผู้ให้บริการ
    </div>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-5">
    <h2 class="font-bold mb-3"><?= e($order['product_title']) ?></h2>
    <div class="rounded-xl border-2 border-dashed border-accent-300 bg-accent-50/30 p-4">
      <div class="grid grid-cols-1 sm:grid-cols-[140px_1fr] gap-4 items-center">
        <?php if ($qrBase64): ?>
          <div class="bg-white rounded-xl p-2 border border-slate-200 w-32 h-32 mx-auto sm:mx-0">
            <img src="data:image/png;base64,<?= $qrBase64 ?>" alt="QR voucher" class="w-full h-full object-contain">
          </div>
        <?php else: ?>
          <div class="w-32 h-32 mx-auto sm:mx-0 bg-white rounded-xl border border-slate-200 grid place-items-center">
            <i data-lucide="ticket" class="w-12 h-12 text-accent-400"></i>
          </div>
        <?php endif; ?>
        <div>
          <div class="text-xs text-slate-500">VOUCHER CODE</div>
          <div class="font-mono font-bold text-xl text-primary-700 break-all"><?= e($voucher) ?></div>
          <?php if (!empty($order['travel_date'])): ?>
            <div class="text-sm text-slate-600 mt-2 flex items-center gap-1"><i data-lucide="calendar" class="w-4 h-4"></i> <?= e($order['travel_date']) ?> <?= e($order['time_slot'] ?? '') ?></div>
          <?php endif; ?>
          <div class="text-sm text-slate-600 mt-1 flex items-center gap-1"><i data-lucide="users" class="w-4 h-4"></i> จำนวน <?= (int)$order['quantity'] ?> · รวม <?= format_money($order['total_price']) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-5">
    <div class="font-semibold mb-2 flex items-center gap-1.5"><i data-lucide="share-2" class="w-4 h-4 text-primary-600"></i> แชร์ / เก็บ voucher</div>
    <div class="flex flex-wrap gap-2"
         x-data="{ copied: false, copyLink(){ navigator.clipboard.writeText('<?= e($shareUrl) ?>').then(()=>{ this.copied=true; setTimeout(()=>this.copied=false, 1500); }); } }">
      <a href="<?= e($lineShare) ?>" target="_blank" rel="noopener"
         class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold">
        <i data-lucide="message-circle" class="w-4 h-4"></i> ส่งไป LINE
      </a>
      <button type="button" @click="copyLink()"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 hover:bg-slate-50 text-sm font-semibold">
        <span x-show="!copied" class="inline-flex items-center gap-2"><i data-lucide="link-2" class="w-4 h-4"></i> คัดลอกลิงก์ติดตาม</span>
        <span x-show="copied" class="inline-flex items-center gap-2 text-emerald-700"><i data-lucide="check" class="w-4 h-4"></i> คัดลอกแล้ว</span>
      </button>
      <a href="<?= e($shareUrl) ?>"
         class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 hover:bg-slate-50 text-sm font-semibold">
        <i data-lucide="search" class="w-4 h-4"></i> ติดตามคำสั่งซื้อ
      </a>
    </div>
  </div>

  <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
    <div class="font-semibold mb-1 flex items-center gap-1.5"><i data-lucide="info" class="w-4 h-4"></i> วิธีใช้งาน</div>
    <ol class="list-decimal list-inside space-y-0.5">
      <li>เก็บรหัส voucher นี้ไว้ (แนะนำให้บันทึก / แชร์ไป LINE ตัวเอง)</li>
      <li>ทีมงานตรวจสอบการชำระเงินและยืนยันรอบกับผู้ให้บริการ</li>
      <li>แสดงรหัส voucher / QR กับผู้ให้บริการในวันใช้บริการ</li>
    </ol>
  </div>

  <div class="mt-5 flex flex-wrap gap-3 justify-center">
    <a href="<?= url('/activities') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent-500 hover:bg-accent-600 text-white rounded-xl font-semibold">ดูกิจกรรมอื่น</a>
    <button onclick="window.print()" class="inline-flex items-center gap-2 px-5 py-2.5 border border-slate-300 hover:bg-slate-50 rounded-xl font-semibold">
      <i data-lucide="printer" class="w-4 h-4"></i> พิมพ์
    </button>
  </div>
</section>
