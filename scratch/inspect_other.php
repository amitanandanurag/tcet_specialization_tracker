<?php
$inventory = json_decode(file_get_contents(__DIR__ . '/file_inventory.json'), true);

echo "=== Files in database/ ===\n";
foreach ($inventory as $f) {
    if ($f['topDir'] === 'database') {
        echo " - {$f['path']} ({$f['size']} bytes)\n";
    }
}

echo "\n=== Files in login/ ===\n";
foreach ($inventory as $f) {
    if ($f['topDir'] === 'login') {
        echo " - {$f['path']} ({$f['size']} bytes)\n";
    }
}

echo "\n=== Files in root ===\n";
foreach ($inventory as $f) {
    if ($f['topDir'] === 'root') {
        echo " - {$f['path']} ({$f['size']} bytes)\n";
    }
}

echo "\n=== Files in images/ ===\n";
foreach ($inventory as $f) {
    if ($f['topDir'] === 'images') {
        echo " - {$f['path']} ({$f['size']} bytes)\n";
    }
}

echo "\n=== Files in scratch/ ===\n";
foreach ($inventory as $f) {
    if ($f['topDir'] === 'scratch') {
        echo " - {$f['path']} ({$f['size']} bytes)\n";
    }
}
