<?php
/**
 * Web-trigger cron endpoint (for shared hosting without CLI access).
 * Call:  https://yoursite.com/cron.php?key=YOUR_SECRET&job=expire_coupons
 */
$basePath = defined('APP_BASE') ? APP_BASE : dirname(__DIR__);

require $basePath . '/app/Core/Application.php';
\App\Core\Application::boot($basePath);

header('Content-Type: text/plain; charset=utf-8');

$expectedKey = (string)\App\Models\Setting::get('cron_secret', '');
$gotKey = $_GET['key'] ?? '';

if ($expectedKey === '' || !is_string($gotKey) || !hash_equals($expectedKey, $gotKey)) {
    http_response_code(403);
    echo "Forbidden — invalid cron key\n";
    exit;
}

if (!\App\Models\Setting::get('automation_enabled', '1')) {
    echo "Automation disabled\n";
    exit;
}

$only = $_GET['job'] ?? null;
$results = \App\Services\CronService::runAll($only);

foreach ($results as $job => $r) {
    printf("[%s] %s — affected=%d, %dms\n   %s\n", $r['status'], $job, $r['affected'], $r['duration'], $r['output']);
}
