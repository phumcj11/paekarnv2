<?php
/** @var array $bySlot @var array $labels @var array $allSlots @var array $homeSlots @var array $placesSlots @var array $bSlots @var array $specialSlots @var array $recommendedSpecs @var array $anchorLinks @var array $screenBadges @var array $placementHints */
$homeSlots = $homeSlots ?? [];
$placesSlots = $placesSlots ?? [];
$bSlots = $bSlots ?? \App\Models\Banner::B_SLOTS;
$specialSlots = $specialSlots ?? \App\Models\Banner::SPECIAL_HOME_SLOTS;
$slotLinks = static fn (string $slot): array => $anchorLinks[$slot] ?? [];
$badgeClass = static function (string $badge): string {
    return str_contains(strtolower($badge), 'desktop')
        ? 'bg-amber-100 text-amber-800 ring-amber-200'
        : 'bg-emerald-100 text-emerald-800 ring-emerald-200';
};
$wireframeRows = [
    ['label' => 'Hero ด้านบน', 'slots' => ['hero'], 'class' => 'h-20 bg-gradient-to-r from-slate-800 to-emerald-700 text-white'],
    ['label' => 'B01 Premium ใต้ค้นหา', 'slots' => ['home_b01'], 'class' => 'h-14 bg-rose-100 text-rose-900'],
    ['label' => 'แถบคูปอง desktop', 'slots' => ['home_desktop_coupon_strip'], 'class' => 'h-10 bg-amber-100 text-amber-900'],
    ['label' => 'B02 ก่อน Recommended', 'slots' => ['home_b02'], 'class' => 'h-10 bg-sky-100 text-sky-900'],
    ['label' => 'แพแนะนำ', 'slots' => [], 'class' => 'h-12 bg-white text-slate-500 border border-dashed border-slate-300'],
    ['label' => 'B03 หลัง Recommended', 'slots' => ['home_b03'], 'class' => 'h-10 bg-sky-50 text-sky-900'],
    ['label' => 'B04 หลัง trust', 'slots' => ['home_b04'], 'class' => 'h-10 bg-teal-50 text-teal-900'],
    ['label' => 'B05 ก่อนแพใหม่', 'slots' => ['home_b05'], 'class' => 'h-10 bg-blue-50 text-blue-900'],
    ['label' => 'แพใหม่ + B06 ใน grid', 'slots' => ['home_b06'], 'class' => 'h-12 bg-orange-50 text-orange-900'],
    ['label' => 'B07 หลัง listings', 'slots' => ['home_b07'], 'class' => 'h-10 bg-lime-50 text-lime-900'],
    ['label' => 'B08 ก่อนกิจกรรม', 'slots' => ['home_b08'], 'class' => 'h-10 bg-indigo-50 text-indigo-900'],
    ['label' => 'B09 ก่อนวิดีโอ', 'slots' => ['home_b09'], 'class' => 'h-10 bg-violet-50 text-violet-900'],
    ['label' => 'B10 ก่อนบทความ', 'slots' => ['home_b10'], 'class' => 'h-10 bg-pink-50 text-pink-900'],
    ['label' => 'ก่อน CTA ล่างสุด', 'slots' => ['home_before_cta'], 'class' => 'h-10 bg-slate-100 text-slate-800'],
    ['label' => 'หน้าที่เที่ยว — Hero', 'slots' => ['places_hero'], 'class' => 'h-14 bg-gradient-to-r from-slate-700 to-emerald-800 text-white'],
    ['label' => 'หน้าที่เที่ยว — Banner โปร', 'slots' => ['places_promo_raft', 'places_promo_deal'], 'class' => 'h-12 bg-lime-50 text-lime-900'],
];
?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
  <div>
    <p class="text-sm text-slate-500">จัดการ Banner หน้าแรก และหน้าที่เที่ยว (/places) — ลำดับด้วย Sort</p>
  </div>
  <a href="<?= url('/admin/banners/create') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 bg-accent-500 hover:bg-accent-600 text-white font-semibold rounded-xl shadow-soft">
    <i data-lucide="plus" class="w-4 h-4"></i> เพิ่ม Banner
  </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[0.95fr_1.05fr] gap-5 mb-6">
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
    <div class="p-4 border-b border-slate-100 bg-slate-50">
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="layout-dashboard" class="w-5 h-5 text-accent-600"></i> Preview ตำแหน่งบนหน้าแรก</h3>
      <p class="mt-1 text-xs text-slate-600">เป็นแผนผังคร่าวๆ ของหน้าแรก กดชื่อ slot เพื่อเปิดตำแหน่งจริงในแท็บใหม่</p>
    </div>
    <div class="p-4 bg-gradient-to-b from-slate-50 to-white">
      <div class="mx-auto max-w-md rounded-2xl border border-slate-200 bg-slate-100 p-3 shadow-inner space-y-2">
        <?php foreach ($wireframeRows as $row): ?>
          <div class="<?= e($row['class']) ?> rounded-xl px-3 py-2 flex items-center justify-between gap-2 text-xs font-semibold shadow-sm">
            <span><?= e($row['label']) ?></span>
            <?php if (!empty($row['slots'])): ?>
              <span class="flex flex-wrap justify-end gap-1.5">
                <?php foreach ($row['slots'] as $slot): ?>
                  <?php $links = $slotLinks($slot); $href = $links[0]['url'] ?? ''; ?>
                  <?php if ($href !== ''): ?>
                    <a href="<?= e($href) ?>" target="_blank" rel="noopener noreferrer" class="rounded-full bg-white/90 px-2 py-0.5 font-mono text-[10px] text-slate-700 ring-1 ring-black/5 hover:bg-white">
                      <?= e($slot) ?>
                    </a>
                  <?php else: ?>
                    <span class="rounded-full bg-white/90 px-2 py-0.5 font-mono text-[10px] text-slate-700 ring-1 ring-black/5"><?= e($slot) ?></span>
                  <?php endif; ?>
                <?php endforeach; ?>
              </span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="mt-3 text-[11px] leading-relaxed text-slate-500">ช่อง B01–B10 เป็นตำแหน่ง Banner หน้าแรกหลัก — ถ้าไม่มี Banner ในช่องนั้น หน้าเว็บจะไม่เว้นช่องว่าง</p>
    </div>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
    <div class="p-4 border-b border-slate-100 bg-slate-50">
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="map" class="w-5 h-5 text-accent-600"></i> รายการตำแหน่งและลิงก์ตรวจสอบ</h3>
    </div>
    <div class="divide-y divide-slate-100">
      <?php foreach ($allSlots as $slot):
        $spec = $recommendedSpecs[$slot] ?? '';
        $badge = $screenBadges[$slot] ?? '';
        $hint = $placementHints[$slot] ?? '';
        $links = $slotLinks($slot); ?>
        <div class="p-4">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <code class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] text-slate-700"><?= e($slot) ?></code>
                <?php if ($badge !== ''): ?><span class="rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 <?= e($badgeClass($badge)) ?>"><?= e($badge) ?></span><?php endif; ?>
              </div>
              <div class="mt-1 text-sm font-semibold text-slate-800"><?= e($labels[$slot] ?? $slot) ?></div>
              <?php if ($hint !== ''): ?><p class="mt-1 text-xs leading-relaxed text-slate-600"><?= e($hint) ?></p><?php endif; ?>
              <?php if ($spec !== ''): ?><p class="mt-1 text-[11px] leading-relaxed text-slate-500"><span class="font-semibold text-slate-600">ขนาดภาพแนะนำ:</span> <?= e($spec) ?></p><?php endif; ?>
            </div>
            <?php if (!empty($links)): ?>
              <div class="flex shrink-0 flex-wrap gap-1.5">
                <?php foreach ($links as $link): ?>
                  <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1.5 text-[11px] font-bold text-primary-700 ring-1 ring-primary-100 hover:bg-primary-50">
                    <?= e($link['label']) ?> <i data-lucide="external-link" class="h-3 w-3"></i>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<h2 class="text-lg font-bold text-slate-800 mb-3 mt-2">ช่อง Banner หน้าแรก B01–B10</h2>
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3 mb-8">
  <?php foreach ($bSlots as $slot):
    $rows = $bySlot[$slot] ?? [];
    $active = null;
    foreach ($rows as $r) {
        if (!empty($r['is_active'])) { $active = $r; break; }
    }
    if ($active === null && $rows !== []) {
        $active = $rows[0];
    }
    $links = $slotLinks($slot);
    $href = $links[0]['url'] ?? '';
  ?>
  <div class="bg-white rounded-xl border border-slate-200 shadow-soft p-3 flex flex-col gap-2">
    <div class="flex items-center justify-between gap-2">
      <code class="text-[10px] font-bold text-primary-700"><?= e(strtoupper(str_replace('home_', '', $slot))) ?></code>
      <?php if ($active): ?>
        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700">เปิด</span>
      <?php else: ?>
        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-500">ว่าง</span>
      <?php endif; ?>
    </div>
    <div class="text-xs font-semibold text-slate-800 line-clamp-2 min-h-[2.5rem]"><?= e($labels[$slot] ?? $slot) ?></div>
    <?php if ($active && !empty($active['image_path'])): ?>
      <img src="<?= e(upload_url($active['image_path'])) ?>" alt="" class="w-full h-16 object-cover rounded-lg border border-slate-100">
      <div class="text-[10px] text-slate-500 truncate"><?= e($active['advertiser_name'] ?? $active['title'] ?? '') ?></div>
    <?php else: ?>
      <div class="h-16 rounded-lg border border-dashed border-slate-200 bg-slate-50 grid place-items-center text-[10px] text-slate-400">ยังไม่มี Banner</div>
    <?php endif; ?>
    <div class="flex flex-wrap gap-1 mt-auto">
      <a href="<?= url('/admin/banners/create?slot=' . urlencode($slot)) ?>" class="text-[10px] px-2 py-1 bg-accent-500 text-white rounded font-bold">+ เพิ่ม</a>
      <?php if ($active): ?>
        <a href="<?= url('/admin/banners/' . (int)$active['id'] . '/edit') ?>" class="text-[10px] px-2 py-1 bg-slate-100 rounded font-medium">แก้ไข</a>
      <?php endif; ?>
      <?php if ($href !== ''): ?>
        <a href="<?= e($href) ?>" target="_blank" rel="noopener" class="text-[10px] px-2 py-1 bg-primary-50 text-primary-700 rounded font-medium">ดูบนเว็บ</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php
