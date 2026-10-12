<?php
/**
 * TCET ERP - Specialization & Course Data Import Verification Suite
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../database/db_connect.php';

$db = new DBController();
$conn = $db->conn;

echo "=== TCET ERP SPECIALIZATION & COURSE DATA VERIFICATION SUITE ===\n\n";

$passed = 0;
$failed = 0;

function checkTest($condition, $name, $detail = '') {
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] $name\n";
        $passed++;
    } else {
        echo "[FAIL] $name" . ($detail ? " ($detail)" : "") . "\n";
        $failed++;
    }
}

// 1. Course count verification
$courseCountRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM st_specialization_subject_master WHERE subject_code IS NOT NULL AND subject_code != ''");
$courseCount = (int)(mysqli_fetch_assoc($courseCountRes)['cnt'] ?? 0);
checkTest($courseCount === 87, "Exactly 87 course records present with course codes in st_specialization_subject_master", "Found $courseCount");

// 2. Distinct course codes
$distCodeRes = mysqli_query($conn, "SELECT COUNT(DISTINCT subject_code) as cnt FROM st_specialization_subject_master WHERE subject_code IS NOT NULL AND subject_code != ''");
$distCodeCount = (int)(mysqli_fetch_assoc($distCodeRes)['cnt'] ?? 0);
checkTest($distCodeCount === 87, "All 87 course records have unique course codes", "Found $distCodeCount");

// 3. Unique specializations count
$specCountRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM st_specialization_master WHERE specialization_id >= 5");
$specCount = (int)(mysqli_fetch_assoc($specCountRes)['cnt'] ?? 0);
checkTest($specCount === 21, "Exactly 21 specialization areas present in st_specialization_master", "Found $specCount");

// 4. Unique academic programs verified
$progCountRes = mysqli_query($conn, "SELECT COUNT(DISTINCT program_name) as cnt FROM st_specialization_subject_master WHERE subject_code IS NOT NULL AND subject_code != ''");
$progCount = (int)(mysqli_fetch_assoc($progCountRes)['cnt'] ?? 0);
checkTest($progCount === 15, "Exactly 15 distinct academic program names represented in imported courses", "Found $progCount");

// 5. Foreign Key integrity: all 87 courses reference valid specializations
$invalidSpecRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM st_specialization_subject_master s LEFT JOIN st_specialization_master sp ON sp.specialization_id = s.specialization_id WHERE s.subject_code IS NOT NULL AND s.subject_code != '' AND sp.specialization_id IS NULL");
$invalidSpecCount = (int)(mysqli_fetch_assoc($invalidSpecRes)['cnt'] ?? 0);
checkTest($invalidSpecCount === 0, "Zero courses with orphan specialization_id references", "Found $invalidSpecCount");

// 6. Foreign Key integrity: all 87 courses reference valid departments
$invalidDeptRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM st_specialization_subject_master s LEFT JOIN st_department_master d ON d.department_id = s.department_id WHERE s.subject_code IS NOT NULL AND s.subject_code != '' AND d.department_id IS NULL");
$invalidDeptCount = (int)(mysqli_fetch_assoc($invalidDeptRes)['cnt'] ?? 0);
checkTest($invalidDeptCount === 0, "Zero courses with orphan department_id references", "Found $invalidDeptCount");

// 7. Foreign Key integrity: all 87 courses reference valid semesters
$invalidSemRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM st_specialization_subject_master s LEFT JOIN st_semester_master sem ON sem.semester_id = s.semester_id WHERE s.subject_code IS NOT NULL AND s.subject_code != '' AND sem.semester_id IS NULL");
$invalidSemCount = (int)(mysqli_fetch_assoc($invalidSemRes)['cnt'] ?? 0);
checkTest($invalidSemCount === 0, "Zero courses with orphan semester_id references", "Found $invalidSemCount");

// 8. Foreign Key integrity: all 87 courses reference valid academic years
$invalidAyRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM st_specialization_subject_master s LEFT JOIN st_session_master sess ON sess.session_id = s.academic_year_id WHERE s.subject_code IS NOT NULL AND s.subject_code != '' AND sess.session_id IS NULL");
$invalidAyCount = (int)(mysqli_fetch_assoc($invalidAyRes)['cnt'] ?? 0);
checkTest($invalidAyCount === 0, "Zero courses with orphan academic_year_id references", "Found $invalidAyCount");

// 9. Representative Spot-Checks against PDF
// Spot-check: AI course MDL-4011
$c1 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, sp.specialization_name, sem.semester_name FROM st_specialization_subject_master s JOIN st_specialization_master sp ON sp.specialization_id = s.specialization_id JOIN st_semester_master sem ON sem.semester_id = s.semester_id WHERE s.subject_code = 'MDL-4011'"));
checkTest($c1 && $c1['subject_name'] === 'Essential Mathematics for ML' && $c1['specialization_name'] === 'Artificial Intelligence' && intval($c1['semester_id']) === 4, "Spot-check MDL-4011: Essential Mathematics for ML, Artificial Intelligence, Sem IV");

// Spot-check: Data Science course MDL-4012-1
$c2 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, sp.specialization_name FROM st_specialization_subject_master s JOIN st_specialization_master sp ON sp.specialization_id = s.specialization_id WHERE s.subject_code = 'MDL-4012-1'"));
checkTest($c2 && $c2['subject_name'] === 'Probability and Statistics' && $c2['specialization_name'] === 'Data Science' && intval($c2['semester_id']) === 4, "Spot-check MDL-4012-1: Probability and Statistics, Data Science, Sem IV");

// Spot-check: Green Technology course MDL-4017 (Inconsistency 1 flagged)
$c3 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, sp.specialization_name FROM st_specialization_subject_master s JOIN st_specialization_master sp ON sp.specialization_id = s.specialization_id WHERE s.subject_code = 'MDL-4017'"));
checkTest($c3 && $c3['subject_name'] === 'Strategies for Sustainable Design' && $c3['specialization_name'] === 'Green Technology' && strpos($c3['source_notes'], 'blank specialization-name') !== false, "Spot-check MDL-4017: Green Technology with source inconsistency note preserved");

// Spot-check: UHV course MDL-40118 (Inconsistency 2 flagged)
$c4 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, sp.specialization_name FROM st_specialization_subject_master s JOIN st_specialization_master sp ON sp.specialization_id = s.specialization_id WHERE s.subject_code = 'MDL40118'"));
checkTest($c4 && strpos($c4['subject_name'], 'UHV-III') !== false && $c4['specialization_name'] === 'Universal Human Values (UHV)' && strpos($c4['source_notes'], 'scheme heading') !== false, "Spot-check MDL40118: UHV-III with p.260 conflict note preserved");

// Spot-check: Sector Specific AIML courses (Fintech & Gaming)
$c5 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, sp.specialization_name FROM st_specialization_subject_master s JOIN st_specialization_master sp ON sp.specialization_id = s.specialization_id WHERE s.subject_code = 'MDL60112_1'"));
checkTest($c5 && strpos($c5['subject_name'], 'Finance') !== false && strpos($c5['source_notes'], 'Fintech') !== false, "Spot-check MDL60112_1: Machine Learning in Finance (Fintech sector note preserved)");

// Spot-check: MBA course MDL04121
$c6 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT s.*, sp.specialization_name, d.department_name FROM st_specialization_subject_master s JOIN st_specialization_master sp ON sp.specialization_id = s.specialization_id JOIN st_department_master d ON d.department_id = s.department_id WHERE s.subject_code = 'MDL04121'"));
checkTest($c6 && $c6['subject_name'] === 'Corporate Finance' && $c6['specialization_name'] === 'Business Development, Marketing and Finance' && $c6['department_name'] === 'MBA', "Spot-check MDL04121: Corporate Finance, MBA program, Sem IV");

// 10. Student data integrity check (non-destruction)
$stuCountRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM st_student_master");
$stuCount = (int)(mysqli_fetch_assoc($stuCountRes)['cnt'] ?? 0);
checkTest($stuCount >= 622, "Student records completely preserved (Found $stuCount students)");

// 11. Legacy student subject mapping integrity
$legacyRowsRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM st_specialization_subject_master WHERE subject_id BETWEEN 1 AND 28");
$legacyCount = (int)(mysqli_fetch_assoc($legacyRowsRes)['cnt'] ?? 0);
checkTest($legacyCount === 28, "Existing student subject mappings (IDs 1-28) untouched and intact", "Found $legacyCount");

// 12. Audit log entry recorded
$auditRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM st_audit_log WHERE action_type = 'DATA_IMPORT_SPECIALIZATION_COURSES'");
$auditCount = (int)(mysqli_fetch_assoc($auditRes)['cnt'] ?? 0);
checkTest($auditCount > 0, "Audit log record for specialization course data import verified in database", "Found $auditCount records");

echo "\n=======================================================\n";
echo "IMPORT VERIFICATION SUMMARY: $passed / " . ($passed + $failed) . " Passed, $failed Failed\n";
echo "=======================================================\n";

exit($failed > 0 ? 1 : 0);
