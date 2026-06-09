<?php /** @var array $stats @var array $recentBookings @var array $myProperties @var array $chart
           @var array|null $membership_owner @var bool $membership_benefits_active @var bool $membership_line_linked @var bool $membership_is_vip */ ?>

<!-- Welcome banner if no properties yet -->
<?php if ($stats['properties'] === 0): ?>
<div class="bg-gradient-to-r from-accent-500 to-primary-700 text-white rounded-2xl p-6 mb-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
  <div>
    <h2 class="text-xl font-bold flex items-center gap-2"><i data-lucide="rocket" class="w-6 h-6"></i> เริ่มต้นใช้งาน</h2>
    <p class="text-white/90 mt-1 text-sm">เพิ่มที่พักแรกของคุณ เพื่อเริ่มรับการจอง</p>
  </div>
  <a href="<?= url('/owner/properties/create') ?>" class="inline-flex items-center gap-2 bg-white text-accent-700 px-5 py-2.5 rounded-xl font-semibold hover:bg-cloud whitespace-nowrap">
    <i data-lucide="plus-circle" class="w-4 h-4"></i> เพิ่มที่พัก
  </a>
</div>
<?php endif; ?>

<!-- KPI Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
  <?php
  $cards = [
    ['hotel','ที่พักทั้งหมด',$stats['properties'], 'เผยแพร่ '.$stats['published'].' แห่ง', 'bg-blue-50 text-blue-600'],
    ['calendar-check','การจองทั้งหมด',$stats['bookings_total'], 'รอยืนยัน '.$stats['bookings_pending'].' รายการ', 'bg-amber-50 text-amber-600'],
    ['ticket','คูปองที่ถูกใช้',$stats['coupons_used'], 'รวมทุกที่พัก', 'bg-rose-50 text-rose-600'],
    ['banknote','รายได้รวม','฿'.number_format($stats['revenue']), 'จากการจอง confirmed/completed', 'bg-emerald-50 text-emerald-600'],
  ];
  foreach ($cards as $c): ?>
  <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-soft">
    <div class="w-11 h-11 rounded-xl <?= $c[4] ?> grid place-items-center"><i data-lucide="<?= $c[0] ?>" class="w-5 h-5"></i></div>
    <div class="mt-3 text-2xl font-extrabold text-ink"><?= $c[2] ?></div>
    <div class="text-xs text-slate-500 mt-0.5"><?= e($c[1]) ?></div>
    <div class="text-xs text-accent-700 mt-2"><?= e($c[3]) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Mini stats row -->
<div class="grid grid-cols-2 md:grid-cols-3 gap-3 mt-4">
  <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
    <i data-lucide="message-circle" class="w-5 h-5 text-accent-600"></i>
    <div><div class="text-lg font-bold"><?= number_format($stats['reviews']) ?></div><div class="text-xs text-slate-500">รีวิวทั้งหมด</div></div>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
    <i data-lucide="star" class="w-5 h-5 text-amber-500 fill-current"></i>
    <div><div class="text-lg font-bold"><?= number_format($stats['rating_avg'], 2) ?></div><div class="text-xs text-slate-500">คะแนนเฉลี่ย</div></div>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3">
    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-500"></i>
    <div><div class="text-lg font-bold"><?= number_format($stats['bookings_confirmed']) ?></div><div class="text-xs text-slate-500">การจองที่ยืนยันแล้ว</div></div>
  </div>
</div>

