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

// Check authorization: SUPER ADMIN (1), ADMIN (2), HOD (3), MENTOR (4), or STUDENT (5)
$sessionRoleId = intval($_SESSION['user_type'] ?? 0);
$sessionUserId = intval($_SESSION['user_id'] ?? 0);
if (!in_array($sessionRoleId, array(1, 2, 3, 4, 5), true)) {
    echo json_encode(array('success' => false, 'message' => 'Unauthorized access.'));
    exit;
}

$conn = $db_handle->conn;
$action = trim($_GET['action'] ?? $_POST['action'] ?? '');

// -------------------------------------------------------------
// ACTION: student_options / autocomplete_students (Unified Searchable Selector)
// -------------------------------------------------------------
if ($action === 'student_options' || $action === 'autocomplete_students') {
    $search = trim($_GET['q'] ?? $_GET['search'] ?? $_GET['term'] ?? '');
    $deptFilter = intval($_GET['department_id'] ?? 0);
    $scope = $db_handle->getUserScope($sessionUserId, $sessionRoleId);

    $where = ["sm.student_id > 0"];
    $types = "";
    $params = [];

    if ($scope['is_hod']) {
        $where[] = "sm.department_id = ?";
        $types .= "i";
        $params[] = $scope['department_id'];
    } elseif ($scope['is_mentor']) {
        $where[] = "sm.student_id IN (SELECT student_id FROM st_mentor_student_mapping WHERE mentor_id = ?)";
        $types .= "i";
        $params[] = $scope['user_id'];
    } elseif ($scope['is_student']) {
        $where[] = "sm.student_id = ?";
        $types .= "i";
        $params[] = $scope['student_id'];
    } elseif ($deptFilter > 0) {
        $where[] = "sm.department_id = ?";
        $types .= "i";
        $params[] = $deptFilter;
    }

    if ($search !== '') {
        $where[] = "(sm.fname LIKE ? OR sm.registration_no LIKE ? OR sm.roll_no LIKE ?)";
        $types .= "sss";
        $like = "%$search%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $limit = min(50, max(5, intval($_GET['limit'] ?? 25)));

    $sql = "SELECT sm.student_id, sm.registration_no, sm.fname, sm.roll_no,
                   COALESCE(cl.class_name, 'N/A') AS class_name,
                   COALESCE(dep.department_name, 'N/A') AS department_name
            FROM st_student_master sm
            LEFT JOIN st_class_master cl ON cl.class_id = sm.class_id
            LEFT JOIN st_department_master dep ON dep.department_id = sm.department_id
            WHERE " . implode(" AND ", $where) . "
            ORDER BY sm.fname ASC
            LIMIT ?";

    $types .= "i";
    $params[] = $limit;

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $options = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $options[] = [
                'student_id' => intval($row['student_id']),
                'registration_no' => $row['registration_no'],
                'name' => $row['fname'],
                'roll_no' => $row['roll_no'] ?? '',
                'class' => $row['class_name'],
                'department' => $row['department_name'],
                'label' => $row['registration_no'] . ' - ' . $row['fname'] . ' (' . $row['class_name'] . ')'
            ];
        }
        mysqli_stmt_close($stmt);
        echo json_encode(array('success' => true, 'students' => $options));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Failed to prepare student search query.'));
    }
    exit;
}

