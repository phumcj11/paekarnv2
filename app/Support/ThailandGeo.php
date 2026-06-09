<?php

namespace App\Support;

/**
 * จังหวัด / อำเภอจากไฟล์ app/Data/thailand_geo.json (สร้างจาก scripts/build_thailand_geo.php)
 */
class ThailandGeo
{
    /** @var array{provinces: list<string>, districts: array<string, list<string>>}|null */
    private static ?array $data = null;

    /** @return array{provinces: list<string>, districts: array<string, list<string>>} */
    public static function load(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }

        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR . 'thailand_geo.json';
        if (!is_readable($path)) {
            return self::$data = ['provinces' => [], 'districts' => []];
        }

        $raw = json_decode((string)file_get_contents($path), true);
        if (!is_array($raw)) {
            return self::$data = ['provinces' => [], 'districts' => []];
        }

        $provinces = $raw['provinces'] ?? [];
        $districts = $raw['districts'] ?? [];
        if (!is_array($provinces)) {
            $provinces = [];
        }
        if (!is_array($districts)) {
            $districts = [];
        }

        $plist = [];
        foreach ($provinces as $p) {
            $t = trim((string)$p);
            if ($t !== '') {
                $plist[] = $t;
            }
        }

        $dmap = [];
        foreach ($districts as $prov => $list) {
            if (!is_string($prov) || !is_array($list)) {
                continue;
            }
            $names = [];
            foreach ($list as $x) {
                $t = trim((string)$x);
                if ($t !== '') {
                    $names[$t] = true;
                }
            }
            $keys = array_keys($names);
            sort($keys, SORT_STRING);
            $dmap[$prov] = $keys;
        }

        return self::$data = ['provinces' => $plist, 'districts' => $dmap];
    }

    /** @return array{provinces: list<string>, districts: array<string, list<string>>} */
    public static function forPropertyForm(): array
    {
        $districts = self::kanchanaburiDistricts();

        return [
            'provinces' => ['กาญจนบุรี'],
            'districts' => ['กาญจนบุรี' => $districts],
        ];
    }

    /** @return list<string> */
    public static function kanchanaburiDistricts(): array
    {
        $data = self::load();

        return $data['districts']['กาญจนบุรี'] ?? [];
    }

    /** @return array{provinces: list<string>, districts: array<string, list<string>>} */
    public static function forJs(): array
    {
        return self::load();
    }
}
