<?php

namespace App\Services;

use App\Core\Application;
use App\Models\Setting;

/**
 * อีเมลเมื่อมีเจ้าของกิจการสมัครใหม่
 */
final class PartnerRegistrationMailService
{
    private static function siteName(): string
    {
        return (string) Setting::get('site_name', 'แพกาญ.com');
    }

    private static function absUrl(string $path): string
    {
        return rtrim((string) Application::$publicUrl, '/') . '/' . ltrim($path, '/');
    }

    private static function logoUrl(): string
    {
        return self::absUrl('assets/site-logo.png');
    }

    private static function wrapHtml(string $inner, string $preheader = ''): string
    {
        $site = e(self::siteName());
        $logo = e(self::logoUrl());
        $pre = $preheader !== ''
            ? '<div style="display:none;max-height:0;overflow:hidden">' . e($preheader) . '</div>'
            : '';

        return '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . $site . '</title></head>'
            . '<body style="margin:0;padding:0;background:#ecfdf5;font-family:Sarabun,Segoe UI,Helvetica,Arial,sans-serif;color:#0f172a">'
            . $pre
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:linear-gradient(165deg,#ecfdf5 0%,#f8fafc 55%,#fff 100%);padding:24px 12px">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:20px;overflow:hidden;border:1px solid #d1fae5;box-shadow:0 12px 40px rgba(15,118,110,0.12)">'
            . '<tr><td style="background:linear-gradient(135deg,#0d9488 0%,#14b8a6 100%);padding:28px 24px;text-align:center">'
            . '<img src="' . $logo . '" alt="' . $site . '" width="64" height="64" style="display:block;margin:0 auto 12px;border-radius:14px;background:#fff;padding:6px">'
            . '<div style="color:#fff;font-size:20px;font-weight:700;line-height:1.3">' . $site . '</div>'
            . '<span style="color:rgba(255,255,255,0.9);font-size:13px;margin-top:6px;display:block">พาร์ทเนอร์ที่พัก · กิจกรรม · ร้านอาหาร</span>'
            . '</td></tr>'
            . '<tr><td style="padding:28px 24px 8px">' . $inner . '</td></tr>'
            . '<tr><td style="padding:0 24px 24px;border-top:1px solid #f1f5f9">'
            . '<p style="margin:0;font-size:11px;color:#94a3b8;line-height:1.5;text-align:center">'
            . 'อีเมลอัตโนมัติจาก ' . $site . ' — กรุณาอย่าตอบกลับที่อยู่นี้</p>'
            . '</td></tr></table></td></tr></table></body></html>';
    }

    public static function sendOwnerWelcome(
        string $ownerEmail,
        string $contactName,
        string $businessName,
        bool $wantsSalesHelp = false
    ): void {
        $email = trim($ownerEmail);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        if (!Setting::get('email_enabled', '0')) {
            return;
        }

        $site = self::siteName();
        $subject = '[' . $site . '] รับคำขอสมัครพาร์ทเนอร์แล้ว — รอทีมงานอนุมัติ';
        $loginUrl = self::absUrl('/owner');

        $salesNote = $wantsSalesHelp
            ? '<div style="margin:16px 0;padding:14px 16px;background:#fffbeb;border:1px solid #fde68a;border-radius:12px">'
                . '<p style="margin:0;font-size:13px;color:#92400e;line-height:1.6">'
                . '<strong>คุณเลือกรับบริการช่วยขาย / โปรคูปอง</strong> — ทีมงานจะโทรหาคุณเพื่อแนะนำการเข้าร่วมโปรแกรมคูปองส่วนลด'
                . '</p></div>'
            : '';

        $inner = '<p style="margin:0 0 8px;font-size:18px;font-weight:700;color:#0f172a">สวัสดีครับ/ค่ะ ' . e($contactName) . '</p>'
            . '<p style="margin:0 0 16px;color:#475569;font-size:14px;line-height:1.7">'
            . 'ขอบคุณที่สมัครเป็นเจ้าของกิจการกับ <strong style="color:#0d9488">' . e($site) . '</strong>'
            . ' ในนาม <strong>' . e($businessName) . '</strong></p>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;background:#f0fdfa;border-radius:12px;border:1px solid #ccfbf1">'
            . '<tr><td style="padding:16px 18px">'
            . '<p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#0f766e">สถานะบัญชี: รออนุมัติ</p>'
            . '<p style="margin:0;font-size:13px;color:#475569;line-height:1.65">'
            . 'ทีมงานจะตรวจสอบและเปิดใช้งานบัญชีให้เร็วที่สุด เมื่ออนุมัติแล้วคุณจะเพิ่มที่พักและรับลูกค้าจากแพลตฟอร์มได้เต็มรูปแบบ '
            . 'ระหว่างนี้สามารถเข้าสู่ระบบเพื่อเตรียมข้อมูลได้</p>'
            . '</td></tr></table>'
            . $salesNote
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:8px 0 4px">'
            . '<a href="' . e($loginUrl) . '" style="display:inline-block;background:#0d9488;color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:14px 32px;border-radius:12px">เข้าสู่ระบบเจ้าของกิจการ</a>'
            . '</td></tr></table>';

        NotificationService::sendHtmlMail(
            $email,
            $subject,
            self::wrapHtml($inner, 'รับคำขอสมัครแล้ว — รอทีมงานอนุมัติบัญชี')
        );
    }

    public static function sendAdminAlert(
        int $ownerId,
        string $businessName,
        string $contactName,
        string $contactEmail,
        string $contactPhone,
        string $lineId = '',
        bool $wantsSalesHelp = false
    ): void {
        if (!Setting::get('email_enabled', '0')) {
            return;
        }

        $site = self::siteName();
        $subject = '[' . $site . '] เจ้าของกิจการใหม่รออนุมัติ — ' . $businessName;
        $reviewUrl = self::absUrl('/admin/owners/' . $ownerId);
        $salesLabel = $wantsSalesHelp
            ? '<span style="color:#b45309;font-weight:700">ใช่ — โทรแนะนำคูปอง</span>'
            : 'ไม่ระบุ';

        $inner = '<h1 style="font-size:18px;margin:0 0 8px;color:#0f172a">มีเจ้าของกิจการสมัครใหม่</h1>'
            . '<p style="color:#475569;font-size:14px;margin:0 0 16px;line-height:1.6">กรุณาตรวจสอบและเปลี่ยนสถานะพาร์ทเนอร์เป็น <strong>active</strong> เมื่อพร้อม</p>'
            . '<table style="width:100%;font-size:13px;margin:0 0 20px;border-collapse:collapse;background:#f8fafc;border-radius:12px">'
            . '<tr><td style="padding:10px 14px;color:#64748b;width:38%;border-bottom:1px solid #e2e8f0">ชื่อกิจการ</td>'
            . '<td style="padding:10px 14px;font-weight:600;border-bottom:1px solid #e2e8f0">' . e($businessName) . '</td></tr>'
            . '<tr><td style="padding:10px 14px;color:#64748b;border-bottom:1px solid #e2e8f0">ผู้ติดต่อ</td>'
            . '<td style="padding:10px 14px;border-bottom:1px solid #e2e8f0">' . e($contactName) . '</td></tr>'
            . '<tr><td style="padding:10px 14px;color:#64748b;border-bottom:1px solid #e2e8f0">อีเมล</td>'
            . '<td style="padding:10px 14px;border-bottom:1px solid #e2e8f0">' . e($contactEmail) . '</td></tr>'
            . '<tr><td style="padding:10px 14px;color:#64748b;border-bottom:1px solid #e2e8f0">โทร</td>'
            . '<td style="padding:10px 14px;border-bottom:1px solid #e2e8f0">' . e($contactPhone) . '</td></tr>'
            . '<tr><td style="padding:10px 14px;color:#64748b;border-bottom:1px solid #e2e8f0">LINE ID</td>'
            . '<td style="padding:10px 14px;border-bottom:1px solid #e2e8f0">' . e($lineId !== '' ? $lineId : '—') . '</td></tr>'
            . '<tr><td style="padding:10px 14px;color:#64748b;border-bottom:1px solid #e2e8f0">ช่วยขาย/คูปอง</td>'
            . '<td style="padding:10px 14px;border-bottom:1px solid #e2e8f0">' . $salesLabel . '</td></tr>'
            . '<tr><td style="padding:10px 14px;color:#64748b">รหัส owner</td>'
            . '<td style="padding:10px 14px">#' . $ownerId . '</td></tr>'
            . '</table>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">'
            . '<a href="' . e($reviewUrl) . '" style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;font-weight:700;padding:12px 28px;border-radius:10px">เปิดหน้าตรวจสอบ</a>'
            . '</td></tr></table>';

        $adminMail = trim((string) Setting::get('admin_orders_email', ''));
        if ($adminMail !== '' && filter_var($adminMail, FILTER_VALIDATE_EMAIL)) {
            NotificationService::sendHtmlMail($adminMail, $subject, self::wrapHtml($inner));
        }
    }
}
