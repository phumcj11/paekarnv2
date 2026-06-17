<?php
namespace App\Controllers\Owner;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;
use App\Models\Coupon;
use App\Services\CouponRedeemTokenService;
use App\Services\CouponService;
use App\Services\OwnerTier;

class CouponController extends Controller
{
    private function requireCouponFeature(): bool
    {
        if (Auth::isAdmin()) {
            return true;
        }
        $oid = Auth::ownerId();
        if (!$oid || !OwnerTier::can($oid, OwnerTier::FEATURE_COUPON)) {
            Session::flash('error', 'ฟีเจอร์ใช้คูปองต้องใช้แพ็กเกจ Standard ขึ้นไป');
            redirect(url('/owner/membership'));
            return false;
        }
        return true;
    }

    public function index(): void
    {
        if (!$this->requireCouponFeature()) {
            return;
        }
        $properties = $this->ownerProperties();
        $usages = $this->recentUsages();
        View::render('owner/coupons/verify', [
            'page_title' => 'ใช้คูปองลูกค้า',
            'properties' => $properties,
            'coupon' => null, 'check' => null,
            'usages' => $usages,
            'submitted_code' => strtoupper(trim((string) ($_GET['code'] ?? ''))),
        ], 'layouts/owner');
    }

    public function check(): void
    {
        if (!$this->requireCouponFeature()) {
            return;
        }
        $code  = strtoupper(trim((string)($_POST['code'] ?? '')));
        $phone = trim((string)($_POST['phone'] ?? ''));

        $check = CouponService::validate($code, $phone ?: null);
        $coupon = $check['ok'] ? $check['coupon'] : null;

        $properties = $this->ownerProperties();
        $usages = $this->recentUsages();

        View::render('owner/coupons/verify', [
            'page_title' => 'ใช้คูปองลูกค้า',
            'properties' => $properties,
            'coupon' => $coupon,
            'check' => $check,
            'usages' => $usages,
            'submitted_code' => $code,
            'submitted_phone' => $phone,
        ], 'layouts/owner');
    }

    public function markUsed(): void
    {
        if (!$this->requireCouponFeature()) {
            return;
        }
        $couponId   = (int)($_POST['coupon_id'] ?? 0);
        $propertyId = (int)($_POST['property_id'] ?? 0);
        $bookingId  = (int)($_POST['booking_id'] ?? 0); // optional

        $coupon = Coupon::find($couponId);
        if (!$coupon) { Session::flash('error','ไม่พบคูปอง'); back(); }
        if (!CouponService::isRedeemableStatus((string)$coupon['status'])) {
            Session::flash('error', 'คูปองไม่พร้อมใช้งาน');
            back();
        }

        // ตรวจ property ของ owner
        $p = Database::fetch("SELECT id, owner_id FROM properties WHERE id = :i", ['i' => $propertyId]);
        if (!$p) { Session::flash('error','ไม่พบที่พัก'); back(); }
        $oid = Auth::ownerId();
        if (!Auth::isAdmin() && (int)$p['owner_id'] !== (int)$oid) {
            Session::flash('error','ที่พักนี้ไม่ใช่ของคุณ'); back();
        }

        // ถ้าไม่ระบุ booking_id ก็สามารถ mark ใช้แบบ standalone ได้
        if ($bookingId > 0) {
            // ตรวจว่า booking นี้เป็นของ property นี้และเป็นของ owner
            $b = Database::fetch("SELECT id FROM bookings WHERE id = :i AND property_id = :p", ['i' => $bookingId, 'p' => $propertyId]);
            if (!$b) { Session::flash('error','การจองไม่ตรงกับที่พัก'); back(); }
        }

        CouponService::markUsed($couponId, $bookingId > 0 ? $bookingId : null, $propertyId, Auth::id());

        // ถ้าผูกกับ booking ให้บันทึกส่วนลดด้วย
        if ($bookingId > 0) {
            Database::query(
                "UPDATE bookings SET discount = discount + :amt, total_price = GREATEST(0, total_price - :amt2),
                                     coupon_id = :cid, coupon_code_used = :code
                 WHERE id = :i",
                ['amt' => $coupon['face_value'], 'amt2' => $coupon['face_value'], 'cid' => $couponId, 'code' => $coupon['code'], 'i' => $bookingId]
            );
        }

        Session::flash('success', 'ใช้คูปอง ' . $coupon['code'] . ' เรียบร้อย (ลดเงิน ฿' . number_format($coupon['face_value']) . ')');
        redirect(url('/owner/coupons/verify'));
    }

    public function scan(): void
    {
        if (!$this->requireCouponFeature()) {
            return;
        }
        View::render('owner/coupons/scan', [
            'page_title' => 'สแกนคูปอง',
        ], 'layouts/owner');
    }

    public function scanResolve(): void
    {
        if (!$this->requireCouponFeature()) {
            return;
        }
        $raw = trim((string) ($_POST['raw'] ?? ''));
        if ($raw === '') {
            Session::flash('error', 'ไม่พบข้อมูลจาก QR');
            redirect(url('/owner/coupons/scan'));
        }

        $code = null;
        if (preg_match('/PKAN-[A-Z0-9-]+/i', $raw, $m)) {
            $code = strtoupper($m[0]);
        } elseif (preg_match('/PAEKAN:([A-Za-z0-9_-]+)/', $raw, $m)) {
            $parsed = CouponRedeemTokenService::parse($m[1]);
            if ($parsed !== null) {
                $row = CouponRedeemTokenService::couponFromParsed($parsed);
                if ($row !== null) {
                    $code = strtoupper((string) $row['code']);
                }
            }
        } else {
            $parsed = CouponRedeemTokenService::parse($raw);
            if ($parsed !== null) {
                $row = CouponRedeemTokenService::couponFromParsed($parsed);
                if ($row !== null) {
                    $code = strtoupper((string) $row['code']);
                }
            }
        }

        if ($code === null) {
            Session::flash('error', 'อ่าน QR ไม่ได้หรือคูปองไม่ถูกต้อง / หมดอายุ');
            redirect(url('/owner/coupons/scan'));
        }

        redirect(url('/owner/coupons/verify?code=' . rawurlencode($code)));
    }

    private function ownerProperties(): array
    {
        if (Auth::isAdmin()) return Database::fetchAll("SELECT id, name FROM properties ORDER BY name LIMIT 200");
        $oid = Auth::ownerId();
        if (!$oid) return [];
        return Database::fetchAll("SELECT id, name FROM properties WHERE owner_id = :o ORDER BY name", ['o' => $oid]);
    }

    private function recentUsages(): array
    {
        $where = ''; $params = [];
        if (!Auth::isAdmin()) {
            $oid = Auth::ownerId();
            if (!$oid) return [];
            $where = ' AND p.owner_id = :o'; $params['o'] = $oid;
        }
        return Database::fetchAll(
            "SELECT cu.*, c.code, c.face_value, p.name AS property_name, b.code AS booking_code
             FROM coupon_usages cu
             JOIN coupons c ON c.id = cu.coupon_id
             JOIN properties p ON p.id = cu.property_id
             LEFT JOIN bookings b ON b.id = cu.booking_id
             WHERE 1=1 $where
             ORDER BY cu.used_at DESC LIMIT 20",
            $params);
    }
}
