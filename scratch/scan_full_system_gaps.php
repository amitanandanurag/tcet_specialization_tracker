<?php
/**
 * Comprehensive System & Gap Scanner for TCET_ST
 */
require_once __DIR__ . '/../database/db_connect.php';

$baseDir = realpath(__DIR__ . '/..');
$adminDir = $baseDir . '/admin';
$loginDir = $baseDir . '/login';
$dbDir = $baseDir . '/database';

$phpFiles = array_merge(
    glob($baseDir . '/*.php'),
    glob($adminDir . '/*.php'),
    glob($adminDir . '/header/*.php'),
    glob($loginDir . '/*.php'),
    glob($dbDir . '/*.php')
);

// Filter out scratch directory
$phpFiles = array_filter($phpFiles, function($f) {
    return strpos($f, 'scratch') === false;
});

echo "Total PHP Source Files Scanned: " . count($phpFiles) . "\n\n";

$issues = [];
$stats = [
    'csrf_protected' => 0,
    'post_endpoints' => 0,
    'ajax_endpoints' => 0,
    'pages_with_auth' => 0,
    'sql_queries_total' => 0,
    'prepared_statements' => 0
];

foreach ($phpFiles as $file) {
    $relPath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file);
    $content = file_get_contents($file);
    
    $hasPost = (strpos($content, '$_POST') !== false);
    $hasGet = (strpos($content, '$_GET') !== false);
    $hasSession = (strpos($content, '$_SESSION') !== false || strpos($content, 'session_start') !== false);
    $hasCsrfCheck = (strpos($content, 'validateCsrfToken') !== false || strpos($content, 'csrf_token') !== false);
    $isAjax = (strpos($relPath, 'ajax') !== false || strpos($relPath, 'get_') !== false);
    $isHeader = (strpos($relPath, 'header') !== false);
    $isLogin = (strpos($relPath, 'login') !== false);
    
    if ($hasPost) $stats['post_endpoints']++;
    if ($isAjax) $stats['ajax_endpoints']++;
    if ($hasCsrfCheck) $stats['csrf_protected']++;
    if ($hasSession) $stats['pages_with_auth']++;
    
    // Check for potential raw GET actions (e.g. $_GET['action'] == 'delete')
    if (preg_match('/\$_GET\[[\'"](action|delete|del|status|update)[\'"]\]/i', $content, $m)) {
        $issues[] = "[P1/P2] Unsafe GET state mutation parameter in {$relPath}: " . $m[0];
    }
    
    // Check for unsafe SQL query patterns with direct concatenation
    if (preg_match_all('/(query|runQuery|numRows)\s*\(\s*["\'](SELECT|INSERT|UPDATE|DELETE)[^"\']*\$_(POST|GET|REQUEST)[^"\']*["\']\s*\)/i', $content, $m)) {
        foreach ($m[0] as $match) {
            $issues[] = "[P0/P1] Unsafe direct \$_POST/GET in SQL query in {$relPath}: " . substr($match, 0, 100);
        }
    }
    
    // Check for raw die(mysqli_error)
    if (preg_match('/die\s*\(\s*mysqli_error/i', $content, $m)) {
        $issues[] = "[P2] Raw mysqli_error exposed in {$relPath}";
    }
}

echo "=== SCAN SUMMARY ===\n";
echo "Post Endpoints: " . $stats['post_endpoints'] . "\n";
echo "AJAX Endpoints: " . $stats['ajax_endpoints'] . "\n";
echo "CSRF Protected: " . $stats['csrf_protected'] . "\n";
echo "Authenticated:  " . $stats['pages_with_auth'] . "\n\n";

echo "=== POTENTIAL ISSUES / GAPS IDENTIFIED (" . count($issues) . ") ===\n";
foreach ($issues as $idx => $issue) {
    echo ($idx + 1) . ". " . $issue . "\n";
}
