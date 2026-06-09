<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\PropertyUnit;
use App\Services\BookingService;
use App\Services\CouponService;
use App\Support\PropertyBookingCapabilities;

class BookingController extends Controller
{
    /** @return array{0:string,1:array<string,mixed>} */
    private function bookingListFilters(): array
    {
        $where = '1=1';
        $params = [];
        if (!empty($_GET['status'])) {
            $where .= ' AND b.status = :st';
            $params['st'] = $_GET['status'];
        }
        if (!empty($_GET['property_id'])) {
            $where .= ' AND b.property_id = :pid';
            $params['pid'] = (int)$_GET['property_id'];
        }
        if (!empty($_GET['q'])) {
            $where .= ' AND (b.code LIKE :q OR b.guest_name LIKE :q OR b.guest_phone LIKE :q OR p.name LIKE :q)';
            $params['q'] = '%' . $_GET['q'] . '%';
        }

        return [$where, $params];
    }

    /** @return array{properties:list<array>,unitsByProperty:array<int,list<array>>} */
    private function formOptions(?array $booking = null): array
    {
        $properties = Database::fetchAll(
            "SELECT id, name, booking_mode, coupon_enabled FROM properties WHERE status IN ('published','pending') ORDER BY name"
        );
        $unitsByProperty = [];
        foreach ($properties as $p) {
            $unitsByProperty[(int)$p['id']] = Database::fetchAll(
                'SELECT id, name, price, capacity_min, capacity_max FROM property_units WHERE property_id = :pid ORDER BY sort_order, id',
                ['pid' => (int)$p['id']]
            );
        }

        return ['properties' => $properties, 'unitsByProperty' => $unitsByProperty];
    }

    public function index(): void
    {
        $perPage = (int)config('app.paginate.admin', 20);
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        [$where, $params] = $this->bookingListFilters();

        $rows = Database::fetchAll(
            "SELECT b.*, p.name AS property_name FROM bookings b
             JOIN properties p ON p.id = b.property_id
             WHERE $where ORDER BY b.created_at DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        $total = (int)Database::fetch(
            "SELECT COUNT(*) c FROM bookings b JOIN properties p ON p.id=b.property_id WHERE $where",
            $params
        )['c'];

        $propertyFilter = Database::fetchAll(
            "SELECT DISTINCT p.id, p.name FROM properties p
             INNER JOIN bookings b ON b.property_id = p.id ORDER BY p.name LIMIT 200"
        );

        View::render('admin/bookings/index', [
            'page_title' => 'การจอง',
            'rows' => $rows, 'total' => $total, 'page' => $page,
            'totalPages' => max(1, (int)ceil($total / $perPage)),
            'propertyFilter' => $propertyFilter,
        ], 'layouts/admin');
    }

    public function exportCsv(): void
    {
        AuditLog::record('admin_bookings_export_csv', [
            'q'      => isset($_GET['q']) ? substr((string)$_GET['q'], 0, 200) : null,
            'status' => isset($_GET['status']) ? (string)$_GET['status'] : null,
        ]);
        [$where, $params] = $this->bookingListFilters();
        $limit = max(100, min(50000, (int)config('app.admin_export_max_rows', 5000)));
        $rows = Database::fetchAll(
            "SELECT b.*, p.name AS property_name FROM bookings b
             JOIN properties p ON p.id = b.property_id
             WHERE $where ORDER BY b.created_at DESC LIMIT $limit",
            $params
        );

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="bookings-' . date('Ymd-His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        $headers = [
            'code', 'guest_name', 'guest_phone', 'guest_email', 'property_name',
            'check_in', 'check_out', 'nights', 'guest_count', 'subtotal', 'discount',
            'total_price', 'coupon_code_used', 'status', 'payment_status', 'created_at',
        ];
        fputcsv($out, $headers);
        foreach ($rows as $b) {
            $line = [];
            foreach ($headers as $h) {
                $line[] = isset($b[$h]) ? (string)$b[$h] : '';
            }
            fputcsv($out, $line);
        }
        fclose($out);
        exit;
    }

