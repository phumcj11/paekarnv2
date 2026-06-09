<?php
/** @var string $label @var string $content @var string|null $hint @var string|null $example @var string|null $hintHtml */
?>
<div class="settings-field space-y-1.5">
  <span class="text-sm font-semibold text-slate-800 block"><?= e($label) ?></span>
  <?php if (!empty($hintHtml)): ?>
    <p class="text-xs text-slate-600 leading-relaxed"><?= $hintHtml ?></p>
  <?php elseif (!empty($hint)): ?>
    <p class="text-xs text-slate-600 leading-relaxed"><?= e($hint) ?></p>
  <?php endif; ?>
  <div class="settings-field-input"><?= $content ?></div>
  <?php if (!empty($example)): ?>
    <p class="text-[11px] text-slate-500 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 leading-relaxed"><?= e($example) ?></p>
  <?php endif; ?>
</div>
