<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Setting;

/**
 * Job runner for scheduled automation tasks.
 *
 * Run via:
 *   php cli/cron.php           (recommended, run all due jobs)
 *   /cron.php?key=SECRET       (web-trigger from external scheduler)
 */
class CronService
{
    /** ทุก job: name => callable returning ['affected'=>n, 'output'=>string] */
    public static function jobs(): array
    {
        return [
            'expire_coupons'      => [self::class, 'expireCoupons'],
            'mark_no_show'        => [self::class, 'markNoShow'],
            'send_checkin_reminders' => [self::class, 'sendCheckinReminders'],
            'owner_weekly_report' => [self::class, 'ownerWeeklyReport'],
            'cleanup_drafts'      => [self::class, 'cleanupDrafts'],
            'membership_apply_grace'   => [self::class, 'membershipApplyGrace'],
            'membership_downgrade'      => [self::class, 'membershipDowngradeExpired'],
            'membership_warn_expiring' => [self::class, 'membershipWarnExpiring'],
            'membership_sync_listing_boost' => [self::class, 'membershipSyncListingBoost'],
            'activity_featured_expire'      => [self::class, 'activityFeaturedExpire'],
        ];
    }

    /** Run ทุก job หรือเฉพาะ job ที่ระบุ */
    public static function runAll(?string $only = null): array
    {
        $results = [];
        foreach (self::jobs() as $name => $fn) {
            if ($only && $only !== $name) continue;
            $results[$name] = self::runOne($name, $fn);
        }
        return $results;
    }

    private static function runOne(string $name, callable $fn): array
    {
        $start = microtime(true);
        $status = 'success'; $output = ''; $affected = 0;
        try {
            $r = $fn();
            $affected = (int)($r['affected'] ?? 0);
            $output   = (string)($r['output'] ?? '');
        } catch (\Throwable $e) {
            $status = 'failed'; $output = $e->getMessage();
        }
        $duration = (int)round((microtime(true) - $start) * 1000);
        Database::insert('cron_logs', [
            'job' => $name, 'status' => $status, 'affected' => $affected,
            'output' => $output, 'duration_ms' => $duration,
        ]);
        return compact('status','affected','output','duration');
    }

    // --------------------- JOBS ------------------------

    public static function expireCoupons(): array
    {
        $rows = Database::fetchAll("SELECT id, code, customer_id FROM coupons WHERE status IN ('unused','reserved') AND expires_at < NOW() LIMIT 500");
        $n = 0;
        foreach ($rows as $r) {
            Database::update('coupons', ['status' => 'expired'], 'id = :i', ['i' => $r['id']]);
            $n++;
            // notify customer
            if ($r['customer_id']) {
                $u = Database::fetch("SELECT user_id FROM customers WHERE id = :c", ['c' => $r['customer_id']]);
                if ($u) {
                    NotificationService::send((int)$u['user_id'], 'coupon_expired',
                        'คูปองหมดอายุแล้ว ⏰',
                        "คูปอง {$r['code']} ของคุณหมดอายุการใช้งาน",
                        '/account/coupons');
                }
            }
        }
        return ['affected' => $n, 'output' => "Expired $n coupons"];
    }

    public static function markNoShow(): array
    {
        // Bookings ที่ confirmed แต่เลย check_out 1 วันแล้วยังไม่ complete
        $rows = Database::fetchAll(
            "SELECT id, code FROM bookings WHERE status='confirmed' AND check_out < DATE_SUB(NOW(), INTERVAL 1 DAY) LIMIT 200"
        );
        $n = 0;
        foreach ($rows as $r) {
            Database::update('bookings', ['status' => 'completed'], 'id = :i', ['i' => $r['id']]);
            $n++;
        }
        // และ pending ที่เลย check_in ไป → cancelled
        $stale = Database::fetchAll(
            "SELECT id FROM bookings WHERE status='pending' AND check_in < DATE_SUB(NOW(), INTERVAL 1 DAY) LIMIT 200"
        );
        foreach ($stale as $r) {
            Database::update('bookings', ['status' => 'cancelled'], 'id = :i', ['i' => $r['id']]);
            $n++;
        }
        return ['affected' => $n, 'output' => "Auto-completed/cancelled $n stale bookings"];
    }

