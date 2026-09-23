<?php
$root = 'c:/xampp/htdocs/st';
$ite = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$files = [];
$extCounts = [];
$dirCounts = [];

foreach ($ite as $f) {
    $pathname = $f->getPathname();
    if (strpos($pathname, '.git') !== false || strpos($pathname, '.vscode') !== false) {
        continue;
    }
    $rel = str_replace('\\', '/', substr($pathname, strlen($root) + 1));
    $ext = strtolower($f->getExtension());
    $size = $f->getSize();
    
    $parts = explode('/', $rel);
    $topDir = count($parts) > 1 ? $parts[0] : 'root';
    
    $extCounts[$ext] = ($extCounts[$ext] ?? 0) + 1;
    $dirCounts[$topDir] = ($dirCounts[$topDir] ?? 0) + 1;
    
    $files[] = [
        'path' => $rel,
        'size' => $size,
        'ext' => $ext,
        'topDir' => $topDir
    ];
}

echo "=== Total Files: " . count($files) . " ===\n\n";

echo "--- Breakdown by Top-Level Directory ---\n";
arsort($dirCounts);
foreach ($dirCounts as $d => $c) {
    echo sprintf("%-20s: %4d files\n", $d, $c);
}

echo "\n--- Breakdown by Extension ---\n";
arsort($extCounts);
foreach ($extCounts as $e => $c) {
    echo sprintf("%-10s: %4d files\n", ($e ? $e : '[no ext]'), $c);
}

file_put_contents(__DIR__ . '/file_inventory.json', json_encode($files, JSON_PRETTY_PRINT));
echo "\nSaved inventory to scratch/file_inventory.json\n";
