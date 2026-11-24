<?php
/**
 * TCET_ST Master Regression Test Suite
 * Validates all 17 critical functional areas post-cleanup.
 */

$baseUrl = 'http://localhost/st';
$rootDir = realpath(__DIR__ . '/..');

require_once $rootDir . '/database/db_connect.php';
$db = new DBController();
$conn = $db->conn;

echo "====================================================================\n";
echo "TCET_ST MASTER POST-CLEANUP REGRESSION TEST SUITE (17 AREAS)\n";
echo "====================================================================\n\n";

function curlGet($url, $cookieJar = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    if ($cookieJar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    }
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    return [
        'code' => $code,
        'headers' => substr($raw, 0, $headerSize),
        'body' => substr($raw, $headerSize)
    ];
}

$results = [];
$cookieAdmin = tempnam(sys_get_temp_dir(), 'reg_admin_');

// Setup Super Admin Session
$bridgeInit = curlGet($baseUrl . '/tests/uat_session_bridge.php?user=superadmin&dest=admin/dashboard.php&db=tcet_st', $cookieAdmin);

// 1. Login & Logout Flow
echo "[AREA 1] Testing Login & Logout Workflow...\n";
$r1_login = curlGet($baseUrl . '/index.php');
$r1_dash = curlGet($baseUrl . '/admin/dashboard.php', $cookieAdmin);
$r1_logout = curlGet($baseUrl . '/login/logout.php', $cookieAdmin);
$r1_post_logout = curlGet($baseUrl . '/admin/dashboard.php');
$a1_pass = (in_array($r1_login['code'], [200, 302]) && $r1_dash['code'] === 200 && in_array($r1_logout['code'], [200, 302]) && in_array($r1_post_logout['code'], [302, 403]));
$results['1. Login and Logout'] = $a1_pass ? 'PASS' : 'FAIL';
echo "  Login: {$r1_login['code']} | Dash: {$r1_dash['code']} | Logout: {$r1_logout['code']} | Result: " . ($a1_pass ? 'PASS' : 'FAIL') . "\n\n";

// Re-authenticate admin
curlGet($baseUrl . '/tests/uat_session_bridge.php?user=superadmin&dest=admin/dashboard.php&db=tcet_st', $cookieAdmin);

// 2. Dashboard
echo "[AREA 2] Testing Dashboard & Visual Widgets...\n";
$r2 = curlGet($baseUrl . '/admin/dashboard.php', $cookieAdmin);
$a2_pass = ($r2['code'] === 200 && (strpos($r2['body'], 'TOTAL ENROLLED STUDENTS') !== false || strpos($r2['body'], 'Dashboard') !== false));
$results['2. Dashboard'] = $a2_pass ? 'PASS' : 'FAIL';
echo "  HTTP: {$r2['code']} | Result: " . ($a2_pass ? 'PASS' : 'FAIL') . "\n\n";

// 3. Student Directory
echo "[AREA 3] Testing Student Directory & DataTables Backend...\n";
$r3_page = curlGet($baseUrl . '/admin/student-info.php', $cookieAdmin);
$r3_ajax = curlGet($baseUrl . '/admin/student_info_ajax.php', $cookieAdmin);
$json3 = json_decode($r3_ajax['body'], true);
$a3_pass = ($r3_page['code'] === 200 && $r3_ajax['code'] === 200 && isset($json3['recordsTotal']) && $json3['recordsTotal'] > 0);
$results['3. Student Directory'] = $a3_pass ? 'PASS' : 'FAIL';
echo "  Page: {$r3_page['code']} | Ajax: {$r3_ajax['code']} | Records: " . ($json3['recordsTotal'] ?? 0) . " | Result: " . ($a3_pass ? 'PASS' : 'FAIL') . "\n\n";

// 4. Student Profile Modal
echo "[AREA 4] Testing Student Profile Modal (student_view.php)...\n";
$r4 = curlGet($baseUrl . '/admin/student_view.php?id=9016', $cookieAdmin);
$a4_pass = ($r4['code'] === 200 && strpos($r4['body'], 'Shailesh Gajengi') !== false && strpos($r4['body'], 'erp-doc-table-wrap') !== false);
$results['4. Student Profile Modal'] = $a4_pass ? 'PASS' : 'FAIL';
echo "  HTTP: {$r4['code']} | Doc Table Wrapped: YES | Result: " . ($a4_pass ? 'PASS' : 'FAIL') . "\n\n";

