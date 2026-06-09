<?php /** @var array $rows @var int $page @var int $totalPages @var int $total */ ?>
<div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
  <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
    <div>
      <h2 class="font-bold text-lg flex items-center gap-2"><i data-lucide="calendar-check" class="w-5 h-5 text-accent-600"></i> การจอง</h2>
      <p class="text-sm text-slate-500">ทั้งหมด <?= number_format($total) ?> รายการ</p>
    </div>
    <form method="get" class="flex flex-wrap gap-2 items-center">
      <input type="text" name="q" placeholder="ค้นหา code/ชื่อ/เบอร์..." value="<?= e($_GET['q'] ?? '') ?>" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
      <a href="<?= url('/admin/bookings/create') ?>" class="px-4 py-2 bg-accent-500 text-white rounded-lg text-sm inline-flex items-center gap-1"><i data-lucide="plus" class="w-4 h-4"></i> สร้างการจอง</a>
      <select name="property_id" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <option value="">ทุกที่พัก</option>
        <?php foreach ($propertyFilter as $pf): ?>
        <option value="<?= (int)$pf['id'] ?>" <?= ((int)($_GET['property_id'] ?? 0) === (int)$pf['id']) ? 'selected' : '' ?>><?= e($pf['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="status" class="px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <option value="">ทุกสถานะ</option>
        <?php foreach (['pending','confirmed','rejected','cancelled','completed','no_show'] as $st): ?>
          <option value="<?= $st ?>" <?= ($_GET['status']??'')===$st?'selected':'' ?>><?= $st ?></option>
        <?php endforeach; ?>
      </select>
      <button class="px-4 py-2 bg-primary-600 text-white rounded-lg text-sm">กรอง</button>
      <?php $eq = $_GET; unset($eq['page']); $qs = http_build_query($eq); ?>
      <a href="<?= url('/admin/bookings/export.csv') ?><?= $qs !== '' ? '?' . e($qs) : '' ?>" class="px-4 py-2 border border-slate-300 rounded-lg text-sm hover:bg-slate-50 inline-flex items-center gap-1"><i data-lucide="download" class="w-4 h-4"></i> ส่งออก CSV</a>
    </form>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase text-slate-600">
        <tr>
          <th class="text-left px-5 py-3">รหัส</th>
          <th class="text-left px-5 py-3">ผู้จอง</th>
          <th class="text-left px-5 py-3">ที่พัก</th>
          <th class="text-left px-5 py-3">วันที่</th>
          <th class="text-left px-5 py-3">โหมด</th>
          <th class="text-left px-5 py-3">รวม</th>
          <th class="text-left px-5 py-3">คูปอง</th>
          <th class="text-left px-5 py-3">สถานะ</th>
          <th class="text-right px-5 py-3"></th>
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
      foreach ($rows as $b):
        $tone = $bookingToneClass[$b['status']] ?? 'bg-slate-100 text-slate-700';
        $sti = $bookingStatusIcons[$b['status']] ?? 'circle-dot';
        $admBm = (string)($b['mode'] ?? '');
        $admModeIc = ($admBm === 'info_only') ? 'info' : 'calendar-check';
      ?>
        <tr class="hover:bg-slate-50">
          <td class="px-5 py-3 font-mono text-xs text-primary-700"><?= e($b['code']) ?></td>
          <td class="px-5 py-3"><?= e($b['guest_name']) ?><div class="text-xs text-slate-500"><?= e($b['guest_phone']) ?></div></td>
          <td class="px-5 py-3"><?= e($b['property_name']) ?></td>
          <td class="px-5 py-3 text-xs"><?= format_date_th($b['check_in']) ?> → <?= format_date_th($b['check_out']) ?><div class="text-slate-400"><?= $b['nights'] ?> คืน · <?= $b['guest_count'] ?> ท่าน</div></td>
          <td class="px-5 py-3 text-xs">
            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 rounded-full whitespace-nowrap"><i data-lucide="<?= e($admModeIc) ?>" class="w-3.5 h-3.5 shrink-0 text-slate-600"></i><?= e($admBm) ?></span>
          </td>
          <td class="px-5 py-3 font-semibold text-primary-700"><?= format_money($b['total_price']) ?><?php if ($b['discount']>0): ?><div class="text-xs text-rose-600">-<?= format_money($b['discount']) ?></div><?php endif; ?></td>
          <td class="px-5 py-3 text-xs"><?= $b['coupon_code_used'] ? '<span class="font-mono">'.e($b['coupon_code_used']).'</span>' : '-' ?></td>
          <td class="px-5 py-3"><span class="inline-flex items-center gap-1 text-xs font-semibold <?= $tone ?> px-2 py-1 rounded-full"><i data-lucide="<?= e($sti) ?>" class="w-3.5 h-3.5 shrink-0"></i><?= e($b['status']) ?></span></td>
          <td class="px-5 py-3 text-right flex flex-wrap gap-1 justify-end">
            <a href="<?= url('/admin/bookings/' . $b['id']) ?>" class="px-3 py-1.5 text-xs bg-primary-600 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="eye" class="w-3.5 h-3.5"></i> ดู</a>
            <a href="<?= url('/admin/bookings/' . $b['id'] . '/edit') ?>" class="px-3 py-1.5 text-xs border border-slate-300 rounded-lg inline-flex items-center gap-1"><i data-lucide="pencil" class="w-3.5 h-3.5"></i> แก้</a>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?><tr><td colspan="9" class="text-center py-10 text-slate-500"><span class="inline-flex flex-col items-center gap-2"><i data-lucide="inbox" class="w-10 h-10 text-slate-300"></i><span>ไม่มีรายการ</span></span></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="p-4">
    <?php $q=$_GET; unset($q['page']); \App\Core\View::partial('partials/pagination', ['page'=>$page,'totalPages'=>$totalPages,'baseUrl'=>url('/admin/bookings'),'query'=>$q]); ?>
  </div>
</div>
