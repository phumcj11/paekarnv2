<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\AuditLog;
use App\Models\MembershipPlan;
use App\Services\MembershipService;
use App\Services\OwnerTier;
use App\Core\Csrf;

class MembershipController extends Controller
{
    public function orders(): void
    {
        $rows = Database::fetchAll(
            "SELECT mo.*, mp.code AS plan_code, mp.tier AS plan_tier,
                    u.name AS owner_name, u.email AS owner_email
             FROM membership_orders mo
             JOIN membership_plans mp ON mp.id = mo.plan_id
             JOIN owners ow ON ow.id = mo.owner_id
             JOIN users u ON u.id = ow.user_id
             ORDER BY mo.id DESC LIMIT 200"
        );
        View::render('admin/membership/orders', [
            'page_title' => 'คำสั่งซื้อสมาชิกเจ้าของแพ',
            'rows'       => $rows,
        ], 'layouts/admin');
    }

    public function approve(int $id): void
    {
        try {
            MembershipService::approveOrder($id);
            Session::flash('success', 'อนุมัติและเปิดสิทธิ์สมาชิกแล้ว');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        back();
    }

    public function cancel(int $id): void
    {
        try {
            MembershipService::cancelOrder($id);
            Session::flash('success', 'ยกเลิกคำสั่งซื้อแล้ว');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        back();
    }

    public function plans(): void
    {
        $rows = MembershipPlan::all('sort_order', 'ASC');
        View::render('admin/membership/plans', [
            'page_title'     => 'แพ็กเกจสมาชิกเจ้าของแพ',
            'rows'           => $rows,
            'tierFeatures'   => OwnerTier::featuresConfig(),
        ], 'layouts/admin');
    }

    public function saveTierFeatures(): void
    {
        $raw = file_get_contents('php://input');
        $body = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($body)) {
            $this->json(['ok' => false, 'msg' => 'ข้อมูลไม่ถูกต้อง'], 400);
        }
        if (!Csrf::verify((string) ($body['_csrf'] ?? ''))) {
            $this->json(['ok' => false, 'msg' => 'เซสชันหมดอายุ — รีเฟรชหน้าแล้วลองใหม่'], 403);
        }

        OwnerTier::saveFeaturesConfig([
            'base_property' => $body['base_property'] ?? [],
            'features'      => $body['features'] ?? [],
            'boost'         => $body['boost'] ?? [],
        ]);

        AuditLog::record('owner_tier_features_updated', [], 'settings', 0);
        $this->json(['ok' => true, 'msg' => 'บันทึกสิทธิ์แต่ละระดับแล้ว']);
    }

    public function planToggleActive(int $id): void
    {
        $plan = MembershipPlan::find($id);
        if (!$plan) {
            Session::flash('error', 'ไม่พบแพ็กเกจ');
            back();
        }

        $active = !empty($_POST['is_active']) ? 1 : 0;
        MembershipPlan::update($id, ['is_active' => $active]);
        AuditLog::record(
            'membership_plan_toggled',
            ['plan_id' => $id, 'code' => $plan['code'], 'is_active' => $active],
            'membership_plan',
            $id
        );

        if ($this->wantsJson()) {
            $this->json(['ok' => true, 'is_active' => $active]);
        }

        Session::flash('success', $active ? 'เปิดการขายแล้ว' : 'ปิดการขายแล้ว');
        redirect(url('/admin/membership/plans'));
    }

    private function wantsJson(): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $xhr = (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');

        return str_contains($accept, 'application/json') || strtolower($xhr) === 'xmlhttprequest';
    }

    public function planCreate(): void
    {
        View::render('admin/membership/plan_form', [
            'page_title' => 'เพิ่มแพ็กเกจสมาชิก',
            'plan'       => null,
        ], 'layouts/admin');
    }

    public function planStore(): void
    {
        $data = $this->validate([
            'code'          => 'required|max:40',
            'tier'          => 'required|in:standard,vip',
            'price'         => 'required|numeric',
            'sort_order'    => 'numeric',
            'duration_days' => 'numeric',
        ]);

        $code = strtoupper(preg_replace('/\s+/', '_', trim($data['code'])));
        if (MembershipPlan::findByCode($code)) {
            Session::flash('error', 'รหัสแพ็กเกจซ้ำ — ใช้รหัสอื่น');
            Session::withOld($_POST);
            back();
        }

        $life = !empty($_POST['is_lifetime']) ? 1 : 0;
        $days = $life ? null : max(1, (int)($data['duration_days'] ?? 0));
        if (!$life && $days <= 0) {
            Session::flash('error', 'ระบุจำนวนวันของแพ็กเกจ หรือติ๊กตลอดชีพ');
            Session::withOld($_POST);
            back();
        }

        $id = Database::insert('membership_plans', [
            'code'          => $code,
            'tier'          => $data['tier'],
            'duration_days' => $days,
            'price'         => (float)$data['price'],
            'is_lifetime'   => $life,
            'is_active'     => !empty($_POST['is_active']) ? 1 : 0,
            'sort_order'    => (int)($data['sort_order'] ?? 0),
        ]);

        AuditLog::record('membership_plan_created', ['plan_id' => $id, 'code' => $code], 'membership_plan', $id);
        Session::flash('success', 'สร้างแพ็กเกจแล้ว');
        redirect(url('/admin/membership/plans'));
    }

    public function planEdit(int $id): void
    {
        $plan = MembershipPlan::find($id);
        if (!$plan) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');

            return;
        }
        View::render('admin/membership/plan_form', [
            'page_title' => 'แก้ไขแพ็กเกจ: ' . $plan['code'],
            'plan'       => $plan,
        ], 'layouts/admin');
    }

