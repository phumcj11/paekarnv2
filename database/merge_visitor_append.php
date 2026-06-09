<?php
declare(strict_types=1);

/**
 * One-off: merge fenced PHP from DATA_APPEND_visitor_places.md into kanchanaburi_visitor_places.php
 * Run: php database/merge_visitor_append.php
 */

$base = __DIR__;
$mainPath = $base . '/data/kanchanaburi_visitor_places.php';
$mdPath   = $base . '/DATA_APPEND_visitor_places.md';

$m = file_get_contents($mainPath);
if (str_contains($m, "'slug' => 'thailand-burma-railway-centre'")) {
    echo "Append already in kanchanaburi_visitor_places.php — skip merge.\n";
    exit(0);
}
$d = file_get_contents($mdPath);
if (!preg_match('/```php\s*([\s\S]*?)```/', $d, $x)) {
    fwrite(STDERR, "Could not find php fenced block in DATA_APPEND_visitor_places.md\n");
    exit(1);
}
$s = trim($x[1]);
if ($s === '') {
    fwrite(STDERR, "Empty snippet\n");
    exit(1);
}
if (!preg_match('/,\s*\];\s*$/', $m)) {
    fwrite(STDERR, "Main file does not end with ], ]; pattern\n");
    exit(1);
}
$o = preg_replace('/,\s*\];\s*$/', ",\n" . $s . "\n];", $m, 1);
if ($o === null || $o === $m) {
    fwrite(STDERR, "Replace failed\n");
    exit(1);
}
file_put_contents($mainPath, $o);
echo "OK: merged append into data/kanchanaburi_visitor_places.php\n";
