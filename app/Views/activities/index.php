<?php
/** @var array<int,array<string,mixed>> $rows */
/** @var array<string,string> $categories */
/** @var list<string> $districtChoices */
/** @var list<string> $zoneChoices */
/** @var ?string $filterCategory */
/** @var ?string $filterDistrict */
/** @var ?string $filterZone */
/** @var int $page */
/** @var int $totalPages */
/** @var array<string,string> $filterQuery */
?>
<section class="bg-primary-700 text-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-10 md:py-12">
    <div class="text-sm text-accent-200 font-semibold uppercase tracking-wide">Activities & Services</div>
    <h1 class="text-2xl md:text-3xl font-bold mt-1 flex items-center gap-2"><i data-lucide="map" class="w-7 h-7"></i> กิจกรรมท่องเที่ยวกาญจนบุรี</h1>
    <p class="text-white/85 mt-2 max-w-2xl leading-relaxed">จองกิจกรรม รถเช่า รถนำเที่ยว และบริการท่องเที่ยว แยกตามอำเภอในกาญจนบุรี พร้อมราคาพิเศษผ่านแพกาญ</p>
    <a href="<?= url('/provider/register') ?>" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-white/15 hover:bg-white/25 border border-white/30 rounded-xl text-sm font-semibold transition">
      <i data-lucide="handshake" class="w-4 h-4"></i> รถเช่า / บริการท้องถิ่น? สมัครเป็นพาร์ทเนอร์
    </a>
  </div>
</section>

<section class="bg-cloud border-b border-slate-100">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
    <form method="get" action="<?= url('/activities') ?>" class="flex flex-col lg:flex-row lg:flex-wrap gap-3 lg:items-end">
      <div class="flex-1 min-w-[180px]">
        <label class="block text-xs font-semibold text-slate-600 mb-1">หมวด</label>
        <select name="category" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm">
          <option value="">ทุกหมวด</option>
          <?php foreach ($categories as $k => $lab): ?><option value="<?= e($k) ?>" <?= ($filterCategory ?? '') === $k ? 'selected' : '' ?>><?= e($lab) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="flex-1 min-w-[180px]">
        <label class="block text-xs font-semibold text-slate-600 mb-1">อำเภอ</label>
        <select name="district" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm">
          <option value="">ทุกอำเภอ</option>
          <?php foreach ($districtChoices as $d): ?><option value="<?= e($d) ?>" <?= ($filterDistrict ?? '') === $d ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="flex-1 min-w-[200px]">
        <label class="block text-xs font-semibold text-slate-600 mb-1">โซน</label>
        <select name="zone" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm">
          <option value="">ทุกโซน</option>
          <?php foreach ($zoneChoices as $z): ?><option value="<?= e($z) ?>" <?= ($filterZone ?? '') === $z ? 'selected' : '' ?>><?= e($z) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="flex gap-2">
        <button class="px-5 py-2 rounded-xl bg-primary-600 text-white font-semibold text-sm hover:bg-primary-700 transition">ค้นหา</button>
        <a href="<?= url('/activities') ?>" class="px-4 py-2 rounded-xl border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-white transition inline-flex items-center">ล้าง</a>
      </div>
    </form>
  </div>
</section>

<section class="max-w-7xl mx-auto px-4 sm:px-6 py-10">
  <?php if ($rows === []): ?>
    <div class="text-center py-16 text-slate-500 rounded-2xl border border-dashed border-slate-300 bg-white">
      ยังไม่มีกิจกรรมในตัวกรองนี้
    </div>
  <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($rows as $activity): ?>
        <?php \App\Core\View::partial('partials/activity-card', ['activity' => $activity]); ?>
      <?php endforeach; ?>
    </div>
    <?php \App\Core\View::partial('partials/pagination', [
        'page' => $page,
        'totalPages' => $totalPages,
        'baseUrl' => url('/activities'),
        'query' => $filterQuery,
    ]); ?>
  <?php endif; ?>
</section>
