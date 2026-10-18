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
    'Session Security & Cookie Configuration Suite' => __DIR__ . '/test_session_security_configuration.php',
];

$totalTests = 0;
$totalPassed = 0;
$totalFailed = 0;

putenv('TCET_DB_NAME=tcet_st_test');
putenv('TCET_TEST_DB=tcet_st_test');

foreach ($suites as $name => $file) {
    echo ">>> Running $name..." . PHP_EOL;
    $output = [];
    $returnVar = 0;
    exec("set \"TCET_DB_NAME=tcet_st_test\" && set \"TCET_TEST_DB=tcet_st_test\" && \"C:\\xampp\\php\\php.exe\" \"$file\"", $output, $returnVar);
    
    $outText = implode("\n", $output);
    
    // Parse test counts
    if (preg_match('/(?:Passed\s*[:=]\s*|,\s*Passed\s*=\s*)(\d+)/i', $outText, $pMatches)) {
        $passed = (int)$pMatches[1];
    } elseif (preg_match('/(\d+)\s+Passed/i', $outText, $pMatches2)) {
        $passed = (int)$pMatches2[1];
    } else {
        $passed = substr_count($outText, '[PASS]');
    }
    
    if (preg_match('/(?:Failed\s*[:=]\s*|,\s*Failed\s*=\s*)(\d+)/i', $outText, $fMatches)) {
        $failed = (int)$fMatches[1];
    } elseif (preg_match('/(\d+)\s+Failed/i', $outText, $fMatches2)) {
        $failed = (int)$fMatches2[1];
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
