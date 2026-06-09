<?php
/**
 * Merge kongvut/thai-province-data province + district JSON into app/Data/thailand_geo.json
 *
 * Download แล้ววางไฟล์ชั่วคราวในโฟลเดอร์โปรเจกต์:
 *   curl.exe -sL -o tmp_province.json "https://raw.githubusercontent.com/kongvut/thai-province-data/master/api/latest/province.json"
 *   curl.exe -sL -o tmp_district.json "https://raw.githubusercontent.com/kongvut/thai-province-data/master/api/latest/district.json"
 *
 * Run: php scripts/build_thailand_geo.php
 */
$root = dirname(__DIR__);
$provPath = $root . DIRECTORY_SEPARATOR . 'tmp_province.json';
$distPath = $root . DIRECTORY_SEPARATOR . 'tmp_district.json';
if (!is_file($provPath) || !is_file($distPath)) {
    fwrite(STDERR, "Missing tmp_province.json or tmp_district.json in project root. Download from kongvut/thai-province-data api/latest.\n");
    exit(1);
}

$prov = json_decode(file_get_contents($provPath), true);
$dist = json_decode(file_get_contents($distPath), true);
if (!is_array($prov) || !is_array($dist)) {
    fwrite(STDERR, "Invalid JSON input.\n");
    exit(1);
}

$idToName = [];
foreach ($prov as $p) {
    if (!empty($p['deleted_at'])) {
        continue;
    }
    $idToName[(int)$p['id']] = (string)$p['name_th'];
}

$byProvince = [];
foreach ($dist as $d) {
    if (!empty($d['deleted_at'])) {
        continue;
    }
    $pid = (int)($d['province_id'] ?? 0);
    if (!isset($idToName[$pid])) {
        continue;
    }
    $pname = $idToName[$pid];
    $dname = trim((string)($d['name_th'] ?? ''));
    if ($dname === '') {
        continue;
    }
    $byProvince[$pname][$dname] = true;
}

foreach ($byProvince as $pn => $set) {
    $list = array_keys($set);
    sort($list, SORT_STRING);
    $byProvince[$pn] = $list;
}

$provinceNames = array_values(array_unique(array_values($idToName)));
sort($provinceNames, SORT_STRING);

$outDir = $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Data';
if (!is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

$target = $outDir . DIRECTORY_SEPARATOR . 'thailand_geo.json';
$payload = ['provinces' => $provinceNames, 'districts' => $byProvince];
file_put_contents($target, json_encode($payload, JSON_UNESCAPED_UNICODE));

echo "Wrote {$target} (" . count($provinceNames) . ' provinces)' . PHP_EOL;
