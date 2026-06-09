<?php /** @var ?array $kb */ ?>
<form method="post" action="<?= url('/admin/ai/kb') ?>" class="max-w-3xl bg-white rounded-2xl border border-slate-200 shadow-soft p-6 space-y-4">
  <?= csrf() ?>
  <?php if (!empty($kb['id'])): ?><input type="hidden" name="id" value="<?= $kb['id'] ?>"><?php endif; ?>

  <h3 class="font-bold text-lg flex items-center gap-2"><i data-lucide="book-plus" class="w-6 h-6 text-purple-600"></i> <?= !empty($kb['id'])?'แก้ไข':'เพิ่ม' ?> Knowledge Base</h3>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
      <label class="text-sm font-medium mb-1 block">หมวด</label>
      <input name="category" value="<?= e($kb['category'] ?? '') ?>" placeholder="booking, coupon, ..." class="w-full px-3 py-2 rounded-lg border border-slate-300">
    </div>
    <div>
      <label class="text-sm font-medium mb-1 block">ลำดับ</label>
      <input type="number" name="sort_order" value="<?= e($kb['sort_order'] ?? 0) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
    </div>
    <div class="flex items-end">
      <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" <?= !isset($kb['is_active']) || $kb['is_active'] ? 'checked' : '' ?> class="rounded">
        <span class="text-sm">เปิดใช้งาน</span>
      </label>
    </div>
  </div>

  <div>
    <label class="text-sm font-medium mb-1 block">คำถาม *</label>
    <input name="question" required value="<?= e($kb['question'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
  </div>
  <div>
    <label class="text-sm font-medium mb-1 block">คำตอบ *</label>
    <textarea name="answer" rows="6" required class="w-full px-3 py-2 rounded-lg border border-slate-300"><?= e($kb['answer'] ?? '') ?></textarea>
  </div>
  <div>
    <label class="text-sm font-medium mb-1 block">Keywords (คั่นด้วย ,)</label>
    <input name="keywords" value="<?= e($kb['keywords'] ?? '') ?>" placeholder="จอง, booking, reserve" class="w-full px-3 py-2 rounded-lg border border-slate-300">
  </div>

  <div class="flex justify-between">
    <a href="<?= url('/admin/ai/kb') ?>" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-lg text-sm">ยกเลิก</a>
    <button class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl">บันทึก</button>
  </div>
</form>
