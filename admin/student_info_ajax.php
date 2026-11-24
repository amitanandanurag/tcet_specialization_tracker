<?php
require_once __DIR__ . "/../database/db_connect.php";
if (class_exists('DBController')) {
    DBController::startSecureSession();
} elseif (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    @session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($db_handle) || !($db_handle instanceof DBController)) {
    $db_handle = new DBController();
}
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = $db_handle->conn;
}

// Resolve user session IDs with fallbacks
$sessionLoginId = intval($_SESSION['user_login_id'] ?? $_SESSION['user_session'] ?? 0);
$sessionUserId = intval($_SESSION['user_id'] ?? 0);
$sessionRoleId = intval($_SESSION['role_id'] ?? $_SESSION['user_type'] ?? 0);

// If role_id or user_id not yet populated, verify against st_login/st_user_master
if ($sessionRoleId <= 0 && ($sessionLoginId > 0 || $sessionUserId > 0)) {
    $authSql = "SELECT l.login_id, l.user_id, u.role_id, u.department_id 
                FROM st_login l 
                INNER JOIN st_user_master u ON u.user_id = l.user_id 
                WHERE " . ($sessionLoginId > 0 ? "l.login_id = '$sessionLoginId'" : "u.user_id = '$sessionUserId'") . " 
                LIMIT 1";
    $authRes = $db_handle->query($authSql);
    if ($authRes && ($authRow = $authRes->fetch_assoc())) {
        $sessionUserId = intval($authRow['user_id']);
        $sessionRoleId = intval($authRow['role_id']);
        $_SESSION['user_id'] = $sessionUserId;
        $_SESSION['role_id'] = $sessionRoleId;
        $_SESSION['user_type'] = $sessionRoleId;
        $_SESSION['department_id'] = intval($authRow['department_id'] ?? 0);
    }
}

