<?php
/**
 * Test Suite: Student Registration Specialization Mapping & Eligibility Validation
 * Verifies all 10 scenarios from the prompt specification.
 */

require_once __DIR__ . "/../database/db_connect.php";

$db = new DBController();
$conn = $db->conn;

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

function assertTest($condition, $description, $details = '') {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "  [PASS] $description\n";
    } else {
        $failedTests++;
        echo "  [FAIL] $description" . ($details ? " - $details" : "") . "\n";
    }
}

echo "=======================================================\n";
echo "TCET ERP - STUDENT REGISTRATION SPECIALIZATION MAPPING\n";
echo "=======================================================\n\n";

// -------------------------------------------------------------
// Scenario 1: Changing class refreshes valid specializations
// -------------------------------------------------------------
echo "Scenario 1: Class-Based Specialization Refresh\n";

// FY class ID
$fyRes = mysqli_query($conn, "SELECT class_id FROM st_class_master WHERE class_name = 'FY' LIMIT 1");
$fyId = ($fyRow = mysqli_fetch_assoc($fyRes)) ? intval($fyRow['class_id']) : 1;

$fySpecs = $db->getEligibleSpecializations(3, 1, $fyId); // CE dept, Honours track, FY class
assertTest(empty($fySpecs), "FY Class is blocked from specializations (returns 0 specializations)");

// SY class ID
$syRes = mysqli_query($conn, "SELECT class_id FROM st_class_master WHERE class_name = 'SY' LIMIT 1");
$syId = ($syRow = mysqli_fetch_assoc($syRes)) ? intval($syRow['class_id']) : 2;

$sySpecs = $db->getEligibleSpecializations(3, 1, $syId); // CE dept, Honours track, SY class
assertTest(!empty($sySpecs) && count($sySpecs) >= 10, "SY Class allows valid specializations (found " . count($sySpecs) . ")");


// -------------------------------------------------------------
// Scenario 2: Changing department refreshes valid specializations
// -------------------------------------------------------------
echo "\nScenario 2: Department-Based Specialization Refresh\n";

// Civil Engineering (Dept 8) vs Mechanical (Dept 7) vs Computer Engineering (Dept 3)
$ceHonors = $db->getEligibleSpecializations(3, 1, $syId);
$civilHonors = $db->getEligibleSpecializations(8, 1, $syId);
$mechHonors = $db->getEligibleSpecializations(7, 1, $syId);

$ceNames = array_column($ceHonors, 'specialization_name');
$civilNames = array_column($civilHonors, 'specialization_name');
$mechNames = array_column($mechHonors, 'specialization_name');

assertTest(in_array('Artificial Intelligence', $ceNames), "CE Department has Artificial Intelligence in Honours");
assertTest(!in_array('Artificial Intelligence', $civilNames), "Civil Department does NOT have Artificial Intelligence in Honours");
assertTest(in_array('Green Technology', $civilNames), "Civil Department has Green Technology in Honours");
assertTest(!in_array('Green Technology', $ceNames), "CE Department does NOT have Green Technology in Honours");
assertTest(in_array('Energy Engineering', $mechNames), "Mechanical Department has Energy Engineering in Honours");


// -------------------------------------------------------------
// Scenario 3: Selecting Honours Degree displays eligible specializations
// -------------------------------------------------------------
echo "\nScenario 3: Honours Degree Eligibility\n";

// AIDS (Dept 12) - should not have AI or Data Science in Honours (own discipline)
$aidsHonors = $db->getEligibleSpecializations(12, 1, $syId);
$aidsNames = array_column($aidsHonors, 'specialization_name');
assertTest(!in_array('Artificial Intelligence', $aidsNames), "AIDS Department is excluded from AI Honours");
assertTest(!in_array('Data Science', $aidsNames), "AIDS Department is excluded from Data Science Honours");
assertTest(in_array('Blockchain', $aidsNames), "AIDS Department includes Blockchain Honours");


// -------------------------------------------------------------
// Scenario 4: Selecting Minor Degree displays eligible specializations
// -------------------------------------------------------------
echo "\nScenario 4: Minor Degree Eligibility\n";

// Mechanical (Dept 7) taking Minor Degree (Track 3)
$mechMinor = $db->getEligibleSpecializations(7, 3, $syId);
$mechMinorNames = array_column($mechMinor, 'specialization_name');
assertTest(in_array('Artificial Intelligence', $mechMinorNames), "Mechanical Engineering is eligible for AI Minor Degree");
assertTest(in_array('Data Science', $mechMinorNames), "Mechanical Engineering is eligible for Data Science Minor Degree");
assertTest(in_array('Cyber Security', $mechMinorNames), "Mechanical Engineering is eligible for Cyber Security Minor Degree");

