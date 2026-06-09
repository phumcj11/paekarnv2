<?php
/** @var ?array<string,mixed> $place */
/** @var array<string,string> $categories */
/** @var list<string> $districtChoices */
/** @var list<string> $zoneChoices */

$isEdit = !empty($place);
$action = $isEdit ? url('/admin/visitor-places/' . $place['id']) : url('/admin/visitor-places');
$oldInput = \App\Core\Session::get('_old', []);
$nameVal = old('name', $isEdit ? (string)($place['name'] ?? '') : '');
$slugVal = old('slug', $isEdit ? (string)($place['slug'] ?? '') : '');
$selCat = (string)($oldInput['category'] ?? ($isEdit ? ($place['category'] ?? 'attraction') : 'attraction'));
$zoneVal = (string)($oldInput['zone'] ?? ($isEdit ? ($place['zone'] ?? '') : ''));
$districtVal = (string)($oldInput['district'] ?? ($isEdit ? ($place['district'] ?? '') : ''));
$chkActive = array_key_exists('is_active', $oldInput) ? !empty($oldInput['is_active']) : ($place === null ? true : !empty($place['is_active']));
?>
<a href="<?= url('/admin/visitor-places') ?>" class="text-sm text-slate-500 hover:text-primary-700 inline-flex items-center gap-1 mb-3"><i data-lucide="arrow-left" class="w-4 h-4"></i> กลับ</a>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="max-w-3xl space-y-4">
  <?= csrf() ?>
  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div class="sm:col-span-2">
        <label class="text-sm font-medium mb-1 block">ชื่อสถานที่</label>
        <input type="text" name="name" required maxlength="200" class="w-full px-3 py-2 rounded-lg border border-slate-300" value="<?= $nameVal ?>">
      </div>
      <div class="sm:col-span-2">
        <label class="text-sm font-medium mb-1 block">Slug (URL path — a-z กับขีดกลาง)</label>
        <input type="text" name="slug" required maxlength="180" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-sm" placeholder="meena-cafe-saphan" value="<?= $slugVal ?>">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">หมวด</label>
        <select name="category" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <?php foreach ($categories as $k => $lab): ?>
            <option value="<?= e($k) ?>" <?= $selCat === $k ? 'selected' : '' ?>><?= e($lab) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">โซน (ให้ตรงกับที่พัก เพื่อแนะนำแพใกล้เคียง)</label>
        <select name="zone" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <option value="">— ไม่ระบุโซน —</option>
          <?php foreach ($zoneChoices as $z): ?>
            <option value="<?= e($z) ?>" <?= $zoneVal === $z ? 'selected' : '' ?>><?= e($z) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="sm:col-span-2">
        <label class="text-sm font-medium mb-1 block">อำเภอ (กรองหน้าเว็บ /places)</label>
        <select name="district" class="w-full px-3 py-2 rounded-lg border border-slate-300">
          <option value="">— ไม่ระบุอำเภอ —</option>
          <?php foreach ($districtChoices as $d): ?>
            <option value="<?= e($d) ?>" <?= $districtVal === $d ? 'selected' : '' ?>><?= e($d) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="sm:col-span-2">
        <label class="text-sm font-medium mb-1 block">บทคัดย่อ</label>
        <textarea name="excerpt" rows="2" maxlength="400" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm"><?= old('excerpt', $isEdit ? (string)($place['excerpt'] ?? '') : '') ?></textarea>
      </div>
      <div class="sm:col-span-2">
        <label class="text-sm font-medium mb-1 block">รายละเอียด</label>
        <textarea name="description" rows="8" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm"><?= old('description', $isEdit ? (string)($place['description'] ?? '') : '') ?></textarea>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">ละติจูด (ถ้ามี)</label>
        <input type="text" name="latitude" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-sm" placeholder="14.0226" value="<?= old('latitude', $isEdit && $place['latitude'] !== null ? (string)$place['latitude'] : '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">ลองจิจูด (ถ้ามี)</label>
        <input type="text" name="longitude" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-sm" placeholder="99.5328" value="<?= old('longitude', $isEdit && $place['longitude'] !== null ? (string)$place['longitude'] : '') ?>">
      </div>
      <div class="sm:col-span-2">
        <label class="text-sm font-medium mb-1 block">ที่อยู่ / การเดินทางสั้นๆ</label>
        <textarea name="address" rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm"><?= old('address', $isEdit ? (string)($place['address'] ?? '') : '') ?></textarea>
      </div>
      <div class="sm:col-span-2">
        <label class="text-sm font-medium mb-1 block">ลิงก์ Google Maps</label>
        <input type="url" name="google_maps_url" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm" placeholder="https://maps.google.com/..." value="<?= old('google_maps_url', $isEdit ? (string)($place['google_maps_url'] ?? '') : '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">คะแนนเฉลี่ย (0–5)</label>
        <input type="number" step="0.1" min="0" max="5" name="rating_avg" class="w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="4.7" value="<?= old('rating_avg', $isEdit && ($place['rating_avg'] ?? null) !== null ? (string)$place['rating_avg'] : '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">จำนวนรีวิว</label>
        <input type="number" min="0" name="rating_count" class="w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="128" value="<?= old('rating_count', $isEdit ? (string)(int)($place['rating_count'] ?? 0) : '0') ?>">
      </div>
      <div class="sm:col-span-2">
        <label class="text-sm font-medium mb-1 block">เวลาเปิดปิด</label>
        <input type="text" name="opening_hours" maxlength="120" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm" placeholder="08:00 - 18:00" value="<?= old('opening_hours', $isEdit ? (string)($place['opening_hours'] ?? '') : '') ?>">
      </div>
      <div class="sm:col-span-2">
        <label class="text-sm font-medium mb-1 block">Tags สำหรับหน้า list คาเฟ่</label>
        <input type="text" name="tags" maxlength="500" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm" placeholder="กาแฟดี, มุมถ่ายรูปสวย, pet_friendly" value="<?= old('tags', $isEdit ? (string)($place['tags'] ?? '') : '') ?>">
        <p class="text-xs text-slate-500 mt-1">ใช้คำเช่น coffee_good, photo_spot, pet_friendly เพื่อให้ filter chips ทำงาน</p>
      </div>
      <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-2">
        <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 cursor-pointer">
          <input type="checkbox" name="is_open_now" value="1" class="rounded border-slate-300" <?= !empty($oldInput) ? (!empty($oldInput['is_open_now']) ? 'checked' : '') : (!empty($place['is_open_now']) ? 'checked' : '') ?>>
          <span class="text-sm">เปิดอยู่ตอนนี้</span>
        </label>
        <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 cursor-pointer">
          <input type="checkbox" name="is_pet_friendly" value="1" class="rounded border-slate-300" <?= !empty($oldInput) ? (!empty($oldInput['is_pet_friendly']) ? 'checked' : '') : (!empty($place['is_pet_friendly']) ? 'checked' : '') ?>>
          <span class="text-sm">Pet Friendly</span>
        </label>
        <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 cursor-pointer">
          <input type="checkbox" name="is_photo_spot" value="1" class="rounded border-slate-300" <?= !empty($oldInput) ? (!empty($oldInput['is_photo_spot']) ? 'checked' : '') : (!empty($place['is_photo_spot']) ? 'checked' : '') ?>>
          <span class="text-sm">มุมถ่ายรูป</span>
        </label>
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">ลำดับแสดง</label>
        <input type="number" name="sort_order" min="0" class="w-full px-3 py-2 rounded-lg border border-slate-300" value="<?= old('sort_order', $isEdit ? (string)(int)($place['sort_order'] ?? 0) : '0') ?>">
      </div>
    </div>

    <div class="border-t border-slate-100 pt-4 space-y-3">
      <label class="text-sm font-medium block">รูปปก</label>
      <input type="url" name="cover_image_url" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm" placeholder="URL รูป (ถ้ามี) — หรืออัปโหลดด้านล่าง"
             value="<?= old('cover_image_url', '') ?>">
      <input type="file" name="cover_image" accept="image/*" class="w-full text-sm">
      <?php if ($isEdit && !empty($place['cover_image'])): ?>
        <img src="<?= e(upload_url((string)$place['cover_image'])) ?>" alt="" class="rounded-xl max-h-48 object-cover border border-slate-200">
      <?php endif; ?>
    </div>

    <div class="border-t border-slate-100 pt-4 space-y-3">
      <label class="text-sm font-medium block">รูป Gallery (สำหรับ thumbnail แถวล่าง)</label>
      <p class="text-xs text-slate-500">อัปโหลดได้หลายรูปพร้อมกัน (Ctrl+คลิก หรือ Shift+คลิก) — จะ <strong>แทนที่</strong>ของเดิมทั้งหมด</p>
      <input type="file" name="gallery_images[]" accept="image/*" multiple class="w-full text-sm">
      <?php
        $galleryJson = trim((string)($place['gallery_images'] ?? ''));
        $galleryList = [];
        if ($galleryJson !== '' && str_starts_with($galleryJson, '[')) {
            $decoded = json_decode($galleryJson, true);
            if (is_array($decoded)) { $galleryList = $decoded; }
        }
      ?>
      <?php if ($isEdit && !empty($galleryList)): ?>
        <div class="flex flex-wrap gap-2 mt-1">
          <?php foreach ($galleryList as $gf): ?>
            <img src="<?= e(upload_url((string)$gf)) ?>" alt="" class="w-20 h-20 rounded-lg object-cover border border-slate-200">
          <?php endforeach; ?>
        </div>
        <label class="flex items-center gap-2 text-xs text-red-600 cursor-pointer">
          <input type="checkbox" name="clear_gallery" value="1" class="rounded">
          ลบรูป Gallery ทั้งหมด (ไม่อัปโหลดใหม่)
        </label>
      <?php else: ?>
        <p class="text-xs text-slate-400 italic">ยังไม่มีรูป gallery</p>
      <?php endif; ?>
    </div>

    <label class="flex items-center gap-2 cursor-pointer">
      <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" <?= $chkActive ? 'checked' : '' ?>>
      <span class="text-sm">แสดงบนเว็บ</span>
    </label>

    <div class="border-t border-slate-100 pt-4 space-y-3">
      <h3 class="font-semibold text-slate-800">SEO</h3>
      <div>
        <label class="text-sm font-medium mb-1 block">Meta title</label>
        <input type="text" name="meta_title" maxlength="255" class="w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="ชื่อสถานที่ — ที่เที่ยวกาญจนบุรี | แพกาญ.com" value="<?= old('meta_title', $isEdit ? (string)($place['meta_title'] ?? '') : '') ?>">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">Meta description</label>
        <textarea name="meta_description" rows="2" maxlength="500" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm"><?= old('meta_description', $isEdit ? (string)($place['meta_description'] ?? '') : '') ?></textarea>
      </div>
    </div>
  </div>
  <button type="submit" class="px-6 py-2.5 bg-accent-500 hover:bg-accent-600 text-white rounded-xl font-semibold">บันทึก</button>
</form>
