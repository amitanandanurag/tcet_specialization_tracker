<?php
$inventory = json_decode(file_get_contents(__DIR__ . '/file_inventory.json'), true);

$subdirs = [];
foreach ($inventory as $f) {
    $parts = explode('/', $f['path']);
    if ($parts[0] === 'admin' && count($parts) > 2) {
        $sub = $parts[0] . '/' . $parts[1];
        $subdirs[$sub] = ($subdirs[$sub] ?? 0) + 1;
    }
}
arsort($subdirs);
echo "--- Breakdown of admin/ subdirectories ---\n";
foreach ($subdirs as $s => $c) {
    echo sprintf("%-30s: %4d files\n", $s, $c);
}

$rootAdminPhp = [];
foreach ($inventory as $f) {
    $parts = explode('/', $f['path']);
    if ($parts[0] === 'admin' && count($parts) === 2 && $f['ext'] === 'php') {
        $rootAdminPhp[] = $parts[1];
    }
}
sort($rootAdminPhp);
echo "\n--- Direct PHP files in admin/ (" . count($rootAdminPhp) . " files) ---\n";
foreach ($rootAdminPhp as $p) {
    echo " - $p\n";
}
