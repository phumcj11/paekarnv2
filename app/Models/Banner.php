<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Banner extends Model
{
    protected static string $table = 'banners';

    /** ช่อง Banner หน้าแรก B01–B10 (ตาม mockup) */
    public const B_SLOTS = [
        'home_b01',
        'home_b02',
        'home_b03',
        'home_b04',
        'home_b05',
        'home_b06',
        'home_b07',
        'home_b08',
        'home_b09',
        'home_b10',
    ];

    /** ช่องพิเศษ (ไม่ใช่ B-slot) */
    public const SPECIAL_HOME_SLOTS = [
        'hero',
        'home_desktop_coupon_strip',
        'home_before_zones',
        'home_before_cta',
    ];

    /** slot เดิม — เก็บใน allSlots เพื่อ backward compat */
    public const LEGACY_HOME_SLOTS = [
        'home_after_stats',
        'home_before_featured',
        'home_before_coupon',
        'home_before_newest',
        'home_before_reviews',
        'home_before_blog',
    ];

    public const HOME_SLOTS = [
        ...self::B_SLOTS,
        ...self::SPECIAL_HOME_SLOTS,
        ...self::LEGACY_HOME_SLOTS,
    ];

    public const PLACES_SLOTS = [
        'places_hero',
        'places_promo_raft',
        'places_promo_deal',
    ];

    /** @return list<string> */
    public static function homeSlotKeys(): array
    {
        return array_merge(self::B_SLOTS, self::SPECIAL_HOME_SLOTS);
    }

    /** @return array<string, list<string>> B-slot => legacy slots ที่ fallback ได้ */
    public static function legacyFallbackSlots(): array
    {
        return [
            'home_b03' => ['home_after_stats', 'home_before_featured'],
            'home_b04' => ['home_before_coupon'],
            'home_b05' => ['home_before_newest'],
            'home_b09' => ['home_before_reviews'],
            'home_b10' => ['home_before_blog'],
        ];
    }

    public static function isBSlot(string $slot): bool
    {
        return in_array($slot, self::B_SLOTS, true);
    }

    public static function isNativeSlot(string $slot): bool
    {
        return $slot === 'home_b06';
    }

    public static function activeConditionSql(): string
    {
        return "is_active = 1
            AND (starts_at IS NULL OR starts_at <= NOW())
            AND (ends_at IS NULL OR ends_at >= NOW())";
    }

    /** Banners ที่แสดงได้ทั้งหมดของ slot เดียว */
    public static function activeForSlot(string $slot): array
    {
        $order = Database::tableHasColumn('banners', 'priority')
            ? 'priority DESC, sort_order ASC, id ASC'
            : 'sort_order ASC, id ASC';
        $sql = "SELECT * FROM banners WHERE slot = :s AND " . self::activeConditionSql()
            . " ORDER BY {$order}";

        return Database::fetchAll($sql, ['s' => $slot]);
    }

    /** @return list<string> */
    public static function allSlots(): array
    {
        return array_merge(self::HOME_SLOTS, self::PLACES_SLOTS);
    }

    public static function isPlacesSlot(string $slot): bool
    {
        return in_array($slot, self::PLACES_SLOTS, true);
    }

    public static function publicImageUrl(string $path, string $size = 'md'): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        return preg_match('#^https?://#i', $path) ? $path : upload_img($path, $size);
    }

    /**
     * เนื้อหา Banner หน้า /places — ใช้ค่า default เมื่อแอดมินยังไม่ได้ตั้ง
     *
     * @return array<string,array{image:string,title:string,subtitle:string,button:string,link:string}>
     */
    public static function placesPageContent(): array
    {
        $defaults = [
            'places_hero' => [
                'image'    => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ab/Bridge_over_River_Kwai_in_Kanchanaburi%2C_Thailand.jpg/800px-Bridge_over_River_Kwai_in_Kanchanaburi%2C_Thailand.jpg',
                'title'    => 'กาญจนบุรี',
                'subtitle' => 'ง่ายๆ ใกล้คุณ',
                'button'   => '',
                'link'     => '',
            ],
            'places_promo_raft' => [
                'image'    => 'https://images.unsplash.com/photo-1499793983690-e29da59ef1c2?w=800&q=70',
                'title'    => 'แพที่พักกาญจนบุรี',
                'subtitle' => 'บรรยากาศดี วิวสวย น่าไปพักผ่อน',
                'button'   => 'ดูแพที่พักแนะนำ',
                'link'     => '/rafts',
            ],
            'places_promo_deal' => [
                'image'    => '',
                'title'    => 'ที่พักลดสูงสุด',
                'subtitle' => '50%',
                'button'   => 'ดูโปรทั้งหมด',
                'link'     => '/properties?coupon=1',
            ],
        ];

        $out = [];
        foreach (self::PLACES_SLOTS as $slot) {
            $d   = $defaults[$slot];
            $row = self::activeForSlot($slot)[0] ?? null;
            if (!$row) {
                $out[$slot] = $d;
                continue;
            }
            $img = self::publicImageUrl((string)($row['image_path'] ?? ''));
            $out[$slot] = [
                'image'    => $img !== '' ? $img : $d['image'],
                'title'    => trim((string)($row['title'] ?? '')) !== '' ? trim((string)$row['title']) : $d['title'],
                'subtitle' => trim((string)($row['subtitle'] ?? '')) !== '' ? trim((string)$row['subtitle']) : $d['subtitle'],
                'button'   => trim((string)($row['button_text'] ?? '')) !== '' ? trim((string)$row['button_text']) : $d['button'],
                'link'     => trim((string)($row['link_url'] ?? '')) !== '' ? trim((string)$row['link_url']) : $d['link'],
            ];
        }

        return $out;
    }

    /** หน้าแรก: group ตาม slot */
    public static function groupedForHome(): array
    {
        $keys = array_unique(array_merge(
            self::homeSlotKeys(),
            self::LEGACY_HOME_SLOTS
        ));
        $out = [];
        foreach ($keys as $slot) {
            $out[$slot] = self::activeForSlot($slot);
        }
        return $out;
    }

    public static function labels(): array
    {
        return [
            'home_b01' => 'B01 — Premium Banner (ใต้กล่องค้นหา)',
            'home_b02' => 'B02 — ก่อน Recommended',
            'home_b03' => 'B03 — หลัง Recommended',
            'home_b04' => 'B04 — หลังทำไมต้องเลือกแพกาญ',
            'home_b05' => 'B05 — ก่อนแพใหม่ล่าสุด',
            'home_b06' => 'B06 — การ์ดผู้สนับสนุน (ใน grid)',
            'home_b07' => 'B07 — หลังรายการแพ',
            'home_b08' => 'B08 — ก่อนกิจกรรม',
            'home_b09' => 'B09 — ก่อนวิดีโอรีวิว',
            'home_b10' => 'B10 — ก่อนบทความ',
            'hero'                   => 'Hero — สไลด์หลัก (carousel)',
            'home_desktop_coupon_strip' => 'แถบโปรคูปอง (desktop — หลังฟอร์มค้นหา)',
            'home_before_zones'      => 'ก่อน «จุดหมายยอดนิยม»',
            'home_before_cta'        => 'ก่อน CTA ล่างสุด',
            'home_after_stats'       => '[legacy → B03] กริดโปรโมชันใต้แพแนะนำ',
            'home_before_featured'   => '[legacy → B03] กริดโปรโมชันใต้แพแนะนำ',
            'home_before_coupon'     => '[legacy → B04] ก่อนโปรคูปองมือถือ',
            'home_before_newest'     => '[legacy → B05] ก่อนแพใหม่ล่าสุด',
            'home_before_reviews'    => '[legacy → B09] ก่อนรีวิว',
            'home_before_blog'       => '[legacy → B10] ก่อนบทความ',
            'places_hero'            => 'หน้าที่เที่ยว — Hero (รูปพื้นหลัง)',
            'places_promo_raft'      => 'หน้าที่เที่ยว — Banner แพที่พัก (ซ้าย)',
            'places_promo_deal'      => 'หน้าที่เที่ยว — Banner โปรโมชัน (ขวา)',
        ];
    }

    public static function homeAnchorFragments(string $slot): array
    {
        if ($slot === 'hero') {
            return [
                ['label' => 'ดูตำแหน่ง Desktop', 'fragment' => '#banner-slot-hero-desktop'],
                ['label' => 'ดูตำแหน่ง Mobile', 'fragment' => '#home-mobile-hero'],
            ];
        }

        $anchors = [
            'home_b01' => '#banner-slot-home-b01',
            'home_b02' => '#banner-slot-home-b02',
            'home_b03' => '#banner-slot-home-b03',
            'home_b04' => '#banner-slot-home-b04',
            'home_b05' => '#banner-slot-home-b05',
            'home_b06' => '#banner-slot-home-b06',
            'home_b07' => '#banner-slot-home-b07',
            'home_b08' => '#banner-slot-home-b08',
            'home_b09' => '#banner-slot-home-b09',
            'home_b10' => '#banner-slot-home-b10',
            'home_desktop_coupon_strip' => '#banner-slot-home-desktop-coupon-strip',
            'home_before_zones' => '#banner-slot-home-before-zones',
            'home_before_cta' => '#banner-slot-home-before-cta',
            'home_after_stats' => '#banner-slot-home-b03',
            'home_before_featured' => '#banner-slot-home-b03',
            'home_before_coupon' => '#banner-slot-home-b04',
            'home_before_newest' => '#banner-slot-home-b05',
            'home_before_reviews' => '#banner-slot-home-b09',
            'home_before_blog' => '#banner-slot-home-b10',
            'places_hero' => '#places-hero',
            'places_promo_raft' => '#places-promo-banners',
            'places_promo_deal' => '#places-promo-banners',
        ];

        if (isset($anchors[$slot])) {
            return [['label' => self::isPlacesSlot($slot) ? 'เปิดหน้าที่เที่ยว' : 'เปิดตำแหน่งบนหน้าแรก', 'fragment' => $anchors[$slot]]];
        }

        return [];
    }

    public static function screenBadges(): array
    {
        $allScreens = 'Mobile + Desktop';
        $desktopOnly = 'Desktop only';
        $badges = [
            'hero' => $allScreens,
            'home_desktop_coupon_strip' => $desktopOnly,
            'home_before_zones' => $allScreens,
            'home_before_cta' => $allScreens,
            'places_hero' => $allScreens,
            'places_promo_raft' => $allScreens,
            'places_promo_deal' => $allScreens,
        ];
        foreach (self::B_SLOTS as $slot) {
            $badges[$slot] = $slot === 'home_b06' ? $allScreens : $allScreens;
        }
        foreach (self::LEGACY_HOME_SLOTS as $slot) {
            $badges[$slot] = $allScreens;
        }

        return $badges;
    }

    public static function placementHints(): array
    {
        return [
            'home_b01' => 'Premium — ใต้กล่องค้นหา/จอง ด้านบนสุดของเนื้อหา',
            'home_b02' => 'ก่อนหัวข้อ Recommended by แพกาญ.com',
            'home_b03' => 'หลังการ์ดแพแนะนำ ก่อน «ทำไมต้องเลือกแพกาญ»',
            'home_b04' => 'หลังบล็อก trust ก่อนโปรคูปองมือถือ',
            'home_b05' => 'ก่อนหัวข้อ «แพใหม่ล่าสุด»',
            'home_b06' => 'แทรกใน grid แพใหม่ล่าสุด — ต้องมีป้าย «ผู้สนับสนุน»',
            'home_b07' => 'หลัง section แพใหม่ล่าสุด',
            'home_b08' => 'ก่อนหัวข้อ «กิจกรรม & บริการ»',
            'home_b09' => 'ก่อนโซนคลิปรีวิว',
            'home_b10' => 'ก่อนหัวข้อ «บทความ & ทริปแนะนำ»',
            'hero' => 'สไลด์ภาพใหญ่ด้านบนสุดของหน้าแรก',
            'home_desktop_coupon_strip' => 'แถบโปรคูปองใต้ B01 แสดงเฉพาะ desktop',
            'home_before_zones' => 'ก่อนหัวข้อ «จุดหมายยอดนิยม»',
            'home_before_cta' => 'ก่อน CTA ล่างสุดของหน้าแรก',
            'places_hero' => 'รูปพื้นหลัง Hero หน้า /places',
            'places_promo_raft' => 'Banner รูปแพด้านซ้าย หน้า /places',
            'places_promo_deal' => 'Banner โปรด้านขวา หน้า /places',
        ];
    }

    public static function recommendedImageSpecs(): array
    {
        $wide150 = 'แนะนำ Desktop 1170 × 150 px · Mobile 1080 × 540 px (แนวนอน)';
        $specs = [
            'home_b01' => 'Desktop 1170 × 200 px · Mobile 1080 × 1080 หรือ 1080 × 1350 px',
            'home_b02' => $wide150,
            'home_b03' => $wide150,
            'home_b04' => $wide150,
            'home_b05' => $wide150,
            'home_b06' => 'การ์ด ~300 × 250 px หรือสัดส่วนเดียวกับการ์ดที่พัก',
            'home_b07' => $wide150,
            'home_b08' => $wide150,
            'home_b09' => $wide150,
            'home_b10' => $wide150,
            'hero' => '1920 × 900 px ขึ้นไป (ประมาณ 2∶1)',
            'home_desktop_coupon_strip' => '512 × 512 px (สี่เหลี่ยมจัตุรัส) — โลโก้/ไอคอน',
            'home_before_zones' => $wide150,
            'home_before_cta' => $wide150,
            'places_hero' => '1280 × 720 px ขึ้นไป (แนวนอน)',
            'places_promo_raft' => '800 × 600 px ขึ้นไป',
            'places_promo_deal' => 'ไม่บังคับ — การ์ดใช้พื้นเขียว',
        ];
        foreach (self::LEGACY_HOME_SLOTS as $slot) {
            $specs[$slot] = $wide150;
        }

        return $specs;
    }

    /** aspect class สำหรับ frontend */
    public static function aspectClass(string $slot): string
    {
        if ($slot === 'home_b01') {
            return 'aspect-[1170/200] md:aspect-[1170/200] max-md:aspect-[4/5]';
        }
        if ($slot === 'home_b06') {
            return 'aspect-[4/3]';
        }

        return 'aspect-[1170/150] max-md:aspect-[2/1]';
    }
}
