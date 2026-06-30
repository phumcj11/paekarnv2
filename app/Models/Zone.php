<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/** Canonical zone labels for forms; `properties.zone` stores matching text (no FK). */
class Zone extends Model
{
    protected static string $table = 'zones';

    /** @return list<string> Same defaults seeded in migrations/schema */
    public static function defaultPresetNames(): array
    {
        return [
            'เขื่อนศรีนครินทร์',
            'เขื่อนวชิราลงกรณ์',
            'สังขละบุรี',
            'แม่น้ำแคว',
            'แควน้อย',
            'แควใหญ่',
            'อุทยานแห่งชาติเอราวัณ',
            'ไทรโยค',
            'ทองผาภูมิ',
            'ศรีสวัสดิ์',
        ];
    }

    /** คำอธิบายสั้น ๆ ใน dropdown — ช่วยเลือกโzo ให้ตรง section หน้าแรก */
    public static function selectHint(string $zone): string
    {
        return match ($zone) {
            'แม่น้ำแคว', 'แควใหญ่', 'ริมแม่น้ำแคว' => 'หน้าเมือง / แพริมแม่น้ำแคว',
            'แควน้อย', 'ริมแม่น้ำแควน้อย', 'น้ำตกไทรโยคน้อย' => 'ไทรโยคน้อย / แพริมแควน้อย',
            'เขื่อนวชิราลงกรณ์' => 'เขื่อนเขาแหลม',
            default => '',
        };
    }

    public static function labelForSelect(string $zone): string
    {
        $hint = self::selectHint($zone);

        return $hint !== '' ? $zone . ' — ' . $hint : $zone;
    }

