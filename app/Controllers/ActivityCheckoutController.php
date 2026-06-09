<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Core\Upload;
use App\Models\ActivityOrder;
use App\Models\ActivityProduct;
use App\Models\ActivityProviderSubscription;
use App\Models\Setting;
use App\Services\NotificationService;

class ActivityCheckoutController extends Controller
{
    public function buy(int $id): void
    {
        $product = $this->productForCheckout($id);
        if (!$product) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $this->view('activities/checkout', [
            'meta_title' => 'จอง / ซื้อ voucher — ' . $product['title'],
            'product'    => $product,
            'options'    => ActivityProduct::options($id),
            'user'       => Auth::user(),
            'bank'       => [
                'bank_name'    => Setting::get('bank_name', 'กสิกรไทย'),
                'bank_account' => Setting::get('bank_account', '123-4-56789-0'),
                'bank_holder'  => Setting::get('bank_holder', 'บจก. แพกาญ.com'),
                'promptpay'    => Setting::get('promptpay_id', '0810000000'),
            ],
        ]);
    }

    public function checkout(int $id): void
    {
        $product = $this->productForCheckout($id);
        if (!$product) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }
        if (($product['booking_mode'] ?? 'lead') !== 'voucher') {
            Session::flash('error', 'รายการนี้ยังไม่เปิดซื้อ voucher ผ่านเว็บ กรุณาติดต่อผู้ให้บริการ');
            redirect(url('/activities/' . $product['slug']));
        }

        $data = $this->validate([
            'name'     => 'required|max:160',
            'phone'    => 'required|phone',
            'email'    => 'email|max:160',
            'quantity' => 'required|integer',
        ]);

        $options = ActivityProduct::options($id);
        $option = null;
        $optionId = (int)($_POST['option_id'] ?? 0);
        foreach ($options as $op) {
            if ((int)$op['id'] === $optionId) {
                $option = $op;
                break;
            }
        }
        if (!$option && $options !== []) {
            $option = $options[0];
        }
        $unitPrice = $option ? (float)$option['price'] : (float)$product['base_price'];
        $minQty = $option ? (int)$option['min_qty'] : 1;
        $maxQty = $option ? (int)$option['max_qty'] : 20;
        $qty = max($minQty, min($maxQty, (int)$data['quantity']));
        $subtotal = $unitPrice * $qty;
        $commission = ActivityOrder::commissionAmount($product, $subtotal);
        $slip = null;
        try {
            $slip = Upload::image('slip', 'activity-slips');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            back();
        }

        $orderNo = ActivityOrder::generateOrderNo();
        $idOrder = Database::insert('activity_orders', [
            'order_no'          => $orderNo,
            'product_id'        => $id,
            'option_id'         => $option ? (int)$option['id'] : null,
            'customer_id'       => Auth::customerId(),
            'buyer_name'        => trim((string)$data['name']),
            'buyer_phone'       => trim((string)$data['phone']),
            'buyer_email'       => trim((string)($data['email'] ?? '')) ?: null,
            'travel_date'       => trim((string)($_POST['travel_date'] ?? '')) ?: null,
            'time_slot'         => trim((string)($_POST['time_slot'] ?? '')) ?: null,
            'quantity'          => $qty,
            'unit_price'        => $unitPrice,
            'subtotal'          => $subtotal,
            'discount'          => 0,
            'total_price'       => $subtotal,
            'commission_amount' => $commission,
            'provider_payout'   => max(0, $subtotal - $commission),
            'payment_method'    => in_array(($_POST['payment_method'] ?? ''), ['promptpay', 'bank_transfer', 'cash', 'other'], true)
                ? (string)$_POST['payment_method']
                : 'promptpay',
            'payment_slip'      => $slip,
            'status'            => $slip ? 'pending' : 'pending',
            'voucher_code'      => ActivityOrder::generateVoucherCode(),
            'notes'             => trim((string)($_POST['notes'] ?? '')) ?: null,
        ]);

        $order = ActivityOrder::find($idOrder);

        try {
            if (!empty($product['provider_id'])) {
                $pr = Database::fetch(
                    'SELECT user_id FROM activity_providers WHERE id = :id LIMIT 1',
                    ['id' => (int)$product['provider_id']]
                );
                if ($pr && !empty($pr['user_id'])) {
                    NotificationService::send(
                        (int)$pr['user_id'],
                        'activity_order_new',
                        'มีคำสั่งซื้อใหม่',
                        $orderNo . ' — ' . ($product['title'] ?? ''),
                        '/provider/orders/' . $idOrder
                    );
                }
            }
            NotificationService::sendToRole(
                'admin',
                'activity_order_new',
                'คำสั่งซื้อกิจกรรมใหม่',
                $orderNo . ' — รอตรวจสลิป',
                '/admin/activity-orders/' . $idOrder
            );
        } catch (\Throwable $e) {
        }

        Session::flash('success', 'ส่งคำสั่งซื้อกิจกรรมเรียบร้อย');
        redirect(url('/activities/success/' . $order['order_no']));
    }

    public function success(string $orderNo): void
    {
        $order = ActivityOrder::findByOrderNo($orderNo);
        if (!$order) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $this->view('activities/success', [
            'meta_title' => 'รับ voucher กิจกรรม — แพกาญ.com',
            'order'      => $order,
        ]);
    }

    private function productForCheckout(int $id): ?array
    {
        if (!ActivityProduct::tableReady()) {
            return null;
        }

        $row = Database::fetch(
            "SELECT ap.*, pr.name AS provider_name, pr.phone AS provider_phone, pr.line_id AS provider_line_id,
                    pr.commission_type, pr.commission_value, ap.provider_id
             FROM activity_products ap
             LEFT JOIN activity_providers pr ON pr.id = ap.provider_id
             WHERE ap.id = :id AND ap.status = 'published'
             LIMIT 1",
            ['id' => $id]
        );
        if (!$row) {
            return null;
        }

        return $this->applySubscriptionCommission($row);
    }

    /** @param array<string,mixed> $product */
    private function applySubscriptionCommission(array $product): array
    {
        $pid = (int)($product['provider_id'] ?? 0);
        if ($pid <= 0 || !ActivityProviderSubscription::tableReady()) {
            return $product;
        }
        $override = ActivityProviderSubscription::commissionOverrideForProvider($pid);
        if ($override === null) {
            return $product;
        }
        $product['commission_type'] = 'percent';
        $product['commission_value'] = $override;

        return $product;
    }
}

