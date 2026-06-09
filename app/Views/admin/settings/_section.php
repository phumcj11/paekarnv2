<?php
/** @var string $title @var string $icon @var string $content @var string|null $intro @var string $iconClass */
$iconClass = $iconClass ?? 'text-accent-600';
?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 md:p-6 space-y-4">
  <div class="border-b border-slate-100 pb-4">
    <h3 class="font-bold text-lg flex items-center gap-2 text-slate-900">
      <i data-lucide="<?= e($icon) ?>" class="w-5 h-5 <?= e($iconClass) ?>"></i>
      <?= e($title) ?>
    </h3>
    <?php if (!empty($intro)): ?>
      <p class="text-sm text-slate-600 leading-relaxed mt-2"><?= $intro ?></p>
    <?php endif; ?>
  </div>
  <?= $content ?>
</div>
