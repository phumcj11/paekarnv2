<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\LoginThrottle;
use App\Core\Session;
use App\Core\Validator;
use App\Models\AuditLog;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth/login');
    }

    public function login(): void
    {
        $data = $this->validate([
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ]);

        $locked = LoginThrottle::lockedMessage('customer_login', $data['email']);
        if ($locked !== null) {
            Session::flash('error', $locked);
            Session::withOld(['email' => $data['email']]);
            back();
        }

        if (Auth::attempt($data['email'], $data['password'])) {
            LoginThrottle::clear('customer_login', $data['email']);
            AuditLog::record('customer_login_success', ['email' => $data['email']]);
            Session::flash('success', 'เข้าสู่ระบบสำเร็จ');
            $intended = Session::get('intended_url');
            Session::remove('intended_url');
            $role = Auth::role();
            $defaultUrl = match ($role) {
                'admin' => url('/admin'),
                'owner' => url('/owner'),
                default => url('/account'),
            };
            redirect($intended ?: $defaultUrl);
        }

        LoginThrottle::hitFailure('customer_login', $data['email']);
        AuditLog::record('customer_login_failure', ['email' => $data['email']]);
        Session::flash('error', 'อีเมลหรือรหัสผ่านไม่ถูกต้อง');
        Session::withOld(['email' => $data['email']]);
        back();
    }

    public function showRegister(): void
    {
        $this->view('auth/register');
    }

    public function register(): void
    {
        $data = $this->validate([
            'name'     => 'required|max:120',
            'email'    => 'required|email|max:160',
            'phone'    => 'required|phone',
            'password' => 'required|min:8',
            'password_confirm' => 'required|same:password',
        ]);

        if (User::findByEmail($data['email'])) {
            Session::flash('error', 'อีเมลนี้ถูกใช้งานแล้ว');
            Session::withOld($data);
            back();
        }

        $userId = User::create([
            'role'     => 'customer',
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'status'   => 'active',
        ]);
        Database::insert('customers', ['user_id' => $userId]);

        $user = User::find($userId);
        Auth::login($user);
        Session::flash('success', 'สมัครสมาชิกสำเร็จ ยินดีต้อนรับสู่แพกาญ.com');
        redirect(url('/account'));
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('success', 'ออกจากระบบเรียบร้อย');
        redirect(url('/'));
    }

    // ----- Admin login (separate page) -----
    public function showAdminLogin(): void
    {
        $this->view('auth/admin_login', [], null);
    }

    public function adminLogin(): void
    {
        $data = $this->validate([
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ]);

        $locked = LoginThrottle::lockedMessage('admin_login', $data['email']);
        if ($locked !== null) {
            Session::flash('error', $locked);
            Session::withOld(['email' => $data['email']]);
            back();
        }

        if (Auth::attempt($data['email'], $data['password'], 'admin')) {
            LoginThrottle::clear('admin_login', $data['email']);
            AuditLog::record('admin_login_success', ['email' => $data['email']]);
            Session::flash('success', 'เข้าสู่ระบบ Admin สำเร็จ');
            redirect(url('/admin'));
        }

        LoginThrottle::hitFailure('admin_login', $data['email']);
        AuditLog::record('admin_login_failure', ['email' => $data['email']]);
        Session::flash('error', 'ไม่สามารถเข้าสู่ระบบได้ ตรวจสอบสิทธิ์ Admin');
        Session::withOld(['email' => $data['email']]);
        back();
    }

    // ----- Owner Auth -----
    public function showOwnerLogin(): void
    {
        $this->view('auth/owner_login', [], null);
    }

    public function ownerLogin(): void
    {
        $data = $this->validate([
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ]);

        $locked = LoginThrottle::lockedMessage('owner_login', $data['email']);
        if ($locked !== null) {
            Session::flash('error', $locked);
            Session::withOld(['email' => $data['email']]);
            back();
        }

        // owner หรือ admin ก็เข้าได้
        $user = User::findByEmail($data['email']);
        if (!$user || $user['status'] !== 'active' || !in_array($user['role'], ['owner','admin'])) {
            LoginThrottle::hitFailure('owner_login', $data['email']);
            AuditLog::record('owner_login_failure', ['email' => $data['email'], 'reason' => 'account']);
            Session::flash('error', 'ไม่พบบัญชีเจ้าของแพ หรือยังไม่ได้รับอนุมัติ');
            Session::withOld(['email' => $data['email']]);
            back();
        }
        if (!password_verify($data['password'], $user['password'])) {
            LoginThrottle::hitFailure('owner_login', $data['email']);
            AuditLog::record('owner_login_failure', ['email' => $data['email'], 'reason' => 'password']);
            Session::flash('error', 'รหัสผ่านไม่ถูกต้อง');
            Session::withOld(['email' => $data['email']]);
            back();
        }

        \App\Core\Database::update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);
        Auth::login($user);
        AuditLog::record('owner_login_success', ['email' => $data['email'], 'user_id' => (int)$user['id']]);
        Session::flash('success', 'เข้าสู่ระบบเจ้าของกิจการสำเร็จ');
        redirect(url('/owner'));
    }

    public function showOwnerRegister(): void
    {
        $this->view('auth/owner_register', [], null);
    }

    public function ownerRegister(): void
    {
        $data = $this->validate([
            'name'     => 'required|max:120',
            'email'    => 'required|email|max:160',
            'phone'    => 'required|phone',
            'business_name' => 'required|max:160',
            'password' => 'required|min:8',
            'password_confirm' => 'required|same:password',
        ]);

        if (User::findByEmail($data['email'])) {
            Session::flash('error', 'อีเมลนี้ถูกใช้งานแล้ว');
            Session::withOld(array_merge($data, [
                'line_id' => trim((string) ($_POST['line_id'] ?? '')),
                'wants_sales_help' => !empty($_POST['wants_sales_help']) ? '1' : '',
            ]));
            back();
        }

        $userId = User::create([
            'role'     => 'owner',
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'status'   => 'active', // ในระบบจริงอาจตั้งเป็น 'pending' รอ admin approve
        ]);

        $ownerRow = [
            'user_id'        => $userId,
            'business_name'  => $data['business_name'],
            'partner_status' => 'pending',
            'discount_agreement' => 10.00,
        ];
        if (\App\Core\Database::tableHasColumn('owners', 'line_id')) {
            $ownerRow['line_id'] = trim((string) ($_POST['line_id'] ?? '')) ?: null;
        }
        if (\App\Core\Database::tableHasColumn('owners', 'wants_sales_help')) {
            $ownerRow['wants_sales_help'] = !empty($_POST['wants_sales_help']) ? 1 : 0;
        }
        $ownerId = (int) \App\Core\Database::insert('owners', $ownerRow);

        try {
            \App\Services\AdminApprovalNotifyService::partnerRegistered(
                $ownerId,
                (string) $data['business_name'],
                (string) $data['name'],
                (string) $data['email'],
                (string) $data['phone'],
                $userId,
                trim((string) ($_POST['line_id'] ?? '')),
                !empty($_POST['wants_sales_help'])
            );
        } catch (\Throwable $e) {
        }

        $user = User::find($userId);
        Auth::login($user);
        Session::flash('success', 'สมัครเจ้าของกิจการสำเร็จ! บัญชีของคุณอยู่ในสถานะรออนุมัติ — ทีมงานจะติดต่อกลับเร็วที่สุด');
        redirect(url('/owner'));
    }

    public function showProviderLogin(): void
    {
        $this->view('auth/provider_login', [], null);
    }

    public function providerLogin(): void
    {
        $data = $this->validate([
            'email'    => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (!Auth::attempt($data['email'], $data['password'], 'provider')) {
            Session::flash('error', 'อีเมลหรือรหัสผ่านไม่ถูกต้อง');
            Session::withOld(['email' => $data['email']]);
            redirect(url('/provider/login'));
        }

        Session::flash('success', 'เข้าสู่ระบบผู้ให้บริการสำเร็จ');
        redirect(url('/provider'));
    }

    public function showProviderRegister(): void
    {
        $this->view('auth/provider_register', [
            'types' => \App\Models\ActivityProvider::TYPES,
            'districts' => \App\Models\VisitorPlace::DISTRICTS,
        ], null);
    }

    public function providerRegister(): void
    {
        if (!\App\Models\ActivityProvider::tableReady()) {
            Session::flash('error', 'ระบบผู้ให้บริการยังไม่พร้อม กรุณาติดต่อทีมงาน');
            redirect(url('/contact'));
        }

        $data = $this->validate([
            'name'            => 'required|max:120',
            'email'           => 'required|email|max:160',
            'phone'           => 'required|phone',
            'business_name'   => 'required|max:180',
            'password'        => 'required|min:8',
            'password_confirm'=> 'required|same:password',
        ]);

        if (User::findByEmail($data['email'])) {
            Session::flash('error', 'อีเมลนี้ถูกใช้งานแล้ว');
            Session::withOld($_POST);
            back();
        }

        $type = (string)($_POST['type'] ?? 'car_rental');
        if (!array_key_exists($type, \App\Models\ActivityProvider::TYPES)) {
            $type = 'car_rental';
        }
        $district = trim((string)($_POST['district'] ?? ''));
        if ($district !== '' && !in_array($district, \App\Models\VisitorPlace::DISTRICTS, true)) {
            Session::flash('error', 'กรุณาเลือกอำเภอจากรายการ');
            Session::withOld($_POST);
            back();
        }

        $userId = User::create([
            'role'     => 'provider',
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'status'   => 'active',
        ]);

        $providerRow = [
            'user_id'          => $userId,
            'name'             => trim((string)$data['business_name']),
            'type'             => $type,
            'contact_name'     => trim((string)$data['name']),
            'phone'            => trim((string)$data['phone']),
            'line_id'          => trim((string)($_POST['line_id'] ?? '')) ?: null,
            'email'            => trim((string)$data['email']),
            'district'         => $district !== '' ? $district : null,
            'zone'             => trim((string)($_POST['zone'] ?? '')) ?: null,
            'address'          => trim((string)($_POST['address'] ?? '')) ?: null,
            'commission_type'  => 'percent',
            'commission_value' => 10.00,
            'status'           => 'inactive',
            'partner_status'   => 'pending',
        ];
        if (!\App\Core\Database::tableHasColumn('activity_providers', 'partner_status')) {
            unset($providerRow['partner_status']);
            $providerRow['status'] = 'inactive';
        }
        if (!\App\Core\Database::tableHasColumn('activity_providers', 'user_id')) {
            unset($providerRow['user_id']);
        }

        \App\Core\Database::insert('activity_providers', $providerRow);

        try {
            \App\Services\NotificationService::sendToRole(
                'admin',
                'provider_registered',
                'มีผู้ให้บริการสมัครใหม่',
                sprintf('%s (%s) — รออนุมัติ', $data['business_name'], \App\Models\ActivityProvider::TYPES[$type] ?? $type),
                '/admin/activity-providers'
            );
        } catch (\Throwable $e) {
        }

        Session::flash('success', 'สมัครผู้ให้บริการสำเร็จ! กรุณาเข้าสู่ระบบ — ทีมงานจะอนุมัติภายใน 24 ชม.');
        redirect(url('/provider/login'));
    }
}
