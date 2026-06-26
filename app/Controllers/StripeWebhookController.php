<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\StripeService;

class StripeWebhookController extends Controller
{
    public function stripe(): void
    {
        $payload = file_get_contents('php://input');
        if ($payload === false) {
            $payload = '';
        }

        try {
            StripeService::handleWebhook($payload, $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null);
            $this->json(['received' => true]);
        } catch (\InvalidArgumentException $e) {
            $this->json(['error' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            error_log('[Stripe webhook] ' . $e->getMessage());
            $this->json(['error' => 'Webhook handler failed'], 500);
        }
    }
}
