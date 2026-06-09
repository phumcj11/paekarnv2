<?php
namespace App\Core;

class I18n
{
    private static string $locale = 'th';
    private static array $messages = [];

    public static function boot(): void
    {
        // ลำดับ: ?lang= → session → default
        if (isset($_GET['lang']) && in_array($_GET['lang'], Application::$config['app']['available_locales'])) {
            self::$locale = $_GET['lang'];
            Session::set('locale', self::$locale);
        } else {
            self::$locale = Session::get('locale', Application::$config['app']['locale']);
        }

        $file = Application::$basePath . '/app/Lang/' . self::$locale . '.php';
        if (is_file($file)) self::$messages = require $file;
    }

    public static function locale(): string { return self::$locale; }

    public static function trans(string $key, array $params = []): string
    {
        $msg = self::$messages[$key] ?? $key;
        foreach ($params as $k => $v) {
            $msg = str_replace(':' . $k, (string)$v, $msg);
        }
        return $msg;
    }
}
