<?php
/** @var array $reviews @var array<int,array<string,mixed>> $reviewVideos @var array $blogs @var array $zones @var array $amenities @var array $heroSlides @var array $heroCopy @var array $bannersBySlot @var array<int,array<string,mixed>> $homeSectionPlan */
$heroFirst = $heroSlides[0] ?? [];
$heroCopy = $heroCopy ?? [];
$heroJson = json_encode($heroSlides ?? [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if ($heroJson === false) {
    $heroJson = '[]';
}
?>
<script>
window.__PAEKAN_HERO__ = <?= $heroJson ?>;
window.__PAEKAN_ACTIVITIES_URL__ = <?= json_encode(url('/activities'), JSON_UNESCAPED_UNICODE) ?>;
function paekanHero() {
  return {
    slides: window.__PAEKAN_HERO__ || [],
    i: 0,
    timer: null,
    init() { this.arm(); },
    arm() {
      if (this.timer) clearInterval(this.timer);
      if (this.slides.length > 1) {
        this.timer = setInterval(() => this.next(), 6500);
      }
    },
    next() { this.i = (this.i + 1) % this.slides.length; this.arm(); },
    prev() { this.i = (this.i - 1 + this.slides.length) % this.slides.length; this.arm(); },
    go(n) { this.i = n; this.arm(); },
  };
}
</script>
<style>
  .home-hero-paekan {
    font-family: var(--font-heading, 'Kanit', system-ui, sans-serif);
  }
  /* iOS/Safari: ให้พื้นที่แตะของ date picker เต็มการ์ด */
  #home-search-mobile input[type="date"]::-webkit-calendar-picker-indicator {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
    cursor: pointer;
    opacity: 0;
  }
  /* แถบเลื่อนงบประมาณ (มือถือ — หน้าแรก) */
  #home-search-mobile .pm-range-wrap input[type="range"] {
    position: absolute;
    left: 0;
    right: 0;
    width: 100%;
    height: 36px;
    margin: 0;
    padding: 0;
    background: none;
    pointer-events: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    top: 50%;
    transform: translateY(-50%);
  }
  #home-search-mobile .pm-range-wrap input[type="range"]::-webkit-slider-runnable-track {
    height: 8px;
    background: transparent;
  }
  #home-search-mobile .pm-range-wrap input[type="range"]::-webkit-slider-thumb {
    pointer-events: auto;
    -webkit-appearance: none;
    width: 26px;
    height: 26px;
    margin-top: -9px;
    border-radius: 9999px;
    background: #fff;
    border: 3px solid #166534;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.18);
    cursor: grab;
  }
  #home-search-mobile .pm-range-wrap input[type="range"]:active::-webkit-slider-thumb {
    cursor: grabbing;
    transform: scale(1.05);
  }
  #home-search-mobile .pm-range-wrap input[type="range"]::-moz-range-track {
    height: 8px;
    background: transparent;
  }
  #home-search-mobile .pm-range-wrap input[type="range"]::-moz-range-thumb {
    pointer-events: auto;
    width: 26px;
    height: 26px;
    border-radius: 9999px;
    background: #fff;
    border: 3px solid #166534;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.18);
    cursor: grab;
  }

  /* แถบเลื่อนงบประมาณ (เดสก์ท็อป — การ์ดค้นหา Hero) */
  #home-search-desktop .pm-range-wrap input[type="range"] {
    position: absolute;
    left: 0;
    right: 0;
    width: 100%;
    height: 36px;
    margin: 0;
    padding: 0;
    background: none;
    pointer-events: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    top: 50%;
    transform: translateY(-50%);
  }
  #home-search-desktop .pm-range-wrap input[type="range"]::-webkit-slider-thumb {
    pointer-events: auto;
    -webkit-appearance: none;
    width: 22px;
    height: 22px;
    margin-top: -7px;
    border-radius: 9999px;
    background: #fff;
    border: 3px solid #15803d;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.18);
    cursor: grab;
  }
  #home-search-desktop .pm-range-wrap input[type="range"]::-moz-range-thumb {
    pointer-events: auto;
    width: 22px;
    height: 22px;
    border-radius: 9999px;
    background: #fff;
    border: 3px solid #15803d;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.18);
    cursor: grab;
  }
  #home-search-desktop .pm-range-wrap input[type="range"]::-webkit-slider-runnable-track {
    height: 6px;
    background: transparent;
  }
  #home-search-desktop .pm-range-wrap input[type="range"]::-moz-range-track {
    height: 6px;
    background: transparent;
  }
</style>
<script>
/** แสดงวันที่คล้าย Agoda + overlay native date input (รองรับ iOS Safari) */
function paekanHomeDateUi() {
  return {
    dateDisplay(iso) {
      if (!iso || typeof iso !== 'string') return 'เลือกวัน';
      const m = iso.match(/^(\d{4})-(\d{2})-(\d{2})$/);
      if (!m) return 'เลือกวัน';
      const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      const day = m[3];
      const mi = parseInt(m[2], 10) - 1;
      const y = m[1];
      if (mi < 0 || mi > 11) return 'เลือกวัน';
      return day + '-' + months[mi] + '-' + y;
    },
    syncCheckoutAfterCheckin() {
      if (!this.checkIn || !this.checkOut) return;
      const a = new Date(this.checkIn + 'T12:00:00');
      const b = new Date(this.checkOut + 'T12:00:00');
      if (b <= a) {
        const n = new Date(a);
        n.setDate(n.getDate() + 1);
        this.checkOut = n.toISOString().slice(0, 10);
      }
    },
    /** วันที่แสดงมือถือ เช่น 8 พ.ค. 2569 */
    dateDisplayTh(iso) {
      if (!iso || typeof iso !== 'string') return 'เลือกวัน';
      const m = iso.match(/^(\d{4})-(\d{2})-(\d{2})$/);
      if (!m) return 'เลือกวัน';
      const months = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
      const y = parseInt(m[1], 10);
      const mo = parseInt(m[2], 10);
      const d = parseInt(m[3], 10);
      if (mo < 1 || mo > 12 || d < 1 || d > 31) return 'เลือกวัน';
      return d + ' ' + months[mo - 1] + ' ' + (y + 543);
    },
    dateWeekdayTh(iso) {
      if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) return '';
      const p = iso.split('-').map((x) => parseInt(x, 10));
      const dt = new Date(p[0], p[1] - 1, p[2], 12, 0, 0);
      return new Intl.DateTimeFormat('th-TH', { weekday: 'long' }).format(dt);
    },
  };
}

function paekanHeroSearchBudgetMixin() {
  const MIN = 0;
  const MAX = 50000;
  const STEP = 500;
  return {
    minV: MIN,
    maxV: MAX,
    step: STEP,
    low: MIN,
    high: MAX,
    fmt(n) {
      return '฿' + new Intl.NumberFormat('th-TH').format(n);
    },
    budgetSummaryLine() {
      const lo = this.low <= this.minV ? null : this.low;
      const hi = this.high >= this.maxV ? null : this.high;
      if (lo === null && hi === null) return 'ทุกช่วงราคา';
      if (lo !== null && hi !== null) return this.fmt(lo) + ' – ' + this.fmt(hi);
      if (lo !== null) return this.fmt(lo) + '+';
      return '≤ ' + this.fmt(hi);
    },
    bandLeftPct() {
      return (this.low / MAX) * 100;
    },
    bandWidthPct() {
      return Math.max(0, ((this.high - this.low) / MAX) * 100);
    },
    clampLow() {
      if (this.low > this.high - STEP) this.low = this.high - STEP;
      if (this.low < MIN) this.low = MIN;
    },
    clampHigh() {
      if (this.high < this.low + STEP) this.high = this.low + STEP;
      if (this.high > MAX) this.high = MAX;
    },
    budgetMinSubmit() {
      return this.low <= MIN ? '' : String(this.low);
    },
    budgetMaxSubmit() {
      return this.high >= MAX ? '' : String(this.high);
    },
    resetBudgetRange() {
      this.low = MIN;
      this.high = MAX;
    },
  };
}

