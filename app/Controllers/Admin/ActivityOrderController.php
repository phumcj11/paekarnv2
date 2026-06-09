<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\ActivityLeadClick;
use App\Models\ActivityOrder;
use App\Services\NotificationService;

class ActivityOrderController
{
    public function index(): void
    {
        $month = trim((string)($_GET['month'] ?? date('Y-m')));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $payoutFilter = trim((string)($_GET['payout'] ?? ''));
        if (!in_array($payoutFilter, ['', 'pending', 'paid'], true)) {
            $payoutFilter = '';
        }

        View::render('admin/activity_orders/index', [
            'page_title'      => 'คำสั่งซื้อกิจกรรม',
            'rows'            => ActivityOrder::adminLatest(150, $month, $payoutFilter !== '' ? $payoutFilter : null),
            'statuses'        => ActivityOrder::STATUSES,
            'revenue'         => ActivityOrder::adminRevenueSummary($month),
            'byProvider'      => ActivityOrder::adminRevenueByProvider($month),
            'leadRows'        => ActivityLeadClick::tableReady() ? ActivityLeadClick::adminByProduct($month) : [],
            'hasSettlement'   => ActivityOrder::hasSettlementColumns(),
            'filterMonth'     => $month,
            'filterPayout'    => $payoutFilter,
        ], 'layouts/admin');
    }

    public function show(int $id): void
    {
        $order = Database::fetch(
            "SELECT ao.*, ap.title AS product_title, ap.slug AS product_slug, op.name AS option_name,
                    pr.id AS provider_id, pr.name AS provider_name
             FROM activity_orders ao
             INNER JOIN activity_products ap ON ap.id = ao.product_id
             LEFT JOIN activity_options op ON op.id = ao.option_id
             LEFT JOIN activity_providers pr ON pr.id = ap.provider_id
             WHERE ao.id = :id LIMIT 1",
            ['id' => $id]
        );
        if (!$order) {
            http_response_code(404);
            View::render('errors/404', [], 'layouts/admin');
            return;
        }

        View::render('admin/activity_orders/show', [
            'page_title'    => 'คำสั่งซื้อกิจกรรม #' . $order['order_no'],
            'order'         => $order,
            'statuses'      => ActivityOrder::STATUSES,
            'hasSettlement' => ActivityOrder::hasSettlementColumns(),
        ], 'layouts/admin');
    }

    public function updateStatus(int $id): void
    {
        $status = (string)($_POST['status'] ?? '');
        if (!array_key_exists($status, ActivityOrder::STATUSES)) {
            Session::flash('error', 'สถานะไม่ถูกต้อง');
            back();
        }
        $payload = ['status' => $status];
        if ($status === 'paid' || $status === 'confirmed') {
            $payload['paid_at'] = date('Y-m-d H:i:s');
        }
        if ($status === 'redeemed') {
            $payload['redeemed_at'] = date('Y-m-d H:i:s');
        }

        Database::update('activity_orders', $payload, 'id = :id', ['id' => $id]);

        if ($status === 'paid') {
            $this->notifyProviderForOrder($id, 'ชำระเงินแล้ว — รอยืนยันรอบบริการ');
        }

        Session::flash('success', 'อัปเดตสถานะเรียบร้อย');
        back();
    }

    public function markPayout(int $id): void
    {
        if (!ActivityOrder::hasSettlementColumns()) {
            Session::flash('error', 'ยังไม่ได้ติดตั้ง patch settlement — รัน database/patches/20260522_provider_monetization_mysql57.sql');
            back();
        }

        $ref = trim((string)($_POST['provider_payout_ref'] ?? ''));
        if (ActivityOrder::markProviderPaid($id, $ref)) {
            $this->notifyProviderPayout($id, $ref);
            Session::flash('success', 'บันทึกการโอนให้ provider แล้ว');
        } else {
            Session::flash('error', 'ไม่สามารถบันทึกการโอนได้ (ออเดอร์ไม่พร้อมหรือโอนแล้ว)');
        }
        back();
    }

    public function clearPayout(int $id): void
    {
        if (!ActivityOrder::hasSettlementColumns()) {
            Session::flash('error', 'ยังไม่ได้ติดตั้ง patch settlement');
            back();
        }

        if (ActivityOrder::clearProviderPaid($id)) {
            Session::flash('success', 'ยกเลิกสถานะโอนแล้ว');
        } else {
            Session::flash('error', 'ไม่สามารถยกเลิกได้');
        }
        back();
    }

    private function notifyProviderForOrder(int $orderId, string $message): void
    {
        try {
            $row = Database::fetch(
                "SELECT ao.order_no, ao.product_id, ap.title, ap.provider_id, pr.user_id
                 FROM activity_orders ao
                 INNER JOIN activity_products ap ON ap.id = ao.product_id
                 LEFT JOIN activity_providers pr ON pr.id = ap.provider_id
                 WHERE ao.id = :id LIMIT 1",
                ['id' => $orderId]
            );
            if ($row && !empty($row['user_id'])) {
                NotificationService::send(
                    (int)$row['user_id'],
                    'activity_order_update',
                    'อัปเดตคำสั่งซื้อกิจกรรม',
                    ($row['order_no'] ?? '') . ' — ' . $message,
                    '/provider/orders/' . $orderId
                );
            }
        } catch (\Throwable $e) {
        }
    }

    private function notifyProviderPayout(int $orderId, string $ref): void
    {
        try {
            $row = Database::fetch(
                "SELECT ao.order_no, ao.provider_payout, pr.user_id
                 FROM activity_orders ao
                 INNER JOIN activity_products ap ON ap.id = ao.product_id
                 LEFT JOIN activity_providers pr ON pr.id = ap.provider_id
                 WHERE ao.id = :id LIMIT 1",
                ['id' => $orderId]
            );
            if ($row && !empty($row['user_id'])) {
                $msg = ($row['order_no'] ?? '') . ' — โอน ' . format_money($row['provider_payout'] ?? 0) . ' แล้ว';
                if ($ref !== '') {
                    $msg .= ' (ref: ' . $ref . ')';
                }
                NotificationService::send(
                    (int)$row['user_id'],
                    'activity_payout',
                    'ได้รับเงินจากแพกาญแล้ว',
                    $msg,
                    '/provider/orders/' . $orderId
                );
            }
        } catch (\Throwable $e) {
        }
    }
}
