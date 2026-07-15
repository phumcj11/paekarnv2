<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;
use App\Services\AnalyticsEventContext;

class PropertyLeadClick extends Model
{
    protected static string $table = 'property_lead_clicks';

    public const TYPES = [
        'phone'  => 'คลิกปุ่มโทร',
        'line'   => 'Add LINE',
        'coupon' => 'ซื้อคูปอง',
        'book'   => 'จองออนไลน์',
        'map'    => 'ดูแผนที่',
    ];

    private const DEDUP_MINUTES = 30;

    public static function tableReady(): bool
    {
        return Database::tableHasColumn('property_lead_clicks', 'id');
    }

    public static function v2Ready(): bool
    {
        return Database::tableHasColumn('property_lead_clicks', 'tracking_version');
    }

    /** บันทึก CTA หลัง validate destination แล้ว */
    public static function record(int $propertyId, ?int $unitId, string $type): void
    {
        if (!self::tableReady() || $propertyId <= 0 || !array_key_exists($type, self::TYPES)) {
            return;
        }

        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        $row = [
            'property_id' => $propertyId,
            'unit_id'     => $unitId > 0 ? $unitId : null,
            'click_type'  => $type,
            'ip_hash'     => $ip !== '' ? hash('sha256', $ip) : null,
            'user_agent'  => $ua !== '' ? $ua : null,
        ];

        if (self::v2Ready()) {
            $ctx = AnalyticsEventContext::capture();
            $isCounted = (int)$ctx['is_counted'];
            $dedupeReason = null;

            if ($isCounted && self::isDuplicate($propertyId, $unitId, $type, (string)$ctx['visitor_hash'])) {
                $isCounted = 0;
                $dedupeReason = 'duplicate_30m';
            } elseif (!$isCounted) {
                $dedupeReason = (string)($ctx['exclude_reason'] ?? 'excluded');
            }

            $row = array_merge($row, [
                'visitor_hash'     => $ctx['visitor_hash'],
                'session_hash'     => $ctx['session_hash'],
                'device_type'      => $ctx['device_type'],
                'is_bot'           => $ctx['is_bot'],
                'is_internal'      => $ctx['is_internal'],
                'is_counted'       => $isCounted,
                'tracking_version' => $ctx['tracking_version'],
                'referrer_host'    => $ctx['referrer_host'],
                'dedupe_reason'    => $dedupeReason,
            ]);
        }

        Database::insert('property_lead_clicks', $row);
    }

    private static function isDuplicate(int $propertyId, ?int $unitId, string $type, string $visitorHash): bool
    {
        $unitClause = $unitId > 0 ? 'AND unit_id = :uid' : 'AND unit_id IS NULL';
        $params = [
            'vh' => $visitorHash,
            'p'  => $propertyId,
            't'  => $type,
            'm'  => self::DEDUP_MINUTES,
        ];
        if ($unitId > 0) {
            $params['uid'] = $unitId;
        }

        $row = Database::fetch(
            "SELECT id FROM property_lead_clicks
             WHERE visitor_hash = :vh AND property_id = :p AND click_type = :t
               AND is_counted = 1 AND tracking_version = 2
               {$unitClause}
               AND created_at >= DATE_SUB(NOW(), INTERVAL :m MINUTE)
             LIMIT 1",
            $params
        );

        return !empty($row);
    }

    /** @return array{phone:int,line:int,coupon:int,book:int,map:int,total:int} unique counted V2 */
    public static function uniqueCounts(int $days = 0): array
    {
        $empty = ['phone' => 0, 'line' => 0, 'coupon' => 0, 'book' => 0, 'map' => 0, 'total' => 0];
        if (!self::tableReady()) {
            return $empty;
        }

        $since = $days === 0
            ? 'DATE(created_at) = CURDATE()'
            : 'created_at >= DATE_SUB(NOW(), INTERVAL ' . max(1, $days) . ' DAY)';

        if (self::v2Ready()) {
            $row = Database::fetch(
                "SELECT SUM(CASE WHEN is_counted = 1 THEN 1 ELSE 0 END) AS total,
                        SUM(CASE WHEN click_type = 'phone'  AND is_counted = 1 THEN 1 ELSE 0 END) AS phone_clicks,
                        SUM(CASE WHEN click_type = 'line'   AND is_counted = 1 THEN 1 ELSE 0 END) AS line_clicks,
                        SUM(CASE WHEN click_type = 'coupon' AND is_counted = 1 THEN 1 ELSE 0 END) AS coupon_clicks,
                        SUM(CASE WHEN click_type = 'book'   AND is_counted = 1 THEN 1 ELSE 0 END) AS book_clicks,
                        SUM(CASE WHEN click_type = 'map'    AND is_counted = 1 THEN 1 ELSE 0 END) AS map_clicks
                 FROM property_lead_clicks
                 WHERE tracking_version = 2 AND {$since}"
            ) ?: [];
        } else {
            $row = Database::fetch(
                "SELECT COUNT(*) AS total,
                        SUM(CASE WHEN click_type = 'phone' THEN 1 ELSE 0 END) AS phone_clicks,
                        SUM(CASE WHEN click_type = 'line' THEN 1 ELSE 0 END) AS line_clicks,
                        SUM(CASE WHEN click_type = 'coupon' THEN 1 ELSE 0 END) AS coupon_clicks,
                        SUM(CASE WHEN click_type = 'book' THEN 1 ELSE 0 END) AS book_clicks,
                        SUM(CASE WHEN click_type = 'map' THEN 1 ELSE 0 END) AS map_clicks
                 FROM property_lead_clicks
                 WHERE {$since}"
            ) ?: [];
        }

        return [
            'phone'  => (int)($row['phone_clicks'] ?? 0),
            'line'   => (int)($row['line_clicks'] ?? 0),
            'coupon' => (int)($row['coupon_clicks'] ?? 0),
            'book'   => (int)($row['book_clicks'] ?? 0),
            'map'    => (int)($row['map_clicks'] ?? 0),
            'total'  => (int)($row['total'] ?? 0),
        ];
    }

