<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Coupon;
use App\Models\CouponOrder;
use App\Models\Setting;
use App\Services\NotificationService;

class CouponService
{
    public static function purchase(array $buyer, int $quantity, ?int $customerId, ?string $slipPath): array
    {
        $pricing = self::resolvePricing(isset($buyer['campaign_code']) ? (string)$buyer['campaign_code'] : null);
        $face  = (int)$pricing['face_value'];
        $sale  = (int)$pricing['sale_price'];
        $days  = (int)Setting::get('coupon_validity_days', 90);
        $total = $sale * $quantity;

        $orderId = CouponOrder::create([
            'order_no'       => CouponOrder::generateOrderNo(),
            'customer_id'    => $customerId,
            'buyer_name'     => $buyer['name'],
            'buyer_phone'    => $buyer['phone'],
            'buyer_email'    => $buyer['email'] ?? null,
            'face_value'     => $face,
            'sale_price'     => $sale,
            'quantity'       => $quantity,
            'total_price'    => $total,
            'payment_method' => $buyer['payment_method'] ?? 'promptpay',
            'slip_path'      => $slipPath,
            'status'         => $slipPath ? 'paid' : 'pending',
            'paid_at'        => $slipPath ? date('Y-m-d H:i:s') : null,
        ]);

        $codes = [];
        for ($i = 0; $i < $quantity; $i++) {
            $code = Coupon::generateCode();
            Coupon::create([
                'code'        => $code,
                'order_id'    => $orderId,
                'customer_id' => $customerId,
                'phone'       => $buyer['phone'],
                'face_value'  => $face,
                'sale_price'  => $sale,
                'status'      => 'unused',
                'expires_at'  => date('Y-m-d H:i:s', strtotime("+{$days} days")),
            ]);
            $codes[] = $code;
        }

        return ['order_id' => $orderId, 'codes' => $codes, 'total' => $total];
    }

    /**
     * Mark coupon order paid after Stripe Checkout — idempotent; sends notifications once.
     */
    public static function completeStripePayment(int $orderId, string $sessionId, ?string $paymentIntentId): bool
    {
        $order = CouponOrder::find($orderId);
        if (!$order) {
            return false;
        }

        $wasPaid = (string) $order['status'] === 'paid';
        $payload = [
            'stripe_checkout_session_id' => $sessionId,
        ];
        if ($paymentIntentId !== null && $paymentIntentId !== '') {
            $payload['stripe_payment_intent_id'] = $paymentIntentId;
        }
        if (!$wasPaid) {
            $payload['status'] = 'paid';
            $payload['paid_at'] = date('Y-m-d H:i:s');
        }

        Database::update('coupon_orders', $payload, 'id = :id', ['id' => $orderId]);

        if ($wasPaid) {
            return false;
        }

        $coupons = Database::fetchAll(
            'SELECT code FROM coupons WHERE order_id = :id ORDER BY id',
            ['id' => $orderId]
        );
        $codes = array_map(static fn (array $c): string => (string) $c['code'], $coupons);
        $buyer = [
            'name'  => (string) ($order['buyer_name'] ?? ''),
            'phone' => (string) ($order['buyer_phone'] ?? ''),
            'email' => $order['buyer_email'] ?? null,
        ];

        self::notifyPurchaseOrder(
            !empty($order['customer_id']) ? (int) $order['customer_id'] : null,
            $buyer,
            (int) ($order['quantity'] ?? 1),
            (int) ($order['total_price'] ?? 0),
            $orderId,
            $codes
        );

        return true;
    }

