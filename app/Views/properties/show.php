<?php
/** @var array $property @var array $units @var array $gallery @var array $amenities @var array $reviews @var array $similar @var int $highlight_unit_id */
use App\Support\PropertyBookingCapabilities;
$highlight_unit_id = (int)($highlight_unit_id ?? 0);
$gallerySlides = [];
$gallerySeen = [];
foreach ($gallery ?? [] as $row) {
    $p = trim((string)($row['path'] ?? ''));
    if ($p === '' || isset($gallerySeen[$p])) {
        continue;
    }
    $gallerySeen[$p] = true;
    $gallerySlides[] = $row;
}
if ($gallerySlides === []) {
    $fallback = trim((string)($property['cover_image'] ?? ''));
    if ($fallback !== '') {
        $gallerySlides = [['path' => $fallback]];
    }
}
$petMap = ['not_allowed'=>'ไม่อนุญาต','allowed'=>'รับสัตว์เลี้ยง','on_request'=>'แจ้งล่วงหน้า'];
$typeMap = ['raft'=>'แพพัก','resort'=>'รีสอร์ท','homestay'=>'โฮมสเตย์','house'=>'บ้านพัก','pool_villa'=>'บ้านพูลวิลล่า','hotel'=>'โรงแรม','camping'=>'แคมป์ปิ้ง'];
$cover = $gallerySlides[0]['path'] ?? ($property['cover_image'] ?? '');
$stickyUnitId = $highlight_unit_id > 0 ? $highlight_unit_id : (int)($units[0]['id'] ?? 0);
$propertyCtaUrls = PropertyBookingCapabilities::urlsForUnit($property, (int)$property['id'], $stickyUnitId);
$hasMobileCta = !empty($propertyCtaUrls['buy_coupon']) || !empty($propertyCtaUrls['book_online']) || !empty($propertyCtaUrls['contact']) || !empty($propertyCtaUrls['line']);
?>

<style>
/* การ์ด Modal — grid + ความสูงชัดเจนเพื่อให้โซนกลางเลื่อนได้ */
.unit-modal-panel {
  height: min(88vh, 42rem);
}
@media (min-width: 768px) {
  .unit-modal-panel {
    height: min(86vh, 44rem);
  }
}
.unit-modal-body-scroll {
  scrollbar-gutter: stable;
  scrollbar-width: thin;
  scrollbar-color: rgb(148 163 184) rgb(241 245 249);
  -webkit-overflow-scrolling: touch;
}
.unit-modal-body-scroll::-webkit-scrollbar {
  width: 9px;
}
.unit-modal-body-scroll::-webkit-scrollbar-track {
  background: rgb(241 245 249);
  border-radius: 9999px;
}
.unit-modal-body-scroll::-webkit-scrollbar-thumb {
  background: rgb(148 163 184 / 0.92);
  border-radius: 9999px;
}

/* Hero ใน modal ยูนิต — สัดส่วน 4:3 + จำกัดความสูงสูงสุด (ไม่บีบเป็นแถบเตี้ยเหมือน h-40 คงที่) */
.unit-modal-hero-wrap {
  position: relative;
  width: 100%;
  aspect-ratio: 4 / 3;
  max-height: min(38vh, 260px);
  overflow: hidden;
}
@media (min-width: 640px) {
  .unit-modal-hero-wrap {
    max-height: min(40vh, 300px);
  }
}
@media (min-width: 768px) {
  .unit-modal-hero-wrap {
    max-height: min(42vh, 340px);
  }
}

/*
 * Lightbox — จำกัดความกว้าง/สูงใน CSS โดยตรง
 * (คลาส Tailwind แบบ arbitrary เช่น max-h-[min(...)] จะไม่มีใน app.css ถ้าไม่ได้รัน npm run build:css
 * ทำให้รูปขยายตามขนาดไฟล์จริงและดูเหมือนเต็มจอ)
 */
.paekan-lightbox-property-wrap {
  z-index: 10045;
}
.paekan-lightbox-property-card {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: min(96vw, 56rem);
  max-height: min(92vh, 56rem);
  display: flex;
  flex-direction: column;
  box-shadow: 0 28px 90px -12px rgba(0, 0, 0, 0.78);
  border: 1px solid rgba(255, 255, 255, 0.15);
}
@media (min-width: 640px) {
  .paekan-lightbox-property-card {
    max-width: min(94vw, 56rem);
  }
}
.paekan-lightbox-property-stage {
  flex: 1 1 auto;
  min-height: min(72vh, 640px);
}
.paekan-lightbox-property-img {
  max-height: min(78vh, 820px);
  width: auto;
  max-width: 100%;
  border-radius: 0.5rem;
  object-fit: contain;
  box-shadow: 0 12px 40px -8px rgba(0, 0, 0, 0.65);
  border: 0;
  user-select: none;
}
@media (min-width: 640px) {
  .paekan-lightbox-property-img {
    max-height: min(80vh, 840px);
  }
}

