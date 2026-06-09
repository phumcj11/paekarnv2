<?php
/** @var string $title @var string $eyebrow @var string $moreUrl @var array<int,array<string,mixed>> $properties */
if (empty($properties)) {
    return;
}
$sectionClass = (string)($sectionClass ?? 'max-w-7xl mx-auto px-4 sm:px-6 mt-14');
?>
<section class="home-featured-type <?= e($sectionClass) ?>">
  <div class="hidden md:block relative" x-data="{
    canPrev: false,
    canNext: false,
    dragging: false,
    dragStartX: 0,
    dragScrollLeft: 0,
    featScroll(dir) {
      const el = this.$refs.featTrack;
      if (!el) return;
      const card = el.querySelector('.shrink-0');
      const step = card ? card.offsetWidth + 20 : 340;
      el.scrollBy({ left: dir * step, behavior: 'smooth' });
    },
    updateArrows() {
      const el = this.$refs.featTrack;
      if (!el) return;
      const max = el.scrollWidth - el.clientWidth;
      this.canPrev = el.scrollLeft > 4;
      this.canNext = max > 4 && el.scrollLeft < max - 4;
    },
    onDragStart(e) {
      if (e.button !== 0) return;
      if (e.target.closest('a, button')) return;
      const el = this.$refs.featTrack;
      if (!el) return;
      this.dragging = true;
      this.dragStartX = e.pageX;
      this.dragScrollLeft = el.scrollLeft;
      el.classList.add('cursor-grabbing', 'select-none');
    },
    onDragMove(e) {
      if (!this.dragging) return;
      const el = this.$refs.featTrack;
      if (!el) return;
      e.preventDefault();
      el.scrollLeft = this.dragScrollLeft - (e.pageX - this.dragStartX);
    },
    onDragEnd() {
      if (!this.dragging) return;
      this.dragging = false;
      const el = this.$refs.featTrack;
      if (el) el.classList.remove('cursor-grabbing', 'select-none');
      this.updateArrows();
    },
    init() {
      this.$nextTick(() => {
        this.updateArrows();
        const el = this.$refs.featTrack;
        if (el && typeof ResizeObserver !== 'undefined') {
          new ResizeObserver(() => this.updateArrows()).observe(el);
        }
      });
    }
  }">
    <div class="flex items-end justify-between mb-6 gap-4">
      <div>
        <span class="text-xs font-bold text-forest-700 uppercase tracking-wider"><?= e($eyebrow) ?></span>
        <h2 class="text-2xl lg:text-3xl font-extrabold text-ink tracking-tight mt-1"><?= e($title) ?></h2>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <a href="<?= e($moreUrl) ?>" class="inline-flex items-center gap-1 text-sm font-bold text-forest-800 hover:text-forest-600 transition">
          ดูทั้งหมด <i data-lucide="chevron-right" class="w-4 h-4"></i>
        </a>
        <button type="button" @click="featScroll(-1)" :disabled="!canPrev" aria-label="เลื่อนก่อนหน้า"
                class="w-11 h-11 rounded-full bg-white border border-slate-200 shadow-md text-forest-900 hover:bg-forest-50 grid place-items-center transition disabled:opacity-35 disabled:pointer-events-none">
          <i data-lucide="chevron-left" class="w-6 h-6"></i>
        </button>
        <button type="button" @click="featScroll(1)" :disabled="!canNext" aria-label="เลื่อนถัดไป"
                class="w-11 h-11 rounded-full bg-white border border-slate-200 shadow-md text-forest-900 hover:bg-forest-50 grid place-items-center transition disabled:opacity-35 disabled:pointer-events-none">
          <i data-lucide="chevron-right" class="w-6 h-6"></i>
        </button>
      </div>
    </div>
    <div class="relative group/feat">
      <div x-ref="featTrack"
           @scroll.passive="updateArrows()"
           @mousedown="onDragStart($event)"
           @mousemove.window="onDragMove($event)"
           @mouseup.window="onDragEnd()"
           @mouseleave="onDragEnd()"
           class="flex gap-5 overflow-x-auto no-scrollbar pb-3 snap-x snap-mandatory scroll-pl-1 -mr-2 pr-2 cursor-grab touch-pan-x overscroll-x-contain">
        <?php foreach ($properties as $property): \App\Core\View::partial('partials/property-card-carousel', ['property' => $property]); endforeach; ?>
      </div>
    </div>
  </div>
  <div class="md:hidden">
    <div class="text-center mb-4">
      <span class="text-xs font-semibold text-accent-600 uppercase tracking-wider"><?= e($eyebrow) ?></span>
      <h2 class="text-2xl font-bold text-ink mt-1"><?= e($title) ?></h2>
    </div>
    <?php \App\Core\View::partial('partials/property-horizontal-mobile-stack', [
      'properties' => $properties,
      'wrapperClass' => 'max-w-2xl mx-auto w-full',
      'listClass' => 'mb-4',
    ]); ?>
    <div class="text-center mt-2">
      <a href="<?= e($moreUrl) ?>" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-700 hover:text-accent-600">
        ดูทั้งหมด <i data-lucide="arrow-right" class="w-4 h-4"></i>
      </a>
    </div>
  </div>
</section>
