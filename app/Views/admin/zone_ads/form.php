<?php
/** @var array<string,mixed>|null $row @var list<string> $zone_hints */
$isEdit = !empty($row);
$img = $row['image_path'] ?? '';
$dStart = !empty($row['starts_at']) ? date('Y-m-d', strtotime((string)$row['starts_at'])) : '';
$dEnd = !empty($row['ends_at']) ? date('Y-m-d', strtotime((string)$row['ends_at'])) : '';
?>
<div class="max-w-2xl">
  <a href="<?= url('/admin/zone-ads') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-4"><i data-lucide="arrow-left" class="w-4 h-4"></i> ทั้งหมด</a>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-6">
    <h2 class="font-bold text-lg mb-4"><?= $isEdit ? 'แก้ไขโฆษณาโซน' : 'เพิ่มโฆษณาโซน' ?></h2>
    <form method="post" action="<?= $isEdit ? url('/admin/zone-ads/' . (int)$row['id']) : url('/admin/zone-ads') ?>" enctype="multipart/form-data" class="space-y-4"><?= csrf() ?>
      <div>
        <label class="text-sm font-medium block mb-1">ชื่อโซน <span class="text-rose-600">*</span></label>
        <input type="text" name="zone" required maxlength="80" value="<?= e(old('zone', $row['zone'] ?? '')) ?>" list="zone-ad-zone-list" class="w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="ต้องตรงกับ properties.zone">
        <datalist id="zone-ad-zone-list">
          <?php foreach ($zone_hints as $zn): ?>
          <option value="<?= e($zn) ?>"></option>
          <?php endforeach; ?>
        </datalist>
      </div>
      <div>
        <label class="text-sm font-medium block mb-1">หัวข้อ (caption)</label>
        <input type="text" name="title" maxlength="180" value="<?= e(old('title', $row['title'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium block mb-1">ลิงก์เมื่อคลิก</label>
        <input type="text" name="link_url" maxlength="500" value="<?= e(old('link_url', $row['link_url'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="/properties หรือ https://...">
      </div>
      <div>
        <label class="text-sm font-medium block mb-1">รหัสที่พักผู้ซื้อโฆษณา (ไม่บังคับ)</label>
        <input type="number" name="property_id" min="1" step="1" value="<?= e(old('property_id', isset($row['property_id']) && $row['property_id'] !== null ? (string)(int)$row['property_id'] : '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 tabular-nums" placeholder="ว่างได้">
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium block mb-1">เริ่ม (วันที่)</label>
          <input type="date" name="starts_at" value="<?= e(old('starts_at', $dStart)) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium block mb-1">สิ้นสุด (วันที่)</label>
          <input type="date" name="ends_at" value="<?= e(old('ends_at', $dEnd)) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
      </div>
      <div>
        <label class="text-sm font-medium block mb-1">ลำดับการแสดง</label>
        <input type="number" name="sort_order" step="1" value="<?= e(old('sort_order', isset($row['sort_order']) ? (string)(int)$row['sort_order'] : '0')) ?>" class="w-32 px-3 py-2 rounded-lg border border-slate-300 tabular-nums">
      </div>
      <div>
        <label class="text-sm font-medium block mb-1">รูป <?= $isEdit ? '(ไม่เลือก = คงเดิม)' : '' ?></label>
        <?php if ($isEdit && $img !== ''): ?>
        <p class="text-xs text-slate-500 mb-2">ปัจจุบัน: <span class="font-mono break-all"><?= e($img) ?></span></p>
        <?php if (!preg_match('#^https?://#i', (string)$img)): ?>
        <img src="<?= e(upload_url((string)$img)) ?>" alt="" class="max-h-28 rounded-lg border border-slate-200 mb-2">
        <?php endif; ?>
        <?php endif; ?>
        <input type="text" name="image_url" maxlength="500" value="<?= e(old('image_url', '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 mb-2" placeholder="หรือใส่ URL รูปแทนการอัปโหลด">
        <input type="file" name="image" accept="image/*" class="w-full text-sm">
      </div>
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" <?= (!$isEdit || !empty($row['is_active'])) ? 'checked' : '' ?> class="rounded">
        เปิดใช้งาน
      </label>
      <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-accent-500 hover:bg-accent-600 text-white font-semibold rounded-lg inline-flex items-center justify-center gap-2"><i data-lucide="save" class="w-4 h-4"></i> บันทึก</button>
    </form>
  </div>
</div>
