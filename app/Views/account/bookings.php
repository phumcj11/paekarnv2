<?php /** @var array $rows */
$statusColors = [
  'pending'=>'bg-amber-100 text-amber-700','confirmed'=>'bg-emerald-100 text-emerald-700',
  'rejected'=>'bg-rose-100 text-rose-700','cancelled'=>'bg-slate-100 text-slate-700',
  'completed'=>'bg-blue-100 text-blue-700','no_show'=>'bg-slate-200 text-slate-700',
];
$statusLabels = [
  'pending'=>'รอยืนยัน','confirmed'=>'ยืนยันแล้ว','rejected'=>'ปฏิเสธ',
  'cancelled'=>'ยกเลิก','completed'=>'เสร็จสิ้น','no_show'=>'ไม่มา',
];
?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-8 grid grid-cols-1 lg:grid-cols-12 gap-6">
  <?php \App\Core\View::partial('partials/account-nav'); ?>
  <div class="lg:col-span-9">
    <h1 class="text-2xl font-bold mb-4 flex items-center gap-2"><i data-lucide="calendar-check" class="w-6 h-6 text-accent-600"></i> การจองของฉัน</h1>
    <?php if (empty($rows)): ?>
      <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-10 text-center">
        <i data-lucide="calendar-x" class="w-12 h-12 mx-auto text-slate-400"></i>
        <h3 class="mt-3 font-semibold text-lg">ยังไม่มีการจอง</h3>
        <a href="<?= url('/properties') ?>" class="mt-3 inline-block px-5 py-2.5 bg-primary-600 text-white rounded-xl">ค้นหาที่พัก</a>
      </div>
    <?php else: ?>
      <div class="space-y-3">
      <?php foreach ($rows as $b): ?>
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col md:flex-row">
          <img src="<?= e(upload_url($b['cover_image'])) ?>" class="md:w-48 h-44 md:h-auto object-cover">
          <div class="p-4 md:p-5 flex-1">
            <div class="flex items-center justify-between gap-2 flex-wrap">
              <div class="font-bold text-lg"><?= e($b['property_name']) ?></div>
              <span class="text-xs px-2 py-1 rounded-full <?= $statusColors[$b['status']] ?? 'bg-slate-100' ?>"><?= e($statusLabels[$b['status']] ?? $b['status']) ?></span>
            </div>
            <div class="text-sm text-slate-500"><?= e($b['unit_name']) ?> · <?= e($b['code']) ?></div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3 text-sm">
              <div><div class="text-xs text-slate-500">เช็คอิน</div><div class="font-medium"><?= format_date_th($b['check_in']) ?></div></div>
              <div><div class="text-xs text-slate-500">เช็คเอาท์</div><div class="font-medium"><?= format_date_th($b['check_out']) ?></div></div>
              <div><div class="text-xs text-slate-500">จำนวนคืน</div><div class="font-medium"><?= $b['nights'] ?></div></div>
              <div><div class="text-xs text-slate-500">รวม</div><div class="font-bold text-primary-700"><?= format_money($b['total_price']) ?></div></div>
            </div>
            <div class="mt-3 flex gap-2">
              <a href="<?= url('/property/' . $b['property_slug']) ?>" class="px-3 py-1.5 border border-slate-300 hover:bg-slate-50 rounded-lg text-sm inline-flex items-center gap-1.5"><i data-lucide="hotel" class="w-3.5 h-3.5"></i> ดูที่พัก</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