<?php if ($membership_owner): ?>
<div class="rounded-2xl border p-5 mt-5 shadow-soft <?= $membership_is_vip && $membership_benefits_active ? 'bg-gradient-to-r from-amber-50 to-orange-50 border-amber-200' : ($membership_benefits_active ? 'bg-gradient-to-r from-sky-50 to-blue-50 border-sky-200' : 'bg-slate-50 border-slate-200') ?>">
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div>
      <h3 class="font-bold flex items-center gap-2 <?= $membership_is_vip && $membership_benefits_active ? 'text-amber-900' : 'text-primary-900' ?>">
        <?php if ($membership_is_vip && $membership_benefits_active): ?>
          <i data-lucide="crown" class="w-5 h-5 text-amber-600"></i> สมาชิก VIP ใช้งานอยู่
        <?php elseif ($membership_benefits_active): ?>
          <i data-lucide="badge-check" class="w-5 h-5 text-sky-600"></i> สมาชิกธรรมดาใช้งานอยู่
        <?php else: ?>
          <i data-lucide="circle-alert" class="w-5 h-5 text-slate-600"></i> สถานะสมาชิก / สิทธิ์พิเศษ
        <?php endif; ?>
      </h3>
      <?php if ($membership_is_vip && $membership_benefits_active): ?>
        <p class="text-sm text-amber-900/90 mt-1">คุณมีสิทธิ์รับการแจ้งเตือนเมื่อมีลูกค้ากรอกฟอร์ม &quot;ขอให้ช่วยหาที่พัก&quot; ที่ตรงโซนและงบของที่พักที่เผยแพร่ของคุณ — แนะนำผูก LINE ในเมนูบัญชีลูกค้าและเปิดการแจ้งเตือน</p>
      <?php elseif (!$membership_benefits_active): ?>
        <p class="text-sm text-slate-700 mt-1">สิทธิ์แพ็กเกจหมดอายุหรืออยู่นอกระยะใช้งาน — ต่ออายุเพื่อเปิดฟีเจอร์สมาชิก</p>
      <?php endif; ?>
      <?php if ($membership_benefits_active && !$membership_line_linked): ?>
        <p class="text-xs text-rose-700 mt-2 font-medium">ยังไม่ได้ผูก LINE — คุณอาจพลาดการแจ้งเตือนทาง LINE (ยังมีอีเมลเมื่อเปิดในระบบ)</p>
      <?php endif; ?>
    </div>
    <div class="flex flex-wrap gap-2 shrink-0">
      <a href="<?= url('/owner/membership') ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-700 hover:bg-primary-800 text-white text-sm font-semibold"><i data-lucide="award" class="w-4 h-4"></i> จัดการสมาชิก</a>
      <?php if ($membership_is_vip && $membership_benefits_active): ?>
        <span class="inline-flex items-center gap-1 px-3 py-2 rounded-xl bg-white/80 border border-amber-200 text-xs font-semibold text-amber-900">Lead VIP เปิดใช้งาน</span>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="bg-gradient-to-r from-accent-50 to-primary-50 rounded-2xl border border-accent-200 p-5 mt-4 shadow-soft">
  <h3 class="font-bold text-sm text-primary-900 mb-3 flex items-center gap-2"><i data-lucide="calendar-days" class="w-4 h-4 text-accent-600"></i> เดือนนี้</h3>
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
    <div>
      <div class="text-xs text-slate-600">ยอดการจอง (ยืนยัน/สำเร็จ)</div>
      <div class="text-xl font-extrabold text-emerald-700 mt-0.5"><?= format_money($stats['revenue_month']) ?></div>
    </div>
    <div>
      <div class="text-xs text-slate-600">มูลค่าคูปองที่ใช้ที่แพของคุณ</div>
      <div class="text-xl font-extrabold text-rose-700 mt-0.5"><?= format_money($stats['coupon_face_month']) ?></div>
    </div>
    <div>
      <div class="text-xs text-slate-600">ลูกค้าติดต่อจากเว็บ (Lead)</div>
      <div class="text-xl font-extrabold text-primary-800 mt-0.5"><?= number_format($stats['leads_month']) ?> <span class="text-xs font-normal text-slate-500">รายการ</span></div>
    </div>
  </div>
</div>

