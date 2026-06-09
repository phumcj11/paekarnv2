<?php
use App\Services\CompareService;
/** @var array<int,array<string,mixed>> $rows */
$rows = $rows ?? [];
$maxItems = (int)($max_items ?? CompareService::MAX_ITEMS);
$minPrice = null;
$maxCapacity = null;
foreach ($rows as $r) {
    $price = (float)($r['price'] ?? 0);
    if ($price > 0 && ($minPrice === null || $price < $minPrice)) {
        $minPrice = $price;
    }
    $cap = (int)($r['capacity_max'] ?? 0);
    if ($cap > 0 && ($maxCapacity === null || $cap > $maxCapacity)) {
        $maxCapacity = $cap;
    }
}
$rowCount = count($rows);
$petMap = ['not_allowed' => 'ไม่อนุญาต', 'allowed' => 'รับสัตว์เลี้ยง', 'on_request' => 'แจ้งล่วงหน้า'];
$raftMap = ['shore' => 'แพริมน้ำ', 'towed' => 'แพลาก'];

/**
 * @param bool $mobile ถ้า true ย่อ amenities เหลือ 3 chips
 */
$cell = static function (array $r, string $kind, bool $mobile = false) use ($minPrice, $maxCapacity, $petMap, $raftMap, $rowCount): string {
    ob_start();
    if ($kind === 'price') {
        $isBest = $minPrice !== null && (float)$r['price'] === (float)$minPrice;
        ?>
        <div class="<?= $isBest ? 'rounded-xl bg-emerald-50 p-2 ring-1 ring-emerald-100' : '' ?>">
          <div class="<?= $mobile ? 'text-base' : 'text-lg' ?> font-extrabold text-forest-900"><?= e((string)$r['price_label']) ?></div>
          <?php if (!empty($r['price_note'])): ?><div class="mt-0.5 text-[11px] font-semibold text-slate-500"><?= e((string)$r['price_note']) ?></div><?php endif; ?>
          <?php if ($isBest): ?><span class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold text-white"><i data-lucide="badge-check" class="h-3 w-3"></i> ราคาดีสุด</span><?php endif; ?>
        </div>
        <?php
    } elseif ($kind === 'beds') {
        echo '<span class="font-bold text-slate-800">' . (int)$r['bedrooms'] . ' ห้องนอน</span>';
    } elseif ($kind === 'baths') {
        echo '<span class="font-bold text-slate-800">' . (int)$r['bathrooms'] . ' ห้องน้ำ</span>';
    } elseif ($kind === 'capacity') {
        $isBest = $maxCapacity !== null && (int)$r['capacity_max'] === (int)$maxCapacity;
        echo '<span class="font-bold text-slate-800">' . (int)$r['capacity_min'] . '–' . (int)$r['capacity_max'] . ' ท่าน</span>';
        if ($isBest && $rowCount > 1) {
            echo '<span class="ml-1 inline-flex items-center gap-0.5 rounded-full bg-sky-100 px-1.5 py-0.5 text-[10px] font-bold text-sky-800">มากสุด</span>';
        }
    } elseif ($kind === 'zone') {
        $loc = trim((string)($r['zone'] ?: $r['district'] ?: 'กาญจนบุรี'));
        echo '<span class="font-semibold text-slate-700">' . e($loc) . '</span>';
    } elseif ($kind === 'coupon') {
        $yes = (int)$r['coupon_enabled'] === 1;
        echo '<span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-bold ' . ($yes ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-100' : 'bg-slate-100 text-slate-500') . '">';
        echo '<i data-lucide="' . ($yes ? 'ticket' : 'x-circle') . '" class="h-3 w-3"></i>' . ($yes ? 'ได้' : 'ไม่ร่วม') . '</span>';
    } elseif ($kind === 'review') {
        if ((int)$r['rating_count'] > 0) {
            echo '<span class="inline-flex items-center gap-1 font-bold text-slate-800"><i data-lucide="star" class="h-3.5 w-3.5 fill-amber-400 text-amber-500"></i>' . number_format((float)$r['rating_avg'], 1) . ' <span class="text-[10px] text-slate-400">(' . (int)$r['rating_count'] . ')</span></span>';
        } else {
            echo '<span class="text-[11px] font-semibold text-slate-400">ยังไม่มี</span>';
        }
    } elseif ($kind === 'pet') {
        $v = $petMap[(string)$r['pet_policy']] ?? '-';
        echo '<span class="font-semibold text-slate-700">' . e($v) . '</span>';
    } elseif ($kind === 'raft') {
        echo '<span class="font-semibold text-slate-700">' . e((string)($raftMap[(string)($r['raft_variant'] ?? '')] ?? ($r['type'] === 'pool_villa' ? 'พูลวิลล่า' : '-'))) . '</span>';
    } elseif ($kind === 'amenities') {
        $ams = $r['amenities'] ?? [];
        if ($ams === []) {
            echo '<span class="text-[11px] font-semibold text-slate-400">ยังไม่ระบุ</span>';
        } else {
            $limit = $mobile ? 3 : 8;
            $extra = count($ams) - $limit;
            echo '<div class="flex flex-wrap gap-1">';
            foreach (array_slice($ams, 0, $limit) as $am) {
                echo '<span class="inline-flex items-center gap-0.5 rounded-full bg-slate-50 px-1.5 py-0.5 text-[10px] font-semibold text-slate-700 ring-1 ring-slate-200"><i data-lucide="' . e((string)$am['icon']) . '" class="h-2.5 w-2.5 text-teal-600"></i>' . e((string)$am['name']) . '</span>';
            }
            if ($extra > 0) {
                echo '<span class="inline-flex items-center rounded-full bg-teal-50 px-1.5 py-0.5 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100">+' . $extra . '</span>';
            }
            echo '</div>';
        }
    } elseif ($kind === 'cta') {
        if (!empty($r['book_url'])) {
            echo '<a href="' . e((string)$r['book_url']) . '" class="mb-2 inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-accent-500 px-3 py-2.5 text-xs font-extrabold text-white hover:bg-accent-600"><i data-lucide="calendar-check" class="h-4 w-4"></i> จอง</a>';
        }
        if (!empty($r['buy_coupon_url'])) {
            echo '<a href="' . e((string)$r['buy_coupon_url']) . '" class="mb-2 inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-gradient-to-r from-rose-500 to-rose-600 px-3 py-2.5 text-xs font-extrabold text-white hover:from-rose-600 hover:to-rose-700"><i data-lucide="gift" class="h-4 w-4"></i> ' . e(coupon_cta_label()) . '</a>';
        } elseif (empty($r['book_url']) && !empty($r['phone_url'])) {
            echo '<a href="' . e((string)$r['phone_url']) . '" class="mb-2 inline-flex w-full items-center justify-center gap-1.5 rounded-xl bg-primary-700 px-3 py-2.5 text-xs font-extrabold text-white hover:bg-primary-800"><i data-lucide="phone" class="h-4 w-4"></i> ติดต่อ</a>';
        }
        echo '<a href="' . e((string)$r['detail_url']) . '" class="inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50"><i data-lucide="external-link" class="h-4 w-4"></i> ดูรายละเอียด</a>';
    }
    return trim(ob_get_clean());
};

$rowDefs = [
    ['banknote',    'ราคาเริ่มต้น', 'price'],
    ['bed-double',  'ห้องนอน',      'beds'],
    ['bath',        'ห้องน้ำ',       'baths'],
    ['users',       'ความจุ',        'capacity'],
    ['map-pin',     'โซน',           'zone'],
    ['ticket',      'คูปอง',         'coupon'],
    ['star',        'รีวิว',          'review'],
    ['paw-print',   'สัตว์เลี้ยง',   'pet'],
    ['anchor',      'ประเภท',         'raft'],
    ['sparkles',    'สิ่งอำนวยฯ',    'amenities'],
];
?>

<!-- ===== Mobile compact bar ===== -->
<div class="sticky top-14 z-30 border-b border-slate-200/90 bg-white/95 shadow-sm backdrop-blur md:hidden">
  <div class="flex items-center gap-2 px-3 py-2.5">
    <div class="flex min-w-0 flex-1 items-center gap-2">
      <i data-lucide="git-compare-arrows" class="h-4 w-4 shrink-0 text-teal-600"></i>
      <span class="truncate text-sm font-extrabold text-slate-900">เทียบ <?= $rowCount ?> หลัง</span>
    </div>
    <a href="<?= url('/rafts') ?>" class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-teal-200 bg-teal-50 text-teal-700" aria-label="เพิ่มแพ">
      <i data-lucide="plus" class="h-4 w-4"></i>
    </a>
    <button type="button" onclick="paekanCompareSaveImage()" class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-teal-200 bg-teal-600 text-white shadow-sm" aria-label="บันทึกภาพเปรียบเทียบ">
      <i data-lucide="image-down" class="h-4 w-4"></i>
    </button>
    <button type="button" x-data @click="$store.compare.clear()" class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-slate-200 bg-white text-slate-600" aria-label="ล้างทั้งหมด">
      <i data-lucide="eraser" class="h-4 w-4"></i>
    </button>
  </div>
</div>

<!-- ===== Desktop hero section ===== -->
<section class="hidden bg-gradient-to-b from-teal-50 via-white to-cloud md:block">
  <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:py-10">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
      <div>
        <div class="inline-flex items-center gap-2 rounded-full border border-teal-100 bg-white px-3 py-1 text-xs font-bold text-teal-700 shadow-sm">
          <i data-lucide="git-compare-arrows" class="h-4 w-4"></i>
          เทียบได้สูงสุด <?= $maxItems ?> หลัง
        </div>
        <h1 class="mt-3 font-heading text-3xl font-extrabold tracking-tight text-slate-950 md:text-4xl">เทียบแพที่เลือก</h1>
        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-600">ดูราคา ความจุ ห้องนอน รีวิว คูปอง และสิ่งอำนวยความสะดวกของแพพัก/บ้านพูลวิลล่าแต่ละหลังในหน้าเดียว</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button type="button" onclick="paekanCompareSaveImage()" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-teal-700">
          <i data-lucide="image-down" class="h-4 w-4"></i> บันทึกภาพ
        </button>
        <a href="<?= url('/rafts') ?>" class="inline-flex items-center gap-2 rounded-xl border border-teal-200 bg-white px-4 py-2.5 text-sm font-bold text-teal-700 shadow-sm hover:bg-teal-50">
          <i data-lucide="plus" class="h-4 w-4"></i> เพิ่มแพ
        </a>
        <button type="button" x-data @click="$store.compare.clear()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 shadow-sm hover:bg-slate-50">
          <i data-lucide="eraser" class="h-4 w-4"></i> ล้างทั้งหมด
        </button>
      </div>
    </div>
  </div>
</section>

<?php if ($rows === []): ?>
<section class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
  <div class="rounded-3xl border border-dashed border-teal-200 bg-white p-8 text-center shadow-sm">
    <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-teal-50 text-teal-700">
      <i data-lucide="scale" class="h-8 w-8"></i>
    </div>
    <h2 class="mt-4 text-xl font-extrabold text-slate-900">ยังไม่มีแพในรายการเทียบ</h2>
    <p class="mt-2 text-sm text-slate-600">กดปุ่มตาชั่งมุมล่างขวา หรือบนการ์ดแพ เพื่อเพิ่มเข้ารายการเทียบ</p>
    <a href="<?= url('/rafts') ?>" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-teal-600 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-teal-900/15 hover:bg-teal-700">
      <i data-lucide="search" class="h-4 w-4"></i> ค้นหาแพ
    </a>
  </div>
</section>
<?php else:
// inline style สำหรับ column width (หลีกเลี่ยง Tailwind arbitrary value ที่อาจไม่ได้ generate)
$mobileColStyle = match (true) {
    $rowCount <= 1 => 'flex:none;width:calc(100vw - 6.5rem)',
    $rowCount === 2 => 'flex:none;width:calc((100vw - 6.5rem) / 2)',
    default         => 'flex:none;width:min(13rem, calc((100vw - 6.5rem) * 0.56))',
};
?>

<!-- ===== มือถือ: row-based sticky-label slide ===== -->
<section class="px-2 pb-32 pt-2 md:hidden" x-data="paekanCompareMobile(<?= $rowCount ?>)">

  <!-- Dots indicator + counter — เหนือตาราง -->
  <div class="mb-2 flex items-center justify-between gap-2 px-1">
    <div class="flex min-w-0 flex-1 items-center gap-1.5 overflow-x-auto no-scrollbar">
      <?php foreach ($rows as $i => $r): ?>
      <button type="button"
              @click="goTo(<?= (int)$i ?>)"
              class="shrink-0 rounded-full transition-all duration-200"
              :class="active === <?= (int)$i ?> ? 'h-2.5 w-7 bg-teal-600 shadow-sm' : 'h-2 w-2 bg-slate-300 hover:bg-slate-400'"
              aria-label="ไปหลังที่ <?= (int)$i + 1 ?>"></button>
      <?php endforeach; ?>
    </div>
    <p class="shrink-0 text-[11px] font-extrabold tabular-nums text-slate-500" x-text="'หลังที่ ' + (active + 1) + ' / ' + total"></p>
  </div>

  <!--
    Row-based layout: scroller = overflow-x-auto wrapper
    แต่ละแถวคือ flex row มี sticky label ซ้าย + data cells ขวา
    ทุก cell ในแถวเดียวกัน = ความสูงเท่ากันเสมอ (CSS flex alignment)
  -->
  <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-lg">
    <div x-ref="scroller"
         @scroll.passive="onScroll()"
         class="overflow-x-auto no-scrollbar">

      <!-- ===== Header row: thumbnail + ชื่อ ===== -->
      <div class="flex border-b border-slate-200 bg-slate-50/80">
        <!-- label cell -->
        <div class="sticky left-0 z-10 flex items-end shrink-0 border-r border-slate-200 bg-slate-50/95 px-2 pb-2 text-[10px] font-extrabold uppercase tracking-wide text-slate-400"
             style="width:5.5rem">แพ</div>
        <!-- data cells -->
        <?php foreach ($rows as $i => $r): ?>
        <div class="relative shrink-0 border-r border-slate-100 p-2 last:border-r-0"
             style="<?= e($mobileColStyle) ?>"
             data-compare-col="<?= (int)$i ?>">
          <div class="flex items-start gap-2">
            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200">
              <img src="<?= e((string)$r['cover_url']) ?>" alt="" class="h-full w-full object-cover" loading="lazy">
            </div>
            <div class="min-w-0 flex-1 pt-0.5">
              <p class="line-clamp-2 text-[11px] font-extrabold leading-snug text-slate-950"><?= e((string)$r['unit_name']) ?></p>
              <p class="mt-0.5 line-clamp-1 text-[9px] font-semibold text-slate-500"><?= e((string)$r['property_name']) ?></p>
            </div>
          </div>
          <button type="button"
                  @click="removeItem(<?= (int)$r['property_id'] ?>, <?= (int)$r['unit_id'] ?>)"
                  class="absolute right-1 top-1 grid h-6 w-6 place-items-center rounded-full bg-white/95 text-slate-500 shadow ring-1 ring-slate-200"
                  aria-label="ลบออกจากรายการเทียบ">
            <i data-lucide="x" class="h-3 w-3"></i>
          </button>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- ===== Data rows — ความสูงทุก cell ในแถวเท่ากันเสมอ ===== -->
      <?php foreach ($rowDefs as [$icon, $label, $kind]): ?>
      <div class="flex border-t border-slate-100">
        <!-- label cell -->
        <div class="sticky left-0 z-10 flex shrink-0 items-start border-r border-slate-200 bg-white/98 px-2 pt-2.5 pb-2"
             style="width:5.5rem">
          <i data-lucide="<?= e($icon) ?>" class="mr-1 mt-0.5 h-3 w-3 shrink-0 text-teal-600"></i>
          <span class="text-[10px] font-extrabold leading-tight text-slate-600"><?= e($label) ?></span>
        </div>
        <!-- data cells -->
        <?php foreach ($rows as $r): ?>
        <div class="shrink-0 border-r border-slate-100 px-2 pt-2.5 pb-2 text-[11px] leading-snug text-slate-700 last:border-r-0"
             style="<?= e($mobileColStyle) ?>">
          <?= $cell($r, $kind, true) ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>

      <!-- ===== CTA row — ปุ่มอยู่ในตาราง ไม่ลอยซ้ำด้านล่าง ===== -->
      <div class="flex border-t border-slate-200">
        <div class="sticky left-0 z-10 shrink-0 border-r border-slate-200 bg-white/98 px-2 pt-2.5 pb-2"
             style="width:5.5rem">
          <span class="text-[10px] font-extrabold leading-tight text-slate-600">ดำเนินการ</span>
        </div>
        <?php foreach ($rows as $r): ?>
        <div class="shrink-0 border-r border-slate-100 p-2 last:border-r-0"
             style="<?= e($mobileColStyle) ?>">
          <?php if (!empty($r['book_url'])): ?>
          <a href="<?= e((string)$r['book_url']) ?>" class="inline-flex w-full items-center justify-center gap-1 rounded-lg bg-accent-500 px-2 py-2 text-[11px] font-extrabold text-white">
            <i data-lucide="calendar-check" class="h-3.5 w-3.5"></i> จอง
          </a>
          <?php endif; ?>
          <?php if (!empty($r['buy_coupon_url'])): ?>
          <a href="<?= e((string)$r['buy_coupon_url']) ?>" class="<?= !empty($r['book_url']) ? 'mt-1 ' : '' ?>inline-flex w-full items-center justify-center gap-1 rounded-lg bg-gradient-to-r from-rose-500 to-rose-600 px-2 py-2 text-[11px] font-extrabold text-white">
            <i data-lucide="gift" class="h-3.5 w-3.5"></i> <?= e(coupon_cta_label(true)) ?>
          </a>
          <?php elseif (empty($r['book_url']) && !empty($r['phone_url'])): ?>
          <a href="<?= e((string)$r['phone_url']) ?>" class="inline-flex w-full items-center justify-center gap-1 rounded-lg bg-primary-700 px-2 py-2 text-[11px] font-extrabold text-white">
            <i data-lucide="phone" class="h-3.5 w-3.5"></i> โทร
          </a>
          <?php endif; ?>
          <a href="<?= e((string)$r['detail_url']) ?>" class="mt-1 inline-flex w-full items-center justify-center gap-1 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[10px] font-bold text-slate-600">
            <i data-lucide="external-link" class="h-3 w-3"></i> ดูทั้งหมด
          </a>
        </div>
        <?php endforeach; ?>
      </div>

    </div><!-- /scroller -->
  </div>

  <button type="button" onclick="paekanCompareSaveImage()"
          class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-extrabold text-teal-800 shadow-sm active:scale-[0.99]">
    <i data-lucide="image-down" class="h-5 w-5"></i> บันทึกภาพเปรียบเทียบ
  </button>
</section>

<script>
function paekanCompareMobile(total) {
  return {
    active: 0,
    total: total,
    _snapTimer: null,
    _cw() {
      // column width = offsetWidth of any data-compare-col cell
      const s = this.$refs.scroller;
      const el = s && s.querySelector('[data-compare-col]');
      return el ? el.offsetWidth : 160;
    },
    goTo(i) {
      const s = this.$refs.scroller;
      if (!s) return;
      s.scrollTo({ left: i * this._cw(), behavior: 'smooth' });
      this.active = i;
    },
    onScroll() {
      const s = this.$refs.scroller;
      if (!s) return;
      const cw = this._cw();
      if (cw <= 0) return;
      // Update dot indicator as user drags
      this.active = Math.max(0, Math.min(this.total - 1, Math.round(s.scrollLeft / cw)));
      // Snap to nearest column after user lifts finger
      clearTimeout(this._snapTimer);
      this._snapTimer = setTimeout(() => {
        const idx = Math.max(0, Math.min(this.total - 1, Math.round(s.scrollLeft / cw)));
        s.scrollTo({ left: idx * cw, behavior: 'smooth' });
        this.active = idx;
      }, 120);
    },
    removeItem(propertyId, unitId) {
      if (!window.Alpine || !Alpine.store('compare')) return;
      Alpine.store('compare').remove(propertyId, unitId);
      window.setTimeout(function () {
        window.location.href = Alpine.store('compare').compareUrl();
      }, 120);
    }
  };
}
</script>

<!-- ===== Desktop: ตารางเทียบ ===== -->
<section class="mx-auto hidden max-w-7xl px-4 py-6 sm:px-6 md:block">
  <div x-data class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_18px_60px_-30px_rgba(15,23,42,0.35)]">
    <div class="overflow-x-auto">
      <div class="min-w-[760px]">
        <div class="grid border-b border-slate-200 bg-slate-50/80" style="grid-template-columns: 150px repeat(<?= max(1, count($rows)) ?>, minmax(200px, 1fr));">
          <div class="sticky left-0 z-10 bg-slate-50/95 p-4 text-xs font-extrabold uppercase tracking-wide text-slate-500">รายการ</div>
          <?php foreach ($rows as $r): ?>
          <div class="relative p-3">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
              <div class="relative aspect-[4/3] bg-slate-100">
                <img src="<?= e((string)$r['cover_url']) ?>" alt="" class="h-full w-full object-cover">
                <button type="button"
                        x-data
                        @click="$store.compare.remove(<?= (int)$r['property_id'] ?>, <?= (int)$r['unit_id'] ?>)"
                        class="absolute right-2 top-2 grid h-8 w-8 place-items-center rounded-full bg-white/95 text-slate-700 shadow ring-1 ring-slate-200"
                        aria-label="ลบออกจากรายการเทียบ">
                  <i data-lucide="x" class="h-4 w-4"></i>
                </button>
              </div>
              <div class="p-3">
                <h2 class="line-clamp-2 text-sm font-extrabold leading-snug text-slate-950"><?= e((string)$r['unit_name']) ?></h2>
                <p class="mt-0.5 line-clamp-1 text-xs font-semibold text-slate-500"><?= e((string)$r['property_name']) ?></p>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <?php
        $desktopRowDefs = array_merge($rowDefs, [['calendar-check', 'ดำเนินการ', 'cta']]);
        foreach ($desktopRowDefs as [$icon, $label, $kind]):
        ?>
        <div class="grid border-b border-slate-100 last:border-b-0" style="grid-template-columns: 150px repeat(<?= max(1, count($rows)) ?>, minmax(200px, 1fr));">
          <div class="sticky left-0 z-10 flex items-center gap-2 border-r border-slate-100 bg-white/95 p-4 text-xs font-extrabold text-slate-700">
            <i data-lucide="<?= e($icon) ?>" class="h-4 w-4 text-teal-600"></i>
            <?= e($label) ?>
          </div>
          <?php foreach ($rows as $r): ?>
          <div class="p-4 text-sm text-slate-700"><?= $cell($r, $kind) ?></div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<?php
