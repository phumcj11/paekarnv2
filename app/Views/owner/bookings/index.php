<?php /** @var array $rows @var array $counts @var string $status @var string $q */

$sc = ['pending'=>'amber','confirmed'=>'emerald','rejected'=>'rose','cancelled'=>'slate','completed'=>'blue','no_show'=>'slate'];
$bookingStatusIcons = ['pending'=>'clock','confirmed'=>'check-circle','rejected'=>'x-circle','cancelled'=>'ban','completed'=>'flag','no_show'=>'user-x'];
$statusLabels = [
    'pending'   => 'รอยืนยัน',
    'confirmed' => 'ยืนยันแล้ว',
    'completed' => 'เสร็จสิ้น',
    'cancelled' => 'ยกเลิก',
    'rejected'  => 'ปฏิเสธ',
    'no_show'   => 'ไม่มา',
];
$modeLabels = [
    'info_only'       => 'ข้อมูลอย่างเดียว',
    'coupon_assisted' => 'บันทึกมือ',
    'full_booking'    => 'จองออนไลน์',
];

$tabs = [
    ['','ทั้งหมด',$counts['total'] ?? 0,'list'],
    ['pending','รอยืนยัน',$counts['pending'] ?? 0,'clock'],
    ['confirmed','ยืนยันแล้ว',$counts['confirmed'] ?? 0,'check-circle'],
    ['completed','เสร็จสิ้น',$counts['completed'] ?? 0,'flag'],
    ['rejected','ปฏิเสธ',$counts['rejected'] ?? 0,'x-circle'],
];
?>

<!-- Status filter -->
<div class="flex gap-2 overflow-x-auto pb-1 mb-4 scrollbar-hide lg:grid lg:grid-cols-5 lg:overflow-visible lg:pb-0">
  <?php foreach ($tabs as $t):
    $active = (string)$status === $t[0];
    $url = url('/owner/bookings') . ($t[0] ? '?status='.$t[0] : '');
    if ($q !== '') $url .= ($t[0] ? '&' : '?') . 'q=' . urlencode($q);
  ?>
  <a href="<?= e($url) ?>"
     class="shrink-0 lg:shrink bg-white rounded-xl border <?= $active ? 'border-core-500 ring-2 ring-core-100' : 'border-slate-200' ?> p-3 flex items-center gap-2 lg:gap-3 hover:shadow-soft transition min-w-[120px] lg:min-w-0">
    <div class="w-8 h-8 lg:w-9 lg:h-9 rounded-lg <?= $active ? 'bg-core-100 text-core-700' : 'bg-slate-100 text-slate-600' ?> grid place-items-center shrink-0">
      <i data-lucide="<?= $t[3] ?>" class="w-4 h-4"></i>
    </div>
    <div class="min-w-0">
      <div class="text-[10px] lg:text-xs text-slate-500 truncate"><?= e($t[1]) ?></div>
      <div class="font-bold text-sm lg:text-base"><?= number_format((int)$t[2]) ?></div>
    </div>
  </a>
  <?php endforeach; ?>
</div>

<!-- Mobile: actions + search -->
<div class="lg:hidden mb-4 space-y-2">
  <a href="<?= url('/owner/bookings/create') ?>" class="ow-btn-primary w-full justify-center">
    <i data-lucide="plus" class="w-4 h-4"></i> บันทึกการจอง
  </a>
  <form method="get" class="flex gap-2">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="ค้นหารหัส / ชื่อ / เบอร์"
           class="flex-1 min-w-0 px-3 py-2.5 rounded-xl border border-slate-200 text-sm">
    <button type="submit" class="px-4 py-2.5 bg-slate-700 text-white rounded-xl text-sm font-semibold shrink-0">ค้นหา</button>
  </form>
</div>

<!-- Mobile: card list -->
<div class="lg:hidden space-y-2 mb-5">
  <?php if (empty($rows)): ?>
  <div class="ow-card p-8 text-center">
    <i data-lucide="inbox" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
    <p class="text-sm text-slate-500">ไม่พบรายการจอง</p>
    <?php if ($q !== '' || $status !== ''): ?>
    <a href="<?= url('/owner/bookings') ?>" class="inline-block mt-2 text-xs font-semibold text-core-600">ล้างตัวกรอง</a>
    <?php endif; ?>
  </div>
  <?php else: ?>
  <?php foreach ($rows as $b):
    $c = $sc[$b['status']] ?? 'slate';
    $stLabel = $statusLabels[$b['status']] ?? $b['status'];
    $mode = $modeLabels[$b['mode'] ?? ''] ?? ($b['mode'] ?? '');
    $detailUrl = url('/owner/bookings/' . $b['id']);
  ?>
  <div class="ow-card p-3">
    <a href="<?= e($detailUrl) ?>" class="block">
      <div class="flex items-start justify-between gap-2">
        <div class="min-w-0 flex-1">
          <div class="font-semibold text-sm text-slate-800 truncate"><?= e($b['guest_name']) ?></div>
          <div class="text-[10px] text-core-600 font-mono mt-0.5"><?= e($b['code']) ?></div>
        </div>
        <span class="text-[10px] font-bold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2 py-0.5 rounded-full shrink-0"><?= e($stLabel) ?></span>
      </div>
      <div class="mt-2 text-xs text-slate-600 space-y-1">
        <div class="flex items-center gap-1.5">
          <i data-lucide="hotel" class="w-3 h-3 text-slate-400 shrink-0"></i>
          <span class="truncate"><?= e($b['property_name']) ?><?= $b['unit_name'] ? ' · ' . e($b['unit_name']) : '' ?></span>
        </div>
        <div class="flex items-center gap-1.5">
          <i data-lucide="calendar" class="w-3 h-3 text-slate-400 shrink-0"></i>
          <span><?= format_date_th($b['check_in']) ?> → <?= format_date_th($b['check_out']) ?> (<?= (int)$b['nights'] ?> คืน)</span>
        </div>
        <div class="flex items-center justify-between gap-2 pt-0.5">
          <span class="font-bold text-core-700"><?= format_money($b['total_price']) ?></span>
          <?php if ($mode): ?>
          <span class="text-[10px] text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full"><?= e($mode) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </a>
    <?php if (!empty($b['guest_phone'])): ?>
    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
      <span class="text-xs text-slate-500 truncate"><?= e($b['guest_phone']) ?></span>
      <a href="tel:<?= e(preg_replace('/\s+/', '', $b['guest_phone'])) ?>"
         class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg shrink-0">
        <i data-lucide="phone" class="w-3.5 h-3.5"></i> โทร
      </a>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Desktop: table -->
