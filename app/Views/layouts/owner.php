<?php
use App\Core\Auth;
use App\Core\Session;
use App\Services\OwnerMembership;
$user = Auth::user();
$flashSuccess = Session::flash('success');
$flashError   = Session::flash('error');
Session::consumeOld();
if (!function_exists('ow_active')) {
  function ow_active(string $needle, string $cls = 'bg-accent-600 text-white shadow-soft'): string {
    $cur = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return str_contains($cur, $needle) ? $cls : 'text-slate-300 hover:bg-white/5';
  }
}
if (!function_exists('ow_sidebar_units_active')) {
  /** ไฮไลต์เมนู «ห้อง / ยูนิต» ทั้งหน้า hub และหน้า units ใต้ property */
  function ow_sidebar_units_active(string $cls = 'bg-accent-600 text-white shadow-soft'): string {
    $cur = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (preg_match('#^/owner/units/?$#', $cur)) {
      return $cls;
    }
    if (preg_match('#^/owner/properties/[0-9]+/units#', $cur)) {
      return $cls;
    }

    return 'text-slate-300 hover:bg-white/5';
  }
}
// owner status + membership (badge header / sidebar context)
$ownerStatus = null;
$ownerTier = 'none';
$ownerMembershipActive = false;
if ($user && $user['role'] === 'owner') {
  $row = \App\Core\Database::fetch(
    "SELECT partner_status, membership_tier, membership_expires_at, membership_grace_until FROM owners WHERE user_id = :u",
    ['u' => $user['id']]
  );
  $ownerStatus = $row['partner_status'] ?? null;
  $ownerTier = $row['membership_tier'] ?? 'none';
  $ownerMembershipActive = $row ? OwnerMembership::hasActiveBenefits($row) : false;
}
?><!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($meta_title ?? $page_title ?? 'พอร์ทัลเจ้าของแพ — แพกาญ.com') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script src="https://unpkg.com/lucide@latest"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>body{font-family:'Sarabun','Inter',system-ui;-webkit-font-smoothing:antialiased}[x-cloak]{display:none}</style>
</head>
<body class="bg-slate-100 text-slate-800" x-data="{sidebar:false}">

<!-- Sidebar -->
<aside class="fixed inset-y-0 left-0 z-40 w-64 bg-gradient-to-b from-primary-700 via-primary-800 to-slate-900 text-slate-300 flex flex-col transition-transform"
       :class="sidebar?'translate-x-0':'-translate-x-full lg:translate-x-0'">
  <div class="px-5 py-4 border-b border-white/10 flex items-center gap-3">
    <div class="w-9 h-9 rounded-xl bg-accent-500 grid place-items-center text-white"><i data-lucide="briefcase" class="w-5 h-5"></i></div>
    <div>
      <div class="text-white font-bold">พอร์ทัลเจ้าของแพ</div>
      <div class="text-[10px] text-white/50">แพกาญ · จัดการที่พักแบบง่าย</div>
    </div>
  </div>
  <nav class="flex-1 overflow-y-auto p-3 space-y-1 text-sm">
    <?php
    $items = [
      ['/owner/dashboard',   'gauge',           'ภาพรวม', null],
      ['/owner/properties',  'hotel',           'ที่พักของฉัน', null],
      ['/owner/units',       'bed-double',      'ห้อง / ยูนิต', 'units_hub'],
      ['/owner/bookings',    'calendar-check',  'การจอง', null],
      ['/owner/content-plans', 'calendar-days',  'ปฏิทินโพสต์', null],
      ['/owner/coupons/verify','ticket',        'ตรวจคูปอง', null],
      ['/owner/coupons/scan', 'camera',         'สแกนคูปอง', null],
      ['/owner/membership',  'award',           'สมาชิกเจ้าของแพ', null],
      ['/owner/profile',     'user-cog',        'โปรไฟล์ & ธนาคาร', null],
    ];
    foreach ($items as $it):
      $href = $it[0];
      $kind = $it[3] ?? null;
      $navCls = ($kind === 'units_hub') ? ow_sidebar_units_active() : ow_active($href);
      ?>
      <a href="<?= url($href) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?= $navCls ?>">
        <i data-lucide="<?= $it[1] ?>" class="w-4 h-4"></i> <?= $it[2] ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="p-3 border-t border-white/10">
    <a href="<?= url('/') ?>" class="flex items-center gap-2 px-3 py-2 text-xs text-slate-400 hover:text-white">
      <i data-lucide="external-link" class="w-3.5 h-3.5"></i> เปิดหน้าเว็บ
    </a>
    <form action="<?= url('/logout') ?>" method="post"><?= csrf() ?>
      <button class="w-full mt-1 flex items-center gap-2 px-3 py-2 text-rose-300 hover:bg-rose-600/20 rounded-lg text-xs">
        <i data-lucide="log-out" class="w-3.5 h-3.5"></i> ออกจากระบบ
      </button>
    </form>
  </div>
