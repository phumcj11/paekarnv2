<?php
use App\Core\Auth;
use App\Core\Database;
use App\Core\I18n;
use App\Core\View;
use App\Models\Setting;
$siteName = Setting::get('site_name', 'แพกาญ.com');
$user     = Auth::user();
$locale   = I18n::locale();

$favCount = 0;
if ($user && Auth::isCustomer()) {
    $cid = Auth::customerId();
    if ($cid) {
        $favCount = (int)(Database::fetch('SELECT COUNT(*) c FROM favorites WHERE customer_id = :c', ['c' => $cid])['c'] ?? 0);
    }
}

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$navSegs = array_values(array_filter(explode('/', trim($uriPath, '/'))));
$isHome  = count($navSegs) === 0 || (count($navSegs) === 1 && ($navSegs[0] === 'paekan_v1' || $navSegs[0] === 'public'));
$heroSubLine = __('hero_subtitle');
$logoTagline = $heroSubLine
  ? (mb_strlen($heroSubLine) > 46 ? mb_substr($heroSubLine, 0, 46) . '…' : $heroSubLine)
  : 'Travel · Voucher · Kanchanaburi';

$raftsActive = str_contains($uriPath, '/rafts');
$availableActive = str_contains($uriPath, '/available-');
$resortsActive = str_contains($uriPath, '/resorts');
$hotelsActive = str_contains($uriPath, '/hotels');
$staysActive = str_contains($uriPath, '/stays');
$poolVillaActive = str_contains($uriPath, '/pool-villas');
$campingActive = str_contains($uriPath, '/camping');
$guestSeekActive = str_contains($uriPath, '/guest-seek');
$reviewsHubActive = str_contains($uriPath, '/reviews');
$placesActive = str_contains($uriPath, '/places') && !preg_match('#/property/#', $uriPath);
$activitiesActive = str_contains($uriPath, '/activities') || str_contains($uriPath, '/activity/checkout');

$navLabel = static function (string $key, string $default): string {
    $value = trim((string)Setting::get($key, ''));
    return $value !== '' ? $value : $default;
};

/** เมนูหลัก — แยกประเภทที่พัก → กิจกรรม/ที่เที่ยว → โปร → content (หาที่พัก = CTA แยก) */
$guestSeekLabel = $navLabel('nav_label_guest_seek', 'หาที่พัก');
$navItems = [
    ['href' => url('/'),            'label' => $navLabel('nav_label_home', 'หน้าแรก'),                 'icon' => 'home',           'active' => $isHome],
    ['href' => url('/rafts'),       'label' => $navLabel('nav_label_rafts', 'แพพัก'),                 'icon' => 'anchor',         'active' => $raftsActive],
    ['href' => url('/available-today'), 'label' => 'ว่างวันนี้',                                         'icon' => 'check-circle-2', 'active' => $availableActive, 'badge' => 'ใหม่'],
    ['href' => url('/resorts'),     'label' => $navLabel('nav_label_resorts', 'รีสอร์ท'),              'icon' => 'trees',          'active' => $resortsActive],
    ['href' => url('/hotels'),      'label' => $navLabel('nav_label_hotels', 'โรงแรม'),               'icon' => 'building-2',     'active' => $hotelsActive],
    ['href' => url('/stays'),       'label' => $navLabel('nav_label_stays', 'โฮมสเตย์ & บ้านพัก'),     'icon' => 'home',           'active' => $staysActive],
    ['href' => url('/pool-villas'), 'label' => $navLabel('nav_label_pool_villa', 'บ้านพูลวิลล่า'),     'icon' => 'waves',          'active' => $poolVillaActive],
    ['href' => url('/camping'),     'label' => $navLabel('nav_label_camping', 'แคมป์'),                'icon' => 'tent',           'active' => $campingActive],
    ['href' => url('/activities'),  'label' => $navLabel('nav_label_activities', 'กิจกรรม'),           'icon' => 'map',            'active' => $activitiesActive],
    ['href' => url('/places'),      'label' => $navLabel('nav_label_places', 'ที่เที่ยว'),             'icon' => 'map-pin',        'active' => $placesActive],
    ['href' => url('/coupons'),     'label' => $navLabel('nav_label_coupons', 'โปรโมชั่น'),            'icon' => 'ticket',         'active' => str_contains($uriPath, '/coupons')],
    ['href' => url('/reviews'),     'label' => $navLabel('nav_label_reviews', 'รีวิว'),                'icon' => 'message-circle', 'active' => $reviewsHubActive],
    ['href' => url('/blog'),        'label' => $navLabel('nav_label_blog', 'บทความ'),                  'icon' => 'newspaper',      'active' => str_contains($uriPath, '/blog')],
];
$guestSeekCtaClass = $guestSeekActive
    ? 'border-forest-300 bg-forest-50 text-forest-800 ring-1 ring-forest-200/80'
    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-slate-300';
