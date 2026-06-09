<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\Upload;
use App\Models\Setting;
use App\Models\CouponOrder;
use App\Models\Property;
use App\Services\CouponService;

class CouponController extends Controller
{
    public function index(): void
    {
        $face   = (int)Setting::get('coupon_face_value', 500);
        $sale   = (int)Setting::get('coupon_sale_price', 250);
        $valid  = (int)Setting::get('coupon_validity_days', 90);
        $supportedProperties = Database::fetchAll(
            "SELECT id, slug, name, cover_image, zone, district, province, min_price, type, raft_variant,
                    rating_avg, rating_count, coupon_enabled, is_featured, description
             FROM properties
             WHERE status='published' AND coupon_enabled=1
             ORDER BY rating_avg DESC LIMIT 8"
        );
        $supportedProperties = Property::attachUnitStats(Property::attachGalleryThumbnails($supportedProperties));
        $this->view('coupons/index', [
            'meta_title' => 'คูปองเงินสดลดที่พักกาญจนบุรี — ซื้อ '.$sale.' ใช้ '.$face.' บาท | แพกาญ.com',
            'meta_description' => 'ซื้อคูปองเงินสดของแพกาญ.com ใช้ลดค่าที่พักได้จริงกับที่พักในโครงการ — แพลตฟอร์มที่พักตรวจสอบจริง',
            'face' => $face, 'sale' => $sale, 'valid' => $valid,
            'properties' => $supportedProperties,
        ]);
    }

    public function buy(): void
    {
        $pricing = CouponService::resolvePricing(null);
        $face = (int)$pricing['face_value'];
        $sale = (int)$pricing['sale_price'];
        $campaigns = [];
        try {
            $campaigns = Database::fetchAll(
                "SELECT code, name, face_value, sale_price FROM coupon_campaigns
                 WHERE is_active = 1 AND (starts_at IS NULL OR starts_at <= NOW())
                   AND (ends_at IS NULL OR ends_at >= NOW())
                 ORDER BY id DESC"
            );
        } catch (\Throwable) {
        }
        $bank = [
            'bank_name'    => Setting::get('bank_name', 'กสิกรไทย'),
            'bank_account' => Setting::get('bank_account', '123-4-56789-0'),
            'bank_holder'  => Setting::get('bank_holder', 'บจก. แพกาญ.com'),
            'promptpay'    => Setting::get('promptpay_id', '0810000000'),
        ];
        $user = Auth::user();
        $this->view('coupons/buy', [
            'meta_title' => 'ซื้อคูปองเงินสดลดที่พัก — แพกาญ.com',
            'face' => $face, 'sale' => $sale, 'bank' => $bank, 'user' => $user,
            'campaigns' => $campaigns,
        ]);
    }

    public function checkout(): void
    {
        $data = $this->validate([
            'name'  => 'required|max:120',
            'phone' => 'required|phone',
            'email' => 'email|max:160',
            'qty'   => 'required|integer',
        ]);
        $qty = max(1, min(10, (int)$data['qty']));

        $slip = null;
        try {
            $slip = Upload::image('slip', 'slips');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            back();
        }

        $customerId = Auth::customerId();
        $buyer = [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'payment_method' => $_POST['payment_method'] ?? 'promptpay',
            'campaign_code' => trim((string)($_POST['campaign_code'] ?? '')) ?: null,
        ];

        $result = CouponService::purchase($buyer, $qty, $customerId, $slip);

        $orderRow = CouponOrder::find($result['order_id']);
        Session::flash('success', 'ซื้อคูปองสำเร็จ! เก็บรหัสคูปองไว้ใช้งาน');
        $successUrl = url('/coupons/success/' . $orderRow['order_no']);

        // PHP-FPM: ปิดการตอบกลับกับเบราว์เซอร์ก่อน แล้วค่อยส่งแจ้งเตือน (mail/LINE)
        // ไม่ให้ผู้ใช้ค้างที่หน้าชำระเงินเมื่อช่องทางแจ้งเตือนช้า
        if (function_exists('fastcgi_finish_request')) {
            session_write_close();
            header('Location: ' . $successUrl, true, 302);
            fastcgi_finish_request();
            CouponService::notifyPurchaseOrder(
                $customerId,
                $buyer,
                $qty,
                (int)$result['total'],
                (int)$result['order_id'],
                $result['codes']
            );
            exit;
        }

        CouponService::notifyPurchaseOrder(
            $customerId,
            $buyer,
            $qty,
            (int)$result['total'],
            (int)$result['order_id'],
            $result['codes']
        );
        redirect($successUrl);
    }

    public function success(string $orderNo): void
    {
        $order = Database::fetch("SELECT * FROM coupon_orders WHERE order_no = :o", ['o' => $orderNo]);
        if (!$order) { http_response_code(404); $this->view('errors/404'); return; }
        $coupons = Database::fetchAll("SELECT * FROM coupons WHERE order_id = :id", ['id' => $order['id']]);
        $this->view('coupons/success', [
            'meta_title' => 'รับคูปองเงินสดสำเร็จ — แพกาญ.com',
            'order' => $order, 'coupons' => $coupons,
        ]);
    }

    public function validateApi(): void
    {
        $code  = trim((string) ($_GET['code'] ?? ''));
        $phone = trim((string) ($_GET['phone'] ?? ''));
        if ($code === '') {
            $this->json(['ok' => false, 'msg' => 'กรุณากรอกรหัสคูปอง']);
        }
        $result = CouponService::validate($code, $phone !== '' ? $phone : null);
        if (!$result['ok']) {
            $this->json(['ok' => false, 'msg' => $result['msg'] ?? 'คูปองใช้ไม่ได้']);
        }
        $coupon = $result['coupon'];
        $this->json([
            'ok'    => true,
            'value' => (float) $coupon['face_value'],
            'msg'   => 'ใช้คูปองได้ ลด ฿' . number_format((float) $coupon['face_value']),
        ]);
    }
}
