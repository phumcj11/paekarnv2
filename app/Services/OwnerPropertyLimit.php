<?php

namespace App\Services;

use App\Core\Database;

/**
 * โควต้าจำนวนที่พักต่อ owner
 *
 * Default: max_properties = 1 — Owner สร้างได้ 1 ที่พัก (หลายยูนิตได้)
 * Admin ปรับ max_properties ให้แต่ละ owner ผ่าน /admin/owners/{id}/edit
 */
final class OwnerPropertyLimit
{
    /** จำนวนที่พักสูงสุดที่ owner คนนี้สร้างได้ */
    public static function maxProperties(int $ownerId): int
    {
        if (!Database::tableHasColumn('owners', 'max_properties')) {
            return 1;
        }
        $row = Database::fetch('SELECT max_properties FROM owners WHERE id = :id', ['id' => $ownerId]);
        return max(1, (int)($row['max_properties'] ?? 1));
    }

    /** จำนวนที่พักที่ owner มีอยู่ตอนนี้ */
    public static function currentCount(int $ownerId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS cnt FROM properties WHERE owner_id = :id',
            ['id' => $ownerId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    /** true = ยังสร้างที่พักเพิ่มได้ */
    public static function canAddProperty(int $ownerId): bool
    {
        return self::currentCount($ownerId) < self::maxProperties($ownerId);
    }

    /** true = มีที่พักเกินโควต้า (เกิดหลัง admin reset max หรือ import ข้อมูล) */
    public static function isOverQuota(int $ownerId): bool
    {
        return self::currentCount($ownerId) > self::maxProperties($ownerId);
    }
}
