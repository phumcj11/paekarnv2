<?php
use App\Core\Auth;

/** @var string|null $page */
$p = $page ?? '';
$isHome = $p === 'home/index';
$isSearch = $p === 'properties/index';
$isFav = $p === 'account/favorites';
$isCoupon = str_starts_with($p, 'coupons/') || $p === 'account/coupons';
$isAcct = str_starts_with($p, 'account/') && !$isFav && !$isCoupon;

$user = Auth::user();
$couponUrl = ($user && Auth::isCustomer()) ? url('/account/coupons') : url('/coupons/buy');

$tabClass = function (bool $on): string {
    return $on
        ? 'flex flex-col items-center justify-center gap-0.5 text-accent-600'
        : 'flex flex-col items-center justify-center gap-0.5 text-slate-500 active:text-slate-800';
};
?>
<nav class="md:hidden fixed bottom-0 inset-x-0 z-[45] bg-white/95 backdrop-blur-md border-t border-slate-200/90 pb-[max(0.35rem,env(safe-area-inset-bottom))] shadow-[0_-8px_32px_rgba(15,23,42,0.08)]" aria-label="เมนูล่าง">
  <div class="grid grid-cols-5 min-h-[3.25rem]">
    <a href="<?= url('/') ?>" class="<?= $tabClass($isHome) ?>">
      <i data-lucide="home" class="w-5 h-5 stroke-[2.25px]"></i>
      <span class="text-[10px] font-bold leading-none">หน้าหลัก</span>
    </a>
    <a href="<?= url('/properties') ?>" class="<?= $tabClass($isSearch) ?>">
      <i data-lucide="search" class="w-5 h-5 stroke-[2.25px]"></i>
      <span class="text-[10px] font-bold leading-none">ค้นหา</span>
    </a>
    <a href="<?= url('/account/favorites') ?>" class="<?= $tabClass($isFav) ?>">
      <i data-lucide="heart" class="w-5 h-5 stroke-[2.25px]"></i>
      <span class="text-[10px] font-bold leading-none">ถูกใจ</span>
    </a>
    <a href="<?= $couponUrl ?>" class="<?= $tabClass($isCoupon) ?>">
      <i data-lucide="gift" class="w-5 h-5 stroke-[2.25px]"></i>
      <span class="text-[10px] font-bold leading-none">คูปอง</span>
    </a>
    <a href="<?= url('/account') ?>" class="<?= $tabClass($isAcct) ?>">
      <i data-lucide="user" class="w-5 h-5 stroke-[2.25px]"></i>
      <span class="text-[10px] font-bold leading-none">บัญชี</span>
    </a>
  </div>
</nav>
