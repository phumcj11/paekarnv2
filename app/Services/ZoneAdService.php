<?php

namespace App\Services;

use App\Core\Database;

/**
 * Phase 4 lean helper — แบนเนอร์โซนที่ active ตามวันที่ (ผูก Home/UI ภายหลังได้)
 */
class ZoneAdService
{
    /** @return list<array<string,mixed>> */
    public static function activeForZone(string $zone, int $limit = 3): array
    {
        $z = trim($zone);
        if ($z === '') {
            return [];
        }
        try {
            return Database::fetchAll(
                "SELECT * FROM zone_ad_campaigns
             WHERE is_active = 1 AND zone = :z
             AND (starts_at IS NULL OR starts_at <= CURDATE())
             AND (ends_at IS NULL OR ends_at >= CURDATE())
             ORDER BY sort_order ASC, id ASC
             LIMIT " . max(1, min(20, $limit)),
                ['z' => $z]
            );
        } catch (\Throwable) {
            return [];
        }
    }
}