    public static function tableExists(): bool
    {
        try {
            Database::fetch('SELECT 1 FROM `zones` LIMIT 1');

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return list<string> Ordered master names only */
    public static function orderedMasterNames(): array
    {
        if (!self::tableExists()) {
            return [];
        }
        $rows = Database::fetchAll('SELECT `name` FROM `zones` ORDER BY `sort_order` ASC, `name` ASC');
        $out = [];
        foreach ($rows as $r) {
            $n = trim((string)($r['name'] ?? ''));
            if ($n !== '') {
                $out[] = $n;
            }
        }

        return $out;
    }

    /**
     * Master zones (ordered) + legacy names from properties + optional ensure.
     *
     * @return list<string>
     */
    public static function namesForSelectMerged(?string $ensureInclude = null): array
    {
        $masterOrdered = self::orderedMasterNames();
        if ($masterOrdered === []) {
            $masterOrdered = self::defaultPresetNames();
        }

        $all = [];
        foreach ($masterOrdered as $n) {
            $all[$n] = true;
        }

        $legacy = Database::fetchAll(
            "SELECT DISTINCT TRIM(zone) AS zone FROM properties
             WHERE zone IS NOT NULL AND TRIM(zone) <> ''"
        );
        foreach ($legacy as $r) {
            $z = trim((string)($r['zone'] ?? ''));
            if ($z !== '') {
                $all[$z] = true;
            }
        }

        if ($ensureInclude !== null) {
            $e = trim($ensureInclude);
            if ($e !== '') {
                $all[$e] = true;
            }
        }

        $out = [];
        foreach ($masterOrdered as $n) {
            if (isset($all[$n])) {
                $out[] = $n;
                unset($all[$n]);
            }
        }

        $rest = array_keys($all);
        sort($rest, SORT_STRING);

        return array_merge($out, $rest);
    }

    /** @return array<int,array<string,mixed>> */
    public static function adminRowsWithUsage(): array
    {
        if (!self::tableExists()) {
            return [];
        }

        $districtCol = self::districtMapTableExists()
            ? ', (SELECT GROUP_CONCAT(m.district ORDER BY m.sort_order ASC, m.district ASC SEPARATOR \', \')
                 FROM zone_district_map m WHERE m.zone_name = z.name) AS mapped_districts'
            : ', NULL AS mapped_districts';

        return Database::fetchAll(
            'SELECT z.*,
                (SELECT COUNT(*) FROM properties p WHERE TRIM(p.zone) = z.name) AS cnt_properties,
                (SELECT COUNT(*) FROM properties p WHERE TRIM(p.zone) = z.name AND p.status = \'published\') AS cnt_published,
                (SELECT COUNT(*) FROM visitor_places vp WHERE TRIM(vp.zone) = z.name) AS cnt_visitor_places'
            . $districtCol . '
             FROM zones z
             ORDER BY z.sort_order ASC, z.name ASC'
        );
    }

    public static function findByName(string $name): ?array
    {
        if (!self::tableExists()) {
            return null;
        }

        return Database::fetch('SELECT * FROM zones WHERE name = :n LIMIT 1', ['n' => $name]);
    }

    /** Rename zone text everywhere when master row label changes */
    public static function applyRenameEverywhere(string $oldName, string $newName): void
    {
        $oldName = trim($oldName);
        $newName = trim($newName);
        if ($oldName === '' || $newName === '' || $oldName === $newName) {
            return;
        }

        Database::query(
            'UPDATE properties SET zone = :new WHERE TRIM(zone) = :old',
            ['new' => $newName, 'old' => $oldName]
        );
        Database::query(
            'UPDATE visitor_places SET zone = :new WHERE TRIM(zone) = :old',
            ['new' => $newName, 'old' => $oldName]
        );

        try {
            Database::query(
                'UPDATE leads SET preferred_zone = :new WHERE TRIM(preferred_zone) = :old',
                ['new' => $newName, 'old' => $oldName]
            );
        } catch (\Throwable $e) {
            // schema เก่าอาจไม่มีคอลัมน์
        }

        $raw = \App\Models\Setting::get('home_zone_cover_images', '{}');
        $map = json_decode((string)$raw, true);
        if (is_array($map) && array_key_exists($oldName, $map)) {
            $map[$newName] = $map[$oldName];
            unset($map[$oldName]);
            \App\Models\Setting::set('home_zone_cover_images', json_encode($map, JSON_UNESCAPED_UNICODE));
        }

        if (self::districtMapTableExists()) {
            Database::query(
                'UPDATE zone_district_map SET zone_name = :new WHERE zone_name = :old',
                ['new' => $newName, 'old' => $oldName]
            );
        }
    }

    public static function usageCountsForName(string $name): array
    {
        $name = trim($name);
        $props = (int)Database::fetch(
            'SELECT COUNT(*) AS c FROM properties WHERE TRIM(zone) = :z',
            ['z' => $name]
        )['c'];
        $places = (int)Database::fetch(
            'SELECT COUNT(*) AS c FROM visitor_places WHERE TRIM(zone) = :z',
            ['z' => $name]
        )['c'];

        return ['properties' => $props, 'visitor_places' => $places];
    }

    public static function districtMapTableExists(): bool
    {
        try {
            Database::fetch('SELECT 1 FROM `zone_district_map` LIMIT 1');

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return list<string> */
    public static function recommendedZonesForDistrict(string $district): array
    {
        $district = trim($district);
        if ($district === '' || !self::districtMapTableExists()) {
            return [];
        }

        $rows = Database::fetchAll(
            'SELECT zone_name FROM zone_district_map
             WHERE district = :d
             ORDER BY sort_order ASC, zone_name ASC',
            ['d' => $district]
        );
        $out = [];
        foreach ($rows as $r) {
            $z = trim((string)($r['zone_name'] ?? ''));
            if ($z !== '') {
                $out[] = $z;
            }
        }

        return $out;
    }

    /**
     * district => list of zone names (for JS on property form).
     *
     * @return array<string, list<string>>
     */
    public static function districtMapGrouped(): array
    {
        if (!self::districtMapTableExists()) {
            return [];
        }

        $rows = Database::fetchAll(
            'SELECT district, zone_name FROM zone_district_map ORDER BY district ASC, sort_order ASC, zone_name ASC'
        );
        $map = [];
        foreach ($rows as $r) {
            $d = trim((string)($r['district'] ?? ''));
            $z = trim((string)($r['zone_name'] ?? ''));
            if ($d === '' || $z === '') {
                continue;
            }
            $map[$d][] = $z;
        }

        return $map;
    }

    /** @return list<string> */
    public static function districtsForZoneName(string $zoneName): array
    {
        $zoneName = trim($zoneName);
        if ($zoneName === '' || !self::districtMapTableExists()) {
            return [];
        }

        $rows = Database::fetchAll(
            'SELECT district FROM zone_district_map
             WHERE zone_name = :z
             ORDER BY sort_order ASC, district ASC',
            ['z' => $zoneName]
        );
        $out = [];
        foreach ($rows as $r) {
            $d = trim((string)($r['district'] ?? ''));
            if ($d !== '') {
                $out[] = $d;
            }
        }

        return $out;
    }

    /** @param list<string> $districts */
    public static function syncDistrictMapForZoneName(string $zoneName, array $districts): void
    {
        $zoneName = trim($zoneName);
        if ($zoneName === '' || !self::districtMapTableExists()) {
            return;
        }

        Database::query('DELETE FROM zone_district_map WHERE zone_name = :z', ['z' => $zoneName]);

        $sort = 0;
        foreach ($districts as $d) {
            $d = trim((string)$d);
            if ($d === '') {
                continue;
            }
            $sort++;
            Database::insert('zone_district_map', [
                'zone_name'  => $zoneName,
                'district'   => $d,
                'sort_order' => $sort,
            ]);
        }
    }

    /** @param list<string> $districts */
    public static function syncDistrictMap(int $zoneId, array $districts): void
    {
        $row = self::find($zoneId);
        if (!$row) {
            return;
        }

        self::syncDistrictMapForZoneName((string)($row['name'] ?? ''), $districts);
    }

    public static function zoneMatchesDistrict(?string $zone, ?string $district): bool
    {
        $zone = trim((string)$zone);
        $district = trim((string)$district);
        if ($zone === '' || $district === '') {
            return true;
        }

        if ($zone === $district) {
            return true;
        }

        $recommended = self::recommendedZonesForDistrict($district);
        if ($recommended === []) {
            return true;
        }

        return in_array($zone, $recommended, true);
    }

    public static function maybeFlashDistrictZoneMismatch(?string $district, ?string $zone): void
    {
        if (self::zoneMatchesDistrict($zone, $district)) {
            return;
        }

        \App\Core\Session::flash(
            'info',
            'หมายเหตุ: โซนที่เลือกไม่อยู่ในรายการแนะนำสำหรับอำเภอนี้ — แอดมินจะตรวจสอบอีกครั้ง'
        );
    }
}
