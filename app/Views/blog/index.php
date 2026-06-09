<?php /** @var array $rows @var int $page @var int $totalPages */ ?>
<section class="bg-primary-700 text-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
    <h1 class="text-2xl md:text-3xl font-bold flex items-center gap-2"><i data-lucide="newspaper" class="w-7 h-7"></i> บทความท่องเที่ยว</h1>
    <p class="text-white/85 mt-1">แพลนทริปกาญจน์ ข่าวที่พัก โปรโมชั่น และคู่มือใช้คูปอง</p>
  </div>
</section>

<section class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
  <?php if (empty($rows)): ?>
    <div class="text-center py-16 text-slate-500">ยังไม่มีบทความ</div>
  <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php foreach ($rows as $b): ?>
      <a href="<?= url('/blog/' . $b['slug']) ?>"
         class="group bg-white rounded-2xl overflow-hidden border border-slate-200 hover:shadow-soft transition flex flex-col">
        <div class="aspect-[16/9] bg-slate-100 overflow-hidden">
          <img src="<?= e(upload_url($b['cover_image'])) ?>" class="w-full h-full object-cover group-hover:scale-105 transition" loading="lazy" alt="" referrerpolicy="no-referrer">
        </div>
        <div class="p-5 flex-1 flex flex-col">
          <div class="text-xs text-accent-600 font-semibold uppercase"><?= e($b['category']) ?></div>
          <h3 class="mt-1 font-semibold text-lg group-hover:text-primary-700"><?= e($b['title']) ?></h3>
          <p class="text-sm text-slate-600 mt-2 line-clamp-3"><?= e($b['excerpt']) ?></p>
          <div class="mt-auto pt-3 flex items-center gap-3 text-xs text-slate-500">
            <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3.5 h-3.5"></i><?= format_date_th($b['published_at']) ?></span>
            <span class="flex items-center gap-1"><i data-lucide="eye" class="w-3.5 h-3.5"></i><?= number_format($b['view_count']) ?></span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
    </div>
    <?php \App\Core\View::partial('partials/pagination', ['page'=>$page,'totalPages'=>$totalPages,'baseUrl'=>url('/blog'),'query'=>[]]); ?>
  <?php endif; ?>
</section>
