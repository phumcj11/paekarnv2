<?php
use App\Core\Session;
use App\Models\Setting;
use App\Controllers\Admin\SettingsController;
use App\Services\CompareService;
$siteName = Setting::get('site_name', 'แพกาญ.com');
$flashSuccess = Session::flash('success');
$flashError   = Session::flash('error');
$flashInfo    = Session::flash('info');
Session::consumeOld();

$hardDescFallback = 'จองที่พักกาญจนบุรีที่ตรวจสอบแล้ว แพ รีสอร์ท บ้านพัก โฮมสเตย์ รีวิวจริง ใช้คูปองเงินสดลดค่าที่พัก';
$seoDefaultDesc   = trim((string)Setting::get('seo_default_description', ''));
$pageDesc = (isset($meta_description) && $meta_description !== null && $meta_description !== '')
    ? (string)$meta_description
    : ($seoDefaultDesc !== '' ? $seoDefaultDesc : $hardDescFallback);

$titleFallback = $siteName . ' — ที่พักกาญจนบุรีตรวจสอบจริง คูปองสมาชิก';
$pageTitle = (isset($meta_title) && $meta_title !== null && $meta_title !== '')
    ? (string)$meta_title
    : $titleFallback;

$seoOgRaw = trim((string)Setting::get('seo_og_image', ''));
$defaultOg = '';
if ($seoOgRaw !== '') {
    $defaultOg = preg_match('#^https?://#i', $seoOgRaw) ? $seoOgRaw : upload_url($seoOgRaw);
}
$metaOgIn = isset($meta_og_image) ? trim((string)$meta_og_image) : '';
$ogImage = $metaOgIn !== '' ? $metaOgIn : $defaultOg;

$ogType = (isset($og_type) && $og_type !== null && $og_type !== '') ? (string)$og_type : 'website';
$canonicalUrl = isset($meta_canonical) && $meta_canonical !== '' ? (string)$meta_canonical : '';
?><!DOCTYPE html>
<html lang="<?= \App\Core\I18n::locale() ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($pageDesc) ?>">
<meta name="theme-color" content="#14532D">
<meta name="csrf-token" content="<?= \App\Core\Csrf::token() ?>">
<link rel="icon" href="<?= asset('site-logo.png') ?>" type="image/png">
<link rel="apple-touch-icon" href="<?= asset('site-logo.png') ?>">
<?php if ($canonicalUrl !== ''): ?>
<link rel="canonical" href="<?= e($canonicalUrl) ?>">
<?php endif; ?>
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($pageDesc) ?>">
<meta property="og:type" content="<?= e($ogType) ?>">
<?php if ($ogImage !== ''): ?>
<meta property="og:image" content="<?= e($ogImage) ?>">
<?php endif; ?>
<?php if ($canonicalUrl !== ''): ?>
<meta property="og:url" content="<?= e($canonicalUrl) ?>">
<?php endif; ?>
<?php
$ga4Id = trim((string) Setting::get('ga4_measurement_id', ''));
if ($ga4Id !== '' && preg_match('/^G-[A-Z0-9]+$/', $ga4Id)): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga4Id) ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '<?= e($ga4Id) ?>');
</script>
<?php endif; ?>
<?php if (!empty($schema_org_json)): ?>
<script type="application/ld+json"><?= $schema_org_json ?></script>
<?php endif; ?>

<?php
// ======= Font settings =======
$fontOptions   = \App\Controllers\Admin\SettingsController::$fontOptions;
$fontBodyKey   = (string)Setting::get('font_body', 'noto_sans_thai');
$fontHeadKey   = (string)Setting::get('font_heading', 'kanit');
$fontSizeBase  = (int)Setting::get('font_size_base', 15);
if ($fontSizeBase < 13 || $fontSizeBase > 20) $fontSizeBase = 15;
$bodyOpt  = $fontOptions[$fontBodyKey]  ?? $fontOptions['noto_sans_thai'];
$headOpt  = $fontOptions[$fontHeadKey]  ?? $fontOptions['kanit'];
$gfonts   = array_unique([$bodyOpt['gfont'], $headOpt['gfont']]);
$bodyCSS  = $bodyOpt['css'];
$headCSS  = $headOpt['css'];
$gfontsUrl = 'https://fonts.googleapis.com/css2?'
    . implode('&', array_map(fn($f) => 'family=' . $f, $gfonts))
    . '&display=swap';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="<?= e($gfontsUrl) ?>" rel="stylesheet">
