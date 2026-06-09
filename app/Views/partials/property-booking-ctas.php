<?php
/**
 * @var array $property
 * @var int $unitId 0 = ใช้ unit จาก query ในหน้าจอง
 * @var string $variant sidebar|inline|modal
 * @var bool $stopPropagation สำหรับการ์ดยูนิต
 */
use App\Support\PropertyBookingCapabilities;

$property = $property ?? [];
$unitId = (int)($unitId ?? 0);
$variant = $variant ?? 'sidebar';
$stopPropagation = !empty($stopPropagation);
$propertyId = (int)($property['id'] ?? 0);
$urls = PropertyBookingCapabilities::urlsForUnit($property, $propertyId, $unitId);

$isSidebar = $variant === 'sidebar';
$clickStop = $stopPropagation ? '@click.stop' : '';
$contactBtnClass = $isSidebar
    ? 'inline-flex min-h-[3rem] w-full items-center justify-center gap-2 rounded-xl py-3.5 text-sm font-bold touch-manipulation'
    : 'inline-flex min-h-[2.75rem] items-center justify-center gap-1.5 rounded-lg px-3 py-2.5 text-sm font-semibold touch-manipulation';
$contactSpan = $isSidebar && (empty($urls['line']) || empty($urls['contact'])) ? 'col-span-2' : '';
?>

<?php if (!empty($urls['book_online'])): ?>
<a href="<?= e($urls['book_online']) ?>" <?= $clickStop ?>
   class="<?= $isSidebar
       ? 'w-full inline-flex items-center justify-center gap-2 py-3 rounded-xl font-semibold bg-accent-500 hover:bg-accent-600 text-white shadow-sm touch-manipulation min-h-[3rem]'
       : 'inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg text-sm font-semibold bg-accent-500 hover:bg-accent-600 text-white shadow-sm touch-manipulation' ?>">
  <i data-lucide="calendar-plus" class="w-4 h-4"></i> <?= $isSidebar ? 'จองที่พักนี้' : ($variant === 'modal' ? 'จองยูนิตนี้' : 'จองยูนิตนี้') ?>
</a>
<?php endif; ?>

<?php if (!empty($urls['buy_coupon'])): ?>
<a href="<?= e($urls['buy_coupon']) ?>" <?= $clickStop ?>
   class="<?= $isSidebar
       ? 'w-full inline-flex items-center justify-center gap-2 py-3 rounded-xl font-semibold bg-rose-600 hover:bg-rose-700 text-white shadow-md touch-manipulation min-h-[3rem]'
       : 'inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg text-sm font-semibold bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 touch-manipulation' ?>">
  <i data-lucide="gift" class="w-4 h-4"></i> <?= e(coupon_cta_label()) ?>
</a>
<?php endif; ?>

<?php if (!empty($urls['contact']) || !empty($urls['line'])): ?>
<div class="<?= $isSidebar ? 'grid grid-cols-2 gap-2' : 'flex flex-wrap gap-2' ?>">
  <?php if (!empty($urls['contact'])): ?>
  <a href="<?= e($urls['contact']) ?>" <?= $clickStop ?>
     class="<?= $contactBtnClass ?> <?= $contactSpan ?> border-2 border-primary-600 text-primary-700 bg-white hover:bg-primary-50 active:bg-primary-100">
    <i data-lucide="phone" class="w-5 h-5 shrink-0"></i> โทร
  </a>
  <?php endif; ?>
  <?php if (!empty($urls['line'])): ?>
  <a href="<?= e($urls['line']) ?>" <?= $clickStop ?> target="_blank" rel="noopener noreferrer"
     class="<?= $contactBtnClass ?> <?= $contactSpan ?> bg-[#06C755] hover:bg-[#05b34c] active:bg-[#049a42] text-white shadow-sm">
    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>
    Add LINE
  </a>
  <?php endif; ?>
</div>
<?php endif; ?>
