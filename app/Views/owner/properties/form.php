<?php
/** @var ?array $property @var array $amenities @var array $selectedAmenities
 *  @var array|null $images @var array|null $units
 *  @var string|null $route_prefix owner|admin
 *  @var array|null $owners_list สำหรับ admin เท่านั้น
 *  @var list<string>|null $zone_options (ถ้าไม่ส่ง จะใช้ Property::zonesForSelect)
 *  @var bool $show_coupon_contract_field แอดมินเท่านั้น — ฟิลด์สัญญาคูปองเมื่อมีคอลัมน์ใน DB */
$route_prefix = $route_prefix ?? 'owner';
$propUrl = fn(string $suffix = '') => url('/' . $route_prefix . '/properties' . $suffix);
$intakeVals = $property ? \App\Models\Property::decodeOwnerIntake($property['owner_intake'] ?? null) : [];
$isEdit = !empty($property);
$zone_options_list = $zone_options ?? \App\Models\Property::zonesForSelect($property['zone'] ?? null);
$zoneDistrictMap = \App\Models\Zone::districtMapGrouped();
$zoneOptionLabels = [];
foreach ($zone_options_list as $zopt) {
    $zoneOptionLabels[$zopt] = \App\Models\Zone::labelForSelect($zopt);
}
$kanchanaburiProvince = 'กาญจนบุรี';
$kanchanaburiDistricts = \App\Support\ThailandGeo::kanchanaburiDistricts();
$selectedDistrict = trim((string) old('district', $property['district'] ?? ''));
$canManageBookingMode = ($route_prefix === 'admin' || \App\Core\Auth::isAdmin());
$action = $isEdit ? $propUrl('/' . $property['id']) : $propUrl();
$ownerUnitCount = count($units ?? []);
$show_coupon_contract_field = !empty($show_coupon_contract_field);
$ccSignedVal = '';
if ($isEdit && !empty($property['coupon_contract_signed_at'])) {
    $t = strtotime((string)$property['coupon_contract_signed_at']);
    $ccSignedVal = $t ? date('Y-m-d\TH:i', $t) : '';
}
$bookingCaps = $property
    ? \App\Support\PropertyBookingCapabilities::fromProperty($property)
    : ['allow_contact' => true, 'coupon_enabled' => false, 'allow_online_booking' => false, 'booking_requires_payment' => false];
?>
<div class="flex items-center justify-between mb-4">
  <a href="<?= $propUrl() ?>" class="text-sm text-slate-500 hover:text-accent-700 inline-flex items-center gap-1"><i data-lucide="arrow-left" class="w-4 h-4"></i> ทั้งหมด</a>
  <?php if ($route_prefix === 'owner' && $isEdit):
    $canLineHub = \App\Services\OwnerFeatureGate::allowed(\App\Services\OwnerTier::FEATURE_LINE_HUB);
    $canAvailability = \App\Services\OwnerFeatureGate::allowed(\App\Services\OwnerTier::FEATURE_AVAILABILITY);
    $membershipUrl = url('/owner/membership');
  ?>
  <div class="flex items-center gap-2 flex-wrap justify-end">
    <a href="<?= url('/owner/properties/' . $property['id'] . '/units') ?>" class="px-3 py-1.5 text-xs bg-accent-500 text-white rounded-lg inline-flex items-center gap-1 font-semibold"><i data-lucide="bed-double" class="w-3.5 h-3.5"></i> จัดการห้อง/แพ (<?= $ownerUnitCount ?>)</a>
    <?php if ($canAvailability): ?>
    <a href="<?= url('/owner/properties/' . $property['id'] . '/availability') ?>" class="px-3 py-1.5 text-xs bg-primary-600 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="calendar" class="w-3.5 h-3.5"></i> ปฏิทินวันว่าง</a>
    <?php else: ?>
    <a href="<?= e($membershipUrl) ?>" class="px-3 py-1.5 text-xs border border-sky-200 text-sky-700 bg-sky-50 rounded-lg inline-flex items-center gap-1" title="ต้องสมัครแพ็กเกจ"><i data-lucide="lock" class="w-3.5 h-3.5"></i> ปฏิทิน (สมาชิก)</a>
    <?php endif; ?>
    <?php if ($property['status'] === 'published'): ?>
    <a target="_blank" href="<?= url('/property/' . $property['slug']) ?>" class="px-3 py-1.5 text-xs border border-slate-300 hover:bg-slate-50 rounded-lg inline-flex items-center gap-1"><i data-lucide="external-link" class="w-3.5 h-3.5"></i> ดูบนเว็บ</a>
    <?php endif; ?>
  </div>
  <?php elseif ($route_prefix === 'admin' && $isEdit): ?>
  <div class="flex flex-wrap items-center gap-2">
    <a href="<?= url('/admin/properties/' . $property['id'] . '/units') ?>" class="px-3 py-1.5 text-xs bg-accent-500 text-white rounded-lg inline-flex items-center gap-1"><i data-lucide="bed-double" class="w-3.5 h-3.5"></i> จัดการห้องพัก / ราคา (<?= count($units ?? []) ?>)</a>
    <?php if (($property['status'] ?? '') === 'published'): ?>
    <a target="_blank" href="<?= url('/property/' . $property['slug']) ?>" class="px-3 py-1.5 text-xs border border-slate-300 hover:bg-slate-50 rounded-lg inline-flex items-center gap-1"><i data-lucide="external-link" class="w-3.5 h-3.5"></i> ดูบนเว็บ</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php if ($route_prefix === 'owner' && !$isEdit): ?>
<div class="mb-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-950 leading-relaxed">
  <p class="font-semibold flex items-center gap-2"><i data-lucide="list-ordered" class="w-4 h-4 shrink-0"></i> ขั้นตอนใช้งาน (สั้นๆ)</p>
  <ol class="mt-2 ml-1 space-y-1 list-decimal list-inside text-sky-900/95">
    <li><strong>หน้านี้</strong> — กรอกข้อมูลที่พัก (ชื่อ ที่อยู่ รูป ฯลฯ) แล้วกด «สร้างที่พัก»</li>
    <li><strong>หลังบันทึก</strong> — กดปุ่มสีเขียว <strong>จัดการห้อง/แพ</strong> เพื่อเพิ่มห้องหรือแพแต่ละลำและราคา (ลูกค้าจองตามห้อง/แพ ไม่ใช่แค่ชื่อที่พักอย่างเดียว)</li>
  </ol>
