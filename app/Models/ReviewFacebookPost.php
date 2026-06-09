<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class ReviewFacebookPost extends Model
{
    protected static string $table = 'review_facebook_posts';

    /** Hosts that may host embeddable public posts (permalink). */
    public static function isLikelyFacebookPostUrl(string $url): bool
    {
        return self::normalizePostUrl($url) !== null;
    }

    public static function normalizePostUrl(string $input): ?string
    {
        $input = trim($input);
        if ($input === '') {
            return null;
        }
        if (!preg_match('#^https?://#i', $input)) {
            $input = 'https://' . $input;
        }
        $parts = parse_url($input);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }
        $host   = strtolower($parts['host']);
        $exact  = ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'web.facebook.com', 'mbasic.facebook.com', 'fb.watch', 'www.fb.watch'];
        $okHost = in_array($host, $exact, true) || str_ends_with($host, '.facebook.com');
        if (!$okHost) {
            return null;
        }
        $scheme = strtolower((string)$parts['scheme']);
        $path   = $parts['path'] ?? '';
        $query  = isset($parts['query']) ? '?' . $parts['query'] : '';

        return $scheme . '://' . $parts['host'] . $path . $query;
    }

    /** @return list<array<string,mixed>> */
    public static function activeOrdered(int $limit = 12, int $offset = 0): array
    {
        $limit  = max(1, min(50, $limit));
        $offset = max(0, $offset);
        return Database::fetchAll(
            "SELECT * FROM review_facebook_posts
             WHERE is_active = 1
             ORDER BY sort_order ASC, id DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
    }

    /** @return list<array<string,mixed>> */
    public static function adminAll(): array
    {
        return Database::fetchAll(
            'SELECT * FROM review_facebook_posts ORDER BY sort_order ASC, id DESC'
        );
    }
}
