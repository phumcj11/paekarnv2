<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Property;

class AnalyticsPageViewService
{
    public static function requestPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);

        return ($path === '' || $path === false) ? '/' : $path;
    }

    public static function v2Ready(): bool
    {
        return Database::tableHasColumn('analytics_page_views', 'tracking_version');
    }

    /** บันทึก page view (เงียบเมื่อตารางยังไม่มีหรือ DB error) */
    public static function record(string $path, ?int $propertyId = null, ?string $slug = null): void
    {
        $path = substr($path, 0, 512);
        $slug = $slug !== null ? substr($slug, 0, 255) : null;

        $row = [
            'path' => $path,
            'property_id' => $propertyId,
            'slug' => $slug,
            'referrer_host' => self::referrerHost(),
        ];

        if (self::v2Ready()) {
            $ctx = AnalyticsEventContext::capture();
            $row = array_merge($row, [
                'visitor_hash'     => $ctx['visitor_hash'],
                'session_hash'     => $ctx['session_hash'],
                'user_agent'       => $ctx['user_agent'],
                'device_type'      => $ctx['device_type'],
                'is_bot'           => $ctx['is_bot'],
                'is_internal'      => $ctx['is_internal'],
                'is_counted'       => $ctx['is_counted'],
                'tracking_version' => $ctx['tracking_version'],
            ]);
        }

        try {
            Database::insert('analytics_page_views', $row);
        } catch (\Throwable $e) {
        }
    }

    /** บันทึก GET public อัตโนมัติก่อน dispatch route */
    public static function maybeRecordFromRequest(): void
    {
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
            return;
        }

        $path = self::normalizePath(self::requestPath());
        if ($path === '' || !self::shouldTrackPath($path)) {
            return;
        }

        if (preg_match('#^/property/([^/]+)$#', $path, $m)) {
            $slug = urldecode($m[1]);
            $property = Property::findBySlug($slug);
            self::record($path, $property ? (int)$property['id'] : null, $slug);

            return;
        }

        if (preg_match('#^/blog/([^/]+)$#', $path, $m)) {
            self::record($path, null, urldecode($m[1]));

            return;
        }

        self::record($path, null, null);
    }

    /** @return array{views_today:int,views_7d:int,views_30d:int,unique_today:int,unique_7d:int,unique_30d:int} */
    public static function humanCounts(): array
    {
        $empty = [
            'views_today' => 0, 'views_7d' => 0, 'views_30d' => 0,
            'unique_today' => 0, 'unique_7d' => 0, 'unique_30d' => 0,
        ];
        if (!self::v2Ready()) {
            return $empty;
        }

        try {
            $row = Database::fetch(
                "SELECT
                    SUM(CASE WHEN DATE(created_at) = CURDATE() AND is_counted = 1 THEN 1 ELSE 0 END) AS views_today,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_counted = 1 THEN 1 ELSE 0 END) AS views_7d,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND is_counted = 1 THEN 1 ELSE 0 END) AS views_30d,
                    COUNT(DISTINCT CASE WHEN DATE(created_at) = CURDATE() AND is_counted = 1 THEN visitor_hash END) AS unique_today,
                    COUNT(DISTINCT CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND is_counted = 1 THEN visitor_hash END) AS unique_7d,
                    COUNT(DISTINCT CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND is_counted = 1 THEN visitor_hash END) AS unique_30d
                 FROM analytics_page_views
                 WHERE tracking_version = 2"
            ) ?: [];

            return [
                'views_today'  => (int)($row['views_today'] ?? 0),
                'views_7d'     => (int)($row['views_7d'] ?? 0),
                'views_30d'    => (int)($row['views_30d'] ?? 0),
                'unique_today' => (int)($row['unique_today'] ?? 0),
                'unique_7d'    => (int)($row['unique_7d'] ?? 0),
                'unique_30d'   => (int)($row['unique_30d'] ?? 0),
            ];
        } catch (\Throwable $e) {
            return $empty;
        }
    }

    /** @return array{views_today:int,views_7d:int,views_30d:int} */
    public static function legacyCounts(): array
    {
        try {
            $row = Database::fetch(
                "SELECT
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS views_today,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS views_7d,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS views_30d
                 FROM analytics_page_views
                 WHERE tracking_version = 1 OR tracking_version IS NULL"
            ) ?: [];

            return [
                'views_today' => (int)($row['views_today'] ?? 0),
                'views_7d'    => (int)($row['views_7d'] ?? 0),
                'views_30d'   => (int)($row['views_30d'] ?? 0),
            ];
        } catch (\Throwable $e) {
            return ['views_today' => 0, 'views_7d' => 0, 'views_30d' => 0];
        }
    }

    public static function v2StartedAt(): ?string
    {
        if (!self::v2Ready()) {
            return null;
        }

        try {
            $row = Database::fetch(
                "SELECT MIN(created_at) AS started FROM analytics_page_views WHERE tracking_version = 2"
            );

            return isset($row['started']) && $row['started'] !== null ? (string)$row['started'] : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** @return list<array<string,mixed>> */
    public static function topPathsV2(int $days, int $limit = 25): array
    {
        if (!self::v2Ready()) {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $since = $days === 0 ? 'DATE(created_at) = CURDATE()' : 'created_at >= DATE_SUB(NOW(), INTERVAL ' . (int)$days . ' DAY)';

        try {
            return Database::fetchAll(
                "SELECT path,
                        SUM(CASE WHEN is_counted = 1 THEN 1 ELSE 0 END) AS views,
                        COUNT(DISTINCT CASE WHEN is_counted = 1 THEN visitor_hash END) AS unique_visitors
                 FROM analytics_page_views
                 WHERE tracking_version = 2 AND {$since}
                 GROUP BY path
                 ORDER BY views DESC
                 LIMIT {$limit}"
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return list<array<string,mixed>> */
    public static function topPropertiesV2(int $days, int $limit = 25): array
    {
        if (!self::v2Ready()) {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $since = $days === 0 ? 'DATE(v.created_at) = CURDATE()' : 'v.created_at >= DATE_SUB(NOW(), INTERVAL ' . (int)$days . ' DAY)';

        try {
            return Database::fetchAll(
                "SELECT v.property_id, p.name, p.slug,
                        SUM(CASE WHEN v.is_counted = 1 THEN 1 ELSE 0 END) AS views,
                        COUNT(DISTINCT CASE WHEN v.is_counted = 1 THEN v.visitor_hash END) AS unique_visitors
                 FROM analytics_page_views v
                 INNER JOIN properties p ON p.id = v.property_id
                 WHERE v.tracking_version = 2 AND v.property_id IS NOT NULL AND {$since}
                 GROUP BY v.property_id, p.name, p.slug
                 ORDER BY views DESC
                 LIMIT {$limit}"
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function referrerHost(): ?string
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref === '') {
            return null;
        }
        $host = parse_url($ref, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? substr($host, 0, 255) : null;
    }

    private static function normalizePath(string $path): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $base = preg_replace('#/public/index\.php$#', '', $script);
        $base = preg_replace('#/index\.php$#', '', $base);
        if (is_string($base) && $base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        $path = preg_replace('#^/public#', '', $path) ?: '/';

        return $path === '' ? '/' : $path;
    }

    private static function shouldTrackPath(string $path): bool
    {
        $skipPrefixes = [
            '/admin',
            '/owner',
            '/provider',
            '/api/',
            '/assets/',
            '/uploads/',
        ];
        foreach ($skipPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        if (preg_match('#^/(activities|property)/lead/#', $path)) {
            return false;
        }

        return !in_array($path, ['/robots.txt', '/sitemap.xml', '/favicon.ico'], true);
    }
}