    public static function sendCheckinReminders(): array
    {
        $days = (int)Setting::get('reminder_days_before_checkin', 2);
        if ($days < 1) $days = 2;
        $target = date('Y-m-d', strtotime("+$days day"));
        $rows = Database::fetchAll(
            "SELECT b.*, p.id AS property_id, p.name AS pname, p.phone AS pphone, p.check_in AS pci, p.check_out AS pco
             FROM bookings b
             JOIN properties p ON p.id = b.property_id
             WHERE b.status='confirmed' AND b.check_in = :d", ['d' => $target]
        );
        $n = 0;
        $lineN = 0;
        foreach ($rows as $b) {
            // In-app notification สำหรับลูกค้าที่ login ผ่านเว็บ
            if ($b['customer_id']) {
                $u = Database::fetch("SELECT user_id FROM customers WHERE id = :c", ['c' => $b['customer_id']]);
                if ($u) {
                    NotificationService::send((int)$u['user_id'], 'checkin_reminder',
                        "เตือน: เช็คอินอีก $days วัน",
                        sprintf('การจอง #%s ที่ "%s" — เช็คอินวันที่ %s\nเบอร์ติดต่อที่พัก: %s',
                            $b['code'], $b['pname'], format_date_th($b['check_in']), $b['pphone'] ?: '-'),
                        '/account/bookings');
                    $n++;
                }
            }

            // LINE push สำหรับจองที่ผูก guest_line_user_id ไว้
            if (!empty($b['guest_line_user_id'])) {
                try {
                    $checkInThai = self::thaiDate($b['check_in']);
                    $checkOutThai = self::thaiDate($b['check_out']);
                    $daysWord = $days === 1 ? 'พรุ่งนี้' : "อีก $days วัน";
                    $msg = "แจ้งเตือน: เช็คอิน{$daysWord} 🏕️\n\n"
                         . "📋 การจอง #{$b['code']}\n"
                         . "🏡 {$b['pname']}\n"
                         . "📅 เช็คอิน: {$checkInThai}\n"
                         . "📅 เช็คเอาท์: {$checkOutThai}\n";
                    if ($b['pci']) $msg .= "🕐 เวลาเช็คอิน: {$b['pci']} น.\n";
                    if ($b['pphone']) $msg .= "📞 ติดต่อที่พัก: {$b['pphone']}";

                    $sent = PropertyLineService::push(
                        (int)$b['property_id'],
                        (string)$b['guest_line_user_id'],
                        [['type' => 'text', 'text' => $msg]]
                    );
                    if ($sent) $lineN++;
                } catch (\Throwable) { /* never block */ }
            }
        }
        return [
            'affected' => $n + $lineN,
            'output'   => "Sent $n in-app + $lineN LINE check-in reminders for $target",
        ];
    }

    private static function thaiDate(string $ymd): string
    {
        if (!$ymd) return $ymd;
        $ts = strtotime($ymd);
        $thaiMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
        return (int)date('j', $ts) . ' ' . $thaiMonths[(int)date('n', $ts)] . ' ' . ((int)date('Y', $ts) + 543);
    }

