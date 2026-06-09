<?php
/** @var array $items rows from banners table */
/** @var string|null $sectionClass outer section Tailwind classes */
/** @var string|null $gridClass inner grid Tailwind classes */
if (empty($items)) {
    return;
}
$sectionClass = $sectionClass ?? 'max-w-7xl mx-auto px-4 sm:px-6 mt-10 md:mt-12';
$gridClass = $gridClass ?? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-5';
$banner_href = static function (?string $link): string {
    if (!$link) {
        return '';
    }
    return preg_match('#^https?://#i', $link) ? $link : url($link);
};
?>
<section class="<?= e($sectionClass) ?>">
  <div class="<?= e($gridClass) ?>">
    <?php foreach ($items as $b):
      $href = $banner_href($b['link_url'] ?? null);
      $tag = $href ? 'a' : 'div';
      $cls = 'group relative overflow-hidden rounded-2xl bg-slate-100 shadow-soft border border-slate-200/80 aspect-[21/9] md:aspect-[2.2/1] block focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-400';
      ?>
      <<?= $tag ?> <?= $href ? 'href="' . e($href) . '"' : '' ?> class="<?= $cls ?>">
        <img src="<?= e(upload_img($b['image_path'], 'md')) ?>" alt="<?= e($b['title']) ?>" class="absolute inset-0 w-full h-full object-cover transition duration-700 group-hover:scale-[1.03]" loading="lazy">
        <div class="absolute inset-0 bg-gradient-to-t from-primary-950/80 via-primary-900/25 to-transparent pointer-events-none"></div>
        <?php if (!empty($b['title']) || !empty($b['subtitle'])): ?>
          <div class="absolute inset-x-0 bottom-0 p-4 md:p-5 text-white pointer-events-none">
            <?php if (!empty($b['title'])): ?>
              <div class="font-bold text-base md:text-lg drop-shadow"><?= e($b['title']) ?></div>
            <?php endif; ?>
            <?php if (!empty($b['subtitle'])): ?>
              <div class="text-xs md:text-sm text-white/90 mt-0.5 line-clamp-2"><?= e($b['subtitle']) ?></div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </<?= $tag ?>>
    <?php endforeach; ?>
  </div>
</section>
