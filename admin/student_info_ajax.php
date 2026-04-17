<?php
session_start();
require "../database/db_connect.php";
$db_handle = new DBController();

$requestData = $_REQUEST;
$select_class = $_POST['select_class'] ?? '';
$select_section = $_POST['select_section'] ?? '';
$student_table = "st_student_master";

$columns = array(
  0 => 'student_id',
  1 => 'student_id',
  2 => 'student_id',
  3 => 'student_id',
  4 => 'registration_no',
  5 => 'fname',
  6 => 'class_id',
  7 => 'division_id',
  8 => 'department_id',
  9 => 'specialization_id',
  10 => 'specialization_subject_id',
  11 => 'cgpa',
  12 => 'mobile',
  13 => 'academic_year',
  14 => 'roll_no',
  15 => 'joining_date',
  16 => 'dob',
  17 => 'gender',
  18 => 'email',
  19 => 'city',
  20 => 'permanent_address',
  21 => 'student_id',
  22 => 'student_id',
  23 => 'student_id'
);

$sql = "SELECT
  sm.student_id,
  sm.photo,
  sm.mobile,
  sm.registration_no,
  sm.academic_year,
  sm.roll_no,
  sm.fname,
  sm.mname,
  sm.lname,
  sm.dob,
  sm.gender,
  sm.joining_date,
  sm.permanent_address,
  sm.email,
  sm.city,
  IFNULL(cl.class_name, '') AS class_display,
  IFNULL(sec.sections, '') AS section_display,
  IFNULL(dep.department_name, '') AS department_name,
  IFNULL(sp.specialization_name, '') AS specialization_name,
  IFNULL(ssb.subject_name, '') AS specialization_subject_name,
  IFNULL(sm.cgpa, '') AS cgpa
FROM $student_table sm
LEFT JOIN st_class_master cl ON cl.class_id = sm.class_id
LEFT JOIN st_section_master sec ON sec.id = sm.division_id
LEFT JOIN st_department_master dep ON dep.department_id = sm.department_id
LEFT JOIN st_specialization_master sp ON sp.specialization_id = sm.specialization_id
LEFT JOIN st_specialization_subject_master ssb ON ssb.subject_id = sm.specialization_subject_id
WHERE sm.status='1'";

if (!empty($select_class) && !empty($select_section)) {
  $sql .= " AND sm.class_id='".mysqli_real_escape_string($db_handle->conn, $select_class)."' ";
  $sql .= " AND sm.division_id='".mysqli_real_escape_string($db_handle->conn, $select_section)."' ";
}

if (!empty($requestData['search']['value'])) {
  $searchValue = mysqli_real_escape_string($db_handle->conn, $requestData['search']['value']);
  $sql .= " AND (sm.registration_no LIKE '".$searchValue."%' OR sm.fname LIKE '".$searchValue."%' OR sm.lname LIKE '".$searchValue."%') ";
}

$result = $db_handle->query($sql);
$totalData = $db_handle->numRows($sql);
$totalFiltered = $totalData;

$orderColumnIndex = isset($requestData['order'][0]['column']) ? intval($requestData['order'][0]['column']) : 0;
$orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'student_id';
if (!preg_match('/^[a-zA-Z0-9_]+$/', $orderColumn)) {
  $orderColumn = 'student_id';
}
$orderDir = (isset($requestData['order'][0]['dir']) && strtolower($requestData['order'][0]['dir']) === 'desc') ? 'DESC' : 'ASC';
$start = isset($requestData['start']) ? intval($requestData['start']) : 0;
$length = isset($requestData['length']) ? intval($requestData['length']) : 15;

$sql .= " ORDER BY sm.".$orderColumn." ".$orderDir." LIMIT ".$start." ,".$length;
$result = $db_handle->query($sql);

$data = array();
while ($row = $result->fetch_assoc()) {
  $photo = $row["photo"];
  if (empty($photo)) {
    $photo = "student.JPG";
  }

  $mobile_no = $row["mobile"];
  $nestedData = array();
  $student_id=$row["student_id"];
  $nestedData[] = "<input type='checkbox' class='selectRow' value='".$row['student_id']."' />";
  $nestedData[] = $requestData['start'] = $requestData['start'] + 1;
  $nestedData[] = "<a href='whatsapp://send?phone=+91$mobile_no?text=WELCOME TO THAKUR COLLEGE OF ENGINEERING AND TECHNOLOGY' action='share/whatsapp/share'><button class='btn bg-gray btn-sm' type='button'><i class='fa fa-whatsapp' style='font-size:30px;color:green'></i></button></a>";
  $nestedData[] = '<a href="student_photo/'.$photo.'"><img src="student_photo/'.$photo.'" width="50" height="50" style="border-radius:50%;" /></a>';
  $nestedData[] = $row["registration_no"];
  $nestedData[] = "<div align='left'>".$row["fname"]." ".$row["mname"]." ".$row["lname"]."</div>";
  $nestedData[] = $row["class_display"];
  $nestedData[] = $row["section_display"];
  $nestedData[] = $row["department_name"];
  $nestedData[] = $row["specialization_name"];
  $nestedData[] = $row["specialization_subject_name"];
  $nestedData[] = $row["cgpa"];
  $nestedData[] = $row["mobile"];
  $nestedData[] = $row["academic_year"];
  $nestedData[] = $row["roll_no"];
  $nestedData[] = $row["joining_date"];
  $nestedData[] = $row["dob"];
  $nestedData[] = $row["gender"];
  $nestedData[] = $row["email"];
  $nestedData[] = $row["city"];
  $nestedData[] = $row["permanent_address"];
  $nestedData[] = "<button class='btn btn-primary btn-sm' type='button' id='student_view' data-id='".$row["student_id"]."'> <i class='fa fa-eye'></i> </button>";
  $nestedData[] = "<button class='btn bg-olive btn-sm' type='button' id='student_edit' data-id='".$row["student_id"]."'> <i class='fa fa-pencil'></i> </button>";
  $nestedData[] = "<a onclick='delete_user(\"".$row["student_id"]."\", \"".$student_table."\")'><button class='btn btn-danger btn-sm' type='button'><i class='fa fa-trash'></i></button></a>";
  $data[] = $nestedData;
}

$json_data = array(
  "recordsTotal" => intval($totalData),
  "recordsFiltered" => intval($totalFiltered),
  "data" => $data
);

echo json_encode($json_data);
?>