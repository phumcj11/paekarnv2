<?php

namespace App\Controllers\Owner;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\View;

class AnalyticsController extends Controller
{
    /** GET /owner/analytics?property_id=N&range=30 */
    public function index(): void
    {
        $ownerId    = Auth::ownerId();
        $properties = $ownerId
            ? Database::fetchAll(
                "SELECT id, name FROM properties WHERE owner_id = :o ORDER BY id ASC",
                ['o' => $ownerId]
            )
            : Database::fetchAll("SELECT id, name FROM properties ORDER BY id ASC");

        $propertyId = (int)($_GET['property_id'] ?? ($properties[0]['id'] ?? 0));
        $range      = in_array((int)($_GET['range'] ?? 30), [7, 14, 30, 90]) ? (int)$_GET['range'] : 30;

        // guard: owner ต้องเป็นเจ้าของที่พัก
        if ($ownerId && $propertyId) {
            $owns = false;
            foreach ($properties as $p) {
                if ((int)$p['id'] === $propertyId) { $owns = true; break; }
            }
            if (!$owns) $propertyId = (int)($properties[0]['id'] ?? 0);
        }

        $hasLeadTable = Database::tableHasColumn('property_lead_clicks', 'id');
        $hasViewTable = Database::tableHasColumn('analytics_page_views', 'id');

        $clicks      = ['phone' => 0, 'line' => 0, 'coupon' => 0, 'book' => 0];
        $clicksMonth = ['phone' => 0, 'line' => 0, 'coupon' => 0, 'book' => 0];
        $views       = 0;
        $viewsMonth  = 0;
        $dailyClicks = [];
        $dailyViews  = [];
        $topDays     = [];

        if ($propertyId) {
            if ($hasLeadTable) {
                // ยอดรวมตลอด / เดือนนี้
                $row = Database::fetch(
                    "SELECT
                       SUM(CASE WHEN click_type='phone'  THEN 1 ELSE 0 END) AS phone,
                       SUM(CASE WHEN click_type='line'   THEN 1 ELSE 0 END) AS line,
                       SUM(CASE WHEN click_type='coupon' THEN 1 ELSE 0 END) AS coupon,
                       SUM(CASE WHEN click_type='book'   THEN 1 ELSE 0 END) AS book
                     FROM property_lead_clicks
                     WHERE property_id = :p AND created_at >= DATE_SUB(CURDATE(), INTERVAL :r DAY)",
                    ['p' => $propertyId, 'r' => $range]
                );
                if ($row) {
                    $clicks = [
                        'phone'  => (int)$row['phone'],
                        'line'   => (int)$row['line'],
                        'coupon' => (int)$row['coupon'],
                        'book'   => (int)$row['book'],
                    ];
                }

                // รายวัน (สำหรับกราฟ)
                $rawDaily = Database::fetchAll(
                    "SELECT DATE(created_at) AS day,
                       SUM(CASE WHEN click_type='phone'  THEN 1 ELSE 0 END) AS phone,
                       SUM(CASE WHEN click_type='line'   THEN 1 ELSE 0 END) AS line,
                       SUM(CASE WHEN click_type='book'   THEN 1 ELSE 0 END) AS book
                     FROM property_lead_clicks
                     WHERE property_id = :p AND created_at >= DATE_SUB(CURDATE(), INTERVAL :r DAY)
                     GROUP BY DATE(created_at)
                     ORDER BY day ASC",
                    ['p' => $propertyId, 'r' => $range]
                );
                // เติมวันที่ขาด
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

                // เดือนนี้
                $rowM = Database::fetch(
                    "SELECT
                       SUM(CASE WHEN click_type='phone'  THEN 1 ELSE 0 END) AS phone,
                       SUM(CASE WHEN click_type='line'   THEN 1 ELSE 0 END) AS line,
                       SUM(CASE WHEN click_type='coupon' THEN 1 ELSE 0 END) AS coupon,
                       SUM(CASE WHEN click_type='book'   THEN 1 ELSE 0 END) AS book
                     FROM property_lead_clicks
                     WHERE property_id = :p AND DATE_FORMAT(created_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')",
                    ['p' => $propertyId]
                );
                if ($rowM) {
                    $clicksMonth = [
                        'phone'  => (int)$rowM['phone'],
                        'line'   => (int)$rowM['line'],
                        'coupon' => (int)$rowM['coupon'],
                        'book'   => (int)$rowM['book'],
                    ];
                }
            }

            if ($hasViewTable) {
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

                // รายวัน
                $rawV = Database::fetchAll(
                    "SELECT DATE(created_at) AS day, COUNT(*) AS cnt
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
            }
        }

        // อัตรา click-to-view (click rate %)
        $clickRate = $views > 0 ? round(($clicks['phone'] + $clicks['line'] + $clicks['book']) / $views * 100, 1) : 0;

        View::render('owner/analytics/index', [
            'page_title'   => 'Analytics — สถิติที่พัก',
            'properties'   => $properties,
            'propertyId'   => $propertyId,
            'range'        => $range,
            'clicks'       => $clicks,
            'clicksMonth'  => $clicksMonth,
            'views'        => $views,
            'viewsMonth'   => $viewsMonth,
            'dailyClicks'  => $dailyClicks,
            'dailyViews'   => $dailyViews,
            'clickRate'    => $clickRate,
            'hasLeadTable' => $hasLeadTable,
            'hasViewTable' => $hasViewTable,
        ], 'layouts/owner');
    }
}