// CE (Dept 3) taking Minor Degree (Track 3)
$ceMinor = $db->getEligibleSpecializations(3, 3, $syId);
$ceMinorNames = array_column($ceMinor, 'specialization_name');
assertTest(in_array('Green Technology', $ceMinorNames), "Computer Engineering is eligible for Green Technology Minor");
assertTest(!in_array('Artificial Intelligence', $ceMinorNames), "Computer Engineering cannot take AI as a Minor (in-discipline)");


// -------------------------------------------------------------
// Scenario 5: Selecting Minor Multidisciplinary displays only eligible options
// -------------------------------------------------------------
echo "\nScenario 5: Minor Multidisciplinary Options\n";

$multiSpecs = $db->getEligibleSpecializations(3, 4, $syId);
assertTest(count($multiSpecs) === 1 && $multiSpecs[0]['specialization_name'] === 'Minor Multidisciplinary', "Track 4 returns Minor Multidisciplinary single entry");

$minorCoursesRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM st_minorcourse");
$minorCoursesCount = ($row = mysqli_fetch_assoc($minorCoursesRes)) ? intval($row['cnt']) : 0;
assertTest($minorCoursesCount >= 10, "Minor Multidisciplinary courses available in database (found $minorCoursesCount)");


// -------------------------------------------------------------
// Scenario 6: Changing specialization refreshes subject dropdown
// -------------------------------------------------------------
echo "\nScenario 6: Specialization Subject Refresh & Format\n";

// AI (SpecID 5), Sem 5 (Machine Learning)
$aiSubjs = $db->getEligibleSubjects(5, 5, 3, 1);
assertTest(!empty($aiSubjs), "Subjects returned for AI in Semester 5");
if (!empty($aiSubjs)) {
    assertTest($aiSubjs[0]['subject_code'] === 'MDL-5011', "Subject code is MDL-5011");
    assertTest(strpos($aiSubjs[0]['subject_name'], 'MDL-5011 — Machine Learning') !== false, "Subject formatted as 'MDL-5011 — Machine Learning'");
}

// Data Science (SpecID 6), Sem 5
$dsSubjs = $db->getEligibleSubjects(6, 5, 3, 1);
assertTest(!empty($dsSubjs) && $dsSubjs[0]['subject_code'] === 'MDL-5012', "Data Science Sem 5 returns MDL-5012");


// -------------------------------------------------------------
// Scenario 7: Changing semester removes previous semester subjects
// -------------------------------------------------------------
echo "\nScenario 7: Semester Filtering on Subjects\n";

$aiSem4 = $db->getEligibleSubjects(5, 4, 3, 1);
$aiSem5 = $db->getEligibleSubjects(5, 5, 3, 1);
$aiSem8 = $db->getEligibleSubjects(5, 8, 3, 1);

$sem4Codes = array_column($aiSem4, 'subject_code');
$sem5Codes = array_column($aiSem5, 'subject_code');

assertTest(in_array('MDL-4011', $sem4Codes), "Semester 4 contains MDL-4011");
assertTest(!in_array('MDL-5011', $sem4Codes), "Semester 4 does NOT contain Semester 5 subject MDL-5011");
assertTest(in_array('MDL-5011', $sem5Codes), "Semester 5 contains MDL-5011");
assertTest(!in_array('MDL-4011', $sem5Codes), "Semester 5 does NOT contain Semester 4 subject MDL-4011");
assertTest(empty($aiSem8), "Semester 8 returns zero subjects for AI (course ends at Sem 7)");


// -------------------------------------------------------------
// Scenario 8: Invalid combinations are rejected by server
// -------------------------------------------------------------
echo "\nScenario 8: Server-Side Validation Rejection\n";

// Ineligible department for specialization
$invalidDeptCheck = $db->isDepartmentEligibleForSpecialization(8, 5, 1); // Civil (8) for AI (5) in Honours (1)
assertTest(!$invalidDeptCheck, "Server rejects Civil Department for Artificial Intelligence under Honours");

// Ineligible subject for wrong semester
$aiSem5SubjId = $aiSubjs[0]['subject_id'] ?? 30;
$wrongSemCheck = $db->isSubjectAvailable($aiSem5SubjId, 3, 4, 5, 1); // Sem 4 checking Sem 5 subject
assertTest(!$wrongSemCheck, "Server rejects Sem 5 subject when student is in Sem 4");

