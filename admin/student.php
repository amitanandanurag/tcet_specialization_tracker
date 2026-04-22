<?php
include "header/header.php";
require_once "../database/db_connect.php";
$database = new DBController();

if (isset($_POST['save'])) {
  $conn = $database->conn;

  // Get form data
  $academic_year = mysqli_real_escape_string($conn, $_POST['academic'] ?? '');
  $registration_no = mysqli_real_escape_string($conn, $_POST['registration_no'] ?? '');
  $class_id = mysqli_real_escape_string($conn, $_POST['class'] ?? '');
  $division_id = mysqli_real_escape_string($conn, $_POST['batch'] ?? '');

  // Handle grad_year
  $grad_year = 'NULL';
  if (!empty($_POST['batch_id']) && $_POST['batch_id'] != 'Select Year' && is_numeric($_POST['batch_id'])) {
    $grad_year = "'" . mysqli_real_escape_string($conn, $_POST['batch_id']) . "'";
  }

  $roll_no = !empty($_POST['roll_no']) ? "'" . mysqli_real_escape_string($conn, $_POST['roll_no']) . "'" : 'NULL';

  // Handle department_id
  $department_id = 'NULL';
  if (!empty($_POST['department_id']) && $_POST['department_id'] != 'Select Department' && is_numeric($_POST['department_id'])) {
    $department_id = "'" . mysqli_real_escape_string($conn, $_POST['department_id']) . "'";
  }

  // Handle specialization_id
  $specialization_id = 'NULL';
  if (!empty($_POST['specialization_id']) && $_POST['specialization_id'] != 'Select Specialization' && is_numeric($_POST['specialization_id'])) {
    $specialization_id = "'" . mysqli_real_escape_string($conn, $_POST['specialization_id']) . "'";
  }

  // Handle specialization_subject_id
  $specialization_subject_id = 'NULL';
  if (!empty($_POST['unaided_subject']) && $_POST['unaided_subject'] != 'Select Specialization Subject' && is_numeric($_POST['unaided_subject'])) {
    $specialization_subject_id = "'" . mysqli_real_escape_string($conn, $_POST['unaided_subject']) . "'";
  }

  // Handle CGPA - Check which CGPA to use
  $cgpa = 'NULL';
  $specializationText = strtolower($_POST['specialization_id'] ?? '');

  // Get specialization name to check if it's minor or honours
  $spec_check = mysqli_query($conn, "SELECT specialization_name FROM st_specialization_master WHERE specialization_id = '" . mysqli_real_escape_string($conn, $_POST['specialization_id'] ?? '') . "'");
  $spec_row = mysqli_fetch_assoc($spec_check);
  $specialization_name = strtolower($spec_row['specialization_name'] ?? '');

  // Check if it's Minor specialization
  if (strpos($specialization_name, 'minor') !== false) {
    // For Minor: Use minor_cgpa value
    if (!empty($_POST['minor_cgpa']) && is_numeric($_POST['minor_cgpa'])) {
      $cgpa_val = floatval($_POST['minor_cgpa']);
      $cgpa_val = number_format($cgpa_val, 2);
      $cgpa = "'" . $cgpa_val . "'";
    }
  }
  // Check if it's Honours specialization
  else if (strpos($specialization_name, 'honour') !== false || strpos($specialization_name, 'honor') !== false) {
    // For Honours: Use main cgpa value
    if (!empty($_POST['cgpa']) && is_numeric($_POST['cgpa'])) {
      $cgpa_val = floatval($_POST['cgpa']);
      $cgpa_val = number_format($cgpa_val, 2);
      $cgpa = "'" . $cgpa_val . "'";
    }
  }

  $fname = mysqli_real_escape_string($conn, $_POST['fname'] ?? '');

  // Handle mobile
  $mobile = 'NULL';
  if (!empty($_POST['mobile'])) {
    $mobile = "'" . mysqli_real_escape_string($conn, $_POST['mobile']) . "'";
  }

  // Handle email
  $email = 'NULL';
  if (!empty($_POST['email'])) {
    $email = "'" . mysqli_real_escape_string($conn, $_POST['email']) . "'";
  }

  // Handle file uploads
  $upload_dir = "uploads/marklists/";
  if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
  }

  $mark_list_files = [];
  $semester_fields = ['mark-list1', 'mark-list2', 'mark-list3', 'mark-list4', 'mark-list6'];

  foreach ($semester_fields as $field) {
    if (isset($_FILES[$field]) && $_FILES[$field]['error'] == 0 && !empty($_FILES[$field]['name'])) {
      $file_ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
      $safe_reg_no = preg_replace('/[^a-zA-Z0-9]/', '_', $registration_no);
      $file_name = time() . '_' . $safe_reg_no . '_' . $field . '.' . $file_ext;
      $target_path = $upload_dir . $file_name;

      if (move_uploaded_file($_FILES[$field]['tmp_name'], $target_path)) {
        $mark_list_files[] = $file_name;
      }
    }
  }

  $mark_list = !empty($mark_list_files) ? "'" . implode(',', $mark_list_files) . "'" : 'NULL';

  // Semester marks data
  $m_sem1 = "'[]'";
  $m_sem2 = "'[]'";
  $m_sem3 = "'[]'";
  $status = 1;

  // Check if registration number exists
  $check_sql = "SELECT registration_no FROM st_student_master WHERE registration_no = '$registration_no'";
  $check_result = mysqli_query($conn, $check_sql);

  if (mysqli_num_rows($check_result) > 0) {
    echo '<script type="text/javascript">alert("Registration number already exists!");</script>';
    echo "<script>window.open('student_admission.php','_self')</script>";
    exit;
  }

  // INSERT query - using only existing columns
  $sql = "INSERT INTO `st_student_master`(
        `academic_year`,
        `registration_no`,
        `class_id`,
        `division_id`,
        `grad_year`,
        `roll_no`,
        `department_id`,
        `specialization_id`,
        `specialization_subject_id`,
        `cgpa`,
        `fname`,
        `mobile`,
        `email`,
        `mark_list`,
        `status`,
        `m_sem1`,
        `m_sem2`,
        `m_sem3`,
        `created_at`
    ) VALUES (
        '$academic_year',
        '$registration_no',
        '$class_id',
        '$division_id',
        $grad_year,
        $roll_no,
        $department_id,
        $specialization_id,
        $specialization_subject_id,
        $cgpa,
        '$fname',
        $mobile,
        $email,
        $mark_list,
        $status,
        $m_sem1,
        $m_sem2,
        $m_sem3,
        NOW()
    )";

  $result = mysqli_query($conn, $sql);

  if ($result === TRUE) {
    $student_id = mysqli_insert_id($conn);
    echo '<script type="text/javascript">alert("Student registered successfully! Student ID: ' . $student_id . '");</script>';
    echo "<script>window.open('student_list.php','_self')</script>";
  } else {
    echo "Error: " . mysqli_error($conn);
    echo "<br><br>SQL Query: <pre>" . htmlspecialchars($sql) . "</pre>";
  }
}