<?php
use App\Core\Application;
use App\Core\View;
use App\Core\I18n;
use App\Core\Session;
use App\Core\Csrf;

if (!function_exists('e')) {
    function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('url')) {
    function url(string $path = ''): string {
        $base = Application::$publicUrl;
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('upload_url')) {
    function upload_url(?string $path): string {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        return url('uploads/' . ltrim($path, '/'));
    }
}

if (!function_exists('upload_img')) {
    /**
     * URL รูป optimized — เลือก WebP variant อัตโนมัติ
     *
     * @param string $size thumb|md|original
     */
    function upload_img(?string $path, string $size = 'md'): string {
        return \App\Support\ImageOptimizer::variantUrl((string) $path, $size);
    }
}

if (!function_exists('str_first_char')) {
    /** ตัวอักษรแรกสำหรับอวตาร — รองรับโฮสต์ที่ไม่มี ext-mbstring */
    function str_first_char(string $text): string {
        $text = trim($text);
        if ($text === '') {
            return '?';
        }
        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, 1);
        }
        return substr($text, 0, 1);
    }
}

if (!function_exists('format_percent')) {
    function format_percent($value, int $decimals = 2): string {
        return number_format((float) ($value ?? 0), $decimals);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('back')) {
    function back(): void {
        $ref = $_SERVER['HTTP_REFERER'] ?? url('/');
        redirect($ref);
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = ''): string {
        $arr = Session::get('_old', []);
        return e($arr[$key] ?? $default);
    }
}

if (!function_exists('view')) {
    function view(string $tpl, array $data = [], ?string $layout = null): void {
        View::render($tpl, $data, $layout);
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null) {
        $parts = explode('.', $key);
        $val = Application::$config;
        foreach ($parts as $p) {
            if (!is_array($val) || !array_key_exists($p, $val)) return $default;
            $val = $val[$p];
        }
        return $val;
    }
}

if (!function_exists('__')) {
    function __(string $key, array $params = []): string {
        return I18n::trans($key, $params);
    }
}

if (!function_exists('csrf')) {
    function csrf(): string { return Csrf::field(); }
}

if (!function_exists('flash')) {
    function flash(string $type, ?string $msg = null) {
        if ($msg === null) return Session::flash($type);
        Session::flash($type, $msg);
        return null;
    }
}

if (!function_exists('current_url')) {
    function current_url(): string {
        return ($_SERVER['REQUEST_URI'] ?? '/');
    }
}

if (!function_exists('is_active')) {
    function is_active(string $path, string $class = 'text-teal-600 font-semibold'): string {
        $cur = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        return str_contains($cur, $path) ? $class : '';
    }
}

if (!function_exists('coupon_cta_label')) {
    /** ข้อความปุ่มซื้อคูปอง — ตั้งค่าได้ที่ Admin > การตั้งค่า > คูปอง */
    function coupon_cta_label(bool $short = false): string
    {
        $default = __('buy_coupon');
        if ($short) {
            $custom = trim((string)\App\Models\Setting::get('coupon_cta_button_label_short', ''));
            if ($custom !== '') {
                return $custom;
            }
        }
        $custom = trim((string)\App\Models\Setting::get('coupon_cta_button_label', ''));
        return $custom !== '' ? $custom : $default;
    }
}

if (!function_exists('format_money')) {
    function format_money($n, bool $withCurrency = true): string {
        $f = number_format((float)$n, 0);
        return $withCurrency ? '฿' . $f : $f;
    }
}

if (!function_exists('format_date_th')) {
    function format_date_th(?string $date, string $fmt = 'j M Y'): string {
        if (!$date) return '-';
        $ts = strtotime($date);
        if (!$ts) return '-';
        $months = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
        $m = (int)date('n', $ts);
        $y = (int)date('Y', $ts) + 543;
        return date('j', $ts) . ' ' . $months[$m] . ' ' . $y;
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string {
        $text = trim($text);
        $text = preg_replace('/\s+/', '-', $text);
        $text = preg_replace('/[^\p{L}\p{N}\-]/u', '', $text);
        return mb_strtolower($text);
    }
}

if (!function_exists('slug_is_ascii')) {
    function slug_is_ascii(string $slug): bool
    {
        return $slug !== '' && (bool)preg_match('/^[a-z0-9\-]+$/', $slug);
    }
}

if (!function_exists('property_slug_base')) {
    /** Build URL slug base from property names — prefer English ASCII, else random fallback. */
    function property_slug_base(string $name, ?string $nameEn = null): string
    {
        $nameEn = trim((string)$nameEn);
        if ($nameEn !== '') {
            $fromEn = slugify($nameEn);
            if (slug_is_ascii($fromEn)) {
                return $fromEn;
            }
        }
        $fromName = slugify($name);
        if (slug_is_ascii($fromName)) {
            return $fromName;
        }

        return 'property-' . bin2hex(random_bytes(4));
    }
}

if (!function_exists('star_html')) {
    function star_html(float $rating, string $iconClass = 'w-4 h-4'): string {
        $full = (int)floor($rating);
        $half = ($rating - $full) >= 0.5 ? 1 : 0;
        $empty = 5 - $full - $half;
        $html = '<span class="inline-flex items-center text-amber-400">';
        for ($i=0; $i<$full;  $i++) $html .= '<i data-lucide="star" class="'.$iconClass.' fill-current"></i>';
        if ($half) $html .= '<i data-lucide="star-half" class="'.$iconClass.' fill-current"></i>';
        for ($i=0; $i<$empty; $i++) $html .= '<i data-lucide="star" class="'.$iconClass.' text-slate-300"></i>';
        $html .= '</span>';
        return $html;
    }
}
