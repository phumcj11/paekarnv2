<?php
namespace App\Services;

use App\Core\Application;
use App\Models\Setting;

final class PasswordResetMailService
{
    public static function sendOwnerReset(string $email, string $name, string $plainToken): bool
    {
        $email = trim($email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if (!Setting::get('email_enabled', '0')) {
            return false;
        }

        $site    = (string) Setting::get('site_name', 'แพกาญ.com');
        $from    = (string) Setting::get('email_from', 'no-reply@paekan.com');
        $resetUrl = rtrim((string) Application::$publicUrl, '/')
            . '/owner/reset-password?token=' . urlencode($plainToken);

        $subject = '[' . $site . '] รีเซ็ตรหัสผ่าน Owner Portal';
        $body = '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"></head><body style="font-family:Sarabun,sans-serif;color:#0f172a;padding:24px">'
            . '<p>สวัสดีครับ/ค่ะ ' . e($name !== '' ? $name : 'เจ้าของกิจการ') . '</p>'
            . '<p>เราได้รับคำขอรีเซ็ตรหัสผ่านสำหรับ Owner Portal ของ ' . e($site) . '</p>'
            . '<p style="margin:24px 0"><a href="' . e($resetUrl) . '" style="display:inline-block;background:#0d9488;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold">ตั้งรหัสผ่านใหม่</a></p>'
            . '<p style="font-size:13px;color:#64748b">ลิงก์นี้ใช้ได้ภายใน 1 ชั่วโมง หากคุณไม่ได้ขอรีเซ็ต สามารถละเว้นอีเมลนี้ได้</p>'
            . '<p style="font-size:12px;color:#94a3b8;word-break:break-all">' . e($resetUrl) . '</p>'
            . '</body></html>';

        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=utf-8\r\nFrom: " . $from;
        return @mail($email, $subject, $body, $headers);
    }
}
