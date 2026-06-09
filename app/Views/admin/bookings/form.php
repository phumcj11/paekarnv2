<?php
/** @var array<string,mixed>|null $booking @var list<array> $properties @var array<int,list<array>> $unitsByProperty @var string|null $propertyName */
use App\Core\Session;

$isEdit = !empty($booking);
$old = Session::get('_old', []);
$action = $isEdit ? url('/admin/bookings/' . (int)$booking['id']) : url('/admin/bookings');

$val = static function (string $key, $default = '') use ($old, $booking, $isEdit) {
    if (array_key_exists($key, $old)) {
        return $old[$key];
    }
    return $isEdit ? ($booking[$key] ?? $default) : $default;
};

$selProperty = (string)$val('property_id', $isEdit ? (string)$booking['property_id'] : '');
$selUnit = (string)$val('unit_id', $isEdit ? (string)$booking['unit_id'] : '');
$locked = $isEdit && in_array((string)$booking['status'], ['confirmed', 'completed'], true);
?>
<a href="<?= $isEdit ? url('/admin/bookings/' . (int)$booking['id']) : url('/admin/bookings') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</a>

<form method="post" action="<?= $action ?>" class="max-w-3xl space-y-4">
  <?= csrf() ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
    <h2 class="font-bold text-lg"><?= $isEdit ? 'แก้ไขการจอง #' . e($booking['code']) : 'สร้างการจองใหม่' ?></h2>

    <?php if (!$isEdit): ?>
    <div>
      <label class="text-sm font-medium block mb-1">ที่พัก <span class="text-rose-600">*</span></label>
      <select name="property_id" id="bk-property" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <option value="">— เลือกที่พัก —</option>
        <?php foreach ($properties as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= (string)$p['id'] === $selProperty ? 'selected' : '' ?>><?= e($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php else: ?>
    <input type="hidden" name="property_id" value="<?= (int)$booking['property_id'] ?>">
    <p class="text-sm text-slate-600">ที่พัก: <strong><?= e($propertyName ?? '') ?></strong></p>
    <?php endif; ?>

    <div>
      <label class="text-sm font-medium block mb-1">ยูนิต / ห้อง <span class="text-rose-600">*</span></label>
      <select name="unit_id" id="bk-unit" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <option value="">— เลือกยูนิต —</option>
      </select>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium block mb-1">ชื่อผู้จอง <span class="text-rose-600">*</span></label>
        <input type="text" name="guest_name" required maxlength="120" value="<?= e((string)$val('guest_name')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium block mb-1">โทร <span class="text-rose-600">*</span></label>
        <input type="text" name="guest_phone" required value="<?= e((string)$val('guest_phone')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium block mb-1">อีเมล</label>
        <input type="email" name="guest_email" value="<?= e((string)$val('guest_email')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium block mb-1">จำนวนผู้เข้าพัก <span class="text-rose-600">*</span></label>
        <input type="number" name="guest_count" min="1" required value="<?= e((string)$val('guest_count', '2')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium block mb-1">เช็คอิน <span class="text-rose-600">*</span></label>
        <input type="date" name="check_in" required value="<?= e((string)$val('check_in')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium block mb-1">เช็คเอาท์ <span class="text-rose-600">*</span></label>
        <input type="date" name="check_out" required value="<?= e((string)$val('check_out')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
    </div>

    <?php if (!$locked): ?>
    <div>
      <label class="text-sm font-medium block mb-1">รหัสคูปอง (ถ้ามี)</label>
      <input type="text" name="coupon_code" value="<?= e((string)$val('coupon_code', $isEdit ? (string)($booking['coupon_code_used'] ?? '') : '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono" placeholder="PKAN-XXXX-XXXX">
    </div>
    <?php elseif (!empty($booking['coupon_code_used'])): ?>
    <p class="text-sm">คูปอง: <span class="font-mono font-semibold"><?= e($booking['coupon_code_used']) ?></span></p>
    <?php endif; ?>

    <div>
      <label class="text-sm font-medium block mb-1">หมายเหตุ</label>
      <textarea name="notes" rows="3" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm"><?= e((string)$val('notes')) ?></textarea>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium block mb-1">สถานะ</label>
        <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
          <?php foreach (['pending','confirmed','rejected','cancelled','completed','no_show'] as $st): ?>
          <option value="<?= $st ?>" <?= (string)$val('status', 'pending') === $st ? 'selected' : '' ?>><?= $st ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium block mb-1">สถานะชำระเงิน</label>
        <select name="payment_status" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
          <?php foreach (['unpaid','partial','paid','refunded'] as $ps): ?>
          <option value="<?= $ps ?>" <?= (string)$val('payment_status', 'unpaid') === $ps ? 'selected' : '' ?>><?= $ps ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <?php if ($locked): ?>
    <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">การจอง confirmed/completed — ราคาและคูปองล็อก แก้ได้เฉพาะข้อมูลผู้จองและหมายเหตุ</p>
    <?php endif; ?>

    <button type="submit" class="px-6 py-2.5 bg-primary-600 text-white font-semibold rounded-lg inline-flex items-center gap-2"><i data-lucide="save" class="w-4 h-4"></i> บันทึก</button>
  </div>
</form>

<script>
(function () {
  var unitsByProperty = <?= json_encode($unitsByProperty, JSON_UNESCAPED_UNICODE) ?>;
  var propSel = document.getElementById('bk-property');
  var unitSel = document.getElementById('bk-unit');
  var selectedUnit = <?= json_encode($selUnit) ?>;

  function fillUnits(pid) {
    if (!unitSel) return;
    unitSel.innerHTML = '<option value="">— เลือกยูนิต —</option>';
    var list = unitsByProperty[pid] || unitsByProperty[String(pid)] || [];
    list.forEach(function (u) {
      var opt = document.createElement('option');
      opt.value = u.id;
      opt.textContent = u.name + ' (฿' + Number(u.price).toLocaleString() + ')';
      if (String(u.id) === String(selectedUnit)) opt.selected = true;
      unitSel.appendChild(opt);
    });
  }

  var initialPid = propSel ? propSel.value : <?= json_encode($isEdit ? (string)$booking['property_id'] : '') ?>;
  if (initialPid) fillUnits(initialPid);
  if (propSel) {
    propSel.addEventListener('change', function () {
      selectedUnit = '';
      fillUnits(propSel.value);
    });
  }
})();
</script>
