<?php /** @var array $property */
use App\Core\Auth;

$ra = (float)$property['rating_avg'];
$rc = (int)$property['rating_count'];
$locMain = $property['zone'] ?: ($property['district'] ?: '');
$prov = (string)($property['province'] ?? '');
$locDisplay = $locMain !== '' ? $locMain . ($prov !== '' ? ', ' . $prov : '') : ($prov !== '' ? $prov : 'กาญจนบุรี');

$typeMap = ['raft'=>'แพพัก','resort'=>'รีสอร์ท','homestay'=>'โฮมสเตย์','house'=>'บ้านพัก','pool_villa'=>'บ้านพูลวิลล่า','hotel'=>'โรงแรม','camping'=>'แคมป์ปิ้ง'];
$typeLabel = $typeMap[$property['type']] ?? $property['type'];
$descRaw = trim(strip_tags((string)($property['description'] ?? '')));
$descSnippet = preg_replace('/\s+/u', ' ', $descRaw);
if (mb_strlen($descSnippet) > 180) {
    $descSnippet = mb_substr($descSnippet, 0, 177) . '…';
}
$showFeaturedBadge = \App\Support\UnitPricing::listingShowsFeatured($property);
$showCouponBadge   = (int)($property['coupon_enabled'] ?? 0) === 1;
$galleryThumbs = $property['_gallery_thumb_paths'] ?? [];
$couponFace = (int)\App\Models\Setting::get('coupon_face_value', 500);
$couponSale = (int)\App\Models\Setting::get('coupon_sale_price', 250);

$amenLine = \App\Models\Property::listingUnitSummaryLine($property);
$amenIcon = in_array((string)($property['type'] ?? ''), ['hotel', 'resort'], true) ? 'building-2' : 'bed-double';

$favPid  = (int)($property['id'] ?? 0);
$listingUid = (int)($property['listing_unit_id'] ?? 0);
$pUrl = $listingUid > 0
    ? url('/property/' . $property['slug'] . '?unit=' . $listingUid)
    : url('/property/' . $property['slug']);
$cardTitle = ($listingUid > 0 && ($property['listing_unit_name'] ?? '') !== '')
    ? (string)$property['listing_unit_name']
    : (string)$property['name'];
$coverImg = ($listingUid > 0 && ($property['listing_unit_cover'] ?? '') !== '')
    ? (string)$property['listing_unit_cover']
    : (string)($property['cover_image'] ?? '');
$cardPrice = ($listingUid > 0 && isset($property['listing_unit_price']))
    ? (float)$property['listing_unit_price']
    : (float)($property['min_price'] ?? 0);
