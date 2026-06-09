<?php
/** @var array $booking */
$bookingCode = (string) ($booking['code'] ?? '');
$shareUrl = url('/track-order?booking=' . urlencode($bookingCode));
$lineShare = 'https://line.me/R/share?text=' . urlencode('การจองของฉัน เลขที่ ' . $bookingCode . ' — ' . $shareUrl);
?>
<section class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
  <?php \App\Core\View::partial('partials/checkout-steps', ['active' => 3]); ?>

  <div class="text-center mb-8">
    <div class="w-20 h-20 mx-auto rounded-full bg-emerald-500 grid place-items-center text-white shadow-lg">
      <i data-lucide="check" class="w-10 h-10"></i>
    </div>
    <h1 class="mt-4 text-2xl md:text-3xl font-bold">การจองของคุณส่งเรียบร้อย</h1>
    <p class="text-slate-600 mt-1">เลขที่การจอง: <b><?= e($bookingCode) ?></b></p>
    <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-amber-100 text-amber-700 rounded-full text-sm font-semibold">
      <i data-lucide="clock" class="w-4 h-4"></i> รอที่พักยืนยัน
    </div>
  </div>

  <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
    <img src="<?= e(upload_url($booking['cover_image'])) ?>" class="w-full h-48 object-cover" alt="<?= e($booking['property_name']) ?>">
    <div class="p-5">
      <h2 class="font-bold text-xl"><?= e($booking['property_name']) ?></h2>
      <div class="text-sm text-slate-600"><?= e($booking['unit_name']) ?></div>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4 text-sm">
        <div>
          <div class="text-xs text-slate-500">เช็คอิน</div>
          <div class="font-semibold"><?= format_date_th($booking['check_in']) ?></div>
        </div>
        <div>
          <div class="text-xs text-slate-500">เช็คเอาท์</div>
          <div class="font-semibold"><?= format_date_th($booking['check_out']) ?></div>
        </div>
        <div>
          <div class="text-xs text-slate-500">จำนวนคืน</div>
          <div class="font-semibold"><?= $booking['nights'] ?> คืน</div>
        </div>
        <div>
          <div class="text-xs text-slate-500">ผู้เข้าพัก</div>
          <div class="font-semibold"><?= $booking['guest_count'] ?> ท่าน</div>
        </div>
      </div>

      <div class="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
        <div class="flex justify-between text-sm py-1"><span>ค่าที่พัก</span><span><?= format_money($booking['subtotal']) ?></span></div>
        <?php if ($booking['discount']>0): ?>
          <div class="flex justify-between text-sm py-1 text-rose-600"><span>ส่วนลดคูปอง (<?= e($booking['coupon_code_used']) ?>)</span><span>-<?= format_money($booking['discount']) ?></span></div>
        <?php endif; ?>
        <hr class="my-2 border-slate-200">
        <div class="flex justify-between font-bold text-lg text-primary-700"><span>รวม</span><span><?= format_money($booking['total_price']) ?></span></div>
      </div>
    </div>
  </div>

  <div class="mt-5 bg-white rounded-2xl border border-slate-200 p-5">
    <div class="font-semibold mb-2 flex items-center gap-1.5"><i data-lucide="share-2" class="w-4 h-4 text-primary-600"></i> ติดตาม / แชร์การจอง</div>
    <div class="flex flex-wrap gap-2"
         x-data="{ copied:false, copy(){ navigator.clipboard.writeText('<?= e($shareUrl) ?>').then(()=>{ this.copied=true; setTimeout(()=>this.copied=false, 1500); }); } }">
      <a href="<?= e($lineShare) ?>" target="_blank" rel="noopener"
         class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-semibold">
        <i data-lucide="message-circle" class="w-4 h-4"></i> ส่งไป LINE
      </a>
      <button type="button" @click="copy()"
              class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 hover:bg-slate-50 text-sm font-semibold">
        <span x-show="!copied" class="inline-flex items-center gap-2"><i data-lucide="link-2" class="w-4 h-4"></i> คัดลอกลิงก์</span>
        <span x-show="copied" class="inline-flex items-center gap-2 text-emerald-700"><i data-lucide="check" class="w-4 h-4"></i> คัดลอกแล้ว</span>
      </button>
      <a href="<?= e($shareUrl) ?>"
         class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-300 hover:bg-slate-50 text-sm font-semibold">
        <i data-lucide="search" class="w-4 h-4"></i> ติดตามการจอง
      </a>
    </div>
  </div>

  <div class="mt-6 flex flex-wrap gap-3 justify-center">
    <a href="<?= url('/account/bookings') ?>" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 text-white rounded-xl font-semibold">
      <i data-lucide="calendar-check" class="w-4 h-4"></i> ดูการจองของฉัน
    </a>
    <a href="<?= url('/property/' . $booking['slug']) ?>" class="inline-flex items-center gap-2 px-5 py-2.5 border border-slate-300 hover:bg-slate-50 rounded-xl font-semibold">
      <i data-lucide="hotel" class="w-4 h-4"></i> กลับไปดูที่พัก
    </a>
    <button onclick="window.print()" class="inline-flex items-center gap-2 px-5 py-2.5 border border-slate-300 hover:bg-slate-50 rounded-xl font-semibold">
      <i data-lucide="printer" class="w-4 h-4"></i> พิมพ์
    </button>
  </div>
</section>
