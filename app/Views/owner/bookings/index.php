<?php /** @var array $rows @var array $counts @var string $status @var string $q */ ?>

<!-- Status filter pills -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-2 mb-4">
  <?php
  $tabs = [
    ['','ทั้งหมด',$counts['total'] ?? 0,'list'],
    ['pending','รอยืนยัน',$counts['pending'] ?? 0,'clock'],
    ['confirmed','ยืนยันแล้ว',$counts['confirmed'] ?? 0,'check-circle'],
    ['completed','เสร็จสิ้น',$counts['completed'] ?? 0,'flag'],
    ['rejected','ปฏิเสธ',$counts['rejected'] ?? 0,'x-circle'],
  ];
  foreach ($tabs as $t):
    $active = (string)$status === $t[0];
    $url = url('/owner/bookings') . ($t[0] ? '?status='.$t[0] : '');
  ?>
  <a href="<?= e($url) ?>" class="bg-white rounded-xl border <?= $active?'border-accent-500 ring-2 ring-accent-100':'border-slate-200' ?> p-3 flex items-center gap-3 hover:shadow-soft transition">
    <div class="w-9 h-9 rounded-lg <?= $active?'bg-accent-100 text-accent-700':'bg-slate-100 text-slate-600' ?> grid place-items-center"><i data-lucide="<?= $t[3] ?>" class="w-4 h-4"></i></div>
    <div>
      <div class="text-xs text-slate-500"><?= e($t[1]) ?></div>
      <div class="font-bold"><?= number_format((int)$t[2]) ?></div>
    </div>
  </a>
  <?php endforeach; ?>
</div>

<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-4 border-b border-slate-100 flex flex-wrap gap-3 items-center justify-between">
    <h3 class="font-bold flex items-center gap-2"><i data-lucide="calendar-check" class="w-5 h-5 text-accent-600"></i> รายการจอง <?= $status ? "($status)" : '' ?></h3>
    <form method="get" class="flex items-center gap-2">
      <input type="hidden" name="status" value="<?= e($status) ?>">
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="ค้นหารหัส/ชื่อ/เบอร์" class="px-3 py-1.5 rounded-lg border border-slate-300 text-sm w-48">
      <button class="px-3 py-1.5 bg-primary-600 text-white rounded-lg text-sm">ค้นหา</button>
    </form>
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
      <?php
      $sc = ['pending'=>'amber','confirmed'=>'emerald','rejected'=>'rose','cancelled'=>'slate','completed'=>'blue','no_show'=>'slate'];
      $bookingStatusIcons = ['pending'=>'clock','confirmed'=>'check-circle','rejected'=>'x-circle','cancelled'=>'ban','completed'=>'flag','no_show'=>'user-x'];
      foreach ($rows as $b):
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
            ?>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 rounded-full whitespace-nowrap"><i data-lucide="<?= e($modeIc) ?>" class="w-3.5 h-3.5 shrink-0 text-slate-600"></i><?= e($bm) ?></span>
          </td>
          <td class="px-4 py-3 font-semibold text-primary-700"><?= format_money($b['total_price']) ?></td>
          <td class="px-4 py-3"><span class="inline-flex items-center gap-1 text-xs font-semibold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2 py-1 rounded-full"><i data-lucide="<?= e($sti) ?>" class="w-3.5 h-3.5 shrink-0"></i><?= e($b['status']) ?></span></td>
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
