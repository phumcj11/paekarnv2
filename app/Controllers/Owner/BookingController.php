<?php
namespace App\Controllers\Owner;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\PropertyUnit;
use App\Services\BookingService;
use App\Services\OwnerBookingService;

class BookingController extends Controller
{
    private function whereOwner(): array
    {
        if (Auth::isAdmin()) return ['', []];
        $oid = Auth::ownerId();
        return $oid ? [' AND p.owner_id = :oid', ['oid' => $oid]] : [' AND 0=1', []];
    }

    public function index(): void
    {
        [$ownerWhere, $ownerParams] = $this->whereOwner();

        $status = $_GET['status'] ?? '';
        $q      = trim((string)($_GET['q'] ?? ''));

        $where = "1=1 $ownerWhere"; $params = $ownerParams;
        if ($status !== '' && in_array($status, ['pending','confirmed','rejected','cancelled','completed','no_show'])) {
            $where .= " AND b.status = :st"; $params['st'] = $status;
        }
        if ($q !== '') {
            $where .= " AND (b.code LIKE :q OR b.guest_name LIKE :q OR b.guest_phone LIKE :q)";
            $params['q'] = "%$q%";
        }

        $rows = Database::fetchAll(
            "SELECT b.*, p.name AS property_name, u.name AS unit_name
             FROM bookings b
             JOIN properties p ON p.id = b.property_id
             LEFT JOIN property_units u ON u.id = b.unit_id
             WHERE $where ORDER BY b.created_at DESC LIMIT 200",
            $params);

        $counts = Database::fetch(
            "SELECT
              SUM(CASE WHEN b.status='pending' THEN 1 ELSE 0 END) as pending,
              SUM(CASE WHEN b.status='confirmed' THEN 1 ELSE 0 END) as confirmed,
              SUM(CASE WHEN b.status='completed' THEN 1 ELSE 0 END) as completed,
              SUM(CASE WHEN b.status='rejected' THEN 1 ELSE 0 END) as rejected,
              COUNT(*) as total
             FROM bookings b JOIN properties p ON p.id = b.property_id
             WHERE 1=1 $ownerWhere", $ownerParams);

        View::render('owner/bookings/index', [
            'page_title' => 'การจอง', 'rows' => $rows, 'counts' => $counts,
            'status' => $status, 'q' => $q,
        ], 'layouts/owner');
    }

    public function show(int $id): void
    {
        $row = $this->fetchOwnedBooking($id);
        if (!$row) { http_response_code(404); View::render('errors/404'); return; }

        $payments = Database::fetchAll("SELECT * FROM booking_payments WHERE booking_id = :b ORDER BY created_at DESC", ['b' => $id]);

        View::render('owner/bookings/show', [
            'page_title' => 'การจอง #' . $row['code'],
            'b' => $row, 'payments' => $payments,
        ], 'layouts/owner');
    }

    public function updateStatus(int $id): void
    {
        $row = $this->fetchOwnedBooking($id);
        if (!$row) { http_response_code(404); View::render('errors/404'); return; }
        $status = $_POST['status'] ?? '';
        if (!in_array($status, ['confirmed','rejected','cancelled','completed','no_show','pending'])) {
            Session::flash('error', 'สถานะไม่ถูกต้อง'); back();
        }

        if ($status === 'confirmed') {
            $sendLine = !empty($_POST['send_line_confirm']);
            BookingService::confirmAndNotify($id, $sendLine);
        } else {
            Database::update('bookings', ['status' => $status], 'id = :i', ['i' => $id]);
        }

        Session::flash('success', 'อัปเดตสถานะเป็น ' . $status . ' เรียบร้อย');
        if (($_POST['return_to'] ?? '') === 'dashboard') {
            $q = array_filter([
                'cal_p' => (int)($_POST['cal_p'] ?? 0),
                'cal_u' => (int)($_POST['cal_u'] ?? 0),
                'cal_m' => (int)($_POST['cal_m'] ?? 0),
                'cal_y' => (int)($_POST['cal_y'] ?? 0),
            ]);
            $calView = ($_POST['cal_view'] ?? '');
            if ($calView === 'unit') {
                $q['cal_view'] = 'unit';
            }
            redirect(url('/owner/dashboard') . ($q ? '?' . http_build_query($q) : ''));
        }
        redirect(url('/owner/bookings/' . $id));
    }

    public function verifyPayment(int $id): void
    {
        $row = $this->fetchOwnedBooking($id);
        if (!$row) { http_response_code(404); View::render('errors/404'); return; }

        $action = $_POST['action'] ?? 'verify';
        $payId  = (int)($_POST['payment_id'] ?? 0);

        if ($payId) {
            $newStatus = $action === 'reject' ? 'rejected' : 'verified';
            Database::update('booking_payments', [
                'status' => $newStatus,
                'verified_at' => date('Y-m-d H:i:s'),
                'verified_by' => Auth::id(),
            ], 'id = :i AND booking_id = :b', ['i' => $payId, 'b' => $id]);

            if ($newStatus === 'verified') {
                Database::update('bookings', ['payment_status' => 'paid', 'status' => 'confirmed'], 'id = :i', ['i' => $id]);
                Session::flash('success', 'ยืนยันการชำระเงินเรียบร้อย และอัปเดตสถานะเป็น confirmed');
            } else {
                Session::flash('success', 'ปฏิเสธสลิปเรียบร้อย');
            }
        }

        redirect(url('/owner/bookings/' . $id));
    }

    /** GET /owner/api/booking-quote?unit_id=&check_in=&check_out=&guest_count= */
    public function quote(): void
    {
        $unitId     = (int)($_GET['unit_id'] ?? 0);
        $checkIn    = trim((string)($_GET['check_in'] ?? ''));
        $checkOut   = trim((string)($_GET['check_out'] ?? ''));
        $guestCount = max(1, (int)($_GET['guest_count'] ?? 1));

        if (!$unitId || !$checkIn || !$checkOut) {
            $this->json(['error' => 'ข้อมูลไม่ครบ'], 400);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkIn) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkOut)
            || strtotime($checkOut) <= strtotime($checkIn)) {
            $this->json(['error' => 'วันที่ไม่ถูกต้อง'], 400);
        }

