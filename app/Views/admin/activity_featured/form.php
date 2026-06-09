<?php
/** @var array<string,mixed>|null $row */
/** @var list<array<string,mixed>> $products */
$isEdit = !empty($row);
$dStart = !empty($row['starts_at']) ? date('Y-m-d', strtotime((string)$row['starts_at'])) : '';
$dEnd = !empty($row['ends_at']) ? date('Y-m-d', strtotime((string)$row['ends_at'])) : '';
$selPid = (int)old('product_id', $row['product_id'] ?? 0);
?>
<div class="max-w-2xl">
  <a href="<?= url('/admin/activity-featured') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-4"><i data-lucide="arrow-left" class="w-4 h-4"></i> ทั้งหมด</a>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-6">
    <h2 class="font-bold text-lg mb-4"><?= $isEdit ? 'แก้ไขแคมเปญ Featured' : 'เพิ่มแคมเปญ Featured' ?></h2>
    <form method="post" action="<?= $isEdit ? url('/admin/activity-featured/' . (int)$row['id']) : url('/admin/activity-featured') ?>" class="space-y-4"><?= csrf() ?>
      <div>
        <label class="text-sm font-medium block mb-1">สินค้ากิจกรรม <span class="text-rose-600">*</span></label>
        <select name="product_id" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <option value="">— เลือก —</option>
          <?php foreach ($products as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= $selPid === (int)$p['id'] ? 'selected' : '' ?>>
              #<?= (int)$p['id'] ?> <?= e($p['title']) ?> (<?= e($p['status']) ?><?= !empty($p['provider_name']) ? ' · ' . $p['provider_name'] : '' ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium block mb-1">ชื่อแคมเปญ (optional)</label>
        <input type="text" name="title" maxlength="180" value="<?= e(old('title', $row['title'] ?? '')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="เช่น Featured 30 วัน">
      </div>
      <div class="grid sm:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium block mb-1">ราคาที่เก็บ (฿)</label>
          <input type="number" name="price_paid" min="0" step="0.01" value="<?= e(old('price_paid', isset($row['price_paid']) ? (string)$row['price_paid'] : '0')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium block mb-1">Priority boost</label>
          <input type="number" name="priority_boost" min="0" step="1" value="<?= e(old('priority_boost', isset($row['priority_boost']) ? (string)(int)$row['priority_boost'] : '50')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <p class="text-[10px] text-slate-500 mt-1">ยิ่งสูง ยิ่งขึ้นบน /activities</p>
        </div>
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
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" <?= (!$isEdit || !empty($row['is_active'])) ? 'checked' : '' ?> class="rounded">
        เปิดใช้งาน
      </label>
      <button type="submit" class="w-full sm:w-auto px-6 py-2.5 bg-accent-500 hover:bg-accent-600 text-white font-semibold rounded-lg inline-flex items-center justify-center gap-2"><i data-lucide="save" class="w-4 h-4"></i> บันทึก</button>
    </form>
  </div>
</div>
