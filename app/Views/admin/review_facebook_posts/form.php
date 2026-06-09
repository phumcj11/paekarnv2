<?php
/** @var ?array<string,mixed> $row */
use App\Core\Session;

$isEdit = !empty($row);
$action = $isEdit ? url('/admin/review-facebook-posts/' . $row['id']) : url('/admin/review-facebook-posts');
$oldInput = Session::get('_old', []);
if (array_key_exists('is_active', $oldInput)) {
    $chkActive = !empty($oldInput['is_active']);
} else {
    $chkActive = $row === null ? true : !empty($row['is_active']);
}
?>
<a href="<?= url('/admin/review-facebook-posts') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</a>

<form method="post" action="<?= $action ?>" class="max-w-3xl space-y-4">
  <?= csrf() ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
    <div>
      <label class="text-sm font-medium mb-1 block">ลิงก์โพสต์ Facebook (permalink)</label>
      <input type="url" name="post_url" required maxlength="500" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-sm"
             placeholder="https://www.facebook.com/..."
             value="<?= old('post_url', $isEdit ? (string)($row['post_url'] ?? '') : '') ?>">
      <p class="text-xs text-slate-500 mt-1">ใช้ลิงก์โพสต์ที่เปิดดูได้แบบสาธารณะ · fb.watch อาจฝังไม่ได้ในบางเคส — แนะนำคัดลอกจากเมนู «คัดลอกลิงก์» ของโพสต์บน facebook.com</p>
    </div>
    <div>
      <label class="text-sm font-medium mb-1 block">หัวข้อ (แสดงในหน้าแอดมินและคำอธิบายการ์ด)</label>
      <input type="text" name="title" required maxlength="200" class="w-full px-3 py-2 rounded-lg border border-slate-300"
             value="<?= old('title', $isEdit ? (string)($row['title'] ?? '') : '') ?>">
    </div>
    <div>
      <label class="text-sm font-medium mb-1 block">หมายเหตุ / SEO</label>
      <textarea name="description" rows="3" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm"><?= old('description', $isEdit ? (string)($row['description'] ?? '') : '') ?></textarea>
    </div>
    <div>
      <label class="text-sm font-medium mb-1 block">ลำดับแสดง (เลขน้อยขึ้นก่อน)</label>
      <input type="number" name="sort_order" min="0" class="w-full max-w-xs px-3 py-2 rounded-lg border border-slate-300"
             value="<?= old('sort_order', $isEdit ? (string)(int)($row['sort_order'] ?? 0) : '0') ?>">
    </div>
    <label class="flex items-center gap-2 cursor-pointer">
      <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300"
             <?= $chkActive ? 'checked' : '' ?>>
      <span class="text-sm">แสดงบนเว็บ</span>
    </label>
  </div>
  <button type="submit" class="px-6 py-2.5 bg-accent-500 hover:bg-accent-600 text-white rounded-xl font-semibold">บันทึก</button>
</form>
