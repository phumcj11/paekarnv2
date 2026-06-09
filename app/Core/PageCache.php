<?php
namespace App\Core;

/**
 * Simple file-based cache for page data (view variable arrays).
 * Safe to use for shared (non-user-specific) data only.
 */
class PageCache
{
    private static string $dir = '';

    private static function cacheDir(): string
    {
        if (self::$dir === '') {
            self::$dir = Application::$basePath . '/storage/cache/pages';
            if (!is_dir(self::$dir)) {
                mkdir(self::$dir, 0775, true);
            }
        }

        return self::$dir;
    }

    public static function get(string $key): mixed
    {
        $file = self::cacheDir() . '/' . md5($key) . '.cache';
        if (!is_file($file)) {
            return null;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $data = @unserialize($content);
        if (!is_array($data) || !isset($data['expires'], $data['payload'])) {
            return null;
        }

        if ($data['expires'] < time()) {
            @unlink($file);

            return null;
        }

        return $data['payload'];
    }

    public static function set(string $key, mixed $value, int $ttlSeconds = 600): void
    {
        $file    = self::cacheDir() . '/' . md5($key) . '.cache';
        $content = serialize(['expires' => time() + $ttlSeconds, 'payload' => $value]);
        @file_put_contents($file, $content, LOCK_EX);
    }

    public static function forget(string $key): void
    {
        $file = self::cacheDir() . '/' . md5($key) . '.cache';
        if (is_file($file)) {
            @unlink($file);
        }
    }

    /** ล้าง cache ทั้งหมด (ใช้ใน admin เมื่ออัปเดตเนื้อหา) */
    public static function flush(): void
    {
        $dir = self::cacheDir();
        foreach (glob($dir . '/*.cache') ?: [] as $file) {
            @unlink($file);
        }
    }
}
