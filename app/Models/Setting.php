<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Setting extends Model
{
    protected static string $table = 'settings';
    private static array $cache = [];

    public static function get(string $key, $default = null)
    {
        if (empty(self::$cache)) self::loadAll();
        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        $existing = Database::fetch("SELECT id FROM settings WHERE `key` = :k", ['k' => $key]);
        if ($existing) {
            Database::update('settings', ['value' => $value], 'id = :id', ['id' => $existing['id']]);
        } else {
            Database::insert('settings', ['key' => $key, 'value' => $value]);
        }
        self::$cache[$key] = $value;
    }

    private static function loadAll(): void
    {
        try {
            $rows = Database::fetchAll("SELECT `key`, `value` FROM settings");
            foreach ($rows as $r) self::$cache[$r['key']] = $r['value'];
        } catch (\Throwable $e) { /* table maybe missing on first install */ }
    }
}