    public function create(): void
    {
        $opts = $this->formOptions();
        View::render('admin/bookings/form', [
            'page_title' => 'สร้างการจอง',
            'booking'    => null,
            'properties' => $opts['properties'],
            'unitsByProperty' => $opts['unitsByProperty'],
        ], 'layouts/admin');
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

        $property = Database::fetch("SELECT * FROM properties WHERE id = :id", ['id' => (int)$data['property_id']]);
        $unit = PropertyUnit::find((int)$data['unit_id']);
        if (!$property || !$unit || (int)$unit['property_id'] !== (int)$property['id']) {
            Session::flash('error', 'ไม่พบที่พัก / ยูนิต');
            back();
        }
        if (strtotime($data['check_out']) <= strtotime($data['check_in'])) {
            Session::flash('error', 'วันเช็คเอาท์ต้องหลังจากวันเช็คอิน');
            Session::withOld($_POST);
            back();
        }

        $couponCode = trim((string)($_POST['coupon_code'] ?? ''));
        $calc = BookingService::calculate($unit, $data['check_in'], $data['check_out'], (int)$data['guest_count'], $couponCode ?: null);

        $mode = $property['booking_mode'] ?: PropertyBookingCapabilities::syncBookingMode(
            PropertyBookingCapabilities::fromProperty($property)
        );
        $status = (string)($_POST['status'] ?? 'pending');
        if (!in_array($status, ['pending', 'confirmed', 'rejected', 'cancelled', 'completed', 'no_show'], true)) {
            $status = 'pending';
        }
        $paymentStatus = (string)($_POST['payment_status'] ?? 'unpaid');
        if (!in_array($paymentStatus, ['unpaid', 'partial', 'paid', 'refunded'], true)) {
            $paymentStatus = 'unpaid';
        }

        $bookingId = BookingService::create([
            'customer_id'      => !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null,
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
            'notes'            => trim((string)($_POST['notes'] ?? '')) ?: null,
            'status'           => $status,
            'payment_status'   => $paymentStatus,
        ]);

        if ($status === 'confirmed' && !empty($calc['coupon']['id'])) {
            CouponService::markUsed((int)$calc['coupon']['id'], $bookingId, (int)$property['id'], Auth::id());
            Database::update('bookings', ['payment_status' => 'paid'], 'id = :id', ['id' => $bookingId]);
        }

        AuditLog::record('admin_booking_created', ['booking_id' => $bookingId], 'booking', $bookingId);
        Session::flash('success', 'สร้างการจองเรียบร้อย');
        redirect(url('/admin/bookings/' . $bookingId));
    }

    public function show(int $id): void
    {
        $b = Database::fetch(
            "SELECT b.*, p.name AS property_name, p.slug AS property_slug, u.name AS unit_name
             FROM bookings b JOIN properties p ON p.id=b.property_id
             LEFT JOIN property_units u ON u.id=b.unit_id WHERE b.id = :id",
            ['id' => $id]
        );
        if (!$b) { http_response_code(404); View::render('errors/404'); return; }
        $payments = Database::fetchAll("SELECT * FROM booking_payments WHERE booking_id = :id ORDER BY id DESC", ['id' => $id]);
        View::render('admin/bookings/show', [
            'page_title' => 'การจอง #' . $b['code'],
            'booking' => $b, 'payments' => $payments,
            'canHardDelete' => BookingService::canHardDelete($b),
        ], 'layouts/admin');
    }

    public function edit(int $id): void
    {
        $b = Database::fetch('SELECT * FROM bookings WHERE id = :id', ['id' => $id]);
        if (!$b) { http_response_code(404); View::render('errors/404'); return; }
        $opts = $this->formOptions($b);
        View::render('admin/bookings/form', [
            'page_title' => 'แก้ไขการจอง #' . $b['code'],
            'booking'    => $b,
            'properties' => $opts['properties'],
            'unitsByProperty' => $opts['unitsByProperty'],
            'propertyName' => Database::fetch('SELECT name FROM properties WHERE id = :id', ['id' => (int)$b['property_id']])['name'] ?? '',
        ], 'layouts/admin');
    }

