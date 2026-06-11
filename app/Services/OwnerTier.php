<?php

namespace App\Services;

/**
 * จุดกลางสำหรับเช็คสิทธิ์ฟีเจอร์ตามระดับสมาชิกเจ้าของแพ
 *
 * Tiers:
 *   none      — Free (ลงทะเบียนใหม่, ยังไม่ซื้อแพ็กเกจ)
 *   standard  — Starter / Standard
 *   vip       — Pro / VIP
 *
 * การใช้งาน:
 *   OwnerTier::can($ownerId, OwnerTier::FEATURE_BROADCAST)  → bool
 *   OwnerTier::tier($ownerId)                               → 'none'|'standard'|'vip'
 */
final class OwnerTier
{
    // ---- ฟีเจอร์ทั้งหมด ----
    public const FEATURE_GUEST_SEEK_LEADS  = 'guest_seek_leads';   // รับ lead จากฟอร์มหาที่พัก
    public const FEATURE_BROADCAST         = 'broadcast';           // ส่ง LINE broadcast
    public const FEATURE_AUTOMATION        = 'automation';          // ตั้งค่า automation template
    public const FEATURE_ANALYTICS_DEEP    = 'analytics_deep';      // ดู referrer + AI summary
    public const FEATURE_LINE_CRM          = 'line_crm';            // จัดการ LINE contacts
    public const FEATURE_AI_DRAFT          = 'ai_draft';            // AI ช่วยเขียนข้อความ
    public const FEATURE_LISTING_BOOST     = 'listing_boost';       // Boost ลำดับในหน้าค้นหา
    public const FEATURE_AVAILABLE_BOOST   = 'available_boost';     // ขึ้นหน้าแพว่าง
    public const FEATURE_COUPON            = 'coupon';              // ออกคูปองส่วนลด

    /**
     * ตรวจว่า owner มีสิทธิ์ใช้ฟีเจอร์นี้หรือไม่
     *
     * @param self::FEATURE_* $feature
     */
    public static function can(int $ownerId, string $feature): bool
    {
        $tier = self::tier($ownerId);
        return self::tierCan($tier, $feature);
    }

    /**
     * เช็คสิทธิ์จาก tier string โดยตรง (ใช้ได้เมื่อมี $tier แล้ว เพื่อหลีกเลี่ยง query ซ้ำ)
     *
     * @param 'none'|'standard'|'vip' $tier
     * @param self::FEATURE_*         $feature
     */
    public static function tierCan(string $tier, string $feature): bool
    {
        return match ($feature) {
            // Free tier: ฟีเจอร์ขั้นพื้นฐาน
            self::FEATURE_AUTOMATION     => $tier !== 'none',
            self::FEATURE_LINE_CRM       => $tier !== 'none',
            self::FEATURE_AI_DRAFT       => $tier !== 'none',

            // Standard+: ฟีเจอร์ระดับกลาง
            self::FEATURE_BROADCAST      => in_array($tier, ['standard', 'vip'], true),
            self::FEATURE_ANALYTICS_DEEP => in_array($tier, ['standard', 'vip'], true),
            self::FEATURE_COUPON         => in_array($tier, ['standard', 'vip'], true),

            // VIP only: ฟีเจอร์ระดับสูงสุด
            self::FEATURE_GUEST_SEEK_LEADS => $tier === 'vip',
            self::FEATURE_LISTING_BOOST    => $tier === 'vip',
            self::FEATURE_AVAILABLE_BOOST  => $tier === 'vip',

            default => false,
        };
    }

    /**
     * ดึง effective tier ของ owner (รวม grace period)
     * คืนค่า 'none' | 'standard' | 'vip'
     */
    public static function tier(int $ownerId): string
    {
        $row = OwnerMembership::ownerRow($ownerId);
        if (!$row) return 'none';
        if (!OwnerMembership::hasActiveBenefits($row)) return 'none';
        $t = (string)($row['membership_tier'] ?? 'none');
        return in_array($t, ['standard', 'vip'], true) ? $t : 'none';
    }

    /**
     * คืน array ของ features ที่ tier นี้มีสิทธิ์ (ใช้ในหน้า UI แสดงแพ็กเกจ)
     *
     * @return list<string>
     */
    public static function featuresForTier(string $tier): array
    {
        $all = [
            self::FEATURE_AUTOMATION,
            self::FEATURE_LINE_CRM,
            self::FEATURE_AI_DRAFT,
            self::FEATURE_BROADCAST,
            self::FEATURE_ANALYTICS_DEEP,
            self::FEATURE_COUPON,
            self::FEATURE_GUEST_SEEK_LEADS,
            self::FEATURE_LISTING_BOOST,
            self::FEATURE_AVAILABLE_BOOST,
        ];
        return array_values(array_filter($all, fn($f) => self::tierCan($tier, $f)));
    }
}
