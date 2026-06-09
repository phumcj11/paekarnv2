<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\ActivityOrder;
use App\Models\ActivityProduct;

class TrackOrderController extends Controller
{
    public function show(): void
    {
        $prefill = trim((string) ($_GET['voucher'] ?? $_GET['booking'] ?? $_GET['code'] ?? ''));
        $this->view('track/index', [
            'meta_title' => 'ติดตามคำสั่งซื้อ — แพกาญ.com',
            'prefill'    => $prefill,
            'result'     => null,
        ]);
    }

    public function lookup(): void
    {
        $code  = strtoupper(trim((string) ($_POST['code'] ?? '')));
        $phone = preg_replace('/\D+/', '', (string) ($_POST['phone'] ?? ''));
        $phone = is_string($phone) ? $phone : '';

        if ($code === '') {
            Session::flash('error', 'กรุณากรอกรหัสคำสั่งซื้อ / Voucher / เลขจอง');
            $this->view('track/index', ['meta_title' => 'ติดตามคำสั่งซื้อ — แพกาญ.com', 'prefill' => '', 'result' => null]);
            return;
        }

        $result = $this->findOrder($code, $phone);

        $this->view('track/index', [
            'meta_title' => 'ติดตามคำสั่งซื้อ — แพกาญ.com',
            'prefill'    => $code,
            'result'     => $result,
        ]);
    }

    /**
     * @return array{type:string,data:array<string,mixed>}|null
     */
    private function findOrder(string $code, string $phone): ?array
    {
        $phoneCheck = static function (string $needle, string $haystack): bool {
            if ($needle === '') {
                return true;
            }
            $a = preg_replace('/\D+/', '', $needle);
            $b = preg_replace('/\D+/', '', $haystack);
            return $a !== '' && $b !== '' && str_contains($b, $a);
        };

        if (ActivityProduct::tableReady()) {
            $row = Database::fetch(
                "SELECT ao.*, ap.title AS product_title, ap.slug AS product_slug, ap.cover_image
                 FROM activity_orders ao
                 LEFT JOIN activity_products ap ON ap.id = ao.product_id
                 WHERE ao.voucher_code = :c OR ao.order_no = :c
                 LIMIT 1",
                ['c' => $code]
            );
            if ($row && $phoneCheck($phone, (string) ($row['buyer_phone'] ?? ''))) {
                return ['type' => 'activity', 'data' => $row];
            }
        }

        if (Database::tableHasColumn('coupon_orders', 'id')) {
            $row = Database::fetch(
                "SELECT * FROM coupon_orders WHERE order_no = :c LIMIT 1",
                ['c' => $code]
            );
            if ($row && $phoneCheck($phone, (string) ($row['buyer_phone'] ?? ''))) {
                $coupons = Database::fetchAll('SELECT code, face_value, status, expires_at FROM coupons WHERE order_id = :id', ['id' => $row['id']]);
                $row['coupons'] = $coupons;
                return ['type' => 'coupon', 'data' => $row];
            }
        }

        if (Database::tableHasColumn('bookings', 'id')) {
            $row = Database::fetch(
                "SELECT b.*, p.name AS property_name, p.cover_image, p.slug AS property_slug, u.name AS unit_name
                 FROM bookings b
                 JOIN properties p ON p.id = b.property_id
                 LEFT JOIN property_units u ON u.id = b.unit_id
                 WHERE b.code = :c LIMIT 1",
                ['c' => $code]
            );
            if ($row && $phoneCheck($phone, (string) ($row['guest_phone'] ?? ''))) {
                return ['type' => 'booking', 'data' => $row];
            }
        }

        return null;
    }
}
