<?php
$inventory = json_decode(file_get_contents(__DIR__ . '/file_inventory.json'), true);
$root = 'c:/xampp/htdocs/st';

// Collect all text/source files to scan (php, js, css, html, sql, htaccess)
$scanFiles = [];
$scanExts = ['php', 'js', 'css', 'html', 'sql', 'htaccess', 'json'];
foreach ($inventory as $f) {
    if (in_array($f['ext'], $scanExts) && strpos($f['path'], 'scratch/') === false) {
        $full = "$root/{$f['path']}";
        if (file_exists($full)) {
            $scanFiles[$f['path']] = file_get_contents($full);
        }
    }
}

echo "Loaded " . count($scanFiles) . " source files for content reference search.\n\n";

// Now, for every PHP file in admin/, check references
$adminPhpFiles = [];
foreach ($inventory as $f) {
    $parts = explode('/', $f['path']);
    if ($parts[0] === 'admin' && count($parts) === 2 && $f['ext'] === 'php') {
        $adminPhpFiles[] = $parts[1];
    }
}

$refReport = [];
foreach ($adminPhpFiles as $phpFile) {
    $refs = [];
    $baseName = $phpFile; // e.g. "student.php"
    
    foreach ($scanFiles as $scanPath => $content) {
        if ($scanPath === "admin/$phpFile") continue; // skip self
        
        // Search for exact filename with word boundary / quotes / slashes
        if (stripos($content, $baseName) !== false) {
            // Count occurrences or find snippet
            $refs[] = $scanPath;
        }
    }
    $refReport[$phpFile] = $refs;
}

echo "=== Admin PHP Files Reference Summary ===\n";
$zeroRefs = [];
foreach ($refReport as $phpFile => $refs) {
    if (empty($refs)) {
        $zeroRefs[] = $phpFile;
        echo sprintf("[0 REFS] %-35s : NO references found in source files\n", $phpFile);
    } else {
        echo sprintf("[%2d refs] %-35s : referenced in %s\n", count($refs), $phpFile, implode(', ', array_slice($refs, 0, 3)) . (count($refs) > 3 ? '...' : ''));
    }
}

echo "\n--- Total Zero References: " . count($zeroRefs) . " files ---\n";
print_r($zeroRefs);

// Save full ref report
file_put_contents(__DIR__ . '/admin_php_refs.json', json_encode($refReport, JSON_PRETTY_PRINT));
