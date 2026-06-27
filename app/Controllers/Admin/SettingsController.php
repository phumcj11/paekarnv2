<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\PageCache;
use App\Core\Session;
use App\Core\Upload;
use App\Models\Setting;
use App\Models\Zone;
use App\Support\HomepageSections;

class SettingsController extends Controller
{
    private array $keys = [
        'site_name','site_tagline','site_email','site_phone','site_address',
        'contact_hours','contact_linktree_intro',
        'nav_label_home','nav_label_properties','nav_label_rafts','nav_label_resorts','nav_label_hotels','nav_label_stays',
        'nav_label_pool_villa','nav_label_camping',
        'nav_label_activities','nav_label_guest_seek','nav_label_coupons','nav_label_reviews',
        'nav_label_places','nav_label_blog','nav_label_contact',
        'seo_home_title','seo_home_description','seo_default_description','seo_og_image',
        'home_hero_desktop_title_line1','home_hero_desktop_title_line2',
        'home_hero_mobile_title_line1','home_hero_mobile_title_line2',
        'home_hero_promo_line',
        'home_hero_bullet_1','home_hero_bullet_2','home_hero_bullet_3','home_hero_bullet_4',
        'trust_hero_image',
        'coupon_face_value','coupon_sale_price','coupon_validity_days',
        'coupon_cta_button_label','coupon_cta_button_label_short',
        'bank_name','bank_account','bank_holder','promptpay_id',
        'payment_gateway_enabled','payment_gateway_provider','payment_gateway_public_key','payment_gateway_secret_key',
        'payment_gateway_webhook_secret',
        'activity_checkout_gateway_enabled',
        'facebook_url','line_oa','line_friend_url','facebook_plugins_app_id',
        'fb_app_id','fb_app_secret',
        'instagram_url','youtube_url','tiktok_url','wechat_url','xiaohongshu_url','social_whatsapp',
        'lead_seek_line_notify_token','lead_broadcast_max','membership_grace_days','membership_warn_days','membership_sales_open',
        'membership_boost_priority_standard','membership_boost_priority_vip','membership_vip_auto_featured',
        'email_enabled','email_from',
        'admin_orders_email','line_admin_group_id','coupon_qr_secret',
        'ga4_measurement_id','analytics_embed_url','analytics_ga_report_url','analytics_search_console_url',
        'font_body','font_heading','font_size_base',
    ];

    public static array $fontOptions = [
        'noto_sans_thai'  => ['name' => 'Noto Sans Thai',     'tag' => 'สะอาด · ทันสมัย · ใช้โดย Google/Material',       'gfont' => 'Noto+Sans+Thai:wght@300;400;500;600;700',          'css' => "'Noto Sans Thai','Noto Sans',system-ui,sans-serif"],
        'ibm_plex_thai'   => ['name' => 'IBM Plex Sans Thai', 'tag' => 'คม · เป็นระบบ · เหมาะ OTA สไตล์ Agoda',           'gfont' => 'IBM+Plex+Sans+Thai:wght@300;400;500;600;700',       'css' => "'IBM Plex Sans Thai','IBM Plex Sans',system-ui,sans-serif"],
        'sarabun'         => ['name' => 'Sarabun',            'tag' => 'บาง · โปร่ง · อ่านง่ายขนาดเล็ก',                  'gfont' => 'Sarabun:wght@300;400;500;600;700',                  'css' => "'Sarabun',system-ui,sans-serif"],
        'prompt'          => ['name' => 'Prompt',             'tag' => 'กลม · ทันสมัย · นิยมในแอปไทย',                    'gfont' => 'Prompt:wght@300;400;500;600;700',                   'css' => "'Prompt',system-ui,sans-serif"],
        'kanit'           => ['name' => 'Kanit',              'tag' => 'แบรนด์ไทย · เข้ม · ฟอนต์ปัจจุบัน',               'gfont' => 'Kanit:wght@300;400;500;600;700;800',                'css' => "'Kanit',system-ui,sans-serif"],
        'mitr'            => ['name' => 'Mitr',               'tag' => 'เป็นมิตร · สบายตา · ท่องเที่ยว',                  'gfont' => 'Mitr:wght@300;400;500;600;700',                     'css' => "'Mitr',system-ui,sans-serif"],
        'bai_jamjuree'    => ['name' => 'Bai Jamjuree',       'tag' => 'สะอาด · กะทัดรัด · เหมาะ UI ขนาดเล็ก',           'gfont' => 'Bai+Jamjuree:wght@300;400;500;600;700',             'css' => "'Bai Jamjuree',system-ui,sans-serif"],
        'chakra_petch'    => ['name' => 'Chakra Petch',       'tag' => 'เทคโนโลยี · สปอร์ต · ไม่ธรรมดา',                 'gfont' => 'Chakra+Petch:wght@300;400;500;600;700',             'css' => "'Chakra Petch',system-ui,sans-serif"],
        'inter'           => ['name' => 'Inter (อังกฤษ)',     'tag' => 'ฟอนต์สากล · ใช้โดย Agoda/Linear/Vercel (en)',     'gfont' => 'Inter:wght@300;400;500;600;700',                    'css' => "'Inter','Kanit',system-ui,sans-serif"],
        'dm_sans'         => ['name' => 'DM Sans (อังกฤษ)',  'tag' => 'ทันสมัย · Startup · ใช้โดย Notion/Figma',          'gfont' => 'DM+Sans:wght@300;400;500;600;700',                  'css' => "'DM Sans','Sarabun',system-ui,sans-serif"],
    ];

