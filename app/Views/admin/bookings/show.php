<?php /** @var array $booking @var array $payments @var bool $canHardDelete */
$admStColors = ['pending'=>'amber','confirmed'=>'emerald','rejected'=>'rose','cancelled'=>'slate','completed'=>'blue','no_show'=>'slate'];
$admC = $admStColors[$booking['status']] ?? 'slate';
$admStIcons = ['pending'=>'clock','confirmed'=>'check-circle','rejected'=>'x-circle','cancelled'=>'ban','completed'=>'flag','no_show'=>'user-x'];
$admStIc = $admStIcons[$booking['status']] ?? 'circle-dot';
$admBm = (string)($booking['mode'] ?? '');
$admModeIc = ($admBm === 'info_only') ? 'info' : 'calendar-check';
?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-3">
  <a href="<?= url('/admin/bookings') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1"><i data-lucide="arrow-left" class="w-4 h-4"></i> ทั้งหมด</a>
  <div class="flex flex-wrap gap-2">
    <a href="<?= url('/admin/bookings/' . $booking['id'] . '/edit') ?>" class="px-4 py-2 border border-slate-300 rounded-lg text-sm inline-flex items-center gap-1 hover:bg-slate-50"><i data-lucide="pencil" class="w-4 h-4"></i> แก้ไข</a>
    <form method="post" action="<?= url('/admin/bookings/' . $booking['id'] . '/delete') ?>" onsubmit="return confirm('ยกเลิกการจองนี้?')"><?= csrf() ?>
      <button type="submit" class="px-4 py-2 border border-rose-200 text-rose-700 rounded-lg text-sm inline-flex items-center gap-1 hover:bg-rose-50"><i data-lucide="ban" class="w-4 h-4"></i> ยกเลิก</button>
    </form>
    <?php if ($canHardDelete): ?>
    <form method="post" action="<?= url('/admin/bookings/' . $booking['id'] . '/delete') ?>" onsubmit="return confirm('ลบถาวร? ไม่สามารถกู้คืนได้')"><?= csrf() ?>
      <input type="hidden" name="hard_delete" value="1">
      <button type="submit" class="px-4 py-2 bg-rose-600 text-white rounded-lg text-sm inline-flex items-center gap-1"><i data-lucide="trash-2" class="w-4 h-4"></i> ลบถาวร</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
    <div class="flex items-start justify-between flex-wrap gap-3">
      <div>
        <div class="text-xs text-slate-500">รหัสการจอง</div>
        <div class="font-mono text-2xl font-bold text-primary-700"><?= e($booking['code']) ?></div>
        <div class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 rounded-full text-xs font-semibold text-slate-800">
          <i data-lucide="<?= e($admModeIc) ?>" class="w-3.5 h-3.5 text-slate-600"></i>
          โหมด <?= e($admBm !== '' ? $admBm : '—') ?>
        </div>
      </div>
      <span class="text-xs font-semibold bg-<?= $admC ?>-100 text-<?= $admC ?>-700 px-3 py-1 rounded-full inline-flex items-center gap-1 shrink-0"><i data-lucide="<?= e($admStIc) ?>" class="w-3.5 h-3.5 shrink-0"></i><?= e($booking['status']) ?></span>
    </div>

    <hr class="my-4">

    <h3 class="font-bold mb-2">ข้อมูลผู้จอง</h3>
    <div class="grid grid-cols-2 gap-3 text-sm">
      <div><div class="text-xs text-slate-500">ชื่อ</div><div class="font-medium"><?= e($booking['guest_name']) ?></div></div>
      <div><div class="text-xs text-slate-500">โทร</div><div class="font-medium"><?= e($booking['guest_phone']) ?></div></div>
      <div><div class="text-xs text-slate-500">อีเมล</div><div class="font-medium"><?= e($booking['guest_email'] ?? '-') ?></div></div>
      <div><div class="text-xs text-slate-500">จำนวน</div><div class="font-medium"><?= $booking['guest_count'] ?> ท่าน</div></div>
    </div>

    <hr class="my-4">

    <h3 class="font-bold mb-2">ที่พัก</h3>
    <div class="text-sm">
      <a href="<?= url('/admin/properties/' . $booking['property_id']) ?>" class="font-semibold text-primary-700 hover:text-accent-600"><?= e($booking['property_name']) ?></a>
      <div class="text-slate-500"><?= e($booking['unit_name']) ?></div>
    </div>
    <div class="grid grid-cols-3 gap-3 text-sm mt-3">
      <div><div class="text-xs text-slate-500">เช็คอิน</div><div class="font-medium"><?= format_date_th($booking['check_in']) ?></div></div>
      <div><div class="text-xs text-slate-500">เช็คเอาท์</div><div class="font-medium"><?= format_date_th($booking['check_out']) ?></div></div>
      <div><div class="text-xs text-slate-500">คืน</div><div class="font-medium"><?= $booking['nights'] ?></div></div>
    </div>

    <hr class="my-4">

    <h3 class="font-bold mb-2">สรุปเงิน</h3>
    <div class="text-sm space-y-1">
      <div class="flex justify-between"><span>ค่าที่พัก</span><span><?= format_money($booking['subtotal']) ?></span></div>
      <?php if ($booking['discount']>0): ?><div class="flex justify-between text-rose-600"><span>ส่วนลด (<?= e($booking['coupon_code_used']) ?>)</span><span>-<?= format_money($booking['discount']) ?></span></div><?php endif; ?>
      <div class="flex justify-between font-bold text-lg text-primary-700 pt-2 border-t"><span>รวม</span><span><?= format_money($booking['total_price']) ?></span></div>
      <div class="text-xs text-slate-500 pt-1">ชำระเงิน: <?= e($booking['payment_status']) ?></div>
    </div>

    <?php if (!empty($booking['notes'])): ?>
    <hr class="my-4">
    <h3 class="font-bold mb-2">หมายเหตุ</h3>
    <p class="text-sm text-slate-700 whitespace-pre-line bg-slate-50 p-3 rounded-lg"><?= e($booking['notes']) ?></p>
    <?php endif; ?>
  </div>

  <div class="space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold mb-3 flex items-center gap-2"><i data-lucide="check-circle" class="w-5 h-5 text-accent-600"></i> เปลี่ยนสถานะ</h3>
      <form method="post" action="<?= url('/admin/bookings/' . $booking['id'] . '/status') ?>" class="space-y-2"><?= csrf() ?>
        <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
          <?php foreach (['pending','confirmed','rejected','cancelled','completed','no_show'] as $st): ?>
            <option value="<?= $st ?>" <?= $booking['status']===$st?'selected':'' ?>><?= $st ?></option>
          <?php endforeach; ?>
        </select>
        <button class="w-full py-2 bg-primary-600 text-white rounded-lg text-sm font-semibold">บันทึก</button>
      </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold mb-3 flex items-center gap-2"><i data-lucide="receipt" class="w-5 h-5 text-accent-600"></i> สลิป / การชำระเงิน</h3>
      <?php if (empty($payments)): ?>
        <div class="text-sm text-slate-500">ยังไม่มีการชำระเงิน</div>
      <?php else: ?>
        <?php foreach ($payments as $p): ?>
        <div class="border border-slate-200 rounded-lg p-3 mb-2">
          <div class="text-sm font-semibold"><?= format_money($p['amount']) ?> · <?= e($p['method']) ?></div>
          <div class="text-xs text-slate-500"><?= format_date_th($p['paid_at']) ?> · <?= e($p['status']) ?></div>
          <?php if ($p['slip_path']): ?>
            <a href="<?= e(upload_url($p['slip_path'])) ?>" target="_blank" class="block mt-2">
              <img src="<?= e(upload_url($p['slip_path'])) ?>" class="rounded border border-slate-200 max-h-48 object-contain w-full bg-slate-50">
            </a>
          <?php endif; ?>
          <?php if ($p['status'] === 'pending'): ?>
          <div class="flex gap-2 mt-2">
            <form method="post" action="<?= url('/admin/bookings/' . $booking['id'] . '/payment') ?>" class="inline"><?= csrf() ?>
              <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
              <input type="hidden" name="action" value="verify">
              <button class="px-3 py-1 text-xs bg-emerald-600 text-white rounded-lg">ยืนยันสลิป</button>
            </form>
            <form method="post" action="<?= url('/admin/bookings/' . $booking['id'] . '/payment') ?>" class="inline"><?= csrf() ?>
              <input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>">
              <input type="hidden" name="action" value="reject">
              <button class="px-3 py-1 text-xs border border-rose-300 text-rose-700 rounded-lg">ปฏิเสธ</button>
            </form>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
