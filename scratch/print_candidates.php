<?php
$details = json_decode(file_get_contents(__DIR__ . '/inspected_details.json'), true);

foreach ($details as $path => $info) {
    if (!$info['exists']) continue;
    echo "========================================================\n";
    echo "FILE: $path ({$info['lines']} lines, {$info['size']} bytes)\n";
    echo "--------------------------------------------------------\n";
    echo $info['preview'] . "\n";
}
