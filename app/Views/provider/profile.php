<?php
/** @var array<string,mixed>|null $provider */
/** @var bool $isActive */
?>
<form method="post" action="<?= url('/provider/profile') ?>" enctype="multipart/form-data" class="max-w-2xl space-y-4">
  <?= csrf() ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
    <h2 class="font-bold text-lg">โลโก้ / รูปธุรกิจ</h2>
    <p class="text-xs text-slate-500">แสดงบนหน้ารายการกิจกรรมของคุณ (ถ้ามี)</p>
    <?php if (!empty($provider['logo_image'])): ?>
      <img src="<?= e(upload_url($provider['logo_image'])) ?>" alt="" class="rounded-xl border border-slate-200 max-h-40 object-contain bg-slate-50">
      <label class="inline-flex items-center gap-2 text-sm text-slate-600">
        <input type="checkbox" name="remove_logo" value="1" class="rounded border-slate-300"> ลบรูปปัจจุบัน
      </label>
    <?php endif; ?>
    <input type="file" name="logo_image" accept="image/*" class="w-full text-sm">
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
    <h2 class="font-bold text-lg">ข้อมูลติดต่อ</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium mb-1 block">ชื่อผู้ติดต่อ</label>
        <input type="text" name="contact_name" value="<?= old('contact_name', $provider['contact_name'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">โทรศัพท์</label>
        <input type="tel" name="phone" value="<?= old('phone', $provider['phone'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">LINE ID</label>
        <input type="text" name="line_id" value="<?= old('line_id', $provider['line_id'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">อีเมล</label>
        <input type="email" name="email" value="<?= old('email', $provider['email'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
    </div>
    <div>
      <label class="text-sm font-medium mb-1 block">ที่อยู่ / จุดให้บริการ</label>
      <textarea name="address" rows="3" class="w-full px-3 py-2 rounded-lg border border-slate-300"><?= old('address', $provider['address'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
    <h2 class="font-bold text-lg">บัญชีธนาคาร (สำหรับรับเงินในอนาคต)</h2>
    <p class="text-xs text-slate-500">ข้อมูลนี้ใช้สำหรับการโอนรายได้ — ยังไม่มีการโอนอัตโนมัติใน Phase นี้</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium mb-1 block">ธนาคาร</label>
        <input type="text" name="bank_name" value="<?= old('bank_name', $provider['bank_name'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">เลขบัญชี</label>
        <input type="text" name="bank_account" value="<?= old('bank_account', $provider['bank_account'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
    </div>
    <div>
      <label class="text-sm font-medium mb-1 block">ชื่อบัญชี</label>
      <input type="text" name="bank_holder" value="<?= old('bank_holder', $provider['bank_holder'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
    </div>
  </div>

  <button type="submit" class="px-6 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-semibold">บันทึก</button>
</form>
