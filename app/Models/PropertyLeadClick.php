<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class PropertyLeadClick extends Model
{
    protected static string $table = 'property_lead_clicks';

    public const TYPES = [
        'phone'  => 'โทรติดต่อ',
        'line'   => 'Add LINE',
        'coupon' => 'ซื้อคูปอง',
        'book'   => 'จองออนไลน์',
        'map'    => 'ดูแผนที่',
    ];

    public static function tableReady(): bool
    {
        return Database::tableHasColumn('property_lead_clicks', 'id');
    }

    public static function record(int $propertyId, ?int $unitId, string $type): void
    {
        if (!self::tableReady() || $propertyId <= 0 || !array_key_exists($type, self::TYPES)) {
            return;
        }

        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        Database::insert('property_lead_clicks', [
            'property_id' => $propertyId,
            'unit_id'     => $unitId > 0 ? $unitId : null,
            'click_type'  => $type,
            'ip_hash'     => $ip !== '' ? hash('sha256', $ip) : null,
            'user_agent'  => $ua !== '' ? $ua : null,
        ]);
    }

    /** @return array{phone:int,line:int,coupon:int,book:int,total:int} */
    public static function countsToday(): array
    {
        $empty = ['phone' => 0, 'line' => 0, 'coupon' => 0, 'book' => 0, 'total' => 0];
        if (!self::tableReady()) {
            return $empty;
        }

        $row = Database::fetch(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN click_type = 'phone' THEN 1 ELSE 0 END) AS phone_clicks,
                    SUM(CASE WHEN click_type = 'line' THEN 1 ELSE 0 END) AS line_clicks,
                    SUM(CASE WHEN click_type = 'coupon' THEN 1 ELSE 0 END) AS coupon_clicks,
                    SUM(CASE WHEN click_type = 'book' THEN 1 ELSE 0 END) AS book_clicks
             FROM property_lead_clicks
             WHERE DATE(created_at) = CURDATE()"
        ) ?: [];

        return [
            'phone'  => (int)($row['phone_clicks'] ?? 0),
            'line'   => (int)($row['line_clicks'] ?? 0),
            'coupon' => (int)($row['coupon_clicks'] ?? 0),
            'book'   => (int)($row['book_clicks'] ?? 0),
            'total'  => (int)($row['total'] ?? 0),
        ];
    }

    /** @return list<array<string,mixed>> */
    public static function topPropertiesToday(string $clickType, int $limit = 15): array
    {
        if (!self::tableReady() || !array_key_exists($clickType, self::TYPES)) {
            return [];
        }

        $limit = max(1, min(50, $limit));

        return Database::fetchAll(
            "SELECT p.id, p.name, p.slug, COUNT(*) AS cnt
             FROM property_lead_clicks lc
             INNER JOIN properties p ON p.id = lc.property_id
             WHERE lc.click_type = :t AND DATE(lc.created_at) = CURDATE()
             GROUP BY p.id, p.name, p.slug
             ORDER BY cnt DESC
             LIMIT {$limit}",
            ['t' => $clickType]
        );
    }
}
