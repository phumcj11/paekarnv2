<?php
/** @var array $values @var array $zones_for_covers @var array $zone_covers @var array $home_featured_labels @var array $home_sections_order @var array $home_zone_sections @var array $home_section_labels */
require_once __DIR__ . '/../_helpers.php';
$ic = settings_input_class();
use App\Support\HomepageSections;

ob_start();
?>
<div class="grid md:grid-cols-2 gap-4">
  <?php
  $heroFields = [
      'home_hero_desktop_title_line1' => ['หัวข้อใหญ่ (เดสก์ท็อป) — บรรทัดที่ 1', 'เช่น จองแพกาญง่าย'],
      'home_hero_desktop_title_line2' => ['หัวข้อใหญ่ (เดสก์ท็อป) — บรรทัดที่ 2', 'เช่น ได้ส่วนลดทันที'],
      'home_hero_mobile_title_line1' => ['หัวข้อ (มือถือ) — บรรทัดที่ 1', 'เช่น แพพักกาญจนบุรี'],
      'home_hero_mobile_title_line2' => ['หัวข้อ (มือถือ) — บรรทัดที่ 2', 'เช่น จองง่าย ได้ส่วนลดทันที'],
  ];
  foreach ($heroFields as $k => [$label, $ph]):
      ob_start();
      ?>
      <input type="text" name="<?= e($k) ?>" value="<?= e($values[$k] ?? '') ?>" maxlength="120" class="<?= $ic ?>" placeholder="<?= e($ph) ?>">
      <?php
      settings_field($label, ob_get_clean(), 'เว้นว่าง = ใช้ข้อความเริ่มต้นของระบบ');
  endforeach;
  ob_start();
  ?>
  <input type="text" name="home_hero_promo_line" value="<?= e($values['home_hero_promo_line'] ?? '') ?>" maxlength="200" class="<?= $ic ?>" placeholder="เช่น ซื้อคูปอง 250.- ลดทันที 500.-">
  <?php
  settings_field(
      'ข้อความโปรโมชัน (สีเหลือง)',
      ob_get_clean(),
      'แสดงทับสไลด์ Hero — ใช้ทั้งมือถือและเดสก์ท็อป',
      'ข้อความสั้น เน้นส่วนลดหรือโปรหลัก'
  );
  for ($bi = 1; $bi <= 4; $bi++):
      $k = 'home_hero_bullet_' . $bi;
      ob_start();
      ?>
      <input type="text" name="<?= e($k) ?>" value="<?= e($values[$k] ?? '') ?>" maxlength="300" class="<?= $ic ?>">
      <?php
      settings_field(
          'ข้อความพร้อมเครื่องหมายถูก — ข้อ ' . $bi,
          ob_get_clean(),
          'แสดงเฉพาะบนเดสก์ท็อปใต้หัวข้อ Hero'
      );
  endfor;
  $trustImg = trim((string)($values['trust_hero_image'] ?? ''));
  $trustPreviewUrl = '';
  if ($trustImg !== '') {
      $trustPreviewUrl = preg_match('#^https?://#i', $trustImg)
          ? $trustImg
          : (function_exists('upload_url') ? (upload_url($trustImg) ?: '') : '');
  }
  ?>
  <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50/80 p-4 space-y-3">
    <span class="text-sm font-semibold text-slate-800 block">รูปบล็อก «ทำไมต้องเลือกแพกาญ»</span>
    <p class="text-xs text-slate-600 leading-relaxed">อัปโหลดรูปได้โดยตรง หรือใส่ URL/path — ว่างทุกอย่าง = ใช้รูปสต็อกเริ่มต้น · แนะนำแนวนอน 1600×900</p>
    <?php if ($trustPreviewUrl !== ''): ?>
      <div class="flex flex-wrap items-start gap-3">
        <img src="<?= e($trustPreviewUrl) ?>" alt="" class="max-h-28 rounded-lg border border-slate-200 object-cover shadow-sm bg-white" referrerpolicy="no-referrer">
        <label class="inline-flex items-center gap-2 text-xs font-medium text-rose-700 cursor-pointer select-none">
          <input type="checkbox" name="trust_hero_image_clear" value="1" class="rounded border-slate-300 text-rose-600">
          ล้างรูป (กลับไปใช้สต็อกเริ่มต้น)
        </label>
      </div>
    <?php endif; ?>
    <div>
      <label class="text-xs font-semibold text-slate-600 mb-1 block">อัปโหลดรูป</label>
      <input type="file" name="trust_hero_image_upload" accept="image/jpeg,image/png,image/webp,image/gif"
             class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-accent-500 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white hover:file:bg-accent-600 bg-white rounded-lg border border-slate-300 px-2 py-1.5">
    </div>
    <div>
      <label class="text-xs font-semibold text-slate-600 mb-1 block">หรือ URL / path</label>
      <input type="text" name="trust_hero_image" value="<?= e($values['trust_hero_image'] ?? '') ?>" maxlength="500" class="<?= $ic ?> font-mono text-sm" placeholder="https://... หรือ banners/....webp">
    </div>
  </div>
