<?php
namespace App\Core;

/**
 * Application Bootstrap
 * - autoload
 * - load config
 * - start session
 * - dispatch router
 */
class Application
{
    public static array $config = [];
    public static string $basePath;
    public static string $publicUrl;
    public static Router $router;

    public static function boot(string $basePath): void
    {
        self::$basePath = rtrim($basePath, DIRECTORY_SEPARATOR);

        $vendorAutoload = self::$basePath . '/vendor/autoload.php';
        if (is_file($vendorAutoload)) {
            require_once $vendorAutoload;
        }

        // ---------- Autoload (PSR-4 ลีลาง่าย ๆ) ----------
        spl_autoload_register(function (string $class): void {
            if (str_starts_with($class, 'App\\')) {
                $rel = str_replace(['App\\', '\\'], ['', '/'], $class);
                $file = self::$basePath . '/app/' . $rel . '.php';
                if (is_file($file)) require_once $file;
            }
        });

        // ---------- Load config ----------
        $appConfig = require self::$basePath . '/app/Config/app.php';
        $appLocal = self::$basePath . '/app/Config/app.local.php';
        if (is_file($appLocal)) {
            $appConfig = array_replace_recursive($appConfig, require $appLocal);
        }

        $dbConfigFile = self::$basePath . '/app/Config/database.local.php';
        if (!is_file($dbConfigFile)) {
            $dbConfigFile = self::$basePath . '/app/Config/database.php';
        }

        self::$config = [
            'app' => $appConfig,
            'db'  => require $dbConfigFile,
        ];

        date_default_timezone_set(self::$config['app']['timezone'] ?? 'Asia/Bangkok');

        ini_set('default_charset', 'UTF-8');
        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }

        // ---------- Error handling ----------
        if (self::$config['app']['debug']) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }

        // ---------- Session (hardened) ----------
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => !empty($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_name('PAEKAN_SESS');
            session_start();
        }

        // ---------- Detect base URL ----------
        self::$publicUrl = self::detectPublicUrl();

        // ---------- Helpers ----------
        require_once self::$basePath . '/app/Core/Helpers.php';

        // ---------- I18n ----------
        I18n::boot();

        // ---------- Router ----------
        self::$router = new Router();

        // โหลด routes
        $registerWeb      = require self::$basePath . '/app/Routes/web.php';
        $registerAdmin    = require self::$basePath . '/app/Routes/admin.php';
        $registerOwner    = require self::$basePath . '/app/Routes/owner.php';
        $registerProvider = require self::$basePath . '/app/Routes/provider.php';
        $registerWeb(self::$router);
        $registerAdmin(self::$router);
        $registerOwner(self::$router);
        $registerProvider(self::$router);
    }

    public static function run(): void
    {
        try {
            self::maybeBypassMaintenanceFromQuery();

            if (self::isMaintenanceBlocking()) {
                self::respondMaintenance();
                return;
            }

            \App\Services\AnalyticsPageViewService::maybeRecordFromRequest();

            self::$router->dispatch();
        } catch (\Throwable $e) {
            self::handleException($e);
        }
    }

    /**
     * Query maint_bypass ตรงกับ bypass_secret ใน config → เก็บ session แล้วพากลับหน้าแรก
     */
    private static function maybeBypassMaintenanceFromQuery(): void
    {
        $cfg = self::$config['app']['maintenance'] ?? [];
        if (empty($cfg['enabled'])) {
            return;
        }
        $secret = (string) ($cfg['bypass_secret'] ?? '');
        if ($secret === '' || !isset($_GET['maint_bypass'])) {
            return;
        }
        if (!hash_equals($secret, (string) $_GET['maint_bypass'])) {
            return;
        }
        $_SESSION['maint_bypass_ok'] = true;
        redirect(url('/'));
    }

    private static function isMaintenanceBlocking(): bool
    {
        $cfg = self::$config['app']['maintenance'] ?? [];
        if (empty($cfg['enabled'])) {
            return false;
        }
        return empty($_SESSION['maint_bypass_ok']);
    }

    private static function respondMaintenance(): void
    {
        http_response_code(503);
        $cfg     = self::$config['app']['maintenance'] ?? [];
        $retry   = (int) ($cfg['retry_after'] ?? 3600);
        $message = $cfg['message'] ?? null;

        if ($retry > 0) {
            header('Retry-After: ' . $retry);
        }

        View::render('errors/503', [
            'message'     => $message,
            'retry_after' => $retry,
        ]);
    }

    public static function handleException(\Throwable $e): void
    {
        http_response_code(500);
        if (self::$config['app']['debug']) {
            echo '<pre style="background:#1f2937;color:#fca5a5;padding:24px;border-radius:8px;font-family:monospace;font-size:13px;line-height:1.6">';
            echo "<b>Exception:</b> " . htmlspecialchars($e->getMessage()) . "\n";
            echo "<b>File:</b> " . $e->getFile() . ":" . $e->getLine() . "\n\n";
            echo htmlspecialchars($e->getTraceAsString());
            echo '</pre>';
        } else {
            error_log(sprintf(
                '[Paekan] %s: %s in %s:%d',
                $e::class,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));
            View::render('errors/500', ['message' => 'เกิดข้อผิดพลาด']);
        }
    }

    /**
     * คำนวณ base URL อัตโนมัติ ใช้กับทั้งกรณี
     *  - http://localhost/paekan_v1/                (XAMPP root subfolder)
     *  - http://paekan.test/                        (vhost ที่ DocumentRoot=public/)
     */
    private static function detectPublicUrl(): string
    {
        $scheme = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        // /paekan_v1/public/index.php  →  /paekan_v1/   (เอา /public/index.php ออก)
        // /index.php                   →  /
        $base = preg_replace('#/public/index\.php$#', '/', $script);
        $base = preg_replace('#/index\.php$#', '/', $base);
        if ($base === '' || $base === false) $base = '/';

        return rtrim($scheme . '://' . $host . $base, '/');
    }
}
