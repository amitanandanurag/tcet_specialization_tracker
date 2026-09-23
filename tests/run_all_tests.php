<?php
/**
 * TCET ERP - Master Test Runner
 * Runs all regression test suites and aggregates results.
 */

echo "=======================================================" . PHP_EOL;
echo "TCET ERP MASTER REGRESSION TEST SUITE" . PHP_EOL;
echo "=======================================================" . PHP_EOL . PHP_EOL;

$suites = [
    'Enterprise Security Suite' => __DIR__ . '/test_enterprise_security_suite.php',
    'Production Suite' => __DIR__ . '/test_production_suite.php',
    'Academic Progression & Mentor History Suite' => __DIR__ . '/test_academic_progression.php',
    'Final Independent Verification Suite' => __DIR__ . '/test_final_independent_verification.php',
];

$totalTests = 0;
$totalPassed = 0;
$totalFailed = 0;

foreach ($suites as $name => $file) {
    echo ">>> Running $name..." . PHP_EOL;
    $output = [];
    $returnVar = 0;
    exec("\"C:\\xampp\\php\\php.exe\" \"$file\"", $output, $returnVar);
    
    $outText = implode("\n", $output);
    
    // Parse test counts
    if (preg_match('/(?:Passed =|Passed:)\s*(\d+)/i', $outText, $pMatches)) {
        $passed = (int)$pMatches[1];
    } else {
        $passed = substr_count($outText, '[PASS]');
    }
    
    if (preg_match('/(?:Failed =|Failed:)\s*(\d+)/i', $outText, $fMatches)) {
        $failed = (int)$fMatches[1];
    } else {
        $failed = substr_count($outText, '[FAIL]');
    }
    
    $totalPassed += $passed;
    $totalFailed += $failed;
    $totalTests += ($passed + $failed);
    
    echo "    Result: $passed Passed, $failed Failed (Exit Code: $returnVar)" . PHP_EOL;
}

echo PHP_EOL . "=======================================================" . PHP_EOL;
echo "AGGREGATE TEST SUMMARY: $totalPassed / $totalTests Passed, $totalFailed Failed" . PHP_EOL;
echo "=======================================================" . PHP_EOL;
