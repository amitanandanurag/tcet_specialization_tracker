<?php
require_once __DIR__ . "/../database/db_connect.php";

$db = new DBController();
$conn = $db->conn;

echo "=== TCET ERP ACADEMIC PROGRESSION & MENTOR HISTORY VERIFICATION SUITE ===\n\n";

$passed = 0;
$failed = 0;

function assertCondition($name, $condition, $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] $name\n";
        $passed++;
    } else {
        echo "[FAIL] $name" . ($details ? " - $details" : "") . "\n";
        $failed++;
    }
}

// -------------------------------------------------------------
// TEST 1: Schema Integrity Check
// -------------------------------------------------------------
echo "Test 1: Schema Integrity Check\n";
$colHistRes = mysqli_query($conn, "SHOW COLUMNS FROM st_student_semester_history");
$histCols = [];
while ($row = mysqli_fetch_assoc($colHistRes)) {
    $histCols[] = $row['Field'];
}
assertCondition("st_student_semester_history has roll_no", in_array('roll_no', $histCols));
assertCondition("st_student_semester_history has department_id", in_array('department_id', $histCols));

$colMapRes = mysqli_query($conn, "SHOW COLUMNS FROM st_mentor_subject_mapping");
$mapCols = [];
while ($row = mysqli_fetch_assoc($colMapRes)) {
    $mapCols[] = $row['Field'];
}
assertCondition("st_mentor_subject_mapping has semester_id", in_array('semester_id', $mapCols));
assertCondition("st_mentor_subject_mapping has academic_year_id", in_array('academic_year_id', $mapCols));

$idxRes = mysqli_query($conn, "SHOW INDEX FROM st_mentor_subject_mapping WHERE Key_name = 'unique_period_subject_mentor'");
assertCondition("st_mentor_subject_mapping has composite unique index unique_period_subject_mentor", mysqli_num_rows($idxRes) > 0);


// -------------------------------------------------------------
// TEST 2: Active Student Academic History Retrieval
// -------------------------------------------------------------
echo "\nTest 2: Verification of Existing Student Academic History\n";
$firstStudentRes = mysqli_query($conn, "SELECT student_id, fname, registration_no, current_semester_id FROM st_student_master LIMIT 1");
if ($firstStudentRes && ($sRow = mysqli_fetch_assoc($firstStudentRes))) {
    $testStudentId = intval($sRow['student_id']);
    $history = $db->getStudentAcademicHistory($testStudentId);
    assertCondition("Student #{$testStudentId} ({$sRow['fname']}) has academic history records", count($history) > 0, "Found " . count($history) . " history records");
    
    if (count($history) > 0) {
        $firstHistory = $history[0];
        assertCondition("History contains resolved semester_name", !empty($firstHistory['semester_name']));
        assertCondition("History contains resolved mentor_name", !empty($firstHistory['mentor_name']));
        assertCondition("History contains resolved specialization_name", !empty($firstHistory['specialization_name']));
    }
}


// -------------------------------------------------------------
// TEST 3: Period-Aware Subject Mentor Isolation
// -------------------------------------------------------------
echo "\nTest 3: Period-Aware Subject Mentor Isolation\n";

// Find 2 distinct mentors
$mentorsRes = mysqli_query($conn, "SELECT user_id, user_name FROM st_user_master WHERE role_id = 4 ORDER BY user_id ASC LIMIT 2");
$mentors = [];
while ($m = mysqli_fetch_assoc($mentorsRes)) {
    $mentors[] = $m;
}

// Find a test subject
$subRes = mysqli_query($conn, "SELECT subject_id, subject_name FROM st_specialization_subject_master LIMIT 1");
$testSub = mysqli_fetch_assoc($subRes);

if (count($mentors) >= 2 && $testSub) {
    $subjectId = intval($testSub['subject_id']);
    $mentor1Id = intval($mentors[0]['user_id']);
    $mentor2Id = intval($mentors[1]['user_id']);
    
    // Assign Mentor 1 to SEM 5
    $db->assignSubjectMentor($subjectId, $mentor1Id, 1, 5, 1);
    // Assign Mentor 2 to SEM 6
    $db->assignSubjectMentor($subjectId, $mentor2Id, 1, 6, 1);
    
    $resolvedSem5 = $db->getResolvedMentorForSubject($subjectId, 5, 1);
    $resolvedSem6 = $db->getResolvedMentorForSubject($subjectId, 6, 1);
    
    assertCondition("SEM V resolves to Mentor 1 (#{$mentor1Id})", $resolvedSem5 && intval($resolvedSem5['mentor_id']) === $mentor1Id);
    assertCondition("SEM VI resolves to Mentor 2 (#{$mentor2Id})", $resolvedSem6 && intval($resolvedSem6['mentor_id']) === $mentor2Id);
    assertCondition("SEM V and SEM VI have distinct isolated mentors for the same subject", $resolvedSem5['mentor_id'] !== $resolvedSem6['mentor_id']);
}


// -------------------------------------------------------------
// TEST 4: Student Academic Promotion & Non-Destructive Progression
// -------------------------------------------------------------
echo "\nTest 4: Non-Destructive Student Promotion\n";

$testRegNo = "TEST_PROG_" . time();
$sub1 = intval($testSub['subject_id'] ?? 1);

$mockInsertSql = "INSERT INTO st_student_master (
    academic_year_id, registration_no, roll_no, class_id, division_id, department_id,
    specialization_id, specialization_subject_id, current_semester_id, cgpa, fname,
    mobile, email, status, created_at
) VALUES (
    1, '$testRegNo', 'TEST-01', 1, 1, 1,
    1, $sub1, 5, 8.50, 'Progression Test Student',
    '9999999999', 'progtest@tcetmumbai.in', 1, NOW()
)";

