<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Setting;

/**
 * ปรับ priority / is_featured ตามสมาชิกเจ้าของแพ (Phase 2)
 * เก็บ delta ใน membership_priority_boost เพื่อถอนคืนเมื่อหมดสิทธิ์ — ไม่ strip แบบ blind ทั้งตาราง
 */
class MembershipListingBoostService
{
    public static function columnsAvailable(): bool
    {
        return Database::tableHasColumn('properties', 'membership_priority_boost')
            && Database::tableHasColumn('properties', 'membership_featured_applied');
    }

    /** เรียกหลังชำระแพ็ก / cron — ปรับให้ตรงสถานะสมาชิกปัจจุบัน */
    public static function syncOwnerBoost(int $ownerId): void
    {
        if (!self::columnsAvailable() || $ownerId <= 0) {
            return;
        }

        $owner = Database::fetch(
            'SELECT id, partner_status, membership_tier, membership_expires_at, membership_grace_until
             FROM owners WHERE id = :id',
            ['id' => $ownerId]
        );
        $eligible = $owner
            && ($owner['partner_status'] ?? '') === 'active'
            && OwnerMembership::hasActiveFeatureBenefits($owner);
        $tier = $eligible ? OwnerMembership::featureTier($owner) : 'none';

        $priStd = max(0, (int) Setting::get('membership_boost_priority_standard', 20));
        $priVip = max(0, (int) Setting::get('membership_boost_priority_vip', 60));
        $vipFeatured = (int) Setting::get('membership_vip_auto_featured', 1) === 1;

        $targetDelta = 0;
        if ($tier === 'vip') {
            $targetDelta = $priVip;
        } elseif ($tier === 'standard') {
            $targetDelta = $priStd;
        }
        $wantFeatured = $tier === 'vip' && $vipFeatured;

        $props = Database::fetchAll(
            'SELECT id, priority, membership_priority_boost, membership_featured_applied, is_featured
             FROM properties WHERE owner_id = :o AND status = \'published\'',
            ['o' => $ownerId]
        );

        foreach ($props as $p) {
            $pid = (int) $p['id'];
            $boost = (int) $p['membership_priority_boost'];
            $pri = (int) $p['priority'];
            $featApplied = (int) $p['membership_featured_applied'];
            $isFeat = (int) $p['is_featured'];

            $newPri = max(0, $pri - $boost + $targetDelta);

            $upd = [
                'priority'                    => $newPri,
                'membership_priority_boost'   => $targetDelta,
                'membership_featured_applied' => 0,
            ];

            if ($wantFeatured) {
                $upd['is_featured'] = 1;
                $upd['membership_featured_applied'] = 1;
            } else {
                if ($featApplied === 1) {
                    $upd['is_featured'] = 0;
                } else {
                    $upd['is_featured'] = $isFeat;
                }
            }

            Database::update('properties', $upd, 'id = :id', ['id' => $pid]);
        }
    }

    /** ถอน boost ทั้งหมดของ owner (ก่อน downgrade membership) */
    public static function stripBoostForOwner(int $ownerId): void
    {
        if (!self::columnsAvailable() || $ownerId <= 0) {
            return;
        }

        $props = Database::fetchAll(
            'SELECT id, priority, membership_priority_boost, membership_featured_applied FROM properties
             WHERE owner_id = :o AND (membership_priority_boost <> 0 OR membership_featured_applied <> 0)',
            ['o' => $ownerId]
        );

        foreach ($props as $p) {
            $boost = (int) $p['membership_priority_boost'];
            $newPri = max(0, (int) $p['priority'] - $boost);
            $upd = [
                'priority'                    => $newPri,
                'membership_priority_boost'   => 0,
                'membership_featured_applied' => 0,
            ];
            if ((int) $p['membership_featured_applied'] === 1) {
                $upd['is_featured'] = 0;
            }
            Database::update('properties', $upd, 'id = :id', ['id' => (int) $p['id']]);
        }
    }
}
