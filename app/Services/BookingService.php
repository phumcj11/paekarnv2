<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\PropertyUnit;
use App\Services\NotificationService;

class BookingService
{
    public static function calculate(array $unit, string $checkIn, string $checkOut, int $guests, ?string $couponCode = null): array
    {
        $nights = max(1, (int)((strtotime($checkOut) - strtotime($checkIn)) / 86400));
        $base   = (float)$unit['price'];

        $subtotal = 0;
        for ($i = 0; $i < $nights; $i++) {
            $d = strtotime("+$i day", strtotime($checkIn));
            $dow = (int)date('w', $d);  // 0=Sun, 6=Sat
            $price = ($dow === 5 || $dow === 6) && $unit['price_weekend'] > 0
                ? (float)$unit['price_weekend'] : $base;
            $subtotal += $price;
        }

        $extra = 0;
        if ($guests > $unit['capacity_max']) {
            $extra = ($guests - $unit['capacity_max']) * (float)$unit['extra_person_fee'] * $nights;
        }
        $subtotal += $extra;

        $discount = 0;
        $couponData = null;
        if ($couponCode) {
            $v = CouponService::validate($couponCode);
            if ($v['ok']) {
                $discount = min($subtotal, (float)$v['coupon']['face_value']);
                $couponData = $v['coupon'];
            }
        }

        return [
            'nights'      => $nights,
            'subtotal'    => $subtotal,
            'extra_fee'   => $extra,
            'discount'    => $discount,
            'total'       => max(0, $subtotal - $discount),
            'coupon'      => $couponData,
        ];
    }

    public static function create(array $data): int
    {
        $data['code'] = Booking::generateCode();
        $bookingId = Booking::create($data);

        // Phase 3: notify owner of new booking
        try {
            $b = Database::fetch("SELECT b.*, p.name AS pname FROM bookings b JOIN properties p ON p.id=b.property_id WHERE b.id = :i", ['i' => $bookingId]);
            if ($b) {
                NotificationService::sendToPropertyOwner(
                    (int)$b['property_id'],
                    'booking_new',
                    'มีการจองใหม่!',
                    sprintf('คุณ %s จองแพ "%s" %d คืน รวม %s บาท', $b['guest_name'], $b['pname'], $b['nights'], number_format($b['total_price'])),
                    '/owner/bookings/' . $bookingId,
                    ['booking_id' => $bookingId, 'code' => $b['code']]
                );
                // notify admin too
                NotificationService::sendToRole('admin', 'booking_new', 'มีการจองใหม่ในระบบ',
                    sprintf('การจอง #%s — %s', $b['code'], $b['pname']),
                    '/admin/bookings/' . $bookingId);
            }
        } catch (\Throwable $e) { /* never block booking */ }

        return $bookingId;
    }

