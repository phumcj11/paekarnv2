<?php
namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Models\PropertyUnit;

/**
 * CRUD การจองฝั่งเจ้าของ — รองรับ schema เก่า/ใหม่ (mode, source, created_by)
 */
class OwnerBookingService
{
    /** mode ที่ INSERT ได้บน schema มาตรฐาน */
    public static function bookingModeForManual(): string
    {
        return 'coupon_assisted';
    }

    /**
     * @param array{property_id:int,unit_id:int,guest_name:string,guest_phone:string,check_in:string,check_out:string,guest_count?:int,guest_email?:?string,notes?:?string,system_note?:?string,total_price?:float|string|null,deposit_amount?:float|string|null,source?:string,guest_line_user_id?:?string,status?:string,send_line_confirm?:bool} $params
     */
    public static function createManual(array $params): int
    {
        $propertyId = (int)($params['property_id'] ?? 0);
        $unitId     = (int)($params['unit_id'] ?? 0);
        $guestName  = trim((string)($params['guest_name'] ?? ''));
        $guestPhone = trim((string)($params['guest_phone'] ?? ''));
        $checkIn    = trim((string)($params['check_in'] ?? ''));
        $checkOut   = trim((string)($params['check_out'] ?? ''));
        $guestCount = max(1, (int)($params['guest_count'] ?? 1));

        if (!$propertyId || !$unitId || !$guestName || !$guestPhone || !$checkIn || !$checkOut) {
            throw new \InvalidArgumentException('ข้อมูลการจองไม่ครบ');
        }
        if (strtotime($checkOut) <= strtotime($checkIn)) {
            throw new \InvalidArgumentException('วันเช็คเอาท์ต้องหลังวันเช็คอิน');
        }

        $unit = PropertyUnit::find($unitId);
        if (!$unit || (int)$unit['property_id'] !== $propertyId) {
            throw new \InvalidArgumentException('ไม่พบยูนิตของที่พักนี้');
        }

        $calc = BookingService::calculate($unit, $checkIn, $checkOut, $guestCount);

        $chargedTotal = array_key_exists('total_price', $params) && $params['total_price'] !== '' && $params['total_price'] !== null
            ? max(0, (float)$params['total_price'])
            : $calc['total'];
        $deposit = array_key_exists('deposit_amount', $params) && $params['deposit_amount'] !== '' && $params['deposit_amount'] !== null
            ? max(0, (float)$params['deposit_amount'])
            : 0;
        if ($deposit > $chargedTotal) {
            $deposit = $chargedTotal;
        }

        $userNotes  = trim((string)($params['notes'] ?? ''));
        $systemNote = trim((string)($params['system_note'] ?? ''));
        $noteParts  = array_values(array_filter([$userNotes, $systemNote], static fn(string $s): bool => $s !== ''));
        $notes      = $noteParts ? implode("\n", $noteParts) : null;

        $paymentStatus = 'unpaid';
        if ($deposit > 0 && $deposit < $chargedTotal) {
            $paymentStatus = 'partial';
        } elseif ($chargedTotal > 0 && $deposit >= $chargedTotal) {
            $paymentStatus = 'paid';
        }

        $source = in_array($params['source'] ?? '', ['manual_phone', 'manual_line', 'admin'], true)
            ? $params['source'] : 'manual_phone';
        $status = in_array($params['status'] ?? '', ['pending', 'confirmed'], true)
            ? $params['status'] : 'confirmed';

        $payload = [
            'property_id'    => $propertyId,
            'unit_id'        => $unitId,
            'mode'           => self::bookingModeForManual(),
            'guest_name'     => $guestName,
            'guest_phone'    => $guestPhone,
            'guest_email'    => trim((string)($params['guest_email'] ?? '')) ?: null,
            'guest_count'    => $guestCount,
            'check_in'       => $checkIn,
            'check_out'      => $checkOut,
            'nights'         => $calc['nights'],
            'subtotal'       => $calc['subtotal'],
            'discount'       => max(0, $calc['subtotal'] - $chargedTotal),
            'total_price'    => $chargedTotal,
            'status'         => $status,
            'payment_status' => $paymentStatus,
            'notes'          => $notes,
        ];

        if (Database::tableHasColumn('bookings', 'source')) {
            $payload['source'] = $source;
        }
        if (Database::tableHasColumn('bookings', 'created_by_user_id')) {
            $payload['created_by_user_id'] = Auth::id();
        }
        $lineUid = trim((string)($params['guest_line_user_id'] ?? ''));
        if ($lineUid && Database::tableHasColumn('bookings', 'guest_line_user_id')) {
            $payload['guest_line_user_id'] = $lineUid;
        }

        $bookingId = BookingService::create($payload);

        if ($deposit > 0) {
            $payRow = [
                'booking_id' => $bookingId,
                'amount'     => $deposit,
                'method'     => 'cash',
                'paid_at'    => date('Y-m-d H:i:s'),
                'status'     => 'verified',
            ];
            if (Database::tableHasColumn('booking_payments', 'verified_at')) {
                $payRow['verified_at'] = date('Y-m-d H:i:s');
            }
            if (Database::tableHasColumn('booking_payments', 'verified_by')) {
                $payRow['verified_by'] = Auth::id();
            }
            Database::insert('booking_payments', $payRow);
        }

        if (!empty($params['send_line_confirm'])) {
            BookingService::confirmAndNotify($bookingId, true);
        }

        return $bookingId;
    }

