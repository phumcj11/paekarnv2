<?php
/** @var array $values */
require_once __DIR__ . '/../_helpers.php';
$ic = settings_input_class();
$en = (string)($values['email_enabled'] ?? '0');
$lineSettingsUrl = url('/admin/line/settings');
?>

<?php if ($en !== '1'): ?>
<div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 flex gap-3 items-start">
  <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"></i>
  <div class="text-sm text-amber-900">
    <p class="font-bold"><?= e(settings_t('notifications.email_off_title')) ?></p>
    <p class="mt-1 text-amber-800 leading-relaxed"><?= e(settings_t('notifications.email_off_body')) ?></p>
  </div>
</div>
<?php else: ?>
<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 flex gap-3 items-start">
  <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5"></i>
  <div class="text-sm text-emerald-900">
    <p class="font-bold"><?= e(settings_t('notifications.email_on_title')) ?></p>
    <p class="mt-1 text-emerald-800"><?= e(settings_t('notifications.email_on_body')) ?></p>
  </div>
</div>
<?php endif; ?>

<?php
ob_start();
?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<?php
ob_start();
$en = (string)($values['email_enabled'] ?? '0');
?>
<select name="email_enabled" class="<?= $ic ?>">
  <option value="0" <?= $en === '0' ? 'selected' : '' ?>><?= e(settings_t('notifications.fields.email_enabled.opt_off')) ?></option>
  <option value="1" <?= $en === '1' ? 'selected' : '' ?>><?= e(settings_t('notifications.fields.email_enabled.opt_on')) ?></option>
</select>
<?php
settings_field(
    settings_t('notifications.fields.email_enabled.label'),
    ob_get_clean(),
    settings_t('notifications.fields.email_enabled.hint', '')
);

ob_start();
?>
<input type="text" name="email_from" value="<?= e($values['email_from'] ?? '') ?>" class="<?= $ic ?> font-mono text-sm" placeholder="noreply@yourdomain.com">
<?php
settings_field(
    settings_t('notifications.fields.email_from.label'),
    ob_get_clean(),
    settings_t('notifications.fields.email_from.hint', ''),
    settings_t('notifications.fields.email_from.example', '')
);

ob_start();
?>
<input type="email" name="admin_orders_email" value="<?= e($values['admin_orders_email'] ?? '') ?>" class="<?= $ic ?> font-mono text-sm" placeholder="admin@example.com">
<?php
settings_field(
    settings_t('notifications.fields.admin_orders_email.label'),
    ob_get_clean(),
    null,
    null,
    settings_t('notifications.fields.admin_orders_email.hint_html', '')
);

ob_start();
?>
<input type="text" name="line_admin_group_id" value="<?= e($values['line_admin_group_id'] ?? '') ?>" class="<?= $ic ?> font-mono text-xs" placeholder="Cxxxxxxxx...">
<?php
settings_field(
    settings_t('notifications.fields.line_admin_group_id.label'),
    ob_get_clean(),
    settings_t('notifications.fields.line_admin_group_id.hint', ''),
    settings_t('notifications.fields.line_admin_group_id.example', '')
);

ob_start();
?>
<input type="text" name="coupon_qr_secret" value="<?= e($values['coupon_qr_secret'] ?? '') ?>" class="<?= $ic ?> font-mono text-xs" placeholder="">
<?php
settings_field(
    settings_t('notifications.fields.coupon_qr_secret.label'),
    ob_get_clean(),
    settings_t('notifications.fields.coupon_qr_secret.hint', ''),
    settings_t('notifications.fields.coupon_qr_secret.example', '')
);
?>
</div>

<div class="rounded-xl border border-sky-200 bg-sky-50 p-4 flex flex-wrap items-center justify-between gap-3">
  <div class="text-sm text-sky-900">
    <p class="font-bold flex items-center gap-2"><i data-lucide="message-circle" class="w-4 h-4"></i> <?= e(settings_t('notifications.line_card_title')) ?></p>
    <p class="mt-1 text-sky-800 text-xs leading-relaxed"><?= e(settings_t('notifications.line_card_body')) ?></p>
  </div>
  <a href="<?= e($lineSettingsUrl) ?>" class="inline-flex items-center gap-1.5 rounded-lg bg-sky-700 hover:bg-sky-800 text-white text-sm font-semibold px-4 py-2 transition shrink-0">
    <?= e(settings_t('notifications.line_card_btn')) ?> <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
  </a>
</div>
<?php
$notifContent = ob_get_clean();
settings_section(
    settings_t('notifications.section_title'),
    'bell',
    $notifContent,
    settings_t('notifications.section_intro', ''),
    'text-indigo-600'
);