    /**
     * @param array<string,mixed> $input
     */
    public static function update(int $bookingId, array $input, ?int $actorUserId = null): bool
    {
        $booking = Database::fetch('SELECT * FROM bookings WHERE id = :id', ['id' => $bookingId]);
        if (!$booking) {
            return false;
        }

        $unitId = (int)($input['unit_id'] ?? $booking['unit_id']);
        $unit = PropertyUnit::find($unitId);
        if (!$unit || (int)$unit['property_id'] !== (int)$booking['property_id']) {
            return false;
        }

        $checkIn = (string)($input['check_in'] ?? $booking['check_in']);
        $checkOut = (string)($input['check_out'] ?? $booking['check_out']);
        $guestCount = (int)($input['guest_count'] ?? $booking['guest_count']);
        if (strtotime($checkOut) <= strtotime($checkIn)) {
            return false;
        }

        $locked = in_array((string)$booking['status'], ['confirmed', 'completed'], true);
        $couponCode = null;
        if (!$locked && array_key_exists('coupon_code', $input)) {
            $couponCode = trim((string)$input['coupon_code']) ?: null;
        } elseif (!empty($booking['coupon_code_used'])) {
            $couponCode = (string)$booking['coupon_code_used'];
        }

        $calc = self::calculate($unit, $checkIn, $checkOut, $guestCount, $couponCode);

        $prevCouponId = (int)($booking['coupon_id'] ?? 0);
        $newCouponId = isset($calc['coupon']['id']) ? (int)$calc['coupon']['id'] : null;
        if (!$locked && $prevCouponId > 0 && $prevCouponId !== ($newCouponId ?? 0)) {
            CouponService::releaseFromBooking($prevCouponId, $bookingId);
        }

        $payload = [
            'unit_id'          => $unitId,
            'guest_name'       => (string)($input['guest_name'] ?? $booking['guest_name']),
            'guest_phone'      => (string)($input['guest_phone'] ?? $booking['guest_phone']),
            'guest_email'      => ($input['guest_email'] ?? $booking['guest_email']) ?: null,
            'guest_count'      => $guestCount,
            'check_in'         => $checkIn,
            'check_out'        => $checkOut,
            'nights'           => $calc['nights'],
            'notes'            => array_key_exists('notes', $input) ? ($input['notes'] ?: null) : $booking['notes'],
        ];

        if (!$locked) {
            $payload['subtotal'] = $calc['subtotal'];
            $payload['discount'] = $calc['discount'];
            $payload['total_price'] = $calc['total'];
            $payload['coupon_id'] = $newCouponId;
            $payload['coupon_code_used'] = $calc['coupon']['code'] ?? null;
        }

        if (array_key_exists('status', $input)) {
            $st = (string)$input['status'];
            if (in_array($st, ['pending', 'confirmed', 'rejected', 'cancelled', 'completed', 'no_show'], true)) {
                $payload['status'] = $st;
            }
        }
        if (array_key_exists('payment_status', $input)) {
            $ps = (string)$input['payment_status'];
            if (in_array($ps, ['unpaid', 'partial', 'paid', 'refunded'], true)) {
                $payload['payment_status'] = $ps;
            }
        }
        if (array_key_exists('customer_id', $input)) {
            $payload['customer_id'] = $input['customer_id'] ? (int)$input['customer_id'] : null;
        }

        Database::update('bookings', $payload, 'id = :id', ['id' => $bookingId]);

        $newStatus = (string)($payload['status'] ?? $booking['status']);
        if ($newStatus === 'confirmed' && !empty($payload['coupon_id'] ?? $booking['coupon_id'])) {
            $cid = (int)($payload['coupon_id'] ?? $booking['coupon_id']);
            CouponService::markUsed($cid, $bookingId, (int)$booking['property_id'], $actorUserId);
            Database::update('bookings', ['payment_status' => 'paid'], 'id = :id', ['id' => $bookingId]);
        }
        if (in_array($newStatus, ['cancelled', 'rejected'], true)) {
            $cid = (int)($payload['coupon_id'] ?? $booking['coupon_id'] ?? 0);
            if ($cid > 0) {
                CouponService::releaseFromBooking($cid, $bookingId);
            }
        }

        return true;
    }

    public static function cancel(int $bookingId, bool $hardDelete = false, ?int $actorUserId = null): bool
    {
        $booking = Database::fetch('SELECT * FROM bookings WHERE id = :id', ['id' => $bookingId]);
        if (!$booking) {
            return false;
        }

        if (!empty($booking['coupon_id'])) {
            CouponService::releaseFromBooking((int)$booking['coupon_id'], $bookingId);
        }

        if ($hardDelete && self::canHardDelete($booking)) {
            Database::delete('booking_payments', 'booking_id = :id', ['id' => $bookingId]);
            Database::delete('bookings', 'id = :id', ['id' => $bookingId]);

            return true;
        }

        Database::update('bookings', ['status' => 'cancelled'], 'id = :id', ['id' => $bookingId]);

        return true;
    }

    /** @param array<string,mixed> $booking */
    public static function canHardDelete(array $booking): bool
    {
        if (!in_array((string)($booking['status'] ?? ''), ['pending', 'rejected', 'cancelled'], true)) {
            return false;
        }
        if (!empty($booking['coupon_id'])) {
            $coupon = Coupon::find((int)$booking['coupon_id']);
            if ($coupon && (string)$coupon['status'] === 'used') {
                return false;
            }
        }

        return true;
    }
}