// 5. Academic Progression
echo "[AREA 5] Testing Academic Progression Consistency...\n";
$qProg = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM st_student_semester_history");
$progCount = ($qProg && $row = mysqli_fetch_assoc($qProg)) ? intval($row['cnt']) : 0;
$r5_batch = curlGet($baseUrl . '/admin/batch_promotion.php', $cookieAdmin);
$a5_pass = ($progCount > 0 && $r5_batch['code'] === 200);
$results['5. Academic Progression'] = $a5_pass ? 'PASS' : 'FAIL';
echo "  History Records: $progCount | Batch Promotion Page: {$r5_batch['code']} | Result: " . ($a5_pass ? 'PASS' : 'FAIL') . "\n\n";

// 6. Excel Mass Upload
echo "[AREA 6] Testing Excel Mass Upload Service & Template...\n";
$r6_page = curlGet($baseUrl . '/admin/student_bulk_import.php', $cookieAdmin);
$r6_tpl = file_exists($rootDir . '/STUDENT_EXCEL_IMPORT_TEMPLATE.xlsx');
$a6_pass = ($r6_page['code'] === 200 && strpos($r6_page['body'], 'Bulk Student Upload') !== false && $r6_tpl);
$results['6. Excel Mass Upload'] = $a6_pass ? 'PASS' : 'FAIL';
echo "  Bulk Import Page: {$r6_page['code']} | Template on Disk: " . ($r6_tpl ? 'YES' : 'NO') . " | Result: " . ($a6_pass ? 'PASS' : 'FAIL') . "\n\n";

// 7. Student Document Upload & Download
echo "[AREA 7] Testing Document Controller (Physical & Historical Slip)...\n";
$r7_phys = curlGet($baseUrl . '/admin/document_view.php?type=marklist&file=1777623879_1032251364_mark-list1.jpeg', $cookieAdmin);
$r7_hist = curlGet($baseUrl . '/admin/document_view.php?type=marklist&file=HISTORICAL_MIGRATION_REF_SEM4-0001', $cookieAdmin);
$a7_pass = ($r7_phys['code'] === 200 && strpos($r7_phys['headers'], 'image/jpeg') !== false && 
            $r7_hist['code'] === 200 && strpos($r7_hist['body'], 'Verified on Admission') !== false);
$results['7. Student Document Management'] = $a7_pass ? 'PASS' : 'FAIL';
echo "  Physical Stream: {$r7_phys['code']} | Historical Slip: {$r7_hist['code']} | Result: " . ($a7_pass ? 'PASS' : 'FAIL') . "\n\n";

// 8. Student Login & Dual Login Credentials
echo "[AREA 8] Testing Student Login System & Accounts...\n";
$qStudLog = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM st_user_master WHERE role_id = 5");
$studLogCount = ($qStudLog && $row = mysqli_fetch_assoc($qStudLog)) ? intval($row['cnt']) : 0;
$a8_pass = ($studLogCount >= 877);
$results['8. Student Login & Account Provisioning'] = $a8_pass ? 'PASS' : 'FAIL';
echo "  Student User Accounts: $studLogCount | Result: " . ($a8_pass ? 'PASS' : 'FAIL') . "\n\n";

// 9. Admin Functionality
echo "[AREA 9] Testing Admin Management (admin_info.php & user-info.php)...\n";
$r9_info = curlGet($baseUrl . '/admin/admin_info.php', $cookieAdmin);
$r9_users = curlGet($baseUrl . '/admin/user-info.php?type=users', $cookieAdmin);
$a9_pass = ($r9_info['code'] === 200 && $r9_users['code'] === 200);
$results['9. Admin Functionality'] = $a9_pass ? 'PASS' : 'FAIL';
echo "  Admin Info: {$r9_info['code']} | User List: {$r9_users['code']} | Result: " . ($a9_pass ? 'PASS' : 'FAIL') . "\n\n";

// 10. Coordinator / HOD Functionality
echo "[AREA 10] Testing Coordinator & HOD Monitoring...\n";
$r10_alloc = curlGet($baseUrl . '/admin/coordinator_allocation.php', $cookieAdmin);
$r10_hod = curlGet($baseUrl . '/admin/hod_mentor_allocation.php', $cookieAdmin);
$a10_pass = ($r10_alloc['code'] === 200 && $r10_hod['code'] === 200);
$results['10. Coordinator / HOD Functionality'] = $a10_pass ? 'PASS' : 'FAIL';
echo "  Coordinator: {$r10_alloc['code']} | HOD Monitoring: {$r10_hod['code']} | Result: " . ($a10_pass ? 'PASS' : 'FAIL') . "\n\n";

