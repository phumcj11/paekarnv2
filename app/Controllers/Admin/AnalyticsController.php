<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\View;
use App\Models\PropertyLeadClick;
use App\Models\Setting;
use App\Services\AnalyticsExternalSnapshotService;
use App\Services\AnalyticsPageViewService;

class AnalyticsController extends Controller
{
    public function index(): void
    {
        $v2Ready = AnalyticsPageViewService::v2Ready() && PropertyLeadClick::v2Ready();
        $clicksTableOk = PropertyLeadClick::tableReady();
        $tableOk = true;

        $humanCounts = AnalyticsPageViewService::humanCounts();
        $legacyViewCounts = AnalyticsPageViewService::legacyCounts();
        $v2StartedAt = AnalyticsPageViewService::v2StartedAt() ?? PropertyLeadClick::v2StartedAt();

        $clickUniqueToday = PropertyLeadClick::uniqueCounts(0);
        $clickUnique7 = PropertyLeadClick::uniqueCounts(7);
        $clickUnique30 = PropertyLeadClick::uniqueCounts(30);
        $clickAuditToday = PropertyLeadClick::auditCountsToday();
        $clickLegacyToday = PropertyLeadClick::countsToday();

        $bookingsToday = PropertyLeadClick::bookingOutcomes(0);
        $bookings7 = PropertyLeadClick::bookingOutcomes(7);
        $bookings30 = PropertyLeadClick::bookingOutcomes(30);

        $topPathsToday = [];
        $topPaths7 = [];
        $topPaths30 = [];
        $topPropertiesToday = [];
        $topProperties30 = [];
        $leaderboard30 = [];
        $topClickPhoneToday = [];
        $topClickLineToday = [];
        $topClickCouponToday = [];
        $recentAudit = [];

        $extSnap = AnalyticsExternalSnapshotService::latest(12);

        try {
            if ($v2Ready) {
                $topPathsToday = AnalyticsPageViewService::topPathsV2(0);
                $topPaths7 = AnalyticsPageViewService::topPathsV2(7);
                $topPaths30 = AnalyticsPageViewService::topPathsV2(30);
                $topPropertiesToday = AnalyticsPageViewService::topPropertiesV2(0);
                $topProperties30 = AnalyticsPageViewService::topPropertiesV2(30);

                $leaderboard30 = Database::fetchAll(
                    "SELECT p.id, p.name, p.slug, p.rating_avg, p.rating_count,
                            COALESCE(vc.views, 0) AS views_30d,
                            COALESCE(vc.unique_visitors, 0) AS unique_visitors_30d,
                            COALESCE(bc.bcnt, 0) AS bookings_30d
                     FROM properties p
                     LEFT JOIN (
                       SELECT property_id,
                              SUM(CASE WHEN is_counted = 1 THEN 1 ELSE 0 END) AS views,
                              COUNT(DISTINCT CASE WHEN is_counted = 1 THEN visitor_hash END) AS unique_visitors
                       FROM analytics_page_views
                       WHERE tracking_version = 2
                         AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                         AND property_id IS NOT NULL
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
            } else {
                $countsLegacy = [
                    'views_today' => (int) Database::fetch(
                        'SELECT COUNT(*) c FROM analytics_page_views WHERE DATE(created_at) = CURDATE()'
                    )['c'],
                    'views_7d' => (int) Database::fetch(
                        'SELECT COUNT(*) c FROM analytics_page_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'
                    )['c'],
                    'views_30d' => (int) Database::fetch(
                        'SELECT COUNT(*) c FROM analytics_page_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'
                    )['c'],
                ];
                $humanCounts = array_merge($countsLegacy, [
                    'unique_today' => 0, 'unique_7d' => 0, 'unique_30d' => 0,
                ]);

                $topPathsToday = Database::fetchAll(
                    'SELECT path, COUNT(*) AS cnt, COUNT(*) AS views, 0 AS unique_visitors FROM analytics_page_views
                     WHERE DATE(created_at) = CURDATE()
                     GROUP BY path ORDER BY cnt DESC LIMIT 25'
                );
                $topPaths7 = Database::fetchAll(
                    'SELECT path, COUNT(*) AS cnt, COUNT(*) AS views, 0 AS unique_visitors FROM analytics_page_views
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                     GROUP BY path ORDER BY cnt DESC LIMIT 25'
                );
                $topPaths30 = Database::fetchAll(
                    'SELECT path, COUNT(*) AS cnt, COUNT(*) AS views, 0 AS unique_visitors FROM analytics_page_views
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                     GROUP BY path ORDER BY cnt DESC LIMIT 25'
                );
                $topPropertiesToday = Database::fetchAll(
                    'SELECT v.property_id, p.name, p.slug, COUNT(*) AS cnt, COUNT(*) AS views, 0 AS unique_visitors
                     FROM analytics_page_views v
                     INNER JOIN properties p ON p.id = v.property_id
                     WHERE DATE(v.created_at) = CURDATE() AND v.property_id IS NOT NULL
                     GROUP BY v.property_id, p.name, p.slug ORDER BY cnt DESC LIMIT 25'
                );
                $topProperties30 = Database::fetchAll(
                    'SELECT v.property_id, p.name, p.slug, COUNT(*) AS cnt, COUNT(*) AS views, 0 AS unique_visitors
                     FROM analytics_page_views v
                     INNER JOIN properties p ON p.id = v.property_id
                     WHERE v.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND v.property_id IS NOT NULL
                     GROUP BY v.property_id, p.name, p.slug ORDER BY cnt DESC LIMIT 25'
                );
                $leaderboard30 = Database::fetchAll(
                    "SELECT p.id, p.name, p.slug, p.rating_avg, p.rating_count,
                            COALESCE(vc.cnt, 0) AS views_30d,
                            0 AS unique_visitors_30d,
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
            }
        } catch (\Throwable $e) {
            $tableOk = false;
        }

        if ($clicksTableOk) {
            $topClickPhoneToday = PropertyLeadClick::topPropertiesToday('phone');
            $topClickLineToday = PropertyLeadClick::topPropertiesToday('line');
            $topClickCouponToday = PropertyLeadClick::topPropertiesToday('coupon');
            $recentAudit = PropertyLeadClick::recentAudit(25);
        }

        View::render('admin/analytics/index', [
            'page_title' => 'Analytics',
            'v2_ready' => $v2Ready,
            'v2_started_at' => $v2StartedAt,
            'human_counts' => $humanCounts,
            'legacy_view_counts' => $legacyViewCounts,
            'click_unique_today' => $clickUniqueToday,
            'click_unique_7' => $clickUnique7,
            'click_unique_30' => $clickUnique30,
            'click_audit_today' => $clickAuditToday,
            'click_legacy_today' => $clickLegacyToday,
            'bookings_today' => $bookingsToday,
            'bookings_7' => $bookings7,
            'bookings_30' => $bookings30,
            'top_paths_today' => $topPathsToday,
            'top_paths_7' => $topPaths7,
            'top_paths_30' => $topPaths30,
            'top_properties_today' => $topPropertiesToday,
            'top_properties_30' => $topProperties30,
            'leaderboard_30' => $leaderboard30,
            'top_click_phone_today' => $topClickPhoneToday,
            'top_click_line_today' => $topClickLineToday,
            'top_click_coupon_today' => $topClickCouponToday,
            'recent_audit' => $recentAudit,
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
