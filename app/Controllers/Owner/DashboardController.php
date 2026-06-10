<?php
namespace App\Controllers\Owner;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\OwnerMembership;
use App\Services\OwnerAvailabilityCalendar;

class DashboardController extends Controller
{
    public function index(): void
    {
        $ownerId = Auth::ownerId();

        // ถ้าเป็น admin ที่เข้า /owner ให้ดูภาพรวมทั้งหมด ไม่ filter owner
        $whereOwner = '';
        $params = [];
        if ($ownerId) { $whereOwner = " AND p.owner_id = :oid"; $params['oid'] = $ownerId; }

        $stats = [
            'properties'          => (int)Database::fetch("SELECT COUNT(*) c FROM properties p WHERE 1=1 $whereOwner", $params)['c'],
            'published'           => (int)Database::fetch("SELECT COUNT(*) c FROM properties p WHERE p.status='published' $whereOwner", $params)['c'],
            'bookings_total'      => (int)Database::fetch("SELECT COUNT(*) c FROM bookings b JOIN properties p ON p.id=b.property_id WHERE 1=1 $whereOwner", $params)['c'],
            'bookings_pending'    => (int)Database::fetch("SELECT COUNT(*) c FROM bookings b JOIN properties p ON p.id=b.property_id WHERE b.status='pending' $whereOwner", $params)['c'],
            'bookings_confirmed'  => (int)Database::fetch("SELECT COUNT(*) c FROM bookings b JOIN properties p ON p.id=b.property_id WHERE b.status='confirmed' $whereOwner", $params)['c'],
            'coupons_used'        => (int)Database::fetch("SELECT COUNT(*) c FROM coupon_usages cu JOIN properties p ON p.id=cu.property_id WHERE 1=1 $whereOwner", $params)['c'],
            'revenue'             => (float)Database::fetch("SELECT COALESCE(SUM(b.total_price),0) s FROM bookings b JOIN properties p ON p.id=b.property_id WHERE b.status IN('confirmed','completed') $whereOwner", $params)['s'],
            'reviews'             => (int)Database::fetch("SELECT COUNT(*) c FROM reviews r JOIN properties p ON p.id=r.property_id WHERE r.is_approved=1 $whereOwner", $params)['c'],
            'rating_avg'          => (float)Database::fetch("SELECT COALESCE(AVG(p.rating_avg),0) s FROM properties p WHERE p.rating_count>0 $whereOwner", $params)['s'],
            'revenue_month'       => (float)Database::fetch(
                "SELECT COALESCE(SUM(b.total_price),0) s FROM bookings b JOIN properties p ON p.id=b.property_id
                 WHERE b.status IN ('confirmed','completed')
                 AND DATE_FORMAT(b.created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') $whereOwner",
                $params
            )['s'],
            'coupon_face_month'   => (float)Database::fetch(
                "SELECT COALESCE(SUM(cu.amount),0) s FROM coupon_usages cu JOIN properties p ON p.id=cu.property_id
                 WHERE DATE_FORMAT(cu.used_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') $whereOwner",
                $params
            )['s'],
            'leads_month'         => (int)Database::fetch(
                "SELECT COUNT(*) c FROM leads l JOIN properties p ON p.id=l.property_id
                 WHERE DATE_FORMAT(l.created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') $whereOwner",
                $params
            )['c'],
        ];

        $recentBookings = Database::fetchAll(
            "SELECT b.*, p.name AS property_name, u.name AS unit_name FROM bookings b
             JOIN properties p ON p.id=b.property_id
             LEFT JOIN property_units u ON u.id=b.unit_id
             WHERE 1=1 $whereOwner
             ORDER BY b.created_at DESC LIMIT 8", $params);

        $myProperties = Database::fetchAll(
            "SELECT p.*, (SELECT COUNT(*) FROM bookings b WHERE b.property_id=p.id) AS booking_count
             FROM properties p WHERE 1=1 $whereOwner
             ORDER BY p.created_at DESC LIMIT 5", $params);

        // chart 14 days
        $chart = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i day"));
            $row = Database::fetch(
                "SELECT COUNT(*) c FROM bookings b JOIN properties p ON p.id=b.property_id
                 WHERE DATE(b.created_at) = :d $whereOwner",
                array_merge(['d' => $d], $params)
            );
            $chart[] = ['date' => date('j/n', strtotime($d)), 'count' => (int)$row['c']];
        }

        $membershipOwner          = null;
        $membershipBenefitsActive = false;
        $membershipLineLinked     = false;
        $membershipIsVip          = false;

        if ($ownerId) {
            $membershipOwner = OwnerMembership::ownerRow($ownerId);
            if ($membershipOwner) {
                $membershipBenefitsActive = OwnerMembership::hasActiveBenefits($membershipOwner);
                $membershipIsVip        = ($membershipOwner['membership_tier'] ?? '') === 'vip';
                $authUser                = Auth::user();
                if ($authUser) {
                    $urow = Database::fetch('SELECT line_user_id FROM users WHERE id = :i', ['i' => (int)$authUser['id']]);
                    $membershipLineLinked = !empty($urow['line_user_id']);
                }
            }
        }

        // ปฏิทินสำหรับหน้าแรกมือถือ
        $calProperties = Database::fetchAll(
            "SELECT p.id, p.name FROM properties p WHERE 1=1 $whereOwner ORDER BY p.created_at DESC",
            $params
        );
        $calPropertyId = isset($_GET['cal_p']) ? (int)$_GET['cal_p'] : (int)($calProperties[0]['id'] ?? 0);
        $validCalProperty = false;
        foreach ($calProperties as $cp) {
            if ((int)$cp['id'] === $calPropertyId) {
                $validCalProperty = true;
                break;
            }
        }
        if (!$validCalProperty && !empty($calProperties)) {
            $calPropertyId = (int)$calProperties[0]['id'];
        }
        $calMonth = isset($_GET['cal_m']) ? max(1, min(12, (int)$_GET['cal_m'])) : (int)date('n');
        $calYear  = isset($_GET['cal_y']) ? (int)$_GET['cal_y'] : (int)date('Y');

        $calUnits = $calPropertyId
            ? Database::fetchAll(
                "SELECT id, name, total_units FROM property_units WHERE property_id = :p AND is_active=1 ORDER BY sort_order, id",
                ['p' => $calPropertyId]
            )
            : [];
        $calUnitId = isset($_GET['cal_u']) ? (int)$_GET['cal_u'] : (int)($calUnits[0]['id'] ?? 0);
        $validCalUnit = false;
        foreach ($calUnits as $cu) {
            if ((int)$cu['id'] === $calUnitId) {
                $validCalUnit = true;
                break;
            }
        }
        if (!$validCalUnit && !empty($calUnits)) {
            $calUnitId = (int)$calUnits[0]['id'];
        }
        $calTotalUnits = 1;
        foreach ($calUnits as $cu) {
            if ((int)$cu['id'] === $calUnitId) {
                $calTotalUnits = max(1, (int)$cu['total_units']);
                break;
            }
        }

        $homeCalendar = null;
        $calPropertyName = '';
        foreach ($calProperties as $cp) {
            if ((int)$cp['id'] === $calPropertyId) {
                $calPropertyName = $cp['name'];
                break;
            }
        }
        if ($calUnitId > 0) {
            $homeCalendar = OwnerAvailabilityCalendar::buildMonth($calUnitId, $calMonth, $calYear, $calTotalUnits);
            $homeCalendar['property_id']   = $calPropertyId;
            $homeCalendar['property_name'] = $calPropertyName;
            $homeCalendar['unit_id']         = $calUnitId;
            $homeCalendar['month']           = $calMonth;
            $homeCalendar['year']            = $calYear;
            $homeCalendar['units']           = $calUnits;
        }

        View::render('owner/dashboard', [
            'page_title'               => 'ภาพรวม',
            'stats'                    => $stats,
            'recentBookings'           => $recentBookings,
            'myProperties'             => $myProperties,
            'chart'                    => $chart,
            'membership_owner'         => $membershipOwner,
            'membership_benefits_active' => $membershipBenefitsActive,
            'membership_line_linked'   => $membershipLineLinked,
            'membership_is_vip'        => $membershipIsVip,
            'homeCalendar'             => $homeCalendar,
            'calProperties'            => $calProperties,
        ], 'layouts/owner');
    }
}
