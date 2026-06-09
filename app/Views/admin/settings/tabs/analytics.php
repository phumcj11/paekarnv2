<?php
/** @var array $values */
require_once __DIR__ . '/../_helpers.php';
$ic = settings_input_class();

ob_start();
?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<?php
ob_start();
?>
<input type="text" name="ga4_measurement_id" value="<?= e($values['ga4_measurement_id'] ?? '') ?>" class="<?= $ic ?> font-mono text-sm" placeholder="G-XXXXXXXXXX">
<?php
settings_field(
    'GA4 Measurement ID',
    ob_get_clean(),
    'ฝังสคริปต์วัดผลบนเว็บสาธารณะ — จาก Google Analytics → Admin → Data Streams',
    'รูปแบบ G-XXXXXXXXXX'
);

ob_start();
?>
<input type="url" name="analytics_ga_report_url" value="<?= e($values['analytics_ga_report_url'] ?? '') ?>" class="<?= $ic ?> font-mono text-xs" placeholder="https://analytics.google.com/...">
<?php
settings_field(
    'ลิงก์เปิดรายงาน GA4',
    ob_get_clean(),
    'ปุ่มลัดในแอดมินไปยังรายงานขององค์กรคุณ'
);

ob_start();
?>
<input type="url" name="analytics_search_console_url" value="<?= e($values['analytics_search_console_url'] ?? '') ?>" class="<?= $ic ?> font-mono text-xs" placeholder="https://search.google.com/search-console/...">
<?php
settings_field(
    'ลิงก์ Search Console',
    ob_get_clean(),
    'ตรวจคำค้นและ indexing — ส่ง sitemap /sitemap.xml ใน Console'
);

ob_start();
?>
<input type="url" name="analytics_embed_url" value="<?= e($values['analytics_embed_url'] ?? '') ?>" class="<?= $ic ?> font-mono text-xs" placeholder="https://lookerstudio.google.com/embed/reporting/...">
<?php
settings_field(
    'URL ฝังรายงาน (Looker Studio / GA embed)',
    ob_get_clean(),
    'แสดงในแดชบอร์ดแอดมิน — ต้องตั้งค่าแชร์แบบ embed ได้',
    'คัดลอก URL จาก Looker Studio → Embed'
);
?>
</div>
<p class="text-xs text-slate-500 mt-2">
  ตาราง <code class="bg-slate-100 px-1 rounded">analytics_page_views</code> ต้องมีจาก migration ในโฟลเดอร์ database/migrations
</p>
<?php
$analyticsContent = ob_get_clean();
settings_section(
    settings_t('analytics.section_title'),
    'bar-chart-3',
    $analyticsContent,
    settings_t('analytics.section_intro', ''),
    'text-indigo-600'
);