<div class="hidden lg:block bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-4 border-b border-slate-100 flex flex-wrap gap-3 items-center justify-between">
    <h3 class="font-bold flex items-center gap-2"><i data-lucide="calendar-check" class="w-5 h-5 text-accent-600"></i> รายการจอง <?= $status ? "($status)" : '' ?></h3>
    <div class="flex items-center gap-2 flex-wrap">
      <a href="<?= url('/owner/bookings/create') ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-accent-600 hover:bg-accent-700 text-white text-sm font-semibold rounded-lg shadow transition">
        <i data-lucide="plus" class="w-4 h-4"></i> บันทึกการจอง
      </a>
      <form method="get" class="flex items-center gap-2">
        <input type="hidden" name="status" value="<?= e($status) ?>">
        <input type="text" name="q" value="<?= e($q) ?>" placeholder="ค้นหารหัส/ชื่อ/เบอร์" class="px-3 py-1.5 rounded-lg border border-slate-300 text-sm w-48">
        <button class="px-3 py-1.5 bg-primary-600 text-white rounded-lg text-sm">ค้นหา</button>
      </form>
    </div>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="text-left px-4 py-3">รหัส</th>
          <th class="text-left px-4 py-3">ผู้จอง</th>
          <th class="text-left px-4 py-3">ที่พัก / ห้อง</th>
          <th class="text-left px-4 py-3">เช็คอิน → เช็คเอาท์</th>
          <th class="text-left px-4 py-3">โหมด</th>
          <th class="text-left px-4 py-3">รวม</th>
          <th class="text-left px-4 py-3">สถานะ</th>
          <th class="text-right px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php foreach ($rows as $b):
        $c = $sc[$b['status']] ?? 'slate';
        $sti = $bookingStatusIcons[$b['status']] ?? 'circle-dot';
      ?>
        <tr class="hover:bg-slate-50">
          <td class="px-4 py-3 font-mono text-xs text-accent-700"><?= e($b['code']) ?></td>
          <td class="px-4 py-3"><?= e($b['guest_name']) ?><div class="text-xs text-slate-500"><?= e($b['guest_phone']) ?></div></td>
          <td class="px-4 py-3"><?= e($b['property_name']) ?><div class="text-xs text-slate-500"><?= e($b['unit_name']) ?></div></td>
          <td class="px-4 py-3 text-xs"><?= format_date_th($b['check_in']) ?><br>→ <?= format_date_th($b['check_out']) ?></td>
          <td class="px-4 py-3 text-xs">
            <?php
            $bm = (string)($b['mode'] ?? '');
            $modeIc = ($bm === 'info_only') ? 'info' : 'calendar-check';
            $modeLabel = $modeLabels[$bm] ?? $bm;
            ?>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 rounded-full whitespace-nowrap"><i data-lucide="<?= e($modeIc) ?>" class="w-3.5 h-3.5 shrink-0 text-slate-600"></i><?= e($modeLabel) ?></span>
          </td>
          <td class="px-4 py-3 font-semibold text-primary-700"><?= format_money($b['total_price']) ?></td>
          <td class="px-4 py-3"><span class="inline-flex items-center gap-1 text-xs font-semibold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2 py-1 rounded-full"><i data-lucide="<?= e($sti) ?>" class="w-3.5 h-3.5 shrink-0"></i><?= e($statusLabels[$b['status']] ?? $b['status']) ?></span></td>
          <td class="px-4 py-3 text-right">
            <a href="<?= url('/owner/bookings/' . $b['id']) ?>" class="px-3 py-1.5 text-xs bg-accent-600 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="eye" class="w-3.5 h-3.5"></i> ดู</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
        <tr><td colspan="8" class="text-center py-10 text-slate-500">
          <span class="inline-flex flex-col items-center gap-2">
            <i data-lucide="inbox" class="w-10 h-10 text-slate-300"></i>
            <span>ไม่พบรายการ</span>
          </span>
        </td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
