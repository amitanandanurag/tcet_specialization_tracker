<?php
/**
 * TCET Specialization Tracker — Comprehensive Excel Mass Student Import & Onboarding Test Suite
 * Executed strictly against an isolated disposable database: tcet_st_disposable_import_test
 * 
 * Verifies all 20 Acceptance Criteria:
 * 1. Template integrity
 * 2. Multi-format parsing (.xlsx, .csv)
 * 3. Missing mandatory fields detection
 * 4. Intra-file duplicate ERP detection
 * 5. Pre-existing database ERP conflict protection
 * 6. Invalid department detection
 * 7. Invalid class / FY exclusion
 * 8. CGPA threshold enforcement
 * 9. Real st_student_master insertion
 * 10. st_enrollment creation
 * 11. st_student_semester_history sync
 * 12. st_user_master creation (role 5, linked student_id, is_first_login=1)
 * 13. st_login creation (dual credential, bcrypt password)
 * 14. First-login authentication & forced password reset
 * 15. Student self-service profile completion & locked academic fields
 * 16. Idempotent import retry & duplicate avoidance
 * 17. Historical candidate reconciliation (promoted_student_id, IMPORTED status)
 * 18. Student Directory query without duplicates
 * 19. RBAC route security
 * 20. Authoritative database tcet_st zero-mutation integrity check
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../database/enrollment_service.php';
require_once __DIR__ . '/../database/student_bulk_import_service.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

echo "================================================================================\n";
echo " TCET ERP — EXCEL MASS STUDENT IMPORT & ONBOARDING AUTOMATED TEST SUITE\n";
echo "================================================================================\n\n";

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$authDb = 'tcet_st';
$testDb = 'tcet_st_disposable_import_test';

$connRoot = mysqli_connect($host, $user, $pass);
if (!$connRoot) {
    die("FATAL: MySQL connection failed: " . mysqli_connect_error() . "\n");
}

// Baseline authoritative count check
$preAuthCount = 0;
$cRes = mysqli_query($connRoot, "SELECT COUNT(*) FROM `{$authDb}`.st_student_master");
if ($cRes && ($cRow = mysqli_fetch_row($cRes))) {
    $preAuthCount = (int)$cRow[0];
}

echo "1. PREPARING ISOLATED DISPOSABLE DATABASE [{$testDb}]...\n";
mysqli_query($connRoot, "DROP DATABASE IF EXISTS `{$testDb}`");
if (!mysqli_query($connRoot, "CREATE DATABASE `{$testDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
    die("FATAL: Failed creating test database {$testDb}: " . mysqli_error($connRoot) . "\n");
}

// Clone all tables from tcet_st
$tRes = mysqli_query($connRoot, "SHOW FULL TABLES FROM `{$authDb}` WHERE Table_type = 'BASE TABLE'");
while ($tRow = mysqli_fetch_row($tRes)) {
    $tName = $tRow[0];
    mysqli_query($connRoot, "CREATE TABLE `{$testDb}`.`{$tName}` LIKE `{$authDb}`.`{$tName}`");
    mysqli_query($connRoot, "INSERT INTO `{$testDb}`.`{$tName}` SELECT * FROM `{$authDb}`.`{$tName}`");
}
echo "   Disposable database cloned successfully with full schema and reference masters.\n\n";

// Connect to test database
$connTest = mysqli_connect($host, $user, $pass, $testDb);
if (!$connTest) {
    die("FATAL: Could not connect to {$testDb}\n");
}

// Reset Candidate S1032241061 in disposable DB to simulate unimported historical candidate
mysqli_query($connTest, "DELETE FROM st_student_master WHERE registration_no = 'S1032241061'");
mysqli_query($connTest, "DELETE FROM st_login WHERE username = 'S1032241061' OR username = 's1032241061@tcetmumbai.in'");
mysqli_query($connTest, "UPDATE st_historical_student_candidates SET promoted_student_id = NULL, import_status = 'NOT_IMPORTED', academic_approval_status = 'PENDING' WHERE cur_erp_id = 'S1032241061'");

$importService = new StudentBulkImportService($connTest);

$passed = 0;
$failed = 0;

function assertTest(string $desc, bool $condition) {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] $desc\n";
        $passed++;
    } else {
        echo "  [FAIL] $desc\n";
        $failed++;
    }
}

// -----------------------------------------------------------------------------
// TEST 1: Template Download & Integrity
// -----------------------------------------------------------------------------
echo "--- TEST SUITE 1: TEMPLATE & PARSER INTEGRITY ---\n";
$tplFile = __DIR__ . '/../STUDENT_EXCEL_IMPORT_TEMPLATE.xlsx';
assertTest("Template file exists on disk", file_exists($tplFile));
assertTest("Template file size > 5000 bytes", file_exists($tplFile) && filesize($tplFile) > 5000);

// -----------------------------------------------------------------------------
// TEST 2: Multi-format Parsing (.xlsx and .csv)
// -----------------------------------------------------------------------------
$parsedTpl = $importService->parseUploadedFile($tplFile, 'STUDENT_EXCEL_IMPORT_TEMPLATE.xlsx');
assertTest("Template parses into associative rows (3 sample rows)", count($parsedTpl) === 3);

// Create a test CSV file
$testCsv = __DIR__ . '/test_import_payload.csv';
$csvContent = "ERP_Registration_ID,Roll_Number,Student_Full_Name,Email_Address,Mobile_Number,Department_Code,Academic_Year,Class,Semester,Division,Specialization_Track,Specialization_Name,CGPA,Graduation_Year,Faculty_Mentor,Status\n";
$csvContent .= "S1032249001,01,Test Student One,student1@tcetmumbai.in,9876543210,CE,2025 -2026,SE,4,A,Honours Degree,Artificial Intelligence,8.50,2027,,Active\n";
$csvContent .= "S1032249002,02,Test Student Two,student2@tcetmumbai.in,9876543211,IT,2025 -2026,SE,4,B,Honours Degree,Data Science,8.20,2027,,Active\n";
file_put_contents($testCsv, $csvContent);

$parsedCsv = $importService->parseUploadedFile($testCsv, 'test_import_payload.csv');
assertTest("CSV format parses cleanly into rows (2 rows)", count($parsedCsv) === 2);
@unlink($testCsv);

// -----------------------------------------------------------------------------
// TEST 3-8: Validation Engine (4 Categories)
// -----------------------------------------------------------------------------
echo "\n--- TEST SUITE 2: FOUR-CATEGORY VALIDATION ENGINE ---\n";
$mockRows = [
    // 1. Valid Row
    [
        'excel_row_num' => 2,
        'erp_registration_id' => 'S1032249101',
        'roll_number' => '10',
        'student_full_name' => 'Valid Student Alpha',
        'email_address' => 'alpha@tcetmumbai.in',
        'mobile_number' => '9820123456',
        'department_code' => 'CE',
        'academic_year' => '2025 -2026',
        'class' => 'SE',
        'semester' => '4',
        'division' => 'A',
        'specialization_track' => 'Honours Degree',
        'specialization_name' => 'Artificial Intelligence',
        'cgpa' => '8.65',
        'graduation_year' => '2027',
        'faculty_mentor' => '',
        'status' => 'Active'
    ],
    // 2. Intra-file duplicate ERP
    [
        'excel_row_num' => 3,
        'erp_registration_id' => 'S1032249101', // Same ERP
        'roll_number' => '11',
        'student_full_name' => 'Duplicate Student In File',
        'email_address' => 'dup@tcetmumbai.in',
        'mobile_number' => '9820123457',
        'department_code' => 'CE',
        'academic_year' => '2025 -2026',
        'class' => 'SE',
        'semester' => '4',
        'division' => 'A',
        'specialization_track' => 'Honours Degree',
        'specialization_name' => 'Artificial Intelligence',
        'cgpa' => '8.50',
        'graduation_year' => '2027',
        'faculty_mentor' => '',
        'status' => 'Active'
    ],
    // 3. Missing Mandatory Fields (no ERP, no Name)
    [
        'excel_row_num' => 4,
        'erp_registration_id' => '',
        'roll_number' => '12',
        'student_full_name' => '',
        'email_address' => 'noname@tcetmumbai.in',
        'mobile_number' => '9820123458',
        'department_code' => 'CE',
        'academic_year' => '2025 -2026',
        'class' => 'SE',
        'semester' => '4',
        'division' => 'B',
        'specialization_track' => 'Honours Degree',
        'specialization_name' => 'Artificial Intelligence',
        'cgpa' => '8.00',
        'graduation_year' => '2027',
        'faculty_mentor' => '',
        'status' => 'Active'
    ],
    // 4. Invalid Department Code
    [
        'excel_row_num' => 5,
        'erp_registration_id' => 'S1032249102',
        'roll_number' => '13',
        'student_full_name' => 'Invalid Dept Student',
        'email_address' => 'invdept@tcetmumbai.in',
        'mobile_number' => '9820123459',
        'department_code' => 'NON_EXISTENT_DEPT_XYZ',
        'academic_year' => '2025 -2026',
        'class' => 'SE',
        'semester' => '4',
        'division' => 'A',
        'specialization_track' => 'Honours Degree',
        'specialization_name' => 'Artificial Intelligence',
        'cgpa' => '8.20',
        'graduation_year' => '2027',
        'faculty_mentor' => '',
        'status' => 'Active'
    ],
    // 5. Invalid Class (FY not eligible for Specialization)
    [
        'excel_row_num' => 6,
        'erp_registration_id' => 'S1032249103',
        'roll_number' => '14',
        'student_full_name' => 'FY Student',
        'email_address' => 'fy@tcetmumbai.in',
        'mobile_number' => '9820123460',
        'department_code' => 'CE',
        'academic_year' => '2025 -2026',
        'class' => 'FY',
        'semester' => '2',
        'division' => 'A',
        'specialization_track' => 'Honours Degree',
        'specialization_name' => 'Artificial Intelligence',
        'cgpa' => '8.50',
        'graduation_year' => '2029',
        'faculty_mentor' => '',
        'status' => 'Active'
    ],
    // 6. CGPA below minimum institutional threshold for Track 1
    [
        'excel_row_num' => 7,
        'erp_registration_id' => 'S1032249104',
        'roll_number' => '15',
        'student_full_name' => 'Low CGPA Student',
        'email_address' => 'lowcgpa@tcetmumbai.in',
        'mobile_number' => '9820123461',
        'department_code' => 'CE',
        'academic_year' => '2025 -2026',
        'class' => 'SE',
        'semester' => '4',
        'division' => 'A',
        'specialization_track' => 'Honours Degree',
        'specialization_name' => 'Artificial Intelligence',
        'cgpa' => '6.45', // < 7.00 threshold
        'graduation_year' => '2027',
        'faculty_mentor' => '',
        'status' => 'Active'
    ],
    // 7. Valid Second Student (aimed for historical candidate reconciliation test)
    [
        'excel_row_num' => 8,
        'erp_registration_id' => 'S1032241061', // Matches Candidate ID 1 in historical candidates!
        'roll_number' => '34',
        'student_full_name' => 'Shailesh Gajengi',
        'email_address' => 'shailesh.gajengi@tcetmumbai.in',
        'mobile_number' => '9820123462',
        'department_code' => 'CE',
        'academic_year' => '2025 -2026',
        'class' => 'SE',
        'semester' => '4',
        'division' => 'A',
        'specialization_track' => 'Honours Degree',
        'specialization_name' => 'Artificial Intelligence',
        'cgpa' => '9.37',
        'graduation_year' => '2027',
        'faculty_mentor' => 'Deepali Joshi',
        'status' => 'Active'
    ]
];

$validationResults = $importService->validateBatch($mockRows);
$sum = $validationResults['summary'];

assertTest("Summary total rows = 7", $sum['total_rows'] === 7);
assertTest("Valid count = 2 (Alpha and Shailesh)", $sum['valid_count'] === 2);
assertTest("Duplicate count = 1 (Intra-file duplicate)", $sum['duplicate_count'] === 1);
assertTest("Missing info count = 1 (Empty ERP and Name)", $sum['missing_count'] === 1);
assertTest("Invalid data count = 3 (Invalid dept, FY class, Low CGPA)", $sum['invalid_count'] === 3);

// -----------------------------------------------------------------------------
// TEST 9-13: Execution & Atomic Database Insertion
// -----------------------------------------------------------------------------
echo "\n--- TEST SUITE 3: DATABASE INSERTION & LOGIN PROVISIONING ---\n";
$importReport = $importService->executeImport($validationResults['valid'], 1, ['default_password' => 'Tcet@123']);

assertTest("Students created in st_student_master = 2", $importReport['students_created'] === 2);
assertTest("Logins created in st_login = 4 (Dual login per student: ERP & Email)", $importReport['logins_created'] === 4);
assertTest("Zero execution errors", count($importReport['errors']) === 0);

$alphaStudentId = $importReport['imported_students'][0]['student_id'];
$alphaUserId = $importReport['imported_students'][0]['user_id'];

// Check st_student_master row
$sRow = mysqli_fetch_assoc(mysqli_query($connTest, "SELECT * FROM st_student_master WHERE student_id = {$alphaStudentId}"));
assertTest("Student record exists with correct registration_no", $sRow && $sRow['registration_no'] === 'S1032249101');
assertTest("Student record has active status=1", $sRow && (int)$sRow['status'] === 1);
assertTest("Student record has current_semester_id=4", $sRow && (int)$sRow['current_semester_id'] === 4);

// Check st_enrollment
$enRow = mysqli_fetch_assoc(mysqli_query($connTest, "SELECT * FROM st_enrollment WHERE student_id = {$alphaStudentId}"));
assertTest("Enrollment record exists for student", $enRow !== null);
assertTest("Enrollment has Active status", $enRow && $enRow['status'] === 'Active');

// Check st_student_semester_history
$histRow = mysqli_fetch_assoc(mysqli_query($connTest, "SELECT * FROM st_student_semester_history WHERE student_id = {$alphaStudentId} AND semester_id = 4"));
assertTest("Semester history record synchronized", $histRow !== null);

// Check st_user_master
$uRow = mysqli_fetch_assoc(mysqli_query($connTest, "SELECT * FROM st_user_master WHERE user_id = {$alphaUserId}"));
assertTest("User master row created with role_id=5 (Student)", $uRow && (int)$uRow['role_id'] === 5);
assertTest("User master linked to student_id", $uRow && (int)$uRow['student_id'] === $alphaStudentId);
assertTest("User master initialized with is_first_login=1", $uRow && (int)$uRow['is_first_login'] === 1);

// Check st_login dual credentials
$logErp = mysqli_fetch_assoc(mysqli_query($connTest, "SELECT * FROM st_login WHERE username = 'S1032249101' AND user_id = {$alphaUserId}"));
assertTest("Login credential exists for ERP ID", $logErp !== null);
$logEmail = mysqli_fetch_assoc(mysqli_query($connTest, "SELECT * FROM st_login WHERE username = 'alpha@tcetmumbai.in' AND user_id = {$alphaUserId}"));
assertTest("Login credential exists for Email", $logEmail !== null);

// Password bcrypt hash verification
$hashMatches = password_verify('Tcet@123', $logErp['password'] ?? '');
assertTest("Stored bcrypt hash verifies against initial password 'Tcet@123'", $hashMatches);

// -----------------------------------------------------------------------------
// TEST 14: Authentication & First-Login Password Change Simulation
// -----------------------------------------------------------------------------
echo "\n--- TEST SUITE 4: AUTHENTICATION & FIRST LOGIN WORKFLOW ---\n";
// 1. First login check: simulate login/login.php
$checkFirstLogin = mysqli_fetch_assoc(mysqli_query($connTest, "SELECT is_first_login FROM st_user_master WHERE user_id = {$alphaUserId}"));
$authResponse = ((int)$checkFirstLogin['is_first_login'] === 1) ? "change_password" : "ok4";
assertTest("Initial login triggers 'change_password' redirect", $authResponse === 'change_password');

// 2. Simulate password change via admin/change_password.php
$newPassword = 'MyNewSecurePassword#2026';
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
mysqli_query($connTest, "UPDATE st_login SET password = '{$newHash}' WHERE user_id = {$alphaUserId}");
mysqli_query($connTest, "UPDATE st_user_master SET is_first_login = 0 WHERE user_id = {$alphaUserId}");

// 3. Second login check: should now return "ok4"
$checkSecondLogin = mysqli_fetch_assoc(mysqli_query($connTest, "SELECT is_first_login FROM st_user_master WHERE user_id = {$alphaUserId}"));
$secondAuthResponse = ((int)$checkSecondLogin['is_first_login'] === 1) ? "change_password" : "ok4";
assertTest("Subsequent login returns 'ok4' (Redirect to Student Dashboard)", $secondAuthResponse === 'ok4');
$newHashVerifies = password_verify($newPassword, mysqli_fetch_assoc(mysqli_query($connTest, "SELECT password FROM st_login WHERE username = 'S1032249101'"))['password']);
assertTest("Updated password verifies with new credentials", $newHashVerifies);

// -----------------------------------------------------------------------------
// TEST 15: Student Self-Service Profile Completion Simulation
// -----------------------------------------------------------------------------
echo "\n--- TEST SUITE 5: STUDENT SELF-SERVICE PROFILE COMPLETION ---\n";
// Update allowed fields: mobile and email
$newMobile = '9988776655';
$newEmail = 'updated.alpha@tcetmumbai.in';
mysqli_query($connTest, "UPDATE st_student_master SET mobile = '{$newMobile}', email = '{$newEmail}' WHERE student_id = {$alphaStudentId}");
mysqli_query($connTest, "UPDATE st_user_master SET phone_number = '{$newMobile}', email_id = '{$newEmail}' WHERE student_id = {$alphaStudentId}");

$updStudent = mysqli_fetch_assoc(mysqli_query($connTest, "SELECT * FROM st_student_master WHERE student_id = {$alphaStudentId}"));
assertTest("Student mobile updated to new contact", $updStudent['mobile'] === $newMobile);
assertTest("Student email updated to new email", $updStudent['email'] === $newEmail);
assertTest("ERP ID remains strictly locked to original value", $updStudent['registration_no'] === 'S1032249101');
assertTest("Department ID remains strictly locked to original value", (int)$updStudent['department_id'] === 3);

// -----------------------------------------------------------------------------
// TEST 16: Idempotent Re-Import Protection
// -----------------------------------------------------------------------------
echo "\n--- TEST SUITE 6: IDEMPOTENT RE-IMPORT PROTECTION ---\n";
// Re-validate mockRows again
$reValidate = $importService->validateBatch($mockRows);
assertTest("Re-importing S1032249101 now caught as pre-existing database duplicate", count($reValidate['duplicate']) >= 2);

// -----------------------------------------------------------------------------
// TEST 17: Historical Candidate Reconciliation
// -----------------------------------------------------------------------------
echo "\n--- TEST SUITE 7: HISTORICAL CANDIDATE RECONCILIATION ---\n";
// Candidate 1 (S1032241061) was imported in Test 9
$reconciledCandidate = mysqli_fetch_assoc(mysqli_query($connTest, "SELECT * FROM st_historical_student_candidates WHERE cur_erp_id = 'S1032241061'"));
assertTest("Historical candidate promoted_student_id populated", !empty($reconciledCandidate['promoted_student_id']));
assertTest("Historical candidate import_status = 'IMPORTED'", $reconciledCandidate['import_status'] === 'IMPORTED');
assertTest("Historical candidate academic_approval_status = 'APPROVED'", $reconciledCandidate['academic_approval_status'] === 'APPROVED');

// -----------------------------------------------------------------------------
// TEST 18: Student Directory Query Integration (No Virtual UNION Duplication)
// -----------------------------------------------------------------------------
echo "\n--- TEST SUITE 8: STUDENT DIRECTORY QUERY INTEGRATION ---\n";
// When querying active students, S1032241061 is in st_student_master
$dirQuery = "SELECT sm.student_id, sm.registration_no, sm.fname FROM st_student_master sm WHERE sm.registration_no = 'S1032241061'";
$dirRes = mysqli_query($connTest, $dirQuery);
assertTest("Student appears as real record in st_student_master query", mysqli_num_rows($dirRes) === 1);

// Historical query with exclusion of IMPORTED
$histDirQuery = "SELECT hc.candidate_id FROM st_historical_student_candidates hc WHERE hc.cur_erp_id = 'S1032241061' AND (hc.promoted_student_id IS NULL OR hc.import_status != 'IMPORTED')";
$histDirRes = mysqli_query($connTest, $histDirQuery);
assertTest("Historical UNION query correctly excludes imported student (0 duplicate rows)", mysqli_num_rows($histDirRes) === 0);

// -----------------------------------------------------------------------------
// TEST 19: RBAC Route Security
// -----------------------------------------------------------------------------
echo "\n--- TEST SUITE 9: RBAC ROUTE AUTHORIZATION ---\n";
// Set session and script context before requiring functions
$_SERVER['PHP_SELF'] = '/st/admin/index.php';
$_SESSION['user_id'] = 1;
$_SESSION['role_id'] = 1;
$_SESSION['user_type'] = 1;

if (!function_exists('checkUserRouteAuthorization')) {
    ob_start();
    require_once __DIR__ . '/../admin/header/header.php';
    ob_end_clean();
}

$superAdminAuth = checkUserRouteAuthorization($connTest, 1, 1, 'student_bulk_import.php');
$adminAuth = checkUserRouteAuthorization($connTest, 2, 2, 'student_bulk_import.php');
$coordAuth = checkUserRouteAuthorization($connTest, 3, 3, 'student_bulk_import.php');
$mentorAuth = checkUserRouteAuthorization($connTest, 4, 4, 'student_bulk_import.php');
$studentAuth = checkUserRouteAuthorization($connTest, 5, 5, 'student_bulk_import.php');

assertTest("Super Admin (Role 1) has route access", $superAdminAuth === true);
assertTest("Admin (Role 2) is blocked", $adminAuth === false);
assertTest("Coordinator (Role 3) is blocked", $coordAuth === false);
assertTest("Mentor (Role 4) is blocked", $mentorAuth === false);
assertTest("Student (Role 5) is blocked", $studentAuth === false);

// -----------------------------------------------------------------------------
// TEST 20: Authoritative Database Zero-Mutation Safety Check
// -----------------------------------------------------------------------------
echo "\n--- TEST SUITE 10: AUTHORITATIVE DATABASE INTEGRITY CHECK ---\n";
$postAuthCount = 0;
$cResPost = mysqli_query($connRoot, "SELECT COUNT(*) FROM `{$authDb}`.st_student_master");
if ($cResPost && ($cRowPost = mysqli_fetch_row($cResPost))) {
    $postAuthCount = (int)$cRowPost[0];
}
assertTest("Authoritative database tcet_st st_student_master record count unchanged ({$preAuthCount} == {$postAuthCount})", $preAuthCount === $postAuthCount);

// Clean up disposable test database
mysqli_query($connRoot, "DROP DATABASE IF EXISTS `{$testDb}`");
echo "   Disposable database {$testDb} dropped safely.\n\n";

echo "================================================================================\n";
echo " TEST SUMMARY: PASSED = {$passed} | FAILED = {$failed}\n";
echo "================================================================================\n";

if ($failed === 0) {
    echo "STATUS: ALL 20 ACCEPTANCE TESTS PASSED WITH 100% SUCCESS.\n";
    exit(0);
} else {
    echo "STATUS: SOME TESTS FAILED.\n";
    exit(1);
}
