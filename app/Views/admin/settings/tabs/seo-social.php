<?php
/** @var array $values */
require_once __DIR__ . '/../_helpers.php';
$ic = settings_input_class();

ob_start();
?>
<div class="grid grid-cols-1 gap-4">
<?php
ob_start();
?>
<input type="text" name="seo_home_title" value="<?= e($values['seo_home_title'] ?? '') ?>" maxlength="255" class="<?= $ic ?>"
       placeholder="เช่น แพกาญ.com — ที่พักกาญจนบุรีตรวจสอบจริง">
<?php
settings_field('Title หน้าแรก', ob_get_clean(), 'ชื่อแท็บเบราว์เซอร์และหัวข้อใน Google — ว่าง = ใช้ค่าเริ่มต้นระบบ', 'ควรไม่เกิน ~60 ตัวอักษร');

ob_start();
?>
<textarea name="seo_home_description" rows="2" maxlength="500" class="<?= $ic ?>" placeholder="สรุปเว็บใน 1–2 ประโยค"><?= e($values['seo_home_description'] ?? '') ?></textarea>
<?php
settings_field('Meta description หน้าแรก', ob_get_clean(), 'คำอธิบายใต้ลิงก์ในผลการค้นหา — สรุปจุดขายหลัก', 'ประมาณ 120–160 ตัวอักษร');

ob_start();
?>
<textarea name="seo_default_description" rows="2" maxlength="500" class="<?= $ic ?>"><?= e($values['seo_default_description'] ?? '') ?></textarea>
<?php
settings_field(
    'Meta description เริ่มต้น (หน้าอื่น)',
    ob_get_clean(),
    'ใช้เมื่อหน้านั้นไม่ได้กำหนด description เอง เช่น หน้ารายการหรือบทความบางหน้า'
);

ob_start();
?>
<input type="text" name="seo_og_image" value="<?= e($values['seo_og_image'] ?? '') ?>" maxlength="500" class="<?= $ic ?> font-mono text-sm"
       placeholder="https://... หรือ path เช่น banners/hero.webp">
<?php
settings_field(
    'รูป Open Graph เริ่มต้น',
    ob_get_clean(),
    'รูปเมื่อแชร์ลิงก์บน Facebook/LINE — ใช้เมื่อหน้านั้นไม่มีรูปของตัวเอง',
    'แนะนำ 1200×630 px'
);
?>
<p class="text-xs text-slate-500 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
  <strong>Search Console:</strong> ส่ง sitemap ที่ <code class="bg-white px-1 rounded">/sitemap.xml</code> และตรวจ <code class="bg-white px-1 rounded">/robots.txt</code>
</p>
</div>
<?php
$seoContent = ob_get_clean();
settings_section(
    settings_t('seo.section_title'),
    'globe',
    $seoContent,
    settings_t('seo.section_intro', ''),
    'text-forest-700'
);

ob_start();
?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<?php
$socialFields = [
    'facebook_plugins_app_id' => ['Meta App ID (Embedded Post รีวิว)', 'ใช้ฝังโพสต์ Facebook บนหน้ารีวิว — จาก developers.facebook.com'],
    'facebook_url' => ['Facebook URL', 'ลิงก์เต็ม — ว่าง = ไม่แสดงปุ่ม'],
    'line_oa' => ['LINE OA (@id)', 'เช่น @paekan — ใช้สร้างลิงก์เพิ่มเพื่อนถ้าไม่ใส่ URL เต็ม'],
    'line_friend_url' => ['LINE เพิ่มเพื่อน (URL เต็ม)', 'เช่น https://line.me/R/ti/p/@xxx — มีความสำคัญกว่า @id'],
    'instagram_url' => ['Instagram URL', null],
    'youtube_url' => ['YouTube URL', null],
    'tiktok_url' => ['TikTok URL', null],
    'wechat_url' => ['WeChat URL', null],
    'xiaohongshu_url' => ['RED / Xiaohongshu URL', null],
    'social_whatsapp' => ['WhatsApp (เฉพาะตัวเลข)', 'ว่าง = ใช้เบอร์โทรหลักจากแท็บข้อมูลเว็บ'],
];
foreach ($socialFields as $k => [$label, $hint]):
    ob_start();
    ?>
    <input type="text" name="<?= e($k) ?>" value="<?= e($values[$k] ?? '') ?>" class="<?= $ic ?> font-mono text-sm">
    <?php
    settings_field($label, ob_get_clean(), $hint);
endforeach;
?>
</div>

<!-- Facebook Page API for Owner Marketing Center -->
<div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-5 space-y-4">
  <h3 class="font-bold text-blue-800 flex items-center gap-2">📘 Facebook Page API — Owner Auto-Post</h3>
  <p class="text-xs text-blue-700">ให้ Owner เชื่อมต่อ Facebook Page แล้วโพสต์จาก Marketing Center ได้โดยตรง</p>
  <div class="grid md:grid-cols-2 gap-4">
    <?php ob_start(); ?>
    <input type="text" name="fb_app_id" value="<?= e($values['fb_app_id'] ?? '') ?>" class="<?= $ic ?> font-mono text-sm" placeholder="1234567890...">
    <?php settings_field('Facebook App ID', ob_get_clean(), 'จาก developers.facebook.com → App Dashboard'); ?>
    <?php ob_start(); ?>
    <input type="password" name="fb_app_secret" value="<?= e($values['fb_app_secret'] ?? '') ?>" class="<?= $ic ?> font-mono text-sm" placeholder="••••••••••••">
    <?php settings_field('Facebook App Secret', ob_get_clean(), 'เก็บเป็นความลับ — ดูได้จาก App Dashboard'); ?>
  </div>
  <details class="text-xs text-blue-700">
    <summary class="cursor-pointer font-semibold">วิธีตั้งค่า Facebook App (คลิกเปิด)</summary>
    <ol class="mt-2 space-y-1 list-decimal list-inside text-blue-600">
      <li>ไปที่ <a href="https://developers.facebook.com" target="_blank" class="underline">developers.facebook.com</a> → Create App</li>
      <li>เพิ่ม Product: <strong>Facebook Login for Business</strong></li>
      <li>Facebook Login Settings → Valid OAuth Redirect URIs ใส่: <code class="bg-white/60 px-1 rounded"><?= e(url('/owner/facebook/callback')) ?></code></li>
      <li>Copy App ID + App Secret มาใส่ด้านบน แล้วบันทึก</li>
      <li>Owner ไปที่ Marketing Center → ตั้งค่า → กด "เชื่อมต่อ Facebook Page"</li>
    </ol>
    <p class="mt-2 text-amber-700 bg-amber-50 rounded p-2">⚠️ Development Mode: เฉพาะ App Admin ใช้ได้ — Production ต้อง Submit App Review</p>
  </details>
</div>
<?php
$socialContent = ob_get_clean();
settings_section(
    settings_t('seo.social_section_title'),
    'share-2',
    $socialContent,
    settings_t('seo.social_section_intro', ''),
    'text-blue-600'
);
