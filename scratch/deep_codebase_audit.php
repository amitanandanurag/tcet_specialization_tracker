<?php
$rootDir = realpath(__DIR__ . '/..');

echo "=== TCET ERP DEEP CODEBASE AUDIT SCRIPT ===\n\n";

$adminFiles = glob("$rootDir/admin/*.php");
$adminHeaderFiles = glob("$rootDir/admin/header/*.php");
$loginFiles = glob("$rootDir/login/*.php");
$databaseFiles = glob("$rootDir/database/*.php");

$allFiles = array_merge($adminFiles, $adminHeaderFiles, $loginFiles, $databaseFiles);

$report = [
    'total_files' => count($allFiles),
    'mutating_post_files' => [],
    'unprotected_post_files' => [],
    'mutating_get_files' => [],
    'ajax_endpoints' => [],
    'file_upload_endpoints' => [],
    'raw_sql_interpolations' => [],
    'die_or_raw_errors' => [],
    'session_calls' => [],
    'potential_xss' => []
];

foreach ($allFiles as $file) {
    $rel = str_replace($rootDir . DIRECTORY_SEPARATOR, '', $file);
    $content = file_get_contents($file);
    
    // Check for AJAX
    if (strpos($rel, 'ajax') !== false) {
        $hasAuth = (strpos($content, 'header.php') !== false || strpos($content, '$_SESSION') !== false || strpos($content, 'session_start') !== false);
        $hasPrepared = (strpos($content, 'prepare(') !== false || strpos($content, 'mysqli_prepare') !== false);
        $report['ajax_endpoints'][] = [
            'file' => $rel,
            'has_auth' => $hasAuth,
            'has_prepared' => $hasPrepared
        ];
    }

    // Check for file uploads
    if (strpos($content, '$_FILES') !== false) {
        $report['file_upload_endpoints'][] = $rel;
    }

    // Check for mutating POST
    $hasPost = (strpos($content, '$_POST') !== false);
    $hasInsertUpdateDelete = preg_match('/\b(INSERT\s+INTO|UPDATE\s+|DELETE\s+FROM)\b/i', $content);
    $hasCsrfCheck = (strpos($content, 'validateCsrfToken') !== false || strpos($content, 'csrf_token') !== false);

    if ($hasPost && $hasInsertUpdateDelete) {
        $report['mutating_post_files'][] = $rel;
        if (!$hasCsrfCheck && strpos($rel, 'ajax') === false && strpos($rel, 'login.php') === false) {
            $report['unprotected_post_files'][] = $rel;
        }
    }

    // Check for mutating GET
    if (strpos($content, '$_GET') !== false && preg_match('/\b(DELETE\s+FROM|UPDATE\s+.*SET|INSERT\s+INTO)\b/i', $content)) {
        $report['mutating_get_files'][] = $rel;
    }

    // Check for raw SQL interpolation with $_GET, $_POST, or $_REQUEST
    if (preg_match_all('/(mysqli_query|\$db_handle->query|\$db->query|\$this->conn->query)\s*\([^;]*\$_(POST|GET|REQUEST)\[/i', $content, $m)) {
        $report['raw_sql_interpolations'][$rel] = count($m[0]);
    }

    // Check for raw die(mysqli_error)
    if (preg_match_all('/(die|exit)\s*\([^;]*mysqli_error/i', $content, $mErr)) {
        $report['die_or_raw_errors'][$rel] = count($mErr[0]);
    }
}

echo "1. TOTAL AUDITED PHP FILES: " . $report['total_files'] . "\n\n";

echo "2. MUTATING POST FILES (" . count($report['mutating_post_files']) . "):\n";
foreach ($report['mutating_post_files'] as $f) {
    echo "  - $f\n";
}

echo "\n3. UNPROTECTED MUTATING POST FILES (Missing CSRF check) (" . count($report['unprotected_post_files']) . "):\n";
foreach ($report['unprotected_post_files'] as $f) {
    echo "  - $f\n";
}

echo "\n4. MUTATING GET FILES (Dangerous GET mutations) (" . count($report['mutating_get_files']) . "):\n";
foreach ($report['mutating_get_files'] as $f) {
    echo "  - $f\n";
}

echo "\n5. AJAX ENDPOINTS (" . count($report['ajax_endpoints']) . "):\n";
foreach ($report['ajax_endpoints'] as $ae) {
    echo sprintf("  - %-35s | Auth: %-5s | Prepared: %-5s\n", $ae['file'], $ae['has_auth'] ? 'YES' : 'NO', $ae['has_prepared'] ? 'YES' : 'NO');
}

echo "\n6. FILE UPLOAD ENDPOINTS (" . count($report['file_upload_endpoints']) . "):\n";
foreach ($report['file_upload_endpoints'] as $f) {
    echo "  - $f\n";
}

echo "\n7. FILES WITH DIRECT RAW SQL CONCATENATIONS OF USER INPUT (" . count($report['raw_sql_interpolations']) . "):\n";
foreach ($report['raw_sql_interpolations'] as $f => $cnt) {
    echo "  - $f ($cnt occurrences)\n";
}

echo "\n8. FILES LEAKING RAW DB ERRORS (mysqli_error in die/exit) (" . count($report['die_or_raw_errors']) . "):\n";
foreach ($report['die_or_raw_errors'] as $f => $cnt) {
    echo "  - $f ($cnt occurrences)\n";
}