    public static function ownerWeeklyReport(): array
    {
        // ส่งทุกวันจันทร์
        if ((int)date('w') !== 1) return ['affected' => 0, 'output' => 'Skipped (only run on Monday)'];

        $owners = Database::fetchAll(
            "SELECT o.id, o.user_id FROM owners o JOIN users u ON u.id=o.user_id WHERE o.partner_status='active' AND u.status='active'"
        );
        $n = 0;
        $lastWeek = date('Y-m-d H:i:s', strtotime('-7 day'));
        foreach ($owners as $o) {
            $stats = Database::fetch(
                "SELECT
                    COUNT(*) AS total_bookings,
                    SUM(CASE WHEN b.status='confirmed' THEN 1 ELSE 0 END) AS confirmed,
                    COALESCE(SUM(CASE WHEN b.status IN('confirmed','completed') THEN b.total_price ELSE 0 END),0) AS revenue,
                    COUNT(DISTINCT cu.id) AS coupon_used
                 FROM bookings b
                 JOIN properties p ON p.id = b.property_id AND p.owner_id = :oid
                 LEFT JOIN coupon_usages cu ON cu.property_id = p.id AND cu.used_at >= :since
                 WHERE b.created_at >= :since",
                ['oid' => $o['id'], 'since' => $lastWeek]
            );
            $msg = sprintf(
                "📊 สรุปสัปดาห์ที่ผ่านมา\n• การจองใหม่: %d รายการ (ยืนยัน %d)\n• คูปองที่ถูกใช้: %d\n• รายได้รวม: ฿%s",
                $stats['total_bookings'] ?? 0, $stats['confirmed'] ?? 0, $stats['coupon_used'] ?? 0,
                number_format($stats['revenue'] ?? 0)
            );
            NotificationService::send((int)$o['user_id'], 'weekly_report', 'รายงานประจำสัปดาห์ของคุณ', $msg, '/owner/dashboard');
            $n++;
        }
        return ['affected' => $n, 'output' => "Sent weekly report to $n owners"];
    }