<!-- Chart + My properties -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-6">
  <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-5 shadow-soft">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="trending-up" class="w-5 h-5 text-accent-600"></i> การจองใน 14 วันล่าสุด</h3>
    </div>
    <?php $maxC = max(1, max(array_column($chart, 'count'))); ?>
    <div class="grid gap-1 h-40 items-end" style="grid-template-columns: repeat(14,minmax(0,1fr));">
      <?php foreach ($chart as $pt): $h = max(4, (int)round(($pt['count']/$maxC)*100)); ?>
      <div class="flex flex-col items-center justify-end h-full group">
        <div class="text-[9px] text-slate-500 group-hover:text-accent-600 font-semibold"><?= $pt['count'] ?></div>
        <div class="w-full bg-accent-500 hover:bg-accent-600 rounded-t" style="height: <?= $h ?>%"></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="grid gap-1 mt-1 text-[9px] text-slate-400 text-center" style="grid-template-columns: repeat(14,minmax(0,1fr));">
      <?php foreach ($chart as $pt): ?><div><?= $pt['date'] ?></div><?php endforeach; ?>
    </div>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-soft">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="hotel" class="w-5 h-5 text-accent-600"></i> ที่พักของฉัน</h3>
      <a href="<?= url('/owner/properties') ?>" class="text-xs text-accent-700 hover:underline">ทั้งหมด →</a>
    </div>
    <ol class="space-y-2.5">
      <?php
      $__propStatusIcons = ['draft'=>'file-text','pending'=>'clock','published'=>'check-circle','rejected'=>'x-circle','archived'=>'archive'];
      foreach ($myProperties as $p):
        $__sic = $__propStatusIcons[$p['status']] ?? 'circle-dot';
      ?>
      <li class="flex items-center gap-3">
        <img src="<?= e(upload_url($p['cover_image']) ?: 'https://placehold.co/100x100') ?>" class="w-9 h-9 rounded-lg object-cover" alt="">
        <div class="flex-1 min-w-0">
          <a href="<?= url('/owner/properties/' . $p['id'] . '/edit') ?>" class="text-sm font-semibold truncate block hover:text-accent-700"><?= e($p['name']) ?></a>
          <div class="text-[11px] text-slate-500 inline-flex items-center gap-2 flex-wrap">
            <span class="inline-flex items-center gap-0.5"><i data-lucide="calendar-check" class="w-3 h-3 shrink-0 opacity-75"></i><span><?= (int)($p['booking_count'] ?? 0) ?></span></span>
            <span class="text-slate-300">·</span>
            <span class="inline-flex items-center gap-0.5"><i data-lucide="star" class="w-3 h-3 shrink-0 text-amber-500 fill-amber-400"></i><?= number_format((float)($p['rating_avg'] ?? 0), 1) ?></span>
          </div>
        </div>
        <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-slate-100 inline-flex items-center gap-0.5 shrink-0"><i data-lucide="<?= e($__sic) ?>" class="w-3 h-3"></i><?= e($p['status']) ?></span>
      </li>
      <?php endforeach; ?>
      <?php if (empty($myProperties)): ?><li class="text-sm text-slate-500 text-center py-6 inline-flex flex-col items-center gap-2 w-full"><i data-lucide="hotel" class="w-9 h-9 text-slate-300"></i><span>ยังไม่มีที่พัก</span></li><?php endif; ?>
    </ol>
  </div>
</div>

