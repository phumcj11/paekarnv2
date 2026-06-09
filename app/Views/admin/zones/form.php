<?php
/** @var array<string,mixed>|null $zone */
$isEdit = $zone !== null;
$id = $isEdit ? (int)$zone['id'] : 0;
?>
<div class="max-w-lg bg-white rounded-2xl border border-slate-200 shadow-soft p-6">
  <h2 class="font-bold text-lg mb-4"><?= $isEdit ? 'แก้ไขโซน' : 'เพิ่มโซน' ?></h2>
  <form method="post" action="<?= $isEdit ? e(url('/admin/zones/' . $id)) : e(url('/admin/zones')) ?>" class="space-y-4"><?= csrf() ?>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อโซน <span class="text-rose-500">*</span></label>
      <input type="text" name="name" required maxlength="80" value="<?= e($zone['name'] ?? '') ?>"
             class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:border-accent-500 outline-none">
      <p class="text-[11px] text-slate-500 mt-1">ต้องตรงกับที่จะใช้ใน <span class="font-mono">properties.zone</span> · ถ้าแก้ชื่อ ระบบจะอัปเดตที่พักและที่เที่ยวที่ใช้ชื่อเดิมทั้งหมด</p>
    </div>
    <div>
      <label class="block text-sm font-medium text-slate-700 mb-1">ลำดับแสดง (sort_order)</label>
      <input type="number" name="sort_order" value="<?= e((string)($zone['sort_order'] ?? '0')) ?>"
             class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:border-accent-500 outline-none">
      <p class="text-[11px] text-slate-500 mt-1">เลขน้อยขึ้นก่อนใน dropdown</p>
    </div>
    <div class="flex gap-2 pt-2">
      <button type="submit" class="px-5 py-2.5 bg-accent-600 text-white rounded-lg font-semibold"><?= $isEdit ? 'บันทึก' : 'สร้าง' ?></button>
      <a href="<?= url('/admin/zones') ?>" class="px-5 py-2.5 border border-slate-300 rounded-lg font-semibold text-slate-700 hover:bg-slate-50">ยกเลิก</a>
    </div>
  </form>
</div>
