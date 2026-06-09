<?php

namespace App\Services;

/**
 * สร้าง PNG QR (base64) ด้วยไลบรารี phpqrcode (LGPL) ใน app/Lib/phpqrcode — ต้องมี ext-gd
 */
class CouponQrImageService
{
    public static function pngBase64(string $payload): ?string
    {
        if (!extension_loaded('gd')) {
            return null;
        }
        $lib = dirname(__DIR__) . '/Lib/phpqrcode/qrlib.php';
        if (!is_file($lib)) {
            return null;
        }
        require_once $lib;

        $tmp = tempnam(sys_get_temp_dir(), 'pqr');
        if ($tmp === false) {
            return null;
        }
        try {
            \QRcode::png($payload, $tmp, \QR_ECLEVEL_M, 4, 2);
            $bin = file_get_contents($tmp);
            @unlink($tmp);

            return $bin !== false ? base64_encode($bin) : null;
        } catch (\Throwable $e) {
            @unlink($tmp);

            return null;
        }
    }
}
