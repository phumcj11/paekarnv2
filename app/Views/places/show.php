<?php
/** @var array<string,mixed> $place */
/** @var array<int,array<string,mixed>> $nearbyProperties */
/** @var array<int,array<string,mixed>> $relatedActivities */
/** @var array<string,string> $categories */
$catLab = $categories[$place['category']] ?? $place['category'];
$cover = \App\Models\VisitorPlace::coverImageUrl($place);
?>
<section class="bg-primary-700 text-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 md:py-10">
    <nav class="text-xs text-white/75 mb-3 flex flex-wrap gap-x-2 gap-y-1">
      <a href="<?= url('/') ?>" class="hover:text-white">หน้าแรก</a>
      <span>/</span>
      <a href="<?= url('/places') ?>" class="hover:text-white">ที่เที่ยว</a>
      <span>/</span>
      <span class="text-white"><?= e($place['name']) ?></span>
    </nav>
    <div class="inline-flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-accent-200"><?= e($catLab) ?></div>
    <h1 class="text-2xl md:text-4xl font-bold mt-1"><?= e($place['name']) ?></h1>
    <?php if (!empty($place['district']) || !empty($place['zone'])): ?>
      <p class="text-white/90 mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
        <?php if (!empty($place['district'])): ?>
          <span class="inline-flex items-center gap-2"><i data-lucide="map" class="w-4 h-4 shrink-0"></i> อำเภอ <?= e((string)$place['district']) ?></span>
        <?php endif; ?>
        <?php if (!empty($place['zone'])): ?>
          <span class="inline-flex items-center gap-2"><i data-lucide="map-pin" class="w-4 h-4 shrink-0"></i> โซน <?= e($place['zone']) ?></span>
        <?php endif; ?>
      </p>
    <?php endif; ?>
  </div>
</section>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-10 grid grid-cols-1 lg:grid-cols-3 gap-8">
  <article class="lg:col-span-2 space-y-6">
    <?php if ($cover !== ''): ?>
      <div class="rounded-2xl overflow-hidden border border-slate-200 shadow-soft bg-slate-100 aspect-[16/9] max-h-[420px]">
        <img src="<?= e($cover) ?>" alt="<?= e($place['name']) ?>" class="w-full h-full object-cover" loading="eager" width="1200" height="675">
      </div>
    <?php endif; ?>

    <?php if (!empty($place['excerpt'])): ?>
      <p class="text-lg text-slate-700 leading-relaxed font-medium border-l-4 border-accent-500 pl-4"><?= e($place['excerpt']) ?></p>
    <?php endif; ?>

    <?php if (!empty($place['description'])): ?>
      <div class="text-slate-700 leading-relaxed"><?= nl2br(e((string)$place['description'])) ?></div>
    <?php endif; ?>

    <?php if (!empty($place['address'])): ?>
      <div class="bg-cloud rounded-2xl border border-slate-200 p-5">
        <h2 class="font-bold text-ink flex items-center gap-2"><i data-lucide="route" class="w-5 h-5 text-primary-600"></i> ที่อยู่ / เดินทาง</h2>
        <p class="mt-2 text-sm text-slate-700 whitespace-pre-wrap"><?= nl2br(e((string)$place['address'])) ?></p>
      </div>
    <?php endif; ?>
  </article>

  <aside class="lg:col-span-1 space-y-6">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 lg:sticky lg:top-28">
      <h2 class="font-bold text-ink">แผนที่</h2>
      <?php if (!empty($place['google_maps_url'])): ?>
        <a href="<?= e($place['google_maps_url']) ?>" target="_blank" rel="noopener noreferrer" class="mt-3 w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-primary-600 text-white font-semibold text-sm hover:bg-primary-700 transition">
          <i data-lucide="external-link" class="w-4 h-4"></i> เปิดใน Google Maps
        </a>
      <?php else: ?>
        <p class="mt-2 text-sm text-slate-500">ยังไม่มีลิงก์แผนที่ — ติดต่อแอดมินให้เพิ่มได้</p>
      <?php endif; ?>
      <a href="<?= url('/places') ?>" class="mt-3 block text-center text-sm font-semibold text-primary-700 hover:underline">ดูที่เที่ยวทั้งหมด</a>
    </div>
  </aside>
</div>

<?php if (!empty($relatedActivities)): ?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 pb-10">
  <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
    <div>
      <h2 class="text-xl md:text-2xl font-bold text-ink flex items-center gap-2"><i data-lucide="map" class="w-6 h-6 text-primary-600"></i> กิจกรรม / บริการใกล้ที่นี่</h2>
      <p class="text-sm text-slate-600 mt-1">จองกิจกรรม รถเช่า หรือรถนำเที่ยวที่เกี่ยวข้องกับจุดหมายนี้</p>
    </div>
    <a href="<?= url('/activities?' . http_build_query(array_filter(['district' => $place['district'] ?? null, 'zone' => $place['zone'] ?? null]))) ?>" class="text-sm font-semibold text-primary-700 inline-flex items-center gap-1">ดูทั้งหมด <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
  </div>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <?php foreach ($relatedActivities as $activity): ?>
      <?php \App\Core\View::partial('partials/activity-card', ['activity' => $activity]); ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="bg-cloud border-y border-slate-100 py-12 mt-4">
  <div class="max-w-7xl mx-auto px-4 sm:px-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
      <div>
        <h2 class="text-xl md:text-2xl font-bold text-ink flex items-center gap-2"><i data-lucide="hotel" class="w-6 h-6 text-primary-600"></i> แพที่พักในโซนเดียวกัน</h2>
        <p class="text-sm text-slate-600 mt-1">
          <?php if (!empty($place['zone'])): ?>
            แนะนำที่พักที่ตั้งโซน «<?= e($place['zone']) ?>» เหมือนจุดหมายนี้
          <?php else: ?>
            ที่พักแนะนำจากระบบ (ยังไม่ผูกโซนกับสถานที่นี้)
          <?php endif; ?>
        </p>
      </div>
      <?php
      $propListUrl = url('/properties');
      if (!empty($place['zone'])) {
          $propListUrl .= '?' . http_build_query(['zone' => $place['zone']]);
      }
      ?>
      <a href="<?= e($propListUrl) ?>" class="text-sm font-semibold text-primary-700 inline-flex items-center gap-1 shrink-0">
        ค้นหาที่พักในโซนนี้ <i data-lucide="arrow-right" class="w-4 h-4"></i>
      </a>
    </div>
    <?php if (empty($nearbyProperties)): ?>
      <p class="text-slate-500 text-sm text-center py-8">ยังไม่มีที่พักแสดงในขณะนี้</p>
    <?php else: ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <?php foreach ($nearbyProperties as $property): ?>
          <?php \App\Core\View::partial('partials/property-card', ['property' => $property]); ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