// 11. Mentor Functionality
echo "[AREA 11] Testing Mentor Allocation & Subject Mapping...\n";
$r11_alloc = curlGet($baseUrl . '/admin/mentor_allocation.php', $cookieAdmin);
$r11_subj = curlGet($baseUrl . '/admin/mentor_subject.php', $cookieAdmin);
$a11_pass = ($r11_alloc['code'] === 200 && $r11_subj['code'] === 200);
$results['11. Mentor Functionality'] = $a11_pass ? 'PASS' : 'FAIL';
echo "  Mentor Alloc: {$r11_alloc['code']} | Mentor Subj: {$r11_subj['code']} | Result: " . ($a11_pass ? 'PASS' : 'FAIL') . "\n\n";

// 12. Student Portal Functionality
echo "[AREA 12] Testing Student Public Portal Registration...\n";
$r12 = curlGet($baseUrl . '/login/student_register.php');
$a12_pass = ($r12['code'] === 200 && (strpos($r12['body'], 'Student Registration') !== false || strpos($r12['body'], 'Registration') !== false));
$results['12. Student Functionality'] = $a12_pass ? 'PASS' : 'FAIL';
echo "  Portal Register HTTP: {$r12['code']} | Result: " . ($a12_pass ? 'PASS' : 'FAIL') . "\n\n";

// 13. Role & Submenu Allocation
echo "[AREA 13] Testing Role & Submenu Allocation Live Matrix...\n";
$r13 = curlGet($baseUrl . '/admin/menu_submenu_allocation.php?tab=role_allocations&profile=1', $cookieAdmin);
$a13_pass = ($r13['code'] === 200 && strpos($r13['body'], 'Live Sidebar Preview') !== false);
$results['13. Role and Submenu Allocation'] = $a13_pass ? 'PASS' : 'FAIL';
echo "  Menu Allocation: {$r13['code']} | Live Preview: YES | Result: " . ($a13_pass ? 'PASS' : 'FAIL') . "\n\n";

// 14. Direct URL Authorization & Security Controls
echo "[AREA 14] Testing Direct URL Authorization Guards...\n";
$r14_unauth = curlGet($baseUrl . '/admin/dashboard.php'); // No session
$r14_upload = curlGet($baseUrl . '/admin/uploads/marklists/index.php'); // Deny rule
$a14_pass = (in_array($r14_unauth['code'], [302, 403]) && $r14_upload['code'] === 403);
$results['14. Direct URL Authorization'] = $a14_pass ? 'PASS' : 'FAIL';
echo "  Unauth Guard: {$r14_unauth['code']} | Upload Vault: {$r14_upload['code']} | Result: " . ($a14_pass ? 'PASS' : 'FAIL') . "\n\n";

// 15. Dashboard Statistics Accuracy
echo "[AREA 15] Testing Dashboard Count Reconciliations...\n";
$qStudents = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM st_student_master");
$studCount = ($qStudents && $row = mysqli_fetch_assoc($qStudents)) ? intval($row['cnt']) : 0;
$a15_pass = ($studCount === 877);
$results['15. Dashboard Statistics'] = $a15_pass ? 'PASS' : 'FAIL';
echo "  Canonical Student Master: $studCount (Expected: 877) | Result: " . ($a15_pass ? 'PASS' : 'FAIL') . "\n\n";

// 16. Specialization Management
echo "[AREA 16] Testing Specialization Subject Management...\n";
$r16 = curlGet($baseUrl . '/admin/specialization_subject_manage.php', $cookieAdmin);
$a16_pass = ($r16['code'] === 200 && strpos($r16['body'], 'Specialization') !== false);
$results['16. Specialization Management'] = $a16_pass ? 'PASS' : 'FAIL';
echo "  Specialization Subjects Page: {$r16['code']} | Result: " . ($a16_pass ? 'PASS' : 'FAIL') . "\n\n";

// 17. Branch and Department Management
echo "[AREA 17] Testing Branch & Department Management (branch_info.php)...\n";
$r17 = curlGet($baseUrl . '/admin/branch_info.php', $cookieAdmin);
$a17_pass = ($r17['code'] === 200 && strpos($r17['body'], 'Department') !== false);
$results['17. Branch & Department Management'] = $a17_pass ? 'PASS' : 'FAIL';
echo "  Branch Info Page: {$r17['code']} | Result: " . ($a17_pass ? 'PASS' : 'FAIL') . "\n\n";

// Summary
echo "====================================================================\n";
echo "REGRESSION SUMMARY MATRIX:\n";
echo "====================================================================\n";
$passCount = 0;
foreach ($results as $area => $stat) {
    if ($stat === 'PASS') $passCount++;
    echo sprintf("%-45s : [%s]\n", $area, $stat);
}

echo "\nTOTAL PASSED: $passCount / 17 AREAS\n";
echo "====================================================================\n";

@unlink($cookieAdmin);

if ($passCount === 17) {
    exit(0);
} else {
    exit(1);
}
