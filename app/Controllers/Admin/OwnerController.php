<?php
namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\User;
use App\Models\AuditLog;

class OwnerController extends Controller
{
    public function index(): void
    {
        $rows = Database::fetchAll(
            "SELECT o.*, u.name, u.email, u.phone,
                    (SELECT COUNT(*) FROM properties p WHERE p.owner_id=o.id) AS property_count
             FROM owners o JOIN users u ON u.id=o.user_id ORDER BY o.id DESC"
        );
        View::render('admin/owners/index', [
            'page_title' => 'เจ้าของแพ',
            'rows'       => $rows,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        View::render('admin/owners/form', [
            'page_title' => 'เพิ่มเจ้าของแพ',
            'record'     => null,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $data = $this->validate([
            'name'               => 'required|max:120',
            'email'              => 'required|email|max:160',
            'phone'              => 'required|phone',
            'business_name'      => 'required|max:160',
            'password'           => 'required|min:8',
            'password_confirm'   => 'required|same:password',
            'partner_status'     => 'required|in:pending,active,paused,terminated',
        ]);

        if (User::findByEmail($data['email'])) {
            Session::flash('error', 'อีเมลนี้ถูกใช้งานแล้ว');
            Session::withOld($_POST);
            back();
        }

        $discount    = max(0.0, min(100.0, (float)($_POST['discount_agreement'] ?? 10)));
        $commission  = max(0.0, min(100.0, (float)($_POST['commission_rate'] ?? 0)));

        $userId = User::create([
            'role'     => 'owner',
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'status'   => 'active',
        ]);

        $ownerId = Database::insert('owners', [
            'user_id'            => $userId,
            'business_name'      => $data['business_name'],
            'tax_id'             => trim((string)($_POST['tax_id'] ?? '')) ?: null,
            'bank_name'          => trim((string)($_POST['bank_name'] ?? '')) ?: null,
            'bank_account'       => trim((string)($_POST['bank_account'] ?? '')) ?: null,
            'bank_holder'        => trim((string)($_POST['bank_holder'] ?? '')) ?: null,
            'partner_status'     => $data['partner_status'],
            'discount_agreement' => $discount,
            'commission_rate'    => $commission,
            'notes'              => trim((string)($_POST['notes'] ?? '')) ?: null,
        ]);

        Session::flash('success', 'สร้างเจ้าของแพเรียบร้อย');
        redirect(url('/admin/owners/' . $ownerId));
    }

    public function show(int $id): void
    {
        $owner = Database::fetch(
            "SELECT o.*, u.name, u.email, u.phone, u.id AS user_id FROM owners o
             JOIN users u ON u.id=o.user_id WHERE o.id = :id",
            ['id' => $id]
        );
        if (!$owner) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        $properties = Database::fetchAll(
            'SELECT * FROM properties WHERE owner_id = :id ORDER BY created_at DESC',
            ['id' => $id]
        );
        View::render('admin/owners/show', [
            'page_title' => 'เจ้าของแพ — ' . $owner['name'],
            'owner'      => $owner,
            'properties' => $properties,
        ], 'layouts/admin');
    }

    public function edit(int $id): void
    {
        $record = Database::fetch(
            "SELECT o.*, u.name, u.email, u.phone, u.id AS user_id FROM owners o
             JOIN users u ON u.id=o.user_id WHERE o.id = :id",
            ['id' => $id]
        );
        if (!$record) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        View::render('admin/owners/form', [
            'page_title' => 'แก้ไขเจ้าของแพ: ' . $record['name'],
            'record'     => $record,
        ], 'layouts/admin');
    }

    public function update(int $id): void
    {
        $record = Database::fetch(
            'SELECT o.*, u.id AS user_id FROM owners o JOIN users u ON u.id=o.user_id WHERE o.id = :id',
            ['id' => $id]
        );
        if (!$record) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $rules = [
            'name'           => 'required|max:120',
            'email'          => 'required|email|max:160',
            'phone'          => 'required|phone',
            'business_name'  => 'required|max:160',
            'partner_status' => 'required|in:pending,active,paused,terminated',
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

        $discount   = max(0.0, min(100.0, (float)($_POST['discount_agreement'] ?? 0)));
        $commission = max(0.0, min(100.0, (float)($_POST['commission_rate'] ?? 0)));

        $tier = trim((string)($_POST['membership_tier'] ?? 'none'));
        if (!in_array($tier, ['none', 'standard', 'vip'], true)) {
            $tier = 'none';
        }

        $expAt = null;
        $graceAt = null;
        if ($tier !== 'none') {
            $expRaw = trim((string)($_POST['membership_expires_at'] ?? ''));
            $graceRaw = trim((string)($_POST['membership_grace_until'] ?? ''));
            if ($expRaw !== '') {
                $ts = strtotime(str_replace('T', ' ', $expRaw));
                $expAt = $ts ? date('Y-m-d H:i:s', $ts) : null;
            }
            if ($graceRaw !== '') {
                $ts = strtotime(str_replace('T', ' ', $graceRaw));
                $graceAt = $ts ? date('Y-m-d H:i:s', $ts) : null;
            }
        }

        $beforeMem = [
            'membership_tier'        => $record['membership_tier'] ?? 'none',
            'membership_expires_at'  => $record['membership_expires_at'] ?? null,
            'membership_grace_until' => $record['membership_grace_until'] ?? null,
        ];

        $userRow = [
            'name'  => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
        ];
        if ($pwd !== '') {
            $userRow['password'] = password_hash($pwd, PASSWORD_BCRYPT);
        }
        User::update((int)$record['user_id'], $userRow);

        Database::update('owners', [
            'business_name'      => $data['business_name'],
            'tax_id'             => trim((string)($_POST['tax_id'] ?? '')) ?: null,
            'bank_name'          => trim((string)($_POST['bank_name'] ?? '')) ?: null,
            'bank_account'       => trim((string)($_POST['bank_account'] ?? '')) ?: null,
            'bank_holder'        => trim((string)($_POST['bank_holder'] ?? '')) ?: null,
            'partner_status'     => $data['partner_status'],
            'discount_agreement' => $discount,
            'commission_rate'    => $commission,
            'notes'              => trim((string)($_POST['notes'] ?? '')) ?: null,
            'membership_tier'          => $tier,
            'membership_expires_at'    => $tier === 'none' ? null : $expAt,
            'membership_grace_until'   => $tier === 'none' ? null : $graceAt,
        ], 'id = :id', ['id' => $id]);

        $afterMem = [
            'membership_tier'          => $tier,
            'membership_expires_at'    => $tier === 'none' ? null : $expAt,
            'membership_grace_until'   => $tier === 'none' ? null : $graceAt,
        ];
        if ($beforeMem !== $afterMem) {
            AuditLog::record(
                'admin_owner_membership_adjust',
                [
                    'before' => $beforeMem,
                    'after'  => $afterMem,
                    'reason' => trim((string)($_POST['membership_adjust_reason'] ?? '')),
                ],
                'owner',
                $id
            );
        }

        Session::flash('success', 'บันทึกการเปลี่ยนแปลงเรียบร้อย');
        redirect(url('/admin/owners/' . $id));
    }

    public function destroy(int $id): void
    {
        $owner = Database::fetch('SELECT id, user_id FROM owners WHERE id = :id', ['id' => $id]);
        if (!$owner) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }
        if ((int)$owner['user_id'] === (int)Auth::id()) {
            Session::flash('error', 'ไม่สามารถลบบัญชีที่ล็อกอินอยู่ได้');
            redirect(url('/admin/owners/' . $id));
        }
        User::destroy((int)$owner['user_id']);
        Session::flash('success', 'ลบเจ้าของแพและบัญชีผู้ใช้เรียบร้อย (ที่พักเดิมจะไม่มีเจ้าของชั่วคราวหากระบบตั้งค่าไว้เช่นนั้น)');
        redirect(url('/admin/owners'));
    }

    public function status(int $id): void
    {
        $status = $_POST['status'] ?? 'active';
        if (!in_array($status, ['pending', 'active', 'paused', 'terminated'], true)) {
            $status = 'active';
        }
        Database::update('owners', ['partner_status' => $status], 'id = :i', ['i' => $id]);
        Session::flash('success', 'อัปเดตสถานะ partner เป็น ' . $status);
        redirect(url('/admin/owners/' . $id));
    }
}
