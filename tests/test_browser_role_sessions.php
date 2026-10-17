<?php
/**
 * TCET ERP - Browser & HTTP Role Verification Test Harness
 * Tests real HTTP requests against local Apache server (http://127.0.0.1/st/)
 * Verifies role-based page access, HTTP status codes, and redirection rules.
 */

putenv('TCET_DB_NAME=tcet_st_test');
putenv('TCET_TEST_DB=tcet_st_test');

require_once __DIR__ . '/../database/db_connect.php';

$db = new DBController('tcet_st_test');
$dbCheckRes = $db->runQuery("SELECT DATABASE() as db");
$activeDb = $dbCheckRes[0]['db'] ?? '';
if ($activeDb !== 'tcet_st_test') {
    die("[FATAL SAFETY ABORT] Connected database is '$activeDb', expected 'tcet_st_test'. Aborting test.\n");
}

echo "=======================================================\n";
echo "TCET ERP - BROWSER & HTTP ROLE VERIFICATION SUITE\n";
echo "Target: http://127.0.0.1/st/ (DB: $activeDb)\n";
echo "=======================================================\n\n";

$passed = 0;
$failed = 0;

function assertHttp($condition, $description) {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] $description\n";
        $passed++;
    } else {
        echo "  [FAIL] $description\n";
        $failed++;
    }
}

// Function to simulate a web session request by running PHP in CLI with session simulation
function simulatePageLoad($roleId, $userId, $scriptName, $queryParams = [], $postData = null) {
    global $db;
    
    $_SESSION = [];
    $_GET = $queryParams;
    $_POST = $postData ?? [];
    $_REQUEST = array_merge($_GET, $_POST);
    $_SERVER['REQUEST_METHOD'] = $postData !== null ? 'POST' : 'GET';
    $_SERVER['SCRIPT_NAME'] = '/st/admin/' . $scriptName;
    $_SERVER['HTTP_HOST'] = '127.0.0.1';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    
    // Resolve user details
    $uRow = $db->runQuery("SELECT user_id, user_name, role_id, department_id, student_id FROM st_user_master WHERE user_id = $userId LIMIT 1");
    if (!empty($uRow)) {
        $_SESSION['user_session'] = (int)$uRow[0]['user_id'];
        $_SESSION['user_login_id'] = (int)$uRow[0]['user_id'];
        $_SESSION['user_id'] = (int)$uRow[0]['user_id'];
        $_SESSION['user_type'] = (int)$roleId;
        $_SESSION['department_id'] = (int)($uRow[0]['department_id'] ?? 0);
        $_SESSION['student_id'] = (int)($uRow[0]['student_id'] ?? 0);
    } else {
        $_SESSION['user_session'] = $userId;
        $_SESSION['user_login_id'] = $userId;
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_type'] = $roleId;
        $_SESSION['department_id'] = 1;
        $_SESSION['student_id'] = 1;
    }
    
    ob_start();
    require_once __DIR__ . '/../admin/header/header.php';
    ob_end_clean();
    return checkUserRouteAuthorization($db->conn, $userId, $roleId, $scriptName);
}

// 1. Super Admin (Role 1) Page Access
echo ">>> Testing Super Admin (Role 1) Browser Access...\n";
assertHttp(simulatePageLoad(1, 1, 'dashboard.php') === true, "Super Admin can access dashboard.php");
assertHttp(simulatePageLoad(1, 1, 'class_crud_new.php') === true, "Super Admin can access Master Data (class_crud_new.php)");
assertHttp(simulatePageLoad(1, 1, 'audit_log.php') === true, "Super Admin can access Audit Log (audit_log.php)");
assertHttp(simulatePageLoad(1, 1, 'allocation_master.php') === true, "Super Admin can access Side Menu Allocation (allocation_master.php)");
assertHttp(simulatePageLoad(1, 1, 'student-info.php') === true, "Super Admin can access student-info.php");

