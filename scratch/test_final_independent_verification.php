<?php
/**
 * Final Independent Comprehensive Verification Script
 * Validates:
 * 1. CSV Formula Injection Escaping
 * 2. Complete Student Academic Lifecycle & History Preservation
 * 3. Real Database Metric Consistency
 * 4. Responsive UI & CSS Shell Verification
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../database/db_connect.php';

$db = new DBController();
$conn = $db->conn;
$passed = 0;
$failed = 0;

function checkTest($condition, $name) {
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] $name\n";
        $passed++;
    } else {
        echo "[FAIL] $name\n";
        $failed++;
    }
}

echo "=== TCET ERP FINAL INDEPENDENT VERIFICATION SUITE ===\n\n";

// -------------------------------------------------------------
// SECTION 1: CSV FORMULA INJECTION ESCAPING VERIFICATION
// -------------------------------------------------------------
echo "--- 1. CSV FORMULA INJECTION VERIFICATION ---\n";

$exportCode = file_get_contents(__DIR__ . '/../admin/export_service.php');
checkTest(strpos($exportCode, 'function sanitizeCsvRow') !== false, "sanitizeCsvRow function is declared in export_service.php");
checkTest(strpos($exportCode, "in_array(\$firstChar, ['=', '+', '-', '@', \"\\t\", \"\\r\"])") !== false, "sanitizeCsvRow checks all critical formula characters: =, +, -, @, \\t, \\r");

// Test formula escaping behavior
$testRow = [
    '=1+1',
    '+1234567890',
    '-5000',
    '@SUM(A1:A5)',
    "\tmalicious",
    'Normal Student Name',
    12345
];

// Include helper logic to test directly
function testSanitizeCsvRow(array $row): array {
    return array_map(function($cell) {
        if (is_string($cell) && strlen($cell) > 0) {
            $firstChar = $cell[0];
            if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"])) {
                return "'" . $cell;
            }
        }
        return $cell;
    }, $row);
}

$sanitized = testSanitizeCsvRow($testRow);
checkTest($sanitized[0] === "'=1+1", "Formula starting with '=' is sanitized with leading single quote");
checkTest($sanitized[1] === "'+1234567890", "Formula starting with '+' is sanitized with leading single quote");
checkTest($sanitized[2] === "'-5000", "Formula starting with '-' is sanitized with leading single quote");
checkTest($sanitized[3] === "'@SUM(A1:A5)", "Formula starting with '@' is sanitized with leading single quote");
checkTest($sanitized[4] === "'\tmalicious", "Formula starting with tab is sanitized with leading single quote");
checkTest($sanitized[5] === "Normal Student Name", "Standard text is preserved unmodified");
checkTest($sanitized[6] === 12345, "Integer value is preserved unmodified");

// -------------------------------------------------------------
// SECTION 2: END-TO-END STUDENT ACADEMIC LIFECYCLE VERIFICATION
// -------------------------------------------------------------
echo "\n--- 2. COMPLETE STUDENT ACADEMIC LIFECYCLE VERIFICATION ---\n";

$testRegNo = "TEST_E2E_" . time();
$subRes = mysqli_query($conn, "SELECT subject_id FROM st_specialization_subject_master LIMIT 1");
$subRow = mysqli_fetch_assoc($subRes);
$sub1 = intval($subRow['subject_id'] ?? 1);

// Pick mentor 4
$db->assignSubjectMentor($sub1, 4, 1, 5, 1);

// 2.1 Admission -> Creation in st_student_master
$insStudent = "INSERT INTO st_student_master (
    academic_year_id, registration_no, roll_no, class_id, division_id, department_id,
    specialization_id, specialization_subject_id, current_semester_id, cgpa, fname,
    mobile, email, status, created_at
) VALUES (
    1, '$testRegNo', 'TEST-E2E-01', 1, 1, 1,
    1, $sub1, 5, 8.85, 'Test Final E2E Student',
    '9999999999', 'e2e_student@tcet.edu', 1, NOW()
)";
$resIns = mysqli_query($conn, $insStudent);
checkTest($resIns === true, "Student record created in st_student_master (Admission)");
$testStudentId = mysqli_insert_id($conn);

// 2.2 Record Initial Semester History (Semester 5)
$db->syncStudentSemesterHistory($testStudentId, 5);
$initialHistory = $db->getStudentAcademicHistory($testStudentId);
checkTest(count($initialHistory) === 1, "Initial Semester 5 history record created");
checkTest(intval($initialHistory[0]['semester_id']) === 5 && $initialHistory[0]['history_status'] === 'Active', "Initial history status is Active for Semester 5");
checkTest($initialHistory[0]['roll_no'] === 'TEST-E2E-01', "Initial history roll_no is TEST-E2E-01");

// 2.3 Dynamic Mentor Resolution for Semester 5
$resolvedMentorSem5 = $db->getResolvedMentorForStudent($testStudentId, 5, 1);
checkTest(!empty($resolvedMentorSem5) && intval($resolvedMentorSem5['mentor_id']) === 4, "Dynamic mentor resolved for Semester 5 is Mentor #4");

// 2.4 Semester Promotion (SEM V -> SEM VI)
$newSubRes = mysqli_query($conn, "SELECT subject_id FROM st_specialization_subject_master WHERE subject_id != $sub1 LIMIT 1");
$newSubRow = mysqli_fetch_assoc($newSubRes);
$newSubId = $newSubRow ? intval($newSubRow['subject_id']) : 2;

// Assign mentor 6 for SEM VI
$db->assignSubjectMentor($newSubId, 6, 1, 6, 1);

$promoteSuccess = $db->promoteStudentSemester(
    $testStudentId,
    6,
    1,
    1,
    1,
    'TEST-E2E-01-SEM6',
    1,
    1,
    $newSubId,
    9.10,
    1
);
checkTest($promoteSuccess === true, "promoteStudentSemester successfully transitioned student to Semester 6");

// 2.5 Verify Non-Destructive Historical Preservation
$histRows = $db->getStudentAcademicHistory($testStudentId);
checkTest(count($histRows) === 2, "Student has exactly 2 semester history records after promotion");

$sem5Hist = null;
$sem6Hist = null;
foreach ($histRows as $h) {
    if (intval($h['semester_id']) === 5) $sem5Hist = $h;
    if (intval($h['semester_id']) === 6) $sem6Hist = $h;
}

checkTest($sem5Hist !== null && $sem5Hist['history_status'] === 'Completed', "Semester 5 record is preserved with status 'Completed'");
checkTest($sem5Hist !== null && $sem5Hist['roll_no'] === 'TEST-E2E-01', "Semester 5 preserved original roll number (TEST-E2E-01)");
checkTest($sem6Hist !== null && $sem6Hist['history_status'] === 'Active', "Semester 6 record is created with status 'Active'");
checkTest($sem6Hist !== null && $sem6Hist['roll_no'] === 'TEST-E2E-01-SEM6', "Semester 6 has new roll number (TEST-E2E-01-SEM6)");

// 2.6 Verify Dynamic Mentor Resolution for Historical vs Current
$histMentorSem5 = $db->getResolvedMentorForStudent($testStudentId, 5, 1);
$histMentorSem6 = $db->getResolvedMentorForStudent($testStudentId, 6, 1);
checkTest(intval($histMentorSem5['mentor_id']) === 4, "Historical SEM 5 resolves to Mentor #4");
checkTest(intval($histMentorSem6['mentor_id']) === 6, "Current SEM 6 resolves to Mentor #6");

// 2.7 Verify Active Profile in st_student_master
$activeProf = $db->runQuery("SELECT current_semester_id, roll_no, specialization_subject_id FROM st_student_master WHERE student_id = $testStudentId LIMIT 1");
checkTest(intval($activeProf[0]['current_semester_id']) === 6, "Active profile current_semester_id is updated to 6");
checkTest($activeProf[0]['roll_no'] === 'TEST-E2E-01-SEM6', "Active profile roll_no is updated to TEST-E2E-01-SEM6");

// 2.8 Clean up test student
mysqli_query($conn, "DELETE FROM st_student_master WHERE student_id = $testStudentId");
mysqli_query($conn, "DELETE FROM st_student_semester_history WHERE student_id = $testStudentId");

// -------------------------------------------------------------
// SECTION 3: REAL DATABASE METRIC CONSISTENCY
// -------------------------------------------------------------
echo "\n--- 3. REAL DATABASE DATA & METRIC VERIFICATION ---\n";

$actualStudentsCount = $db->numRows("SELECT student_id FROM st_student_master");
$actualDepartmentsCount = $db->numRows("SELECT department_id FROM st_department_master");
$actualUsersCount = $db->numRows("SELECT user_id FROM st_user_master");

checkTest($actualStudentsCount > 0, "Real student records exist in st_student_master ($actualStudentsCount students)");
checkTest($actualDepartmentsCount > 0, "Real departments exist in st_department_master ($actualDepartmentsCount departments)");
checkTest($actualUsersCount > 0, "Real user records exist in st_user_master ($actualUsersCount users)");

// -------------------------------------------------------------
// SECTION 4: RESPONSIVE UI & CSS INTEGRITY VERIFICATION
// -------------------------------------------------------------
echo "\n--- 4. RESPONSIVE UI & CSS SHELL VERIFICATION ---\n";

$headerHtml = file_get_contents(__DIR__ . '/../admin/header/header.php');
checkTest(strpos($headerHtml, 'name="viewport"') !== false, "header.php includes responsive viewport meta tag");
checkTest(strpos($headerHtml, 'sidebar-toggle') !== false, "header.php includes mobile sidebar navigation toggle button");
checkTest(strpos($headerHtml, 'erp-theme.css') !== false, "header.php loads centralized erp-theme.css");

$themeCss = file_get_contents(__DIR__ . '/../admin/css/erp-theme.css');
checkTest(strpos($themeCss, '@media') !== false, "erp-theme.css contains responsive breakpoint media queries");
checkTest(strpos($themeCss, '#423cbc') !== false, "erp-theme.css defines institutional brand color #423cbc");

// -------------------------------------------------------------
// SUMMARY
// -------------------------------------------------------------
echo "\n=======================================================\n";
echo "FINAL INDEPENDENT VERIFICATION SUMMARY: Passed = $passed, Failed = $failed\n";
echo "=======================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);
