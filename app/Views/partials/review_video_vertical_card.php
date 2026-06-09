<?php

declare(strict_types=1);

/** @var array<string,mixed> $v */
/** @var bool $with_anchor */

use App\Models\ReviewVideo;

$with_anchor = !empty($with_anchor);
$platform    = ReviewVideo::platformOf($v);
$externalId  = ReviewVideo::externalIdOf($v);
$sourceUrl   = ReviewVideo::sourceUrlOf($v);
$thumbUrl    = ReviewVideo::thumbnailUrlFor($v);
$catLabel    = ReviewVideo::CATEGORIES[$v['category'] ?? 'general'] ?? ($v['category'] ?? '');
$embedUrl    = $platform === 'youtube' ? ReviewVideo::embedUrl($externalId) : '';
$platformLabel = ReviewVideo::platformLabel($v);

$platformStyles = [
    'youtube'   => 'from-red-600/80 to-red-900/90',
    'tiktok'    => 'from-slate-900 to-slate-700',
    'instagram' => 'from-purple-600/80 via-pink-600/70 to-orange-500/80',
];
$gradClass = $platformStyles[$platform] ?? 'from-slate-700 to-slate-900';
?>
<article <?= $with_anchor ? 'id="video-' . (int)$v['id'] . '"' : '' ?>
         class="review-video-card review-video-card--portrait snap-start flex flex-col"
         style="width:280px;flex-shrink:0"
         x-data="{
           play: false,
           loadSocialEmbed() {
             if ('<?= e($platform) ?>' === 'tiktok' && !window.__tiktokEmbedLoaded) {
               const s = document.createElement('script');
               s.src = 'https://www.tiktok.com/embed.js';
               s.async = true;
               document.body.appendChild(s);
               window.__tiktokEmbedLoaded = true;
             }
             if ('<?= e($platform) ?>' === 'instagram' && !window.__igEmbedLoaded) {
               const s = document.createElement('script');
               s.src = 'https://www.instagram.com/embed.js';
               s.async = true;
               document.body.appendChild(s);
               window.__igEmbedLoaded = true;
             }
             if ('<?= e($platform) ?>' === 'instagram' && window.instgrm && window.instgrm.Embeds) {
               window.instgrm.Embeds.process();
             }
           }
         }">
  <div class="review-video-card__media rounded-2xl overflow-hidden border border-slate-200 shadow-soft bg-slate-900" style="aspect-ratio:9/16">
    <button type="button"
            x-show="!play"
            @click="play = true; $nextTick(() => loadSocialEmbed())"
            class="absolute inset-0 z-10 flex flex-col items-center justify-center group focus:outline-none focus-visible:ring-2 ring-accent-400 ring-inset">
      <?php if ($thumbUrl): ?>
        <img src="<?= e($thumbUrl) ?>" alt="" class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:opacity-100 transition" loading="lazy">
      <?php else: ?>
        <div class="absolute inset-0 bg-gradient-to-br <?= e($gradClass) ?>"></div>
        <span class="relative z-10 text-white/90 text-xs font-bold uppercase tracking-wider mb-2"><?= e($platformLabel) ?></span>
      <?php endif; ?>
      <span class="relative z-10 w-14 h-14 rounded-full bg-white/95 shadow-lg grid place-items-center text-primary-700 group-hover:scale-105 transition">
        <i data-lucide="play" class="w-7 h-7 fill-current ml-0.5"></i>
      </span>
      <span class="sr-only">เล่นวิดีโอ <?= e($v['title']) ?></span>
    </button>

    <?php if ($platform === 'youtube'): ?>
      <iframe x-show="play"
              x-cloak
              class="absolute inset-0 w-full h-full"
              title="<?= e($v['title']) ?>"
              x-bind:src="play ? '<?= e($embedUrl) ?>?rel=0&playsinline=1' : ''"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen></iframe>
    <?php elseif ($platform === 'tiktok'): ?>
      <div x-show="play" x-cloak class="absolute inset-0 overflow-y-auto bg-black flex items-start justify-center p-1">
        <blockquote class="tiktok-embed mx-auto" cite="<?= e($sourceUrl) ?>"<?= ctype_digit($externalId) ? ' data-video-id="' . e($externalId) . '"' : '' ?> style="max-width:605px;min-width:260px;">
          <section></section>
        </blockquote>
      </div>
    <?php else: ?>
      <div x-show="play" x-cloak class="absolute inset-0 overflow-y-auto bg-white flex items-start justify-center p-1">
        <blockquote class="instagram-media mx-auto" data-instgrm-permalink="<?= e($sourceUrl) ?>" data-instgrm-version="14" style="max-width:540px;min-width:260px;width:100%;"></blockquote>
      </div>
    <?php endif; ?>
  </div>

  <div class="pt-3 px-0.5 flex-1 flex flex-col min-h-0">
    <div class="flex items-center gap-2">
      <span class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-slate-100 text-slate-600"><?= e($platformLabel) ?></span>
      <span class="text-[10px] font-semibold text-accent-600 uppercase tracking-wide truncate"><?= e($catLabel) ?></span>
    </div>
    <h3 class="mt-1.5 font-semibold text-sm text-ink leading-snug line-clamp-2"><?= e($v['title']) ?></h3>
    <div class="mt-auto pt-2 flex flex-wrap gap-1.5">
      <?php if (!empty($v['property_slug'])): ?>
        <a href="<?= url('/property/' . $v['property_slug']) ?>" class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-1 rounded-full bg-primary-50 text-primary-800 hover:bg-primary-100">
          <i data-lucide="hotel" class="w-3 h-3"></i> ที่พัก
        </a>
      <?php endif; ?>
      <?php if ($sourceUrl !== ''): ?>
        <a href="<?= e($sourceUrl) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-1 rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200">
          เปิดต้นทาง <i data-lucide="external-link" class="w-3 h-3"></i>
        </a>
      <?php endif; ?>
    </div>
  </div>
</article>
