<?php
/** @var ?array<string,mixed> $row */
/** @var list<array{id:int,name:string}> $properties */
use App\Core\Session;

$isEdit = !empty($row);
$action = $isEdit ? url('/admin/reviews/' . (int)$row['id']) : url('/admin/reviews');
$oldInput = Session::get('_old', []);

$selP = isset($oldInput['property_id'])
    ? (string) $oldInput['property_id']
    : ($isEdit ? (string) ($row['property_id'] ?? '') : '');
$nameVal = isset($oldInput['reviewer_name'])
    ? (string) $oldInput['reviewer_name']
    : ($isEdit ? (string) ($row['reviewer_name'] ?? '') : '');
$ratingVal = isset($oldInput['rating'])
    ? (string) (int) $oldInput['rating']
    : ($isEdit ? (string) (int) ($row['rating'] ?? 5) : '5');
$titleVal = isset($oldInput['title'])
    ? (string) $oldInput['title']
    : ($isEdit ? (string) ($row['title'] ?? '') : '');
$contentVal = isset($oldInput['content'])
    ? (string) $oldInput['content']
    : ($isEdit ? (string) ($row['content'] ?? '') : '');
if (array_key_exists('is_verified', $oldInput)) {
    $chkVerified = !empty($oldInput['is_verified']);
} else {
    $chkVerified = $isEdit ? !empty($row['is_verified']) : false;
}
if (array_key_exists('is_approved', $oldInput)) {
    $chkApproved = !empty($oldInput['is_approved']);
} else {
    $chkApproved = $isEdit ? !empty($row['is_approved']) : true;
}
?>
<a href="<?= url('/admin/reviews') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับรายการรีวิว</a>

<form method="post" action="<?= $action ?>" class="max-w-3xl space-y-4">
  <?= csrf() ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
    <div>
      <label class="text-sm font-medium mb-1 block">ที่พัก <span class="text-rose-500">*</span></label>
      <select name="property_id" required class="w-full px-3 py-2 rounded-lg border border-slate-300">
        <option value="">— เลือกที่พัก —</option>
        <?php foreach ($properties as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= (string)$p['id'] === $selP ? 'selected' : '' ?>><?= e($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm font-medium mb-1 block">ชื่อผู้รีวิว <span class="text-rose-500">*</span></label>
      <input type="text" name="reviewer_name" required maxlength="120" class="w-full px-3 py-2 rounded-lg border border-slate-300"
             value="<?= e($nameVal) ?>">
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium mb-1 block">คะแนน <span class="text-rose-500">*</span></label>
        <select name="rating" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <?php for ($s = 5; $s >= 1; $s--): ?>
          <option value="<?= $s ?>" <?= $ratingVal === (string)$s ? 'selected' : '' ?>><?= $s ?> ดาว</option>
          <?php endfor; ?>
        </select>
      </div>
    </div>
    <div>
      <label class="text-sm font-medium mb-1 block">หัวข้อรีวิว</label>
      <input type="text" name="title" maxlength="160" class="w-full px-3 py-2 rounded-lg border border-slate-300"
             value="<?= e($titleVal) ?>">
    </div>
    <div>
      <label class="text-sm font-medium mb-1 block">เนื้อหารีวิว</label>
      <textarea name="content" rows="6" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm"><?= e($contentVal) ?></textarea>
    </div>
    <label class="flex items-center gap-2 cursor-pointer">
      <input type="checkbox" name="is_verified" value="1" class="rounded border-slate-300" <?= $chkVerified ? 'checked' : '' ?>>
      <span class="text-sm">แท็ก Verified (แสดงว่ามีการเข้าพักจริง)</span>
    </label>
    <label class="flex items-center gap-2 cursor-pointer">
      <input type="checkbox" name="is_approved" value="1" class="rounded border-slate-300" <?= $chkApproved ? 'checked' : '' ?>>
      <span class="text-sm">อนุมัติให้แสดงบนเว็บทันที</span>
    </label>
    <?php if ($isEdit && (!empty($row['customer_id']) || !empty($row['booking_id']))): ?>
    <p class="text-xs text-slate-500 border-t border-slate-100 pt-3">
      เชื่อมบัญชีลูกค้า / การจองจากระบบยังคงอยู่ — ฟอร์มนี้ไม่แก้รหัสเหล่านั้น
      <?php if (!empty($row['booking_id'])): ?> · booking_id: <?= (int)$row['booking_id'] ?><?php endif; ?>
      <?php if (!empty($row['customer_id'])): ?> · customer_id: <?= (int)$row['customer_id'] ?><?php endif; ?>
    </p>
    <?php endif; ?>
  </div>
  <button type="submit" class="px-6 py-2.5 bg-accent-500 hover:bg-accent-600 text-white rounded-xl font-semibold"><?= $isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มรีวิว' ?></button>
</form>
