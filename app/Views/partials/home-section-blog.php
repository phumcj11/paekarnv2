<?php /** @var array<int,array<string,mixed>> $blogs */ ?>
<?php if (empty($blogs)) { return; } ?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 mt-14">
  <div class="flex items-end justify-between mb-5">
    <div>
      <span class="text-xs font-semibold text-accent-600 uppercase tracking-wider">Travel Guide</span>
      <h2 class="text-2xl md:text-3xl font-bold text-ink">บทความ & ทริปแนะนำ</h2>
    </div>
    <a href="<?= url('/blog') ?>" class="hidden sm:inline-flex items-center gap-1.5 text-sm font-semibold text-primary-700">
      อ่านทั้งหมด <i data-lucide="arrow-right" class="w-4 h-4"></i>
    </a>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
    <?php foreach ($blogs as $b): ?>
    <a href="<?= url('/blog/' . $b['slug']) ?>"
       class="group bg-white rounded-2xl overflow-hidden border border-slate-200 hover:shadow-soft transition flex flex-col">
      <div class="aspect-[16/9] bg-slate-100 overflow-hidden">
        <img src="<?= e(upload_url($b['cover_image'])) ?>" class="w-full h-full object-cover group-hover:scale-105 transition" loading="lazy" alt="">
      </div>
      <div class="p-5 flex-1 flex flex-col">
        <div class="text-xs text-accent-600 font-semibold uppercase"><?= e($b['category']) ?></div>
        <h3 class="mt-1 font-semibold text-lg group-hover:text-primary-700"><?= e($b['title']) ?></h3>
        <p class="text-sm text-slate-600 mt-2 line-clamp-2"><?= e($b['excerpt']) ?></p>
        <div class="mt-auto pt-3 flex items-center gap-3 text-xs text-slate-500">
          <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3.5 h-3.5"></i><?= format_date_th($b['published_at']) ?></span>
          <span class="flex items-center gap-1"><i data-lucide="eye" class="w-3.5 h-3.5"></i><?= number_format($b['view_count']) ?></span>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
