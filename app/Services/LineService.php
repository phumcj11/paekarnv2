<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Setting;

/**
 * LINE Integration helpers (Messaging API + Login API + Webhook)
 *
 * Required Settings keys:
 *   line_enabled, line_channel_access_token, line_channel_secret
 *   line_login_channel_id, line_login_channel_secret, line_friend_url
 */
class LineService
{
    /** Push text message to LINE userId, groupId or roomId via Messaging API (`to` field). */
    public static function push(string $recipientId, string $message): bool
    {
        $token = (string)Setting::get('line_channel_access_token', '');
        if (!$token || !Setting::get('line_enabled', '0') || $recipientId === '') {
            return false;
        }
        $body = json_encode([
            'to' => $recipientId,
            'messages' => [
                ['type' => 'text', 'text' => mb_substr($message, 0, 4900)],
            ],
        ], JSON_UNESCAPED_UNICODE);

        return self::post('https://api.line.me/v2/bot/message/push', $token, $body) === 200;
    }

    /** Reply with replyToken (used in webhook handler) */
    public static function reply(string $replyToken, string|array $messages): bool
    {
        $token = (string)Setting::get('line_channel_access_token', '');
        if (!$token) return false;
        $msgs = is_array($messages) ? $messages : [['type' => 'text', 'text' => mb_substr($messages, 0, 4900)]];
        $body = json_encode(['replyToken' => $replyToken, 'messages' => $msgs], JSON_UNESCAPED_UNICODE);
        return self::post('https://api.line.me/v2/bot/message/reply', $token, $body) === 200;
    }

    /** Verify X-Line-Signature header (HMAC-SHA256 base64 of body using channel_secret) */
    public static function verifySignature(string $rawBody, ?string $headerSig): bool
    {
        $secret = (string)Setting::get('line_channel_secret', '');
        if (!$secret || !$headerSig) return false;
        $expected = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));
        return hash_equals($expected, $headerSig);
    }

    /** Build LINE Login URL */
    public static function loginUrl(string $state, string $redirectUri): string
    {
        $clientId = (string)Setting::get('line_login_channel_id', '');
        return 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'state'         => $state,
            'scope'         => 'profile openid',
            'nonce'         => bin2hex(random_bytes(8)),
        ]);
    }

    /** Exchange code → access_token, then fetch profile (userId, displayName, pictureUrl) */
    public static function exchangeCode(string $code, string $redirectUri): ?array
    {
        $clientId     = (string)Setting::get('line_login_channel_id', '');
        $clientSecret = (string)Setting::get('line_login_channel_secret', '');
        if (!$clientId || !$clientSecret) return null;

        // 1) token
        $ch = curl_init('https://api.line.me/oauth2/v2.1/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $redirectUri,
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
            ]),
        ]);
        $tokenRes = curl_exec($ch);
        $code1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code1 !== 200 || !$tokenRes) return null;
        $token = json_decode($tokenRes, true);
        if (empty($token['access_token'])) return null;

        // 2) profile
        $ch = curl_init('https://api.line.me/v2/profile');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token['access_token']],
        ]);
        $profileRes = curl_exec($ch);
        $code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code2 !== 200 || !$profileRes) return null;

        return json_decode($profileRes, true) ?: null;
    }

    /** Log webhook to DB */
    public static function logWebhook(string $payload, bool $sigOk, ?string $error = null, bool $processed = false): int
    {
        $type = null;
        $data = json_decode($payload, true);
        if (is_array($data) && !empty($data['events'][0]['type'])) {
            $type = $data['events'][0]['type'];
        }
        return Database::insert('webhook_logs', [
            'source'       => 'line',
            'event_type'   => $type,
            'payload'      => $payload,
            'signature_ok' => $sigOk ? 1 : 0,
            'ip'           => $_SERVER['REMOTE_ADDR'] ?? null,
            'processed'    => $processed ? 1 : 0,
            'error'        => $error,
        ]);
    }

    private static function post(string $url, string $token, string $body): int
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_POSTFIELDS     => $body,
        ]);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code;
    }
}
