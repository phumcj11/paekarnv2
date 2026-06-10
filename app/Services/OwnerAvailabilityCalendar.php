<?php
namespace App\Services;

use App\Core\Database;

class OwnerAvailabilityCalendar
{
    /** @return array{dayMeta: array<string,array>, bookingsByDate: array<string,list<array>>, availMap: array<string,array>, daysInMonth: int, startWeekday: int} */
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
                $availMap[$r['date']] = $r;
            }

            $bookings = Database::fetchAll(
                "SELECT b.id, b.code, b.guest_name, b.guest_phone, b.check_in, b.check_out,
                        b.status, b.total_price, b.nights, u.name AS unit_name,
                        COALESCE((
                            SELECT SUM(bp.amount) FROM booking_payments bp
                            WHERE bp.booking_id = b.id AND bp.status = 'verified'
                        ), 0) AS paid_amount
                 FROM bookings b
                 LEFT JOIN property_units u ON u.id = b.unit_id
                 WHERE b.unit_id = :u AND b.status IN ('pending','confirmed','completed')
                 AND b.check_in <= :e AND b.check_out > :s
                 ORDER BY b.check_in ASC",
                ['u' => $unitId, 's' => $start, 'e' => $end]
            );
            foreach ($bookings as $b) {
                $total = (float)$b['total_price'];
                $paid  = (float)$b['paid_amount'];
                $summary = [
                    'id'          => (int)$b['id'],
                    'code'        => $b['code'],
                    'guest_name'  => $b['guest_name'],
                    'guest_phone' => $b['guest_phone'],
                    'check_in'    => $b['check_in'],
                    'check_out'   => $b['check_out'],
                    'status'      => $b['status'],
                    'total_price' => $total,
                    'paid_amount' => $paid,
                    'balance'     => max(0, $total - $paid),
                    'nights'      => (int)$b['nights'],
                    'unit_name'   => $b['unit_name'] ?? '',
                ];
                $cur  = $b['check_in'];
                $stop = $b['check_out'];
                while ($cur < $stop) {
                    if ($cur >= $start && $cur <= $end) {
                        $availMap[$cur]['booked'] = ($availMap[$cur]['booked'] ?? 0) + 1;
                        $bookingsByDate[$cur][] = $summary;
                    }
                    $cur = date('Y-m-d', strtotime($cur . ' +1 day'));
                }
            }
        }

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
}
