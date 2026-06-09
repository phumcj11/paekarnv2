<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Setting;

/**
 * จับคู่เจ้าของ VIP ที่มีที่พัก published ตรงโซน/ประเภท/งบ แล้วแจ้งเตือน
 */
class GuestSeekLeadService
{
    /**
     * @return list<array{owner_id:int,user_id:int}>
     */
    public static function matchRecipientOwners(?string $zone, ?string $propertyType, ?float $budgetMax): array
    {
        $cap = max(1, min(200, (int)Setting::get('lead_broadcast_max', 50)));
        $zone = trim((string)$zone);
        $ptype = trim((string)$propertyType);
        $budget = $budgetMax !== null && $budgetMax > 0 ? $budgetMax : null;

        $sql = "SELECT DISTINCT ow.id AS owner_id, u.id AS user_id
                FROM properties p
                INNER JOIN owners ow ON ow.id = p.owner_id
                INNER JOIN users u ON u.id = ow.user_id
                WHERE p.status = 'published'
                  AND ow.partner_status = 'active'
                  AND ow.membership_tier = 'vip'
                  AND (
                        ow.membership_expires_at IS NULL
                        OR ow.membership_expires_at > NOW()
                        OR (ow.membership_grace_until IS NOT NULL AND ow.membership_grace_until > NOW())
                      )
                  AND (:zone = '' OR p.zone = :zone)
                  AND (:ptype = '' OR p.type = :ptype)
                  AND (:budget IS NULL OR p.min_price <= :budget)
                LIMIT {$cap}";

        return Database::fetchAll($sql, [
            'zone'   => $zone,
            'ptype'  => $ptype,
            'budget' => $budget,
        ]);
    }

    /**
     * @param array<string,mixed> $lead แถว leads หลัง insert
     * @param list<array{owner_id:int,user_id:int}> $recipients
     */
    public static function notifyRecipients(array $lead, array $recipients): void
    {
        if ($recipients === []) {
            return;
        }

        $summary = self::formatLeadText($lead);
        $title   = 'มีลูกค้าต้องการหาที่พัก (VIP)';
        $link    = '/owner/dashboard';

        foreach ($recipients as $r) {
            $meta = ['_force_email' => true];
            NotificationService::send(
                (int)$r['user_id'],
                'guest_seek_lead',
                $title,
                $summary,
                $link,
                $meta
            );
        }

        $token = (string)Setting::get('lead_seek_line_notify_token', '');
        if ($token !== '') {
            $groupMsg = "[แพกาญ] {$title}\n\n{$summary}";
            LineNotifyService::push($token, $groupMsg);
        }
    }

    /** @param array<string,mixed> $lead */
    public static function formatLeadText(array $lead): string
    {
        $lines = [
            'ชื่อ: ' . ($lead['name'] ?? ''),
            'โทร: ' . ($lead['phone'] ?? '-'),
            'อีเมล: ' . ($lead['email'] ?? '-'),
            'LINE: ' . ($lead['line_contact'] ?? '-'),
            'โซน: ' . ($lead['preferred_zone'] ?? '-'),
            'ประเภทที่พัก: ' . ($lead['preferred_property_type'] ?? '-'),
            'งบสูงสุด: ' . (isset($lead['budget_max']) && $lead['budget_max'] !== null ? number_format((float)$lead['budget_max']) . ' บาท' : '-'),
            'เข้าพัก: ' . ($lead['check_in'] ?? '-') . ' → ' . ($lead['check_out'] ?? '-'),
            'จำนวนคน: ' . ($lead['guest_count'] ?? '-'),
            'รายละเอียด: ' . mb_substr(strip_tags((string)($lead['message'] ?? '')), 0, 500),
        ];

        return implode("\n", $lines);
    }
}
