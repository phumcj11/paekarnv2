<?php

namespace App\Services;

use App\Core\Application;
use App\Models\Coupon;
use App\Models\Setting;

/**
 * โทเค็นลงนามสำหรับข้อมูลใน QR (รูปแบบ PAEKAN:&lt;token&gt;)
 */
class CouponRedeemTokenService
{
    private static function signingKey(): string
    {
        $s = trim((string)Setting::get('coupon_qr_secret', ''));
        if ($s !== '') {
            return $s;
        }

        return hash('sha256', Application::$basePath . '|paekan|coupon_qr_v1', true);
    }

    public static function encode(int $couponId, string $expiresAtSql): string
    {
        $exp = (int) strtotime($expiresAtSql);
        if ($exp <= 0) {
            $exp = time() + 86400 * 365;
        }
        $payload = $couponId . ':' . $exp;
        $sig = hash_hmac('sha256', $payload, self::signingKey(), true);
        $raw = $payload . '|' . $sig;

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    /**
     * @return array{coupon_id:int,exp:int}|null
     */
    public static function parse(?string $token): ?array
    {
        if ($token === null || $token === '') {
            return null;
        }
        $token = trim($token);
        $b64 = strtr($token, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad > 0) {
            $b64 .= str_repeat('=', 4 - $pad);
        }
        $raw = base64_decode($b64, true);
        if ($raw === false || !str_contains($raw, '|')) {
            return null;
        }
        [$payload, $sig] = explode('|', $raw, 2);
        if (!str_contains($payload, ':')) {
            return null;
        }
        [$cid, $exp] = explode(':', $payload, 2);
        $cid = (int) $cid;
        $exp = (int) $exp;
        if ($cid <= 0 || $exp <= 0) {
            return null;
        }
        $expect = hash_hmac('sha256', $payload, self::signingKey(), true);
        if (strlen($sig) !== strlen($expect) || !hash_equals($expect, $sig)) {
            return null;
        }

        return ['coupon_id' => $cid, 'exp' => $exp];
    }

    public static function qrPayloadForCoupon(int $couponId, string $expiresAtSql): string
    {
        return 'PAEKAN:' . self::encode($couponId, $expiresAtSql);
    }

    /** ใช้หลัง parse — ตรวจอายุและว่ามีคูปองจริง */
    public static function couponFromParsed(array $parsed): ?array
    {
        if (time() > $parsed['exp']) {
            return null;
        }
        $row = Coupon::find((int) $parsed['coupon_id']);
        if (!$row) {
            return null;
        }
        if (strtotime((string) $row['expires_at']) < time()) {
            return null;
        }

        return $row;
    }
}
