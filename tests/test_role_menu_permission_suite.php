<?php
/**
 * Automated Verification Suite for Role-Based Menu Allocation & Historical Retirement
 */

require_once __DIR__ . '/../database/db_connect.php';

$db_handle = new DBController();
$conn = $db_handle->connectDB();

$passed = 0;
$failed = 0;
$total = 0;

function assertTest($condition, $message) {
    global $passed, $failed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo "  [PASS] $message\n";
    } else {
        $failed++;
        echo "  [FAIL] $message\n";
    }
}

echo "======================================================================\n";
echo " TCET_ST ROLE-BASED MENU ALLOCATION & PERMISSION VERIFICATION SUITE   \n";
echo "======================================================================\n\n";

// ---------------------------------------------------------------------
// TEST SUITE 1: HISTORICAL MODULE RETIREMENT IN DATABASE
// ---------------------------------------------------------------------
echo "--- 1. HISTORICAL MODULE RETIREMENT IN DATABASE ---\n";
$histRoutes = array('historical_students.php', 'superadmin_approval_monitoring.php', 'hod_historical_approvals.php');
$histRoutesStr = "'" . implode("','", $histRoutes) . "'";

$res = mysqli_query($conn, "SELECT COUNT(*) FROM st_sub_menu_master WHERE sub_menu_route IN ($histRoutesStr) AND is_active = 1");
$activeHistCount = (int)mysqli_fetch_row($res)[0];
assertTest($activeHistCount === 0, "No retired historical submenus are marked active in st_sub_menu_master (Found: $activeHistCount)");

$allocHistRes = mysqli_query($conn, "SELECT COUNT(*) FROM st_menu_allocation_master a JOIN st_sub_menu_master sm ON a.sub_menu_id = sm.sub_menu_id WHERE sm.sub_menu_route IN ($histRoutesStr)");
$allocHistCount = (int)mysqli_fetch_row($allocHistRes)[0];
assertTest($allocHistCount === 0, "Zero retired historical routes allocated in st_menu_allocation_master (Found: $allocHistCount)");

// ---------------------------------------------------------------------
// TEST SUITE 2: ALLOCATION UI RENDERING (TAB 1)
// ---------------------------------------------------------------------
echo "\n--- 2. ALLOCATION UI RENDERING (menu_submenu_allocation.php) ---\n";
// Simulate Super Admin session
$_SESSION['user_id'] = 1;
$_SESSION['role_id'] = 1;
$_SESSION['role'] = 1;
$_SESSION['user_type'] = 1;
$_SESSION['username'] = 'superadmin';
$_SESSION['name'] = 'Super Admin';
$_SESSION['logged_in'] = true;
$usertype = 1;
$userid = 1;
$username = 'superadmin';
$name = 'Super Admin';

ob_start();
$_GET['tab'] = 'role_allocations';
$_GET['profile'] = '1';
include __DIR__ . '/../admin/menu_submenu_allocation.php';
$htmlSuperAdmin = ob_get_clean();

assertTest(strpos($htmlSuperAdmin, 'Historical Student Archive') === false, "Historical Student Archive is NOT present in Tab 1 allocation checkboxes");
assertTest(strpos($htmlSuperAdmin, 'Historical Approval Monitoring') === false, "Historical Approval Monitoring is NOT present in Tab 1 allocation checkboxes");
assertTest(strpos($htmlSuperAdmin, 'Historical Student Approvals') === false, "Historical Student Approvals is NOT present in Tab 1 allocation checkboxes");
assertTest(strpos($htmlSuperAdmin, 'Bulk Student Upload') !== false, "Bulk Student Upload is present in Students allocation checkboxes");
assertTest(strpos($htmlSuperAdmin, '5 / 6 active') !== false || strpos($htmlSuperAdmin, '6 / 6 active') !== false, "Students child count badge reflects non-retired count (X / 6 active)");
assertTest(strpos($htmlSuperAdmin, 'Super Admin Lockout Safeguard') !== false, "Super Admin Lockout Safeguard alert is displayed");
assertTest(strpos($htmlSuperAdmin, 'name="menu_ids[]" value="5"') !== false && strpos($htmlSuperAdmin, 'Protected</span>') !== false, "Settings menu is protected and locked for Super Admin");

