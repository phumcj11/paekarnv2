<?php

namespace App\Services;

use App\Core\Database;
use App\Models\MembershipOrder;
use App\Models\MembershipPlan;
use App\Models\AuditLog;

class MembershipService
{
    public static function createPendingOrder(int $ownerId, int $planId, ?string $slipPath, string $paymentMethod = 'promptpay'): int
    {
        $plan = MembershipPlan::find($planId);
        if (!$plan || !(int)$plan['is_active']) {
            throw new \InvalidArgumentException('แพ็กเกจไม่ถูกต้องหรือปิดการขาย');
        }
        $orderNo = MembershipOrder::generateOrderNo();
        $paidAt = $slipPath ? date('Y-m-d H:i:s') : null;
        $status = $slipPath ? 'paid' : 'pending';

        $orderId = MembershipOrder::create([
            'order_no'       => $orderNo,
            'owner_id'       => $ownerId,
            'plan_id'        => $planId,
            'amount'         => $plan['price'],
            'payment_method' => $paymentMethod,
            'slip_path'      => $slipPath,
            'status'         => $status,
            'paid_at'        => $paidAt,
        ]);

        if ($status === 'paid') {
            self::applyPaidPlan($ownerId, $plan, $orderId);
            $uid = (int)Database::fetch('SELECT user_id FROM owners WHERE id = :o', ['o' => $ownerId])['user_id'];
            NotificationService::send(
                $uid,
                'membership_paid',
                'สมัครสมาชิกสำเร็จ',
                sprintf('แพ็กเกจ %s (%s) เปิดใช้งานแล้ว', $plan['code'], $plan['tier'] === 'vip' ? 'VIP' : 'ธรรมดา'),
                '/owner/membership'
            );
            AuditLog::record('membership_order_auto_paid', ['order_id' => $orderId, 'plan_id' => $planId], 'membership_order', $orderId);
        } else {
            try {
                AdminApprovalNotifyService::membershipOrderPending(
                    $orderNo,
                    (string) $plan['code'],
                    (float) $plan['price'],
                    $orderId
                );
            } catch (\Throwable $e) {
            }
        }

        return $orderId;
    }

    /** แอดมินอนุมัติคำสั่งซื้อที่สถานะ pending */
    public static function approveOrder(int $orderId): void
    {
        $order = Database::fetch(
            'SELECT * FROM membership_orders WHERE id = :id AND status = :st',
            ['id' => $orderId, 'st' => 'pending']
        );
        if (!$order) {
            throw new \RuntimeException('ไม่พบออเดอร์หรือสถานะไม่ใช่รอชำระ');
        }
        $plan = MembershipPlan::find((int)$order['plan_id']);
        if (!$plan) {
            throw new \RuntimeException('ไม่พบแพ็กเกจ');
        }

        Database::update(
            'membership_orders',
            ['status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $orderId]
        );

        self::applyPaidPlan((int)$order['owner_id'], $plan, $orderId);

        $uid = (int)Database::fetch('SELECT user_id FROM owners WHERE id = :o', ['o' => $order['owner_id']])['user_id'];
        NotificationService::send(
            $uid,
            'membership_approved',
            'อนุมัติคำสั่งซื้อสมาชิกแล้ว',
            sprintf('แพ็กเกจ %s เปิดใช้งานแล้ว', $plan['code']),
            '/owner/membership'
        );
        AuditLog::record('membership_order_approved', ['order_id' => $orderId], 'membership_order', $orderId);
    }

    /** แอดมินยกเลิกคำสั่งซื้อที่ยัง pending — ไม่ rollback สิทธิ์ owner (ถ้าเคยได้จากออเดอร์อื่น) */
    public static function cancelOrder(int $orderId): void
    {
        $order = Database::fetch(
            'SELECT id, status FROM membership_orders WHERE id = :id',
            ['id' => $orderId]
        );
        if (!$order) {
            throw new \RuntimeException('ไม่พบออเดอร์');
        }
        if (($order['status'] ?? '') !== 'pending') {
            throw new \RuntimeException('ยกเลิกได้เฉพาะออเดอร์สถานะรอชำระ');
        }
        Database::update(
            'membership_orders',
            ['status' => 'cancelled'],
            'id = :id',
            ['id' => $orderId]
        );
        AuditLog::record('membership_order_cancelled', ['order_id' => $orderId], 'membership_order', $orderId);
    }

    /**
     * @param array<string,mixed> $plan
     */
    private static function applyPaidPlan(int $ownerId, array $plan, int $orderId): void
    {
        $owner = Database::fetch('SELECT membership_expires_at FROM owners WHERE id = :id', ['id' => $ownerId]);
        if (!$owner) {
            return;
        }

        $baseTs = time();
        if (!empty($owner['membership_expires_at']) && strtotime((string)$owner['membership_expires_at']) > $baseTs) {
            $baseTs = strtotime((string)$owner['membership_expires_at']);
        }

        if ((int)$plan['is_lifetime'] === 1) {
            Database::update(
                'owners',
                [
                    'membership_tier'         => $plan['tier'],
                    'membership_expires_at'    => null,
                    'membership_grace_until'   => null,
                ],
                'id = :id',
                ['id' => $ownerId]
            );

            MembershipListingBoostService::syncOwnerBoost($ownerId);

            return;
        }

        $days = (int)($plan['duration_days'] ?? 0);
        if ($days <= 0) {
            return;
        }
        $expires = date('Y-m-d H:i:s', strtotime("+{$days} days", $baseTs));

        Database::update(
            'owners',
            [
                'membership_tier'         => $plan['tier'],
                'membership_expires_at'    => $expires,
                'membership_grace_until'   => null,
            ],
            'id = :id',
            ['id' => $ownerId]
        );

        MembershipListingBoostService::syncOwnerBoost($ownerId);
    }

    /** เปิดรับสมัครแพ็กเกจแล้วหรือยัง (false = แสดง "เปิดให้บริการเร็วๆนี้") */
    public static function salesOpen(): bool
    {
        return false;
    }
}
