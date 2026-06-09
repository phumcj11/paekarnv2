<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{
    public function index(): void
    {
        $propDays = max(1, (int)config('app.dashboard.prop_pending_warn_days', 7));
        $bkHours  = max(1, (int)config('app.dashboard.booking_pending_warn_hours', 48));

        $stats = [
            'properties_published' => (int)Database::fetch("SELECT COUNT(*) c FROM properties WHERE status='published'")['c'],
            'properties_total'     => (int)Database::fetch('SELECT COUNT(*) c FROM properties')['c'],
            'pending_props'        => (int)Database::fetch("SELECT COUNT(*) c FROM properties WHERE status='pending'")['c'],
            'owners_total'         => (int)Database::fetch('SELECT COUNT(*) c FROM owners')['c'],
            'customers_total'      => (int)Database::fetch("SELECT COUNT(*) c FROM users WHERE role='customer'")['c'],
            'customers_new_7d'     => (int)Database::fetch(
                "SELECT COUNT(*) c FROM users WHERE role='customer' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            )['c'],
            'customers_new_30d'    => (int)Database::fetch(
                "SELECT COUNT(*) c FROM users WHERE role='customer' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            )['c'],
            'bookings'             => (int)Database::fetch('SELECT COUNT(*) c FROM bookings')['c'],
            'bookings_today'       => (int)Database::fetch("SELECT COUNT(*) c FROM bookings WHERE DATE(created_at) = CURDATE()")['c'],
            'pending_bk'           => (int)Database::fetch("SELECT COUNT(*) c FROM bookings WHERE status='pending'")['c'],
            'leads_total'          => (int)Database::fetch('SELECT COUNT(*) c FROM leads')['c'],
            'leads_today'          => (int)Database::fetch("SELECT COUNT(*) c FROM leads WHERE DATE(created_at) = CURDATE()")['c'],
            'coupon_qty_sold'      => (int)Database::fetch(
                "SELECT COUNT(*) c FROM coupons c
                 INNER JOIN coupon_orders o ON o.id = c.order_id
                 WHERE o.status = 'paid'"
            )['c'],
            'coupons_used'         => (int)Database::fetch("SELECT COUNT(*) c FROM coupons WHERE status='used'")['c'],
            'coupons_unused'       => (int)Database::fetch("SELECT COUNT(*) c FROM coupons WHERE status='unused'")['c'],
            'revenue'              => (float)Database::fetch(
                "SELECT COALESCE(SUM(c.sale_price),0) s FROM coupons c
                 INNER JOIN coupon_orders o ON o.id = c.order_id
                 WHERE o.status = 'paid'"
            )['s'],
            'booking_rev'          => (float)Database::fetch(
                "SELECT COALESCE(SUM(total_price),0) s FROM bookings WHERE status IN('confirmed','completed')"
            )['s'],
            'reviews'              => (int)Database::fetch('SELECT COUNT(*) c FROM reviews WHERE is_approved=1')['c'],
        ];

        $recentBookings = Database::fetchAll(
            "SELECT b.*, p.name AS property_name FROM bookings b
             JOIN properties p ON p.id = b.property_id
             ORDER BY b.created_at DESC LIMIT 8"
        );

        $topProperties = Database::fetchAll(
            "SELECT p.*, (SELECT COUNT(*) FROM bookings b WHERE b.property_id=p.id) AS booking_count
             FROM properties p WHERE p.status='published'
             ORDER BY booking_count DESC, p.rating_avg DESC LIMIT 5"
        );

        $topByViews = Database::fetchAll(
            "SELECT id, name, slug, view_count, status FROM properties
             WHERE status='published' ORDER BY view_count DESC LIMIT 5"
        );

        $pendingLong = Database::fetchAll(
            "SELECT id, name, status, created_at, updated_at FROM properties
             WHERE status='pending'
               AND updated_at < DATE_SUB(NOW(), INTERVAL $propDays DAY)
             ORDER BY updated_at ASC LIMIT 15"
        );

        $bookingsStale = Database::fetchAll(
            "SELECT b.id, b.code, b.created_at, b.status, p.id AS property_id, p.name AS property_name
             FROM bookings b
             JOIN properties p ON p.id = b.property_id
             WHERE b.status = 'pending'
               AND b.created_at < DATE_SUB(NOW(), INTERVAL $bkHours HOUR)
             ORDER BY b.created_at ASC LIMIT 15"
        );

        $missingPhone = [];
        if (config('app.dashboard.published_missing_phone', true)) {
            $missingPhone = Database::fetchAll(
                "SELECT id, name, status FROM properties
                 WHERE status='published' AND (phone IS NULL OR TRIM(phone) = '')
                 ORDER BY view_count DESC LIMIT 10"
            );
        }

        $chart = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i day"));
            $row = Database::fetch("SELECT COUNT(*) c FROM bookings WHERE DATE(created_at) = :d", ['d' => $d]);
            $chart[] = ['date' => date('j/n', strtotime($d)), 'count' => (int)$row['c']];
        }

        \App\Core\View::render('admin/dashboard', [
            'page_title'      => 'Dashboard',
            'stats'           => $stats,
            'recentBookings'  => $recentBookings,
            'topProperties'   => $topProperties,
            'topByViews'      => $topByViews,
            'problemPending'  => $pendingLong,
            'problemBookings' => $bookingsStale,
            'problemPhones'   => $missingPhone,
            'warn_days'       => $propDays,
            'warn_book_hours' => $bkHours,
            'chart'           => $chart,
        ], 'layouts/admin');
    }
}
