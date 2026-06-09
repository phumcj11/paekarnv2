<?php
/** @var ?array $record */
$isEdit = !empty($record);
$action = $isEdit ? url('/admin/customers/' . $record['id']) : url('/admin/customers');
$rec = $record ?? [];
?>
<a href="<?= url($isEdit ? '/admin/customers/' . $record['id'] : '/admin/customers') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</a>

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
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="id-card" class="w-5 h-5 text-accent-600"></i> ข้อมูลโปรไฟล์</h3>
      <div class="grid md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm font-medium mb-1 block">เพศ</label>
          <select name="gender" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white">
            <option value="">— ไม่ระบุ —</option>
            <?php
            $gSel = old('gender', $rec['gender'] ?? '');
            foreach (['male' => 'ชาย', 'female' => 'หญิง', 'other' => 'อื่น ๆ'] as $gv => $gl): ?>
              <option value="<?= $gv ?>" <?= $gSel === $gv ? 'selected' : '' ?>><?= e($gl) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">วันเกิด</label>
          <input type="date" name="birthdate" value="<?= e(old('birthdate', !empty($rec['birthdate']) ? substr((string)$rec['birthdate'], 0, 10) : '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">ที่อยู่</label>
        <input type="text" name="address" value="<?= e(old('address', $rec['address'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div class="grid md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm font-medium mb-1 block">จังหวัด</label>
          <input type="text" name="province" value="<?= e(old('province', $rec['province'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">LINE ID</label>
          <input type="text" name="line_id" value="<?= e(old('line_id', $rec['line_id'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
      </div>
    </div>
  </div>

  <aside>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3 lg:sticky lg:top-24">
      <div>
        <label class="text-sm font-medium mb-1 block">สถานะบัญชี</label>
        <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white">
          <?php
          $stSel = old('status', $rec['status'] ?? 'active');
          foreach (['active' => 'ใช้งาน', 'suspended' => 'ระงับ', 'pending' => 'รอยืนยัน'] as $sv => $sl): ?>
            <option value="<?= $sv ?>" <?= $stSel === $sv ? 'selected' : '' ?>><?= e($sl) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="w-full py-2.5 bg-accent-500 hover:bg-accent-600 text-white rounded-lg font-semibold"><?= $isEdit ? 'บันทึก' : 'สร้างลูกค้า' ?></button>
    </div>
  </aside>
</form>
