<?php

/**
 * Sync cafe rows from database/data/kanchanaburi_visitor_places.php.
 *
 * รันหลังจากมีคอลัมน์ cafe fields แล้ว:
 * C:\xampp\php\php.exe database/sync_cafe_visitor_places.php
 */

declare(strict_types=1);

$base = dirname(__DIR__);
require $base . '/app/Core/Application.php';
\App\Core\Application::boot($base);

use App\Core\Database;

$requiredColumns = [
    'rating_avg',
    'rating_count',
    'opening_hours',
    'tags',
    'is_open_now',
    'is_pet_friendly',
    'is_photo_spot',
];

foreach ($requiredColumns as $column) {
    if (!Database::tableHasColumn('visitor_places', $column)) {
        fwrite(STDERR, "Missing visitor_places.{$column}. Run database/patches/20260525_visitor_places_cafe_fields.sql first.\n");
        exit(1);
    }
}

$rows = require __DIR__ . '/data/kanchanaburi_visitor_places.php';

$inserted = 0;
$updated = 0;
$skipped = 0;

foreach ($rows as $row) {
    if (($row['category'] ?? '') !== 'cafe') {
        continue;
    }

    $slug = trim((string)($row['slug'] ?? ''));
    if ($slug === '') {
        $skipped++;
        continue;
    }

    $existing = Database::fetch('SELECT id FROM visitor_places WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
    if ($existing) {
        Database::update('visitor_places', $row, 'id = :id', ['id' => (int)$existing['id']]);
        $updated++;
        continue;
    }

    Database::insert('visitor_places', $row);
    $inserted++;
}

echo "Cafe sync complete: inserted {$inserted}, updated {$updated}, skipped {$skipped}.\n";
