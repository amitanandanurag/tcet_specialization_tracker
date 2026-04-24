<?php
session_start();
require "../database/db_connect.php";
$db_handle = new DBController();

$requestData = $_REQUEST;
$select_class = $_POST['select_class'] ?? '';
$select_section = $_POST['select_section'] ?? '';
$select_session = $_POST['select_session'] ?? '';
$searchValue = '';

if (!empty($requestData['search']['value'])) {
    $searchValue = mysqli_real_escape_string($db_handle->conn, $requestData['search']['value']);
} elseif (!empty($_POST['search_value'])) {
    $searchValue = mysqli_real_escape_string($db_handle->conn, $_POST['search_value']);
}

// Build shared FROM/WHERE so all filters apply consistently.
$baseSql = "FROM st_student_master sm
LEFT JOIN st_class_master cl ON cl.class_id = sm.class_id
LEFT JOIN st_section_master sec ON sec.id = sm.division_id
LEFT JOIN st_department_master dep ON dep.department_id = sm.department_id
LEFT JOIN st_specialization_master sp ON sp.specialization_id = sm.specialization_id
LEFT JOIN st_specialization_subject_master ssb ON ssb.subject_id = sm.specialization_subject_id
WHERE sm.status = '0'";

if (!empty($select_class)) {
    $baseSql .= " AND sm.class_id = '" . mysqli_real_escape_string($db_handle->conn, $select_class) . "'";
}
if (!empty($select_section)) {
    $baseSql .= " AND sm.division_id = '" . mysqli_real_escape_string($db_handle->conn, $select_section) . "'";
}
if (!empty($select_session)) {
    $baseSql .= " AND sm.academic_year = '" . mysqli_real_escape_string($db_handle->conn, $select_session) . "'";
}
if (!empty($searchValue)) {
    $baseSql .= " AND (
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

$sql = "SELECT
    sm.student_id,
    sm.registration_no,
    sm.academic_year,
    sm.roll_no,
    sm.fname,
    sm.class_id,
    sm.division_id,
    sm.grad_year,
    sm.department_id,
    sm.specialization_id,
    sm.specialization_subject_id,
    sm.cgpa,
    sm.mobile,
    sm.email,
    sm.mark_list,
    sm.status,
    sm.created_at,
    IFNULL(cl.class_name, '') AS class_display,
    IFNULL(sec.sections, '') AS section_display,
    IFNULL(dep.department_name, '') AS department_name,
    IFNULL(sp.specialization_name, '') AS specialization_name,
    IFNULL(ssb.subject_name, '') AS specialization_subject_name
{$baseSql}";

// Total counts
$totalDataQuery = "SELECT COUNT(*) AS total FROM st_student_master sm WHERE sm.status = '0'";
$totalDataResult = $db_handle->query($totalDataQuery);
$totalDataRow = $totalDataResult ? mysqli_fetch_assoc($totalDataResult) : ['total' => 0];
$totalData = (int) ($totalDataRow['total'] ?? 0);

$totalFilteredQuery = "SELECT COUNT(*) AS total {$baseSql}";
$totalFilteredResult = $db_handle->query($totalFilteredQuery);
$totalFilteredRow = $totalFilteredResult ? mysqli_fetch_assoc($totalFilteredResult) : ['total' => 0];
$totalFiltered = (int) ($totalFilteredRow['total'] ?? 0);

// Ordering
$orderColumn = 'sm.student_id';
$orderDir = 'DESC';
if (isset($requestData['order'][0]['column'])) {
    $columns = ['sm.student_id', 'sm.student_id', 'sm.student_id', 'sm.registration_no', 'sm.fname', 'class_display', 'section_display', 'department_name', 'specialization_name', 'specialization_subject_name', 'sm.cgpa', 'sm.mobile', 'sm.academic_year', 'sm.roll_no', 'sm.email'];
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
    
    // Checkbox
    $nestedData[] = "<input type='checkbox' class='selectRow' value='{$row['student_id']}' />";
    
    // SR NO
    $nestedData[] = $counter++;
    
    // WhatsApp
    $mobile = $row['mobile'];
    $nestedData[] = "<a href='https://wa.me/91$mobile?text=WELCOME%20TO%20THAKUR%20COLLEGE' target='_blank'>
                            <button class='btn btn-success btn-sm'><i class='fa fa-whatsapp'></i></button>
                         </a>";
    
    // Registration No
    $nestedData[] = "<strong>{$row['registration_no']}</strong>";
    
    // Name
    $nestedData[] = "<div align='left'><strong>" . htmlspecialchars($row['fname']) . "</strong></div>";
    
    // Class
    $nestedData[] = !empty($row['class_display']) ? $row['class_display'] : '-';
    
    // Division
    $nestedData[] = !empty($row['section_display']) ? $row['section_display'] : '-';
    
    // Department
    $nestedData[] = !empty($row['department_name']) ? $row['department_name'] : '-';
    
    // Specialization
    $nestedData[] = !empty($row['specialization_name']) ? $row['specialization_name'] : '-';
    
    // Specialization Subject
    $nestedData[] = !empty($row['specialization_subject_name']) ? $row['specialization_subject_name'] : '-';
    
    // CGPA
    $nestedData[] = !empty($row['cgpa']) ? number_format($row['cgpa'], 2) : '-';
    
    
    // Mobile
    $nestedData[] = !empty($row['mobile']) ? $row['mobile'] : '-';

    // Session
    $nestedData[] = !empty($row['academic_year']) ? $row['academic_year'] : '-';
    
    // Roll No
    $nestedData[] = !empty($row['roll_no']) ? $row['roll_no'] : '-';
    
    // Email
    $nestedData[] = !empty($row['email']) ? "<a href='mailto:{$row['email']}'>" . htmlspecialchars($row['email']) . "</a>" : '-';
    
    // View Button
    $nestedData[] = "<button class='btn btn-primary btn-sm' id='student_view' data-id='{$row['student_id']}'><i class='fa fa-eye'></i></button>";
    
    // Edit Button
    $nestedData[] = "<button class='btn bg-olive btn-sm' id='student_edit' data-id='{$row['student_id']}'><i class='fa fa-pencil'></i></button>";
    
    // Delete Button
    $nestedData[] = "<button class='btn btn-danger btn-sm' onclick='delete_user({$row['student_id']}, \"st_student_master\")'><i class='fa fa-trash'></i></button>";
    
    $data[] = $nestedData;
}

echo json_encode([
    "draw" => intval($requestData['draw']),
    "recordsTotal" => intval($totalData),
    "recordsFiltered" => intval($totalFiltered),
    "data" => $data
]);
?>
