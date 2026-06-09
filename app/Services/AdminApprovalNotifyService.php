<?php

namespace App\Services;

use App\Core\Application;
use App\Models\Setting;

/**
 * แจ้งเตือนแอดมินเมื่อมีของในรายการรออนุมัติ — ในระบบ (กระดิ่ง) + อีเมลบังคับ + กลุ่ม LINE OA (ถ้าตั้ง line_admin_group_id)
 */
final class AdminApprovalNotifyService
{
    private static function absoluteLink(string $path): string
    {
        $path = '/' . ltrim($path, '/');

        return rtrim((string) Application::$publicUrl, '/') . $path;
    }

    /** @param array<string,mixed> $extraMeta */
    private static function ping(string $type, string $title, string $message, string $linkPath, array $extraMeta = []): void
    {
        $linkPath = '/' . ltrim($linkPath, '/');

        try {
            NotificationService::sendToRole(
                'admin',
                $type,
                $title,
                $message,
                $linkPath,
                array_merge($extraMeta, ['_force_email' => true])
            );
        } catch (\Throwable $e) {
            // ไม่บล็อก flow ผู้ใช้
        }

        $gid = trim((string) Setting::get('line_admin_group_id', ''));
        if ($gid === '' || !Setting::get('line_enabled', '0')) {
            return;
        }
        try {
            $abs = self::absoluteLink($linkPath);
            $txt = "🔔 {$title}\n\n{$message}\n\n👉 {$abs}";
            LineService::push($gid, $txt);
        } catch (\Throwable $e) {
        }
    }

    /**
     * เจ้าของใหม่สมัครจากหน้า /owner/register
     *
     * @param int $userId users.id ของผู้สมัคร (สำหรับอีเมลยืนยัน)
     */
    public static function partnerRegistered(
        int $ownerId,
        string $businessName,
        string $contactName,
        string $contactEmail = '',
        string $contactPhone = '',
        int $userId = 0,
        string $lineId = '',
        bool $wantsSalesHelp = false
    ): void {
        self::ping(
            'admin_partner_pending',
            'มีเจ้าของใหม่สมัครเข้ามา — รออนุมัติ',
            sprintf('%s (%s)', $businessName, $contactName),
            '/admin/owners/' . $ownerId,
            ['owner_id' => $ownerId]
        );

        try {
            PartnerRegistrationMailService::sendAdminAlert(
                $ownerId,
                $businessName,
                $contactName,
                $contactEmail,
                $contactPhone,
                $lineId,
                $wantsSalesHelp
            );
        } catch (\Throwable $e) {
        }

        if ($userId > 0) {
            try {
                NotificationService::send(
                    $userId,
                    'partner_registered',
                    'สมัครพาร์ทเนอร์สำเร็จ — รออนุมัติ',
                    'เราได้รับข้อมูลแล้ว ทีมงานจะตรวจสอบและเปิดใช้งานบัญชีให้เร็วที่สุด',
                    '/owner',
                    ['owner_id' => $ownerId]
                );
            } catch (\Throwable $e) {
            }
        }

        try {
            PartnerRegistrationMailService::sendOwnerWelcome(
                $contactEmail,
                $contactName,
                $businessName,
                $wantsSalesHelp
            );
        } catch (\Throwable $e) {
        }
    }

    /** สร้างแถว owners จากหน้าโปรไฟล์ (กรณีไม่ได้มาทางฟอร์มสมัคร) */
    public static function partnerProfileRecorded(int $ownerId, ?string $businessName): void
    {
        $bn = trim((string) $businessName) ?: 'ไม่ได้ระบุชื่อที่พัก';
        self::ping(
            'admin_partner_pending',
            'โปรไฟล์เจ้าของใหม่ต้องอนุมัติพาร์ทเนอร์',
            sprintf('ประกอบการ: %s', $bn),
            '/admin/owners/' . $ownerId,
            ['owner_id' => $ownerId]
        );
    }

    /** ที่พักสถานะรออนุมัติ (เจ้าของสร้างใหม่) */
    public static function propertyPendingReview(int $propertyId, string $propertyName): void
    {
        self::ping(
            'admin_property_pending',
            'มีที่พักใหม่รออนุมัติ',
            sprintf('%s #%d', $propertyName, $propertyId),
            '/admin/properties/' . $propertyId,
            ['property_id' => $propertyId]
        );
    }

    /** ยูนิตเพิ่มใหม่ (รอตรวจ) — เฉพาะฝั่งเจ้าของที่พักไม่ใช่แอดมินสราง */
    public static function unitCreatedPendingReview(int $propertyId, string $propertyName, int $unitId, string $unitName): void
    {
        self::ping(
            'admin_unit_pending',
            'มีห้อง/ยูนิตใหม่รอตรวจ',
            sprintf('%s — %s (ที่พัก #%d ยูนิต #%d)', $propertyName, $unitName, $propertyId, $unitId),
            '/admin/properties/' . $propertyId,
            ['property_id' => $propertyId, 'unit_id' => $unitId]
        );
    }

    /** เจ้าของที่พักแก้ยูนิตที่เคยประกาศแล้ว — กลับเข้ารอโมเดอเรชันอีกครั้ง */
    public static function unitEditedNeedsReapproval(int $propertyId, string $propertyName, int $unitId, string $unitName): void
    {
        self::ping(
            'admin_unit_review',
            'แก้ยูนิตแล้ว — รอตรวจอีกครั้ง',
            sprintf('%s — %s (#%d)', $propertyName, $unitName, $unitId),
            '/admin/properties/' . $propertyId,
            ['property_id' => $propertyId, 'unit_id' => $unitId]
        );
    }

    /** ออเดอร์แพ็กสมาชิกที่ยังชำระ/รอยืนสลิป */
    public static function membershipOrderPending(string $orderNo, string $planCode, float $amount, int $orderId): void
    {
        self::ping(
            'admin_membership_order',
            'ออเดอร์สมัครแพ็กสมาชิก — รอรับเงิน/อนุมัติ',
            sprintf('แพ็ก %s เลขที่ %s ยอด ฿%s', $planCode, $orderNo, number_format($amount, 2)),
            '/admin/membership/orders',
            ['order_id' => $orderId, 'order_no' => $orderNo]
        );
    }
}