// Enforce session authentication
if ($sessionUserId <= 0 || $sessionRoleId <= 0) {
    http_response_code(403);
    echo json_encode(['draw' => intval($_REQUEST['draw'] ?? 0), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Unauthorized access. Please login.']);
    exit();
}

$user_id   = $sessionUserId;
$user_role = $sessionRoleId;

// Only Super Admin (1), Admin (2), Coordinator/HOD (3), and Mentor (4) can view student directory
if ($user_role > 4) {
    http_response_code(403);
    echo json_encode(['draw' => intval($_REQUEST['draw'] ?? 0), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Forbidden access for current role.']);
    exit();
}

$user_scope = $db_handle->getUserScope($user_id, $user_role);
$department_id = intval($user_scope['department_id'] ?? 0);
if ($department_id <= 0 && isset($_SESSION['department_id']) && intval($_SESSION['department_id']) > 0) {
    $department_id = intval($_SESSION['department_id']);
}
if ($user_role == 3 && $department_id <= 0) {
    $department_id = -1; // Unassigned HOD/Coordinator sees 0 students (fail-closed)
}

$requestData = $_REQUEST;

// Get filter parameters
$select_class = trim($requestData['select_class'] ?? '');
$select_section = trim($requestData['select_section'] ?? '');
$select_session = trim($requestData['select_session'] ?? '');
$select_batch = trim($requestData['select_batch'] ?? '');
$select_semester = trim($requestData['select_semester'] ?? '');
$select_department = ($user_role == 3) ? $department_id : trim($requestData['select_department'] ?? '');
$select_specialization = trim($requestData['select_specialization'] ?? '');
$select_mentor = trim($requestData['select_mentor'] ?? '');
$searchValue = trim($requestData['search']['value'] ?? '');

// -----------------------------------------------------------------------------
// BUILD WHERE CLAUSES FOR st_student_master
// -----------------------------------------------------------------------------
$baseWhere = " WHERE 1=1";
$where = " WHERE 1=1";

// Role-based restrictions
if ($user_role == 3) {
    $baseWhere .= " AND sm.department_id = " . intval($department_id);
    $where     .= " AND sm.department_id = " . intval($department_id);
} elseif ($user_role == 4) {
    $escMentorUser = mysqli_real_escape_string($conn, $user_id);
    $mentorScopeSql = " AND sm.status = 1 AND (
        EXISTS (
            SELECT 1 FROM st_mentor_student_mapping msm2
            WHERE msm2.student_id = sm.student_id AND msm2.mentor_id = '$escMentorUser'
        )
        OR EXISTS (
            SELECT 1 FROM st_mentor_subject_mapping msub
            WHERE msub.mentor_id = '$escMentorUser'
              AND msub.subject_id = sm.specialization_subject_id
        )
    )";
    $baseWhere .= $mentorScopeSql;
    $where     .= $mentorScopeSql;
}

// Interactive Filters
if ($select_class !== '') {
    $where .= " AND sm.class_id = '" . mysqli_real_escape_string($conn, $select_class) . "'";
}
if ($select_section !== '') {
    $where .= " AND sm.division_id = '" . mysqli_real_escape_string($conn, $select_section) . "'";
}
if ($select_batch !== '') {
    $where .= " AND sm.grad_year = '" . mysqli_real_escape_string($conn, $select_batch) . "'";
}
if ($select_semester !== '') {
    $where .= " AND sm.current_semester_id = '" . mysqli_real_escape_string($conn, $select_semester) . "'";
}
if ($select_session !== '') {
    $where .= " AND sm.academic_year_id = '" . mysqli_real_escape_string($conn, $select_session) . "'";
}
if ($select_department !== '' && $user_role != 3) {
    $where .= " AND sm.department_id = '" . mysqli_real_escape_string($conn, $select_department) . "'";
}
if ($select_specialization !== '') {
    $where .= " AND sm.specialization_id = '" . mysqli_real_escape_string($conn, $select_specialization) . "'";
}
if ($select_mentor !== '') {
    $escMId = mysqli_real_escape_string($conn, $select_mentor);
    $where .= " AND EXISTS (SELECT 1 FROM st_mentor_student_mapping msm3 WHERE msm3.student_id = sm.student_id AND msm3.mentor_id = '$escMId')";
}
if ($searchValue !== '') {
    $escSearch = mysqli_real_escape_string($conn, $searchValue);
    $where .= " AND (
        sm.registration_no LIKE '%$escSearch%'
        OR sm.fname LIKE '%$escSearch%'
        OR sm.roll_no LIKE '%$escSearch%'
        OR sm.email LIKE '%$escSearch%'
        OR sm.mobile LIKE '%$escSearch%'
    )";
}

// -----------------------------------------------------------------------------
// COUNT QUERIES (recordsTotal & recordsFiltered)
// -----------------------------------------------------------------------------
$totRes = mysqli_query($conn, "SELECT COUNT(*) c FROM st_student_master sm $baseWhere");
$recordsTotal = intval(mysqli_fetch_assoc($totRes)['c'] ?? 0);

$filRes = mysqli_query($conn, "SELECT COUNT(*) c FROM st_student_master sm $where");
$recordsFiltered = intval(mysqli_fetch_assoc($filRes)['c'] ?? 0);

// -----------------------------------------------------------------------------
// DATA RETRIEVAL QUERY (Direct from st_student_master as single source of truth)
// -----------------------------------------------------------------------------
$columns = [
    0 => 'sm.student_id',
    1 => 'sm.roll_no',
    2 => 'sm.fname',
    3 => 'cl.class_name',
    4 => 'sec.sections',
    5 => 'sess.session_name',
    6 => 'sem.semester_name',
    7 => 'dep.department_name',
    8 => 'sp.specialization_name',
    9 => 'sm.cgpa',
    10 => 'u_mentor.user_name',
    11 => 'sm.student_id'
];

$orderColumnIndex = intval($requestData['order'][0]['column'] ?? 0);
$orderDir = strtoupper($requestData['order'][0]['dir'] ?? 'ASC');
if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
    $orderDir = 'ASC';
}
$orderField = $columns[$orderColumnIndex] ?? 'sm.student_id';

$start = max(0, intval($requestData['start'] ?? 0));
$length = intval($requestData['length'] ?? 15);
if ($length < 0) {
    $length = 15;
}

$sql = "SELECT 
    sm.student_id,
    COALESCE(NULLIF(sm.registration_no, ''), '-') AS erp_id,
    COALESCE(NULLIF(sm.roll_no, ''), '-') AS roll_no,
    sm.fname AS student_name,
    sm.class_id,
    sm.division_id,
    sm.department_id,
    sm.academic_year_id,
    sm.current_semester_id AS semester_id,
    sm.specialization_id,
    sm.cgpa,
    sm.mobile,
    sm.email,
    sm.status,
    IFNULL(cl.class_name, '') AS class_display,
    IFNULL(sec.sections, '') AS section_display,
    IFNULL(dep.department_name, '') AS department_name,
    IFNULL(sp.specialization_name, '') AS specialization_name,
    CASE 
        WHEN sm.specialization_subject_id IS NOT NULL AND sm.specialization_subject_id != 0 THEN IFNULL(ssb.subject_name, '')
        WHEN sm.minor_subject_id IS NOT NULL AND sm.minor_subject_id != 0 THEN IFNULL(ms.subject_name, '')
        ELSE ''
    END AS course_display,
    IFNULL(sess.session_name, '') AS academic_year_display,
    IFNULL(sem.semester_name, '') AS semester_display,
    msm.mentor_id,
    IFNULL(u_mentor.user_name, '') AS mentor_name
FROM st_student_master sm
LEFT JOIN st_class_master cl ON cl.class_id = sm.class_id
LEFT JOIN st_section_master sec ON sec.id = sm.division_id
LEFT JOIN st_department_master dep ON dep.department_id = sm.department_id
LEFT JOIN st_specialization_master sp ON sp.specialization_id = sm.specialization_id
LEFT JOIN st_specialization_subject_master ssb ON ssb.subject_id = sm.specialization_subject_id
LEFT JOIN st_minorsubject ms ON ms.subject_id = sm.minor_subject_id
LEFT JOIN st_session_master sess ON sess.session_id = sm.academic_year_id
LEFT JOIN st_semester_master sem ON sem.semester_id = sm.current_semester_id
LEFT JOIN st_mentor_student_mapping msm ON (msm.student_id = sm.student_id AND msm.semester_id = sm.current_semester_id)
LEFT JOIN st_user_master u_mentor ON u_mentor.user_id = msm.mentor_id
$where
ORDER BY $orderField $orderDir
LIMIT $start, $length";

$res = mysqli_query($conn, $sql);

$data = [];
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $studentId = intval($row['student_id']);
        $erpId     = htmlspecialchars($row['erp_id']);
        $rollNo    = htmlspecialchars($row['roll_no']);
        $name      = htmlspecialchars($row['student_name']);
        $status    = intval($row['status']);

        $nestedData = [];

        // Col 0: Checkbox
        $nestedData[] = "<input type='checkbox' class='activeStudentRow' value='{$studentId}'>";

        // Col 1: Roll No
        $nestedData[] = "<span class='text-mono' style='font-weight: 600; color: #1e293b; font-size: 12px;'>{$rollNo}</span>";

        // Col 2: Student Name + ERP ID
        $nameCell = "<div style='line-height: 1.25;'>";
        $nameCell .= "<div style='display: flex; align-items: center; gap: 6px;'>";
        $nameCell .= "<a href='javascript:void(0)' class='student_view' data-id='{$studentId}' style='font-weight: 700; color: #0f172a; font-size: 13px; text-decoration: none;' onmouseover=\"this.style.color='#0284c7'\" onmouseout=\"this.style.color='#0f172a'\" title='View Complete Student Profile'>{$name}</a>";
        if ($status === 1) {
            $nameCell .= "<span class='label label-success' style='font-size: 10px; padding: 2px 5px;'><i class='fa fa-check-circle'></i> Active</span>";
        } else {
            $nameCell .= "<span class='label label-default' style='font-size: 10px; padding: 2px 5px;'>Inactive</span>";
        }
        $nameCell .= "</div>";

        if ($erpId !== '' && $erpId !== '-') {
            $nameCell .= "<div style='font-size: 11px; color: #64748b; margin-top: 2px;' class='text-mono'>ERP: {$erpId}</div>";
        }
        $nameCell .= "</div>";
        $nestedData[] = $nameCell;

        // Col 3: Class
        $nestedData[] = htmlspecialchars($row['class_display'] ?: '-');

        // Col 4: Division
        $nestedData[] = htmlspecialchars($row['section_display'] ?: '-');

        // Col 5: Academic Year
        $nestedData[] = "<span style='color: #475569; font-size: 12px;'>" . htmlspecialchars($row['academic_year_display'] ?: '-') . "</span>";

        // Col 6: Semester
        $nestedData[] = "<span style='font-weight: 600; color: #1e293b; font-size: 12px;'>" . htmlspecialchars($row['semester_display'] ?: '-') . "</span>";

        // Col 7: Department
        $deptBadge = htmlspecialchars($row['department_name'] ?: '-');
        $nestedData[] = "<span style='display: inline-block; font-size: 11px; font-weight: 600; color: #334155; padding: 2px 6px; background: #f1f5f9; border-radius: 3px; border: 1px solid #e2e8f0;'>{$deptBadge}</span>";

        // Col 8: Specialization & Subject / Course
        $specName = trim($row['specialization_name'] ?? '');
        $courseName = trim($row['course_display'] ?? '');
        $courseHtml = "-";
        if ($courseName !== '' && $courseName !== '-') {
            $courseHtml = "<div style='line-height: 1.3;'>";
            $courseHtml .= "<span style='font-weight: 600; color: #1e293b; font-size: 12px;'>" . htmlspecialchars($courseName) . "</span>";
            if ($specName !== '' && $specName !== '-') {
                $courseHtml .= "<div style='font-size: 11px; color: #64748b; margin-top: 1px;'>" . htmlspecialchars($specName) . "</div>";
            }
            $courseHtml .= "</div>";
        } elseif ($specName !== '' && $specName !== '-') {
            $courseHtml = "<span style='color: #475569; font-size: 12px;'>" . htmlspecialchars($specName) . "</span>";
        }
        $nestedData[] = $courseHtml;

        // Col 9: CGPA
        $cgpaVal = !empty($row['cgpa']) ? number_format(floatval($row['cgpa']), 2) : '-';
        $nestedData[] = "<span class='text-mono col-num' style='font-weight: 600; color: #0f172a;'>{$cgpaVal}</span>";

        // Col 10: Mentor / Contact
        $mentorName = htmlspecialchars($row['mentor_name'] ?: 'Unassigned');
        $mobile = trim($row['mobile'] ?? '');
        $email = trim($row['email'] ?? '');

        $contactHtml = "<div style='line-height: 1.25;'>";
        if ($mentorName !== 'Unassigned') {
            $contactHtml .= "<div style='font-weight: 600; font-size: 12px; color: #1e293b;'><i class='fa fa-user-circle-o text-muted'></i> {$mentorName}</div>";
        }
        if ($mobile !== '' && $mobile !== '-') {
            $contactHtml .= "<div style='font-size: 11px; color: #475569; margin-top: 2px;'><i class='fa fa-phone text-muted'></i> {$mobile}</div>";
        } elseif ($email !== '' && $email !== '-') {
            $contactHtml .= "<div style='font-size: 11px; color: #64748b; margin-top: 2px;'><i class='fa fa-envelope-o text-muted'></i> {$email}</div>";
        } elseif ($mentorName === 'Unassigned') {
            $contactHtml .= "<span class='text-muted'>Unassigned</span>";
        }
        $contactHtml .= "</div>";
        $nestedData[] = $contactHtml;

        // Col 11: Actions
        $actionHtml = "<div style='display: flex; align-items: center; justify-content: center; gap: 4px;'>";
        $actionHtml .= "<button type='button' class='btn-erp-icon view-btn student_view' data-id='{$studentId}' title='View Student Profile' style='color: #0284c7; border-color: #bae6fd;'><i class='fa fa-eye'></i></button>";
        if ($user_role <= 2) {
            $actionHtml .= "<button type='button' class='btn-erp-icon edit-btn student_edit' data-id='{$studentId}' title='Edit Information'><i class='fa fa-pencil'></i></button>";
            $actionHtml .= "<button type='button' class='btn-erp-icon del-btn' onclick='delete_user({$studentId}, \"st_student_master\")' title='Delete Record'><i class='fa fa-trash'></i></button>";
        }
        $actionHtml .= "</div>";
        $nestedData[] = $actionHtml;

        $data[] = $nestedData;
    }
}

if (!defined('TCET_UNIT_TEST') || TCET_UNIT_TEST !== true) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
}

echo json_encode([
    "draw" => intval($requestData['draw'] ?? 0),
    "recordsTotal" => intval($recordsTotal),
    "recordsFiltered" => intval($recordsFiltered),
    "data" => $data
]);

if (!defined('TCET_UNIT_TEST') || TCET_UNIT_TEST !== true) {
    exit;
}
