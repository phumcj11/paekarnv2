<?php
/** @var array<string,mixed>|null $row */
$isEdit = !empty($row);
$dt = static function (?string $sqlDt): string {
    if (!$sqlDt) {
        return '';
    }
    $t = strtotime($sqlDt);

    return $t ? date('Y-m-d\TH:i', $t) : '';
};
?>
<div class="max-w-2xl">
  <a href="<?= url('/admin/coupon-campaigns') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-4"><i data-lucide="arrow-left" class="w-4 h-4"></i> ทั้งหมด</a>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-6">
    <h2 class="font-bold text-lg mb-4"><?= $isEdit ? 'แก้ไขแคมเปญ' : 'เพิ่มแคมเปญ' ?></h2>
    <form method="post" action="<?= $isEdit ? url('/admin/coupon-campaigns/' . (int)$row['id']) : url('/admin/coupon-campaigns') ?>" class="space-y-4"><?= csrf() ?>
      <div>
        <label class="text-sm font-medium block mb-1">รหัสแคมเปญ <span class="text-rose-600">*</span></label>
        <input type="text" name="code" required maxlength="64" value="<?= e(old('code', $row['code'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono" placeholder="เช่น TET2026">
        <p class="text-xs text-slate-500 mt-1">A–Z ตัวเลข _ และ - เท่านั้น (จะถูกแปลงเป็นตัวใหญ่)</p>
      </div>
      <div>
        <label class="text-sm font-medium block mb-1">ชื่อแสดง <span class="text-rose-600">*</span></label>
        <input type="text" name="name" required maxlength="180" value="<?= e(old('name', $row['name'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium block mb-1">มูลค่าหน้าบัตร <span class="text-rose-600">*</span></label>
          <input type="number" step="0.01" min="0" name="face_value" required value="<?= e(old('face_value', isset($row['face_value']) ? (string)$row['face_value'] : '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 tabular-nums">
        </div>
        <div>
          <label class="text-sm font-medium block mb-1">ราคาขาย <span class="text-rose-600">*</span></label>
          <input type="number" step="0.01" min="0" name="sale_price" required value="<?= e(old('sale_price', isset($row['sale_price']) ? (string)$row['sale_price'] : '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 tabular-nums">
        </div>
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium block mb-1">เริ่ม</label>
          <input type="datetime-local" name="starts_at" value="<?= e(old('starts_at', $dt($row['starts_at'] ?? null))) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium block mb-1">สิ้นสุด</label>
          <input type="datetime-local" name="ends_at" value="<?= e(old('ends_at', $dt($row['ends_at'] ?? null))) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
      </div>
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" <?= (!$isEdit || !empty($row['is_active'])) ? 'checked' : '' ?> class="rounded">
        เปิดใช้งานแคมเปญนี้
      </label>
      <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-accent-500 hover:bg-accent-600 text-white font-semibold rounded-lg inline-flex items-center justify-center gap-2"><i data-lucide="save" class="w-4 h-4"></i> บันทึก</button>
    </form>
  </div>
</div>