$exportColStyle = 'flex:none;width:9.5rem';
$exportLabelW = '5.5rem';
$exportDate = date('j/n/Y H:i');
?>
<!-- โหลด off-screen สำหรับ html2canvas — แสดงทุกคอลัมน์ในภาพเดียว -->
<div id="paekan-compare-export" aria-hidden="true"
     class="pointer-events-none fixed top-0 overflow-hidden bg-white font-sans"
     style="left:-10000px;width:<?= (int)(88 + count($rows) * 152 + 24) ?>px">
  <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white">
    <div class="flex items-center gap-3 border-b border-teal-100 bg-gradient-to-r from-teal-50 to-white px-4 py-3">
      <img src="<?= e(asset('site-logo.png')) ?>" alt="" crossorigin="anonymous" class="h-10 w-10 rounded-xl object-cover ring-1 ring-slate-200">
      <div class="min-w-0 flex-1">
        <div class="text-sm font-extrabold text-slate-900">แพกาญ.com — เทียบแพ</div>
        <div class="text-[11px] font-semibold text-slate-500">เลือก <?= $rowCount ?> หลัง · <?= e($exportDate) ?> น.</div>
      </div>
    </div>

    <div class="flex border-b border-slate-200 bg-slate-50/90">
      <div class="shrink-0 border-r border-slate-200 px-2 py-2 text-[10px] font-extrabold uppercase text-slate-400" style="width:<?= e($exportLabelW) ?>">แพ</div>
      <?php foreach ($rows as $r): ?>
      <div class="shrink-0 border-r border-slate-100 p-2 last:border-r-0" style="<?= e($exportColStyle) ?>">
        <div class="overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200">
          <img src="<?= e((string)$r['cover_url']) ?>" alt="" crossorigin="anonymous" class="h-20 w-full object-cover">
        </div>
        <p class="mt-1.5 line-clamp-2 text-[11px] font-extrabold leading-snug text-slate-950"><?= e((string)$r['unit_name']) ?></p>
        <p class="mt-0.5 line-clamp-1 text-[9px] font-semibold text-slate-500"><?= e((string)$r['property_name']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    <?php foreach ($rowDefs as [$icon, $label, $kind]): ?>
    <div class="flex border-t border-slate-100">
      <div class="flex shrink-0 items-start border-r border-slate-200 bg-white px-2 py-2" style="width:<?= e($exportLabelW) ?>">
        <span class="text-[10px] font-extrabold leading-tight text-slate-600"><?= e($label) ?></span>
      </div>
      <?php foreach ($rows as $r): ?>
      <div class="shrink-0 border-r border-slate-100 px-2 py-2 text-[11px] text-slate-700 last:border-r-0" style="<?= e($exportColStyle) ?>">
        <?= $cell($r, $kind, true) ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>

    <div class="border-t border-slate-200 bg-slate-50 px-4 py-2 text-center text-[10px] font-semibold text-slate-500">
      paekan.com · ข้อมูล ณ วันที่บันทึกภาพ
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
async function paekanCompareSaveImage() {
  var el = document.getElementById('paekan-compare-export');
  if (!el) return;
  if (typeof html2canvas !== 'function') {
    if (window.Swal) {
      Swal.fire({ icon: 'error', title: 'โหลดเครื่องมือไม่สำเร็จ', text: 'ลองรีเฟรชหน้าใหม่', confirmButtonColor: '#0d9488' });
    }
    return;
  }

  if (window.lucide) {
    window.lucide.createIcons();
  }

  var loading = null;
  if (window.Swal) {
    loading = Swal.fire({
      title: 'กำลังสร้างภาพ...',
      text: 'รอสักครู่',
      allowOutsideClick: false,
      didOpen: function () { Swal.showLoading(); }
    });
  }

  try {
    var canvas = await html2canvas(el, {
      scale: 2,
      useCORS: true,
      backgroundColor: '#ffffff',
      logging: false,
      onclone: function (doc) {
        var cloned = doc.getElementById('paekan-compare-export');
        if (cloned) {
          cloned.style.position = 'static';
          cloned.style.left = '0';
          cloned.style.zIndex = '1';
        }
      }
    });

    var fileName = 'paekan-compare-' + new Date().toISOString().slice(0, 10) + '.png';
    var isMobile = window.matchMedia('(max-width: 767px)').matches;

    canvas.toBlob(async function (blob) {
      if (!blob) {
        throw new Error('blob failed');
      }

      if (isMobile && navigator.share && navigator.canShare) {
        var file = new File([blob], fileName, { type: 'image/png' });
        if (navigator.canShare({ files: [file] })) {
          try {
            await navigator.share({ files: [file], title: 'เทียบแพ — แพกาญ.com' });
            if (loading) Swal.close();
            Swal.fire({
              toast: true,
              position: 'top',
              icon: 'success',
              title: 'บันทึก/แชร์ภาพแล้ว',
              showConfirmButton: false,
              timer: 2800,
              timerProgressBar: true
            });
            return;
          } catch (shareErr) {
            if (shareErr && shareErr.name === 'AbortError') {
              if (loading) Swal.close();
              return;
            }
          }
        }
      }

      var url = URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url;
      a.download = fileName;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);

      if (loading) Swal.close();
      if (window.Swal) {
        Swal.fire({
          icon: 'success',
          title: 'บันทึกภาพแล้ว',
          text: isMobile ? 'ตรวจในแกลเลอรี่หรือโฟลเดอร์ดาวน์โหลด' : 'ไฟล์ถูกดาวน์โหลดแล้ว',
          confirmButtonText: 'เข้าใจแล้ว',
          confirmButtonColor: '#0d9488'
        });
      }
    }, 'image/png');
  } catch (err) {
    if (loading) Swal.close();
    if (window.Swal) {
      Swal.fire({
        icon: 'error',
        title: 'สร้างภาพไม่สำเร็จ',
        text: 'ลองใหม่อีกครั้ง หรือลดจำนวนแพที่เทียบ',
        confirmButtonColor: '#0d9488'
      });
    }
  }
}
</script>
<?php endif; ?>

<script>
document.addEventListener('alpine:initialized', function () {
  if (!window.__PAEKAN_COMPARE_PAGE_SYNCED__) {
    window.__PAEKAN_COMPARE_PAGE_SYNCED__ = true;
    var rows = <?= json_encode(array_map(static fn(array $r): array => [
      'property_id' => (int)$r['property_id'],
      'unit_id'     => (int)$r['unit_id'],
      'title'       => (string)$r['unit_name'],
      'subtitle'    => (string)$r['property_name'],
      'image'       => (string)$r['cover_url'],
      'detail_url'  => (string)$r['detail_url'],
    ], $rows), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
    if (window.Alpine && Alpine.store('compare') && rows.length) {
      Alpine.store('compare').items = rows.map(function (row) {
        return Alpine.store('compare').normalize(row);
      }).filter(Boolean).slice(0, Alpine.store('compare').max);
      Alpine.store('compare').save();
    }
  }
});
</script>
