<?php
/** @var array $rows @var int $page @var int $totalPages @var int $total
 *  @var array $filter @var array $amenities @var array $zones @var array $types
 */
$mapRows = [];
foreach ($rows as $r) {
    $lat = (float)($r['latitude'] ?? 0);
    $lng = (float)($r['longitude'] ?? 0);
    if ($lat === 0.0 || $lng === 0.0) {
        continue;
    }
    $listingUid = (int)($r['listing_unit_id'] ?? 0);
    $title = ($listingUid > 0 && ($r['listing_unit_name'] ?? '') !== '')
        ? (string)$r['listing_unit_name']
        : (string)($r['name'] ?? '');
    $cover = ($listingUid > 0 && ($r['listing_unit_cover'] ?? '') !== '')
        ? (string)$r['listing_unit_cover']
        : (string)($r['cover_image'] ?? '');
    $price = ($listingUid > 0 && isset($r['listing_unit_price']))
        ? (float)$r['listing_unit_price']
        : (float)($r['min_price'] ?? 0);

    $priceShort = '';
    if ($price > 0) {
        if ($price >= 1000) {
            $priceShort = '฿' . rtrim(rtrim(number_format($price / 1000, 1), '0'), '.') . 'k';
        } else {
            $priceShort = '฿' . number_format($price);
        }
    }

    $mapRows[] = [
        'key' => (int)($r['id'] ?? 0) . '-' . $listingUid,
        'id' => (int)($r['id'] ?? 0),
        'unit_id' => $listingUid,
        'name' => $title,
        'property_name' => (string)($r['name'] ?? ''),
        'type' => (string)($r['type'] ?? ''),
        'type_label' => (string)($types[$r['type'] ?? ''] ?? ($r['type'] ?? '')),
        'zone' => (string)($r['zone'] ?? ''),
        'district' => (string)($r['district'] ?? ''),
        'price' => $price > 0 ? format_money($price) : '',
        'priceShort' => $priceShort,
        'cover' => upload_img($cover, 'thumb') ?: 'https://placehold.co/640x420?text=Paekan',
        'url' => $listingUid > 0
            ? url('/property/' . $r['slug'] . '?unit=' . $listingUid)
            : url('/property/' . $r['slug']),
        'lat' => $lat,
        'lng' => $lng,
    ];
}
$mapRowsJson = json_encode($mapRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
if ($mapRowsJson === false) {
    $mapRowsJson = '[]';
}
$typePage = is_array($type_page ?? null) ? $type_page : null;
$listingBaseUrl = url((string)($typePage['path'] ?? '/properties'));
$heroTitle = (string)($typePage['title'] ?? 'ค้นหาที่พักในกาญจนบุรี');
$heroSubtitle = (string)($typePage['subtitle'] ?? ('พบ ' . number_format($total) . ' รายการตามเงื่อนไข'));
$heroEyebrow = (string)($typePage['eyebrow'] ?? 'Paekan Stay Finder');
$heroIcon = (string)($typePage['icon'] ?? 'hotel');
$heroGradientKey = (string)($typePage['gradient'] ?? 'default');
$listingHeroTone = match ($heroGradientKey) {
  'pool' => 'pool',
  'camping' => 'camping',
  'raft' => 'raft',
  default => 'default',
};
$listingHeroClass = 'paekan-listing-hero paekan-listing-hero--' . $listingHeroTone;
$heroChips = is_array($typePage['chips'] ?? null) ? $typePage['chips'] : [
    ['icon' => 'hotel', 'label' => 'ที่พักตรวจสอบจริง'],
    ['icon' => 'map-pin', 'label' => 'เลือกตามโซน'],
    ['icon' => 'ticket', 'label' => 'ใช้คูปองได้'],
];
$typePageSeekUrl = '';
if ($typePage !== null) {
    $seekType = (string)($filter['type'] ?? '');
    if ($seekType === '') {
        $seekType = match ((string)($typePage['path'] ?? '')) {
            '/rafts' => 'raft',
            '/resorts' => 'resort',
            '/hotels' => 'hotel',
            '/pool-villas' => 'pool_villa',
            '/camping' => 'camping',
            default => '',
        };
    }
    $typePageSeekUrl = $seekType !== ''
        ? url('/guest-seek?type=' . urlencode($seekType))
        : url('/guest-seek');
}
$listingSeekUrl = $typePageSeekUrl !== '' ? $typePageSeekUrl : url('/guest-seek');
$subtypeTabs = is_array($typePage['subtype_tabs'] ?? null) ? $typePage['subtype_tabs'] : [];
$activeSubtype = (string)($filter['type'] ?? '');
$heroMobileLabel = trim((string)preg_replace('/\s*(ใน)?กาญจนบุรี.*$/u', '', $heroTitle));
if ($heroMobileLabel === '') {
    $heroMobileLabel = $heroTitle;
}
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIINfQhyPQK1JcCAXoo5CVgH/5n9X3DR1/8=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<style>
.paekan-listing-hero {
  background:
    radial-gradient(circle at 18% 18%, rgba(255, 255, 255, 0.18), transparent 28rem),
    radial-gradient(circle at 82% 10%, rgba(251, 191, 36, 0.20), transparent 22rem),
    linear-gradient(135deg, #293648 0%, #314056 45%, #14532d 100%);
  color: #fff;
  isolation: isolate;
  overflow: hidden;
  position: relative;
  box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.08);
}
.paekan-listing-hero--pool {
  background:
    radial-gradient(circle at 18% 18%, rgba(255, 255, 255, 0.18), transparent 28rem),
    radial-gradient(circle at 78% 12%, rgba(125, 211, 252, 0.28), transparent 24rem),
    linear-gradient(135deg, #075985 0%, #0e7490 48%, #047857 100%);
}
.paekan-listing-hero--camping {
  background:
    radial-gradient(circle at 18% 18%, rgba(236, 253, 245, 0.18), transparent 28rem),
    radial-gradient(circle at 78% 14%, rgba(250, 204, 21, 0.22), transparent 24rem),
    linear-gradient(135deg, #064e3b 0%, #3f6212 50%, #92400e 100%);
}
.paekan-listing-hero--raft {
  background:
    radial-gradient(circle at 18% 18%, rgba(255, 255, 255, 0.18), transparent 28rem),
    radial-gradient(circle at 78% 12%, rgba(56, 189, 248, 0.28), transparent 24rem),
    linear-gradient(135deg, #0c4a6e 0%, #0369a1 48%, #065f46 100%);
}
.paekan-listing-hero::after {
  background:
    linear-gradient(180deg, rgba(15, 23, 42, 0.10), rgba(15, 23, 42, 0.28)),
    repeating-linear-gradient(135deg, rgba(255, 255, 255, 0.045) 0 1px, transparent 1px 18px);
  content: "";
  inset: 0;
  pointer-events: none;
  position: absolute;
  z-index: 0;
}
.paekan-listing-hero > * {
  position: relative;
  z-index: 1;
}
[data-paekan-ai-hero] form {
  background: rgba(255, 255, 255, 0.13);
  border-color: rgba(255, 255, 255, 0.24);
  box-shadow: 0 18px 42px -28px rgba(0, 0, 0, 0.7);
}
[data-paekan-ai-hero] input {
  color: #fff;
}
[data-paekan-ai-hero] input::placeholder {
  color: rgba(255, 255, 255, 0.68);
}
@media (max-width: 767px) {
  .paekan-listing-hero--compact-mobile .paekan-listing-mobile-actions {
    margin-top: 0.625rem;
  }
}
.paekan-listing-sticky-bar {
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.96);
  border-bottom: 1px solid rgba(148, 163, 184, 0.35);
  box-shadow: 0 8px 24px -18px rgba(15, 23, 42, 0.45);
  left: 0;
  position: fixed;
  right: 0;
  top: 3.5rem;
  z-index: 35;
}
.paekan-listing-sticky-bar__inner {
  align-items: center;
  display: flex;
  gap: 0.5rem;
  justify-content: space-between;
  max-width: 80rem;
  margin: 0 auto;
  padding: 0.5rem 1rem;
}
.paekan-listing-sticky-bar__title {
  color: #0f172a;
  font-size: 0.8125rem;
  font-weight: 800;
  line-height: 1.25;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.paekan-listing-sticky-bar__actions {
  display: flex;
  flex-shrink: 0;
  gap: 0.375rem;
}
.paekan-listing-sticky-btn {
  align-items: center;
  background: #fff;
  border: 1px solid #cbd5e1;
  border-radius: 0.65rem;
  color: #334155;
  display: inline-flex;
  font-size: 0.6875rem;
  font-weight: 700;
  gap: 0.25rem;
  padding: 0.4rem 0.55rem;
}
.paekan-listing-mobile-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.75rem;
}
.paekan-listing-mobile-actions__btn {
  align-items: center;
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.24);
  border-radius: 0.75rem;
  color: #fff;
  display: inline-flex;
  font-size: 0.75rem;
  font-weight: 800;
  gap: 0.35rem;
  justify-content: center;
  padding: 0.55rem 0.65rem;
}
.paekan-listing-mobile-actions__btn--stretch {
  flex: 1;
}
.paekan-listing-mobile-actions__btn--primary {
  background: #fff;
  color: #0f172a;
  flex: 1;
}
.paekan-listing-mobile-actions__btn--ai.is-active {
  background: rgba(251, 191, 36, 0.95);
  border-color: rgba(251, 191, 36, 1);
  color: #0f172a;
}
.paekan-listing-subtype-scroll {
  -webkit-overflow-scrolling: touch;
  display: flex;
  flex-wrap: nowrap;
  gap: 0.5rem;
  margin-top: 0.75rem;
  overflow-x: auto;
  padding-bottom: 0.15rem;
  scroll-snap-type: x mandatory;
}
.paekan-listing-subtype-scroll > a {
  flex-shrink: 0;
  scroll-snap-align: start;
}
@media (min-width: 768px) {
  .paekan-listing-subtype-scroll {
    flex-wrap: wrap;
    overflow-x: visible;
    scroll-snap-type: none;
  }
}
.paekan-map-shell {
  display: grid;
  gap: 0;
}
@media (min-width: 1024px) {
  .paekan-map-shell {
    grid-template-columns: 19rem minmax(0, 1fr);
    height: min(86vh, 820px);
  }
}
@media (min-width: 1280px) {
  .paekan-map-shell {
    grid-template-columns: 22rem minmax(0, 1fr);
  }
}
.paekan-map-canvas-wrap {
  height: 70vh;
  min-height: 420px;
  position: relative;
  order: 1;
}
@media (min-width: 640px) {
  .paekan-map-canvas-wrap {
    height: 75vh;
    min-height: 540px;
  }
}
@media (min-width: 1024px) {
  .paekan-map-canvas-wrap {
    order: 2;
    min-height: 0;
    height: 100%;
  }
}
.paekan-map-canvas {
  inset: 0;
  position: absolute;
}
.paekan-map-panel.is-fullscreen,
.paekan-map-panel:fullscreen {
  background: #fff;
  border-radius: 0;
  border: 0;
  display: flex;
  flex-direction: column;
  height: 100vh;
  height: 100dvh;
  inset: 0;
  margin: 0;
  position: fixed;
  width: 100vw;
  z-index: 9999;
}
.paekan-map-panel:fullscreen .paekan-map-shell,
.paekan-map-panel.is-fullscreen .paekan-map-shell {
  flex: 1 1 auto;
  height: auto;
  min-height: 0;
}
@media (max-width: 1023px) {
  .paekan-map-panel:fullscreen .paekan-map-shell,
  .paekan-map-panel.is-fullscreen .paekan-map-shell {
    display: block;
    height: 100%;
  }
  .paekan-map-panel:fullscreen .paekan-map-canvas-wrap,
  .paekan-map-panel.is-fullscreen .paekan-map-canvas-wrap {
    display: block;
    height: auto;
    height: 100%;
    min-height: 0;
    order: 1;
  }
  .paekan-map-panel:fullscreen .paekan-map-panel-head,
  .paekan-map-panel.is-fullscreen .paekan-map-panel-head,
  .paekan-map-panel:fullscreen .paekan-map-side,
  .paekan-map-panel.is-fullscreen .paekan-map-side {
    display: none;
  }
}
.paekan-map-panel.is-fullscreen .paekan-map-side-list {
  overscroll-behavior: contain;
}
body.paekan-map-lock {
  overflow: hidden;
}
.paekan-map-side {
  background: #fff;
  display: flex;
  flex-direction: column;
  min-height: 0;
  border-top: 1px solid rgb(241 245 249);
  order: 2;
}
@media (min-width: 1024px) {
  .paekan-map-side {
    order: 1;
    border-top: 0;
    border-right: 1px solid rgb(241 245 249);
  }
}
.paekan-map-side-head {
  border-bottom: 1px solid rgb(241 245 249);
  padding: 12px 14px;
}
.paekan-map-side-list {
  flex: 1 1 auto;
  overflow-y: auto;
  padding: 10px;
  scroll-behavior: smooth;
  scrollbar-gutter: stable;
}
.paekan-map-side-list::-webkit-scrollbar {
  width: 8px;
}
.paekan-map-side-list::-webkit-scrollbar-thumb {
  background: rgb(203 213 225 / 0.85);
  border-radius: 9999px;
}
.paekan-mobile-map-top,
.paekan-mobile-map-card {
  display: none;
}
@media (max-width: 1023px) {
  .paekan-map-panel.is-fullscreen .paekan-mobile-map-top,
  .paekan-map-panel:fullscreen .paekan-mobile-map-top {
    display: block;
    left: 12px;
    position: absolute;
    right: 12px;
    top: max(10px, env(safe-area-inset-top));
    z-index: 900;
  }
  .paekan-mobile-map-search {
    align-items: center;
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 9999px;
    box-shadow: 0 18px 38px -24px rgba(15, 23, 42, 0.55);
    display: grid;
    gap: 8px;
    grid-template-columns: 38px minmax(0, 1fr) 38px;
    padding: 7px;
  }
  .paekan-mobile-map-icon-btn {
    align-items: center;
    background: #fff;
    border: 1px solid rgb(226 232 240);
    border-radius: 9999px;
    color: #0f172a;
    display: inline-flex;
    height: 38px;
    justify-content: center;
    width: 38px;
  }
  .paekan-mobile-map-meta {
    min-width: 0;
  }
  .paekan-mobile-map-meta strong {
    color: #0f172a;
    display: block;
    font-size: 13px;
    line-height: 1.15;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .paekan-mobile-map-meta span {
    color: #64748b;
    display: block;
    font-size: 11px;
    font-weight: 600;
    margin-top: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .paekan-map-panel.is-fullscreen .paekan-mobile-map-card,
  .paekan-map-panel:fullscreen .paekan-mobile-map-card {
    bottom: max(12px, env(safe-area-inset-bottom));
    display: block;
    left: 12px;
    position: absolute;
    right: 12px;
    z-index: 900;
  }
  .paekan-mobile-selected-card {
    background: rgba(255, 255, 255, 0.97);
    border: 1px solid rgba(226, 232, 240, 0.92);
    border-radius: 20px;
    box-shadow: 0 24px 54px -26px rgba(15, 23, 42, 0.65);
    display: grid;
    gap: 10px;
    grid-template-columns: 6.5rem minmax(0, 1fr);
    overflow: hidden;
  }
  .paekan-mobile-selected-card img {
    height: 118px;
    object-fit: cover;
    width: 100%;
  }
  .paekan-mobile-selected-body {
    min-width: 0;
    padding: 10px 10px 10px 0;
  }
  .paekan-map-panel.is-fullscreen .leaflet-control-zoom,
  .paekan-map-panel:fullscreen .leaflet-control-zoom {
    margin-top: 82px !important;
  }
  .paekan-map-panel.is-fullscreen .leaflet-control-attribution,
  .paekan-map-panel:fullscreen .leaflet-control-attribution {
    display: none;
  }
  .paekan-map-panel.is-fullscreen .paekan-map-hint,
  .paekan-map-panel:fullscreen .paekan-map-hint {
    display: none;
  }
}

/* ======== CTA banner: ค้นหาที่พักบนแผนที่ ======== */
.paekan-map-cta {
  align-items: stretch;
  background: linear-gradient(120deg, #ecfeff 0%, #f0fdfa 60%, #fef9c3 100%);
  border: 1px solid rgb(20 184 166 / 0.18);
  border-radius: 22px;
  box-shadow: 0 24px 60px -32px rgba(15, 23, 42, 0.45);
  cursor: pointer;
  display: grid;
  gap: 14px;
  grid-template-columns: 6rem minmax(0, 1fr);
  overflow: hidden;
  padding: 12px;
  text-align: left;
  transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
  width: 100%;
}
@media (min-width: 640px) {
  .paekan-map-cta {
    gap: 16px;
    grid-template-columns: 8.25rem minmax(0, 1fr) auto;
    padding: 14px;
  }
}
.paekan-map-cta:hover {
  border-color: rgb(20 184 166 / 0.45);
  box-shadow: 0 28px 70px -34px rgba(15, 118, 110, 0.55);
  transform: translateY(-2px);
}
.paekan-map-cta-thumb {
  align-items: center;
  background:
    radial-gradient(circle at 30% 20%, #fef3c7 0 14px, transparent 15px),
    radial-gradient(circle at 80% 60%, #bbf7d0 0 22px, transparent 23px),
    radial-gradient(circle at 50% 80%, #bae6fd 0 18px, transparent 19px),
    linear-gradient(140deg, #f1f5f9 0%, #e2e8f0 100%);
  background-color: #f8fafc;
  border-radius: 16px;
  display: flex;
  height: 100%;
  justify-content: center;
  min-height: 92px;
  position: relative;
}
.paekan-map-cta-thumb::before,
.paekan-map-cta-thumb::after {
  background: rgb(250 204 21 / 0.55);
  content: "";
  height: 4px;
  position: absolute;
}
.paekan-map-cta-thumb::before {
  left: 8%;
  right: 18%;
  top: 32%;
  transform: rotate(-9deg);
}
.paekan-map-cta-thumb::after {
  left: 22%;
  right: 8%;
  top: 64%;
  transform: rotate(7deg);
}
.paekan-map-cta-pin {
  align-items: center;
  background: #ef4444;
  border-radius: 50% 50% 50% 0;
  box-shadow: 0 8px 22px -10px rgba(15, 23, 42, 0.6);
  color: #fff;
  display: flex;
  height: 38px;
  justify-content: center;
  position: relative;
  transform: rotate(-45deg);
  width: 38px;
  z-index: 2;
}
.paekan-map-cta-pin > * {
  transform: rotate(45deg);
}
.paekan-map-cta-pin svg {
  height: 16px;
  width: 16px;
}
.paekan-map-cta-body {
  align-self: center;
}

/* ======== Mobile: 3 action tiles (แผนที่ | ช่วยหา | AI) ======== */
.paekan-listing-action-tiles {
  display: grid;
  gap: 0.5rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}
.paekan-action-tile {
  align-items: center;
  border: 1px solid rgb(148 163 184 / 0.35);
  border-radius: 14px;
  box-shadow: 0 10px 28px -18px rgba(15, 23, 42, 0.35);
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  justify-content: center;
  min-height: 4.5rem;
  padding: 0.55rem 0.35rem;
  text-align: center;
  transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.paekan-action-tile:active {
  transform: scale(0.98);
}
.paekan-action-tile__icon {
  align-items: center;
  border-radius: 10px;
  display: flex;
  height: 2rem;
  justify-content: center;
  width: 2rem;
}
.paekan-action-tile__icon svg {
  height: 1.125rem;
  width: 1.125rem;
}
.paekan-action-tile__label {
  color: #0f172a;
  font-size: 0.6875rem;
  font-weight: 800;
  line-height: 1.2;
}
.paekan-action-tile__sub {
  color: #64748b;
  font-size: 0.5625rem;
  font-weight: 600;
  line-height: 1.2;
}
.paekan-action-tile--map {
  background: linear-gradient(145deg, #ecfeff 0%, #f0fdfa 55%, #e0f2fe 100%);
  border-color: rgb(20 184 166 / 0.25);
}
.paekan-action-tile--map .paekan-action-tile__icon {
  background: rgb(255 255 255 / 0.85);
  color: #0d9488;
}
.paekan-action-tile--seek {
  background: linear-gradient(145deg, #fff7ed 0%, #fef3c7 55%, #fde68a 100%);
  border-color: rgb(245 158 11 / 0.28);
}
.paekan-action-tile--seek .paekan-action-tile__icon {
  background: rgb(255 255 255 / 0.85);
  color: #d97706;
}
.paekan-action-tile--ai {
  background: linear-gradient(145deg, #fefce8 0%, #fef9c3 55%, #fde68a 100%);
  border-color: rgb(234 179 8 / 0.3);
}
.paekan-action-tile--ai .paekan-action-tile__icon {
  background: rgb(255 255 255 / 0.85);
  color: #ca8a04;
}
.paekan-action-tile--ai.is-active {
  border-color: #eab308;
  box-shadow: 0 12px 32px -16px rgba(234, 179, 8, 0.55);
}
@media (min-width: 768px) {
  .paekan-listing-action-tiles {
    gap: 0.75rem;
  }
  .paekan-action-tile {
    min-height: 5.25rem;
    padding: 0.75rem 0.5rem;
  }
  .paekan-action-tile:hover {
    transform: translateY(-1px);
  }
  .paekan-action-tile__icon {
    height: 2.25rem;
    width: 2.25rem;
  }
  .paekan-action-tile__label {
    font-size: 0.8125rem;
  }
  .paekan-action-tile__sub {
    font-size: 0.6875rem;
  }
}
.paekan-listing-ai-expand {
  background: linear-gradient(135deg, #0c4a6e 0%, #0369a1 48%, #065f46 100%);
  border-radius: 16px;
  padding: 0.65rem;
}
@media (min-width: 768px) {
  .paekan-listing-ai-expand {
    background: #fff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 8px 24px -16px rgba(15, 23, 42, 0.2);
    padding: 0.75rem;
  }
}
.paekan-listing-ai-expand [data-role="paekan-ai-hero-form"] {
  align-items: center;
  background: rgb(255 255 255 / 0.12);
  border: 1px solid rgb(255 255 255 / 0.22);
  border-radius: 12px;
  display: flex;
  gap: 0.5rem;
  padding: 0.35rem;
}
@media (min-width: 768px) {
  .paekan-listing-ai-expand [data-role="paekan-ai-hero-form"] {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 0.5rem;
  }
}
.paekan-listing-ai-expand input {
  background: transparent;
  color: #fff;
  flex: 1;
  font-size: 0.8125rem;
  min-width: 0;
  outline: none;
  padding: 0.5rem 0.35rem;
}
@media (min-width: 768px) {
  .paekan-listing-ai-expand input {
    color: #0f172a;
    font-size: 0.875rem;
  }
  .paekan-listing-ai-expand input::placeholder {
    color: #94a3b8;
  }
}
.paekan-listing-ai-expand input::placeholder {
  color: rgb(255 255 255 / 0.6);
}
.paekan-listing-ai-expand [data-role="paekan-ai-submit"] {
  background: #fbbf24;
  border-radius: 10px;
  color: #0f172a;
  flex-shrink: 0;
  font-size: 0.75rem;
  font-weight: 800;
  padding: 0.5rem 0.875rem;
}
@media (min-width: 768px) {
  .paekan-listing-ai-expand [data-role="paekan-ai-submit"] {
    font-size: 0.8125rem;
    padding: 0.625rem 1rem;
  }
}

/* ======== Price-tag markers (Agoda-like) ======== */
.paekan-price-marker {
  align-items: center;
  background: #fff;
  border: 1.5px solid rgb(15 23 42 / 0.85);
  border-radius: 9999px;
  box-shadow: 0 8px 22px -10px rgba(15, 23, 42, 0.55);
  color: #0f172a;
  display: inline-flex;
  font-size: 12px;
  font-weight: 800;
  gap: 4px;
  padding: 5px 11px;
  transition: transform 0.18s ease, background 0.18s ease, color 0.18s ease, border-color 0.18s ease;
  white-space: nowrap;
}
.paekan-price-marker:hover {
  transform: translateY(-1px) scale(1.03);
  border-color: #0d9488;
}
.paekan-price-marker svg {
  height: 12px;
  width: 12px;
}
.paekan-price-marker.is-active {
  background: #0f766e;
  border-color: #0f766e;
  color: #fff;
  z-index: 1000;
}
.paekan-price-marker.is-visited {
  background: #f8fafc;
  color: #475569;
}
.paekan-marker-anchor {
  background: transparent !important;
  border: 0 !important;
}

/* ======== Mini side cards ======== */
.paekan-mini-card {
  background: #fff;
  border: 1px solid rgb(226 232 240);
  border-radius: 16px;
  box-shadow: 0 6px 18px -14px rgba(15, 23, 42, 0.4);
  cursor: pointer;
  display: grid;
  gap: 10px;
  grid-template-columns: 6.5rem minmax(0, 1fr);
  margin-bottom: 10px;
  overflow: hidden;
  transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
}
.paekan-mini-card:hover {
  border-color: rgb(20 184 166 / 0.55);
  box-shadow: 0 14px 28px -16px rgba(15, 118, 110, 0.45);
  transform: translateY(-1px);
}
.paekan-mini-card.is-active {
  border-color: #0f766e;
  box-shadow: 0 0 0 2px rgba(20, 184, 166, 0.45), 0 14px 28px -16px rgba(15, 118, 110, 0.5);
}
.paekan-mini-card .mini-thumb {
  background: #e2e8f0;
  height: 100%;
  min-height: 92px;
  position: relative;
}
.paekan-mini-card .mini-thumb img {
  height: 100%;
  left: 0;
  object-fit: cover;
  position: absolute;
  top: 0;
  width: 100%;
}
.paekan-mini-card .mini-body {
  padding: 10px 12px 12px 0;
}
.paekan-map-panel .leaflet-container {
  font-family: inherit;
  height: 100% !important;
  width: 100% !important;
}
.paekan-map-panel .leaflet-container,
.paekan-map-panel .leaflet-pane,
.paekan-map-panel .leaflet-tile,
.paekan-map-panel .leaflet-marker-icon,
.paekan-map-panel .leaflet-marker-shadow,
.paekan-map-panel .leaflet-tile-container,
.paekan-map-panel .leaflet-pane > svg,
.paekan-map-panel .leaflet-pane > canvas,
.paekan-map-panel .leaflet-zoom-box,
.paekan-map-panel .leaflet-image-layer,
.paekan-map-panel .leaflet-layer {
  position: absolute;
  left: 0;
  top: 0;
}
.paekan-map-panel .leaflet-container {
  overflow: hidden;
  -webkit-tap-highlight-color: transparent;
}
.paekan-map-panel .leaflet-tile,
.paekan-map-panel .leaflet-marker-icon,
.paekan-map-panel .leaflet-marker-shadow {
  user-select: none;
  -webkit-user-drag: none;
}
.paekan-map-panel .leaflet-tile {
  filter: inherit;
  max-width: none !important;
}
.paekan-map-panel .leaflet-tile-pane {
  z-index: 200;
}
.paekan-map-panel .leaflet-overlay-pane {
  z-index: 400;
}
.paekan-map-panel .leaflet-marker-pane {
  z-index: 600;
}
.paekan-map-panel .leaflet-tooltip-pane {
  z-index: 650;
}
.paekan-map-panel .leaflet-popup-pane {
  z-index: 700;
}
.paekan-map-panel .leaflet-top,
.paekan-map-panel .leaflet-bottom {
  position: absolute;
  z-index: 1000;
  pointer-events: none;
}
.paekan-map-panel .leaflet-top { top: 0; }
.paekan-map-panel .leaflet-right { right: 0; }
.paekan-map-panel .leaflet-bottom { bottom: 0; }
.paekan-map-panel .leaflet-left { left: 0; }
.paekan-map-panel .leaflet-control {
  position: relative;
  z-index: 800;
  pointer-events: auto;
  float: left;
  clear: both;
}
.paekan-map-panel .leaflet-right .leaflet-control { float: right; }
.paekan-map-panel .leaflet-control-zoom {
  border: 0 !important;
  border-radius: 14px !important;
  box-shadow: 0 12px 30px -16px rgba(15, 23, 42, 0.5);
  margin: 14px !important;
  overflow: hidden;
}
.paekan-map-panel .leaflet-control-zoom a {
  background: #fff;
  border-bottom: 1px solid rgb(226 232 240) !important;
  color: #0f172a;
  font-size: 18px;
  height: 38px !important;
  line-height: 38px !important;
  width: 38px !important;
}
.paekan-map-panel .leaflet-control-zoom a:hover {
  background: #f0fdfa;
  color: #0f766e;
}
.paekan-map-panel .leaflet-control-zoom a:last-child {
  border-bottom: 0 !important;
}
.paekan-map-panel .leaflet-control-attribution {
  background: rgba(255, 255, 255, 0.85) !important;
  border-radius: 8px 0 0 0;
  font-size: 10px;
  margin: 0 !important;
  padding: 2px 6px !important;
}
.paekan-map-panel .leaflet-popup-content-wrapper {
  border-radius: 18px;
  overflow: hidden;
  box-shadow: 0 22px 50px -24px rgba(15, 23, 42, 0.45);
}
.paekan-map-panel .leaflet-popup-content {
  margin: 0;
  width: 240px !important;
}
.paekan-map-marker {
  align-items: center;
  background: #0f766e;
  border: 2px solid #fff;
  border-radius: 9999px;
  box-shadow: 0 12px 28px -12px rgba(15, 23, 42, 0.7);
  color: #fff;
  display: flex;
  height: 34px;
  justify-content: center;
  width: 34px;
}
.paekan-map-marker svg {
  height: 17px;
  width: 17px;
}
.paekan-user-marker {
  align-items: center;
  background: #f59e0b;
  border: 3px solid #fff;
  border-radius: 9999px;
  box-shadow: 0 0 0 7px rgba(245, 158, 11, 0.18), 0 12px 28px -12px rgba(15, 23, 42, 0.7);
  color: #fff;
  display: flex;
  height: 28px;
  justify-content: center;
  width: 28px;
}
</style>
<section class="<?= e($listingHeroClass) ?> paekan-listing-hero--compact-mobile"
         x-data="listingHeroMobile()"
         @scroll.window="stickyVisible = window.scrollY > 120">
  <div x-show="stickyVisible"
       x-cloak
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0 -translate-y-2"
       x-transition:enter-end="opacity-100 translate-y-0"
       class="paekan-listing-sticky-bar md:hidden"
       role="region"
       aria-label="เมนูค้นหาที่พัก">
    <div class="paekan-listing-sticky-bar__inner">
      <p class="paekan-listing-sticky-bar__title"><?= e($heroMobileLabel) ?> · <?= number_format($total) ?> รายการ</p>
      <div class="paekan-listing-sticky-bar__actions">
        <button type="button" class="paekan-listing-sticky-btn" @click="openFilters()">
          <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i> ฟิลเตอร์
        </button>
        <button type="button" class="paekan-listing-sticky-btn" @click="openMap()">
          <i data-lucide="map" class="w-3.5 h-3.5"></i> แผนที่
        </button>
      </div>
    </div>
  </div>
  <div class="absolute inset-0 opacity-20" aria-hidden="true">
    <div class="absolute -right-16 -top-16 h-56 w-56 rounded-full bg-white blur-3xl"></div>
    <div class="absolute left-1/4 top-20 h-32 w-32 rounded-full bg-amber-300 blur-3xl"></div>
  </div>
  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 py-4 md:py-12">
    <div class="hidden md:inline-flex items-center gap-2 rounded-full bg-white/12 px-3 py-1 text-xs font-bold ring-1 ring-white/20 backdrop-blur">
      <i data-lucide="<?= e($heroIcon) ?>" class="w-3.5 h-3.5"></i><?= e($heroEyebrow) ?>
    </div>

    <!-- Mobile: compact title + count -->
    <div class="md:hidden flex items-center gap-2.5">
      <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-white/15 ring-1 ring-white/20">
        <i data-lucide="<?= e($heroIcon) ?>" class="w-5 h-5"></i>
      </span>
      <h1 class="min-w-0 flex-1 text-xl font-extrabold tracking-tight leading-tight truncate"><?= e($heroTitle) ?></h1>
      <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-white px-2.5 py-1 text-[10px] font-extrabold text-slate-900 shadow-sm">
        <i data-lucide="list-checks" class="w-3 h-3 text-emerald-600"></i><?= number_format($total) ?>
      </span>
    </div>

    <!-- Desktop: full title -->
    <h1 class="mt-3 hidden md:flex text-3xl md:text-4xl font-extrabold tracking-tight items-center gap-3">
      <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white/15 ring-1 ring-white/20 shadow-xl shadow-slate-950/10">
        <i data-lucide="<?= e($heroIcon) ?>" class="w-7 h-7"></i>
      </span>
      <span><?= e($heroTitle) ?></span>
    </h1>

    <p class="hidden md:block text-white/88 mt-3 max-w-3xl leading-relaxed"><?= e($heroSubtitle) ?></p>

    <div class="mt-4 hidden md:flex flex-wrap gap-2">
      <?php foreach ($heroChips as $chip): ?>
      <span class="inline-flex items-center gap-1.5 rounded-full bg-white/12 px-3 py-1.5 text-xs font-bold ring-1 ring-white/20 backdrop-blur">
        <i data-lucide="<?= e((string)($chip['icon'] ?? 'sparkles')) ?>" class="w-3.5 h-3.5"></i><?= e((string)($chip['label'] ?? '')) ?>
      </span>
      <?php endforeach; ?>
      <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-extrabold text-slate-900 shadow-sm">
        <i data-lucide="list-checks" class="w-3.5 h-3.5 text-emerald-600"></i>พบ <?= number_format($total) ?> รายการ
      </span>
    </div>

    <?php if ($subtypeTabs !== []): ?>
    <div class="paekan-listing-subtype-scroll md:mt-4 md:flex-wrap md:overflow-visible">
      <?php foreach ($subtypeTabs as $tabKey => $tabLabel):
        $tabHref = url('/stays' . ($tabKey !== '' ? '?type=' . urlencode((string)$tabKey) : ''));
        $tabActive = $activeSubtype === (string)$tabKey;
      ?>
      <a href="<?= e($tabHref) ?>"
         class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold transition <?= $tabActive ? 'bg-white text-sky-800 shadow-md' : 'bg-white/12 text-white ring-1 ring-white/25 hover:bg-white/20' ?>">
        <?= e((string)$tabLabel) ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

  </div>
</section>
<script src="<?= e(asset('js/paekan-ai-hero.js')) ?>"></script>
<script>
function listingHeroMobile() {
  return {
    aiOpen: false,
    stickyVisible: false,
    openFilters() {
      const toggle = document.getElementById('listing-filter-toggle');
      if (toggle) {
        toggle.click();
      }
      const panel = document.getElementById('listing-filters');
      if (panel) {
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    },
    openMap() {
      window.dispatchEvent(new CustomEvent('paekan-listing-open-map'));
      setTimeout(function () {
        const results = document.getElementById('listing-results');
        if (results) {
          results.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }, 80);
    },
  };
}
</script>
<script>
function propertyMapSearch(rows) {
  return {
    view: 'list',
    mode: 'compact',
    aiOpen: false,
    mapRows: Array.isArray(rows) ? rows : [],
    map: null,
    markers: [],
    markerByKey: {},
    activeKey: null,
    hoverKey: null,
    userMarker: null,
    userLocation: null,
    locating: false,
    statusMessage: '',

    init() {
      this.refreshIcons();
      window.addEventListener('paekan-listing-open-map', () => {
        this.setView('map');
        this.$nextTick(() => {
          this.fixMapSize();
          this.refreshIcons();
        });
      });
      document.addEventListener('fullscreenchange', () => {
        this.isFullscreen = !!document.fullscreenElement;
        document.body.classList.toggle('paekan-map-lock', this.isFullscreen);
        if (this.isFullscreen) this.ensureActiveItem();
        this.fixMapSize();
        this.refreshIcons();
      });
      document.addEventListener('webkitfullscreenchange', () => {
        this.isFullscreen = !!document.webkitFullscreenElement;
        document.body.classList.toggle('paekan-map-lock', this.isFullscreen);
        if (this.isFullscreen) this.ensureActiveItem();
        this.fixMapSize();
        this.refreshIcons();
      });
    },

    isFullscreen: false,

    toggleFullscreen() {
      const panel = this.$refs.mapPanel;
      if (!panel) return;
      const nativeFullscreenElement = document.fullscreenElement || document.webkitFullscreenElement;

      if (this.isFullscreen || nativeFullscreenElement) {
        this.isFullscreen = false;
        document.body.classList.remove('paekan-map-lock');
        if (nativeFullscreenElement && document.exitFullscreen) {
          document.exitFullscreen().catch(() => {});
        } else if (nativeFullscreenElement && document.webkitExitFullscreen) {
          document.webkitExitFullscreen();
        }
        this.fixMapSize();
        this.refreshIcons();
        return;
      }

      const requestFullscreen = panel.requestFullscreen || panel.webkitRequestFullscreen;
      if (requestFullscreen) {
        const result = requestFullscreen.call(panel);
        if (result && typeof result.catch === 'function') {
          result.catch(() => this.enterCssFullscreen());
        } else {
          setTimeout(() => {
            if (!document.fullscreenElement && !document.webkitFullscreenElement) {
              this.enterCssFullscreen();
            }
          }, 250);
        }
      } else {
        this.enterCssFullscreen();
      }
    },

    enterCssFullscreen() {
      this.isFullscreen = true;
      document.body.classList.add('paekan-map-lock');
      this.ensureActiveItem();
      this.$nextTick(() => {
        this.fixMapSize();
        this.refreshIcons();
      });
    },

    ensureActiveItem() {
      if (!this.activeKey && this.mapRows.length) {
        this.activeKey = this.mapRows[0].key;
        this.updateMarkerStyles();
      }
    },

    activeItem() {
      return this.mapRows.find((row) => row.key === this.activeKey) || this.mapRows[0] || null;
    },

    setView(next) {
      this.view = next;
      if (next === 'map') {
        this.$nextTick(() => {
          this.initMap();
          this.fixMapSize();
          this.refreshIcons();
        });
      }
    },

    activateCard(key, opts) {
      const item = this.mapRows.find((row) => row.key === key);
      if (!item) return;
      this.activeKey = key;
      this.updateMarkerStyles();
      if (this.map) {
        const target = this.markerByKey[key];
        if (target) target.openPopup();
        if (opts && opts.fly) {
          this.map.flyTo([item.lat, item.lng], Math.max(this.map.getZoom(), 14), { duration: 0.5 });
        }
      }
      this.refreshIcons();
    },

    hoverCard(key) {
      this.hoverKey = key;
      this.updateMarkerStyles();
    },

    updateMarkerStyles() {
      Object.entries(this.markerByKey).forEach(([key, marker]) => {
        const el = marker.getElement();
        if (!el) return;
        const tag = el.querySelector('.paekan-price-marker');
        if (!tag) return;
        const isActive = key === this.activeKey || key === this.hoverKey;
        tag.classList.toggle('is-active', isActive);
      });
    },

    initMap() {
      if (this.map || !this.$refs.map || !window.L || !this.mapRows.length) {
        if (this.map) this.fixMapSize();
        return;
      }

      this.map = L.map(this.$refs.map, {
        scrollWheelZoom: true,
        doubleClickZoom: true,
        touchZoom: true,
        boxZoom: true,
        zoomControl: true,
        zoomSnap: 0.5,
      });

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
      }).addTo(this.map);

      const bounds = [];
      this.mapRows.forEach((item) => {
        const marker = L.marker([item.lat, item.lng], {
          icon: this.markerIcon(item),
          title: item.name,
          riseOnHover: true,
        }).addTo(this.map);
        marker.bindPopup(this.popupHtml(item), { maxWidth: 260, className: 'paekan-map-popup' });
        marker.on('click', () => {
          this.activeKey = item.key;
          this.updateMarkerStyles();
          this.scrollToCard(item.key);
          this.refreshIcons();
        });
        marker.on('mouseover', () => {
          this.hoverKey = item.key;
          this.updateMarkerStyles();
        });
        marker.on('mouseout', () => {
          this.hoverKey = null;
          this.updateMarkerStyles();
        });
        this.markers.push({ item, marker });
        this.markerByKey[item.key] = marker;
        bounds.push([item.lat, item.lng]);
      });

      if (bounds.length === 1) {
        this.map.setView(bounds[0], 12);
      } else {
        this.map.fitBounds(bounds, { padding: [36, 36], maxZoom: 13 });
      }

      this.fixMapSize();
      this.refreshIcons();
    },

    fixMapSize() {
      if (!this.map) return;
      [0, 80, 220, 520].forEach((delay) => {
        setTimeout(() => {
          this.map.invalidateSize(true);
        }, delay);
      });
    },

    markerIcon(item) {
      const icon = this.iconForType(item.type);
      const priceLabel = item.priceShort || item.price || 'ดูราคา';
      const approxWidth = 36 + (priceLabel.length * 7);
      return L.divIcon({
        className: 'paekan-marker-anchor',
        html:
          '<div class="paekan-price-marker" data-marker-key="' + this.escapeAttr(item.key) + '">' +
            '<i data-lucide="' + icon + '"></i>' +
            '<span>' + this.escapeHtml(priceLabel) + '</span>' +
          '</div>',
        iconSize: [approxWidth, 28],
        iconAnchor: [approxWidth / 2, 14],
        popupAnchor: [0, -14],
      });
    },

    scrollToCard(key) {
      const list = document.querySelector('[data-mini-list]');
      if (!list) return;
      const card = list.querySelector('[data-mini-card-key="' + key + '"]');
      if (!card) return;
      const offset = card.offsetTop - 12;
      list.scrollTo({ top: offset, behavior: 'smooth' });
    },

    userIcon() {
      return L.divIcon({
        className: '',
        html: '<div class="paekan-user-marker"><i data-lucide="navigation"></i></div>',
        iconSize: [28, 28],
        iconAnchor: [14, 14],
      });
    },

    iconForType(type) {
      const map = {
        raft: 'anchor',
        pool_villa: 'home',
        resort: 'building-2',
        homestay: 'house',
        house: 'home',
        hotel: 'hotel',
        camping: 'tent',
      };
      return map[type] || 'map-pin';
    },

    popupHtml(item) {
      const distance = item.distanceLabel
        ? '<span class="inline-flex items-center gap-1 rounded-full bg-accent-50 px-2 py-1 text-[11px] font-bold text-accent-700"><i data-lucide="navigation" class="h-3 w-3"></i>' + this.escapeHtml(item.distanceLabel) + '</span>'
        : '';
      const zone = item.zone || item.district || 'กาญจนบุรี';
      const price = item.price
        ? '<div class="text-base font-extrabold text-forest-900">' + this.escapeHtml(item.price) + '<span class="text-[11px] font-semibold text-slate-500"> / คืน</span></div>'
        : '';
      return '' +
        '<article class="overflow-hidden bg-white text-slate-800">' +
          '<img src="' + this.escapeAttr(item.cover) + '" alt="" class="h-28 w-full object-cover">' +
          '<div class="space-y-2 p-3">' +
            '<div class="flex items-center gap-1.5">' +
              '<span class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2 py-0.5 text-[11px] font-bold text-primary-700"><i data-lucide="' + this.iconForType(item.type) + '" class="h-3 w-3"></i>' + this.escapeHtml(item.type_label || 'ที่พัก') + '</span>' +
              distance +
            '</div>' +
            '<h3 class="line-clamp-2 text-sm font-extrabold leading-snug text-slate-950">' + this.escapeHtml(item.name) + '</h3>' +
            '<div class="flex items-center gap-1 text-xs text-slate-500"><i data-lucide="map-pin" class="h-3.5 w-3.5 text-forest-700"></i><span>' + this.escapeHtml(zone) + '</span></div>' +
            price +
            '<a href="' + this.escapeAttr(item.url) + '" class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-accent-500 px-3 py-2 text-xs font-bold text-white hover:bg-accent-600"><i data-lucide="external-link" class="h-3.5 w-3.5"></i>ดูรายละเอียด</a>' +
          '</div>' +
        '</article>';
    },

    locateMe() {
      if (!navigator.geolocation) {
        this.statusMessage = 'เบราว์เซอร์นี้ยังไม่รองรับการใช้ตำแหน่ง จึงแสดงระยะทางจากคุณไม่ได้';
        return;
      }
      if (!this.map) {
        this.setView('map');
      }
      this.locating = true;
      this.statusMessage = 'กำลังขออนุญาตใช้ตำแหน่งจากเบราว์เซอร์';
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          this.locating = false;
          this.userLocation = {
            lat: pos.coords.latitude,
            lng: pos.coords.longitude,
          };
          this.statusMessage = 'แสดงระยะทางโดยประมาณจากตำแหน่งของคุณแล้ว';
          this.applyDistances();
        },
        () => {
          this.locating = false;
          this.statusMessage = 'ยังไม่สามารถแสดงระยะทางจากคุณได้ เพราะไม่ได้รับอนุญาตใช้ตำแหน่ง';
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 }
      );
    },

    applyDistances() {
      if (!this.userLocation) return;
      this.mapRows.forEach((item) => {
        const km = this.distanceKm(this.userLocation.lat, this.userLocation.lng, item.lat, item.lng);
        item.distanceKm = km;
        item.distanceLabel = km < 1 ? 'ห่างจากคุณ ' + Math.round(km * 1000) + ' ม.' : 'ห่างจากคุณ ' + km.toFixed(1) + ' กม.';
        this.updateDistanceBadges(item);
      });

      if (this.map && window.L) {
        const ll = [this.userLocation.lat, this.userLocation.lng];
        if (this.userMarker) {
          this.userMarker.setLatLng(ll);
        } else {
          this.userMarker = L.marker(ll, { icon: this.userIcon(), title: 'ตำแหน่งของคุณ' }).addTo(this.map);
          this.userMarker.bindPopup('<div class="px-3 py-2 text-sm font-bold text-slate-800">ตำแหน่งของคุณ</div>');
        }
        this.markers.forEach(({ item, marker }) => marker.setPopupContent(this.popupHtml(item)));
        const bounds = this.mapRows.map((item) => [item.lat, item.lng]);
        bounds.push(ll);
        this.map.fitBounds(bounds, { padding: [36, 36], maxZoom: 13 });
      }
      this.refreshIcons();
    },

    updateDistanceBadges(item) {
      document.querySelectorAll('[data-distance-key]').forEach((el) => {
        if (el.getAttribute('data-distance-key') !== item.key) return;
        const label = el.querySelector('[data-distance-label]');
        if (label) label.textContent = item.distanceLabel;
        el.classList.remove('hidden');
        el.classList.add('flex');
      });
    },

    distanceKm(lat1, lng1, lat2, lng2) {
      const toRad = (v) => v * Math.PI / 180;
      const r = 6371;
      const dLat = toRad(lat2 - lat1);
      const dLng = toRad(lng2 - lng1);
      const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
        Math.sin(dLng / 2) * Math.sin(dLng / 2);
      return r * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    },

    refreshIcons() {
      if (!window.lucide) return;
      this.$nextTick(() => {
        window.lucide.createIcons();
        requestAnimationFrame(() => window.lucide.createIcons());
      });
    },

    escapeHtml(value) {
      return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
      }[ch]));
    },

    escapeAttr(value) {
      return this.escapeHtml(value).replace(/`/g, '&#096;');
    },
  };
}
</script>

<section class="max-w-7xl mx-auto px-4 sm:px-6 py-8 grid grid-cols-1 lg:grid-cols-12 gap-6">

  <!-- ===== SIDEBAR FILTER ===== -->
  <aside id="listing-filters" class="lg:col-span-3 scroll-mt-28 md:scroll-mt-36" x-data="{open:false}">
    <button id="listing-filter-toggle" type="button" @click="open=!open" class="lg:hidden w-full mb-3 px-4 py-2.5 bg-white border border-slate-300 rounded-lg flex items-center justify-between font-semibold">
      <span class="flex items-center gap-2"><i data-lucide="sliders-horizontal" class="w-4 h-4"></i> ฟิลเตอร์</span>
      <i data-lucide="chevron-down" class="w-4 h-4" x-bind:class="open?'rotate-180':''"></i>
    </button>

    <form method="get" class="bg-white rounded-2xl border border-slate-200 p-5 sticky top-24 space-y-5"
          x-data="{ propType: <?= htmlspecialchars(json_encode($filter['type'] ?? '', JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?> }"
          @change="if ($event.target && $event.target.name === 'type') propType = $event.target.value"
          :class="open?'block':'hidden lg:block'">

      <div>
        <label class="text-xs font-semibold text-slate-600 mb-1 block flex items-center gap-1.5"><i data-lucide="search" class="w-3.5 h-3.5"></i> ค้นหา</label>
        <input type="text" name="q" value="<?= e($filter['q']) ?>" placeholder="ชื่อที่พัก..."
               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none">
      </div>

      <div>
        <label class="text-xs font-semibold text-slate-600 mb-1 block flex items-center gap-1.5"><i data-lucide="map" class="w-3.5 h-3.5"></i> โซน</label>
        <select name="zone" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
          <option value="">ทุกพื้นที่</option>
          <?php foreach ($zones as $z): ?>
            <option value="<?= e($z['zone']) ?>" <?= $filter['zone']===$z['zone']?'selected':'' ?>><?= e($z['zone']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="text-xs font-semibold text-slate-600 mb-1 block flex items-center gap-1.5"><i data-lucide="hotel" class="w-3.5 h-3.5"></i> ประเภทที่พัก</label>
        <?php if ($typePage !== null && $subtypeTabs === []): ?>
        <input type="hidden" name="type" value="<?= e($filter['type']) ?>">
        <div class="hidden md:block rounded-2xl border border-forest-200 bg-forest-50 p-3 text-sm text-forest-900">
          <div class="flex items-center gap-2 font-extrabold">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-white text-forest-700 ring-1 ring-forest-100 shadow-sm">
              <i data-lucide="<?= e($heroIcon) ?>" class="w-5 h-5"></i>
            </span>
            <span><?= e($heroTitle) ?></span>
          </div>
          <p class="mt-2 text-xs leading-relaxed text-forest-800/80">หน้านี้ล็อกประเภทไว้แล้ว เพื่อจัด Layout และ SEO ของหมวดนี้โดยเฉพาะ</p>
          <?php if ($typePageSeekUrl !== ''): ?>
          <a href="<?= e($typePageSeekUrl) ?>" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-3 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700">
            <i data-lucide="send" class="w-4 h-4 shrink-0"></i>
            ขอให้ช่วยหาในหมวดนี้
          </a>
          <?php endif; ?>
        </div>
        <?php elseif ($typePage !== null && $subtypeTabs !== []): ?>
        <div class="grid grid-cols-1 gap-1.5">
          <?php foreach ($subtypeTabs as $tabKey => $tabLabel):
            $tabHref = url('/stays' . ($tabKey !== '' ? '?type=' . urlencode((string)$tabKey) : ''));
            $tabActive = $activeSubtype === (string)$tabKey;
          ?>
          <a href="<?= e($tabHref) ?>"
             class="flex items-center justify-center min-h-[2.5rem] px-2 py-1.5 rounded-xl border text-xs font-semibold transition <?= $tabActive ? 'border-forest-700 bg-forest-700 text-white shadow-md' : 'border-slate-200 bg-white text-slate-600 hover:border-forest-300 hover:bg-forest-50/90' ?>">
            <?= e((string)$tabLabel) ?>
          </a>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-2 gap-1.5">
          <?php foreach ($types as $k => $v):
            $checked = $filter['type'] === $k;
            $kJs = htmlspecialchars($k, ENT_QUOTES, 'UTF-8');
            ?>
          <label
            class="flex items-center justify-center text-center min-h-[2.5rem] px-2 py-1.5 rounded-xl border cursor-pointer text-[11px] sm:text-xs leading-snug transition-all duration-150"
            :class="propType === '<?= $kJs ?>'
              ? 'border-forest-700 bg-forest-700 text-white font-semibold shadow-md ring-2 ring-forest-400/70 ring-offset-2 ring-offset-white'
              : 'border-slate-200 bg-white text-slate-600 font-medium hover:border-forest-300 hover:bg-forest-50/90'"
          >
            <input type="radio" name="type" value="<?= e($k) ?>" <?= $checked ? 'checked' : '' ?> class="sr-only peer">
            <?= e($v) ?>
          </label>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <p x-show="propType === 'hotel' || propType === 'resort'" x-cloak class="mt-2 text-[11px] text-slate-500 leading-snug">โหมดโรงแรม/รีสอร์ท: การ์ดจะเน้นความจุและหลายแบบห้องพัก — จำนวนห้องนอนต่อห้องดูได้ในหน้ารายละเอียดที่พัก</p>
        <p x-show="propType === 'raft'" x-cloak class="mt-2 text-[11px] text-slate-500 leading-snug">โหมดแพ: ที่เปิดหลายยูนิตจะแสดงเป็นการ์ดแยกตามแต่ละแพ/ขนาด — เลือกรูปแบบแพด้านล่าง (แพริมน้ำ / แพลาก) และใช้ฟิลเตอร์สิ่งอำนวยความสะดวกตามฟีเจอร์แพ</p>
        <p x-show="propType === 'pool_villa'" x-cloak class="mt-2 text-[11px] text-slate-500 leading-snug">บ้านพูลวิลล่า: ที่มีหลายยูนิตจะแสดงเป็นการ์ดแยกตามแต่ละแบบที่เปิดขาย — รายละเอียดสระส่วนตัวดูในหน้าที่พัก</p>
      </div>

      <div x-show="propType === '' || propType === 'raft'" x-cloak class="space-y-1.5">
        <label class="text-xs font-semibold text-slate-600 flex items-center gap-1.5"><i data-lucide="anchor" class="w-3.5 h-3.5"></i> รูปแบบแพ</label>
        <select name="raft_variant" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none">
          <option value="">ทุกแบบ</option>
          <option value="shore" <?= ($filter['raft_variant'] ?? '') === 'shore' ? 'selected' : '' ?>>แพริมน้ำ</option>
          <option value="towed" <?= ($filter['raft_variant'] ?? '') === 'towed' ? 'selected' : '' ?>>แพลาก</option>
        </select>
      </div>

      <?php
      $fBed = (int)($filter['bedrooms_min'] ?? 0);
      $fBath = (int)($filter['bathrooms_min'] ?? 0);
      ?>
      <div x-show="propType === 'raft' || propType === 'pool_villa'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-semibold text-slate-600 mb-1 block flex items-center gap-1.5"><i data-lucide="bed-double" class="w-3.5 h-3.5"></i> ห้องนอนขั้นต่ำ / ยูนิต</label>
          <select name="bedrooms_min" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:border-primary-500 outline-none">
            <option value="">ไม่ระบุ</option>
            <?php for ($br = 1; $br <= 10; $br++): ?>
              <option value="<?= $br ?>" <?= $fBed === $br ? 'selected' : '' ?>><?= $br ?> ห้องนอน</option>
            <?php endfor; ?>
          </select>
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-600 mb-1 block flex items-center gap-1.5"><i data-lucide="shower-head" class="w-3.5 h-3.5"></i> ห้องน้ำขั้นต่ำ / ยูนิต</label>
          <select name="bathrooms_min" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:border-primary-500 outline-none">
            <option value="">ไม่ระบุ</option>
            <?php for ($ba = 1; $ba <= 10; $ba++): ?>
              <option value="<?= $ba ?>" <?= $fBath === $ba ? 'selected' : '' ?>><?= $ba ?> ห้องน้ำ</option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <div>
        <label class="text-xs font-semibold text-slate-600 mb-1 block flex items-center gap-1.5"><i data-lucide="users" class="w-3.5 h-3.5"></i> จำนวนคน</label>
        <input type="number" min="1" max="120" name="guests" value="<?= e($filter['guests']) ?>"
               class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
      </div>

      <div>
        <label class="text-xs font-semibold text-slate-600 mb-1 block flex items-center gap-1.5"><i data-lucide="dollar-sign" class="w-3.5 h-3.5"></i> งบประมาณ / คืน</label>
        <div class="grid grid-cols-2 gap-2">
          <input type="number" name="budget_min" value="<?= e($filter['budget_min']) ?>" placeholder="ต่ำสุด" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
          <input type="number" name="budget_max" value="<?= e($filter['budget_max']) ?>" placeholder="สูงสุด" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
        </div>
      </div>

      <div class="space-y-2">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="pet" value="1" <?= $filter['pet']?'checked':'' ?> class="rounded border-slate-300"> 🐶 รับสัตว์เลี้ยง</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="coupon" value="1" <?= $filter['coupon']?'checked':'' ?> class="rounded border-slate-300"> 🎫 ใช้คูปองได้</label>
      </div>

      <div>
        <label class="text-xs font-semibold text-slate-600 mb-1 block flex items-center gap-1.5"><i data-lucide="check-square" class="w-3.5 h-3.5"></i> สิ่งอำนวยความสะดวก</label>
        <div class="space-y-1.5 max-h-52 overflow-auto pr-1">
        <?php foreach ($amenities as $a):
          $checked = in_array($a['id'], (array)$filter['amenities']); ?>
          <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="amenities[]" value="<?= $a['id'] ?>" <?= $checked?'checked':'' ?> class="rounded border-slate-300">
            <span class="text-slate-700"><?= e($a['name']) ?></span>
          </label>
        <?php endforeach; ?>
        </div>
      </div>

      <div class="flex gap-2 pt-2">
        <button class="flex-1 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-semibold text-sm">
          <i data-lucide="filter" class="w-4 h-4 inline"></i> ใช้ฟิลเตอร์
        </button>
        <a href="<?= e($listingBaseUrl) ?>" class="px-3 py-2.5 border border-slate-300 rounded-lg text-sm hover:bg-slate-50">ล้าง</a>
      </div>
    </form>
  </aside>

  <!-- ===== RESULTS ===== -->
  <div id="listing-results" class="lg:col-span-9" x-data="propertyMapSearch(<?= htmlspecialchars($mapRowsJson, ENT_QUOTES, 'UTF-8') ?>)" x-init="init()">
    <div class="mb-4">
      <!-- Mobile: [ย่อ/ละเอียด] ซ้าย | [รายการ/แผนที่] ขวา — แถวเดียว -->
      <div class="flex items-center justify-between gap-2 md:hidden mb-2">
        <div class="inline-grid grid-cols-2 rounded-lg border border-slate-200 bg-white p-0.5 shadow-sm">
          <button type="button" @click="mode='compact'; $dispatch('card-mode', {value:'compact'})"
                  :class="mode === 'compact' ? 'bg-slate-100 text-slate-900' : 'text-slate-400 hover:text-slate-600'"
                  class="inline-flex items-center justify-center gap-1 rounded-md px-3 py-1.5 font-semibold transition text-xs">
            <i data-lucide="layout-list" class="w-3.5 h-3.5"></i> ย่อ
          </button>
          <button type="button" @click="mode='detail'; $dispatch('card-mode', {value:'detail'})"
                  :class="mode === 'detail' ? 'bg-slate-100 text-slate-900' : 'text-slate-400 hover:text-slate-600'"
                  class="inline-flex items-center justify-center gap-1 rounded-md px-3 py-1.5 font-semibold transition text-xs">
            <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i> ละเอียด
          </button>
        </div>
        <div class="inline-grid grid-cols-2 rounded-xl border border-slate-200 bg-white p-1 text-sm font-semibold shadow-sm shrink-0">
          <button type="button" @click="setView('list')" class="inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-1.5 transition" :class="view === 'list' ? 'bg-primary-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'">
            <i data-lucide="list" class="h-4 w-4"></i> รายการ
          </button>
          <button type="button" @click="setView('map')" class="inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-1.5 transition" :class="view === 'map' ? 'bg-primary-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'">
            <i data-lucide="map" class="h-4 w-4"></i> แผนที่
          </button>
        </div>
      </div>
      <!-- Mobile: count + sort row -->
      <div class="flex items-center justify-between gap-2 md:hidden mb-1">
        <span class="text-xs text-slate-500">แสดง <b><?= count($rows) ?></b> จาก <b><?= number_format($total) ?></b> รายการ</span>
        <form method="get" class="flex items-center gap-1.5">
          <?php foreach ($filter as $k=>$v):
            if ($k==='sort') continue;
            if (is_array($v)) { foreach ($v as $vv) echo '<input type="hidden" name="'.e($k).'[]" value="'.e($vv).'">'; }
            else echo '<input type="hidden" name="'.e($k).'" value="'.e($v).'">';
          endforeach; ?>
          <select name="sort" onchange="this.form.submit()" class="rounded-lg border border-slate-200 px-2 py-1 text-xs text-slate-600">
            <option value="recommended" <?= $filter['sort']==='recommended'?'selected':'' ?>>แนะนำ</option>
            <option value="price_asc"   <?= $filter['sort']==='price_asc'?'selected':''   ?>>ราคา ↑</option>
            <option value="price_desc"  <?= $filter['sort']==='price_desc'?'selected':''  ?>>ราคา ↓</option>
            <option value="rating"      <?= $filter['sort']==='rating'?'selected':''      ?>>คะแนนสูง</option>
            <option value="newest"      <?= $filter['sort']==='newest'?'selected':''      ?>>ใหม่สุด</option>
          </select>
        </form>
      </div>
      <!-- Desktop: count + sort ซ้าย | view toggle ขวา -->
      <div class="hidden md:flex md:items-center md:justify-between">
        <div class="flex flex-wrap items-center gap-3">
          <span class="text-sm text-slate-600">
            แสดง <b><?= count($rows) ?></b> จาก <b><?= number_format($total) ?></b> รายการ
            <span class="ml-2 inline-flex items-center gap-1 rounded-full bg-accent-50 px-2 py-0.5 text-xs font-semibold text-accent-700">
              <i data-lucide="map-pin" class="h-3.5 w-3.5"></i>
              <span><?= count($mapRows) ?> แห่งบนแผนที่</span>
            </span>
          </span>
          <form method="get" class="flex items-center gap-2">
            <?php foreach ($filter as $k=>$v):
              if ($k==='sort') continue;
              if (is_array($v)) { foreach ($v as $vv) echo '<input type="hidden" name="'.e($k).'[]" value="'.e($vv).'">'; }
              else echo '<input type="hidden" name="'.e($k).'" value="'.e($v).'">';
            endforeach; ?>
            <label class="text-sm text-slate-500">เรียงโดย:</label>
            <select name="sort" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
              <option value="recommended" <?= $filter['sort']==='recommended'?'selected':'' ?>>แนะนำ</option>
              <option value="price_asc"   <?= $filter['sort']==='price_asc'?'selected':''   ?>>ราคา: ต่ำ → สูง</option>
              <option value="price_desc"  <?= $filter['sort']==='price_desc'?'selected':''  ?>>ราคา: สูง → ต่ำ</option>
              <option value="rating"      <?= $filter['sort']==='rating'?'selected':''      ?>>คะแนนสูงสุด</option>
              <option value="newest"      <?= $filter['sort']==='newest'?'selected':''      ?>>ใหม่ล่าสุด</option>
            </select>
          </form>
        </div>
        <div class="inline-grid grid-cols-2 rounded-xl border border-slate-200 bg-white p-1 text-sm font-semibold shadow-sm shrink-0">
          <button type="button" @click="setView('list')" class="inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-2 transition" :class="view === 'list' ? 'bg-primary-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'">
            <i data-lucide="list" class="h-4 w-4"></i> รายการ
          </button>
          <button type="button" @click="setView('map')" class="inline-flex items-center justify-center gap-1.5 rounded-lg px-3 py-2 transition" :class="view === 'map' ? 'bg-primary-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'">
            <i data-lucide="map" class="h-4 w-4"></i> แผนที่
          </button>
        </div>
      </div>
    </div>

    <div x-show="view === 'map'" x-cloak x-ref="mapPanel" :class="isFullscreen ? 'is-fullscreen' : ''" class="paekan-map-panel mb-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_22px_70px_-34px_rgba(15,23,42,0.42)]">
      <div class="paekan-map-panel-head flex flex-col gap-3 border-b border-slate-100 bg-gradient-to-r from-primary-50 via-white to-accent-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
        <div>
          <div class="inline-flex items-center gap-1.5 rounded-full bg-white/85 px-2.5 py-1 text-xs font-bold text-primary-700 ring-1 ring-primary-100">
            <i data-lucide="map-pinned" class="h-3.5 w-3.5"></i>
            แผนที่ค้นหาที่พัก
          </div>
          <h2 class="mt-2 text-lg font-extrabold text-ink">ดูแพ ที่พัก และบ้านพูลวิลล่าตามตำแหน่งจริง</h2>
          <p class="mt-0.5 text-sm text-slate-600">เลื่อนการ์ดด้านซ้าย แล้วดู marker ราคาขึ้นบนแผนที่ — กดที่ marker เพื่อโฟกัสที่พักนั้น</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button type="button" @click="setView('list')" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i data-lucide="list" class="h-4 w-4"></i> กลับสู่รายการ
          </button>
          <button type="button" @click="toggleFullscreen()" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i :data-lucide="isFullscreen ? 'minimize-2' : 'maximize-2'" class="h-4 w-4"></i>
            <span x-text="isFullscreen ? 'ออกจากเต็มจอ' : 'เต็มจอ'"></span>
          </button>
          <button type="button" @click="locateMe()" :disabled="locating || !mapRows.length" class="inline-flex items-center justify-center gap-2 rounded-xl bg-accent-500 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-accent-600 disabled:cursor-not-allowed disabled:opacity-55">
            <i data-lucide="crosshair" class="h-4 w-4"></i>
            <span x-text="locating ? 'กำลังค้นหาตำแหน่ง...' : (userLocation ? 'ใช้ตำแหน่งแล้ว' : 'ใช้ตำแหน่งฉัน')"></span>
          </button>
        </div>
      </div>
      <div class="paekan-map-shell">
        <aside class="paekan-map-side">
          <div class="paekan-map-side-head">
            <div class="flex items-center justify-between gap-2">
              <div class="flex items-center gap-2 text-sm font-extrabold text-slate-900">
                <i data-lucide="building-2" class="h-4 w-4 text-primary-600"></i>
                <span><span x-text="mapRows.length"></span> ที่พักบนแผนที่</span>
              </div>
              <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600" x-show="userLocation">
                <i data-lucide="navigation" class="h-3 w-3"></i> ใช้ตำแหน่งแล้ว
              </span>
            </div>
            <p class="mt-1 text-xs text-slate-500" x-show="statusMessage" x-text="statusMessage"></p>
            <p class="mt-1 text-xs text-slate-500" x-show="!statusMessage">กดที่การ์ดเพื่อย้ายแผนที่ไปยังที่พักนั้น</p>
          </div>

          <div class="paekan-map-side-list" data-mini-list>
            <template x-for="item in mapRows" :key="item.key">
              <article class="paekan-mini-card"
                       :class="activeKey === item.key ? 'is-active' : ''"
                       :data-mini-card-key="item.key"
                       @mouseenter="hoverCard(item.key)"
                       @mouseleave="hoverCard(null)"
                       @click="activateCard(item.key, { fly: true })">
                <div class="mini-thumb">
                  <img :src="item.cover" :alt="item.name" loading="lazy">
                </div>
                <div class="mini-body">
                  <div class="flex flex-wrap items-center gap-1">
                    <span class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2 py-0.5 text-[10.5px] font-bold text-primary-700">
                      <i :data-lucide="iconForType(item.type)" class="h-3 w-3"></i>
                      <span x-text="item.type_label || 'ที่พัก'"></span>
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-accent-50 px-2 py-0.5 text-[10.5px] font-bold text-accent-700"
                          x-show="item.distanceLabel">
                      <i data-lucide="navigation" class="h-3 w-3"></i>
                      <span x-text="item.distanceLabel"></span>
                    </span>
                  </div>
                  <h3 class="mt-1.5 line-clamp-2 text-sm font-extrabold leading-snug text-slate-950" x-text="item.name"></h3>
                  <div class="mt-1 flex items-center gap-1 text-[11px] text-slate-500">
                    <i data-lucide="map-pin" class="h-3 w-3 text-forest-700"></i>
                    <span x-text="item.zone || item.district || 'กาญจนบุรี'"></span>
                  </div>
                  <div class="mt-1.5 flex items-center justify-between gap-2">
                    <div class="text-sm font-extrabold text-forest-900" x-show="item.price">
                      <span x-text="item.price"></span><span class="text-[10.5px] font-semibold text-slate-500"> / คืน</span>
                    </div>
                    <a :href="item.url" @click.stop class="inline-flex items-center gap-1 rounded-lg bg-accent-500 px-2.5 py-1.5 text-[11px] font-bold text-white shadow-sm transition hover:bg-accent-600">
                      <span>ดูรายละเอียด</span>
                      <i data-lucide="arrow-up-right" class="h-3 w-3"></i>
                    </a>
                  </div>
                </div>
              </article>
            </template>

            <div x-show="!mapRows.length" class="grid place-items-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-500">
              <div>
                <div class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-white text-slate-400 ring-1 ring-slate-200">
                  <i data-lucide="map-pin-off" class="h-6 w-6"></i>
                </div>
                <p class="mt-3 font-semibold text-slate-700">ไม่พบที่พักที่มีพิกัด</p>
                <p class="mt-1">ลองปรับฟิลเตอร์หรือล้างเงื่อนไข</p>
              </div>
            </div>
          </div>
        </aside>

        <div class="paekan-map-canvas-wrap bg-slate-100">
          <div class="paekan-mobile-map-top" x-show="isFullscreen" x-cloak>
            <div class="paekan-mobile-map-search">
              <button type="button" @click="toggleFullscreen()" class="paekan-mobile-map-icon-btn" aria-label="ออกจากเต็มจอ">
                <i data-lucide="arrow-left" class="h-5 w-5"></i>
              </button>
              <button type="button" @click="setView('list'); toggleFullscreen()" class="paekan-mobile-map-meta text-left">
                <strong><?= e($filter['q'] ?: 'ค้นหาที่พักบนแผนที่') ?></strong>
                <span><?= e($filter['zone'] ?: 'กาญจนบุรี') ?> · <?= count($mapRows) ?> แห่งบนแผนที่</span>
              </button>
              <button type="button" @click="locateMe()" class="paekan-mobile-map-icon-btn" aria-label="ใช้ตำแหน่งฉัน">
                <i data-lucide="crosshair" class="h-5 w-5"></i>
              </button>
            </div>
          </div>
          <div x-ref="map" class="paekan-map-canvas"></div>
          <div x-show="!mapRows.length" class="absolute inset-0 z-[400] grid place-items-center bg-slate-50/95 p-6 text-center">
            <div>
              <div class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-white text-slate-400 ring-1 ring-slate-200">
                <i data-lucide="map-pin-off" class="h-7 w-7"></i>
              </div>
              <h3 class="mt-3 font-bold text-slate-900">ยังไม่มีพิกัดสำหรับผลลัพธ์นี้</h3>
              <p class="mt-1 text-sm text-slate-500">ลองล้างฟิลเตอร์ หรือเพิ่มพิกัดให้ที่พักในหลังบ้าน</p>
            </div>
          </div>

          <div class="paekan-map-hint pointer-events-none absolute bottom-3 left-1/2 z-[500] -translate-x-1/2 rounded-full bg-white/95 px-3 py-1.5 text-[11px] font-semibold text-slate-600 shadow ring-1 ring-slate-200" x-show="mapRows.length">
            <span class="inline-flex items-center gap-1">
              <i data-lucide="info" class="h-3 w-3 text-primary-600"></i>
              คลิก marker หรือการ์ดเพื่อดูที่พัก
            </span>
          </div>

          <div class="paekan-mobile-map-card" x-show="isFullscreen && activeItem()" x-cloak>
            <article class="paekan-mobile-selected-card" @click="activeItem() && (window.location.href = activeItem().url)">
              <img :src="activeItem()?.cover" :alt="activeItem()?.name || ''" loading="lazy">
              <div class="paekan-mobile-selected-body">
                <div class="flex items-center gap-1.5">
                  <span class="inline-flex items-center gap-1 rounded-full bg-primary-50 px-2 py-0.5 text-[11px] font-bold text-primary-700">
                    <i :data-lucide="iconForType(activeItem()?.type)" class="h-3 w-3"></i>
                    <span x-text="activeItem()?.type_label || 'ที่พัก'"></span>
                  </span>
                  <span class="inline-flex items-center gap-1 rounded-full bg-accent-50 px-2 py-0.5 text-[11px] font-bold text-accent-700" x-show="activeItem()?.distanceLabel">
                    <i data-lucide="navigation" class="h-3 w-3"></i>
                    <span x-text="activeItem()?.distanceLabel"></span>
                  </span>
                </div>
                <h3 class="mt-1.5 line-clamp-2 text-sm font-extrabold leading-snug text-slate-950" x-text="activeItem()?.name"></h3>
                <div class="mt-1 flex items-center gap-1 text-[11px] text-slate-500">
                  <i data-lucide="map-pin" class="h-3 w-3 text-forest-700"></i>
                  <span x-text="activeItem()?.zone || activeItem()?.district || 'กาญจนบุรี'"></span>
                </div>
                <div class="mt-2 flex items-end justify-between gap-2">
                  <div>
                    <div class="text-[10px] font-semibold text-slate-500">ราคาต่อคืน</div>
                    <div class="text-lg font-extrabold text-accent-700" x-show="activeItem()?.price" x-text="activeItem()?.price"></div>
                  </div>
                  <a :href="activeItem()?.url" @click.stop class="inline-flex items-center gap-1 rounded-xl bg-accent-500 px-3 py-2 text-xs font-bold text-white shadow-sm">
                    ดูรายละเอียด
                    <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                  </a>
                </div>
              </div>
            </article>
          </div>
        </div>
      </div>
    </div>

    <div x-show="view === 'list'" x-cloak>
    <!-- 3 action tiles: แผนที่ | ช่วยหา | AI -->
    <div class="paekan-listing-action-tiles mb-4">
      <button type="button" @click="setView('map')" class="paekan-action-tile paekan-action-tile--map">
        <span class="paekan-action-tile__icon"><i data-lucide="map"></i></span>
        <span class="paekan-action-tile__label">แผนที่</span>
        <span class="paekan-action-tile__sub"><?= count($mapRows) ?> แห่งบนแผนที่</span>
      </button>
      <a href="<?= e($listingSeekUrl) ?>" class="paekan-action-tile paekan-action-tile--seek">
        <span class="paekan-action-tile__icon"><i data-lucide="megaphone"></i></span>
        <span class="paekan-action-tile__label">ช่วยหาที่พัก</span>
        <span class="paekan-action-tile__sub">บอกงบและความชอบ</span>
      </a>
      <button type="button"
              class="paekan-action-tile paekan-action-tile--ai"
              :class="aiOpen ? 'is-active' : ''"
              @click="aiOpen = !aiOpen; $nextTick(() => { if (window.lucide) window.lucide.createIcons(); if (aiOpen) $refs.aiInput?.focus(); })"
              :aria-expanded="aiOpen ? 'true' : 'false'">
        <span class="paekan-action-tile__icon"><i data-lucide="sparkles"></i></span>
        <span class="paekan-action-tile__label">AI ค้นหา</span>
        <span class="paekan-action-tile__sub">พิมพ์ภาษาธรรมชาติ</span>
      </button>
    </div>

    <!-- AI input (expand below tiles) -->
    <div x-show="aiOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="paekan-listing-ai-expand mb-4"
         data-paekan-ai-hero
         data-endpoint="<?= e(url('/ai/smart-search')) ?>">
      <form data-role="paekan-ai-hero-form" class="flex items-center gap-2">
        <div class="flex flex-1 items-center gap-2 min-w-0 px-1">
          <i data-lucide="sparkles" class="w-4 h-4 text-amber-500 shrink-0"></i>
          <input type="text" x-ref="aiInput" name="paekan_ai_query" data-role="paekan-ai-query" autocomplete="off" required maxlength="800"
                 placeholder="แพ 4 คน งบ 3000"
                 class="flex-1 min-w-0">
        </div>
        <button type="submit" data-role="paekan-ai-submit" class="inline-flex items-center gap-1.5 disabled:opacity-50">
          <i data-lucide="wand-sparkles" class="w-4 h-4" aria-hidden="true"></i>
          <span data-role="idle-label">AI ค้นหา</span>
          <span data-role="busy-label" hidden>กำลังค้นหา...</span>
        </button>
      </form>
    </div>

    <?php if (empty($rows)): ?>
      <div class="bg-white border border-dashed border-slate-300 rounded-2xl p-12 text-center">
        <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 grid place-items-center text-slate-400">
          <i data-lucide="search-x" class="w-8 h-8"></i>
        </div>
        <h3 class="mt-4 text-lg font-semibold">ไม่พบที่พักตามเงื่อนไข</h3>
        <p class="text-slate-500 text-sm mt-1">ลองปรับฟิลเตอร์ หรือเลือกโซนอื่น</p>
        <a href="<?= e($listingBaseUrl) ?>" class="mt-4 inline-block px-4 py-2 bg-primary-600 text-white rounded-lg"><?= $typePage !== null ? 'ล้างตัวกรองในหมวดนี้' : 'ดูทุกที่พัก' ?></a>
      </div>
    <?php else: ?>
      <div class="md:hidden">
      <?php \App\Core\View::partial('partials/property-horizontal-mobile-stack', [
        'properties' => $rows,
        'wrapperClass' => 'w-full',
        'showTabs' => false,
      ]); ?>
      </div>
      <div class="hidden md:grid md:grid-cols-2 xl:grid-cols-3 gap-5">
        <?php foreach ($rows as $property):
          \App\Core\View::partial('partials/property-card', ['property' => $property]);
        endforeach; ?>
      </div>

      <?php
      $query = $_GET; unset($query['page']);
      if ($typePage !== null) unset($query['type']);
      \App\Core\View::partial('partials/pagination', [
        'page'=>$page,'totalPages'=>$totalPages,'baseUrl'=>$listingBaseUrl,'query'=>$query
      ]); ?>
    <?php endif; ?>
    </div>
  </div>
</section>
