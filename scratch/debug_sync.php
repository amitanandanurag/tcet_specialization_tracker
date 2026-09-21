<?php
require_once __DIR__ . "/../database/db_connect.php";
$db = new DBController();
$conn = $db->conn;

$subRes = mysqli_query($conn, "SELECT subject_id, specialization_id FROM st_specialization_subject_master LIMIT 1");
$subRow = mysqli_fetch_assoc($subRes);

$testRegNo = "TEST_DBG_" . time();
$mockInsertSql = "INSERT INTO st_student_master (
    academic_year_id, registration_no, roll_no, class_id, division_id, department_id,
    specialization_id, specialization_subject_id, current_semester_id, cgpa, fname,
    mobile, email, status, created_at
) VALUES (
    1, '$testRegNo', 'TEST-01', 1, 1, 1,
    {$subRow['specialization_id']}, {$subRow['subject_id']}, 5, 8.50, 'Progression Test Student',
    '9999999999', 'progtest@tcetmumbai.in', 1, NOW()
)";

mysqli_query($conn, $mockInsertSql);
$stId = mysqli_insert_id($conn);
echo "Created student #$stId\n";

$res = $db->syncStudentSemesterHistory($stId, 5);
echo "syncStudentSemesterHistory returned: " . var_export($res, true) . "\n";
if (!$res) {
    echo "MySQL error: " . mysqli_error($conn) . "\n";
}

$history = $db->getStudentAcademicHistory($stId);
echo "Academic history count: " . count($history) . "\n";
print_r($history);

// Cleanup
mysqli_query($conn, "DELETE FROM st_student_semester_history WHERE student_id = $stId");
mysqli_query($conn, "DELETE FROM st_student_master WHERE student_id = $stId");
