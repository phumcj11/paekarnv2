<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Setting;

/**
 * Multi-channel notification dispatcher.
 *  - in_app : บันทึกลง notifications table → bell dropdown
 *  - line   : ส่ง push ผ่าน LINE Messaging API (ถ้า user มี line_user_id และเปิด notify_line)
 *  - email  : ส่ง email (mail() / SMTP)
 *
 * วิธีใช้:
 *  NotificationService::send($userId, 'booking_new', 'มีการจองใหม่', 'รายละเอียด...', '/owner/bookings/123');
 *  NotificationService::sendToRole('admin', 'system_alert', 'แจ้งเตือน', '...', '/admin');
 */
class NotificationService
{
    /** ส่งไปยัง user คนเดียว ทุกช่องทางที่เปิด */
    public static function send(int $userId, string $type, string $title, string $message, ?string $link = null, array $meta = []): void
    {
        $user = Database::fetch("SELECT id, name, email, line_user_id, notify_line, notify_email FROM users WHERE id = :i", ['i' => $userId]);
        if (!$user) return;

        $forceEmail = false;
        if (isset($meta['_force_email'])) {
            $forceEmail = (bool)$meta['_force_email'];
            unset($meta['_force_email']);
        }

        // 1) in-app เสมอ
        self::log($userId, $type, $title, $message, $link, 'in_app', 'sent', $meta);

        // 2) LINE
        if (!empty($user['line_user_id']) && (int)$user['notify_line'] === 1) {
            $ok = false; $err = null;
            try {
                $lineMsg = self::formatForLine($title, $message, $link);
                $ok = LineService::push($user['line_user_id'], $lineMsg);
            } catch (\Throwable $e) { $err = $e->getMessage(); }
            self::log($userId, $type, $title, $message, $link, 'line', $ok ? 'sent' : 'failed', $meta, $err);
        }

        // 3) Email
        if (!empty($user['email']) && ((int)$user['notify_email'] === 1 || $forceEmail)) {
            $ok = false; $err = null;
            try { $ok = self::sendEmail($user['email'], $title, $message, $link); }
            catch (\Throwable $e) { $err = $e->getMessage(); }
            self::log($userId, $type, $title, $message, $link, 'email', $ok ? 'sent' : 'skipped', $meta, $err);
        }
    }

    /** ส่งไปยังทุก user ของ role นั้น */
    public static function sendToRole(string $role, string $type, string $title, string $message, ?string $link = null, array $meta = []): void
    {
        $users = Database::fetchAll("SELECT id FROM users WHERE role = :r AND status = 'active'", ['r' => $role]);
        foreach ($users as $u) {
            self::send((int) $u['id'], $type, $title, $message, $link, $meta);
        }
    }

    /** อีเมล HTML โดยตรง (เช่น คำสั่งซื้อคูปองไปลูกค้าหรืออีเมลแอดมินกลาง) — ต้องเปิด email_enabled */
    public static function sendHtmlMail(string $to, string $subject, string $htmlBody): bool
    {
        $to = trim($to);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if (!Setting::get('email_enabled', '0')) {
            return false;
        }
        $from = (string) Setting::get('email_from', 'no-reply@example.com');
        $subjectEnc = function_exists('mb_encode_mimeheader')
            ? mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n")
            : $subject;
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: " . $from;

        return @mail($to, $subjectEnc, $htmlBody, $headers);
    }

    /** ส่งให้ owner ที่เป็นเจ้าของ property */
    public static function sendToPropertyOwner(int $propertyId, string $type, string $title, string $message, ?string $link = null, array $meta = []): void
    {
        $row = Database::fetch(
            "SELECT u.id FROM properties p JOIN owners o ON o.id = p.owner_id JOIN users u ON u.id = o.user_id WHERE p.id = :p",
            ['p' => $propertyId]
        );
        if ($row) self::send((int)$row['id'], $type, $title, $message, $link, $meta);
    }

    public static function log(?int $userId, string $type, string $title, string $message, ?string $link, string $channel, string $status, array $meta = [], ?string $error = null): int
    {
        return Database::insert('notifications', [
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'link'    => $link,
            'channel' => $channel,
            'status'  => $status,
            'meta'    => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            'error'   => $error,
        ]);
    }

    private static function formatForLine(string $title, string $message, ?string $link): string
    {
        $text = "🔔 {$title}\n\n{$message}";
        if ($link) {
            $url = str_starts_with($link, 'http') ? $link : (rtrim((string)\App\Core\Application::$publicUrl, '/') . $link);
            $text .= "\n\n👉 {$url}";
        }
        return $text;
    }

    private static function sendEmail(string $to, string $subject, string $message, ?string $link = null): bool
    {
        if (!Setting::get('email_enabled', '0')) {
            return false; // คุณยังไม่ได้เปิด email
        }
        $from = Setting::get('email_from', 'no-reply@paekan.com');
        $body = nl2br(e($message));
        if ($link) {
            $url = str_starts_with($link, 'http') ? $link : (rtrim((string)\App\Core\Application::$publicUrl, '/') . $link);
            $body .= "<p><a href=\"" . e($url) . "\">เปิดดูรายละเอียด →</a></p>";
        }
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=utf-8\r\nFrom: " . $from;
        return @mail($to, $subject, $body, $headers);
    }
}