</div>
<?php
$heroContent = ob_get_clean();
settings_section(
    settings_t('homepage.hero_section_title'),
    'layout-template',
    $heroContent,
    settings_t('homepage.hero_section_intro', ''),
    'text-sky-600'
);

ob_start();
?>
<?php if (empty($zones_for_covers)): ?>
  <p class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
    <?= e(settings_t('homepage.zone_empty', '')) ?>
  </p>
<?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
    <?php foreach ($zones_for_covers as $z):
        $zname = (string)($z['zone'] ?? '');
        if ($zname === '') {
            continue;
        }
        $cur = $zone_covers[$zname] ?? '';
        ?>
    <div class="rounded-xl border border-slate-200 p-4 space-y-2 bg-slate-50/80">
      <input type="hidden" name="zone_cover_zone[]" value="<?= e($zname) ?>">
      <div class="font-semibold text-slate-900"><?= e($zname) ?></div>
      <?php if ($cur !== ''): ?>
        <div class="relative aspect-[4/3] rounded-lg overflow-hidden bg-slate-200 ring-1 ring-slate-200">
          <img src="<?= e(upload_url($cur)) ?>" alt="" class="w-full h-full object-cover">
        </div>
        <label class="inline-flex items-center gap-2 text-xs text-rose-700 cursor-pointer select-none">
          <input type="checkbox" name="zone_cover_remove[]" value="<?= e($zname) ?>" class="rounded border-slate-400">
          ลบรูปนี้
        </label>
      <?php endif; ?>
      <div>
        <label class="text-xs font-medium text-slate-600 block mb-1"><?= $cur !== '' ? 'เปลี่ยนรูป' : 'อัปโหลดรูป' ?></label>
        <input type="file" name="zone_cover_upload[]" accept="image/jpeg,image/png,image/webp,image/gif"
               class="block w-full text-sm text-slate-700 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-forest-800 file:text-white file:text-xs file:font-semibold">
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php
$zoneContent = ob_get_clean();
settings_section(
    settings_t('homepage.zone_section_title'),
    'image',
    $zoneContent,
    settings_t('homepage.zone_section_intro', ''),
    'text-forest-700'
);

ob_start();
$featTypeLabels = [
    'raft'   => 'แพพัก (แถวแรก / Hot Deal)',
    'resort' => 'รีสอร์ท',
    'hotel'  => 'โรงแรม',
    'stay'   => 'โฮมสเตย์ & บ้านพัก',
];
?>
<div class="space-y-4">
  <?php foreach ($featTypeLabels as $fkey => $flabel):
      $frow = $home_featured_labels[$fkey] ?? HomepageSections::defaultFeaturedLabels()[$fkey];
  ?>
  <div class="rounded-xl border border-slate-200 bg-white p-4 grid md:grid-cols-2 gap-3">
    <div class="md:col-span-2 flex items-center justify-between gap-3">
      <span class="font-semibold text-slate-900"><?= e($flabel) ?></span>
      <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
        <input type="checkbox" name="home_feat_<?= e($fkey) ?>_enabled" value="1" class="rounded border-slate-300 text-forest-700" <?= !empty($frow['enabled']) ? 'checked' : '' ?>>
        แสดงบนหน้าแรก
      </label>
    </div>
    <div>
      <label class="text-xs font-semibold text-slate-600 mb-1 block">Eyebrow (ข้อความเล็ก)</label>
      <input type="text" name="home_feat_<?= e($fkey) ?>_eyebrow" value="<?= e($frow['eyebrow'] ?? '') ?>" maxlength="80" class="<?= $ic ?>" placeholder="เช่น Hot Deal">
    </div>
    <div>
      <label class="text-xs font-semibold text-slate-600 mb-1 block">หัวข้อ Section</label>
      <input type="text" name="home_feat_<?= e($fkey) ?>_title" value="<?= e($frow['title'] ?? '') ?>" maxlength="120" class="<?= $ic ?>" placeholder="เช่น Recommended by แพกาญ.com">
    </div>
    <div>
      <label class="text-xs font-semibold text-slate-600 mb-1 block">จำนวนการ์ดสูงสุด</label>
      <input type="number" name="home_feat_<?= e($fkey) ?>_limit" value="<?= (int)($frow['limit'] ?? 8) ?>" min="1" max="24" class="<?= $ic ?> w-28">
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php
$featuredContent = ob_get_clean();
settings_section(
    'Section แนะนำ (Featured)',
    'sparkles',
    $featuredContent,
    'ตั้งชื่อ Hot Deal / Recommended by แพกาญ.com และจำนวนการ์ด — ว่าง = ใช้ค่าเริ่มต้น',
    'text-amber-600'
);

