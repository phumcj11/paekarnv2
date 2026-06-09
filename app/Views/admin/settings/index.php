<?php
/** @var array $values @var array $zones_for_covers @var array $zone_covers @var array $font_options @var array $settings_ui */
require_once __DIR__ . '/_helpers.php';

$settingsTabs = settings_tabs_for_view();
$defaultTabId = (string)($settingsTabs[0]['id'] ?? 'general');
$ui = is_array($settings_ui ?? null) ? $settings_ui : settings_ui_data();

$tabPartialVars = [
    'values'           => $values,
    'zones_for_covers' => $zones_for_covers,
    'zone_covers'      => $zone_covers,
    'font_options'     => $font_options,
    'settings_ui'      => $ui,
    'home_featured_labels' => $home_featured_labels ?? [],
    'home_sections_order'  => $home_sections_order ?? [],
    'home_zone_sections'   => $home_zone_sections ?? [],
    'home_section_labels'  => $home_section_labels ?? [],
];
$tabIdsJson = json_encode(array_column($settingsTabs, 'id'), JSON_UNESCAPED_UNICODE);
?>

<div class="space-y-6" id="admin-settings-root">
  <p class="text-sm text-slate-500 leading-relaxed max-w-3xl">
    <?= e($ui['page_intro'] ?? settings_t('page_intro', 'จัดกลุ่มตามหมวด — สลับแท็บเพื่อแก้ไข แล้วกดบันทึกด้านล่าง (บันทึกทุกแท็บพร้อมกัน)')) ?>
  </p>

  <form method="post" action="<?= url('/admin/settings') ?>" enctype="multipart/form-data" class="space-y-4">
    <?= csrf() ?>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft overflow-hidden">
      <div class="px-3 sm:px-4 pt-3 pb-2 border-b border-slate-100 bg-slate-50/80">
        <nav class="flex gap-1 flex-wrap" role="tablist" aria-label="<?= e($ui['tablist_aria'] ?? settings_t('tablist_aria', 'หมวดการตั้งค่า')) ?>">
          <?php foreach ($settingsTabs as $t):
              $tid = (string)$t['id'];
              $isActive = $tid === $defaultTabId;
              ?>
            <button type="button"
                    role="tab"
                    data-settings-tab="<?= e($tid) ?>"
                    aria-selected="<?= $isActive ? 'true' : 'false' ?>"
                    class="settings-tab-btn inline-flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs sm:text-sm font-semibold transition <?= $isActive
                      ? 'bg-primary-600 text-white shadow-sm'
                      : 'text-slate-600 hover:bg-white hover:text-slate-900' ?>">
              <i data-lucide="<?= e($t['icon']) ?>" class="w-4 h-4 shrink-0"></i>
              <span><?= e($t['label']) ?></span>
            </button>
          <?php endforeach; ?>
        </nav>
      </div>

      <div class="p-4 sm:p-6 min-h-[12rem]">
        <?php foreach ($settingsTabs as $t):
            $tid = (string)$t['id'];
            $isActive = $tid === $defaultTabId;
            ?>
          <div class="settings-tab-panel space-y-4<?= $isActive ? '' : ' hidden' ?>"
               role="tabpanel"
               data-settings-panel="<?= e($tid) ?>"
               id="settings-panel-<?= e($tid) ?>">
            <?php settings_render_tab_partial($tid, $tabPartialVars); ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-soft px-5 py-4 flex flex-wrap items-center justify-between gap-3">
      <p class="text-xs text-slate-500 flex items-center gap-1.5">
        <i data-lucide="info" class="w-3.5 h-3.5 text-slate-400"></i>
        <?= e($ui['save_hint'] ?? settings_t('save_hint', 'การเปลี่ยนแปลงมีผลหลังบันทึก — รวมทุกแท็บในครั้งเดียว')) ?>
      </p>
      <button type="submit"
              class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold shadow-lg shadow-primary-600/20 transition">
        <i data-lucide="save" class="w-4 h-4"></i>
        <?= e($ui['save_btn'] ?? settings_t('save_btn', 'บันทึกการตั้งค่า')) ?>
      </button>
    </div>
  </form>
</div>

<?php
$fontOptionsJson = json_encode(
    array_map(fn($o) => ['css' => $o['css'], 'gfont' => $o['gfont']], $font_options),
    JSON_UNESCAPED_UNICODE
);
?>
<script>
const FONT_OPTIONS = <?= $fontOptionsJson ?>;
const SETTINGS_TAB_IDS = <?= $tabIdsJson ?>;

(function () {
  const root = document.getElementById('admin-settings-root');
  if (!root) return;

  const buttons = root.querySelectorAll('[data-settings-tab]');
  const panels = root.querySelectorAll('[data-settings-panel]');

  function showTab(id) {
    if (!SETTINGS_TAB_IDS.includes(id)) return;
    buttons.forEach(function (btn) {
      const active = btn.getAttribute('data-settings-tab') === id;
      btn.setAttribute('aria-selected', active ? 'true' : 'false');
      btn.classList.toggle('bg-primary-600', active);
      btn.classList.toggle('text-white', active);
      btn.classList.toggle('shadow-sm', active);
      btn.classList.toggle('text-slate-600', !active);
      btn.classList.toggle('hover:bg-white', !active);
      btn.classList.toggle('hover:text-slate-900', !active);
    });
    panels.forEach(function (panel) {
      panel.classList.toggle('hidden', panel.getAttribute('data-settings-panel') !== id);
    });
    try { sessionStorage.setItem('admin_settings_tab', id); } catch (e) {}
    if (location.hash !== '#' + id) {
      history.replaceState(null, '', '#' + id);
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
  }

  buttons.forEach(function (btn) {
    btn.addEventListener('click', function () {
      showTab(btn.getAttribute('data-settings-tab'));
    });
  });

  var hash = (location.hash || '').replace(/^#/, '');
  if (SETTINGS_TAB_IDS.includes(hash)) {
    showTab(hash);
  } else {
    try {
      var saved = sessionStorage.getItem('admin_settings_tab');
      if (saved && SETTINGS_TAB_IDS.includes(saved)) showTab(saved);
    } catch (e) {}
  }
})();

function fontPicker() {
  return {
    bodyKey: '<?= e($values['font_body'] ?? 'noto_sans_thai') ?>',
    headKey: '<?= e($values['font_heading'] ?? 'kanit') ?>',
    fontSize: <?= (int)($values['font_size_base'] ?? 15) ?>,
    loadedFonts: new Set(),
    init() { this.applyPreview(); },
    loadGFont(key) {
      const opt = FONT_OPTIONS[key];
      if (!opt || this.loadedFonts.has(key)) return;
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://fonts.googleapis.com/css2?family=' + opt.gfont + '&display=swap';
      document.head.appendChild(link);
      this.loadedFonts.add(key);
    },
    applyPreview() {
      this.loadGFont(this.bodyKey);
      this.loadGFont(this.headKey);
      const bodyCSS = FONT_OPTIONS[this.bodyKey]?.css || 'system-ui';
      const headCSS = FONT_OPTIONS[this.headKey]?.css || 'system-ui';
      const sz = this.fontSize + 'px';
      const box = document.getElementById('font-preview-box');
      if (!box) return;
      box.style.fontSize = sz;
      const heading = document.getElementById('fp-heading');
      const body = document.getElementById('fp-body');
      const chips = document.getElementById('fp-chips');
      const nav = document.getElementById('fp-nav');
      if (heading) heading.style.fontFamily = headCSS;
      [body, chips, nav].forEach(function (el) { if (el) el.style.fontFamily = bodyCSS; });
    },
  };
}
</script>
