<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION['user_session'] = 1;
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 1;

require_once __DIR__ . '/../database/db_connect.php';

$db = new DBController();
if (!$db->conn) {
    die("Database connection failed: " . $db->last_error . "\n");
}

echo "=== TCET ERP COMPREHENSIVE PRODUCTION VERIFICATION SUITE ===\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($condition, $testName) {
    global $passCount, $failCount;
    if ($condition) {
        echo "[PASS] $testName\n";
        $passCount++;
    } else {
        echo "[FAIL] $testName\n";
        $failCount++;
    }
}

// =========================================================================
// TEST 1: CSRF TOKEN GENERATION & VALIDATION
// =========================================================================
echo "--- TEST 1: CSRF TOKEN INTEGRITY ---\n";
$token1 = DBController::generateCsrfToken();
assertTest(is_string($token1) && strlen($token1) === 64, "CSRF token is valid 64-character hex string");

$inputField = DBController::getCsrfInputField();
assertTest(strpos($inputField, 'name="csrf_token"') !== false && strpos($inputField, $token1) !== false, "CSRF input field embeds valid token");

assertTest(DBController::validateCsrfToken($token1) === true, "CSRF validation succeeds for matching token");
assertTest(DBController::validateCsrfToken("invalid_token_123") === false, "CSRF validation rejects forged/invalid token");
assertTest(DBController::validateCsrfToken("") === false, "CSRF validation rejects empty token");


// =========================================================================
// TEST 2: BCRYPT PASSWORD HASHING & LEGACY UPGRADE
// =========================================================================
echo "\n--- TEST 2: BCRYPT & PASSWORD VERIFICATION ---\n";
$plain = "SecureTest@2026";
$hashed = DBController::hashPassword($plain);

assertTest(strpos($hashed, '$2y$') === 0, "Bcrypt hash generated with \$2y\$ prefix");
assertTest(DBController::verifyPassword($plain, $hashed) === true, "Bcrypt verification succeeds for correct password");
assertTest(DBController::verifyPassword("WrongPass@123", $hashed) === false, "Bcrypt verification rejects incorrect password");

// Legacy Plaintext & MD5 Compatibility
assertTest(DBController::verifyPassword("Tcet@1234", "Tcet@1234") === true, "Legacy plaintext password verified successfully");
assertTest(DBController::verifyPassword("Tcet@1234", md5("Tcet@1234")) === true, "Legacy MD5 password verified successfully");


// =========================================================================
// TEST 3: CENTRALIZED RBAC ROUTE GUARD
// =========================================================================
echo "\n--- TEST 3: RBAC ROUTE AUTHORIZATION ENFORCEMENT ---\n";
require_once __DIR__ . '/../admin/header/header.php';

// Super Admin (Role 1)
assertTest(checkUserRouteAuthorization($db->conn, 1, 1, 'allocation_master.php') === true, "Super Admin (Role 1) authorized for allocation_master.php");
assertTest(checkUserRouteAuthorization($db->conn, 1, 1, 'admin_register.php') === true, "Super Admin (Role 1) authorized for admin_register.php");
assertTest(checkUserRouteAuthorization($db->conn, 1, 1, 'audit_log.php') === true, "Super Admin (Role 1) authorized for audit_log.php");

// Student (Role 5)
assertTest(checkUserRouteAuthorization($db->conn, 5, 5, 'student_dashboard.php') === true, "Student (Role 5) authorized for student_dashboard.php");
assertTest(checkUserRouteAuthorization($db->conn, 5, 5, 'student_admission_view.php') === true, "Student (Role 5) authorized for student_admission_view.php");
assertTest(checkUserRouteAuthorization($db->conn, 5, 5, 'admin_register.php') === false, "Student (Role 5) RESTRICTED from admin_register.php");
assertTest(checkUserRouteAuthorization($db->conn, 5, 5, 'allocation_master.php') === false, "Student (Role 5) RESTRICTED from allocation_master.php");
assertTest(checkUserRouteAuthorization($db->conn, 5, 5, 'audit_log.php') === false, "Student (Role 5) RESTRICTED from audit_log.php");
assertTest(checkUserRouteAuthorization($db->conn, 5, 5, 'coordinator_allocation.php') === false, "Student (Role 5) RESTRICTED from coordinator_allocation.php");

// Coordinator (Role 3)
assertTest(checkUserRouteAuthorization($db->conn, 3, 3, 'mentor_allocation.php') === true, "Coordinator (Role 3) authorized for mentor_allocation.php");
assertTest(checkUserRouteAuthorization($db->conn, 3, 3, 'admin_register.php') === false, "Coordinator (Role 3) RESTRICTED from admin_register.php");


// =========================================================================
// TEST 4: AUDIT TRAIL LOGGING
// =========================================================================
echo "\n--- TEST 4: AUDIT TRAIL RECORDING ---\n";
$testAuditId = $db->writeAuditLog(1, 'TEST_SUITE_EXECUTION', 'st_audit_log', null, 'Automated verification test audit record');
assertTest($testAuditId !== false && $testAuditId > 0, "Audit log record created with ID #$testAuditId");

$auditCheck = mysqli_query($db->conn, "SELECT action_type, description FROM st_audit_log WHERE audit_id = $testAuditId");
$auditRow = $auditCheck ? mysqli_fetch_assoc($auditCheck) : null;
assertTest($auditRow && $auditRow['action_type'] === 'TEST_SUITE_EXECUTION', "Audit log record verified in database");

// Cleanup test audit row
mysqli_query($db->conn, "DELETE FROM st_audit_log WHERE audit_id = $testAuditId");


// =========================================================================
// TEST 5: FULL CODEBASE PHP SYNTAX CHECK
// =========================================================================
echo "\n--- TEST 5: CODEBASE SYNTAX VALIDATION ---\n";
$phpFiles = array_merge(
    glob(__DIR__ . '/../admin/*.php'),
    glob(__DIR__ . '/../admin/header/*.php'),
    glob(__DIR__ . '/../login/*.php'),
    glob(__DIR__ . '/../database/*.php')
);

$syntaxErrors = 0;
foreach ($phpFiles as $file) {
    $output = [];
    $returnVar = 0;
    exec("C:\\xampp\\php\\php.exe -l " . escapeshellarg($file), $output, $returnVar);
    if ($returnVar !== 0) {
        echo "[SYNTAX ERROR] " . basename($file) . ": " . implode(" ", $output) . "\n";
        $syntaxErrors++;
    }
}
assertTest($syntaxErrors === 0, "All " . count($phpFiles) . " PHP files passed syntax validation (0 errors)");


echo "\n=======================================================\n";
echo "VERIFICATION SUMMARY: Passed = $passCount, Failed = $failCount\n";
echo "=======================================================\n";
