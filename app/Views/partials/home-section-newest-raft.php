<?php /** @var array<int,array<string,mixed>> $properties */ ?>
<?php if (empty($properties)) { return; } ?>
<section class="home-newest-accom max-w-7xl mx-auto px-4 sm:px-6 mt-14">
  <div class="flex items-end justify-between mb-5">
    <div>
      <span class="text-xs font-semibold text-accent-600 uppercase tracking-wider">New</span>
      <h2 class="text-2xl md:text-3xl font-bold text-ink">แพใหม่ล่าสุด</h2>
    </div>
    <a href="<?= url('/rafts?sort=newest') ?>" class="hidden md:inline-flex items-center gap-1.5 text-sm font-semibold text-primary-700 hover:text-accent-600">
      ดูทั้งหมด <i data-lucide="arrow-right" class="w-4 h-4"></i>
    </a>
  </div>
  <div class="md:hidden">
    <?php \App\Core\View::partial('partials/property-horizontal-mobile-stack', [
      'properties' => $properties,
      'wrapperClass' => 'max-w-2xl mx-auto w-full mb-4',
    ]); ?>
  </div>
  <div class="hidden md:grid md:grid-cols-2 lg:grid-cols-4 gap-5">
    <?php foreach ($properties as $property): \App\Core\View::partial('partials/property-card', ['property' => $property]); endforeach; ?>
  </div>
  <div class="text-center mt-2 md:hidden">
    <a href="<?= url('/rafts?sort=newest') ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-700 hover:text-accent-600">
      ดูทั้งหมด <i data-lucide="arrow-right" class="w-4 h-4"></i>
    </a>
  </div>
</section>
