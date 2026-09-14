<?php
require_once __DIR__ . "/../database/db_connect.php";
$db = new DBController();
$conn = $db->conn;

$students = mysqli_query($conn, "
    SELECT s.student_id, s.academic_year_id, s.class_id, s.current_semester_id, s.department_id,
           s.division_id, s.roll_no, s.specialization_id, s.specialization_subject_id,
           s.minor_course_id, s.minor_subject_id, s.cgpa
    FROM st_student_master s
    LEFT JOIN st_student_semester_history h ON h.student_id = s.student_id AND h.semester_id = s.current_semester_id
    WHERE h.history_id IS NULL
");

$backfilled = 0;
$errors = 0;
while ($s = mysqli_fetch_assoc($students)) {
    $stId = intval($s['student_id']);
    $ayId = intval($s['academic_year_id'] ?? 2);
    $clsId = intval($s['class_id'] ?? 1);
    $semId = intval($s['current_semester_id'] ?? 1);
    $deptId = intval($s['department_id'] ?? 1);
    $divId = intval($s['division_id'] ?? 1);
    $rollNo = $s['roll_no'] ?? '';
    $specId = intval($s['specialization_id'] ?? 0);
    $subId = intval($s['specialization_subject_id'] ?? 0);
    $minorC = intval($s['minor_course_id'] ?? 0);
    $minorS = intval($s['minor_subject_id'] ?? 0);
    $cgpa = floatval($s['cgpa'] ?? 0);
    
    // Check if subId exists in st_specialization_subject_master
    $subIdSql = "NULL";
    if ($subId > 0) {
        $subCheck = mysqli_query($conn, "SELECT subject_id FROM st_specialization_subject_master WHERE subject_id = $subId LIMIT 1");
        if ($subCheck && mysqli_num_rows($subCheck) > 0) {
            $subIdSql = $subId;
        }
    }
    
    // Resolve mentor
    $mentorIdSql = "NULL";
    if ($subIdSql !== "NULL") {
        $m = $db->getResolvedMentorForSubject($subId, $semId, $ayId);
        if ($m && intval($m['mentor_id']) > 0) {
            $mentorIdSql = intval($m['mentor_id']);
        }
    }
    
    $specIdSql = ($specId > 0) ? $specId : "NULL";
    $minorCSql = ($minorC > 0) ? $minorC : "NULL";
    $minorSSql = ($minorS > 0) ? $minorS : "NULL";
    
    $insSql = "INSERT INTO st_student_semester_history (
        student_id, academic_year_id, class_id, semester_id, department_id,
        division_id, roll_no, specialization_id, specialization_subject_id,
        minor_course_id, minor_subject_id, cgpa, mentor_id, status, progress_percent
    ) VALUES (
        $stId, $ayId, $clsId, $semId, $deptId,
        $divId, '" . mysqli_real_escape_string($conn, $rollNo) . "', $specIdSql, $subIdSql,
        $minorCSql, $minorSSql, $cgpa, $mentorIdSql, 'Active', 0.00
    )";
    if (mysqli_query($conn, $insSql)) {
        $backfilled++;
    } else {
        $errors++;
        echo "Error for student #$stId: " . mysqli_error($conn) . "\n";
    }
}
echo "Backfilled $backfilled records, Errors: $errors\n";

$totHist = mysqli_query($conn, "SELECT COUNT(*) as c FROM st_student_semester_history");
$totH = mysqli_fetch_assoc($totHist);
echo "Total history records now: {$totH['c']}\n";
