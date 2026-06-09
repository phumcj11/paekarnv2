<?php
/** @var ?array<string,mixed> $product */
/** @var array<int,array<string,mixed>> $options */
/** @var array<string,string> $categories */
/** @var list<string> $districtChoices */
/** @var list<string> $zoneChoices */
/** @var bool $isActive */
$isEdit = !empty($product);
$action = $isEdit ? url('/provider/products/' . $product['id']) : url('/provider/products');
$opt = $options[0] ?? [];
$canEdit = $isActive && (!$isEdit || in_array($product['status'] ?? 'draft', ['draft', 'pending_review'], true));
?>
<a href="<?= url('/provider/products') ?>" class="text-sm text-slate-500 hover:text-teal-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</a>

<?php if ($isEdit && ($product['status'] ?? '') === 'published'): ?>
<div class="mb-4 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-sm">
  รายการนี้เผยแพร่แล้ว — การแก้ไขต้องผ่านทีมงาน หรือสร้างฉบับร่างใหม่
</div>
<?php endif; ?>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <?= csrf() ?>
  <div class="lg:col-span-2 space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
      <h2 class="font-bold text-lg">ข้อมูลสินค้า</h2>
      <div>
        <label class="text-sm font-medium mb-1 block">ชื่อสินค้า / บริการ <span class="text-rose-500">*</span></label>
        <input type="text" name="title" required maxlength="220" value="<?= old('title', $product['title'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= $canEdit ? '' : 'readonly' ?>>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">Slug <span class="text-rose-500">*</span></label>
        <input type="text" name="slug" required maxlength="180" value="<?= old('slug', $product['slug'] ?? '') ?>" placeholder="car-rental-kanchanaburi" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-sm" <?= $canEdit ? '' : 'readonly' ?>>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium mb-1 block">หมวด</label>
          <select name="category" class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= $canEdit ? '' : 'disabled' ?>>
            <?php foreach ($categories as $k => $label): ?>
              <option value="<?= e($k) ?>" <?= old('category', $product['category'] ?? 'car_rental') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">อำเภอ</label>
          <select name="district" class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= $canEdit ? '' : 'disabled' ?>>
            <option value="">— ไม่ระบุ —</option>
            <?php foreach ($districtChoices as $d): ?>
              <option value="<?= e($d) ?>" <?= old('district', $product['district'] ?? '') === $d ? 'selected' : '' ?>><?= e($d) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">คำโปรย</label>
        <textarea name="excerpt" rows="2" maxlength="400" class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= $canEdit ? '' : 'readonly' ?>><?= old('excerpt', $product['excerpt'] ?? '') ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">รายละเอียด</label>
        <textarea name="description" rows="6" class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= $canEdit ? '' : 'readonly' ?>><?= old('description', $product['description'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
      <h2 class="font-bold text-lg">ราคา / แพ็กเกจหลัก</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="text-sm font-medium mb-1 block">ราคาเริ่มต้น</label>
          <input type="number" name="base_price" min="0" step="1" value="<?= old('base_price', $product['base_price'] ?? 0) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= $canEdit ? '' : 'readonly' ?>>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">ราคาเต็ม (ขีดฆ่า)</label>
          <input type="number" name="compare_at_price" min="0" step="1" value="<?= old('compare_at_price', $product['compare_at_price'] ?? 0) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= $canEdit ? '' : 'readonly' ?>>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">ระยะเวลา</label>
          <input type="text" name="duration_label" value="<?= old('duration_label', $product['duration_label'] ?? '') ?>" placeholder="1 วัน / 3 ชม." class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= $canEdit ? '' : 'readonly' ?>>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium mb-1 block">ชื่อแพ็กเกจ</label>
          <input type="text" name="option_name" value="<?= old('option_name', $opt['name'] ?? 'แพ็กเกจหลัก') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= $canEdit ? '' : 'readonly' ?>>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">ราคาแพ็กเกจ</label>
          <input type="number" name="option_price" min="0" step="1" value="<?= old('option_price', $opt['price'] ?? ($product['base_price'] ?? 0)) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= $canEdit ? '' : 'readonly' ?>>
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
      <h2 class="font-bold text-lg">รายละเอียดการให้บริการ</h2>
      <input type="text" name="meeting_point" value="<?= old('meeting_point', $product['meeting_point'] ?? '') ?>" placeholder="จุดนัดพบ / รับรถ" class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= $canEdit ? '' : 'readonly' ?>>
      <textarea name="included" rows="2" placeholder="สิ่งที่รวม" class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= $canEdit ? '' : 'readonly' ?>><?= old('included', $product['included'] ?? '') ?></textarea>
      <textarea name="excluded" rows="2" placeholder="สิ่งที่ไม่รวม" class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= $canEdit ? '' : 'readonly' ?>><?= old('excluded', $product['excluded'] ?? '') ?></textarea>
      <textarea name="cancellation_policy" rows="2" placeholder="เงื่อนไขการยกเลิก" class="w-full px-3 py-2 rounded-lg border border-slate-300" <?= $canEdit ? '' : 'readonly' ?>><?= old('cancellation_policy', $product['cancellation_policy'] ?? '') ?></textarea>
    </div>
  </div>

  <aside class="space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
      <?php if ($canEdit): ?>
        <button type="submit" class="w-full py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-semibold">บันทึก</button>
      <?php endif; ?>
      <?php if ($isEdit): ?>
        <div class="text-sm">
          <span class="text-slate-500">สถานะ:</span>
          <span class="font-semibold"><?= e($product['status'] ?? 'draft') ?></span>
        </div>
        <?php if ($canEdit && in_array($product['status'] ?? '', ['draft', 'pending_review'], true)): ?>
          <button type="submit" formaction="<?= url('/provider/products/' . $product['id'] . '/submit-review') ?>"
                  class="w-full py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl font-semibold text-sm">
            ส่งตรวจสอบ
          </button>
        <?php endif; ?>
      <?php else: ?>
        <p class="text-xs text-slate-500">บันทึกเป็น draft ก่อน แล้วกดส่งตรวจเมื่อพร้อม</p>
      <?php endif; ?>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
      <h3 class="font-bold">รูปปก</h3>
      <?php if ($canEdit): ?>
        <input type="url" name="cover_image_url" placeholder="URL รูป" value="<?= old('cover_image_url', '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
        <input type="file" name="cover_image" accept="image/*" class="w-full text-xs">
      <?php endif; ?>
      <?php if (!empty($product['cover_image'])): ?>
        <img src="<?= e(upload_url($product['cover_image'])) ?>" class="rounded-xl border border-slate-200 max-h-48 object-cover w-full" alt="">
      <?php endif; ?>
    </div>
  </aside>
</form>
