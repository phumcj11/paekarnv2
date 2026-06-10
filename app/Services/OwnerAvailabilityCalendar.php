<?php
namespace App\Services;

use App\Core\Database;

class OwnerAvailabilityCalendar
{
    /** ปฏิทินรายยูนิต (หน้าจัดการปฏิทินเต็ม) */
    public static function buildMonth(int $unitId, int $month, int $year, int $totalUnits): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-t', strtotime($start));
        $availMap = [];
        $bookingsByDate = [];

        if ($unitId > 0) {
            $rows = Database::fetchAll(
                "SELECT date, status, available_units, note FROM availability WHERE unit_id = :u AND date BETWEEN :s AND :e",
                ['u' => $unitId, 's' => $start, 'e' => $end]
            );
            foreach ($rows as $r) {
                $availMap[self::ymd($r['date'])] = $r;
            }

            $bookings = self::fetchBookings(null, $unitId, $start, $end);
            self::applyBookingsToMaps($bookings, $start, $end, $availMap, $bookingsByDate);
        }

        return self::finalizeMonth($year, $month, $availMap, $bookingsByDate, $totalUnits);
    }

    /** ปฏิทินรวมทุกยูนิตในที่พัก (หน้าแรก Owner) */
    public static function buildPropertyMonth(int $propertyId, int $month, int $year): array
    {
        $units = Database::fetchAll(
            "SELECT id, name, total_units FROM property_units WHERE property_id = :p AND is_active=1 ORDER BY sort_order, id",
            ['p' => $propertyId]
        );

        $totalCapacity = 0;
        $unitIds = [];
        foreach ($units as $u) {
            $unitIds[] = (int)$u['id'];
            $totalCapacity += max(1, (int)$u['total_units']);
        }
        if ($totalCapacity < 1) {
            $totalCapacity = 1;
        }

        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-t', strtotime($start));
        $availMap = [];
        $bookingsByDate = [];

        if ($unitIds) {
            $closedPerDate = [];
            $placeholders  = implode(',', array_fill(0, count($unitIds), '?'));
            $params        = array_merge($unitIds, [$start, $end]);
            $rows = Database::fetchAll(
                "SELECT unit_id, date, status FROM availability
                 WHERE unit_id IN ($placeholders) AND date BETWEEN ? AND ?",
                $params
            );
            $unitCount = count($unitIds);
            foreach ($rows as $r) {
                if (!in_array($r['status'], ['closed', 'blocked', 'fully_booked'], true)) {
                    continue;
                }
                $d = self::ymd($r['date']);
                $closedPerDate[$d] = ($closedPerDate[$d] ?? 0) + 1;
                if ($closedPerDate[$d] >= $unitCount) {
                    $availMap[$d] = ['status' => 'closed', 'booked' => 0];
                }
            }

            $bookings = self::fetchBookings($propertyId, null, $start, $end);
            self::applyBookingsToMaps($bookings, $start, $end, $availMap, $bookingsByDate);
        }

        return self::finalizeMonth($year, $month, $availMap, $bookingsByDate, $totalCapacity);
    }

    public static function dayMeta(string $date, ?array $row, int $totalUnits): array
    {
        if ($date < date('Y-m-d')) {
            return ['key' => 'past', 'label' => '', 'cls' => 'bg-slate-50 border-slate-200 text-slate-400'];
        }
        $status = $row['status'] ?? null;
        if (in_array($status, ['closed', 'blocked', 'fully_booked'], true)) {
            return ['key' => 'closed', 'label' => 'ปิด', 'cls' => 'bg-slate-300 border-slate-400 text-slate-700'];
        }
        $booked = (int)($row['booked'] ?? 0);
        if ($booked >= $totalUnits) {
            return ['key' => 'full', 'label' => 'เต็ม', 'cls' => 'bg-rose-100 border-rose-300 text-rose-800', 'booked' => $booked];
        }
        if ($booked > 0) {
            return ['key' => 'booked', 'label' => 'จอง', 'cls' => 'bg-amber-100 border-amber-300 text-amber-900', 'booked' => $booked];
        }
        return ['key' => 'open', 'label' => 'ว่าง', 'cls' => 'bg-emerald-100 border-emerald-300 text-emerald-800', 'booked' => 0];
    }

    private static function ymd(?string $date): string
    {
        if (!$date) {
            return '';
        }
        return substr((string)$date, 0, 10);
    }

    /** @return list<array<string,mixed>> */
    private static function fetchBookings(?int $propertyId, ?int $unitId, string $start, string $end): array
    {
        if ($unitId) {
            $where  = 'b.unit_id = :scope';
            $scope  = $unitId;
        } elseif ($propertyId) {
            $where  = 'b.property_id = :scope';
            $scope  = $propertyId;
        } else {
            return [];
        }

        return Database::fetchAll(
            "SELECT b.id, b.code, b.guest_name, b.guest_phone,
                    DATE(b.check_in) AS check_in, DATE(b.check_out) AS check_out,
                    b.status, b.total_price, b.nights, b.unit_id, b.notes,
                    u.name AS unit_name,
                    COALESCE((
                        SELECT SUM(bp.amount) FROM booking_payments bp
                        WHERE bp.booking_id = b.id AND bp.status = 'verified'
                    ), 0) AS paid_amount
             FROM bookings b
             LEFT JOIN property_units u ON u.id = b.unit_id
             WHERE $where AND b.status IN ('pending','confirmed','completed')
             AND b.check_in <= :e AND b.check_out > :s
             ORDER BY b.check_in ASC",
            ['scope' => $scope, 's' => $start, 'e' => $end]
        );
    }

    /** @param list<array<string,mixed>> $bookings */
    private static function applyBookingsToMaps(array $bookings, string $start, string $end, array &$availMap, array &$bookingsByDate): void
    {
        foreach ($bookings as $b) {
            $summary = self::bookingSummary($b);
            $cur  = self::ymd($b['check_in']);
            $stop = self::ymd($b['check_out']);
            while ($cur && $stop && $cur < $stop) {
                if ($cur >= $start && $cur <= $end) {
                    $availMap[$cur]['booked'] = ($availMap[$cur]['booked'] ?? 0) + 1;
                    $bookingsByDate[$cur][] = $summary;
                }
                $cur = date('Y-m-d', strtotime($cur . ' +1 day'));
            }
        }
    }

    /** @param array<string,mixed> $b */
    private static function bookingSummary(array $b): array
    {
        $total = (float)$b['total_price'];
        $paid  = (float)$b['paid_amount'];
        return [
            'id'          => (int)$b['id'],
            'code'        => $b['code'],
            'guest_name'  => $b['guest_name'],
            'guest_phone' => $b['guest_phone'],
            'check_in'    => self::ymd($b['check_in']),
            'check_out'   => self::ymd($b['check_out']),
            'status'      => $b['status'],
            'total_price' => $total,
            'paid_amount' => $paid,
            'balance'     => max(0, $total - $paid),
            'nights'      => (int)$b['nights'],
            'unit_id'     => (int)($b['unit_id'] ?? 0),
            'unit_name'   => $b['unit_name'] ?? '',
            'notes'       => $b['notes'] ?? '',
        ];
    }

    /** @return array{dayMeta: array<string,array>, bookingsByDate: array<string,list<array>>, availMap: array<string,array>, daysInMonth: int, startWeekday: int} */
    private static function finalizeMonth(int $year, int $month, array $availMap, array $bookingsByDate, int $totalUnits): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $daysInMonth  = (int)date('t', strtotime($start));
        $startWeekday = (int)date('w', strtotime($start));
        $dayMeta = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dayMeta[$date] = self::dayMeta($date, $availMap[$date] ?? null, $totalUnits);
        }

        return [
            'dayMeta'        => $dayMeta,
            'bookingsByDate' => $bookingsByDate,
            'availMap'       => $availMap,
            'daysInMonth'    => $daysInMonth,
            'startWeekday'   => $startWeekday,
        ];
    }
}
