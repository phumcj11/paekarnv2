<?php
/** @var array $booking  @var string $print_url */
use App\Services\BookingConfirmationService;
?>
<style>
@media print {
  .no-print { display:none!important; }
  body { background:#fff; }
}
</style>

<section class="max-w-2xl mx-auto px-4 sm:px-6 py-8">
  <!-- Print / share bar -->
  <div class="no-print flex items-center justify-between mb-5">
    <div class="text-sm text-slate-500">ใบยืนยันการจอง</div>
    <button onclick="window.print()"
            class="inline-flex items-center gap-2 px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white text-sm font-semibold rounded-xl transition">
      <i data-lucide="printer" class="w-4 h-4"></i> พิมพ์ / บันทึก PDF
    </button>
  </div>

  <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-soft print:shadow-none print:border-0">
    <!-- Header -->
    <div class="bg-primary-700 text-white px-6 py-5 print:px-4">
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="text-xs uppercase tracking-widest opacity-70 mb-1">ใบยืนยันการจอง</div>
          <div class="text-2xl font-bold"><?= e($booking['property_name']) ?></div>
          <?php if (!empty($booking['unit_name'])): ?>
          <div class="text-sm opacity-80 mt-0.5"><?= e($booking['unit_name']) ?></div>
          <?php endif; ?>
        </div>
        <div class="text-right shrink-0">
          <div class="text-xs opacity-70 mb-1">รหัสจอง</div>
          <div class="text-lg font-mono font-bold tracking-wider"><?= e($booking['code']) ?></div>
          <div class="mt-1.5">
            <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold
              <?= $booking['status'] === 'confirmed' ? 'bg-emerald-400 text-emerald-900' : 'bg-amber-400 text-amber-900' ?>">
              <?= $booking['status'] === 'confirmed' ? 'ยืนยันแล้ว' : e($booking['status']) ?>
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Details grid -->
    <div class="p-6 print:p-4 space-y-5">
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
        <div>
          <div class="text-xs text-slate-500 mb-0.5">เช็คอิน</div>
          <div class="font-semibold"><?= format_date_th($booking['check_in']) ?></div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-0.5">เช็คเอาท์</div>
          <div class="font-semibold"><?= format_date_th($booking['check_out']) ?></div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-0.5">จำนวนคืน</div>
          <div class="font-semibold"><?= (int)$booking['nights'] ?> คืน</div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-0.5">ผู้เข้าพัก</div>
          <div class="font-semibold"><?= (int)$booking['guest_count'] ?> คน</div>
        </div>
      </div>

      <div class="border-t border-slate-100 pt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
        <div>
          <div class="text-xs text-slate-500 mb-0.5">ชื่อผู้จอง</div>
          <div class="font-semibold"><?= e($booking['guest_name']) ?></div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-0.5">เบอร์ติดต่อ</div>
          <div class="font-semibold"><?= e($booking['guest_phone']) ?></div>
        </div>
        <?php if (!empty($booking['guest_email'])): ?>
        <div>
          <div class="text-xs text-slate-500 mb-0.5">อีเมล</div>
          <div class="font-semibold"><?= e($booking['guest_email']) ?></div>
        </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($booking['notes'])): ?>
      <div class="border-t border-slate-100 pt-4 text-sm">
        <div class="text-xs text-slate-500 mb-0.5">หมายเหตุ</div>
        <div class="text-slate-700"><?= nl2br(e($booking['notes'])) ?></div>
      </div>
      <?php endif; ?>

      <!-- ราคา -->
      <div class="border-t border-slate-100 pt-4 space-y-1 text-sm">
        <div class="flex justify-between">
          <span class="text-slate-600">ค่าที่พัก (<?= (int)$booking['nights'] ?> คืน)</span>
          <span><?= number_format((float)$booking['subtotal'], 0) ?> บาท</span>
        </div>
        <?php if ((float)($booking['discount'] ?? 0) > 0): ?>
        <div class="flex justify-between text-emerald-600">
          <span>ส่วนลด</span>
          <span>-<?= number_format((float)$booking['discount'], 0) ?> บาท</span>
        </div>
        <?php endif; ?>
        <div class="flex justify-between font-bold text-base pt-1 border-t border-slate-200 mt-1">
          <span>รวมทั้งสิ้น</span>
          <span class="text-accent-700">฿<?= number_format((float)$booking['total_price'], 0) ?></span>
        </div>
      </div>

      <?php if (!empty($booking['property_phone'])): ?>
      <div class="border-t border-slate-100 pt-4 text-sm text-slate-600">
        <i data-lucide="phone" class="w-3.5 h-3.5 inline mr-1 text-slate-400"></i>
        ติดต่อที่พัก: <strong><?= e($booking['property_phone']) ?></strong>
      </div>
      <?php endif; ?>
    </div>

    <!-- Footer note -->
    <div class="bg-slate-50 border-t border-slate-100 px-6 py-3 text-xs text-slate-500 print:px-4">
      เอกสารนี้ออกโดยระบบ Paekarn.com — รหัสจอง <?= e($booking['code']) ?>
      — สร้างเมื่อ <?= date('d/m/Y H:i', strtotime($booking['created_at'])) ?>
    </div>
  </div>
</section>
