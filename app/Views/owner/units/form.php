<?php
/** @var array $property @var ?array $unit @var array $amenities @var array $selectedAmenities @var string|null $units_path_prefix @var string|null $property_edit_url @var array<int,array<string,mixed>> $unit_gallery @var int $unit_image_max */
$isEdit = !empty($unit);
$pfx = $units_path_prefix ?? '/owner/properties';
$isAdminUnits = strpos($pfx, '/admin') === 0;
$propEdit = $property_edit_url ?? url($pfx . '/' . $property['id'] . '/edit');
$action = $isEdit
  ? url($pfx . '/' . $property['id'] . '/units/' . $unit['id'])
  : url($pfx . '/' . $property['id'] . '/units');
$unit_gallery = $unit_gallery ?? [];
$unit_image_max = (int)($unit_image_max ?? 5);
$gallerySlotsLeft = max(0, $unit_image_max - count($unit_gallery));
$moderation = (string)($unit['moderation_status'] ?? 'pending');
$statusLabels = ['pending' => 'รออนุมัติ', 'published' => 'เผยแพร่แล้ว', 'rejected' => 'ถูกปฏิเสธ'];
$statusClasses = [
  'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
  'published' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
  'rejected' => 'bg-rose-100 text-rose-700 border-rose-200',
];
$statusIcons = ['pending' => 'clock', 'published' => 'circle-check', 'rejected' => 'circle-x'];
?>

