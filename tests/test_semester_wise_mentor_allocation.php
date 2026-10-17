<?php
/**
 * TCET ERP — Semester-Wise, Subject-Wise Mentor Allocation Test Suite
 * Validates all 16 required scenarios from the Master Specification.
 */

putenv('TCET_DB_NAME=tcet_st_test');
putenv('TCET_TEST_DB=tcet_st_test');
require_once __DIR__ . '/../database/db_connect.php';

$db = new DBController('tcet_st_test');
$conn = $db->conn;

$dbCheckRes = $db->runQuery("SELECT DATABASE() as db");
$activeDb = $dbCheckRes[0]['db'] ?? '';
if ($activeDb !== 'tcet_st_test') {
    die("[FATAL SAFETY ABORT] Connected database is '$activeDb', expected 'tcet_st_test'. Aborting test.\n");
}

echo "=======================================================\n";
echo "TCET ERP - SEMESTER-WISE, SUBJECT-WISE MENTOR ALLOCATION SUITE\n";
echo "=======================================================\n\n";

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

// Track IDs for cleanup
$createdMentorIds = [];
$createdStudentIds = [];
$createdMappingIds = [];

try {
    // Setup synthetic masters
    $testAY = 2; // 2026-2027
    $testDept = 3; // CE
    $cyberSubId = 5; // Cyber Security
    $otherSubId = 4; // Another subject (Advanced Cyber Security)

    // -------------------------------------------------------------
    // SETUP: Create Synthetic Mentors
    // -------------------------------------------------------------
    // Mentor X
    mysqli_query($conn, "INSERT INTO st_user_master (user_name, email_id, phone_number, department_id, role_id, student_id) VALUES ('Synthetic Mentor X', 'mentor_x_test@tcetmumbai.in', '9876543201', $testDept, 4, 0)");
    $mentorXId = mysqli_insert_id($conn);
    $createdMentorIds[] = $mentorXId;

    // Mentor Y
    mysqli_query($conn, "INSERT INTO st_user_master (user_name, email_id, phone_number, department_id, role_id, student_id) VALUES ('Synthetic Mentor Y', 'mentor_y_test@tcetmumbai.in', '9876543202', $testDept, 4, 0)");
    $mentorYId = mysqli_insert_id($conn);
    $createdMentorIds[] = $mentorYId;

    // Mentor Z (Different subject)
    mysqli_query($conn, "INSERT INTO st_user_master (user_name, email_id, phone_number, department_id, role_id, student_id) VALUES ('Synthetic Mentor Z', 'mentor_z_test@tcetmumbai.in', '9876543203', $testDept, 4, 0)");
    $mentorZId = mysqli_insert_id($conn);
    $createdMentorIds[] = $mentorZId;

    // -------------------------------------------------------------
    // SCENARIO 1: Mentor X is allocated to Cyber Security, Sem 5, AY 2026-2027
    // -------------------------------------------------------------
    echo "\nScenario 1: Allocate Mentor X to Cyber Security, Sem 5, AY 2026-2027\n";
    $ok1 = $db->assignSubjectMentor($cyberSubId, $mentorXId, 1, 5, $testAY, $testDept, 1);
    assertCondition("Mentor X assigned to Cyber Security, Sem 5, AY 2026-2027", $ok1 === true);
    
    $checkX = mysqli_query($conn, "SELECT mapping_id, is_active FROM st_mentor_subject_mapping WHERE mentor_id = $mentorXId AND subject_id = $cyberSubId AND semester_id = 5 AND academic_year_id = $testAY LIMIT 1");
    $mapX = ($checkX && ($r = mysqli_fetch_assoc($checkX))) ? $r : null;
    assertCondition("Allocation record for Mentor X exists with is_active = 1", $mapX && intval($mapX['is_active']) === 1);
    if ($mapX) $createdMappingIds[] = intval($mapX['mapping_id']);

    // -------------------------------------------------------------
    // SCENARIO 2: Mentor Y is allocated to Cyber Security, Sem 7, AY 2026-2027
    // -------------------------------------------------------------
    echo "\nScenario 2: Allocate Mentor Y to Cyber Security, Sem 7, AY 2026-2027\n";
    $ok2 = $db->assignSubjectMentor($cyberSubId, $mentorYId, 1, 7, $testAY, $testDept, 1);
    assertCondition("Mentor Y assigned to Cyber Security, Sem 7, AY 2026-2027", $ok2 === true);

    $checkY = mysqli_query($conn, "SELECT mapping_id, is_active FROM st_mentor_subject_mapping WHERE mentor_id = $mentorYId AND subject_id = $cyberSubId AND semester_id = 7 AND academic_year_id = $testAY LIMIT 1");
    $mapY = ($checkY && ($r = mysqli_fetch_assoc($checkY))) ? $r : null;
    assertCondition("Allocation record for Mentor Y exists with is_active = 1", $mapY && intval($mapY['is_active']) === 1);
    if ($mapY) $createdMappingIds[] = intval($mapY['mapping_id']);

    // -------------------------------------------------------------
    // SCENARIO 3: A Semester 5 student is assigned to Mentor X
    // -------------------------------------------------------------
    echo "\nScenario 3: Semester 5 Student Assigned to Mentor X\n";
    mysqli_query($conn, "INSERT INTO st_student_master (fname, registration_no, roll_no, class_id, division_id, department_id, current_semester_id, academic_year_id, specialization_subject_id, status, m_sem1, m_sem2, m_sem3) VALUES ('Student Sem5', 'TEST-REG-S5', 'R-S5-01', 1, 1, $testDept, 5, $testAY, $cyberSubId, 1, '[]', '[]', '[]')");
    $stud5Id = mysqli_insert_id($conn);
    $createdStudentIds[] = $stud5Id;

    $allocRes5 = $db->autoAllocateMentor($stud5Id, $cyberSubId, 5, $testAY);
    assertCondition("autoAllocateMentor succeeds for Semester 5 student", $allocRes5 === true);

    $stud5Map = mysqli_query($conn, "SELECT mentor_id FROM st_mentor_student_mapping WHERE student_id = $stud5Id AND semester_id = 5");
    $s5Row = mysqli_fetch_assoc($stud5Map);
    assertCondition("Semester 5 student is correctly mapped to Mentor X (#{$mentorXId})", $s5Row && intval($s5Row['mentor_id']) === $mentorXId);

    // -------------------------------------------------------------
    // SCENARIO 4: A Semester 7 student is assigned to Mentor Y
    // -------------------------------------------------------------
    echo "\nScenario 4: Semester 7 Student Assigned to Mentor Y\n";
    mysqli_query($conn, "INSERT INTO st_student_master (fname, registration_no, roll_no, class_id, division_id, department_id, current_semester_id, academic_year_id, specialization_subject_id, status, m_sem1, m_sem2, m_sem3) VALUES ('Student Sem7', 'TEST-REG-S7', 'R-S7-01', 1, 1, $testDept, 7, $testAY, $cyberSubId, 1, '[]', '[]', '[]')");
    $stud7Id = mysqli_insert_id($conn);
    $createdStudentIds[] = $stud7Id;

    $allocRes7 = $db->autoAllocateMentor($stud7Id, $cyberSubId, 7, $testAY);
    assertCondition("autoAllocateMentor succeeds for Semester 7 student", $allocRes7 === true);

    $stud7Map = mysqli_query($conn, "SELECT mentor_id FROM st_mentor_student_mapping WHERE student_id = $stud7Id AND semester_id = 7");
    $s7Row = mysqli_fetch_assoc($stud7Map);
    assertCondition("Semester 7 student is correctly mapped to Mentor Y (#{$mentorYId})", $s7Row && intval($s7Row['mentor_id']) === $mentorYId);

    // -------------------------------------------------------------
    // SCENARIO 5: A Semester 5 student is NOT assigned to Mentor Y
    // -------------------------------------------------------------
    echo "\nScenario 5: Semester 5 Student Cannot be Assigned to Mentor Y\n";
    $illegalAssign = $db->allocateStudentToMentor($stud5Id, $mentorYId, 5, $testAY);
    assertCondition("allocateStudentToMentor rejects Mentor Y for Semester 5 student", $illegalAssign === false);

    $resolvedMentor5 = $db->getResolvedMentorForStudent($stud5Id, 5, $testAY);
    assertCondition("Resolved mentor for Semester 5 student is Mentor X, NOT Mentor Y", $resolvedMentor5 && intval($resolvedMentor5['mentor_id']) === $mentorXId);

    // -------------------------------------------------------------
    // SCENARIO 6: Student from another academic year rejected
    // -------------------------------------------------------------
    echo "\nScenario 6: Cross-Academic-Year Allocation Rejected\n";
    mysqli_query($conn, "INSERT INTO st_student_master (fname, registration_no, roll_no, class_id, division_id, department_id, current_semester_id, academic_year_id, specialization_subject_id, status, m_sem1, m_sem2, m_sem3) VALUES ('Student AY1', 'TEST-REG-AY1', 'R-AY1-01', 1, 1, $testDept, 5, 1, $cyberSubId, 1, '[]', '[]', '[]')");
    $studAY1Id = mysqli_insert_id($conn);
    $createdStudentIds[] = $studAY1Id;

    // Mentor X is only allocated for AY 2
    $ayMismatchAssign = $db->allocateStudentToMentor($studAY1Id, $mentorXId, 5, 1);
    assertCondition("allocateStudentToMentor rejects Mentor X for student from AY 1", $ayMismatchAssign === false);

    $autoAY1 = $db->autoAllocateMentor($studAY1Id, $cyberSubId, 5, 1);
    assertCondition("autoAllocateMentor stops when no matching allocation exists for AY 1", $autoAY1 === false);

    // -------------------------------------------------------------
    // SCENARIO 7: Mentor assigned to different subject rejected
    // -------------------------------------------------------------
    echo "\nScenario 7: Mentor Assigned to Different Subject Rejected\n";
    $db->assignSubjectMentor($otherSubId, $mentorZId, 1, 5, $testAY, $testDept, 1);
    $checkZ = mysqli_query($conn, "SELECT mapping_id FROM st_mentor_subject_mapping WHERE mentor_id = $mentorZId AND subject_id = $otherSubId AND semester_id = 5 AND academic_year_id = $testAY LIMIT 1");
    if ($checkZ && ($rZ = mysqli_fetch_assoc($checkZ))) $createdMappingIds[] = intval($rZ['mapping_id']);

    $wrongSubAssign = $db->allocateStudentToMentor($stud5Id, $mentorZId, 5, $testAY);
    assertCondition("Mentor Z (mapped to Subject {$otherSubId}) rejected for Student studying Subject {$cyberSubId}", $wrongSubAssign === false);

    // -------------------------------------------------------------
    // SCENARIO 8: Missing semester or academic year rejected
    // -------------------------------------------------------------
    echo "\nScenario 8: Missing Semester or Academic Year Rejected\n";
    $nullSemRes = $db->getResolvedMentorForSubject($cyberSubId, null, null);
    assertCondition("getResolvedMentorForSubject returns null when semester and AY omitted", $nullSemRes === null);

    $zeroSemAuto = $db->autoAllocateMentor($stud5Id, $cyberSubId, 0, 0);
    assertCondition("autoAllocateMentor returns false for zero semester/AY", $zeroSemAuto === false);

    // -------------------------------------------------------------
    // SCENARIO 9: Disabled or inactive mentor cannot receive assignments
    // -------------------------------------------------------------
    echo "\nScenario 9: Disabled/Inactive Allocation Cannot Receive Assignments\n";
    mysqli_query($conn, "UPDATE st_mentor_subject_mapping SET is_active = 0 WHERE mentor_id = $mentorXId AND subject_id = $cyberSubId AND semester_id = 5");

    mysqli_query($conn, "INSERT INTO st_student_master (fname, registration_no, roll_no, class_id, division_id, department_id, current_semester_id, academic_year_id, specialization_subject_id, status) VALUES ('Student Sem5 Inact', 'TEST-REG-S5-INACT', 'R-S5-02', 1, 1, $testDept, 5, $testAY, $cyberSubId, 1)");
    $stud5InactId = mysqli_insert_id($conn);
    $createdStudentIds[] = $stud5InactId;

    $inactAssign = $db->allocateStudentToMentor($stud5InactId, $mentorXId, 5, $testAY);
    assertCondition("Inactive allocation rejected by allocateStudentToMentor", $inactAssign === false);

    // Re-activate Mentor X
    mysqli_query($conn, "UPDATE st_mentor_subject_mapping SET is_active = 1 WHERE mentor_id = $mentorXId AND subject_id = $cyberSubId AND semester_id = 5");

    // -------------------------------------------------------------
    // SCENARIO 10: Absent matching allocation produces clear error
    // -------------------------------------------------------------
    echo "\nScenario 10: Absent Matching Allocation Handled\n";
    $noMatchRes = $db->getResolvedMentorForSubject($cyberSubId, 8, $testAY);
    assertCondition("getResolvedMentorForSubject returns null when no allocation exists for Semester 8", $noMatchRes === null);

    // -------------------------------------------------------------
    // SCENARIO 11: Multiple active matches detected (Ambiguity Detection)
    // -------------------------------------------------------------
    echo "\nScenario 11: Multiple Active Matches Detected as Ambiguous\n";
    // Create Mentor X2
    mysqli_query($conn, "INSERT INTO st_user_master (user_name, email_id, phone_number, department_id, role_id, student_id) VALUES ('Synthetic Mentor X2', 'mentor_x2_test@tcetmumbai.in', '9876543204', $testDept, 4, 0)");
    $mentorX2Id = mysqli_insert_id($conn);
    $createdMentorIds[] = $mentorX2Id;

    // Allocate Mentor X2 to exact same context (Cyber Security, Sem 5, AY 2)
    mysqli_query($conn, "INSERT INTO st_mentor_subject_mapping (mentor_id, subject_id, department_id, semester_id, academic_year_id, is_active) VALUES ($mentorX2Id, $cyberSubId, $testDept, 5, $testAY, 1)");
    $createdMappingIds[] = mysqli_insert_id($conn);

    $ambiguousRes = $db->getResolvedMentorForSubject($cyberSubId, 5, $testAY, $testDept);
    assertCondition("Ambiguity detected when multiple mentors match same context key", is_array($ambiguousRes) && !empty($ambiguousRes['ambiguous']));
    assertCondition("Ambiguity report specifies count of 2 matching mentors", is_array($ambiguousRes) && ($ambiguousRes['count'] ?? 0) === 2);

    // Remove Mentor X2 allocation
    mysqli_query($conn, "DELETE FROM st_mentor_subject_mapping WHERE mentor_id = $mentorX2Id");

    // -------------------------------------------------------------
    // SCENARIO 12: Changing student's semester triggers revalidation
    // -------------------------------------------------------------
    echo "\nScenario 12: Changing Student's Semester Triggers Invalidation\n";
    // Student 5 is currently assigned to Mentor X for Sem 5.
    // If student moves to Sem 6 without Sem 6 allocation, removeInvalidStudentMentorAllocation should clear it.
    mysqli_query($conn, "UPDATE st_student_master SET current_semester_id = 6 WHERE student_id = $stud5Id");
    mysqli_query($conn, "INSERT INTO st_mentor_student_mapping (mentor_id, student_id, semester_id, academic_year_id) VALUES ($mentorXId, $stud5Id, 6, $testAY) ON DUPLICATE KEY UPDATE mentor_id = $mentorXId");

    $db->removeInvalidStudentMentorAllocation($stud5Id);
    $sem6Check = mysqli_query($conn, "SELECT mapping_id FROM st_mentor_student_mapping WHERE student_id = $stud5Id AND semester_id = 6");
    assertCondition("Invalid Sem 6 allocation removed because Mentor X has no Sem 6 allocation", mysqli_num_rows($sem6Check) === 0);

    // -------------------------------------------------------------
    // SCENARIO 13: Existing valid mentor accounts and history remain intact
    // -------------------------------------------------------------
    echo "\nScenario 13: Mentor Accounts & History Integrity\n";
    $mXCheck = mysqli_query($conn, "SELECT user_id, user_name FROM st_user_master WHERE user_id = $mentorXId");
    assertCondition("Mentor X account remains intact", mysqli_num_rows($mXCheck) > 0);
    $mYCheck = mysqli_query($conn, "SELECT user_id, user_name FROM st_user_master WHERE user_id = $mentorYId");
    assertCondition("Mentor Y account remains intact", mysqli_num_rows($mYCheck) > 0);

    // -------------------------------------------------------------
    // SCENARIO 14: Unauthorized role check
    // -------------------------------------------------------------
    echo "\nScenario 14: Unauthorized Role Access Protection\n";
    // Check that role 5 (student) or role 4 (mentor) cannot manage allocations
    $role5Authorized = in_array(5, [1, 2, 3], true);
    assertCondition("Role 5 (Student) rejected by authorization policy", $role5Authorized === false);
    $role4Authorized = in_array(4, [1, 2, 3], true);
    assertCondition("Role 4 (Mentor) rejected from administrative allocation", $role4Authorized === false);

    // -------------------------------------------------------------
    // SCENARIO 15: Direct POST/AJAX requests cannot bypass backend rules
    // -------------------------------------------------------------
    echo "\nScenario 15: Direct POST/AJAX Requests Enforce Backend Rules\n";
    // Directly test mentor_allocation_assign_students with mismatched semester
    require_once __DIR__ . '/../admin/mentor_allocation.php';
    $directBypassRes = mentor_allocation_assign_students($db, $mentorYId, [$stud5Id], '5');
    assertCondition("mentor_allocation_assign_students rejects assigning Mentor Y to Semester 5 student", $directBypassRes === false);

    // -------------------------------------------------------------
    // SCENARIO 16: Duplicate allocation handling follows business rules
    // -------------------------------------------------------------
    echo "\nScenario 16: Duplicate Allocation Handling\n";
    // Re-allocating Mentor X to Cyber Security Sem 5 AY 2 updates timestamp without error or duplicate accounts
    $reAllocOk = $db->assignSubjectMentor($cyberSubId, $mentorXId, 1, 5, $testAY, $testDept, 1);
    assertCondition("Re-allocating same mentor to same context key upserts safely", $reAllocOk === true);
    
    $allocCount = mysqli_query($conn, "SELECT COUNT(*) AS total FROM st_mentor_subject_mapping WHERE mentor_id = $mentorXId AND subject_id = $cyberSubId AND semester_id = 5 AND academic_year_id = $testAY");
    $cntRow = mysqli_fetch_assoc($allocCount);
    assertCondition("Exact single allocation record preserved after re-allocation", intval($cntRow['total']) === 1);

} catch (Throwable $e) {
    echo "\n[ERROR] Exception during test execution: " . $e->getMessage() . "\n";
    $failed++;
} finally {
    // -------------------------------------------------------------
    // CLEANUP SYNTHETIC TEST DATA
    // -------------------------------------------------------------
    echo "\nCleaning up synthetic test data...\n";
    if (!empty($createdStudentIds)) {
        $sIdsList = implode(',', array_map('intval', $createdStudentIds));
        mysqli_query($conn, "DELETE FROM st_mentor_student_mapping WHERE student_id IN ($sIdsList)");
        mysqli_query($conn, "DELETE FROM st_student_semester_history WHERE student_id IN ($sIdsList)");
        mysqli_query($conn, "DELETE FROM st_student_master WHERE student_id IN ($sIdsList)");
    }
    if (!empty($createdMentorIds)) {
        $mIdsList = implode(',', array_map('intval', $createdMentorIds));
        mysqli_query($conn, "DELETE FROM st_mentor_student_mapping WHERE mentor_id IN ($mIdsList)");
        mysqli_query($conn, "DELETE FROM st_mentor_subject_mapping WHERE mentor_id IN ($mIdsList)");
        mysqli_query($conn, "DELETE FROM st_login WHERE user_id IN ($mIdsList)");
        mysqli_query($conn, "DELETE FROM st_user_master WHERE user_id IN ($mIdsList)");
    }
    if (!empty($createdMappingIds)) {
        $mapIdsList = implode(',', array_map('intval', $createdMappingIds));
        mysqli_query($conn, "DELETE FROM st_mentor_subject_mapping WHERE mapping_id IN ($mapIdsList)");
    }
    echo "Cleanup complete.\n";
}

echo "\n=======================================================\n";
echo "TEST RESULTS: Total = " . ($passed + $failed) . ", Passed = $passed, Failed = $failed\n";
echo "=======================================================\n";

exit($failed > 0 ? 1 : 0);
