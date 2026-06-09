<?php
/** @var string $id @var string $title @var array<int,array<string,mixed>> $properties @var array<int,array<string,mixed>> $zone_ads @var string $more_url */
?>
<section id="<?= e($id) ?>" class="home-zone-raft-accom max-w-7xl mx-auto px-4 sm:px-6 mt-14 scroll-mt-28 md:scroll-mt-36">
  <div class="flex items-end justify-between mb-5 gap-4">
    <div>
      <span class="text-xs font-bold text-forest-700 uppercase tracking-wider">แพตามโซน</span>
      <h2 class="text-2xl md:text-3xl font-extrabold text-ink tracking-tight mt-1"><?= e($title) ?></h2>
    </div>
    <a href="<?= e($more_url) ?>" class="hidden md:inline-flex items-center gap-1 text-sm font-bold text-forest-800 hover:text-forest-600 transition shrink-0">
      ดูทั้งหมด <i data-lucide="chevron-right" class="w-4 h-4"></i>
    </a>
  </div>
  <?php if (!empty($zone_ads)): ?>
  <div class="mb-5 rounded-xl border border-dashed border-amber-200/80 bg-amber-50/40 px-3 py-3">
    <p class="text-[10px] font-semibold uppercase tracking-wider text-amber-900/70 mb-2">โปรโมชัน &amp; โปรในโซน</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <?php foreach ($zone_ads as $zad):
        $wrapOpen = !empty($zad['link_resolved']);
      ?>
      <?php if ($wrapOpen): ?><a href="<?= e($zad['link_resolved']) ?>" target="_blank" rel="noopener noreferrer" class="block rounded-xl overflow-hidden border border-slate-200 bg-white hover:shadow-md hover:border-accent-300 transition"><?php else: ?><div class="rounded-xl overflow-hidden border border-slate-200 bg-white"><?php endif; ?>
        <?php if (!empty($zad['image_url'])): ?>
          <img src="<?= e($zad['image_url']) ?>" alt="<?= e((string)($zad['title'] ?? '')) ?>" class="w-full aspect-[21/9] object-cover bg-slate-100" loading="lazy">
        <?php endif; ?>
        <?php if (!empty($zad['title'])): ?>
          <div class="px-3 py-2 text-xs font-medium text-slate-700 truncate"><?= e((string)$zad['title']) ?></div>
        <?php elseif (empty($zad['image_url'])): ?>
          <div class="px-3 py-4 text-xs text-slate-500 text-center">โฆษณาโซน</div>
        <?php endif; ?>
      <?php if ($wrapOpen): ?></a><?php else: ?></div><?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
  <div class="md:hidden">
    <?php \App\Core\View::partial('partials/property-horizontal-mobile-stack', [
      'properties' => $properties,
      'wrapperClass' => 'max-w-2xl mx-auto w-full mb-4',
      'showTabs' => false,
    ]); ?>
  </div>
  <div class="hidden md:grid md:grid-cols-2 lg:grid-cols-4 gap-5">
    <?php foreach ($properties as $property): \App\Core\View::partial('partials/property-card', ['property' => $property]); endforeach; ?>
  </div>
  <div class="text-center mt-2 md:hidden">
    <a href="<?= e($more_url) ?>" class="inline-flex items-center gap-1.5 text-sm font-bold text-forest-800 hover:text-forest-600">
      ดูทั้งหมด <i data-lucide="chevron-right" class="w-4 h-4"></i>
    </a>
  </div>
</section>
