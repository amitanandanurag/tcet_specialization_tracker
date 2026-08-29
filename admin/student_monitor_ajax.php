<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_session'])) {
    echo json_encode(array('success' => false, 'message' => 'Session expired. Please login again.'));
    exit;
}

require_once "../database/db_connect.php";
$db_handle = new DBController();

if (!$db_handle || !($db_handle->conn instanceof mysqli)) {
    echo json_encode(array('success' => false, 'message' => 'Unable to connect to database.'));
    exit;
}

// Check authorization: SUPER ADMIN (1) or ADMIN (2)
$sessionRoleId = intval($_SESSION['user_type'] ?? 0);
if ($sessionRoleId !== 1 && $sessionRoleId !== 2) {
    echo json_encode(array('success' => false, 'message' => 'Unauthorized access.'));
    exit;
}

$registrationNo = trim($_GET['reg_no'] ?? $_POST['reg_no'] ?? '');

if (empty($registrationNo)) {
    echo json_encode(array('success' => false, 'message' => 'Please enter a Registration / Enrollment Number.'));
    exit;
}

$conn = $db_handle->conn;

// 1. Fetch Student Details
$studentSql = "SELECT sm.student_id, sm.registration_no, sm.fname, sm.roll_no, sm.grad_year, sm.cgpa, sm.mobile, sm.email, sm.status, sm.created_at,
                      cl.class_name, sec.sections AS division_name, dep.department_name,
                      sp.specialization_name, ssb.subject_name AS specialization_subject_name,
                      mc.course_name AS minor_course_name, ms.subject_name AS minor_subject_name,
                      sess.session_name AS academic_year_name, sem.semester_name AS current_semester_name,
                      sm.current_semester_id, sm.specialization_id, sm.specialization_subject_id, sm.minor_course_id, sm.minor_subject_id
               FROM st_student_master sm
               LEFT JOIN st_class_master cl ON cl.class_id = sm.class_id
               LEFT JOIN st_section_master sec ON sec.id = sm.division_id
               LEFT JOIN st_department_master dep ON dep.department_id = sm.department_id
               LEFT JOIN st_specialization_master sp ON sp.specialization_id = sm.specialization_id
               LEFT JOIN st_specialization_subject_master ssb ON ssb.subject_id = sm.specialization_subject_id
               LEFT JOIN st_minorcourse mc ON mc.course_id = sm.minor_course_id
               LEFT JOIN st_minorsubject ms ON ms.subject_id = sm.minor_subject_id
               LEFT JOIN st_session_master sess ON sess.session_id = sm.academic_year_id
               LEFT JOIN st_semester_master sem ON sem.semester_id = sm.current_semester_id
               WHERE sm.registration_no = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $studentSql);
if (!$stmt) {
    echo json_encode(array('success' => false, 'message' => 'Database error preparing student query.'));
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $registrationNo);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$student) {
    echo json_encode(array('success' => false, 'message' => 'No student found with the provided Registration / Enrollment Number.'));
    exit;
}

$studentId = intval($student['student_id']);
$currentSemId = intval($student['current_semester_id']);

// Determine the type of specialization for the student
$specName = strtolower($student['specialization_name'] ?? '');
$isMinorMultidisciplinary = strpos($specName, 'minor multidisciplinary') !== false;
$isHonours = strpos($specName, 'honour') !== false || strpos($specName, 'honor') !== false;

// 2. Fetch Semester Data for Semesters 1 to 8
$semestersData = array();

