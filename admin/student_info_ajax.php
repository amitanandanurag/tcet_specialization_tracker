<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require "../database/db_connect.php";
$db_handle = new DBController();

// Enforce session authentication
if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
    http_response_code(403);
    echo json_encode(['draw' => intval($_REQUEST['draw'] ?? 0), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Unauthorized access. Please login.']);
    exit();
}

// Get current user's role and department
$user_id   = intval($_SESSION['user_id'] ?? 0);
$user_role = intval($_SESSION['role_id'] ?? 0);

// Only Super Admin (1), Admin (2), Coordinator (3), and Mentor (4) can view student directory
if ($user_role > 4) {
    http_response_code(403);
    echo json_encode(['draw' => intval($_REQUEST['draw'] ?? 0), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Forbidden access for current role.']);
    exit();
}

$department_id = '';

if ($user_role == 3) {

    $qry = "
        SELECT department_id
        FROM st_user_master
        WHERE user_id='$user_id'
    ";

    $res = $db_handle->conn->query($qry);

    if($res && $res->num_rows){
        $department_id = $res->fetch_assoc()['department_id'];
    }
}


$requestData = $_REQUEST;

// Get filter values
$select_class = $_POST['select_class'] ?? '';
$select_section = $_POST['select_section'] ?? '';
$select_session = $_POST['select_session'] ?? '';
$select_batch = $_POST['select_batch'] ?? '';
$select_semester = $_POST['select_semester'] ?? '';
$select_department = $_POST['select_department'] ?? '';

// Calculate total data count (before filters)
$totalData = 0;
$totalSql = "SELECT COUNT(DISTINCT sm.student_id) total FROM st_student_master sm
LEFT JOIN st_student_semester_history h_current
    ON h_current.student_id = sm.student_id AND h_current.semester_id = sm.current_semester_id
LEFT JOIN st_mentor_student_mapping msm
    ON msm.student_id=sm.student_id AND msm.semester_id=sm.current_semester_id WHERE 1=1";
if($user_role==3){

    $totalSql .= " AND sm.department_id='$department_id'";

}
elseif($user_role==4){

    $totalSql .= " AND sm.status = 1 AND EXISTS (
                SELECT 1 FROM st_mentor_subject_mapping msub
                        JOIN st_specialization_subject_master current_subject ON current_subject.subject_id = msub.subject_id
                        JOIN st_user_master current_mentor ON current_mentor.user_id = msub.mentor_id
        WHERE msub.mentor_id = '$user_id'
                      AND msub.subject_id = COALESCE(NULLIF(sm.specialization_subject_id, 0), h_current.specialization_subject_id)
                            AND current_subject.semester_id = sm.current_semester_id
                            AND current_subject.department_id = sm.department_id
                            AND current_mentor.department_id = sm.department_id
        )";

}

$totalResult = $db_handle->query($totalSql);
if ($totalResult && $totalRow = $totalResult->fetch_assoc()) {
    $totalData = intval($totalRow['total']);
}

// Build the main query with proper joins for both honors and minor subjects
$sql = "SELECT
    sm.student_id,
    sm.registration_no,
    sm.academic_year_id,
    sm.roll_no,
    sm.fname,
    sm.class_id,
    sm.division_id,
    sm.grad_year,
    sm.department_id,
    sm.specialization_id,
    sm.specialization_subject_id,
    sm.minor_course_id,
    sm.minor_subject_id,
    sm.cgpa,
    sm.mobile,
    sm.email,
    sm.mark_list,
    sm.status,
    sm.created_at,
    sm.current_semester_id,

    IFNULL(cl.class_name,'') AS class_display,
    IFNULL(sec.sections,'') AS section_display,
    IFNULL(dep.department_name,'') AS department_name,
    IFNULL(sp.specialization_name,'') AS specialization_name,

    CASE
           WHEN COALESCE(NULLIF(sm.specialization_subject_id, 0), h_current.specialization_subject_id) IS NOT NULL
               AND COALESCE(NULLIF(sm.specialization_subject_id, 0), h_current.specialization_subject_id) <> 0
        THEN IFNULL(ssb.subject_name,'')

        WHEN sm.minor_subject_id IS NOT NULL
             AND sm.minor_subject_id <> 0
        THEN IFNULL(ms.subject_name,'')

        ELSE ''
    END AS specialization_subject_name,

    IFNULL(mc.course_name,'') AS minor_course_name,
    IFNULL(ms.subject_name,'') AS minor_subject_name,
    IFNULL(sess.session_name,'') AS academic_year_name,
    IFNULL(sem.semester_name,'') AS semester_name,

    msm.mentor_id,
    msm.assigned_at

FROM st_student_master sm

LEFT JOIN st_mentor_student_mapping msm
ON msm.student_id = sm.student_id
AND msm.semester_id = sm.current_semester_id

LEFT JOIN st_student_semester_history h_current
ON h_current.student_id = sm.student_id
AND h_current.semester_id = sm.current_semester_id

LEFT JOIN st_class_master cl
       ON cl.class_id = sm.class_id

LEFT JOIN st_section_master sec
       ON sec.id = sm.division_id

LEFT JOIN st_department_master dep
       ON dep.department_id = sm.department_id

LEFT JOIN st_specialization_master sp
       ON sp.specialization_id = sm.specialization_id

LEFT JOIN st_specialization_subject_master ssb
    ON ssb.subject_id = COALESCE(NULLIF(sm.specialization_subject_id, 0), h_current.specialization_subject_id)

LEFT JOIN st_minorcourse mc
       ON mc.course_id = sm.minor_course_id

LEFT JOIN st_minorsubject ms
       ON ms.subject_id = sm.minor_subject_id

LEFT JOIN st_session_master sess
       ON sess.session_id = sm.academic_year_id

LEFT JOIN st_semester_master sem
       ON sem.semester_id = sm.current_semester_id

WHERE 1=1";

if($user_role==1 || $user_role==2){

    // No restriction

}

// Role 3 HOD
elseif($user_role==3){

    $select_department=$department_id;

}

// Role 4 Mentor
elseif($user_role==4){

        $sql .= " AND sm.status = 1
                            AND EXISTS (
                                SELECT 1 FROM st_mentor_subject_mapping msub
                                JOIN st_specialization_subject_master current_subject ON current_subject.subject_id = msub.subject_id
                                JOIN st_user_master current_mentor ON current_mentor.user_id = msub.mentor_id
                                WHERE msub.mentor_id = '" . mysqli_real_escape_string($db_handle->conn, $user_id) . "'
                                    AND msub.subject_id = COALESCE(NULLIF(sm.specialization_subject_id, 0), h_current.specialization_subject_id)
                                    AND current_subject.semester_id = sm.current_semester_id
                                    AND current_subject.department_id = sm.department_id
                                    AND current_mentor.department_id = sm.department_id
                            )";

}

// Apply filters
if (!empty($select_class)) {
    $sql .= " AND sm.class_id = '" . mysqli_real_escape_string($db_handle->conn, $select_class) . "'";
}
if (!empty($select_section)) {
    $sql .= " AND sm.division_id = '" . mysqli_real_escape_string($db_handle->conn, $select_section) . "'";
}
if (!empty($select_batch)) {
    $sql .= " AND sm.grad_year = '" . mysqli_real_escape_string($db_handle->conn, $select_batch) . "'";
}
if (!empty($select_semester)) {
    $sql .= " AND sm.current_semester_id = '" . mysqli_real_escape_string($db_handle->conn, $select_semester) . "'";
}
if (!empty($select_session)) {
    $sql .= " AND sm.academic_year_id = '" . mysqli_real_escape_string($db_handle->conn, $select_session) . "'";
}
if (!empty($select_department)) {
    $sql .= " AND sm.department_id = '" . mysqli_real_escape_string($db_handle->conn, $select_department) . "'";
}

if (!empty($searchValue)) {
    $sql .= " AND (
        sm.registration_no LIKE '%$searchValue%'
        OR sm.fname LIKE '%$searchValue%'
        OR sm.lname LIKE '%$searchValue%'
        OR sm.roll_no LIKE '%$searchValue%'
        OR sm.email LIKE '%$searchValue%'
        OR sm.mobile LIKE '%$searchValue%'
        OR cl.class_name LIKE '%$searchValue%'
        OR sec.sections LIKE '%$searchValue%'
        OR dep.department_name LIKE '%$searchValue%'
        OR sp.specialization_name LIKE '%$searchValue%'
        OR ssb.subject_name LIKE '%$searchValue%'
        OR sm.academic_year LIKE '%$searchValue%'
    )";
}

if (!empty($departmentFilterSql)) {
    $sql .= $departmentFilterSql;
}

// Add search functionality
if (!empty($requestData['search']['value'])) {
    $search = mysqli_real_escape_string($db_handle->conn, $requestData['search']['value']);
    $sql .= " AND (sm.registration_no LIKE '%$search%' 
                OR sm.fname LIKE '%$search%' 
                OR sm.roll_no LIKE '%$search%'
                OR sm.email LIKE '%$search%'
                OR sm.mobile LIKE '%$search%')";
}

