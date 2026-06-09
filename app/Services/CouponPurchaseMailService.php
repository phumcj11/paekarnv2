<?php

namespace App\Services;

use App\Models\Setting;

class CouponPurchaseMailService
{
    /**
     * @param array<int,array{id:int,code:string,expires_at:string}> $couponRows
     */
    public static function sendBuyerConfirmation(
        ?string $buyerEmail,
        string $buyerName,
        string $orderNo,
        int $quantity,
        int $totalBaht,
        int $faceValueBaht,
        array $couponRows
    ): void {
        $email = trim((string) $buyerEmail);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        if (!Setting::get('email_enabled', '0')) {
            return;
        }

        $site = (string) Setting::get('site_name', 'แพกาญ.com');
        $subject = '[' . $site . '] ยืนยันการซื้อคูปอง — ' . $orderNo;

        $rowsHtml = '';
        foreach ($couponRows as $row) {
            $code = e((string) $row['code']);
            $exp = e(format_date_th((string) $row['expires_at']));
            $payload = CouponRedeemTokenService::qrPayloadForCoupon((int) $row['id'], (string) $row['expires_at']);
            $b64 = CouponQrImageService::pngBase64($payload);
            $img = $b64
                ? '<img alt="QR" src="data:image/png;base64,' . $b64 . '" width="160" height="160" style="display:block;border:1px solid #e2e8f0;border-radius:8px"/>'
                : '<span style="color:#64748b;font-size:12px">(ไม่สร้าง QR บนเซิร์ฟเวอร์นี้ — ใช้รหัสด้านล่าง)</span>';

            $rowsHtml .= '<tr><td style="padding:12px;border-bottom:1px solid #e2e8f0;vertical-align:top">'
                . '<div style="font-family:ui-monospace,monospace;font-weight:700;font-size:15px;color:#0f766e">' . $code . '</div>'
                . '<div style="font-size:12px;color:#64748b;margin-top:4px">ใช้ได้ถึง ' . $exp . '</div></td>'
                . '<td style="padding:12px;border-bottom:1px solid #e2e8f0;text-align:center">' . $img . '</td></tr>';
        }

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Sarabun,Segoe UI,sans-serif;background:#f8fafc;color:#0f172a;padding:16px">'
            . '<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:16px;padding:24px;border:1px solid #e2e8f0">'
            . '<h1 style="font-size:18px;margin:0 0 8px">สวัสดีครับ ' . e($buyerName) . '</h1>'
            . '<p style="margin:0 0 16px;color:#475569;font-size:14px">ขอบคุณที่สั่งซื้อคูปองกับ ' . e($site) . '</p>'
            . '<table style="width:100%;font-size:13px;margin-bottom:16px"><tr><td style="color:#64748b">เลขที่คำสั่งซื้อ</td><td style="font-weight:600">' . e($orderNo) . '</td></tr>'
            . '<tr><td style="color:#64748b">จำนวน</td><td>' . (int) $quantity . ' ใบ</td></tr>'
            . '<tr><td style="color:#64748b">ยอดชำระ</td><td>฿' . number_format($totalBaht) . '</td></tr>'
            . '<tr><td style="color:#64748b">มูลค่าต่อใบ</td><td>฿' . number_format($faceValueBaht) . '</td></tr></table>'
            . '<p style="font-size:13px;color:#475569">นำ QR หรือรหัสด้านล่างให้เจ้าของที่พักสแกน/ตรวจในระบบเพื่อใช้ส่วนลด</p>'
            . '<table style="width:100%;border-collapse:collapse;margin-top:8px">' . $rowsHtml . '</table>'
            . '<p style="font-size:11px;color:#94a3b8;margin-top:24px">อีเมลอัตโนมัติ — กรุณาอย่าตอบกลับ</p>'
            . '</div></body></html>';

        NotificationService::sendHtmlMail($email, $subject, $html);
    }

    /**
     * @param array<int,array{id:int,code:string}> $couponRows
     */
    public static function buildAdminCouponOrderHtml(
        string $buyerName,
        string $buyerPhone,
        ?string $buyerEmail,
        string $orderNo,
        int $quantity,
        int $totalBaht,
        array $couponRows
    ): string {
        $codes = array_map(static fn ($r) => e((string) $r['code']), $couponRows);
        $list = implode(', ', $codes);

        return '<p><strong>คำสั่งซื้อคูปองใหม่</strong></p>'
            . '<ul>'
            . '<li>เลขที่: ' . e($orderNo) . '</li>'
            . '<li>ผู้ซื้อ: ' . e($buyerName) . ' / ' . e($buyerPhone)
            . ($buyerEmail ? ' / ' . e($buyerEmail) : '') . '</li>'
            . '<li>จำนวน: ' . (int) $quantity . ' ใบ · รวม ฿' . number_format($totalBaht) . '</li>'
            . '<li>รหัส: ' . $list . '</li>'
            . '</ul>';
    }
}
