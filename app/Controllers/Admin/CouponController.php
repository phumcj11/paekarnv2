<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\CouponOrder;
use App\Services\CouponService;

class CouponController extends Controller
{
    public function index(): void
    {
        $perPage = (int)config('app.paginate.admin', 20);
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        $where = "1=1"; $params = [];
        if (!empty($_GET['status'])) { $where .= " AND status = :st"; $params['st'] = $_GET['status']; }
        if (!empty($_GET['q']))      { $where .= " AND (code LIKE :q OR phone LIKE :q)"; $params['q'] = '%'.$_GET['q'].'%'; }

        $rows = Database::fetchAll("SELECT * FROM coupons WHERE $where ORDER BY id DESC LIMIT $perPage OFFSET $offset", $params);
        $total = (int)Database::fetch("SELECT COUNT(*) c FROM coupons WHERE $where", $params)['c'];
        $stats = [
            'total'      => (int)Database::fetch('SELECT COUNT(*) c FROM coupons')['c'],
            'unused'     => (int)Database::fetch("SELECT COUNT(*) c FROM coupons WHERE status='unused'")['c'],
            'reserved'   => (int)Database::fetch("SELECT COUNT(*) c FROM coupons WHERE status='reserved'")['c'],
            'used'       => (int)Database::fetch("SELECT COUNT(*) c FROM coupons WHERE status='used'")['c'],
            'expired'    => (int)Database::fetch(
                "SELECT COUNT(*) c FROM coupons WHERE status='expired' OR (status IN ('unused','reserved') AND expires_at < NOW())"
            )['c'],
            'revoked'    => (int)Database::fetch("SELECT COUNT(*) c FROM coupons WHERE status='revoked'")['c'],
            'cancelled'  => (int)Database::fetch("SELECT COUNT(*) c FROM coupons WHERE status='cancelled'")['c'],
            'revenue'    => (float)Database::fetch(
                "SELECT COALESCE(SUM(c.sale_price),0) s FROM coupons c
                 INNER JOIN coupon_orders o ON o.id = c.order_id
                 WHERE o.status = 'paid'"
            )['s'],
        ];
        View::render('admin/coupons/index', [
            'page_title' => 'คูปอง', 'rows' => $rows, 'total' => $total,
            'page' => $page, 'totalPages' => max(1,(int)ceil($total/$perPage)),
            'stats' => $stats,
        ], 'layouts/admin');
    }

    public function exportCsv(): void
    {
        AuditLog::record('admin_coupons_export_csv', []);
        $limit = max(100, min(50000, (int)config('app.admin_export_max_rows', 5000)));
        $rows = Database::fetchAll("SELECT * FROM coupons ORDER BY id DESC LIMIT $limit");

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="coupons-' . date('Ymd-His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        $headers = ['code', 'phone', 'face_value', 'sale_price', 'status', 'issued_at', 'expires_at', 'used_at', 'order_id', 'customer_id'];
        fputcsv($out, $headers);
        foreach ($rows as $c) {
            $line = [];
            foreach ($headers as $h) {
                $line[] = isset($c[$h]) ? (string)$c[$h] : '';
            }
            fputcsv($out, $line);
        }
        fclose($out);
        exit;
    }

