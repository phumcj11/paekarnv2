<?php
/** @var array<string,mixed> $activity */
use App\Models\ActivityProduct;

$href = url('/activities/' . $activity['slug']);
$img = ActivityProduct::coverImageUrl($activity);
$cat = ActivityProduct::CATEGORIES[$activity['category']] ?? $activity['category'];
$price = (float)($activity['base_price'] ?? 0);
$compare = (float)($activity['compare_at_price'] ?? 0);
?>
<a href="<?= e($href) ?>" class="group bg-white rounded-2xl overflow-hidden border border-slate-200/90 shadow-[0_12px_36px_-22px_rgba(15,23,42,0.28)] hover:shadow-soft transition flex flex-col h-full">
  <div class="relative aspect-[16/10] bg-slate-100 overflow-hidden">
    <img src="<?= e($img) ?>" alt="<?= e($activity['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">
    <?php if (!empty($activity['is_featured'])): ?>
      <span class="absolute top-3 left-3 rounded-lg bg-amber-500 text-white text-[11px] font-bold px-2.5 py-1 shadow">แนะนำ</span>
    <?php endif; ?>
    <span class="absolute bottom-3 left-3 rounded-lg bg-white/95 text-slate-700 text-[11px] font-bold px-2.5 py-1 shadow"><?= e($cat) ?></span>
  </div>
  <div class="p-4 flex-1 flex flex-col">
    <h3 class="font-bold text-ink leading-snug group-hover:text-primary-700 line-clamp-2"><?= e($activity['title']) ?></h3>
    <div class="mt-2 text-xs text-slate-500 flex flex-wrap gap-x-3 gap-y-1">
      <?php if (!empty($activity['district'])): ?><span class="inline-flex items-center gap-1"><i data-lucide="map" class="w-3.5 h-3.5"></i><?= e($activity['district']) ?></span><?php endif; ?>
      <?php if (!empty($activity['duration_label'])): ?><span class="inline-flex items-center gap-1"><i data-lucide="clock" class="w-3.5 h-3.5"></i><?= e($activity['duration_label']) ?></span><?php endif; ?>
    </div>
    <?php if (!empty($activity['excerpt'])): ?><p class="mt-2 text-sm text-slate-600 line-clamp-2"><?= e($activity['excerpt']) ?></p><?php endif; ?>
    <div class="mt-auto pt-4 flex items-end justify-between gap-2 border-t border-slate-100">
      <div>
        <div class="text-[10px] uppercase tracking-wide text-slate-500 font-semibold">เริ่มต้น</div>
        <div class="text-lg font-extrabold text-forest-900"><?= $price > 0 ? format_money($price) : 'สอบถามราคา' ?></div>
        <?php if ($compare > $price && $price > 0): ?><div class="text-xs text-slate-400 line-through"><?= format_money($compare) ?></div><?php endif; ?>
      </div>
      <span class="text-sm font-semibold text-primary-700 inline-flex items-center gap-1">รายละเอียด <i data-lucide="arrow-right" class="w-4 h-4"></i></span>
    </div>
  </div>
</a>
