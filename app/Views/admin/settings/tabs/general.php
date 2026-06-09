<?php
/** @var array $values */
require_once __DIR__ . '/../_helpers.php';
$ic = settings_input_class();

ob_start();
?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<?php
$generalFieldKeys = ['site_name', 'site_tagline', 'site_email', 'site_phone', 'site_address', 'contact_hours'];
foreach ($generalFieldKeys as $k):
    ob_start();
    ?>
    <input type="text" name="<?= e($k) ?>" value="<?= e($values[$k] ?? '') ?>" class="<?= $ic ?>">
    <?php
    $input = ob_get_clean();
    $ex = settings_t("general.fields.{$k}.example", '');
    settings_field(
        settings_t("general.fields.{$k}.label"),
        $input,
        settings_t("general.fields.{$k}.hint", ''),
        $ex !== '' ? $ex : null
    );
endforeach;
?>
  <div class="md:col-span-2">
    <?php
    ob_start();
    ?>
    <textarea name="contact_linktree_intro" rows="3" maxlength="400" class="<?= $ic ?>"><?= e($values['contact_linktree_intro'] ?? '') ?></textarea>
    <?php
    $ex = settings_t('general.fields.contact_linktree_intro.example', '');
    settings_field(
        settings_t('general.fields.contact_linktree_intro.label'),
        ob_get_clean(),
        settings_t('general.fields.contact_linktree_intro.hint', ''),
        $ex !== '' ? $ex : null
    );
    ?>
  </div>
</div>
<?php
$content = ob_get_clean();
settings_section(
    settings_t('general.section_title'),
    'building-2',
    $content,
    settings_t('general.section_intro', ''),
    'text-accent-600'
);
