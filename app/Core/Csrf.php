<?php
namespace App\Core;

class Csrf
{
    private const KEY = '_csrf_token';
    private const EXP_KEY = '_csrf_token_exp';
    private const MAX_AGE = 86400;

    public static function token(): string
    {
        self::ensureNonce();

        $payload = $_SESSION[self::KEY] . '|' . (int)$_SESSION[self::EXP_KEY];
        $sig = hash_hmac('sha256', $payload, self::secret());

        return rtrim(strtr(base64_encode($payload . '|' . $sig), '+/', '-_'), '=');
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    public static function verify(?string $token): bool
    {
        $token = $token !== null && $token !== '' ? $token : null;
        if ($token === null) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        }
        if ($token === null || $token === '') {
            return false;
        }

        // Legacy plain session token (cached forms ก่อน deploy)
        if (isset($_SESSION[self::KEY]) && hash_equals((string)$_SESSION[self::KEY], $token)) {
            return true;
        }

        return self::verifySigned($token);
    }

    /** @return list<string> POST paths ที่ควร redirect กลับแบบฟอร์มแทน 403 */
    public static function publicAuthPostPaths(): array
    {
        return [
            '/login',
            '/register',
            '/owner/login',
            '/owner/register',
            '/owner/forgot-password',
            '/owner/reset-password',
            '/provider/login',
            '/provider/register',
        ];
    }

    public static function publicAuthRedirectFor(string $path): string
    {
        return match ($path) {
            '/register'              => url('/register'),
            '/login'                 => url('/login'),
            '/owner/register'        => url('/owner/register'),
            '/owner/login'           => url('/owner/login'),
            '/owner/forgot-password' => url('/owner/forgot-password'),
            '/owner/reset-password'  => url('/owner/reset-password'),
            '/provider/register'     => url('/provider/register'),
            '/provider/login'        => url('/provider/login'),
            default                  => url('/'),
        };
    }

    private static function ensureNonce(): void
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
            $_SESSION[self::EXP_KEY] = time() + self::MAX_AGE;
        } elseif (empty($_SESSION[self::EXP_KEY]) || (int)$_SESSION[self::EXP_KEY] < time()) {
            $_SESSION[self::EXP_KEY] = time() + self::MAX_AGE;
        }
    }

    private static function verifySigned(string $token): bool
    {
        $raw = base64_decode(strtr($token, '-_', '+/'), true);
        if ($raw === false) {
            return false;
        }

        $parts = explode('|', $raw);
        if (count($parts) !== 3) {
            return false;
        }

        [$nonce, $exp, $sig] = $parts;
        if (!ctype_xdigit((string)$nonce) || strlen((string)$nonce) !== 64) {
            return false;
        }
        if (!is_numeric($exp) || (int)$exp < time()) {
            return false;
        }

        $payload = $nonce . '|' . $exp;
        if (!hash_equals(hash_hmac('sha256', $payload, self::secret()), (string)$sig)) {
            return false;
        }

        // ยอมรับแม้ session หลุด (มือถือ / LINE WebView) — token ลงนามแล้วและยังไม่หมดอายุ
        $_SESSION[self::KEY] = $nonce;
        $_SESSION[self::EXP_KEY] = (int)$exp;

        return true;
    }

    private static function secret(): string
    {
        static $secret = null;
        if ($secret !== null) {
            return $secret;
        }

        $db = Application::$config['db'] ?? [];
        $secret = hash('sha256', 'paekarn-csrf-v1|' . ($db['database'] ?? '') . '|' . ($db['host'] ?? ''));

        return $secret;
    }
}
