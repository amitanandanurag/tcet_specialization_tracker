<?php
/**
 * TCET ERP - Master Production & Regression Test Runner
 * Runs all active production verification suites against the authoritative database.
 */

echo "=======================================================" . PHP_EOL;
echo "TCET ERP MASTER REGRESSION TEST SUITE" . PHP_EOL;
echo "=======================================================" . PHP_EOL . PHP_EOL;

$targetDb = getenv('TCET_DB_NAME') ?: 'tcet_st';
putenv("TCET_DB_NAME=$targetDb");
putenv("TCET_TEST_DB=$targetDb");
echo "Authoritative Target Database: $targetDb" . PHP_EOL . PHP_EOL;

// 1. Authoritative Production Verification Suites (Safe for production runtime)
$productionSuites = [
    'PHP Syntax Linting Suite' => __DIR__ . '/lint_all.php',
    'Historical Candidate Database Integrity Suite' => __DIR__ . '/verify_db_integrity.php',
    'Session Security & Cookie Configuration Suite' => __DIR__ . '/test_session_security_configuration.php',
    'Clean Application HTTP Endpoint Suite' => __DIR__ . '/test_clean_app.php',
];

// 2. Legacy Sandbox Mutation Suites (Require isolated sandbox to prevent mutating production data)
$sandboxSuites = [
    'Enterprise Security Suite' => __DIR__ . '/test_enterprise_security_suite.php',
    'Production Suite' => __DIR__ . '/test_production_suite.php',
    'Academic Progression & Mentor History Suite' => __DIR__ . '/test_academic_progression.php',
    'Final Independent Verification Suite' => __DIR__ . '/test_final_independent_verification.php',
    'Specialization & Course Data Import Suite' => __DIR__ . '/test_specialization_course_import.php',
    'Specialization Mapping & Registration Suite' => __DIR__ . '/test_student_registration_specialization_mapping.php',
    'Honours with Research Eligibility Suite' => __DIR__ . '/test_honours_with_research_eligibility.php',
    'Progression Consistency Suite' => __DIR__ . '/test_progression_consistency.php',
    'Batch 1 Critical Fixes Suite' => __DIR__ . '/test_batch1_critical_fixes.php',
    'Batch 2 Remediation Suite' => __DIR__ . '/test_batch2_remediation.php',
    'Dashboard Fix & Data Accuracy Suite' => __DIR__ . '/test_dashboard_accuracy.php',
    'Semester-Wise Mentor Allocation Suite' => __DIR__ . '/test_semester_wise_mentor_allocation.php',
    'RBAC & Data Accuracy Suite' => __DIR__ . '/test_role_based_access_control.php',
    'Browser & HTTP Role Verification Suite' => __DIR__ . '/test_browser_role_sessions.php',
    'Master Implementation Updates Suite' => __DIR__ . '/test_master_implementation_suite.php',
];

$totalTests = 0;
$totalPassed = 0;
$totalFailed = 0;
$totalSkipped = 0;

echo "=== PHASE 1: PRODUCTION-SAFE VERIFICATION SUITES ===" . PHP_EOL;
foreach ($productionSuites as $name => $file) {
    echo ">>> Running $name..." . PHP_EOL;
    $output = [];
    $returnVar = 0;
    exec("set \"TCET_DB_NAME=$targetDb\" && set \"TCET_TEST_DB=$targetDb\" && \"C:\\xampp\\php\\php.exe\" \"$file\"", $output, $returnVar);
    
    $outText = implode("\n", $output);
    
    // Parse test counts
    if (preg_match('/(?:Passed\s*[:=]\s*|,\s*Passed\s*=\s*)(\d+)/i', $outText, $pMatches)) {
        $passed = (int)$pMatches[1];
    } elseif (preg_match('/(\d+)\s+Passed/i', $outText, $pMatches2)) {
        $passed = (int)$pMatches2[1];
    } elseif (preg_match('/All\s+(\d+)\s+PHP\s+files\s+passed/i', $outText, $pMatches3)) {
        $passed = (int)$pMatches3[1];
    } else {
        $passed = substr_count($outText, '[PASS]') + substr_count($outText, '-> MATCH') + substr_count($outText, ': YES');
    }
    
    if (preg_match('/(?:Failed\s*[:=]\s*|,\s*Failed\s*=\s*)(\d+)/i', $outText, $fMatches)) {
        $failed = (int)$fMatches[1];
    } elseif (preg_match('/(\d+)\s+Failed/i', $outText, $fMatches2)) {
        $failed = (int)$fMatches2[1];
    } elseif (preg_match('/Found\s+syntax\s+errors\s+in\s+(\d+)\s+files/i', $outText, $fMatches3)) {
        $failed = (int)$fMatches3[1];
    } else {
        $failed = substr_count($outText, '[FAIL]') + substr_count($outText, '-> MISMATCH');
    }
    
    $totalPassed += $passed;
    $totalFailed += $failed;
    $totalTests += ($passed + $failed);
    
    echo "    Result: $passed Passed, $failed Failed (Exit Code: $returnVar)" . PHP_EOL;
}

echo PHP_EOL . "=== PHASE 2: LEGACY SANDBOX MUTATION SUITES ===" . PHP_EOL;
if ($targetDb === 'tcet_st') {
    echo "[PROTECTION ENFORCED] Legacy sandbox suites perform data insertion, table altering, and user mutations." . PHP_EOL;
    echo "These suites are strictly isolated to temporary test sandboxes and are SKIPPED on '$targetDb' to protect genuine academic records." . PHP_EOL;
    foreach ($sandboxSuites as $name => $file) {
        echo ">>> $name: SAFELY SKIPPED (Sandbox mutation isolated)" . PHP_EOL;
        $totalSkipped++;
    }
} else {
    foreach ($sandboxSuites as $name => $file) {
        echo ">>> Running $name..." . PHP_EOL;
        $output = [];
        $returnVar = 0;
        exec("set \"TCET_DB_NAME=$targetDb\" && set \"TCET_TEST_DB=$targetDb\" && \"C:\\xampp\\php\\php.exe\" \"$file\"", $output, $returnVar);
        $outText = implode("\n", $output);
        $passed = substr_count($outText, '[PASS]');
        $failed = substr_count($outText, '[FAIL]');
        $totalPassed += $passed;
        $totalFailed += $failed;
        $totalTests += ($passed + $failed);
        echo "    Result: $passed Passed, $failed Failed (Exit Code: $returnVar)" . PHP_EOL;
    }
}

echo PHP_EOL . "=======================================================" . PHP_EOL;
echo "AGGREGATE TEST SUMMARY: $totalPassed / $totalTests Passed, $totalFailed Failed ($totalSkipped Sandbox Suites Skipped on Production)" . PHP_EOL;
echo "=======================================================" . PHP_EOL;
