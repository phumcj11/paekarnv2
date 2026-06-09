<?php
/** @var array<string,mixed>|null $row */
use App\Models\Setting;

$isEdit = !empty($row);
$isOrderForm = !isset($row) || $isEdit === false;
$action = $isEdit ? url('/admin/coupons/' . (int)$row['id']) : url('/admin/coupons');

$dt = static function (?string $sqlDt): string {
    if (!$sqlDt) return '';
    $t = strtotime($sqlDt);
    return $t ? date('Y-m-d\TH:i', $t) : '';
};

$defaultFace = (int)Setting::get('coupon_face_value', 500);
$defaultSale = (int)Setting::get('coupon_sale_price', 250);
$defaultDays = (int)Setting::get('coupon_validity_days', 90);
?>
<div class="max-w-2xl">
  <a href="<?= $isEdit ? url('/admin/coupons/' . (int)$row['id']) : url('/admin/coupons') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-4"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</a>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-6">
    <h2 class="font-bold text-lg mb-4"><?= $isEdit ? 'แก้ไขคูปอง ' . e($row['code']) : 'ออกคูปองใหม่' ?></h2>

    <?php if ($isEdit && (string)$row['status'] === 'used'): ?>
    <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-4">คูปองถูกใช้แล้ว — แก้ได้เฉพาะสถานะ (admin reset)</p>
    <?php endif; ?>

    <form method="post" action="<?= $action ?>" class="space-y-4"><?= csrf() ?>

    <?php if (!$isEdit): ?>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium block mb-1">ชื่อผู้รับ <span class="text-rose-600">*</span></label>
          <input type="text" name="buyer_name" required maxlength="120" value="<?= e(old('buyer_name', '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium block mb-1">เบอร์โทร <span class="text-rose-600">*</span></label>
          <input type="text" name="buyer_phone" required value="<?= e(old('buyer_phone', '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
      </div>
      <div>
        <label class="text-sm font-medium block mb-1">อีเมล</label>
        <input type="email" name="buyer_email" value="<?= e(old('buyer_email', '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div class="grid sm:grid-cols-3 gap-4">
        <div>
          <label class="text-sm font-medium block mb-1">จำนวนใบ</label>
          <input type="number" name="quantity" min="1" max="20" value="<?= e(old('quantity', '1')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium block mb-1">มูลค่า/ใบ</label>
          <input type="number" step="0.01" name="face_value" value="<?= e(old('face_value', (string)$defaultFace)) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium block mb-1">ราคาขาย/ใบ</label>
          <input type="number" step="0.01" name="sale_price" value="<?= e(old('sale_price', (string)$defaultSale)) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium block mb-1">อายุ (วัน) — ถ้าไม่ระบุวันหมดอายุ</label>
          <input type="number" name="validity_days" min="1" value="<?= e(old('validity_days', (string)$defaultDays)) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium block mb-1">หมดอายุ (กำหนดเอง)</label>
          <input type="datetime-local" name="expires_at" value="<?= e(old('expires_at', '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
      </div>
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="mark_paid" value="1" checked class="rounded">
        บันทึกคำสั่งซื้อเป็นชำระแล้ว (paid)
      </label>
    <?php else: ?>
      <div>
        <label class="text-sm font-medium block mb-1">รหัส</label>
        <input type="text" readonly value="<?= e($row['code']) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 font-mono">
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium block mb-1">เบอร์โทร</label>
          <input type="text" name="phone" value="<?= e(old('phone', $row['phone'])) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= (string)$row['status'] === 'used' ? 'readonly' : '' ?>>
        </div>
        <div>
          <label class="text-sm font-medium block mb-1">สถานะ</label>
          <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300">
            <?php foreach (['unused','reserved','used','expired','revoked','cancelled'] as $st): ?>
            <option value="<?= $st ?>" <?= ($row['status'] ?? '') === $st ? 'selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium block mb-1">มูลค่า</label>
          <input type="number" step="0.01" name="face_value" value="<?= e(old('face_value', (string)$row['face_value'])) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= (string)$row['status'] === 'used' ? 'readonly' : '' ?>>
        </div>
        <div>
          <label class="text-sm font-medium block mb-1">ราคาขาย</label>
          <input type="number" step="0.01" name="sale_price" value="<?= e(old('sale_price', (string)$row['sale_price'])) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= (string)$row['status'] === 'used' ? 'readonly' : '' ?>>
        </div>
      </div>
      <div>
        <label class="text-sm font-medium block mb-1">หมดอายุ</label>
        <input type="datetime-local" name="expires_at" value="<?= e(old('expires_at', $dt($row['expires_at'] ?? null))) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= (string)$row['status'] === 'used' ? 'readonly' : '' ?>>
      </div>
      <div>
        <label class="text-sm font-medium block mb-1">Customer ID (ถ้ามี)</label>
        <input type="number" name="customer_id" value="<?= e(old('customer_id', (string)($row['customer_id'] ?? ''))) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
    <?php endif; ?>

      <button type="submit" class="px-6 py-2.5 bg-accent-500 hover:bg-accent-600 text-white font-semibold rounded-lg inline-flex items-center gap-2"><i data-lucide="save" class="w-4 h-4"></i> บันทึก</button>
    </form>
  </div>
</div>
