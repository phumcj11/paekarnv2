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
