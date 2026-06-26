<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeService
{
    public static function isConfigured(): bool
    {
        if (!class_exists(StripeClient::class)) {
            return false;
        }
        if ((string) Setting::get('payment_gateway_enabled', '0') !== '1') {
            return false;
        }
        if (strtolower(trim((string) Setting::get('payment_gateway_provider', ''))) !== 'stripe') {
            return false;
        }
        $secret = trim((string) Setting::get('payment_gateway_secret_key', ''));

        return $secret !== '';
    }

    public static function client(): StripeClient
    {
        $secret = trim((string) Setting::get('payment_gateway_secret_key', ''));
        if ($secret === '') {
            throw new \RuntimeException('Stripe secret key is not configured.');
        }

        return new StripeClient($secret);
    }

    /**
     * @param array<string,mixed> $order
     */
    public static function createCouponCheckoutSession(array $order, string $successUrl, string $cancelUrl): \Stripe\Checkout\Session
    {
        $total = (int) round((float) ($order['total_price'] ?? 0));
        if ($total < 1) {
            throw new \InvalidArgumentException('Order total must be at least 1 THB.');
        }

        $qty = max(1, (int) ($order['quantity'] ?? 1));
        $face = (int) round((float) ($order['face_value'] ?? 0));
        $site = (string) Setting::get('site_name', 'แพกาญ.com');

        $params = [
            'mode'                => 'payment',
            'success_url'         => $successUrl,
            'cancel_url'          => $cancelUrl,
            'client_reference_id' => (string) ($order['order_no'] ?? ''),
            'metadata'            => [
                'order_id'  => (string) ($order['id'] ?? ''),
                'order_no'  => (string) ($order['order_no'] ?? ''),
                'type'      => 'coupon_order',
            ],
            'line_items'          => [[
                'quantity'   => 1,
                'price_data' => [
                    'currency'     => 'thb',
                    'unit_amount'  => $total * 100,
                    'product_data' => [
                        'name'        => 'คูปองเงินสด ' . $site,
                        'description' => sprintf(
                            'ซื้อ %d ใบ · มูลค่าใช้จริง ฿%s · เลขที่ %s',
                            $qty,
                            number_format($face * $qty),
                            (string) ($order['order_no'] ?? '')
                        ),
                    ],
                ],
            ]],
        ];
        if (!empty($order['buyer_email'])) {
            $params['customer_email'] = (string) $order['buyer_email'];
        }

        return self::client()->checkout->sessions->create($params);
    }

    public static function retrieveCheckoutSession(string $sessionId): \Stripe\Checkout\Session
    {
        return self::client()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['payment_intent'],
        ]);
    }

    public static function fulfillCouponOrderFromSession(\Stripe\Checkout\Session $session): bool
    {
        if (($session->metadata['type'] ?? '') !== 'coupon_order') {
            return false;
        }
        if ($session->payment_status !== 'paid') {
            return false;
        }

        $orderId = (int) ($session->metadata['order_id'] ?? 0);
        if ($orderId <= 0) {
            return false;
        }

        $paymentIntentId = null;
        if (is_string($session->payment_intent)) {
            $paymentIntentId = $session->payment_intent;
        } elseif (is_object($session->payment_intent) && isset($session->payment_intent->id)) {
            $paymentIntentId = (string) $session->payment_intent->id;
        }

        return CouponService::completeStripePayment($orderId, (string) $session->id, $paymentIntentId);
    }

    public static function handleWebhook(string $payload, ?string $sigHeader): void
    {
        $secret = trim((string) Setting::get('payment_gateway_webhook_secret', ''));
        if ($secret === '') {
            throw new \RuntimeException('Stripe webhook secret is not configured.');
        }
        if ($sigHeader === null || $sigHeader === '') {
            throw new \InvalidArgumentException('Missing Stripe-Signature header.');
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            throw new \InvalidArgumentException('Invalid Stripe webhook signature.', 0, $e);
        }

        if ($event->type === 'checkout.session.completed') {
            /** @var \Stripe\Checkout\Session $session */
            $session = $event->data->object;
            self::fulfillCouponOrderFromSession($session);
        }
    }
}
