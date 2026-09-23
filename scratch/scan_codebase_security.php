<?php
$dir = __DIR__ . '/../admin';
$files = glob("$dir/*.php");

echo "=== CODEBASE SCAN: AUTHORIZATION & SQL PATTERNS ===\n\n";

$authReport = [];
$sqlConcatReport = [];
$csrfReport = [];
$deadFiles = [];

foreach ($files as $f) {
    $base = basename($f);
    $content = file_get_contents($f);
    
    // Check for auth / session check
    $hasHeader = (strpos($content, 'header/header.php') !== false || strpos($content, 'header.php') !== false);
    $hasSessionCheck = (strpos($content, '$_SESSION') !== false);
    $hasRoleCheck = (strpos($content, 'usertype') !== false || strpos($content, 'role_id') !== false);
    
    // Check for raw SQL query concatenations
    $hasRawConcat = preg_match_all('/\$db_handle->query\s*\(\s*["\'].*\$_(POST|GET|REQUEST)/i', $content, $m1) ||
                    preg_match_all('/mysqli_query\s*\(\s*\$[^,]+,\s*["\'].*\$_(POST|GET|REQUEST)/i', $content, $m2);
                    
    // Check for prepared statements
    $hasPrepared = (strpos($content, 'mysqli_prepare') !== false || strpos($content, 'prepare(') !== false);
    
    // Check CSRF
    $hasCsrf = (strpos($content, 'csrf') !== false || strpos($content, 'CSRF') !== false);
    
    if (!$hasHeader && !$hasSessionCheck && strpos($base, 'ajax') === false && strpos($base, 'process') === false && strpos($base, 'get_') === false) {
        $authReport['missing_header'][] = $base;
    }
    
    if ($hasRawConcat) {
        $sqlConcatReport[] = $base;
    }
    
    if (strpos($base, '_old.php') !== false || strpos($base, '1.php') !== false) {
        $deadFiles[] = $base;
    }
}

echo "1. Files with raw \$_GET/\$_POST in SQL queries:\n";
foreach ($sqlConcatReport as $f) {
    echo "  - $f\n";
}

echo "\n2. Potential Dead/Duplicate Files:\n";
foreach ($deadFiles as $f) {
    echo "  - $f\n";
}

echo "\n3. Authorization Check in Headers:\n";
$headerContent = file_get_contents(__DIR__ . '/../admin/header/header.php');
echo "Header length: " . strlen($headerContent) . " bytes\n";
if (strpos($headerContent, 'session_start') !== false) echo "  - session_start present\n";
if (strpos($headerContent, 'username') !== false) echo "  - username check present\n";
if (strpos($headerContent, 'usertype') !== false) echo "  - usertype check present\n";
