<?php
/**
 * Detailed SQL and Input Handling Audit for TCET_ST
 */
require_once __DIR__ . '/../database/db_connect.php';

$baseDir = realpath(__DIR__ . '/..');
$files = array_merge(
    glob($baseDir . '/admin/*.php'),
    glob($baseDir . '/login/*.php'),
    glob($baseDir . '/admin/header/*.php')
);

$findings = [];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $rel = basename($file);
    
    // Check if $_POST or $_GET is used directly in queries without escaping or intval
    // Look for patterns like: $sql = "... " . $_POST['x'] . " ...";
    if (preg_match_all('/(\$(?:sql|query|select|insert|update|delete)[a-zA-Z0-9_]*\s*=\s*["\'][^"\']*\$_[A-Z]+\[[^\]]+\][^"\']*["\'])/i', $content, $matches)) {
        foreach ($matches[1] as $m) {
            $findings[] = "Direct global variable in SQL in $rel: " . trim($m);
        }
    }
}

echo "=== SQL AUDIT FINDINGS ===\n";
echo "Total potential raw concatenations: " . count($findings) . "\n";
foreach ($findings as $f) {
    echo "- $f\n";
}
