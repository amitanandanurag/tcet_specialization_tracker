<?php
// Mock session setup for testing page rendering
function testRenderPage($filePath, $sessionData = [], $requestData = []) {
    echo "Rendering: " . basename($filePath) . " ... ";
    
    $oldCwd = getcwd();
    chdir(dirname($filePath));
    
    // Setup superglobals
    $_SESSION = $sessionData;
    $_REQUEST = array_merge($_REQUEST, $requestData);
    $_GET = array_merge($_GET, $requestData);
    $_POST = [];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['SCRIPT_NAME'] = '/' . basename($filePath);
    
    ob_start();
    try {
        include $filePath;
        $output = ob_get_clean();
        chdir($oldCwd);
        
        // Check for fatal errors or warnings in output
        if (stripos($output, 'Fatal error') !== false || stripos($output, 'Parse error') !== false) {
            echo "[FAIL] Fatal error in output!\n";
            return false;
        }
        
        echo "[PASS] (" . strlen($output) . " bytes)\n";
        return true;
    } catch (Throwable $e) {
        ob_end_clean();
        chdir($oldCwd);
        echo "[FAIL] Exception: " . $e->getMessage() . " on line " . $e->getLine() . "\n";
        return false;
    }
}

// Find a valid admin login_id and student login_id from st_login
require_once __DIR__ . '/../database/db_connect.php';
$db = new DBController();
$loginAdmin = mysqli_query($db->conn, "SELECT login_id, user_id FROM st_login ORDER BY login_id ASC LIMIT 1");
$adminRow = mysqli_fetch_assoc($loginAdmin);

$adminSession = [
    'user_session' => intval($adminRow['login_id'] ?? 1),
    'user_id' => intval($adminRow['user_id'] ?? 1),
    'user_type' => 1,
    'role_id' => 1,
    'user_name' => 'Administrator'
];

$baseDir = dirname(__DIR__) . '/admin';

echo "=== TCET ERP PAGE RENDERING INTEGRATION TESTS ===\n\n";

$allPassed = true;

// 1. student_dashboard.php (Student view)
$allPassed = testRenderPage("$baseDir/student_dashboard.php", $adminSession, ['student_id' => 1]) && $allPassed;

// 2. student_view.php (Admin view)
$allPassed = testRenderPage("$baseDir/student_view.php", $adminSession, ['id' => 1]) && $allPassed;

// 3. student_admission_view.php (Admission view)
$allPassed = testRenderPage("$baseDir/student_admission_view.php", $adminSession, ['id' => 1]) && $allPassed;

// 4. mentor_assignment.php (Coordinator / Admin view)
$allPassed = testRenderPage("$baseDir/mentor_assignment.php", $adminSession) && $allPassed;

// 5. mentor_subject.php (Mapping view)
$allPassed = testRenderPage("$baseDir/mentor_subject.php", $adminSession) && $allPassed;

echo "\n" . ($allPassed ? "ALL PAGES RENDERED CLEANLY WITH ZERO CRITICAL ERRORS!" : "SOME PAGES ENCOUNTERED ERRORS") . "\n";