$filteredResult = $db_handle->query($sql);
$totalFiltered = 0;
if ($filteredResult) {
    $filteredIds = array();
    while ($filteredRow = $filteredResult->fetch_assoc()) {
        $filteredIds[(int) $filteredRow['student_id']] = true;
    }
    $totalFiltered = count($filteredIds);
}

// Ordering
$orderColumn = 'sm.student_id';
$orderDir = 'DESC';

if (isset($requestData['order'][0]['column'])) {
    $columns = [
        0 => 'sm.student_id',
        1 => 'sm.roll_no',
        2 => 'sm.fname',
        3 => 'cl.class_name',
        4 => 'sec.sections',
        5 => 'sess.session_name',
        6 => 'sem.semester_name',
        7 => 'dep.department_name',
        8 => 'specialization_subject_name',
        9 => 'sm.cgpa',
        10 => 'sm.mobile',
        11 => 'sm.student_id'
    ];
    $colIndex = intval($requestData['order'][0]['column']);
    if (isset($columns[$colIndex])) {
        $orderColumn = $columns[$colIndex];
    }
    $orderDir = strtoupper($requestData['order'][0]['dir']) === 'ASC' ? 'ASC' : 'DESC';
}

// Pagination
$start = intval($requestData['start'] ?? 0);
$length = intval($requestData['length'] ?? 15);