for ($sem = 1; $sem <= 8; $sem++) {
    $semData = array(
        'semester_number' => $sem,
        'semester_name' => 'Semester ' . $sem,
        'specialization' => 'N/A',
        'subject' => 'N/A',
        'mentor' => 'N/A',
        'progress' => null,
        'type' => 'Regular',
        'credits' => 0.00,
        'marks_entry' => null,
        'nptel_entry' => null,
        'status' => 'Not Started'
    );

    $historyStmt = mysqli_prepare($conn, "SELECT sp.specialization_name, ss.subject_name,
                                                u.user_name AS mentor_name, h.progress_percent, h.status
                                         FROM st_student_semester_history h
                                         LEFT JOIN st_specialization_master sp ON sp.specialization_id = h.specialization_id
                                         LEFT JOIN st_specialization_subject_master ss ON ss.subject_id = h.specialization_subject_id
                                         LEFT JOIN st_user_master u ON u.user_id = h.mentor_id
                                         WHERE h.student_id = ? AND h.semester_id = ? LIMIT 1");
    if ($historyStmt) {
        mysqli_stmt_bind_param($historyStmt, 'ii', $studentId, $sem);
        mysqli_stmt_execute($historyStmt);
        $historyResult = mysqli_stmt_get_result($historyStmt);
        if ($historyResult && ($historyRow = mysqli_fetch_assoc($historyResult))) {
            $semData['specialization'] = $historyRow['specialization_name'] ?: 'N/A';
            $semData['subject'] = $historyRow['subject_name'] ?: 'N/A';
            $semData['mentor'] = $historyRow['mentor_name'] ?: 'N/A';
            $semData['progress'] = $historyRow['progress_percent'] !== null ? floatval($historyRow['progress_percent']) : null;
            if ($historyRow['status'] === 'Completed') {
                $semData['status'] = 'Completed';
            }
        }
        mysqli_stmt_close($historyStmt);
    }

    // Get semester name master if possible
    $semMasterSql = "SELECT semester_name FROM st_semester_master WHERE semester_id = ? LIMIT 1";
    $semMasterStmt = mysqli_prepare($conn, $semMasterSql);
    if ($semMasterStmt) {
        mysqli_stmt_bind_param($semMasterStmt, 'i', $sem);
        mysqli_stmt_execute($semMasterStmt);
        $semMasterRes = mysqli_stmt_get_result($semMasterStmt);
        if ($semMasterRow = mysqli_fetch_assoc($semMasterRes)) {
            $semData['semester_name'] = $semMasterRow['semester_name'];
        }
        mysqli_stmt_close($semMasterStmt);
    }

    // A. Check Enrollment Specialization for this semester
    $enrollSql = "SELECT e.specialization_id, sp.specialization_name
                  FROM st_enrollment e
                  LEFT JOIN st_specialization_master sp ON sp.specialization_id = e.specialization_id
                  WHERE e.student_id = ? AND e.semester_id = ? LIMIT 1";
    $enrollStmt = mysqli_prepare($conn, $enrollSql);
    if ($enrollStmt) {
        mysqli_stmt_bind_param($enrollStmt, 'ii', $studentId, $sem);
        mysqli_stmt_execute($enrollStmt);
        $enrollRes = mysqli_stmt_get_result($enrollStmt);
        if ($enrollRow = mysqli_fetch_assoc($enrollRes)) {
            $semData['specialization'] = $enrollRow['specialization_name'];
        }
        mysqli_stmt_close($enrollStmt);
    }

    // Fallback: If no enrollment record but this is the student's current semester, use their current specialization
    if ($semData['specialization'] === 'N/A' && $sem === $currentSemId) {
        $semData['specialization'] = $student['specialization_name'] ?? 'N/A';
    }

    // B. Check Credits Earned for this semester
    $creditSql = "SELECT credits_earned FROM st_credit_ledger WHERE student_id = ? AND semester_id = ? LIMIT 1";
    $creditStmt = mysqli_prepare($conn, $creditSql);
    if ($creditStmt) {
        mysqli_stmt_bind_param($creditStmt, 'ii', $studentId, $sem);
        mysqli_stmt_execute($creditStmt);
        $creditRes = mysqli_stmt_get_result($creditStmt);
        if ($creditRow = mysqli_fetch_assoc($creditRes)) {
            $semData['credits'] = floatval($creditRow['credits_earned']);
        }
        mysqli_stmt_close($creditStmt);
    }

    // C. Check Offline Marks Entry (Final Exams / College Level Exams)
    $offlineSql = "SELECT course_name, nptel_status, nptel_exam_score, nptel_assignment_raw,
                          ise1_marks, ise2_marks, ese_written_marks, college_total_score, final_score, remarks
                   FROM st_offline_marks_entry
                   WHERE student_id = ? AND semester_id = ? LIMIT 1";
    $offlineStmt = mysqli_prepare($conn, $offlineSql);
    if ($offlineStmt) {
        mysqli_stmt_bind_param($offlineStmt, 'ii', $studentId, $sem);
        mysqli_stmt_execute($offlineStmt);
        $offlineRes = mysqli_stmt_get_result($offlineStmt);
        if ($offlineRow = mysqli_fetch_assoc($offlineRes)) {
            $semData['marks_entry'] = $offlineRow;
            $semData['subject'] = $offlineRow['course_name'];
            $semData['status'] = 'Completed';
        }
        mysqli_stmt_close($offlineStmt);
    }

    // D. Check NPTEL Records for this semester
    $nptelSql = "SELECT course_name, score, pass_fail, offline_exam_flag, offline_exam_score
                 FROM st_nptel_records
                 WHERE student_id = ? AND semester_id = ? LIMIT 1";
    $nptelStmt = mysqli_prepare($conn, $nptelSql);
    if ($nptelStmt) {
        mysqli_stmt_bind_param($nptelStmt, 'ii', $studentId, $sem);
        mysqli_stmt_execute($nptelStmt);
        $nptelRes = mysqli_stmt_get_result($nptelStmt);
        if ($nptelRow = mysqli_fetch_assoc($nptelRes)) {
            $semData['nptel_entry'] = $nptelRow;
            if ($semData['subject'] === 'N/A') {
                $semData['subject'] = $nptelRow['course_name'];
            }
            if ($semData['status'] === 'Not Started') {
                $semData['status'] = $nptelRow['pass_fail'] === 'Pass' ? 'Completed' : 'Failed';
            }
        }
        mysqli_stmt_close($nptelStmt);
    }

    // E. Current semester subject fallback if no entries exist yet
    if ($semData['subject'] === 'N/A' && $sem === $currentSemId) {
        if ($isMinorMultidisciplinary) {
            $semData['subject'] = $student['minor_subject_name'] ?? 'N/A';
        } elseif ($isHonours) {
            $semData['subject'] = $student['specialization_subject_name'] ?? 'N/A';
        }
        $semData['status'] = 'Current Semester';
    }

    // F. Fetch default subject from minor subjects if enrolled in minor and sem matches
    if ($semData['subject'] === 'N/A' && !empty($student['minor_course_id'])) {
        $minorSubSql = "SELECT subject_name, credits FROM st_minorsubject WHERE course_id = ? AND semester_id = ? LIMIT 1";
        $minorSubStmt = mysqli_prepare($conn, $minorSubSql);
        if ($minorSubStmt) {
            mysqli_stmt_bind_param($minorSubStmt, 'ii', $student['minor_course_id'], $sem);
            mysqli_stmt_execute($minorSubStmt);
            $minorSubRes = mysqli_stmt_get_result($minorSubStmt);
            if ($minorSubRow = mysqli_fetch_assoc($minorSubRes)) {
                $semData['subject'] = $minorSubRow['subject_name'];
                if ($semData['credits'] === 0.00) {
                    $semData['credits'] = floatval($minorSubRow['credits']);
                }
            }
            mysqli_stmt_close($minorSubStmt);
        }
    }

    $semestersData[] = $semData;
}

echo json_encode(array(
    'success' => true,
    'student' => array(
        'name' => $student['fname'],
        'registration_no' => $student['registration_no'],
        'roll_no' => $student['roll_no'] ?? 'N/A',
        'class' => $student['class_display'] ?? $student['class_name'] ?? 'N/A',
        'division' => $student['division_name'] ?? 'N/A',
        'department' => $student['department_name'] ?? 'N/A',
        'academic_year' => $student['academic_year_name'] ?? 'N/A',
        'graduation_year' => ($student['grad_year'] > 0) ? $student['grad_year'] : 'N/A',
        'email' => $student['email'] ?? 'N/A',
        'mobile' => $student['mobile'] ?? 'N/A',
        'cgpa' => !empty($student['cgpa']) ? number_format(floatval($student['cgpa']), 2) : 'N/A',
        'specialization' => $student['specialization_name'] ?? 'N/A',
        'specialization_subject' => $student['specialization_subject_name'] ?? 'N/A',
        'minor_course' => $student['minor_course_name'] ?? 'N/A',
        'minor_subject' => $student['minor_subject_name'] ?? 'N/A',
        'status' => intval($student['status']) === 1 ? 'Active' : 'Inactive',
        'current_semester' => $student['current_semester_name'] ?? 'N/A'
    ),
    'semesters' => $semestersData
));
exit;
