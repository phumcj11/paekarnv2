<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\Session;

/** ช่วยเช็ค/บล็อกฟีเจอร์ Owner ตามแพ็กเกจ */
final class OwnerFeatureGate
{
    public static function allowed(string $feature): bool
    {
        if (Auth::isAdmin()) {
            return true;
        }
        $oid = Auth::ownerId();
        return $oid && OwnerTier::can($oid, $feature);
    }

    /** @return true = ผ่าน, false = redirect แล้ว */
    public static function denyPage(string $feature, string $message): bool
    {
        if (self::allowed($feature)) {
            return true;
        }
        Session::flash('error', $message);
        redirect(url('/owner/membership'));
        return false;
    }

    /**
     * @param object{json(array<string,mixed>): void} $controller
     * @return true = ผ่าน, false = ส่ง JSON error แล้ว
     */
    public static function denyJson(object $controller, string $feature, string $message): bool
    {
        if (self::allowed($feature)) {
            return true;
        }
        $controller->json(['ok' => false, 'error' => $message]);
        return false;
    }
}
