<?php

namespace App\Controllers\Owner;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Upload;
use App\Core\View;
use App\Models\MembershipPlan;
use App\Services\MembershipService;
use App\Services\OwnerMembership;

class MembershipController extends Controller
{
    public function index(): void
    {
        $ownerId = Auth::ownerId();
        if (!$ownerId) {
            Session::flash('error', 'ไม่พบข้อมูลเจ้าของแพ');
            redirect(url('/owner/dashboard'));
            return;
        }
        $owner = OwnerMembership::ownerRow($ownerId);
        $plans = MembershipPlan::activeOrdered();
        $orders = Database::fetchAll(
            "SELECT mo.*, mp.code AS plan_code, mp.tier AS plan_tier
             FROM membership_orders mo
             JOIN membership_plans mp ON mp.id = mo.plan_id
             WHERE mo.owner_id = :o
             ORDER BY mo.id DESC LIMIT 30",
            ['o' => $ownerId]
        );
        View::render('owner/membership/index', [
            'page_title' => 'สมาชิกเจ้าของแพ',
            'owner'      => $owner,
            'plans'      => $plans,
            'orders'     => $orders,
            'salesOpen'  => MembershipService::salesOpen(),
        ], 'layouts/owner');
    }

    public function buy(): void
    {
        if (!MembershipService::salesOpen()) {
            Session::flash('error', 'แพ็กเกจยังไม่เปิดให้บริการ — เปิดให้บริการเร็วๆนี้');
            redirect(url('/owner/membership'));
            return;
        }
        $ownerId = Auth::ownerId();
        if (!$ownerId) {
            redirect(url('/owner/dashboard'));
            return;
        }
        $planId = (int)($_GET['plan'] ?? 0);
        $plan   = $planId ? MembershipPlan::find($planId) : null;
        if (!$plan || !(int)$plan['is_active']) {
            Session::flash('error', 'เลือกแพ็กเกจจากหน้ารายการ');
            redirect(url('/owner/membership'));
            return;
        }
        $bank = [
            'bank_name'    => \App\Models\Setting::get('bank_name', ''),
            'bank_account' => \App\Models\Setting::get('bank_account', ''),
            'bank_holder'  => \App\Models\Setting::get('bank_holder', ''),
            'promptpay'    => \App\Models\Setting::get('promptpay_id', ''),
        ];
        View::render('owner/membership/buy', [
            'page_title' => 'สมัครสมาชิก — ' . $plan['code'],
            'plan'       => $plan,
            'bank'       => $bank,
        ], 'layouts/owner');
    }

    public function checkout(): void
    {
        if (!MembershipService::salesOpen()) {
            Session::flash('error', 'แพ็กเกจยังไม่เปิดให้บริการ — เปิดให้บริการเร็วๆนี้');
            redirect(url('/owner/membership'));
            return;
        }
        $ownerId = Auth::ownerId();
        if (!$ownerId) {
            redirect(url('/owner/dashboard'));
            return;
        }
        $data = $this->validate([
            'plan_id' => 'required|integer',
        ]);
        $planId = (int)$data['plan_id'];

        $slip = null;
        if (!empty($_FILES['slip']['tmp_name'])) {
            try {
                $slip = Upload::image('slip', 'slips');
            } catch (\Throwable $e) {
                Session::flash('error', $e->getMessage());
                back();
            }
        }

        try {
            $orderId = MembershipService::createPendingOrder(
                $ownerId,
                $planId,
                $slip,
                trim((string)($_POST['payment_method'] ?? 'promptpay')) ?: 'promptpay'
            );
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            back();
        }

        $row = Database::fetch('SELECT order_no, status FROM membership_orders WHERE id = :id', ['id' => $orderId]);
        if (($row['status'] ?? '') === 'paid') {
            Session::flash('success', 'สมัครสมาชิกเรียบร้อย — สิทธิ์เปิดใช้ทันที');
        } else {
            Session::flash('success', 'ส่งคำสั่งซื้อแล้ว — รอแอดมินตรวจสลิป');
        }
        redirect(url('/owner/membership/success/' . $row['order_no']));
    }

    public function success(string $orderNo): void
    {
        $ownerId = Auth::ownerId();
        if (!$ownerId) {
            redirect(url('/owner/dashboard'));
            return;
        }
        $order = Database::fetch(
            "SELECT mo.*, mp.code AS plan_code FROM membership_orders mo
             JOIN membership_plans mp ON mp.id = mo.plan_id
             WHERE mo.order_no = :no AND mo.owner_id = :o",
            ['no' => $orderNo, 'o' => $ownerId]
        );
        if (!$order) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/owner');

            return;
        }
        View::render('owner/membership/success', [
            'page_title' => 'คำสั่งซื้อสมาชิก',
            'order'      => $order,
        ], 'layouts/owner');
    }
}
