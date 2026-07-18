<?php
use App\Core\Auth;
use App\Models\Notification;

if (!function_exists('self_notif_icon')) {
    function self_notif_icon(string $type): string {
        return match (true) {
            str_contains($type, 'booking')  => 'calendar-check',
            str_contains($type, 'coupon')   => 'ticket',
            str_contains($type, 'payment')  => 'banknote',
            str_contains($type, 'review')   => 'star',
            str_contains($type, 'property') => 'hotel',
            str_contains($type, 'partner')  => 'user-plus',
            str_contains($type, 'unit')     => 'bed-double',
            str_contains($type, 'membership') => 'crown',
            default => 'bell',
        };
    }
}

if (!Auth::check()) {
    return;
}
$uid = (int)Auth::id();
$unread = Notification::unreadCount($uid);
$items  = Notification::recentForUser($uid, 8);
?>
<div class="relative" x-data="{open:false}" @click.away="open=false">
  <button @click="open=!open; if(open) fetch('<?= url('/notifications/read') ?>',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'}})" class="relative inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 shadow-sm">
    <i data-lucide="bell" class="w-[17px] h-[17px]"></i>
    <?php if ($unread > 0): ?>
      <span class="absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-0.5 grid place-items-center bg-rose-500 text-white text-[9px] font-bold rounded-full"><?= $unread > 9 ? '9+' : $unread ?></span>
    <?php endif; ?>
  </button>
  <div x-show="open" x-transition x-cloak class="absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden z-50">
    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
      <div class="font-bold text-sm flex items-center gap-2"><i data-lucide="bell" class="w-4 h-4 text-accent-600"></i> การแจ้งเตือน</div>
      <?php if ($unread > 0): ?><span class="text-xs text-rose-600"><?= $unread ?> ใหม่</span><?php endif; ?>
    </div>
    <div class="max-h-80 overflow-y-auto">
      <?php if (empty($items)): ?>
        <div class="px-4 py-10 text-center text-sm text-slate-500">
          <i data-lucide="inbox" class="w-8 h-8 mx-auto text-slate-300"></i>
          <p class="mt-2">ยังไม่มีการแจ้งเตือน</p>
        </div>
      <?php else: ?>
        <?php foreach ($items as $n): ?>
        <a href="<?= e($n['link'] ? url($n['link']) : '#') ?>" class="block px-4 py-3 border-b border-slate-50 hover:bg-slate-50 <?= !$n['read_at'] ? 'bg-accent-50/40' : '' ?>">
          <div class="flex items-start gap-2">
            <div class="w-7 h-7 rounded-lg bg-accent-100 text-accent-700 grid place-items-center shrink-0">
              <i data-lucide="<?= self_notif_icon((string)($n['type'] ?? '')) ?>" class="w-3.5 h-3.5"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-semibold truncate"><?= e($n['title']) ?></div>
              <div class="text-xs text-slate-600 line-clamp-2"><?= e($n['message']) ?></div>
              <div class="text-[10px] text-slate-400 mt-1"><?= time_ago($n['created_at'] ?? null) ?></div>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