<?php if (!empty($preload_lcp_image)): ?>
<link rel="preload" as="image" href="<?= e($preload_lcp_image) ?>" fetchpriority="high">
<?php endif; ?>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"></noscript>
<!-- Compare store, SweetAlert2, Lucide, Alpine — all moved to footer -->
<style>
:root {
  --font-body: <?= $bodyCSS ?>;
  --font-heading: <?= $headCSS ?>;
  --font-size-base: <?= $fontSizeBase ?>px;
}
html { font-size: var(--font-size-base); scroll-behavior: smooth; }
body { font-family: var(--font-body); -webkit-font-smoothing: antialiased; }
h1,h2,h3,h4,h5,h6,.font-heading { font-family: var(--font-heading); }
.no-scrollbar::-webkit-scrollbar{display:none}
.no-scrollbar{-ms-overflow-style:none;scrollbar-width:none}
[x-cloak]{display:none!important}
/* Fallback หน้าแรกมือถือ */
#home-mobile-hero{background:linear-gradient(180deg,#0369a1 0%,#0f766e 52%,#134e4a 100%);color:#fff}
#home-mobile-hero .hero-mobile-headline{color:#fff!important;text-shadow:0 2px 14px rgba(0,0,0,.4)}
#home-mobile-hero .hero-mobile-promo{color:#fde68a!important;text-shadow:0 1px 8px rgba(0,0,0,.35)}
main{min-height:35vh}
</style>
</head>
<body class="bg-cloud text-ink pb-[4.75rem] md:pb-0">

<?php \App\Core\View::partial('partials/nav'); ?>

<?php if ($flashSuccess): ?>
<div class="fixed top-20 left-1/2 -translate-x-1/2 z-50 bg-emerald-600 text-white px-5 py-3 rounded-xl shadow-lg flex items-center gap-2" x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,4000)">
  <i data-lucide="check-circle" class="w-5 h-5"></i><?= e($flashSuccess) ?>
</div>
<?php endif; ?>
<?php if ($flashError): ?>
<div class="fixed top-20 left-1/2 -translate-x-1/2 z-50 bg-rose-600 text-white px-5 py-3 rounded-xl shadow-lg flex items-center gap-2" x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,4500)">
  <i data-lucide="alert-circle" class="w-5 h-5"></i><?= e($flashError) ?>
</div>
<?php endif; ?>
<?php if ($flashInfo): ?>
<div class="fixed top-20 left-1/2 -translate-x-1/2 z-50 bg-primary-600 text-white px-5 py-3 rounded-xl shadow-lg flex items-center gap-2" x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,4500)">
  <i data-lucide="info" class="w-5 h-5"></i><?= e($flashInfo) ?>
</div>
<?php endif; ?>

<main><?= $content ?? '' ?></main>

<?php \App\Core\View::partial('partials/footer'); ?>
<?php \App\Core\View::partial('partials/mobile-tab-bar', ['page' => $page ?? '']); ?>
<?php \App\Core\View::partial('partials/compare-toast'); ?>
<?php \App\Core\View::partial('partials/compare-bar'); ?>
<?php \App\Core\View::partial('partials/floating-actions', ['page' => $page ?? '']); ?>

