<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Property;

class AccountController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        $cid  = Auth::customerId();

        $stats = [
            'bookings' => $cid ? (int)Database::fetch("SELECT COUNT(*) c FROM bookings WHERE customer_id=:c", ['c'=>$cid])['c'] : 0,
            'coupons'  => $cid ? (int)Database::fetch("SELECT COUNT(*) c FROM coupons WHERE customer_id=:c AND status IN ('unused','reserved')", ['c'=>$cid])['c'] : 0,
            'favorites'=> $cid ? (int)Database::fetch("SELECT COUNT(*) c FROM favorites WHERE customer_id=:c", ['c'=>$cid])['c'] : 0,
        ];
        $recent = $cid ? Database::fetchAll(
            "SELECT b.*, p.name AS property_name, p.cover_image, p.slug AS property_slug
             FROM bookings b JOIN properties p ON p.id=b.property_id
             WHERE b.customer_id = :c ORDER BY b.created_at DESC LIMIT 3",
            ['c' => $cid]
        ) : [];

        $this->view('account/index', [
            'meta_title' => 'บัญชีของฉัน — แพกาญ.com',
            'user' => $user, 'stats' => $stats, 'recent' => $recent,
        ]);
    }

    public function bookings(): void
    {
        $cid = Auth::customerId();
        $rows = $cid ? Booking::forCustomer($cid) : [];
        $this->view('account/bookings', [
            'meta_title' => 'การจองของฉัน — แพกาญ.com',
            'rows' => $rows,
        ]);
    }

    public function coupons(): void
    {
        $cid = Auth::customerId();
        $rows = $cid ? Coupon::forCustomer($cid) : [];
        $this->view('account/coupons', [
            'meta_title' => 'คูปองของฉัน — แพกาญ.com',
            'rows' => $rows,
        ]);
    }

    public function favorites(): void
    {
        $cid = Auth::customerId();
        $rows = $cid ? Database::fetchAll(
            "SELECT p.* FROM favorites f JOIN properties p ON p.id=f.property_id
             WHERE f.customer_id = :c ORDER BY f.created_at DESC",
            ['c'=>$cid]) : [];
        if ($rows !== []) {
            $rows = Property::attachUnitStats(Property::attachGalleryThumbnails($rows));
        }
        $this->view('account/favorites', [
            'meta_title' => 'ที่พักที่บันทึก — แพกาญ.com',
            'rows' => $rows,
        ]);
    }

    public function toggleFavorite(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $cid = Auth::customerId();
        if (!$cid) {
            http_response_code(401);
            echo json_encode(['redirect' => url('/login')]);
            exit;
        }
        $pid = (int)($_POST['property_id'] ?? 0);
        if ($pid <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'invalid property_id']);
            exit;
        }
        $exists = Database::fetch(
            "SELECT id FROM favorites WHERE customer_id = :c AND property_id = :p LIMIT 1",
            ['c' => $cid, 'p' => $pid]
        );
        if ($exists) {
            Database::query(
                "DELETE FROM favorites WHERE customer_id = :c AND property_id = :p",
                ['c' => $cid, 'p' => $pid]
            );
            echo json_encode(['favorited' => false]);
        } else {
            Database::query(
                "INSERT IGNORE INTO favorites (customer_id, property_id, created_at) VALUES (:c, :p, NOW())",
                ['c' => $cid, 'p' => $pid]
            );
            echo json_encode(['favorited' => true]);
        }
        exit;
    }

    public function profile(): void
    {
        $fresh = \App\Models\User::find((int)Auth::id());
        $this->view('account/profile', [
            'meta_title' => 'โปรไฟล์ — แพกาญ.com',
            'user' => $fresh ?: Auth::user(),
        ]);
    }
}
