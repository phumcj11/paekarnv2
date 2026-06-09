<?php
namespace App\Services;

use App\Core\Database;

class BookingConfirmationService
{
    /**
     * สร้าง signed URL สำหรับใบยืนยัน — HMAC ของ (code + phone) ป้องกันเดา
     */
    public static function publicUrl(array $booking): string
    {
        $token = self::sign((string)$booking['code'], (string)$booking['guest_phone']);
        return url('/booking/confirmation/' . urlencode((string)$booking['code']) . '?t=' . urlencode($token));
    }

    public static function sign(string $code, string $phone): string
    {
        $secret = config('app.key', 'paekan-confirm-secret');
        return hash_hmac('sha256', $code . '|' . $phone, $secret);
    }

    public static function verify(string $code, string $token): ?array
    {
        $b = Database::fetch(
            "SELECT b.*, p.name AS property_name, p.cover_image, p.phone AS property_phone,
                    p.slug AS property_slug, u.name AS unit_name
             FROM bookings b
             JOIN properties p ON p.id = b.property_id
             LEFT JOIN property_units u ON u.id = b.unit_id
             WHERE b.code = :c LIMIT 1",
            ['c' => $code]
        );
        if (!$b) return null;
        $expected = self::sign($code, (string)$b['guest_phone']);
        if (!hash_equals($expected, $token)) return null;
        return $b;
    }
}
