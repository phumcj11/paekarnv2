<?php

namespace App\Controllers\Owner;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\View;
use App\Models\PropertyLeadClick;
use App\Services\AIService;
use App\Services\AnalyticsPageViewService;
use App\Services\OwnerFeatureGate;
use App\Services\OwnerTier;

class AnalyticsController extends Controller
{
    /** GET /owner/analytics/ai-summary?property_id=N&range=7 — JSON: {ok, summary} */
    public function aiSummary(): void
    {
        $ownerId    = Auth::ownerId();
        $propertyId = (int)($_GET['property_id'] ?? 0);
        $range      = in_array((int)($_GET['range'] ?? 7), [7, 14, 30, 90]) ? (int)$_GET['range'] : 7;

        if (!$propertyId) { $this->json(['ok' => false, 'error' => 'ไม่ระบุที่พัก']); return; }

        if ($ownerId) {
            $owns = Database::fetch("SELECT id FROM properties WHERE id = :p AND owner_id = :o LIMIT 1", ['p' => $propertyId, 'o' => $ownerId]);
            if (!$owns) { $this->json(['ok' => false, 'error' => 'ไม่มีสิทธิ์']); return; }
            if (!OwnerTier::can($ownerId, OwnerTier::FEATURE_ANALYTICS_DEEP)) {
                $this->json(['ok' => false, 'error' => 'ฟีเจอร์นี้ต้องใช้แพ็กเกจ Standard ขึ้นไป']); return;
            }
        }

        $property = Database::fetch("SELECT name, type, zone FROM properties WHERE id = :i LIMIT 1", ['i' => $propertyId]);
        if (!$property) { $this->json(['ok' => false, 'error' => 'ไม่พบที่พัก']); return; }

        $v2Ready = PropertyLeadClick::v2Ready() && AnalyticsPageViewService::v2Ready();
        $stats = ['views' => 0, 'unique_visitors' => 0, 'phone' => 0, 'line' => 0, 'book' => 0, 'map' => 0];

        if (AnalyticsPageViewService::v2Ready()) {
            $v = Database::fetch(
                "SELECT SUM(CASE WHEN is_counted = 1 THEN 1 ELSE 0 END) AS views,
                        COUNT(DISTINCT CASE WHEN is_counted = 1 THEN visitor_hash END) AS unique_visitors
                 FROM analytics_page_views
                 WHERE property_id = :p AND tracking_version = 2
                   AND created_at >= DATE_SUB(CURDATE(), INTERVAL :r DAY)",
                ['p' => $propertyId, 'r' => $range]
            );
            $stats['views'] = (int)($v['views'] ?? 0);
            $stats['unique_visitors'] = (int)($v['unique_visitors'] ?? 0);
        } elseif (Database::tableHasColumn('analytics_page_views', 'id')) {
            $v = Database::fetch(
                "SELECT COUNT(*) AS cnt FROM analytics_page_views
                 WHERE property_id = :p AND created_at >= DATE_SUB(CURDATE(), INTERVAL :r DAY)",
                ['p' => $propertyId, 'r' => $range]
            );
            $stats['views'] = (int)($v['cnt'] ?? 0);
        }

        if (PropertyLeadClick::tableReady()) {
            $counted = $v2Ready ? 'AND is_counted = 1 AND tracking_version = 2' : '';
            $lRow = Database::fetch(
                "SELECT SUM(CASE WHEN click_type='phone' THEN 1 ELSE 0 END) AS phone,
                        SUM(CASE WHEN click_type='line'  THEN 1 ELSE 0 END) AS line,
                        SUM(CASE WHEN click_type='book'  THEN 1 ELSE 0 END) AS book,
                        SUM(CASE WHEN click_type='map'   THEN 1 ELSE 0 END) AS map
                 FROM property_lead_clicks
                 WHERE property_id = :p AND created_at >= DATE_SUB(CURDATE(), INTERVAL :r DAY) {$counted}",
                ['p' => $propertyId, 'r' => $range]
            );
            $stats['phone'] = (int)($lRow['phone'] ?? 0);
            $stats['line']  = (int)($lRow['line'] ?? 0);
            $stats['book']  = (int)($lRow['book'] ?? 0);
            $stats['map']   = (int)($lRow['map'] ?? 0);
        }

        $bookingsRow = Database::fetch(
            "SELECT COUNT(*) AS total, SUM(CASE WHEN status IN ('confirmed','completed') THEN 1 ELSE 0 END) AS confirmed
             FROM bookings b JOIN properties p ON p.id=b.property_id
             WHERE p.id = :pid AND b.created_at >= DATE_SUB(CURDATE(), INTERVAL :r DAY)",
            ['pid' => $propertyId, 'r' => $range]
        );

        $phoneLabel = $v2Ready ? 'คลิกปุ่มโทรที่ไม่ซ้ำ' : 'กดโทร';
        $viewLabel = $v2Ready ? 'ผู้เข้าชมไม่ซ้ำ' : 'ยอดเข้าชมหน้าที่พัก';

        $instruction = sprintf(
            "คุณคือที่ปรึกษาการตลาดสำหรับที่พัก \"%s\" (%s) ในกาญจนบุรี\n\n" .
            "ข้อมูลสถิติ %d วันที่ผ่านมา (Analytics V2 — นับเฉพาะคนจริงที่ไม่ซ้ำ):\n" .
            "- %s: %s\n" .
            "- Page views (คนจริง): %s ครั้ง\n" .
            "- %s: %s ครั้ง\n" .
            "- กด LINE (ไม่ซ้ำ): %s ครั้ง\n" .
            "- กดจอง (ไม่ซ้ำ): %s ครั้ง\n" .
            "- เปิดแผนที่ (ไม่ซ้ำ): %s ครั้ง\n" .
            "- การจองจริงที่สร้าง: %s รายการ (ยืนยัน/เสร็จ: %s)\n\n" .
            "หมายเหตุ: คลิกปุ่มโทร ≠ สายโทรสำเร็จ\n\n" .
            "วิเคราะห์สถิตินี้เป็นภาษาไทยสั้นๆ 2-3 ประโยค บอกว่าน่าสนใจอย่างไร และแนะนำ 1 action ที่เจ้าของควรทำต่อ " .
            "ใช้ภาษาเป็นกันเอง ไม่ต้องใส่หัวข้อหรือ bullet",
            $property['name'],
            $property['type'],
            $range,
            $viewLabel,
            number_format($v2Ready ? $stats['unique_visitors'] : $stats['views']),
            number_format($stats['views']),
            $phoneLabel,
            number_format($stats['phone']),
            number_format($stats['line']),
            number_format($stats['book']),
            number_format($stats['map']),
            number_format((int)($bookingsRow['total'] ?? 0)),
            number_format((int)($bookingsRow['confirmed'] ?? 0))
        );

        $summary = AIService::generate($instruction, '', 0.65, 200);
        if ($summary === null) {
            $this->json(['ok' => false, 'error' => 'AI ไม่พร้อมใช้งาน']); return;
        }
        $this->json(['ok' => true, 'summary' => trim($summary)]);
    }