// ---------------------------------------------------------------------
// TEST SUITE 3: SUBMENU MASTER TAB (TAB 3)
// ---------------------------------------------------------------------
echo "\n--- 3. SUBMENU MASTER TAB (TAB 3) ---\n";
ob_start();
$_GET['tab'] = 'submenu_master';
include __DIR__ . '/../admin/menu_submenu_allocation.php';
$htmlTab3 = ob_get_clean();

assertTest(strpos($htmlTab3, 'Retired') !== false, "Retired badge is displayed for historical routes in Tab 3");
assertTest(strpos($htmlTab3, 'Decommissioned') !== false, "Decommissioned action label is displayed in place of Enable button");

// ---------------------------------------------------------------------
// TEST SUITE 4: TRANSACTIONAL SAVING & RE-IMPORT PROTECTION
// ---------------------------------------------------------------------
echo "\n--- 4. TRANSACTIONAL ALLOCATION SAVING & RE-IMPORT PROTECTION ---\n";
// Test saving for Role 2 (Admin)
$csrfToken = DBController::getCsrfToken();
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['csrf_token'] = $csrfToken;
$_POST['action'] = 'save_role_allocations';
$_POST['profile'] = '2';
// Provide menus 1, 2 and submenus 1, 2, 7, 8, PLUS malicious injection of retired submenus 60, 61
$_POST['menu_ids'] = array(1, 2);
$_POST['sub_menu_ids'] = array(1, 2, 7, 8, 60, 61);

ob_start();
include __DIR__ . '/../admin/menu_submenu_allocation.php';
$postOutput = ob_get_clean();

// Check what was saved in the database for Role 2
$r2AllocQuery = mysqli_query($conn, "SELECT sub_menu_id FROM st_menu_allocation_master WHERE role_id = 2 AND user_id = 0 AND sub_menu_id IS NOT NULL");
$savedR2Subs = [];
while ($row = mysqli_fetch_assoc($r2AllocQuery)) {
    $savedR2Subs[] = (int)$row['sub_menu_id'];
}

assertTest(in_array(1, $savedR2Subs, true), "Submenu 1 (Enroll) saved for Role 2");
assertTest(in_array(2, $savedR2Subs, true), "Submenu 2 (List of Students) saved for Role 2");
assertTest(in_array(7, $savedR2Subs, true), "Submenu 7 (Register Admin) saved for Role 2");
assertTest(in_array(8, $savedR2Subs, true), "Submenu 8 (Admin Info) saved for Role 2");
assertTest(!in_array(60, $savedR2Subs, true), "Submenu 60 (Retired Historical) rejected and NOT saved");
assertTest(!in_array(61, $savedR2Subs, true), "Submenu 61 (Retired Historical) rejected and NOT saved");

// Test Super Admin Lockout Safeguard on POST: attempt to save Role 1 with empty submenus and empty menus
$_POST['profile'] = '1';
$_POST['menu_ids'] = array();
$_POST['sub_menu_ids'] = array();

ob_start();
include __DIR__ . '/../admin/menu_submenu_allocation.php';
$postOutputRole1 = ob_get_clean();

$r1AllocQuery = mysqli_query($conn, "SELECT sub_menu_id FROM st_menu_allocation_master WHERE role_id = 1 AND user_id = 0");
$savedR1Subs = [];
while ($row = mysqli_fetch_assoc($r1AllocQuery)) {
    if ($row['sub_menu_id'] !== null) {
        $savedR1Subs[] = (int)$row['sub_menu_id'];
    }
}

