<?php
/** @var array<string,mixed> $product */
/** @var array<int,array<string,mixed>> $options */
/** @var array<string,string> $categories */
use App\Models\ActivityProduct;

$cat = $categories[$product['category']] ?? $product['category'];
$img = ActivityProduct::coverImageUrl($product);
$line = ActivityProduct::lineUrl($product['provider_line_id'] ?? '');
$price = (float)$product['base_price'];
?>
<section class="bg-primary-700 text-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 md:py-10">
    <nav class="text-xs text-white/75 mb-3 flex flex-wrap gap-x-2 gap-y-1">
      <a href="<?= url('/') ?>" class="hover:text-white">หน้าแรก</a><span>/</span>
      <a href="<?= url('/activities') ?>" class="hover:text-white">กิจกรรม</a><span>/</span>
      <span><?= e($product['title']) ?></span>
    </nav>
    <div class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-accent-200"><?= e($cat) ?></div>
    <h1 class="text-2xl md:text-4xl font-bold mt-1"><?= e($product['title']) ?></h1>
    <p class="text-white/90 mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm">
      <?php if (!empty($product['district'])): ?><span><i data-lucide="map" class="w-4 h-4 inline"></i> <?= e($product['district']) ?></span><?php endif; ?>
      <?php if (!empty($product['duration_label'])): ?><span><i data-lucide="clock" class="w-4 h-4 inline"></i> <?= e($product['duration_label']) ?></span><?php endif; ?>
    </p>
  </div>
</section>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-10 grid grid-cols-1 lg:grid-cols-3 gap-8">
  <article class="lg:col-span-2 space-y-6">
    <div class="rounded-2xl overflow-hidden border border-slate-200 shadow-soft bg-slate-100 aspect-[16/9] max-h-[460px]">
      <img src="<?= e($img) ?>" alt="<?= e($product['title']) ?>" class="w-full h-full object-cover">
    </div>
    <?php if (!empty($product['excerpt'])): ?><p class="text-lg text-slate-700 leading-relaxed font-medium border-l-4 border-accent-500 pl-4"><?= e($product['excerpt']) ?></p><?php endif; ?>
    <?php if (!empty($product['description'])): ?><div class="text-slate-700 leading-relaxed whitespace-pre-wrap"><?= e($product['description']) ?></div><?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <?php if (!empty($product['included'])): ?>
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h2 class="font-bold flex items-center gap-2"><i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i> สิ่งที่รวม</h2>
        <p class="mt-2 text-sm text-slate-700 whitespace-pre-wrap"><?= e($product['included']) ?></p>
      </div>
      <?php endif; ?>
      <?php if (!empty($product['excluded'])): ?>
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h2 class="font-bold flex items-center gap-2"><i data-lucide="x-circle" class="w-5 h-5 text-rose-500"></i> ไม่รวม</h2>
        <p class="mt-2 text-sm text-slate-700 whitespace-pre-wrap"><?= e($product['excluded']) ?></p>
      </div>
      <?php endif; ?>
    </div>

    <?php if (!empty($product['meeting_point']) || !empty($product['cancellation_policy'])): ?>
    <div class="bg-cloud rounded-2xl border border-slate-200 p-5">
      <?php if (!empty($product['meeting_point'])): ?><h2 class="font-bold">จุดนัดพบ</h2><p class="mt-1 text-sm text-slate-700"><?= e($product['meeting_point']) ?></p><?php endif; ?>
      <?php if (!empty($product['cancellation_policy'])): ?><h2 class="font-bold mt-4">เงื่อนไขการยกเลิก</h2><p class="mt-1 text-sm text-slate-700 whitespace-pre-wrap"><?= e($product['cancellation_policy']) ?></p><?php endif; ?>
    </div>
    <?php endif; ?>
  </article>

  <aside class="lg:col-span-1">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 lg:sticky lg:top-28 space-y-4">
      <div>
        <div class="text-xs text-slate-500 uppercase font-semibold">ราคาเริ่มต้น</div>
        <div class="text-3xl font-extrabold text-primary-700"><?= $price > 0 ? format_money($price) : 'สอบถามราคา' ?></div>
        <?php if ((float)$product['compare_at_price'] > $price && $price > 0): ?><div class="text-sm text-slate-400 line-through"><?= format_money($product['compare_at_price']) ?></div><?php endif; ?>
      </div>
      <?php if ($options !== []): ?>
      <div class="space-y-2">
        <h3 class="font-semibold">ตัวเลือก</h3>
        <?php foreach ($options as $op): ?>
          <div class="rounded-xl border border-slate-200 p-3">
            <div class="font-semibold text-sm"><?= e($op['name']) ?></div>
            <?php if (!empty($op['description'])): ?><div class="text-xs text-slate-500 mt-0.5"><?= e($op['description']) ?></div><?php endif; ?>
            <div class="text-primary-700 font-bold mt-1"><?= format_money($op['price']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if (($product['booking_mode'] ?? 'lead') === 'voucher'): ?>
        <a href="<?= url('/activity/checkout/' . $product['id']) ?>" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-accent-500 hover:bg-accent-600 text-white font-bold">
          <i data-lucide="ticket" class="w-5 h-5"></i> ซื้อ voucher
        </a>
      <?php endif; ?>
      <?php if ($line !== ''): ?><a href="<?= url('/activities/lead/' . (int)$product['id'] . '?type=line') ?>" target="_blank" rel="noopener" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border border-emerald-500 text-emerald-700 font-bold hover:bg-emerald-50">ติดต่อ LINE</a><?php endif; ?>
      <?php if (!empty($product['provider_phone'])): ?><a href="<?= url('/activities/lead/' . (int)$product['id'] . '?type=phone') ?>" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border border-primary-600 text-primary-700 font-bold hover:bg-primary-50"><i data-lucide="phone" class="w-5 h-5"></i> โทรสอบถาม</a><?php endif; ?>
      <div class="text-xs text-slate-500">ผู้ให้บริการ: <?= e($product['provider_name'] ?: 'แพกาญ.com') ?></div>
    </div>
  </aside>
</div>
