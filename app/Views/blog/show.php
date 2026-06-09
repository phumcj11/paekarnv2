<?php /** @var array $post @var array $related */ ?>
<article class="max-w-3xl mx-auto px-4 sm:px-6 py-8">
  <a href="<?= url('/blog') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</a>
  <div class="text-xs text-accent-600 font-semibold uppercase"><?= e($post['category']) ?></div>
  <h1 class="text-2xl md:text-4xl font-extrabold leading-tight mt-1"><?= e($post['title']) ?></h1>
  <div class="mt-3 flex items-center gap-3 text-xs text-slate-500">
    <span class="flex items-center gap-1"><i data-lucide="calendar" class="w-3.5 h-3.5"></i><?= format_date_th($post['published_at']) ?></span>
    <span class="flex items-center gap-1"><i data-lucide="eye" class="w-3.5 h-3.5"></i><?= number_format($post['view_count']) ?></span>
  </div>
  <img src="<?= e(upload_url($post['cover_image'])) ?>" class="w-full rounded-2xl my-5 aspect-[16/9] object-cover" referrerpolicy="no-referrer" alt="">
  <div class="prose prose-slate max-w-none">
    <?= $post['content'] /* HTML allowed (admin only) */ ?>
  </div>
</article>

<?php if (!empty($related)): ?>
<section class="max-w-3xl mx-auto px-4 sm:px-6 pb-10">
  <h3 class="font-bold text-lg mb-3">บทความที่เกี่ยวข้อง</h3>
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
  <?php foreach ($related as $b): ?>
    <a href="<?= url('/blog/' . $b['slug']) ?>" class="group bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-soft">
      <img src="<?= e(upload_url($b['cover_image'])) ?>" class="aspect-[16/9] object-cover w-full" loading="lazy" referrerpolicy="no-referrer" alt="">
      <div class="p-3">
        <h4 class="text-sm font-semibold group-hover:text-primary-700 line-clamp-2"><?= e($b['title']) ?></h4>
      </div>
    </a>
  <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