<!-- Recent bookings -->
<div class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
    <h3 class="font-bold flex items-center gap-2"><i data-lucide="clock" class="w-5 h-5 text-accent-600"></i> การจองล่าสุด</h3>
    <a href="<?= url('/owner/bookings') ?>" class="text-sm font-semibold text-accent-700 hover:underline">ดูทั้งหมด →</a>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
        <tr>
          <th class="text-left px-5 py-3">รหัส</th>
          <th class="text-left px-5 py-3">ผู้จอง</th>
          <th class="text-left px-5 py-3">ที่พัก / ห้อง</th>
          <th class="text-left px-5 py-3">วันที่</th>
          <th class="text-left px-5 py-3">โหมด</th>
          <th class="text-left px-5 py-3">รวม</th>
          <th class="text-left px-5 py-3">สถานะ</th>
          <th class="text-right px-5 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
      <?php
      $bookingStatusIcons = ['pending'=>'clock','confirmed'=>'check-circle','rejected'=>'x-circle','cancelled'=>'ban','completed'=>'flag','no_show'=>'user-x'];
      $colors = ['pending'=>'amber','confirmed'=>'emerald','rejected'=>'rose','cancelled'=>'slate','completed'=>'blue','no_show'=>'slate'];
      foreach ($recentBookings as $b):
        $c = $colors[$b['status']] ?? 'slate';
        $sti = $bookingStatusIcons[$b['status']] ?? 'circle-dot';
        $bm = (string)($b['mode'] ?? '');
        $modeIc = ($bm === 'info_only') ? 'info' : 'calendar-check';
      ?>
        <tr class="hover:bg-slate-50">
          <td class="px-5 py-3 font-mono text-xs text-accent-700"><?= e($b['code']) ?></td>
          <td class="px-5 py-3"><?= e($b['guest_name']) ?><div class="text-xs text-slate-500"><?= e($b['guest_phone']) ?></div></td>
          <td class="px-5 py-3"><?= e($b['property_name']) ?><div class="text-xs text-slate-500"><?= e($b['unit_name']) ?></div></td>
          <td class="px-5 py-3 text-xs"><?= format_date_th($b['check_in']) ?> → <?= format_date_th($b['check_out']) ?></td>
          <td class="px-5 py-3 text-xs">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 rounded-full whitespace-nowrap"><i data-lucide="<?= e($modeIc) ?>" class="w-3.5 h-3.5 shrink-0 text-slate-600"></i><?= e($bm) ?></span>
          </td>
          <td class="px-5 py-3 font-semibold text-primary-700"><?= format_money($b['total_price']) ?></td>
          <td class="px-5 py-3"><span class="inline-flex items-center gap-1 text-xs font-semibold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-2 py-1 rounded-full"><i data-lucide="<?= e($sti) ?>" class="w-3.5 h-3.5 shrink-0"></i><?= e($b['status']) ?></span></td>
          <td class="px-5 py-3 text-right">
            <a href="<?= url('/owner/bookings/' . $b['id']) ?>" class="px-3 py-1.5 text-xs bg-accent-600 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="eye" class="w-3.5 h-3.5"></i> ดู</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($recentBookings)): ?>
        <tr><td colspan="8" class="text-center py-10 text-slate-500">
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

<!-- Quick links -->
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 mt-6">
  <?php $links = [
    ['/owner/properties', 'layers', 'หลังพัก & ปฏิทิน', 'bg-slate-50 text-slate-700'],
    ['/owner/properties/create', 'plus-circle', 'เพิ่มที่พัก', 'bg-emerald-50 text-emerald-600'],
    ['/owner/bookings', 'calendar-check', 'จัดการจอง', 'bg-blue-50 text-blue-600'],
    ['/owner/coupons/verify', 'ticket', 'ตรวจคูปอง', 'bg-rose-50 text-rose-600'],
    ['/owner/coupons/scan', 'camera', 'สแกนคูปอง', 'bg-teal-50 text-teal-700'],
    ['/owner/membership', 'crown', 'สมาชิกเจ้าของแพ', 'bg-amber-50 text-amber-700'],
    ['/owner/profile', 'landmark', 'บัญชีธนาคาร', 'bg-amber-50 text-amber-600'],
];
foreach ($links as $l): ?>
  <a href="<?= url($l[0]) ?>" class="bg-white rounded-2xl border border-slate-200 p-4 hover:border-accent-400 hover:shadow-soft transition flex items-center gap-3">
    <div class="w-10 h-10 rounded-xl <?= $l[3] ?> grid place-items-center"><i data-lucide="<?= $l[1] ?>" class="w-5 h-5"></i></div>
    <div class="text-sm font-semibold"><?= e($l[2]) ?></div>
  </a>
  <?php endforeach; ?>
</div>
