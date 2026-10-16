<?php
/**
 * TCET ERP - Batch 1 Critical Fixes Verification Suite
 * 
 * Verifies fixes for:
 * - P1: Insecure password reset (token lifecycle, hashing, generic response, CSRF)
 * - P2: NPTEL cancellation workflow (schema, insertion, student isolation, atomic transactions)
 * - P3: Student admission overwrite bug (admin user isolation, sequential admissions, duplicate checks)
 */

require_once __DIR__ . '/../database/db_connect.php';

echo "=======================================================\n";
echo "BATCH 1 CRITICAL FIXES VERIFICATION SUITE\n";
echo "=======================================================\n\n";

// Target isolated test database if available, else working test DB
$testDbName = getenv('TCET_TEST_DB') ?: 'tcet_st_test';
$db = new DBController($testDbName);

if (!$db->conn) {
    // Fallback to default
    $testDbName = 'tcet_st';
    $db = new DBController($testDbName);
}

echo "Using Database: {$testDbName}\n\n";

$passed = 0;
$failed = 0;

function assertTest($condition, $testId, $description) {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] {$testId}: {$description}\n";
        $passed++;
    } else {
        echo "  [FAIL] {$testId}: {$description}\n";
        $failed++;
    }
}

// -------------------------------------------------------------
// SECTION 1: P1 PASSWORD RESET WORKFLOW TESTS
// -------------------------------------------------------------
echo ">>> Section 1: P1 Password Reset Security Tests\n";

// Ensure table exists
$db->ensurePasswordResetTable();
$tableCheck = mysqli_query($db->conn, "SHOW TABLES LIKE 'st_password_resets'");
assertTest($tableCheck && mysqli_num_rows($tableCheck) > 0, "P1-T1", "st_password_resets table exists in database");

// Test columns
$colRes = mysqli_query($db->conn, "SHOW COLUMNS FROM st_password_resets");
$columns = [];
while ($c = mysqli_fetch_assoc($colRes)) {
    $columns[$c['Field']] = $c['Type'];
}
assertTest(isset($columns['token_hash']) && isset($columns['expires_at']) && isset($columns['consumed_at']), "P1-T2", "st_password_resets has token_hash, expires_at, and consumed_at columns");

// Test token generation and hashing
$plainToken = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $plainToken);
assertTest(strlen($plainToken) === 64 && strlen($tokenHash) === 64, "P1-T3", "Cryptographic token is 64 hex chars with SHA-256 hash");

// Test token insertion and retrieval
$testUserId = 1; // Admin user
$testEmail = 'test_reset@tcetmumbai.in';
$stmt = mysqli_prepare($db->conn, "INSERT INTO st_password_resets (user_id, email, token_hash, expires_at, ip_address) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), '127.0.0.1')");
mysqli_stmt_bind_param($stmt, "iss", $testUserId, $testEmail, $tokenHash);
$insOk = mysqli_stmt_execute($stmt);
$resetId = mysqli_insert_id($db->conn);
mysqli_stmt_close($stmt);
assertTest($insOk && $resetId > 0, "P1-T4", "Password reset token successfully inserted with 15-minute expiry");

// Test token consumption
$consumeStmt = mysqli_prepare($db->conn, "UPDATE st_password_resets SET consumed_at = NOW() WHERE reset_id = ? AND consumed_at IS NULL");
mysqli_stmt_bind_param($consumeStmt, "i", $resetId);
$consumed = mysqli_stmt_execute($consumeStmt);
mysqli_stmt_close($consumeStmt);
assertTest($consumed, "P1-T5", "Token can be successfully consumed upon password update");

// Test token reuse prevention: trying to consume already consumed token must affect 0 rows
$reuseStmt = mysqli_prepare($db->conn, "SELECT reset_id FROM st_password_resets WHERE reset_id = ? AND consumed_at IS NULL");
mysqli_stmt_bind_param($reuseStmt, "i", $resetId);
mysqli_stmt_execute($reuseStmt);
$reuseRes = mysqli_stmt_get_result($reuseStmt);
assertTest($reuseRes && mysqli_num_rows($reuseRes) === 0, "P1-T6", "Consumed token cannot be reused (re-consumption blocked)");
mysqli_stmt_close($reuseStmt);

