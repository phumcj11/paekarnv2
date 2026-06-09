<?php
/** @var ?array<string,mixed> $product */
/** @var array<int,array<string,mixed>> $providers */
/** @var array<int,array<string,mixed>> $places */
/** @var array<int,array<string,mixed>> $options */
/** @var array<string,string> $categories */
/** @var array<string,string> $modes */
/** @var list<string> $districtChoices */
/** @var list<string> $zoneChoices */
$isEdit = !empty($product);
$action = $isEdit ? url('/admin/activity-products/' . $product['id']) : url('/admin/activity-products');
$oldInput = \App\Core\Session::get('_old', []);
$categoryVal = (string)($oldInput['category'] ?? ($product['category'] ?? 'tour'));
$modeVal = (string)($oldInput['booking_mode'] ?? ($product['booking_mode'] ?? 'lead'));
$statusVal = (string)($oldInput['status'] ?? ($product['status'] ?? 'draft'));
$districtVal = (string)($oldInput['district'] ?? ($product['district'] ?? ''));
$zoneVal = (string)($oldInput['zone'] ?? ($product['zone'] ?? ''));
$providerVal = (int)($oldInput['provider_id'] ?? ($product['provider_id'] ?? 0));
$placeVal = (int)($oldInput['place_id'] ?? ($product['place_id'] ?? 0));
$opt = $options[0] ?? [];
?>
<a href="<?= url('/admin/activity-products') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</a>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <?= csrf() ?>
  <div class="lg:col-span-2 space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
      <h2 class="font-bold text-lg">ข้อมูลสินค้า</h2>
      <div>
        <label class="text-sm font-medium mb-1 block">ชื่อสินค้า / กิจกรรม</label>
        <input type="text" name="title" required maxlength="220" value="<?= old('title', $product['title'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">Slug</label>
        <input type="text" name="slug" required maxlength="180" value="<?= old('slug', $product['slug'] ?? '') ?>" placeholder="erawan-waterfall-private-tour" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-sm">
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium mb-1 block">หมวด</label>
          <select name="category" class="w-full px-3 py-2 rounded-lg border border-slate-300">
            <?php foreach ($categories as $k => $label): ?><option value="<?= e($k) ?>" <?= $categoryVal === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">รูปแบบขาย</label>
          <select name="booking_mode" class="w-full px-3 py-2 rounded-lg border border-slate-300">
            <?php foreach ($modes as $k => $label): ?><option value="<?= e($k) ?>" <?= $modeVal === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">ผู้ให้บริการ</label>
          <select name="provider_id" class="w-full px-3 py-2 rounded-lg border border-slate-300">
            <option value="">— ไม่ระบุ —</option>
            <?php foreach ($providers as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $providerVal === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">ผูกสถานที่ท่องเที่ยว</label>
          <select name="place_id" class="w-full px-3 py-2 rounded-lg border border-slate-300">
            <option value="">— ไม่ผูก —</option>
            <?php foreach ($places as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $placeVal === (int)$p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?><?= !empty($p['district']) ? ' · ' . e($p['district']) : '' ?></option><?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">อำเภอ</label>
          <select name="district" class="w-full px-3 py-2 rounded-lg border border-slate-300">
            <option value="">— ไม่ระบุ —</option>
            <?php foreach ($districtChoices as $d): ?><option value="<?= e($d) ?>" <?= $districtVal === $d ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">โซน</label>
          <select name="zone" class="w-full px-3 py-2 rounded-lg border border-slate-300">
            <option value="">— ไม่ระบุ —</option>
            <?php foreach ($zoneChoices as $z): ?><option value="<?= e($z) ?>" <?= $zoneVal === $z ? 'selected' : '' ?>><?= e($z) ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">คำโปรย</label>
        <textarea name="excerpt" rows="2" maxlength="400" class="w-full px-3 py-2 rounded-lg border border-slate-300"><?= old('excerpt', $product['excerpt'] ?? '') ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">รายละเอียด</label>
        <textarea name="description" rows="7" class="w-full px-3 py-2 rounded-lg border border-slate-300"><?= old('description', $product['description'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
      <h2 class="font-bold text-lg">ราคา / Option หลัก</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="text-sm font-medium mb-1 block">ราคาเริ่มต้น</label>
          <input type="number" name="base_price" min="0" step="1" value="<?= old('base_price', $product['base_price'] ?? 0) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">ราคาเต็ม (ขีดฆ่า)</label>
          <input type="number" name="compare_at_price" min="0" step="1" value="<?= old('compare_at_price', $product['compare_at_price'] ?? 0) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">ระยะเวลา</label>
          <input type="text" name="duration_label" value="<?= old('duration_label', $product['duration_label'] ?? '') ?>" placeholder="ครึ่งวัน / 3 ชม." class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="text-sm font-medium mb-1 block">ชื่อ Option</label>
          <input type="text" name="option_name" value="<?= old('option_name', $opt['name'] ?? 'แพ็กเกจหลัก') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">ราคา Option</label>
          <input type="number" name="option_price" min="0" step="1" value="<?= old('option_price', $opt['price'] ?? ($product['base_price'] ?? 0)) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">จำนวนสูงสุด</label>
          <input type="number" name="option_max_qty" min="1" value="<?= old('option_max_qty', $opt['max_qty'] ?? 20) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
      </div>
      <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="option_is_active" value="1" <?= !isset($opt['is_active']) || !empty($opt['is_active']) ? 'checked' : '' ?>> เปิดขาย option นี้
      </label>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
      <h2 class="font-bold text-lg">รายละเอียดการเดินทาง</h2>
      <input type="text" name="meeting_point" value="<?= old('meeting_point', $product['meeting_point'] ?? '') ?>" placeholder="จุดนัดพบ" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      <textarea name="included" rows="3" placeholder="สิ่งที่รวมในแพ็กเกจ" class="w-full px-3 py-2 rounded-lg border border-slate-300"><?= old('included', $product['included'] ?? '') ?></textarea>
      <textarea name="excluded" rows="3" placeholder="สิ่งที่ไม่รวม" class="w-full px-3 py-2 rounded-lg border border-slate-300"><?= old('excluded', $product['excluded'] ?? '') ?></textarea>
      <textarea name="cancellation_policy" rows="3" placeholder="เงื่อนไขการยกเลิก" class="w-full px-3 py-2 rounded-lg border border-slate-300"><?= old('cancellation_policy', $product['cancellation_policy'] ?? '') ?></textarea>
    </div>
  </div>

  <aside class="space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
      <button class="w-full py-2.5 bg-accent-500 hover:bg-accent-600 text-white rounded-xl font-semibold">บันทึก</button>
      <div>
        <label class="text-sm font-medium mb-1 block">สถานะ</label>
        <select name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <option value="draft" <?= $statusVal === 'draft' ? 'selected' : '' ?>>Draft</option>
          <option value="pending_review" <?= $statusVal === 'pending_review' ? 'selected' : '' ?>>รอตรวจสอบ</option>
          <option value="published" <?= $statusVal === 'published' ? 'selected' : '' ?>>Published</option>
          <option value="archived" <?= $statusVal === 'archived' ? 'selected' : '' ?>>Archived</option>
        </select>
      </div>
      <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_featured" value="1" <?= !empty($product['is_featured']) ? 'checked' : '' ?>> Featured หน้าแรก
      </label>
      <div>
        <label class="text-sm font-medium mb-1 block">Priority</label>
        <input type="number" name="priority" value="<?= old('priority', $product['priority'] ?? 0) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
      <h3 class="font-bold">รูปปก</h3>
      <input type="url" name="cover_image_url" placeholder="URL รูป หรืออัปโหลดด้านล่าง" value="<?= old('cover_image_url', '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
      <input type="file" name="cover_image" accept="image/*" class="w-full text-xs">
      <?php if (!empty($product['cover_image'])): ?><img src="<?= e(upload_url($product['cover_image'])) ?>" class="rounded-xl border border-slate-200 max-h-48 object-cover" alt=""><?php endif; ?>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
      <h3 class="font-bold">SEO</h3>
      <input type="text" name="meta_title" maxlength="255" placeholder="Meta title" value="<?= old('meta_title', $product['meta_title'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
      <textarea name="meta_description" rows="3" maxlength="500" placeholder="Meta description" class="w-full px-3 py-2 rounded-lg border border-slate-300"><?= old('meta_description', $product['meta_description'] ?? '') ?></textarea>
    </div>
  </aside>
</form>

<?php if ($isEdit && ($product['status'] ?? '') === 'pending_review'): ?>
<div class="max-w-md ml-auto lg:float-right lg:-mt-[420px] lg:mr-0 mb-6 lg:mb-0 bg-sky-50 border border-sky-200 rounded-2xl p-5 space-y-3">
  <h3 class="font-bold text-sky-900">คิวตรวจสอบ</h3>
  <form method="post" action="<?= url('/admin/activity-products/' . $product['id'] . '/publish') ?>"><?= csrf() ?>
    <button class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold text-sm">เผยแพร่ (Publish)</button>
  </form>
  <form method="post" action="<?= url('/admin/activity-products/' . $product['id'] . '/reject') ?>" class="space-y-2"><?= csrf() ?>
    <textarea name="review_note" rows="2" placeholder="หมายเหตุส่งกลับแก้ไข" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm"><?= e($product['review_note'] ?? '') ?></textarea>
    <button class="w-full py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl font-semibold text-sm">ปฏิเสธ → กลับ Draft</button>
  </form>
</div>
<div class="clear-both"></div>
<?php endif; ?>