// Submenus 25 (Allocation), 13 (Masters), 20 (Profile), 21 (Change Password), 49 (Audit Log)
assertTest(in_array(25, $savedR1Subs, true), "Super Admin safeguard: Submenu 25 (Menu Allocation) preserved");
assertTest(in_array(13, $savedR1Subs, true), "Super Admin safeguard: Submenu 13 (Masters) preserved");
assertTest(in_array(20, $savedR1Subs, true), "Super Admin safeguard: Submenu 20 (Profile) preserved");
assertTest(in_array(21, $savedR1Subs, true), "Super Admin safeguard: Submenu 21 (Password) preserved");
assertTest(in_array(49, $savedR1Subs, true), "Super Admin safeguard: Submenu 49 (Audit Log) preserved");

// Restore standard allocations for Role 1 & Role 2 using reset_to_defaults
$_POST['action'] = 'reset_to_defaults';
ob_start();
include __DIR__ . '/../admin/menu_submenu_allocation.php';
$resetOutput = ob_get_clean();

// ---------------------------------------------------------------------
// TEST SUITE 5: ACTUAL SIDEBAR RENDERING (side_menu.php)
// ---------------------------------------------------------------------
echo "\n--- 5. ACTUAL SIDEBAR RENDERING (side_menu.php) ---\n";
// Test sidebar for Super Admin (Role 1)
$usertype = 1;
$userid = 1;
ob_start();
include __DIR__ . '/../admin/header/side_menu.php';
$sidebarR1 = ob_get_clean();

assertTest(strpos($sidebarR1, 'student-info.php') !== false, "Super Admin sidebar includes List of Students");
assertTest(strpos($sidebarR1, 'student_bulk_import.php') !== false, "Super Admin sidebar includes Bulk Student Upload");
assertTest(strpos($sidebarR1, 'historical_students.php') === false, "Super Admin sidebar excludes Historical Student Archive");
assertTest(strpos($sidebarR1, 'superadmin_approval_monitoring.php') === false, "Super Admin sidebar excludes Historical Approval Monitoring");
assertTest(strpos($sidebarR1, 'hod_historical_approvals.php') === false, "Super Admin sidebar excludes Historical Student Approvals");

// Test sidebar for Mentor (Role 4)
$usertype = 4;
$userid = 100;
ob_start();
include __DIR__ . '/../admin/header/side_menu.php';
$sidebarR4 = ob_get_clean();

assertTest(strpos($sidebarR4, 'offline_marks_entry.php') !== false, "Mentor sidebar includes Offline Marks Entry");
assertTest(strpos($sidebarR4, 'mentor_assignment.php') !== false, "Mentor sidebar includes Mentor Assignments");
assertTest(strpos($sidebarR4, 'admin_register.php') === false, "Mentor sidebar excludes Admin Registration");
assertTest(strpos($sidebarR4, 'menu_submenu_allocation.php') === false, "Mentor sidebar excludes Menu Allocation");

// Test sidebar for Student (Role 5)
$usertype = 5;
$userid = 200;
ob_start();
include __DIR__ . '/../admin/header/side_menu.php';
$sidebarR5 = ob_get_clean();

assertTest(strpos($sidebarR5, 'student_dashboard.php') !== false, "Student sidebar targets student_dashboard.php");
assertTest(strpos($sidebarR5, 'nptel_certificate.php') !== false, "Student sidebar includes NPTEL Certificate");
assertTest(strpos($sidebarR5, 'student-info.php') === false, "Student sidebar excludes Student Directory");
assertTest(strpos($sidebarR5, 'admin_info.php') === false, "Student sidebar excludes Admin Info");

// ---------------------------------------------------------------------
// TEST SUITE 6: DIRECT URL ROUTE ACCESS AUTHORIZATION (header.php)
// ---------------------------------------------------------------------
echo "\n--- 6. DIRECT URL ACCESS AUTHORIZATION (checkUserRouteAuthorization) ---\n";
// Include header function
require_once __DIR__ . '/../admin/header/header.php';

// Super Admin (Role 1)
assertTest(checkUserRouteAuthorization($conn, 1, 1, 'menu_submenu_allocation.php') === true, "Super Admin authorized for menu_submenu_allocation.php");
assertTest(checkUserRouteAuthorization($conn, 1, 1, 'student_bulk_import.php') === true, "Super Admin authorized for student_bulk_import.php");