ob_start();
$sectionOrderJson = json_encode(array_values($home_sections_order), JSON_UNESCAPED_UNICODE);
if ($sectionOrderJson === false) {
    $sectionOrderJson = '[]';
}
?>
<input type="hidden" name="home_sections_order" id="home_sections_order" value="<?= e($sectionOrderJson) ?>">
<ul id="home-section-order-list" class="space-y-2">
  <?php foreach ($home_sections_order as $sid):
      $slabel = $home_section_labels[$sid] ?? $sid;
  ?>
  <li class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2" data-section-id="<?= e($sid) ?>">
    <span class="flex-1 text-sm font-medium text-slate-800"><?= e($slabel) ?></span>
    <button type="button" class="home-sec-up text-xs font-bold px-2 py-1 rounded-lg bg-white border border-slate-200 hover:bg-slate-100" aria-label="เลื่อนขึ้น">↑</button>
    <button type="button" class="home-sec-down text-xs font-bold px-2 py-1 rounded-lg bg-white border border-slate-200 hover:bg-slate-100" aria-label="เลื่อนลง">↓</button>
  </li>
  <?php endforeach; ?>
</ul>
<script>
(function () {
  const list = document.getElementById('home-section-order-list');
  const hidden = document.getElementById('home_sections_order');
  if (!list || !hidden) return;
  function sync() {
    const ids = Array.from(list.querySelectorAll('[data-section-id]')).map(function (li) { return li.getAttribute('data-section-id'); });
    hidden.value = JSON.stringify(ids);
  }
  list.addEventListener('click', function (e) {
    const btn = e.target.closest('button');
    if (!btn) return;
    const li = btn.closest('li');
    if (!li) return;
    if (btn.classList.contains('home-sec-up') && li.previousElementSibling) {
      list.insertBefore(li, li.previousElementSibling);
      sync();
    }
    if (btn.classList.contains('home-sec-down') && li.nextElementSibling) {
      list.insertBefore(li.nextElementSibling, li);
      sync();
    }
  });
})();
</script>
<?php
$orderContent = ob_get_clean();
settings_section(
    'ลำดับ Section หน้าแรก',
    'list-ordered',
    $orderContent,
    'ใช้ปุ่ม ↑ ↓ จัดลำดับ — แบนเนอร์โปรโมชันจะติดกับ Section ถัดไปตามเดิม',
    'text-indigo-600'
);

ob_start();
?>
<div class="space-y-3">
  <?php foreach ($home_zone_sections as $i => $z): ?>
  <div class="rounded-xl border border-slate-200 bg-white p-4 grid md:grid-cols-4 gap-3 items-end">
    <input type="hidden" name="home_zone_id[]" value="<?= e($z['id']) ?>">
    <div class="md:col-span-2">
      <label class="text-xs font-semibold text-slate-600 mb-1 block">ชื่อ Section</label>
      <input type="text" name="home_zone_title[]" value="<?= e($z['title']) ?>" maxlength="120" class="<?= $ic ?>">
    </div>
    <div>
      <label class="text-xs font-semibold text-slate-600 mb-1 block">ลำดับในกลุ่มโซน</label>
      <input type="number" name="home_zone_sort[]" value="<?= (int)($z['sort_order'] ?? ($i + 1)) ?>" min="0" max="99" class="<?= $ic ?> w-24">
    </div>
    <div>
      <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
        <input type="checkbox" name="home_zone_enabled[]" value="<?= e($z['id']) ?>" class="rounded border-slate-300 text-forest-700" <?= !empty($z['enabled']) ? 'checked' : '' ?>>
        แสดงบนหน้าแรก
      </label>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<p class="text-xs text-slate-500 mt-3 leading-relaxed">ตำแหน่งการ์ดในแต่ละโzo: ตั้ง <strong>Priority</strong> และ <strong>Featured</strong> ที่ฟอร์มที่พัก · <strong>homepage_priority</strong> ที่ฟอร์มยูนิต (admin)</p>
<?php
$zoneSecContent = ob_get_clean();
settings_section(
    'แพตามโzo (Section โzo)',
    'map-pin',
    $zoneSecContent,
    'เรียงลำดับโzoและเปิด/ปิด — ตำแหน่งการ์ดในโzo ใช้ Priority ที่ที่พัก/ยูนิต',
    'text-teal-700'
);

ob_start();
$boostCoupon = (string)($values['home_sort_boost_coupon'] ?? '0') === '1';
?>
<label class="inline-flex items-start gap-3 cursor-pointer select-none">
  <input type="checkbox" name="home_sort_boost_coupon" value="1" class="mt-1 rounded border-slate-300 text-forest-700" <?= $boostCoupon ? 'checked' : '' ?>>
  <span>
    <span class="block text-sm font-semibold text-slate-900">ดันการ์ด «ใช้คูปองได้» ขึ้นก่อน</span>
    <span class="block text-xs text-slate-600 mt-0.5 leading-relaxed">เรียงหลัง Featured แล้วก่อน Priority — badge «แนะนำสมาชิกแพกาญ» ยังใช้ Featured เป็นหลัก</span>
  </span>
</label>
<?php
$sortContent = ob_get_clean();
settings_section(
    'กติกาเรียงการ์ด',
    'arrow-up-down',
    $sortContent,
    'มีผลกับ Section แนะนำ แพตามโzo และแพใหม่ล่าสุด',
    'text-rose-600'
);
