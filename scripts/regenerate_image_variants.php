<?php
/**
 * สร้าง WebP variants (_md.webp, _thumb.webp) ให้รูปเดิมทั้งหมดใน public/uploads/
 *
 * Run: C:\xampp\php\php.exe scripts/regenerate_image_variants.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/Application.php';

\App\Core\Application::boot(dirname(__DIR__));
require_once dirname(__DIR__) . '/app/Core/Helpers.php';

if (!function_exists('imagewebp')) {
    fwrite(STDERR, "GD with WebP support is required (enable php_gd / imagewebp).\n");
    exit(1);
}

echo "Regenerating WebP variants in public/uploads/ ...\n";

$stats = \App\Support\ImageOptimizer::regenerateAll(static function (string $relative): void {
    echo "  · {$relative}\n";
});

echo "\nDone.\n";
echo "  Total originals : {$stats['total']}\n";
echo "  OK              : {$stats['ok']}\n";
echo "  Skipped         : {$stats['skip']}\n";
echo "  Failed          : {$stats['fail']}\n";

\App\Core\PageCache::flush();
echo "Homepage cache flushed.\n";

exit($stats['fail'] > 0 ? 1 : 0);
