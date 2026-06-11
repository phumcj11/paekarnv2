<?php
namespace App\Services;

use App\Core\Database;
use App\Services\BookingConfirmationService;
use App\Services\PropertyLineService;

/**
 * Owner automation: อ่าน property_message_templates, render placeholders, ส่งผ่าน LINE OA
 * ใช้โดย BookingService (booking_confirmed, deposit_received)
 * และ CronService (checkin_reminder_1d, checkout_followup, review_request)
 */
class MessageTemplateService
{
    /**
     * ดึง template ที่ enabled สำหรับ property + event_type
     * คืน null ถ้าไม่มีหรือ disabled
     */
    public static function getTemplate(int $propertyId, string $eventType): ?array
    {
        if (!Database::tableHasColumn('property_message_templates', 'id')) {
            return null;
        }
        $row = Database::fetch(
            "SELECT message_text, send_delay_hours FROM property_message_templates
             WHERE property_id = :p AND event_type = :e AND is_enabled = 1 LIMIT 1",
            ['p' => $propertyId, 'e' => $eventType]
        );
        return $row ?: null;
    }

    /**
     * แทนที่ placeholder ใน template text ด้วยข้อมูลจริงของการจอง
     *
     * Placeholders ที่รองรับ:
     *   {{booking_code}}, {{property_name}}, {{unit_name}}, {{guest_name}},
     *   {{check_in_date}}, {{check_out_date}}, {{nights}}, {{total_price}},
     *   {{property_phone}}, {{review_url}}, {{confirm_url}}
     *
     * @param array<string,mixed> $booking  row from bookings JOIN properties
     */
    public static function render(string $text, array $booking): string
    {
        $confirmUrl = '';
        if (!empty($booking['code']) && !empty($booking['guest_phone'])) {
            try {
                $confirmUrl = BookingConfirmationService::publicUrl($booking);
            } catch (\Throwable) {}
        }

        $propertyName = $booking['property_name'] ?? $booking['pname'] ?? '';
        $propertyPhone = $booking['pphone'] ?? $booking['property_phone'] ?? '';

        $map = [
            '{{booking_code}}'   => (string)($booking['code'] ?? ''),
            '{{property_name}}'  => $propertyName,
            '{{unit_name}}'      => (string)($booking['unit_name'] ?? ''),
            '{{guest_name}}'     => (string)($booking['guest_name'] ?? ''),
            '{{check_in_date}}'  => self::thaiDate((string)($booking['check_in'] ?? '')),
            '{{check_out_date}}' => self::thaiDate((string)($booking['check_out'] ?? '')),
            '{{nights}}'         => (string)($booking['nights'] ?? ''),
            '{{total_price}}'    => number_format((float)($booking['total_price'] ?? 0), 0),
            '{{property_phone}}' => $propertyPhone,
            '{{review_url}}'     => $confirmUrl,
            '{{confirm_url}}'    => $confirmUrl,
        ];

        return str_replace(array_keys($map), array_values($map), $text);
    }

    /**
     * Fetch booking, render template, push via property LINE OA.
     * Returns true if sent OK, false if no template / no LINE ID / not enabled.
     */
    public static function sendToGuest(int $bookingId, string $eventType): bool
    {
        $b = Database::fetch(
            "SELECT b.*, p.name AS property_name, p.phone AS pphone
             FROM bookings b
             JOIN properties p ON p.id = b.property_id
             LEFT JOIN property_units u ON u.id = b.unit_id
             WHERE b.id = :i LIMIT 1",
            ['i' => $bookingId]
        );
        if (!$b || empty($b['guest_line_user_id'])) return false;

        $tpl = self::getTemplate((int)$b['property_id'], $eventType);
        if (!$tpl) return false;

        $text = self::render((string)$tpl['message_text'], $b);
        if (!$text) return false;

        try {
            return PropertyLineService::push(
                (int)$b['property_id'],
                (string)$b['guest_line_user_id'],
                [['type' => 'text', 'text' => $text]]
            );
        } catch (\Throwable) {
            return false;
        }
    }

    private static function thaiDate(string $ymd): string
    {
        if (!$ymd) return '';
        $ts = strtotime($ymd);
        if (!$ts) return $ymd;
        $thaiMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
        return (int)date('j', $ts) . ' ' . $thaiMonths[(int)date('n', $ts)] . ' ' . ((int)date('Y', $ts) + 543);
    }
}
