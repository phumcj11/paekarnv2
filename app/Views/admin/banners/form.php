<?php
/** @var ?array $banner @var array $labels @var array $allSlots @var array $recommendedSpecs @var array $anchorLinks @var array $screenBadges @var array $placementHints */
$isEdit = !empty($banner['id']);
$initialSlot = ($banner['slot'] ?? '') !== '' ? (string)$banner['slot'] : (string)($allSlots[0] ?? 'hero');
$xDataJson = htmlspecialchars(json_encode([
    'slot' => $initialSlot,
    'specs' => $recommendedSpecs,
    'anchors' => $anchorLinks,
    'badges' => $screenBadges,
    'hints' => $placementHints,
], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
?>
<form method="post" action="<?= url($isEdit ? '/admin/banners/'.$banner['id'] : '/admin/banners') ?>" enctype="multipart/form-data" class="max-w-3xl space-y-6" x-data="<?= $xDataJson ?>">
  <?= csrf() ?>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-6 space-y-4">
    <h3 class="font-bold text-lg"><?= $isEdit ? 'แก้ไข Banner' : 'เพิ่ม Banner' ?></h3>

    <div>
      <label class="text-sm font-medium mb-1 block">ตำแหน่งบนหน้าแรก *</label>
      <select name="slot" required x-model="slot" class="w-full px-3 py-2.5 rounded-xl border border-slate-300">
        <?php foreach ($allSlots as $s): ?>
          <option value="<?= e($s) ?>" <?= ($banner['slot'] ?? '') === $s ? 'selected' : '' ?>><?= e(($labels[$s] ?? $s) . ' ('.$s.')') ?></option>
        <?php endforeach; ?>
      </select>
      <p class="mt-2 text-[11px] text-slate-600 leading-relaxed rounded-lg border border-sky-100 bg-sky-50 px-3 py-2" x-show="specs[slot]" x-cloak>
        <span class="font-semibold text-sky-900">ขนาดภาพแนะนำ:</span>
        <span class="text-sky-950/90" x-text="specs[slot]"></span>
      </p>
      <div class="mt-3 rounded-xl border border-emerald-100 bg-emerald-50/70 p-3" x-cloak>
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <span class="text-xs font-bold text-emerald-900">ตำแหน่งบนหน้าแรก</span>
              <span class="rounded-full bg-white px-2 py-0.5 text-[11px] font-bold text-emerald-800 ring-1 ring-emerald-200" x-text="badges[slot] || 'All screens'"></span>
            </div>
            <p class="mt-1 text-[11px] leading-relaxed text-emerald-950/80" x-text="hints[slot] || 'ตำแหน่ง Banner บนหน้าแรก'"></p>
          </div>
          <div class="flex shrink-0 flex-wrap gap-1.5">
            <template x-for="link in (anchors[slot] || [])" :key="link.url">
              <a :href="link.url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-lg bg-white px-2.5 py-1.5 text-[11px] font-bold text-primary-700 ring-1 ring-primary-100 hover:bg-primary-50">
                <span x-text="link.label"></span>
                <i data-lucide="external-link" class="h-3 w-3"></i>
              </a>
            </template>
          </div>
        </div>
        <div class="mt-3 grid grid-cols-[0.9fr_1.1fr] gap-2 rounded-lg bg-white/80 p-2 ring-1 ring-emerald-100">
          <div class="rounded-lg bg-gradient-to-br from-slate-800 to-emerald-700 p-2 text-[10px] font-bold text-white">Hero / Search</div>
          <div class="space-y-1.5">
            <div class="h-5 rounded bg-amber-100"></div>
            <div class="h-5 rounded bg-sky-100"></div>
            <div class="h-5 rounded bg-emerald-100"></div>
          </div>
        </div>
      </div>
    </div>

    <div>
      <label class="text-sm font-medium mb-1 block">หัวข้อหลัก</label>
      <input type="text" name="title" value="<?= e($banner['title'] ?? '') ?>" maxlength="180" class="w-full px-3 py-2.5 rounded-xl border border-slate-300" placeholder="เช่น โปรหน้าร้อน">
    </div>
    <div>
      <label class="text-sm font-medium mb-1 block">หัวข้อย่อย</label>
      <textarea name="subtitle" rows="2" maxlength="255" class="w-full px-3 py-2.5 rounded-xl border border-slate-300" placeholder="คำอธิบายสั้นๆ"><?= e($banner['subtitle'] ?? '') ?></textarea>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium mb-1 block">ลิงก์เมื่อคลิก</label>
        <input type="text" name="link_url" value="<?= e($banner['link_url'] ?? '') ?>" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 font-mono text-sm" placeholder="/properties หรือ https://">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">ข้อความปุ่ม (Hero / แถบคูปอง desktop)</label>
        <input type="text" name="button_text" value="<?= e($banner['button_text'] ?? '') ?>" maxlength="80" class="w-full px-3 py-2.5 rounded-xl border border-slate-300" placeholder="เช่น ค้นหาที่พัก · ดูรายละเอียดเพิ่มเติม">
      </div>
    </div>

    <div class="border border-dashed border-slate-300 rounded-xl p-4 space-y-3 bg-slate-50/50">
      <label class="text-sm font-medium block">รูปภาพ *</label>
      <?php if ($isEdit && !empty($banner['image_path'])): ?>
        <div class="flex gap-3 items-start">
          <img src="<?= e(upload_url($banner['image_path'])) ?>" alt="" class="w-40 h-24 object-cover rounded-lg border border-slate-200">
          <p class="text-xs text-slate-500">อัปโหลดใหม่หรือใส่ URL ด้านล่างเพื่อแทนที่</p>
        </div>
      <?php endif; ?>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" class="block w-full text-sm">
      <div class="text-xs text-slate-500">หรือ URL รูปภาพ (Unsplash / CDN)</div>
      <input type="text" name="image_url" class="w-full px-3 py-2 rounded-lg border border-slate-300 font-mono text-xs" placeholder="https://...">
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label class="text-sm font-medium mb-1 block">ลำดับ (sort)</label>
        <input type="number" name="sort_order" value="<?= e((string)($banner['sort_order'] ?? 0)) ?>" class="w-full px-3 py-2 rounded-xl border border-slate-300">
      </div>
      <div class="md:col-span-2 flex items-end">
        <label class="inline-flex items-center gap-2 cursor-pointer">
          <input type="checkbox" name="is_active" value="1" <?= !isset($banner['is_active']) || $banner['is_active'] ? 'checked' : '' ?> class="rounded border-slate-300">
          <span class="text-sm font-medium">แสดงบนเว็บ</span>
        </label>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="text-sm font-medium mb-1 block">เริ่มแสดง (ว่าง = ทันที)</label>
        <input type="datetime-local" name="starts_at" value="<?= !empty($banner['starts_at']) ? date('Y-m-d\TH:i', strtotime($banner['starts_at'])) : '' ?>" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm">
      </div>
      <div>
        <label class="text-sm font-medium mb-1 block">สิ้นสุดแสดง (ว่าง = ไม่กำหนด)</label>
        <input type="datetime-local" name="ends_at" value="<?= !empty($banner['ends_at']) ? date('Y-m-d\TH:i', strtotime($banner['ends_at'])) : '' ?>" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-sm">
      </div>
    </div>
  </div>

  <div class="flex justify-between gap-3">
    <a href="<?= url('/admin/banners') ?>" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 font-semibold text-sm">ยกเลิก</a>
    <button type="submit" class="px-6 py-2.5 rounded-xl bg-primary-700 hover:bg-primary-800 text-white font-bold text-sm shadow-soft">บันทึก</button>
  </div>
</form>