</aside>

<div x-show="sidebar" x-cloak @click="sidebar=false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

<div class="lg:ml-64 min-h-screen flex flex-col">
  <header class="bg-white border-b border-slate-200 sticky top-0 z-20">
    <div class="px-4 sm:px-6 h-16 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <button @click="sidebar=!sidebar" class="lg:hidden p-2 rounded-lg hover:bg-slate-100"><i data-lucide="menu" class="w-5 h-5"></i></button>
        <h1 class="font-bold text-slate-700"><?= e($page_title ?? 'แดชบอร์ด') ?></h1>
      </div>
      <div class="flex items-center gap-3">
        <?php if ($ownerStatus === 'pending'): ?>
          <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-xs font-semibold">
            <i data-lucide="clock" class="w-3 h-3"></i> รออนุมัติจาก Admin
          </span>
        <?php elseif ($ownerStatus === 'active'): ?>
          <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-xs font-semibold">
            <i data-lucide="check-circle" class="w-3 h-3"></i> พันธมิตรที่ใช้งานได้
          </span>
        <?php endif; ?>
        <?php if ($user['role'] === 'owner' && $ownerTier === 'vip' && $ownerMembershipActive): ?>
          <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-50 text-amber-800 border border-amber-300 rounded-full text-xs font-semibold">
            <i data-lucide="crown" class="w-3 h-3"></i> VIP
          </span>
        <?php elseif ($user['role'] === 'owner' && $ownerTier === 'standard' && $ownerMembershipActive): ?>
          <span class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 bg-sky-50 text-sky-800 border border-sky-200 rounded-full text-xs font-semibold">
            <i data-lucide="badge-check" class="w-3 h-3"></i> สมาชิก
          </span>
        <?php endif; ?>
        <?php \App\Core\View::partial('partials/bell'); ?>
        <div class="flex items-center gap-2">
          <div class="w-9 h-9 rounded-full bg-accent-100 text-accent-700 grid place-items-center font-bold text-sm"><?= mb_substr($user['name'],0,1) ?></div>
          <div class="hidden sm:block">
            <div class="text-sm font-semibold leading-tight"><?= e($user['name']) ?></div>
            <div class="text-[11px] text-slate-500">เจ้าของที่พัก</div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <?php if ($flashSuccess): ?>
    <div class="mx-4 sm:mx-6 mt-4 px-4 py-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-center gap-2"><i data-lucide="check-circle" class="w-4 h-4"></i><?= e($flashSuccess) ?></div>
  <?php endif; ?>
  <?php if ($flashError): ?>
    <div class="mx-4 sm:mx-6 mt-4 px-4 py-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl flex items-center gap-2"><i data-lucide="alert-circle" class="w-4 h-4"></i><?= e($flashError) ?></div>
  <?php endif; ?>

  <main class="flex-1 min-h-0 min-w-0 p-4 sm:p-6"><?= $content ?? '' ?></main>

  <footer class="px-6 py-4 text-xs text-slate-500 border-t border-slate-200 bg-white">
    © <?= date('Y') ?> แพกาญ.com — Owner Portal
  </footer>
</div>

<script>document.addEventListener('DOMContentLoaded',()=>lucide.createIcons());document.addEventListener('alpine:initialized',()=>lucide.createIcons());</script>
</body>
</html>
