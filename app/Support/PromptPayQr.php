<?php

declare(strict_types=1);

namespace App\Support;

use App\Services\CouponQrImageService;

/**
 * PromptPay QR payload generator (EMVCo / BOT spec).
 *
 * Accepts a PromptPay ID (mobile number, 13-digit national ID, or 15-digit e-wallet)
 * and an optional amount. Returns a payload string that can be encoded into a QR.
 */
class PromptPayQr
{
    private const AID_PROMPTPAY = 'A000000677010111';

    public static function payload(string $id, float $amount = 0.0): string
    {
        $digits = preg_replace('/\D+/', '', $id);
        if ($digits === '' || $digits === null) {
            return '';
        }

        $length = strlen($digits);
        if ($length === 13) {
            $target = self::field('02', $digits);
        } elseif ($length === 15) {
            $target = self::field('03', $digits);
        } else {
            $phone = ltrim($digits, '0');
            if ($phone === '') {
                return '';
            }
            if (strpos($phone, '66') !== 0) {
                $phone = '66' . $phone;
            }
            $phone = str_pad($phone, 13, '0', STR_PAD_LEFT);
            $target = self::field('01', $phone);
        }

        $merchantAccount = self::field('00', self::AID_PROMPTPAY) . $target;

        $payload  = self::field('00', '01');
        $payload .= self::field('01', $amount > 0 ? '12' : '11');
        $payload .= self::field('29', $merchantAccount);
        $payload .= self::field('53', '764');
        if ($amount > 0) {
            $payload .= self::field('54', number_format($amount, 2, '.', ''));
        }
        $payload .= self::field('58', 'TH');
        $payload .= '6304';

        return $payload . self::crc16($payload);
    }

    public static function pngBase64(string $id, float $amount = 0.0): ?string
    {
        $payload = self::payload($id, $amount);
        if ($payload === '') {
            return null;
        }

        return CouponQrImageService::pngBase64($payload);
    }

    private static function field(string $id, string $value): string
    {
        return $id . str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT) . $value;
    }

    private static function crc16(string $data): string
    {
        $crc = 0xFFFF;
        $len = strlen($data);
        for ($i = 0; $i < $len; $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc <<= 1;
                }
                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
