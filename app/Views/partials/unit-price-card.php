<?php
/** @var array $pricingUnit @var string|null $priceClass @var string|null $suffixClass @var string|null $noteClass */
use App\Support\UnitPricing;

$unit = UnitPricing::coerceUnit($pricingUnit);
$cardPrice = UnitPricing::formatCardPrice($unit);
$priceNote = UnitPricing::guestPriceNote($unit);
$priceClass = $priceClass ?? 'text-lg font-extrabold text-forest-900 tabular-nums leading-none';
$suffixClass = $suffixClass ?? 'text-[11px] font-semibold text-slate-500';
$noteClass = $noteClass ?? 'text-[10px] text-slate-500 mt-0.5 leading-snug';
?>
<?php if ($cardPrice !== ''): ?>
<div class="<?= e($priceClass) ?>"><?= $cardPrice ?>
  <span class="<?= e($suffixClass) ?>">/ คืน</span>
</div>
<?php if ($priceNote !== ''): ?>
<div class="<?= e($noteClass) ?>"><?= e($priceNote) ?></div>
<?php endif; ?>
<?php endif; ?>