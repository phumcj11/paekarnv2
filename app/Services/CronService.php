<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Setting;
use App\Models\ContentPlan;
use App\Services\LineService;
use App\Services\MessageTemplateService;
use App\Services\PropertyLineService;
use App\Services\FacebookService;
use App\Services\ContentPlanPublishService;

/**
 * Job runner for scheduled automation tasks.
 *
 * Run via:
 *   php cli/cron.php           (recommended, run all due jobs)
 *   /cron.php?key=SECRET       (web-trigger from external scheduler)
 */
class CronService
{
    /** Set to true to simulate without sending messages or writing to DB */
    public static bool $dryRun = false;

    /** ทุก job: name => callable returning ['affected'=>n, 'output'=>string] */
    public static function jobs(): array
    {
        return [
            'expire_coupons'           => [self::class, 'expireCoupons'],
            'mark_no_show'             => [self::class, 'markNoShow'],
            'send_checkin_reminders'   => [self::class, 'sendCheckinReminders'],
            'send_checkout_followup'   => [self::class, 'sendCheckoutFollowup'],
            'send_review_requests'     => [self::class, 'sendReviewRequests'],
            'process_message_queue'    => [self::class, 'processMessageQueue'],
            'send_reengagement'        => [self::class, 'sendReengagement'],
            'notify_saturday_vacancy'  => [self::class, 'notifySaturdayVacancy'],
            'owner_weekly_report'      => [self::class, 'ownerWeeklyReport'],
            'cleanup_drafts'           => [self::class, 'cleanupDrafts'],
            'membership_apply_grace'        => [self::class, 'membershipApplyGrace'],
            'membership_downgrade'          => [self::class, 'membershipDowngradeExpired'],
            'membership_warn_expiring'      => [self::class, 'membershipWarnExpiring'],
            'membership_sync_listing_boost' => [self::class, 'membershipSyncListingBoost'],
            'activity_featured_expire'      => [self::class, 'activityFeaturedExpire'],
            'publish_scheduled_posts'       => [self::class, 'publishScheduledPosts'],
        ];
    }

    /** Run ทุก job หรือเฉพาะ job ที่ระบุ */
    public static function runAll(?string $only = null, bool $dryRun = false): array
    {
        self::$dryRun = $dryRun;
        $results = [];
        foreach (self::jobs() as $name => $fn) {
            if ($only && $only !== $name) continue;
            $results[$name] = self::runOne($name, $fn, $dryRun);
        }
        self::$dryRun = false;
        return $results;
    }

