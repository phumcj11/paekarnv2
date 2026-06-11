<?php
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Services\OwnerMembership;
$user = Auth::user();
$flashSuccess = Session::flash('success');
$flashError   = Session::flash('error');
Session::consumeOld();
if (!function_exists('ow_active')) {
  function ow_active(string $needle, string $cls = 'ow-sidebar-link--active'): string {
    $cur = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if ($needle === '/owner/dashboard' && ($cur === '/owner' || $cur === '/owner/dashboard')) return $cls;
    return str_contains($cur, $needle) ? $cls : '';
  }
}
if (!function_exists('ow_sidebar_units_active')) {
  function ow_sidebar_units_active(string $cls = 'ow-sidebar-link--active'): string {
    $cur = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (preg_match('#^/owner/units/?$#', $cur)) return $cls;
    if (preg_match('#^/owner/properties/[0-9]+/units#', $cur)) return $cls;
    return '';
  }
}
if (!function_exists('ow_nav_active')) {
  function ow_nav_active(string $needle, string $cls = 'ow-bottom-nav__item--active'): string {
    $cur = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if ($needle === '/owner/dashboard' && ($cur === '/owner' || $cur === '/owner/dashboard')) return $cls;
    return str_contains($cur, $needle) ? $cls : '';
  }
}
$ownerStatus = null;
$ownerTier = 'none';
$ownerMembershipActive = false;
$firstPropertyId = null;
$ownerId = Auth::ownerId();
if ($user && $user['role'] === 'owner') {
  $row = Database::fetch(
    "SELECT partner_status, membership_tier, membership_expires_at, membership_grace_until FROM owners WHERE user_id = :u",
    ['u' => $user['id']]
  );
  $ownerStatus = $row['partner_status'] ?? null;
  $ownerTier = $row['membership_tier'] ?? 'none';
  $ownerMembershipActive = $row ? OwnerMembership::hasActiveBenefits($row) : false;
  if ($ownerId) {
    $fp = Database::fetch("SELECT id FROM properties WHERE owner_id = :o ORDER BY id ASC LIMIT 1", ['o' => $ownerId]);
    $firstPropertyId = $fp['id'] ?? null;
  }
}
$chatUrl = $firstPropertyId ? url('/owner/properties/' . $firstPropertyId . '/line') : url('/owner/properties');
$calendarUrl = $firstPropertyId
  ? url('/owner/properties/' . $firstPropertyId . '/availability')
  : url('/owner/properties');
?><!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($meta_title ?? $page_title ?? 'พอร์ทัลเจ้าของแพ — แพกาญ.com') ?></title>
<link rel="icon" href="<?= asset('site-logo.png') ?>" type="image/png">
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=Kanit:wght@400;500;600;700&family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://unpkg.com/lucide@latest"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>body{font-family:'Kanit','Be Vietnam Pro','Sarabun',system-ui;-webkit-font-smoothing:antialiased}[x-cloak]{display:none}</style>
</head>
<body class="ow-shell" x-data="{sidebar:false}">

<!-- Sidebar (desktop) -->
<aside class="ow-sidebar"
       :class="sidebar?'translate-x-0':'-translate-x-full lg:translate-x-0'">
  <div class="px-5 py-5 border-b border-slate-100 flex items-center gap-3">
    <div class="w-10 h-10 rounded-2xl bg-core-600 grid place-items-center text-white shadow-cloud">
      <i data-lucide="briefcase" class="w-5 h-5"></i>
    </div>
    <div>
      <div class="font-bold text-slate-800 leading-tight">พอร์ทัลเจ้าของแพ</div>
      <div class="text-[10px] text-slate-500">แพกาญ · จัดการที่พักแบบง่าย</div>
    </div>
  </div>
  <nav class="flex-1 overflow-y-auto p-4 space-y-1 text-sm">
    <?php
    $items = [
      ['/owner/dashboard',   'layout-dashboard', 'ภาพรวม', null],
      ['/owner/properties',  'hotel',            'ที่พักของฉัน', null],
      ['/owner/units',       'bed-double',       'ห้อง / ยูนิต', 'units_hub'],
      ['/owner/bookings',    'calendar-check',   'การจอง', null],
      ['/owner/analytics',   'bar-chart-2',      'Analytics', null],
      ['/owner/line-contacts', 'message-circle', 'แชท LINE', null],
      ['/owner/automation',  'zap',              'Automation', null],
      ['/owner/content-plans', 'megaphone',      'การตลาด', null],
      ['/owner/coupons/verify','ticket',         'ตรวจคูปอง', null],
      ['/owner/coupons/scan', 'camera',          'สแกนคูปอง', null],
      ['/owner/membership',  'award',            'สมาชิกเจ้าของแพ', null],
      ['/owner/profile',     'user-cog',         'โปรไฟล์ & ธนาคาร', null],
    ];
    foreach ($items as $it):
      $href = $it[0];
      $kind = $it[3] ?? null;
      $navCls = ($kind === 'units_hub') ? ow_sidebar_units_active() : ow_active($href);
      ?>
      <a href="<?= url($href) ?>" class="ow-sidebar-link <?= $navCls ?>">
        <i data-lucide="<?= $it[1] ?>" class="w-4 h-4 shrink-0"></i> <?= $it[2] ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="p-4 border-t border-slate-100 space-y-1">
    <a href="<?= url('/') ?>" class="ow-sidebar-link text-xs !py-2">
      <i data-lucide="external-link" class="w-3.5 h-3.5"></i> เปิดหน้าเว็บ
    </a>
    <form action="<?= url('/logout') ?>" method="post"><?= csrf() ?>
      <button type="submit" class="ow-sidebar-link text-xs !py-2 text-rose-600 hover:bg-rose-50 hover:text-rose-700 w-full">
        <i data-lucide="log-out" class="w-3.5 h-3.5"></i> ออกจากระบบ
      </button>
    </form>
  </div>
