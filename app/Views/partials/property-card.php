<?php
/** @var array $property */
use App\Core\Auth;
use App\Models\Property;
use App\Support\UnitPricing;

$typeMap = ['raft' => 'แพพัก', 'resort' => 'รีสอร์ท', 'homestay' => 'โฮมสเตย์', 'house' => 'บ้านพัก', 'pool_villa' => 'บ้านพูลวิลล่า', 'hotel' => 'โรงแรม', 'camping' => 'แคมป์ปิ้ง'];
$favHref = Auth::check() ? url('/account/favorites') : url('/login');
$amenLine = Property::listingUnitSummaryLine($property);
$amenIcon = in_array((string)($property['type'] ?? ''), ['hotel', 'resort'], true) ? 'building-2' : 'bed-double';

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
$ariaListing = $listingUid > 0 ? $cardTitle . ' — ' . $property['name'] : $property['name'];
$showFeatured = UnitPricing::listingShowsFeatured($property);
$couponOn = (int)($property['coupon_enabled'] ?? 0) === 1;

// membership badge
$ownerTier = (string)($property['owner_membership_tier'] ?? 'none');
$ownerMembershipExpires = $property['owner_membership_expires_at'] ?? null;
$ownerMembershipActive = ($ownerTier === 'standard' || $ownerTier === 'vip')
    && ($ownerMembershipExpires === null || strtotime((string)$ownerMembershipExpires) > time());