    private static function runOne(string $name, callable $fn, bool $dryRun = false): array
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
        if (!$dryRun) {
            Database::insert('cron_logs', [
                'job' => $name, 'status' => $status, 'affected' => $affected,
                'output' => $output, 'duration_ms' => $duration,
            ]);
        }
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
                if (self::$dryRun) {
                    $lineN++;
                } else {
                    try {
                        // ใช้ owner template ถ้ามี, ไม่งั้น fallback เป็น hardcode
                        $sent = MessageTemplateService::sendToGuest((int)$b['id'], 'checkin_reminder_1d');
                        if (!$sent) {
                            $checkInThai  = self::thaiDate($b['check_in']);
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
                        }
                        if ($sent) $lineN++;
                    } catch (\Throwable) { /* never block */ }
                }
            }
        }
        $prefix = self::$dryRun ? '[DRY-RUN] Would send' : 'Sent';
        return [
            'affected' => $n + $lineN,
            'output'   => "$prefix $n in-app + $lineN LINE check-in reminders for $target",
        ];
    }

    private static function thaiDate(string $ymd): string
    {
        if (!$ymd) return $ymd;
        $ts = strtotime($ymd);
        $thaiMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
        return (int)date('j', $ts) . ' ' . $thaiMonths[(int)date('n', $ts)] . ' ' . ((int)date('Y', $ts) + 543);
    }

    /** ส่ง checkout_followup template ให้การจองที่ check_out = วันนี้ */
    public static function sendCheckoutFollowup(): array
    {
        $target = date('Y-m-d');
        $rows = Database::fetchAll(
            "SELECT b.id, b.guest_line_user_id, b.property_id
             FROM bookings b
             WHERE b.status IN ('confirmed','completed')
               AND DATE(b.check_out) = :d
               AND b.guest_line_user_id IS NOT NULL
               AND b.guest_line_user_id != ''
             LIMIT 300",
            ['d' => $target]
        );
        $n = 0;
        foreach ($rows as $b) {
            if (self::$dryRun) { $n++; continue; }
            $sent = MessageTemplateService::sendToGuest((int)$b['id'], 'checkout_followup');
            if ($sent) $n++;
        }
        $prefix = self::$dryRun ? '[DRY-RUN] Would send' : 'Sent';
        return ['affected' => $n, 'output' => "$prefix $n checkout followup messages for $target"];
    }

    /** ส่ง review_request template ให้การจองที่ check_out = 3 วันที่แล้ว */
    public static function sendReviewRequests(): array
    {
        $target = date('Y-m-d', strtotime('-3 days'));
        $rows = Database::fetchAll(
            "SELECT b.id, b.guest_line_user_id, b.property_id
             FROM bookings b
             WHERE b.status IN ('confirmed','completed')
               AND DATE(b.check_out) = :d
               AND b.guest_line_user_id IS NOT NULL
               AND b.guest_line_user_id != ''
             LIMIT 300",
            ['d' => $target]
        );
        $n = 0;
        foreach ($rows as $b) {
            if (self::$dryRun) { $n++; continue; }
            $sent = MessageTemplateService::sendToGuest((int)$b['id'], 'review_request');
            if ($sent) $n++;
        }
        $prefix = self::$dryRun ? '[DRY-RUN] Would send' : 'Sent';
        return ['affected' => $n, 'output' => "$prefix $n review request messages for checkouts on $target"];
    }

    /** ส่งข้อความจาก message_queue ที่ถึงเวลาแล้ว (send_after <= NOW()) */
    public static function processMessageQueue(): array
    {
        if (!Database::tableHasColumn('message_queue', 'id')) {
            return ['affected' => 0, 'output' => 'Skipped (message_queue table missing)'];
        }
        $rows = Database::fetchAll(
            "SELECT * FROM message_queue
              WHERE status = 'pending' AND send_after <= NOW()
              ORDER BY send_after ASC LIMIT 200"
        );
        $sent = 0; $failed = 0;
        foreach ($rows as $row) {
            if (self::$dryRun) { $sent++; continue; }
            try {
                $ok = PropertyLineService::push(
                    (int)$row['property_id'],
                    (string)$row['guest_line_user_id'],
                    [['type' => 'text', 'text' => (string)$row['message_text']]]
                );
                Database::update('message_queue',
                    ['status' => $ok ? 'sent' : 'failed', 'sent_at' => date('Y-m-d H:i:s')],
                    'id = :i', ['i' => $row['id']]
                );
                $ok ? $sent++ : $failed++;
            } catch (\Throwable $e) {
                Database::update('message_queue',
                    ['status' => 'failed', 'sent_at' => date('Y-m-d H:i:s')],
                    'id = :i', ['i' => $row['id']]
                );
                $failed++;
            }
        }
        $prefix = self::$dryRun ? '[DRY-RUN] Would process' : 'Processed';
        return ['affected' => $sent, 'output' => "$prefix message_queue: sent=$sent failed=$failed"];
    }

    /**
     * ส่ง reengagement_30d template ให้ผู้ติดตาม LINE ที่ไม่จองใน 60-90 วัน
     * รันทุกวันจันทร์
     */
    public static function sendReengagement(): array
    {
        if ((int)date('w') !== 1) {
            return ['affected' => 0, 'output' => 'Skipped (only run on Monday)'];
        }

        $ago60  = date('Y-m-d', strtotime('-60 days'));
        $ago90  = date('Y-m-d', strtotime('-90 days'));

        // contacts ที่ booking ล่าสุดอยู่ในช่วง 60-90 วันก่อน (หรือไม่เคยจองเลยแต่ทักมา 90+ วัน)
        $contacts = Database::fetchAll(
            "SELECT plc.id, plc.line_user_id, plc.property_id,
                    MAX(b.check_out) AS last_checkout
             FROM property_line_contacts plc
             LEFT JOIN bookings b
                    ON b.guest_line_user_id = plc.line_user_id
                   AND b.property_id = plc.property_id
                   AND b.status IN ('confirmed','completed')
             WHERE plc.unfollowed_at IS NULL
             GROUP BY plc.id, plc.line_user_id, plc.property_id
             HAVING (last_checkout IS NOT NULL AND last_checkout BETWEEN :ago90 AND :ago60)
                 OR (last_checkout IS NULL AND plc.last_seen_at < :ago90s)
             LIMIT 500",
            ['ago60' => $ago60, 'ago90' => $ago90, 'ago90s' => $ago90 . ' 00:00:00']
        );

        $n = 0;
        foreach ($contacts as $c) {
            if (self::$dryRun) { $n++; continue; }
            $tpl = \App\Services\MessageTemplateService::getTemplate((int)$c['property_id'], 'reengagement_30d');
            if (!$tpl) continue;
            $property = Database::fetch("SELECT name, phone FROM properties WHERE id = :i LIMIT 1", ['i' => $c['property_id']]);
            if (!$property) continue;
            $text = \App\Services\MessageTemplateService::renderForProperty((string)$tpl['message_text'], $property);
            if (!$text) continue;
            try {
                $ok = PropertyLineService::push(
                    (int)$c['property_id'],
                    (string)$c['line_user_id'],
                    [['type' => 'text', 'text' => $text]]
                );
                if ($ok) $n++;
            } catch (\Throwable) {}
        }
        $prefix = self::$dryRun ? '[DRY-RUN] Would send' : 'Sent';
        return ['affected' => $n, 'output' => "$prefix $n reengagement messages (window $ago90 – $ago60)"];
    }

    /**
     * แจ้งเตือนเจ้าของทุกวันพฤหัส–ศุกร์ ถ้าเสาร์นี้ยังมีห้องว่าง
     * ส่งผ่าน LINE OA ของที่พัก (multicast ไม่ได้ ส่งให้เจ้าของด้วย LineService ส่วนตัว)
     */
    public static function notifySaturdayVacancy(): array
    {
        $dow = (int)date('w'); // 0=Sun, 4=Thu, 5=Fri
        if (!in_array($dow, [4, 5], true)) {
            return ['affected' => 0, 'output' => 'Skipped (only run Thu–Fri)'];
        }

        $sat = date('w') === '5'
            ? date('Y-m-d', strtotime('+1 day'))
            : date('Y-m-d', strtotime('+2 days')); // Thursday → Saturday

        $n = 0;
        $properties = Database::fetchAll(
            "SELECT p.id, p.name, p.owner_id,
                    u.id AS unit_id, u.name AS unit_name
             FROM properties p
             JOIN property_units u ON u.property_id = p.id AND u.is_active = 1
             WHERE p.status = 'published'
             ORDER BY p.id"
        );

        $notified = [];
        foreach ($properties as $prop) {
            $pid = (int)$prop['id'];
            $oid = (int)$prop['owner_id'];

            if (isset($notified[$pid])) continue;

            $blocked = Database::fetch(
                "SELECT 1 FROM availability av
                  WHERE av.unit_id = :uid AND av.date = :d
                    AND av.status IN ('closed','blocked','fully_booked') LIMIT 1",
                ['uid' => $prop['unit_id'], 'd' => $sat]
            );
            $booked = Database::fetch(
                "SELECT COUNT(*) AS cnt FROM bookings b
                  WHERE b.unit_id = :uid AND b.status IN ('pending','confirmed')
                    AND b.check_in <= :co AND b.check_out > :ci",
                ['uid' => $prop['unit_id'], 'ci' => $sat, 'co' => date('Y-m-d', strtotime($sat . ' +1 day'))]
            );

            if ($blocked || (int)($booked['cnt'] ?? 0) >= 1) continue;

            // มีห้องว่าง → แจ้งเจ้าของ
            $notified[$pid] = true;
            if (self::$dryRun) { $n++; continue; }

            $ownerUser = Database::fetch(
                "SELECT u.line_user_id FROM owners o JOIN users u ON u.id = o.user_id WHERE o.id = :oid LIMIT 1",
                ['oid' => $oid]
            );
            if (empty($ownerUser['line_user_id'])) continue;

            $thaiMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
            $satThai = (int)date('j', strtotime($sat)) . ' ' . $thaiMonths[(int)date('n', strtotime($sat))] . ' ' . ((int)date('Y', strtotime($sat)) + 543);

            $msg = "📢 แจ้งเตือน: {$prop['name']} ยังว่างวันเสาร์นี้!\n\n"
                 . "📅 {$satThai} ยังมีห้องว่าง\n"
                 . "🔔 อย่าลืม Broadcast ให้ลูกค้า LINE หรืออัปเดตโซเชียลมีเดีย\n\n"
                 . "👉 จัดการได้ที่ paekan.com/owner/line-contacts";

            try {
                if (LineService::push((string)$ownerUser['line_user_id'], $msg)) {
                    $n++;
                }
            } catch (\Throwable) {}
        }
        $prefix = self::$dryRun ? '[DRY-RUN] Would notify' : 'Notified';
        return ['affected' => $n, 'output' => "$prefix $n owners of Saturday vacancy on $sat"];
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

    /** แจ้งเตือนก่อนหมดอายุ — in-app + LINE push ผ่าน OA แพกาญ.com (วันกำหนดใน membership_warn_days) */
    public static function membershipWarnExpiring(): array
    {
        $intervals = self::membershipWarnDayIntervals();
        $n = 0;
        $lineN = 0;
        $skipped = 0;
        $thaiMonths = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
        $renewUrl = rtrim((string) \App\Core\Application::$publicUrl, '/') . '/owner/membership';

        foreach ($intervals as $days) {
            $rows = Database::fetchAll(
                "SELECT o.id, o.user_id, o.membership_tier, o.membership_expires_at
                 FROM owners o
                 WHERE o.membership_tier IN ('standard','vip')
                 AND o.membership_expires_at IS NOT NULL
                 AND o.membership_expires_at > NOW()
                 AND DATE(o.membership_expires_at) = DATE(DATE_ADD(CURDATE(), INTERVAL :d DAY))
                 LIMIT 500",
                ['d' => $days]
            );

            foreach ($rows as $r) {
                $userId = (int) $r['user_id'];
                $notifType = "membership_expiring_{$days}d";
                if (self::membershipExpiryAlreadyNotified($userId, $notifType)) {
                    $skipped++;
                    continue;
                }

                $expTs    = strtotime((string)$r['membership_expires_at']);
                $dayStr   = (int)date('j', $expTs);
                $monStr   = $thaiMonths[(int)date('n', $expTs)];
                $yearStr  = (int)date('Y', $expTs) + 543;
                $expThai  = "{$dayStr} {$monStr} {$yearStr}";
                $tierLabel = $r['membership_tier'] === 'vip' ? 'VIP' : 'Standard';
                $typeLabel = $days === 1 ? 'พรุ่งนี้' : "อีก {$days} วัน";

                NotificationService::send(
                    $userId,
                    $notifType,
                    "สมาชิกจะหมดอายุใน {$days} วัน",
                    "แพ็กเกจ {$tierLabel} จะหมดอายุวันที่ {$expThai} — ต่ออายุได้ที่หน้าสมาชิกเจ้าของแพ",
                    '/owner/membership'
                );
                $n++;

                try {
                    $uRow = Database::fetch(
                        'SELECT line_user_id FROM users WHERE id = :uid LIMIT 1',
                        ['uid' => $userId]
                    );
                    if (!empty($uRow['line_user_id'])) {
                        $msg = "⚠️ แจ้งเตือนสมาชิกแพกาญ.com\n\n"
                             . "แพ็กเกจ {$tierLabel} ของคุณจะหมดอายุ{$typeLabel}\n"
                             . "📅 วันหมดอายุ: {$expThai}\n\n"
                             . "ต่ออายุตอนนี้เพื่อคงสิทธิ์โปรโมตที่พัก\n"
                             . "👉 {$renewUrl}";
                        if (LineService::push((string)$uRow['line_user_id'], $msg)) {
                            $lineN++;
                        }
                    }
                } catch (\Throwable) {
                }
            }
        }

        return [
            'affected' => $n + $lineN,
            'output'   => "Sent {$n} in-app + {$lineN} LINE warnings (skipped {$skipped} duplicates)",
        ];
    }

    /** @return list<int> */
    private static function membershipWarnDayIntervals(): array
    {
        $raw = trim((string) Setting::get('membership_warn_days', '30,7,3,1'));
        if ($raw === '') {
            return [30, 7, 3, 1];
        }
        $days = [];
        foreach (preg_split('/[\s,;]+/', $raw) ?: [] as $part) {
            $d = (int) $part;
            if ($d >= 1 && $d <= 365 && !in_array($d, $days, true)) {
                $days[] = $d;
            }
        }
        rsort($days);

        return $days !== [] ? $days : [30, 7, 3, 1];
    }

    private static function membershipExpiryAlreadyNotified(int $userId, string $type): bool
    {
        if ($userId <= 0) {
            return true;
        }
        try {
            $row = Database::fetch(
                "SELECT id FROM notifications
                 WHERE user_id = :u AND type = :t AND channel = 'in_app'
                   AND DATE(created_at) = CURDATE()
                 LIMIT 1",
                ['u' => $userId, 't' => $type]
            );

            return !empty($row);
        } catch (\Throwable) {
            return false;
        }
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

    /** Auto-publish content_plans ที่ status=scheduled และ post_date <= วันนี้ */
    public static function publishScheduledPosts(): array
    {
        if (!Database::tableHasColumn('content_plans', 'id')) {
            return ['affected' => 0, 'output' => 'Skipped (content_plans table missing)'];
        }

        $today = date('Y-m-d');
        $rows  = Database::fetchAll(
            "SELECT cp.*, p.facebook_page_id, p.facebook_page_token, p.line_channel_access_token
             FROM content_plans cp
             LEFT JOIN properties p ON p.id = cp.property_id
             WHERE cp.status = 'scheduled'
               AND cp.post_date <= :d
               AND cp.platform IN ('facebook','line','instagram')
             ORDER BY cp.post_date ASC, cp.id ASC
             LIMIT 50",
            ['d' => $today]
        );

        $published = 0;
        $skipped   = 0;
        $failed    = 0;

        foreach ($rows as $plan) {
            $propId = (int)($plan['property_id'] ?? 0);
            if (!$propId) {
                $skipped++;
                continue;
            }

            $platform = (string)($plan['platform'] ?? 'facebook');
            if ($platform === 'facebook') {
                if (!FacebookService::isConfigured() || empty($plan['facebook_page_token'])) {
                    $skipped++;
                    continue;
                }
            } elseif ($platform === 'line') {
                if (empty(trim((string)($plan['line_channel_access_token'] ?? '')))) {
                    $skipped++;
                    continue;
                }
            } elseif ($platform === 'instagram') {
                if (!FacebookService::isConfigured() || empty($plan['facebook_page_token'])) {
                    $skipped++;
                    continue;
                }
            }

            if (self::$dryRun) {
                $published++;
                continue;
            }

            $result = ContentPlanPublishService::publish($plan, null);
            if (empty($result['ok'])) {
                $failed++;
                continue;
            }

            ContentPlanPublishService::markPublished((int)$plan['id'], (int)$plan['owner_id']);
            $published++;
        }

        $prefix = self::$dryRun ? '[DRY-RUN] Would publish' : 'Published';
        return [
            'affected' => $published,
            'output'   => "{$prefix} {$published} scheduled posts (skipped={$skipped} failed={$failed})",
        ];
    }
}
