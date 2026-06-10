<?php
namespace App\Controllers\Owner;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\Property;
use App\Models\PropertyUnit;
use App\Services\BookingService;
use App\Services\OwnerAvailabilityCalendar;

class AvailabilityController extends Controller
{
    private function findOwn(int $id): ?array
    {
        $p = Property::find($id);
        if (!$p) return null;
        if (Auth::isAdmin()) return $p;
        $oid = Auth::ownerId();
        return ($oid && (int)$p['owner_id'] === (int)$oid) ? $p : null;
    }

    public function index(int $id): void
    {
        $property = $this->findOwn($id);
        if (!$property) { http_response_code(404); View::render('errors/404'); return; }

        $units = Database::fetchAll(
            "SELECT id, name, total_units, moderation_status FROM property_units WHERE property_id = :p AND is_active=1 ORDER BY sort_order, id",
            ['p' => $id]
        );

        $month  = isset($_GET['month']) ? max(1, min(12, (int)$_GET['month'])) : (int)date('n');
        $year   = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $unitId = isset($_GET['unit']) ? (int)$_GET['unit'] : (int)($units[0]['id'] ?? 0);

        $selectedUnit = null;
        foreach ($units as $u) {
            if ((int)$u['id'] === $unitId) { $selectedUnit = $u; break; }
        }
        $totalUnits = max(1, (int)($selectedUnit['total_units'] ?? 1));

        $cal = $unitId
            ? OwnerAvailabilityCalendar::buildMonth($unitId, $month, $year, $totalUnits)
            : ['dayMeta' => [], 'bookingsByDate' => [], 'availMap' => [], 'daysInMonth' => (int)date('t'), 'startWeekday' => 0];
        $dayMeta        = $cal['dayMeta'];
        $bookingsByDate = $cal['bookingsByDate'];
        $availMap       = $cal['availMap'];

        View::render('owner/availability/index', [
            'page_title' => 'ปฏิทินวันว่าง: ' . $property['name'],
            'property' => $property, 'units' => $units, 'unitId' => $unitId,
            'month' => $month, 'year' => $year, 'availMap' => $availMap,
            'dayMeta' => $dayMeta, 'totalUnits' => $totalUnits,
            'bookingsByDate' => $bookingsByDate,
        ], 'layouts/owner');
    }

    public function save(int $id): void
    {
        $property = $this->findOwn($id);
        if (!$property) { http_response_code(404); View::render('errors/404'); return; }

        $unitId = (int)($_POST['unit_id'] ?? 0);
        $u = Database::fetch("SELECT id FROM property_units WHERE id = :i AND property_id = :p", ['i' => $unitId, 'p' => $id]);
        if (!$u) { Session::flash('error', 'ไม่พบห้อง'); back(); }

        $dates  = (array)($_POST['dates'] ?? []);
        $status = $_POST['status'] ?? 'open';
        $available = (int)($_POST['available_units'] ?? 1);

        if (!in_array($status, ['open','closed','fully_booked','blocked'])) $status = 'open';

        foreach ($dates as $d) {
            $d = trim($d);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) continue;

            $exists = Database::fetch("SELECT id FROM availability WHERE unit_id = :u AND date = :d", ['u' => $unitId, 'd' => $d]);
            if ($exists) {
                Database::update('availability', ['status' => $status, 'available_units' => $available], 'id = :i', ['i' => $exists['id']]);
            } else {
                Database::insert('availability', [
                    'unit_id' => $unitId, 'date' => $d, 'status' => $status, 'available_units' => $available,
                ]);
            }
        }

        Session::flash('success', 'อัปเดตวันว่างเรียบร้อย ' . count($dates) . ' วัน');
        $month = (int)($_POST['month'] ?? date('n'));
        $year  = (int)($_POST['year']  ?? date('Y'));
        redirect(url('/owner/properties/' . $id . '/availability') . '?unit=' . $unitId . '&month=' . $month . '&year=' . $year);
    }

    /** POST — บันทึกการจองจากปฏิทิน (มีลูกค้าจอง) */
    public function storeBooking(int $id): void
    {
        $property = $this->findOwn($id);
        if (!$property) { http_response_code(404); View::render('errors/404'); return; }

        $unitId     = (int)($_POST['unit_id'] ?? 0);
        $guestName  = trim((string)($_POST['guest_name'] ?? ''));
        $guestPhone = trim((string)($_POST['guest_phone'] ?? ''));
        $checkIn    = trim((string)($_POST['check_in'] ?? ''));
        $checkOut   = trim((string)($_POST['check_out'] ?? ''));
        $month      = (int)($_POST['month'] ?? date('n'));
        $year       = (int)($_POST['year'] ?? date('Y'));

        $unit = PropertyUnit::find($unitId);
        if (!$unit || (int)$unit['property_id'] !== $id) {
            Session::flash('error', 'กรุณาเลือกยูนิตให้ถูกต้อง');
            redirect(url('/owner/properties/' . $id . '/availability') . '?unit=' . $unitId . '&month=' . $month . '&year=' . $year);
        }

        if (!$guestName || !$guestPhone || !$checkIn || !$checkOut) {
            Session::flash('error', 'กรุณากรอกชื่อ โทรศัพท์ และช่วงวันพักให้ครบ');
            redirect(url('/owner/properties/' . $id . '/availability') . '?unit=' . $unitId . '&month=' . $month . '&year=' . $year);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkIn) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkOut)
            || strtotime($checkOut) <= strtotime($checkIn)) {
            Session::flash('error', 'วันเช็คเอาท์ต้องหลังวันเช็คอิน');
            redirect(url('/owner/properties/' . $id . '/availability') . '?unit=' . $unitId . '&month=' . $month . '&year=' . $year);
        }

        $calc = BookingService::calculate($unit, $checkIn, $checkOut, 1);
        $bookingId = BookingService::create([
            'property_id'        => $id,
            'unit_id'            => $unitId,
            'mode'               => 'manual',
            'guest_name'         => $guestName,
            'guest_phone'        => $guestPhone,
            'guest_email'        => null,
            'guest_count'        => 1,
            'check_in'           => $checkIn,
            'check_out'          => $checkOut,
            'nights'             => $calc['nights'],
            'subtotal'           => $calc['subtotal'],
            'discount'           => 0,
            'total_price'        => $calc['total'],
            'status'             => 'confirmed',
            'payment_status'     => 'unpaid',
            'notes'              => 'บันทึกจากปฏิทินวันว่าง',
            'source'             => 'manual_phone',
            'guest_line_user_id' => null,
            'created_by_user_id' => Auth::id(),
        ]);

        $code = Database::fetch('SELECT code FROM bookings WHERE id = :i', ['i' => $bookingId])['code'] ?? '';
        Session::flash('success', 'บันทึกการจอง #' . $code . ' เรียบร้อย');
        redirect(url('/owner/properties/' . $id . '/availability') . '?unit=' . $unitId . '&month=' . $month . '&year=' . $year);
    }
}
