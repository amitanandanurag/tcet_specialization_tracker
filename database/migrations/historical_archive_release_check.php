<?php
/**
 * TCET_ST — Release Readiness Checklist Runner (Phase 3.2 Modular Tool)
 *
 * Runs comprehensive automated pre-deployment and release criteria checks.
 *
 * Usage:
 *   php historical_archive_release_check.php [--db=tcet_st_test]
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    die("CLI only.\n");
}

$options = getopt('', ['db::', 'help']);
$targetDb = trim((string)($options['db'] ?? 'tcet_st_test'));

echo "=== TCET_ST HISTORICAL ARCHIVE RELEASE CHECKLIST RUNNER ===\n";
echo "Database: {$targetDb}\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Run Preflight
echo "--- Running Preflight Checks ---\n";
passthru("php " . escapeshellarg(__DIR__ . "/historical_archive_preflight.php") . " --db=" . escapeshellarg($targetDb), $code1);

// 2. Run Verifier
echo "\n--- Running Ingestion Verification Assertions ---\n";
passthru("php " . escapeshellarg(__DIR__ . "/historical_archive_verify.php") . " --db=" . escapeshellarg($targetDb), $code2);

if ($code1 === 0 && $code2 === 0) {
    echo "\n>>> ALL RELEASE CHECKLIST ASSERTIONS SATISFIED <<<\n";
    exit(0);
} else {
    echo "\n>>> RELEASE CHECKLIST FAILED <<<\n";
    exit(1);
}