$slotGroups = [
    ['title' => 'ช่องพิเศษหน้าแรก (Hero / โซน / CTA)', 'slots' => $specialSlots],
    ['title' => 'หน้าที่เที่ยว (/places)', 'slots' => $placesSlots],
];
foreach ($slotGroups as $group):
  if (empty($group['slots'])) continue;
?>
  <h2 class="text-lg font-bold text-slate-800 mb-3 mt-2"><?= e($group['title']) ?></h2>
<?php foreach ($group['slots'] as $slot):
  $rows = $bySlot[$slot] ?? [];
  $links = $slotLinks($slot);
  $badge = $screenBadges[$slot] ?? '';
  $hint = $placementHints[$slot] ?? '';
  ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft mb-5 overflow-hidden">
    <div class="px-4 py-3 border-b border-slate-100 bg-primary-50/40">
      <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <div class="font-bold text-primary-800"><?= e($labels[$slot] ?? $slot) ?></div>
            <?php if ($badge !== ''): ?><span class="rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 <?= e($badgeClass($badge)) ?>"><?= e($badge) ?></span><?php endif; ?>
          </div>
          <?php if ($hint !== ''): ?><p class="text-[11px] text-slate-600 mt-1 leading-snug"><?= e($hint) ?></p><?php endif; ?>
          <?php $spec = $recommendedSpecs[$slot] ?? ''; ?>
          <?php if ($spec !== ''): ?>
            <p class="text-[11px] text-slate-600 mt-1 leading-snug"><span class="font-semibold text-slate-700">ขนาดภาพแนะนำ:</span> <?= e($spec) ?></p>
          <?php endif; ?>
        </div>
        <div class="flex shrink-0 flex-col items-end gap-2">
          <span class="text-xs font-mono text-slate-500"><?= e($slot) ?></span>
          <?php if (!empty($links)): ?>
            <div class="flex flex-wrap justify-end gap-1.5">
              <?php foreach ($links as $link): ?>
                <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-lg bg-white px-2 py-1 text-[11px] font-bold text-primary-700 ring-1 ring-primary-100 hover:bg-primary-50">
                  <?= e($link['label']) ?> <i data-lucide="external-link" class="h-3 w-3"></i>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php if (empty($rows)): ?>
      <div class="px-4 py-8 text-center text-slate-500 text-sm">ยังไม่มี Banner ในตำแหน่งนี้</div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-xs uppercase text-slate-600">
            <tr>
              <th class="text-left px-4 py-2">ลำดับ</th>
              <th class="text-left px-4 py-2">ภาพ</th>
              <th class="text-left px-4 py-2">หัวข้อ</th>
              <th class="text-left px-4 py-2">ลิงก์</th>
              <th class="text-left px-4 py-2">สถานะ</th>
              <th class="text-right px-4 py-2">จัดการ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
          <?php foreach ($rows as $r): ?>
            <tr class="hover:bg-slate-50">
              <td class="px-4 py-2 font-mono"><?= (int)$r['sort_order'] ?></td>
              <td class="px-4 py-2">
                <?php if (!empty($r['image_path'])): ?>
                  <img src="<?= e(upload_url($r['image_path'])) ?>" alt="" class="h-12 w-20 rounded-lg border border-slate-200 object-cover bg-slate-100">
                <?php else: ?>
                  <div class="grid h-12 w-20 place-items-center rounded-lg border border-dashed border-slate-200 bg-slate-50 text-[10px] text-slate-400">ไม่มีรูป</div>
                <?php endif; ?>
              </td>
              <td class="px-4 py-2">
                <div class="font-medium"><?= e($r['title'] ?: '(ไม่มีหัวข้อ)') ?></div>
                <?php if (!empty($r['subtitle'])): ?><div class="text-xs text-slate-500 line-clamp-1"><?= e($r['subtitle']) ?></div><?php endif; ?>
              </td>
              <td class="px-4 py-2 text-xs font-mono max-w-[180px] truncate"><?= e($r['link_url'] ?: '-') ?></td>
              <td class="px-4 py-2">
                <span class="text-xs px-2 py-0.5 rounded-full <?= $r['is_active'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>"><?= $r['is_active'] ? 'เปิด' : 'ปิด' ?></span>
              </td>
              <td class="px-4 py-2 text-right whitespace-nowrap">
                <a href="<?= url('/admin/banners/'.$r['id'].'/edit') ?>" class="text-xs px-2 py-1 bg-slate-100 hover:bg-slate-200 rounded">แก้ไข</a>
                <form method="post" action="<?= url('/admin/banners/'.$r['id'].'/delete') ?>" class="inline" onsubmit="return confirm('ลบ Banner นี้?')"><?= csrf() ?>
                  <button type="submit" class="text-xs px-2 py-1 bg-rose-50 text-rose-600 rounded hover:bg-rose-100">ลบ</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
<?php endforeach; ?>
