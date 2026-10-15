<?php
/**
 * Test Suite: Honours with Research Eligibility Verification
 * Verifies all 8 test cases from FIX: HONOURS WITH RESEARCH ELIGIBILITY specification.
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
echo "TCET ERP - HONOURS WITH RESEARCH ELIGIBILITY TEST SUITE\n";
echo "=======================================================\n\n";

// Class IDs
$beRes = mysqli_query($conn, "SELECT class_id FROM st_class_master WHERE class_name = 'BE' LIMIT 1");
$beClassId = ($beRow = mysqli_fetch_assoc($beRes)) ? intval($beRow['class_id']) : 7;

$teRes = mysqli_query($conn, "SELECT class_id FROM st_class_master WHERE class_name = 'TE' LIMIT 1");
$teClassId = ($teRow = mysqli_fetch_assoc($teRes)) ? intval($teRow['class_id']) : 6;

$fyRes = mysqli_query($conn, "SELECT class_id FROM st_class_master WHERE class_name = 'FY' LIMIT 1");
$fyClassId = ($fyRow = mysqli_fetch_assoc($fyRes)) ? intval($fyRow['class_id']) : 1;

// Department IDs
$bcaRes = mysqli_query($conn, "SELECT department_id FROM st_department_master WHERE department_name = 'BCA' LIMIT 1");
$bcaDeptId = ($bcaRow = mysqli_fetch_assoc($bcaRes)) ? intval($bcaRow['department_id']) : 11;

$ceRes = mysqli_query($conn, "SELECT department_id FROM st_department_master WHERE department_name = 'CE' LIMIT 1");
$ceDeptId = ($ceRow = mysqli_fetch_assoc($ceRes)) ? intval($ceRow['department_id']) : 3;

$itRes = mysqli_query($conn, "SELECT department_id FROM st_department_master WHERE department_name = 'IT' LIMIT 1");
$itDeptId = ($itRow = mysqli_fetch_assoc($itRes)) ? intval($itRow['department_id']) : 4;


// -------------------------------------------------------------
// Test Case 1: BE + Semester 7 -> Honours with Research is available
// -------------------------------------------------------------
echo "Test Case 1: BE + Semester 7 -> Honours with Research is available\n";

$isEligible1 = $db->isTrackEligible(2, $beClassId, 7, $ceDeptId);
assertTest($isEligible1 === true, "DBController::isTrackEligible(2, BE, 7, CE) returns true");

$tracks1 = $db->getEligibleTracks($beClassId, 7, $ceDeptId);
$trackIds1 = array_column($tracks1, 'specialization_id');
assertTest(in_array(2, $trackIds1), "getEligibleTracks for BE + Sem 7 includes Track 2 (Honours with Research)");
assertTest(in_array(1, $trackIds1), "getEligibleTracks for BE + Sem 7 preserves Track 1 (Honours Degree)");
assertTest(in_array(3, $trackIds1), "getEligibleTracks for BE + Sem 7 preserves Track 3 (Minor Degree)");
assertTest(in_array(4, $trackIds1), "getEligibleTracks for BE + Sem 7 preserves Track 4 (Minor Multidisciplinary)");


// -------------------------------------------------------------
// Test Case 2: BE + Semester 8 -> Honours with Research is available
// -------------------------------------------------------------
echo "\nTest Case 2: BE + Semester 8 -> Honours with Research is available\n";

$isEligible2 = $db->isTrackEligible(2, $beClassId, 8, $ceDeptId);
assertTest($isEligible2 === true, "DBController::isTrackEligible(2, BE, 8, CE) returns true");

$tracks2 = $db->getEligibleTracks($beClassId, 8, $ceDeptId);
$trackIds2 = array_column($tracks2, 'specialization_id');
assertTest(in_array(2, $trackIds2), "getEligibleTracks for BE + Sem 8 includes Track 2 (Honours with Research)");


// -------------------------------------------------------------
// Test Case 3: BE + Semester 6 -> Honours with Research is unavailable
// -------------------------------------------------------------
echo "\nTest Case 3: BE + Semester 6 -> Honours with Research is unavailable\n";

$isEligible3 = $db->isTrackEligible(2, $beClassId, 6, $ceDeptId);
assertTest($isEligible3 === false, "DBController::isTrackEligible(2, BE, 6, CE) returns false");

$tracks3 = $db->getEligibleTracks($beClassId, 6, $ceDeptId);
$trackIds3 = array_column($tracks3, 'specialization_id');
assertTest(!in_array(2, $trackIds3), "getEligibleTracks for BE + Sem 6 excludes Track 2 (Honours with Research)");

// Test Semesters 1 to 5 as well
for ($s = 1; $s <= 5; $s++) {
    $isElig = $db->isTrackEligible(2, $beClassId, $s, $ceDeptId);
    assertTest($isElig === false, "DBController::isTrackEligible(2, BE, Sem $s) returns false");
}


// -------------------------------------------------------------
// Test Case 4: BCA + any semester -> Honours with Research is unavailable
// -------------------------------------------------------------
echo "\nTest Case 4: BCA + any semester -> Honours with Research is unavailable\n";

assertTest($db->isNonBeDepartment($bcaDeptId) === true, "BCA department is recognized as a non-BE program");

for ($s = 1; $s <= 8; $s++) {
    $bcaElig = $db->isTrackEligible(2, $beClassId, $s, $bcaDeptId);
    assertTest($bcaElig === false, "BCA + Semester $s: Honours with Research is unavailable");
}

$bcaTracks = $db->getEligibleTracks($beClassId, 7, $bcaDeptId);
$bcaTrackIds = array_column($bcaTracks, 'specialization_id');
assertTest(!in_array(2, $bcaTrackIds), "getEligibleTracks for BCA + Sem 7 excludes Track 2");


// -------------------------------------------------------------
// Test Case 5: Non-BE programs and changing selection behavior
// -------------------------------------------------------------
echo "\nTest Case 5: Program & Class Eligibility Constraints\n";

// TE Class with Sem 7 (e.g. invalid class)
$teElig = $db->isTrackEligible(2, $teClassId, 7, $ceDeptId);
assertTest($teElig === false, "TE Class + Sem 7 cannot take Honours with Research");

// FY Class
$fyElig = $db->isTrackEligible(2, $fyClassId, 7, $ceDeptId);
assertTest($fyElig === false, "FY Class cannot take Honours with Research");

// MCA / MBA departments
$mcaRes = mysqli_query($conn, "SELECT department_id FROM st_department_master WHERE department_name = 'MCA' LIMIT 1");
if ($mcaRow = mysqli_fetch_assoc($mcaRes)) {
    $mcaDeptId = intval($mcaRow['department_id']);
    assertTest($db->isTrackEligible(2, $beClassId, 7, $mcaDeptId) === false, "MCA Department is excluded from Honours with Research");
}

$mbaRes = mysqli_query($conn, "SELECT department_id FROM st_department_master WHERE department_name = 'MBA' LIMIT 1");
if ($mbaRow = mysqli_fetch_assoc($mbaRes)) {
    $mbaDeptId = intval($mbaRow['department_id']);
    assertTest($db->isTrackEligible(2, $beClassId, 7, $mbaDeptId) === false, "MBA Department is excluded from Honours with Research");
}


// -------------------------------------------------------------
// Test Case 6: Server-side rejection of ineligible requests
// -------------------------------------------------------------
echo "\nTest Case 6: Server-Side Validation Rejection\n";

// Simulation of student_process validation logic
function simulateServerValidation($trackId, $classId, $semId, $deptId, $db) {
    if ($trackId === 2) {
        if (!$db->isTrackEligible(2, $classId, $semId, $deptId)) {
            return [
                'status' => 'rejected',
                'message' => 'Error: Honours with Research is available only to BE students in Semester 7 and Semester 8.'
            ];
        }
    }
    return ['status' => 'accepted'];
}

$rej1 = simulateServerValidation(2, $beClassId, 6, $ceDeptId, $db);
assertTest($rej1['status'] === 'rejected', "Server rejects BE + Semester 6 for Track 2");

$rej2 = simulateServerValidation(2, $teClassId, 7, $ceDeptId, $db);
assertTest($rej2['status'] === 'rejected', "Server rejects TE + Semester 7 for Track 2");

$rej3 = simulateServerValidation(2, $beClassId, 7, $bcaDeptId, $db);
assertTest($rej3['status'] === 'rejected', "Server rejects BCA + Semester 7 for Track 2");

$rej4 = simulateServerValidation(2, $beClassId, 8, $ceDeptId, $db);
assertTest($rej4['status'] === 'accepted', "Server accepts valid BE + Semester 8 for Track 2");


// -------------------------------------------------------------
// Test Case 7: Other specialization tracks continue to work
// -------------------------------------------------------------
echo "\nTest Case 7: Preservation of Other Tracks & Existing Rules\n";

// Track 1 (Honours Degree)
assertTest($db->isTrackEligible(1, $beClassId, 7, $ceDeptId) === true, "Track 1 (Honours Degree) is eligible for BE + Sem 7");
assertTest($db->isTrackEligible(1, $teClassId, 5, $ceDeptId) === true, "Track 1 (Honours Degree) is eligible for TE + Sem 5");

// Track 3 (Minor Degree)
assertTest($db->isTrackEligible(3, $beClassId, 7, $ceDeptId) === true, "Track 3 (Minor Degree) is eligible for BE + Sem 7");
assertTest($db->isTrackEligible(3, $teClassId, 5, $ceDeptId) === true, "Track 3 (Minor Degree) is eligible for TE + Sem 5");

// Track 4 (Minor Multidisciplinary)
assertTest($db->isTrackEligible(4, $beClassId, 7, $ceDeptId) === true, "Track 4 (Minor Multidisciplinary) is eligible for BE + Sem 7");

// Database track configuration
$specMasterRes = mysqli_query($conn, "SELECT specialization_id, specialization_name, is_track, sem_from, sem_to, offering_program FROM st_specialization_master WHERE is_track = 1 ORDER BY specialization_id ASC");
$allTrackConfigs = mysqli_fetch_all($specMasterRes, MYSQLI_ASSOC);

assertTest(count($allTrackConfigs) === 4, "Exact 4 specialization tracks preserved in database");
$t2Config = null;
foreach ($allTrackConfigs as $tc) {
    if ($tc['specialization_id'] == 2) {
        $t2Config = $tc;
    }
}
assertTest($t2Config !== null, "Track 2 found in st_specialization_master");
assertTest($t2Config['sem_from'] == 7 && $t2Config['sem_to'] == 8, "Track 2 has sem_from=7 and sem_to=8 in database");
assertTest(strtoupper($t2Config['offering_program']) === 'BE', "Track 2 has offering_program='BE' in database");


// -------------------------------------------------------------
// Test Case 8: Existing student registration remains functional
// -------------------------------------------------------------
echo "\nTest Case 8: Student Admission DB Flow with Honours with Research\n";

$testRegNo = "TEST-HWRES-" . time();
$mockInsertSql = "INSERT INTO st_student_master (
    academic_year_id, registration_no, roll_no, class_id, division_id, department_id,
    specialization_track_id, specialization_id, current_semester_id, cgpa, fname,
    mobile, email, status, created_at
) VALUES (
    1, '$testRegNo', 'RES-01', $beClassId, 1, $ceDeptId,
    2, 5, 7, 8.80, 'Honours Research Test Student',
    '9876543219', 'hwres@tcetmumbai.in', 1, NOW()
)";

if (mysqli_query($conn, $mockInsertSql)) {
    $studentId = mysqli_insert_id($conn);
    assertTest($studentId > 0, "Successfully registered BE Sem 7 student with Track 2 (Honours with Research)");

    // Sync semester history
    $syncOk = $db->syncStudentSemesterHistory($studentId, 7);
    assertTest($syncOk === true, "syncStudentSemesterHistory executed for Honours with Research student");

    // Verify record
    $verifyRes = mysqli_query($conn, "SELECT specialization_track_id, specialization_id, current_semester_id, cgpa FROM st_student_master WHERE student_id = $studentId");
    $verifyRow = mysqli_fetch_assoc($verifyRes);
    assertTest(intval($verifyRow['specialization_track_id']) === 2, "Verified specialization_track_id = 2");
    assertTest(intval($verifyRow['current_semester_id']) === 7, "Verified current_semester_id = 7");
    assertTest(floatval($verifyRow['cgpa']) === 8.80, "Verified CGPA = 8.80");

    // Clean up
    mysqli_query($conn, "DELETE FROM st_student_semester_history WHERE student_id = $studentId");
    mysqli_query($conn, "DELETE FROM st_student_master WHERE student_id = $studentId");
} else {
    assertTest(false, "Student registration insert failed: " . mysqli_error($conn));
}


// -------------------------------------------------------------
// Test Case 9: AJAX Endpoint admin/get_tracks.php
// -------------------------------------------------------------
echo "\nTest Case 9: AJAX Endpoint admin/get_tracks.php\n";

// Execute get_tracks.php via sub-process simulation
$getTracksBe7 = $db->getEligibleTracks($beClassId, 7, $ceDeptId);
$getTracksBe6 = $db->getEligibleTracks($beClassId, 6, $ceDeptId);
$getTracksBca = $db->getEligibleTracks($beClassId, 7, $bcaDeptId);

assertTest(count($getTracksBe7) === 4, "get_tracks endpoint logic for BE + Sem 7 returns 4 tracks");
assertTest(count($getTracksBe6) === 3, "get_tracks endpoint logic for BE + Sem 6 returns 3 tracks (excludes track 2)");
assertTest(count($getTracksBca) === 3, "get_tracks endpoint logic for BCA returns 3 tracks (excludes track 2)");


echo "\n=======================================================\n";
echo "HONOURS WITH RESEARCH ELIGIBILITY RESULTS: $passedTests Passed, $failedTests Failed (Total: $totalTests)\n";
echo "=======================================================\n";

exit($failedTests === 0 ? 0 : 1);
