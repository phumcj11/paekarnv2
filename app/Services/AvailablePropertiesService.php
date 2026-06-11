<?php

namespace App\Services;

use App\Core\Database;

/**
 * ค้นหาที่พักที่ยังว่างในวันที่กำหนด
 * ใช้สำหรับหน้า "แพว่างวันนี้" / "ว่างเสาร์นี้" บนเว็บหลัก
 */
class AvailablePropertiesService
{
    /**
     * ดึงที่พักที่มียูนิตว่างอย่างน้อย 1 ยูนิตในวันที่กำหนด
     *
     * @return list<array<string,mixed>>
     */
    public static function findAvailableOn(string $date, ?string $type = null, int $limit = 60): array
    {
        $typeFilter = $type ? "AND p.type = :type" : '';
        $params = ['date' => $date, 'checkin' => $date, 'checkout' => date('Y-m-d', strtotime($date . ' +1 day'))];
        if ($type) $params['type'] = $type;

        // ยูนิตที่ว่าง = ยูนิตที่ active + ไม่ถูก block ในวันนั้น + การจองที่ทับซ้อน < total_units
        // เรียงลำดับ: VIP membership → Standard → is_featured → อัปเดตปฏิทินล่าสุด → rating
        $sql = "
            SELECT p.id, p.name, p.slug, p.type, p.zone, p.district, p.province,
                   p.cover_image, p.min_price, p.rating_avg, p.rating_count,
                   p.is_featured, p.coupon_enabled,
                   p.owner_id,
                   (SELECT o.membership_tier FROM owners o WHERE o.id = p.owner_id LIMIT 1) AS owner_membership_tier,
                   (SELECT o.membership_expires_at FROM owners o WHERE o.id = p.owner_id LIMIT 1) AS owner_membership_expires_at,
                   (SELECT o.membership_grace_until FROM owners o WHERE o.id = p.owner_id LIMIT 1) AS owner_membership_grace_until,
                   MIN(u.price) AS unit_min_price,
                   COUNT(DISTINCT u.id) AS available_unit_count,
                   (SELECT MAX(av2.updated_at) FROM availability av2
                    WHERE av2.unit_id IN (SELECT id FROM property_units WHERE property_id = p.id)
                    AND av2.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS calendar_updated_at
            FROM properties p
            INNER JOIN property_units u ON u.property_id = p.id AND u.is_active = 1
            WHERE p.status = 'published'
            {$typeFilter}
            AND NOT EXISTS (
                SELECT 1 FROM availability av
                WHERE av.unit_id = u.id
                AND av.date = :date
                AND av.status IN ('closed','blocked','fully_booked')
            )
            AND (
                SELECT COUNT(*)
                FROM bookings b
                WHERE b.unit_id = u.id
                AND b.status IN ('pending','confirmed')
                AND b.check_in <= :checkout
                AND b.check_out > :checkin
            ) < u.total_units
            GROUP BY p.id, p.name, p.slug, p.type, p.zone, p.district, p.province,
                     p.cover_image, p.min_price, p.rating_avg, p.rating_count,
                     p.is_featured, p.coupon_enabled, p.owner_id
            ORDER BY
                CASE
                  WHEN (SELECT o2.membership_tier FROM owners o2 WHERE o2.id = p.owner_id LIMIT 1) = 'vip'   THEN 2
                  WHEN (SELECT o2.membership_tier FROM owners o2 WHERE o2.id = p.owner_id LIMIT 1) = 'standard' THEN 1
                  ELSE 0
                END DESC,
                p.is_featured DESC,
                p.rating_avg DESC, p.rating_count DESC, p.id DESC
            LIMIT {$limit}
        ";

        $rows = Database::fetchAll($sql, $params);

        // ใช้ unit_min_price ถ้าต่ำกว่า min_price
        foreach ($rows as &$r) {
            $unitPrice = (float)($r['unit_min_price'] ?? 0);
            if ($unitPrice > 0 && ($unitPrice < (float)$r['min_price'] || (float)$r['min_price'] <= 0)) {
                $r['min_price'] = $unitPrice;
            }
        }
        unset($r);

        return $rows;
    }

    /** วันเสาร์ที่ใกล้ที่สุด (ถ้าวันนี้เป็นเสาร์ ก็ใช้วันนี้) */
    public static function thisOrNextSaturday(): string
    {
        $dow = (int)date('w'); // 0=Sun, 6=Sat
        $diff = ($dow === 6) ? 0 : (6 - $dow);
        return date('Y-m-d', strtotime("+{$diff} day"));
    }

    /** วันเสาร์ + อาทิตย์ถัดไป */
    public static function nextWeekendDates(): array
    {
        $sat = self::thisOrNextSaturday();
        $sun = date('Y-m-d', strtotime($sat . ' +1 day'));
        return ['saturday' => $sat, 'sunday' => $sun];
    }

    /** label วันที่ภาษาไทย */
    public static function thaiDate(string $ymd): string
    {
        $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $days   = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัส', 'ศุกร์', 'เสาร์'];
        $ts     = strtotime($ymd);
        return $days[(int)date('w', $ts)] . ' ' . (int)date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . ((int)date('Y', $ts) + 543);
    }
}
