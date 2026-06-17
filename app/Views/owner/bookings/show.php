<?php /** @var array $b @var array $payments @var bool $canHardDelete */
$colors = ['pending'=>'amber','confirmed'=>'emerald','rejected'=>'rose','cancelled'=>'slate','completed'=>'blue','no_show'=>'slate'];
$c = $colors[$b['status']] ?? 'slate';
$bookingHdrIcons = ['pending'=>'clock','confirmed'=>'check-circle','rejected'=>'x-circle','cancelled'=>'ban','completed'=>'flag','no_show'=>'user-x'];
$hdrIc = $bookingHdrIcons[$b['status']] ?? 'circle-dot';
$bmHdr = (string)($b['mode'] ?? '');
$modeHdrIc = ($bmHdr === 'info_only') ? 'info' : 'calendar-check';
?>
<div class="flex items-center justify-between mb-4">
  <a href="<?= url('/owner/bookings') ?>" class="text-sm text-slate-500 hover:text-accent-700 inline-flex items-center gap-1"><i data-lucide="arrow-left" class="w-4 h-4"></i> รายการจอง</a>
  <span class="text-xs font-semibold bg-<?= $c ?>-100 text-<?= $c ?>-700 px-3 py-1 rounded-full inline-flex items-center gap-1"><i data-lucide="<?= e($hdrIc) ?>" class="w-3.5 h-3.5 shrink-0"></i><?= e($b['status']) ?></span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 space-y-4">

    <!-- Header -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 flex items-start gap-4">
      <img src="<?= e(upload_url($b['property_cover']) ?: 'https://placehold.co/200x150') ?>" class="w-24 h-24 rounded-xl object-cover">
      <div class="flex-1">
        <div class="text-xs text-slate-500">รหัสจอง</div>
        <div class="font-mono text-accent-700 font-bold"><?= e($b['code']) ?></div>
        <h2 class="text-lg font-bold mt-2"><?= e($b['property_name']) ?></h2>
        <div class="text-sm text-slate-500"><?= e($b['unit_name'] ?: 'ไม่ระบุห้อง') ?></div>
      </div>
      <div class="text-right text-sm">
        <div class="text-xs text-slate-500">โหมดการจอง</div>
        <div class="font-bold text-primary-700 inline-flex items-center justify-end gap-1.5 mt-0.5"><i data-lucide="<?= e($modeHdrIc) ?>" class="w-4 h-4 shrink-0 text-accent-600"></i><?= e($bmHdr) ?></div>
      </div>
    </div>

    <?php
    $paidTotal = 0.0;
    foreach ($payments as $p) {
        if (($p['status'] ?? '') === 'verified') {
            $paidTotal += (float)($p['amount'] ?? 0);
        }
    }
    $remaining = max(0, (float)($b['total_price'] ?? 0) - $paidTotal);
    $statusSteps = [
        'pending'   => ['label' => 'รอยืนยัน', 'icon' => 'clock'],
        'confirmed' => ['label' => 'ยืนยันแล้ว', 'icon' => 'check-circle'],
        'completed' => ['label' => 'เข้าพักเสร็จ', 'icon' => 'flag'],
    ];
    $terminalMap = [
        'rejected'  => ['label' => 'ปฏิเสธ', 'icon' => 'x-circle'],
        'cancelled' => ['label' => 'ยกเลิก', 'icon' => 'ban'],
        'no_show'   => ['label' => 'No-show', 'icon' => 'user-x'],
    ];
    $currentStatus = (string)($b['status'] ?? 'pending');
    $flowKeys = array_keys($statusSteps);
    $currentIdx = array_search($currentStatus, $flowKeys, true);
    if ($currentIdx === false && isset($terminalMap[$currentStatus])) {
        $timelineSteps = [$terminalMap[$currentStatus]];
    } else {
        $timelineSteps = [];
        foreach ($statusSteps as $key => $meta) {
            $idx = array_search($key, $flowKeys, true);
            $state = 'upcoming';
            if ($currentIdx !== false) {
                if ($idx < $currentIdx) {
                    $state = 'done';
                } elseif ($idx === $currentIdx) {
                    $state = 'current';
                }
            }
            $timelineSteps[] = array_merge($meta, ['key' => $key, 'state' => $state]);
        }
    }
    ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold mb-4 flex items-center gap-2"><i data-lucide="git-branch" class="w-5 h-5 text-accent-600"></i> สถานะการจอง</h3>
      <ol class="space-y-3">
        <?php foreach ($timelineSteps as $step): ?>
        <?php
          $state = $step['state'] ?? 'current';
          $dotClass = match ($state) {
              'done'    => 'bg-emerald-500 text-white ring-emerald-100',
              'current' => 'bg-accent-600 text-white ring-accent-100',
              default   => 'bg-slate-200 text-slate-500 ring-slate-100',
          };
          $textClass = $state === 'upcoming' ? 'text-slate-400' : 'text-slate-800';
        ?>
        <li class="flex items-start gap-3">
          <span class="w-8 h-8 rounded-full ring-4 grid place-items-center shrink-0 <?= $dotClass ?>">
            <i data-lucide="<?= e($step['icon']) ?>" class="w-4 h-4"></i>
          </span>
          <div class="pt-0.5">
            <div class="text-sm font-semibold <?= $textClass ?>"><?= e($step['label']) ?></div>
            <?php if (($step['state'] ?? '') === 'current'): ?>
              <div class="text-xs text-accent-700 font-medium mt-0.5">สถานะปัจจุบัน</div>
            <?php elseif (($step['state'] ?? '') === 'done'): ?>
              <div class="text-xs text-emerald-600 mt-0.5">ดำเนินการแล้ว</div>
            <?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
      </ol>
      <?php if (!empty($b['created_at'])): ?>
      <p class="text-xs text-slate-500 mt-4 pt-3 border-t border-slate-100">สร้างเมื่อ <?= format_date_th($b['created_at']) ?></p>
      <?php endif; ?>
    </div>

    <!-- Stay -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold mb-3 flex items-center gap-2"><i data-lucide="calendar" class="w-5 h-5 text-accent-600"></i> รายละเอียดการเข้าพัก</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
        <div><div class="text-xs text-slate-500">เช็คอิน</div><div class="font-semibold"><?= format_date_th($b['check_in']) ?></div></div>
        <div><div class="text-xs text-slate-500">เช็คเอาท์</div><div class="font-semibold"><?= format_date_th($b['check_out']) ?></div></div>
        <div><div class="text-xs text-slate-500">จำนวนคืน</div><div class="font-semibold"><?= e($b['nights']) ?> คืน</div></div>
        <div><div class="text-xs text-slate-500">จำนวนผู้เข้าพัก</div><div class="font-semibold"><?= e($b['guest_count']) ?> คน</div></div>
      </div>
      <?php if ($b['notes']): ?>
        <div class="mt-3 p-3 bg-slate-50 rounded-lg text-sm"><strong>หมายเหตุ:</strong> <?= nl2br(e($b['notes'])) ?></div>
      <?php endif; ?>
    </div>

    <!-- Customer -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold mb-3 flex items-center gap-2"><i data-lucide="user" class="w-5 h-5 text-accent-600"></i> ข้อมูลผู้จอง</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
        <div><div class="text-xs text-slate-500">ชื่อ</div><div class="font-semibold"><?= e($b['guest_name']) ?></div></div>
        <div><div class="text-xs text-slate-500">เบอร์โทร</div><div class="font-semibold"><a href="tel:<?= e($b['guest_phone']) ?>" class="text-accent-700"><?= e($b['guest_phone']) ?></a></div></div>
        <div><div class="text-xs text-slate-500">อีเมล</div><div class="font-semibold"><?= e($b['guest_email'] ?: '-') ?></div></div>
      </div>
      <?php if (!empty($b['guest_line_user_id'])): ?>
      <div class="mt-3 p-2.5 bg-teal-50 border border-teal-200 rounded-lg flex items-center gap-2 text-xs text-teal-800">
        <svg class="w-4 h-4 text-[#06C755] shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.03 2 11c0 2.98 1.6 5.6 4.08 7.27L5.5 22l4.15-2.05A10.94 10.94 0 0 0 12 20c5.52 0 10-4.03 10-9S17.52 2 12 2z"/></svg>
        <span class="font-mono"><?= e($b['guest_line_user_id']) ?></span>
      </div>
      <?php endif; ?>
      <div class="mt-3 flex gap-2 flex-wrap">
        <a href="tel:<?= e($b['guest_phone']) ?>" class="px-3 py-2 bg-emerald-500 text-white rounded-lg text-sm inline-flex items-center gap-1.5"><i data-lucide="phone" class="w-4 h-4"></i> โทรหาลูกค้า</a>
        <?php if ($b['guest_email']): ?>
          <a href="mailto:<?= e($b['guest_email']) ?>" class="px-3 py-2 border border-slate-300 hover:bg-slate-50 rounded-lg text-sm inline-flex items-center gap-1.5"><i data-lucide="mail" class="w-4 h-4"></i> ส่งอีเมล</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Payment -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold mb-3 flex items-center gap-2"><i data-lucide="receipt" class="w-5 h-5 text-accent-600"></i> การชำระเงิน</h3>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4 text-sm">
        <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
          <div class="text-xs text-slate-500">ชำระแล้ว</div>
          <div class="font-bold text-emerald-700 tabular-nums"><?= format_money($paidTotal) ?></div>
        </div>
        <div class="rounded-xl bg-slate-50 border border-slate-100 p-3">
          <div class="text-xs text-slate-500">คงเหลือ</div>
          <div class="font-bold text-amber-700 tabular-nums"><?= format_money($remaining) ?></div>
        </div>
        <div class="rounded-xl bg-slate-50 border border-slate-100 p-3 col-span-2 md:col-span-1">
          <div class="text-xs text-slate-500">สถานะการชำระ</div>
          <div class="font-bold text-slate-800"><?= e($b['payment_status']) ?></div>
        </div>
      </div>
      <?php if (empty($payments)): ?>
        <p class="text-sm text-slate-500">ยังไม่มีการชำระเงิน</p>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($payments as $p): ?>
          <div class="border border-slate-200 rounded-lg p-3 flex flex-col md:flex-row gap-3">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <span class="text-sm font-semibold"><?= format_money($p['amount']) ?></span>
                <span class="text-xs px-2 py-0.5 bg-slate-100 rounded-full"><?= e($p['method']) ?></span>
                <span class="text-xs px-2 py-0.5 bg-<?= $p['status']==='verified'?'emerald':($p['status']==='rejected'?'rose':'amber') ?>-100 text-<?= $p['status']==='verified'?'emerald':($p['status']==='rejected'?'rose':'amber') ?>-700 rounded-full"><?= e($p['status']) ?></span>
              </div>
              <div class="text-xs text-slate-500">อ้างอิง: <?= e($p['reference'] ?: '-') ?></div>
              <div class="text-xs text-slate-500"><?= format_date_th($p['created_at']) ?></div>
              <?php if ($p['status'] === 'pending'): ?>
                <form method="post" action="<?= url('/owner/bookings/' . $b['id'] . '/payment') ?>" class="mt-2 inline">
                  <?= csrf() ?>
                  <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                  <button name="action" value="verify" class="px-3 py-1.5 bg-emerald-500 text-white rounded-lg text-xs inline-flex items-center gap-1"><i data-lucide="check" class="w-3.5 h-3.5"></i> ยืนยันสลิป</button>
                  <button name="action" value="reject" class="px-3 py-1.5 bg-rose-500 text-white rounded-lg text-xs inline-flex items-center gap-1" onclick="return confirm('ปฏิเสธสลิปใบนี้?')"><i data-lucide="x" class="w-3.5 h-3.5"></i> ปฏิเสธ</button>
                </form>
              <?php endif; ?>
            </div>
            <?php if ($p['slip_path']): ?>
              <a href="<?= e(upload_url($p['slip_path'])) ?>" target="_blank" class="md:w-32">
                <img src="<?= e(upload_url($p['slip_path'])) ?>" class="w-full aspect-square object-cover rounded-lg border border-slate-200">
              </a>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- Sidebar actions -->
  <aside class="space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold mb-3 flex items-center gap-2"><i data-lucide="calculator" class="w-5 h-5 text-accent-600"></i> สรุปราคา</h3>
      <div class="space-y-1.5 text-sm">
        <div class="flex justify-between"><span class="text-slate-600">ราคารวม</span><span><?= format_money($b['subtotal']) ?></span></div>
        <?php if ($b['discount'] > 0): ?>
          <div class="flex justify-between text-rose-600"><span>ส่วนลดคูปอง<?= $b['coupon_code_used']? ' ('.e($b['coupon_code_used']).')':'' ?></span><span>-<?= format_money($b['discount']) ?></span></div>
        <?php endif; ?>
        <hr class="my-2">
        <div class="flex justify-between text-base font-bold text-primary-700"><span>รวมสุทธิ</span><span><?= format_money($b['total_price']) ?></span></div>
        <div class="flex justify-between text-xs"><span>การชำระ</span><span class="font-semibold"><?= e($b['payment_status']) ?></span></div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold mb-3 flex items-center gap-2"><i data-lucide="zap" class="w-5 h-5 text-accent-600"></i> การดำเนินการ</h3>
      <form method="post" action="<?= url('/owner/bookings/' . $b['id'] . '/status') ?>" class="space-y-2">
        <?= csrf() ?>
        <?php if ($b['status'] === 'pending'): ?>
          <?php if (!empty($b['guest_line_user_id'])): ?>
          <label class="flex items-start gap-2 mb-2 cursor-pointer">
            <input type="checkbox" name="send_line_confirm" value="1" checked class="mt-0.5 rounded accent-teal-500">
            <span class="text-xs text-teal-700 font-medium">ส่งใบยืนยันให้ลูกค้าทาง LINE ด้วย</span>
          </label>
          <?php endif; ?>
          <button name="status" value="confirmed" class="w-full py-2 bg-emerald-500 text-white rounded-lg text-sm font-semibold inline-flex items-center justify-center gap-2"><i data-lucide="check-circle" class="w-4 h-4"></i> ยืนยันการจอง</button>
          <button name="status" value="rejected" onclick="return confirm('ปฏิเสธการจองนี้?')" class="w-full py-2 bg-rose-500 text-white rounded-lg text-sm font-semibold inline-flex items-center justify-center gap-2"><i data-lucide="x-circle" class="w-4 h-4"></i> ปฏิเสธ</button>
        <?php elseif ($b['status'] === 'confirmed'): ?>
          <button name="status" value="completed" class="w-full py-2 bg-blue-500 text-white rounded-lg text-sm font-semibold inline-flex items-center justify-center gap-2"><i data-lucide="flag" class="w-4 h-4"></i> ทำเครื่องหมายว่าเข้าพักเสร็จสิ้น</button>
          <button name="status" value="no_show" onclick="return confirm('ลูกค้าไม่ได้มา?')" class="w-full py-2 bg-slate-500 text-white rounded-lg text-sm font-semibold inline-flex items-center justify-center gap-2"><i data-lucide="user-x" class="w-4 h-4"></i> No-show</button>
        <?php elseif ($b['status'] === 'rejected' || $b['status'] === 'cancelled'): ?>
          <button name="status" value="pending" class="w-full py-2 bg-amber-500 text-white rounded-lg text-sm font-semibold inline-flex items-center justify-center gap-2"><i data-lucide="undo-2" class="w-4 h-4"></i> เปิดเป็นรอยืนยันอีกครั้ง</button>
        <?php else: ?>
          <p class="text-xs text-slate-500 text-center">การจองนี้ปิดสถานะแล้ว</p>
        <?php endif; ?>
      </form>
      <?php if (in_array($b['status'], ['confirmed','completed'], true)):
        $confirmLink = \App\Services\BookingConfirmationService::publicUrl($b);
      ?>
      <a href="<?= e($confirmLink) ?>" target="_blank"
         class="mt-3 w-full py-2 border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-sm font-semibold inline-flex items-center justify-center gap-2 transition">
        <i data-lucide="file-text" class="w-4 h-4"></i> ดูใบยืนยันการจอง
      </a>
      <?php endif; ?>
      <?php if (!empty($canHardDelete)): ?>
      <form method="post" action="<?= url('/owner/bookings/' . $b['id'] . '/delete') ?>"
            onsubmit="return confirm('ลบการจองนี้ถาวร? ไม่สามารถกู้คืนได้ แต่แอดมินเว็บไซต์จะเห็นบันทึกใน Audit log')"
            class="mt-3">
        <?= csrf() ?>
        <button type="submit" class="w-full py-2 bg-rose-600 text-white rounded-lg text-sm font-semibold inline-flex items-center justify-center gap-2">
          <i data-lucide="trash-2" class="w-4 h-4"></i> ลบถาวร
        </button>
      </form>
      <?php endif; ?>
    </div>

    <?php if ($b['coupon_code_used']): ?>
    <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4 text-sm">
      <div class="flex items-center gap-2 font-semibold text-rose-700"><i data-lucide="ticket" class="w-4 h-4"></i> คูปองที่ใช้</div>
      <div class="font-mono mt-1"><?= e($b['coupon_code_used']) ?></div>
      <div class="text-xs mt-1">ส่วนลด: <?= format_money($b['discount']) ?></div>
    </div>
    <?php endif; ?>
  </aside>
</div>
