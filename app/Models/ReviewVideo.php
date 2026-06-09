<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class ReviewVideo extends Model
{
    protected static string $table = 'review_videos';

    public const CATEGORIES = [
        'rafting_review' => 'รีวิวแพ / ที่พัก',
        'attraction'     => 'ที่เที่ยว',
        'city_guide'     => 'ไกด์เมืองกาญ',
        'general'        => 'ทั่วไป',
    ];

    public const PLATFORMS = [
        'youtube'   => 'YouTube',
        'tiktok'    => 'TikTok',
        'instagram' => 'Instagram',
    ];

    public static function hasSocialColumns(): bool
    {
        return Database::tableHasColumn('review_videos', 'platform');
    }

    public static function parseYoutubeId(string $input): ?string
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }
        if (preg_match('#^[a-zA-Z0-9_-]{11}$#', $input)) {
            return $input;
        }
        if (preg_match('#[?&]v=([a-zA-Z0-9_-]{11})#', $input, $m)) {
            return $m[1];
        }
        if (preg_match('#youtu\.be/([a-zA-Z0-9_-]{11})#', $input, $m)) {
            return $m[1];
        }
        if (preg_match('#youtube\.com/embed/([a-zA-Z0-9_-]{11})#', $input, $m)) {
            return $m[1];
        }
        if (preg_match('#youtube\.com/shorts/([a-zA-Z0-9_-]{11})#', $input, $m)) {
            return $m[1];
        }

        return null;
    }

    /** @return ?array{platform:string,external_id:string,source_url:string,orientation:string,youtube_id?:string} */
    public static function parseVideoUrl(string $input): ?array
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }

        $ytId = self::parseYoutubeId($input);
        if ($ytId !== null) {
            $isShort = stripos($input, '/shorts/') !== false;

            return [
                'platform'    => 'youtube',
                'external_id' => $ytId,
                'source_url'  => $isShort
                    ? 'https://www.youtube.com/shorts/' . $ytId
                    : 'https://www.youtube.com/watch?v=' . $ytId,
                'orientation' => $isShort ? 'portrait' : 'landscape',
                'youtube_id'  => $ytId,
            ];
        }

        if (!preg_match('#^https?://#i', $input)) {
            $input = 'https://' . ltrim($input, '/');
        }

        if (preg_match('#tiktok\.com/@[^/]+/video/(\d+)#i', $input, $m)) {
            $url = self::normalizeHttpUrl($input);

            return [
                'platform'    => 'tiktok',
                'external_id' => $m[1],
                'source_url'  => $url,
                'orientation' => 'portrait',
            ];
        }

        if (preg_match('#(?:vm|vt)\.tiktok\.com/([A-Za-z0-9]+)#i', $input, $m)) {
            $url = self::normalizeHttpUrl($input);

            return [
                'platform'    => 'tiktok',
                'external_id' => 'vm_' . $m[1],
                'source_url'  => $url,
                'orientation' => 'portrait',
            ];
        }

        if (preg_match('#instagram\.com/(reel|p)/([A-Za-z0-9_-]+)#i', $input, $m)) {
            $url = self::normalizeHttpUrl($input);

            return [
                'platform'    => 'instagram',
                'external_id' => $m[2],
                'source_url'  => $url,
                'orientation' => 'portrait',
            ];
        }

        return null;
    }

    private static function normalizeHttpUrl(string $input): string
    {
        $input = trim($input);
        if (!preg_match('#^https?://#i', $input)) {
            $input = 'https://' . $input;
        }
        $parts = parse_url($input);
        if ($parts === false || empty($parts['host'])) {
            return $input;
        }
        $scheme = strtolower((string)($parts['scheme'] ?? 'https'));
        $path   = $parts['path'] ?? '';
        $query  = isset($parts['query']) ? '?' . $parts['query'] : '';

        return $scheme . '://' . $parts['host'] . $path . $query;
    }

    public static function platformOf(array $row): string
    {
        $p = (string)($row['platform'] ?? 'youtube');

        return array_key_exists($p, self::PLATFORMS) ? $p : 'youtube';
    }

    public static function externalIdOf(array $row): string
    {
        $id = trim((string)($row['external_id'] ?? ''));
        if ($id !== '') {
            return $id;
        }

        return trim((string)($row['youtube_id'] ?? ''));
    }

    public static function sourceUrlOf(array $row): string
    {
        $url = trim((string)($row['source_url'] ?? ''));
        if ($url !== '') {
            return $url;
        }
        $id = self::externalIdOf($row);
        if ($id === '') {
            return '';
        }

        return 'https://www.youtube.com/watch?v=' . $id;
    }

    public static function embedUrl(string $youtubeId): string
    {
        return 'https://www.youtube-nocookie.com/embed/' . rawurlencode($youtubeId);
    }

    public static function embedUrlFor(array $row): string
    {
        if (self::platformOf($row) !== 'youtube') {
            return self::sourceUrlOf($row);
        }

        return self::embedUrl(self::externalIdOf($row));
    }

    public static function thumbnailUrl(string $youtubeId): string
    {
        return 'https://i.ytimg.com/vi/' . rawurlencode($youtubeId) . '/hqdefault.jpg';
    }

    public static function thumbnailUrlFor(array $row): ?string
    {
        $cached = trim((string)($row['thumbnail_url'] ?? ''));
        if ($cached !== '') {
            return $cached;
        }
        if (self::platformOf($row) === 'youtube') {
            $id = self::externalIdOf($row);

            return $id !== '' ? self::thumbnailUrl($id) : null;
        }

        return null;
    }

    public static function platformLabel(array $row): string
    {
        return self::PLATFORMS[self::platformOf($row)] ?? self::platformOf($row);
    }

    public static function isPortrait(array $row): bool
    {
        if (self::hasSocialColumns()) {
            return ($row['orientation'] ?? 'landscape') === 'portrait';
        }

        return false;
    }

    /** @param list<array<string,mixed>> $rows
     *  @return array{landscape:list<array<string,mixed>>,portrait:list<array<string,mixed>>} */
    public static function partitionByOrientation(array $rows): array
    {
        $landscape = [];
        $portrait  = [];

        foreach ($rows as $row) {
            if (self::isPortrait($row)) {
                $portrait[] = $row;
            } else {
                $landscape[] = $row;
            }
        }

        return ['landscape' => $landscape, 'portrait' => $portrait];
    }

    /** @return list<array<string,mixed>> */
    public static function activeOrdered(int $limit = 20, int $offset = 0): array
    {
        $limit  = max(1, min(100, $limit));
        $offset = max(0, $offset);

        return Database::fetchAll(
            "SELECT v.*, p.name AS property_name, p.slug AS property_slug
             FROM review_videos v
             LEFT JOIN properties p ON p.id = v.related_property_id
             WHERE v.is_active = 1
             ORDER BY v.sort_order ASC, v.id DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
    }

    /** @return list<array<string,mixed>> */
    public static function activePortraitOrdered(int $limit = 24, int $offset = 0): array
    {
        $limit  = max(1, min(100, $limit));
        $offset = max(0, $offset);
        if (self::hasSocialColumns()) {
            return Database::fetchAll(
                "SELECT v.*, p.name AS property_name, p.slug AS property_slug
                 FROM review_videos v
                 LEFT JOIN properties p ON p.id = v.related_property_id
                 WHERE v.is_active = 1
                 ORDER BY v.orientation = 'portrait' DESC, v.sort_order ASC, v.id DESC
                 LIMIT {$limit} OFFSET {$offset}"
            );
        }

        return self::activeOrdered($limit, $offset);
    }

    /** @return list<array<string,mixed>> */
    public static function adminAll(): array
    {
        return Database::fetchAll(
            "SELECT v.*, p.name AS property_name
             FROM review_videos v
             LEFT JOIN properties p ON p.id = v.related_property_id
             ORDER BY v.sort_order ASC, v.id DESC"
        );
    }
}
