<?php
/** @var array $values */
require_once __DIR__ . '/../_helpers.php';
$ic = settings_input_class();

ob_start();
?>
<div class="rounded-xl border border-violet-200 bg-violet-50/80 px-4 py-3 text-sm text-violet-900 leading-relaxed">
  <?= e(settings_t('partners.lead_banner', '')) ?>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
<?php
ob_start();
?>
<textarea name="lead_seek_line_notify_token" rows="2" class="<?= $ic ?> font-mono text-xs" placeholder="ใส่ token จาก notify-bot.line.me"><?= e($values['lead_seek_line_notify_token'] ?? '') ?></textarea>
<?php
settings_field(
    'LINE Notify token (กลุ่ม VIP — ไม่บังคับ)',
    ob_get_clean(),
    'เมื่อมีคำขอใหม่ ระบบส่งซ้ำไปช่องนี้ (นอกจากแจ้งเจ้าของทางอีเมล/LINE OA)',
    'สร้าง token ที่ notify-bot.line.me แล้วเชิญบอทเข้ากลุ่ม'
);

ob_start();
?>
<input type="number" name="lead_broadcast_max" min="1" max="200" value="<?= e($values['lead_broadcast_max'] ?? '50') ?>" class="<?= $ic ?>">
<?php
settings_field(
    'จำนวนพาร์ทเนอร์ VIP สูงสุดต่อคำขอ',
    ob_get_clean(),
    'จำกัดว่าคำขอหนึ่งครั้งแจกให้กี่เจ้าของ — ป้องกัน spam ถ้าตั้งสูงเกินไป',
    'ค่าเริ่มต้น 50'
);
?>
</div>
<?php
$leadContent = ob_get_clean();
settings_section(
    settings_t('partners.lead_section_title'),
    'search',
    $leadContent,
    settings_t('partners.lead_section_intro', ''),
    'text-violet-600'
);

ob_start();
?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<?php
ob_start();
?>
<input type="text" name="membership_warn_days" value="<?= e($values['membership_warn_days'] ?? '30,7,3,1') ?>" class="<?= $ic ?>" placeholder="30,7,3,1">
<?php
settings_field(
    'แจ้งเตือนก่อนหมดอายุสมาชิก (วัน)',
    ob_get_clean(),
    'cron membership_warn_expiring — คั่นด้วย comma · ส่ง in-app + LINE OA แพกาญ.com',
    'เช่น 30,7,3,1'
);

ob_start();
?>
<input type="number" name="membership_grace_days" min="0" max="90" value="<?= e($values['membership_grace_days'] ?? '7') ?>" class="<?= $ic ?>">
<?php
settings_field(
    'Grace period หลังหมดอายุสมาชิก (วัน)',
    ob_get_clean(),
    'ช่วงที่ยังถือว่าเป็นสมาชิกหลังวันหมดอายุ — cron จะ downgrade หลังครบกำหนด',
    '0 = หมดอายุแล้วลดสิทธิทันที'
);

ob_start();
?>
<input type="number" name="membership_boost_priority_standard" min="0" max="9999" value="<?= e($values['membership_boost_priority_standard'] ?? '20') ?>" class="<?= $ic ?>">
<?php
settings_field(
    'Boost priority — สมาชิกธรรมดา',
    ob_get_clean(),
    'คะแนนบวกในการเรียงลำดับที่พักในรายการ — ยิ่งสูงยิ่งขึ้นบน'
);

ob_start();
?>
<input type="number" name="membership_boost_priority_vip" min="0" max="9999" value="<?= e($values['membership_boost_priority_vip'] ?? '60') ?>" class="<?= $ic ?>">
<?php
settings_field(
    'Boost priority — VIP',
    ob_get_clean(),
    'ควรสูงกว่าสมาชิกธรรมดาเพื่อให้ VIP เด่นในลิสติ้ง'
);

ob_start();
$vf = (string)($values['membership_vip_auto_featured'] ?? '1');
?>
<select name="membership_vip_auto_featured" class="<?= $ic ?>">
  <option value="1" <?= $vf === '1' ? 'selected' : '' ?>>เปิด — ตั้งป้าย Featured อัตโนมัติ (cron/sync)</option>
  <option value="0" <?= $vf === '0' ? 'selected' : '' ?>>ปิด — เพิ่มเฉพาะ priority ไม่แตะ is_featured</option>
</select>
<?php
settings_field(
    'VIP — ป้าย Featured อัตโนมัติ',
    ob_get_clean(),
    'เมื่อปิด: VIP ได้แค่คะแนนเรียงลำดับ ไม่เปลี่ยนคอลัมน์ featured ในฐานข้อมูล'
);
?>
</div>
<?php
$memberContent = ob_get_clean();
settings_section(
    settings_t('partners.member_section_title'),
    'crown',
    $memberContent,
    settings_t('partners.member_section_intro', ''),
    'text-amber-600'
);