        $unit = PropertyUnit::find($unitId);
        if (!$unit) {
            $this->json(['error' => 'ไม่พบยูนิต'], 404);
        }

        $ownerId  = Auth::ownerId();
        $property = Database::fetch('SELECT owner_id FROM properties WHERE id = :i LIMIT 1', ['i' => $unit['property_id']]);
        if (!$property || (!Auth::isAdmin() && $ownerId && (int)$property['owner_id'] !== $ownerId)) {
            $this->json(['error' => 'ไม่มีสิทธิ์'], 403);
        }

        $calc = BookingService::calculate($unit, $checkIn, $checkOut, $guestCount);
        $this->json([
            'total'    => $calc['total'],
            'subtotal' => $calc['subtotal'],
            'nights'   => $calc['nights'],
            'discount' => $calc['discount'],
        ]);
    }

    /** GET /owner/api/line-contacts?property_id=N — ลูกค้า LINE ที่รู้จักจาก property OA */
    public function lineContacts(): void
    {
        $propertyId = (int)($_GET['property_id'] ?? 0);
        if (!$propertyId) { $this->json([]); }

        $ownerId  = Auth::ownerId();
        $property = Database::fetch("SELECT id, owner_id FROM properties WHERE id = :i LIMIT 1", ['i' => $propertyId]);
        if (!$property || (!Auth::isAdmin() && $ownerId && (int)$property['owner_id'] !== $ownerId)) {
            $this->json([]);
        }

        $contacts = Database::fetchAll(
            "SELECT line_user_id, display_name, picture_url, last_seen_at
             FROM property_line_contacts
             WHERE property_id = :p AND unfollowed_at IS NULL
             ORDER BY last_seen_at DESC
             LIMIT 200",
            ['p' => $propertyId]
        );
        $this->json($contacts);
    }

    /** GET /owner/bookings/create */
    public function create(): void
    {
        $ownerId = Auth::ownerId();
        $properties = $ownerId
            ? Database::fetchAll(
                "SELECT p.id, p.name, p.type FROM properties p
                 WHERE p.owner_id = :o AND p.status = 'published' ORDER BY p.name",
                ['o' => $ownerId]
              )
            : Database::fetchAll("SELECT id, name, type FROM properties WHERE status='published' ORDER BY name");

        // units keyed by property_id
        $unitsByProperty = [];
        if (!empty($properties)) {
            $pids = implode(',', array_map(fn($p) => (int)$p['id'], $properties));
            $units = Database::fetchAll(
                "SELECT id, property_id, name, price, price_weekend, capacity_max, total_units
                 FROM property_units WHERE property_id IN ($pids) AND is_active=1
                 ORDER BY sort_order, id"
            );
            foreach ($units as $u) {
                $unitsByProperty[(int)$u['property_id']][] = $u;
            }
        }

        View::render('owner/bookings/form', [
            'page_title'       => 'บันทึกการจองใหม่',
            'properties'       => $properties,
            'unitsByProperty'  => $unitsByProperty,
            'booking'          => null,
        ], 'layouts/owner');
    }

    /** POST /owner/bookings */
    public function store(): void
    {
        $ownerId = Auth::ownerId();
        $input   = $this->input();

        $propertyId = (int)($input['property_id'] ?? 0);
        $unitId     = (int)($input['unit_id'] ?? 0);

        // Verify ownership
        $property = Database::fetch("SELECT * FROM properties WHERE id = :i", ['i' => $propertyId]);
        if (!$property || (!Auth::isAdmin() && $ownerId && (int)$property['owner_id'] !== $ownerId)) {
            Session::flash('error', 'ไม่พบที่พักหรือไม่มีสิทธิ์');
            back();
        }

        $unit = PropertyUnit::find($unitId);
        if (!$unit || (int)$unit['property_id'] !== $propertyId) {
            Session::flash('error', 'กรุณาเลือกยูนิตให้ถูกต้อง');
            Session::withOld($input);
            back();
        }

        $guestName  = trim((string)($input['guest_name'] ?? ''));
        $guestPhone = trim((string)($input['guest_phone'] ?? ''));
        $checkIn    = trim((string)($input['check_in'] ?? ''));
        $checkOut   = trim((string)($input['check_out'] ?? ''));
        $guestCount = max(1, (int)($input['guest_count'] ?? 1));

        if (!$guestName || !$guestPhone || !$checkIn || !$checkOut) {
            Session::flash('error', 'กรุณากรอกข้อมูลที่จำเป็นให้ครบ');
            Session::withOld($input);
            back();
        }
        if (strtotime($checkOut) <= strtotime($checkIn)) {
            Session::flash('error', 'วันเช็คเอาท์ต้องหลังวันเช็คอิน');
            Session::withOld($input);
            back();
        }

        $source = in_array($input['source'] ?? '', ['manual_phone', 'manual_line', 'admin'], true)
            ? $input['source'] : 'manual_phone';
        $sendLine = !empty($input['send_line_confirm']);

        try {
            $totalPrice = isset($input['total_price']) && $input['total_price'] !== ''
                ? max(0, (float)$input['total_price']) : null;
            $deposit = isset($input['deposit_amount']) && $input['deposit_amount'] !== ''
                ? max(0, (float)$input['deposit_amount']) : null;

            $bookingId = OwnerBookingService::createManual([
                'property_id'        => $propertyId,
                'unit_id'            => $unitId,
                'guest_name'         => $guestName,
                'guest_phone'        => $guestPhone,
                'guest_email'        => trim((string)($input['guest_email'] ?? '')) ?: null,
                'guest_count'        => $guestCount,
                'check_in'           => $checkIn,
                'check_out'          => $checkOut,
                'total_price'        => $totalPrice,
                'deposit_amount'     => $deposit,
                'notes'              => trim((string)($input['notes'] ?? '')) ?: null,
                'source'             => $source,
                'guest_line_user_id' => trim((string)($input['guest_line_user_id'] ?? '')) ?: null,
                'send_line_confirm'  => $sendLine,
            ]);
            Session::flash('success', 'บันทึกการจองเรียบร้อย' . ($sendLine ? ' และส่งใบยืนยันแล้ว' : ''));
            redirect(url('/owner/bookings/' . $bookingId));
        } catch (\Throwable $e) {
            error_log('[OwnerBooking] manual save: ' . $e->getMessage());
            Session::flash('error', 'บันทึกการจองไม่สำเร็จ — ' . $e->getMessage());
            Session::withOld($input);
            back();
        }
    }

    private function fetchOwnedBooking(int $id): ?array
    {
        [$ownerWhere, $ownerParams] = $this->whereOwner();
        return Database::fetch(
            "SELECT b.*, p.name AS property_name, p.cover_image AS property_cover, p.slug AS property_slug,
                    u.name AS unit_name
             FROM bookings b
             JOIN properties p ON p.id = b.property_id
             LEFT JOIN property_units u ON u.id = b.unit_id
             WHERE b.id = :i $ownerWhere LIMIT 1",
            array_merge(['i' => $id], $ownerParams)
        );
    }
}
