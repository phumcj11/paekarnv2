<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\User;

class CustomerController extends Controller
{
    public function index(): void
    {
        $perPage = (int)config('app.paginate.admin', 20);
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $offset  = ($page - 1) * $perPage;

        $where  = "u.role = 'customer'";
        $params = [];

        if (!empty($_GET['status']) && in_array($_GET['status'], ['active', 'suspended', 'pending'], true)) {
            $where .= ' AND u.status = :st';
            $params['st'] = $_GET['status'];
        }
        if (!empty($_GET['period']) && $_GET['period'] === '7d') {
            $where .= ' AND u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
        } elseif (!empty($_GET['period']) && $_GET['period'] === '30d') {
            $where .= ' AND u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
        }
        if (!empty($_GET['q'])) {
            $where .= ' AND (u.name LIKE :q OR u.email LIKE :q OR u.phone LIKE :q)';
            $params['q'] = '%' . trim((string)$_GET['q']) . '%';
        }

        $total = (int)Database::fetch(
            "SELECT COUNT(*) c FROM users u LEFT JOIN customers c ON c.user_id = u.id WHERE $where",
            $params
        )['c'];

        $rows = Database::fetchAll(
            "SELECT c.id, c.user_id, c.gender, c.province, c.line_id,
                    u.name, u.email, u.phone, u.status, u.created_at, u.last_login_at,
                    (SELECT COUNT(*) FROM bookings b WHERE b.customer_id = c.id) AS booking_count,
                    (SELECT COUNT(*) FROM coupons cp WHERE cp.customer_id = c.id) AS coupon_count
             FROM users u
             LEFT JOIN customers c ON c.user_id = u.id
             WHERE $where
             ORDER BY u.created_at DESC
             LIMIT $perPage OFFSET $offset",
            $params
        );

        foreach ($rows as &$row) {
            if (empty($row['id']) && !empty($row['user_id'])) {
                $row['id'] = Customer::ensureProfile((int)$row['user_id']);
            }
        }
        unset($row);

        $stats = [
            'total'   => (int)Database::fetch("SELECT COUNT(*) c FROM users WHERE role='customer'")['c'],
            'active'  => (int)Database::fetch("SELECT COUNT(*) c FROM users WHERE role='customer' AND status='active'")['c'],
            'new_7d'  => (int)Database::fetch(
                "SELECT COUNT(*) c FROM users WHERE role='customer' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            )['c'],
            'new_30d' => (int)Database::fetch(
                "SELECT COUNT(*) c FROM users WHERE role='customer' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )['c'],
        ];

        View::render('admin/customers/index', [
            'page_title' => 'ลูกค้า',
            'rows'       => $rows,
            'stats'      => $stats,
            'page'       => $page,
            'totalPages' => max(1, (int)ceil($total / $perPage)),
            'total'      => $total,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        View::render('admin/customers/form', [
            'page_title' => 'เพิ่มลูกค้า',
            'record'     => null,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $data = $this->validate([
            'name'             => 'required|max:120',
            'email'            => 'required|email|max:160',
            'phone'            => 'required|phone',
            'password'         => 'required|min:8',
            'password_confirm' => 'required|same:password',
            'status'           => 'required|in:active,suspended,pending',
        ]);

        if (User::findByEmail($data['email'])) {
            Session::flash('error', 'อีเมลนี้ถูกใช้งานแล้ว');
            Session::withOld($_POST);
            back();
        }

        $userId = User::create([
            'role'     => 'customer',
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'status'   => $data['status'],
        ]);

        $customerId = Database::insert('customers', [
            'user_id'   => $userId,
            'gender'    => $this->nullableGender($_POST['gender'] ?? null),
            'birthdate' => $this->nullableDate($_POST['birthdate'] ?? null),
            'address'   => trim((string)($_POST['address'] ?? '')) ?: null,
            'province'  => trim((string)($_POST['province'] ?? '')) ?: null,
            'line_id'   => trim((string)($_POST['line_id'] ?? '')) ?: null,
        ]);

        AuditLog::record('admin_customer_created', ['email' => $data['email']], 'customer', $customerId);
        Session::flash('success', 'สร้างลูกค้าเรียบร้อย');
        redirect(url('/admin/customers/' . $customerId));
    }

    public function show(int $id): void
    {
        $customer = Customer::findWithUser($id);
        if (!$customer) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $bookings = Database::fetchAll(
            "SELECT b.id, b.code, b.status, b.check_in, b.check_out, b.total_price, b.created_at, p.name AS property_name
             FROM bookings b
             LEFT JOIN properties p ON p.id = b.property_id
             WHERE b.customer_id = :cid
             ORDER BY b.created_at DESC LIMIT 10",
            ['cid' => $id]
        );
        $coupons = Database::fetchAll(
            "SELECT id, code, status, face_value, sale_price, expires_at, used_at
             FROM coupons WHERE customer_id = :cid ORDER BY id DESC LIMIT 10",
            ['cid' => $id]
        );

        View::render('admin/customers/show', [
            'page_title' => 'ลูกค้า — ' . $customer['name'],
            'customer'   => $customer,
            'bookings'   => $bookings,
            'coupons'    => $coupons,
        ], 'layouts/admin');
    }

    public function edit(int $id): void
    {
        $record = Customer::findWithUser($id);
        if (!$record) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        View::render('admin/customers/form', [
            'page_title' => 'แก้ไขลูกค้า: ' . $record['name'],
            'record'     => $record,
        ], 'layouts/admin');
    }

    public function update(int $id): void
    {
        $record = Customer::findWithUser($id);
        if (!$record) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $rules = [
            'name'   => 'required|max:120',
            'email'  => 'required|email|max:160',
            'phone'  => 'required|phone',
            'status' => 'required|in:active,suspended,pending',
        ];
        $pwd = trim((string)($_POST['password'] ?? ''));
        if ($pwd !== '') {
            $rules['password'] = 'required|min:8';
            $rules['password_confirm'] = 'required|same:password';
        }
        $data = $this->validate($rules);

        $other = User::findByEmail($data['email']);
        if ($other && (int)$other['id'] !== (int)$record['user_id']) {
            Session::flash('error', 'อีเมลนี้ถูกใช้งานแล้ว');
            Session::withOld($_POST);
            back();
        }

        $userRow = [
            'name'   => $data['name'],
            'email'  => $data['email'],
            'phone'  => $data['phone'],
            'status' => $data['status'],
        ];
        if ($pwd !== '') {
            $userRow['password'] = password_hash($pwd, PASSWORD_BCRYPT);
        }
        User::update((int)$record['user_id'], $userRow);

        Database::update('customers', [
            'gender'    => $this->nullableGender($_POST['gender'] ?? null),
            'birthdate' => $this->nullableDate($_POST['birthdate'] ?? null),
            'address'   => trim((string)($_POST['address'] ?? '')) ?: null,
            'province'  => trim((string)($_POST['province'] ?? '')) ?: null,
            'line_id'   => trim((string)($_POST['line_id'] ?? '')) ?: null,
        ], 'id = :id', ['id' => $id]);

        AuditLog::record('admin_customer_updated', ['customer_id' => $id], 'customer', $id);
        Session::flash('success', 'บันทึกการเปลี่ยนแปลงเรียบร้อย');
        redirect(url('/admin/customers/' . $id));
    }

    public function destroy(int $id): void
    {
        $record = Customer::findWithUser($id);
        if (!$record) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        if ((int)$record['user_id'] === (int)Auth::id()) {
            Session::flash('error', 'ไม่สามารถลบบัญชีที่ล็อกอินอยู่ได้');
            redirect(url('/admin/customers/' . $id));
        }

        User::destroy((int)$record['user_id']);
        AuditLog::record('admin_customer_deleted', ['customer_id' => $id, 'email' => $record['email']], 'customer', $id);
        Session::flash('success', 'ลบลูกค้าและบัญชีผู้ใช้เรียบร้อย');
        redirect(url('/admin/customers'));
    }

    private function nullableGender(?string $value): ?string
    {
        $v = trim((string)$value);
        return in_array($v, ['male', 'female', 'other'], true) ? $v : null;
    }

    private function nullableDate(?string $value): ?string
    {
        $v = trim((string)$value);
        if ($v === '') {
            return null;
        }
        $ts = strtotime($v);
        return $ts ? date('Y-m-d', $ts) : null;
    }
}