<script>
// Compare store config — inline because it uses PHP-generated URLs
window.__PAEKAN_COMPARE__ = {
  key: 'paekan_compare_v1',
  hintKey: 'paekan_compare_hint_seen',
  max: <?= CompareService::MAX_ITEMS ?>,
  isCustomer: <?= \App\Core\Auth::isCustomer() ? 'true' : 'false' ?>,
  compareUrl: <?= json_encode(url('/compare'), JSON_UNESCAPED_SLASHES) ?>,
  syncUrl: <?= json_encode(url('/api/compare/sync'), JSON_UNESCAPED_SLASHES) ?>,
  clearUrl: <?= json_encode(url('/api/compare/clear'), JSON_UNESCAPED_SLASHES) ?>,
  raftsUrl: <?= json_encode(url('/rafts'), JSON_UNESCAPED_SLASHES) ?>,
  searchUrl: <?= json_encode(url('/properties'), JSON_UNESCAPED_SLASHES) ?>
};
document.addEventListener('alpine:init', () => {
  Alpine.store('compare', {
    key: window.__PAEKAN_COMPARE__.key,
    hintKey: window.__PAEKAN_COMPARE__.hintKey,
    max: window.__PAEKAN_COMPARE__.max,
    items: [],
    ready: false,
    toast: { show: false, message: '', type: 'success', icon: 'scale' },
    toastTimer: null,
    init() {
      this.items = this.readLocal();
      this.ready = true;
      this.sync();
      this.refreshIcons();
    },
    normalize(item) {
      const p = parseInt(item.property_id || item.propertyId || item.p || 0, 10);
      const u = parseInt(item.unit_id || item.unitId || item.u || 0, 10);
      if (!p || !u) return null;
      return {
        property_id: p,
        unit_id: u,
        title: item.title || item.unit_name || '',
        subtitle: item.subtitle || item.property_name || '',
        image: item.image || item.cover_url || '',
        detail_url: item.detail_url || '',
        added_at: item.added_at || item.addedAt || new Date().toISOString()
      };
    },
    readLocal() {
      try {
        const raw = JSON.parse(localStorage.getItem(this.key) || '[]');
        const seen = {};
        return (Array.isArray(raw) ? raw : []).map((item) => this.normalize(item)).filter((item) => {
          if (!item) return false;
          const key = item.property_id + ':' + item.unit_id;
          if (seen[key]) return false;
          seen[key] = true;
          return true;
        }).slice(0, this.max);
      } catch (e) {
        return [];
      }
    },
    save() {
      localStorage.setItem(this.key, JSON.stringify(this.items.slice(0, this.max)));
      this.refreshIcons();
    },
    isSelected(propertyId, unitId) {
      const p = parseInt(propertyId, 10);
      const u = parseInt(unitId, 10);
      return this.items.some((item) => item.property_id === p && item.unit_id === u);
    },
    toggle(item) {
      const normalized = this.normalize(item || {});
      if (!normalized) return;
      if (this.isSelected(normalized.property_id, normalized.unit_id)) {
        this.remove(normalized.property_id, normalized.unit_id);
        return;
      }
      if (this.items.length >= this.max) {
        this.alertMaxReached();
        return;
      }
      this.items.push(normalized);
      this.save();
      this.alertAdded(normalized);
      this.sync();
      this.markHintSeen();
    },
    addAndGo(item) {
      const normalized = this.normalize(item || {});
      if (!normalized) return;
      if (!this.isSelected(normalized.property_id, normalized.unit_id)) {
        if (this.items.length >= this.max) {
          this.alertMaxReached();
          return;
        }
        this.items.push(normalized);
        this.save();
        this.markHintSeen();
      }
      window.location.href = this.compareUrl();
    },
    remove(propertyId, unitId) {
      const p = parseInt(propertyId, 10);
      const u = parseInt(unitId, 10);
      const found = this.items.find((item) => item.property_id === p && item.unit_id === u);
      this.items = this.items.filter((item) => !(item.property_id === p && item.unit_id === u));
      this.save();
      this.alertRemoved(found || null);
      this.sync();
    },
    clear() {
      if (!this.items.length) {
        this.swalToast('ยังไม่มีแพในรายการเทียบ', 'info');
        return;
      }
      const count = this.items.length;
      if (window.Swal) {
        Swal.fire({
          title: 'ล้างรายการเทียบ?',
          html: 'จะเอาแพที่เลือก <b>' + count + '</b> หลังออกจากรายการทั้งหมด',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'ล้างเลย',
          cancelButtonText: 'ยกเลิก',
          confirmButtonColor: '#e11d48',
          cancelButtonColor: '#94a3b8',
          reverseButtons: true
        }).then((result) => {
          if (result.isConfirmed) this.doClear();
        });
        return;
      }
      if (window.confirm('ล้างรายการเทียบทั้งหมด?')) {
        this.doClear();
      }
    },
    doClear() {
      this.items = [];
      this.save();
      fetch(window.__PAEKAN_COMPARE__.clearUrl, { method: 'POST', headers: { 'Accept': 'application/json' } }).catch(() => {});
      const path = window.location.pathname || '';
      const onComparePage = /\/compare(?:\/|$|\?)/.test(path);
      if (onComparePage) {
        window.location.href = window.__PAEKAN_COMPARE__.searchUrl || '/properties';
        return;
      }
      this.swalToast('ล้างรายการเทียบแล้ว', 'success');
    },
    compareUrl() {
      if (!this.items.length) return window.__PAEKAN_COMPARE__.compareUrl;
      const u = this.items.map((item) => item.property_id + '-' + item.unit_id).join(',');
      return window.__PAEKAN_COMPARE__.compareUrl + '?u=' + encodeURIComponent(u);
    },
    sync() {
      if (!window.__PAEKAN_COMPARE__.isCustomer) {
        return;
      }
      fetch(window.__PAEKAN_COMPARE__.syncUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ items: this.items })
      }).then((res) => res.ok ? res.json() : null)
        .then((json) => {
          if (!json || !json.ok || !Array.isArray(json.rows)) return;
          this.items = json.rows.map((row) => this.normalize({
            property_id: row.property_id,
            unit_id: row.unit_id,
            title: row.unit_name,
            subtitle: row.property_name,
            image: row.cover_url,
            detail_url: row.detail_url
          })).filter(Boolean).slice(0, this.max);
          this.save();
        }).catch(() => {});
    },
    notify(message, type, icon) {
      this.toast = { show: true, message: message, type: type || 'success', icon: icon || 'scale' };
      window.clearTimeout(this.toastTimer);
      this.refreshIcons();
      this.toastTimer = window.setTimeout(() => {
        this.toast.show = false;
      }, type === 'warn' ? 3200 : 2400);
    },
    escHtml(text) {
      return String(text || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    },
    swalToast(title, icon, text) {
      if (window.Swal) {
        Swal.fire({
          toast: true,
          position: 'top',
          icon: icon || 'success',
          title: title,
          text: text || '',
          showConfirmButton: false,
          timer: icon === 'info' ? 2600 : 3000,
          timerProgressBar: true
        });
        return;
      }
      this.notify(title, icon === 'warning' ? 'warn' : 'success', icon === 'info' ? 'info' : 'scale');
    },
    alertAdded(item) {
      const count = this.items.length;
      const remaining = Math.max(0, this.max - count);
      const name = this.escHtml(item.title || 'แพที่เลือก');
      if (window.Swal) {
        Swal.fire({
          icon: 'success',
          title: 'เพิ่มในรายการเทียบแล้ว',
          html: '<p class="font-semibold text-slate-800">' + name + '</p>'
            + '<p class="text-sm text-slate-600 mt-2">เลือกแล้ว <b>' + count + '</b> / ' + this.max + ' หลัง'
            + (remaining > 0 ? ' · เพิ่มได้อีก ' + remaining + ' หลัง' : ' · ครบแล้ว') + '</p>'
            + '<p class="text-xs text-teal-700 mt-3">กดไอคอน <b>เทียบ</b> มุมล่างขวาเมื่อพร้อมเปรียบเทียบ</p>',
          confirmButtonText: 'เข้าใจแล้ว',
          confirmButtonColor: '#0d9488',
          timer: 5000,
          timerProgressBar: true
        });
        return;
      }
      this.notify('เพิ่มในรายการเทียบแล้ว (' + count + ' หลัง)', 'success', 'scale');
    },
    alertRemoved(item) {
      const name = item && item.title ? String(item.title) : '';
      if (window.Swal) {
        Swal.fire({
          toast: true,
          position: 'top',
          icon: 'info',
          title: 'เอาออกจากรายการเทียบแล้ว',
          text: name,
          showConfirmButton: false,
          timer: 2800,
          timerProgressBar: true
        });
        return;
      }
      this.notify('เอาออกจากรายการเทียบแล้ว', 'success', 'x');
    },
    alertMaxReached() {
      if (window.Swal) {
        Swal.fire({
          icon: 'warning',
          title: 'เลือกครบแล้ว',
          text: 'เทียบได้สูงสุด ' + this.max + ' หลัง — ลบออกจากรายการก่อนเพิ่มใหม่',
          confirmButtonText: 'เข้าใจแล้ว',
          confirmButtonColor: '#0d9488'
        });
        return;
      }
      this.notify('เทียบได้สูงสุด ' + this.max + ' หลัง', 'warn', 'alert-circle');
    },
    shouldShowHint() {
      try {
        return this.items.length === 0 && localStorage.getItem(this.hintKey) !== '1';
      } catch (e) {
        return false;
      }
    },
    markHintSeen() {
      try { localStorage.setItem(this.hintKey, '1'); } catch (e) {}
    },
    refreshIcons() {
      queueMicrotask(() => {
        if (window.lucide) {
          window.lucide.createIcons();
          requestAnimationFrame(() => window.lucide && window.lucide.createIcons());
        }
      });
    }
  });

  Alpine.data('paymentBlock', (endpoint, amountKey) => ({
    paymentAmount: 0,
    qrSrc: '',
    qrLoading: false,
    qrError: '',
    copied: '',
    slipName: '',
    slipPreview: '',
    slipSize: '',

    parentFormData() {
      const form = this.$el.closest('form');
      if (!form || !form._x_dataStack || !form._x_dataStack.length) {
        return null;
      }
      return form._x_dataStack[form._x_dataStack.length - 1];
    },

    resolveMethod() {
      const stack = this.$el._x_dataStack || [];
      for (let i = 0; i < stack.length; i++) {
        if (stack[i] && typeof stack[i].method === 'string') {
          return stack[i].method;
        }
      }
      return 'promptpay';
    },

    syncAmountFromParent() {
      const parent = this.parentFormData();
      if (!parent) return;
      const value = parent[amountKey];
      const num = typeof value === 'number' ? value : Number(value);
      this.paymentAmount = Number.isFinite(num) ? num : 0;
    },

    formatAmount() {
      return (this.paymentAmount || 0).toLocaleString('th-TH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    },

    copy(text, key) {
      if (!text) return;
      navigator.clipboard.writeText(text).then(() => {
        this.copied = key;
        setTimeout(() => { this.copied = ''; }, 1500);
      }).catch(() => { this.copied = ''; });
    },

    previewSlip(ev) {
      const f = ev.target.files && ev.target.files[0];
      if (!f) {
        this.slipName = '';
        this.slipPreview = '';
        this.slipSize = '';
        return;
      }
      this.slipName = f.name;
      this.slipSize = (f.size / 1024).toFixed(0) + ' KB';
      if (f.type.startsWith('image/')) {
        const r = new FileReader();
        r.onload = (e) => { this.slipPreview = e.target.result; };
        r.readAsDataURL(f);
      } else {
        this.slipPreview = '';
      }
    },

    refreshQr() {
      if (this.resolveMethod() !== 'promptpay') return;
      this.syncAmountFromParent();
      this.qrLoading = true;
      this.qrError = '';
      this.qrSrc = '';
      const url = endpoint + '?amount=' + encodeURIComponent(this.paymentAmount || 0);
      fetch(url)
        .then((r) => r.json())
        .then((d) => {
          this.qrLoading = false;
          if (d.ok) {
            this.qrSrc = d.image;
          } else {
            this.qrError = d.msg || 'ไม่สามารถสร้าง QR ได้';
          }
        })
        .catch(() => {
          this.qrLoading = false;
          this.qrError = 'เกิดข้อผิดพลาด';
        });
    },

    init() {
      this.$nextTick(() => {
        this.syncAmountFromParent();
        this.refreshQr();
      });
    },
  }));

  // Favourite toggle — works on any card across the site
  Alpine.data('favBtn', (propertyId) => ({
    pid: parseInt(propertyId, 10),
    faved: false,
    loading: false,
    toggleUrl: <?= json_encode(url('/account/favorites/toggle'), JSON_UNESCAPED_SLASHES) ?>,
    loginUrl:  <?= json_encode(url('/login'), JSON_UNESCAPED_SLASHES) ?>,
    get csrf() {
      return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    },
    async toggle() {
      if (this.loading) return;
      this.loading = true;
      try {
        const body = new URLSearchParams({ property_id: this.pid, _csrf: this.csrf });
        const res  = await fetch(this.toggleUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: body.toString()
        });
        if (res.status === 401) { window.location.href = this.loginUrl; return; }
        if (res.ok) { const data = await res.json(); this.faved = !!data.favorited; }
      } catch (_) {}
      this.loading = false;
    }
  }));
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
document.addEventListener('alpine:initialized', () => { if (window.lucide) lucide.createIcons(); });
</script>
<!-- Deferred third-party scripts — loaded after HTML is parsed -->
<script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script defer src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
