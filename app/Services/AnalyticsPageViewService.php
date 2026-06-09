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

    private static function referrerHost(): ?string
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref === '') {
            return null;
        }
        $host = parse_url($ref, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? substr($host, 0, 255) : null;
    }

    /** บันทึก page view (เงียบเมื่อตารางยังไม่มีหรือ DB error) */
    public static function record(string $path, ?int $propertyId = null, ?string $slug = null): void
    {
        $path = substr($path, 0, 512);
        $slug = $slug !== null ? substr($slug, 0, 255) : null;
        try {
            Database::insert('analytics_page_views', [
                'path' => $path,
                'property_id' => $propertyId,
                'slug' => $slug,
                'referrer_host' => self::referrerHost(),
            ]);
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
