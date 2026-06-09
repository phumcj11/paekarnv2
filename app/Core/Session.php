<?php
namespace App\Core;

class Session
{
    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    /**
     * flash('error') = read & remove
     * flash('error', 'msg') = write
     */
    public static function flash(string $type, ?string $msg = null)
    {
        $bag = $_SESSION['_flash'] ?? [];
        if ($msg === null) {
            $val = $bag[$type] ?? null;
            unset($bag[$type]);
            $_SESSION['_flash'] = $bag;
            return $val;
        }
        $bag[$type] = $msg;
        $_SESSION['_flash'] = $bag;
        return null;
    }

    /** เก็บค่าเก่าให้ form กรณี validation fail */
    public static function withOld(array $old): void
    {
        $_SESSION['_old'] = $old;
    }

    public static function consumeOld(): void
    {
        unset($_SESSION['_old']);
    }
}
