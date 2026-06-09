<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Banner extends Model
{
    protected static string $table = 'banners';

    /** Slots ที่โหลดในหน้าแรก (บางช่องแสดงหลัง «แพที่พักแนะนำ» — ตามลำดับใน home/index.php) */
    public const HOME_SLOTS = [
        'hero',
        'home_desktop_coupon_strip',
        'home_after_stats',
        'home_before_featured',
        'home_before_coupon',
        'home_before_zones',
        'home_before_newest',
        'home_before_reviews',
        'home_before_blog',
        'home_before_cta',
    ];

    public static function activeConditionSql(): string
    {
        return "is_active = 1
            AND (starts_at IS NULL OR starts_at <= NOW())
            AND (ends_at IS NULL OR ends_at >= NOW())";
    }

    /** Banners ที่แสดงได้ทั้งหมดของ slot เดียว */
    public static function activeForSlot(string $slot): array
    {
        $sql = "SELECT * FROM banners WHERE slot = :s AND " . self::activeConditionSql()
            . " ORDER BY sort_order ASC, id ASC";
        return Database::fetchAll($sql, ['s' => $slot]);
    }

    public const PLACES_SLOTS = [
        'places_hero',
        'places_promo_raft',
        'places_promo_deal',
    ];

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
                'subtitle' => trim((string)($row['subtitle'] ?? '')) !== '' ? trim((string)($row['subtitle'])) : $d['subtitle'],
                'button'   => trim((string)($row['button_text'] ?? '')) !== '' ? trim((string)$row['button_text']) : $d['button'],
                'link'     => trim((string)($row['link_url'] ?? '')) !== '' ? trim((string)$row['link_url']) : $d['link'],
            ];
        }

        return $out;
    }

    /** หน้าแรก: group ตาม slot */
    public static function groupedForHome(): array
    {
        $out = [];
        foreach (self::HOME_SLOTS as $slot) {
            $out[$slot] = self::activeForSlot($slot);
        }
        return $out;
    }

    public static function labels(): array
    {
        return [
            'hero'                   => 'Hero — สไลด์หลัก (carousel)',
            'home_desktop_coupon_strip' => 'แถบโปรคูปอง (desktop — หลังฟอร์มค้นหา): รูปซ้าย / หัวข้อ / ปุ่ม',
            'home_after_stats'       => 'หลัง «แพที่พักแนะนำ» — คอลัมน์ซ้ายบน desktop (เรียงก่อน)',
            'home_before_featured'   => 'หลัง «แพที่พักแนะนำ» — คอลัมน์ขวาบน desktop (เรียงถัดไป)',
            'home_before_coupon'     => 'ก่อนบล็อกโปรคูปอง',
            'home_before_zones'      => 'ก่อน «เลือกตามโซน»',
            'home_before_newest'     => 'ก่อน «ที่พักใหม่»',
            'home_before_reviews'    => 'ก่อน «รีวิว»',
            'home_before_blog'       => 'ก่อน «บทความ»',
            'home_before_cta'        => 'ก่อนปุ่ม CTA ล่างสุด',
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
            'home_desktop_coupon_strip' => '#banner-slot-home-desktop-coupon-strip',
            'home_after_stats' => '#banner-slot-home-after-stats',
            'home_before_featured' => '#banner-slot-home-before-featured',
            'home_before_coupon' => '#banner-slot-home-before-coupon',
            'home_before_zones' => '#banner-slot-home-before-zones',
            'home_before_newest' => '#banner-slot-home-before-newest',
            'home_before_reviews' => '#banner-slot-home-before-reviews',
            'home_before_blog' => '#banner-slot-home-before-blog',
            'home_before_cta' => '#banner-slot-home-before-cta',
            'places_hero' => '#places-hero',
            'places_promo_raft' => '#places-promo-banners',
            'places_promo_deal' => '#places-promo-banners',
        ];

        if (isset($anchors[$slot])) {
            return [['label' => Banner::isPlacesSlot($slot) ? 'เปิดหน้าที่เที่ยว' : 'เปิดตำแหน่งบนหน้าแรก', 'fragment' => $anchors[$slot]]];
        }

        return [];
    }

    public static function screenBadges(): array
    {
        return [
            'hero' => 'Mobile + Desktop',
            'home_desktop_coupon_strip' => 'Desktop only',
            'home_after_stats' => 'Desktop emphasis',
            'home_before_featured' => 'Desktop emphasis',
            'home_before_coupon' => 'All screens',
            'home_before_zones' => 'All screens',
            'home_before_newest' => 'All screens',
            'home_before_reviews' => 'All screens',
            'home_before_blog' => 'All screens',
            'home_before_cta' => 'All screens',
            'places_hero' => 'Mobile + Desktop',
            'places_promo_raft' => 'Mobile + Desktop',
            'places_promo_deal' => 'Mobile + Desktop',
        ];
    }

    public static function placementHints(): array
    {
        return [
            'hero' => 'สไลด์ภาพใหญ่ด้านบนสุดของหน้าแรก แยก DOM ระหว่างมือถือและเดสก์ท็อป',
            'home_desktop_coupon_strip' => 'แถบโปรคูปองใต้กล่องค้นหาหน้าแรก แสดงเฉพาะจอ desktop',
            'home_after_stats' => 'กริดโปรโมชันใต้ «แพที่พักแนะนำ» ฝั่งซ้ายเมื่อเป็น desktop',
            'home_before_featured' => 'กริดโปรโมชันใต้ «แพที่พักแนะนำ» ฝั่งขวาเมื่อเป็น desktop',
            'home_before_coupon' => 'ก่อนบล็อกโปรคูปองมือถือ และหลังบล็อก «ทำไมต้องเลือกแพกาญ» บน desktop',
            'home_before_zones' => 'ก่อนหัวข้อ «เลือกตามโซน»',
            'home_before_newest' => 'ก่อนหัวข้อ «ที่พักใหม่ล่าสุด»',
            'home_before_reviews' => 'ก่อนโซนวิดีโอ/รีวิว',
            'home_before_blog' => 'ก่อนหัวข้อ «บทความ & ทริปแนะนำ»',
            'home_before_cta' => 'ก่อน CTA ล่างสุดของหน้าแรก',
            'places_hero' => 'รูปพื้นหลัง Hero หน้า /places · หัวข้อ = ชื่อจังหวัด (script) · หัวข้อย่อย = tagline เช่น ง่ายๆ ใกล้คุณ',
            'places_promo_raft' => 'Banner รูปแพด้านซ้าย · หัวข้อ = ชื่อหลัก · หัวข้อย่อย = คำบรรยาย · ปุ่ม = ข้อความ CTA · ลิงก์ = URL ปลายทาง',
            'places_promo_deal' => 'Banner โปรสีเขียวด้านขวา · รูปไม่บังคับ · หัวข้อ = หัวเรื่อง · หัวข้อย่อย = ตัวเลขลด (เช่น 50%) · ปุ่ม = ข้อความ CTA',
        ];
    }

    /**
     * ข้อความแนะนำขนาดรูปต่อ slot — ใช้ในแอดมิน (ไม่บังคับทางเทคนิค เพราะ frontend ใช้ object-cover/crop)
     */
    public static function recommendedImageSpecs(): array
    {
        $promoCard = 'การ์ดโปรโมชันแนวนอน — แนะนำอย่างน้อย 1600 × 720 px (อัตราส่วนใกล้ 21∶9 หรือ 2.2∶1 ตามที่หน้าเว็บครอป)';

        return [
            'hero' => '1920 × 900 px ขึ้นไป (ประมาณ 2∶1) — พื้นหลังเต็มความกว้าง; วางโฟกัสซ้าย–กลางเพราะมีไล่สีและข้อความทับ',
            'home_desktop_coupon_strip' => '512 × 512 px (สี่เหลี่ยมจัตุรัส) — โลโก้หรือไอคอนโปรโมชัน แสดงเล็ก (~60px, object-contain)',
            'home_after_stats' => $promoCard . ' · ใช้ในคอลัมน์ซ้ายใต้แพแนะนำ',
            'home_before_featured' => $promoCard . ' · ใช้ในคอลัมน์ขวาใต้แพแนะนำ',
            'home_before_coupon' => $promoCard,
            'home_before_zones' => $promoCard,
            'home_before_newest' => $promoCard,
            'home_before_reviews' => $promoCard,
            'home_before_blog' => $promoCard,
            'home_before_cta' => $promoCard,
            'places_hero' => '1280 × 720 px ขึ้นไป (แนวนอน) — สะพานแคว / วิวกาญจนบุรี; วางโฟกัสกลาง–ล่าง',
            'places_promo_raft' => '800 × 600 px ขึ้นไป — รูปแพ/ที่พักริมน้ำ (แสดงครอบเต็มการ์ด)',
            'places_promo_deal' => 'ไม่บังคับ — การ์ดใช้พื้นเขียว; อัปโหลดรูปได้ถ้าต้องการพื้นหลังแทน',
        ];
    }
}
