<?php

namespace App\Services;

/**
 * LINE Notify — ตั้งค่า token ใน settings key lead_seek_line_notify_token
 */
class LineNotifyService
{
    public static function push(string $token, string $message): bool
    {
        $token = trim($token);
        if ($token === '') {
            return false;
        }
        $body = http_build_query(['message' => mb_substr($message, 0, 990)], '', '&');
        $ch   = curl_init('https://notify-api.line.me/api/notify');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_POSTFIELDS     => $body,
        ]);
        $raw = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200) {
            return false;
        }
        $json = json_decode((string)$raw, true);

        return is_array($json) && !empty($json['status']) && (int)$json['status'] === 200;
    }
}
