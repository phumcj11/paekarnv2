<?php
/**
 * Trust signals shown below sidebar summary or below form.
 */
$lineUrl = \App\Models\Setting::get('line_oa', '');
?>
<div class="mt-4 bg-white border border-slate-200 rounded-2xl p-4 text-sm">
  <div class="flex items-start gap-2 py-1.5">
    <i data-lucide="shield-check" class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5"></i>
    <div>
      <div class="font-semibold text-slate-800">ชำระเงินปลอดภัย</div>
      <div class="text-xs text-slate-500">เชื่อมต่อผ่าน SSL ทีมงานตรวจสอบสลิปด้วยมนุษย์</div>
    </div>
  </div>
  <div class="flex items-start gap-2 py-1.5">
    <i data-lucide="clock-4" class="w-5 h-5 text-primary-600 flex-shrink-0 mt-0.5"></i>
    <div>
      <div class="font-semibold text-slate-800">ตรวจสอบภายใน 24 ชม.</div>
      <div class="text-xs text-slate-500">หากเกินเวลา ทีมงานจะติดต่อกลับทันที</div>
    </div>
  </div>
  <div class="flex items-start gap-2 py-1.5">
    <i data-lucide="badge-check" class="w-5 h-5 text-accent-600 flex-shrink-0 mt-0.5"></i>
    <div>
      <div class="font-semibold text-slate-800">คืนเงิน 100%</div>
      <div class="text-xs text-slate-500">หากตรวจสลิปไม่ผ่าน คืนเงินเต็มจำนวน</div>
    </div>
  </div>
  <?php if ($lineUrl !== ''): ?>
  <a href="<?= e($lineUrl) ?>" target="_blank" rel="noopener"
     class="mt-2 flex items-center justify-center gap-2 w-full px-3 py-2 rounded-lg border border-emerald-200 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 text-sm font-semibold">
    <i data-lucide="message-circle" class="w-4 h-4"></i> ติดต่อทีมงาน LINE
  </a>
  <?php endif; ?>
</div>
