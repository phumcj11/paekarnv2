<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class ActivityFeaturedCampaign extends Model
{
    protected static string $table = 'activity_featured_campaigns';

    public static function tableReady(): bool
    {
        return Database::tableHasColumn('activity_featured_campaigns', 'id');
    }

    /** @return list<array<string,mixed>> */
    public static function adminAll(int $limit = 200): array
    {
        if (!self::tableReady()) {
            return [];
        }
        $limit = max(1, min(500, $limit));

        return Database::fetchAll(
            "SELECT c.*, ap.title AS product_title, ap.slug AS product_slug, pr.name AS provider_name
             FROM activity_featured_campaigns c
             INNER JOIN activity_products ap ON ap.id = c.product_id
             LEFT JOIN activity_providers pr ON pr.id = c.provider_id
             ORDER BY c.is_active DESC, c.ends_at DESC, c.id DESC
             LIMIT {$limit}"
        );
    }

    public static function find(int $id): ?array
    {
        if (!self::tableReady()) {
            return null;
        }

        return Database::fetch(
            'SELECT * FROM activity_featured_campaigns WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    /** Active campaign for product today (if any). */
    public static function activeForProduct(int $productId): ?array
    {
        if (!self::tableReady() || $productId <= 0) {
            return null;
        }

        return Database::fetch(
            "SELECT * FROM activity_featured_campaigns
             WHERE product_id = :pid AND is_active = 1
               AND (starts_at IS NULL OR starts_at <= CURDATE())
               AND (ends_at IS NULL OR ends_at >= CURDATE())
             ORDER BY priority_boost DESC, id DESC
             LIMIT 1",
            ['pid' => $productId]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function expiredActive(): array
    {
        if (!self::tableReady()) {
            return [];
        }

        return Database::fetchAll(
            "SELECT * FROM activity_featured_campaigns
             WHERE is_active = 1 AND ends_at IS NOT NULL AND ends_at < CURDATE()
             LIMIT 500"
        );
    }
}
