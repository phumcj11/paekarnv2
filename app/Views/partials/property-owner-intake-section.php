<?php
/** @var array $property @var bool|null $standalone */
$standalone = ($standalone ?? true);
$labels = \App\Models\Property::ownerIntakeFieldLabels();
$intake = \App\Models\Property::decodeOwnerIntake($property['owner_intake'] ?? null);
$items = [];
foreach ($labels as $k => $label) {
    if (!empty($intake[$k]) && is_string($intake[$k])) {
        $v = trim($intake[$k]);
        if ($v !== '') {
            $items[] = ['label' => $label, 'text' => $v];
        }
    }
}
if ($items === []) {
    return;
}
?>
<?php if ($standalone): ?>
<section class="bg-white border border-slate-200 rounded-2xl p-5">
  <h2 class="text-xl font-bold flex items-center gap-2 mb-4"><i data-lucide="clipboard-list" class="w-5 h-5 text-accent-600"></i> รายละเอียดเพิ่มเติม</h2>
<?php endif; ?>
  <dl class="space-y-4 text-sm <?= $standalone ? '' : 'bg-slate-50 border border-slate-100 rounded-xl p-4' ?>">
    <?php foreach ($items as $row): ?>
      <div>
        <dt class="font-semibold text-primary-800"><?= e($row['label']) ?></dt>
        <dd class="mt-1 text-slate-700 whitespace-pre-line leading-relaxed"><?= e($row['text']) ?></dd>
      </div>
    <?php endforeach; ?>
  </dl>
<?php if ($standalone): ?>
</section>
<?php endif; ?>