    /** GET /owner/analytics?property_id=N&range=30 */
    public function index(): void
    {
        if (!OwnerFeatureGate::denyPage(OwnerTier::FEATURE_ANALYTICS, 'Analytics ต้องสมัครแพ็กเกจ Starter ขึ้นไป')) {
            return;
        }
        $ownerId    = Auth::ownerId();
        $properties = $ownerId
            ? Database::fetchAll(
                "SELECT id, name FROM properties WHERE owner_id = :o ORDER BY id ASC",
                ['o' => $ownerId]
            )
            : Database::fetchAll("SELECT id, name FROM properties ORDER BY id ASC");

        $propertyId = (int)($_GET['property_id'] ?? ($properties[0]['id'] ?? 0));
        $range      = in_array((int)($_GET['range'] ?? 30), [7, 14, 30, 90]) ? (int)$_GET['range'] : 30;

        if ($ownerId && $propertyId) {
            $owns = false;
            foreach ($properties as $p) {
                if ((int)$p['id'] === $propertyId) { $owns = true; break; }
            }
            if (!$owns) $propertyId = (int)($properties[0]['id'] ?? 0);
        }

        $canDeep = Auth::isAdmin() || ($ownerId && OwnerTier::can($ownerId, OwnerTier::FEATURE_ANALYTICS_DEEP));
        $v2Ready = PropertyLeadClick::v2Ready() && AnalyticsPageViewService::v2Ready();
        $v2StartedAt = AnalyticsPageViewService::v2StartedAt() ?? PropertyLeadClick::v2StartedAt();

        $hasLeadTable = PropertyLeadClick::tableReady();
        $hasViewTable = Database::tableHasColumn('analytics_page_views', 'id');

        $clicks      = ['phone' => 0, 'line' => 0, 'coupon' => 0, 'book' => 0, 'map' => 0];
        $clicksMonth = ['phone' => 0, 'line' => 0, 'coupon' => 0, 'book' => 0, 'map' => 0];
        $views       = 0;
        $uniqueVisitors = 0;
        $viewsMonth  = 0;
        $uniqueMonth = 0;
        $dailyClicks = [];
        $dailyViews  = [];
        $topReferrers = [];

        $countedFilter = $v2Ready ? 'AND is_counted = 1 AND tracking_version = 2' : '';
        $viewCountedFilter = $v2Ready ? 'AND is_counted = 1 AND tracking_version = 2' : '';

        if ($propertyId) {
            if ($hasLeadTable) {
                $row = Database::fetch(
                    "SELECT
                       SUM(CASE WHEN click_type='phone'  THEN 1 ELSE 0 END) AS phone,
                       SUM(CASE WHEN click_type='line'   THEN 1 ELSE 0 END) AS line,
                       SUM(CASE WHEN click_type='coupon' THEN 1 ELSE 0 END) AS coupon,
                       SUM(CASE WHEN click_type='book'   THEN 1 ELSE 0 END) AS book,
                       SUM(CASE WHEN click_type='map'    THEN 1 ELSE 0 END) AS map
                     FROM property_lead_clicks
                     WHERE property_id = :p AND created_at >= DATE_SUB(CURDATE(), INTERVAL :r DAY) {$countedFilter}",
                    ['p' => $propertyId, 'r' => $range]
                );
                if ($row) {
                    $clicks = [
                        'phone'  => (int)$row['phone'],
                        'line'   => (int)$row['line'],
                        'coupon' => (int)$row['coupon'],
                        'book'   => (int)$row['book'],
                        'map'    => (int)$row['map'],
                    ];
                }

                $rawDaily = Database::fetchAll(
                    "SELECT DATE(created_at) AS day,
                       SUM(CASE WHEN click_type='phone'  THEN 1 ELSE 0 END) AS phone,
                       SUM(CASE WHEN click_type='line'   THEN 1 ELSE 0 END) AS line,
                       SUM(CASE WHEN click_type='book'   THEN 1 ELSE 0 END) AS book
                     FROM property_lead_clicks
                     WHERE property_id = :p AND created_at >= DATE_SUB(CURDATE(), INTERVAL :r DAY) {$countedFilter}
                     GROUP BY DATE(created_at)
                     ORDER BY day ASC",
                    ['p' => $propertyId, 'r' => $range]
                );
                $dailyMap = [];
                foreach ($rawDaily as $r2) { $dailyMap[$r2['day']] = $r2; }
                for ($i = $range - 1; $i >= 0; $i--) {
                    $d = date('Y-m-d', strtotime("-{$i} day"));
                    $dailyClicks[] = [
                        'date'   => date('j/n', strtotime($d)),
                        'phone'  => (int)($dailyMap[$d]['phone'] ?? 0),
                        'line'   => (int)($dailyMap[$d]['line'] ?? 0),
                        'book'   => (int)($dailyMap[$d]['book'] ?? 0),
                    ];
                }

                $rowM = Database::fetch(
                    "SELECT
                       SUM(CASE WHEN click_type='phone'  THEN 1 ELSE 0 END) AS phone,
                       SUM(CASE WHEN click_type='line'   THEN 1 ELSE 0 END) AS line,
                       SUM(CASE WHEN click_type='coupon' THEN 1 ELSE 0 END) AS coupon,
                       SUM(CASE WHEN click_type='book'   THEN 1 ELSE 0 END) AS book,
                       SUM(CASE WHEN click_type='map'    THEN 1 ELSE 0 END) AS map
                     FROM property_lead_clicks
                     WHERE property_id = :p AND DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m') {$countedFilter}",
                    ['p' => $propertyId]
                );
                if ($rowM) {
                    $clicksMonth = [
                        'phone'  => (int)$rowM['phone'],
                        'line'   => (int)$rowM['line'],
                        'coupon' => (int)$rowM['coupon'],
                        'book'   => (int)$rowM['book'],
                        'map'    => (int)$rowM['map'],
                    ];
                }
            }

            if ($hasViewTable) {
                if ($v2Ready) {
                    $vRow = Database::fetch(
                        "SELECT SUM(CASE WHEN is_counted = 1 THEN 1 ELSE 0 END) AS views,
                                COUNT(DISTINCT CASE WHEN is_counted = 1 THEN visitor_hash END) AS unique_visitors
                         FROM analytics_page_views
                         WHERE property_id = :p AND created_at >= DATE_SUB(CURDATE(), INTERVAL :r DAY) {$viewCountedFilter}",
                        ['p' => $propertyId, 'r' => $range]
                    );
                    $views = (int)($vRow['views'] ?? 0);
                    $uniqueVisitors = (int)($vRow['unique_visitors'] ?? 0);

                    $vRowM = Database::fetch(
                        "SELECT SUM(CASE WHEN is_counted = 1 THEN 1 ELSE 0 END) AS views,
                                COUNT(DISTINCT CASE WHEN is_counted = 1 THEN visitor_hash END) AS unique_visitors
                         FROM analytics_page_views
                         WHERE property_id = :p AND DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m') {$viewCountedFilter}",
                        ['p' => $propertyId]
                    );
                    $viewsMonth = (int)($vRowM['views'] ?? 0);
                    $uniqueMonth = (int)($vRowM['unique_visitors'] ?? 0);
                } else {
                    $vRow = Database::fetch(
                        "SELECT COUNT(*) AS cnt FROM analytics_page_views
                         WHERE property_id = :p AND created_at >= DATE_SUB(CURDATE(), INTERVAL :r DAY)",
                        ['p' => $propertyId, 'r' => $range]
                    );
                    $views = (int)($vRow['cnt'] ?? 0);

                    $vRowM = Database::fetch(
                        "SELECT COUNT(*) AS cnt FROM analytics_page_views
                         WHERE property_id = :p AND DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')",
                        ['p' => $propertyId]
                    );
                    $viewsMonth = (int)($vRowM['cnt'] ?? 0);
                }

                $rawV = Database::fetchAll(
                    $v2Ready
                        ? "SELECT DATE(created_at) AS day,
                                  SUM(CASE WHEN is_counted = 1 THEN 1 ELSE 0 END) AS cnt
                           FROM analytics_page_views
                           WHERE property_id = :p AND created_at >= DATE_SUB(CURDATE(), INTERVAL :r DAY) {$viewCountedFilter}
                           GROUP BY DATE(created_at) ORDER BY day ASC"
                        : "SELECT DATE(created_at) AS day, COUNT(*) AS cnt
                           FROM analytics_page_views
                           WHERE property_id = :p AND created_at >= DATE_SUB(CURDATE(), INTERVAL :r DAY)
                           GROUP BY DATE(created_at) ORDER BY day ASC",
                    ['p' => $propertyId, 'r' => $range]
                );
                $dailyVMap = [];
                foreach ($rawV as $rv) { $dailyVMap[$rv['day']] = (int)$rv['cnt']; }
                for ($i = $range - 1; $i >= 0; $i--) {
                    $d = date('Y-m-d', strtotime("-{$i} day"));
                    $dailyViews[] = [
                        'date' => date('j/n', strtotime($d)),
                        'cnt'  => $dailyVMap[$d] ?? 0,
                    ];
                }

                if ($canDeep) {
                    $topReferrers = Database::fetchAll(
                        $v2Ready
                            ? "SELECT COALESCE(referrer_host, '(direct)') AS referrer, COUNT(*) AS cnt
                               FROM analytics_page_views
                               WHERE property_id = :p AND is_counted = 1 AND tracking_version = 2
                                 AND created_at >= DATE_SUB(CURDATE(), INTERVAL :r DAY)
                               GROUP BY referrer_host ORDER BY cnt DESC LIMIT 10"
                            : "SELECT COALESCE(referrer_host, '(direct)') AS referrer, COUNT(*) AS cnt
                               FROM analytics_page_views
                               WHERE property_id = :p AND created_at >= DATE_SUB(CURDATE(), INTERVAL :r DAY)
                               GROUP BY referrer_host ORDER BY cnt DESC LIMIT 10",
                        ['p' => $propertyId, 'r' => $range]
                    );
                }
            }
        }

        $clickRate = $views > 0 ? round(($clicks['phone'] + $clicks['line'] + $clicks['book']) / $views * 100, 1) : 0;

        $bookingsInRange = ['total' => 0, 'confirmed' => 0, 'revenue' => 0.0];
        if ($propertyId) {
            $bRow = Database::fetch(
                "SELECT COUNT(*) AS total,
                        SUM(CASE WHEN b.status IN ('confirmed','completed') THEN 1 ELSE 0 END) AS confirmed,
                        COALESCE(SUM(CASE WHEN b.status IN ('confirmed','completed') THEN b.total_price ELSE 0 END), 0) AS revenue
                 FROM bookings b
                 WHERE b.property_id = :p AND b.created_at >= DATE_SUB(CURDATE(), INTERVAL :r DAY)",
                ['p' => $propertyId, 'r' => $range]
            );
            if ($bRow) {
                $bookingsInRange = [
                    'total'     => (int)$bRow['total'],
                    'confirmed' => (int)$bRow['confirmed'],
                    'revenue'   => (float)$bRow['revenue'],
                ];
            }
        }
        $contactClicks  = $clicks['phone'] + $clicks['line'] + $clicks['book'];
        $viewToContact  = $views > 0 ? round($contactClicks / $views * 100, 1) : 0;
        $contactToBook  = $contactClicks > 0 ? round($bookingsInRange['confirmed'] / $contactClicks * 100, 1) : 0;

        View::render('owner/analytics/index', [
            'aiSummaryUrl' => ($canDeep && $propertyId) ? url('/owner/analytics/ai-summary?property_id=' . $propertyId . '&range=' . $range) : null,
            'canDeep'      => $canDeep,
            'v2Ready'      => $v2Ready,
            'v2StartedAt'  => $v2StartedAt,
            'page_title'   => 'Analytics — สถิติที่พัก',
            'properties'   => $properties,
            'propertyId'   => $propertyId,
            'range'        => $range,
            'clicks'       => $clicks,
            'clicksMonth'  => $clicksMonth,
            'views'        => $views,
            'uniqueVisitors' => $uniqueVisitors,
            'viewsMonth'   => $viewsMonth,
            'uniqueMonth'  => $uniqueMonth,
            'dailyClicks'  => $dailyClicks,
            'dailyViews'   => $dailyViews,
            'clickRate'    => $clickRate,
            'bookingsInRange' => $bookingsInRange,
            'contactClicks'   => $contactClicks,
            'viewToContact'   => $viewToContact,
            'contactToBook'   => $contactToBook,
            'topReferrers' => $topReferrers,
            'hasLeadTable' => $hasLeadTable,
            'hasViewTable' => $hasViewTable,
        ], 'layouts/owner');
    }
}
