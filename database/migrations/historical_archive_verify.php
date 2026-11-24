<?php
/**
 * TCET_ST — Historical Archive Post-Ingestion Verifier (Phase 3.2 Modular Tool)
 *
 * Verifies exact post-ingestion assertions:
 * - Batches: 2
 * - Candidates: 878 (SEM IV: 600, SEM VI: 278)
 * - Conflicts: 41
 * - Excluded Duplicates: 5
 * - Audit Log Entries: >= 21
 * - Promoted Student Count: STRICTLY 0
 *
 * Usage:
 *   php historical_archive_verify.php [--db=tcet_st_test|tcet_st_dryrun_verify]
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    die("CLI only.\n");
}

$options = getopt('', ['db::', 'help']);
if (isset($options['help'])) {
    echo "Usage: php historical_archive_verify.php [--db=tcet_st_test|tcet_st_dryrun_verify]\n";
    exit(0);
}

$targetDb = trim((string)($options['db'] ?? 'tcet_st_test'));

if ($targetDb === 'tcet_st') {
    fwrite(STDERR, "FATAL SECURITY BLOCK: Production database 'tcet_st' is locked.\n");
    exit(1);
}

$allowlist = ['tcet_st_test', 'tcet_st_dryrun_verify'];
if (!in_array($targetDb, $allowlist, true)) {
    fwrite(STDERR, "FATAL: Database '{$targetDb}' is not in allowlist.\n");
    exit(1);
}

$conn = @new mysqli('127.0.0.1', 'root', '', $targetDb);
if ($conn->connect_error) {
    fwrite(STDERR, "FATAL: Connection failed: " . $conn->connect_error . "\n");
    exit(1);
}

$activeRes = $conn->query("SELECT DATABASE() AS db");
$activeDb = $activeRes->fetch_assoc()['db'] ?? '';
if ($activeDb !== $targetDb) {
    fwrite(STDERR, "FATAL: Active DB mismatch: '{$activeDb}' !== '{$targetDb}'.\n");
    exit(1);
}

echo "=== HISTORICAL ARCHIVE POST-INGESTION VERIFIER ===\n";
echo "Database: {$activeDb}\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

$pass = true;

// 1. Batches check
$batchCount = (int)$conn->query("SELECT COUNT(*) FROM st_historical_import_batches")->fetch_row()[0];
if ($batchCount === 2) {
    echo "[PASS] Batches count = 2\n";
} else {
    echo "[FAIL] Batches count = {$batchCount}, expected 2\n";
    $pass = false;
}

// 2. Candidates check
$candCount = (int)$conn->query("SELECT COUNT(*) FROM st_historical_student_candidates")->fetch_row()[0];
if ($candCount === 878) {
    echo "[PASS] Historical candidates count = 878\n";
} else {
    echo "[FAIL] Historical candidates count = {$candCount}, expected 878\n";
    $pass = false;
}

// 2b. Cohort breakdown (semester_id: 4 = SEM IV, 6 = SEM VI)
$sem4Count = (int)$conn->query("SELECT COUNT(*) FROM st_historical_student_candidates WHERE semester_id = 4")->fetch_row()[0];
$sem6Count = (int)$conn->query("SELECT COUNT(*) FROM st_historical_student_candidates WHERE semester_id = 6")->fetch_row()[0];
if ($sem4Count === 600 && $sem6Count === 278) {
    echo "[PASS] Cohort distribution: SEM IV = 600, SEM VI = 278\n";
} else {
    echo "[FAIL] Cohort distribution: SEM IV = {$sem4Count} (exp 600), SEM VI = {$sem6Count} (exp 278)\n";
    $pass = false;
}

// 3. Conflicts check
$conflictCount = (int)$conn->query("SELECT COUNT(*) FROM st_historical_conflict_register")->fetch_row()[0];
if ($conflictCount === 41) {
    echo "[PASS] Conflict register count = 41\n";
} else {
    echo "[FAIL] Conflict register count = {$conflictCount}, expected 41\n";
    $pass = false;
}

// 4. Excluded Duplicates check
$dupCount = (int)$conn->query("SELECT COUNT(*) FROM st_historical_excluded_duplicates")->fetch_row()[0];
if ($dupCount === 5) {
    echo "[PASS] Excluded duplicates count = 5\n";
} else {
    echo "[FAIL] Excluded duplicates count = {$dupCount}, expected 5\n";
    $pass = false;
}

// 5. Audit Log check
$auditCount = (int)$conn->query("SELECT COUNT(*) FROM st_historical_candidate_audit")->fetch_row()[0];
if ($auditCount >= 21) {
    echo "[PASS] Audit log entries = {$auditCount} (>= 21)\n";
} else {
    echo "[FAIL] Audit log entries = {$auditCount}, expected >= 21\n";
    $pass = false;
}

// 6. ZERO ACTIVE PROMOTIONS ASSERTION
$promotedCount = (int)$conn->query("SELECT COUNT(*) FROM st_historical_student_candidates WHERE promoted_student_id IS NOT NULL")->fetch_row()[0];
if ($promotedCount === 0) {
    echo "[PASS] Promoted student count = 0 (STRICT ZERO-PROMOTION ENFORCED)\n";
} else {
    echo "[FAIL] Promoted student count = {$promotedCount}, expected 0!\n";
    $pass = false;
}

// 7. Active core student master row count check
$activeStudentCount = (int)$conn->query("SELECT COUNT(*) FROM st_student_master")->fetch_row()[0];
echo "[INFO] Current st_student_master row count = {$activeStudentCount}\n";

if ($pass) {
    echo "\n>>> VERIFICATION STATUS: 100% PASSED <<<\n";
    exit(0);
} else {
    echo "\n>>> VERIFICATION STATUS: ASSERTION FAILURES ENCOUNTERED <<<\n";
    exit(1);
}