// -------------------------------------------------------------
// ACTION: list_students (Paginated, Filterable & Sortable Roster)
// -------------------------------------------------------------
if ($action === 'list_students') {
    $search = trim($_GET['search'] ?? $_GET['keyword'] ?? '');
    $deptId = intval($_GET['department_id'] ?? 0);
    $ayId = intval($_GET['academic_year_id'] ?? 0);
    $mentorId = intval($_GET['mentor_id'] ?? 0);
    $hodId = intval($_GET['hod_id'] ?? 0);
    $classId = intval($_GET['class_id'] ?? 0);
    $semId = intval($_GET['semester_id'] ?? 0);
    $divId = intval($_GET['division_id'] ?? 0);
    $specId = intval($_GET['specialization_id'] ?? 0);

    $sortBy = trim($_GET['sort_by'] ?? 'name');
    $sortDir = strtoupper(trim($_GET['sort_dir'] ?? 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = min(50, max(5, intval($_GET['per_page'] ?? 10)));
    $offset = ($page - 1) * $perPage;

    $scope = $db_handle->getUserScope($sessionUserId, $sessionRoleId);
    $where = ["sm.student_id > 0"];
    $types = "";
    $params = [];

    // RBAC scoping
    if ($scope['is_hod']) {
        $where[] = "sm.department_id = ?";
        $types .= "i";
        $params[] = $scope['department_id'];
    } elseif ($scope['is_mentor']) {
        $where[] = "sm.student_id IN (SELECT student_id FROM st_mentor_student_mapping WHERE mentor_id = ?)";
        $types .= "i";
        $params[] = $scope['user_id'];
    } elseif ($scope['is_student']) {
        $where[] = "sm.student_id = ?";
        $types .= "i";
        $params[] = $scope['student_id'];
    } elseif ($deptId > 0) {
        $where[] = "sm.department_id = ?";
        $types .= "i";
        $params[] = $deptId;
    }

    if ($ayId > 0) {
        $where[] = "sm.academic_year_id = ?";
        $types .= "i";
        $params[] = $ayId;
    }
    if ($classId > 0) {
        $where[] = "sm.class_id = ?";
        $types .= "i";
        $params[] = $classId;
    }
    if ($semId > 0) {
        $where[] = "sm.current_semester_id = ?";
        $types .= "i";
        $params[] = $semId;
    }
    if ($divId > 0) {
        $where[] = "sm.division_id = ?";
        $types .= "i";
        $params[] = $divId;
    }
    if ($specId > 0) {
        $where[] = "sm.specialization_id = ?";
        $types .= "i";
        $params[] = $specId;
    }
    if ($mentorId > 0 && !$scope['is_mentor']) {
        $where[] = "sm.student_id IN (SELECT student_id FROM st_mentor_student_mapping WHERE mentor_id = ?)";
        $types .= "i";
        $params[] = $mentorId;
    }
    if ($hodId > 0) {
        $where[] = "sm.department_id IN (SELECT department_id FROM st_user_master WHERE user_id = ? AND role_id = 3)";
        $types .= "i";
        $params[] = $hodId;
    }
    if ($search !== '') {
        $where[] = "(sm.fname LIKE ? OR sm.registration_no LIKE ? OR sm.roll_no LIKE ?)";
        $types .= "sss";
        $like = "%$search%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    // Sorting allowlist
    $sortMap = array(
        'name' => 'sm.fname',
        'registration_no' => 'sm.registration_no',
        'academic_year' => 'sess.session_name',
        'department' => 'dep.department_name',
        'class' => 'cl.class_name',
        'semester' => 'sm.current_semester_id'
    );
    $orderCol = $sortMap[$sortBy] ?? 'sm.fname';
    $orderClause = "ORDER BY $orderCol $sortDir, sm.student_id ASC";

    $whereClause = implode(" AND ", $where);

    // Total Count
    $countSql = "SELECT COUNT(DISTINCT sm.student_id) AS total
                 FROM st_student_master sm
                 LEFT JOIN st_department_master dep ON dep.department_id = sm.department_id
                 LEFT JOIN st_session_master sess ON sess.session_id = sm.academic_year_id
                 LEFT JOIN st_class_master cl ON cl.class_id = sm.class_id
                 LEFT JOIN st_semester_master sem ON sem.semester_id = sm.current_semester_id
                 LEFT JOIN st_section_master sec ON sec.id = sm.division_id
                 LEFT JOIN st_specialization_master sp ON sp.specialization_id = sm.specialization_id
                 WHERE $whereClause";

    $total = 0;
    $countStmt = mysqli_prepare($conn, $countSql);
    if ($countStmt) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($countStmt, $types, ...$params);
        }
        mysqli_stmt_execute($countStmt);
        $countRes = mysqli_stmt_get_result($countStmt);
        if ($cRow = mysqli_fetch_assoc($countRes)) {
            $total = intval($cRow['total']);
        }
        mysqli_stmt_close($countStmt);
    }

    // Records Query
    $dataSql = "SELECT sm.student_id, sm.registration_no, sm.fname, sm.roll_no, sm.status,
                       COALESCE(dep.department_name, 'N/A') AS department_name,
                       COALESCE(sess.session_name, 'N/A') AS academic_year,
                       COALESCE(cl.class_name, 'N/A') AS class_name,
                       COALESCE(sem.semester_name, CONCAT('Semester ', sm.current_semester_id)) AS semester_name,
                       COALESCE(sec.sections, 'N/A') AS division_name,
                       COALESCE(sp.specialization_name, 'N/A') AS specialization_name,
                       (
                           SELECT u.user_name
                           FROM st_mentor_student_mapping msm
                           JOIN st_user_master u ON u.user_id = msm.mentor_id
                           WHERE msm.student_id = sm.student_id
                           ORDER BY msm.mapping_id DESC LIMIT 1
                       ) AS mentor_name,
                       (
                           SELECT u.user_name
                           FROM st_user_master u
                           WHERE u.role_id = 3 AND u.department_id = sm.department_id
                           LIMIT 1
                       ) AS hod_name
                FROM st_student_master sm
                LEFT JOIN st_department_master dep ON dep.department_id = sm.department_id
                LEFT JOIN st_session_master sess ON sess.session_id = sm.academic_year_id
                LEFT JOIN st_class_master cl ON cl.class_id = sm.class_id
                LEFT JOIN st_semester_master sem ON sem.semester_id = sm.current_semester_id
                LEFT JOIN st_section_master sec ON sec.id = sm.division_id
                LEFT JOIN st_specialization_master sp ON sp.specialization_id = sm.specialization_id
                WHERE $whereClause
                $orderClause
                LIMIT ? OFFSET ?";

    $dataStmt = mysqli_prepare($conn, $dataSql);
    $students = [];
    if ($dataStmt) {
        $dataTypes = $types . "ii";
        $dataParams = array_merge($params, [$perPage, $offset]);
        mysqli_stmt_bind_param($dataStmt, $dataTypes, ...$dataParams);
        mysqli_stmt_execute($dataStmt);
        $dataRes = mysqli_stmt_get_result($dataStmt);
        while ($row = mysqli_fetch_assoc($dataRes)) {
            $students[] = [
                'student_id' => intval($row['student_id']),
                'registration_no' => $row['registration_no'],
                'name' => $row['fname'],
                'roll_no' => $row['roll_no'] ?? 'N/A',
                'status' => intval($row['status']) === 1 ? 'Active' : 'Inactive',
                'department' => $row['department_name'],
                'academic_year' => $row['academic_year'],
                'class' => $row['class_name'],
                'semester' => $row['semester_name'],
                'division' => $row['division_name'],
                'specialization' => $row['specialization_name'],
                'mentor' => $row['mentor_name'] ?: 'Not Assigned',
                'hod' => $row['hod_name'] ?: 'Not Assigned'
            ];
        }
        mysqli_stmt_close($dataStmt);
    }

    echo json_encode(array(
        'success' => true,
        'students' => $students,
        'pagination' => array(
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ($total > 0) ? (int) ceil($total / $perPage) : 1
        )
    ));
    exit;
}

// -------------------------------------------------------------
// DEFAULT ACTION: Individual Student Progress Profile
// -------------------------------------------------------------
$registrationNo = trim($_GET['reg_no'] ?? $_POST['reg_no'] ?? '');

if (empty($registrationNo)) {
    echo json_encode(array('success' => false, 'message' => 'Please enter a Registration / Enrollment Number.'));
    exit;
}

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

if (!$db_handle->canAccessStudent($studentId, $sessionUserId, $sessionRoleId)) {
    echo json_encode(array('success' => false, 'message' => 'Access Denied: You do not have permission to view students from other departments.'));
    exit;
}

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
