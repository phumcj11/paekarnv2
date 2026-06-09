<?php
/** @var array $properties @var ?array $coupon @var ?array $check @var array $usages */
$submitted_code = $submitted_code ?? '';
$submitted_phone = $submitted_phone ?? '';
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
  <!-- LEFT: Form -->
  <div class="space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="ticket" class="w-5 h-5 text-rose-500"></i> ตรวจสอบและใช้คูปอง</h3>
      <p class="text-sm text-slate-500 mt-1">กรอกรหัสคูปองของลูกค้าเพื่อหักส่วนลด</p>

      <form method="post" action="<?= url('/owner/coupons/verify') ?>" class="mt-4 space-y-3">
        <?= csrf() ?>
        <div>
          <label class="text-sm font-medium mb-1 block">รหัสคูปอง</label>
          <input type="text" name="code" required value="<?= e($submitted_code) ?>" placeholder="PKAN-XXXX-XXXX" class="w-full px-3 py-3 rounded-lg border-2 border-slate-300 font-mono uppercase tracking-wider text-center text-lg focus:border-accent-500" oninput="this.value=this.value.toUpperCase()">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">เบอร์โทรลูกค้า (ไม่บังคับ)</label>
          <input type="tel" name="phone" value="<?= e($submitted_phone) ?>" placeholder="0812345678" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <p class="text-xs text-slate-500 mt-1">เพื่อยืนยันว่าคูปองตรงกับเจ้าของจริง</p>
        </div>
        <button class="w-full py-2.5 bg-accent-500 hover:bg-accent-600 text-white font-semibold rounded-lg inline-flex items-center justify-center gap-2"><i data-lucide="search" class="w-4 h-4"></i> ตรวจสอบ</button>
      </form>
    </div>

    <!-- Result -->
    <?php if ($check && !$check['ok']): ?>
      <div class="bg-rose-50 border border-rose-200 rounded-2xl p-5 flex gap-3">
        <i data-lucide="alert-circle" class="w-6 h-6 text-rose-600 shrink-0"></i>
        <div>
          <h4 class="font-bold text-rose-700">ใช้คูปองไม่ได้</h4>
          <p class="text-sm text-rose-600 mt-1"><?= e($check['msg']) ?></p>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($coupon): ?>
      <div class="bg-emerald-50 border-2 border-emerald-300 rounded-2xl p-5">
        <div class="flex items-center gap-2 text-emerald-700 mb-3">
          <i data-lucide="check-circle" class="w-5 h-5"></i>
          <h4 class="font-bold">คูปองใช้ได้!</h4>
        </div>
        <div class="bg-white rounded-xl p-4 mb-4">
          <div class="text-xs text-slate-500">รหัส</div>
          <div class="font-mono text-lg font-bold text-accent-700"><?= e($coupon['code']) ?></div>
          <div class="grid grid-cols-2 mt-3 gap-3 text-sm">
            <div><div class="text-xs text-slate-500">มูลค่า</div><div class="font-bold text-emerald-700">฿<?= number_format($coupon['face_value']) ?></div></div>
            <div><div class="text-xs text-slate-500">วันหมดอายุ</div><div class="font-bold"><?= format_date_th($coupon['expires_at']) ?></div></div>
            <div><div class="text-xs text-slate-500">เบอร์ที่ผูก</div><div><?= e($coupon['phone']) ?></div></div>
            <div><div class="text-xs text-slate-500">สถานะ</div><div><?= e($coupon['status']) ?></div></div>
          </div>
        </div>

        <form method="post" action="<?= url('/owner/coupons/use') ?>" class="space-y-3">
          <?= csrf() ?>
          <input type="hidden" name="coupon_id" value="<?= $coupon['id'] ?>">
          <div>
            <label class="text-sm font-medium mb-1 block">เลือกที่พัก</label>
            <select name="property_id" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
              <option value="">— เลือกที่พักของคุณ —</option>
              <?php foreach ($properties as $p): ?>
                <option value="<?= $p['id'] ?>"><?= e($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="text-sm font-medium mb-1 block">รหัสจอง (ไม่บังคับ)</label>
            <input type="number" name="booking_id" placeholder="ใส่ id การจองถ้ามี" class="w-full px-3 py-2 rounded-lg border border-slate-300">
            <p class="text-xs text-slate-500 mt-1">หากไม่ระบุ จะหัก/บันทึกการใช้คูปองโดยไม่ผูกกับการจอง</p>
          </div>
          <button class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg inline-flex items-center justify-center gap-2" onclick="return confirm('ยืนยันใช้คูปองนี้? คูปองจะถูกทำเครื่องหมายว่าใช้แล้วและเปลี่ยนคืนไม่ได้')">
            <i data-lucide="check" class="w-4 h-4"></i> ใช้คูปอง (หัก ฿<?= number_format($coupon['face_value']) ?>)
          </button>
        </form>
      </div>
    <?php endif; ?>
  </div>

  <!-- RIGHT: Recent usages -->
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft">
    <div class="p-5 border-b border-slate-100">
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="history" class="w-5 h-5 text-accent-600"></i> ประวัติการใช้คูปอง</h3>
      <p class="text-xs text-slate-500 mt-1">20 รายการล่าสุด</p>
    </div>
    <div class="p-3 max-h-[500px] overflow-y-auto">
      <?php if (empty($usages)): ?>
        <div class="text-center py-8 text-slate-500 text-sm">ยังไม่มีการใช้คูปอง</div>
      <?php else: ?>
        <ul class="space-y-2">
          <?php foreach ($usages as $u): ?>
          <li class="border border-slate-200 rounded-lg p-3 flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-rose-100 text-rose-600 grid place-items-center"><i data-lucide="ticket" class="w-5 h-5"></i></div>
            <div class="flex-1 min-w-0">
              <div class="font-mono text-xs text-accent-700"><?= e($u['code']) ?></div>
              <div class="text-sm font-semibold truncate"><?= e($u['property_name']) ?></div>
              <div class="text-xs text-slate-500"><?= format_date_th($u['used_at']) ?> · <?= $u['booking_code'] ? 'จอง #'.e($u['booking_code']) : 'ไม่ผูกการจอง' ?></div>
            </div>
            <div class="text-right">
              <div class="font-bold text-emerald-700">-฿<?= number_format($u['amount']) ?></div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>