$showMemberBadge = $ownerMembershipActive && !$showFeatured; // VIP already shows featured badge
$rc = (int)($property['rating_count'] ?? 0);
$ra = (float)($property['rating_avg'] ?? 0);
$distanceKey = (int)($property['id'] ?? 0) . '-' . $listingUid;
$compareEnabled = $listingUid > 0 && in_array((string)($property['type'] ?? ''), Property::listingExpandTypes(), true);
$comparePayload = $compareEnabled ? [
    'property_id' => (int)($property['id'] ?? 0),
    'unit_id' => $listingUid,
    'title' => $cardTitle,
    'subtitle' => (string)($property['name'] ?? ''),
    'image' => upload_img($coverImg, 'thumb') ?: 'https://placehold.co/800x600?text=Paekan',
    'detail_url' => $pUrl,
] : [];
$compareJson = $compareEnabled ? htmlspecialchars(json_encode($comparePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE), ENT_QUOTES, 'UTF-8') : '';
?>
<div class="group bg-white rounded-2xl overflow-hidden border border-slate-200/90 shadow-[0_14px_44px_-20px_rgba(15,23,42,0.22)] hover:shadow-[0_22px_50px_-22px_rgba(15,23,42,0.28)] hover:-translate-y-0.5 transition flex flex-col h-full">
  <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
    <a href="<?= e($pUrl) ?>" class="absolute inset-0 z-[1]" aria-label="<?= e($ariaListing) ?>"><span class="sr-only"><?= e($ariaListing) ?></span></a>
    <img src="<?= e(upload_img($coverImg, 'thumb') ?: 'https://placehold.co/400x300?text=Paekan') ?>"
         alt=""
         class="relative z-0 w-full h-full object-cover group-hover:scale-105 transition duration-500 pointer-events-none" loading="lazy" width="400" height="300">

    <div class="absolute top-3 left-3 z-[6] flex flex-col gap-1.5 items-start pointer-events-none max-w-[min(72%,15rem)]">
      <?php if ($showFeatured): ?>
      <span class="px-2 py-1 bg-amber-500 text-white text-[10px] sm:text-[11px] font-bold rounded-lg shadow-md inline-flex items-center gap-1 ring-1 ring-white/25 leading-tight max-w-[11rem]">
        <i data-lucide="crown" class="w-3 h-3 shrink-0"></i><span class="leading-snug">สมาชิก VIP แพกาญ</span>
      </span>
      <?php elseif ($showMemberBadge): ?>
      <span class="px-2 py-1 bg-sky-600 text-white text-[10px] sm:text-[11px] font-bold rounded-lg shadow-md inline-flex items-center gap-1 ring-1 ring-white/25 leading-tight max-w-[11rem]">
        <i data-lucide="badge-check" class="w-3 h-3 shrink-0"></i><span class="leading-snug">สมาชิกแพกาญ</span>
      </span>
      <?php endif; ?>
      <?php if ($couponOn): ?>
      <span class="px-2.5 py-1 bg-forest-800 text-white text-[11px] font-bold rounded-lg shadow-md inline-flex items-center gap-1 ring-1 ring-white/25">
        <i data-lucide="ticket" class="w-3 h-3 shrink-0"></i> ใช้คูปองได้
      </span>
      <?php else: ?>
      <span class="px-2 py-1 bg-white/92 text-slate-600 text-[10px] font-semibold rounded-lg shadow-md inline-flex items-center gap-1 ring-1 ring-slate-200/90 backdrop-blur-[2px] leading-snug">
        <i data-lucide="info" class="w-3 h-3 shrink-0 text-slate-500"></i> ไม่เข้าร่วมคูปองแพกาญ
      </span>
      <?php endif; ?>
    </div>

    <a href="<?= e($favHref) ?>"
       class="absolute top-3 right-3 z-[6] w-9 h-9 rounded-full bg-white/95 backdrop-blur-sm shadow-md ring-1 ring-slate-200/80 grid place-items-center text-slate-600 hover:text-rose-600 hover:ring-rose-200 transition"
       aria-label="บันทึกที่พัก">
      <i data-lucide="heart" class="w-[18px] h-[18px]"></i>
    </a>
    <?php if ($compareEnabled): ?>
    <div class="absolute bottom-3 left-3 z-[7]" x-data="{ item: <?= $compareJson ?> }">
      <button type="button"
              @click.stop.prevent="$store.compare.toggle(item)"
              :aria-label="$store.compare.isSelected(item.property_id, item.unit_id) ? 'เอาออกจากรายการเทียบ' : 'เพิ่มในรายการเทียบ'"
              class="inline-flex h-10 min-w-10 items-center justify-center gap-1.5 rounded-full px-3 text-xs font-extrabold shadow-lg ring-1 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-400"
              :class="$store.compare.isSelected(item.property_id, item.unit_id) ? 'bg-teal-600 text-white ring-teal-200' : 'bg-white/95 text-slate-700 ring-slate-200/80 hover:text-teal-700 hover:ring-teal-200'">
        <i data-lucide="scale" class="h-4 w-4"></i>
        <span class="hidden sm:inline" x-text="$store.compare.isSelected(item.property_id, item.unit_id) ? 'เลือกแล้ว' : 'เทียบ'"></span>
      </button>
      <div x-show="$store.compare.shouldShowHint()" x-cloak class="pointer-events-none absolute bottom-full left-0 mb-2 w-32 rounded-xl bg-slate-900 px-3 py-2 text-[11px] font-bold leading-snug text-white shadow-xl">
        กดเพื่อเทียบแพ
      </div>
    </div>
    <?php endif; ?>
  </div>

  <a href="<?= e($pUrl) ?>" class="p-4 flex flex-col flex-1 text-left min-h-0">
    <h3 class="font-bold text-[15px] text-ink leading-snug line-clamp-2 group-hover:text-forest-900 transition">
      <?= e($cardTitle) ?>
    </h3>
    <?php if ($listingUid > 0): ?>
    <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-1"><?= e($property['name']) ?></p>
    <?php endif; ?>    <div class="flex items-center gap-1 text-[12px] text-slate-500 mt-1.5">
      <i data-lucide="map-pin" class="w-3.5 h-3.5 text-forest-700 shrink-0"></i>
      <?php
      $locMain = $property['zone'] ?: $property['district'] ?: '';
      $prov = (string)($property['province'] ?? '');
      ?>
      <span class="line-clamp-2"><?= $locMain !== '' ? e($locMain) . ($prov !== '' ? ', ' . e($prov) : '') : e($prov !== '' ? $prov : 'กาญจนบุรี') ?></span>
    </div>
    <div data-distance-key="<?= e($distanceKey) ?>" class="hidden mt-2 inline-flex w-fit items-center gap-1 rounded-full border border-accent-100 bg-accent-50 px-2.5 py-1 text-[11px] font-bold text-accent-700">
      <i data-lucide="navigation" class="h-3 w-3 shrink-0"></i>
      <span data-distance-label></span>
    </div>

    <?php if ($amenLine !== ''): ?>
      <div class="mt-2 flex flex-wrap gap-x-2 gap-y-1 text-[11px] text-slate-600 font-semibold leading-snug">
        <span class="inline-flex items-center gap-1"><i data-lucide="<?= e($amenIcon) ?>" class="w-3.5 h-3.5 text-slate-400 shrink-0"></i><?= e($amenLine) ?></span>
      </div>
    <?php else: ?>
      <div class="mt-2 text-[11px] text-slate-500 font-medium"><?= e($typeMap[$property['type']] ?? $property['type']) ?></div>
    <?php endif; ?>

    <div class="mt-auto pt-4 flex items-end justify-between gap-2 border-t border-slate-100">
      <div>
        <div class="text-[10px] text-slate-500 font-semibold uppercase tracking-wide">เริ่มต้น</div>
        <div class="text-lg font-extrabold text-forest-900 tabular-nums leading-none"><?= format_money($cardPrice) ?>
          <span class="text-[11px] font-semibold text-slate-500">/ คืน</span>
        </div>
      </div>
      <?php if ($rc > 0): ?>
      <div class="flex items-center gap-1 text-[12px] font-bold shrink-0">
        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-500 shrink-0"></i>
        <span class="text-slate-800"><?= number_format($ra, 1) ?></span>
        <span class="text-slate-400 font-semibold">(<?= $rc ?>)</span>
      </div>
      <?php else: ?>
      <span class="inline-flex items-center gap-1 text-[11px] text-slate-400 font-semibold shrink-0 leading-snug text-right max-w-[7rem]"><i data-lucide="message-circle" class="w-3.5 h-3.5 shrink-0 opacity-70"></i>ยังไม่มีรีวิว</span>
      <?php endif; ?>
    </div>
  </a>
</div>
