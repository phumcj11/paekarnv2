<?php

declare(strict_types=1);

namespace App\Controllers\Provider;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\ActivityOrder;
use App\Services\NotificationService;

class OrderController extends Controller
{
    public function index(): void
    {
        $pid = Auth::providerId();
        $status = trim((string)($_GET['status'] ?? ''));
        View::render('provider/orders/index', [
            'page_title' => 'คำสั่งซื้อ',
            'rows'       => $pid ? ActivityOrder::forProvider($pid, $status !== '' ? $status : null) : [],
            'statuses'   => ActivityOrder::STATUSES,
            'filter'     => $status,
            'isActive'   => Auth::providerIsActive(),
        ], 'layouts/provider');
    }

    public function show(int $id): void
    {
        $order = $this->findOwned($id);
        if (!$order) {
            return;
        }
        View::render('provider/orders/show', [
            'page_title' => 'คำสั่งซื้อ ' . $order['order_no'],
            'order'      => $order,
            'statuses'   => ActivityOrder::STATUSES,
            'isActive'   => Auth::providerIsActive(),
        ], 'layouts/provider');
    }

    public function confirm(int $id): void
    {
        if (!Auth::providerIsActive()) {
            Session::flash('error', 'บัญชียังไม่ได้รับการอนุมัติ');
            back();
        }
        $order = $this->findOwned($id);
        if (!$order) {
            return;
        }
        if ($order['status'] !== 'paid') {
            Session::flash('error', 'ยืนยันได้เฉพาะออเดอร์ที่ชำระแล้ว');
            back();
        }

        Database::update('activity_orders', ['status' => 'confirmed'], 'id = :id', ['id' => $id]);

        if (!empty($order['buyer_email'])) {
            try {
                NotificationService::sendHtmlMail(
                    (string)$order['buyer_email'],
                    'ยืนยันการจองกิจกรรม — ' . $order['order_no'],
                    '<p>คำสั่งซื้อ ' . e($order['order_no']) . ' ได้รับการยืนยันจากผู้ให้บริการแล้ว</p>'
                );
            } catch (\Throwable $e) {
            }
        }

        Session::flash('success', 'ยืนยันรอบบริการเรียบร้อย');
        redirect(url('/provider/orders/' . $id));
    }

    /** @return array<string,mixed>|null */
    private function findOwned(int $id): ?array
    {
        $pid = Auth::providerId();
        if (!$pid) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/provider');
            return null;
        }
        $order = ActivityOrder::findForProvider($id, $pid);
        if (!$order) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/provider');
            return null;
        }

        return $order;
    }
}