if (mysqli_query($conn, $mockInsertSql)) {
    $mockStudentId = mysqli_insert_id($conn);
    
    // Initial history for SEM V
    $db->syncStudentSemesterHistory($mockStudentId, 5);
    
    $initialHistory = $db->getStudentAcademicHistory($mockStudentId);
    assertCondition("Initial SEM V history created", count($initialHistory) === 1);
    if (count($initialHistory) > 0) {
        assertCondition("Initial record is Semester 5", intval($initialHistory[0]['semester_id']) === 5);
        assertCondition("Initial record status is Active", $initialHistory[0]['history_status'] === 'Active');
        assertCondition("Initial record roll_no is TEST-01", $initialHistory[0]['roll_no'] === 'TEST-01');
    }
    
    // Pick subject 2 for SEM VI
    $newSubRes = mysqli_query($conn, "SELECT subject_id FROM st_specialization_subject_master WHERE subject_id != $sub1 LIMIT 1");
    $newSubRow = mysqli_fetch_assoc($newSubRes);
    $newSubjectId = $newSubRow ? intval($newSubRow['subject_id']) : 2;
    
    // Promote student to SEM VI
    $promoted = $db->promoteStudentSemester(
        $mockStudentId,
        6,               // newSemesterId
        1,               // newAcademicYearId
        1,               // newClassId
        1,               // newDivisionId
        'TEST-01-SEM6',  // newRollNo
        1,               // newDepartmentId
        1,               // newSpecializationId
        $newSubjectId,   // newSubjectId
        8.75             // newCgpa
    );
    
    assertCondition("promoteStudentSemester executed successfully", $promoted === true);
    
    // Fetch updated history
    $promotedHistory = $db->getStudentAcademicHistory($mockStudentId);
    assertCondition("Student now has 2 historical semester entries", count($promotedHistory) === 2);
    
    // Check that SEM V history is preserved and marked Completed
    $sem5Found = false;
    $sem6Found = false;
    foreach ($promotedHistory as $h) {
        if (intval($h['semester_id']) === 5) {
            $sem5Found = true;
            assertCondition("SEM V record is preserved with Completed status", $h['history_status'] === 'Completed');
            assertCondition("SEM V preserved original roll no TEST-01", $h['roll_no'] === 'TEST-01');
            assertCondition("SEM V preserved original subject ID {$sub1}", intval($h['specialization_subject_id']) === $sub1);
        }
        if (intval($h['semester_id']) === 6) {
            $sem6Found = true;
            assertCondition("SEM VI record is created with Active status", $h['history_status'] === 'Active');
            assertCondition("SEM VI has new roll no TEST-01-SEM6", $h['roll_no'] === 'TEST-01-SEM6');
            assertCondition("SEM VI has new subject ID {$newSubjectId}", intval($h['specialization_subject_id']) === $newSubjectId);
        }
    }
    assertCondition("Both SEM V and SEM VI records verified in historical ledger", $sem5Found && $sem6Found);
    
    // Verify st_student_master current_semester_id updated to 6
    $masterCheck = mysqli_query($conn, "SELECT current_semester_id, cgpa, roll_no, specialization_subject_id FROM st_student_master WHERE student_id = $mockStudentId");
    $masterRow = mysqli_fetch_assoc($masterCheck);
    assertCondition("st_student_master current_semester_id is now 6", intval($masterRow['current_semester_id']) === 6);
    assertCondition("st_student_master roll_no is now TEST-01-SEM6", $masterRow['roll_no'] === 'TEST-01-SEM6');
    assertCondition("st_student_master subject is now {$newSubjectId}", intval($masterRow['specialization_subject_id']) === $newSubjectId);
    
    // Clean up test student
    mysqli_query($conn, "DELETE FROM st_student_semester_history WHERE student_id = $mockStudentId");
    mysqli_query($conn, "DELETE FROM st_student_master WHERE student_id = $mockStudentId");
    echo "Cleaned up test student #{$mockStudentId}.\n";
} else {
    echo "[FAIL] Could not insert mock student for promotion test: " . mysqli_error($conn) . "\n";
    $failed++;
}


// -------------------------------------------------------------
// TEST 5: Period Mentor Dynamic Resolution for Student
// -------------------------------------------------------------
echo "\nTest 5: Student Dynamic Mentor Resolution (Past vs Current)\n";
$resolvedMentorForStudent = $db->getResolvedMentorForStudent(1);
assertCondition("getResolvedMentorForStudent returns structured mentor array or null", is_array($resolvedMentorForStudent) || is_null($resolvedMentorForStudent));


// -------------------------------------------------------------
// TEST 6: Audit Log Capture on Academic Promotion
// -------------------------------------------------------------
echo "\nTest 6: Audit Log Recording on Student Progression\n";
$auditRes = mysqli_query($conn, "SELECT action_type, description FROM st_audit_log WHERE action_type = 'STUDENT_PROMOTED_SEMESTER' ORDER BY audit_id DESC LIMIT 1");
if ($auditRes && ($aRow = mysqli_fetch_assoc($auditRes))) {
    assertCondition("Audit log contains STUDENT_PROMOTED_SEMESTER record", true, $aRow['description']);
} else {
    assertCondition("Audit log contains STUDENT_PROMOTED_SEMESTER record", false, "No audit entry found");
}

echo "\n=======================================================\n";
echo "TEST RESULTS: Total = " . ($passed + $failed) . ", Passed = $passed, Failed = $failed\n";
echo "=======================================================\n";