    public function planUpdate(int $id): void
    {
        $plan = MembershipPlan::find($id);
        if (!$plan) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');

            return;
        }

        $data = $this->validate([
            'tier'          => 'required|in:standard,vip',
            'price'         => 'required|numeric',
            'sort_order'    => 'numeric',
            'duration_days' => 'numeric',
        ]);

        $life = !empty($_POST['is_lifetime']) ? 1 : 0;
        $days = $life ? null : max(1, (int)($data['duration_days'] ?? 0));
        if (!$life && $days <= 0) {
            Session::flash('error', 'ระบุจำนวนวันของแพ็กเกจ หรือติ๊กตลอดชีพ');
            Session::withOld($_POST);
            back();
        }

        MembershipPlan::update($id, [
            'tier'          => $data['tier'],
            'duration_days' => $days,
            'price'         => (float)$data['price'],
            'is_lifetime'   => $life,
            'is_active'     => !empty($_POST['is_active']) ? 1 : 0,
            'sort_order'    => (int)($data['sort_order'] ?? 0),
        ]);

        AuditLog::record(
            'membership_plan_updated',
            ['plan_id' => $id, 'code' => $plan['code']],
            'membership_plan',
            $id
        );
        Session::flash('success', 'บันทึกแพ็กเกจแล้ว');
        redirect(url('/admin/membership/plans'));
    }

    public function planDelete(int $id): void
    {
        $plan = MembershipPlan::find($id);
        if (!$plan) {
            Session::flash('error', 'ไม่พบแพ็กเกจ');
            back();
        }
        $n = (int)Database::fetch(
            'SELECT COUNT(*) c FROM membership_orders WHERE plan_id = :p',
            ['p' => $id]
        )['c'];
        if ($n > 0) {
            Session::flash('error', 'ลบไม่ได้: มีคำสั่งซื้ออ้างอิงแพ็กเกจนี้ — ให้ปิดการขาย (ไม่ active) แทน');
            back();
        }
        Database::delete('membership_plans', 'id = :id', ['id' => $id]);
        AuditLog::record(
            'membership_plan_deleted',
            ['plan_id' => $id, 'code' => $plan['code']],
            'membership_plan',
            $id
        );
        Session::flash('success', 'ลบแพ็กเกจแล้ว');
        redirect(url('/admin/membership/plans'));
    }
}
