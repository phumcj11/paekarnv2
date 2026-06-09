<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class ActivityLeadClick extends Model
{
    protected static string $table = 'activity_lead_clicks';

    public const TYPES = [
        'line'  => 'LINE',
        'phone' => 'โทรศัพท์',
    ];

    public static function tableReady(): bool
    {
        return Database::tableHasColumn('activity_lead_clicks', 'id');
    }

    public static function record(int $productId, ?int $providerId, string $type): void
    {
        if (!self::tableReady() || $productId <= 0 || !array_key_exists($type, self::TYPES)) {
            return;
        }

        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        Database::insert('activity_lead_clicks', [
            'product_id'  => $productId,
            'provider_id' => $providerId > 0 ? $providerId : null,
            'click_type'  => $type,
            'ip_hash'     => $ip !== '' ? hash('sha256', $ip) : null,
            'user_agent'  => $ua !== '' ? $ua : null,
        ]);
    }

    /** @return array{total:int,line:int,phone:int,month:int} */
    public static function providerSummary(int $providerId): array
    {
        $empty = ['total' => 0, 'line' => 0, 'phone' => 0, 'month' => 0];
        if (!self::tableReady() || $providerId <= 0) {
            return $empty;
        }

        $all = Database::fetch(
            "SELECT COUNT(*) AS total,
                    SUM(CASE WHEN click_type = 'line' THEN 1 ELSE 0 END) AS line_clicks,
                    SUM(CASE WHEN click_type = 'phone' THEN 1 ELSE 0 END) AS phone_clicks
             FROM activity_lead_clicks WHERE provider_id = :pid",
            ['pid' => $providerId]
        ) ?: [];
        $month = Database::fetch(
            "SELECT COUNT(*) AS c FROM activity_lead_clicks
             WHERE provider_id = :pid AND created_at >= :ms",
            ['pid' => $providerId, 'ms' => date('Y-m-01 00:00:00')]
        ) ?: [];

        return [
            'total' => (int)($all['total'] ?? 0),
            'line'  => (int)($all['line_clicks'] ?? 0),
            'phone' => (int)($all['phone_clicks'] ?? 0),
            'month' => (int)($month['c'] ?? 0),
        ];
    }

    /** @return list<array<string,mixed>> */
    public static function adminByProduct(?string $monthYm = null, int $limit = 100): array
    {
        if (!self::tableReady()) {
            return [];
        }
        $where = ['1=1'];
        $params = [];
        if ($monthYm !== null && $monthYm !== '' && preg_match('/^\d{4}-\d{2}$/', $monthYm)) {
            $where[] = 'DATE_FORMAT(lc.created_at, "%Y-%m") = :m';
            $params['m'] = $monthYm;
        }
        $limit = max(1, min(500, $limit));

        return Database::fetchAll(
            "SELECT ap.id AS product_id, ap.title AS product_title, pr.name AS provider_name,
                    COUNT(*) AS click_count,
                    SUM(CASE WHEN lc.click_type = 'line' THEN 1 ELSE 0 END) AS line_clicks,
                    SUM(CASE WHEN lc.click_type = 'phone' THEN 1 ELSE 0 END) AS phone_clicks
             FROM activity_lead_clicks lc
             INNER JOIN activity_products ap ON ap.id = lc.product_id
             LEFT JOIN activity_providers pr ON pr.id = lc.provider_id
             WHERE " . implode(' AND ', $where) . "
             GROUP BY ap.id, ap.title, pr.name
             ORDER BY click_count DESC
             LIMIT {$limit}",
            $params
        );
    }
}
