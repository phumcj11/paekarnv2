<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class ActivityProduct extends Model
{
    protected static string $table = 'activity_products';

    public const CATEGORIES = [
        'tour'              => 'ทัวร์ / แพ็กเกจเที่ยว',
        'water_activity'    => 'กิจกรรมทางน้ำ',
        'nature_activity'   => 'ธรรมชาติ / แคมป์',
        'elephant_activity' => 'กิจกรรมช้าง',
        'ticket'            => 'บัตรเข้า / ค่าเข้าชม',
        'workshop'          => 'เวิร์กช็อป / ชุมชน',
        'car_rental'        => 'รถเช่า',
        'private_driver'    => 'รถพร้อมคนขับ',
        'van_transfer'      => 'รถตู้ / รับส่ง',
        'tour_guide'        => 'ไกด์ / รถนำเที่ยว',
        'boat_service'      => 'เรือ / แพนำเที่ยว',
        'equipment_rental'  => 'เช่าอุปกรณ์',
        'other'             => 'อื่นๆ',
    ];

    public const BOOKING_MODES = [
        'lead'    => 'ติดต่อ / ส่ง lead',
        'voucher' => 'ซื้อ voucher ผ่านเว็บ',
    ];

    public const STATUSES = [
        'draft'          => 'ฉบับร่าง',
        'pending_review' => 'รอตรวจสอบ',
        'published'      => 'เผยแพร่แล้ว',
        'archived'       => 'เก็บถาวร',
    ];

    public static function tableReady(): bool
    {
        return Database::tableHasColumn('activity_products', 'id');
    }

    /** @return array{sql:string,params:array<string,mixed>} */
    private static function publishedFilterClause(?string $category, ?string $district, ?string $zone): array
    {
        $where = ["ap.status = 'published'"];
        $params = [];
        if ($category !== null && $category !== '' && array_key_exists($category, self::CATEGORIES)) {
            $where[] = 'ap.category = :cat';
            $params['cat'] = $category;
        }
        if ($district !== null && $district !== '') {
            $where[] = 'ap.district = :district';
            $params['district'] = $district;
        }
        if ($zone !== null && $zone !== '') {
            $where[] = 'ap.zone = :zone';
            $params['zone'] = $zone;
        }

        return ['sql' => implode(' AND ', $where), 'params' => $params];
    }

    public static function publishedCount(?string $category, ?string $district, ?string $zone): int
    {
        if (!self::tableReady()) {
            return 0;
        }
        $f = self::publishedFilterClause($category, $district, $zone);

        return (int)Database::fetch("SELECT COUNT(*) c FROM activity_products ap WHERE {$f['sql']}", $f['params'])['c'];
    }

    /** @return list<array<string,mixed>> */
    public static function publishedPage(?string $category, ?string $district, ?string $zone, int $limit, int $offset): array
    {
        if (!self::tableReady()) {
            return [];
        }
        $limit = max(1, min(48, $limit));
        $offset = max(0, $offset);
        $f = self::publishedFilterClause($category, $district, $zone);

        return Database::fetchAll(
            "SELECT ap.*, pr.name AS provider_name, pr.phone AS provider_phone, pr.line_id AS provider_line_id
             FROM activity_products ap
             LEFT JOIN activity_providers pr ON pr.id = ap.provider_id
             WHERE {$f['sql']}
             ORDER BY ap.is_featured DESC, ap.priority DESC, ap.district ASC, ap.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $f['params']
        );
    }

    public static function findPublishedById(int $id): ?array
    {
        if (!self::tableReady() || $id <= 0) {
            return null;
        }

        return Database::fetch(
            "SELECT ap.*, pr.name AS provider_name, pr.phone AS provider_phone, pr.line_id AS provider_line_id
             FROM activity_products ap
             LEFT JOIN activity_providers pr ON pr.id = ap.provider_id
             WHERE ap.id = :id AND ap.status = 'published'
             LIMIT 1",
            ['id' => $id]
        );
    }

    public static function findPublishedBySlug(string $slug): ?array
    {
        if (!self::tableReady()) {
            return null;
        }

        return Database::fetch(
            "SELECT ap.*, pr.name AS provider_name, pr.phone AS provider_phone, pr.line_id AS provider_line_id,
                    pr.commission_type, pr.commission_value, vp.name AS place_name, vp.slug AS place_slug
             FROM activity_products ap
             LEFT JOIN activity_providers pr ON pr.id = ap.provider_id
             LEFT JOIN visitor_places vp ON vp.id = ap.place_id
             WHERE ap.slug = :slug AND ap.status = 'published'
             LIMIT 1",
            ['slug' => $slug]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function featured(int $limit = 8): array
    {
        if (!self::tableReady()) {
            return [];
        }
        $limit = max(1, min(24, $limit));

        return Database::fetchAll(
            "SELECT ap.*, pr.name AS provider_name, pr.phone AS provider_phone, pr.line_id AS provider_line_id
             FROM activity_products ap
             LEFT JOIN activity_providers pr ON pr.id = ap.provider_id
             WHERE ap.status = 'published'
             ORDER BY ap.is_featured DESC, ap.priority DESC, ap.id DESC
             LIMIT {$limit}"
        );
    }

    /** @return list<array<string,mixed>> */
    public static function relatedToPlace(array $place, int $limit = 4): array
    {
        if (!self::tableReady()) {
            return [];
        }
        $limit = max(1, min(12, $limit));
        $params = [];
        $where = ["ap.status = 'published'"];
        if (!empty($place['id'])) {
            $where[] = 'ap.place_id = :pid';
            $params['pid'] = (int)$place['id'];
        } elseif (!empty($place['district'])) {
            $where[] = 'ap.district = :district';
            $params['district'] = (string)$place['district'];
        }

        return Database::fetchAll(
            "SELECT ap.*, pr.name AS provider_name, pr.phone AS provider_phone, pr.line_id AS provider_line_id
             FROM activity_products ap
             LEFT JOIN activity_providers pr ON pr.id = ap.provider_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY ap.is_featured DESC, ap.priority DESC, ap.id DESC
             LIMIT {$limit}",
            $params
        );
    }

    /** @return list<array<string,mixed>> */
    public static function options(int $productId, bool $activeOnly = true): array
    {
        if (!Database::tableHasColumn('activity_options', 'id')) {
            return [];
        }
        $where = 'product_id = :pid';
        if ($activeOnly) {
            $where .= ' AND is_active = 1';
        }

        return Database::fetchAll(
            "SELECT * FROM activity_options WHERE {$where} ORDER BY sort_order ASC, id ASC",
            ['pid' => $productId]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function adminAll(): array
    {
        if (!self::tableReady()) {
            return [];
        }

        return Database::fetchAll(
            "SELECT ap.*, pr.name AS provider_name, vp.name AS place_name
             FROM activity_products ap
             LEFT JOIN activity_providers pr ON pr.id = ap.provider_id
             LEFT JOIN visitor_places vp ON vp.id = ap.place_id
             ORDER BY
               CASE ap.status WHEN 'pending_review' THEN 0 WHEN 'draft' THEN 1 WHEN 'published' THEN 2 ELSE 3 END,
               ap.is_featured DESC, ap.priority DESC, ap.id DESC"
        );
    }

    /** @return list<array<string,mixed>> */
    public static function forProvider(int $providerId): array
    {
        if (!self::tableReady() || $providerId <= 0) {
            return [];
        }

        return Database::fetchAll(
            "SELECT ap.* FROM activity_products ap
             WHERE ap.provider_id = :pid
             ORDER BY ap.updated_at DESC, ap.id DESC",
            ['pid' => $providerId]
        );
    }

    public static function findForProvider(int $productId, int $providerId): ?array
    {
        if (!self::tableReady() || $providerId <= 0) {
            return null;
        }

        return Database::fetch(
            'SELECT * FROM activity_products WHERE id = :id AND provider_id = :pid LIMIT 1',
            ['id' => $productId, 'pid' => $providerId]
        );
    }

    public static function pendingReviewCount(): int
    {
        if (!self::tableReady()) {
            return 0;
        }
        $row = Database::fetch(
            "SELECT COUNT(*) AS c FROM activity_products WHERE status = 'pending_review'"
        );

        return (int)($row['c'] ?? 0);
    }

    public static function coverImageUrl(array $row): string
    {
        $cover = trim((string)($row['cover_image'] ?? ''));
        if ($cover !== '') {
            return upload_url($cover);
        }

        return 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?w=1200&q=80';
    }

    public static function lineUrl(?string $lineId): string
    {
        $lineId = trim((string)$lineId);
        if ($lineId === '') {
            return '';
        }
        if (str_starts_with($lineId, 'http')) {
            return $lineId;
        }

        return 'https://line.me/R/ti/p/' . rawurlencode($lineId);
    }
}
