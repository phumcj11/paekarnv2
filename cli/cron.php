<?php
/**
 * แพกาญ.com — Cron CLI runner
 *
 * Usage:
 *   php cli/cron.php                  # run all jobs
 *   php cli/cron.php expire_coupons   # run one specific job
 *
 * Setup ใน Windows Task Scheduler / cPanel cron:
 *   * * * * *   /usr/bin/php /path/to/paekan_v1/cli/cron.php >> /tmp/paekan-cron.log 2>&1
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require __DIR__ . '/../app/Core/Application.php';
\App\Core\Application::boot(__DIR__ . '/..');

if (!\App\Models\Setting::get('automation_enabled', '1')) {
    echo "[Skipped] Automation is disabled in settings\n";
    exit(0);
}

$only = $argv[1] ?? null;
$start = microtime(true);
echo "==========================================\n";
echo "แพกาญ.com cron started @ " . date('Y-m-d H:i:s') . "\n";
echo "==========================================\n";

$results = \App\Services\CronService::runAll($only);

foreach ($results as $job => $r) {
    $icon = $r['status'] === 'success' ? '✓' : '✗';
    printf("%s [%-22s] affected=%-3d duration=%dms\n   → %s\n",
        $icon, $job, $r['affected'], $r['duration'], $r['output']);
}

$total = (int)round((microtime(true) - $start) * 1000);
echo "------------------------------------------\n";
echo "Done in {$total}ms\n";