.paekan-lightbox-unit-wrap {
  z-index: 10060;
}
.paekan-lightbox-unit-card {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: min(96vw, 56rem);
  max-height: min(92vh, 56rem);
  display: flex;
  flex-direction: column;
  box-shadow: 0 28px 90px -12px rgba(0, 0, 0, 0.78);
  border: 1px solid rgba(255, 255, 255, 0.12);
}
@media (min-width: 640px) {
  .paekan-lightbox-unit-card {
    max-width: min(94vw, 56rem);
  }
}
.paekan-lightbox-unit-stage {
  position: relative;
  flex: 1 1 auto;
  min-height: min(76vh, 680px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3.5rem 0.625rem 1rem;
  box-sizing: border-box;
}
@media (min-width: 640px) {
  .paekan-lightbox-unit-stage {
    padding: 4rem 1rem 1.25rem;
    min-height: min(78vh, 720px);
  }
}
.paekan-lightbox-unit-img {
  max-height: min(82vh, 860px);
  width: auto;
  max-width: 100%;
  border-radius: 0.375rem;
  object-fit: contain;
  border: 0;
  box-shadow: 0 8px 32px -6px rgba(0, 0, 0, 0.55);
  user-select: none;
}
@media (min-width: 640px) {
  .paekan-lightbox-unit-img {
    max-height: min(84vh, 880px);
  }
}
</style>

<!-- Breadcrumb -->
<div class="bg-white border-b border-slate-100">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 text-xs text-slate-500 flex items-center gap-1.5 overflow-x-auto no-scrollbar">
    <a href="<?= url('/') ?>" class="hover:text-primary-700">หน้าแรก</a>
    <i data-lucide="chevron-right" class="w-3 h-3"></i>
    <a href="<?= url('/properties') ?>" class="hover:text-primary-700">ที่พัก</a>
    <i data-lucide="chevron-right" class="w-3 h-3"></i>
    <a href="<?= url('/properties?zone=' . urlencode($property['zone'])) ?>" class="hover:text-primary-700"><?= e($property['zone']) ?></a>
    <i data-lucide="chevron-right" class="w-3 h-3"></i>
    <span class="text-ink font-medium truncate"><?= e($property['name']) ?></span>
  </div>
</div>

<!-- ===== HERO + GALLERY ===== -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 mt-4">
  <div class="flex flex-col-reverse lg:flex-row lg:items-end lg:justify-between gap-3 mb-4">
    <div>
      <div class="flex items-center gap-2 flex-wrap">
        <?php if ($property['is_verified']): ?>
        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-accent-100 text-accent-700 text-xs font-semibold rounded-full">
          <i data-lucide="badge-check" class="w-3 h-3"></i> Verified
        </span>
        <?php endif; ?>
        <span class="px-2 py-0.5 bg-primary-100 text-primary-700 text-xs font-semibold rounded-full"><?= e($typeMap[$property['type']] ?? $property['type']) ?></span>
        <?php if (($property['type'] ?? '') === 'raft' && !empty($property['raft_variant'])):
          $rvLabels = ['shore' => 'แพริมน้ำ', 'towed' => 'แพลาก']; ?>
        <span class="px-2 py-0.5 bg-sky-100 text-sky-800 text-xs font-semibold rounded-full"><?= e($rvLabels[$property['raft_variant']] ?? $property['raft_variant']) ?></span>
        <?php endif; ?>
        <?php if ((int)($property['is_featured'] ?? 0) === 1): ?>
        <span class="px-2 py-0.5 bg-amber-100 text-amber-900 text-xs font-semibold rounded-full inline-flex items-center gap-1 border border-amber-200/80" title="เรียงลำดับ/การแสดงผลจากแพลตฟอร์มหรือแพ็กสมาชิก — ไม่ใช่คะแนนรีวิวอย่างเดียว">
          <i data-lucide="sparkles" class="w-3 h-3 shrink-0"></i> แนะนำสมาชิกแพกาญ
        </span>
        <?php endif; ?>
        <?php if ((int)($property['coupon_enabled'] ?? 0) === 1): ?>
        <span class="px-2 py-0.5 bg-rose-100 text-rose-700 text-xs font-semibold rounded-full inline-flex items-center gap-1">
          <i data-lucide="gift" class="w-3 h-3"></i> ใช้คูปองได้
        </span>
        <?php else: ?>
        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-xs font-semibold rounded-full inline-flex items-center gap-1 border border-slate-200/80">
          <i data-lucide="info" class="w-3 h-3 shrink-0"></i> ไม่เข้าร่วมคูปองแพกาญ
        </span>
        <?php endif; ?>
      </div>
      <h1 class="text-2xl md:text-3xl font-extrabold text-ink mt-1.5"><?= e($property['name']) ?></h1>
      <div class="flex items-center gap-3 mt-1 text-sm text-slate-600 flex-wrap">
        <span class="flex items-center gap-1"><i data-lucide="map-pin" class="w-4 h-4 text-accent-600"></i><?= e($property['address']) ?></span>
        <span class="flex items-center gap-1"><?= star_html((float)$property['rating_avg']) ?> <b class="ml-1"><?= number_format((float)$property['rating_avg'],1) ?></b> <span class="text-slate-400">(<?= (int)$property['rating_count'] ?> รีวิว)</span></span>
      </div>
    </div>
    <div class="flex gap-2">
      <button class="px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm hover:bg-slate-50 inline-flex items-center gap-1.5"><i data-lucide="heart" class="w-4 h-4"></i> บันทึก</button>
      <button class="px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm hover:bg-slate-50 inline-flex items-center gap-1.5"><i data-lucide="share-2" class="w-4 h-4"></i> แชร์</button>
    </div>
  </div>

  <!-- Hero gallery: แสดงเฉพาะรูปจริง ไม่เติมซ้ำให้ครบ 5 ช่อง -->
  <div class="grid grid-cols-4 grid-rows-2 gap-2 rounded-2xl overflow-hidden h-[260px] sm:h-[400px] md:h-[500px]"
       x-data="{open:false,current:0,images:<?= htmlspecialchars(json_encode(array_map(fn($g)=>upload_url($g['path']), $gallerySlides)), ENT_QUOTES) ?>}">
    <?php if ($gallerySlides !== []): ?>
    <button type="button" @click="current=0;open=true;$nextTick(() => { if (window.lucide) { lucide.createIcons(); requestAnimationFrame(() => lucide.createIcons()); } })" class="col-span-4 row-span-2 sm:col-span-2 sm:row-span-2 group">
      <img src="<?= e(upload_url($gallerySlides[0]['path'])) ?>" class="w-full h-full object-cover group-hover:opacity-90 transition" alt="">
    </button>
    <?php for ($i = 1; $i < 5; $i++): ?>
      <?php if (isset($gallerySlides[$i])): ?>
      <button type="button" @click="current=<?= $i ?>;open=true;$nextTick(() => { if (window.lucide) { lucide.createIcons(); requestAnimationFrame(() => lucide.createIcons()); } })" class="hidden sm:block group relative h-full min-h-0">
        <img src="<?= e(upload_url($gallerySlides[$i]['path'])) ?>" class="w-full h-full object-cover group-hover:opacity-90 transition" alt="">
        <?php if ($i === 4 && count($gallerySlides) > 5): ?>
        <div class="absolute inset-0 bg-black/50 grid place-items-center text-white">
          <span class="font-semibold">+<?= count($gallerySlides) - 5 ?> รูป</span>
        </div>
        <?php endif; ?>
      </button>
      <?php else: ?>
      <div class="hidden sm:block bg-slate-100 min-h-0 h-full rounded-none" aria-hidden="true"></div>
      <?php endif; ?>
    <?php endfor; ?>
    <?php else: ?>
    <div class="col-span-4 row-span-2 flex items-center justify-center bg-slate-100 text-slate-400 text-sm rounded-2xl">ไม่มีรูป</div>
    <?php endif; ?>

    <!-- Lightbox แกลเลอรี่ที่พัก — กล่องกลางจอ (ไม่เต็มจอ) -->
    <template x-teleport="body">
    <div x-show="open" x-cloak class="paekan-lightbox-property-wrap fixed inset-0 flex items-center justify-center p-2 sm:p-4" @keydown.escape.window="open=false">
      <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-[2px]" @click="open=false" aria-hidden="true"></div>
      <div class="paekan-lightbox-property-card flex flex-col overflow-hidden rounded-2xl bg-slate-950"
           @click.stop role="dialog" aria-modal="true" aria-label="ดูรูปที่พัก">
        <div class="paekan-lightbox-property-stage relative flex min-h-0 flex-1 items-center justify-center bg-gradient-to-b from-slate-900 to-black px-2 pb-4 pt-12 sm:px-4 sm:pb-5 sm:pt-14">
          <div class="absolute inset-x-0 top-0 z-20 bg-gradient-to-b from-black/80 via-black/45 to-transparent px-3 pb-10 pt-3 sm:px-4 sm:pb-12 sm:pt-4">
            <div class="flex w-full items-center justify-between gap-3">
              <div class="min-w-0">
                <span class="text-xs font-semibold tabular-nums text-white/95 drop-shadow sm:text-sm" x-show="images.length > 1">
                  รูปที่ <span x-text="current + 1"></span> / <span x-text="images.length"></span>
                </span>
                <span class="text-xs text-white/80 sm:text-sm" x-show="images.length <= 1">รูปที่พัก</span>
              </div>
              <button type="button" @click="open=false" class="shrink-0 rounded-xl p-2 text-white transition hover:bg-white/20" aria-label="ปิด">
                <i data-lucide="x" class="h-5 w-5"></i>
              </button>
            </div>
          </div>
          <template x-if="images.length > 1">
            <button type="button" @click="current=(current-1+images.length)%images.length" class="absolute left-2 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/22 p-2.5 text-white shadow-lg ring-1 ring-white/25 backdrop-blur-sm transition hover:bg-white/35 sm:left-3 sm:p-3" aria-label="ก่อนหน้า">
              <i data-lucide="chevron-left" class="h-6 w-6 text-white sm:h-7 sm:w-7"></i>
            </button>
          </template>
          <template x-if="images.length > 1">
            <button type="button" @click="current=(current+1)%images.length" class="absolute right-2 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/22 p-2.5 text-white shadow-lg ring-1 ring-white/25 backdrop-blur-sm transition hover:bg-white/35 sm:right-3 sm:p-3" aria-label="ถัดไป">
              <i data-lucide="chevron-right" class="h-6 w-6 text-white sm:h-7 sm:w-7"></i>
            </button>
          </template>
          <img :src="images[current]" class="paekan-lightbox-property-img relative z-[1]" alt="">
        </div>
      </div>
    </div>
    </template>
  </div>
</section>

<!-- ===== CONTENT ===== -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 mt-8 pb-28 md:pb-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
  <div class="lg:col-span-2 space-y-8">

    <!-- Quick info -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 grid grid-cols-2 sm:grid-cols-4 gap-4">
      <?php
      $minCap = 999; $maxCap = 0; $bedSum = 0;
      foreach ($units as $u) { $minCap=min($minCap,$u['capacity_min']); $maxCap=max($maxCap,$u['capacity_max']); $bedSum += $u['bedrooms']; }
      $infos = [
        ['log-in','เช็คอิน', date('H:i', strtotime($property['check_in']))],
        ['log-out','เช็คเอาท์', date('H:i', strtotime($property['check_out']))],
        ['users','รองรับ', $minCap.'–'.$maxCap.' ท่าน'],
        ['paw-print','สัตว์เลี้ยง', $petMap[$property['pet_policy']] ?? '-'],
      ];
      foreach ($infos as $info): ?>
      <div class="text-center sm:text-left">
        <div class="w-9 h-9 rounded-lg bg-accent-50 text-accent-600 grid place-items-center mx-auto sm:mx-0"><i data-lucide="<?= $info[0] ?>" class="w-4 h-4"></i></div>
        <div class="text-xs text-slate-500 mt-1.5"><?= $info[1] ?></div>
        <div class="font-semibold text-sm"><?= e((string)$info[2]) ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Description -->
    <div>
      <h2 class="text-xl font-bold flex items-center gap-2 mb-3"><i data-lucide="file-text" class="w-5 h-5 text-accent-600"></i> รายละเอียดที่พัก</h2>
      <div class="prose prose-slate max-w-none text-slate-700 leading-relaxed whitespace-pre-line"><?= e($property['description']) ?></div>
    </div>

    <?php \App\Core\View::partial('partials/property-owner-intake-section', ['property' => $property]); ?>

    <!-- Amenities -->
    <?php if (!empty($amenities)): ?>
    <div>
      <h2 class="text-xl font-bold flex items-center gap-2 mb-3"><i data-lucide="check-circle" class="w-5 h-5 text-accent-600"></i> สิ่งอำนวยความสะดวก</h2>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
        <?php foreach ($amenities as $a): ?>
        <div class="flex items-center gap-2.5 px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-sm">
          <i data-lucide="<?= e($a['icon'] ?: 'check') ?>" class="w-4 h-4 text-accent-600"></i>
          <span><?= e($a['name']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Units -->
    <?php
    use App\Support\UnitPricing;
    $unitsModalData = [];
    $compareableUnitProperty = in_array((string)($property['type'] ?? ''), ['raft', 'pool_villa'], true);
    foreach ($units as $uMod) {
        $gpMod = $uMod['_gallery_paths'] ?? [];
        if ($gpMod === [] && !empty($uMod['cover_image'])) {
            $gpMod = [$uMod['cover_image']];
        }
        $imgUrls = [];
        foreach ($gpMod as $pp) {
            $up = upload_url(trim((string) $pp));
            if ($up !== '') {
                $imgUrls[] = $up;
            }
        }
        if ($imgUrls === []) {
            $imgUrls[] = 'https://placehold.co/1200x800';
        }
        $priceExtra = '';
        if ((float) $uMod['price_weekend'] > (float) $uMod['price']) {
            $priceExtra = 'เสาร์-อาทิตย์ ' . format_money($uMod['price_weekend']) . ' · วันหยุด ' . format_money($uMod['price_holiday']);
        }
        $uModCtaUrls = PropertyBookingCapabilities::urlsForUnit($property, (int)$property['id'], (int)$uMod['id']);
        $unitsModalData[] = [
            'id' => (int) $uMod['id'],
            'name' => (string) $uMod['name'],
            'description' => (string) ($uMod['description'] ?? ''),
            'capacity_min' => (int) $uMod['capacity_min'],
            'capacity_max' => (int) $uMod['capacity_max'],
            'bedrooms' => (int) $uMod['bedrooms'],
            'bathrooms' => (int) $uMod['bathrooms'],
            'area_sqm' => !empty($uMod['area_sqm']) ? (float) $uMod['area_sqm'] : null,
            'images' => $imgUrls,
            'price_label' => UnitPricing::formatCardPrice($uMod) !== '' ? UnitPricing::formatCardPrice($uMod) : format_money($uMod['price']),
            'price_note' => UnitPricing::guestPriceNote($uMod),
            'price_detail' => UnitPricing::formatDetailLines($uMod),
            'price_extra' => $priceExtra,
            'book_url' => $uModCtaUrls['book_online'] ?? '',
            'buy_coupon_url' => $uModCtaUrls['buy_coupon'] ?? '',
            'tel' => $uModCtaUrls['contact'] ?? '',
            'cta_urls' => $uModCtaUrls,
            'booking_mode' => (string) ($property['booking_mode'] ?? ''),
            'compare' => $compareableUnitProperty ? [
                'property_id' => (int) $property['id'],
                'unit_id' => (int) $uMod['id'],
                'title' => (string) $uMod['name'],
                'subtitle' => (string) $property['name'],
                'image' => $imgUrls[0] ?? '',
                'detail_url' => url('/property/' . $property['slug'] . '?unit=' . $uMod['id']),
            ] : null,
        ];
    }
    $unitsModalJson = json_encode($unitsModalData, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($unitsModalJson === false) {
        $unitsModalJson = '[]';
    }
    ?>
    <script>
    window.__PAEKAN_UNITS_MODAL__ = <?= $unitsModalJson ?>;
    function paekanPropertyUnitsUi(initialUnits, autoOpenUnitId) {
      var units = Array.isArray(initialUnits) ? initialUnits : [];
      var autoId = parseInt(autoOpenUnitId, 10) || 0;
      return {
        units: units,
        modalUnit: null,
        modalHeroIdx: 0,
        lbOpen: false,
        lbImages: [],
        lbCurrent: 0,
        init() {
          var self = this;
          if (autoId > 0) {
            setTimeout(function () {
              self.openUnitModal(autoId);
            }, 420);
          }
        },
        syncScrollLock() {
          if (this.lbOpen || this.modalUnit) document.body.classList.add('overflow-hidden');
          else document.body.classList.remove('overflow-hidden');
        },
        openUnitModal(id) {
          var u = units.find(function (x) { return x.id === id; });
          if (!u) return;
          this.modalHeroIdx = 0;
          this.modalUnit = u;
          this.syncScrollLock();
          this.modalRefreshLucide();
        },
        closeModal() {
          this.modalUnit = null;
          this.modalHeroIdx = 0;
          this.syncScrollLock();
        },
        modalRefreshLucide() {
          var refresh = function () {
            if (window.lucide) window.lucide.createIcons();
          };
          if (typeof this.$nextTick === 'function') {
            this.$nextTick(function () {
              refresh();
              requestAnimationFrame(refresh);
            });
          } else {
            queueMicrotask(function () {
              refresh();
              requestAnimationFrame(refresh);
            });
          }
        },
        modalHeroNext() {
          if (!this.modalUnit || !this.modalUnit.images || !this.modalUnit.images.length) return;
          var n = this.modalUnit.images.length;
          this.modalHeroIdx = (this.modalHeroIdx + 1) % n;
          this.modalRefreshLucide();
        },
        modalHeroPrev() {
          if (!this.modalUnit || !this.modalUnit.images || !this.modalUnit.images.length) return;
          var n = this.modalUnit.images.length;
          this.modalHeroIdx = (this.modalHeroIdx - 1 + n) % n;
          this.modalRefreshLucide();
        },
        modalHeroGo(idx) {
          if (!this.modalUnit || !this.modalUnit.images || !this.modalUnit.images.length) return;
          var i = parseInt(idx, 10) || 0;
          if (i < 0) i = 0;
          if (i >= this.modalUnit.images.length) i = this.modalUnit.images.length - 1;
          this.modalHeroIdx = i;
          this.modalRefreshLucide();
        },
        openLightbox(id, startIdx) {
          var u = units.find(function (x) { return x.id === id; });
          if (!u || !u.images || !u.images.length) return;
          this.lbImages = u.images;
          var i = parseInt(startIdx, 10) || 0;
          if (i < 0) i = 0;
          if (i >= this.lbImages.length) i = this.lbImages.length - 1;
          this.lbCurrent = i;
          this.lbOpen = true;
          this.syncScrollLock();
          var self = this;
          var refresh = function () {
            if (window.lucide) window.lucide.createIcons();
          };
          if (typeof this.$nextTick === 'function') {
            this.$nextTick(function () {
              refresh();
              requestAnimationFrame(refresh);
            });
          } else {
            queueMicrotask(function () {
              refresh();
              requestAnimationFrame(refresh);
            });
          }
        },
        closeLightbox() {
          this.lbOpen = false;
          this.syncScrollLock();
        },
        lbNext() {
          if (!this.lbImages.length) return;
          this.lbCurrent = (this.lbCurrent + 1) % this.lbImages.length;
        },
        lbPrev() {
          if (!this.lbImages.length) return;
          this.lbCurrent = (this.lbCurrent - 1 + this.lbImages.length) % this.lbImages.length;
        },
        escapeLayers() {
          if (this.lbOpen) this.closeLightbox();
          else if (this.modalUnit) this.closeModal();
        },
        lbOnWindowKeydown(e) {
          if (!this.lbOpen) return;
          if (e.key === 'ArrowRight') {
            e.preventDefault();
            this.lbNext();
          } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            this.lbPrev();
          }
        },
      };
    }
    </script>
    <div id="units" class="relative" x-data="paekanPropertyUnitsUi(window.__PAEKAN_UNITS_MODAL__ || [], <?= (int) ($highlight_unit_id ?? 0) ?>)"
         @keydown.escape.window="escapeLayers()">
      <h2 class="text-xl font-bold flex items-center gap-2 mb-3"><i data-lucide="bed-double" class="w-5 h-5 text-accent-600"></i> ห้องพัก / ยูนิต (<?= count($units) ?>)</h2>
      <div class="space-y-4">
        <?php foreach ($units as $u):
          $gPaths = $u['_gallery_paths'] ?? [];
          if ($gPaths === [] && !empty($u['cover_image'])) {
              $gPaths = [$u['cover_image']];
          }
          $heroSrc = $gPaths[0] ?? '';
          ?>
        <div id="unit-<?= (int)$u['id'] ?>" data-unit-card role="button" tabindex="0"
             @click="openUnitModal(<?= (int)$u['id'] ?>)"
             @keydown.enter.prevent="openUnitModal(<?= (int)$u['id'] ?>)"
             class="bg-white border border-slate-200 rounded-2xl overflow-hidden flex flex-col md:flex-row scroll-mt-28 ring-offset-2 transition-shadow duration-300 cursor-pointer hover:border-accent-300 hover:shadow-md outline-none focus-visible:ring-2 focus-visible:ring-accent-500">
          <div class="md:w-64 shrink-0 bg-slate-100 flex flex-col">
            <div class="aspect-[4/3] md:flex-1 md:min-h-[12rem]">
              <img src="<?= e(upload_url($heroSrc) ?: 'https://placehold.co/600x400') ?>" class="w-full h-full object-cover pointer-events-none" alt="" loading="lazy">
            </div>
            <?php if (count($gPaths) > 1): ?>
            <div class="flex gap-1 p-1.5 overflow-x-auto border-t border-slate-200 bg-white/90"
                 @click.stop>
              <?php foreach (array_slice($gPaths, 1) as $ti => $gp): $thumbIdx = $ti + 1; ?>
              <button type="button"
                      @click.stop="openLightbox(<?= (int)$u['id'] ?>, <?= (int)$thumbIdx ?>)"
                      class="shrink-0 rounded-md ring-1 ring-slate-200 overflow-hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 cursor-zoom-in"
                      aria-label="ดูรูปใหญ่">
                <img src="<?= e(upload_url($gp)) ?>" alt="" class="w-12 h-12 object-cover block" loading="lazy">
              </button>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
          <div class="p-4 md:p-5 flex-1 flex flex-col">
            <h3 class="font-bold text-lg"><?= e($u['name']) ?></h3>
            <div class="flex items-center gap-3 text-xs text-slate-500 mt-1 flex-wrap">
              <span class="inline-flex items-center gap-1"><i data-lucide="users" class="w-3.5 h-3.5"></i><?= $u['capacity_min'] ?>–<?= $u['capacity_max'] ?> ท่าน</span>
              <span class="inline-flex items-center gap-1"><i data-lucide="bed-double" class="w-3.5 h-3.5"></i><?= $u['bedrooms'] ?> ห้องนอน</span>
              <span class="inline-flex items-center gap-1"><i data-lucide="shower-head" class="w-3.5 h-3.5"></i><?= $u['bathrooms'] ?> ห้องน้ำ</span>
              <?php if ($u['area_sqm']): ?><span class="inline-flex items-center gap-1"><i data-lucide="ruler" class="w-3.5 h-3.5"></i><?= $u['area_sqm'] ?> ตร.ม.</span><?php endif; ?>
            </div>
            <p class="text-sm text-slate-600 mt-2 line-clamp-2"><?= e($u['description']) ?></p>

            <div class="mt-auto pt-3 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
              <div class="w-full md:w-auto">
                <div class="text-xs text-slate-500">วันธรรมดา</div>
                <?php $detailLines = UnitPricing::formatDetailLines($u); ?>
                <div class="text-2xl font-extrabold text-primary-700"><?= e($detailLines['primary']) ?></div>
                <?php if (!empty($detailLines['included'])): ?><div class="text-xs text-slate-600 mt-0.5"><?= e($detailLines['included']) ?></div><?php endif; ?>
                <?php if (!empty($detailLines['extra'])): ?><div class="text-xs text-slate-500"><?= e($detailLines['extra']) ?></div><?php endif; ?>
                <?php if (!empty($detailLines['max_example'])): ?><div class="text-xs text-primary-600 font-medium"><?= e($detailLines['max_example']) ?></div><?php endif; ?>
                <?php if ($u['price_weekend'] > $u['price']): ?>
                <div class="text-xs text-slate-500">เสาร์-อาทิตย์ <?= format_money($u['price_weekend']) ?> · วันหยุด <?= format_money($u['price_holiday']) ?></div>
                <?php endif; ?>
              </div>
              <div class="flex w-full flex-col gap-2 md:w-auto md:flex-row md:flex-wrap md:justify-end">
                <?php if (in_array((string)($property['type'] ?? ''), ['raft', 'pool_villa'], true)): ?>
                  <button type="button"
                          @click.stop="$store.compare.toggle({
                            property_id: <?= (int)$property['id'] ?>,
                            unit_id: <?= (int)$u['id'] ?>,
                            title: <?= htmlspecialchars(json_encode((string)$u['name'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>,
                            subtitle: <?= htmlspecialchars(json_encode((string)$property['name'], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>,
                            image: <?= htmlspecialchars(json_encode(upload_url($heroSrc) ?: 'https://placehold.co/600x400', JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>,
                            detail_url: <?= htmlspecialchars(json_encode(url('/property/' . $property['slug'] . '?unit=' . $u['id']), JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>
                          })"
                          class="inline-flex w-full md:w-auto min-h-[2.75rem] items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg text-sm font-semibold border transition touch-manipulation"
                          :class="$store.compare.isSelected(<?= (int)$property['id'] ?>, <?= (int)$u['id'] ?>) ? 'bg-teal-600 border-teal-600 text-white' : 'border-teal-200 text-teal-700 hover:bg-teal-50'"
                          :aria-label="$store.compare.isSelected(<?= (int)$property['id'] ?>, <?= (int)$u['id'] ?>) ? 'เอาออกจากรายการเทียบ' : 'เพิ่มในรายการเทียบ'">
                    <i data-lucide="scale" class="w-4 h-4"></i>
                    <span x-text="$store.compare.isSelected(<?= (int)$property['id'] ?>, <?= (int)$u['id'] ?>) ? 'เลือกเทียบแล้ว' : 'เทียบหลังนี้'"></span>
                  </button>
                <?php endif; ?>
                <?php if ($hasMobileCta): ?><div class="hidden md:contents"><?php endif; ?>
                <?php \App\Core\View::partial('partials/property-booking-ctas', [
                  'property' => $property,
                  'unitId' => (int)$u['id'],
                  'variant' => 'inline',
                  'stopPropagation' => true,
                ]); ?>
                <?php if ($hasMobileCta): ?></div><?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Modal / Lightbox โหนดไป body เพื่อกัน fixed ถูกบิดจาก stacking context ภายใน <main> -->
      <template x-teleport="body">
      <div x-show="modalUnit" x-cloak class="fixed inset-0 z-[10050] flex items-center justify-center p-4 sm:p-6">
        <div class="absolute inset-0 bg-black/55 backdrop-blur-[3px]" @click="closeModal()" aria-hidden="true"></div>
        <div class="unit-modal-panel relative z-[1] grid w-full max-w-md grid-rows-[auto_auto_minmax(0,1fr)_auto] overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10 md:max-w-xl"
             @click.stop role="dialog" aria-modal="true" aria-labelledby="unit-modal-title">
          <div class="flex items-start justify-between gap-3 border-b border-primary-100/80 bg-gradient-to-r from-primary-50/95 via-white to-white px-5 py-4 sm:px-6">
            <h3 id="unit-modal-title" class="pr-2 text-base font-bold leading-snug text-slate-900 sm:text-lg" x-text="modalUnit ? modalUnit.name : ''"></h3>
            <button type="button" class="shrink-0 rounded-xl p-2 text-slate-500 transition hover:bg-primary-100/80 hover:text-slate-800" @click="closeModal()" aria-label="ปิด">
              <i data-lucide="x" class="h-5 w-5"></i>
            </button>
          </div>
          <div class="shrink-0 border-b border-slate-200">
            <div x-show="modalUnit && modalUnit.images && modalUnit.images.length" class="relative w-full" role="presentation">
              <div class="unit-modal-hero-wrap bg-slate-100">
                <img
                  :src="modalUnit && modalUnit.images && modalUnit.images[modalHeroIdx] ? modalUnit.images[modalHeroIdx] : ''"
                  class="absolute inset-0 h-full w-full cursor-zoom-in object-cover object-center transition-opacity duration-200"
                  @click="modalUnit && openLightbox(modalUnit.id, modalHeroIdx)"
                  alt=""
                >
              </div>
              <template x-if="modalUnit && modalUnit.images && modalUnit.images.length > 1">
                <div>
                  <button
                    type="button"
                    @click.stop="modalHeroPrev()"
                    class="absolute left-2 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-800 shadow-md ring-1 ring-slate-200/80 transition hover:bg-white"
                    aria-label="รูปก่อนหน้า"
                  >
                    <i data-lucide="chevron-left" class="h-5 w-5"></i>
                  </button>
                  <button
                    type="button"
                    @click.stop="modalHeroNext()"
                    class="absolute right-2 top-1/2 z-10 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-800 shadow-md ring-1 ring-slate-200/80 transition hover:bg-white"
                    aria-label="รูปถัดไป"
                  >
                    <i data-lucide="chevron-right" class="h-5 w-5"></i>
                  </button>
                  <div class="pointer-events-none absolute bottom-2 left-0 right-0 flex justify-center gap-1.5 px-3">
                    <template x-for="(dotSrc, idx) in modalUnit.images" :key="'modot'+modalUnit.id + '-' + idx">
                      <button
                        type="button"
                        @click.stop="modalHeroGo(idx)"
                        class="pointer-events-auto h-2 rounded-full transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-500"
                        :class="modalHeroIdx === idx ? 'w-5 bg-accent-500' : 'w-2 bg-white/85 hover:bg-white'"
                        :aria-label="'รูปที่ ' + (idx + 1)"
                      ></button>
                    </template>
                  </div>
                </div>
              </template>
            </div>
            <div
              x-show="modalUnit && modalUnit.images && modalUnit.images.length > 1"
              class="flex gap-2 overflow-x-auto border-t border-slate-200/90 bg-white px-3 py-2.5"
            >
              <template x-for="(src, idx) in (modalUnit ? modalUnit.images : [])" :key="'mthumb'+modalUnit.id+'-'+idx">
                <button
                  type="button"
                  @click="modalHeroGo(idx)"
                  class="h-12 w-12 shrink-0 overflow-hidden rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-500"
                  :class="modalHeroIdx === idx ? 'ring-2 ring-accent-500 ring-offset-1' : 'ring-1 ring-slate-200'"
                >
                  <img :src="src" class="h-full w-full object-cover" alt="">
                </button>
              </template>
            </div>
          </div>
          <div class="unit-modal-body-scroll min-h-0 overflow-y-auto overflow-x-hidden bg-white px-5 py-5 sm:px-6">
            <div class="space-y-4 text-sm text-slate-700">
              <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500" x-show="modalUnit">
                <span class="inline-flex items-center gap-1"><i data-lucide="users" class="h-3.5 w-3.5"></i><span x-text="modalUnit ? (modalUnit.capacity_min + '–' + modalUnit.capacity_max + ' ท่าน') : ''"></span></span>
                <span class="inline-flex items-center gap-1"><i data-lucide="bed-double" class="h-3.5 w-3.5"></i><span x-text="modalUnit ? (modalUnit.bedrooms + ' ห้องนอน') : ''"></span></span>
                <span class="inline-flex items-center gap-1"><i data-lucide="shower-head" class="h-3.5 w-3.5"></i><span x-text="modalUnit ? (modalUnit.bathrooms + ' ห้องน้ำ') : ''"></span></span>
                <template x-if="modalUnit && modalUnit.area_sqm">
                  <span class="inline-flex items-center gap-1"><i data-lucide="ruler" class="h-3.5 w-3.5"></i><span x-text="modalUnit.area_sqm + ' ตร.ม.'"></span></span>
                </template>
              </div>
              <div class="leading-relaxed whitespace-pre-line text-slate-700" x-text="modalUnit ? modalUnit.description : ''"></div>
              <div class="rounded-xl border border-primary-100 bg-primary-50/80 px-4 py-3" x-show="modalUnit">
                <div class="text-xs text-slate-500">วันธรรมดา</div>
                <div class="text-2xl font-extrabold text-primary-700"><span x-text="modalUnit ? modalUnit.price_label : ''"></span><span class="text-xs font-normal text-slate-500"> / คืน</span></div>
                <div class="mt-1 text-xs text-slate-600" x-show="modalUnit && modalUnit.price_extra" x-text="modalUnit ? modalUnit.price_extra : ''"></div>
              </div>
            </div>
          </div>
          <div class="flex shrink-0 flex-wrap justify-end gap-2 border-t border-slate-100 bg-slate-50/50 px-5 py-4 sm:px-6">
            <button type="button"
                    x-show="modalUnit && modalUnit.compare"
                    @click="$store.compare.toggle(modalUnit.compare)"
                    class="inline-flex items-center gap-1.5 rounded-lg border px-4 py-2.5 text-sm font-semibold transition"
                    :class="modalUnit && modalUnit.compare && $store.compare.isSelected(modalUnit.compare.property_id, modalUnit.compare.unit_id) ? 'bg-teal-600 border-teal-600 text-white' : 'border-teal-200 text-teal-700 hover:bg-teal-50'"
                    :aria-label="modalUnit && modalUnit.compare && $store.compare.isSelected(modalUnit.compare.property_id, modalUnit.compare.unit_id) ? 'เอาออกจากรายการเทียบ' : 'เพิ่มในรายการเทียบ'">
              <i data-lucide="scale" class="w-4 h-4"></i>
              <span x-text="modalUnit && modalUnit.compare && $store.compare.isSelected(modalUnit.compare.property_id, modalUnit.compare.unit_id) ? 'อยู่ในรายการเทียบ' : 'เทียบหลังนี้'"></span>
            </button>
            <template x-if="modalUnit && modalUnit.cta_urls && modalUnit.cta_urls.contact">
              <a :href="modalUnit.cta_urls.contact" class="inline-flex items-center gap-1.5 px-4 py-2.5 border border-primary-600 text-primary-700 hover:bg-primary-50 rounded-lg text-sm font-semibold">
                <i data-lucide="phone" class="w-4 h-4"></i> ติดต่อโดยตรง
              </a>
            </template>
            <template x-if="modalUnit && modalUnit.cta_urls && modalUnit.cta_urls.book_online">
              <a :href="modalUnit.cta_urls.book_online" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-accent-500 hover:bg-accent-600 text-white rounded-lg text-sm font-semibold">
                <i data-lucide="calendar-plus" class="w-4 h-4"></i> จองยูนิตนี้
              </a>
            </template>
            <template x-if="modalUnit && modalUnit.cta_urls && modalUnit.cta_urls.buy_coupon">
              <a :href="modalUnit.cta_urls.buy_coupon" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-gradient-to-r from-rose-500 to-rose-600 hover:from-rose-600 hover:to-rose-700 text-white rounded-lg text-sm font-semibold shadow-sm">
                <i data-lucide="gift" class="w-4 h-4"></i> <?= e(coupon_cta_label()) ?>
              </a>
            </template>
            <button type="button" @click="closeModal()" class="px-4 py-2.5 border border-slate-300 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50">ปิด</button>
          </div>
        </div>
      </div>
      </template>

      <template x-teleport="body">
      <div x-show="lbOpen" x-cloak class="paekan-lightbox-unit-wrap fixed inset-0 flex items-center justify-center p-2 sm:p-4" @keydown.window="lbOnWindowKeydown($event)">
        <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-[2px]" @click="closeLightbox()" aria-hidden="true"></div>
        <div class="paekan-lightbox-unit-card flex flex-col overflow-hidden rounded-2xl bg-slate-950"
             @click.stop role="dialog" aria-modal="true" aria-label="ดูรูปยูนิต">
          <div class="paekan-lightbox-unit-stage relative flex min-h-0 flex-1 items-center justify-center bg-gradient-to-b from-slate-900 to-black">
            <div class="paekan-lightbox-unit-chrome absolute inset-x-0 top-0 z-20 bg-gradient-to-b from-black/80 via-black/45 to-transparent px-3 pb-10 pt-3 sm:px-4 sm:pb-12 sm:pt-4">
              <div class="flex w-full items-center justify-between gap-3">
                <div class="min-w-0">
                  <span class="text-xs font-semibold tabular-nums text-white/95 drop-shadow sm:text-sm" x-show="lbImages.length > 1">
                    รูปที่ <span x-text="lbCurrent + 1"></span> / <span x-text="lbImages.length"></span>
                  </span>
                  <span class="text-xs text-white/80 sm:text-sm" x-show="lbImages.length <= 1">ดูรูปใหญ่</span>
                </div>
                <button type="button" @click="closeLightbox()" class="shrink-0 rounded-xl p-2 text-white transition hover:bg-white/20" aria-label="ปิด">
                  <i data-lucide="x" class="h-5 w-5"></i>
                </button>
              </div>
            </div>
            <template x-if="lbImages.length > 1">
              <button type="button" @click="lbPrev()" class="absolute left-2 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/22 p-2.5 text-white shadow-lg ring-1 ring-white/25 backdrop-blur-sm transition hover:bg-white/35 sm:left-3 sm:p-3" aria-label="ก่อนหน้า">
                <i data-lucide="chevron-left" class="h-6 w-6 text-white sm:h-7 sm:w-7"></i>
              </button>
            </template>
            <template x-if="lbImages.length > 1">
              <button type="button" @click="lbNext()" class="absolute right-2 top-1/2 z-10 -translate-y-1/2 rounded-full bg-white/22 p-2.5 text-white shadow-lg ring-1 ring-white/25 backdrop-blur-sm transition hover:bg-white/35 sm:right-3 sm:p-3" aria-label="ถัดไป">
                <i data-lucide="chevron-right" class="h-6 w-6 text-white sm:h-7 sm:w-7"></i>
              </button>
            </template>
            <img :src="lbImages[lbCurrent] || ''"
                 class="paekan-lightbox-unit-img relative z-[1]"
                 alt="">
          </div>
        </div>
      </div>
      </template>
    </div>

    <!-- Map -->
    <div>
      <h2 class="text-xl font-bold flex items-center gap-2 mb-3"><i data-lucide="map" class="w-5 h-5 text-accent-600"></i> ที่ตั้ง</h2>
      <div class="rounded-2xl overflow-hidden border border-slate-200 aspect-[16/9] bg-slate-100">
        <?php
        $lat = $property['latitude'] ?: 14.0228;
        $lng = $property['longitude'] ?: 99.5328;
        $bbox = ($lng-0.05).','.($lat-0.05).','.($lng+0.05).','.($lat+0.05);
        ?>
        <iframe class="w-full h-full" loading="lazy" src="https://www.openstreetmap.org/export/embed.html?bbox=<?= $bbox ?>&layer=mapnik&marker=<?= $lat ?>,<?= $lng ?>"></iframe>
      </div>
      <a href="<?= url('/property/lead/' . $property['id'] . '?type=map&unit=' . ($listingUid ?? 0)) ?>"
         class="mt-2 inline-flex items-center gap-1.5 text-sm text-primary-700 hover:text-accent-600">
        <i data-lucide="external-link" class="w-4 h-4"></i> เปิดใน Google Maps
      </a>
    </div>

    <!-- Rules -->
    <?php if ($property['rules']): ?>
    <div>
      <h2 class="text-xl font-bold flex items-center gap-2 mb-3"><i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600"></i> กฎการเข้าพัก</h2>
      <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 text-slate-700 whitespace-pre-line text-sm"><?= e($property['rules']) ?></div>
    </div>
    <?php endif; ?>

    <!-- Reviews -->
    <div id="reviews">
      <div class="flex items-end justify-between mb-3">
        <h2 class="text-xl font-bold flex items-center gap-2"><i data-lucide="message-circle" class="w-5 h-5 text-accent-600"></i> รีวิวจากผู้พัก (<?= (int)$property['rating_count'] ?>)</h2>
        <div class="flex items-center gap-1 text-2xl font-extrabold text-amber-500"><?= number_format((float)$property['rating_avg'],1) ?><i data-lucide="star" class="w-5 h-5 fill-current"></i></div>
      </div>
      <?php if (empty($reviews)): ?>
        <div class="bg-white border border-dashed border-slate-300 rounded-2xl p-6 text-center text-slate-500 text-sm">
          ยังไม่มีรีวิว เป็นคนแรกที่รีวิวที่พักนี้!
        </div>
      <?php else: ?>
        <div class="space-y-3">
        <?php foreach ($reviews as $r): ?>
          <div class="bg-white border border-slate-200 rounded-2xl p-5">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-primary-100 text-primary-700 grid place-items-center font-bold"><?= mb_substr($r['reviewer_name'],0,1) ?></div>
              <div class="flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-semibold"><?= e($r['reviewer_name']) ?></span>
                  <?php if ($r['is_verified']): ?>
                    <span class="text-xs px-1.5 py-0.5 bg-accent-100 text-accent-700 rounded-full inline-flex items-center gap-0.5"><i data-lucide="check" class="w-3 h-3"></i>เข้าพักจริง</span>
                  <?php endif; ?>
                </div>
                <div class="text-xs text-slate-500"><?= format_date_th($r['created_at']) ?></div>
              </div>
              <div><?= star_html((float)$r['rating']) ?></div>
            </div>
            <?php if ($r['title']): ?><div class="font-semibold mt-2"><?= e($r['title']) ?></div><?php endif; ?>
            <p class="text-sm text-slate-700 mt-1 leading-relaxed"><?= e($r['content']) ?></p>
          </div>
        <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- ===== STICKY SIDEBAR (CTA) ===== -->
  <aside class="lg:col-span-1">
    <div class="lg:sticky lg:top-24 space-y-4">

      <div class="bg-white border border-slate-200 rounded-2xl shadow-soft overflow-hidden">
        <div class="px-5 py-4 bg-primary-50 border-b border-primary-100">
          <div class="text-xs text-slate-500">เริ่มต้น</div>
          <div class="text-3xl font-extrabold text-primary-700"><?= format_money($property['min_price']) ?>
            <span class="text-xs font-normal text-slate-500">/ คืน</span>
          </div>
        </div>
        <div class="p-5 space-y-3">
          <?php \App\Core\View::partial('partials/property-booking-ctas', [
            'property' => $property,
            'unitId' => 0,
            'variant' => 'sidebar',
          ]); ?>

          <?php
          $em = trim((string)($property['contact_email'] ?? ''));
          $ws = trim((string)($property['website_url'] ?? ''));
          $wsHref = $ws !== '' ? (preg_match('#^https?://#i', $ws) ? $ws : 'https://' . $ws) : '';
          ?>
          <?php if ($em !== ''): ?>
          <a href="mailto:<?= e($em) ?>"
             class="w-full inline-flex items-center justify-center gap-2 py-3 border border-slate-300 hover:bg-slate-50 rounded-xl text-slate-700 font-semibold text-sm">
            <i data-lucide="mail" class="w-4 h-4"></i> <?= e($em) ?>
          </a>
          <?php endif; ?>
          <?php if ($wsHref !== ''): ?>
          <a href="<?= e($wsHref) ?>" target="_blank" rel="noopener noreferrer"
             class="w-full inline-flex items-center justify-center gap-2 py-3 border border-slate-300 hover:bg-slate-50 rounded-xl text-slate-700 font-semibold text-sm">
            <i data-lucide="globe" class="w-4 h-4"></i> เว็บไซต์
          </a>
          <?php endif; ?>
        </div>

        <div class="border-t border-slate-100 p-5 text-xs text-slate-600 space-y-1.5">
          <div class="flex items-center gap-1.5"><i data-lucide="shield-check" class="w-3.5 h-3.5 text-accent-600"></i> ระบบจองปลอดภัย</div>
          <div class="flex items-center gap-1.5"><i data-lucide="undo-2" class="w-3.5 h-3.5 text-accent-600"></i> ยกเลิกได้ตามนโยบาย</div>
          <div class="flex items-center gap-1.5"><i data-lucide="message-circle" class="w-3.5 h-3.5 text-accent-600"></i> ติดต่อทีมงาน 24/7</div>
        </div>
      </div>

      <!-- FAQ -->
      <div class="bg-white border border-slate-200 rounded-2xl p-5" x-data="{open:0}">
        <h3 class="font-bold mb-3 flex items-center gap-2"><i data-lucide="help-circle" class="w-5 h-5 text-accent-600"></i> คำถามที่พบบ่อย</h3>
        <?php
        $faqs = [
          ['คูปองใช้ยังไง?', 'ซื้อคูปองมูลค่า 500 ในราคา 250 → กรอกรหัสตอนจอง → ทางที่พักจะ verify ตอน check-in'],
          ['สามารถยกเลิกได้ไหม?', 'สามารถยกเลิกตามนโยบายของแต่ละที่พัก แนะนำติดต่อก่อนวันเข้าพักอย่างน้อย 7 วัน'],
          ['ต้องวางมัดจำเท่าไหร่?', 'มัดจำ ' . format_money($property['deposit_amount']) . ' (คืนเมื่อไม่มีความเสียหาย)'],
        ];
        foreach ($faqs as $i=>$q): ?>
        <div class="border-b border-slate-100 last:border-0">
          <button @click="open===<?= $i ?>?open=null:open=<?= $i ?>" class="w-full text-left py-3 flex items-center justify-between font-medium text-sm">
            <?= e($q[0]) ?>
            <i data-lucide="chevron-down" class="w-4 h-4 transition" :class="open===<?= $i ?>?'rotate-180':''"></i>
          </button>
          <div x-show="open===<?= $i ?>" x-collapse class="text-sm text-slate-600 pb-3"><?= e($q[1]) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </aside>

  <?php if ($hasMobileCta):
    $mobileStickyCols = 0;
    foreach (['book_online', 'contact', 'line', 'buy_coupon'] as $stickyKey) {
        if (!empty($propertyCtaUrls[$stickyKey])) {
            $mobileStickyCols++;
        }
    }
    $mobileStickyGrid = match (max(1, $mobileStickyCols)) {
        1 => 'grid-cols-1',
        2 => 'grid-cols-2',
        3 => 'grid-cols-3',
        default => 'grid-cols-4',
    };
  ?>
  <div class="fixed inset-x-0 bottom-[4.75rem] z-40 border-t border-slate-200/90 bg-white/96 px-3 py-2.5 shadow-[0_-8px_30px_rgba(15,23,42,0.08)] backdrop-blur md:hidden"
       style="padding-right: max(4.5rem, calc(4.5rem + env(safe-area-inset-right)));">
    <div class="grid gap-2 <?= e($mobileStickyGrid) ?>">
      <?php if (!empty($propertyCtaUrls['book_online'])): ?>
      <a href="<?= e($propertyCtaUrls['book_online']) ?>"
         class="inline-flex min-h-[3rem] items-center justify-center gap-1.5 rounded-xl border border-accent-200 bg-accent-50 px-2 py-3 text-sm font-extrabold text-accent-700 touch-manipulation active:bg-accent-100">
        <i data-lucide="calendar-plus" class="h-4 w-4 shrink-0"></i> จอง
      </a>
      <?php endif; ?>
      <?php if (!empty($propertyCtaUrls['contact'])): ?>
      <a href="<?= e($propertyCtaUrls['contact']) ?>"
         class="inline-flex min-h-[3rem] items-center justify-center gap-1.5 rounded-xl border-2 border-primary-600 bg-white px-2 py-3 text-sm font-extrabold text-primary-700 touch-manipulation active:bg-primary-50">
        <i data-lucide="phone" class="h-4 w-4 shrink-0"></i> โทร
      </a>
      <?php endif; ?>
      <?php if (!empty($propertyCtaUrls['line'])): ?>
      <a href="<?= e($propertyCtaUrls['line']) ?>"
         class="inline-flex min-h-[3rem] items-center justify-center gap-1.5 rounded-xl bg-[#06C755] px-2 py-3 text-sm font-extrabold text-white shadow-md touch-manipulation active:bg-[#05b34c]">
        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>
        LINE
      </a>
      <?php endif; ?>
      <?php if (!empty($propertyCtaUrls['buy_coupon'])): ?>
      <a href="<?= e($propertyCtaUrls['buy_coupon']) ?>"
         class="inline-flex min-h-[3rem] items-center justify-center gap-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 px-2 py-3 text-sm font-extrabold text-white shadow-lg touch-manipulation active:bg-rose-700">
        <i data-lucide="gift" class="h-4 w-4 shrink-0"></i> <?= e(coupon_cta_label(true)) ?>
      </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</section>

<!-- Similar -->
<?php if (!empty($similar)): ?>
<section class="max-w-7xl mx-auto px-4 sm:px-6 mt-12">
  <h2 class="text-xl md:text-2xl font-bold mb-4 flex items-center gap-2"><i data-lucide="layers" class="w-5 h-5 text-accent-600"></i> ที่พักใกล้เคียง</h2>
  <div class="md:hidden">
  <?php \App\Core\View::partial('partials/property-horizontal-mobile-stack', [
    'properties' => $similar,
    'wrapperClass' => 'w-full',
  ]); ?>
  </div>
  <div class="hidden md:grid md:grid-cols-2 lg:grid-cols-3 gap-5">
    <?php foreach ($similar as $property): \App\Core\View::partial('partials/property-card', ['property' => $property]); endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($highlight_unit_id > 0): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var el = document.getElementById('unit-<?= (int) $highlight_unit_id ?>');
  if (!el) return;
  /* เลื่อนไปโซนยูนิตเบื้องหลัง — Modal จะเปิดอัตโนมัติจาก Alpine */
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
});
</script>
<?php endif; ?>
