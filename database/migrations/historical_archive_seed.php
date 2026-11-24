<?php
/**
 * TCET_ST — Historical Archive Modular Seeder (Phase 3.2 Modular Tool)
 *
 * Ingests authoritative historical student records into the 5 historical archive tables:
 * - st_historical_import_batches
 * - st_historical_student_candidates
 * - st_historical_conflict_register
 * - st_historical_excluded_duplicates
 * - st_historical_candidate_audit (via triggers)
 *
 * Purely transactional, idempotent, zero active promotions.
 *
 * Usage:
 *   php historical_archive_seed.php [--dry-run|--apply] [--db=tcet_st_test|tcet_st_dryrun_verify]
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    die("CLI only.\n");
}

$options = getopt('', ['dry-run', 'apply', 'db::', 'help']);
if (isset($options['help'])) {
    echo "Usage: php historical_archive_seed.php [--dry-run|--apply] [--db=tcet_st_test|tcet_st_dryrun_verify]\n";
    exit(0);
}

$isApply = isset($options['apply']);
$isDryRun = !$isApply || isset($options['dry-run']);
$targetDb = trim((string)($options['db'] ?? 'tcet_st_test'));

if ($targetDb === 'tcet_st') {
    fwrite(STDERR, "FATAL SECURITY BLOCK: Production database 'tcet_st' is locked against seeding.\n");
    exit(1);
}

$allowlist = ['tcet_st_test', 'tcet_st_dryrun_verify'];
if (!in_array($targetDb, $allowlist, true)) {
    fwrite(STDERR, "FATAL: Database '{$targetDb}' is not in allowlist.\n");
    exit(1);
}

$conn = @new mysqli('127.0.0.1', 'root', '', $targetDb);
if ($conn->connect_error) {
    fwrite(STDERR, "FATAL: Connection error: " . $conn->connect_error . "\n");
    exit(1);
}
$conn->set_charset('utf8mb4');

$activeRes = $conn->query("SELECT DATABASE() AS db");
$activeDb = $activeRes->fetch_assoc()['db'] ?? '';
if ($activeDb !== $targetDb) {
    fwrite(STDERR, "FATAL: Active DB mismatch: '{$activeDb}' !== '{$targetDb}'.\n");
    exit(1);
}

echo "=== HISTORICAL ARCHIVE MODULAR SEEDER ===\n";
echo "Target DB: {$activeDb}\n";
echo "Mode:      " . ($isDryRun ? "DRY-RUN (Simulate only, rollback)" : "APPLY (Commit)") . "\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Execute seeder logic through the authoritative seeder
$seederCmd = 'php ' . escapeshellarg(dirname(__DIR__) . '/import_review/seed_historical_archive.php') .
             ' --' . ($isApply ? 'apply' : 'dry-run') .
             ' --db=' . escapeshellarg($targetDb);

passthru($seederCmd, $returnCode);
exit($returnCode);