<div class="flex items-center justify-between mb-4">
  <a href="<?= url($pfx . '/' . $property['id'] . '/units') ?>" class="text-sm text-slate-500 hover:text-accent-700 inline-flex items-center gap-1">
    <i data-lucide="arrow-left" class="w-4 h-4"></i> กลับห้องพักของ <?= e($property['name']) ?>
  </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 space-y-4">
    <form id="frm-unit" method="post" action="<?= $action ?>" enctype="multipart/form-data">
      <?= csrf() ?>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
        <h3 class="font-bold flex items-center gap-2"><i data-lucide="bed-double" class="w-5 h-5 text-accent-600"></i> ข้อมูลห้องพัก</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="text-sm font-medium mb-1 block">ชื่อห้อง <span class="text-rose-500">*</span></label>
            <input type="text" name="name" required value="<?= e($unit['name'] ?? '') ?>" placeholder="เช่น แพไม้ริมน้ำ A1" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          </div>
          <div>
            <label class="text-sm font-medium mb-1 block">ชื่อ (อังกฤษ)</label>
            <input type="text" name="name_en" value="<?= e($unit['name_en'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          </div>
          <div>
            <label class="text-sm font-medium mb-1 block">รหัสห้อง</label>
            <input type="text" name="code" value="<?= e($unit['code'] ?? '') ?>" placeholder="A1, R3" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono">
          </div>
          <div>
            <label class="text-sm font-medium mb-1 block">ประเภทเตียง</label>
            <input type="text" name="bed_type" value="<?= e($unit['bed_type'] ?? '') ?>" placeholder="King 1 + Queen 1" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          </div>
          <div class="md:col-span-2">
            <label class="text-sm font-medium mb-1 block">รายละเอียด</label>
            <textarea name="description" rows="3" class="w-full px-3 py-2 rounded-lg border border-slate-300"><?= e($unit['description'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
        <h3 class="font-bold flex items-center gap-2"><i data-lucide="users" class="w-5 h-5 text-accent-600"></i> ความจุ & ขนาด</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
          <div>
            <label class="text-sm font-medium mb-1 block">รับขั้นต่ำ</label>
            <input type="number" min="1" name="capacity_min" required value="<?= e($unit['capacity_min'] ?? 1) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          </div>
          <div>
            <label class="text-sm font-medium mb-1 block">รับสูงสุด</label>
            <input type="number" min="1" name="capacity_max" required value="<?= e($unit['capacity_max'] ?? 2) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          </div>
          <div>
            <label class="text-sm font-medium mb-1 block">ห้องนอน</label>
            <input type="number" min="0" name="bedrooms" required value="<?= e($unit['bedrooms'] ?? 1) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          </div>
          <div>
            <label class="text-sm font-medium mb-1 block">ห้องน้ำ</label>
            <input type="number" min="0" name="bathrooms" required value="<?= e($unit['bathrooms'] ?? 1) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          </div>
          <div>
            <label class="text-sm font-medium mb-1 block">พื้นที่ (ตร.ม)</label>
            <input type="number" min="0" name="area_sqm" value="<?= e($unit['area_sqm'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
        <h3 class="font-bold flex items-center gap-2"><i data-lucide="dollar-sign" class="w-5 h-5 text-accent-600"></i> ราคา</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
          <div>
            <label class="text-sm font-medium mb-1 block">ราคาวันธรรมดา <span class="text-rose-500">*</span></label>
            <div class="relative"><input type="number" min="0" step="100" name="price" required value="<?= e($unit['price'] ?? 0) ?>" class="w-full pl-7 pr-3 py-2 rounded-lg border border-slate-300"><span class="absolute left-3 top-2.5 text-slate-400 text-sm">฿</span></div>
          </div>
          <div>
            <label class="text-sm font-medium mb-1 block">ราคาเสาร์-อาทิตย์</label>
            <div class="relative"><input type="number" min="0" step="100" name="price_weekend" value="<?= e($unit['price_weekend'] ?? 0) ?>" class="w-full pl-7 pr-3 py-2 rounded-lg border border-slate-300"><span class="absolute left-3 top-2.5 text-slate-400 text-sm">฿</span></div>
          </div>
          <div>
            <label class="text-sm font-medium mb-1 block">ราคาวันหยุดนักขัตฤกษ์</label>
            <div class="relative"><input type="number" min="0" step="100" name="price_holiday" value="<?= e($unit['price_holiday'] ?? 0) ?>" class="w-full pl-7 pr-3 py-2 rounded-lg border border-slate-300"><span class="absolute left-3 top-2.5 text-slate-400 text-sm">฿</span></div>
          </div>
          <div>
            <label class="text-sm font-medium mb-1 block">Low Season</label>
            <div class="relative"><input type="number" min="0" step="100" name="price_low" value="<?= e($unit['price_low'] ?? 0) ?>" class="w-full pl-7 pr-3 py-2 rounded-lg border border-slate-300"><span class="absolute left-3 top-2.5 text-slate-400 text-sm">฿</span></div>
          </div>
          <div>
            <label class="text-sm font-medium mb-1 block">High Season</label>
            <div class="relative"><input type="number" min="0" step="100" name="price_high" value="<?= e($unit['price_high'] ?? 0) ?>" class="w-full pl-7 pr-3 py-2 rounded-lg border border-slate-300"><span class="absolute left-3 top-2.5 text-slate-400 text-sm">฿</span></div>
          </div>
          <div>
            <label class="text-sm font-medium mb-1 block">ค่าผู้ใหญ่เพิ่ม</label>
            <div class="relative"><input type="number" min="0" step="50" name="extra_person_fee" value="<?= e($unit['extra_person_fee'] ?? 0) ?>" class="w-full pl-7 pr-3 py-2 rounded-lg border border-slate-300"><span class="absolute left-3 top-2.5 text-slate-400 text-sm">฿</span></div>
                    <div>
            <label class="text-sm font-medium mb-1 block">ราคารวมจำนวนท่าน</label>
            <input type="number" min="1" name="price_includes_guests" value="<?= e($unit['price_includes_guests'] ?? '') ?>" placeholder="<?= e((string)($unit['capacity_max'] ?? 4)) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
            <p class="text-xs text-slate-500 mt-1">เช่น 4 = ราคาวันธรรมดารวม 4 ท่าน · ท่านที่ 5 ขึ้นไปใช้ค่าเสริม</p>
          </div>
        </div>
        </div>
        <div class="text-xs text-slate-500 bg-blue-50 border border-blue-200 rounded-lg p-3 flex gap-2">
          <i data-lucide="info" class="w-4 h-4 text-blue-600 shrink-0 mt-0.5"></i>
          <div>ระบบจะใช้ราคาตามวันโดยอัตโนมัติ:<br>
            • วันศุกร์-อาทิตย์ → ราคาเสาร์-อาทิตย์<br>
            • วันหยุดราชการ → ราคานักขัตฤกษ์<br>
            • ค่าเสริมคิดเมื่อจำนวนท่านเกิน «ราคารวมจำนวนท่าน»<br>
            (ตั้งเป็น 0 = ใช้ราคาวันธรรมดา)
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
        <h3 class="font-bold flex items-center gap-2 mb-3"><i data-lucide="check-circle" class="w-5 h-5 text-accent-600"></i> สิ่งอำนวยความสะดวกในห้อง</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
          <?php foreach ($amenities as $a):
            $checked = in_array($a['id'], $selectedAmenities); ?>
          <label class="flex items-center gap-2 px-3 py-2 border-2 rounded-lg cursor-pointer transition <?= $checked?'border-accent-500 bg-accent-50':'border-slate-200 hover:border-slate-300' ?>">
            <input type="checkbox" name="amenities[]" value="<?= $a['id'] ?>" <?= $checked?'checked':'' ?>>
            <i data-lucide="<?= e($a['icon'] ?: 'check') ?>" class="w-4 h-4 text-accent-600"></i>
            <span class="text-sm"><?= e($a['name']) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
    </form>

    <?php if ($isEdit && $unit_gallery !== []): ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h4 class="font-bold flex items-center gap-2 mb-3"><i data-lucide="images" class="w-5 h-5 text-accent-600"></i> รูปในระบบ</h4>
      <p class="text-xs text-slate-500 mb-3">รูปแรกเป็นปกห้อง · เหลือช่องเพิ่มได้ <?= $gallerySlotsLeft ?> / <?= $unit_image_max ?></p>
      <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
        <?php foreach ($unit_gallery as $gi): ?>
        <div class="relative group rounded-lg overflow-hidden border border-slate-200">
          <img src="<?= e(upload_url($gi['path'])) ?>" alt="" class="w-full aspect-square object-cover">
          <form method="post" action="<?= url($pfx . '/' . $property['id'] . '/units/' . $unit['id'] . '/images/' . (int)$gi['id'] . '/delete') ?>" class="absolute top-1 right-1" onsubmit="return confirm('ลบรูปนี้?')">
            <?= csrf() ?>
            <button type="submit" class="rounded-full bg-rose-600 hover:bg-rose-700 text-white w-7 h-7 text-sm leading-none shadow-md flex items-center justify-center" aria-label="ลบรูป">×</button>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <aside>
    <div class="lg:sticky lg:top-24 space-y-4">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
        <button type="submit" form="frm-unit" class="w-full py-2.5 bg-accent-500 hover:bg-accent-600 text-white font-semibold rounded-lg inline-flex items-center justify-center gap-2"><i data-lucide="save" class="w-4 h-4"></i> <?= $isEdit?'บันทึก':'เพิ่มห้องพัก' ?></button>
        <?php if ($isEdit): ?>
          <div class="text-xs border rounded-lg p-3 <?= e($statusClasses[$moderation] ?? 'bg-slate-100 text-slate-600 border-slate-200') ?>">
            <div class="font-semibold inline-flex items-center gap-1"><i data-lucide="<?= e($statusIcons[$moderation] ?? 'circle-help') ?>" class="w-3.5 h-3.5"></i><?= e($statusLabels[$moderation] ?? $moderation) ?></div>
            <p class="mt-1 opacity-90"><?= $isAdminUnits ? 'แอดมินสามารถอนุมัติหรือปฏิเสธยูนิตจากหน้ารายการยูนิตหรือหน้ารายละเอียดที่พัก' : 'เมื่อเจ้าของแก้ไขข้อมูลยูนิต ระบบจะส่งกลับไปรอ Admin ตรวจอีกครั้งก่อนแสดงบนเว็บ' ?></p>
          </div>
        <?php else: ?>
          <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">ยูนิตใหม่จะอยู่ในสถานะรอ Admin อนุมัติก่อนแสดงบนหน้าเว็บ</p>
        <?php endif; ?>
      </div>

      <?php if ($isEdit): ?>
      <form method="post" action="<?= url($pfx . '/' . $property['id'] . '/units/' . $unit['id'] . '/delete') ?>" onsubmit="return confirm('ยืนยันลบห้องนี้?')" class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
        <?= csrf() ?>
        <button type="submit" class="w-full py-2 bg-rose-50 text-rose-600 border border-rose-200 rounded-lg text-sm hover:bg-rose-100"><i data-lucide="trash-2" class="w-3.5 h-3.5 inline"></i> ลบห้องพัก</button>
      </form>
      <?php endif; ?>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3 text-sm">
        <h4 class="font-bold flex items-center gap-2"><i data-lucide="settings" class="w-5 h-5 text-accent-600"></i> ตั้งค่า</h4>
        <div>
          <label class="text-sm font-medium mb-1 block">จำนวนหลังที่มี</label>
          <input form="frm-unit" type="number" min="1" name="total_units" value="<?= e($unit['total_units'] ?? 1) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <p class="text-xs text-slate-500 mt-1">ถ้ามี 5 หลังที่เหมือนกัน ใส่ 5</p>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">ลำดับ</label>
          <input form="frm-unit" type="number" name="sort_order" value="<?= e($unit['sort_order'] ?? 0) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <label class="flex items-center gap-2 mt-2">
          <input form="frm-unit" type="checkbox" name="is_active" value="1" <?= ($unit['is_active'] ?? 1)?'checked':'' ?>>
          <span class="text-sm">เปิดให้จอง</span>
        </label>
        <?php if ($isAdminUnits): ?>
        <hr class="border-slate-200 my-3">
        <p class="text-xs font-semibold text-slate-600 mb-2">หน้าแรก (Admin)</p>
        <label class="flex items-center gap-2">
          <input form="frm-unit" type="checkbox" name="is_featured" value="1" <?= !empty($unit['is_featured']) ? 'checked' : '' ?>>
          <span class="text-sm">แนะนำหน้าแรก (Featured)</span>
        </label>
        <div class="mt-2">
          <label class="text-sm font-medium mb-1 block">Priority หน้าแรก</label>
          <input form="frm-unit" type="number" name="homepage_priority" value="<?= e((string)($unit['homepage_priority'] ?? 0)) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <p class="text-xs text-slate-500 mt-1">ยูนิตนี้ขึ้นการ์ดแยกบนหน้าแรก · ลิงก์ไปหน้ารายละเอียดยูนิต</p>
        </div>
        <?php endif; ?>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3 text-sm">
        <h4 class="font-bold flex items-center gap-2 mb-2"><i data-lucide="image" class="w-5 h-5 text-accent-600"></i> รูปห้อง</h4>
        <?php if ($gallerySlotsLeft <= 0): ?>
          <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-2">ครบ <?= $unit_image_max ?> รูปแล้ว — ลบรูปเก่าก่อนถึงจะเพิ่มได้</p>
        <?php else: ?>
          <p class="text-xs text-slate-500">เพิ่มได้สูงสุด <?= $unit_image_max ?> รูปต่อห้อง · เหลือช่อง <?= $gallerySlotsLeft ?></p>
          <div>
            <label class="text-sm font-medium mb-1 block">เลือกหลายรูป</label>
            <input form="frm-unit" type="file" name="unit_images[]" accept="image/*" multiple class="w-full text-xs">
          </div>
          <div>
            <label class="text-sm font-medium mb-1 block">หรือรูปเดียว (ต่อท้ายแกลเลอรี)</label>
            <input form="frm-unit" type="file" name="cover_image" accept="image/*" class="w-full text-xs">
          </div>
        <?php endif; ?>
      </div>
    </div>
  </aside>
</div>
