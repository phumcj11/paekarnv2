<?php
/** @var ?array $record */
$isEdit = !empty($record);
$action = $isEdit ? url('/admin/owners/' . $record['id']) : url('/admin/owners');
$rec = $record ?? [];
$memDtLocal = static function (?string $db): string {
    if ($db === null || $db === '') {
        return '';
    }
    $ts = strtotime((string)$db);

    return $ts ? date('Y-m-d\TH:i', $ts) : '';
};
?>
<a href="<?= url($isEdit ? '/admin/owners/' . $record['id'] : '/admin/owners') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</a>

<form method="post" action="<?= $action ?>" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <?= csrf() ?>
  <div class="lg:col-span-2 space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="user" class="w-5 h-5 text-accent-600"></i> บัญชีเข้าระบบ</h3>
      <div class="grid md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm font-medium mb-1 block">ชื่อ-นามสกุล</label>
          <input type="text" name="name" required value="<?= e(old('name', $rec['name'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">เบอร์โทร</label>
          <input type="tel" name="phone" required value="<?= e(old('phone', $rec['phone'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">อีเมล (ใช้เข้าระบบ)</label>
        <input type="email" name="email" required value="<?= e(old('email', $rec['email'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div class="grid md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm font-medium mb-1 block">รหัสผ่าน <?= $isEdit ? '<span class="text-slate-400 font-normal">(เว้นว่างถ้าไม่เปลี่ยน)</span>' : '' ?></label>
          <input type="password" name="password" <?= $isEdit ? '' : 'required minlength="8"' ?> autocomplete="new-password" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">ยืนยันรหัสผ่าน</label>
          <input type="password" name="password_confirm" <?= $isEdit ? '' : 'required minlength="8"' ?> autocomplete="new-password" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="briefcase" class="w-5 h-5 text-accent-600"></i> ข้อมูลพาร์ทเนอร์</h3>
      <div>
        <label class="text-sm font-medium mb-1 block">ชื่อกิจการ / ที่พัก</label>
        <input type="text" name="business_name" required value="<?= e(old('business_name', $rec['business_name'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">เลขประจำตัวผู้เสียภาษี (ถ้ามี)</label>
        <input type="text" name="tax_id" value="<?= e(old('tax_id', $rec['tax_id'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div class="grid md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm font-medium mb-1 block">ส่วนลดตามข้อตกลง (%)</label>
          <input type="number" step="0.01" min="0" max="100" name="discount_agreement" value="<?= e(old('discount_agreement', $rec['discount_agreement'] ?? '10')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">ค่าคอมมิชชัน (%)</label>
          <input type="number" step="0.01" min="0" max="100" name="commission_rate" value="<?= e(old('commission_rate', $rec['commission_rate'] ?? '0')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">หมายเหตุภายใน</label>
        <textarea name="notes" rows="3" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm"><?= e(old('notes', $rec['notes'] ?? '')) ?></textarea>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="landmark" class="w-5 h-5 text-accent-600"></i> บัญชีธนาคาร</h3>
      <div class="grid md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm font-medium mb-1 block">ธนาคาร</label>
          <input type="text" name="bank_name" value="<?= e(old('bank_name', $rec['bank_name'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">เลขบัญชี</label>
          <input type="text" name="bank_account" value="<?= e(old('bank_account', $rec['bank_account'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono">
        </div>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">ชื่อบัญชี</label>
        <input type="text" name="bank_holder" value="<?= e(old('bank_holder', $rec['bank_holder'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
    </div>

    <?php if ($isEdit): ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3 border-amber-100 ring-1 ring-amber-100/80">
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="crown" class="w-5 h-5 text-amber-600"></i> สมาชิกเจ้าของแพ (แก้ด้วยตนเอง)</h3>
      <p class="text-xs text-slate-600">ใช้เมื่อต้องการปรับ tier / วันหมดโดยไม่ผ่านคำสั่งซื้อ · เลือก none จะล้างวันหมดและ grace</p>
      <div>
        <label class="text-sm font-medium mb-1 block">Tier</label>
        <select name="membership_tier" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white">
          <?php $mt = old('membership_tier', $rec['membership_tier'] ?? 'none'); ?>
          <?php foreach (['none', 'standard', 'vip'] as $mv): ?>
            <option value="<?= $mv ?>" <?= $mt === $mv ? 'selected' : '' ?>><?= $mv ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="grid md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm font-medium mb-1 block">หมดอายุแพ็กเกจ</label>
          <input type="datetime-local" name="membership_expires_at" value="<?= e(old('membership_expires_at', $memDtLocal($rec['membership_expires_at'] ?? null))) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <p class="text-[11px] text-slate-500 mt-0.5">ว่าง = ตลอดชีพ (เมื่อ tier ไม่ใช่ none)</p>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">Grace ถึง</label>
          <input type="datetime-local" name="membership_grace_until" value="<?= e(old('membership_grace_until', $memDtLocal($rec['membership_grace_until'] ?? null))) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">เหตุผล (audit)</label>
        <input type="text" name="membership_adjust_reason" maxlength="255" value="<?= e(old('membership_adjust_reason')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm" placeholder="เช่น ชดเชยด้วยตนเอง / ต่ออายุให้ลูกค้า">
      </div>
    </div>
    <?php endif; ?>
  </div>

  <aside>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3 lg:sticky lg:top-24">
      <div>
        <label class="text-sm font-medium mb-1 block">สถานะพาร์ทเนอร์</label>
        <select name="partner_status" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <?php
          $stSel = old('partner_status', $rec['partner_status'] ?? 'pending');
          foreach (['pending', 'active', 'paused', 'terminated'] as $st): ?>
            <option value="<?= $st ?>" <?= $stSel === $st ? 'selected' : '' ?>><?= $st ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="w-full py-2.5 bg-accent-500 hover:bg-accent-600 text-white rounded-lg font-semibold"><?= $isEdit ? 'บันทึก' : 'สร้างเจ้าของแพ' ?></button>
    </div>
  </aside>
</form>
