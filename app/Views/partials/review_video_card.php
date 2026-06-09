<?php
/** @var array<string,mixed> $v */
/** @var bool $with_anchor */
use App\Models\ReviewVideo;

$with_anchor = !empty($with_anchor);
$embedUrl    = ReviewVideo::platformOf($v) === 'youtube'
    ? ReviewVideo::embedUrl(ReviewVideo::externalIdOf($v))
    : '';
$thumbUrl    = ReviewVideo::thumbnailUrlFor($v) ?? '';
$ytId        = ReviewVideo::externalIdOf($v);
$sourceUrl   = ReviewVideo::sourceUrlOf($v);
$catLabel    = ReviewVideo::CATEGORIES[$v['category'] ?? 'general'] ?? ($v['category'] ?? '');
?>
<article <?= $with_anchor ? 'id="video-' . (int)$v['id'] . '"' : '' ?> class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-soft flex flex-col h-full">
  <div class="relative aspect-video bg-slate-900" x-data="{ play: false }">
    <button type="button"
            x-show="!play"
            @click="play = true"
            class="absolute inset-0 z-10 flex items-center justify-center group focus:outline-none focus-visible:ring-2 ring-accent-400 ring-inset">
      <img src="<?= e($thumbUrl) ?>" alt="" class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:opacity-100 transition" loading="lazy" width="480" height="360">
      <span class="relative z-10 w-14 h-14 md:w-16 md:h-16 rounded-full bg-white/95 shadow-lg grid place-items-center text-red-600 group-hover:scale-105 transition">
        <i data-lucide="play" class="w-7 h-7 md:w-8 md:h-8 fill-current ml-0.5"></i>
      </span>
      <span class="sr-only">เล่นวิดีโอ <?= e($v['title']) ?></span>
    </button>
    <iframe x-show="play"
            x-cloak
            class="absolute inset-0 w-full h-full"
            width="560"
            height="315"
            title="<?= e($v['title']) ?>"
            x-bind:src="play ? '<?= e($embedUrl) ?>?rel=0' : ''"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen></iframe>
  </div>
  <div class="p-4 md:p-5 flex-1 flex flex-col">
    <div class="text-[11px] font-semibold text-accent-600 uppercase tracking-wide"><?= e($catLabel) ?></div>
    <h3 class="mt-1 font-semibold text-lg text-ink leading-snug"><?= e($v['title']) ?></h3>
    <?php if (!empty($v['description'])): ?>
      <p class="text-sm text-slate-600 mt-2 line-clamp-3 leading-relaxed"><?= e($v['description']) ?></p>
    <?php endif; ?>
    <div class="mt-auto pt-4 flex flex-wrap gap-2">
      <?php if (!empty($v['property_slug'])): ?>
        <a href="<?= url('/property/' . $v['property_slug']) ?>" class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-full bg-primary-50 text-primary-800 hover:bg-primary-100 transition">
          <i data-lucide="hotel" class="w-3.5 h-3.5"></i> ดูที่พักที่เกี่ยวข้อง
        </a>
      <?php endif; ?>
      <a href="<?= e($sourceUrl !== '' ? $sourceUrl : 'https://www.youtube.com/watch?v=' . $ytId) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-xs font-semibold px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
        <span>เปิดต้นทาง</span>
      </a>
    </div>
  </div>
</article>
