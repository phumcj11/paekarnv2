<?php

declare(strict_types=1);

/** @var array<int,array<string,mixed>> $videos */
/** @var string $sectionId */
/** @var string $eyebrow */
/** @var string $title */
/** @var string $subtitle */
/** @var string|null $moreUrl */
/** @var string $sectionClass */

$videos = $videos ?? [];

if ($videos === []) {
    return;
}

$sectionId    = (string)($sectionId ?? 'review-videos-grid');
$eyebrow      = (string)($eyebrow ?? 'YouTube');
$title        = (string)($title ?? 'วิดีโอรีวิว');
$subtitle     = (string)($subtitle ?? '');
$moreUrl      = $moreUrl ?? null;
$sectionClass = (string)($sectionClass ?? '');
?>
<section id="<?= e($sectionId) ?>" class="<?= e(trim($sectionClass)) ?> scroll-mt-28 md:scroll-mt-36">
  <div class="max-w-7xl mx-auto px-4 sm:px-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
      <div>
        <span class="text-xs font-semibold text-accent-600 uppercase tracking-wider"><?= e($eyebrow) ?></span>
        <h2 class="text-2xl md:text-3xl font-bold text-ink mt-1"><?= e($title) ?></h2>
        <?php if ($subtitle !== ''): ?>
          <p class="text-sm text-slate-600 mt-1 max-w-xl"><?= e($subtitle) ?></p>
        <?php endif; ?>
      </div>
      <?php if ($moreUrl): ?>
        <a href="<?= e($moreUrl) ?>" class="inline-flex items-center gap-1 text-sm font-semibold text-primary-700 hover:text-accent-600 shrink-0">
          ดูทั้งหมด <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </a>
      <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($videos as $v): ?>
        <?php \App\Core\View::partial('partials/review_video_card', ['v' => $v, 'with_anchor' => !empty($with_anchor)]); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