/** แท็บประเภทที่พัก + ผู้เข้าพักแบบสรุป — ใช้ทั้งมือถือและเดสก์ท็อป Hero */
function paekanHeroTabMixin() {
  return {
    heroTab: 'all',
    staySubtype: '',
    rooms: 1,
    adults: 2,
    children: 0,
    zoneVal: '',
    raftVariant: '',
    actCategory: '',
    activitiesUrl: '/activities',
    pickHeroTab(tab) {
      this.heroTab = tab;
      if (tab !== 'stay') this.staySubtype = '';
      if (tab !== 'raft') this.raftVariant = '';
    },
    effectivePropType() {
      if (this.heroTab === 'stay' && (this.staySubtype === 'homestay' || this.staySubtype === 'house')) {
        return this.staySubtype;
      }
      return '';
    },
    heroFormAction() {
      if (this.heroTab === 'raft') return '<?= url('/rafts') ?>';
      if (this.heroTab === 'pool_villa') return '<?= url('/pool-villas') ?>';
      if (this.heroTab === 'camping') return '<?= url('/camping') ?>';
      if (this.heroTab === 'stay') {
        if (this.staySubtype === 'resort') return '<?= url('/resorts') ?>';
        if (this.staySubtype === 'hotel') return '<?= url('/hotels') ?>';
        return '<?= url('/stays') ?>';
      }
      return '<?= url('/properties') ?>';
    },
    guestSummaryLine() {
      return this.rooms + ' ห้อง ' + this.adults + ' ผู้ใหญ่ ' + this.children + ' เด็ก';
    },
    /** แมปช่วงจำนวนคน → ขอบบนของช่วง (ผู้ใหญ่เท่านั้น เด็ก=0) สำหรับค้นหา capacity_max >= guests */
    applyGuestTotalFromPreset(cap) {
      const n = Number(cap);
      if (!Number.isFinite(n) || n < 1) return;
      this.adults = Math.min(Math.round(n), 99);
      this.children = 0;
    },
    guestsHiddenValue() {
      const n = this.adults + this.children;
      if (n <= 0) return '';
      return String(Math.min(Math.max(n, 1), 120));
    },
    submitHero(e) {
      if (this.heroTab !== 'activities') return;
      e.preventDefault();
      const p = new URLSearchParams();
      if (this.zoneVal) p.set('zone', this.zoneVal);
      if (this.actCategory) p.set('category', this.actCategory);
      const qs = p.toString();
      window.location.href = this.activitiesUrl + (qs ? '?' + qs : '');
    },
    primarySubmitLabel() {
      return this.heroTab === 'activities' ? 'ค้นหากิจกรรม & ที่เที่ยว' : 'ค้นหาที่พัก';
    },
  };
}

function paekanMobileHeroSearch() {
  return {
    ...paekanHomeDateUi(),
    ...paekanHeroTabMixin(),
    ...paekanHeroSearchBudgetMixin(),
    advanced: false,
    propType: '',
    checkIn: '',
    checkOut: '',
    init() {
      const d = new Date();
      d.setDate(d.getDate() + 1);
      this.checkIn = d.toISOString().slice(0, 10);
      const d2 = new Date(d);
      d2.setDate(d2.getDate() + 1);
      this.checkOut = d2.toISOString().slice(0, 10);
      this.activitiesUrl = typeof window.__PAEKAN_ACTIVITIES_URL__ === 'string' ? window.__PAEKAN_ACTIVITIES_URL__ : '/activities';
    },
    nightsLabel() {
      if (!this.checkIn || !this.checkOut) return '—';
      const a = new Date(this.checkIn + 'T12:00:00');
      const b = new Date(this.checkOut + 'T12:00:00');
      const n = Math.round((b - a) / 86400000);
      if (Number.isNaN(n) || n <= 0) return '—';
      return n + ' คืน';
    },
  };
}

function paekanMobileAiSearch() {
  const TYPE_LABELS = {
    raft: 'แพพัก', resort: 'รีสอร์ท', homestay: 'โฮมสเตย์',
    house: 'บ้านพัก', pool_villa: 'พูลวิลล่า', hotel: 'โรงแรม', camping: 'แคมป์ปิ้ง',
  };
  return {
    query: '',
    busy: false,
    result: null,   // {summary, top_picks, redirect}
    async run() {
      if (!this.query.trim()) return;
      this.busy = true;
      this.result = null;
      try {
        const fd = new FormData();
        fd.append('query', this.query);
        const r = await fetch('<?= url('/ai/smart-search') ?>', { method: 'POST', body: fd });
        const j = await r.json();
        if (j.ok) {
          if (j.top_picks && j.top_picks.length > 0) {
            this.result = j;
            this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
          } else if (j.redirect) {
            window.location.href = j.redirect;
          }
        }
      } catch (e) {}
      finally { this.busy = false; }
    },
    goAll() {
      if (this.result && this.result.redirect) window.location.href = this.result.redirect;
    },
    fmtPrice(n) {
      return n > 0 ? '฿' + Number(n).toLocaleString('th-TH') : '';
    },
    typeLabel(t) {
      return TYPE_LABELS[t] || t || '';
    },
    metaLine(p) {
      const tl = this.typeLabel(p.type);
      return tl + (p.zone ? ' · ' + p.zone : '');
    },
  };
}

function paekanDesktopHeroSearch() {
  return {
    ...paekanHomeDateUi(),
    ...paekanHeroTabMixin(),
    ...paekanHeroSearchBudgetMixin(),
    advanced: false,
    propType: '',
    checkIn: '',
    checkOut: '',
    init() {
      const d = new Date();
      d.setDate(d.getDate() + 1);
      this.checkIn = d.toISOString().slice(0, 10);
      const d2 = new Date(d);
      d2.setDate(d2.getDate() + 1);
      this.checkOut = d2.toISOString().slice(0, 10);
      this.activitiesUrl = typeof window.__PAEKAN_ACTIVITIES_URL__ === 'string' ? window.__PAEKAN_ACTIVITIES_URL__ : '/activities';
    },
  };
}
</script>

