<?php
/** @var array $values @var array $font_options */
require_once __DIR__ . '/../_helpers.php';
$ic = settings_input_class();

$navLabels = [
    'nav_label_home' => ['หน้าแรก', 'home', 'เมนูหลัก'],
    'nav_label_rafts' => ['แพพัก', 'anchor', 'เมนูหลัก'],
    'nav_label_resorts' => ['รีสอร์ท', 'trees', 'เมนูหลัก'],
    'nav_label_hotels' => ['โรงแรม', 'building-2', 'เมนูหลัก'],
    'nav_label_stays' => ['โฮมสเตย์ & บ้านพัก', 'home', 'เมนูหลัก'],
    'nav_label_pool_villa' => ['บ้านพูลวิลล่า', 'waves', 'เมนูหลัก'],
    'nav_label_camping' => ['แคมป์', 'tent', 'เมนูหลัก'],
    'nav_label_activities' => ['กิจกรรม', 'map', 'เมนูหลัก'],
    'nav_label_places' => ['ที่เที่ยว', 'map-pin', 'เมนูหลัก'],
    'nav_label_coupons' => ['โปรโมชั่น', 'ticket', 'เมนูหลัก'],
    'nav_label_reviews' => ['รีวิว', 'message-circle', 'เมนูหลัก'],
    'nav_label_blog' => ['บทความ', 'newspaper', 'เมนูหลัก'],
    'nav_label_properties' => ['ค้นหาทั้งหมด', 'search', 'legacy — ไม่อยู่ในเมนูหลัก'],
    'nav_label_contact' => ['ติดต่อเรา', 'mail', 'footer'],
    'nav_label_guest_seek' => ['หาที่พัก', 'search', 'CTA header (ไม่อยู่ในแถบเมนู)'],
];

ob_start();
?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
  <?php foreach ($navLabels as $k => [$placeholder, $icon, $where]): ?>
  <label class="rounded-xl border border-slate-200 bg-slate-50/80 p-3 hover:border-forest-200 transition">
    <span class="text-xs font-bold text-slate-600 mb-1.5 flex items-center gap-1.5">
      <i data-lucide="<?= e($icon) ?>" class="w-3.5 h-3.5 text-forest-700"></i><?= e($placeholder) ?>
      <span class="text-[10px] font-normal text-slate-400">· <?= e($where) ?></span>
    </span>
    <input type="text" name="<?= e($k) ?>" value="<?= e($values[$k] ?? '') ?>" maxlength="40"
           class="w-full px-3 py-2 rounded-lg border border-slate-300 bg-white text-sm"
           placeholder="<?= e($placeholder) ?>">
    <span class="text-[10px] text-slate-400 mt-1 block"><?= e(settings_t('nav.empty_hint', 'ว่าง = ใช้')) ?> «<?= e($placeholder) ?>»</span>
  </label>
  <?php endforeach; ?>
</div>
<?php
$navContent = ob_get_clean();
settings_section(
    settings_t('nav.section_title'),
    'navigation',
    $navContent,
    settings_t('nav.section_intro', ''),
    'text-forest-700'
);
?>

