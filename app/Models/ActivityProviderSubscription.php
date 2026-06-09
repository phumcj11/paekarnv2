<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class ActivityProviderSubscription extends Model
{
    protected static string $table = 'activity_provider_subscriptions';

    public const PLANS = [
        'free'    => 'Free (lead mode)',
        'partner' => 'Voucher Partner',
        'premium' => 'Lead Premium',
    ];

    public static function tableReady(): bool
    {
        return Database::tableHasColumn('activity_provider_subscriptions', 'id');
    }

    public static function activeForProvider(int $providerId): ?array
    {
        if (!self::tableReady() || $providerId <= 0) {
            return null;
        }

        return Database::fetch(
            "SELECT * FROM activity_provider_subscriptions
             WHERE provider_id = :pid AND is_active = 1
               AND (starts_at IS NULL OR starts_at <= CURDATE())
               AND (ends_at IS NULL OR ends_at >= CURDATE())
             ORDER BY id DESC LIMIT 1",
            ['pid' => $providerId]
        );
    }

    /** @return list<array<string,mixed>> */
    public static function forProvider(int $providerId): array
    {
        if (!self::tableReady() || $providerId <= 0) {
            return [];
        }

        return Database::fetchAll(
            'SELECT * FROM activity_provider_subscriptions WHERE provider_id = :pid ORDER BY id DESC LIMIT 20',
            ['pid' => $providerId]
        );
    }

    public static function commissionOverrideForProvider(int $providerId): ?float
    {
        $sub = self::activeForProvider($providerId);
        if (!$sub || $sub['commission_override'] === null) {
            return null;
        }
        $v = (float)$sub['commission_override'];

        return $v > 0 ? $v : null;
    }
}
