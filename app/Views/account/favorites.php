<?php /** @var array $rows */ ?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 py-8 grid grid-cols-1 lg:grid-cols-12 gap-6">
  <?php \App\Core\View::partial('partials/account-nav'); ?>
  <div class="lg:col-span-9">
    <h1 class="text-2xl font-bold mb-4 flex items-center gap-2"><i data-lucide="heart" class="w-6 h-6 text-pink-600"></i> ที่พักที่บันทึก</h1>
    <?php if (empty($rows)): ?>
      <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-10 text-center">
        <i data-lucide="heart-off" class="w-12 h-12 mx-auto text-slate-400"></i>
        <h3 class="mt-3 font-semibold">ยังไม่มีที่พักที่บันทึก</h3>
        <a href="<?= url('/properties') ?>" class="mt-3 inline-block px-5 py-2.5 bg-primary-600 text-white rounded-xl">ค้นหาที่พัก</a>
      </div>
    <?php else: ?>
    <div class="md:hidden">
    <?php \App\Core\View::partial('partials/property-horizontal-mobile-stack', [
      'properties' => $rows,
      'wrapperClass' => 'w-full',
    ]); ?>
    </div>
    <div class="hidden md:grid md:grid-cols-2 xl:grid-cols-3 gap-5">
      <?php foreach ($rows as $property): \App\Core\View::partial('partials/property-card', ['property' => $property]); endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