    public function create(): void
    {
        View::render('admin/coupons/form', [
            'page_title' => 'ออกคูปองใหม่',
            'row'        => null,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $data = $this->validate([
            'buyer_name'  => 'required|max:120',
            'buyer_phone' => 'required|phone',
            'buyer_email' => 'email',
        ]);

        $quantity = max(1, min(20, (int)($_POST['quantity'] ?? 1)));
        $result = CouponService::adminIssue([
            'buyer_name'     => $data['buyer_name'],
            'buyer_phone'    => $data['buyer_phone'],
            'buyer_email'    => $data['buyer_email'] ?? null,
            'customer_id'    => !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null,
            'face_value'     => ($_POST['face_value'] ?? '') !== '' ? (float)$_POST['face_value'] : null,
            'sale_price'     => ($_POST['sale_price'] ?? '') !== '' ? (float)$_POST['sale_price'] : null,
            'quantity'       => $quantity,
            'validity_days'  => (int)($_POST['validity_days'] ?? 0) ?: null,
            'expires_at'     => trim((string)($_POST['expires_at'] ?? '')) ?: null,
            'payment_method' => $_POST['payment_method'] ?? 'cash',
            'mark_paid'      => !empty($_POST['mark_paid']),
        ]);

        AuditLog::record('admin_coupon_issued', [
            'order_id' => $result['order_id'],
            'codes'    => $result['codes'],
        ], 'coupon_order', $result['order_id']);

        Session::flash('success', 'ออกคูปอง ' . count($result['codes']) . ' ใบเรียบร้อย');
        redirect(url('/admin/coupons/orders/' . $result['order_id']));
    }

    public function show(int $id): void
    {
        $row = Coupon::find($id);
        if (!$row) { http_response_code(404); View::render('errors/404'); return; }

        $order = !empty($row['order_id']) ? CouponOrder::find((int)$row['order_id']) : null;
        $booking = null;
        if (!empty($row['used_booking_id'])) {
            $booking = Database::fetch(
                'SELECT id, code, guest_name, status FROM bookings WHERE id = :id',
                ['id' => (int)$row['used_booking_id']]
            );
        }
        $usages = Database::fetchAll(
            'SELECT cu.*, p.name AS property_name FROM coupon_usages cu
             LEFT JOIN properties p ON p.id = cu.property_id
             WHERE cu.coupon_id = :id ORDER BY cu.id DESC',
            ['id' => $id]
        );

        View::render('admin/coupons/show', [
            'page_title' => 'คูปอง ' . $row['code'],
            'row'        => $row,
            'order'      => $order,
            'booking'    => $booking,
            'usages'     => $usages,
            'canHardDelete' => in_array((string)$row['status'], ['unused', 'cancelled', 'revoked', 'expired'], true),
        ], 'layouts/admin');
    }

    public function edit(int $id): void
    {
        $row = Coupon::find($id);
        if (!$row) { http_response_code(404); View::render('errors/404'); return; }
        View::render('admin/coupons/form', [
            'page_title' => 'แก้ไขคูปอง',
            'row'        => $row,
        ], 'layouts/admin');
    }

    public function update(int $id): void
    {
        $row = Coupon::find($id);
        if (!$row) {
            Session::flash('error', 'ไม่พบคูปอง');
            back();
        }

        $expiresRaw = trim((string)($_POST['expires_at'] ?? ''));
        $expiresAt = $expiresRaw !== '' ? str_replace('T', ' ', $expiresRaw) . ':00' : null;

        if (!CouponService::adminUpdate($id, [
            'phone'        => $_POST['phone'] ?? $row['phone'],
            'face_value'   => $_POST['face_value'] ?? $row['face_value'],
            'sale_price'   => $_POST['sale_price'] ?? $row['sale_price'],
            'expires_at'   => $expiresAt,
            'customer_id'  => $_POST['customer_id'] ?? $row['customer_id'],
            'status'       => $_POST['status'] ?? $row['status'],
        ])) {
            Session::flash('error', 'ไม่สามารถบันทึกได้');
            back();
        }

        AuditLog::record('admin_coupon_updated', ['coupon_id' => $id], 'coupon', $id);
        Session::flash('success', 'บันทึกคูปองเรียบร้อย');
        redirect(url('/admin/coupons/' . $id));
    }

    public function destroy(int $id): void
    {
        $hard = !empty($_POST['hard_delete']);
        $row = Coupon::find($id);
        if (!$row) {
            Session::flash('error', 'ไม่พบคูปอง');
            back();
        }

        if (!CouponService::revoke($id, $hard)) {
            Session::flash('error', 'ไม่สามารถลบคูปองได้ (อาจถูกใช้แล้ว)');
            back();
        }

        AuditLog::record($hard ? 'admin_coupon_deleted' : 'admin_coupon_revoked', [
            'code' => $row['code'],
        ], 'coupon', $id);

        Session::flash('success', $hard ? 'ลบคูปองถาวรแล้ว' : 'เพิกถอนคูปองแล้ว');
        redirect(url('/admin/coupons'));
    }

    public function orders(): void
    {
        $perPage = (int)config('app.paginate.admin', 20);
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;
        $total = (int)Database::fetch('SELECT COUNT(*) c FROM coupon_orders')['c'];
        $rows = Database::fetchAll(
            "SELECT * FROM coupon_orders ORDER BY id DESC LIMIT $perPage OFFSET $offset"
        );

        View::render('admin/coupons/orders', [
            'page_title' => 'คำสั่งซื้อคูปอง',
            'rows'       => $rows,
            'page'       => $page,
            'totalPages' => max(1, (int)ceil($total / $perPage)),
            'total'      => $total,
        ], 'layouts/admin');
    }

    public function orderCreate(): void
    {
        View::render('admin/coupons/form', [
            'page_title' => 'สร้างคำสั่งซื้อคูปอง',
            'row'        => null,
        ], 'layouts/admin');
    }

    public function orderStore(): void
    {
        $this->store();
    }

    public function orderShow(int $id): void
    {
        $order = CouponOrder::find($id);
        if (!$order) { http_response_code(404); View::render('errors/404'); return; }

        $coupons = Database::fetchAll(
            'SELECT * FROM coupons WHERE order_id = :id ORDER BY id',
            ['id' => $id]
        );

        View::render('admin/coupons/orders/show', [
            'page_title' => 'คำสั่งซื้อ ' . $order['order_no'],
            'order'      => $order,
            'coupons'    => $coupons,
        ], 'layouts/admin');
    }

    public function orderUpdate(int $id): void
    {
        $order = CouponOrder::find($id);
        if (!$order) {
            Session::flash('error', 'ไม่พบคำสั่งซื้อ');
            back();
        }

        CouponService::adminUpdateOrder($id, [
            'buyer_name'     => $_POST['buyer_name'] ?? $order['buyer_name'],
            'buyer_phone'    => $_POST['buyer_phone'] ?? $order['buyer_phone'],
            'buyer_email'    => $_POST['buyer_email'] ?? $order['buyer_email'],
            'face_value'     => $_POST['face_value'] ?? $order['face_value'],
            'sale_price'     => $_POST['sale_price'] ?? $order['sale_price'],
            'quantity'       => $_POST['quantity'] ?? $order['quantity'],
            'total_price'    => $_POST['total_price'] ?? $order['total_price'],
            'payment_method' => $_POST['payment_method'] ?? $order['payment_method'],
            'status'         => $_POST['status'] ?? $order['status'],
        ]);

        AuditLog::record('admin_coupon_order_updated', ['order_id' => $id], 'coupon_order', $id);
        Session::flash('success', 'บันทึกคำสั่งซื้อเรียบร้อย');
        redirect(url('/admin/coupons/orders/' . $id));
    }

    public function orderCancel(int $id): void
    {
        CouponService::cancelOrder($id);
        AuditLog::record('admin_coupon_order_cancelled', ['order_id' => $id], 'coupon_order', $id);
        Session::flash('success', 'ยกเลิกคำสั่งซื้อแล้ว');
        redirect(url('/admin/coupons/orders/' . $id));
    }

    public function exportOrdersCsv(): void
    {
        AuditLog::record('admin_coupon_orders_export_csv', []);
        $limit = max(100, min(50000, (int)config('app.admin_export_max_rows', 5000)));
        $rows = Database::fetchAll("SELECT * FROM coupon_orders ORDER BY id DESC LIMIT $limit");

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="coupon-orders-' . date('Ymd-His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        $headers = [
            'order_no', 'buyer_name', 'buyer_phone', 'buyer_email',
            'face_value', 'sale_price', 'quantity', 'total_price',
            'payment_method', 'status', 'paid_at', 'created_at',
        ];
        fputcsv($out, $headers);
        foreach ($rows as $o) {
            $line = [];
            foreach ($headers as $h) {
                $line[] = isset($o[$h]) ? (string)$o[$h] : '';
            }
            fputcsv($out, $line);
        }
        fclose($out);
        exit;
    }

    public function approveOrder(int $id): void
    {
        Database::update('coupon_orders', ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $id]);
        AuditLog::record('coupon_order_approved', ['order_id' => $id], 'coupon_order', $id);
        Session::flash('success', 'อนุมัติคำสั่งซื้อเรียบร้อย');
        back();
    }

    public function setCouponStatus(int $id): void
    {
        $st = trim((string)($_POST['status'] ?? ''));
        $allowed = ['unused', 'reserved', 'used', 'expired', 'revoked', 'cancelled'];
        if (!in_array($st, $allowed, true)) {
            Session::flash('error', 'สถานะไม่ถูกต้อง');
            back();
        }
        $row = Database::fetch('SELECT id FROM coupons WHERE id = :id', ['id' => $id]);
        if (!$row) {
            Session::flash('error', 'ไม่พบคูปอง');
            back();
        }
        Database::update('coupons', ['status' => $st], 'id = :id', ['id' => $id]);
        AuditLog::record('coupon_status_set', ['coupon_id' => $id, 'to' => $st], 'coupon', $id);
        Session::flash('success', 'อัปเดตสถานะคูปองแล้ว');
        back();
    }
}