    /**
     * แก้ไขการจองที่เจ้าของบันทึกเอง (หรือจองมือทุกประเภทที่ยังไม่ completed/cancelled)
     *
     * @param array{unit_id?:int,guest_name?:string,guest_phone?:string,check_in?:string,check_out?:string,guest_count?:int,notes?:?string,total_price?:float|string|null,deposit_amount?:float|string|null,guest_line_user_id?:?string} $params
     */
    public static function updateManual(int $bookingId, int $ownerId, array $params): bool
    {
        $booking = self::findOwned($bookingId, $ownerId);
        if (!$booking) {
            return false;
        }
        if (in_array((string)$booking['status'], ['cancelled', 'completed'], true)) {
            throw new \InvalidArgumentException('ไม่สามารถแก้ไขการจองที่ยกเลิกหรือเสร็จสิ้นแล้ว');
        }

        $unitId     = (int)($params['unit_id'] ?? $booking['unit_id']);
        $guestName  = trim((string)($params['guest_name'] ?? $booking['guest_name']));
        $guestPhone = trim((string)($params['guest_phone'] ?? $booking['guest_phone']));
        $checkIn    = trim((string)($params['check_in'] ?? $booking['check_in']));
        $checkOut   = trim((string)($params['check_out'] ?? $booking['check_out']));
        $guestCount = max(1, (int)($params['guest_count'] ?? $booking['guest_count'] ?? 1));

        if (!$guestName || !$guestPhone || !$checkIn || !$checkOut) {
            throw new \InvalidArgumentException('ข้อมูลการจองไม่ครบ');
        }
        if (strtotime($checkOut) <= strtotime($checkIn)) {
            throw new \InvalidArgumentException('วันเช็คเอาท์ต้องหลังวันเช็คอิน');
        }

        $unit = PropertyUnit::find($unitId);
        if (!$unit || (int)$unit['property_id'] !== (int)$booking['property_id']) {
            throw new \InvalidArgumentException('ไม่พบยูนิตของที่พักนี้');
        }

        self::assertUnitAvailableForRange($unitId, $checkIn, $checkOut, $bookingId);

        $calc = BookingService::calculate($unit, $checkIn, $checkOut, $guestCount);

        $chargedTotal = array_key_exists('total_price', $params) && $params['total_price'] !== '' && $params['total_price'] !== null
            ? max(0, (float)$params['total_price'])
            : (float)$booking['total_price'];

        $deposit = array_key_exists('deposit_amount', $params) && $params['deposit_amount'] !== '' && $params['deposit_amount'] !== null
            ? max(0, (float)$params['deposit_amount'])
            : null;
        if ($deposit !== null && $deposit > $chargedTotal) {
            $deposit = $chargedTotal;
        }

        $notes = array_key_exists('notes', $params)
            ? (trim((string)($params['notes'] ?? '')) ?: null)
            : ($booking['notes'] ?: null);

        $paymentStatus = (string)($booking['payment_status'] ?? 'unpaid');
        if ($deposit !== null) {
            if ($deposit > 0 && $deposit < $chargedTotal) {
                $paymentStatus = 'partial';
            } elseif ($chargedTotal > 0 && $deposit >= $chargedTotal) {
                $paymentStatus = 'paid';
            } else {
                $paymentStatus = 'unpaid';
            }
        }

        $payload = [
            'unit_id'        => $unitId,
            'guest_name'     => $guestName,
            'guest_phone'    => $guestPhone,
            'guest_count'    => $guestCount,
            'check_in'       => $checkIn,
            'check_out'      => $checkOut,
            'nights'         => $calc['nights'],
            'subtotal'       => $calc['subtotal'],
            'discount'       => max(0, $calc['subtotal'] - $chargedTotal),
            'total_price'    => $chargedTotal,
            'payment_status' => $paymentStatus,
            'notes'          => $notes,
        ];

        if (array_key_exists('guest_line_user_id', $params) && Database::tableHasColumn('bookings', 'guest_line_user_id')) {
            $lineUid = trim((string)($params['guest_line_user_id'] ?? ''));
            $payload['guest_line_user_id'] = $lineUid ?: null;
        }

        Database::update('bookings', $payload, 'id = :i', ['i' => $bookingId]);

        if ($deposit !== null) {
            self::syncCashDeposit($bookingId, $deposit);
        }

        return true;
    }