</aside>

<div x-show="sidebar" x-cloak @click="sidebar=false" class="fixed inset-0 z-30 bg-slate-900/30 lg:hidden"></div>

<div class="lg:ml-64 min-h-screen flex flex-col pb-[4.5rem] lg:pb-0">
  <header class="ow-header">
    <div class="px-4 sm:px-6 h-14 sm:h-16 flex items-center justify-between max-w-[1440px] mx-auto w-full">
      <div class="flex items-center gap-3 min-w-0">
        <button type="button" @click="sidebar=!sidebar" class="lg:hidden p-2 -ml-1 rounded-xl hover:bg-slate-100" aria-label="เมนู">
          <i data-lucide="menu" class="w-5 h-5 text-slate-700"></i>
        </button>
        <h1 class="font-bold text-slate-800 truncate"><?= e($page_title ?? 'แดชบอร์ด') ?></h1>
      </div>
      <div class="flex items-center gap-2 sm:gap-3 shrink-0">
        <?php if ($ownerStatus === 'pending'): ?>
          <span class="hidden md:inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 text-amber-700 rounded-full text-xs font-semibold">
            <span class="ow-status-dot"></span> รออนุมัติ
          </span>
        <?php elseif ($ownerStatus === 'active'): ?>
          <span class="hidden md:inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-full text-xs font-semibold">
            <span class="ow-status-dot"></span> พันธมิตรใช้งานได้
          </span>
        <?php endif; ?>
        <?php if ($user['role'] === 'owner' && $ownerTier === 'vip' && $ownerMembershipActive): ?>
          <span class="hidden sm:inline-flex items-center gap-1 px-2 py-1 bg-amber-50 text-amber-800 rounded-full text-[10px] font-bold">
            <i data-lucide="crown" class="w-3 h-3"></i> VIP
          </span>
        <?php endif; ?>
        <?php \App\Core\View::partial('partials/bell'); ?>
        <div class="w-9 h-9 rounded-full bg-core-100 text-core-700 grid place-items-center font-bold text-sm ring-2 ring-white shadow-sm">
          <?= mb_substr($user['name'], 0, 1) ?>
        </div>
      </div>
    </div>
  </header>

  <?php if ($flashSuccess): ?>
    <div class="mx-4 sm:mx-6 mt-4 max-w-[1440px] lg:mx-auto w-full px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-center gap-2 text-sm">
      <i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i><?= e($flashSuccess) ?>
    </div>
  <?php endif; ?>
  <?php if ($flashError): ?>
    <div class="mx-4 sm:mx-6 mt-4 max-w-[1440px] lg:mx-auto w-full px-4 py-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl flex items-center gap-2 text-sm">
      <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i><?= e($flashError) ?>
    </div>
  <?php endif; ?>

  <main class="flex-1 min-h-0 min-w-0 p-4 sm:p-6 max-w-[1440px] mx-auto w-full"><?= $content ?? '' ?></main>

  <footer class="px-4 sm:px-6 py-4 text-xs text-slate-500 border-t border-slate-200 bg-white hidden lg:block">
    <div class="max-w-[1440px] mx-auto flex flex-wrap items-center justify-between gap-2">
      <span>© <?= date('Y') ?> paekarn.com — Owner Portal</span>
      <div class="flex gap-4">
        <a href="<?= url('/privacy') ?>" class="hover:text-core-600">นโยบายความเป็นส่วนตัว</a>
        <a href="<?= url('/contact') ?>" class="hover:text-core-600">ติดต่อเรา</a>
      </div>
    </div>
  </footer>
</div>

<!-- Bottom nav (mobile) -->
<nav class="ow-bottom-nav" aria-label="เมนูหลัก">
  <div class="grid grid-cols-5 max-w-lg mx-auto">
    <a href="<?= url('/owner/dashboard') ?>" class="ow-bottom-nav__item <?= ow_nav_active('/owner/dashboard') ?>">
      <i data-lucide="home" class="w-5 h-5"></i><span>หน้าแรก</span>
    </a>
    <a href="<?= url('/owner/bookings') ?>" class="ow-bottom-nav__item <?= ow_nav_active('/owner/bookings') ?>">
      <i data-lucide="calendar-check" class="w-5 h-5"></i><span>การจอง</span>
    </a>
    <a href="<?= e($calendarUrl) ?>" class="ow-bottom-nav__item <?= ow_nav_active('/availability') ?>">
      <i data-lucide="calendar-days" class="w-5 h-5"></i><span>ปฏิทิน</span>
    </a>
    <a href="<?= url('/owner/line-contacts' . ($firstPropertyId ? '?property_id=' . $firstPropertyId : '')) ?>" class="ow-bottom-nav__item <?= ow_nav_active('/owner/line-contacts') ?>">
      <i data-lucide="message-circle" class="w-5 h-5"></i><span>LINE CRM</span>
    </a>
    <a href="<?= url('/owner/profile') ?>" class="ow-bottom-nav__item <?= ow_nav_active('/owner/profile') ?>">
      <i data-lucide="user" class="w-5 h-5"></i><span>โปรไฟล์</span>
    </a>
  </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>document.addEventListener('DOMContentLoaded',()=>lucide.createIcons());document.addEventListener('alpine:initialized',()=>lucide.createIcons());</script>
</body>
</html>
