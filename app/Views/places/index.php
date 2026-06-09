<?php
/** @var array<int,array<string,mixed>> $rows */
/** @var array<string,string> $categories */
/** @var list<string> $zoneChoices */
/** @var int $page */
/** @var int $totalPages */
/** @var ?string $filterCategory */
/** @var ?string $filterZone */
/** @var ?string $filterDistrict */
/** @var list<string> $districtChoices */
/** @var array<string,string> $filterQuery */
/** @var float|null $filterLat */
/** @var float|null $filterLng */
/** @var ?string $filterSort */
/** @var ?string $filterQ */
/** @var ?string $filterTag */
/** @var bool $filterOpenNow */
/** @var array<string,array{image:string,title:string,subtitle:string,button:string,link:string}> $placeBanners */

$filterQuery     = $filterQuery ?? [];
$districtChoices = $districtChoices ?? [];
$filterLat       = $filterLat ?? null;
$filterLng       = $filterLng ?? null;
$filterSort      = $filterSort ?? null;
$filterQ         = $filterQ ?? null;
$filterTag       = $filterTag ?? null;
$filterOpenNow   = !empty($filterOpenNow);
$hasGeo          = $filterLat !== null && $filterLng !== null;

$placeBanners = $placeBanners ?? \App\Models\Banner::placesPageContent();
$heroBanner   = $placeBanners['places_hero'];
$raftBanner   = $placeBanners['places_promo_raft'];
$dealBanner   = $placeBanners['places_promo_deal'];

$bannerHref = static function (string $link): string {
    if ($link === '') {
        return '#';
    }
    if (preg_match('#^https?://#i', $link)) {
        return $link;
    }

    return url($link);
};

$categoryChips = [
    ['icon' => 'droplets',      'label' => 'น้ำตก',                 'url' => url('/places?category=nature')],
    ['icon' => 'landmark',      'label' => 'วัดสวย',                'url' => url('/places?category=temple')],
    ['icon' => 'sailboat',      'label' => 'แพพัก',                 'url' => url('/rafts')],
    ['icon' => 'camera',        'label' => 'จุดถ่ายรูป',            'url' => url('/places?category=viewpoint')],
    ['icon' => 'mountain-snow', 'label' => 'ชมวิว',                 'url' => url('/places?category=viewpoint')],
    ['icon' => 'users',         'label' => 'กิจกรรมครอบครัว',       'url' => url('/activities')],
    ['icon' => 'heart',         'label' => 'ที่เที่ยวสำหรับคู่รัก', 'url' => url('/places?category=attraction')],
    ['icon' => 'ship',          'label' => 'ล่องแพ',                'url' => url('/activities')],
    ['icon' => 'scroll-text',   'label' => 'ประวัติศาสตร์',          'url' => url('/places?category=attraction')],
    ['icon' => 'trees',         'label' => 'อุทยานธรรมชาติ',        'url' => url('/places?category=nature')],
];

$quickTiles = [
    ['category' => '',           'icon' => 'mountain',  'label' => 'ที่เที่ยว',   'sub' => 'ใกล้ฉัน', 'bg' => 'places-tile--green',  'href' => null],
    ['category' => 'cafe',       'icon' => 'coffee',    'label' => 'คาเฟ่',       'sub' => 'ใกล้ฉัน', 'bg' => 'places-tile--pink',   'href' => url('/places?category=cafe')],
    ['category' => '',           'icon' => 'bed-double','label' => 'ที่พัก',      'sub' => 'ใกล้ฉัน', 'bg' => 'places-tile--blue',   'href' => url('/rafts')],
    ['category' => 'restaurant', 'icon' => 'utensils',  'label' => 'ร้านอาหาร',  'sub' => 'ใกล้ฉัน', 'bg' => 'places-tile--orange', 'href' => null],
];

$isCafeMode = $filterCategory === 'cafe';
$isFiltered = $filterCategory !== null || $filterZone !== null || $filterDistrict !== null || $hasGeo || ($filterSort !== null && $filterSort !== 'nearest') || $filterQ !== null || $filterTag !== null || $filterOpenNow;

$sortOptions = $hasGeo
    ? ['nearest' => 'ใกล้ที่สุด', 'latest' => 'อัปเดตล่าสุด']
    : ['' => 'แนะนำ', 'latest' => 'อัปเดตล่าสุด'];
$currentSort = $filterSort ?? ($hasGeo ? 'nearest' : '');

$cafeBaseQuery = array_filter([
    'category' => 'cafe',
    'lat'      => $hasGeo ? (string)$filterLat : null,
    'lng'      => $hasGeo ? (string)$filterLng : null,
    'sort'     => $filterSort,
    'q'        => $filterQ,
], static fn ($v) => $v !== null && $v !== '');
$cafeTagUrl = static function (?string $tag, bool $openNow = false) use ($cafeBaseQuery): string {
    $q = $cafeBaseQuery;
    if ($tag !== null && $tag !== '') {
        $q['tag'] = $tag;
    }
    if ($openNow) {
        $q['open_now'] = '1';
    }
    return url('/places?' . http_build_query($q));
};
?>

<style>

.places-page { font-family: var(--font-heading, system-ui), sans-serif; }