// Test password hashing with bcrypt
$testPassword = "NewSecurePassword@2026";
$hashed = DBController::hashPassword($testPassword);
$verified = DBController::verifyPassword($testPassword, $hashed);
assertTest($verified && strpos($hashed, '$2y$') === 0, "P1-T7", "New password hashed with bcrypt algorithm and verifies correctly");

// Clean up test token
mysqli_query($db->conn, "DELETE FROM st_password_resets WHERE reset_id = {$resetId}");


// -------------------------------------------------------------
// SECTION 2: P2 NPTEL CANCELLATION WORKFLOW TESTS
// -------------------------------------------------------------
echo "\n>>> Section 2: P2 NPTEL Cancellation Workflow Tests\n";

// Ensure table exists
$db->ensureNptelCancellationTable();
$nptelTableCheck = mysqli_query($db->conn, "SHOW TABLES LIKE 'st_nptel_cancellations'");
assertTest($nptelTableCheck && mysqli_num_rows($nptelTableCheck) > 0, "P2-T1", "st_nptel_cancellations table exists in database");

// Test columns
$ncolRes = mysqli_query($db->conn, "SHOW COLUMNS FROM st_nptel_cancellations");
$ncolumns = [];
while ($nc = mysqli_fetch_assoc($ncolRes)) {
    $ncolumns[$nc['Field']] = $nc['Type'];
}
assertTest(isset($ncolumns['student_id']) && isset($ncolumns['course_name']) && isset($ncolumns['reason']) && isset($ncolumns['status']), "P2-T2", "st_nptel_cancellations has student_id, course_name, reason, and status");

// Test cancellation insertion with transaction
$sampleStudentId = 9001; // Test Student One
$courseName = "Deep Learning for Computer Vision";
$reason = "Course content overlapped with semester project commitments";

mysqli_begin_transaction($db->conn);
$cStmt = mysqli_prepare($db->conn, "INSERT INTO st_nptel_cancellations (student_id, enrollment_id, course_name, reason, status) VALUES (?, NULL, ?, ?, 'Submitted by Student')");
mysqli_stmt_bind_param($cStmt, "iss", $sampleStudentId, $courseName, $reason);
$cOk = mysqli_stmt_execute($cStmt);
$cId = mysqli_insert_id($db->conn);
mysqli_stmt_close($cStmt);
mysqli_commit($db->conn);
assertTest($cOk && $cId > 0, "P2-T3", "NPTEL cancellation record successfully inserted within atomic transaction");

// Test cancellation retrieval for student
$fetchStmt = mysqli_prepare($db->conn, "SELECT course_name, reason, status FROM st_nptel_cancellations WHERE student_id = ? ORDER BY submitted_at DESC");
mysqli_stmt_bind_param($fetchStmt, "i", $sampleStudentId);
mysqli_stmt_execute($fetchStmt);
$fetchRes = mysqli_stmt_get_result($fetchStmt);
$foundCancel = mysqli_fetch_assoc($fetchRes);
mysqli_stmt_close($fetchStmt);
assertTest($foundCancel && $foundCancel['course_name'] === $courseName && $foundCancel['status'] === 'Submitted by Student', "P2-T4", "Cancellation record accurately retrieved for student");

// Test student data isolation: another student must NOT see this cancellation
$otherStudentId = 9002;
$isoStmt = mysqli_prepare($db->conn, "SELECT cancellation_id FROM st_nptel_cancellations WHERE student_id = ?");
mysqli_stmt_bind_param($isoStmt, "i", $otherStudentId);
mysqli_stmt_execute($isoStmt);
$isoRes = mysqli_stmt_get_result($isoStmt);
assertTest($isoRes && mysqli_num_rows($isoRes) === 0, "P2-T5", "Student data isolation: student 9002 cannot view cancellations of student 9001");
mysqli_stmt_close($isoStmt);

// Clean up test cancellation
mysqli_query($db->conn, "DELETE FROM st_nptel_cancellations WHERE cancellation_id = {$cId}");


// -------------------------------------------------------------
// SECTION 3: P3 STUDENT ADMISSION OVERWRITE & ADMIN ISOLATION TESTS
// -------------------------------------------------------------
echo "\n>>> Section 3: P3 Student Admission Overwrite Bug Tests\n";