    /**
     * แจ้งเตือนหลังซื้อคูปอง — เรียกหลังส่ง redirect แล้ว (เช่น หลัง fastcgi_finish_request)
     * เพื่อไม่ให้ผู้ใช้รอ mail()/LINE หลายคนค้างที่หน้า checkout
     */
    public static function notifyPurchaseOrder(?int $customerId, array $buyer, int $quantity, int $total, int $orderId, array $codes): void
    {
        $face = (int) Setting::get('coupon_face_value', 500);
        $orderRow = CouponOrder::find($orderId);
        $orderNo = $orderRow['order_no'] ?? '';
        $couponRows = Database::fetchAll(
            'SELECT id, code, expires_at FROM coupons WHERE order_id = :id ORDER BY id',
            ['id' => $orderId]
        );

        try {
            CouponPurchaseMailService::sendBuyerConfirmation(
                isset($buyer['email']) ? (string) $buyer['email'] : null,
                (string) ($buyer['name'] ?? ''),
                $orderNo,
                $quantity,
                $total,
                $face,
                $couponRows
            );
        } catch (\Throwable $e) {
        }

        $site = (string) Setting::get('site_name', 'แพกาญ.com');
        $adminHtml = CouponPurchaseMailService::buildAdminCouponOrderHtml(
            (string) ($buyer['name'] ?? ''),
            (string) ($buyer['phone'] ?? ''),
            isset($buyer['email']) ? (string) $buyer['email'] : null,
            $orderNo,
            $quantity,
            $total,
            $couponRows
        );
        $adminSubject = '[' . $site . '] คำสั่งซื้อคูปองใหม่ — ' . $orderNo;
        $extraMail = trim((string) Setting::get('admin_orders_email', ''));
        if ($extraMail !== '' && filter_var($extraMail, FILTER_VALIDATE_EMAIL)) {
            try {
                NotificationService::sendHtmlMail($extraMail, $adminSubject, $adminHtml);
            } catch (\Throwable $e) {
            }
        }

        $gid = trim((string) Setting::get('line_admin_group_id', ''));
        if ($gid !== '') {
            try {
                $lineTxt = "มีคำสั่งซื้อคูปองใหม่\n"
                    . ($buyer['name'] ?? '') . ' · ' . $quantity . " ใบ · ฿" . number_format($total)
                    . "\nเลขที่ " . $orderNo;
                LineService::push($gid, $lineTxt);
            } catch (\Throwable $e) {
            }
        }

        try {
            if ($customerId) {
                $u = Database::fetch('SELECT user_id FROM customers WHERE id = :c', ['c' => $customerId]);
                if ($u) {
                    NotificationService::send(
                        (int) $u['user_id'],
                        'coupon_purchased',
                        'ซื้อคูปองสำเร็จ 🎁',
                        sprintf('ได้รับคูปอง %d ใบ มูลค่ารวม %s บาท', $quantity, number_format($face * $quantity)),
                        '/account/coupons',
                        ['order_id' => $orderId, 'codes' => $codes]
                    );
                }
            }
            NotificationService::sendToRole(
                'admin',
                'coupon_order_new',
                'มีคำสั่งซื้อคูปองใหม่',
                sprintf('คุณ %s ซื้อ %d ใบ รวม %s บาท — เลขที่ %s', $buyer['name'] ?? '', $quantity, number_format($total), $orderNo),
                '/admin/coupons/orders',
                ['_force_email' => true, 'order_no' => $orderNo, 'codes' => $codes]
            );
        } catch (\Throwable $e) {
        }
    }

    /** unused / reserved — ready to redeem at property */
    public static function isRedeemableStatus(string $status): bool
    {
        return $status === 'unused' || $status === 'reserved';
    }

    public static function validate(string $code, ?string $phone = null): array
    {
        $coupon = Coupon::findByCode($code);
        if (!$coupon)            return ['ok' => false, 'msg' => 'ไม่พบคูปองนี้'];
        if (!self::isRedeemableStatus((string)$coupon['status'])) {
            return ['ok' => false, 'msg' => 'คูปองนี้ไม่พร้อมใช้ (ใช้แล้ว / หมดอายุ / ยกเลิก / เพิกถอน)'];
        }
        if (strtotime($coupon['expires_at']) < time()) return ['ok' => false, 'msg' => 'คูปองหมดอายุแล้ว'];
        if ($phone && $coupon['phone'] !== $phone) {
            return ['ok' => false, 'msg' => 'เบอร์โทรไม่ตรงกับที่ผูกไว้กับคูปอง'];
        }
        return ['ok' => true, 'coupon' => $coupon];
    }

    public static function markUsed(int $couponId, ?int $bookingId, int $propertyId, ?int $verifiedBy = null): void
    {
        $coupon = Coupon::find($couponId);
        if (!$coupon || !self::isRedeemableStatus((string)$coupon['status'])) {
            return;
        }

        $bk = ($bookingId !== null && $bookingId > 0) ? $bookingId : null;

        Database::update('coupons', [
            'status'             => 'used',
            'used_at'            => date('Y-m-d H:i:s'),
            'used_property_id'   => $propertyId,
            'used_booking_id'    => $bk,
        ], 'id = :id', ['id' => $couponId]);

        Database::insert('coupon_usages', [
            'coupon_id'   => $couponId,
            'booking_id'  => $bk,
            'property_id' => $propertyId,
            'verified_by' => $verifiedBy,
            'amount'      => $coupon['face_value'],
        ]);
    }

