<?php
/**
 * TCET ERP - Enterprise Security & Hardening Verification Suite
 * Tests AJAX Authentication, Role Guards, CSRF Enforcement, File Upload Restrictions,
 * Session Security, and Database Error Handling via isolated test executions.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../database/db_connect.php';

$db_handle = new DBController();
$passed = 0;
$failed = 0;

function assertTest($condition, $description) {
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] " . $description . "\n";
        $passed++;
    } else {
        echo "[FAIL] " . $description . "\n";
        $failed++;
    }
}

function runPhpSubprocess($scriptCode) {
    $tempFile = __DIR__ . '/_temp_sub_test_' . uniqid() . '.php';
    file_put_contents($tempFile, "<?php\n" . $scriptCode);
    $cmd = 'C:\\xampp\\php\\php.exe ' . escapeshellarg($tempFile);
    $output = shell_exec($cmd);
    @unlink($tempFile);
    return $output;
}

echo "=== TCET ERP ENTERPRISE SECURITY & HARDENING VERIFICATION SUITE ===\n\n";

// -------------------------------------------------------------
// MODULE 1: AJAX AUTHENTICATION & ROLE ISOLATION
// -------------------------------------------------------------
echo "--- MODULE 1: AJAX AUTHENTICATION & ROLE ISOLATION ---\n";

// Test 1.1: Unauthenticated request to role_info_ajax_base.php
$code = '
$_SESSION = [];
$_POST = [];
$_GET = [];
$_REQUEST = [];
$roleId = 2;
ob_start();
chdir(__DIR__ . "/../admin");
include "role_info_ajax_base.php";
$out = ob_get_clean();
echo $out;
';
$out = runPhpSubprocess($code);
$json = json_decode($out, true);
assertTest(isset($json['error']) && strpos($json['error'], 'Unauthorized') !== false, 'Unauthenticated access to role_info_ajax_base is rejected with 403 error');

// Test 1.2: Student role (5) trying to access Admin info AJAX (roleId = 2)
$code = '
session_start();
$_SESSION = ["user_id" => 10, "role_id" => 5];
$_POST = [];
$_GET = [];
$_REQUEST = [];
$roleId = 2;
ob_start();
chdir(__DIR__ . "/../admin");
include "role_info_ajax_base.php";
$out = ob_get_clean();
echo $out;
';
$out = runPhpSubprocess($code);
$json = json_decode($out, true);
assertTest(isset($json['error']) && strpos($json['error'], 'Forbidden') !== false, 'Student (Role 5) is forbidden from querying Admin Info AJAX');

// Test 1.3: Coordinator role (3) allowed for Mentor Info (roleId = 4)
$code = '
session_start();
$_SESSION = ["user_id" => 3, "role_id" => 3];
$_POST = [];
$_GET = [];
$_REQUEST = [];
$roleId = 4;
ob_start();
chdir(__DIR__ . "/../admin");
include "role_info_ajax_base.php";
$out = ob_get_clean();
echo $out;
';
$out = runPhpSubprocess($code);
$json = json_decode($out, true);
assertTest(isset($json['recordsTotal']), 'Coordinator (Role 3) is authorized to query Mentor Info AJAX');

// Test 1.4: Unauthenticated access to all_class_ajax.php
$code = '
$_SESSION = [];
$_POST = [];
$_GET = [];
$_REQUEST = [];
ob_start();
chdir(__DIR__ . "/../admin");
include "all_class_ajax.php";
$out = ob_get_clean();
echo $out;
';
$out = runPhpSubprocess($code);
$json = json_decode($out, true);
assertTest(isset($json['error']) && strpos($json['error'], 'Unauthorized') !== false, 'Unauthenticated access to all_class_ajax.php is rejected');

// Test 1.5: Unauthenticated access to student_info_ajax.php
$code = '
$_SESSION = [];
$_POST = [];
$_GET = [];
$_REQUEST = [];
ob_start();
chdir(__DIR__ . "/../admin");
include "student_info_ajax.php";
$out = ob_get_clean();
echo $out;
';
$out = runPhpSubprocess($code);
$json = json_decode($out, true);
assertTest(isset($json['error']) && strpos($json['error'], 'Unauthorized') !== false, 'Unauthenticated access to student_info_ajax.php is rejected');

// Test 1.6: Student role (5) restricted from student_info_ajax.php
$code = '
session_start();
$_SESSION = ["user_id" => 10, "role_id" => 5];
$_POST = [];
$_GET = [];
$_REQUEST = [];
ob_start();
chdir(__DIR__ . "/../admin");
include "student_info_ajax.php";
$out = ob_get_clean();
echo $out;
';
$out = runPhpSubprocess($code);
$json = json_decode($out, true);
assertTest(isset($json['error']) && strpos($json['error'], 'Forbidden') !== false, 'Student (Role 5) is forbidden from querying full student directory AJAX');

// Test 1.7: Unauthenticated access to student_concise_details_ajax.php
$code = '
$_SESSION = [];
$_POST = [];
$_GET = [];
$_REQUEST = [];
ob_start();
chdir(__DIR__ . "/../admin");
include "student_concise_details_ajax.php";
$out = ob_get_clean();
echo $out;
';
$out = runPhpSubprocess($code);
$json = json_decode($out, true);
assertTest(isset($json['error']) && strpos($json['error'], 'Unauthorized') !== false, 'Unauthenticated access to student_concise_details_ajax.php is rejected');

// Test 1.8: Unauthenticated access to admin_dashboard_ajax.php
$code = '
$_SESSION = [];
$_POST = [];
$_GET = ["action" => "get_rejected_students"];
$_REQUEST = $_GET;
ob_start();
chdir(__DIR__ . "/../admin");
include "admin_dashboard_ajax.php";
$out = ob_get_clean();
echo $out;
';
$out = runPhpSubprocess($code);
$json = json_decode($out, true);
assertTest(isset($json['error']) && strpos($json['error'], 'Unauthorized') !== false, 'Unauthenticated access to admin_dashboard_ajax.php is rejected');

// -------------------------------------------------------------
// MODULE 2: ANTI-CSRF MUTATION ENFORCEMENT
// -------------------------------------------------------------
echo "\n--- MODULE 2: ANTI-CSRF MUTATION ENFORCEMENT ---\n";

DBController::generateCsrfToken();
$validToken = $_SESSION['csrf_token'];
$fakeToken = 'invalid_forged_csrf_token_value_xyz';

// Test 2.1: validateCsrfToken validates correctly
assertTest(DBController::validateCsrfToken($validToken) === true, 'validateCsrfToken accepts valid CSRF token');
assertTest(DBController::validateCsrfToken($fakeToken) === false, 'validateCsrfToken rejects forged CSRF token');
assertTest(DBController::validateCsrfToken('') === false, 'validateCsrfToken rejects empty CSRF token');

// Test 2.2: student_delete.php rejects forged CSRF
$code = '
session_start();
$_SESSION = ["user_id" => 1, "role_id" => 1, "csrf_token" => "' . $validToken . '"];
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST = ["id" => 99999, "csrf_token" => "' . $fakeToken . '"];
$_REQUEST = $_POST;
ob_start();
chdir(__DIR__ . "/../admin");
include "student_delete.php";
$out = ob_get_clean();
echo $out;
';
$out = runPhpSubprocess($code);
$json = json_decode($out, true);
assertTest(isset($json['status']) && $json['status'] === 'error' && strpos($json['message'], 'CSRF') !== false, 'student_delete.php rejects mutation with forged CSRF token');

// Test 2.3: student_bulk_delete.php rejects empty CSRF
$code = '
session_start();
$_SESSION = ["user_id" => 1, "role_id" => 1, "csrf_token" => "' . $validToken . '"];
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST = ["ids" => [99999]];
$_REQUEST = $_POST;
ob_start();
chdir(__DIR__ . "/../admin");
include "student_bulk_delete.php";
$out = ob_get_clean();
echo $out;
';
$out = runPhpSubprocess($code);
$json = json_decode($out, true);
assertTest(isset($json['status']) && $json['status'] === 'error' && strpos($json['message'], 'CSRF') !== false, 'student_bulk_delete.php rejects bulk deletion without CSRF token');

// Test 2.4: user_delete.php rejects forged CSRF
$code = '
session_start();
$_SESSION = ["user_id" => 1, "role_id" => 1, "csrf_token" => "' . $validToken . '"];
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST = ["user_id" => 99999, "csrf_token" => "' . $fakeToken . '"];
$_REQUEST = $_POST;
ob_start();
chdir(__DIR__ . "/../admin");
include "user_delete.php";
$out = ob_get_clean();
echo $out;
';
$out = runPhpSubprocess($code);
$json = json_decode($out, true);
assertTest(isset($json['success']) && $json['success'] === false, 'user_delete.php rejects user deletion with forged CSRF token');

// Test 2.5: role_delete_base.php rejects forged CSRF
$code = '
session_start();
$_SESSION = ["user_id" => 1, "role_id" => 1, "csrf_token" => "' . $validToken . '"];
$_SERVER["REQUEST_METHOD"] = "POST";
$_POST = ["user_id" => 99999, "csrf_token" => "' . $fakeToken . '"];
$_REQUEST = $_POST;
$roleId = 2;
ob_start();
chdir(__DIR__ . "/../admin");
include "role_delete_base.php";
$out = ob_get_clean();
echo $out;
';
$out = runPhpSubprocess($code);
$json = json_decode($out, true);
assertTest(isset($json['success']) && $json['success'] === false, 'role_delete_base.php rejects role deletion with forged CSRF token');

// -------------------------------------------------------------
// MODULE 3: FILE UPLOAD SECURITY & PERMISSIONS
// -------------------------------------------------------------
echo "\n--- MODULE 3: FILE UPLOAD RESTRICTIONS & PERMISSIONS ---\n";

// Test 3.1: Check uploads .htaccess exists and contains protection
$htaccessPath = __DIR__ . '/../admin/uploads/.htaccess';
assertTest(file_exists($htaccessPath), '.htaccess exists in admin/uploads/ directory');
$htaccessContent = file_get_contents($htaccessPath);
assertTest(strpos($htaccessContent, 'php|phtml') !== false && strpos($htaccessContent, 'Deny from all') !== false, '.htaccess denies direct execution of script files (.php, .phtml, .exe)');

// Test 3.2: Verify allowed extensions constant in upload endpoints
$studentProcessCode = file_get_contents(__DIR__ . '/../admin/student_process.php');
assertTest(strpos($studentProcessCode, "allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']") !== false, 'student_process.php enforces strict allowlist: pdf, jpg, jpeg, png, doc, docx');
assertTest(strpos($studentProcessCode, "max_file_size = 5 * 1024 * 1024") !== false, 'student_process.php enforces 5MB maximum file size limit');

$editProcessCode = file_get_contents(__DIR__ . '/../admin/edit_process.php');
assertTest(strpos($editProcessCode, "allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']") !== false, 'edit_process.php enforces strict file extension allowlist');
assertTest(strpos($editProcessCode, "max_file_size = 5 * 1024 * 1024") !== false, 'edit_process.php enforces 5MB maximum file size limit');

// -------------------------------------------------------------
// MODULE 4: SESSION SECURITY & ERROR HANDLING
// -------------------------------------------------------------
echo "\n--- MODULE 4: SESSION SECURITY & ERROR HANDLING ---\n";

// Test 4.1: Verify startSecureSession method exists
assertTest(method_exists('DBController', 'startSecureSession'), 'DBController::startSecureSession is defined');

// Test 4.2: Verify login.php contains session_regenerate_id
$loginCode = file_get_contents(__DIR__ . '/../login/login.php');
assertTest(strpos($loginCode, 'session_regenerate_id(true)') !== false, 'login.php regenerates session ID upon successful authentication');

// Test 4.3: Verify logout.php thoroughly clears session cookie
$logoutCode = file_get_contents(__DIR__ . '/../login/logout.php');
assertTest(strpos($logoutCode, 'setcookie(session_name()') !== false && strpos($logoutCode, 'session_destroy()') !== false, 'logout.php invalidates session cookie on client and destroys session');

// Test 4.4: Verify runQuery does not leak fatal die errors
$badQueryRes = $db_handle->runQuery("SELECT non_existent_column_for_test FROM non_existent_table_xyz");
assertTest($badQueryRes === false, 'DBController::runQuery returns false without calling die() on SQL failure');
assertTest(!empty($db_handle->last_error), 'DBController captures last_error for secure logging');

// -------------------------------------------------------------
// SUMMARY
// -------------------------------------------------------------
echo "\n=======================================================\n";
echo "ENTERPRISE SECURITY TEST SUMMARY: Passed = $passed, Failed = $failed\n";
echo "=======================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
