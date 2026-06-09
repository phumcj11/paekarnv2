<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\Upload;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Models\Booking;
use App\Models\Coupon;
use App\Services\BookingService;
use App\Services\CouponService;
use App\Support\PropertyBookingCapabilities;

class BookingController extends Controller
{
    public function create(int $propertyId): void
    {
        $property = Property::findByIdPublic($propertyId);
        if (!$property) { http_response_code(404); $this->view('errors/404'); return; }

        $intent = (string)($_GET['intent'] ?? 'book');
        if (!in_array($intent, ['book', 'coupon'], true)) {
            $intent = 'book';
        }

        $units    = Property::units($propertyId);
        $unitId   = (int)($_GET['unit'] ?? ($units[0]['id'] ?? 0));

        if ($intent === 'coupon') {
            $params = ['intent' => 'book', 'unit' => $unitId];
            $couponParam = trim((string)($_GET['coupon'] ?? ''));
            if ($couponParam !== '') {
                $params['coupon'] = $couponParam;
            }
            redirect(url('/booking/create/' . $propertyId . '?' . http_build_query($params)));
        }

        if (!PropertyBookingCapabilities::allowsIntent($property, $intent)) {
            Session::flash('error', 'ที่พักนี้ยังไม่เปิดจองออนไลน์');
            redirect(url('/property/' . $property['slug']));
        }

        $unit     = null;
        foreach ($units as $u) if ($u['id'] == $unitId) { $unit = $u; break; }
        if (!$unit && !empty($units)) $unit = $units[0];

        $walletCoupons = [];
        $cid = Auth::customerId();
        if ($cid) {
            $walletCoupons = Coupon::walletForCustomer($cid);
        }
        $prefillCoupon = trim((string)($_GET['coupon'] ?? ''));

        $this->view('bookings/create', [
            'meta_title' => 'จองที่พัก — ' . $property['name'] . ' — แพกาญ.com',
            'property' => $property,
            'units'    => $units,
            'unit'     => $unit,
            'user'     => Auth::user(),
            'booking_intent' => 'book',
            'wallet_coupons' => $walletCoupons,
            'prefill_coupon' => $prefillCoupon,
        ]);
    }

    public function store(): void
    {
        $data = $this->validate([
            'property_id' => 'required|integer',
            'unit_id'     => 'required|integer',
            'guest_name'  => 'required|max:120',
            'guest_phone' => 'required|phone',
            'guest_email' => 'email',
            'guest_count' => 'required|integer',
            'check_in'    => 'required|date',
            'check_out'   => 'required|date',
        ]);

        $property = Property::findByIdPublic((int)$data['property_id']);
        $unit     = PropertyUnit::findActive((int)$data['unit_id']);
        if (!$property || !$unit || (int)$unit['property_id'] !== (int)$property['id']) {
            Session::flash('error', 'ไม่พบที่พัก / ห้องที่เลือก');
            back();
        }
        if (strtotime($data['check_out']) <= strtotime($data['check_in'])) {
            Session::flash('error', 'วันเช็คเอาท์ต้องหลังจากวันเช็คอิน');
            Session::withOld($data);
            back();
        }

        $intent = (string)($_POST['booking_intent'] ?? 'book');
        if ($intent === 'coupon') {
            $intent = 'book';
        }
        if (!in_array($intent, ['book'], true)) {
            $intent = 'book';
        }
        if (!PropertyBookingCapabilities::allowsIntent($property, $intent)) {
            Session::flash('error', 'ไม่สามารถจองในรูปแบบที่เลือกได้');
            back();
        }

        $couponCode = trim((string)($_POST['coupon_code'] ?? ''));
        $calc = BookingService::calculate($unit, $data['check_in'], $data['check_out'], (int)$data['guest_count'], $couponCode ?: null);

        // upload slip เมื่อจองแบบชำระเงินเท่านั้น
        $slipPath = null;
        $needsPayment = PropertyBookingCapabilities::showPayment($property, $intent);
        if ($needsPayment) {
            try { $slipPath = Upload::image('slip', 'slips'); }
            catch (\Throwable $e) { Session::flash('error', $e->getMessage()); back(); }
        }

        $mode = $property['booking_mode'] ?: PropertyBookingCapabilities::syncBookingMode(
            PropertyBookingCapabilities::fromProperty($property)
        );

        $bookingId = BookingService::create([
            'customer_id'      => Auth::customerId(),
            'property_id'      => (int)$property['id'],
            'unit_id'          => (int)$unit['id'],
            'mode'             => $mode,
            'guest_name'       => $data['guest_name'],
            'guest_phone'      => $data['guest_phone'],
            'guest_email'      => $data['guest_email'] ?? null,
            'guest_count'      => (int)$data['guest_count'],
            'check_in'         => $data['check_in'],
            'check_out'        => $data['check_out'],
            'nights'           => $calc['nights'],
            'subtotal'         => $calc['subtotal'],
            'discount'         => $calc['discount'],
            'total_price'      => $calc['total'],
            'coupon_id'        => $calc['coupon']['id'] ?? null,
            'coupon_code_used' => $calc['coupon']['code'] ?? null,
            'notes'            => $_POST['notes'] ?? null,
            'status'           => 'pending',
            'payment_status'   => $slipPath ? 'partial' : 'unpaid',
        ]);

        // booking payment record
        if ($slipPath) {
            Database::insert('booking_payments', [
                'booking_id' => $bookingId,
                'amount'     => $calc['total'],
                'method'     => 'bank_transfer',
                'slip_path'  => $slipPath,
                'paid_at'    => date('Y-m-d H:i:s'),
                'status'     => 'pending',
            ]);
        }

        // hold coupon (mark used เมื่อ owner ยืนยัน — ตอนนี้แค่ link)
        if (!empty($calc['coupon'])) {
            // เปลี่ยน status เป็น used เลยตอน admin approve booking (ทำใน Admin\BookingController)
        }

        // info-only mode → log lead instead
        if ($mode === 'info_only') {
            Database::insert('leads', [
                'source'      => 'web',
                'name'        => $data['guest_name'],
                'phone'       => $data['guest_phone'],
                'email'       => $data['guest_email'] ?? null,
                'property_id' => $property['id'],
                'message'     => 'จองผ่านระบบ booking (info_only)',
                'check_in'    => $data['check_in'],
                'check_out'   => $data['check_out'],
                'guest_count' => (int)$data['guest_count'],
                'status'      => 'new',
            ]);
        }

        $code = Database::fetch('SELECT code FROM bookings WHERE id = :id', ['id' => $bookingId])['code'];
        Session::flash('success', 'สร้างคำขอจองสำเร็จ');
        redirect(url('/booking/success/' . $code));
    }

    public function success(string $code): void
    {
        $booking = Database::fetch(
            "SELECT b.*, p.name AS property_name, p.cover_image, p.slug, u.name AS unit_name
             FROM bookings b
             JOIN properties p ON p.id = b.property_id
             LEFT JOIN property_units u ON u.id = b.unit_id
             WHERE b.code = :c",
            ['c' => $code]
        );
        if (!$booking) { http_response_code(404); $this->view('errors/404'); return; }

        $this->view('bookings/success', [
            'meta_title' => 'จองสำเร็จ — ' . $booking['property_name'] . ' — แพกาญ.com',
            'booking' => $booking,
        ]);
    }
}
