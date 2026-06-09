<?php

declare(strict_types=1);

namespace App\Controllers\Provider;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\View;
use App\Models\ActivityLeadClick;
use App\Models\ActivityOrder;
use App\Models\ActivityProduct;
use App\Models\ActivityProviderSubscription;

class DashboardController extends Controller
{
    public function index(): void
    {
        $provider = Auth::providerRow();
        $pid = Auth::providerId();
        $stats = [
            'products'       => 0,
            'draft'          => 0,
            'pending_review' => 0,
            'published'      => 0,
            'orders_new'     => 0,
            'orders_confirm' => 0,
            'orders_redeem'  => 0,
            'revenue_month'  => 0.0,
            'payout_pending' => 0.0,
            'payout_paid'    => 0.0,
            'leads_month'    => 0,
            'leads_total'    => 0,
        ];

        if ($pid && ActivityProduct::tableReady()) {
            $stats['products'] = (int)(Database::fetch(
                'SELECT COUNT(*) AS c FROM activity_products WHERE provider_id = :p',
                ['p' => $pid]
            )['c'] ?? 0);
            foreach (['draft', 'pending_review', 'published'] as $st) {
                $stats[$st === 'draft' ? 'draft' : ($st === 'pending_review' ? 'pending_review' : 'published')] = (int)(Database::fetch(
                    'SELECT COUNT(*) AS c FROM activity_products WHERE provider_id = :p AND status = :s',
                    ['p' => $pid, 's' => $st]
                )['c'] ?? 0);
            }
        }

        if ($pid && Database::tableHasColumn('activity_orders', 'id')) {
            $stats['orders_new'] = (int)(Database::fetch(
                "SELECT COUNT(*) AS c FROM activity_orders ao
                 INNER JOIN activity_products ap ON ap.id = ao.product_id
                 WHERE ap.provider_id = :p AND ao.status IN ('paid','pending')",
                ['p' => $pid]
            )['c'] ?? 0);
            $stats['orders_confirm'] = (int)(Database::fetch(
                "SELECT COUNT(*) AS c FROM activity_orders ao
                 INNER JOIN activity_products ap ON ap.id = ao.product_id
                 WHERE ap.provider_id = :p AND ao.status = 'confirmed'",
                ['p' => $pid]
            )['c'] ?? 0);
            $stats['orders_redeem'] = (int)(Database::fetch(
                "SELECT COUNT(*) AS c FROM activity_orders ao
                 INNER JOIN activity_products ap ON ap.id = ao.product_id
                 WHERE ap.provider_id = :p AND ao.status IN ('paid','confirmed')",
                ['p' => $pid]
            )['c'] ?? 0);
            $monthStart = date('Y-m-01 00:00:00');
            $stats['revenue_month'] = (float)(Database::fetch(
                "SELECT COALESCE(SUM(ao.provider_payout), 0) AS s FROM activity_orders ao
                 INNER JOIN activity_products ap ON ap.id = ao.product_id
                 WHERE ap.provider_id = :p AND ao.status IN ('paid','confirmed','redeemed')
                   AND ao.created_at >= :m",
                ['p' => $pid, 'm' => $monthStart]
            )['s'] ?? 0);

            $payout = ActivityOrder::providerPayoutSummary($pid);
            $stats['payout_pending'] = $payout['pending'];
            $stats['payout_paid'] = $payout['paid'];
        }

        if ($pid && ActivityLeadClick::tableReady()) {
            $leads = ActivityLeadClick::providerSummary($pid);
            $stats['leads_month'] = $leads['month'];
            $stats['leads_total'] = $leads['total'];
        }

        $subscription = ($pid && ActivityProviderSubscription::tableReady())
            ? ActivityProviderSubscription::activeForProvider($pid)
            : null;

        View::render('provider/dashboard', [
            'page_title'   => 'ภาพรวมผู้ให้บริการ',
            'provider'     => $provider,
            'stats'        => $stats,
            'isActive'     => Auth::providerIsActive(),
            'subscription' => $subscription,
            'hasSettlement'=> ActivityOrder::hasSettlementColumns(),
        ], 'layouts/provider');
    }
}