    /** @return array{phone:int,line:int,coupon:int,book:int,map:int,total:int,raw:int,duplicate:int,bot:int,internal:int} */
    public static function auditCountsToday(): array
    {
        $base = self::uniqueCounts(0);
        $base['raw'] = 0;
        $base['duplicate'] = 0;
        $base['bot'] = 0;
        $base['internal'] = 0;

        if (!self::v2Ready()) {
            $base['raw'] = $base['total'];

            return $base;
        }

        $row = Database::fetch(
            "SELECT COUNT(*) AS raw_total,
                    SUM(CASE WHEN dedupe_reason = 'duplicate_30m' THEN 1 ELSE 0 END) AS dup,
                    SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END) AS bot,
                    SUM(CASE WHEN is_internal = 1 THEN 1 ELSE 0 END) AS internal
             FROM property_lead_clicks
             WHERE tracking_version = 2 AND DATE(created_at) = CURDATE()"
        ) ?: [];

        $base['raw'] = (int)($row['raw_total'] ?? 0);
        $base['duplicate'] = (int)($row['dup'] ?? 0);
        $base['bot'] = (int)($row['bot'] ?? 0);
        $base['internal'] = (int)($row['internal'] ?? 0);

        return $base;
    }

    /** @return array{phone:int,line:int,coupon:int,book:int,total:int} legacy raw counts today */
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
             WHERE (tracking_version = 1 OR tracking_version IS NULL) AND DATE(created_at) = CURDATE()"
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
        return self::topPropertiesV2($clickType, 0, $limit);
    }

    /** @return list<array<string,mixed>> */
    public static function topPropertiesV2(string $clickType, int $days, int $limit = 15): array
    {
        if (!self::tableReady() || !array_key_exists($clickType, self::TYPES)) {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $since = $days === 0
            ? 'DATE(lc.created_at) = CURDATE()'
            : 'lc.created_at >= DATE_SUB(NOW(), INTERVAL ' . max(1, $days) . ' DAY)';

        $counted = self::v2Ready() ? 'AND lc.is_counted = 1 AND lc.tracking_version = 2' : '';

        return Database::fetchAll(
            "SELECT p.id, p.name, p.slug, COUNT(*) AS cnt
             FROM property_lead_clicks lc
             INNER JOIN properties p ON p.id = lc.property_id
             WHERE lc.click_type = :t {$counted} AND {$since}
             GROUP BY p.id, p.name, p.slug
             ORDER BY cnt DESC
             LIMIT {$limit}",
            ['t' => $clickType]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function recentAudit(int $limit = 30): array
    {
        if (!self::tableReady()) {
            return [];
        }

        $limit = max(1, min(100, $limit));
        $cols = self::v2Ready()
            ? 'lc.id, lc.created_at, lc.click_type, lc.device_type, lc.referrer_host,
               lc.visitor_hash, lc.is_counted, lc.is_bot, lc.is_internal, lc.dedupe_reason,
               lc.tracking_version, p.name AS property_name, p.slug AS property_slug'
            : 'lc.id, lc.created_at, lc.click_type, lc.user_agent,
               p.name AS property_name, p.slug AS property_slug';

        return Database::fetchAll(
            "SELECT {$cols}
             FROM property_lead_clicks lc
             INNER JOIN properties p ON p.id = lc.property_id
             ORDER BY lc.created_at DESC
             LIMIT {$limit}"
        );
    }

    public static function v2StartedAt(): ?string
    {
        if (!self::v2Ready()) {
            return null;
        }

        $row = Database::fetch(
            "SELECT MIN(created_at) AS started FROM property_lead_clicks WHERE tracking_version = 2"
        );

        return isset($row['started']) && $row['started'] !== null ? (string)$row['started'] : null;
    }

    /** @return array{total:int,confirmed:int} */
    public static function bookingOutcomes(int $days = 0): array
    {
        $since = $days === 0
            ? 'DATE(b.created_at) = CURDATE()'
            : 'b.created_at >= DATE_SUB(NOW(), INTERVAL ' . max(1, $days) . ' DAY)';

        $row = Database::fetch(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN b.status IN ('confirmed','completed') THEN 1 ELSE 0 END) AS confirmed
             FROM bookings b
             WHERE {$since}"
        ) ?: [];

        return [
            'total'     => (int)($row['total'] ?? 0),
            'confirmed' => (int)($row['confirmed'] ?? 0),
        ];
    }
}
