<?php

declare(strict_types=1);

namespace App\Controllers\Provider;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Core\View;
use App\Models\ActivityOrder;

class RedeemController extends Controller
{
    public function index(): void
    {
        View::render('provider/redeem/index', [
            'page_title' => 'Redeem Voucher',
            'order'      => null,
            'check'      => null,
            'code'       => strtoupper(trim((string)($_GET['code'] ?? ''))),
            'isActive'   => Auth::providerIsActive(),
        ], 'layouts/provider');
    }

    public function lookup(): void
    {
        $code = strtoupper(trim((string)($_POST['code'] ?? '')));
        $pid = Auth::providerId();
        if (!$pid || $code === '') {
            Session::flash('error', 'กรุณากรอกรหัส voucher');
            back();
        }

        $order = ActivityOrder::findByVoucherForProvider($code, $pid);
        $check = ['ok' => (bool)$order];
        if (!$order) {
            $check['msg'] = 'ไม่พบ voucher นี้ในระบบของคุณ';
        } elseif ($order['status'] === 'redeemed') {
            $check['ok'] = false;
            $check['msg'] = 'ใช้งานแล้ว';
        } elseif ($order['status'] === 'cancelled') {
            $check['ok'] = false;
            $check['msg'] = 'คำสั่งซื้อถูกยกเลิก';
        } elseif (!in_array($order['status'], ['paid', 'confirmed'], true)) {
            $check['ok'] = false;
            $check['msg'] = 'ยังไม่พร้อม redeem (สถานะ: ' . $order['status'] . ')';
        }

        View::render('provider/redeem/index', [
            'page_title' => 'Redeem Voucher',
            'order'      => $order,
            'check'      => $check,
            'code'       => $code,
            'isActive'   => Auth::providerIsActive(),
        ], 'layouts/provider');
    }

    public function redeem(): void
    {
        if (!Auth::providerIsActive()) {
            Session::flash('error', 'บัญชียังไม่ได้รับการอนุมัติ');
            back();
        }
        $code = strtoupper(trim((string)($_POST['code'] ?? '')));
        $pid = Auth::providerId();
        if (!$pid || $code === '') {
            Session::flash('error', 'ข้อมูลไม่ครบ');
            back();
        }

        $result = ActivityOrder::redeemVoucher($code, $pid, Auth::id());
        if (!$result['ok']) {
            Session::flash('error', $result['msg'] ?? 'ไม่สามารถ redeem ได้');
            back();
        }

        Session::flash('success', 'Redeem voucher ' . $code . ' เรียบร้อย');
        redirect(url('/provider/redeem?code=' . urlencode($code)));
    }
}
