<?php

/**
 * นำเข้าข้อมูลที่เที่ยวจังหวัดกาญจนบุรี (คัดสรรตามอำเภอ — ข้อมูลทั่วไปสำหรับโปรโมทท่องเที่ยว)
 *
 * รันครั้งเดียว: php database/import_kanchanaburi_visitor_places.php
 * ต้องมีคอลัมน์ district แล้ว (รัน migrations/20260208_visitor_places_district.sql)
 */

declare(strict_types=1);

$base = dirname(__DIR__);
require $base . '/app/Core/Application.php';
\App\Core\Application::boot($base);

use App\Core\Database;

$rows = require __DIR__ . '/data/kanchanaburi_visitor_places.php';

$n = 0;
$skip = 0;
foreach ($rows as $row) {
    $slug = $row['slug'] ?? '';
    if ($slug === '') {
        continue;
    }
    $exists = Database::fetch('SELECT id FROM visitor_places WHERE slug = :s LIMIT 1', ['s' => $slug]);
    if ($exists) {
        $skip++;
        continue;
    }
    Database::insert('visitor_places', $row);
    $n++;
}

echo "Imported {$n} rows, skipped {$skip} (slug already exists).\n";
