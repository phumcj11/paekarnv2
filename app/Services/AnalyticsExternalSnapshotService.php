<?php

namespace App\Services;

use App\Core\Database;

class AnalyticsExternalSnapshotService
{
    /** @return array{table_ok: bool, rows: array<int, array<string, mixed>>} */
    public static function latest(int $limit = 15): array
    {
        try {
            $lim = max(1, min(50, $limit));
            $rows = Database::fetchAll(
                "SELECT id, source, snapshot_key, payload_json, fetched_at
                 FROM analytics_external_snapshots
                 ORDER BY fetched_at DESC LIMIT {$lim}"
            );

            return ['table_ok' => true, 'rows' => $rows];
        } catch (\Throwable $e) {
            return ['table_ok' => false, 'rows' => []];
        }
    }

    /** สำหรับสคริปต์ cron / job ที่ดึง GA4 หรือ GSC แล้วเขียน cache */
    public static function save(string $source, string $snapshotKey, array $payload): ?int
    {
        try {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            return Database::insert('analytics_external_snapshots', [
                'source' => substr($source, 0, 32),
                'snapshot_key' => substr($snapshotKey, 0, 128),
                'payload_json' => $json,
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