    /** คืนคูปองเมื่อยกเลิก/แก้ไขการจอง */
    public static function releaseFromBooking(int $couponId, int $bookingId): void
    {
        $coupon = Coupon::find($couponId);
        if (!$coupon) {
            return;
        }
        $linked = (int)($coupon['used_booking_id'] ?? 0);
        if (!in_array((string)$coupon['status'], ['used', 'reserved'], true)) {
            return;
        }
        if ($linked > 0 && $linked !== $bookingId) {
            return;
        }

        Database::update('coupons', [
            'status'           => 'unused',
            'used_at'          => null,
            'used_property_id' => null,
            'used_booking_id'  => null,
        ], 'id = :id', ['id' => $couponId]);

        Database::delete(
            'coupon_usages',
            'coupon_id = :c AND booking_id = :b',
            ['c' => $couponId, 'b' => $bookingId]
        );
    }

    /**
     * ออกคูปองจากหลังบ้าน (comp / manual)
     *
     * @param array<string,mixed> $data
     * @return array{order_id:int,codes:list<string>}
     */
    public static function adminIssue(array $data): array
    {
        $face = (float)($data['face_value'] ?? Setting::get('coupon_face_value', 500));
        $sale = (float)($data['sale_price'] ?? Setting::get('coupon_sale_price', 250));
        $days = (int)($data['validity_days'] ?? Setting::get('coupon_validity_days', 90));
        $quantity = max(1, min(20, (int)($data['quantity'] ?? 1)));
        $total = $sale * $quantity;
        $customerId = !empty($data['customer_id']) ? (int)$data['customer_id'] : null;
        $markPaid = !empty($data['mark_paid']);

        $orderId = CouponOrder::create([
            'order_no'       => CouponOrder::generateOrderNo(),
            'customer_id'    => $customerId,
            'buyer_name'     => (string)$data['buyer_name'],
            'buyer_phone'    => (string)$data['buyer_phone'],
            'buyer_email'    => ($data['buyer_email'] ?? null) ?: null,
            'face_value'     => $face,
            'sale_price'     => $sale,
            'quantity'       => $quantity,
            'total_price'    => $total,
            'payment_method' => (string)($data['payment_method'] ?? 'cash'),
            'slip_path'      => null,
            'status'         => $markPaid ? 'paid' : 'pending',
            'paid_at'        => $markPaid ? date('Y-m-d H:i:s') : null,
        ]);

        $expiresAt = !empty($data['expires_at'])
            ? (string)$data['expires_at']
            : date('Y-m-d H:i:s', strtotime("+{$days} days"));

        $codes = [];
        for ($i = 0; $i < $quantity; $i++) {
            $code = Coupon::generateCode();
            Coupon::create([
                'code'        => $code,
                'order_id'    => $orderId,
                'customer_id' => $customerId,
                'phone'       => (string)$data['buyer_phone'],
                'face_value'  => $face,
                'sale_price'  => $sale,
                'status'      => 'unused',
                'expires_at'  => $expiresAt,
            ]);
            $codes[] = $code;
        }

        return ['order_id' => $orderId, 'codes' => $codes];
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function adminUpdate(int $couponId, array $data): bool
    {
        $coupon = Coupon::find($couponId);
        if (!$coupon) {
            return false;
        }

        $isUsed = (string)$coupon['status'] === 'used';
        $payload = [];

        if (!$isUsed && !empty($data['phone'])) {
            $payload['phone'] = (string)$data['phone'];
        }
        if (!$isUsed && isset($data['face_value'])) {
            $payload['face_value'] = (float)$data['face_value'];
        }
        if (!$isUsed && isset($data['sale_price'])) {
            $payload['sale_price'] = (float)$data['sale_price'];
        }
        if (!$isUsed && !empty($data['expires_at'])) {
            $payload['expires_at'] = (string)$data['expires_at'];
        }
        if (array_key_exists('customer_id', $data)) {
            $payload['customer_id'] = $data['customer_id'] ? (int)$data['customer_id'] : null;
        }
        if (!empty($data['status'])) {
            $st = (string)$data['status'];
            if (in_array($st, ['unused', 'reserved', 'used', 'expired', 'revoked', 'cancelled'], true)) {
                if ($st === 'unused' && $isUsed) {
                    $payload['status'] = 'unused';
                    $payload['used_at'] = null;
                    $payload['used_property_id'] = null;
                    $payload['used_booking_id'] = null;
                } elseif (!$isUsed || $st !== 'unused') {
                    $payload['status'] = $st;
                }
            }
        }

        if ($payload === []) {
            return true;
        }

        Database::update('coupons', $payload, 'id = :id', ['id' => $couponId]);

        return true;
    }

    public static function revoke(int $couponId, bool $hardDelete = false): bool
    {
        $coupon = Coupon::find($couponId);
        if (!$coupon) {
            return false;
        }

        if ((string)$coupon['status'] === 'used' && !empty($coupon['used_booking_id'])) {
            if ($hardDelete) {
                return false;
            }
            Database::update('coupons', ['status' => 'revoked'], 'id = :id', ['id' => $couponId]);

            return true;
        }

        if ($hardDelete && in_array((string)$coupon['status'], ['unused', 'cancelled', 'revoked', 'expired'], true)) {
            Database::delete('coupons', 'id = :id', ['id' => $couponId]);

            return true;
        }

        Database::update('coupons', ['status' => 'revoked'], 'id = :id', ['id' => $couponId]);

        return true;
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function adminUpdateOrder(int $orderId, array $data): bool
    {
        $order = CouponOrder::find($orderId);
        if (!$order) {
            return false;
        }

        $payload = [];
        foreach (['buyer_name', 'buyer_phone', 'buyer_email', 'payment_method'] as $f) {
            if (array_key_exists($f, $data)) {
                $payload[$f] = $data[$f] ?: null;
            }
        }
        if (isset($data['face_value'])) {
            $payload['face_value'] = (float)$data['face_value'];
        }
        if (isset($data['sale_price'])) {
            $payload['sale_price'] = (float)$data['sale_price'];
        }
        if (isset($data['quantity'])) {
            $payload['quantity'] = max(1, (int)$data['quantity']);
        }
        if (isset($data['total_price'])) {
            $payload['total_price'] = (float)$data['total_price'];
        }
        if (!empty($data['status']) && in_array($data['status'], ['pending', 'paid', 'cancelled', 'refunded'], true)) {
            $payload['status'] = $data['status'];
            if ($data['status'] === 'paid' && empty($order['paid_at'])) {
                $payload['paid_at'] = date('Y-m-d H:i:s');
            }
        }

        if ($payload === []) {
            return true;
        }

        Database::update('coupon_orders', $payload, 'id = :id', ['id' => $orderId]);

        return true;
    }

    public static function cancelOrder(int $orderId): void
    {
        $order = CouponOrder::find($orderId);
        if (!$order) {
            return;
        }

        Database::update('coupon_orders', ['status' => 'cancelled'], 'id = :id', ['id' => $orderId]);

        $coupons = Database::fetchAll(
            'SELECT id, status FROM coupons WHERE order_id = :oid',
            ['oid' => $orderId]
        );
        foreach ($coupons as $c) {
            if (in_array((string)$c['status'], ['unused', 'reserved'], true)) {
                Database::update('coupons', ['status' => 'cancelled'], 'id = :id', ['id' => (int)$c['id']]);
            }
        }
    }

    /** Resolve pricing from optional campaign code or global settings */
    public static function resolvePricing(?string $campaignCode = null): array
    {
        if ($campaignCode !== null && $campaignCode !== '') {
            try {
                $camp = Database::fetch(
                    'SELECT * FROM coupon_campaigns WHERE code = :c AND is_active = 1 LIMIT 1',
                    ['c' => strtoupper(trim($campaignCode))]
                );
                if ($camp) {
                    $now = time();
                    $starts = !empty($camp['starts_at']) ? strtotime((string)$camp['starts_at']) : null;
                    $ends = !empty($camp['ends_at']) ? strtotime((string)$camp['ends_at']) : null;
                    if (($starts === null || $now >= $starts) && ($ends === null || $now <= $ends)) {
                        return [
                            'face_value' => (float)$camp['face_value'],
                            'sale_price' => (float)$camp['sale_price'],
                            'campaign_id' => (int)$camp['id'],
                            'campaign_code' => (string)$camp['code'],
                        ];
                    }
                }
            } catch (\Throwable) {
            }
        }

        return [
            'face_value'    => (float)Setting::get('coupon_face_value', 500),
            'sale_price'    => (float)Setting::get('coupon_sale_price', 250),
            'campaign_id'   => null,
            'campaign_code' => null,
        ];
    }
}