$sql .= " ORDER BY $orderColumn $orderDir LIMIT $start, $length";
$result = $db_handle->query($sql);

// Prepare data
$data = [];
$counter = $start + 1;

while ($row = mysqli_fetch_assoc($result)) {
    $nestedData = [];
    $studentId = intval($row['student_id']);
    $nestedData[] = "<input type='checkbox' class='selectRow' value='{$studentId}' style='cursor: pointer;' />";

    // 1. Roll No
    $rollNo = !empty($row['roll_no']) ? htmlspecialchars($row['roll_no']) : '-';
    $nestedData[] = "<span class='text-mono' style='font-weight: 600; color: #1e293b;'>{$rollNo}</span>";

    // 2. Student Name & ERP ID (Two-line cell)
    $fullName = trim($row['fname'] ?? '');
    $regNo = trim($row['registration_no'] ?? '');
    $studentCell = "<div class='student-cell'>";
    $studentCell .= "<span class='student-cell-name'>" . htmlspecialchars($fullName !== '' ? $fullName : 'Unknown Student') . "</span>";
    if ($regNo !== '') {
        $studentCell .= "<span class='student-cell-reg'>ERP: " . htmlspecialchars($regNo) . "</span>";
    }
    $studentCell .= "</div>";
    $nestedData[] = $studentCell;

    // 3. Class
    $nestedData[] = !empty($row['class_display']) ? htmlspecialchars($row['class_display']) : '-';

    // 4. Division
    $nestedData[] = !empty($row['section_display']) ? htmlspecialchars($row['section_display']) : '-';

    // 5. Academic Year
    $nestedData[] = !empty($row['academic_year_name']) ? '<span style="color: #475569;">' . htmlspecialchars($row['academic_year_name']) . '</span>' : '-';

    // 6. Semester
    $semName = !empty($row['semester_name']) ? htmlspecialchars($row['semester_name']) : '-';
    $nestedData[] = "<span style='font-weight: 600; color: #1e293b;'>{$semName}</span>";

    // 7. Department
    $deptName = !empty($row['department_name']) ? htmlspecialchars($row['department_name']) : '-';
    $nestedData[] = "<span style='display: inline-block; font-size: 11px; font-weight: 600; color: #334155; padding: 2px 6px; background: #f1f5f9; border-radius: 3px; border: 1px solid #e2e8f0;'>{$deptName}</span>";

    // 8. Specialization & Subject
    $specName = trim($row['specialization_name'] ?? '');
    $subName = trim($row['specialization_subject_name'] ?? '');
    $subjectDisplay = '-';
    if ($subName !== '') {
        $subjectDisplay = "<div style='line-height: 1.3;'>";
        $subjectDisplay .= "<span style='font-weight: 600; color: #1e293b; font-size: 12px;'>" . htmlspecialchars($subName) . "</span>";
        if ($specName !== '') {
            $subjectDisplay .= "<div style='font-size: 11px; color: #64748b; margin-top: 1px;'>" . htmlspecialchars($specName) . "</div>";
        }
        $subjectDisplay .= "</div>";
    } elseif ($specName !== '') {
        $subjectDisplay = "<span style='color: #475569; font-size: 12px;'>" . htmlspecialchars($specName) . "</span>";
    }
    $nestedData[] = $subjectDisplay;

    // 9. CGPA
    $cgpa = !empty($row['cgpa']) ? number_format(floatval($row['cgpa']), 2) : '-';
    $nestedData[] = "<span class='text-mono col-num' style='font-weight: 600; color: #0f172a;'>{$cgpa}</span>";

    // 10. Contact (Mobile & WhatsApp & Email)
    $mobile = trim($row['mobile'] ?? '');
    $email = trim($row['email'] ?? '');
    $contactHtml = "<div style='display: flex; align-items: center; gap: 6px;'>";
    if ($mobile !== '') {
        $waMsg = "Dear%20" . urlencode($fullName) . "%2C%20Welcome%20to%20Thakur%20College.";
        $contactHtml .= "<a href='https://wa.me/91{$mobile}?text={$waMsg}' target='_blank' class='btn-erp-icon' style='color: #16a34a; border-color: #bbf7d0;' title='WhatsApp: {$mobile}'><i class='fa fa-whatsapp'></i></a>";
        $contactHtml .= "<span class='text-mono' style='font-size: 11px; color: #475569;'>{$mobile}</span>";
    } elseif ($email !== '') {
        $contactHtml .= "<a href='mailto:{$email}' class='btn-erp-icon' title='Email: {$email}'><i class='fa fa-envelope-o'></i></a>";
    } else {
        $contactHtml .= "<span class='text-muted'>-</span>";
    }
    $contactHtml .= "</div>";
    $nestedData[] = $contactHtml;

    // 11. Actions
    $actionHtml = "<div style='display: flex; align-items: center; justify-content: center; gap: 4px;'>";
    $actionHtml .= "<button type='button' class='btn-erp-icon view-btn student_view' data-id='{$studentId}' title='View Student Record'><i class='fa fa-eye'></i></button>";
    $actionHtml .= "<button type='button' class='btn-erp-icon edit-btn student_edit' data-id='{$studentId}' title='Edit Information'><i class='fa fa-pencil'></i></button>";
    $actionHtml .= "<button type='button' class='btn-erp-icon del-btn' onclick='delete_user({$studentId}, \"st_student_master\")' title='Delete Record'><i class='fa fa-trash'></i></button>";
    $actionHtml .= "</div>";
    $nestedData[] = $actionHtml;

    $data[] = $nestedData;
}

while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/json');
echo json_encode([
    "draw" => intval($requestData['draw']),
    "recordsTotal" => intval($totalData),
    "recordsFiltered" => intval($totalFiltered),
    "data" => $data
]);
exit;
?>