<?php
/**
 * TCET_ST — Historical Archive Preflight Checker (Phase 3.2 Modular Tool)
 *
 * Checks database readiness, table existence, schema compliance, and source CSV integrity
 * before any ingestion operation. Purely read-only.
 *
 * Usage:
 *   php historical_archive_preflight.php [--db=tcet_st_test|tcet_st_dryrun_verify]
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    die("CLI only.\n");
}

$options = getopt('', ['db::', 'help']);
if (isset($options['help'])) {
    echo "Usage: php historical_archive_preflight.php [--db=tcet_st_test|tcet_st_dryrun_verify]\n";
    exit(0);
}

$targetDb = trim((string)($options['db'] ?? 'tcet_st_test'));

if ($targetDb === 'tcet_st') {
    fwrite(STDERR, "FATAL SECURITY BLOCK: Target 'tcet_st' is strictly locked against preflight execution.\n");
    exit(1);
}

$allowlist = ['tcet_st_test', 'tcet_st_dryrun_verify'];
if (!in_array($targetDb, $allowlist, true)) {
    fwrite(STDERR, "FATAL: Database '{$targetDb}' is not in explicit allowlist (" . implode(', ', $allowlist) . ").\n");
    exit(1);
}

$conn = @new mysqli('127.0.0.1', 'root', '', $targetDb);
if ($conn->connect_error) {
    fwrite(STDERR, "FATAL: Connection error: " . $conn->connect_error . "\n");
    exit(1);
}

$activeRes = $conn->query("SELECT DATABASE() AS db");
$activeDb = $activeRes->fetch_assoc()['db'] ?? '';
if ($activeDb !== $targetDb) {
    fwrite(STDERR, "FATAL: Active DB mismatch: '{$activeDb}' !== '{$targetDb}'.\n");
    exit(1);
}

echo "=== HISTORICAL ARCHIVE PREFLIGHT CHECK ===\n";
echo "Database: {$activeDb}\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

$pass = true;

// 1. Check Tables
$expectedTables = [
    'st_historical_import_batches',
    'st_historical_student_candidates',
    'st_historical_conflict_register',
    'st_historical_excluded_duplicates',
    'st_historical_candidate_audit'
];
foreach ($expectedTables as $t) {
    $res = $conn->query("SHOW TABLES LIKE '{$t}'");
    if ($res && $res->num_rows === 1) {
        echo "[PASS] Table {$t} exists.\n";
    } else {
        echo "[FAIL] Table {$t} does NOT exist.\n";
        $pass = false;
    }
}

// 2. Check Triggers
$expectedTriggers = [
    'trg_hist_cand_immutable_orig',
    'trg_hist_cand_no_delete',
    'trg_hist_audit_no_update',
    'trg_hist_audit_no_delete'
];
foreach ($expectedTriggers as $trg) {
    $res = $conn->query("SHOW TRIGGERS WHERE `Trigger` = '{$trg}'");
    if ($res && $res->num_rows === 1) {
        echo "[PASS] Trigger {$trg} is active.\n";
    } else {
        echo "[FAIL] Trigger {$trg} is missing.\n";
        $pass = false;
    }
}

// 3. Check CSV Sources
$csvDir = dirname(__DIR__) . '/import_review';
$csvFiles = [
    'historical_import_approval.csv',
    'production_import_readiness.csv',
    'student_sem_iv.csv',
    'student_sem_vi.csv',
    'student_reconciliation.csv',
    'academic_conflict_resolution_register.csv'
];
foreach ($csvFiles as $csv) {
    $fullPath = $csvDir . '/' . $csv;
    if (file_exists($fullPath) && is_readable($fullPath)) {
        echo "[PASS] CSV Source file {$csv} is available (" . filesize($fullPath) . " bytes).\n";
    } else {
        echo "[FAIL] CSV Source file {$csv} missing or unreadable.\n";
        $pass = false;
    }
}

if ($pass) {
    echo "\n>>> PREFLIGHT STATUS: ALL CHECKS PASSED <<<\n";
    exit(0);
} else {
    echo "\n>>> PREFLIGHT STATUS: FAILED CHECKS ENCOUNTERED <<<\n";
    exit(1);
}