    public static function assertUnitAvailableForRange(int $unitId, string $checkIn, string $checkOut, ?int $excludeBookingId = null): void
    {
        $unit = PropertyUnit::find($unitId);
        if (!$unit) {
            throw new \InvalidArgumentException('ไม่พบยูนิต');
        }
        $capacity = max(1, (int)($unit['total_units'] ?? 1));

        $params = ['u' => $unitId, 'ci' => $checkIn, 'co' => $checkOut];
        $excludeSql = '';
        if ($excludeBookingId) {
            $excludeSql = ' AND id <> :bid';
            $params['bid'] = $excludeBookingId;
        }

        $row = Database::fetch(
            "SELECT COUNT(*) AS cnt FROM bookings
             WHERE unit_id = :u AND status IN ('pending','confirmed')
             AND check_in < :co AND check_out > :ci{$excludeSql}",
            $params
        );
        if ((int)($row['cnt'] ?? 0) >= $capacity) {
            throw new \InvalidArgumentException('ช่วงวันที่นี้ยูนิตเต็มแล้ว — เลือกวันอื่นหรือยูนิตอื่น');
        }
    }

    private static function syncCashDeposit(int $bookingId, float $amount): void
    {
        Database::query(
            "DELETE FROM booking_payments WHERE booking_id = :b AND method = 'cash' AND status = 'verified'",
            ['b' => $bookingId]
        );
        if ($amount <= 0) {
            return;
        }
        $payRow = [
            'booking_id' => $bookingId,
            'amount'     => $amount,
            'method'     => 'cash',
            'paid_at'    => date('Y-m-d H:i:s'),
            'status'     => 'verified',
        ];
        if (Database::tableHasColumn('booking_payments', 'verified_at')) {
            $payRow['verified_at'] = date('Y-m-d H:i:s');
        }
        if (Database::tableHasColumn('booking_payments', 'verified_by')) {
            $payRow['verified_by'] = Auth::id();
        }
        Database::insert('booking_payments', $payRow);
    }

    public static function cancelOwned(int $bookingId, int $ownerId): bool
    {
        $row = Database::fetch(
            "SELECT b.id FROM bookings b JOIN properties p ON p.id = b.property_id WHERE b.id = :i AND p.owner_id = :o",
            ['i' => $bookingId, 'o' => $ownerId]
        );
        if (!$row) {
            return false;
        }
        Database::update('bookings', ['status' => 'cancelled'], 'id = :i', ['i' => $bookingId]);
        return true;
    }

    /** @return array<string,mixed>|null */
    public static function findOwned(int $bookingId, int $ownerId): ?array
    {
        return Database::fetch(
            "SELECT b.*, p.name AS property_name, u.name AS unit_name
             FROM bookings b
             JOIN properties p ON p.id = b.property_id
             LEFT JOIN property_units u ON u.id = b.unit_id
             WHERE b.id = :i AND p.owner_id = :o",
            ['i' => $bookingId, 'o' => $ownerId]
        );
    }
}
