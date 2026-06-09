<?php
use App\Core\Auth;
use App\Core\Session;
$user = Auth::user();
$flashSuccess = Session::flash('success');
$flashError   = Session::flash('error');
Session::consumeOld();
$cur = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$sidebarProviderPending = class_exists(\App\Models\ActivityProvider::class)
    ? \App\Models\ActivityProvider::pendingCount() : 0;
$sidebarProductPending = class_exists(\App\Models\ActivityProduct::class)
    ? \App\Models\ActivityProduct::pendingReviewCount() : 0;
if (!function_exists('ad_active')) {
  function ad_active(string $needle, string $cls = 'bg-primary-700 text-white shadow-soft'): string {
    $cur = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return str_contains($cur, $needle) ? $cls : 'text-slate-300 hover:bg-white/5';
  }
}
?><!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($meta_title ?? 'Admin — แพกาญ.com') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<script src="https://unpkg.com/lucide@latest"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>body{font-family:'Sarabun','Inter',system-ui;-webkit-font-smoothing:antialiased}[x-cloak]{display:none!important}</style>
</head>
<body class="bg-slate-100 text-slate-800" x-data="{sidebar:false}">

<!-- Sidebar -->
<aside class="fixed inset-y-0 left-0 z-40 w-64 bg-primary-800 text-slate-300 flex flex-col transition-transform"
       :class="sidebar?'translate-x-0':'-translate-x-full lg:translate-x-0'">
  <div class="px-5 py-4 border-b border-white/10 flex items-center gap-3">
    <div class="w-9 h-9 rounded-xl bg-accent-500 grid place-items-center text-white"><i data-lucide="anchor" class="w-5 h-5"></i></div>
    <div>
      <div class="text-white font-bold">แพกาญ Admin</div>
      <div class="text-[10px] text-white/50">Backoffice v1.0</div>
    </div>
  </div>
  <nav class="flex-1 overflow-y-auto p-3 space-y-1 text-sm">
    <?php
    $items = [
      ['/admin/dashboard',  'gauge',           'Dashboard'],
      ['/admin/analytics',    'bar-chart-3',    'Analytics'],
      ['/admin/properties', 'hotel',           'ที่พัก'],
      ['/admin/bookings',   'calendar-check',  'การจอง'],
      ['/admin/coupons',    'ticket',          'คูปอง'],
      ['/admin/coupon-campaigns', 'tags',      'แคมเปญคูปอง'],
      ['/admin/zone-ads',   'signpost',        'โฆษณาโซน'],
      ['/admin/audit-logs', 'scroll-text',     'Audit log'],
      ['/admin/membership/orders', 'crown',    'คำสั่งซื้อสมาชิก'],
      ['/admin/membership/plans', 'package',   'แพ็กเกจสมาชิก'],
      ['/admin/owners',     'briefcase',       'เจ้าของแพ'],
      ['/admin/customers',  'users',           'ลูกค้า'],
      ['/admin/reviews',    'message-circle',  'รีวิว'],
      ['/admin/review-videos', 'video',      'วิดีโอแนะนำ'],
      ['/admin/review-facebook-posts', 'facebook', 'โพสต์ Facebook'],
      ['/admin/visitor-places', 'map-pin',   'ที่เที่ยว / POI'],
      ['/admin/activity-providers', 'handshake', 'ผู้ให้บริการกิจกรรม'],
      ['/admin/activity-products', 'map', 'กิจกรรม / บริการ'],
      ['/admin/activity-featured', 'star', 'Featured กิจกรรม'],
      ['/admin/activity-orders', 'ticket-check', 'คำสั่งซื้อกิจกรรม'],
      ['/admin/zones', 'layers',       'โซนที่พัก'],
      ['/admin/leads',      'sparkles',        'CRM / Leads'],
      ['/admin/blog',       'newspaper',       'บล็อก'],
      ['/admin/banners',    'layout-grid',     'Banner หน้าเว็บ'],
      ['/admin/promotions', 'megaphone',       'การตลาด & โปร'],
      ['__divider__', '', 'Phase 3'],
      ['/admin/automation', 'workflow',        'Automation'],
      ['/admin/ai',         'bot',             'AI Settings'],
      ['/admin/ai/kb',      'book-open',       'AI Knowledge Base'],
      ['/admin/ai/chats',   'message-square',  'AI Chat History'],
      ['/admin/line',       'message-circle',  'LINE OA'],
      ['__divider__', '', ''],
      ['/admin/settings',   'settings',        'การตั้งค่า'],
      ['/admin/tools/images', 'image-down',    'Optimize รูป WebP'],
    ];
    foreach ($items as $it):
      if ($it[0] === '__divider__'):
        if ($it[2] !== ''): ?>
          <div class="px-3 pt-3 pb-1 text-[10px] uppercase tracking-wider text-white/40"><?= e($it[2]) ?></div>
        <?php else: ?>
          <hr class="my-1 border-white/10">
        <?php endif;
        continue;
      endif; ?>
      <a href="<?= url($it[0]) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?= ad_active($it[0]) ?>">
        <i data-lucide="<?= $it[1] ?>" class="w-4 h-4"></i>
        <span class="flex-1"><?= $it[2] ?></span>
        <?php if ($it[0] === '/admin/activity-providers' && $sidebarProviderPending > 0): ?>
          <span class="min-w-[1.25rem] h-5 px-1.5 rounded-full bg-amber-500 text-white text-[10px] font-bold grid place-items-center"><?= $sidebarProviderPending ?></span>
        <?php elseif ($it[0] === '/admin/activity-products' && $sidebarProductPending > 0): ?>
          <span class="min-w-[1.25rem] h-5 px-1.5 rounded-full bg-sky-500 text-white text-[10px] font-bold grid place-items-center"><?= $sidebarProductPending ?></span>
        <?php endif; ?>
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

<!-- Backdrop mobile -->
<div x-show="sidebar" x-cloak @click="sidebar=false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

<!-- Main -->
<div class="lg:ml-64 min-h-screen flex flex-col">
  <header class="bg-white border-b border-slate-200 sticky top-0 z-20">
    <div class="px-4 sm:px-6 h-16 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <button @click="sidebar=!sidebar" class="lg:hidden p-2 rounded-lg hover:bg-slate-100"><i data-lucide="menu" class="w-5 h-5"></i></button>
        <h1 class="font-bold text-slate-700"><?= e($page_title ?? 'แดชบอร์ด') ?></h1>
      </div>
      <div class="flex items-center gap-3">
        <a href="<?= url('/') ?>" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-slate-600 hover:text-primary-700">
          <i data-lucide="external-link" class="w-4 h-4"></i> View Site
        </a>
        <?php \App\Core\View::partial('partials/bell'); ?>
        <div class="flex items-center gap-2">
          <div class="w-9 h-9 rounded-full bg-primary-100 text-primary-700 grid place-items-center font-bold text-sm"><?= e(str_first_char((string) ($user['name'] ?? ''))) ?></div>
          <div class="hidden sm:block">
            <div class="text-sm font-semibold leading-tight"><?= e($user['name']) ?></div>
            <div class="text-[11px] text-slate-500">Administrator</div>
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
    © <?= date('Y') ?> แพกาญ.com — Admin Backoffice
  </footer>
</div>

<script>document.addEventListener('DOMContentLoaded',()=>lucide.createIcons());document.addEventListener('alpine:initialized',()=>lucide.createIcons());</script>
</body>
</html>