    public function index(): void
    {
        $values = [];
        foreach ($this->keys as $k) {
            $values[$k] = Setting::get($k, '');
        }
        $zoneNames = Zone::namesForSelectMerged(null);
        $zonesForCovers = array_map(static fn (string $n) => ['zone' => $n], $zoneNames);
        $zoneCovers = json_decode((string)Setting::get('home_zone_cover_images', '{}'), true);
        if (!is_array($zoneCovers)) {
            $zoneCovers = [];
        }
        $settingsUiFile = dirname(__DIR__, 2) . '/Lang/th_admin_settings.php';
        $settingsUi = [];
        if (is_file($settingsUiFile)) {
            try {
                $loaded = require $settingsUiFile;
                if (is_array($loaded)) {
                    $settingsUi = $loaded;
                }
            } catch (\Throwable) {
                $settingsUi = [];
            }
        }

        $this->adminView('settings/index', [
            'page_title'       => (string)($settingsUi['page_title'] ?? 'การตั้งค่าระบบ'),
            'settings_ui'      => $settingsUi,
            'values'           => $values,
            'zones_for_covers' => $zonesForCovers,
            'zone_covers'      => $zoneCovers,
            'font_options'     => self::$fontOptions,
            'home_featured_labels' => HomepageSections::featuredLabels(),
            'home_sections_order'  => HomepageSections::sectionsOrder(),
            'home_zone_sections'   => HomepageSections::zoneSections(),
            'home_section_labels'  => HomepageSections::sectionLabelMap(),
        ]);
    }

