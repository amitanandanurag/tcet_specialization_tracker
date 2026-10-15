<?php
/**
 * TCET ERP - Academic Progression Data-Consistency & UI Verification Suite
 */
require_once __DIR__ . '/../database/db_connect.php';

$db_handle = new DBController();
$conn = $db_handle->conn;

echo "=== TCET ERP ACADEMIC PROGRESSION DATA-CONSISTENCY SUITE ===" . PHP_EOL . PHP_EOL;

$passCount = 0;
$failCount = 0;

function assertProgression($condition, $message) {
    global $passCount, $failCount;
    if ($condition) {
        echo "[PASS] $message" . PHP_EOL;
        $passCount++;
    } else {
        echo "[FAIL] $message" . PHP_EOL;
        $failCount++;
    }
}

// 1. Verify that hardcoded progression cards have been removed from all student views
$filesToCheck = [
    'admin/student_dashboard.php' => file_get_contents(__DIR__ . '/../admin/student_dashboard.php'),
    'admin/student_admission_view.php' => file_get_contents(__DIR__ . '/../admin/student_admission_view.php'),
    'admin/student_view.php' => file_get_contents(__DIR__ . '/../admin/student_view.php')
];

foreach ($filesToCheck as $fn => $content) {
    assertProgression(strpos($content, 'erp-progression-track') === false, "$fn has no hardcoded erp-progression-track");
    assertProgression(strpos($content, 'allSemesters =') === false, "$fn has no hardcoded allSemesters array");
    assertProgression(strpos($content, 'Future Stage') === false, "$fn has no fabricated Future Stage labels");
    assertProgression(strpos($content, 'No semester progression records are available for this student.') !== false, "$fn includes clean empty state message");
}

// 2. Test Rendering for a Student with 0 Semester History Records
// Create temporary test student with 0 history rows
$testReg = "TEST_EMPTY_" . time();
$insSql = "INSERT INTO st_student_master (
    academic_year_id, registration_no, roll_no, class_id, division_id, department_id,
    specialization_id, specialization_subject_id, current_semester_id, cgpa, fname,
    mobile, email, status, created_at
) VALUES (
    1, '$testReg', 'TEST-EMPTY-01', 1, 1, 1,
    1, 1, 5, 8.50, 'Empty Progression Student',
    '9999999990', 'empty@tcet.local', 1, NOW()
)";
$conn->query($insSql);
$emptyStudentId = $conn->insert_id;

// Verify getStudentAcademicHistory returns empty array
$emptyHistory = $db_handle->getStudentAcademicHistory($emptyStudentId);
assertProgression(is_array($emptyHistory) && count($emptyHistory) === 0, "getStudentAcademicHistory returns empty array for student with 0 records");

// Render student_view.php with output buffering
$_REQUEST['id'] = $emptyStudentId;
ob_start();
include __DIR__ . '/../admin/student_view.php';
$outputEmpty = ob_get_clean();

assertProgression(strpos($outputEmpty, 'No semester progression records are available for this student.') !== false, "student_view.php displays clean empty state when 0 records exist");
assertProgression(strpos($outputEmpty, 'SEM III') === false, "student_view.php does NOT fabricate SEM III when 0 records exist");
assertProgression(strpos($outputEmpty, 'SEM IV') === false, "student_view.php does NOT fabricate SEM IV when 0 records exist");
assertProgression(strpos($outputEmpty, 'SEM VI') === false, "student_view.php does NOT fabricate SEM VI when 0 records exist");

// Clean up empty test student
$conn->query("DELETE FROM st_student_master WHERE student_id = $emptyStudentId");

// 3. Test Rendering for a Student with Actual History Records (e.g. Student #1)
$realHistory = $db_handle->getStudentAcademicHistory(1);
assertProgression(!empty($realHistory), "Student #1 has real academic history in database (" . count($realHistory) . " records)");

$_REQUEST['id'] = 1;
ob_start();
include __DIR__ . '/../admin/student_view.php';
$outputReal = ob_get_clean();

assertProgression(strpos($outputReal, 'Academic Progression & Semester History') !== false, "student_view.php contains Academic Progression & Semester History section");
foreach ($realHistory as $rh) {
    assertProgression(strpos($outputReal, htmlspecialchars($rh['semester_name'])) !== false, "student_view.php displays verified semester: " . $rh['semester_name']);
}

// 4. Test Student Dashboard Access Scoping & Identity Security
// Verify that student_dashboard.php uses session userid
$dashContent = $filesToCheck['admin/student_dashboard.php'];
assertProgression(strpos($dashContent, 'usertype ?? 0') !== false, "student_dashboard.php checks student role (Role 5)");
assertProgression(strpos($dashContent, 'WHERE s.student_id = ?') !== false, "student_dashboard.php prepares scoped query with parameter binding");

echo PHP_EOL . "=======================================================" . PHP_EOL;
echo "PROGRESSION CONSISTENCY SUMMARY: Passed = $passCount, Failed = $failCount" . PHP_EOL;
echo "=======================================================" . PHP_EOL;