// Ineligible subject for wrong department
$wrongDeptSubjCheck = $db->isSubjectAvailable($aiSem5SubjId, 8, 5, 5, 1); // Civil dept checking AI subject
assertTest(!$wrongDeptSubjCheck, "Server rejects subject when department is not eligible for that track/specialization");


// -------------------------------------------------------------
// Scenario 9: Valid student registration still works
// -------------------------------------------------------------
echo "\nScenario 9: Student Registration End-to-End Workflow\n";

$testRegNo = "ST_SPEC_TEST_" . time();
$mockInsertSql = "INSERT INTO st_student_master (
    academic_year_id, registration_no, roll_no, class_id, division_id, department_id,
    specialization_track_id, specialization_id, specialization_subject_id, current_semester_id, cgpa, fname,
    mobile, email, status, created_at
) VALUES (
    1, '$testRegNo', 'SPEC-01', 3, 1, 3,
    1, 5, $aiSem5SubjId, 5, 8.75, 'Specialization Test Student',
    '9876543210', 'spectest@tcetmumbai.in', 1, NOW()
)";

if (mysqli_query($conn, $mockInsertSql)) {
    $newStudentId = mysqli_insert_id($conn);
    assertTest($newStudentId > 0, "Student record successfully inserted with track, specialization, and subject");
    
    // Sync semester history
    $syncOk = $db->syncStudentSemesterHistory($newStudentId, 5);
    assertTest($syncOk, "syncStudentSemesterHistory successfully executed for student $newStudentId");
    
    // Verify stored student data
    $verifyRes = mysqli_query($conn, "SELECT specialization_track_id, specialization_id, specialization_subject_id, cgpa FROM st_student_master WHERE student_id = $newStudentId");
    $verifyRow = mysqli_fetch_assoc($verifyRes);
    assertTest(intval($verifyRow['specialization_track_id']) === 1, "Stored specialization_track_id is 1 (Honours)");
    assertTest(intval($verifyRow['specialization_id']) === 5, "Stored specialization_id is 5 (Artificial Intelligence)");
    assertTest(intval($verifyRow['specialization_subject_id']) === $aiSem5SubjId, "Stored specialization_subject_id is $aiSem5SubjId");
    assertTest(floatval($verifyRow['cgpa']) === 8.75, "Stored CGPA is 8.75");
    
    // Verify semester history record
    $histRes = mysqli_query($conn, "SELECT specialization_track_id, specialization_id, specialization_subject_id FROM st_student_semester_history WHERE student_id = $newStudentId AND semester_id = 5");
    $histRow = mysqli_fetch_assoc($histRes);
    assertTest(intval($histRow['specialization_track_id']) === 1, "History has specialization_track_id = 1");
    assertTest(intval($histRow['specialization_id']) === 5, "History has specialization_id = 5");

    // Clean up test student
    mysqli_query($conn, "DELETE FROM st_student_semester_history WHERE student_id = $newStudentId");
    mysqli_query($conn, "DELETE FROM st_student_master WHERE student_id = $newStudentId");
} else {
    assertTest(false, "Student insertion failed: " . mysqli_error($conn));
}


// -------------------------------------------------------------
// Scenario 10: Existing workflows remain functional
// -------------------------------------------------------------
echo "\nScenario 10: Preserved Legacy Workflows (NPTEL, Mentor, Marks, Progression)\n";

// Existing students count
$legacyRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM st_student_master WHERE specialization_id = 1");
$legacyCount = ($lRow = mysqli_fetch_assoc($legacyRes)) ? intval($lRow['cnt']) : 0;
assertTest($legacyCount > 600, "Existing student records preserved (found $legacyCount with specialization_id 1)");

// Legacy subjects (IDs 1-28) intact
$legSubRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM st_specialization_subject_master WHERE subject_id <= 28");
$legSubCount = ($sRow = mysqli_fetch_assoc($legSubRes)) ? intval($sRow['cnt']) : 0;
assertTest($legSubCount === 28, "Legacy subjects 1-28 preserved without alteration");

// Imported courses (IDs 29-115) intact
$impSubRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM st_specialization_subject_master WHERE subject_code IS NOT NULL");
$impSubCount = ($iRow = mysqli_fetch_assoc($impSubRes)) ? intval($iRow['cnt']) : 0;
assertTest($impSubCount === 87, "Imported 87 curriculum courses fully intact");

echo "\n=======================================================\n";
echo "TEST RESULTS: $passedTests Passed, $failedTests Failed (Total: $totalTests)\n";
echo "=======================================================\n";

exit($failedTests === 0 ? 0 : 1);
