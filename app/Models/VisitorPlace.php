<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class VisitorPlace extends Model
{
    protected static string $table = 'visitor_places';

    public const CATEGORIES = [
        'cafe'        => 'คาเฟ่ / ร้านกาแฟ',
        'restaurant'  => 'ร้านอาหาร',
        'attraction'  => 'ที่เที่ยวทั่วไป',
        'viewpoint'   => 'จุดชมวิว',
        'temple'      => 'วัด / ศาสนสถาน',
        'market'      => 'ตลาด / ชุมชน',
        'nature'      => 'ธรรมชาติ / อุทยาน',
        'other'       => 'อื่นๆ',
    ];

    /** อำเภอในจังหวัดกาญจนบุรี (ลำดับทางการ) */
    public const DISTRICTS = [
        'เมืองกาญจนบุรี',
        'ท่าม่วง',
        'ท่ามะกา',
        'ทองผาภูมิ',
        'สังขละบุรี',
        'พนมทวน',
        'เลาขวัญ',
        'หนองปรือ',
        'ห้วยกระเจา',
        'ด่านมะขามเตี้ย',
        'บ่อพลอย',
        'ศรีสวัสดิ์',
        'ไทรโยค',
    ];

    public static function findPublishedBySlug(string $slug): ?array
    {
        return Database::fetch(
            "SELECT * FROM visitor_places WHERE slug = :s AND is_active = 1 LIMIT 1",
            ['s' => $slug]
        );
    }

    /**
     * @return array{sql:string,params:array<string,mixed>}
     */
    private static function publishedFilterClause(
        ?string $category,
        ?string $zone,
        ?string $district,
        ?string $q = null,
        ?string $tag = null,
        bool $openNow = false
    ): array
    {
        $where  = ['vp.is_active = 1'];
        $params = [];
        if ($category !== null && $category !== '' && array_key_exists($category, self::CATEGORIES)) {
            $where[]       = 'vp.category = :cat';
            $params['cat'] = $category;
        }
        if ($zone !== null && $zone !== '') {
            $where[]        = 'vp.zone = :zone';
            $params['zone'] = $zone;
        }
        if ($district !== null && $district !== '' && in_array($district, self::DISTRICTS, true)) {
            $where[]          = 'vp.district = :dist';
            $params['dist']   = $district;
        }
        $q = trim((string) $q);
        if ($q !== '') {
            $where[] = '(vp.name LIKE :q_name OR vp.excerpt LIKE :q_excerpt OR vp.address LIKE :q_address OR vp.zone LIKE :q_zone OR vp.district LIKE :q_district)';
            $like = '%' . $q . '%';
            $params['q_name'] = $like;
            $params['q_excerpt'] = $like;
            $params['q_address'] = $like;
            $params['q_zone'] = $like;
            $params['q_district'] = $like;
        }
        $tag = trim((string) $tag);
        if ($tag !== '') {
            if ($tag === 'pet_friendly') {
                $where[] = 'vp.is_pet_friendly = 1';
            } elseif ($tag === 'photo_spot') {
                $where[] = 'vp.is_photo_spot = 1';
            } else {
                $where[] = 'vp.tags LIKE :tag';
                $params['tag'] = '%' . $tag . '%';
            }
        }
        if ($openNow) {
            $where[] = 'vp.is_open_now = 1';
        }
        return ['sql' => implode(' AND ', $where), 'params' => $params];
    }

    public static function publishedCount(
        ?string $category,
        ?string $zone,
        ?string $district,
        ?string $q = null,
        ?string $tag = null,
        bool $openNow = false
    ): int
    {
        $f   = self::publishedFilterClause($category, $zone, $district, $q, $tag, $openNow);
        $sql = 'SELECT COUNT(*) c FROM visitor_places vp WHERE ' . $f['sql'];
        return (int) Database::fetch($sql, $f['params'])['c'];
    }

    /** @return list<array<string,mixed>> */
    public static function publishedPage(
        ?string $category,
        ?string $zone,
        ?string $district,
        int $limit,
        int $offset,
        ?string $sort = null,
        ?string $q = null,
        ?string $tag = null,
        bool $openNow = false
    ): array
    {
        $limit  = max(1, min(48, $limit));
        $offset = max(0, $offset);
        $f      = self::publishedFilterClause($category, $zone, $district, $q, $tag, $openNow);
        $order  = ($sort === 'latest')
            ? 'vp.created_at DESC, vp.id DESC'
            : (($category === 'cafe') ? 'vp.rating_avg DESC, vp.sort_order ASC, vp.id DESC' : 'vp.sort_order ASC, vp.id DESC');
        $sql    = "SELECT vp.* FROM visitor_places vp WHERE {$f['sql']} ORDER BY {$order} LIMIT {$limit} OFFSET {$offset}";
        return Database::fetchAll($sql, $f['params']);
    }

    /**
     * Count rows sorted by distance (requires lat/lng + rows to have latitude/longitude)
     */
    public static function publishedCountGeo(
        float $lat,
        float $lng,
        ?string $category,
        ?string $zone,
        ?string $district,
        ?string $q = null,
        ?string $tag = null,
        bool $openNow = false
    ): int
    {
        $f   = self::publishedFilterClause($category, $zone, $district, $q, $tag, $openNow);
        $sql = 'SELECT COUNT(*) c FROM visitor_places vp WHERE ' . $f['sql']
             . ' AND vp.latitude IS NOT NULL AND vp.longitude IS NOT NULL';
        return (int) Database::fetch($sql, $f['params'])['c'];
    }

    /** @return list<array<string,mixed>> */
    public static function publishedPageGeo(
        float $lat,
        float $lng,
        ?string $category,
        ?string $zone,
        ?string $district,
        int $limit,
        int $offset,
        ?string $sort = null,
        ?string $q = null,
        ?string $tag = null,
        bool $openNow = false
    ): array
    {
        $limit  = max(1, min(48, $limit));
        $offset = max(0, $offset);
        $f      = self::publishedFilterClause($category, $zone, $district, $q, $tag, $openNow);
        $params = array_merge($f['params'], [
            '_lat_cos' => $lat,
            '_lat_sin' => $lat,
            '_lng' => $lng,
        ]);
        $order  = ($sort === 'latest')
            ? 'vp.created_at DESC, vp.id DESC'
            : 'distance_km ASC';
        $sql    = "SELECT vp.*,
            (6371 * ACOS(LEAST(1, COS(RADIANS(:_lat_cos)) * COS(RADIANS(vp.latitude))
              * COS(RADIANS(vp.longitude) - RADIANS(:_lng))
              + SIN(RADIANS(:_lat_sin)) * SIN(RADIANS(vp.latitude))))) AS distance_km
            FROM visitor_places vp
            WHERE {$f['sql']}
              AND vp.latitude IS NOT NULL AND vp.longitude IS NOT NULL
            ORDER BY {$order}
            LIMIT {$limit} OFFSET {$offset}";
        return Database::fetchAll($sql, $params);
    }

    /** @return list<array<string,mixed>> */
    public static function adminAll(): array
    {
        return Database::fetchAll(
            'SELECT * FROM visitor_places ORDER BY (district IS NULL), district ASC, sort_order ASC, id DESC'
        );
    }

    /**
     * URL รูปปกสำหรับการ์ด/หน้ารายละเอียด — ถ้ามี cover_image ใน DB (path uploads หรือ URL เต็ม) ใช้ค่านั้น,
     * ไม่เช่นนั้นเลือกจากธีมตาม slug/หมวด (รูป Commons เชิงลิขสิทธิ์ CC และภาพสต็อก Unsplash)
     *
     * @param array<string,mixed> $row
     */
    public static function coverImageUrl(array $row): string
    {
        $cover = trim((string)($row['cover_image'] ?? ''));
        if ($cover !== '') {
            return upload_img($cover, 'thumb');
        }
        $theme = self::inferCoverTheme($row);

        return self::COVER_BY_THEME[$theme] ?? self::COVER_BY_THEME['kwai'];
    }

    /**
     * Returns an array of gallery image URLs (full size).
     * Falls back to cover image when gallery is empty.
     *
     * @param  array<string,mixed> $row
     * @param  int                 $limit  max images to return
     * @return list<string>
     */
    public static function galleryUrls(array $row, int $limit = 6): array
    {
        $json = trim((string)($row['gallery_images'] ?? ''));
        if ($json !== '' && str_starts_with($json, '[')) {
            $decoded = json_decode($json, true);
            if (is_array($decoded) && !empty($decoded)) {
                $urls = [];
                foreach (array_slice($decoded, 0, $limit) as $filename) {
                    $filename = trim((string)$filename);
                    if ($filename !== '') {
                        $urls[] = upload_img($filename, 'thumb');
                    }
                }
                if (!empty($urls)) {
                    return $urls;
                }
            }
        }

        // Fall back: just the cover image
        return [self::coverImageUrl($row)];
    }

    /** @var array<string,string> */
    private const COVER_BY_THEME = [
        'kwai'       => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Bridge_on_the_River_Kwai_-_tourist_plaza.JPG/1024px-Bridge_on_the_River_Kwai_-_tourist_plaza.JPG',
        'waterfall'  => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/af/Erawan_Waterfall_Level_7_%28on_July%29.jpg/1024px-Erawan_Waterfall_Level_7_%28on_July%29.jpg',
        'dam'        => 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Srinagarind_Dam.jpg/1280px-Srinagarind_Dam.jpg',
        'hellfire'   => 'https://upload.wikimedia.org/wikipedia/commons/7/7a/Hellfire_pass.jpg',
        'railway'    => 'https://upload.wikimedia.org/wikipedia/commons/a/ab/Bridge_over_River_Kwai_in_Kanchanaburi%2C_Thailand.jpg',
        'temple'     => 'https://images.unsplash.com/photo-1569937756447-86cfe617087c?w=1200&q=80',
        'mon_bridge' => 'https://images.unsplash.com/photo-1545569341-9eb8b30979d9?w=1200&q=80',
        'cemetery'   => 'https://images.unsplash.com/photo-1467699719954-e9939220769f?w=1200&q=80',
        'elephant'   => 'https://images.unsplash.com/photo-1564760055775-d63b17a55c44?w=1200&q=80',
        'lotus'      => 'https://images.unsplash.com/photo-1490750967868-88aa4486c946?w=1200&q=80',
        'viewpoint'  => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1200&q=80',
        'museum'     => 'https://images.unsplash.com/photo-1566127444979-b3d2b994ddb9?w=1200&q=80',
        'market'     => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=1200&q=80',
        'cafe'       => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=1200&q=80',
        'restaurant' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1200&q=80',
        'khmer'      => 'https://images.unsplash.com/photo-1600630695478-e073046673bf?w=1200&q=80',
        'jungle'     => 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1200&q=80',
        'other'      => 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/24/Bridge_on_the_River_Kwai_-_tourist_plaza.JPG/1024px-Bridge_on_the_River_Kwai_-_tourist_plaza.JPG',
    ];

    /**
     * @param array<string,mixed> $row
     */
    private static function inferCoverTheme(array $row): string
    {
        $slug = (string)($row['slug'] ?? '');
        $cat  = (string)($row['category'] ?? 'attraction');

        if (str_contains($slug, 'hellfire')) {
            return 'hellfire';
        }
        if (str_contains($slug, 'elephant') || str_starts_with($slug, 'chang-')) {
            return 'elephant';
        }
        if (str_contains($slug, 'prommitr')) {
            return 'museum';
        }
        if (str_contains($slug, 'prasat-muang')) {
            return 'khmer';
        }
        if (str_contains($slug, 'thung-bua')) {
            return 'lotus';
        }
        if (
            str_starts_with($slug, 'saphan-mon')
            || (str_contains($slug, 'songkalia') && !str_contains($slug, 'dining'))
            || str_contains($slug, 'khlong-jao')
            || str_contains($slug, 'wang-wiwekaram')
        ) {
            return 'mon_bridge';
        }
        if (
            str_contains($slug, 'cemetery')
            || str_starts_with($slug, 'suksan-thahan')
            || str_contains($slug, 'chongkai-allied')
        ) {
            return 'cemetery';
        }
        if (
            str_contains($slug, 'khuean-')
            || str_contains($slug, 'sam-praphea')
            || str_contains($slug, 'rajjaprabha-dam')
            || str_contains($slug, 'khuean-thap')
        ) {
            return 'dam';
        }
        if (
            str_starts_with($slug, 'namtok-')
            || str_starts_with($slug, 'dao-tung')
            || str_contains($slug, 'sam-phran-waterfall')
            || str_contains($slug, 'namtok-dti')
            || str_contains($slug, 'huai-mae-khamin')
            || str_contains($slug, 'tham-lawana')
            || str_contains($slug, 'tham-daowadueng')
            || str_starts_with($slug, 'lumnam-')
        ) {
            return 'waterfall';
        }
        if (
            (
                str_contains($slug, 'railway')
                || str_contains($slug, 'tham-krasae')
                || str_contains($slug, 'thon-chukkachat')
                || str_contains($slug, 'tha-kilen')
                || str_contains($slug, 'railway-station')
                || str_contains($slug, 'death-railway')
            )
            && !str_contains($slug, 'coffee')
        ) {
            return 'railway';
        }
        if (str_starts_with($slug, 'wat-') || str_contains($slug, 'city-pillar')) {
            return 'temple';
        }
        if (
            str_contains($slug, 'museum')
            || str_contains($slug, 'jeath')
            || str_contains($slug, 'thailand-burma')
        ) {
            return 'museum';
        }
        if (
            str_contains($slug, 'walking-street')
            || str_contains($slug, 'chumchon-pakphraek')
            || str_contains($slug, 'bo-phloi-gem')
            || str_contains($slug, 'weaving-village')
            || str_contains($slug, 'malika')
        ) {
            return 'market';
        }
        if (
            str_contains($slug, 'pilok')
            || str_contains($slug, 'sam-phrada')
            || str_contains($slug, 'pha-chu-thong')
            || str_contains($slug, 'noen-maprang')
            || str_contains($slug, 'tamdiao')
            || str_contains($slug, 'ban-khao-lam')
            || str_contains($slug, 'pak-saeng')
            || str_contains($slug, 'thung-thalay')
            || str_contains($slug, 'thung-nang')
        ) {
            return 'viewpoint';
        }
        if (str_contains($slug, 'sai-yok-national') || str_contains($slug, 'jungle-rafts')) {
            return 'jungle';
        }

        return match ($cat) {
            'nature'    => 'waterfall',
            'temple'    => 'temple',
            'viewpoint' => 'viewpoint',
            'market'    => 'market',
            'cafe'      => 'cafe',
            'restaurant' => 'restaurant',
            'other'     => 'other',
            default     => 'kwai',
        };
    }
}