    public static function cleanupDrafts(): array
    {
        // Delete properties marked draft for 60+ days with no units
        $rows = Database::fetchAll(
            "SELECT p.id FROM properties p
             WHERE p.status='draft' AND p.created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)
             AND NOT EXISTS (SELECT 1 FROM property_units u WHERE u.property_id = p.id)"
        );
        $n = 0;
        foreach ($rows as $r) {
            Database::delete('properties', 'id = :i', ['i' => $r['id']]);
            $n++;
        }
        return ['affected' => $n, 'output' => "Removed $n stale draft properties"];
    }

    /** หลังหมดอายุแพ็กเกจ: ตั้งช่วง grace จาก membership_grace_days */
    public static function membershipApplyGrace(): array
    {
        $days = (int)Setting::get('membership_grace_days', 7);
        if ($days <= 0) {
            return ['affected' => 0, 'output' => 'Skipped membership_apply_grace (membership_grace_days <= 0)'];
        }

        $rows = Database::fetchAll(
            "SELECT id, membership_expires_at FROM owners
             WHERE membership_tier IN ('standard','vip')
             AND membership_expires_at IS NOT NULL
             AND membership_expires_at < NOW()
             AND membership_grace_until IS NULL
             LIMIT 500"
        );

        $n = 0;
        foreach ($rows as $r) {
            $expTs = strtotime((string)$r['membership_expires_at']);
            $graceEnd = date('Y-m-d H:i:s', strtotime("+{$days} days", $expTs));
            Database::update('owners', ['membership_grace_until' => $graceEnd], 'id = :id', ['id' => $r['id']]);
            $n++;
        }

        return ['affected' => $n, 'output' => "Applied grace period to $n owners"];
    }

    /** ปรับ tier เป็น none เมื่อเกิน grace หรือไม่มี grace และแพ็กเกจหมดแล้ว */
    public static function membershipDowngradeExpired(): array
    {
        $graceDays = (int)Setting::get('membership_grace_days', 7);
        $n = 0;

        $rows = Database::fetchAll(
            "SELECT id, user_id FROM owners
             WHERE membership_tier IN ('standard','vip')
             AND membership_grace_until IS NOT NULL
             AND membership_grace_until < NOW()
             LIMIT 500"
        );
        foreach ($rows as $r) {
            self::finalizeMembershipDowngrade((int)$r['id'], (int)$r['user_id']);
            $n++;
        }

        if ($graceDays <= 0) {
            $rows2 = Database::fetchAll(
                "SELECT id, user_id FROM owners
                 WHERE membership_tier IN ('standard','vip')
                 AND membership_expires_at IS NOT NULL
                 AND membership_expires_at < NOW()
                 AND membership_grace_until IS NULL
                 LIMIT 500"
            );
            foreach ($rows2 as $r) {
                self::finalizeMembershipDowngrade((int)$r['id'], (int)$r['user_id']);
                $n++;
            }
        }

        return ['affected' => $n, 'output' => "Downgraded $n expired memberships"];
    }

    /** แจ้งเตือนก่อนหมดอายุ 7 วัน (สมัครแบบมีวันหมด) */
    public static function membershipWarnExpiring(): array
    {
        $rows = Database::fetchAll(
            "SELECT id, user_id, membership_expires_at FROM owners
             WHERE membership_tier IN ('standard','vip')
             AND membership_expires_at IS NOT NULL
             AND membership_expires_at > NOW()
             AND DATE(membership_expires_at) = DATE(DATE_ADD(CURDATE(), INTERVAL 7 DAY))
             LIMIT 500"
        );

        $n = 0;
        foreach ($rows as $r) {
            NotificationService::send(
                (int)$r['user_id'],
                'membership_expiring_7d',
                'สมาชิกจะหมดอายุใน 7 วัน',
                sprintf(
                    'แพ็กเกจของคุณจะหมดอายุวันที่ %s — ต่ออายุได้ที่หน้าสมาชิกเจ้าของแพ',
                    format_date_th($r['membership_expires_at'])
                ),
                '/owner/membership'
            );
            $n++;
        }

        return ['affected' => $n, 'output' => "Sent $n membership expiry warnings"];
    }

    private static function finalizeMembershipDowngrade(int $ownerId, int $userId): void
    {
        MembershipListingBoostService::stripBoostForOwner($ownerId);

        Database::update(
            'owners',
            [
                'membership_tier'          => 'none',
                'membership_expires_at'    => null,
                'membership_grace_until'   => null,
            ],
            'id = :id',
            ['id' => $ownerId]
        );

        NotificationService::send(
            $userId,
            'membership_expired',
            'สมาชิกเจ้าของแพหมดอายุแล้ว',
            'สิทธิ์สมาชิกของคุณสิ้นสุดแล้ว คุณสามารถต่ออายุได้ที่หน้าสมาชิกเพื่อเปิดฟีเจอร์พิเศษอีกครั้ง',
            '/owner/membership'
        );
    }

    /** Phase 2 — sync priority/is_featured จากแพ็กสมาชิก (ต้องรัน migration คอลัมน์ membership_*) */
    public static function membershipSyncListingBoost(): array
    {
        if (!MembershipListingBoostService::columnsAvailable()) {
            return ['affected' => 0, 'output' => 'Skipped membership_sync_listing_boost (columns missing — run migration 20260213_monetization_listing_boost.sql)'];
        }

        $ids = [];
        foreach (Database::fetchAll("SELECT id FROM owners WHERE membership_tier IN ('standard','vip')") as $r) {
            $ids[(int) $r['id']] = true;
        }
        foreach (Database::fetchAll(
            'SELECT DISTINCT owner_id AS id FROM properties WHERE owner_id IS NOT NULL AND (membership_priority_boost <> 0 OR membership_featured_applied <> 0)'
        ) as $r) {
            $ids[(int) $r['id']] = true;
        }

        $n = 0;
        foreach (array_keys($ids) as $oid) {
            if ($oid <= 0) {
                continue;
            }
            MembershipListingBoostService::syncOwnerBoost($oid);
            $n++;
        }

        return ['affected' => $n, 'output' => "membership_sync_listing_boost: synced $n owner(s)"];
    }

    /** Provider monetization — ปิด featured campaign ที่หมดอายุ */
    public static function activityFeaturedExpire(): array
    {
        return \App\Services\ActivityFeaturedService::expireCampaigns();
    }
}
