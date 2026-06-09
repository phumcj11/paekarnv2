<?php

namespace App\Core;

/**
 * จำกัดความถี่การล็อกอินผิดพลาดต่อ IP + อีเมล (ไฟล์ใน storage/cache/login_throttle)
 */
class LoginThrottle
{
    private static function cfg(): array
    {
        $c = config('app.login_throttle', []);
        return [
            'max_attempts'  => max(1, (int)($c['max_attempts'] ?? 5)),
            'decay_minutes' => max(1, (int)($c['decay_minutes'] ?? 15)),
            'lockout_minutes' => max(1, (int)($c['lockout_minutes'] ?? 15)),
        ];
    }

    private static function dir(): string
    {
        $d = Application::$basePath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'login_throttle';
        if (!is_dir($d)) {
            @mkdir($d, 0755, true);
        }

        return $d;
    }

    private static function clientIp(): string
    {
        return substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: '0';
    }

    private static function filePath(string $channel, string $email): string
    {
        $key = hash('sha256', $channel . '|' . self::clientIp() . '|' . strtolower(trim($email)));

        return self::dir() . DIRECTORY_SEPARATOR . $key . '.json';
    }

    /** @return array{fails: list<int>, locked_until: int} */
    private static function readState(string $path): array
    {
        if (!is_readable($path)) {
            return ['fails' => [], 'locked_until' => 0];
        }
        $raw = json_decode((string)file_get_contents($path), true);
        if (!is_array($raw)) {
            return ['fails' => [], 'locked_until' => 0];
        }
        $fails = $raw['fails'] ?? [];
        if (!is_array($fails)) {
            $fails = [];
        }
        $locked = (int)($raw['locked_until'] ?? 0);

        return ['fails' => array_values(array_filter(array_map('intval', $fails))), 'locked_until' => $locked];
    }

    private static function writeState(string $path, array $state): void
    {
        file_put_contents($path, json_encode($state, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /**
     * ถ้าถูกล็อกคืนข้อความแจ้งผู้ใช้ (ภาษาไทย) — ถ้าไม่ถูกล็อกคืน null
     */
    public static function lockedMessage(string $channel, string $email): ?string
    {
        $email = trim($email);
        if ($email === '') {
            return null;
        }
        $path = self::filePath($channel, $email);
        $state = self::readState($path);
        $now = time();
        if (($state['locked_until'] ?? 0) > $now) {
            $mins = max(1, (int)ceil(($state['locked_until'] - $now) / 60));

            return 'พยายามเข้าสู่ระบบผิดหลายครั้ง กรุณารอประมาณ ' . $mins . ' นาทีแล้วลองใหม่';
        }

        return null;
    }

    public static function hitFailure(string $channel, string $email): void
    {
        $email = trim($email);
        if ($email === '') {
            return;
        }
        $cfg = self::cfg();
        $path = self::filePath($channel, $email);
        $state = self::readState($path);
        $now = time();
        $cutoff = $now - ($cfg['decay_minutes'] * 60);
        $fails = array_values(array_filter($state['fails'], fn (int $t): bool => $t >= $cutoff));
        $fails[] = $now;
        $lockedUntil = 0;
        if (count($fails) >= $cfg['max_attempts']) {
            $lockedUntil = $now + ($cfg['lockout_minutes'] * 60);
            $fails = [];
        }
        self::writeState($path, ['fails' => $fails, 'locked_until' => $lockedUntil]);
    }

    public static function clear(string $channel, string $email): void
    {
        $email = trim($email);
        if ($email === '') {
            return;
        }
        $path = self::filePath($channel, $email);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
