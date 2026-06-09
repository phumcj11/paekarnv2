<?php
namespace App\Models;

use App\Core\Database;

class ContentPlan
{
    public const PLATFORMS = ['facebook', 'line', 'instagram', 'other'];
    public const STATUSES  = ['draft', 'scheduled', 'published', 'cancelled'];

    public const PLATFORM_LABELS = [
        'facebook'  => 'Facebook',
        'line'      => 'LINE',
        'instagram' => 'Instagram',
        'other'     => 'อื่นๆ',
    ];

    public const STATUS_LABELS = [
        'draft'     => 'ร่าง',
        'scheduled' => 'ตั้งเวลา',
        'published' => 'โพสต์แล้ว',
        'cancelled' => 'ยกเลิก',
    ];

    public const STATUS_COLORS = [
        'draft'     => 'bg-slate-100 text-slate-600',
        'scheduled' => 'bg-blue-100 text-blue-700',
        'published' => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-red-100 text-red-600',
    ];

    /** รายการตามเดือน */
    public static function forMonth(int $ownerId, int $year, int $month): array
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
        return Database::fetchAll(
            "SELECT cp.*, p.name AS property_name
             FROM content_plans cp
             LEFT JOIN properties p ON p.id = cp.property_id
             WHERE cp.owner_id = :oid AND cp.post_date BETWEEN :f AND :t
             ORDER BY cp.post_date ASC, cp.id ASC",
            ['oid' => $ownerId, 'f' => $from, 't' => $to]
        );
    }

    /** รายการล่าสุด N รายการ */
    public static function recent(int $ownerId, int $limit = 5): array
    {
        return Database::fetchAll(
            "SELECT cp.*, p.name AS property_name
             FROM content_plans cp
             LEFT JOIN properties p ON p.id = cp.property_id
             WHERE cp.owner_id = :oid
             ORDER BY cp.post_date DESC, cp.id DESC LIMIT :lim",
            ['oid' => $ownerId, 'lim' => $limit]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetch("SELECT * FROM content_plans WHERE id = :id", ['id' => $id]);
    }

    public static function findForOwner(int $id, int $ownerId): ?array
    {
        return Database::fetch(
            "SELECT * FROM content_plans WHERE id = :id AND owner_id = :oid",
            ['id' => $id, 'oid' => $ownerId]
        );
    }

    public static function create(array $data): int
    {
        return Database::insert('content_plans', self::sanitize($data));
    }

    public static function update(int $id, array $data): void
    {
        Database::update('content_plans', self::sanitize($data), ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        Database::delete('content_plans', ['id' => $id]);
    }

    /** จำนวนโพสต์ในเดือนนี้ per status */
    public static function countThisMonth(int $ownerId): array
    {
        $rows = Database::fetchAll(
            "SELECT status, COUNT(*) c FROM content_plans
             WHERE owner_id = :oid AND DATE_FORMAT(post_date,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')
             GROUP BY status",
            ['oid' => $ownerId]
        );
        $map = ['draft' => 0, 'scheduled' => 0, 'published' => 0, 'cancelled' => 0];
        foreach ($rows as $r) {
            if (isset($map[$r['status']])) $map[$r['status']] = (int)$r['c'];
        }
        return $map;
    }

    private static function sanitize(array $d): array
    {
        $platform = in_array($d['platform'] ?? '', self::PLATFORMS) ? $d['platform'] : 'facebook';
        $status   = in_array($d['status'] ?? '', self::STATUSES)   ? $d['status']   : 'draft';
        return [
            'owner_id'     => (int)$d['owner_id'],
            'property_id'  => !empty($d['property_id']) ? (int)$d['property_id'] : null,
            'post_date'    => $d['post_date'],
            'platform'     => $platform,
            'status'       => $status,
            'title'        => mb_substr(trim((string)($d['title'] ?? '')), 0, 200),
            'body'         => trim((string)($d['body'] ?? '')),
            'hashtags'     => trim((string)($d['hashtags'] ?? '')) ?: null,
            'image_url'    => trim((string)($d['image_url'] ?? '')) ?: null,
            'ai_generated' => !empty($d['ai_generated']) ? 1 : 0,
        ];
    }
}
