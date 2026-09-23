<?php
$inventory = json_decode(file_get_contents(__DIR__ . '/file_inventory.json'), true);

$dirsToCheck = [
    'admin/css',
    'admin/js',
    'admin/images',
    'admin/uploads',
    'admin/emp-photos',
    'admin/student_photo',
    'login/css',
    'login/js',
    'login/images',
    'login/script',
    'images'
];

foreach ($dirsToCheck as $dir) {
    echo "=== Directory: $dir ===\n";
    foreach ($inventory as $f) {
        if (strpos($f['path'], $dir . '/') === 0 || $f['path'] === $dir) {
            echo " - {$f['path']} ({$f['size']} bytes)\n";
        }
    }
    echo "\n";
}
