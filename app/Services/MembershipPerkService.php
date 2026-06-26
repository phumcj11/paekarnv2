<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\AuditLog;

class MembershipPerkService
{
    public static function tableAvailable(): bool
    {
        static $ok = null;
        if ($ok !== null) {
            return $ok;
        }
        try {
            $ok = Database::tableHasColumn('owner_membership_perk_grants', 'id');
        } catch (\Throwable) {
            $ok = false;
        }

        return $ok;
    }

    /** สร้าง row pending สำหรับสิทธิ์บริการที่ tier ปัจจุบันได้รับ */
    public static function syncPendingGrantsForOwner(int $ownerId): void
    {
        if (!self::tableAvailable() || $ownerId <= 0) {
            return;
        }

        $owner = OwnerMembership::ownerRow($ownerId);
        if (!$owner || !OwnerMembership::hasActiveServiceBenefits($owner)) {
            return;
        }

        $tier = OwnerMembership::serviceTier($owner);
        foreach (OwnerTier::servicePerksForTier($tier) as $perk) {
            $existing = Database::fetch(
                'SELECT id FROM owner_membership_perk_grants WHERE owner_id = :o AND perk_key = :k LIMIT 1',
                ['o' => $ownerId, 'k' => $perk['key']]
            );
            if (!$existing) {
                Database::insert('owner_membership_perk_grants', [
                    'owner_id' => $ownerId,
                    'perk_key' => $perk['key'],
                    'status'   => 'pending',
                ]);
            }
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function grantsForOwner(int $ownerId): array
    {
        if (!self::tableAvailable() || $ownerId <= 0) {
            return [];
        }

        $labels = [];
        foreach (OwnerTier::servicePerksConfig()['perks'] as $p) {
            $labels[$p['key']] = $p['label'];
        }

        $rows = Database::fetchAll(
            'SELECT * FROM owner_membership_perk_grants WHERE owner_id = :o ORDER BY id ASC',
            ['o' => $ownerId]
        );

        $out = [];
        foreach ($rows as $r) {
            $key = (string) $r['perk_key'];
            if (!isset($labels[$key])) {
                continue;
            }
            $r['label'] = $labels[$key];
            $out[] = $r;
        }

        return $out;
    }

    /**
     * รายการสิทธิ์ที่ owner ควรเห็น (รวม pending ที่ยังไม่มี row)
     *
     * @return list<array{key: string, label: string, status: string, note: ?string, granted_at: ?string}>
     */
    public static function displayGrantsForOwner(int $ownerId): array
    {
        $owner = OwnerMembership::ownerRow($ownerId);
        if (!$owner || !OwnerMembership::hasActiveServiceBenefits($owner)) {
            return [];
        }

        self::syncPendingGrantsForOwner($ownerId);
        $byKey = [];
        foreach (self::grantsForOwner($ownerId) as $g) {
            $byKey[(string) $g['perk_key']] = $g;
        }

        $tier = OwnerMembership::serviceTier($owner);
        $out = [];
        foreach (OwnerTier::servicePerksForTier($tier) as $perk) {
            $g = $byKey[$perk['key']] ?? null;
            $out[] = [
                'key'         => $perk['key'],
                'label'       => $perk['label'],
                'status'      => $g ? (string) $g['status'] : 'pending',
                'note'        => $g['note'] ?? null,
                'granted_at'  => $g['granted_at'] ?? null,
            ];
        }

        return $out;
    }

    public static function markGranted(int $ownerId, string $perkKey, int $adminUserId, ?string $note = null): bool
    {
        if (!self::tableAvailable() || $ownerId <= 0 || $perkKey === '') {
            return false;
        }

        self::syncPendingGrantsForOwner($ownerId);

        $row = Database::fetch(
            'SELECT id FROM owner_membership_perk_grants WHERE owner_id = :o AND perk_key = :k LIMIT 1',
            ['o' => $ownerId, 'k' => $perkKey]
        );
        if (!$row) {
            Database::insert('owner_membership_perk_grants', [
                'owner_id'   => $ownerId,
                'perk_key'   => $perkKey,
                'status'     => 'granted',
                'note'       => $note,
                'granted_at' => date('Y-m-d H:i:s'),
                'granted_by' => $adminUserId > 0 ? $adminUserId : null,
            ]);
        } else {
            Database::update(
                'owner_membership_perk_grants',
                [
                    'status'     => 'granted',
                    'note'       => $note,
                    'granted_at' => date('Y-m-d H:i:s'),
                    'granted_by' => $adminUserId > 0 ? $adminUserId : null,
                ],
                'id = :id',
                ['id' => (int) $row['id']]
            );
        }

        AuditLog::record(
            'membership_perk_granted',
            ['owner_id' => $ownerId, 'perk_key' => $perkKey],
            'owner',
            $ownerId
        );

        return true;
    }
}