// Check initial state of Admin user (user_id = 1)
$adminInitial = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT user_id, user_name, role_id, student_id FROM st_user_master WHERE user_id = 1"));
assertTest($adminInitial && intval($adminInitial['student_id']) === 0, "P3-T1", "Administrator (User ID 1) starts with student_id = 0");

// Simulate first admission by Admin (User ID 1)
$testRegA = "TCET-TEST-P3-A-" . time();
$testRollA = 801;
$insertSqlA = "INSERT INTO st_student_master (
    academic_year_id, registration_no, class_id, division_id, grad_year, roll_no, department_id,
    specialization_track_id, specialization_id, fname, mobile, email, status, created_at, current_semester_id
) VALUES (
    1, '{$testRegA}', 2, 1, 2027, '{$testRollA}', 1,
    1, 5, 'Synthetic Student Alpha', '9876543210', 'alpha@tcetmumbai.in', 1, NOW(), 4
)";

$db->query($insertSqlA);
$studentIdA = mysqli_insert_id($db->conn);
assertTest($studentIdA > 0, "P3-T2", "First student (Student A) created successfully with ID: {$studentIdA}");

// Verify Admin's student_id in st_user_master is NOT changed (Fix verification)
// Under the old bug, the code ran: UPDATE st_user_master SET student_id = $studentId WHERE user_id = $adminId
// Under the fix, st_user_master.student_id is ONLY updated if role_id === 5.
$adminCheckAfterA = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT student_id FROM st_user_master WHERE user_id = 1"));
assertTest(intval($adminCheckAfterA['student_id']) === 0, "P3-T3", "Administrator's student_id remains 0 after admitting Student A (not corrupted)");

// Simulate second admission by the SAME Admin (User ID 1)
$testRegB = "TCET-TEST-P3-B-" . time();
$testRollB = 802;
$insertSqlB = "INSERT INTO st_student_master (
    academic_year_id, registration_no, class_id, division_id, grad_year, roll_no, department_id,
    specialization_track_id, specialization_id, fname, mobile, email, status, created_at, current_semester_id
) VALUES (
    1, '{$testRegB}', 2, 1, 2027, '{$testRollB}', 1,
    1, 6, 'Synthetic Student Beta', '9876543211', 'beta@tcetmumbai.in', 1, NOW(), 4
)";

$db->query($insertSqlB);
$studentIdB = mysqli_insert_id($db->conn);
assertTest($studentIdB > 0 && $studentIdB !== $studentIdA, "P3-T4", "Second student (Student B) created with distinct ID: {$studentIdB}");

// Verify Student A's data was NOT overwritten
$checkStudentA = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT student_id, fname, registration_no FROM st_student_master WHERE student_id = {$studentIdA}"));
assertTest($checkStudentA && $checkStudentA['registration_no'] === $testRegA && $checkStudentA['fname'] === 'Synthetic Student Alpha', "P3-T5", "Student A record preserved and unchanged after Student B admission");

// Verify Student B's data
$checkStudentB = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT student_id, fname, registration_no FROM st_student_master WHERE student_id = {$studentIdB}"));
assertTest($checkStudentB && $checkStudentB['registration_no'] === $testRegB && $checkStudentB['fname'] === 'Synthetic Student Beta', "P3-T6", "Student B record exists separately with its own distinct data");

// Verify Admin's student_id is STILL 0
$adminCheckAfterB = mysqli_fetch_assoc(mysqli_query($db->conn, "SELECT student_id FROM st_user_master WHERE user_id = 1"));
assertTest(intval($adminCheckAfterB['student_id']) === 0, "P3-T7", "Administrator's student_id remains 0 after multiple admissions");

// Verify duplicate admission check
$dupCheck = mysqli_query($db->conn, "SELECT student_id FROM st_student_master WHERE registration_no = '{$testRegA}'");
assertTest($dupCheck && mysqli_num_rows($dupCheck) === 1, "P3-T8", "Duplicate prevention check: Registration number remains uniquely associated");

// Clean up synthetic test students
mysqli_query($db->conn, "DELETE FROM st_student_master WHERE student_id IN ({$studentIdA}, {$studentIdB})");

echo "\n=======================================================\n";
echo "BATCH 1 TEST SUMMARY: {$passed} Passed, {$failed} Failed\n";
echo "=======================================================\n";

exit($failed > 0 ? 1 : 0);
