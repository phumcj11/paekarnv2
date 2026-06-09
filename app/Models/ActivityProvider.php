<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class ActivityProvider extends Model
{
    protected static string $table = 'activity_providers';

    public const TYPES = [
        'tour_operator'    => 'บริษัททัวร์ / กิจกรรม',
        'car_rental'       => 'รถเช่า',
        'private_driver'   => 'รถพร้อมคนขับ',
        'van_transfer'     => 'รถตู้ / รับส่ง',
        'boat_service'     => 'เรือ / แพนำเที่ยว',
        'guide'            => 'ไกด์ / รถนำเที่ยว',
        'equipment_rental' => 'เช่าอุปกรณ์',
        'other'            => 'อื่นๆ',
    ];

    public static function tableReady(): bool
    {
        return Database::tableHasColumn('activity_providers', 'id');
    }

    /** @return list<array<string,mixed>> */
    public static function activeForSelect(): array
    {
        if (!self::tableReady()) {
            return [];
        }

        return Database::fetchAll(
            "SELECT id, name, type, district, zone FROM activity_providers
             WHERE status = 'active'
             ORDER BY name ASC"
        );
    }

    /** @return list<array<string,mixed>> */
    public static function adminAll(): array
    {
        if (!self::tableReady()) {
            return [];
        }

        $partnerCol = Database::tableHasColumn('activity_providers', 'partner_status')
            ? 'partner_status' : 'status';

        return Database::fetchAll(
            "SELECT *, {$partnerCol} AS partner_status_display FROM activity_providers
             ORDER BY
               CASE WHEN {$partnerCol} = 'pending' THEN 0 WHEN {$partnerCol} = 'active' THEN 1 ELSE 2 END,
               district ASC, name ASC"
        );
    }

    public static function findByUserId(int $userId): ?array
    {
        if (!self::tableReady() || !Database::tableHasColumn('activity_providers', 'user_id')) {
            return null;
        }

        return Database::fetch(
            'SELECT * FROM activity_providers WHERE user_id = :u LIMIT 1',
            ['u' => $userId]
        );
    }

    public static function pendingCount(): int
    {
        if (!self::tableReady() || !Database::tableHasColumn('activity_providers', 'partner_status')) {
            return 0;
        }
        $row = Database::fetch(
            "SELECT COUNT(*) AS c FROM activity_providers WHERE partner_status = 'pending'"
        );

        return (int)($row['c'] ?? 0);
    }

    public static function partnerStatusLabel(string $status): string
    {
        return match ($status) {
            'pending'     => 'รออนุมัติ',
            'active'      => 'ใช้งานได้',
            'paused'      => 'พักชั่วคราว',
            'terminated'  => 'ยกเลิก',
            default       => $status,
        };
    }
}
