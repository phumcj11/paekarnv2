<?php
/** @var array<string,mixed> $banner @var string $slot @var bool $eagerLoad */
use App\Models\Banner;

$slot = (string)($slot ?? '');
$eagerLoad = !empty($eagerLoad);
$aspect = Banner::aspectClass($slot !== '' ? $slot : 'home_b02');

$href = trim((string)($banner['link_url'] ?? ''));
if ($href !== '' && !preg_match('#^https?://#i', $href)) {
    $href = url(ltrim($href, '/'));
}
$openNewTab = !isset($banner['open_new_tab']) || (int)$banner['open_new_tab'] === 1;

$desktop = Banner::publicImageUrl((string)($banner['image_path'] ?? ''), 'md');
$mobilePath = trim((string)($banner['mobile_image_path'] ?? ''));
$mobile = $mobilePath !== '' ? Banner::publicImageUrl($mobilePath, 'md') : $desktop;
if ($desktop === '' && $mobile === '') {
    return;
}

$alt = trim((string)($banner['alt_text'] ?? ''));
if ($alt === '') {
    $alt = trim((string)($banner['title'] ?? 'โฆษณา'));
}
$anchorId = 'banner-slot-' . str_replace('_', '-', $slot);
$tag = $href !== '' ? 'a' : 'div';
$tagAttrs = $href !== '' ? ' href="' . e($href) . '"' . ($openNewTab ? ' target="_blank" rel="noopener sponsored"' : ' rel="sponsored"') : '';
?>
<div id="<?= e($anchorId) ?>" class="scroll-mt-28 md:scroll-mt-36 max-w-7xl mx-auto px-4 sm:px-6 mt-8 md:mt-10" role="complementary" aria-label="โฆษณา">
  <<?= $tag ?><?= $tagAttrs ?> class="group relative block overflow-hidden rounded-2xl border border-slate-200/80 bg-slate-100 shadow-soft <?= e($aspect) ?> w-full focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-400">
    <picture>
      <?php if ($mobile !== $desktop && $mobile !== ''): ?>
        <source media="(max-width: 767px)" srcset="<?= e($mobile) ?>">
      <?php endif; ?>
      <img src="<?= e($desktop) ?>" alt="<?= e($alt) ?>" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-[1.02]" <?= $eagerLoad ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
    </picture>
    <?php if (!empty($banner['title']) || !empty($banner['subtitle'])): ?>
      <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-primary-950/85 via-primary-900/30 to-transparent p-4 md:p-5 pointer-events-none">
        <?php if (!empty($banner['title'])): ?>
          <div class="text-sm md:text-base font-bold text-white drop-shadow"><?= e((string)$banner['title']) ?></div>
        <?php endif; ?>
        <?php if (!empty($banner['subtitle'])): ?>
          <div class="text-xs text-white/90 mt-0.5 line-clamp-2"><?= e((string)$banner['subtitle']) ?></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <span class="absolute top-2 right-2 rounded-md bg-black/50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white/95">Sponsored</span>
  </<?= $tag ?>>
</div>