/* Hero — กะทัดรัด เน้นรูป + ปุ่ม CTA */
.places-hero {
  position: relative;
  min-height: 220px;
  max-height: 280px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
}
@media (min-width: 640px) {
  .places-hero { min-height: 260px; max-height: 320px; }
}
.places-hero__img {
  position: absolute; inset: 0;
  width: 100%; height: 100%; object-fit: cover;
}
.places-hero__overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,.55) 0%, rgba(0,0,0,.12) 55%, transparent 100%);
}
.places-hero__loc-pill {
  position: absolute; top: .75rem; left: 50%; transform: translateX(-50%);
  z-index: 20;
  display: inline-flex; align-items: center; gap: .375rem;
  background: rgba(255,255,255,.92); backdrop-filter: blur(8px);
  color: #1e293b; font-size: .8125rem; font-weight: 600;
  padding: .35rem .875rem; border-radius: 9999px;
  box-shadow: 0 2px 12px rgba(0,0,0,.18);
  white-space: nowrap;
}
.places-hero__body {
  position: relative; z-index: 10;
  width: 100%; max-width: 36rem; margin: 0 auto;
  padding: 0 1rem 1rem;
  text-align: center;
}
.places-hero__cta {
  display: inline-flex; align-items: center; gap: .625rem;
  background: #fff; color: #1e293b;
  font-weight: 700; font-size: .8125rem;
  padding: .625rem 1.25rem;
  border-radius: 9999px;
  box-shadow: 0 4px 16px rgba(0,0,0,.2);
  transition: background .15s, transform .15s;
  max-width: 100%;
}
.places-hero__cta:hover { background: #f8fafc; transform: translateY(-1px); }
.places-hero__cta .pin { color: #16a34a; flex-shrink: 0; }

/* Quick tiles */
.places-tiles { display: grid; grid-template-columns: repeat(4, 1fr); gap: .5rem; }
.places-tile {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: .5rem; padding: .875rem .25rem;
  border-radius: 1.125rem; border: none; cursor: pointer;
  text-decoration: none; text-align: center;
  transition: transform .15s, box-shadow .15s;
  min-height: 100px;
}
@media (min-width: 640px) {
  .places-tiles { gap: .625rem; }
  .places-tile { gap: .5625rem; padding: 1.125rem .5rem; min-height: 108px; }
}
.places-tile:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }
.places-tile--green  { background: #ecfdf5; color: #166534; }
.places-tile--pink   { background: #fdf2f8; color: #9d174d; }
.places-tile--blue   { background: #eff6ff; color: #1d4ed8; }
.places-tile--orange { background: #fff7ed; color: #c2410c; }
.places-tile__icon {
  width: 2.75rem; height: 2.75rem;
  display: grid; place-items: center;
}
@media (min-width: 640px) {
  .places-tile__icon { width: 3rem; height: 3rem; }
}
.places-tile__icon svg { width: 2rem; height: 2rem; stroke-width: 2.35px; }
@media (min-width: 640px) {
  .places-tile__icon svg { width: 2.125rem; height: 2.125rem; }
}
.places-tile__label { font-size: .8125rem; font-weight: 800; line-height: 1.28; letter-spacing: -.015em; }
@media (min-width: 640px) {
  .places-tile__label { font-size: .875rem; line-height: 1.32; }
}

/* Category grid 5×2 */
.places-cat-grid {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: .75rem .375rem;
}
@media (max-width: 380px) { .places-cat-grid { grid-template-columns: repeat(4, 1fr); } }
.places-cat-item {
  display: flex; flex-direction: column; align-items: center; gap: .375rem;
  text-decoration: none; color: #334155;
  transition: transform .12s;
}
.places-cat-item:hover { transform: scale(1.04); color: #14532d; }
.places-cat-item__circle {
  width: 3rem; height: 3rem; border-radius: 9999px;
  background: #f0fdf4; border: 1.5px solid #bbf7d0;
  display: grid; place-items: center;
  color: #15803d;
}
.places-cat-item__label {
  font-size: .625rem; font-weight: 600;
  text-align: center; line-height: 1.25;
  max-width: 4.5rem;
}

/* Promo banners — asymmetric */
.places-promos {
  display: grid;
  grid-template-columns: 1.45fr 1fr;
  gap: .75rem;
  align-items: stretch;
}
@media (max-width: 540px) { .places-promos { grid-template-columns: 1fr; } }
.places-promo-raft {
  position: relative; overflow: hidden;
  border-radius: 1.125rem; min-height: 148px;
  display: flex; align-items: flex-end; padding: 1rem;
  text-decoration: none; color: #fff;
}
.places-promo-raft img {
  position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;
  transition: transform .5s;
}
.places-promo-raft:hover img { transform: scale(1.05); }
.places-promo-raft__overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,.7) 0%, rgba(0,0,0,.15) 60%);
}
.places-promo-raft__body { position: relative; z-index: 1; }
.places-promo-raft__badge {
  display: inline-block; font-size: .625rem; font-weight: 700;
  background: #16a34a; color: #fff;
  padding: .2rem .5rem; border-radius: .375rem; margin-bottom: .375rem;
}
.places-promo-deal {
  border-radius: 1.125rem; min-height: 148px;
  background: linear-gradient(145deg, #dcfce7 0%, #bbf7d0 100%);
  padding: 1rem; display: flex; flex-direction: column;
  text-decoration: none; color: #14532d;
  position: relative; overflow: hidden;
  transition: transform .15s, box-shadow .15s;
}
.places-promo-deal:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(22,101,52,.15); }
.places-promo-deal__badge {
  display: inline-block; font-size: .625rem; font-weight: 700;
  background: #15803d; color: #fff;
  padding: .2rem .5rem; border-radius: .375rem; margin-bottom: .5rem; align-self: flex-start;
}
.places-promo-deal__pct {
  font-size: 2.5rem; font-weight: 800; line-height: 1;
  color: #15803d; margin: .25rem 0;
}
.places-promo-deal__icon {
  position: absolute; right: .75rem; bottom: .75rem;
  opacity: .15; width: 4rem; height: 4rem;
}
.places-promo-deal__btn {
  margin-top: auto; align-self: flex-start;
  font-size: .6875rem; font-weight: 700;
  background: #15803d; color: #fff;
  padding: .375rem .75rem; border-radius: 9999px;
  display: inline-flex; align-items: center; gap: .25rem;
}

/* Horizontal scroll cards */
.places-scroll {
  display: flex; gap: .875rem;
  overflow-x: auto; padding-bottom: .5rem;
  scroll-snap-type: x mandatory;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}
.places-scroll::-webkit-scrollbar { display: none; }
.places-card-h {
  flex: 0 0 auto; width: min(72vw, 260px);
  scroll-snap-align: start;
  text-decoration: none; color: inherit;
  border-radius: 1rem; overflow: hidden;
  background: #fff; border: 1px solid #e2e8f0;
  transition: box-shadow .15s, transform .15s;
}
.places-card-h:hover { box-shadow: 0 8px 24px rgba(0,0,0,.1); transform: translateY(-2px); }
.places-card-h__img-wrap {
  position: relative; aspect-ratio: 4/3;
  background: #f1f5f9; overflow: hidden;
}
.places-card-h__img {
  width: 100%; height: 100%; object-fit: cover;
  transition: transform .5s;
}
.places-card-h:hover .places-card-h__img { transform: scale(1.05); }
.places-card-h__heart {
  position: absolute; top: .5rem; right: .5rem;
  width: 1.75rem; height: 1.75rem; border-radius: 9999px;
  background: rgba(255,255,255,.88); backdrop-filter: blur(4px);
  border: none; cursor: pointer;
  display: grid; place-items: center;
  color: #94a3b8; transition: color .15s;
}
.places-card-h__heart:hover { color: #f43f5e; }
.places-card-h__dist {
  position: absolute; bottom: .5rem; left: .5rem;
  background: rgba(0,0,0,.55); backdrop-filter: blur(4px);
  color: #fff; font-size: .625rem; font-weight: 600;
  padding: .2rem .5rem; border-radius: 9999px;
  display: inline-flex; align-items: center; gap: .2rem;
}
.places-card-h__body { padding: .75rem; }
.places-card-h__cat { font-size: .625rem; font-weight: 700; color: #16a34a; text-transform: uppercase; }
.places-card-h__name { font-size: .875rem; font-weight: 700; color: #0f172a; margin-top: .2rem; line-height: 1.35; }
.places-card-h__loc { font-size: .6875rem; color: #64748b; margin-top: .25rem; }

/* Cafe list mode */
.places-cafe-page { background: #f1f5f9; min-height: 100vh; }

/* Header — white */
.places-cafe-header { background: #fff; border-bottom: 1px solid #e2e8f0; color: #0f172a; }
.places-cafe-header__back {
  width: 2.25rem; height: 2.25rem; border-radius: 9999px;
  background: #f1f5f9; display: grid; place-items: center;
  color: #334155; text-decoration: none; flex-shrink: 0;
}
.places-cafe-header__geo-btn {
  display: inline-flex; align-items: center; gap: .375rem;
  background: #f0fdf4; color: #166534; border: 1.5px solid #bbf7d0;
  font-size: .65rem; font-weight: 700;
  padding: .4rem .7rem; border-radius: 9999px;
  white-space: nowrap; cursor: pointer; flex-shrink: 0;
}

/* Search */
.places-cafe-search { display:flex; align-items:center; gap:.5rem; background:#fff; border:1.5px solid #e2e8f0; border-radius:.875rem; padding:.6rem .875rem; color:#64748b; }
.places-cafe-search input { width:100%; border:0; outline:0; font-size:.8125rem; color:#0f172a; background:transparent; }

/* Chip bar */
.places-cafe-chipbar { display:flex; gap:.5rem; overflow-x:auto; padding-bottom:.25rem; scrollbar-width:none; }
.places-cafe-chipbar::-webkit-scrollbar{display:none}
.places-cafe-chip {
  display:inline-flex; align-items:center; gap:.3rem;
  padding:.45rem .75rem; border-radius:9999px;
  border:1.5px solid #d1fae5; background:#fff; color:#166534;
  font-size:.72rem; font-weight:700; white-space:nowrap;
  cursor:pointer; text-decoration:none;
}
.places-cafe-chip.is-active { background:#15803d; border-color:#15803d; color:#fff; }

/* Shared small tag pill */
.cafe-tag-pill {
  display:inline-flex; align-items:center;
  padding:.2rem .55rem; border-radius:9999px;
  font-size:.65rem; font-weight:600;
  background:rgba(255,255,255,.18); color:#fff;
  border:1px solid rgba(255,255,255,.3);
}
.cafe-tag-pill--dark { background:#f1f5f9; color:#334155; border-color:#e2e8f0; }

/* Featured card (first in list) */
.cafe-featured {
  position:relative; border-radius:1.125rem; overflow:hidden;
  width:100%; aspect-ratio:4/3;
  display:flex; align-items:flex-end;
  box-shadow:0 16px 48px -16px rgba(15,23,42,.45);
}
@media (min-width:480px) { .cafe-featured { aspect-ratio:16/9; } }
.cafe-featured__img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
.cafe-featured__overlay {
  position:absolute; inset:0;
  background:linear-gradient(to top, rgba(0,0,0,.82) 0%, rgba(0,0,0,.15) 55%, transparent 78%);
}
.cafe-featured__badge {
  position:absolute; top:.75rem; left:.75rem;
  background:linear-gradient(90deg,#f59e0b,#fbbf24); color:#fff;
  font-size:.65rem; font-weight:800;
  padding:.3rem .7rem; border-radius:9999px;
  box-shadow:0 2px 8px rgba(245,158,11,.45);
}
.cafe-featured__heart {
  position:absolute; top:.75rem; right:.75rem;
  width:2rem; height:2rem; border-radius:9999px;
  background:rgba(255,255,255,.18); backdrop-filter:blur(4px);
  border:none; cursor:pointer; display:grid; place-items:center; color:#fff;
}
.cafe-featured__body { position:relative; z-index:2; width:100%; padding:.875rem; }
.cafe-featured__name { font-size:1.05rem; font-weight:800; color:#fff; line-height:1.25; }
.cafe-featured__meta { display:flex; align-items:center; gap:.35rem; margin-top:.25rem; font-size:.7rem; color:rgba(255,255,255,.9); }
.cafe-featured__tagbar { display:flex; flex-wrap:wrap; gap:.3rem; margin-top:.45rem; }
.cafe-featured__info { display:flex; align-items:center; gap:.65rem; margin-top:.5rem; font-size:.67rem; color:rgba(255,255,255,.8); flex-wrap:wrap; }
.cafe-featured__actions { display:flex; gap:.5rem; margin-top:.65rem; }
.cafe-featured__btn-detail {
  flex:1; padding:.5rem; border-radius:.625rem;
  border:1.5px solid rgba(255,255,255,.45); background:rgba(255,255,255,.1); backdrop-filter:blur(4px);
  color:#fff; font-size:.73rem; font-weight:700; text-align:center;
  text-decoration:none; display:flex; align-items:center; justify-content:center; gap:.3rem;
}
.cafe-featured__btn-nav {
  padding:.5rem 1rem; border-radius:.625rem;
  background:#16a34a; color:#fff;
  font-size:.73rem; font-weight:700;
  text-decoration:none; display:flex; align-items:center; justify-content:center; gap:.3rem;
  flex-shrink:0;
}

/* Horizontal cafe card (2nd+ items) */
.cafe-card {
  background:#fff; border-radius:1.125rem; overflow:hidden;
  box-shadow:0 4px 20px -8px rgba(15,23,42,.18);
  border:1px solid #f1f5f9;
}
.cafe-card__top { display:flex; align-items:stretch; }
.cafe-card__img-col { width:7rem; flex-shrink:0; }
.cafe-card__img { width:100%; height:100%; object-fit:cover; display:block; }
.cafe-card__content {
  flex:1; min-width:0; padding:.6rem .65rem .6rem;
  display:flex; flex-direction:column; gap:.2rem;
}
.cafe-card__name { font-size:.85rem; font-weight:800; color:#0f172a; line-height:1.25; padding-right:1.75rem; }
.cafe-card__rating { display:flex; align-items:center; gap:.3rem; font-size:.68rem; color:#64748b; flex-wrap:wrap; }
.cafe-card__tagbar { display:flex; flex-wrap:wrap; gap:.25rem; }
.cafe-card__info { display:flex; align-items:center; gap:.4rem; font-size:.64rem; color:#475569; flex-wrap:wrap; }
.cafe-card__thumbs { display:flex; gap:.25rem; margin-top:.2rem; }
.cafe-card__thumb { width:2.5rem; height:2.5rem; border-radius:.375rem; object-fit:cover; flex-shrink:0; display:block; }
.cafe-card__thumb-more {
  width:2.5rem; height:2.5rem; border-radius:.375rem; position:relative; overflow:hidden; flex-shrink:0;
}
.cafe-card__thumb-more img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:.4; }
.cafe-card__thumb-more span {
  position:absolute; inset:0; z-index:1; background:rgba(0,0,0,.42);
  display:grid; place-items:center;
  font-size:.62rem; font-weight:800; color:#fff;
}
.cafe-card__heart {
  position:absolute; top:.55rem; right:.55rem; z-index:2;
  width:1.75rem; height:1.75rem; border-radius:9999px;
  background:#fff; border:1px solid #f1f5f9;
  display:grid; place-items:center; color:#94a3b8;
  cursor:pointer; box-shadow:0 1px 4px rgba(0,0,0,.08);
}
.cafe-card__heart:hover { color:#f43f5e; }
.cafe-card__actions { display:flex; gap:.4rem; margin-top:auto; padding-top:.3rem; }
.cafe-card__btn-detail {
  flex:1; padding:.4rem .35rem; border-radius:.45rem;
  border:1.5px solid #e2e8f0; background:#fff; color:#334155;
  font-size:.68rem; font-weight:700; text-align:center;
  text-decoration:none; display:flex; align-items:center; justify-content:center;
}
.cafe-card__btn-nav {
  padding:.4rem .65rem; border-radius:.45rem;
  background:#16a34a; color:#fff;
  font-size:.68rem; font-weight:700;
  text-decoration:none; display:flex; align-items:center; justify-content:center; gap:.25rem;
  white-space:nowrap;
}

.places-cafe-list { display:flex; flex-direction:column; gap:.875rem; }
.places-cafe-row { display:flex; align-items:center; justify-content:space-between; gap:.75rem; font-size:.75rem; color:#475569; }

.places-sort {
  font-size: .75rem; font-weight: 600; color: #475569;
  border: 1px solid #cbd5e1; border-radius: 9999px;
  padding: .3rem .75rem; background: #fff;
  cursor: pointer;
}
</style>

<div class="places-page">

<?php if (!$isCafeMode): ?>
<!-- ===== HERO ===== -->
<section class="places-hero" id="places-hero">
  <img src="<?= e($heroBanner['image']) ?>" alt="<?= e($heroBanner['title']) ?>" class="places-hero__img" loading="eager" fetchpriority="high" width="1280" height="400">
  <div class="places-hero__overlay"></div>

  <div class="places-hero__loc-pill">
    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-green-600"></i>
    กาญจนบุรี
    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400"></i>
  </div>

  <div class="places-hero__body">
    <h1 class="sr-only">ที่เที่ยว <?= e($heroBanner['title']) ?></h1>
    <button type="button" id="places-hero-geo-btn" class="places-hero__cta">
      <i data-lucide="map-pin" class="w-4 h-4 pin"></i>
      <span id="places-hero-geo-label">ค้นหาที่เที่ยวใกล้ฉัน</span>
      <i data-lucide="chevron-right" class="w-4 h-4 text-slate-400"></i>
    </button>
  </div>
</section>

<!-- ===== QUICK TILES ===== -->
<section class="bg-white">
  <div class="max-w-lg mx-auto px-3 py-5 sm:px-4 sm:py-6">
    <div class="places-tiles">
      <?php foreach ($quickTiles as $tile): ?>
        <?php if ($tile['href']): ?>
          <a href="<?= e($tile['href']) ?>" class="places-tile <?= e($tile['bg']) ?>">
            <span class="places-tile__icon"><i data-lucide="<?= e($tile['icon']) ?>" aria-hidden="true"></i></span>
            <span class="places-tile__label"><?= e($tile['label']) ?><br><?= e($tile['sub']) ?></span>
          </a>
        <?php else: ?>
          <button type="button" data-places-geo-tile data-category="<?= e($tile['category']) ?>"
            class="places-tile <?= e($tile['bg']) ?>">
            <span class="places-tile__icon"><i data-lucide="<?= e($tile['icon']) ?>" aria-hidden="true"></i></span>
            <span class="places-tile__label"><?= e($tile['label']) ?><br><?= e($tile['sub']) ?></span>
          </button>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== CATEGORY GRID ===== -->
<section class="bg-white border-t border-slate-100">
  <div class="max-w-lg mx-auto px-4 py-5">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-sm font-bold text-slate-800">
        <span class="mr-1">🔥</span> หมวดยอดนิยมในกาญจนบุรี
      </h2>
      <a href="<?= url('/places') ?>" class="text-xs text-green-700 font-semibold">ดูทั้งหมด &rsaquo;</a>
    </div>
    <div class="places-cat-grid">
      <?php foreach ($categoryChips as $chip): ?>
        <a href="<?= e($chip['url']) ?>" class="places-cat-item">
          <span class="places-cat-item__circle">
            <i data-lucide="<?= e($chip['icon']) ?>" class="w-[18px] h-[18px]"></i>
          </span>
          <span class="places-cat-item__label"><?= e($chip['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== PROMO BANNERS ===== -->
<section class="bg-slate-50 border-t border-slate-100" id="places-promo-banners">
  <div class="max-w-lg mx-auto px-4 py-5">
    <div class="places-promos">

      <a href="<?= e($bannerHref($raftBanner['link'])) ?>" class="places-promo-raft group">
        <img src="<?= e($raftBanner['image']) ?>" alt="<?= e($raftBanner['title']) ?>" loading="lazy">
        <div class="places-promo-raft__overlay"></div>
        <div class="places-promo-raft__body">
          <span class="places-promo-raft__badge">แนะนำ</span>
          <div class="font-bold text-sm leading-snug"><?= e($raftBanner['title']) ?></div>
          <div class="text-[11px] opacity-85 mt-0.5 leading-snug"><?= nl2br(e($raftBanner['subtitle'])) ?></div>
          <?php if ($raftBanner['button'] !== ''): ?>
          <span class="mt-2 inline-flex items-center gap-1 text-[10px] font-bold bg-white/25 backdrop-blur-sm rounded-full px-2 py-0.5">
            <?= e($raftBanner['button']) ?> <i data-lucide="chevron-right" class="w-3 h-3"></i>
          </span>
          <?php endif; ?>
        </div>
      </a>

      <a href="<?= e($bannerHref($dealBanner['link'])) ?>" class="places-promo-deal<?= $dealBanner['image'] !== '' ? ' places-promo-deal--has-img' : '' ?>">
        <?php if ($dealBanner['image'] !== ''): ?>
          <img src="<?= e($dealBanner['image']) ?>" alt="" class="absolute inset-0 w-full h-full object-cover opacity-20" loading="lazy">
        <?php endif; ?>
        <span class="places-promo-deal__badge">โปรดี วันนี้!</span>
        <div class="text-xs font-bold leading-snug relative z-10"><?= e($dealBanner['title']) ?></div>
        <div class="places-promo-deal__pct relative z-10"><?= e($dealBanner['subtitle']) ?><sup class="text-base">*</sup></div>
        <?php if ($dealBanner['button'] !== ''): ?>
        <span class="places-promo-deal__btn relative z-10">
          <?= e($dealBanner['button']) ?> <i data-lucide="chevron-right" class="w-3 h-3"></i>
        </span>
        <?php endif; ?>
        <i data-lucide="bed-double" class="places-promo-deal__icon"></i>
      </a>

    </div>
  </div>
</section>

<!-- ===== RESULTS ===== -->
<?php endif; ?>
<section class="<?= $isCafeMode ? 'places-cafe-page pb-10' : 'max-w-lg mx-auto px-4 py-6 pb-10' ?>">

<?php if ($isCafeMode): ?>
<?php
$_mapsUrl = static function (array $p): string {
    $g = trim((string)($p['google_maps_url'] ?? ''));
    if ($g !== '') return $g;
    $lat = $p['latitude'] ?? null;
    $lng = $p['longitude'] ?? null;
    if ($lat && $lng) return 'https://maps.google.com/?q=' . $lat . ',' . $lng;
    return '#';
};
$_parseTags = static function (string $s): array {
    if ($s === '') return [];
    return array_values(array_filter(array_map('trim', explode('·', $s))));
};
$_distStr = static function (?float $km): ?string {
    if ($km === null) return null;
    if ($km < 1.0) return round($km * 1000) . ' ม. จากคุณ';
    return number_format($km, 1) . ' กม. จากคุณ';
};
?>

<!-- ===== CAFE HEADER ===== -->
<div class="places-cafe-header">
  <div class="max-w-lg mx-auto px-4 py-3">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <div class="flex items-center gap-1.5">
          <i data-lucide="map-pin" class="w-4 h-4 text-green-600 flex-shrink-0"></i>
          <h1 class="font-extrabold text-lg leading-tight text-slate-900">คาเฟ่ใกล้ฉัน</h1>
        </div>
        <p class="text-xs text-slate-500 mt-0.5 pl-[1.375rem]">พบคาเฟ่สวยๆ ใกล้ตัวคุณ</p>
      </div>
      <div class="flex flex-col items-end gap-1 flex-shrink-0">
        <button type="button" data-places-geo-tile data-category="cafe" class="places-cafe-header__geo-btn">
          <i data-lucide="locate" class="w-3.5 h-3.5"></i>ใช้ตำแหน่งปัจจุบัน
        </button>
        <span class="inline-flex items-center gap-1 text-[10px] text-slate-500">
          <i data-lucide="map-pin" class="w-3 h-3 text-green-600"></i>
          ตำแหน่งปัจจุบัน: <strong class="text-slate-700"><?= $hasGeo ? 'ใกล้คุณ' : 'กาญจนบุรี' ?></strong>
        </span>
      </div>
    </div>
  </div>
</div>

<!-- ===== SEARCH + CHIPS ===== -->
<div class="bg-white border-b border-slate-100">
  <div class="max-w-lg mx-auto px-4 pt-3 pb-3 space-y-3">
    <form method="get" action="<?= url('/places') ?>" class="flex items-center gap-2">
      <input type="hidden" name="category" value="cafe">
      <?php if ($hasGeo): ?>
        <input type="hidden" name="lat" value="<?= e((string)$filterLat) ?>">
        <input type="hidden" name="lng" value="<?= e((string)$filterLng) ?>">
        <input type="hidden" name="sort" value="nearest">
      <?php endif; ?>
      <label class="places-cafe-search flex-1">
        <i data-lucide="search" class="w-4 h-4 shrink-0"></i>
        <input type="search" name="q" value="<?= e((string)($filterQ ?? '')) ?>" placeholder="ค้นหาคาเฟ่ ชื่อร้าน หรือโซน...">
      </label>
      <button type="button" id="places-filter-toggle"
        class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 shadow-sm">
        <i data-lucide="sliders-horizontal" class="w-4 h-4"></i> ตัวกรอง
      </button>
    </form>

    <div class="places-cafe-chipbar">
      <?php $isDefault = !$filterOpenNow && $filterTag === null && ($currentSort !== 'nearest' || !$hasGeo); ?>
      <a class="places-cafe-chip <?= $isDefault ? 'is-active' : '' ?>" href="<?= url('/places?category=cafe') ?>">
        <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i>ทั้งหมด</a>
      <a class="places-cafe-chip <?= ($currentSort === 'nearest' && !$filterOpenNow && $filterTag === null) ? 'is-active' : '' ?>"
         href="<?= e(url('/places?' . http_build_query(array_filter(array_merge($cafeBaseQuery, ['sort' => 'nearest']), static fn($v) => $v !== null && $v !== '')))) ?>">
        <i data-lucide="navigation" class="w-3.5 h-3.5"></i>ระยะทาง</a>
      <a class="places-cafe-chip <?= $filterOpenNow ? 'is-active' : '' ?>" href="<?= e($cafeTagUrl(null, true)) ?>">
        <i data-lucide="clock" class="w-3.5 h-3.5"></i>เปิดตอนนี้</a>
      <a class="places-cafe-chip <?= $filterTag === 'coffee_good' ? 'is-active' : '' ?>" href="<?= e($cafeTagUrl('coffee_good')) ?>">
        <i data-lucide="coffee" class="w-3.5 h-3.5"></i>กาแฟดี</a>
      <a class="places-cafe-chip <?= $filterTag === 'pet_friendly' ? 'is-active' : '' ?>" href="<?= e($cafeTagUrl('pet_friendly')) ?>">
        <i data-lucide="paw-print" class="w-3.5 h-3.5"></i>Pet Friendly</a>
    </div>
  </div>
</div>

<!-- ===== FILTER PANEL ===== -->
<div id="places-filter-panel" class="<?= ($filterZone !== null || $filterDistrict !== null) ? '' : 'hidden' ?>">
  <div class="max-w-lg mx-auto px-4 py-3">
    <form method="get" action="<?= url('/places') ?>" class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3 shadow-sm">
      <input type="hidden" name="category" value="cafe">
      <?php if ($filterQ): ?><input type="hidden" name="q" value="<?= e($filterQ) ?>"><?php endif; ?>
      <?php if ($filterTag): ?><input type="hidden" name="tag" value="<?= e($filterTag) ?>"><?php endif; ?>
      <?php if ($filterOpenNow): ?><input type="hidden" name="open_now" value="1"><?php endif; ?>
      <?php if ($hasGeo): ?>
        <input type="hidden" name="lat" value="<?= e((string)$filterLat) ?>">
        <input type="hidden" name="lng" value="<?= e((string)$filterLng) ?>">
        <input type="hidden" name="sort" value="nearest">
      <?php endif; ?>
      <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">โซน</label>
        <select name="zone" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm">
          <option value="">ทุกโซน</option>
          <?php foreach ($zoneChoices as $z): ?>
            <option value="<?= e($z) ?>" <?= ($filterZone ?? '') === $z ? 'selected' : '' ?>><?= e($z) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">อำเภอ</label>
        <select name="district" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm">
          <option value="">ทุกอำเภอ</option>
          <?php foreach ($districtChoices as $d): ?>
            <option value="<?= e($d) ?>" <?= ($filterDistrict ?? '') === $d ? 'selected' : '' ?>><?= e($d) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="flex gap-2 pt-1">
        <button type="submit" class="flex-1 py-2 rounded-xl bg-green-700 text-white font-semibold text-sm hover:bg-green-800 transition">ค้นหา</button>
        <a href="<?= url('/places?category=cafe') ?>" class="px-4 py-2 rounded-xl border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition inline-flex items-center">ล้าง</a>
      </div>
    </form>
  </div>
</div>

<!-- ===== CARD LIST ===== -->
<?php if (empty($rows)): ?>
  <div class="max-w-lg mx-auto px-4 pb-10">
    <div class="text-center py-12 rounded-2xl border border-dashed border-slate-200 bg-white">
      <div class="w-14 h-14 mx-auto rounded-full bg-pink-50 grid place-items-center text-pink-400 mb-3">
        <i data-lucide="coffee" class="w-7 h-7"></i>
      </div>
      <h3 class="text-sm font-semibold text-slate-700">ยังไม่มีคาเฟ่ในตัวกรองนี้</h3>
      <p class="text-slate-400 text-xs mt-1">ลองล้างตัวกรอง หรือกดใช้ตำแหน่งฉันอีกครั้ง</p>
    </div>
  </div>
<?php else: ?>
  <div class="places-cafe-list max-w-lg mx-auto px-4 py-4 pb-10">
    <?php foreach ($rows as $idx => $p):
      $href        = url('/places/' . $p['slug']);
      $img         = \App\Models\VisitorPlace::coverImageUrl($p);
      $galleryImgs = \App\Models\VisitorPlace::galleryUrls($p, 6);
      $navUrl      = $_mapsUrl($p);
      $rawDistKm   = isset($p['distance_km']) ? (float)$p['distance_km'] : null;
      $distLabel   = $_distStr($rawDistKm);
      $rating      = isset($p['rating_avg']) && (float)$p['rating_avg'] > 0 ? number_format((float)$p['rating_avg'], 1) : null;
      $ratingCount = isset($p['rating_count']) ? (int)$p['rating_count'] : 0;
      $tagList     = $_parseTags(trim((string)($p['tags'] ?? '')));
      $hours       = trim((string)($p['opening_hours'] ?? ''));
      $open        = !empty($p['is_open_now']);
      $isPet       = !empty($p['is_pet_friendly']);
      $locStr      = implode(' · ', array_filter([(string)($p['district'] ?? ''), (string)($p['zone'] ?? '')]));
      $hasGallery  = count($galleryImgs) > 1;
      $extraCount  = $hasGallery ? max(0, count($galleryImgs) - 3) : max(1, min(20, (int)ceil($ratingCount / 12)));
    ?>

    <?php if ($idx === 0): ?>
    <!-- FEATURED CARD -->
    <div class="cafe-featured">
      <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" class="cafe-featured__img" loading="lazy">
      <div class="cafe-featured__overlay"></div>
      <span class="cafe-featured__badge">ร้านแนะนำวันนี้</span>
      <button type="button" class="cafe-featured__heart" aria-label="บันทึก">
        <i data-lucide="bookmark" class="w-4 h-4"></i>
      </button>
      <div class="cafe-featured__body">
        <div class="cafe-featured__name"><?= e($p['name']) ?></div>
        <!-- Rating row + thumbnails inline -->
        <div class="flex items-center justify-between gap-2 mt-1">
          <div class="cafe-featured__meta">
            <?php if ($rating !== null): ?>
              <span class="text-amber-400 font-bold">★ <?= e($rating) ?></span>
              <span class="text-white/80">(<?= $ratingCount ?> รีวิว)</span>
            <?php endif; ?>
            <?php if ($isPet): ?><span class="text-white/60">· 🐾 Pet friendly</span><?php endif; ?>
          </div>
          <div class="flex items-center gap-1 flex-shrink-0">
            <?php foreach (array_slice($galleryImgs, 0, 3) as $gi): ?>
              <a href="<?= e($href) ?>"><img src="<?= e($gi) ?>" alt="" class="w-9 h-9 rounded-md object-cover opacity-90 hover:opacity-100 transition"></a>
            <?php endforeach; ?>
            <?php if ($extraCount > 0): ?>
              <a href="<?= e($href) ?>" class="w-9 h-9 rounded-md bg-black/50 text-white text-[11px] font-bold flex items-center justify-center hover:bg-black/70 transition">+<?= $extraCount ?></a>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($tagList): ?>
          <div class="cafe-featured__tagbar">
            <?php foreach (array_slice($tagList, 0, 4) as $t): ?>
              <span class="cafe-tag-pill"><?= e($t) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="cafe-featured__info">
          <?php if ($distLabel !== null): ?>
            <span class="inline-flex items-center gap-1"><i data-lucide="map-pin" class="w-3 h-3"></i><?= e($distLabel) ?></span>
          <?php elseif ($locStr): ?>
            <span class="inline-flex items-center gap-1"><i data-lucide="map-pin" class="w-3 h-3"></i><?= e($locStr) ?></span>
          <?php endif; ?>
          <?php if ($hours): ?>
            <span class="inline-flex items-center gap-1">
              <i data-lucide="clock" class="w-3 h-3"></i>
              <span class="<?= $open ? 'text-green-300 font-bold' : '' ?>"><?= $open ? 'เปิดอยู่' : 'เปิด' ?></span>
              · <?= e($hours) ?>
            </span>
          <?php endif; ?>
        </div>
        <div class="cafe-featured__actions">
          <a href="<?= e($href) ?>" class="cafe-featured__btn-detail">ดูรายละเอียด</a>
          <?php if ($navUrl !== '#'): ?>
            <a href="<?= e($navUrl) ?>" target="_blank" rel="noopener" class="cafe-featured__btn-nav">
              <i data-lucide="navigation" class="w-3.5 h-3.5"></i> นำทาง
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php else: ?>
    <!-- HORIZONTAL CARD -->
    <div class="cafe-card" style="position:relative;">
      <button type="button" class="cafe-card__heart" aria-label="บันทึก" onclick="event.stopPropagation()">
        <i data-lucide="heart" class="w-4 h-4"></i>
      </button>
      <div class="cafe-card__top">
        <div class="cafe-card__img-col">
          <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" class="cafe-card__img" loading="lazy">
        </div>
        <div class="cafe-card__content">
          <div class="cafe-card__name"><?= e($p['name']) ?></div>
          <div class="cafe-card__rating">
            <?php if ($rating !== null): ?>
              <span class="text-amber-500 font-bold">★ <?= e($rating) ?></span>
              <span>(<?= $ratingCount ?> รีวิว)</span>
            <?php endif; ?>
            <?php if ($isPet): ?><span class="text-green-700 font-semibold">· Pet friendly</span><?php endif; ?>
          </div>
          <?php if ($tagList): ?>
            <div class="cafe-card__tagbar">
              <?php foreach (array_slice($tagList, 0, 4) as $t): ?>
                <span class="cafe-tag-pill cafe-tag-pill--dark"><?= e($t) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <div class="cafe-card__info">
            <?php if ($distLabel !== null): ?>
              <span class="inline-flex items-center gap-1 text-green-700 font-semibold">
                <i data-lucide="map-pin" class="w-3 h-3"></i><?= e($distLabel) ?>
              </span>
            <?php elseif ($locStr): ?>
              <span class="inline-flex items-center gap-1">
                <i data-lucide="map-pin" class="w-3 h-3"></i><?= e($locStr) ?>
              </span>
            <?php endif; ?>
            <?php if ($hours): ?>
              <span class="text-slate-300">|</span>
              <span class="inline-flex items-center gap-1">
                <i data-lucide="clock" class="w-3 h-3"></i>
                <span class="<?= $open ? 'text-green-700 font-bold' : '' ?>"><?= $open ? 'เปิดอยู่' : 'เปิด' ?></span>
                · <?= e($hours) ?>
              </span>
            <?php endif; ?>
          </div>
          <!-- Thumbnails inside right column -->
          <div class="cafe-card__thumbs">
            <?php foreach (array_slice($galleryImgs, 0, 3) as $gi): ?>
              <a href="<?= e($href) ?>">
                <img src="<?= e($gi) ?>" alt="" class="cafe-card__thumb" loading="lazy">
              </a>
            <?php endforeach; ?>
            <?php if ($extraCount > 0): ?>
              <a href="<?= e($href) ?>" class="cafe-card__thumb-more">
                <img src="<?= e($img) ?>" alt="">
                <span>+<?= $extraCount ?></span>
              </a>
            <?php endif; ?>
          </div>
          <!-- Buttons inside right column -->
          <div class="cafe-card__actions">
            <a href="<?= e($href) ?>" class="cafe-card__btn-detail">ดูรายละเอียด</a>
            <?php if ($navUrl !== '#'): ?>
              <a href="<?= e($navUrl) ?>" target="_blank" rel="noopener" class="cafe-card__btn-nav">
                <i data-lucide="navigation" class="w-3.5 h-3.5"></i> นำทาง
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php endforeach; ?>

    <?php if ($totalPages > 1): ?>
      <?php \App\Core\View::partial('partials/pagination', [
          'page'       => $page,
          'totalPages' => $totalPages,
          'baseUrl'    => url('/places'),
          'query'      => array_merge(['category' => 'cafe'], $filterQuery),
      ]); ?>
    <?php endif; ?>
  </div>
<?php endif; ?>
<?php else: ?>
  <div class="flex items-center justify-between mb-4 gap-2">
    <h2 class="text-sm font-bold text-slate-800 shrink-0">
      <span class="mr-0.5">⭐</span>
      <?= $hasGeo ? 'สถานที่แนะนำใกล้คุณ' : 'สถานที่แนะนำใกล้คุณ' ?>
    </h2>
    <div class="flex items-center gap-2">
      <form method="get" action="<?= url('/places') ?>" id="places-sort-form" class="inline">
        <?php foreach ($filterQuery as $k => $v): ?>
          <?php if ($k !== 'sort' && $k !== 'page'): ?>
            <input type="hidden" name="<?= e($k) ?>" value="<?= e((string)$v) ?>">
          <?php endif; ?>
        <?php endforeach; ?>
        <select name="sort" class="places-sort" onchange="this.form.submit()">
          <?php foreach ($sortOptions as $val => $lab): ?>
            <option value="<?= e($val) ?>" <?= $currentSort === $val ? 'selected' : '' ?>><?= e($lab) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
      <button type="button" id="places-filter-toggle"
        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-full border border-slate-300 text-[11px] font-semibold text-slate-600 hover:bg-slate-50 transition">
        <i data-lucide="sliders-horizontal" class="w-3 h-3"></i>
        กรอง
      </button>
    </div>
  </div>

  <!-- Collapsible filter -->
  <div id="places-filter-panel" class="<?= $isFiltered && !$hasGeo ? '' : 'hidden' ?> mb-5">
    <form method="get" action="<?= url('/places') ?>"
      class="bg-white rounded-2xl border border-slate-200 p-4 space-y-3 shadow-sm">
      <?php if ($hasGeo): ?>
        <input type="hidden" name="lat" value="<?= e((string)$filterLat) ?>">
        <input type="hidden" name="lng" value="<?= e((string)$filterLng) ?>">
      <?php endif; ?>
      <?php if ($filterSort): ?>
        <input type="hidden" name="sort" value="<?= e($filterSort) ?>">
      <?php endif; ?>
      <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">หมวด</label>
        <select name="category" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm">
          <option value="">ทุกหมวด</option>
          <?php foreach ($categories as $k => $lab): ?>
            <option value="<?= e($k) ?>" <?= ($filterCategory ?? '') === $k ? 'selected' : '' ?>><?= e($lab) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">โซน / แถบที่พัก</label>
        <select name="zone" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm">
          <option value="">ทุกโซน</option>
          <?php foreach ($zoneChoices as $z): ?>
            <option value="<?= e($z) ?>" <?= ($filterZone ?? '') === $z ? 'selected' : '' ?>><?= e($z) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">อำเภอ</label>
        <select name="district" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm">
          <option value="">ทุกอำเภอ</option>
          <?php foreach ($districtChoices as $d): ?>
            <option value="<?= e($d) ?>" <?= ($filterDistrict ?? '') === $d ? 'selected' : '' ?>><?= e($d) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="flex gap-2 pt-1">
        <button type="submit" class="flex-1 py-2 rounded-xl bg-green-700 text-white font-semibold text-sm hover:bg-green-800 transition">ค้นหา</button>
        <a href="<?= url('/places') ?>" class="px-4 py-2 rounded-xl border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition inline-flex items-center">ล้าง</a>
      </div>
    </form>
  </div>

  <?php if (empty($rows)): ?>
    <div class="text-center py-12 rounded-2xl border border-dashed border-slate-200 bg-white">
      <div class="w-14 h-14 mx-auto rounded-full bg-green-50 grid place-items-center text-green-400 mb-3">
        <i data-lucide="map-pin" class="w-7 h-7"></i>
      </div>
      <h3 class="text-sm font-semibold text-slate-700">ยังไม่มีรายการในตัวกรองนี้</h3>
      <p class="text-slate-400 text-xs mt-1">ลองเปลี่ยนอำเภอ โซน หรือกดค้นหาใกล้ฉัน</p>
      <button type="button" data-places-geo-tile data-category=""
        class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-green-700 text-white rounded-full text-xs font-bold">
        <i data-lucide="navigation" class="w-3.5 h-3.5"></i> ค้นหาใกล้ฉัน
      </button>
    </div>
  <?php else: ?>
    <!-- Horizontal scroll cards -->
    <div class="places-scroll mb-6">
      <?php foreach ($rows as $p): ?>
        <?php
        $href   = url('/places/' . $p['slug']);
        $img    = \App\Models\VisitorPlace::coverImageUrl($p);
        $catLab = $categories[$p['category']] ?? $p['category'];
        $distKm = isset($p['distance_km']) ? round((float)$p['distance_km'], 1) : null;
        $locParts = array_filter([$p['district'] ?? '', $p['zone'] ?? '']);
        ?>
        <a href="<?= e($href) ?>" class="places-card-h">
          <div class="places-card-h__img-wrap">
            <img src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" class="places-card-h__img" loading="lazy">
            <button type="button" class="places-card-h__heart" aria-label="บันทึก" onclick="event.preventDefault();event.stopPropagation();">
              <i data-lucide="heart" class="w-3.5 h-3.5"></i>
            </button>
            <?php if ($distKm !== null): ?>
              <span class="places-card-h__dist">
                <i data-lucide="navigation-2" class="w-2.5 h-2.5"></i> ~<?= $distKm ?> กม.
              </span>
            <?php endif; ?>
          </div>
          <div class="places-card-h__body">
            <div class="places-card-h__cat"><?= e($catLab) ?></div>
            <div class="places-card-h__name"><?= e($p['name']) ?></div>
            <?php if ($locParts): ?>
              <div class="places-card-h__loc"><?= e(implode(' · ', $locParts)) ?></div>
            <?php endif; ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
      <?php \App\Core\View::partial('partials/pagination', [
          'page'       => $page,
          'totalPages' => $totalPages,
          'baseUrl'    => url('/places'),
          'query'      => $filterQuery,
      ]); ?>
    <?php endif; ?>
  <?php endif; ?>

<?php endif; ?>
</section>
</div><!-- /.places-page -->

<script>
(function () {
  const toggleBtn   = document.getElementById('places-filter-toggle');
  const filterPanel = document.getElementById('places-filter-panel');
  if (toggleBtn && filterPanel) {
    toggleBtn.addEventListener('click', () => filterPanel.classList.toggle('hidden'));
  }

  function geoRedirect(category) {
    const btn   = document.getElementById('places-hero-geo-btn');
    const label = document.getElementById('places-hero-geo-label');
    if (label) label.textContent = 'กำลังหาตำแหน่ง...';
    if (btn) btn.disabled = true;

    if (!navigator.geolocation) {
      alert('เบราว์เซอร์ไม่รองรับ GPS — ลองกรองด้วยอำเภอหรือโซนแทน');
      if (label) label.textContent = 'ค้นหาที่เที่ยวใกล้ฉัน';
      if (btn) btn.disabled = false;
      return;
    }

    navigator.geolocation.getCurrentPosition(
      function (pos) {
        const lat = pos.coords.latitude.toFixed(6);
        const lng = pos.coords.longitude.toFixed(6);
        let url = '<?= url('/places') ?>?lat=' + lat + '&lng=' + lng + '&sort=nearest';
        if (category) url += '&category=' + encodeURIComponent(category);
        window.location.href = url;
      },
      function () {
        alert('ไม่สามารถรับตำแหน่งได้ — กรุณาอนุญาต GPS แล้วลองใหม่');
        if (label) label.textContent = 'ค้นหาที่เที่ยวใกล้ฉัน';
        if (btn) btn.disabled = false;
      },
      { timeout: 10000, maximumAge: 60000 }
    );
  }

  const heroBtn = document.getElementById('places-hero-geo-btn');
  if (heroBtn) heroBtn.addEventListener('click', () => geoRedirect(''));

  document.querySelectorAll('[data-places-geo-tile]').forEach(function (el) {
    el.addEventListener('click', function () {
      geoRedirect(this.dataset.category || '');
    });
  });
})();
</script>
