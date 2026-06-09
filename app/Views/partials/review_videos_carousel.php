<?php

declare(strict_types=1);

/** @var array<int,array<string,mixed>> $videos */
/** @var string $sectionId */
/** @var string $eyebrow */
/** @var string $title */
/** @var string $subtitle */
/** @var string|null $moreUrl */
/** @var string $sectionClass */

use App\Models\ReviewVideo;

if ($videos === []) {
    return;
}

$sectionId     = (string)($sectionId ?? 'review-videos-carousel');
$eyebrow       = (string)($eyebrow ?? 'Short clips');
$title         = (string)($title ?? 'คลิปรีวิวแนวตั้ง');
$subtitle      = (string)($subtitle ?? '');
$moreUrl       = $moreUrl ?? null;
$sectionClass  = (string)($sectionClass ?? '');
?>
<section id="<?= e($sectionId) ?>" class="<?= e(trim($sectionClass)) ?> scroll-mt-28 md:scroll-mt-36">
  <div class="max-w-7xl mx-auto px-4 sm:px-6">
    <div class="relative" x-data="{
      canPrev: false,
      canNext: false,
      dragging: false,
      dragStartX: 0,
      dragScrollLeft: 0,
      scroll(dir) {
        const el = this.$refs.track;
        if (!el) return;
        const card = el.querySelector('.review-video-card');
        const step = card ? card.offsetWidth + 16 : 296;
        el.scrollBy({ left: dir * step, behavior: 'smooth' });
      },
      updateArrows() {
        const el = this.$refs.track;
        if (!el) return;
        const max = el.scrollWidth - el.clientWidth;
        this.canPrev = el.scrollLeft > 4;
        this.canNext = max > 4 && el.scrollLeft < max - 4;
      },
      onDragStart(e) {
        if (e.button !== 0) return;
        if (e.target.closest('a, button, iframe, blockquote')) return;
        const el = this.$refs.track;
        if (!el) return;
        this.dragging = true;
        this.dragStartX = e.pageX;
        this.dragScrollLeft = el.scrollLeft;
        el.classList.add('cursor-grabbing', 'select-none');
      },
      onDragMove(e) {
        if (!this.dragging) return;
        const el = this.$refs.track;
        if (!el) return;
        e.preventDefault();
        el.scrollLeft = this.dragScrollLeft - (e.pageX - this.dragStartX);
      },
      onDragEnd() {
        if (!this.dragging) return;
        this.dragging = false;
        const el = this.$refs.track;
        if (el) el.classList.remove('cursor-grabbing', 'select-none');
        this.updateArrows();
      },
      init() {
        this.$nextTick(() => {
          this.updateArrows();
          const el = this.$refs.track;
          if (el && typeof ResizeObserver !== 'undefined') {
            new ResizeObserver(() => this.updateArrows()).observe(el);
          }
        });
      }
    }">
      <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div>
          <span class="text-xs font-semibold text-accent-600 uppercase tracking-wider"><?= e($eyebrow) ?></span>
          <h2 class="text-2xl md:text-3xl font-bold text-ink mt-1"><?= e($title) ?></h2>
          <?php if ($subtitle !== ''): ?>
            <p class="text-sm text-slate-600 mt-1 max-w-xl"><?= e($subtitle) ?></p>
          <?php endif; ?>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <?php if ($moreUrl): ?>
            <a href="<?= e($moreUrl) ?>" class="inline-flex items-center gap-1 text-sm font-semibold text-primary-700 hover:text-accent-600 mr-2">
              ดูทั้งหมด <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
          <?php endif; ?>
          <button type="button" @click="scroll(-1)" :disabled="!canPrev" aria-label="เลื่อนก่อนหน้า"
                  class="w-10 h-10 rounded-full bg-white border border-slate-200 shadow-md grid place-items-center disabled:opacity-35 disabled:pointer-events-none hover:bg-slate-50">
            <i data-lucide="chevron-left" class="w-5 h-5"></i>
          </button>
          <button type="button" @click="scroll(1)" :disabled="!canNext" aria-label="เลื่อนถัดไป"
                  class="w-10 h-10 rounded-full bg-white border border-slate-200 shadow-md grid place-items-center disabled:opacity-35 disabled:pointer-events-none hover:bg-slate-50">
            <i data-lucide="chevron-right" class="w-5 h-5"></i>
          </button>
        </div>
      </div>

      <div x-ref="track"
           @scroll.passive="updateArrows()"
           @mousedown="onDragStart($event)"
           @mousemove.window="onDragMove($event)"
           @mouseup.window="onDragEnd()"
           @mouseleave="onDragEnd()"
           class="flex gap-4 overflow-x-auto no-scrollbar pb-2 snap-x snap-mandatory scroll-pl-1 cursor-grab touch-pan-x overscroll-x-contain -mr-2 pr-2">
        <?php foreach ($videos as $v): ?>
          <?php \App\Core\View::partial('partials/review_video_vertical_card', ['v' => $v, 'with_anchor' => !empty($with_anchor)]); ?>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
