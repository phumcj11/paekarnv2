<?php

namespace App\Services;

use App\Core\Database;

/**
 * สถานะสมาชิกเจ้าของแพ (standard / VIP) — ผูกกับตาราง owners
 */
class OwnerMembership
{
    /** @return array<string,mixed>|null */
    public static function ownerRow(int $ownerId): ?array
    {
        return Database::fetch(
            'SELECT id, user_id, partner_status, membership_tier, membership_expires_at, membership_grace_until
             FROM owners WHERE id = :id',
            ['id' => $ownerId]
        );
    }

    /** สิทธิ์แพ็กเกจยังใช้ได้ (รวมช่วง grace) */
    public static function hasActiveBenefits(?array $owner): bool
    {
        if (!$owner || ($owner['membership_tier'] ?? 'none') === 'none') {
            return false;
        }
        $tier = $owner['membership_tier'];
        if ($tier !== 'standard' && $tier !== 'vip') {
            return false;
        }
        $exp = $owner['membership_expires_at'] ?? null;
        $grace = $owner['membership_grace_until'] ?? null;
        if ($exp === null) {
            return true;
        }
        if (strtotime((string)$exp) > time()) {
            return true;
        }
        if ($grace !== null && strtotime((string)$grace) > time()) {
            return true;
        }

        return false;
    }

    public static function isVipActive(int $ownerId): bool
    {
        $o = self::ownerRow($ownerId);
        return $o && ($o['membership_tier'] ?? '') === 'vip' && self::hasActiveBenefits($o);
    }

    /** เฉพาะ VIP + partner active + สิทธิ์ไม่หมด → รับ lead จากฟอร์มหาที่พัก */
    public static function receivesGuestSeekLeads(int $ownerId): bool
    {
        $o = self::ownerRow($ownerId);
        if (!$o || ($o['partner_status'] ?? '') !== 'active') {
            return false;
        }
        if (($o['membership_tier'] ?? '') !== 'vip') {
            return false;
        }

        return self::hasActiveBenefits($o);
    }
}
