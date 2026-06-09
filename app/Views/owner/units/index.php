<?php /** @var array $property @var array $units @var string|null $units_path_prefix @var string|null $property_edit_url */
$pfx = $units_path_prefix ?? '/owner/properties';
$propEdit = $property_edit_url ?? url($pfx . '/' . $property['id'] . '/edit');
$isAdminUnits = strpos($pfx, '/admin') === 0;
$statusLabels = ['pending' => 'รออนุมัติ', 'published' => 'เผยแพร่แล้ว', 'rejected' => 'ถูกปฏิเสธ'];
$statusClasses = [
  'pending' => 'bg-amber-100 text-amber-700',
  'published' => 'bg-emerald-100 text-emerald-700',
  'rejected' => 'bg-rose-100 text-rose-700',
];
$statusIcons = ['pending' => 'clock', 'published' => 'circle-check', 'rejected' => 'circle-x'];
?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
  <a href="<?= e($propEdit) ?>" class="text-sm text-slate-500 hover:text-accent-700 inline-flex items-center gap-1"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับที่พัก</a>
  <a href="<?= url($pfx . '/' . $property['id'] . '/units/create') ?>" class="w-full sm:w-auto px-4 py-3 sm:py-2 bg-accent-500 text-white rounded-xl sm:rounded-lg text-base sm:text-sm font-semibold inline-flex items-center justify-center gap-1.5 shadow-soft sm:shadow-none"><i data-lucide="plus-circle" class="w-5 h-5 sm:w-4 sm:h-4"></i> เพิ่มห้อง / ยูนิต</a>
</div>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 mb-4 flex items-center gap-4">
  <img src="<?= e(upload_url($property['cover_image']) ?: 'https://placehold.co/200x150') ?>" class="w-20 h-20 rounded-xl object-cover" alt="">
  <div class="min-w-0">
    <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="hotel" class="w-5 h-5 text-accent-600 shrink-0"></i><span class="truncate"><?= e($property['name']) ?></span></h2>
    <p class="text-sm text-slate-500 mt-0.5 inline-flex items-center gap-1 flex-wrap"><i data-lucide="map-pin" class="w-3.5 h-3.5 shrink-0"></i><?= e($property['zone']) ?> · <span class="inline-flex items-center gap-0.5"><i data-lucide="bed-double" class="w-3.5 h-3.5"></i><?= count($units) ?> ห้อง</span></p>
  </div>
</div>

<?php if (empty($units)): ?>
  <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
    <i data-lucide="bed-double" class="w-12 h-12 mx-auto text-slate-400"></i>
    <h3 class="mt-3 font-semibold">ยังไม่มีห้องพัก</h3>
    <p class="text-sm text-slate-500 mt-1">เพิ่มห้องพักหลังแรกเพื่อเริ่มรับการจอง</p>
    <a href="<?= url($pfx . '/' . $property['id'] . '/units/create') ?>" class="mt-4 inline-flex items-center gap-1.5 px-5 py-2.5 bg-accent-500 text-white rounded-xl font-semibold"><i data-lucide="plus-circle" class="w-4 h-4"></i> เพิ่มห้องพัก</a>
  </div>
<?php else: ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <?php foreach ($units as $u): ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden flex">
    <div class="w-32 h-auto bg-slate-100 shrink-0">
      <img src="<?= e(upload_url($u['cover_image']) ?: upload_url($property['cover_image']) ?: 'https://placehold.co/200x300') ?>" class="w-full h-full object-cover">
    </div>
    <div class="flex-1 p-4 flex flex-col">
      <div class="flex items-start justify-between gap-2">
        <div>
          <h3 class="font-bold"><?= e($u['name']) ?></h3>
          <div class="text-xs text-slate-500 mt-0.5">
            <?= $u['capacity_min'] ?>-<?= $u['capacity_max'] ?> คน · <?= $u['bedrooms'] ?> นอน · <?= $u['bathrooms'] ?> น้ำ
          </div>
        </div>
        <?php $moderation = (string)($u['moderation_status'] ?? 'pending'); ?>
        <span class="text-[10px] px-2 py-0.5 rounded-full inline-flex items-center gap-0.5 <?= e($statusClasses[$moderation] ?? 'bg-slate-100 text-slate-600') ?>"><i data-lucide="<?= e($statusIcons[$moderation] ?? 'circle-help') ?>" class="w-3 h-3"></i><?= e($statusLabels[$moderation] ?? $moderation) ?></span>
      </div>
      <div class="mt-2">
        <?php if ($u['is_active']): ?>
          <span class="text-[10px] px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full inline-flex items-center gap-0.5"><i data-lucide="circle-check" class="w-3 h-3"></i>เปิด</span>
        <?php else: ?>
          <span class="text-[10px] px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full inline-flex items-center gap-0.5"><i data-lucide="circle-off" class="w-3 h-3"></i>ปิด</span>
        <?php endif; ?>
      </div>
      <div class="grid grid-cols-3 gap-1 mt-2 text-center">
        <div class="bg-slate-50 rounded p-1.5"><div class="text-[10px] text-slate-500">ราคา</div><div class="text-xs font-bold"><?= number_format($u['price']) ?></div></div>
        <div class="bg-slate-50 rounded p-1.5"><div class="text-[10px] text-slate-500">เสาร์อา</div><div class="text-xs font-bold"><?= number_format($u['price_weekend']) ?></div></div>
        <div class="bg-slate-50 rounded p-1.5"><div class="text-[10px] text-slate-500">นักขัตฤกษ์</div><div class="text-xs font-bold"><?= number_format($u['price_holiday']) ?></div></div>
      </div>
      <div class="mt-auto pt-3 flex items-center justify-between">
        <div class="text-xs text-slate-500"><?= $u['booking_count'] ?? 0 ?> bookings</div>
        <div class="flex gap-1">
          <?php if ($isAdminUnits && $moderation !== 'published'): ?>
          <form method="post" action="<?= url($pfx . '/' . $property['id'] . '/units/' . $u['id'] . '/approve') ?>" class="inline">
            <?= csrf() ?>
            <button class="px-3 py-1.5 text-xs bg-emerald-500 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="check" class="w-3.5 h-3.5"></i> อนุมัติ</button>
          </form>
          <?php endif; ?>
          <?php if ($isAdminUnits && $moderation !== 'rejected'): ?>
          <form method="post" action="<?= url($pfx . '/' . $property['id'] . '/units/' . $u['id'] . '/reject') ?>" class="inline">
            <?= csrf() ?>
            <button class="px-3 py-1.5 text-xs bg-rose-50 text-rose-600 border border-rose-200 rounded-lg inline-flex items-center gap-1"><i data-lucide="x" class="w-3.5 h-3.5"></i> ปฏิเสธ</button>
          </form>
          <?php endif; ?>
          <a href="<?= url($pfx . '/' . $property['id'] . '/units/' . $u['id'] . '/edit') ?>" class="px-3 py-1.5 text-xs bg-primary-600 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="edit" class="w-3.5 h-3.5"></i> แก้ไข</a>
          <form method="post" action="<?= url($pfx . '/' . $property['id'] . '/units/' . $u['id'] . '/delete') ?>" onsubmit="return confirm('ยืนยันลบห้องนี้?')" class="inline">
            <?= csrf() ?>
            <button class="px-3 py-1.5 text-xs bg-rose-50 text-rose-600 border border-rose-200 rounded-lg hover:bg-rose-100"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
          </form>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php endif; ?>