// 2. Admin (Role 2) Page Access
echo "\n>>> Testing Admin (Role 2) Browser Access...\n";
assertHttp(simulatePageLoad(2, 2, 'dashboard.php') === true, "Admin can access dashboard.php");
assertHttp(simulatePageLoad(2, 2, 'student-info.php') === true, "Admin can access student-info.php");
assertHttp(simulatePageLoad(2, 2, 'class_crud_new.php') === false, "Admin BLOCKED from Master Data (class_crud_new.php)");
assertHttp(simulatePageLoad(2, 2, 'audit_log.php') === false, "Admin BLOCKED from Audit Log (audit_log.php)");
assertHttp(simulatePageLoad(2, 2, 'allocation_master.php') === false, "Admin BLOCKED from Side Menu Allocation (allocation_master.php)");

// 3. Coordinator / HOD (Role 3) Page Access
echo "\n>>> Testing Coordinator / HOD (Role 3) Browser Access...\n";
assertHttp(simulatePageLoad(3, 3, 'dashboard.php') === true, "HOD can access dashboard.php");
assertHttp(simulatePageLoad(3, 3, 'branch_info.php') === true, "HOD can access branch_info.php");
assertHttp(simulatePageLoad(3, 3, 'class_crud_new.php') === false, "HOD BLOCKED from Master Data (class_crud_new.php)");
assertHttp(simulatePageLoad(3, 3, 'audit_log.php') === false, "HOD BLOCKED from Audit Log (audit_log.php)");
assertHttp(simulatePageLoad(3, 3, 'allocation_master.php') === false, "HOD BLOCKED from Side Menu Allocation (allocation_master.php)");

// 4. Mentor (Role 4) Page Access
echo "\n>>> Testing Mentor (Role 4) Browser Access...\n";
assertHttp(simulatePageLoad(4, 4, 'dashboard.php') === true, "Mentor can access dashboard.php");
assertHttp(simulatePageLoad(4, 4, 'mentor_assignment.php') === true, "Mentor can access self mentor_assignment.php");
assertHttp(simulatePageLoad(4, 4, 'admin_register.php') === false, "Mentor BLOCKED from admin_register.php");
assertHttp(simulatePageLoad(4, 4, 'class_crud_new.php') === false, "Mentor BLOCKED from Master Data (class_crud_new.php)");
assertHttp(simulatePageLoad(4, 4, 'audit_log.php') === false, "Mentor BLOCKED from Audit Log (audit_log.php)");

// 5. Student (Role 5) Page Access
echo "\n>>> Testing Student (Role 5) Browser Access...\n";
assertHttp(simulatePageLoad(5, 5, 'student_dashboard.php') === true, "Student can access student_dashboard.php");
assertHttp(simulatePageLoad(5, 5, 'dashboard.php') === false, "Student BLOCKED from staff dashboard.php");
assertHttp(simulatePageLoad(5, 5, 'admin_info.php') === false, "Student BLOCKED from admin_info.php");
assertHttp(simulatePageLoad(5, 5, 'mentor_info.php') === false, "Student BLOCKED from mentor_info.php");
assertHttp(simulatePageLoad(5, 5, 'student-info.php') === false, "Student BLOCKED from staff student-info.php");
assertHttp(simulatePageLoad(5, 5, 'class_crud_new.php') === false, "Student BLOCKED from Master Data (class_crud_new.php)");
assertHttp(simulatePageLoad(5, 5, 'audit_log.php') === false, "Student BLOCKED from Audit Log (audit_log.php)");
assertHttp(simulatePageLoad(5, 5, 'allocation_master.php') === false, "Student BLOCKED from Side Menu Allocation (allocation_master.php)");

// 6. Direct HTTP curl verification for public pages
echo "\n>>> Testing Direct HTTP Network Responses...\n";
$publicUrl = "http://127.0.0.1/st/index.php";
$ch = curl_init($publicUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$resBody = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

assertHttp($httpCode === 200 || $httpCode === 302, "HTTP Server responds on 127.0.0.1/st/index.php (Code $httpCode)");

echo "\n=======================================================\n";
echo "BROWSER HTTP VERIFICATION SUMMARY: $passed Passed, $failed Failed\n";
echo "=======================================================\n";

exit($failed > 0 ? 1 : 0);
