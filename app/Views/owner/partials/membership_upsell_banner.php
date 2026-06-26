<?php
/**
 * แบนเนอร์ชวนสมัครสมาชิก — Owner tier ฟรี
 *
 * @var string $membershipUrl
 * @var list<array{icon:string,title:string,desc:string}> $features
 * @var string $variant  hero | compact
 * @var bool $salesOpen
 */
use App\Services\MembershipService;

$variant   = $variant ?? 'hero';
$features  = $features ?? [];
$salesOpen = $salesOpen ?? MembershipService::salesOpen();
if ($features === []) {
    return;
}
$isCompact = $variant === 'compact';
?>
<div class="ow-upsell <?= $isCompact ? 'ow-upsell--compact' : 'mb-5' ?>">
  <div class="ow-upsell__glow ow-upsell__glow--1" aria-hidden="true"></div>
  <div class="ow-upsell__glow ow-upsell__glow--2" aria-hidden="true"></div>

  <div class="relative z-[1]">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
      <div class="min-w-0">
        <span class="ow-upsell__badge">
          <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
          Starter ขึ้นไป
        </span>
        <h3 class="mt-2 text-lg sm:text-xl font-bold text-white leading-snug">
          ปลดล็อกเครื่องมือ<br class="sm:hidden">จัดการที่พักครบชุด
        </h3>
        <p class="mt-1.5 text-sm text-sky-100/90 max-w-md">
          รับจอง ดูรายได้ จัดการปฏิทิน และเครื่องมือการตลาด — ช่วยให้ที่พักของคุณขายได้มากขึ้น
        </p>
      </div>
      <?php if ($isCompact): ?>
        <?php if ($salesOpen): ?>
        <a href="<?= e($membershipUrl) ?>" class="ow-upsell__cta shrink-0">
          ดูแพ็กเกจ <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </a>
        <?php else: ?>
        <span class="ow-upsell__cta ow-upsell__cta--soon shrink-0">
          <i data-lucide="clock" class="w-4 h-4"></i> เปิดให้บริการเร็วๆนี้
        </span>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="grid grid-cols-2 gap-2 sm:gap-2.5 <?= $isCompact ? 'sm:grid-cols-4' : '' ?>">
      <?php foreach ($features as $f): ?>
      <div class="ow-upsell__feature">
        <div class="ow-upsell__feature-icon">
          <i data-lucide="<?= e($f['icon']) ?>" class="w-4 h-4"></i>
        </div>
        <div class="min-w-0 flex-1">
          <div class="text-xs font-bold text-white truncate"><?= e($f['title']) ?></div>
          <div class="text-[10px] text-sky-100/75 leading-snug mt-0.5 line-clamp-2"><?= e($f['desc']) ?></div>
        </div>
        <i data-lucide="lock" class="w-3 h-3 text-amber-300/80 shrink-0"></i>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if (!$isCompact): ?>
    <div class="mt-4 flex flex-col sm:flex-row sm:items-center gap-3">
      <?php if ($salesOpen): ?>
      <a href="<?= e($membershipUrl) ?>" class="ow-upsell__cta w-full sm:w-auto justify-center">
        <i data-lucide="award" class="w-4 h-4"></i>
        ดูแพ็กเกจและราคา
        <i data-lucide="arrow-right" class="w-4 h-4"></i>
      </a>
      <p class="text-[11px] text-sky-100/60 text-center sm:text-left">เปรียบเทียบสิทธิ์ฟรี vs Starter vs VIP ได้ในหน้าถัดไป</p>
      <?php else: ?>
      <span class="ow-upsell__cta ow-upsell__cta--soon w-full sm:w-auto justify-center">
        <i data-lucide="clock" class="w-4 h-4"></i>
        เปิดให้บริการเร็วๆนี้
      </span>
      <p class="text-[11px] text-sky-100/60 text-center sm:text-left">กำลังจัดเตรียมแพ็กเกจและราคา — สนใจติดต่อทีมงานได้ที่ <a href="<?= url('/contact') ?>" class="underline text-sky-200 hover:text-white">ติดต่อเรา</a></p>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
