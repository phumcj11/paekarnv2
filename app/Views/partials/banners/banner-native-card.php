<?php
/** @var array<string,mixed> $banner */
use App\Models\Banner;

$href = trim((string)($banner['link_url'] ?? ''));
if ($href !== '' && !preg_match('#^https?://#i', $href)) {
    $href = url(ltrim($href, '/'));
}
$openNewTab = !isset($banner['open_new_tab']) || (int)$banner['open_new_tab'] === 1;
$img = Banner::publicImageUrl((string)($banner['image_path'] ?? ''), 'md');
$alt = trim((string)($banner['alt_text'] ?? ''));
if ($alt === '') {
    $alt = trim((string)($banner['title'] ?? 'ผู้สนับสนุน'));
}
$tag = $href !== '' ? 'a' : 'div';
$tagAttrs = $href !== '' ? ' href="' . e($href) . '"' . ($openNewTab ? ' target="_blank" rel="noopener sponsored"' : ' rel="sponsored"') : '';
?>
<div id="banner-slot-home-b06" class="scroll-mt-28 md:scroll-mt-36">
  <<?= $tag ?><?= $tagAttrs ?> class="group flex h-full flex-col overflow-hidden rounded-2xl border-2 border-dashed border-amber-300/90 bg-white shadow-soft ring-1 ring-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400" aria-label="<?= e($alt) ?>">
    <div class="relative aspect-[4/3] bg-slate-100 overflow-hidden">
      <?php if ($img !== ''): ?>
        <img src="<?= e($img) ?>" alt="<?= e($alt) ?>" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" loading="lazy">
      <?php else: ?>
        <div class="grid h-full place-items-center text-slate-400 text-sm">ผู้สนับสนุน</div>
      <?php endif; ?>
      <span class="absolute top-2 left-2 rounded-md bg-amber-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white shadow">ผู้สนับสนุน</span>
    </div>
    <div class="flex flex-1 flex-col p-3 md:p-4">
      <?php if (!empty($banner['title'])): ?>
        <h3 class="font-bold text-sm md:text-base text-ink line-clamp-2"><?= e((string)$banner['title']) ?></h3>
      <?php endif; ?>
      <?php if (!empty($banner['subtitle'])): ?>
        <p class="mt-1 text-xs text-slate-600 line-clamp-2"><?= e((string)$banner['subtitle']) ?></p>
      <?php endif; ?>
      <?php if (!empty($banner['button_text'])): ?>
        <span class="mt-auto pt-3 inline-flex items-center gap-1 text-xs font-bold text-forest-800"><?= e((string)$banner['button_text']) ?> <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i></span>
      <?php endif; ?>
    </div>
  </<?= $tag ?>>
</div>