$distanceKey = (int)($property['id'] ?? 0) . '-' . $listingUid;
$ariaListing = $listingUid > 0 ? $cardTitle . ' — ' . $property['name'] : $property['name'];
$compareEnabled = $listingUid > 0 && in_array((string)($property['type'] ?? ''), \App\Models\Property::listingExpandTypes(), true);
$comparePayload = $compareEnabled ? [
    'property_id' => (int)($property['id'] ?? 0),
    'unit_id' => $listingUid,
    'title' => $cardTitle,
    'subtitle' => (string)($property['name'] ?? ''),
    'image' => upload_url($coverImg) ?: 'https://placehold.co/800x600?text=Paekan',
    'detail_url' => $pUrl,
] : [];
$compareJson = $compareEnabled ? htmlspecialchars(json_encode($comparePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE), ENT_QUOTES, 'UTF-8') : '';
?>
<div class="group flex flex-row items-stretch rounded-xl border border-slate-200/90 bg-white shadow-[0_8px_28px_-12px_rgba(15,23,42,0.16)] overflow-hidden hover:shadow-[0_14px_36px_-14px_rgba(15,23,42,0.22)] hover:border-forest-200/70 transition active:bg-slate-50/80">
  <div class="paekan-hcard-photo">
    <a href="<?= e($pUrl) ?>" class="absolute inset-0 z-[1]" aria-label="<?= e($ariaListing) ?>"><span class="sr-only"><?= e($ariaListing) ?></span></a>
    <img src="<?= e(upload_url($coverImg) ?: 'https://placehold.co/800x600?text=Paekan') ?>"
         alt=""
         class="paekan-hcard-photo__img group-hover:scale-105 transition duration-500 pointer-events-none"
         width="160"
         height="200"
         loading="lazy"
         decoding="async">

    <?php if ($showFeaturedBadge || $showCouponBadge): ?>
    <div class="absolute top-2 left-2 z-[4] flex flex-col gap-1 items-start pointer-events-none max-w-[88%]">
      <?php if ($showFeaturedBadge): ?>
        <span class="px-1.5 py-0.5 bg-amber-500 text-white text-[9px] font-bold rounded-md shadow-sm inline-flex items-center gap-0.5 ring-1 ring-white/30 leading-tight">
          <i data-lucide="sparkles" class="w-2.5 h-2.5 shrink-0"></i><span class="leading-tight">แพกาญแนะนำ</span>
        </span>
      <?php endif; ?>
      <?php if ($showCouponBadge): ?>
        <span class="px-1.5 py-0.5 bg-forest-800 text-white text-[9px] font-bold rounded-md shadow-sm inline-flex items-center gap-0.5 ring-1 ring-white/30">
          <i data-lucide="ticket" class="w-2.5 h-2.5 shrink-0"></i>คูปอง
        </span>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div x-data="favBtn(<?= $favPid ?>)" class="absolute top-2 right-2 z-[5]">
      <button @click.stop.prevent="toggle"
              :aria-label="faved ? 'เอาออกจากที่บันทึก' : 'บันทึกที่พัก'"
              :disabled="loading"
              class="w-8 h-8 rounded-full bg-white/95 backdrop-blur-sm shadow-md ring-1 ring-slate-200/80 grid place-items-center transition"
              :class="faved ? 'text-rose-600 ring-rose-200' : 'text-slate-600 hover:text-rose-600 hover:ring-rose-200'">
        <i data-lucide="heart" class="w-4 h-4 pointer-events-none" :class="faved ? 'fill-rose-500' : ''"></i>
      </button>
    </div>
    <?php if ($compareEnabled): ?>
    <div class="absolute bottom-2 left-2 z-[6]" x-data="{ item: <?= $compareJson ?> }">
      <button type="button"
              @click.stop.prevent="$store.compare.toggle(item)"
              :aria-label="$store.compare.isSelected(item.property_id, item.unit_id) ? 'เอาออกจากรายการเทียบ' : 'เพิ่มในรายการเทียบ'"
              class="grid h-8 w-8 place-items-center rounded-full text-xs font-extrabold shadow-md ring-1 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-400"
              :class="$store.compare.isSelected(item.property_id, item.unit_id) ? 'bg-teal-600 text-white ring-teal-200' : 'bg-white/95 text-slate-700 ring-slate-200/80 hover:text-teal-700 hover:ring-teal-200'">
        <i data-lucide="scale" class="h-4 w-4"></i>
      </button>
    </div>
    <?php endif; ?>
  </div>
  <a href="<?= e($pUrl) ?>" class="flex-1 min-w-0 flex items-stretch text-left">
    <div class="flex-1 min-w-0 py-2.5 pl-2.5 pr-1 sm:pr-2 flex flex-col gap-1 justify-center">
      <h3 class="font-bold text-sm sm:text-[15px] text-ink leading-snug line-clamp-2 group-hover:text-forest-900 transition">
        <?= e($cardTitle) ?>
      </h3>
      <?php if ($listingUid > 0): ?>
      <p class="text-[11px] text-slate-500 line-clamp-1"><?= e($property['name']) ?></p>
      <?php endif; ?>
      <div x-show="mode === 'detail'" x-cloak class="flex flex-wrap items-center gap-1.5">
        <span class="inline-flex px-2 py-0.5 rounded-full bg-slate-50 text-slate-800 text-[11px] font-semibold border border-slate-200"><?= e($typeLabel) ?></span>
        <?php if (($property['type'] ?? '') === 'raft' && !empty($property['raft_variant'])):
          $rvMap = ['shore' => 'แพริมน้ำ', 'towed' => 'แพลาก']; ?>
        <span class="inline-flex px-2 py-0.5 rounded-full bg-sky-50 text-sky-800 text-[11px] font-semibold border border-sky-100"><?= e($rvMap[$property['raft_variant']] ?? '') ?></span>
        <?php endif; ?>
        <?php if ($showCouponBadge): ?>
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-900 text-[10px] font-bold border border-emerald-200">
            <i data-lucide="percent" class="w-3 h-3 shrink-0"></i>
            โปรคูปอง ซื้อ <?= format_money($couponSale) ?> ใช้แทน <?= format_money($couponFace) ?>
          </span>
        <?php endif; ?>
        <?php if ($showFeaturedBadge): ?>
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 text-amber-900 text-[10px] font-bold border border-amber-200">
            <i data-lucide="award" class="w-3 h-3 shrink-0"></i>
            แนะนำสมาชิกแพกาญ
          </span>
        <?php endif; ?>
      </div>

      <div class="flex items-start gap-1 text-[11px] sm:text-xs text-slate-600 mt-0.5">
        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-forest-700 shrink-0 mt-0.5"></i>
        <span class="line-clamp-2"><?= e($locDisplay) ?></span>
      </div>
      <div data-distance-key="<?= e($distanceKey) ?>" class="hidden w-fit items-center gap-1 rounded-full border border-accent-100 bg-accent-50 px-2 py-0.5 text-[10px] font-bold text-accent-700">
        <i data-lucide="navigation" class="h-3 w-3 shrink-0"></i>
        <span data-distance-label></span>
      </div>

      <?php if ($amenLine !== ''): ?>
      <div class="flex flex-wrap gap-x-2 gap-y-1 text-[10px] sm:text-[11px] text-slate-600 font-semibold leading-snug">
        <span class="inline-flex items-center gap-1"><i data-lucide="<?= e($amenIcon) ?>" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i><?= e($amenLine) ?></span>
      </div>
      <?php else: ?>
      <div class="text-[10px] text-slate-500 font-medium"><?= e($typeLabel) ?></div>
      <?php endif; ?>

      <?php if ($galleryThumbs !== []): ?>
      <div class="flex gap-1 mt-0.5 overflow-x-auto" aria-hidden="true">
        <?php foreach (array_slice($galleryThumbs, 0, 4) as $tp): ?>
        <div class="w-8 h-8 rounded-md overflow-hidden ring-1 ring-slate-200/90 shrink-0 bg-slate-100">
          <img src="<?= e(upload_url($tp)) ?>" alt="" class="w-full h-full object-cover pointer-events-none" loading="lazy">
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if ($descSnippet !== ''): ?>
        <p x-show="mode === 'detail'" x-cloak class="text-[11px] text-slate-600 line-clamp-2"><?= e($descSnippet) ?></p>
      <?php endif; ?>
      <?php
      $intakeCompact = \App\Models\Property::ownerIntakeCompactLines($property, 2);
      ?>
      <?php if ($intakeCompact !== []): ?>
      <div x-show="mode === 'detail'" x-cloak class="space-y-1 border-t border-slate-100 pt-1.5 mt-0.5">
        <?php foreach ($intakeCompact as $line): ?>
          <p class="text-[10px] text-slate-600 leading-snug"><span class="font-semibold text-slate-700"><?= e($line['label']) ?>:</span> <?= e($line['text']) ?></p>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="mt-auto pt-2 border-t border-slate-100 flex items-end justify-between gap-2">
        <div>
          <div class="text-[9px] sm:text-[10px] text-slate-500 font-semibold uppercase tracking-wide">เริ่มต้น</div>
          <div class="text-[15px] sm:text-lg font-extrabold text-forest-900 tabular-nums leading-none"><?= format_money($cardPrice) ?>
            <span class="text-[10px] font-semibold text-slate-500">/ คืน</span>
          </div>
        </div>
        <?php if ($rc > 0): ?>
        <div class="flex items-center gap-1 text-[11px] sm:text-[12px] font-bold shrink-0 pb-0.5">
          <i data-lucide="star" class="w-3.5 h-3.5 sm:w-4 sm:h-4 fill-amber-400 text-amber-500 shrink-0"></i>
          <span class="text-slate-800"><?= number_format($ra, 1) ?></span>
          <span class="text-slate-400 font-semibold">(<?= $rc ?>)</span>
        </div>
        <?php else: ?>
        <span class="text-[10px] text-slate-400 font-semibold shrink-0 leading-snug text-right max-w-[5.5rem] pb-0.5">ยังไม่มีรีวิว</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="flex items-center justify-center min-w-[2.25rem] sm:min-w-[2.75rem] pr-1.5 sm:pr-2 text-slate-300 group-hover:text-forest-400 transition shrink-0 self-stretch">
      <i data-lucide="chevron-right" class="w-5 h-5" aria-hidden="true"></i>
    </div>
  </a>
</div>
