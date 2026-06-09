<?php

/**
 * นำเข้า 10 บทความ blog_kanchan_city — ใช้ PDO + utf8mb4 (แก้ปัญหาภาษาเพี้ยนจาก mysql.exe บน Windows)
 *
 * รัน: php database/import_blog_kanchan_city_posts.php
 * (จากโฟลเดอร์โปรเจกต์ หรือระบุ path เต็ม)
 */

declare(strict_types=1);

$base = dirname(__DIR__);
require $base . '/app/Core/Application.php';
\App\Core\Application::boot($base);

use App\Core\Database;

$slugs = [
    'kanchanaburi-one-day-city-tour',
    'river-kwai-bridge-and-museums-guide',
    'kanchanaburi-historical-park-and-old-town',
    'ancient-town-tha-muang-evening-market',
    'riverside-cafes-mae-klong-kanchanaburi',
    'kanchanaburi-2-days-city-and-sai-yok',
    'wwii-history-walk-kanchanaburi-town',
    'souvenirs-kanchanaburi-local-food-gifts',
    'kanchanaburi-family-one-day-soft-itinerary',
    'stay-downtown-vs-raft-resort-paekan',
];

$sqlPath = __DIR__ . '/migrations/20260508_blog_kanchan_city_10.sql';
if (!is_readable($sqlPath)) {
    fwrite(STDERR, "Missing file: {$sqlPath}\n");
    exit(1);
}

$raw = file_get_contents($sqlPath);
if ($raw === false) {
    fwrite(STDERR, "Cannot read: {$sqlPath}\n");
    exit(1);
}

// ตัดบรรทัดคอมเมนต์ (-- ...) ออก เหลือเฉพาะคำสั่ง INSERT
$lines = preg_split('/\R/', $raw);
$buf = [];
foreach ($lines as $line) {
    if (preg_match('/^\s*--/', $line)) {
        continue;
    }
    $buf[] = $line;
}
$insertSql = trim(implode("\n", $buf));
if ($insertSql === '' || !str_starts_with($insertSql, 'INSERT INTO')) {
    fwrite(STDERR, "SQL file does not contain INSERT after comments.\n");
    exit(1);
}

$pdo = Database::pdo();
$pdo->beginTransaction();
try {
    $inList = "'" . implode("','", array_map(static fn (string $s): string => str_replace("'", "''", $s), $slugs)) . "'";
    $pdo->exec("DELETE FROM blog_posts WHERE slug IN ({$inList})");
    $pdo->exec($insertSql);
    $pdo->commit();
    echo "OK: deleted old rows (if any) + imported 10 blog posts with UTF-8.\n";
} catch (\Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
