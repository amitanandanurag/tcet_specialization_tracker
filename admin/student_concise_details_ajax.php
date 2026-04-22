<?php 
session_start(); 
require "../database/db_connect.php";

$db_handle = new DBController(); 
$requestData = $_REQUEST;

// for filters
$select_class = $_POST['select_class'] ?? '';
$select_section = $_POST['select_section'] ?? '';
$select_department = $_POST['select_department'] ?? '';
$select_specialization = $_POST['select_specialization'] ?? ''; 
$select_specialization_subject = $_POST['select_specialization_subject'] ?? '';

//base query 

$baseSql = " FROM st_student_master sm

INNER JOIN st_class_master cm 
    ON cm.class_id = sm.class_id

INNER JOIN st_section_master sec 
    ON sec.id = sm.division_id

INNER JOIN st_department_master dept 
    ON dept.department_id = sm.department_id

INNER JOIN st_specialization_master spec 
    ON spec.specialization_id = sm.specialization_id

INNER JOIN st_specialization_subject_master subj 
    ON subj.subject_id = sm.specialization_subject_id

WHERE sm.status = '1'
";

//filter application  
if (!empty($select_class)) {
    $baseSql .= " AND sm.class_id = '" . mysqli_real_escape_string($db_handle->conn, $select_class) . "'";
}

if (!empty($select_section)) {
    $baseSql .= " AND sm.division_id = '" . mysqli_real_escape_string($db_handle->conn, $select_section) . "'";
}

if (!empty($select_department)) {
    $baseSql .= " AND sm.department_id = '" . mysqli_real_escape_string($db_handle->conn, $select_department) . "'";
}

if (!empty($select_specialization)) {
    $baseSql .= " AND sm.specialization_id = '" . mysqli_real_escape_string($db_handle->conn, $select_specialization) . "'";
}

if (!empty($select_specialization_subject)) {
    $baseSql .= " AND sm.specialization_subject_id = '" . mysqli_real_escape_string($db_handle->conn, $select_specialization_subject) . "'";
}

//total records 

$countSql = "SELECT COUNT(*) as total " . $baseSql;
$countResult = $db_handle->query($countSql);
$totalRow = mysqli_fetch_assoc($countResult);

$totalData = $totalRow['total'];
$totalFiltered = $totalData;

//final data query 

$sql = "SELECT 
    cm.class_name AS class,
    sec.sections AS division,
    dept.department_name AS department,
    spec.specialization_name AS specialization,
    subj.subject_name AS specialization_subject,
    COUNT(*) AS student_count
    $baseSql
    GROUP BY 
        sm.class_id, 
        sm.division_id, 
        sm.department_id, 
        sm.specialization_id, 
        sm.specialization_subject_id
    ORDER BY cm.class_name ASC
";

//pagination 
$start  = $requestData['start'] ?? 0;
$length = $requestData['length'] ?? 10;

if ($length != -1) {
    $sql .= " LIMIT $start, $length";
}

$result = $db_handle->query($sql);

//data processing
$data = array();
$srNo = $start;
$totalStudentCount = 0;

while ($row = mysqli_fetch_assoc($result)) {

    $count = (int)$row["student_count"];

    $data[] = array(
        ++$srNo,
        $row["class"],
        $row["division"],
        $row["department"],
        $row["specialization"],
        $row["specialization_subject"],
        $count
    );

    $totalStudentCount += $count;
}

//adding total row
$data[] = array(
    '',
    '<b>Total</b>',
    '',
    '',
    '',
    '',
    '<b>'.$totalStudentCount.'</b>'
);

//returning json response
echo json_encode(array(
    "draw" => intval($requestData['draw'] ?? 0),
    "recordsTotal" => intval($totalData),
    "recordsFiltered" => intval($totalFiltered),
    "data" => $data
));
?>