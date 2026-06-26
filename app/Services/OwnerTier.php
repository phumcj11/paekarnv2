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
    public const FEATURE_AVAILABILITY      = 'availability';        // ปฏิทินวันว่าง
    public const FEATURE_CONTENT_PLAN      = 'content_plan';        // Content Plan / การตลาด
    public const FEATURE_ANALYTICS         = 'analytics';           // Analytics พื้นฐาน
    public const FEATURE_LINE_HUB          = 'line_hub';            // LINE Hub / ตั้งค่า OA

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
            // ฟรี: จัดการที่พัก / ยูนิต + แสดงบนเว็บ (+ การจอง) เท่านั้น
            // ต้องสมัครแพ็ก (standard หรือ vip):
            self::FEATURE_AVAILABILITY   => $tier !== 'none',
            self::FEATURE_CONTENT_PLAN   => $tier !== 'none',
            self::FEATURE_ANALYTICS      => $tier !== 'none',
            self::FEATURE_LINE_HUB       => $tier !== 'none',
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
        $all = array_keys(self::featureLabels());
        return array_values(array_filter($all, fn($f) => self::tierCan($tier, $f)));
    }

    /** @return array<string, string> feature constant => ชื่อภาษาไทย */
    public static function featureLabels(): array
    {
        return [
            self::FEATURE_AVAILABILITY     => 'ปฏิทินวันว่าง + จองจากปฏิทิน',
            self::FEATURE_LINE_HUB           => 'LINE Hub — ตั้งค่า OA / Chatbot',
            self::FEATURE_CONTENT_PLAN       => 'Content Plan + โพสต์ FB / LINE / IG',
            self::FEATURE_ANALYTICS          => 'Analytics พื้นฐาน + Funnel',
            self::FEATURE_LINE_CRM         => 'LINE CRM (รายชื่อแชท, tag, ส่งทีละคน)',
            self::FEATURE_AUTOMATION       => 'Automation — บันทึก template ข้อความอัตโนมัติ',
            self::FEATURE_AI_DRAFT         => 'AI ช่วยร่างข้อความ (LINE / Automation / Content)',
            self::FEATURE_BROADCAST        => 'LINE Broadcast',
            self::FEATURE_ANALYTICS_DEEP   => 'Analytics ลึก (Referrer + AI สรุป)',
            self::FEATURE_COUPON           => 'ตรวจ / สแกนคูปอง',
            self::FEATURE_GUEST_SEEK_LEADS => 'รับ Lead ฟอร์ม "ขอให้ช่วยหาที่พัก"',
            self::FEATURE_LISTING_BOOST    => 'Boost ลำดับค้นหา + Featured',
            self::FEATURE_AVAILABLE_BOOST  => 'Boost หน้าแพว่าง (ranking)',
        ];
    }

    /**
     * แถวสำหรับตารางเปรียบเทียบ tier (ใช้ในหน้า membership / admin)
     *
     * @return list<array{label: string, none?: bool|string, standard?: bool|string, vip?: bool|string, feature?: string}>
     */
    public static function comparisonRows(): array
    {
        $rows = [
            ['label' => 'จัดการที่พัก / ยูนิต + แสดงบนเว็บ', 'none' => true, 'standard' => true, 'vip' => true],
            ['label' => 'การจอง (รายการ / สร้างจอง)', 'none' => true, 'standard' => true, 'vip' => true],
        ];

        foreach (self::featureLabels() as $feature => $label) {
            if ($feature === self::FEATURE_AVAILABLE_BOOST) {
                $rows[] = [
                    'label'    => $label,
                    'none'     => false,
                    'standard' => '+20',
                    'vip'      => '+30',
                ];
                continue;
            }
            $rows[] = [
                'label'   => $label,
                'feature' => $feature,
            ];
        }

        return $rows;
    }

    /**
     * ค่า cell ในตารางเปรียบเทียบ
     *
     * @param array{label: string, none?: bool|string, standard?: bool|string, vip?: bool|string, feature?: string} $row
     * @return bool|string
     */
    public static function comparisonCell(array $row, string $tier): bool|string
    {
        if (isset($row['feature'])) {
            return self::tierCan($tier, $row['feature']);
        }
        return $row[$tier] ?? false;
    }
}
