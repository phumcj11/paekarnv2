<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\View;
use App\Models\PropertyLeadClick;
use App\Models\Setting;
use App\Services\AnalyticsExternalSnapshotService;

class AnalyticsController extends Controller
{
    public function index(): void
    {
        $counts = [
            'views_today' => 0,
            'views_7d' => 0,
            'views_30d' => 0,
        ];
        $topPathsToday = [];
        $topPaths7 = [];
        $topPaths30 = [];
        $topPropertiesToday = [];
        $topProperties30 = [];
        $leaderboard30 = [];
        $clickCountsToday = ['phone' => 0, 'line' => 0, 'coupon' => 0, 'book' => 0, 'total' => 0];
        $topClickPhoneToday = [];
        $topClickLineToday = [];
        $topClickCouponToday = [];
        $clicksTableOk = PropertyLeadClick::tableReady();
        $tableOk = true;

        $extSnap = AnalyticsExternalSnapshotService::latest(12);

        try {
            $counts['views_today'] = (int) Database::fetch(
                'SELECT COUNT(*) c FROM analytics_page_views WHERE DATE(created_at) = CURDATE()'
            )['c'];
            $counts['views_7d'] = (int) Database::fetch(
                'SELECT COUNT(*) c FROM analytics_page_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
            )['c'];
            $counts['views_30d'] = (int) Database::fetch(
                'SELECT COUNT(*) c FROM analytics_page_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
            )['c'];

            $topPathsToday = Database::fetchAll(
                'SELECT path, COUNT(*) AS cnt FROM analytics_page_views
                 WHERE DATE(created_at) = CURDATE()
                 GROUP BY path ORDER BY cnt DESC LIMIT 25'
            );
            $topPaths7 = Database::fetchAll(
                'SELECT path, COUNT(*) AS cnt FROM analytics_page_views
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY path ORDER BY cnt DESC LIMIT 25'
            );
            $topPaths30 = Database::fetchAll(
                'SELECT path, COUNT(*) AS cnt FROM analytics_page_views
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY path ORDER BY cnt DESC LIMIT 25'
            );
            $topPropertiesToday = Database::fetchAll(
                'SELECT v.property_id, p.name, p.slug, COUNT(*) AS cnt
                 FROM analytics_page_views v
                 INNER JOIN properties p ON p.id = v.property_id
                 WHERE DATE(v.created_at) = CURDATE() AND v.property_id IS NOT NULL
                 GROUP BY v.property_id, p.name, p.slug ORDER BY cnt DESC LIMIT 25'
            );
            $topProperties30 = Database::fetchAll(
                'SELECT v.property_id, p.name, p.slug, COUNT(*) AS cnt
                 FROM analytics_page_views v
                 INNER JOIN properties p ON p.id = v.property_id
                 WHERE v.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND v.property_id IS NOT NULL
                 GROUP BY v.property_id, p.name, p.slug ORDER BY cnt DESC LIMIT 25'
            );

            $leaderboard30 = Database::fetchAll(
                "SELECT p.id, p.name, p.slug, p.rating_avg, p.rating_count,
                        COALESCE(vc.cnt, 0) AS views_30d,
                        COALESCE(bc.bcnt, 0) AS bookings_30d
                 FROM properties p
                 LEFT JOIN (
                   SELECT property_id, COUNT(*) AS cnt FROM analytics_page_views
                   WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND property_id IS NOT NULL
                   GROUP BY property_id
                 ) vc ON vc.property_id = p.id
                 LEFT JOIN (
                   SELECT property_id, COUNT(*) AS bcnt FROM bookings
                   WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                   GROUP BY property_id
                 ) bc ON bc.property_id = p.id
                 WHERE p.status = 'published'
                 ORDER BY views_30d DESC, bookings_30d DESC, p.rating_avg DESC
                 LIMIT 20"
            );
        } catch (\Throwable $e) {
            $tableOk = false;
        }

        if ($clicksTableOk) {
            $clickCountsToday = PropertyLeadClick::countsToday();
            $topClickPhoneToday = PropertyLeadClick::topPropertiesToday('phone');
            $topClickLineToday = PropertyLeadClick::topPropertiesToday('line');
            $topClickCouponToday = PropertyLeadClick::topPropertiesToday('coupon');
        }

        View::render('admin/analytics/index', [
            'page_title' => 'Analytics',
            'counts' => $counts,
            'top_paths_today' => $topPathsToday,
            'top_paths_7' => $topPaths7,
            'top_paths_30' => $topPaths30,
            'top_properties_today' => $topPropertiesToday,
            'top_properties_30' => $topProperties30,
            'leaderboard_30' => $leaderboard30,
            'click_counts_today' => $clickCountsToday,
            'top_click_phone_today' => $topClickPhoneToday,
            'top_click_line_today' => $topClickLineToday,
            'top_click_coupon_today' => $topClickCouponToday,
            'clicks_table_ok' => $clicksTableOk,
            'table_ok' => $tableOk,
            'embed_url' => trim((string) Setting::get('analytics_embed_url', '')),
            'ga_report_url' => trim((string) Setting::get('analytics_ga_report_url', '')),
            'gsc_url' => trim((string) Setting::get('analytics_search_console_url', '')),
            'ga4_id' => trim((string) Setting::get('ga4_measurement_id', '')),
            'external_snapshots_ok' => $extSnap['table_ok'],
            'external_snapshots' => $extSnap['rows'],
        ], 'layouts/admin');
    }
}
