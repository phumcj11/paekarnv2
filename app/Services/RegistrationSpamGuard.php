<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;

/**
 * กัน bot/spam ฟอร์มสมัครพาร์ทเนอร์ (owner / activity provider)
 */
final class RegistrationSpamGuard
{
    public const HONEYPOT_FIELD = 'company_website';
    public const TIMING_FIELD = '_form_started';

    private const MIN_SECONDS = 3;
    private const MAX_AGE_SECONDS = 7200;

    /** @return array{silent:bool,message:string}|null null = ผ่าน */
    public static function evaluate(string $channel, array $input): ?array
    {
        if (trim((string)($input[self::HONEYPOT_FIELD] ?? '')) !== '') {
            return ['silent' => true, 'message' => ''];
        }

        if (!self::timingValid((string)($input[self::TIMING_FIELD] ?? ''))) {
            return ['silent' => true, 'message' => ''];
        }

        if (self::ipRateLimited($channel)) {
            return [
                'silent' => false,
                'message' => 'มีการสมัครจากเครื่องนี้บ่อยเกินไป กรุณารอสักครู่แล้วลองใหม่',
            ];
        }

        $name = trim((string)($input['name'] ?? ''));
        $business = trim((string)($input['business_name'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));
        $phone = trim((string)($input['phone'] ?? ''));
        $lineId = trim((string)($input['line_id'] ?? ''));

        if (self::looksLikeGibberish($name)
            || self::looksLikeGibberish($business)
            || ($lineId !== '' && self::looksLikeGibberish($lineId))
        ) {
            return ['silent' => true, 'message' => ''];
        }

        if ($email !== '' && self::isSuspiciousEmail($email)) {
            return ['silent' => true, 'message' => ''];
        }

        if ($phone !== '' && !self::isValidThaiMobile($phone)) {
            return [
                'silent' => false,
                'message' => 'กรุณากรอกเบอร์มือถือไทยที่ใช้งานได้ (เช่น 0812345678)',
            ];
        }

        return null;
    }

    public static function recordSuccess(string $channel): void
    {
        self::bumpIpCounter($channel);
    }

    /** HTML ฟิลด์ลับ + timestamp ลงนาม (ใส่ในฟอร์มสมัคร) */
    public static function hiddenFields(): string
    {
        $ts = time();
        $payload = $ts . '|' . self::clientIp();
        $sig = hash_hmac('sha256', $payload, self::secret());
        $token = rtrim(strtr(base64_encode($payload . '|' . $sig), '+/', '-_'), '=');

        return '<div aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden">'
            . '<label for="reg_hp">Website</label>'
            . '<input type="text" name="' . self::HONEYPOT_FIELD . '" id="reg_hp" value="" tabindex="-1" autocomplete="off">'
            . '</div>'
            . '<input type="hidden" name="' . self::TIMING_FIELD . '" value="' . htmlspecialchars($token, ENT_QUOTES) . '">';
    }

    private static function timingValid(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        $raw = base64_decode(strtr($token, '-_', '+/'), true);
        if ($raw === false) {
            return false;
        }

        $parts = explode('|', $raw);
        if (count($parts) !== 3) {
            return false;
        }

        [$ts, $ip, $sig] = $parts;
        if (!is_numeric($ts)) {
            return false;
        }

        $payload = $ts . '|' . $ip;
        if (!hash_equals(hash_hmac('sha256', $payload, self::secret()), $sig)) {
            return false;
        }

        $started = (int)$ts;
        $now = time();
        if ($started <= 0 || $started > $now || ($now - $started) < self::MIN_SECONDS) {
            return false;
        }
        if (($now - $started) > self::MAX_AGE_SECONDS) {
            return false;
        }

        return true;
    }

    private static function looksLikeGibberish(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value) < 8) {
            return false;
        }

        if (preg_match('/[\p{Thai}\s]/u', $value)) {
            return false;
        }

        if (!preg_match('/^[a-zA-Z0-9._@-]+$/', $value)) {
            return false;
        }

        $letters = preg_replace('/[^a-zA-Z]/', '', $value);
        if (strlen($letters) < 8) {
            return false;
        }

        $upper = preg_match_all('/[A-Z]/', $letters);
        $lower = preg_match_all('/[a-z]/', $letters);
        if ($upper >= 2 && $lower >= 2 && !preg_match('/^[A-Z][a-z]+(?:[\s-][A-Z][a-z]+)*$/', $value)) {
            return true;
        }

        if (strlen($letters) >= 12 && $upper >= 3 && $lower >= 3) {
            return true;
        }

        return false;
    }

    private static function isSuspiciousEmail(string $email): bool
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        $local = strstr($email, '@', true);
        if ($local === false) {
            return true;
        }

        if (substr_count($local, '.') >= 3) {
            return true;
        }

        if (preg_match('/^(test|spam|fake|bot|noreply|no-reply)\d*@/i', $email)) {
            return true;
        }

        $disposable = ['mailinator.com', 'tempmail.com', 'guerrillamail.com', '10minutemail.com'];
        $domain = substr(strrchr($email, '@') ?: '', 1);
        if (in_array($domain, $disposable, true)) {
            return true;
        }

        return false;
    }

    private static function isValidThaiMobile(string $phone): bool
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if ($digits === '') {
            return false;
        }

        if (preg_match('/^0[689]\d{8}$/', $digits)) {
            return true;
        }

        if (preg_match('/^66[689]\d{8}$/', $digits)) {
            return true;
        }

        return false;
    }

    private static function ipRateLimited(string $channel): bool
    {
        $path = self::rateLimitPath($channel);
        $state = self::readJson($path);
        $now = time();
        $window = 3600;
        $max = 3;

        $hits = array_values(array_filter(
            $state['hits'] ?? [],
            static fn ($t) => is_int($t) && $t >= ($now - $window)
        ));

        return count($hits) >= $max;
    }

    private static function bumpIpCounter(string $channel): void
    {
        $path = self::rateLimitPath($channel);
        $state = self::readJson($path);
        $now = time();
        $window = 3600;

        $hits = array_values(array_filter(
            $state['hits'] ?? [],
            static fn ($t) => is_int($t) && $t >= ($now - $window)
        ));
        $hits[] = $now;

        self::writeJson($path, ['hits' => $hits]);
    }

    private static function rateLimitPath(string $channel): string
    {
        $dir = Application::$basePath . '/storage/cache/registration_spam';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $key = hash('sha256', $channel . '|' . self::clientIp());

        return $dir . '/' . $key . '.json';
    }

    /** @return array<string,mixed> */
    private static function readJson(string $path): array
    {
        if (!is_readable($path)) {
            return [];
        }
        $data = json_decode((string)file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    /** @param array<string,mixed> $data */
    private static function writeJson(string $path, array $data): void
    {
        file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    private static function clientIp(): string
    {
        return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: '0';
    }

    private static function secret(): string
    {
        static $secret = null;
        if ($secret !== null) {
            return $secret;
        }

        $db = Application::$config['db'] ?? [];
        $secret = hash('sha256', 'paekarn-reg-spam|' . ($db['database'] ?? '') . '|' . ($db['host'] ?? ''));

        return $secret;
    }
}
