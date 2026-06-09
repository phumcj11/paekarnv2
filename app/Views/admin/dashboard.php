<?php /** @var array $stats @var array $recentBookings @var array $topProperties @var array $topByViews @var array $chart @var array $problemPending @var array $problemBookings @var array $problemPhones @var int $warn_days @var int $warn_book_hours */ ?>

<!-- KPI row 1 -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
  <?php
  $cards = [
    ['hotel', 'ที่พักเผยแพร่', number_format($stats['properties_published']), '/' . number_format($stats['properties_total']) . ' ทั้งหมด · รออนุมัติ ' . number_format($stats['pending_props']), 'bg-blue-50 text-blue-600'],
    ['briefcase', 'เจ้าของแพ', number_format($stats['owners_total']), 'ผู้ใช้ลูกค้า ' . number_format($stats['customers_total']), 'bg-indigo-50 text-indigo-600'],
    ['calendar-check', 'การจองวันนี้', number_format($stats['bookings_today']), 'ทั้งหมด ' . number_format($stats['bookings']) . ' · รอยืนยัน ' . number_format($stats['pending_bk']), 'bg-amber-50 text-amber-600'],
    ['sparkles', 'Lead วันนี้', number_format($stats['leads_today']), 'Lead ทั้งหมด ' . number_format($stats['leads_total']), 'bg-purple-50 text-purple-600'],
  ];
  foreach ($cards as $c): ?>
  <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-soft">
    <div class="flex items-start justify-between">
      <div class="w-11 h-11 rounded-xl <?= $c[4] ?> grid place-items-center"><i data-lucide="<?= $c[0] ?>" class="w-5 h-5"></i></div>
    </div>
    <div class="mt-3 text-2xl font-extrabold text-ink"><?= e((string)$c[2]) ?></div>
    <div class="text-xs text-slate-500 mt-0.5"><?= e($c[1]) ?></div>
    <div class="text-xs text-accent-700 mt-2"><?= e($c[3]) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- KPI row 2 -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
  <?php
  $cards2 = [
    ['user-plus', 'ลูกค้าใหม่', number_format($stats['customers_new_7d']), '7 วัน · ' . number_format($stats['customers_new_30d']) . ' คนใน 30 วัน', 'bg-teal-50 text-teal-600'],
    ['ticket', 'คูปองขายแล้ว (ใบ)', number_format($stats['coupon_qty_sold']), 'ใช้แล้ว ' . number_format($stats['coupons_used']) . ' · ค้างใช้ ' . number_format($stats['coupons_unused']), 'bg-rose-50 text-rose-600'],
    ['banknote', 'รายได้คูปอง', '฿' . number_format($stats['revenue']), 'การจองยืนยัน ฿' . number_format($stats['booking_rev']), 'bg-emerald-50 text-emerald-600'],
    ['star', 'รีวิวที่อนุมัติ', number_format($stats['reviews']), 'คะแนนเฉลี่ยจากที่พัก', 'bg-yellow-50 text-yellow-600'],
  ];
  foreach ($cards2 as $c): ?>
  <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-soft">
    <div class="w-11 h-11 rounded-xl <?= $c[4] ?> grid place-items-center"><i data-lucide="<?= $c[0] ?>" class="w-5 h-5"></i></div>
    <div class="mt-3 text-2xl font-extrabold text-ink"><?= e((string)$c[2]) ?></div>
    <div class="text-xs text-slate-500 mt-0.5"><?= e($c[1]) ?></div>
    <div class="text-xs text-accent-700 mt-2"><?= e($c[3]) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Problems + Chart -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-6">
  <div class="lg:col-span-2 space-y-4">
    <div class="bg-rose-50 border border-rose-200 rounded-2xl p-5 shadow-soft">
      <h3 class="font-bold flex items-center gap-2 text-rose-900 mb-2"><i data-lucide="alert-triangle" class="w-5 h-5"></i> แพมีปัญหา (v0)</h3>
      <p class="text-xs text-rose-800/90 mb-3">
        ที่พักค้าง <code class="bg-white/80 px-1 rounded">pending</code> เกิน <?= (int)$warn_days ?> วัน · การจอง <code class="bg-white/80 px-1 rounded">pending</code> เกิน <?= (int)$warn_book_hours ?> ชม.
        <?php if (!empty($problemPhones)): ?> · ที่พักเผยแพร่ไม่มีเบอร์<?php endif; ?>
      </p>
      <div class="grid md:grid-cols-3 gap-3 text-sm">
        <div class="bg-white rounded-xl border border-rose-100 p-3">
          <div class="font-semibold text-rose-800 mb-1">ที่พักรออนุมัตินาน</div>
          <?php if (empty($problemPending)): ?>
            <div class="text-slate-500 text-xs">ไม่มีรายการ</div>
          <?php else: ?>
            <ul class="space-y-1 text-xs">
              <?php foreach ($problemPending as $p): ?>
                <li><a class="text-primary-700 hover:underline font-medium" href="<?= url('/admin/properties/' . $p['id'] . '/edit') ?>"><?= e($p['name']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
        <div class="bg-white rounded-xl border border-rose-100 p-3">
          <div class="font-semibold text-rose-800 mb-1">จองค้างนาน</div>
          <?php if (empty($problemBookings)): ?>
            <div class="text-slate-500 text-xs">ไม่มีรายการ</div>
          <?php else: ?>
            <ul class="space-y-1 text-xs">
              <?php foreach ($problemBookings as $b): ?>
                <li>
                  <a class="text-primary-700 hover:underline font-mono" href="<?= url('/admin/bookings/' . $b['id']) ?>"><?= e($b['code']) ?></a>
                  <span class="text-slate-500"> · <?= e($b['property_name']) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
        <div class="bg-white rounded-xl border border-rose-100 p-3">
          <div class="font-semibold text-rose-800 mb-1">เผยแพร่แต่ไม่มีเบอร์</div>
          <?php if (empty($problemPhones)): ?>
            <div class="text-slate-500 text-xs">ไม่มีรายการ</div>
          <?php else: ?>
            <ul class="space-y-1 text-xs">
              <?php foreach ($problemPhones as $p): ?>
                <li><a class="text-primary-700 hover:underline" href="<?= url('/admin/properties/' . $p['id'] . '/edit') ?>"><?= e($p['name']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-soft">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold flex items-center gap-2"><i data-lucide="trending-up" class="w-5 h-5 text-accent-600"></i> การจองใน 14 วันล่าสุด</h3>
      </div>
      <?php $maxC = max(1, max(array_column($chart, 'count'))); ?>
      <div class="grid grid-cols-14 gap-1 h-40 items-end" style="grid-template-columns: repeat(14,minmax(0,1fr));">
        <?php foreach ($chart as $pt): $h = max(4, (int)round(($pt['count']/$maxC)*100)); ?>
        <div class="flex flex-col items-center justify-end h-full group">
          <div class="text-[9px] text-slate-500 group-hover:text-accent-600 font-semibold"><?= $pt['count'] ?></div>
          <div class="w-full bg-accent-500 hover:bg-accent-600 rounded-t" style="height: <?= $h ?>%"></div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="grid grid-cols-14 gap-1 mt-1 text-[9px] text-slate-400 text-center" style="grid-template-columns: repeat(14,minmax(0,1fr));">
        <?php foreach ($chart as $pt): ?><div><?= $pt['date'] ?></div><?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-soft">
      <h3 class="font-bold flex items-center gap-2 mb-3"><i data-lucide="eye" class="w-5 h-5 text-accent-600"></i> เข้าชมสูงสุด</h3>
      <ol class="space-y-2.5">
        <?php foreach ($topByViews as $i => $p): ?>
        <li class="flex items-center gap-2">
          <span class="w-6 text-xs font-bold text-slate-400"><?= $i+1 ?></span>
          <div class="flex-1 min-w-0">
            <a href="<?= url('/admin/properties/' . $p['id'] . '/edit') ?>" class="text-sm font-semibold truncate hover:text-accent-700 block"><?= e($p['name']) ?></a>
            <div class="text-[11px] text-slate-500"><?= number_format((int)$p['view_count']) ?> views</div>
          </div>
        </li>
        <?php endforeach; ?>
        <?php if (empty($topByViews)): ?><li class="text-xs text-slate-500">ยังไม่มีข้อมูล</li><?php endif; ?>
      </ol>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-soft">
      <h3 class="font-bold flex items-center gap-2 mb-3"><i data-lucide="trophy" class="w-5 h-5 text-accent-600"></i> Top จองสูงสุด</h3>
      <ol class="space-y-2.5">
        <?php foreach ($topProperties as $i => $p): ?>
        <li class="flex items-center gap-3">
          <div class="w-7 h-7 rounded-full bg-slate-100 grid place-items-center font-bold text-xs"><?= $i+1 ?></div>
          <img src="<?= e(upload_url((string)($p['cover_image'] ?? ''))) ?>" alt="" class="w-9 h-9 rounded-lg object-cover">
          <div class="flex-1 min-w-0">
            <div class="text-sm font-semibold truncate"><?= e($p['name']) ?></div>
            <div class="text-[11px] text-slate-500"><?= $p['booking_count'] ?? 0 ?> การจอง · ⭐ <?= number_format((float)$p['rating_avg'], 1) ?></div>
          </div>
        </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </div>
</div>

<!-- Recent bookings -->
<div class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
    <h3 class="font-bold flex items-center gap-2"><i data-lucide="clock" class="w-5 h-5 text-accent-600"></i> การจองล่าสุด</h3>
    <div class="flex gap-2 text-sm">
      <a href="<?= url('/admin/leads') ?>" class="font-semibold text-primary-700 hover:text-accent-600">Leads →</a>
      <a href="<?= url('/admin/bookings') ?>" class="font-semibold text-primary-700 hover:text-accent-600">ดูทั้งหมด →</a>
    </div>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
        <tr>
          <th class="text-left px-5 py-3">รหัส</th>
          <th class="text-left px-5 py-3">ผู้จอง</th>
          <th class="text-left px-5 py-3">ที่พัก</th>
          <th class="text-left px-5 py-3">วันที่</th>
          <th class="text-left px-5 py-3">โหมด</th>
          <th class="text-left px-5 py-3">รวม</th>
          <th class="text-left px-5 py-3">สถานะ</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php
      $bookingToneClass = [
        'pending'   => 'bg-amber-100 text-amber-700',
        'confirmed' => 'bg-emerald-100 text-emerald-700',
        'rejected'  => 'bg-rose-100 text-rose-700',
        'cancelled' => 'bg-slate-100 text-slate-700',
        'completed' => 'bg-blue-100 text-blue-700',
        'no_show'   => 'bg-slate-100 text-slate-700',
      ];
      $bookingStatusIcons = ['pending'=>'clock','confirmed'=>'check-circle','rejected'=>'x-circle','cancelled'=>'ban','completed'=>'flag','no_show'=>'user-x'];
      foreach ($recentBookings as $b):
        $tone = $bookingToneClass[$b['status']] ?? 'bg-slate-100 text-slate-700';
        $sti = $bookingStatusIcons[$b['status']] ?? 'circle-dot';
        $admBm = (string)($b['mode'] ?? '');
        $admModeIc = ($admBm === 'info_only') ? 'info' : 'calendar-check';
      ?>
        <tr class="hover:bg-slate-50">
          <td class="px-5 py-3 font-mono text-xs text-primary-700"><?= e($b['code']) ?></td>
          <td class="px-5 py-3"><?= e($b['guest_name']) ?><div class="text-xs text-slate-500"><?= e($b['guest_phone']) ?></div></td>
          <td class="px-5 py-3 text-slate-700"><?= e($b['property_name']) ?></td>
          <td class="px-5 py-3 text-xs"><?= format_date_th($b['check_in']) ?> → <?= format_date_th($b['check_out']) ?></td>
          <td class="px-5 py-3 text-xs">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 rounded-full whitespace-nowrap"><i data-lucide="<?= e($admModeIc) ?>" class="w-3.5 h-3.5 shrink-0 text-slate-600"></i><?= e($admBm) ?></span>
          </td>
          <td class="px-5 py-3 font-semibold text-primary-700"><?= format_money($b['total_price']) ?></td>
          <td class="px-5 py-3"><span class="inline-flex items-center gap-1 text-xs font-semibold <?= $tone ?> px-2 py-1 rounded-full"><i data-lucide="<?= e($sti) ?>" class="w-3.5 h-3.5 shrink-0"></i><?= e($b['status']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($recentBookings)): ?>
        <tr><td colspan="7" class="text-center py-10 text-slate-500">
          <span class="inline-flex flex-col items-center gap-2">
            <i data-lucide="calendar-off" class="w-10 h-10 text-slate-300 mx-auto"></i>
            <span>ยังไม่มีการจอง</span>
          </span>
        </td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