<!-- ========== MOBILE: Hero + การ์ดค้นหาทับ (เลย์เอาต์แอป — Agoda-like / แพกาญ.com) ========== -->
<section id="home-mobile-hero" class="home-hero-paekan md:hidden relative w-full bg-gradient-to-b from-sky-600 via-teal-700 to-emerald-900 pb-6">
  <div class="relative min-h-[300px] max-h-[46vh] w-full overflow-hidden">
    <img src="<?= e($heroFirst['img'] ?? '') ?>" alt="" class="absolute inset-0 w-full h-full object-cover" loading="eager" decoding="async" fetchpriority="high" width="900" height="600">
    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/40 to-black/25"></div>
    <div class="relative z-[1] flex flex-col justify-end min-h-[300px] max-h-[46vh] px-5 pb-32 pt-14">
      <h1 class="hero-mobile-headline text-[1.6rem] leading-[1.22] font-extrabold text-white tracking-tight drop-shadow-[0_2px_14px_rgba(0,0,0,0.45)]">
        <?= e((string)($heroCopy['mobile_title_line1'] ?? '')) ?><br><span class="text-[1.05rem] font-bold tracking-normal opacity-[0.96]"><?= e((string)($heroCopy['mobile_title_line2'] ?? '')) ?></span>
      </h1>
      <p class="hero-mobile-promo mt-3 text-amber-300 font-bold text-[13px] drop-shadow-md tracking-wide"><?= e((string)($heroCopy['promo'] ?? '')) ?></p>
    </div>
  </div>

  <?php
  $heroMobTabs = [
      ['id' => 'all', 'label' => 'ทั้งหมด', 'icon' => 'layout-grid'],
      ['id' => 'raft', 'label' => 'แพพัก', 'icon' => 'anchor'],
      ['id' => 'pool_villa', 'label' => 'บ้านพูลวิลล่า', 'icon' => 'waves'],
      ['id' => 'stay', 'label' => 'ที่พัก', 'icon' => 'home'],
      ['id' => 'camping', 'label' => 'ลานกางเต็นท์', 'icon' => 'trees'],
      ['id' => 'activities', 'label' => 'กิจกรรมท้องถิ่น', 'icon' => 'map'],
  ];
  ?>

  <div class="relative z-10 -mt-28 px-3 space-y-3 min-w-0 max-w-full" x-data="paekanMobileHeroSearch()">
    <div class="w-full min-w-0 max-w-full overflow-x-visible">
      <div class="flex gap-2 overflow-x-scroll overflow-y-visible pb-1 pt-0.5 pl-0.5 pr-4 no-scrollbar touch-pan-x overscroll-x-contain snap-x snap-mandatory [-webkit-overflow-scrolling:touch] scroll-pl-1 scroll-pr-2"
           role="tablist" aria-label="ประเภทที่พัก">
      <?php foreach ($heroMobTabs as $ht): ?>
      <button type="button" role="tab" @click="pickHeroTab('<?= e($ht['id']) ?>')"
              class="snap-start shrink-0 flex flex-col items-center justify-center gap-0.5 min-w-[3.85rem] px-2 py-2 rounded-xl text-[9px] font-bold leading-tight transition-all border shadow-sm"
              :class="heroTab === '<?= e($ht['id']) ?>'
                ? 'bg-white text-sky-700 border-white shadow-md scale-[1.02]'
                : 'bg-white/15 text-white border-white/25 hover:bg-white/22'">
        <span class="inline-grid place-items-center w-[17px] h-[17px] shrink-0 [&_svg]:stroke-[2px]"
              :class="heroTab === '<?= e($ht['id']) ?>' ? 'text-sky-600' : 'text-white'">
          <i data-lucide="<?= e($ht['icon']) ?>" class="w-[17px] h-[17px]" aria-hidden="true"></i>
        </span>
        <span class="text-center"><?= e($ht['label']) ?></span>
      </button>
      <?php endforeach; ?>
      </div>
    </div>

    <form id="home-search-mobile" :action="heroFormAction()" method="get"
          @submit="submitHero($event)"
          class="bg-white rounded-2xl border border-slate-200/85 overflow-hidden p-0
                 shadow-[0_12px_42px_-14px_rgba(15,23,42,0.14),0_6px_20px_-8px_rgba(14,116,144,0.12)]
                 ring-1 ring-black/[0.035]">

      <input type="hidden" name="type" :value="effectivePropType()" :disabled="!effectivePropType()">
      <input type="hidden" name="raft_variant" :value="raftVariant" :disabled="heroTab !== 'raft' || raftVariant === ''">
      <input type="hidden" name="guests" :value="guestsHiddenValue()" :disabled="heroTab === 'activities' || guestsHiddenValue() === ''">
      <input type="hidden" name="budget_min" :value="budgetMinSubmit()" :disabled="heroTab === 'activities'">
      <input type="hidden" name="budget_max" :value="budgetMaxSubmit()" :disabled="heroTab === 'activities'">

      <div class="divide-y divide-slate-100">

        <!-- ที่พัก: ชื่อที่พัก (เล็ก) + เลือกโซน -->
        <div class="px-3 py-2.5 space-y-2" x-show="heroTab !== 'activities'">
          <div class="flex items-stretch rounded-xl bg-slate-50 ring-1 ring-slate-200/85 overflow-hidden min-h-[2.875rem]">
            <span class="w-11 shrink-0 bg-gradient-to-br from-indigo-500 via-violet-600 to-purple-600 text-white grid place-items-center" aria-hidden="true">
              <i data-lucide="search" class="w-[18px] h-[18px] stroke-[2.5px]"></i>
            </span>
            <input type="text" name="q" value="" autocomplete="off"
                   placeholder="ค้นหาชื่อที่พัก…"
                   class="flex-1 min-w-0 border-0 bg-transparent min-h-[2.75rem] py-2 pl-3 pr-3 text-[13px] font-semibold text-slate-800 placeholder:text-slate-400 placeholder:font-medium outline-none focus:ring-0">
          </div>
          <div class="flex items-stretch rounded-xl bg-slate-100 ring-1 ring-slate-200/90 overflow-hidden min-h-[2.875rem]">
            <span class="w-11 shrink-0 bg-gradient-to-br from-sky-500 via-teal-600 to-emerald-600 text-white grid place-items-center" aria-hidden="true">
              <i data-lucide="map-pin" class="w-[18px] h-[18px] stroke-[2.5px]"></i>
            </span>
            <div class="relative flex flex-1 min-w-0">
              <select name="zone" x-model="zoneVal"
                      class="h-full w-full min-h-[2.75rem] border-0 bg-transparent py-2 pl-3 pr-10 text-[14px] font-bold text-slate-800 outline-none focus:ring-0 appearance-none">
                <option value="">ทุกโซน</option>
                <?php foreach ($zones as $z): ?>
                  <option value="<?= e($z['zone']) ?>"><?= e($z['zone']) ?></option>
                <?php endforeach; ?>
              </select>
              <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true">
                <i data-lucide="chevron-down" class="w-4 h-4"></i>
              </span>
            </div>
          </div>
        </div>

        <!-- กิจกรรม: เฉพาะโซน + หมวด -->
        <div class="px-3 py-2.5 space-y-2" x-show="heroTab === 'activities'">
          <p class="text-[12px] font-bold text-slate-700">ค้นหากิจกรรมในกาญจนบุรี</p>
          <select name="zone" x-model="zoneVal"
                  class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 px-3 text-[13px] font-semibold outline-none focus:border-sky-500">
            <option value="">ทุกพื้นที่</option>
            <?php foreach ($zones as $z): ?>
              <option value="<?= e($z['zone']) ?>"><?= e($z['zone']) ?></option>
            <?php endforeach; ?>
          </select>
          <select x-model="actCategory"
                  class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 px-3 text-[13px] font-semibold outline-none focus:border-sky-500">
            <option value="">ทุกหมวด</option>
            <?php foreach (\App\Models\ActivityProduct::CATEGORIES as $ck => $cl): ?>
              <option value="<?= e($ck) ?>"><?= e($cl) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- แพบนมือถือ: แบบแพเพิ่มเติมแสดงทันที (ไม่พับ) -->
        <div class="border-t border-slate-100" x-show="heroTab === 'raft'">
          <div class="px-3 py-2.5 text-[13px] font-bold text-slate-800 border-b border-slate-100 bg-white">แบบแพเพิ่มเติม</div>
          <div class="px-3 pb-3 pt-2.5 bg-slate-50/60">
            <div class="flex flex-wrap gap-1.5">
              <button type="button" @click="raftVariant = ''" class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold border transition"
                      :class="raftVariant === '' ? 'bg-sky-600 text-white border-sky-600' : 'bg-white text-slate-700 border-slate-200'">ทั้งหมด</button>
              <button type="button" @click="raftVariant = 'shore'" class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold border transition"
                      :class="raftVariant === 'shore' ? 'bg-sky-600 text-white border-sky-600' : 'bg-white text-slate-700 border-slate-200'">แพริมน้ำ</button>
              <button type="button" @click="raftVariant = 'towed'" class="px-2.5 py-1.5 rounded-lg text-[11px] font-bold border transition"
                      :class="raftVariant === 'towed' ? 'bg-sky-600 text-white border-sky-600' : 'bg-white text-slate-700 border-slate-200'">แพลาก</button>
            </div>
          </div>
        </div>

        <!-- พูลวิลล่า / ที่พักบนมือถือ: พับได้ -->
        <details class="group border-t border-slate-100" x-show="heroTab === 'pool_villa' || heroTab === 'stay'">
          <summary class="flex items-center justify-between px-3 py-2.5 cursor-pointer list-none [&::-webkit-details-marker]:hidden text-[13px] font-bold text-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-sky-200">
            <span x-text="heroTab === 'pool_villa' ? 'จุดเด่นพูลวิลล่า' : 'เลือกประเภทที่พัก'"></span>
            <i data-lucide="chevron-down" class="w-4 h-4 text-slate-400 shrink-0 transition-transform group-open:rotate-180"></i>
          </summary>
          <div class="px-3 pb-3 space-y-2.5 bg-slate-50/60 border-t border-slate-100">
            <div class="flex flex-wrap gap-1.5" x-show="heroTab === 'pool_villa'">
              <label class="inline-flex items-center gap-1.5 px-2 py-1.5 rounded-lg bg-white border border-slate-200 text-[11px] font-semibold text-slate-800 cursor-pointer">
                <input type="checkbox" name="amenities[]" value="3" class="rounded border-slate-300 text-emerald-600 shrink-0" :disabled="heroTab !== 'pool_villa'"> สระว่ายน้ำ
              </label>
              <label class="inline-flex items-center gap-1.5 px-2 py-1.5 rounded-lg bg-white border border-slate-200 text-[11px] font-semibold text-slate-800 cursor-pointer">
                <input type="checkbox" name="amenities[]" value="14" class="rounded border-slate-300 text-emerald-600 shrink-0" :disabled="heroTab !== 'pool_villa'"> วิวแม่น้ำ
              </label>
              <label class="inline-flex items-center gap-1.5 px-2 py-1.5 rounded-lg bg-white border border-slate-200 text-[11px] font-semibold text-slate-800 cursor-pointer">
                <input type="checkbox" name="amenities[]" value="13" class="rounded border-slate-300 text-emerald-600 shrink-0" :disabled="heroTab !== 'pool_villa'"> วิวภูเขา
              </label>
            </div>
            <div x-show="heroTab === 'stay'">
              <select x-model="staySubtype" class="w-full rounded-xl border border-slate-200 bg-white py-2.5 px-3 text-[12px] font-bold text-slate-900 outline-none focus:border-teal-600">
                <option value="">ทั้งหมดในกลุ่มที่พักทั่วไป</option>
                <option value="resort">รีสอร์ท</option>
                <option value="homestay">โฮมสเตย์</option>
                <option value="hotel">โรงแรม</option>
                <option value="house">บ้านพัก</option>
              </select>
            </div>
          </div>
        </details>

        <!-- ห้องนอน / ห้องน้ำ (ขั้นต่ำต่อยูนิต) — แพพัก & พูลวิลล่า -->
        <div class="px-3 py-2.5 border-t border-slate-100 bg-white space-y-2" x-show="heroTab === 'raft' || heroTab === 'pool_villa'">
          <p class="text-[11px] font-bold text-slate-700">ห้องนอน / ห้องน้ำ (ขั้นต่ำต่อยูนิต)</p>
          <div class="grid grid-cols-2 gap-2">
            <div class="min-w-0">
              <label class="block text-[10px] font-bold text-slate-500 mb-1">ห้องนอน</label>
              <select name="bedrooms_min"
                      class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-2.5 pr-2 text-[13px] font-bold text-slate-900 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-200"
                      :disabled="heroTab !== 'raft' && heroTab !== 'pool_villa'">
                <option value="">ไม่ระบุ</option>
                <?php for ($br = 1; $br <= 10; $br++): ?>
                  <option value="<?= $br ?>"><?= $br ?> ห้อง</option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="min-w-0">
              <label class="block text-[10px] font-bold text-slate-500 mb-1">ห้องน้ำ</label>
              <select name="bathrooms_min"
                      class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-2.5 pr-2 text-[13px] font-bold text-slate-900 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-200"
                      :disabled="heroTab !== 'raft' && heroTab !== 'pool_villa'">
                <option value="">ไม่ระบุ</option>
                <?php for ($ba = 1; $ba <= 10; $ba++): ?>
                  <option value="<?= $ba ?>"><?= $ba ?> ห้อง</option>
                <?php endfor; ?>
              </select>
            </div>
          </div>
        </div>

        <!-- เช็คอิน / เช็คเอาท์ — overlay input เต็มการ์ด เพื่อให้ iOS/Safari เปิดปฏิทินได้ -->
        <div class="px-2 py-2" x-show="heroTab !== 'activities'">
          <div class="flex gap-1.5 items-stretch">
            <label class="relative flex-1 min-w-0 rounded-2xl border border-slate-200/90 bg-white p-3 min-h-[5.35rem] shadow-[0_4px_22px_-14px_rgba(15,23,42,0.12)] cursor-pointer touch-manipulation transition-[transform,box-shadow,border-color] active:scale-[0.985] hover:border-sky-300/90 hover:shadow-[0_10px_32px_-16px_rgba(14,165,233,0.22)] focus-within:border-sky-400 focus-within:ring-2 focus-within:ring-sky-100"
                   aria-label="เลือกวันเช็คอิน">
              <span class="pointer-events-none relative z-0 flex items-center gap-1.5 text-sky-700">
                <span class="grid place-items-center w-7 h-7 rounded-lg bg-sky-50 text-sky-600 ring-1 ring-sky-100/80 shrink-0">
                  <i data-lucide="calendar" class="w-4 h-4 stroke-[2px]"></i>
                </span>
                <span class="text-[10px] font-bold uppercase tracking-wide text-slate-500">เช็คอิน</span>
              </span>
              <p class="pointer-events-none relative z-0 text-[15px] font-extrabold text-sky-900 mt-2 leading-snug tracking-tight" x-text="dateDisplayTh(checkIn)"></p>
              <p class="pointer-events-none relative z-0 text-[11px] text-slate-500 mt-1 leading-tight" x-text="dateWeekdayTh(checkIn)"></p>
              <input type="date" name="check_in" x-model="checkIn" x-ref="mobCheckIn"
                     @change="syncCheckoutAfterCheckin()"
                     :disabled="heroTab === 'activities'"
                     class="absolute inset-0 z-10 h-full w-full opacity-0 cursor-pointer [font-size:1rem]">
            </label>
            <div class="flex flex-col justify-center shrink-0 self-center px-0.5" aria-hidden="true">
              <div class="pointer-events-none flex items-center gap-1 rounded-full bg-gradient-to-r from-sky-50 to-white border border-sky-200/90 px-2.5 py-1.5 shadow-sm">
                <i data-lucide="moon" class="w-3.5 h-3.5 text-sky-600 shrink-0 stroke-[2px]"></i>
                <span class="text-[10px] font-extrabold text-sky-900 whitespace-nowrap tracking-tight" x-text="nightsLabel()"></span>
              </div>
            </div>
            <label class="relative flex-1 min-w-0 rounded-2xl border border-slate-200/90 bg-white p-3 min-h-[5.35rem] shadow-[0_4px_22px_-14px_rgba(15,23,42,0.12)] cursor-pointer touch-manipulation transition-[transform,box-shadow,border-color] active:scale-[0.985] hover:border-sky-300/90 hover:shadow-[0_10px_32px_-16px_rgba(14,165,233,0.22)] focus-within:border-sky-400 focus-within:ring-2 focus-within:ring-sky-100"
                   aria-label="เลือกวันเช็คเอาท์">
              <span class="pointer-events-none relative z-0 flex items-center gap-1.5 text-sky-700">
                <span class="grid place-items-center w-7 h-7 rounded-lg bg-sky-50 text-sky-600 ring-1 ring-sky-100/80 shrink-0">
                  <i data-lucide="calendar" class="w-4 h-4 stroke-[2px]"></i>
                </span>
                <span class="text-[10px] font-bold uppercase tracking-wide text-slate-500">เช็คเอาท์</span>
              </span>
              <p class="pointer-events-none relative z-0 text-[15px] font-extrabold text-sky-900 mt-2 leading-snug tracking-tight" x-text="dateDisplayTh(checkOut)"></p>
              <p class="pointer-events-none relative z-0 text-[11px] text-slate-500 mt-1 leading-tight" x-text="dateWeekdayTh(checkOut)"></p>
              <input type="date" name="check_out" x-model="checkOut" :min="checkIn" x-ref="mobCheckOut"
                     @change="syncCheckoutAfterCheckin()"
                     :disabled="heroTab === 'activities'"
                     class="absolute inset-0 z-10 h-full w-full opacity-0 cursor-pointer [font-size:1rem]">
            </label>
          </div>
        </div>

        <!-- ผู้เข้าพัก — แถวสรุปแบบ Agoda แตะเพื่อขยายตั้งค่า -->
        <details class="group" x-show="heroTab !== 'activities'">
          <summary class="flex items-center gap-3 px-3 py-3 cursor-pointer list-none [&::-webkit-details-marker]:hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-sky-300 rounded-none">
            <span class="w-9 h-9 rounded-full bg-slate-100 text-sky-700 grid place-items-center shrink-0 ring-1 ring-slate-200">
              <i data-lucide="users" class="w-[18px] h-[18px] stroke-[2px]"></i>
            </span>
            <div class="flex-1 min-w-0 text-left">
              <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">ผู้เข้าพัก</div>
              <div class="text-[14px] font-bold text-slate-900 leading-snug mt-0.5" x-text="guestSummaryLine()"></div>
            </div>
            <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 shrink-0 transition-transform duration-200 group-open:rotate-180"></i>
          </summary>
          <div class="px-3 pb-3 border-t border-slate-50 bg-slate-50/40 pt-2 space-y-2">
            <div class="min-w-0" x-show="heroTab === 'raft' || heroTab === 'pool_villa'">
              <label class="block text-[9px] font-bold text-slate-500 mb-1">ช่วงจำนวนคน (เลือกเร็ว)</label>
              <select class="w-full rounded-lg border border-slate-200 bg-white py-2 px-2 text-[11px] font-bold text-slate-900 outline-none focus:border-sky-500"
                      aria-label="เลือกช่วงจำนวนคนเร็ว"
                      @change="if ($event.target.value) { applyGuestTotalFromPreset(+$event.target.value); $event.target.selectedIndex = 0; }">
                <option value="">กำหนดเอง — เลือกด้านล่าง</option>
                <option value="6">ประมาณ 2–6 คน</option>
                <option value="12">ประมาณ 8–12 คน</option>
                <option value="20">ประมาณ 15–20 คน</option>
                <option value="25">ประมาณ 20–25 คน</option>
                <option value="30">ประมาณ 25–30 คน</option>
                <option value="40">ประมาณ 31–40 คน</option>
                <option value="50">ประมาณ 40–50 คน</option>
                <option value="99">ประมาณ 50 คนขึ้นไป</option>
              </select>
            </div>
            <div class="grid grid-cols-3 gap-2">
            <div class="min-w-0">
              <label class="block text-[9px] font-bold text-slate-500 mb-1">ห้อง</label>
              <select x-model.number="rooms" class="w-full rounded-lg border border-slate-200 bg-white py-2 px-1 text-[11px] font-bold text-slate-900 outline-none focus:border-sky-500">
                <?php for ($r = 1; $r <= 8; $r++): ?><option value="<?= $r ?>"><?= $r ?></option><?php endfor; ?>
              </select>
            </div>
            <div class="min-w-0">
              <label class="block text-[9px] font-bold text-slate-500 mb-1">ผู้ใหญ่</label>
              <select x-model.number="adults" class="w-full rounded-lg border border-slate-200 bg-white py-2 px-1 text-[11px] font-bold text-slate-900 outline-none focus:border-sky-500">
                <?php for ($ga = 1; $ga <= 99; $ga++): ?><option value="<?= $ga ?>"><?= $ga ?></option><?php endfor; ?>
              </select>
            </div>
            <div class="min-w-0">
              <label class="block text-[9px] font-bold text-slate-500 mb-1">เด็ก</label>
              <select x-model.number="children" class="w-full rounded-lg border border-slate-200 bg-white py-2 px-1 text-[11px] font-bold text-slate-900 outline-none focus:border-sky-500">
                <?php for ($gc = 0; $gc <= 30; $gc++): ?><option value="<?= $gc ?>"><?= $gc ?></option><?php endfor; ?>
              </select>
            </div>
          </div>
          </div>
        </details>

      </div>

      <div class="px-3 pt-2 pb-2">
        <button type="submit"
                class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl bg-sky-600 hover:bg-sky-700 active:scale-[0.99] text-white font-extrabold text-[15px] tracking-tight shadow-[0_10px_28px_-10px_rgba(2,132,199,0.55)] transition"
                :aria-label="primarySubmitLabel()">
          <i data-lucide="search" class="w-5 h-5 shrink-0 stroke-[2.5px]"></i>
          <span x-text="primarySubmitLabel()"></span>
        </button>
      </div>

      <div class="flex justify-center pb-2 px-3">
        <button type="button" @click="advanced = !advanced"
                class="inline-flex items-center gap-1 text-[13px] font-bold text-sky-800 hover:text-sky-950 transition py-1">
          <span x-text="advanced ? 'ซ่อนตัวกรองเพิ่มเติม' : 'งบประมาณ · ฟิลเตอร์เพิ่มเติม'"></span>
          <i data-lucide="chevron-down" class="w-4 h-4 transition-transform shrink-0" :class="advanced ? 'rotate-180' : ''"></i>
        </button>
      </div>

      <div x-show="advanced" x-transition
           class="border-t border-slate-200 bg-slate-50 px-3 py-3 space-y-4">
        <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200/80" x-show="heroTab !== 'activities'">
          <div class="flex items-start justify-between gap-2 mb-2">
            <span class="font-bold text-[12px] text-slate-800">งบประมาณต่อคืน</span>
            <button type="button" @click="resetBudgetRange()" class="text-[10px] font-bold text-sky-700 whitespace-nowrap">เคลียร์</button>
          </div>
          <p class="text-[13px] font-extrabold text-sky-800 tabular-nums mb-2" x-text="budgetSummaryLine()"></p>
          <div class="pm-range-wrap relative h-10">
            <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-1.5 rounded-full bg-slate-200 pointer-events-none"></div>
            <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1.5 rounded-full bg-gradient-to-r from-sky-400 to-teal-600 pointer-events-none transition-[left,width] duration-75"
                 :style="'left:' + bandLeftPct() + '%; width:' + bandWidthPct() + '%;'"></div>
            <input type="range" class="relative z-[5]" :min="minV" :max="maxV" :step="step" x-model.number="low" @input="clampLow()">
            <input type="range" class="relative z-[6]" :min="minV" :max="maxV" :step="step" x-model.number="high" @input="clampHigh()">
          </div>
        </div>
        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500 flex items-center gap-2">
          <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5 text-sky-700"></i> ฟิลเตอร์อื่นๆ
        </p>
        <div x-show="heroTab === 'all'" class="space-y-2">
          <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wide block">ประเภทแพ (แท็บทั้งหมด)</label>
          <select name="raft_variant"
                  :disabled="heroTab !== 'all'"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-[12px] font-bold outline-none focus:border-sky-600 disabled:opacity-50">
            <option value="">ทั้งหมด</option>
            <option value="shore">แพริมน้ำ</option>
            <option value="towed">แพลาก</option>
          </select>
        </div>
        <div class="flex flex-wrap gap-4">
          <label class="inline-flex items-center gap-2 text-[12px] font-semibold text-slate-800 cursor-pointer">
            <input type="checkbox" name="pet" value="1" class="rounded border-slate-300 text-sky-600"> รับสัตว์เลี้ยง
          </label>
          <label class="inline-flex items-center gap-2 text-[12px] font-semibold text-slate-800 cursor-pointer">
            <input type="checkbox" name="coupon" value="1" class="rounded border-slate-300 text-sky-600"> ใช้คูปองได้
          </label>
        </div>
        <?php if (!empty($amenities)): ?>
        <div>
          <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-2 block">สิ่งอำนวยความสะดวก</label>
          <div class="max-h-40 overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 grid gap-1.5">
            <?php foreach ($amenities as $a): ?>
              <label class="flex items-start gap-2 text-[12px] text-slate-700 cursor-pointer leading-snug">
                <input type="checkbox" name="amenities[]" value="<?= (int)$a['id'] ?>" class="rounded border-slate-300 text-sky-600 mt-0.5 shrink-0">
                <span><?= e($a['name']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

    </form>

    <div class="mt-3" x-data="paekanMobileAiSearch()">
      <div class="rounded-2xl p-[1.5px] bg-gradient-to-br from-violet-500 via-indigo-500 to-sky-500 shadow-[0_14px_44px_-18px_rgba(76,29,149,0.5)]">
        <div class="rounded-[13px] bg-white px-3.5 pt-3.5 pb-3 space-y-3">
          <header class="flex flex-col items-center text-center gap-1.5 px-1 pt-0.5">
            <span class="grid place-items-center w-9 h-9 rounded-xl bg-gradient-to-br from-sky-100 to-violet-100 text-sky-500 shrink-0" aria-hidden="true">
              <i data-lucide="sparkles" class="w-[17px] h-[17px] stroke-[2px]"></i>
            </span>
            <div class="min-w-0 w-full">
              <h2 class="text-[15px] font-extrabold text-sky-950 leading-snug tracking-tight">ให้ AI ช่วยหาที่พักที่ตรงใจ</h2>
              <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">บอกสิ่งที่คุณต้องการ เราจะหาที่พักที่ใช่ให้คุณ</p>
            </div>
          </header>
          <form @submit.prevent="run()" class="flex items-stretch gap-2 min-h-[3rem]">
            <div class="shrink-0 w-[3.35rem] rounded-xl bg-gradient-to-br from-violet-600 via-indigo-600 to-sky-600 text-white flex flex-col items-center justify-center gap-0.5 shadow-inner py-1.5 px-1 select-none"
                 aria-hidden="true">
              <i data-lucide="sparkles" class="w-[17px] h-[17px] stroke-[2.35px] opacity-95"></i>
              <span class="text-[9px] font-extrabold tracking-[0.08em] leading-none">AI</span>
            </div>
            <input type="text" x-model="query" name="ai_query_home_mob" autocomplete="off"
                   placeholder="เช่น แพริมน้ำ 4 คน งบไม่เกิน 3,000 ใกล้เขื่อน"
                   aria-label="ให้ AI ช่วยหาที่พักที่ตรงใจ — พิมพ์สิ่งที่ต้องการ"
                   class="flex-1 min-w-0 rounded-xl border border-slate-200 bg-white py-2 px-3 text-[12px] font-semibold text-slate-800 placeholder:text-slate-400 placeholder:font-medium outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100/80">
            <button type="submit" :disabled="busy"
                    class="shrink-0 flex flex-col items-center justify-center gap-0.5 rounded-xl bg-gradient-to-br from-violet-600 via-indigo-600 to-sky-700 hover:from-violet-700 hover:via-indigo-700 hover:to-sky-800 disabled:opacity-55 text-white shadow-md px-3 min-w-[4.25rem] transition"
                    :aria-busy="busy"
                    aria-label="ค้นหาด้วย AI">
              <span class="flex items-center gap-1 font-extrabold text-[12px] leading-none" x-show="!busy">
                <i data-lucide="sparkles" class="w-[14px] h-[14px] stroke-[2.25px] shrink-0"></i>
                ค้นหา
              </span>
              <span class="flex flex-col items-center gap-0.5 py-0.5" x-show="busy" x-cloak>
                <i data-lucide="loader-circle" class="w-[18px] h-[18px] animate-spin stroke-[2.25px]"></i>
                <span class="text-[9px] font-bold opacity-90">รอสักครู่</span>
              </span>
            </button>
          </form>

          <!-- AI inline results panel -->
          <div x-show="result" x-cloak class="mt-1 -mx-3.5 -mb-3 border-t border-slate-100 overflow-hidden rounded-b-[13px]">
            <div class="flex items-center justify-between gap-2 px-3 py-2.5 bg-gradient-to-r from-sky-600 via-teal-600 to-emerald-600">
              <div class="flex items-center gap-2 min-w-0 flex-1">
                <span class="grid place-items-center w-7 h-7 rounded-lg bg-white/20 text-white shrink-0" aria-hidden="true">
                  <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                </span>
                <span class="text-[11px] font-bold text-white leading-snug line-clamp-2" x-text="result && result.summary"></span>
              </div>
              <button type="button" @click="result=null" class="shrink-0 w-7 h-7 rounded-full bg-white/20 hover:bg-white/30 text-white text-xs font-bold grid place-items-center transition" aria-label="ปิด">✕</button>
            </div>
            <div class="px-3 py-2.5 space-y-2 bg-slate-50/80">
              <template x-for="p in (result && result.top_picks || [])" :key="p.id">
                <a :href="p.url"
                   class="group flex gap-3 p-2.5 rounded-xl border border-slate-200/90 bg-white shadow-[0_6px_22px_-10px_rgba(15,23,42,0.14)] hover:border-sky-300 hover:shadow-[0_12px_32px_-12px_rgba(14,116,144,0.22)] transition no-underline text-inherit">
                  <div class="relative w-[5.5rem] h-[5.5rem] shrink-0 rounded-lg overflow-hidden bg-slate-100 ring-1 ring-slate-200/80">
                    <img x-show="p.cover" :src="p.cover" :alt="p.name" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy">
                    <div x-show="!p.cover" class="absolute inset-0 grid place-items-center text-2xl bg-gradient-to-br from-teal-50 to-sky-100">🏕️</div>
                  </div>
                  <div class="flex-1 min-w-0 flex flex-col gap-0.5 py-0.5">
                    <div class="text-[13px] font-extrabold text-slate-900 leading-tight line-clamp-1" x-text="p.name"></div>
                    <div class="text-[11px] text-slate-500 line-clamp-1" x-text="metaLine(p)"></div>
                    <div class="text-[11px] text-sky-800 bg-sky-50 border border-sky-100 rounded-lg px-2 py-1 leading-snug line-clamp-2 mt-0.5">
                      <span class="font-semibold text-sky-600">AI:</span>
                      <span x-text="' ' + p.reason"></span>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap mt-auto pt-1">
                      <span x-show="p.min_price > 0" class="text-[13px] font-extrabold text-slate-900">
                        <span x-text="'฿' + Number(p.min_price).toLocaleString('th-TH')"></span>
                        <span class="text-[10px] font-medium text-slate-500">/คืน</span>
                      </span>
                      <span x-show="p.rating_avg > 0" class="inline-flex items-center gap-0.5 text-[11px] font-semibold text-amber-700">
                        ⭐ <span x-text="Number(p.rating_avg).toFixed(1)"></span>
                      </span>
                      <span x-show="p.coupon_enabled" class="inline-flex items-center gap-0.5 text-[10px] font-bold text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-md px-1.5 py-0.5">🎫 คูปอง</span>
                    </div>
                  </div>
                </a>
              </template>
            </div>
            <div x-show="result && result.redirect" class="px-3 pb-3 bg-slate-50/80">
              <button type="button" @click="goAll()"
                      class="w-full py-2.5 rounded-xl bg-gradient-to-r from-sky-500 to-teal-500 hover:from-sky-600 hover:to-teal-600 text-white text-[12px] font-extrabold shadow-md transition">
                ดูผลทั้งหมด →
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="mt-4">
      <div class="rounded-2xl bg-gradient-to-r from-amber-400 via-orange-400 to-rose-400 p-4 flex items-stretch gap-3 shadow-lg ring-1 ring-white/35">
        <div class="text-[2rem] shrink-0 grid place-items-center w-14 rounded-xl bg-white/20 backdrop-blur-sm">🎫</div>
        <div class="flex-1 min-w-0 text-white py-0.5">
          <div class="font-extrabold text-[15px] leading-tight drop-shadow-sm">คูปองเงินสด 500 บาท</div>
          <div class="text-[13px] font-semibold mt-1 opacity-95 leading-snug">จ่ายเพียง 250 บาท — ใช้แทนเงินสดลดที่พัก</div>
        </div>
        <a href="<?= url('/coupons/buy') ?>" class="shrink-0 self-center bg-white text-orange-600 font-extrabold text-[11px] px-3 py-2 rounded-xl shadow-md whitespace-nowrap">ดูรายละเอียด ›</a>
      </div>
    </div>

  </div>
</section>

<!-- ========== HERO + การ์ดค้นหาทับ (desktop — ตามม็อกอัป) ========== -->
<section id="banner-slot-hero-desktop" class="hidden md:block relative overflow-visible scroll-mt-28 md:scroll-mt-36" x-data="paekanHero()">
  <div class="relative min-h-[580px] lg:min-h-[600px] pb-36 lg:pb-40">
    <div class="absolute inset-0 bg-forest-950">
      <template x-for="(s, idx) in slides" :key="'bg'+idx">
        <div x-show="i === idx"
             x-transition:enter="transition ease-out duration-700"
             x-transition:enter-start="opacity-0 scale-105"
             x-transition:enter-end="opacity-100 scale-100"
             class="absolute inset-0">
          <img :src="s.img" alt="" class="w-full h-full object-cover" loading="eager">
          <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/45 to-black/25"></div>
        </div>
      </template>
    </div>

    <template x-if="slides.length > 1">
      <div>
        <button type="button" @click="prev()" aria-label="สไลด์ก่อนหน้า"
                class="absolute left-4 lg:left-8 top-[40%] z-20 -translate-y-1/2 w-11 h-11 rounded-full bg-white/90 hover:bg-white border border-slate-200/80 text-forest-900 grid place-items-center transition shadow-lg">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <button type="button" @click="next()" aria-label="สไลด์ถัดไป"
                class="absolute right-4 lg:right-8 top-[40%] z-20 -translate-y-1/2 w-11 h-11 rounded-full bg-white/90 hover:bg-white border border-slate-200/80 text-forest-900 grid place-items-center transition shadow-lg">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </button>
      </div>
    </template>

    <template x-if="slides.length > 1">
      <div class="absolute bottom-[14.5rem] lg:bottom-[15.5rem] left-1/2 z-20 -translate-x-1/2 flex items-center gap-2 px-3 py-2 rounded-full bg-black/25 backdrop-blur-sm border border-white/15">
        <template x-for="(s, idx) in slides" :key="'dot'+idx">
          <button type="button" @click="go(idx)" class="h-2 rounded-full transition-all duration-300"
                  :class="i === idx ? 'w-8 bg-emerald-400 shadow-[0_0_12px_rgba(52,211,153,0.55)]' : 'w-2 bg-white/35 hover:bg-white/60'"
                  :aria-label="'สไลด์ที่ ' + (idx+1)"></button>
        </template>
      </div>
    </template>

    <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-8 pt-14 lg:pt-16 pb-6">
      <div class="max-w-2xl text-white">
        <h1 class="text-[2.25rem] lg:text-[2.85rem] font-extrabold leading-[1.15] tracking-tight drop-shadow-[0_2px_24px_rgba(0,0,0,0.35)]">
          <?= e((string)($heroCopy['desktop_title_line1'] ?? '')) ?><span class="hidden lg:inline"><br></span><span class="lg:hidden"> </span><?= e((string)($heroCopy['desktop_title_line2'] ?? '')) ?>
        </h1>
        <p class="mt-4 text-lg lg:text-xl font-bold text-amber-300 drop-shadow-md tracking-wide"><?= e((string)($heroCopy['promo'] ?? '')) ?></p>
        <ul class="mt-7 space-y-2.5 text-[14px] lg:text-[15px] text-white/95 font-medium leading-snug">
          <?php foreach (['bullet_1', 'bullet_2', 'bullet_3', 'bullet_4'] as $bk): ?>
          <li class="flex items-start gap-2.5"><span class="mt-0.5 text-emerald-300 shrink-0"><i data-lucide="check-circle" class="w-[18px] h-[18px]"></i></span><span><?= e((string)($heroCopy[$bk] ?? '')) ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>

  <div class="relative z-30 max-w-6xl mx-auto px-6 lg:px-8 -mt-[8.5rem] lg:-mt-[9rem] mb-10 home-hero-paekan">
    <form id="home-search-desktop" :action="heroFormAction()" method="get"
          x-data="paekanDesktopHeroSearch()"
          @submit="submitHero($event)"
          class="bg-white rounded-[1.35rem] border border-slate-300/85 overflow-hidden
                 shadow-[0_32px_64px_-14px_rgba(15,23,42,0.45),0_18px_40px_-14px_rgba(14,116,144,0.2),0_8px_24px_-8px_rgba(5,150,105,0.12),0_2px_8px_-2px_rgba(0,0,0,0.1)]
                 ring-1 ring-slate-900/[0.06]">

      <div class="flex gap-2 overflow-x-auto no-scrollbar px-4 pt-3.5 pb-2 bg-gradient-to-r from-sky-50 via-white to-emerald-50/90 border-b border-slate-100">
        <?php foreach ($heroMobTabs as $ht): ?>
        <button type="button" @click="pickHeroTab('<?= e($ht['id']) ?>')"
                class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-[12px] font-bold border transition"
                :class="heroTab === '<?= e($ht['id']) ?>'
                  ? 'bg-sky-600 text-white border-sky-600 shadow-md'
                  : 'bg-white text-slate-700 border-slate-200 hover:border-sky-200'">
          <i data-lucide="<?= e($ht['icon']) ?>" class="w-4 h-4 shrink-0 stroke-[2px]"></i>
          <span><?= e($ht['label']) ?></span>
        </button>
        <?php endforeach; ?>
      </div>

      <div class="px-4 pt-3 pb-2 border-b border-slate-100 bg-slate-50/50 space-y-2" x-show="heroTab === 'activities'" x-cloak>
        <label class="text-[11px] font-bold text-slate-500">หมวดที่เที่ยว</label>
        <select x-model="actCategory"
                class="w-full max-w-md rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-bold outline-none focus:border-sky-600">
          <option value="">ทุกหมวด</option>
          <?php foreach (\App\Models\ActivityProduct::CATEGORIES as $ck => $cl): ?>
            <option value="<?= e($ck) ?>"><?= e($cl) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="px-4 pt-3 pb-3 border-b border-slate-100 flex flex-wrap gap-2 items-center" x-show="heroTab === 'raft'">
        <span class="text-[11px] font-bold text-slate-500 w-full sm:w-auto">แบบแพ</span>
        <button type="button" @click="raftVariant = ''" class="px-3 py-1.5 rounded-lg text-xs font-bold border" :class="raftVariant === '' ? 'bg-sky-600 text-white border-sky-600' : 'bg-white border-slate-200'">ทั้งหมด</button>
        <button type="button" @click="raftVariant = 'shore'" class="px-3 py-1.5 rounded-lg text-xs font-bold border" :class="raftVariant === 'shore' ? 'bg-sky-600 text-white border-sky-600' : 'bg-white border-slate-200'">แพริมน้ำ</button>
        <button type="button" @click="raftVariant = 'towed'" class="px-3 py-1.5 rounded-lg text-xs font-bold border" :class="raftVariant === 'towed' ? 'bg-sky-600 text-white border-sky-600' : 'bg-white border-slate-200'">แพลาก</button>
      </div>

      <div class="px-4 py-3 border-b border-slate-100 flex flex-wrap gap-3 items-center bg-slate-50/40" x-show="heroTab === 'pool_villa'">
        <span class="text-[11px] font-bold text-slate-500">พูลวิลล่า</span>
        <label class="inline-flex items-center gap-1.5 text-xs font-semibold cursor-pointer"><input type="checkbox" name="amenities[]" value="3" class="rounded text-emerald-600" :disabled="heroTab !== 'pool_villa'"> สระว่ายน้ำ</label>
        <label class="inline-flex items-center gap-1.5 text-xs font-semibold cursor-pointer"><input type="checkbox" name="amenities[]" value="14" class="rounded text-emerald-600" :disabled="heroTab !== 'pool_villa'"> วิวแม่น้ำ</label>
        <label class="inline-flex items-center gap-1.5 text-xs font-semibold cursor-pointer"><input type="checkbox" name="amenities[]" value="13" class="rounded text-emerald-600" :disabled="heroTab !== 'pool_villa'"> วิวภูเขา</label>
      </div>

      <div class="px-4 py-3 border-b border-slate-100 bg-white grid sm:grid-cols-2 gap-3" x-show="heroTab === 'raft' || heroTab === 'pool_villa'">
        <div>
          <label class="text-[11px] font-bold text-slate-600 mb-1 block flex items-center gap-1.5"><i data-lucide="bed-double" class="w-3.5 h-3.5 text-sky-600"></i> ห้องนอนขั้นต่ำ / ยูนิต</label>
          <select name="bedrooms_min"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-900 outline-none focus:border-sky-600"
                  :disabled="heroTab !== 'raft' && heroTab !== 'pool_villa'">
            <option value="">ไม่ระบุ</option>
            <?php for ($br = 1; $br <= 10; $br++): ?>
              <option value="<?= $br ?>"><?= $br ?> ห้องนอน</option>
            <?php endfor; ?>
          </select>
        </div>
        <div>
          <label class="text-[11px] font-bold text-slate-600 mb-1 block flex items-center gap-1.5"><i data-lucide="shower-head" class="w-3.5 h-3.5 text-sky-600"></i> ห้องน้ำขั้นต่ำ / ยูนิต</label>
          <select name="bathrooms_min"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-900 outline-none focus:border-sky-600"
                  :disabled="heroTab !== 'raft' && heroTab !== 'pool_villa'">
            <option value="">ไม่ระบุ</option>
            <?php for ($ba = 1; $ba <= 10; $ba++): ?>
              <option value="<?= $ba ?>"><?= $ba ?> ห้องน้ำ</option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/30" x-show="heroTab === 'stay'">
        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1 block">ประเภทที่พัก</label>
        <select x-model="staySubtype" class="w-full max-w-sm rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold outline-none focus:border-teal-600">
          <option value="">ทั้งหมดในกลุ่มที่พักทั่วไป</option>
          <option value="resort">รีสอร์ท</option>
          <option value="homestay">โฮมสเตย์</option>
          <option value="hotel">โรงแรม</option>
          <option value="house">บ้านพัก</option>
        </select>
      </div>

      <input type="hidden" name="type" :value="effectivePropType()" :disabled="!effectivePropType()">
      <input type="hidden" name="raft_variant" :value="raftVariant" :disabled="heroTab !== 'raft' || raftVariant === ''">
      <input type="hidden" name="guests" :value="guestsHiddenValue()" :disabled="heroTab === 'activities' || guestsHiddenValue() === ''">
      <input type="hidden" name="budget_min" :value="budgetMinSubmit()" :disabled="heroTab === 'activities'">
      <input type="hidden" name="budget_max" :value="budgetMaxSubmit()" :disabled="heroTab === 'activities'">

      <div class="flex flex-col xl:flex-row xl:items-stretch xl:min-h-[108px]">
        <div class="xl:flex-[1.1] xl:min-w-0 flex flex-col justify-center gap-2 px-5 py-4 border-b xl:border-b-0 xl:border-r border-slate-100">
          <span class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide text-slate-400"><i data-lucide="map-pin" class="w-4 h-4 text-teal-600"></i> โซน</span>
          <select name="zone" x-model="zoneVal" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-900 outline-none focus:border-teal-600">
            <option value="">ทุกพื้นที่</option>
            <?php foreach ($zones as $z): ?>
              <option value="<?= e($z['zone']) ?>"><?= e($z['zone']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="xl:w-[9.5rem] shrink-0 flex flex-col justify-center gap-1 px-5 py-4 border-b xl:border-b-0 xl:border-r border-slate-100 group" x-show="heroTab !== 'activities'">
          <span class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide text-slate-400"><i data-lucide="calendar" class="w-4 h-4 text-forest-700"></i> เช็คอิน</span>
          <label class="relative min-h-[40px] flex items-center mt-0.5 cursor-pointer rounded-lg -mx-1 px-1 py-0.5 hover:bg-slate-100/90 transition-colors outline-none focus-within:ring-2 focus-within:ring-forest-400/50 block w-full"
                 aria-label="เลือกวันเช็คอิน">
            <span class="pointer-events-none flex items-center gap-2 w-full relative z-0">
              <span class="font-bold text-slate-900 text-[15px] tabular-nums truncate flex-1 min-w-0" x-text="dateDisplay(checkIn)"></span>
              <i data-lucide="calendar" class="w-4 h-4 text-slate-500 shrink-0"></i>
            </span>
            <input type="date" name="check_in" x-model="checkIn" x-ref="deskCheckIn"
                   @change="syncCheckoutAfterCheckin()"
                   :disabled="heroTab === 'activities'"
                   class="absolute inset-0 z-10 h-full w-full opacity-0 cursor-pointer [font-size:1rem]">
          </label>
        </div>

        <div class="xl:w-[9.5rem] shrink-0 flex flex-col justify-center gap-1 px-5 py-4 border-b xl:border-b-0 xl:border-r border-slate-100 group" x-show="heroTab !== 'activities'">
          <span class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide text-slate-400"><i data-lucide="calendar" class="w-4 h-4 text-forest-700 opacity-80"></i> เช็คเอาท์</span>
          <label class="relative min-h-[40px] flex items-center mt-0.5 cursor-pointer rounded-lg -mx-1 px-1 py-0.5 hover:bg-slate-100/90 transition-colors outline-none focus-within:ring-2 focus-within:ring-forest-400/50 block w-full"
                 aria-label="เลือกวันเช็คเอาท์">
            <span class="pointer-events-none flex items-center gap-2 w-full relative z-0">
              <span class="font-bold text-slate-900 text-[15px] tabular-nums truncate flex-1 min-w-0" x-text="dateDisplay(checkOut)"></span>
              <i data-lucide="calendar" class="w-4 h-4 text-slate-500 shrink-0"></i>
            </span>
            <input type="date" name="check_out" x-model="checkOut" :min="checkIn" x-ref="deskCheckOut"
                   @change="syncCheckoutAfterCheckin()"
                   :disabled="heroTab === 'activities'"
                   class="absolute inset-0 z-10 h-full w-full opacity-0 cursor-pointer [font-size:1rem]">
          </label>
        </div>

        <div class="xl:w-[15rem] shrink-0 flex flex-col justify-center gap-1.5 px-4 py-4 border-b xl:border-b-0 xl:border-r border-slate-100" x-show="heroTab !== 'activities'">
          <span class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide text-slate-400"><i data-lucide="users" class="w-4 h-4 text-forest-700"></i> ผู้เข้าพัก</span>
          <p class="text-[13px] font-extrabold text-sky-800 leading-tight truncate" x-text="guestSummaryLine()"></p>
          <div class="space-y-1 min-w-0" x-show="heroTab === 'raft' || heroTab === 'pool_villa'">
            <label class="text-[10px] font-bold text-slate-500">ช่วงจำนวนคน (เลือกเร็ว)</label>
            <select class="w-full rounded-lg border border-slate-200 bg-white py-1.5 px-2 text-[11px] font-bold text-slate-900 outline-none focus:border-sky-500"
                    aria-label="เลือกช่วงจำนวนคนเร็ว"
                    @change="if ($event.target.value) { applyGuestTotalFromPreset(+$event.target.value); $event.target.selectedIndex = 0; }">
              <option value="">กำหนดเอง — เลือกด้านล่าง</option>
              <option value="6">ประมาณ 2–6 คน</option>
              <option value="12">ประมาณ 8–12 คน</option>
              <option value="20">ประมาณ 15–20 คน</option>
              <option value="25">ประมาณ 20–25 คน</option>
              <option value="30">ประมาณ 25–30 คน</option>
              <option value="40">ประมาณ 31–40 คน</option>
              <option value="50">ประมาณ 40–50 คน</option>
              <option value="99">ประมาณ 50 คนขึ้นไป</option>
            </select>
          </div>
          <div class="flex gap-1.5">
            <select x-model.number="rooms" class="flex-1 min-w-0 rounded-lg border border-slate-200 bg-white py-1.5 px-1 text-[11px] font-bold outline-none focus:border-sky-500">
              <?php for ($r = 1; $r <= 8; $r++): ?><option value="<?= $r ?>"><?= $r ?> ห้อง</option><?php endfor; ?>
            </select>
            <select x-model.number="adults" class="flex-1 min-w-0 rounded-lg border border-slate-200 bg-white py-1.5 px-1 text-[11px] font-bold outline-none focus:border-sky-500">
              <?php for ($ga = 1; $ga <= 99; $ga++): ?><option value="<?= $ga ?>"><?= $ga ?> ใหญ่</option><?php endfor; ?>
            </select>
            <select x-model.number="children" class="flex-1 min-w-0 rounded-lg border border-slate-200 bg-white py-1.5 px-1 text-[11px] font-bold outline-none focus:border-sky-500">
              <?php for ($gc = 0; $gc <= 30; $gc++): ?><option value="<?= $gc ?>"><?= $gc ?> เด็ก</option><?php endfor; ?>
            </select>
          </div>
        </div>

        <div class="xl:flex-[1.25] xl:min-w-[11rem] flex flex-col justify-center px-5 py-4 border-b xl:border-b-0 xl:border-r border-slate-100" x-show="heroTab !== 'activities'">
          <span class="flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1"><i data-lucide="wallet" class="w-4 h-4 text-forest-700"></i> งบต่อคืน</span>
          <p class="text-[13px] font-bold text-forest-800 tabular-nums mb-2 truncate" x-text="budgetSummaryLine()"></p>
          <div class="pm-range-wrap relative h-9 w-full max-w-[220px]">
            <div class="absolute left-0 right-0 top-1/2 -translate-y-1/2 h-2 rounded-full bg-slate-200 shadow-inner pointer-events-none"></div>
            <div class="absolute left-0 top-1/2 -translate-y-1/2 h-2 rounded-full bg-gradient-to-r from-emerald-400 to-forest-700 shadow-sm pointer-events-none transition-[left,width] duration-75"
                 :style="'left:' + bandLeftPct() + '%; width:' + bandWidthPct() + '%;'"></div>
            <input type="range" class="relative z-[5]" :min="minV" :max="maxV" :step="step" x-model.number="low" @input="clampLow()">
            <input type="range" class="relative z-[6]" :min="minV" :max="maxV" :step="step" x-model.number="high" @input="clampHigh()">
          </div>
        </div>

        <button type="submit"
                class="xl:w-[160px] shrink-0 flex flex-row xl:flex-col items-center justify-center gap-2 px-6 py-5 xl:py-0 bg-gradient-to-br from-sky-600 to-emerald-600 hover:from-sky-700 hover:to-emerald-700 text-white font-extrabold text-[15px] tracking-tight transition shadow-inner ring-1 ring-sky-900/10"
                :aria-label="primarySubmitLabel()">
          <i data-lucide="search" class="w-6 h-6 shrink-0 stroke-[2.5px]"></i>
          <span class="leading-tight text-center text-[13px]" x-text="primarySubmitLabel()"></span>
        </button>
      </div>

      <div x-show="advanced" x-transition
           class="border-t border-slate-200/90 bg-gradient-to-b from-slate-50 to-slate-100/90 p-5 sm:p-6 space-y-5 shadow-[inset_0_2px_12px_rgba(15,23,42,0.06)]">
        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500 flex items-center gap-2">
          <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5 text-forest-700"></i>
          ฟิลเตอร์เพิ่มเติม — เหมือนหน้าแพที่พัก
        </p>
        <div x-show="heroTab === 'all'" class="max-w-md">
          <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1 block flex items-center gap-1.5"><i data-lucide="anchor" class="w-3.5 h-3.5 text-forest-700"></i> ประเภทแพ (เมื่อเลือกแท็บทั้งหมด)</label>
          <select name="raft_variant"
                  :disabled="heroTab !== 'all'"
                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-bold outline-none focus:border-forest-600 disabled:opacity-45 shadow-sm">
            <option value="">ทั้งหมด</option>
            <option value="shore">แพริมน้ำ</option>
            <option value="towed">แพลาก</option>
          </select>
        </div>
        <div class="flex flex-wrap gap-6 pt-1 border-t border-slate-200/80 border-dashed">
          <label class="inline-flex items-center gap-2 text-sm font-semibold text-forest-950 cursor-pointer select-none">
            <input type="checkbox" name="pet" value="1" class="rounded border-slate-300 text-forest-700 focus:ring-forest-500">
            🐶 รับสัตว์เลี้ยง
          </label>
          <label class="inline-flex items-center gap-2 text-sm font-semibold text-forest-950 cursor-pointer select-none">
            <input type="checkbox" name="coupon" value="1" class="rounded border-slate-300 text-forest-700 focus:ring-forest-500">
            🎫 ใช้คูปองได้
          </label>
        </div>
        <?php if (!empty($amenities)): ?>
        <div>
          <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-2 block flex items-center gap-1.5"><i data-lucide="check-square" class="w-3.5 h-3.5 text-forest-700"></i> สิ่งอำนวยความสะดวก</label>
          <div class="max-h-52 overflow-y-auto rounded-xl border border-slate-200 bg-white p-3 shadow-inner grid sm:grid-cols-2 gap-2 pr-1">
            <?php foreach ($amenities as $a): ?>
              <label class="flex items-start gap-2 text-sm text-slate-700 cursor-pointer leading-snug">
                <input type="checkbox" name="amenities[]" value="<?= (int)$a['id'] ?>" class="rounded border-slate-300 text-forest-700 focus:ring-forest-500 mt-0.5 shrink-0">
                <span><?= e($a['name']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="flex justify-center py-2.5 bg-white border-t border-slate-100">
        <button type="button" @click="advanced = !advanced"
                class="inline-flex items-center gap-1 text-sm font-bold text-forest-900 hover:text-forest-700 transition">
          <span x-text="advanced ? 'ซ่อนตัวกรองขั้นสูง' : 'ค้นหาขั้นสูง'"></span>
          <i data-lucide="chevron-down" class="w-4 h-4 transition-transform" :class="advanced ? 'rotate-180' : ''"></i>
        </button>
      </div>
    </form>
  </div>
</section>

<div id="banner-slot-home-desktop-coupon-strip" class="scroll-mt-28 md:scroll-mt-36" aria-hidden="true"></div>
<?php
$couponStripDesktop = ($bannersBySlot['home_desktop_coupon_strip'] ?? [])[0] ?? null;
\App\Core\View::partial('partials/home-coupon-strip-desktop', ['banner' => $couponStripDesktop]);
?>
<?php \App\Core\View::partial('partials/home-sections-loop', [
    'homeSectionPlan' => $homeSectionPlan ?? [],
    'bannersBySlot'   => $bannersBySlot ?? [],
]); ?>