<div class="bg-white rounded-2xl border border-slate-200 shadow-soft p-5 md:p-6 space-y-5" x-data="fontPicker()" x-init="init()">
  <div class="border-b border-slate-100 pb-4 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h3 class="font-bold text-lg flex items-center gap-2 text-slate-900">
        <i data-lucide="type" class="w-5 h-5 text-violet-600"></i> <?= e(settings_t('nav.font_title')) ?>
      </h3>
      <p class="text-sm text-slate-600 leading-relaxed mt-2">
        <?= e(settings_t('nav.font_intro', '')) ?>
      </p>
    </div>
    <button type="button" @click="applyPreview()"
            class="shrink-0 inline-flex items-center gap-1.5 rounded-xl border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-bold text-violet-700 hover:bg-violet-100 transition">
      <i data-lucide="eye" class="w-3.5 h-3.5"></i> <?= e(settings_t('nav.preview_btn', 'Preview')) ?>
    </button>
  </div>

  <div class="grid md:grid-cols-3 gap-4">
    <div>
      <label class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-1.5 block">
        <i data-lucide="align-left" class="w-3.5 h-3.5 text-slate-400"></i> ฟอนต์เนื้อหา (Body)
      </label>
      <p class="text-xs text-slate-500 mb-2">ใช้กับย่อหน้า ปุ่ม และข้อความทั่วไป</p>
      <select name="font_body" x-model="bodyKey" @change="applyPreview()" class="<?= $ic ?>">
        <?php foreach ($font_options as $key => $opt): ?>
          <option value="<?= e($key) ?>" <?= ($values['font_body'] ?? 'noto_sans_thai') === $key ? 'selected' : '' ?>>
            <?= e($opt['name']) ?> — <?= e($opt['tag']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-1.5 block">
        <i data-lucide="heading" class="w-3.5 h-3.5 text-slate-400"></i> ฟอนต์หัวข้อ (Heading)
      </label>
      <p class="text-xs text-slate-500 mb-2">ใช้กับ H1–H3 และหัวข้อการ์ด</p>
      <select name="font_heading" x-model="headKey" @change="applyPreview()" class="<?= $ic ?>">
        <?php foreach ($font_options as $key => $opt): ?>
          <option value="<?= e($key) ?>" <?= ($values['font_heading'] ?? 'kanit') === $key ? 'selected' : '' ?>>
            <?= e($opt['name']) ?> — <?= e($opt['tag']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-1.5 block">
        <i data-lucide="text-cursor-input" class="w-3.5 h-3.5 text-slate-400"></i>
        ขนาดฐาน: <span class="font-extrabold text-violet-700" x-text="fontSize + 'px'"></span>
      </label>
      <p class="text-xs text-slate-500 mb-2">ขนาดตัวอักษรพื้นฐานทั้งเว็บ (13–18px)</p>
      <input type="range" name="font_size_base" min="13" max="18" step="1"
             x-model.number="fontSize" @input="applyPreview()"
             value="<?= (int)($values['font_size_base'] ?? 15) ?>"
             class="w-full accent-violet-600">
      <div class="flex justify-between text-[10px] text-slate-400 mt-0.5">
        <span>13px เล็ก</span><span>15px กลาง</span><span>18px ใหญ่</span>
      </div>
      <div class="mt-2 grid grid-cols-3 gap-1">
        <?php foreach ([13 => 'เล็ก', 15 => 'กลาง', 16 => 'ใหญ่ขึ้น'] as $sz => $lbl): ?>
          <button type="button" @click="fontSize=<?= $sz ?>; applyPreview()"
                  class="rounded-lg border px-2 py-1.5 text-[11px] font-semibold transition"
                  :class="fontSize==<?= $sz ?> ? 'border-violet-400 bg-violet-50 text-violet-700' : 'border-slate-200 text-slate-500 hover:bg-slate-50'">
            <?= $sz ?>px <?= $lbl ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div id="font-preview-box" class="rounded-2xl border border-dashed border-violet-200 bg-violet-50/50 p-5 space-y-3">
    <h3 id="fp-heading" class="text-xl font-extrabold text-slate-900 leading-snug">ดูที่พักครบทุกประเภทในกาญจนบุรี</h3>
    <p id="fp-body" class="text-slate-700 leading-relaxed">ค้นหาที่พักกาญจนบุรีที่ตรงใจ — แพ รีสอร์ท โรงแรม บ้านพัก โฮมสเตย์ รีวิวจริง 100% ใช้คูปองเงินสดลดค่าที่พักได้ทันที</p>
    <div id="fp-chips" class="flex flex-wrap gap-2 text-sm">
      <span class="inline-flex items-center gap-1 rounded-full bg-white border border-slate-200 px-3 py-1 font-semibold text-slate-700">แพพัก ฿2,500/คืน</span>
      <span class="inline-flex items-center gap-1 rounded-full bg-emerald-600 px-3 py-1 font-bold text-white">ดูรายละเอียด</span>
    </div>
    <div id="fp-nav" class="flex flex-wrap gap-x-2 gap-y-1 text-xs font-semibold text-slate-600 border-t border-violet-100 pt-3">
      <span>หน้าแรก</span><span>·</span><span>แพพัก</span><span>·</span><span>รีสอร์ท</span><span>·</span><span>กิจกรรม</span><span>·</span><span>ที่เที่ยว</span>
      <span class="w-full text-[10px] font-normal text-slate-400 mt-1">+ ปุ่ม «หาที่พัก» แยกทางขวา header</span>
    </div>
    <p class="text-[11px] text-slate-400">* มีผลกับเว็บจริงหลังบันทึกและรีเฟรชหน้า</p>
  </div>
</div>
