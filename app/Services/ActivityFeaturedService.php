<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\ActivityFeaturedCampaign;
use App\Models\ActivityProduct;

class ActivityFeaturedService
{
    /** Apply featured flag + priority from active campaign to product. */
    public static function syncProduct(int $productId): void
    {
        if (!ActivityProduct::tableReady() || $productId <= 0) {
            return;
        }

        $campaign = ActivityFeaturedCampaign::activeForProduct($productId);
        if ($campaign) {
            Database::update('activity_products', [
                'is_featured' => 1,
                'priority'    => (int)($campaign['priority_boost'] ?? 50),
            ], 'id = :id', ['id' => $productId]);

            return;
        }

        if (!ActivityFeaturedCampaign::tableReady()) {
            return;
        }

        $hasOther = Database::fetch(
            "SELECT id FROM activity_featured_campaigns
             WHERE product_id = :pid AND is_active = 1
               AND (starts_at IS NULL OR starts_at <= CURDATE())
               AND (ends_at IS NULL OR ends_at >= CURDATE())
             LIMIT 1",
            ['pid' => $productId]
        );
        if ($hasOther) {
            return;
        }

        Database::update('activity_products', [
            'is_featured' => 0,
        ], 'id = :id AND is_featured = 1', ['id' => $productId]);
    }

    /** @return array{affected:int,output:string} */
    public static function expireCampaigns(): array
    {
        if (!ActivityFeaturedCampaign::tableReady()) {
            return ['affected' => 0, 'output' => 'activity_featured_campaigns table missing'];
        }

        $rows = ActivityFeaturedCampaign::expiredActive();
        $n = 0;
        $productIds = [];
        foreach ($rows as $row) {
            Database::update('activity_featured_campaigns', ['is_active' => 0], 'id = :id', ['id' => (int)$row['id']]);
            $productIds[(int)$row['product_id']] = true;
            $n++;
        }
        foreach (array_keys($productIds) as $pid) {
            self::syncProduct($pid);
        }

        return ['affected' => $n, 'output' => "Expired {$n} featured campaigns"];
    }
}