    public function update(int $id): void
    {
        $b = Database::fetch('SELECT * FROM bookings WHERE id = :id', ['id' => $id]);
        if (!$b) {
            Session::flash('error', 'ไม่พบการจอง');
            back();
        }

        $data = $this->validate([
            'unit_id'     => 'required|integer',
            'guest_name'  => 'required|max:120',
            'guest_phone' => 'required|phone',
            'guest_email' => 'email',
            'guest_count' => 'required|integer',
            'check_in'    => 'required|date',
            'check_out'   => 'required|date',
        ]);

        $input = array_merge($data, [
            'notes'           => $_POST['notes'] ?? null,
            'status'          => $_POST['status'] ?? $b['status'],
            'payment_status'  => $_POST['payment_status'] ?? $b['payment_status'],
            'coupon_code'     => $_POST['coupon_code'] ?? '',
            'customer_id'     => $_POST['customer_id'] ?? $b['customer_id'],
        ]);

        if (!BookingService::update($id, $input, Auth::id())) {
            Session::flash('error', 'ไม่สามารถบันทึกได้ — ตรวจสอบวันที่/ยูนิต/คูปอง');
            Session::withOld($_POST);
            back();
        }

        AuditLog::record('admin_booking_updated', ['booking_id' => $id], 'booking', $id);
        Session::flash('success', 'บันทึกการจองเรียบร้อย');
        redirect(url('/admin/bookings/' . $id));
    }

    public function destroy(int $id): void
    {
        $hard = !empty($_POST['hard_delete']);
        $b = Database::fetch('SELECT * FROM bookings WHERE id = :id', ['id' => $id]);
        if (!$b) {
            Session::flash('error', 'ไม่พบการจอง');
            back();
        }

        if (!BookingService::cancel($id, $hard, Auth::id())) {
            Session::flash('error', 'ไม่สามารถลบการจองได้');
            back();
        }

        AuditLog::record($hard ? 'admin_booking_deleted' : 'admin_booking_cancelled', [
            'code' => $b['code'],
            'hard' => $hard,
        ], 'booking', $id);

        Session::flash('success', $hard ? 'ลบการจองถาวรแล้ว' : 'ยกเลิกการจองแล้ว');
        redirect(url('/admin/bookings'));
    }

    public function verifyPayment(int $id): void
    {
        $b = Database::fetch('SELECT * FROM bookings WHERE id = :id', ['id' => $id]);
        if (!$b) { http_response_code(404); View::render('errors/404'); return; }

        $action = $_POST['action'] ?? 'verify';
        $payId  = (int)($_POST['payment_id'] ?? 0);

        if ($payId) {
            $newStatus = $action === 'reject' ? 'rejected' : 'verified';
            Database::update('booking_payments', [
                'status'      => $newStatus,
                'verified_at' => date('Y-m-d H:i:s'),
                'verified_by' => Auth::id(),
            ], 'id = :i AND booking_id = :b', ['i' => $payId, 'b' => $id]);

            if ($newStatus === 'verified') {
                Database::update('bookings', ['payment_status' => 'paid', 'status' => 'confirmed'], 'id = :i', ['i' => $id]);
                if (!empty($b['coupon_id'])) {
                    CouponService::markUsed((int)$b['coupon_id'], $id, (int)$b['property_id'], Auth::id());
                }
                Session::flash('success', 'ยืนยันการชำระเงินเรียบร้อย');
            } else {
                Session::flash('success', 'ปฏิเสธสลิปเรียบร้อย');
            }
            AuditLog::record('admin_booking_payment_' . $newStatus, ['payment_id' => $payId], 'booking', $id);
        }

        redirect(url('/admin/bookings/' . $id));
    }

    public function updateStatus(int $id): void
    {
        $status = $_POST['status'] ?? null;
        $allowed = ['pending','confirmed','rejected','cancelled','completed','no_show'];
        if (!in_array($status, $allowed)) { Session::flash('error','สถานะไม่ถูกต้อง'); back(); }

        $booking = Database::fetch("SELECT * FROM bookings WHERE id = :id", ['id' => $id]);
        if (!$booking) {
            Session::flash('error', 'ไม่พบการจอง');
            back();
        }
        $prevStatus = $booking['status'] ?? null;
        Database::update('bookings', ['status' => $status], 'id = :id', ['id' => $id]);

        if ($status === 'confirmed' && !empty($booking['coupon_id'])) {
            CouponService::markUsed((int)$booking['coupon_id'], (int)$booking['id'], (int)$booking['property_id'], Auth::id());
            Database::update('bookings', ['payment_status' => 'paid'], 'id = :id', ['id' => $id]);
        }
        if (in_array($status, ['cancelled', 'rejected'], true) && !empty($booking['coupon_id'])) {
            CouponService::releaseFromBooking((int)$booking['coupon_id'], (int)$booking['id']);
        }

        AuditLog::record('booking_status_changed', [
            'from' => $prevStatus,
            'to'   => $status,
            'code' => $booking['code'] ?? null,
        ], 'booking', $id);

        Session::flash('success', 'อัปเดตสถานะเรียบร้อย');
        back();
    }
}
