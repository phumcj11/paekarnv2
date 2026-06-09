<?php

namespace App\Services;

/**
 * จุดกลางสำหรับเช็คสิทธิ์ฟีเจอร์ตามระดับสมาชิกเจ้าของแพ (ไม่กระจาย if ทุกที่)
 */
final class OwnerTier
{
    public const FEATURE_GUEST_SEEK_LEADS = 'guest_seek_leads';

    /** @param self::FEATURE_* $feature */
    public static function can(int $ownerId, string $feature): bool
    {
        return match ($feature) {
            self::FEATURE_GUEST_SEEK_LEADS => OwnerMembership::receivesGuestSeekLeads($ownerId),
            default                        => false,
        };
    }
}
