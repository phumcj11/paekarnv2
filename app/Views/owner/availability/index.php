<?php
/** @var array $property @var array $units @var int $unitId @var int $month @var int $year
 *  @var array $dayMeta @var array $bookingsByDate @var int $daysInMonth @var int $startWeekday */
$pid = (int)$property['id'];
?>

<div class="flex items-center justify-between mb-4 gap-2">
  <a href="<?= url('/owner/properties/' . $pid . '/line') ?>" class="text-sm text-slate-500 hover:text-core-600 inline-flex items-center gap-1 shrink-0">
    <i data-lucide="arrow-left" class="w-4 h-4"></i> ตั้งค่า LINE
  </a>
  <h2 class="font-bold text-sm sm:text-base flex items-center gap-2 min-w-0 truncate">
    <i data-lucide="calendar" class="w-5 h-5 text-core-600 shrink-0"></i>
    <span class="truncate"><?= e($property['name']) ?></span>
  </h2>
  <a href="<?= url('/owner/properties/' . $pid . '/edit') ?>" class="text-xs text-slate-500 hover:text-core-600 shrink-0">แก้ไข</a>
</div>

<?php if (empty($units)): ?>
<div class="ow-card p-12 text-center max-w-lg mx-auto">
  <i data-lucide="bed-double" class="w-12 h-12 mx-auto text-slate-400"></i>
  <h3 class="mt-3 font-semibold">ยังไม่มีห้องพัก</h3>
  <p class="text-sm text-slate-500 mt-1">เพิ่มยูนิตก่อนจึงจะจัดการปฏิทินได้</p>
  <a href="<?= url('/owner/properties/' . $pid . '/units/create') ?>" class="ow-btn-primary mt-4 inline-flex">เพิ่มห้องพัก</a>
</div>
<?php else: ?>
<?php require __DIR__ . '/../partials/availability_manage.php'; ?>
<?php endif; ?>
