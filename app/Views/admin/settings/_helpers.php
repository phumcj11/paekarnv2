<?php

use App\Core\Application;
use App\Core\View;

/** @return array<string, mixed> */
function settings_ui_data(): array
{
    static $data = null;
    if ($data === null) {
        $file = Application::$basePath . '/app/Lang/th_admin_settings.php';
        $data = [];
        if (is_file($file)) {
            try {
                $loaded = require $file;
                if (is_array($loaded)) {
                    $data = $loaded;
                }
            } catch (\Throwable) {
                $data = [];
            }
        }
    }
    return $data;
}

/** Dot-path lookup for hosting-safe UI strings (e.g. general.section_title). */
function settings_t(string $path, string $fallback = ''): string
{
    $node = settings_ui_data();
    foreach (explode('.', $path) as $segment) {
        if (!is_array($node) || !array_key_exists($segment, $node)) {
            return $fallback !== '' ? $fallback : $path;
        }
        $node = $node[$segment];
    }
    return is_string($node) ? $node : ($fallback !== '' ? $fallback : $path);
}

/** Render a labeled field with optional hint and example. */
function settings_field(string $label, string $content, ?string $hint = null, ?string $example = null, ?string $hintHtml = null): void
{
    View::partial('admin/settings/_field', [
        'label'    => $label,
        'content'  => $content,
        'hint'     => $hint,
        'example'  => $example,
        'hintHtml' => $hintHtml,
    ]);
}

/** Render a settings card section. */
function settings_section(string $title, string $icon, string $content, ?string $intro = null, string $iconClass = 'text-accent-600'): void
{
    View::partial('admin/settings/_section', [
        'title'     => $title,
        'icon'      => $icon,
        'content'   => $content,
        'intro'     => $intro,
        'iconClass' => $iconClass,
    ]);
}

function settings_input_class(): string
{
    return 'w-full px-3 py-2.5 rounded-xl border border-slate-300 bg-white text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-100 outline-none transition';
}

/** @return list<array{id:string,label:string,icon:string}> */
function settings_default_tabs(): array
{
    return [
        ['id' => 'general', 'label' => 'ข้อมูลเว็บ', 'icon' => 'building-2'],
        ['id' => 'nav-fonts', 'label' => 'เมนู & ฟอนต์', 'icon' => 'navigation'],
        ['id' => 'homepage', 'label' => 'หน้าแรก', 'icon' => 'home'],
        ['id' => 'seo-social', 'label' => 'SEO & โซเชียล', 'icon' => 'globe'],
        ['id' => 'commerce', 'label' => 'คูปอง & ชำระเงิน', 'icon' => 'wallet'],
        ['id' => 'notifications', 'label' => 'แจ้งเตือน', 'icon' => 'bell'],
        ['id' => 'partners-lead', 'label' => 'พาร์ทเนอร์ & Lead', 'icon' => 'users'],
        ['id' => 'analytics', 'label' => 'Analytics', 'icon' => 'bar-chart-3'],
    ];
}

/** Tabs for the settings page (lang file or built-in fallback). */
function settings_tabs_for_view(): array
{
    $tabs = settings_ui_data()['tabs'] ?? null;
    if (!is_array($tabs) || count($tabs) < 2) {
        return settings_default_tabs();
    }
    return $tabs;
}

function settings_render_tab_partial(string $tabId, array $vars): void
{
    $file = Application::$basePath . '/app/Views/admin/settings/tabs/' . $tabId . '.php';
    if (!is_file($file)) {
        echo '<div class="rounded-xl border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm">'
            . 'ไม่พบไฟล์แท็บ <code class="font-mono">' . htmlspecialchars($tabId, ENT_QUOTES, 'UTF-8') . '</code>'
            . ' — อัปโหลดโฟลเดอร์ <code class="font-mono">app/Views/admin/settings/tabs/</code> ให้ครบ</div>';
        return;
    }
    View::partial('admin/settings/tabs/' . $tabId, $vars);
}