    public function update(): void
    {
        foreach ($this->keys as $k) {
            if ($k === 'trust_hero_image') {
                continue;
            }
            if (isset($_POST[$k])) {
                Setting::set($k, (string)$_POST[$k]);
            }
        }

        if (!empty($_POST['trust_hero_image_clear'])) {
            Setting::set('trust_hero_image', '');
        } else {
            try {
                $uploaded = Upload::image('trust_hero_image_upload', 'banners');
                if ($uploaded !== null) {
                    Setting::set('trust_hero_image', $uploaded);
                } elseif (isset($_POST['trust_hero_image'])) {
                    Setting::set('trust_hero_image', trim((string)$_POST['trust_hero_image']));
                }
            } catch (\Throwable $e) {
                Session::flash('error', 'อัปโหลดรูปบล็อก «ทำไมต้องเลือกแพกาญ» ไม่สำเร็จ: ' . $e->getMessage());
                redirect(url('/admin/settings'));

                return;
            }
        }

        $covers = json_decode((string)Setting::get('home_zone_cover_images', '{}'), true);
        if (!is_array($covers)) {
            $covers = [];
        }

        foreach ($_POST['zone_cover_remove'] ?? [] as $zn) {
            $zn = trim((string)$zn);
            if ($zn !== '') {
                unset($covers[$zn]);
            }
        }

        $zoneNames = $_POST['zone_cover_zone'] ?? [];
        $uploads = $_FILES['zone_cover_upload'] ?? null;
        if (is_array($zoneNames) && is_array($uploads) && isset($uploads['tmp_name']) && is_array($uploads['tmp_name'])) {
            foreach ($zoneNames as $i => $zoneName) {
                $zoneName = trim((string)$zoneName);
                if ($zoneName === '') {
                    continue;
                }
                $err = (int)($uploads['error'][$i] ?? UPLOAD_ERR_NO_FILE);
                if ($err === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                try {
                    $entry = [
                        'name' => $uploads['name'][$i] ?? '',
                        'type' => $uploads['type'][$i] ?? '',
                        'tmp_name' => (string)($uploads['tmp_name'][$i] ?? ''),
                        'error' => $err,
                        'size' => (int)($uploads['size'][$i] ?? 0),
                    ];
                    $path = Upload::imageFromEntry($entry, 'zones');
                    if ($path !== null) {
                        $covers[$zoneName] = $path;
                    }
                } catch (\Throwable $e) {
                    Session::flash('error', 'อัปโหลดรูปโซนไม่สำเร็จ: ' . $e->getMessage());
                    redirect(url('/admin/settings'));

                    return;
                }
            }
        }

        Setting::set('home_zone_cover_images', json_encode($covers, JSON_UNESCAPED_UNICODE));

        self::saveHomepageLayoutSettings();

        PageCache::flush();
        Session::flash('success', 'บันทึกการตั้งค่าเรียบร้อย');
        redirect(url('/admin/settings'));
    }

    private static function saveHomepageLayoutSettings(): void
    {
        Setting::set('home_sort_boost_coupon', !empty($_POST['home_sort_boost_coupon']) ? '1' : '0');

        $featuredKeys = ['raft', 'resort', 'hotel', 'stay'];
        $featuredOut = [];
        foreach ($featuredKeys as $key) {
            $featuredOut[$key] = [
                'title'   => trim((string) ($_POST['home_feat_' . $key . '_title'] ?? '')),
                'eyebrow' => trim((string) ($_POST['home_feat_' . $key . '_eyebrow'] ?? '')),
                'enabled' => !empty($_POST['home_feat_' . $key . '_enabled']),
                'limit'   => max(1, min(24, (int) ($_POST['home_feat_' . $key . '_limit'] ?? 8))),
            ];
        }
        Setting::set('home_featured_labels', json_encode($featuredOut, JSON_UNESCAPED_UNICODE));

        $orderRaw = trim((string) ($_POST['home_sections_order'] ?? ''));
        $orderDecoded = json_decode($orderRaw, true);
        if (is_array($orderDecoded)) {
            $valid = array_flip(HomepageSections::defaultOrder());
            $clean = [];
            foreach ($orderDecoded as $id) {
                $id = trim((string) $id);
                if ($id !== '' && isset($valid[$id])) {
                    $clean[] = $id;
                }
            }
            if ($clean !== []) {
                Setting::set('home_sections_order', json_encode(array_values(array_unique($clean)), JSON_UNESCAPED_UNICODE));
            }
        }

        $zoneIds = $_POST['home_zone_id'] ?? [];
        $zoneTitles = $_POST['home_zone_title'] ?? [];
        $zoneEnabled = $_POST['home_zone_enabled'] ?? [];
        $zoneSorts = $_POST['home_zone_sort'] ?? [];
        $zoneOut = [];
        if (is_array($zoneIds)) {
            foreach ($zoneIds as $i => $zoneId) {
                $zoneId = trim((string) $zoneId);
                if ($zoneId === '') {
                    continue;
                }
                $defaults = null;
                foreach (HomepageSections::defaultZoneSections() as $d) {
                    if ($d['id'] === $zoneId) {
                        $defaults = $d;
                        break;
                    }
                }
                if ($defaults === null) {
                    continue;
                }
                $title = trim((string) ($zoneTitles[$i] ?? ''));
                $zoneOut[] = [
                    'id'         => $zoneId,
                    'title'      => $title !== '' ? $title : $defaults['title'],
                    'zones'      => $defaults['zones'],
                    'enabled'    => in_array($zoneId, is_array($zoneEnabled) ? $zoneEnabled : [], true),
                    'sort_order' => (int) ($zoneSorts[$i] ?? $defaults['sort_order']),
                ];
            }
        }
        if ($zoneOut !== []) {
            usort($zoneOut, static fn (array $a, array $b): int => ($a['sort_order'] <=> $b['sort_order']) ?: strcmp($a['id'], $b['id']));
            Setting::set('home_zone_sections', json_encode($zoneOut, JSON_UNESCAPED_UNICODE));
        }
    }
}
