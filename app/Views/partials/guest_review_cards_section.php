<?php
/** @var array<int,array<string,mixed>> $reviews */
if (empty($reviews)) {
    return;
}
?>
<section id="reviews-home" class="bg-white border-y border-slate-100 py-14 scroll-mt-28 md:scroll-mt-36 <?= e(trim($section_extra_class ?? '')) ?>">
  <div class="max-w-7xl mx-auto px-4 sm:px-6">
    <div class="text-center mb-8">
      <span class="text-xs font-semibold text-accent-600 uppercase tracking-wider">Customer Voices</span>
      <h2 class="text-2xl md:text-3xl font-bold text-ink">รีวิวจากผู้เข้าพักจริง</h2>
      <?php if (!empty($section_subtitle ?? '')): ?>
        <p class="text-sm text-slate-600 mt-2 max-w-xl mx-auto"><?= e($section_subtitle) ?></p>
      <?php endif; ?>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ($reviews as $r): ?>
      <a href="<?= url('/property/' . $r['property_slug']) ?>"
         class="group bg-cloud rounded-2xl p-5 border border-slate-100 hover:border-accent-200 hover:shadow-soft transition flex flex-col">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-primary-100 text-primary-700 grid place-items-center font-bold"><?= mb_substr((string)$r['reviewer_name'], 0, 1) ?></div>
          <div>
            <div class="font-semibold text-sm"><?= e($r['reviewer_name']) ?></div>
            <div class="text-xs text-slate-500"><?= format_date_th($r['created_at']) ?></div>
          </div>
        </div>
        <div class="mt-3"><?= star_html((float)$r['rating']) ?></div>
        <?php if (!empty($r['title'])): ?>
          <div class="font-semibold mt-2 group-hover:text-primary-700"><?= e($r['title']) ?></div>
        <?php endif; ?>
        <p class="text-sm text-slate-600 line-clamp-3 mt-1"><?= e((string)($r['content'] ?? '')) ?></p>
        <div class="mt-auto pt-3 text-xs text-accent-600 font-semibold inline-flex items-center gap-1">
          <i data-lucide="hotel" class="w-3.5 h-3.5"></i> <?= e($r['property_name']) ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