?>
<header x-data="{ open:false, userMenu:false }" class="sticky top-0 z-40 w-full bg-white border-b border-slate-200/90 shadow-[0_1px_0_rgba(15,23,42,0.04)]">
  <div class="w-full px-4 sm:px-6 lg:px-8 xl:px-10 2xl:px-12">
    <div class="flex items-center gap-2 sm:gap-3 xl:gap-6 min-h-[56px] lg:min-h-[56px] py-1.5 lg:py-0 w-full min-w-0">

      <!-- ด้านซ้าย: Logo + เมนูหลัก (ชิดกัน — เมนูไม่ถูกดันไปกลางจอ) -->
      <div class="flex min-w-0 flex-1 items-center gap-3 xl:gap-5">
        <a href="<?= url('/') ?>" class="flex items-center gap-2 lg:gap-2 min-w-0 shrink-0 max-w-[60%] sm:max-w-none group">
          <span class="relative block h-10 w-10 lg:h-9 lg:w-9 shrink-0 overflow-hidden rounded-full bg-white shadow-md shadow-primary-900/10 ring-2 ring-white">
            <img src="<?= asset('site-logo.png') ?>" alt="<?= e($siteName) ?>" width="88" height="88" decoding="async"
                 class="absolute inset-0 h-full w-full object-contain p-0.5 transition group-hover:scale-[1.03]">
          </span>
          <div class="leading-tight flex flex-col min-w-0 justify-center">
            <div class="font-heading font-bold text-forest-900 tracking-tight truncate text-[15px] sm:text-[14px] lg:text-[13px]"><?= e($siteName) ?></div>
            <div class="text-[9px] lg:text-[10px] text-slate-500 hidden 2xl:block truncate max-w-[200px] xl:max-w-xs"><?= e($logoTagline) ?></div>
          </div>
        </a>

        <nav class="hidden lg:flex items-center justify-start gap-x-0.5 xl:gap-x-1 2xl:gap-x-1.5 gap-y-1 min-w-0 py-0.5 flex-wrap xl:flex-nowrap" aria-label="เมนูหลัก">
            <?php foreach ($navItems as $item): ?>
              <a href="<?= e($item['href']) ?>"
                 class="relative px-2 py-2 rounded-lg text-[11px] xl:text-[12px] 2xl:text-[13px] whitespace-nowrap shrink-0 transition <?= $item['active'] ? 'text-forest-700 font-bold bg-forest-50/90 ring-1 ring-forest-200/70' : 'text-slate-800 font-semibold hover:text-forest-800 hover:bg-slate-50' ?>">
                <?= e($item['label']) ?>
                <?php if (!empty($item['badge'])): ?>
                <span class="absolute -top-0.5 -right-1 px-1 py-px text-[8px] font-bold bg-emerald-500 text-white rounded-full leading-none"><?= e($item['badge']) ?></span>
                <?php endif; ?>
              </a>
            <?php endforeach; ?>
        </nav>
      </div>

      <!-- เดสก์ท็อป: หาที่พัก CTA / ภาษา / คูปอง / ไอคอน / โปรไฟล์ — ชิดขวา ขนาดย่อ -->
      <div class="hidden lg:flex items-center gap-1 xl:gap-1.5 shrink-0 ml-auto">
        <a href="<?= url('/guest-seek') ?>"
           class="hidden xl:inline-flex items-center gap-1 px-2 py-1.5 rounded-lg border text-[11px] font-semibold transition shadow-sm <?= $guestSeekCtaClass ?>"
           title="ให้ทีมช่วยหาที่พักตามงบและโซน">
          <i data-lucide="search" class="w-[15px] h-[15px] shrink-0"></i>
          <span class="whitespace-nowrap"><?= e($guestSeekLabel) ?></span>
        </a>
        <a href="<?= url('/guest-seek') ?>"
           class="xl:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg border shadow-sm transition <?= $guestSeekCtaClass ?>"
           title="<?= e($guestSeekLabel) ?>">
          <i data-lucide="search" class="w-[17px] h-[17px]"></i>
        </a>
        <div class="flex items-center rounded-lg bg-slate-100 p-px ring-1 ring-slate-200/80">
          <a href="?lang=th" class="px-1.5 py-1 rounded-md text-[10px] font-bold <?= $locale === 'th' ? 'bg-white text-forest-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' ?>">TH</a>
          <a href="?lang=en" class="px-1.5 py-1 rounded-md text-[10px] font-bold <?= $locale === 'en' ? 'bg-white text-forest-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' ?>">EN</a>
        </div>

        <?php if ($user && Auth::isCustomer()): ?>
          <a href="<?= url('/account/coupons') ?>" class="inline-flex items-center justify-center gap-1 px-2 py-1.5 xl:px-2.5 rounded-lg bg-forest-800 hover:bg-forest-900 text-white text-[11px] xl:text-xs font-bold shadow-sm shadow-forest-950/15 transition ring-1 ring-forest-700/40"
             title="คูปองของฉัน">
            <i data-lucide="gift" class="w-[15px] h-[15px] shrink-0"></i>
            <span class="hidden 2xl:inline whitespace-nowrap">คูปองของฉัน</span>
            <span class="hidden xl:inline 2xl:hidden">คูปอง</span>
          </a>
        <?php else: ?>
          <a href="<?= url('/coupons/buy') ?>" class="inline-flex items-center justify-center gap-1 px-2 py-1.5 xl:px-2.5 rounded-lg bg-forest-800 hover:bg-forest-900 text-white text-[11px] xl:text-xs font-bold shadow-sm shadow-forest-950/15 transition ring-1 ring-forest-700/40"
             title="ซื้อคูปอง">
            <i data-lucide="gift" class="w-[15px] h-[15px] shrink-0"></i>
            <span class="hidden 2xl:inline whitespace-nowrap">ซื้อคูปอง</span>
            <span class="hidden xl:inline 2xl:hidden">ซื้อ</span>
          </a>
        <?php endif; ?>

        <a href="<?= url(Auth::check() ? '/account/favorites' : '/login') ?>" class="relative inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 hover:text-rose-600 transition shadow-sm" title="ถูกใจ">
          <i data-lucide="heart" class="w-[17px] h-[17px]"></i>
          <?php if ($favCount > 0): ?>
            <span class="absolute -top-px -right-px min-w-[15px] h-[15px] px-0.5 grid place-items-center bg-rose-500 text-white text-[9px] font-bold rounded-full"><?= $favCount > 9 ? '9+' : $favCount ?></span>
          <?php endif; ?>
        </a>

        <a href="<?= url('/compare') ?>" class="relative inline-flex items-center justify-center w-9 h-9 rounded-lg border border-teal-100 bg-teal-50 hover:bg-teal-100 text-teal-700 transition shadow-sm" title="เทียบแพ">
          <i data-lucide="scale" class="w-[17px] h-[17px]"></i>
          <span x-data x-show="$store.compare && $store.compare.items.length > 0" x-cloak x-text="$store.compare.items.length > 9 ? '9+' : $store.compare.items.length" class="absolute -top-px -right-px min-w-[15px] h-[15px] px-0.5 grid place-items-center bg-teal-600 text-white text-[9px] font-bold rounded-full"></span>
        </a>

        <?php if ($user): ?>
          <?php View::partial('partials/bell'); ?>
          <div class="relative" @click.away="userMenu=false">
            <button @click="userMenu=!userMenu" type="button" class="flex items-center gap-1 pl-1 pr-1.5 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 shadow-sm transition max-w-[200px]"
                    title="สวัสดี, คุณ <?= e(trim($user['name'])) ?>">
              <div class="w-8 h-8 rounded-full bg-forest-100 text-forest-800 grid place-items-center font-bold text-xs ring-2 ring-white shadow-sm shrink-0"><?= mb_substr($user['name'], 0, 1) ?></div>
              <div class="hidden 2xl:block text-left min-w-0">
                <div class="text-[12px] font-semibold text-slate-800 truncate max-w-[10rem] leading-tight">สวัสดี, คุณ <?= e(mb_substr(trim($user['name']), 0, 16)) ?></div>
              </div>
              <i data-lucide="chevron-down" class="w-[14px] h-[14px] text-slate-400 shrink-0"></i>
            </button>
            <div x-show="userMenu" x-transition x-cloak class="absolute right-0 mt-2 w-56 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden">
              <?php if ($user['role'] === 'admin'): ?>
                <a href="<?= url('/admin') ?>" class="flex items-center gap-2 px-4 py-2.5 hover:bg-slate-50 text-sm"><i data-lucide="layout-dashboard" class="w-4 h-4"></i> Admin</a>
              <?php endif; ?>
              <?php if (in_array($user['role'], ['owner','admin'])): ?>
                <a href="<?= url('/owner') ?>" class="flex items-center gap-2 px-4 py-2.5 hover:bg-slate-50 text-sm"><i data-lucide="briefcase" class="w-4 h-4"></i> พาร์ทเนอร์</a>
              <?php endif; ?>
              <a href="<?= url('/account') ?>" class="flex items-center gap-2 px-4 py-2.5 hover:bg-slate-50 text-sm"><i data-lucide="user" class="w-4 h-4"></i> บัญชีของฉัน</a>
              <a href="<?= url('/account/bookings') ?>" class="flex items-center gap-2 px-4 py-2.5 hover:bg-slate-50 text-sm"><i data-lucide="calendar-check" class="w-4 h-4"></i> การจอง</a>
              <a href="<?= url('/account/coupons') ?>" class="flex items-center gap-2 px-4 py-2.5 hover:bg-slate-50 text-sm"><i data-lucide="ticket" class="w-4 h-4"></i> คูปองของฉัน</a>
              <form action="<?= url('/logout') ?>" method="post" class="border-t border-slate-100"><?= csrf() ?>
                <button type="submit" class="w-full text-left flex items-center gap-2 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50"><i data-lucide="log-out" class="w-4 h-4"></i> <?= __('logout') ?></button>
              </form>
            </div>
          </div>
        <?php else: ?>
          <a href="<?= url('/owner/login') ?>" class="inline-flex items-center gap-1 px-2 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:ring-1 hover:ring-slate-200 transition" title="เจ้าของแพ">
            <i data-lucide="briefcase" class="w-4 h-4 text-accent-600"></i><span class="hidden 2xl:inline">พาร์ทเนอร์</span>
          </a>
          <a href="<?= url('/login') ?>" class="px-2 py-1.5 text-xs font-semibold text-slate-600 hover:text-forest-800"><?= __('login') ?></a>
          <a href="<?= url('/register') ?>" class="px-2.5 py-1.5 rounded-lg bg-forest-800 hover:bg-forest-900 text-white text-xs font-bold shadow-sm shadow-forest-950/15 ring-1 ring-forest-700/40"><?= __('register') ?></a>
        <?php endif; ?>
      </div>

      <!-- Mobile -->
      <div class="flex lg:hidden items-center gap-2 shrink-0">
        <a href="<?= url('/coupons/buy') ?>" class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-accent-500 text-white shadow-md" aria-label="<?= e(__('buy_coupon')) ?>">
          <i data-lucide="gift" class="w-[18px] h-[18px]"></i>
        </a>
        <?php if ($user): ?>
          <?php View::partial('partials/bell'); ?>
        <?php endif; ?>
        <button type="button" @click="open=!open" class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 ring-1 ring-slate-200/80" aria-label="เมนู">
          <i data-lucide="menu" class="w-6 h-6" x-show="!open"></i>
          <i data-lucide="x" class="w-6 h-6" x-show="open" x-cloak></i>
        </button>
      </div>
    </div>

    <!-- Mobile drawer — เมนูหลัก -->
    <div x-show="open" x-transition x-cloak class="lg:hidden pb-4 border-t border-slate-100 mt-1 pt-3">
      <a href="<?= url('/guest-seek') ?>"
         class="flex items-center gap-3 px-4 py-3 mb-3 rounded-xl border-2 border-dashed transition <?= $guestSeekActive ? 'border-forest-300 bg-forest-50' : 'border-accent-200 bg-accent-50/80 hover:bg-accent-50' ?>">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-accent-700 shadow-sm ring-1 ring-accent-200/70">
          <i data-lucide="search" class="w-[18px] h-[18px]"></i>
        </span>
        <span class="min-w-0 text-left">
          <span class="block font-bold text-slate-800 text-sm">ยังไม่เจอที่พัก?</span>
          <span class="block text-xs text-slate-600 mt-0.5">ให้ทีมช่วยหา — <?= e($guestSeekLabel) ?></span>
        </span>
        <i data-lucide="chevron-right" class="w-5 h-5 text-slate-400 shrink-0 ml-auto"></i>
      </a>
      <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400 px-0.5 mb-2">เมนูหลัก</p>
      <div class="grid grid-cols-2 gap-2">
        <?php foreach ($navItems as $item): ?>
          <a href="<?= e($item['href']) ?>" class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-forest-50 ring-1 ring-slate-200/80 hover:ring-forest-200/80 transition min-h-[3.25rem]">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-forest-700 shadow-sm ring-1 ring-slate-200/70">
              <i data-lucide="<?= e($item['icon']) ?>" class="w-[18px] h-[18px]"></i>
            </span>
            <span class="font-semibold text-slate-800 text-[13px] leading-snug text-left <?= $item['active'] ? 'text-forest-800' : '' ?>"><?= e($item['label']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
      <div class="flex items-center justify-center gap-2 mt-4 p-1 rounded-xl bg-slate-100">
        <a href="?lang=th" class="flex-1 text-center py-2 rounded-lg text-sm font-bold <?= $locale==='th'?'bg-white shadow-sm text-primary-800':'text-slate-500' ?>">ไทย TH</a>
        <a href="?lang=en" class="flex-1 text-center py-2 rounded-lg text-sm font-bold <?= $locale==='en'?'bg-white shadow-sm text-primary-800':'text-slate-500' ?>">English EN</a>
      </div>
      <div class="mt-3 flex flex-col gap-2">
        <?php if ($user): ?>
          <a href="<?= url('/account') ?>" class="flex items-center gap-3 px-3 py-3 rounded-xl bg-white ring-1 ring-slate-200">
            <i data-lucide="user" class="w-5 h-5 text-primary-600"></i><span class="font-semibold"><?= e($user['name']) ?></span>
          </a>
          <?php if ($user['role']==='admin'): ?><a href="<?= url('/admin') ?>" class="px-3 py-2 text-sm font-semibold text-slate-700">Admin</a><?php endif; ?>
          <?php if (in_array($user['role'],['owner','admin'])): ?><a href="<?= url('/owner') ?>" class="px-3 py-2 text-sm font-semibold text-slate-700">พาร์ทเนอร์</a><?php endif; ?>
          <form action="<?= url('/logout') ?>" method="post"><?= csrf() ?>
            <button type="submit" class="w-full py-3 rounded-xl bg-rose-50 text-rose-700 font-bold text-sm flex items-center justify-center gap-2"><i data-lucide="log-out" class="w-4 h-4"></i> <?= __('logout') ?></button>
          </form>
        <?php else: ?>
          <div class="grid grid-cols-2 gap-2">
            <a href="<?= url('/login') ?>" class="py-3 rounded-xl bg-white ring-1 ring-slate-200 text-center font-bold text-slate-700"><?= __('login') ?></a>
            <a href="<?= url('/register') ?>" class="py-3 rounded-xl bg-primary-700 text-white text-center font-bold"><?= __('register') ?></a>
          </div>
          <a href="<?= url('/owner/login') ?>" class="flex items-center justify-center gap-2 py-3 rounded-xl border-2 border-dashed border-accent-200 text-accent-700 font-bold text-sm"><i data-lucide="briefcase" class="w-4 h-4"></i> เจ้าของแพ — พาร์ทเนอร์</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</header>
