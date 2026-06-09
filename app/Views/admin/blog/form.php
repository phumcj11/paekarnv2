<?php
/** @var ?array $post */
$isEdit = !empty($post);
$action = $isEdit ? url('/admin/blog/' . $post['id']) : url('/admin/blog');
?>
<a href="<?= url('/admin/blog') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</a>
<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <?= csrf() ?>
  <div class="lg:col-span-2 space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
      <div>
        <label class="text-sm font-medium mb-1 block">หัวข้อ</label>
        <input type="text" name="title" required value="<?= e($post['title'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">Slug (URL)</label>
        <input type="text" name="slug" required value="<?= e($post['slug'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">บทคัดย่อ</label>
        <textarea name="excerpt" rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-300"><?= e($post['excerpt'] ?? '') ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">เนื้อหา (HTML)</label>
        <textarea name="content" rows="14" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-sm"><?= e($post['content'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="search" class="w-5 h-5 text-accent-600"></i> SEO</h3>
      <p class="text-xs text-slate-600 leading-relaxed">
        ใช้สำหรับ Google / แชร์โซเชียล · Title / Description ควรมี<strong>คีย์เวิร์ดทริปหรือโซน</strong> (เช่น แพเขื่อนศรีนครินทร์, ที่พัก 10 คน) · ชุดบทความแนะนำใช้รูปแบบซ้ำได้ เช่น «10 แพ… น่านอน» «รีวิว…» เพื่อ cluster SEO · ถ้าว่างจะใช้หัวข้อและบทคัดย่อแทน
      </p>
      <div>
        <label class="text-sm font-medium mb-1 block">Meta Title</label>
        <input type="text" name="meta_title" value="<?= e($post['meta_title'] ?? '') ?>" maxlength="255" class="w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="เช่น 10 แพกาญน่านอน — คู่มือจากแพกาญ.com">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">Meta Description</label>
        <textarea name="meta_description" rows="2" maxlength="500" class="w-full px-3 py-2 rounded-lg border border-slate-300"><?= e($post['meta_description'] ?? '') ?></textarea>
      </div>
    </div>
  </div>

  <aside>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3 lg:sticky lg:top-24">
      <div>
        <label class="text-sm font-medium mb-1 block">รูปหน้าปก</label>
        <input type="file" name="cover_image" accept="image/*" class="w-full text-sm">
        <?php if (!empty($post['cover_image'])): ?>
          <img src="<?= e(upload_url($post['cover_image'])) ?>" class="mt-2 rounded-lg w-full aspect-[16/9] object-cover">
        <?php endif; ?>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">หมวดหมู่</label>
        <input type="text" name="category" value="<?= e($post['category'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">Tags (คั่นด้วย , )</label>
        <input type="text" name="tags" value="<?= e($post['tags'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">สถานะ</label>
        <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <?php foreach (['draft','published','archived'] as $st): ?>
            <option value="<?= $st ?>" <?= ($post['status'] ?? 'draft')===$st?'selected':'' ?>><?= $st ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="w-full py-2.5 bg-accent-500 hover:bg-accent-600 text-white rounded-lg font-semibold">บันทึก</button>
    </div>
  </aside>
</form>
