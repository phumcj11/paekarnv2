<?php
namespace App\Controllers\Owner;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;

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
        Database::update('bookings', ['status' => $status], 'id = :i', ['i' => $id]);
        Session::flash('success', 'อัปเดตสถานะเป็น ' . $status . ' เรียบร้อย');
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