</div>
<?php endif; ?>

<?php if ($route_prefix === 'owner' && $isEdit && $ownerUnitCount === 0): ?>
<div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950">
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
      <p class="font-semibold flex items-center gap-2"><i data-lucide="circle-alert" class="w-4 h-4 shrink-0"></i> ยังไม่มีห้องหรือแพในระบบ</p>
      <p class="mt-1 text-amber-900/90">ที่พักหนึ่งรายการมักมีหลายห้องหรือหลายแพ — เพิ่มอย่างน้อย 1 รายการเพื่อให้ลูกค้าเลือกจองได้</p>
    </div>
    <a href="<?= url('/owner/properties/' . $property['id'] . '/units/create') ?>" class="shrink-0 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-accent-500 hover:bg-accent-600 text-white rounded-lg text-sm font-semibold whitespace-nowrap">
      <i data-lucide="plus-circle" class="w-4 h-4"></i> เพิ่มห้อง/แพแรก
    </a>
  </div>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
<form id="propertyForm" method="post" action="<?= $action ?>" enctype="multipart/form-data" class="lg:col-span-3 grid grid-cols-1 lg:grid-cols-3 gap-4">
  <?= csrf() ?>

  <div class="lg:col-span-2 space-y-4">

    <?php if ($route_prefix === 'admin'): ?>
    <?php
    $adminActiveUnits = 0;
    foreach ($units ?? [] as $__u) {
        if ((int)($__u['is_active'] ?? 0) === 1) {
            $adminActiveUnits++;
        }
    }
    ?>
    <div class="bg-amber-50 border border-amber-200 rounded-2xl shadow-soft p-5 space-y-3">
      <h3 class="font-bold flex items-center gap-2 text-amber-900"><i data-lucide="shield" class="w-5 h-5"></i> การจัดการโดยแอดมิน</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div class="md:col-span-2">
          <label class="text-sm font-medium mb-1 block">เจ้าของแพ <span class="text-rose-500">*</span></label>
          <select name="owner_id" required class="w-full px-3 py-2 rounded-lg border border-amber-300 bg-white">
            <?php foreach ($owners_list ?? [] as $ow): ?>
              <option value="<?= (int)$ow['id'] ?>" <?= ($isEdit && (int)($property['owner_id'] ?? 0) === (int)$ow['id']) ? 'selected' : '' ?>>
                <?= e(($ow['business_name'] ?: $ow['name']) . ' · ' . ($ow['email'] ?? '')) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">สถานะเผยแพร่ <span class="text-rose-500">*</span></label>
          <select name="status" required class="w-full px-3 py-2 rounded-lg border border-amber-300 bg-white">
            <?php
            $st = $isEdit ? ($property['status'] ?? 'draft') : 'published';
            foreach (['draft'=>'ฉบับร่าง','pending'=>'รออนุมัติ','published'=>'เผยแพร่','rejected'=>'ปฏิเสธ','archived'=>'เก็บถาวร'] as $k => $lab): ?>
              <option value="<?= $k ?>" <?= $st === $k ? 'selected' : '' ?>><?= e($lab) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">ลำดับหน้าแรก (Priority)</label>
          <input type="number" name="priority" min="0" max="9999" step="1"
            value="<?= (int) old('priority', (string)($property['priority'] ?? 0)) ?>"
            class="w-full px-3 py-2 rounded-lg border border-amber-300 bg-white">
          <p class="text-[11px] text-amber-900/75 mt-1 leading-snug">ยิ่งสูงยิ่งขึ้นก่อนใน section โzo และรายการทั่วไป · 0 = ปกติ</p>
        </div>
        <div class="flex items-end pb-1">
          <label class="inline-flex items-center gap-2 text-sm font-medium cursor-pointer">
            <input type="checkbox" name="is_featured" value="1"
              <?= !empty($property['is_featured']) || old('is_featured') !== '' ? 'checked' : '' ?>
              class="rounded border-amber-400">
            Featured ⭐ (ขึ้นก่อน Priority ต่ำ)
          </label>
        </div>
        <?php if ($isEdit): ?>
        <div class="md:col-span-2" id="admin-slug-field" data-base-url="<?= e(rtrim(url('/property/'), '/')) ?>">
          <label class="text-sm font-medium mb-1 block">ลิงก์หน้ารายละเอียด</label>
          <div id="admin-slug-auto-view">
            <code class="block text-xs font-mono bg-white px-2.5 py-2 rounded-lg border border-amber-200 text-amber-950 break-all" id="admin-slug-preview"><?= e(url('/property/' . ($property['slug'] ?? ''))) ?></code>
            <p class="text-[11px] text-amber-900/75 mt-1.5 leading-snug">ระบบสร้างจากชื่อที่พักอัตโนมัติ (ใช้ชื่ออังกฤษถ้ามี) — ไม่ต้องกรอกเอง</p>
            <button type="button" id="admin-slug-edit-btn" class="text-xs font-semibold text-amber-800 underline hover:text-amber-950 mt-1">แก้ URL เอง</button>
          </div>
          <div id="admin-slug-custom-view" class="hidden space-y-1.5 mt-1">
            <input type="hidden" name="slug_custom" id="slug-custom-flag" value="">
            <input type="text" name="slug" id="admin-slug-input" value="<?= e($property['slug'] ?? '') ?>" maxlength="180" class="w-full px-3 py-2 rounded-lg border border-amber-300 bg-white font-mono text-sm" placeholder="sky-lake-view-resort">
            <p class="text-[11px] text-rose-700 leading-snug">เปลี่ยน URL หลังเผยแพร่อาจทำให้ลิงก์เดิมใช้ไม่ได้</p>
            <button type="button" id="admin-slug-auto-btn" class="text-xs font-semibold text-amber-800 underline hover:text-amber-950">ใช้ URL อัตโนมัติอีกครั้ง</button>
          </div>
        </div>
        <div class="flex items-center gap-2 pt-6">
          <label class="inline-flex items-center gap-2 text-sm font-medium cursor-pointer">
            <input type="checkbox" name="is_verified" value="1" <?= !empty($property['is_verified']) ? 'checked' : '' ?> class="rounded border-amber-400">
            Verified
          </label>
        </div>
        <?php endif; ?>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-3 border-t border-amber-200/70">
        <div>
          <label class="text-sm font-medium mb-1 block">ราคาเริ่มต้นแสดงบนเว็บ (บาท/คืน)</label>
          <input type="number" name="admin_display_min_price" step="0.01" min="0"
            value="<?= $isEdit ? e((string)(float)($property['min_price'] ?? 0)) : '' ?>"
            class="w-full px-3 py-2 rounded-lg border border-amber-300 bg-white <?= ($isEdit && $adminActiveUnits > 0) ? 'bg-slate-50 text-slate-600' : '' ?>"
            <?= ($isEdit && $adminActiveUnits > 0) ? 'readonly tabindex="-1"' : '' ?>>
          <p class="text-[11px] text-amber-900/75 mt-1 leading-snug">
            <?php if ($isEdit && $adminActiveUnits > 0): ?>
              มีห้องเปิดขาย <?= (int)$adminActiveUnits ?> แบบ — ระบบใช้ราคาต่ำสุดจากยูนิต (ช่องนี้สะท้อนค่าปัจจุบันเท่านั้น)
            <?php else: ?>
              ใช้เมื่อยังไม่มีห้อง/ยูนิต หรือตั้งราคาโชว์ก่อนแยกห้อง
            <?php endif; ?>
          </p>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">คะแนนดาวแสดง (0–5)</label>
          <input type="number" name="admin_rating_avg" step="0.1" min="0" max="5"
            value="<?= $isEdit ? e((string)(float)($property['rating_avg'] ?? 0)) : '0' ?>"
            class="w-full px-3 py-2 rounded-lg border border-amber-300 bg-white">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">จำนวนรีวิวแสดง</label>
          <input type="number" name="admin_rating_count" min="0" step="1"
            value="<?= $isEdit ? (int)($property['rating_count'] ?? 0) : 0 ?>"
            class="w-full px-3 py-2 rounded-lg border border-amber-300 bg-white">
        </div>
        <div class="md:col-span-3">
          <label class="inline-flex items-start gap-2 text-sm cursor-pointer text-amber-900">
            <input type="checkbox" name="rating_locked" value="1" class="rounded border-amber-400 mt-0.5" <?= !empty($property['rating_locked']) ? 'checked' : '' ?>>
            <span>ล็อกคะแนนนี้ — ไม่ให้ระบบคำนวณทับจากรีวิวที่อนุมัติ (เอาเครื่องหมายออกเมื่อต้องการให้ใช้ค่าจากรีวิวจริงอัตโนมัติ)</span>
          </label>
        </div>
      </div>

      <?php if (!$isEdit): ?>
      <div class="md:col-span-2 flex items-center gap-2 pt-1">
        <label class="inline-flex items-center gap-2 text-sm cursor-pointer text-amber-900">
          <input type="checkbox" name="mark_verified" value="1" class="rounded border-amber-400">
          ตั้ง Verified ทันที (เมื่อสถานะเป็นเผยแพร่)
        </label>
      </div>
      <?php endif; ?>
      <?php if ($isEdit): ?>
      <p class="text-xs text-amber-900/80">ห้องพักและราคา — ใช้ปุ่ม «จัดการห้องพัก / ราคา» ด้านบน · ปฏิทินวันว่างให้เจ้าของแพเข้า Owner Portal</p>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="info" class="w-5 h-5 text-accent-600"></i> ข้อมูลพื้นฐาน</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="text-sm font-medium mb-1 block">ชื่อที่พัก (ไทย) <span class="text-rose-500">*</span></label>
          <input type="text" name="name" required value="<?= e($property['name'] ?? old('name')) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">ชื่อ (อังกฤษ)</label>
          <input type="text" name="name_en" value="<?= e($property['name_en'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">ประเภท <span class="text-rose-500">*</span></label>
          <select name="type" required id="prop-type-select"
                  class="w-full px-3 py-2 rounded-lg border border-slate-300"
                  onchange="document.getElementById('raft-variant-row').classList.toggle('hidden', this.value !== 'raft')">
            <?php foreach (['raft'=>'แพพัก','resort'=>'รีสอร์ท','homestay'=>'โฮมสเตย์','house'=>'บ้านพัก','pool_villa'=>'บ้านพูลวิลล่า','hotel'=>'โรงแรม','camping'=>'แคมป์ปิ้ง'] as $k=>$v): ?>
              <option value="<?= $k ?>" <?= ($property['type'] ?? 'raft')===$k?'selected':'' ?>><?= e($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div id="raft-variant-row" class="md:col-span-2 <?= ($property['type'] ?? 'raft') !== 'raft' ? 'hidden' : '' ?>">
          <label class="text-sm font-medium mb-1 block">ประเภทแพ (เมื่อเป็นแพพัก)</label>
          <select name="raft_variant" class="w-full px-3 py-2 rounded-lg border border-slate-300">
            <option value="">— เลือก —</option>
            <option value="shore" <?= (($property['raft_variant'] ?? '') === 'shore') ? 'selected' : '' ?>>แพริมน้ำ</option>
            <option value="towed" <?= (($property['raft_variant'] ?? '') === 'towed') ? 'selected' : '' ?>>แพลาก</option>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">จังหวัด</label>
          <input type="hidden" name="province" value="<?= e($kanchanaburiProvince) ?>">
          <input type="text" readonly value="<?= e($kanchanaburiProvince) ?>"
                 class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700 cursor-default">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">อำเภอ</label>
          <select name="district" id="owner-property-district" class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white">
            <option value="">— เลือกอำเภอ —</option>
            <?php foreach ($kanchanaburiDistricts as $dist): ?>
              <option value="<?= e($dist) ?>" <?= $selectedDistrict === $dist ? 'selected' : '' ?>><?= e($dist) ?></option>
            <?php endforeach; ?>
            <?php if ($selectedDistrict !== '' && !in_array($selectedDistrict, $kanchanaburiDistricts, true)): ?>
              <option value="<?= e($selectedDistrict) ?>" selected><?= e($selectedDistrict) ?></option>
            <?php endif; ?>
          </select>
        </div>
        <div class="md:col-span-2">
          <?php $zoneVal = (string)($property['zone'] ?? ''); ?>
          <label class="text-sm font-medium mb-1 block">โซน / พื้นที่ <span class="text-rose-500">*</span></label>
          <select name="zone" id="owner-property-zone" required class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white">
            <option value="">— เลือกโซน / พื้นที่ —</option>
            <?php foreach ($zone_options_list as $zopt): ?>
              <option value="<?= e($zopt) ?>" <?= $zoneVal === $zopt ? 'selected' : '' ?>><?= e($zoneOptionLabels[$zopt] ?? $zopt) ?></option>
            <?php endforeach; ?>
          </select>
          <p class="text-xs text-slate-500 mt-1">โซน ใช้จัดกลุ่มบนหน้าเว็บและค้นหา (เช่น แพริมแม่น้ำแคว) — ไม่จำเป็นต้องตรงชื่ออำเภอ ระบบจะแนะนำให้เมื่อเลือกอำเภอแล้ว</p>
          <p id="owner-property-zone-hint" class="hidden text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mt-2"></p>
        </div>
        <div class="md:col-span-2">
          <label class="text-sm font-medium mb-1 block">ที่อยู่</label>
          <input type="text" name="address" value="<?= e($property['address'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">Latitude</label>
          <input type="text" name="latitude" value="<?= e($property['latitude'] ?? '') ?>" placeholder="14.0228" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-sm">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">Longitude</label>
          <input type="text" name="longitude" value="<?= e($property['longitude'] ?? '') ?>" placeholder="99.5328" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-sm">
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="file-text" class="w-5 h-5 text-accent-600"></i> รายละเอียด & กฎ</h3>
      <div x-data="aiHelp()">
        <div class="flex items-center justify-between mb-1">
          <label class="text-sm font-medium">รายละเอียดที่พัก</label>
          <button type="button" @click="generate('description', $refs.descBox)" :disabled="busy"
                  class="text-xs px-2.5 py-1 bg-gradient-to-r from-purple-500 to-accent-500 text-white rounded-md inline-flex items-center gap-1 hover:opacity-90 disabled:opacity-50">
            <i data-lucide="sparkles" class="w-3 h-3"></i> <span x-text="busy?'กำลังสร้าง...':'AI ช่วยเขียน'"></span>
          </button>
        </div>
        <textarea name="description" rows="5" x-ref="descBox" class="w-full px-3 py-2 rounded-lg border border-slate-300"><?= e($property['description'] ?? '') ?></textarea>
        <div class="text-xs text-slate-500 mt-1 inline-flex items-start gap-1.5"><i data-lucide="lightbulb" class="w-3.5 h-3.5 shrink-0 mt-0.5 text-amber-500"></i><span>ใช้ AI ช่วยเขียนได้ — กรอกชื่อที่พัก ประเภท และโซนก่อน</span></div>
      </div>
      <div x-data="aiHelp()">
        <div class="flex items-center justify-between mb-1">
          <label class="text-sm font-medium">กฎการเข้าพัก</label>
          <button type="button" @click="generate('rules', $refs.rulesBox)" :disabled="busy"
                  class="text-xs px-2.5 py-1 bg-gradient-to-r from-purple-500 to-accent-500 text-white rounded-md inline-flex items-center gap-1 hover:opacity-90 disabled:opacity-50">
            <i data-lucide="sparkles" class="w-3 h-3"></i> <span x-text="busy?'กำลังสร้าง...':'AI ช่วยร่าง'"></span>
          </button>
        </div>
        <textarea name="rules" rows="4" x-ref="rulesBox" class="w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="ห้ามส่งเสียงดังหลัง 22:00 ฯลฯ"><?= e($property['rules'] ?? '') ?></textarea>
      </div>
      <script>
      function aiHelp() {
        return {
          busy: false,
          async generate(kind, target) {
            const form = target.closest('form');
            const get  = n => form.querySelector(`[name="${n}"]`)?.value || '';
            this.busy = true;
            try {
              const fd = new FormData();
              fd.append('kind', kind);
              fd.append('name', get('name'));
              fd.append('type', get('type'));
              fd.append('zone', get('zone'));
              fd.append('features', form.querySelectorAll('input[name="amenities[]"]:checked').length + ' amenities');
              const r = await fetch('<?= url('/ai/generate') ?>', {method:'POST', body: fd});
              const j = await r.json();
              if (j.ok) target.value = j.text;
              else alert(j.error || 'AI ไม่พร้อมใช้งาน');
            } catch (e) { alert('เชื่อมต่อ AI ไม่สำเร็จ'); }
            finally { this.busy = false; }
          }
        }
      }
      </script>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div>
          <label class="text-sm font-medium mb-1 block">เช็คอิน</label>
          <input type="time" name="check_in" value="<?= e(substr($property['check_in'] ?? '14:00:00',0,5)) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">เช็คเอาท์</label>
          <input type="time" name="check_out" value="<?= e(substr($property['check_out'] ?? '12:00:00',0,5)) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">สัตว์เลี้ยง</label>
          <select name="pet_policy" class="w-full px-3 py-2 rounded-lg border border-slate-300">
            <?php foreach (['not_allowed'=>'ไม่อนุญาต','allowed'=>'อนุญาต','on_request'=>'แจ้งล่วงหน้า'] as $k=>$v): ?>
              <option value="<?= $k ?>" <?= ($property['pet_policy'] ?? 'not_allowed')===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">มัดจำ (บาท)</label>
          <input type="number" name="deposit_amount" value="<?= e($property['deposit_amount'] ?? '0') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
        <div class="col-span-2 md:col-span-4">
          <label class="text-sm font-medium mb-1 block">หมายเหตุมัดจำ</label>
          <input type="text" name="deposit_note" value="<?= e($property['deposit_note'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300">
        </div>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="clipboard-list" class="w-5 h-5 text-accent-600"></i> คำถามจากแบบฟอร์มเจ้าของแพ</h3>
      <p class="text-xs text-slate-500 leading-relaxed">ช่องด้านล่างสอดคล้องกับคำถามในแบบฟอร์มกระดาษ — จะแสดงในหน้าที่พักและโหมด «ดูข้อมูลมากขึ้น» บนการ์ดมือถือ (ช่องว่างไม่แสดง)</p>
      <div class="space-y-3">
        <?php foreach (\App\Models\Property::ownerIntakeFieldLabels() as $intakeKey => $intakeLabel): ?>
        <div>
          <label class="text-sm font-medium mb-1 block"><?= e($intakeLabel) ?></label>
          <textarea name="intake_<?= e($intakeKey) ?>" rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm" placeholder="ถ้าไม่มีข้อมูลให้เว้นว่าง"><?= e($intakeVals[$intakeKey] ?? '') ?></textarea>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
      <h3 class="font-bold flex items-center gap-2"><i data-lucide="phone" class="w-5 h-5 text-accent-600"></i> ช่องทางติดต่อ</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div><label class="text-sm font-medium mb-1 block">เบอร์โทร</label><input type="tel" name="phone" value="<?= e($property['phone'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300"></div>
        <div><label class="text-sm font-medium mb-1 block">LINE ID</label><input type="text" name="line_id" value="<?= e($property['line_id'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="@username หรือลิงก์ line.me"></div>
        <div><label class="text-sm font-medium mb-1 block">Facebook URL / Page</label><input type="text" name="facebook_url" value="<?= e($property['facebook_url'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="https://facebook.com/YourPage"></div>
        <div><label class="text-sm font-medium mb-1 block">Instagram URL</label><input type="url" name="instagram_url" value="<?= e($property['instagram_url'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="https://instagram.com/yourpage"></div>
        <div><label class="text-sm font-medium mb-1 block">TikTok URL</label><input type="url" name="tiktok_url" value="<?= e($property['tiktok_url'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="https://tiktok.com/@yourpage"></div>
        <div><label class="text-sm font-medium mb-1 block">อีเมล</label><input type="email" name="contact_email" value="<?= e($property['contact_email'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="contact@example.com"></div>
        <div class="md:col-span-2"><label class="text-sm font-medium mb-1 block">เว็บไซต์</label><input type="url" name="website_url" value="<?= e($property['website_url'] ?? '') ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm" placeholder="https://"></div>
      </div>
    </div>

    <!-- Amenities -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold mb-3 flex items-center gap-2"><i data-lucide="check-circle" class="w-5 h-5 text-accent-600"></i> สิ่งอำนวยความสะดวก</h3>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
        <?php foreach ($amenities as $a):
          $checked = in_array($a['id'], $selectedAmenities); ?>
        <label class="flex items-center gap-2 px-3 py-2 border-2 rounded-lg cursor-pointer transition <?= $checked ? 'border-accent-500 bg-accent-50' : 'border-slate-200 hover:border-slate-300' ?>">
          <input type="checkbox" name="amenities[]" value="<?= $a['id'] ?>" <?= $checked?'checked':'' ?> class="rounded">
          <i data-lucide="<?= e($a['icon'] ?: 'check') ?>" class="w-4 h-4 text-accent-600"></i>
          <span class="text-sm"><?= e($a['name']) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
      <h3 class="font-bold flex items-center gap-2 mb-3"><i data-lucide="search" class="w-5 h-5 text-accent-600"></i> SEO</h3>
      <p class="text-xs text-slate-600 mb-3 leading-relaxed">
        แนะนำให้กรอกทั้งสองช่องเพื่อผลค้นหา · ใส่<strong>โซนหรือประเภท</strong>ในชื่อ title เช่น «แพ OO เขื่อนศรีนครินทร์ — แพพักริมน้ำ» เพื่อคีย์เวิร์ดใน Google · Title ~50–60 ตัวอักษร · Description ~150–160 ตัวอักษร · ไม่ซ้ำกับที่พักอื่นมากเกินไป · ถ้าว่าง ระบบจะใช้ชื่อแพและคำอธิบายหลักแทน
      </p>
      <div class="space-y-3">
        <div>
          <label class="text-sm font-medium mb-1 block">Meta Title</label>
          <input type="text" name="meta_title" value="<?= e($property['meta_title'] ?? '') ?>" maxlength="255" class="w-full px-3 py-2 rounded-lg border border-slate-300" placeholder="เช่น แพ OO เขื่อนศรีนครินทร์ — แพพักริมน้ำ กาญจนบุรี">
        </div>
        <div>
          <label class="text-sm font-medium mb-1 block">Meta Description</label>
          <textarea name="meta_description" rows="2" maxlength="500" class="w-full px-3 py-2 rounded-lg border border-slate-300"><?= e($property['meta_description'] ?? '') ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- Sidebar -->
  <aside class="lg:col-span-1">
    <div class="lg:sticky lg:top-24 space-y-4">
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
        <button type="submit" class="w-full py-2.5 bg-accent-500 hover:bg-accent-600 text-white font-semibold rounded-lg inline-flex items-center justify-center gap-2"><i data-lucide="save" class="w-4 h-4"></i> <?= $isEdit?'บันทึก':'สร้างที่พัก' ?></button>
        <?php if ($isEdit): ?>
        <button type="submit" formaction="<?= $propUrl('/' . $property['id'] . '/delete') ?>" formmethod="post" onclick="return confirm('ยืนยันลบที่พักนี้? การจองและรีวิวจะถูกลบไปด้วย')" class="w-full py-2 bg-rose-50 text-rose-600 border border-rose-200 rounded-lg text-sm hover:bg-rose-100 inline-flex items-center justify-center gap-2"><i data-lucide="trash-2" class="w-4 h-4"></i> ลบที่พักนี้</button>
        <?php endif; ?>
      </div>

      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 space-y-3">
        <h4 class="font-bold flex items-center gap-2"><i data-lucide="settings-2" class="w-5 h-5 text-accent-600"></i> ความสามารถการจอง</h4>
        <?php
        $capLabels = [
          'allow_contact'          => ['แสดงปุ่มโทร', 'ลูกค้าเห็นปุ่มโทร (เจ้าของต้องกรอกเบอร์ในช่องทางติดต่อ)', 'phone'],
          'coupon_enabled'         => ['ใช้คูปอง', 'แสดงปุ่มซื้อคูปองบนหน้าแพ — ลูกค้าใช้คูปองตอนจอง/ชำระเงิน', 'ticket'],
          'allow_online_booking'   => ['จองออนไลน์', 'จองผ่านระบบแพกาญ (ฟอร์มจอง)', 'calendar-check'],
        ];
        if ($canManageBookingMode): ?>
        <div x-data class="space-y-2">
        <p class="text-xs text-slate-500">เลือกได้หลายข้อพร้อมกัน — ต้องเปิดอย่างน้อย 1 ข้อ</p>
        <?php foreach ($capLabels as $field => [$title, $desc, $icon]):
          $checked = !empty($bookingCaps[$field]);
        ?>
        <label class="flex items-start gap-2.5 p-2.5 border-2 rounded-lg cursor-pointer transition <?= $checked ? 'border-accent-500 bg-accent-50' : 'border-slate-200 hover:border-slate-300' ?>">
          <input type="checkbox" name="<?= e($field) ?>" value="1" <?= $checked ? 'checked' : '' ?> class="mt-0.5 rounded"
                 <?= $field === 'allow_online_booking' ? '@change="$refs.paySlip.disabled = !$el.checked; if(!$el.checked) $refs.paySlip.checked = false"' : '' ?>>
          <div>
            <div class="text-sm font-semibold flex items-center gap-1.5"><i data-lucide="<?= e($icon) ?>" class="w-4 h-4 text-accent-600"></i><?= e($title) ?></div>
            <div class="text-xs text-slate-500 mt-0.5"><?= e($desc) ?></div>
          </div>
        </label>
        <?php endforeach; ?>

        <?php if (\App\Core\Database::tableHasColumn('properties', 'show_line_contact')):
          $lineChecked = !empty($bookingCaps['show_line_contact']);
        ?>
        <label class="flex items-start gap-2.5 p-2.5 border-2 rounded-lg cursor-pointer transition <?= $lineChecked ? 'border-[#06C755] bg-[#06C755]/5' : 'border-slate-200 hover:border-slate-300' ?>">
          <input type="checkbox" name="show_line_contact" value="1" <?= $lineChecked ? 'checked' : '' ?> class="mt-0.5 rounded">
          <div>
            <div class="text-sm font-semibold flex items-center gap-1.5">
              <svg class="w-4 h-4 text-[#06C755]" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>
              แสดงปุ่ม LINE
            </div>
            <div class="text-xs text-slate-500 mt-0.5">ลูกค้าเห็นปุ่ม Add LINE (เจ้าของต้องกรอก LINE ID ในช่องทางติดต่อ)</div>
          </div>
        </label>
        <?php endif; ?>

        <label class="ml-4 flex items-start gap-2.5 p-2.5 border rounded-lg cursor-pointer transition <?= !empty($bookingCaps['booking_requires_payment']) ? 'border-accent-300 bg-accent-50/50' : 'border-slate-200' ?>">
          <input type="checkbox" name="booking_requires_payment" value="1" x-ref="paySlip"
                 <?= !empty($bookingCaps['booking_requires_payment']) ? 'checked' : '' ?>
                 <?= empty($bookingCaps['allow_online_booking']) ? 'disabled' : '' ?>
                 class="mt-0.5 rounded">
          <div>
            <div class="text-sm font-semibold flex items-center gap-1.5"><i data-lucide="shield-check" class="w-4 h-4 text-accent-600"></i> บังคับอัปโหลดสลิป</div>
            <div class="text-xs text-slate-500 mt-0.5">ใช้กับปุ่ม «จองที่พัก» — ลูกค้าต้องชำระและอัปโหลดหลักฐาน</div>
          </div>
        </label>
        </div>

        <?php if ($route_prefix === 'admin' && $show_coupon_contract_field): ?>
        <hr class="my-2">
        <div>
          <label class="flex items-center gap-2 text-sm font-medium mb-1"><i data-lucide="file-signature" class="w-4 h-4 text-slate-600"></i> บันทึกสัญญาร่วมคูปอง</label>
          <input type="datetime-local" name="coupon_contract_signed_at" value="<?= e(old('coupon_contract_signed_at', $ccSignedVal)) ?>" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm">
          <p class="text-xs text-slate-500 mt-1">ว่าง = ยังไม่ลงนาม · ใช้ติดตาม checklist Phase คูปอง</p>
        </div>
        <?php endif; ?>
        <?php elseif (!$isEdit): ?>
        <p class="text-xs text-slate-600 leading-relaxed">หลัง Admin ตรวจและอนุมัติที่พัก แอดมินจะเป็นผู้กำหนด<strong>โหมดการจอง</strong>และ<strong>การรับคูปอง</strong>ให้ — คุณลงข้อมูลที่พักไว้ก่อนได้</p>
        <?php else:
          $capSummary = [
            ['allow_contact', 'ปุ่มโทร', 'phone'],
            ['show_line_contact', 'ปุ่ม LINE', 'message-circle'],
            ['coupon_enabled', 'ใช้คูปอง', 'ticket'],
            ['allow_online_booking', 'จองออนไลน์', 'calendar-check'],
          ];
        ?>
        <div class="space-y-2 text-sm">
          <?php foreach ($capSummary as [$key, $label, $icon]):
            if ($key === 'show_line_contact' && !\App\Core\Database::tableHasColumn('properties', 'show_line_contact')) {
              continue;
            }
            $on = !empty($bookingCaps[$key]);
          ?>
          <div class="flex items-center gap-2 <?= $on ? 'text-slate-800' : 'text-slate-400' ?>">
            <i data-lucide="<?= e($icon) ?>" class="w-4 h-4 shrink-0"></i>
            <span><?= e($label) ?>: <span class="font-medium"><?= $on ? 'เปิด' : 'ปิด' ?></span></span>
          </div>
          <?php endforeach; ?>
          <?php if (!empty($bookingCaps['allow_online_booking'])): ?>
          <div class="flex items-center gap-2 text-slate-700">
            <i data-lucide="shield-check" class="w-4 h-4 shrink-0"></i>
            <span>บังคับสลิป: <span class="font-medium"><?= !empty($bookingCaps['booking_requires_payment']) ? 'เปิด' : 'ปิด' ?></span></span>
          </div>
          <?php endif; ?>
          <p class="text-xs text-slate-500 mt-2">หากต้องการเปลี่ยน กรุณาติดต่อแอดมิน</p>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($isEdit): ?>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
        <h4 class="font-bold flex items-center gap-2 mb-2"><i data-lucide="image" class="w-5 h-5 text-accent-600"></i> รูปหน้าปก</h4>
        <?php if ($property['cover_image']): ?>
          <img src="<?= e(upload_url($property['cover_image'])) ?>" class="w-full aspect-[16/9] object-cover rounded-lg mb-2">
        <?php endif; ?>
        <input type="file" name="cover_image" accept="image/*" class="w-full text-sm">
        <p class="text-xs text-slate-500 mt-1">เปลี่ยนรูปหน้าปก (ไม่บังคับ)</p>
      </div>
      <?php else: ?>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
        <h4 class="font-bold flex items-center gap-2 mb-2"><i data-lucide="image" class="w-5 h-5 text-accent-600"></i> รูปหน้าปก</h4>
        <input type="file" name="cover_image" accept="image/*" class="w-full text-sm">
        <p class="text-xs text-slate-500 mt-1">รูปจะแสดงเป็นรูปหลักของที่พัก</p>
      </div>
      <?php endif; ?>

      <?php if ($isEdit): ?>
      <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 text-sm">
        <h4 class="font-bold flex items-center gap-2 mb-2"><i data-lucide="info" class="w-5 h-5 text-accent-600"></i> สถานะ</h4>
        <?php if ($route_prefix === 'owner' && !\App\Core\Auth::isAdmin()):
          $st = $property['status'] ?? '';
          $statusHints = [
            'draft' => 'ฉบับร่าง — แก้ไขและประสานแอดมินเมื่อพร้อมให้ตรวจ',
            'pending' => 'อยู่ระหว่างรอ Admin อนุมัติ',
            'published' => 'เผยแพร่บนเว็บแล้ว',
            'rejected' => 'ไม่ผ่านการอนุมัติ — ติดต่อแอดมินเพื่อแก้ไข',
            'archived' => 'ซ่อนจากหน้าเว็บชั่วคราว',
          ];
          if (in_array($st, ['published', 'archived'], true)): ?>
        <label class="block text-sm font-medium text-slate-700 mb-1">การแสดงผลบนเว็บ</label>
        <select name="listing_visibility" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm mb-2">
          <option value="published" <?= $st === 'published' ? 'selected' : '' ?>>แสดงบนเว็บ (เผยแพร่)</option>
          <option value="archived" <?= $st === 'archived' ? 'selected' : '' ?>>ซ่อนชั่วคราว (ไม่แสดงในรายการ)</option>
        </select>
        <p class="text-xs text-slate-500 mb-2">เปิดหรือปิดการแสดงผลได้เอง — โหมดการจองปรับได้เฉพาะแอดมิน</p>
          <?php else: ?>
        <div class="font-semibold"><?= e($st) ?></div>
        <p class="text-xs text-slate-600 mt-1"><?= e($statusHints[$st] ?? '') ?></p>
          <?php endif; ?>
        <?php else: ?>
        <div>สถานะปัจจุบัน: <span class="font-semibold"><?= e($property['status']) ?></span></div>
        <div class="text-xs text-slate-500 mt-1"><?= $property['status']==='pending' ? 'อยู่ระหว่างรอ Admin อนุมัติ' : '' ?></div>
        <?php endif; ?>
        <div class="mt-2 text-xs text-slate-500 leading-relaxed">
          ลิงก์หน้ารายละเอียด — ระบบสร้างให้อัตโนมัติ<br>
          <code class="font-mono">/property/<?= e($property['slug']) ?></code>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </aside>

  <?php if (\App\Core\Database::tableHasColumn('properties', 'line_messaging_enabled') && $isEdit): ?>
  <?php
    $showLineHubBlock = $route_prefix !== 'owner'
      || \App\Services\OwnerFeatureGate::allowed(\App\Services\OwnerTier::FEATURE_LINE_HUB);
  ?>
  <?php if (!$showLineHubBlock): ?>
  <div class="lg:col-span-3 bg-sky-50 rounded-2xl border border-sky-200 shadow-soft p-5">
    <p class="font-bold text-sky-900">LINE Hub — ต้องสมัครแพ็กเกจ Starter ขึ้นไป</p>
    <p class="text-sm text-sky-800 mt-1">ตั้งค่า LINE OA, Chatbot และปฏิทินที่ลูกค้าเห็นใน LINE ได้เมื่ออัปเกรด</p>
    <a href="<?= url('/owner/membership') ?>" class="inline-block mt-3 text-sm font-semibold text-sky-700 hover:underline">ดูแพ็กเกจ →</a>
  </div>
  <?php else: ?>
  <div class="lg:col-span-3 bg-gradient-to-br from-[#06C755]/5 to-accent-50 rounded-2xl border border-[#06C755]/20 shadow-soft p-5">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <h4 class="font-bold flex items-center gap-2 text-slate-800">
          <svg class="w-5 h-5 text-[#06C755]" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.03 2 11c0 2.98 1.6 5.6 4.08 7.27L5.5 22l4.15-2.05A10.94 10.94 0 0 0 12 20c5.52 0 10-4.03 10-9S17.52 2 12 2z"/></svg>
          LINE OA & Chatbot
        </h4>
        <p class="text-xs text-slate-600 mt-1 max-w-md">
          ตั้งค่า Token, Rich Menu, ทดสอบส่งข้อความ และเชื่อมกับปฏิทินวันว่างที่ลูกค้าเห็นใน LINE
        </p>
        <?php if (!empty($property['line_messaging_enabled'])): ?>
        <span class="inline-block mt-2 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">เปิดใช้งานแล้ว</span>
        <?php endif; ?>
      </div>
      <div class="flex flex-wrap gap-2">
        <a href="<?= url('/owner/properties/' . (int)$property['id'] . '/line') ?>"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#06C755] hover:bg-[#05a847] text-white text-sm font-semibold rounded-xl transition">
          <i data-lucide="settings" class="w-4 h-4"></i> ตั้งค่า LINE
        </a>
        <?php if ($route_prefix !== 'owner' || ($canAvailability ?? \App\Services\OwnerFeatureGate::allowed(\App\Services\OwnerTier::FEATURE_AVAILABILITY))): ?>
        <a href="<?= url('/owner/properties/' . (int)$property['id'] . '/availability') ?>"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-semibold rounded-xl transition">
          <i data-lucide="calendar" class="w-4 h-4"></i> ปฏิทินวันว่าง
        </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>

</form>
<script>
window.__ZONE_DISTRICT_MAP__ = <?= json_encode($zoneDistrictMap, JSON_UNESCAPED_UNICODE) ?>;
window.__ZONE_OPTIONS__ = <?= json_encode(array_values(array_map(
    static fn(string $z): array => ['value' => $z, 'label' => $zoneOptionLabels[$z] ?? $z],
    $zone_options_list
)), JSON_UNESCAPED_UNICODE) ?>;
(function () {
  const districtEl = document.getElementById('owner-property-district');
  const zoneEl = document.getElementById('owner-property-zone');
  const hintEl = document.getElementById('owner-property-zone-hint');
  if (!districtEl || !zoneEl) return;

  const map = window.__ZONE_DISTRICT_MAP__ || {};
  const allOptions = window.__ZONE_OPTIONS__ || [];
  const placeholder = '— เลือกโซน / พื้นที่ —';

  function addOption(parent, opt, selected) {
    const o = document.createElement('option');
    o.value = opt.value;
    o.textContent = opt.label;
    if (selected) o.selected = true;
    parent.appendChild(o);
  }

  function rebuildZoneSelect() {
    const district = districtEl.value.trim();
    const current = zoneEl.value;
    const recommended = district && Array.isArray(map[district]) ? map[district] : [];
    const recommendedSet = new Set(recommended);

    zoneEl.innerHTML = '';
    const emptyOpt = document.createElement('option');
    emptyOpt.value = '';
    emptyOpt.textContent = placeholder;
    zoneEl.appendChild(emptyOpt);

    if (recommended.length > 0) {
      const groupRec = document.createElement('optgroup');
      groupRec.label = 'แนะนำสำหรับ ' + district;
      recommended.forEach(function (val) {
        const opt = allOptions.find(function (o) { return o.value === val; });
        if (opt) addOption(groupRec, opt, current === opt.value);
      });
      if (groupRec.children.length) zoneEl.appendChild(groupRec);
    }

    const groupOther = document.createElement('optgroup');
    groupOther.label = recommended.length > 0 ? 'โซนอื่นทั้งหมด' : 'โซนทั้งหมด';
    allOptions.forEach(function (opt) {
      if (recommendedSet.has(opt.value)) return;
      addOption(groupOther, opt, current === opt.value);
    });
    if (groupOther.children.length) zoneEl.appendChild(groupOther);

    if (current && !zoneEl.querySelector('option[value="' + CSS.escape(current) + '"]')) {
      addOption(zoneEl, { value: current, label: current }, true);
    }

    if (hintEl) {
      if (district && recommended.length === 0) {
        hintEl.textContent = 'ยังไม่มีโซนแนะนำสำหรับอำเภอนี้ — เลือกโซนที่ใกล้เคียงที่สุด หรือติดต่อแอดมิน';
        hintEl.classList.remove('hidden');
      } else {
        hintEl.textContent = '';
        hintEl.classList.add('hidden');
      }
    }

    if (!current && recommended.length === 1) {
      zoneEl.value = recommended[0];
    }
  }

  districtEl.addEventListener('change', rebuildZoneSelect);
  rebuildZoneSelect();
})();
</script>
<script>
(function () {
  const root = document.getElementById('admin-slug-field');
  if (!root) return;

  const autoView = document.getElementById('admin-slug-auto-view');
  const customView = document.getElementById('admin-slug-custom-view');
  const preview = document.getElementById('admin-slug-preview');
  const customFlag = document.getElementById('slug-custom-flag');
  const slugInput = document.getElementById('admin-slug-input');
  const editBtn = document.getElementById('admin-slug-edit-btn');
  const autoBtn = document.getElementById('admin-slug-auto-btn');
  const baseUrl = root.getAttribute('data-base-url') || '';
  const nameInput = document.querySelector('input[name="name"]');
  const nameEnInput = document.querySelector('input[name="name_en"]');

  function slugifyClient(text) {
    return String(text || '').trim().toLowerCase()
      .replace(/\s+/g, '-')
      .replace(/[^a-z0-9\-]/g, '')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '');
  }

  function slugBaseClient(name, nameEn) {
    const en = slugifyClient(nameEn);
    if (en) return en;
    const th = slugifyClient(name);
    if (th) return th;
    return 'property-auto';
  }

  function updatePreview() {
    if (!preview || customFlag.value === '1') return;
    const slug = slugBaseClient(nameInput ? nameInput.value : '', nameEnInput ? nameEnInput.value : '');
    preview.textContent = baseUrl + '/' + slug;
  }

  function showCustom(on) {
    customFlag.value = on ? '1' : '';
    autoView.classList.toggle('hidden', on);
    customView.classList.toggle('hidden', !on);
    if (on && slugInput) {
      slugInput.focus();
    } else {
      updatePreview();
    }
  }

  if (editBtn) editBtn.addEventListener('click', function () { showCustom(true); });
  if (autoBtn) autoBtn.addEventListener('click', function () { showCustom(false); });
  if (nameInput) nameInput.addEventListener('input', updatePreview);
  if (nameEnInput) nameEnInput.addEventListener('input', updatePreview);
  updatePreview();
})();
</script>
<?php if ($isEdit): ?>
<div class="lg:col-span-3 mt-4 bg-white rounded-2xl border border-slate-200 shadow-soft p-5">
  <div class="flex items-center justify-between mb-3">
    <h3 class="font-bold flex items-center gap-2"><i data-lucide="image" class="w-5 h-5 text-accent-600"></i> รูปภาพแกลเลอรี (<?= count($images ?? []) ?>)</h3>
  </div>
  <form method="post" action="<?= $propUrl('/' . $property['id'] . '/images') ?>" enctype="multipart/form-data" class="flex flex-wrap gap-2 mb-4">
    <?= csrf() ?>
    <input type="file" name="image[]" accept="image/*" multiple required class="flex-1 min-w-[200px] text-sm file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:bg-accent-500 file:text-white file:font-semibold file:cursor-pointer">
    <button type="submit" class="px-4 py-2 bg-accent-600 text-white rounded-lg text-sm font-semibold inline-flex items-center gap-1 shrink-0"><i data-lucide="upload" class="w-4 h-4"></i> อัปโหลด</button>
  </form>
  <p class="text-xs text-slate-500 mb-4">เลือกได้หลายรูปพร้อมกัน (Ctrl/Cmd+คลิก หรือลากเลือก)</p>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
    <?php foreach (($images ?? []) as $img): ?>
    <div class="relative group">
      <img src="<?= e(upload_url($img['path'])) ?>" alt="" class="aspect-[4/3] w-full object-cover rounded-lg border border-slate-200">
      <form method="post" action="<?= $propUrl('/' . $property['id'] . '/images/' . $img['id'] . '/delete') ?>" onsubmit="return confirm('ยืนยันลบรูป?')" class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition">
        <?= csrf() ?>
        <button type="submit" class="w-7 h-7 grid place-items-center bg-rose-500 text-white rounded-full"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
      </form>
    </div>
    <?php endforeach; ?>
    <?php if (empty($images ?? [])): ?>
      <div class="col-span-full text-center py-8 text-slate-500 text-sm">ยังไม่มีรูปภาพ</div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

</div>
