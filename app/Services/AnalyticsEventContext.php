<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;

/**
 * รวบรวม context สำหรับ analytics event (page view / CTA) ให้ใช้นิยามเดียวกัน
 */
class AnalyticsEventContext
{
    private const VISITOR_COOKIE = 'pk_vid';
    private const SESSION_COOKIE = 'pk_sid';
    private const TRACKING_VERSION = 2;

    /** @var array<string,mixed>|null */
    private static ?array $cached = null;

    /** @return array<string,mixed> */
    public static function capture(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $isBot = self::detectBot($ua);
        $isInternal = self::detectInternal();
        $visitorHash = self::visitorHash();
        $sessionHash = self::sessionHash();
        $deviceType = self::detectDevice($ua);
        $referrerHost = self::referrerHost();

        self::$cached = [
            'visitor_hash'     => $visitorHash,
            'session_hash'     => $sessionHash,
            'user_agent'       => $ua !== '' ? $ua : null,
            'device_type'      => $deviceType,
            'referrer_host'    => $referrerHost,
            'is_bot'           => $isBot ? 1 : 0,
            'is_internal'      => $isInternal ? 1 : 0,
            'is_counted'       => (!$isBot && !$isInternal) ? 1 : 0,
            'tracking_version' => self::TRACKING_VERSION,
            'exclude_reason'   => $isBot ? 'bot' : ($isInternal ? 'internal' : null),
        ];

        return self::$cached;
    }

    public static function trackingVersion(): int
    {
        return self::TRACKING_VERSION;
    }

    public static function shortHash(?string $hash): string
    {
        if ($hash === null || $hash === '') {
            return '—';
        }

        return substr($hash, 0, 8);
    }

    private static function visitorHash(): string
    {
        $raw = (string)($_COOKIE[self::VISITOR_COOKIE] ?? '');
        if ($raw === '' || !preg_match('/^[a-f0-9]{32}$/', $raw)) {
            $raw = bin2hex(random_bytes(16));
            self::setCookie(self::VISITOR_COOKIE, $raw, time() + 365 * 86400);
        }

        return hash('sha256', $raw);
    }

    private static function sessionHash(): string
    {
        $sid = session_id();
        if (is_string($sid) && $sid !== '') {
            return hash('sha256', 'php:' . $sid);
        }

        $raw = (string)($_COOKIE[self::SESSION_COOKIE] ?? '');
        if ($raw === '' || !preg_match('/^[a-f0-9]{32}$/', $raw)) {
            $raw = bin2hex(random_bytes(16));
            self::setCookie(self::SESSION_COOKIE, $raw, 0);
        }

        return hash('sha256', 'cookie:' . $raw);
    }

    private static function setCookie(string $name, string $value, int $expires): void
    {
        if (headers_sent()) {
            return;
        }

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        setcookie($name, $value, [
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function referrerHost(): ?string
    {
        $ref = (string)($_SERVER['HTTP_REFERER'] ?? '');
        if ($ref === '') {
            return null;
        }
        $host = parse_url($ref, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? substr($host, 0, 255) : null;
    }

    private static function detectInternal(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        return Auth::isAdmin() || Auth::isOwner() || Auth::isProvider();
    }

    private static function detectBot(string $ua): bool
    {
        if ($ua === '' || strlen($ua) < 12) {
            return true;
        }

        $patterns = [
            'bot', 'crawl', 'spider', 'slurp', 'mediapartners',
            'googlebot', 'bingbot', 'yandex', 'baiduspider', 'duckduckbot',
            'facebookexternalhit', 'twitterbot', 'linkedinbot', 'whatsapp',
            'telegrambot', 'discordbot', 'petalbot', 'semrush', 'ahrefs',
            'mj12bot', 'dotbot', 'headless', 'lighthouse', 'pingdom',
            'uptimerobot', 'curl/', 'wget/', 'python-requests', 'go-http-client',
            'java/', 'scrapy', 'httpclient', 'libwww', 'phantomjs',
        ];

        $lower = strtolower($ua);
        foreach ($patterns as $p) {
            if (str_contains($lower, $p)) {
                return true;
            }
        }

        return false;
    }

    private static function detectDevice(string $ua): string
    {
        if ($ua === '') {
            return 'unknown';
        }

        $lower = strtolower($ua);
        if (str_contains($lower, 'ipad') || (str_contains($lower, 'tablet') && !str_contains($lower, 'mobile'))) {
            return 'tablet';
        }
        if (str_contains($lower, 'mobile') || str_contains($lower, 'iphone') || str_contains($lower, 'android')) {
            return 'mobile';
        }

        return 'desktop';
    }
}