// Admin (Role 2)
assertTest(checkUserRouteAuthorization($conn, 2, 2, 'menu_submenu_allocation.php') === false, "Admin BLOCKED from menu_submenu_allocation.php");
assertTest(checkUserRouteAuthorization($conn, 2, 2, 'student_bulk_import.php') === false, "Admin BLOCKED from student_bulk_import.php");
assertTest(checkUserRouteAuthorization($conn, 2, 2, 'class_crud_new.php') === false, "Admin BLOCKED from class_crud_new.php");

// Coordinator (Role 3)
assertTest(checkUserRouteAuthorization($conn, 3, 3, 'menu_submenu_allocation.php') === false, "Coordinator BLOCKED from menu_submenu_allocation.php");
assertTest(checkUserRouteAuthorization($conn, 3, 3, 'admin_register.php') === false, "Coordinator BLOCKED from admin_register.php");

// Mentor (Role 4)
assertTest(checkUserRouteAuthorization($conn, 4, 4, 'menu_submenu_allocation.php') === false, "Mentor BLOCKED from menu_submenu_allocation.php");
assertTest(checkUserRouteAuthorization($conn, 4, 4, 'coordinator_allocation.php') === false, "Mentor BLOCKED from coordinator_allocation.php");
assertTest(checkUserRouteAuthorization($conn, 4, 4, 'student_admission.php') === false, "Mentor BLOCKED from student_admission.php");

// Student (Role 5)
assertTest(checkUserRouteAuthorization($conn, 5, 5, 'menu_submenu_allocation.php') === false, "Student BLOCKED from menu_submenu_allocation.php");
assertTest(checkUserRouteAuthorization($conn, 5, 5, 'student-info.php') === false, "Student BLOCKED from student-info.php");
assertTest(checkUserRouteAuthorization($conn, 5, 5, 'admin_info.php') === false, "Student BLOCKED from admin_info.php");
assertTest(checkUserRouteAuthorization($conn, 5, 5, 'mentor_allocation.php') === false, "Student BLOCKED from mentor_allocation.php");

// ---------------------------------------------------------------------
// TEST SUITE 7: DATABASE INTEGRITY & ORPHAN CHECKS
// ---------------------------------------------------------------------
echo "\n--- 7. DATABASE INTEGRITY & ORPHAN AUDIT ---\n";
$dupRes = mysqli_query($conn, "SELECT role_id, user_id, menu_id, sub_menu_id, COUNT(*) as cnt FROM st_menu_allocation_master GROUP BY role_id, user_id, menu_id, COALESCE(sub_menu_id, -1) HAVING cnt > 1");
$dupCount = mysqli_num_rows($dupRes);
assertTest($dupCount === 0, "Zero duplicate allocations in st_menu_allocation_master");

$orphanParentRes = mysqli_query($conn, "SELECT a.* FROM st_menu_allocation_master a LEFT JOIN st_menu_master m ON a.menu_id = m.menu_id WHERE m.menu_id IS NULL");
$orphanParentCount = mysqli_num_rows($orphanParentRes);
assertTest($orphanParentCount === 0, "Zero orphan parent menu allocations");

$orphanSubRes = mysqli_query($conn, "SELECT a.* FROM st_menu_allocation_master a LEFT JOIN st_sub_menu_master sm ON a.sub_menu_id = sm.sub_menu_id WHERE a.sub_menu_id IS NOT NULL AND sm.sub_menu_id IS NULL");
$orphanSubCount = mysqli_num_rows($orphanSubRes);
assertTest($orphanSubCount === 0, "Zero orphan submenu allocations");

echo "\n======================================================================\n";
echo " TEST SUMMARY: PASSED = $passed | FAILED = $failed | TOTAL = $total\n";
echo "======================================================================\n";

if ($failed === 0) {
    echo "STATUS: ALL $total ACCEPTANCE TESTS PASSED WITH 100% SUCCESS.\n";
    exit(0);
} else {
    echo "STATUS: $failed ASSERTION(S) FAILED.\n";
    exit(1);
}
