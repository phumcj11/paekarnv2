<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class ActivityOrder extends Model
{
    protected static string $table = 'activity_orders';

    public const STATUSES = [
        'pending'   => 'รอตรวจสอบ',
        'paid'      => 'ชำระแล้ว',
        'confirmed' => 'ยืนยันแล้ว',
        'redeemed'  => 'ใช้งานแล้ว',
        'cancelled' => 'ยกเลิก',
    ];

    public static function tableReady(): bool
    {
        return Database::tableHasColumn('activity_orders', 'id');
    }

    public static function hasSettlementColumns(): bool
    {
        return Database::tableHasColumn('activity_orders', 'provider_paid_at');
    }

    /** @return list<string> */
    public static function payoutEligibleStatuses(): array
    {
        return ['paid', 'confirmed', 'redeemed'];
    }

    private static function payoutEligibleSql(): string
    {
        return "ao.status IN ('paid','confirmed','redeemed')";
    }

    /** @return array{month:string,commission:float,payout:float,gross:float,pending_payout:float,paid_payout:float,order_count:int} */
    public static function adminRevenueSummary(?string $monthYm = null): array
    {
        $empty = [
            'month'          => $monthYm ?? '',
            'commission'     => 0.0,
            'payout'         => 0.0,
            'gross'          => 0.0,
            'pending_payout' => 0.0,
            'paid_payout'    => 0.0,
            'order_count'    => 0,
        ];
        if (!self::tableReady()) {
            return $empty;
        }

        $where = [self::payoutEligibleSql()];
        $params = [];
        if ($monthYm !== null && $monthYm !== '' && preg_match('/^\d{4}-\d{2}$/', $monthYm)) {
            $where[] = 'DATE_FORMAT(ao.created_at, "%Y-%m") = :m';
            $params['m'] = $monthYm;
        }

        $paidCol = self::hasSettlementColumns()
            ? 'COALESCE(SUM(CASE WHEN ao.provider_paid_at IS NOT NULL THEN ao.provider_payout ELSE 0 END), 0)'
            : '0';
        $pendingCol = self::hasSettlementColumns()
            ? 'COALESCE(SUM(CASE WHEN ao.provider_paid_at IS NULL THEN ao.provider_payout ELSE 0 END), 0)'
            : 'COALESCE(SUM(ao.provider_payout), 0)';

        $row = Database::fetch(
            'SELECT COALESCE(SUM(ao.commission_amount), 0) AS commission,
                    COALESCE(SUM(ao.provider_payout), 0) AS payout,
                    COALESCE(SUM(ao.total_price), 0) AS gross,
                    COUNT(*) AS order_count,
                    ' . $pendingCol . ' AS pending_payout,
                    ' . $paidCol . ' AS paid_payout
             FROM activity_orders ao
             WHERE ' . implode(' AND ', $where),
            $params
        ) ?: [];

        return [
            'month'          => $monthYm ?? '',
            'commission'     => (float)($row['commission'] ?? 0),
            'payout'         => (float)($row['payout'] ?? 0),
            'gross'          => (float)($row['gross'] ?? 0),
            'pending_payout' => (float)($row['pending_payout'] ?? 0),
            'paid_payout'    => (float)($row['paid_payout'] ?? 0),
            'order_count'    => (int)($row['order_count'] ?? 0),
        ];
    }

    /** @return list<array<string,mixed>> */
    public static function adminRevenueByProvider(?string $monthYm = null, int $limit = 50): array
    {
        if (!self::tableReady()) {
            return [];
        }

        $where = [self::payoutEligibleSql(), 'ap.provider_id IS NOT NULL'];
        $params = [];
        if ($monthYm !== null && $monthYm !== '' && preg_match('/^\d{4}-\d{2}$/', $monthYm)) {
            $where[] = 'DATE_FORMAT(ao.created_at, "%Y-%m") = :m';
            $params['m'] = $monthYm;
        }
        $limit = max(1, min(200, $limit));

        $pendingExpr = self::hasSettlementColumns()
            ? 'COALESCE(SUM(CASE WHEN ao.provider_paid_at IS NULL THEN ao.provider_payout ELSE 0 END), 0)'
            : 'COALESCE(SUM(ao.provider_payout), 0)';
        $paidExpr = self::hasSettlementColumns()
            ? 'COALESCE(SUM(CASE WHEN ao.provider_paid_at IS NOT NULL THEN ao.provider_payout ELSE 0 END), 0)'
            : '0';

        return Database::fetchAll(
            "SELECT pr.id AS provider_id, pr.name AS provider_name,
                    COUNT(*) AS order_count,
                    COALESCE(SUM(ao.commission_amount), 0) AS commission,
                    COALESCE(SUM(ao.provider_payout), 0) AS payout,
                    COALESCE(SUM(ao.total_price), 0) AS gross,
                    {$pendingExpr} AS pending_payout,
                    {$paidExpr} AS paid_payout
             FROM activity_orders ao
             INNER JOIN activity_products ap ON ap.id = ao.product_id
             LEFT JOIN activity_providers pr ON pr.id = ap.provider_id
             WHERE " . implode(' AND ', $where) . "
             GROUP BY pr.id, pr.name
             ORDER BY commission DESC, order_count DESC
             LIMIT {$limit}",
            $params
        );
    }

    /** @return array{pending:float,paid:float,month_pending:float,month_paid:float} */
    public static function providerPayoutSummary(int $providerId): array
    {
        $empty = ['pending' => 0.0, 'paid' => 0.0, 'month_pending' => 0.0, 'month_paid' => 0.0];
        if (!self::tableReady() || $providerId <= 0) {
            return $empty;
        }

        $base = self::payoutEligibleSql() . ' AND ap.provider_id = :pid';
        $params = ['pid' => $providerId];
        $monthStart = date('Y-m-01 00:00:00');

        if (self::hasSettlementColumns()) {
            $all = Database::fetch(
                "SELECT COALESCE(SUM(CASE WHEN ao.provider_paid_at IS NULL THEN ao.provider_payout ELSE 0 END), 0) AS pending,
                        COALESCE(SUM(CASE WHEN ao.provider_paid_at IS NOT NULL THEN ao.provider_payout ELSE 0 END), 0) AS paid
                 FROM activity_orders ao
                 INNER JOIN activity_products ap ON ap.id = ao.product_id
                 WHERE {$base}",
                $params
            ) ?: [];
            $month = Database::fetch(
                "SELECT COALESCE(SUM(CASE WHEN ao.provider_paid_at IS NULL THEN ao.provider_payout ELSE 0 END), 0) AS pending,
                        COALESCE(SUM(CASE WHEN ao.provider_paid_at IS NOT NULL THEN ao.provider_payout ELSE 0 END), 0) AS paid
                 FROM activity_orders ao
                 INNER JOIN activity_products ap ON ap.id = ao.product_id
                 WHERE {$base} AND ao.created_at >= :ms",
                $params + ['ms' => $monthStart]
            ) ?: [];

            return [
                'pending'       => (float)($all['pending'] ?? 0),
                'paid'          => (float)($all['paid'] ?? 0),
                'month_pending' => (float)($month['pending'] ?? 0),
                'month_paid'    => (float)($month['paid'] ?? 0),
            ];
        }

        $all = Database::fetch(
            "SELECT COALESCE(SUM(ao.provider_payout), 0) AS pending FROM activity_orders ao
             INNER JOIN activity_products ap ON ap.id = ao.product_id
             WHERE {$base}",
            $params
        ) ?: [];
        $month = Database::fetch(
            "SELECT COALESCE(SUM(ao.provider_payout), 0) AS pending FROM activity_orders ao
             INNER JOIN activity_products ap ON ap.id = ao.product_id
             WHERE {$base} AND ao.created_at >= :ms",
            $params + ['ms' => $monthStart]
        ) ?: [];

        return [
            'pending'       => (float)($all['pending'] ?? 0),
            'paid'          => 0.0,
            'month_pending' => (float)($month['pending'] ?? 0),
            'month_paid'    => 0.0,
        ];
    }

    /** @return list<array<string,mixed>> */
    public static function adminLatest(int $limit = 100, ?string $monthYm = null, ?string $payoutFilter = null): array
    {
        if (!self::tableReady()) {
            return [];
        }
        $limit = max(1, min(500, $limit));
        $where = ['1=1'];
        $params = [];
        if ($monthYm !== null && $monthYm !== '' && preg_match('/^\d{4}-\d{2}$/', $monthYm)) {
            $where[] = 'DATE_FORMAT(ao.created_at, "%Y-%m") = :m';
            $params['m'] = $monthYm;
        }
        if (self::hasSettlementColumns() && $payoutFilter === 'pending') {
            $where[] = self::payoutEligibleSql();
            $where[] = 'ao.provider_paid_at IS NULL';
        } elseif (self::hasSettlementColumns() && $payoutFilter === 'paid') {
            $where[] = 'ao.provider_paid_at IS NOT NULL';
        }

        return Database::fetchAll(
            "SELECT ao.*, ap.title AS product_title, ap.slug AS product_slug, op.name AS option_name,
                    pr.id AS provider_id, pr.name AS provider_name
             FROM activity_orders ao
             INNER JOIN activity_products ap ON ap.id = ao.product_id
             LEFT JOIN activity_options op ON op.id = ao.option_id
             LEFT JOIN activity_providers pr ON pr.id = ap.provider_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY ao.id DESC
             LIMIT {$limit}",
            $params
        );
    }

    public static function markProviderPaid(int $orderId, string $payoutRef): bool
    {
        if (!self::tableReady() || !self::hasSettlementColumns() || $orderId <= 0) {
            return false;
        }

        $order = Database::fetch(
            'SELECT id, status, provider_paid_at FROM activity_orders WHERE id = :id LIMIT 1',
            ['id' => $orderId]
        );
        if (!$order || !in_array($order['status'], self::payoutEligibleStatuses(), true)) {
            return false;
        }
        if (!empty($order['provider_paid_at'])) {
            return false;
        }

        Database::update('activity_orders', [
            'provider_paid_at'    => date('Y-m-d H:i:s'),
            'provider_payout_ref' => trim($payoutRef) !== '' ? trim($payoutRef) : null,
        ], 'id = :id', ['id' => $orderId]);

        return true;
    }

    public static function clearProviderPaid(int $orderId): bool
    {
        if (!self::tableReady() || !self::hasSettlementColumns() || $orderId <= 0) {
            return false;
        }

        Database::update('activity_orders', [
            'provider_paid_at'    => null,
            'provider_payout_ref' => null,
        ], 'id = :id', ['id' => $orderId]);

        return true;
    }

    public static function generateOrderNo(): string
    {
        return 'ACT-' . date('Ym') . '-' . str_pad((string)random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    public static function generateVoucherCode(): string
    {
        do {
            $code = 'PAK-ACT-' . strtoupper(bin2hex(random_bytes(3)));
            $exists = self::tableReady()
                ? Database::fetch('SELECT id FROM activity_orders WHERE voucher_code = :c LIMIT 1', ['c' => $code])
                : null;
        } while ($exists);

        return $code;
    }

    public static function findByOrderNo(string $orderNo): ?array
    {
        if (!self::tableReady()) {
            return null;
        }

        return Database::fetch(
            "SELECT ao.*, ap.title AS product_title, ap.slug AS product_slug, ap.cover_image,
                    op.name AS option_name
             FROM activity_orders ao
             INNER JOIN activity_products ap ON ap.id = ao.product_id
             LEFT JOIN activity_options op ON op.id = ao.option_id
             WHERE ao.order_no = :o
             LIMIT 1",
            ['o' => $orderNo]
        );
    }

    public static function commissionAmount(array $product, float $subtotal): float
    {
        $type = (string)($product['commission_type'] ?? 'percent');
        $value = (float)($product['commission_value'] ?? 0);
        if ($value <= 0) {
            return 0.0;
        }
        if ($type === 'fixed') {
            return min($subtotal, $value);
        }

        return round($subtotal * ($value / 100), 2);
    }

    /** @return list<array<string,mixed>> */
    public static function forProvider(int $providerId, ?string $status = null, int $limit = 100): array
    {
        if (!self::tableReady() || $providerId <= 0) {
            return [];
        }
        $limit = max(1, min(500, $limit));
        $where = 'ap.provider_id = :pid';
        $params = ['pid' => $providerId];
        if ($status !== null && $status !== '' && array_key_exists($status, self::STATUSES)) {
            $where .= ' AND ao.status = :st';
            $params['st'] = $status;
        }

        return Database::fetchAll(
            "SELECT ao.*, ap.title AS product_title, ap.slug AS product_slug, op.name AS option_name
             FROM activity_orders ao
             INNER JOIN activity_products ap ON ap.id = ao.product_id
             LEFT JOIN activity_options op ON op.id = ao.option_id
             WHERE {$where}
             ORDER BY ao.id DESC
             LIMIT {$limit}",
            $params
        );
    }

    public static function findForProvider(int $orderId, int $providerId): ?array
    {
        if (!self::tableReady() || $providerId <= 0) {
            return null;
        }

        return Database::fetch(
            "SELECT ao.*, ap.title AS product_title, ap.slug AS product_slug, ap.provider_id,
                    op.name AS option_name
             FROM activity_orders ao
             INNER JOIN activity_products ap ON ap.id = ao.product_id
             LEFT JOIN activity_options op ON op.id = ao.option_id
             WHERE ao.id = :id AND ap.provider_id = :pid
             LIMIT 1",
            ['id' => $orderId, 'pid' => $providerId]
        );
    }

    public static function findByVoucherForProvider(string $code, int $providerId): ?array
    {
        if (!self::tableReady() || $providerId <= 0 || $code === '') {
            return null;
        }

        return Database::fetch(
            "SELECT ao.*, ap.title AS product_title, ap.provider_id, op.name AS option_name
             FROM activity_orders ao
             INNER JOIN activity_products ap ON ap.id = ao.product_id
             LEFT JOIN activity_options op ON op.id = ao.option_id
             WHERE ao.voucher_code = :c AND ap.provider_id = :pid
             LIMIT 1",
            ['c' => strtoupper(trim($code)), 'pid' => $providerId]
        );
    }

    /** @return array{ok:bool,msg?:string,order?:array<string,mixed>} */
    public static function redeemVoucher(string $code, int $providerId, ?int $redeemedByUserId = null): array
    {
        $order = self::findByVoucherForProvider($code, $providerId);
        if (!$order) {
            return ['ok' => false, 'msg' => 'ไม่พบ voucher นี้ในระบบของคุณ'];
        }
        if ($order['status'] === 'redeemed') {
            return ['ok' => false, 'msg' => 'Voucher นี้ใช้งานแล้ว'];
        }
        if ($order['status'] === 'cancelled') {
            return ['ok' => false, 'msg' => 'คำสั่งซื้อถูกยกเลิก'];
        }
        if (!in_array($order['status'], ['paid', 'confirmed'], true)) {
            return ['ok' => false, 'msg' => 'ยังไม่พร้อม redeem — สถานะ: ' . ($order['status'] ?? '')];
        }

        $payload = [
            'status'      => 'redeemed',
            'redeemed_at' => date('Y-m-d H:i:s'),
        ];
        if (Database::tableHasColumn('activity_orders', 'redeemed_by') && $redeemedByUserId) {
            $payload['redeemed_by'] = $redeemedByUserId;
        }
        Database::update('activity_orders', $payload, 'id = :id', ['id' => (int)$order['id']]);

        $order['status'] = 'redeemed';

        return ['ok' => true, 'order' => $order];
    }
}
