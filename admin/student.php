<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "../database/db_connect.php";
$database = new DBController();

if (isset($_POST['save'])) {
  $conn = $database->conn;

  // Get form data - DON'T add quotes here, just escape
  $academic_year_id = mysqli_real_escape_string($conn, $_POST['academic'] ?? '');
  $registration_no = mysqli_real_escape_string($conn, $_POST['registration_no'] ?? '');
  $class_id = mysqli_real_escape_string($conn, $_POST['class'] ?? '');
  $division_id = mysqli_real_escape_string($conn, $_POST['batch'] ?? '');
  $current_semester_id = mysqli_real_escape_string($conn, $_POST['current_semester_id'] ?? '');
  $fname = mysqli_real_escape_string($conn, $_POST['fname'] ?? '');

  // Handle grad_year - properly quoted
  $grad_year = 'NULL';
  if (!empty($_POST['academic_year_id']) && $_POST['academic_year_id'] != 'Select Year' && is_numeric($_POST['academic_year_id'])) {
    $grad_year = "'" . mysqli_real_escape_string($conn, $_POST['academic_year_id']) . "'";
  }

  // Handle roll_no - properly quoted
  $roll_no = 'NULL';
  if (!empty($_POST['roll_no'])) {
    $roll_no = "'" . mysqli_real_escape_string($conn, $_POST['roll_no']) . "'";
  }

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

  // Handle CGPA - FIXED LOGIC
  $cgpa = 'NULL';

  // Get specialization name
  $spec_id = $_POST['specialization_id'] ?? '';
  if (!empty($spec_id) && is_numeric($spec_id)) {
    $spec_check = mysqli_query($conn, "SELECT specialization_name FROM st_specialization_master WHERE specialization_id = '" . mysqli_real_escape_string($conn, $spec_id) . "'");
    if ($spec_check) {
      $spec_row = mysqli_fetch_assoc($spec_check);
      $specialization_name = strtolower($spec_row['specialization_name'] ?? '');

      // Check for Minor Multidisciplinary (most specific first)
      if (strpos($specialization_name, 'minor multidisciplinary') !== false) {
        // For Minor Multidisciplinary - use minor_cgpa
        if (!empty($_POST['minor_cgpa']) && is_numeric($_POST['minor_cgpa'])) {
          $cgpa_val = floatval($_POST['minor_cgpa']);
          $cgpa_val = number_format($cgpa_val, 2);
          $cgpa = "'" . $cgpa_val . "'";
        }
      }
      // Check for Minor (non-multidisciplinary)
      else if (strpos($specialization_name, 'minor') !== false) {
        // For Minor - you can use regular cgpa or leave NULL
        if (!empty($_POST['cgpa']) && is_numeric($_POST['cgpa'])) {
          $cgpa_val = floatval($_POST['cgpa']);
          $cgpa_val = number_format($cgpa_val, 2);
          $cgpa = "'" . $cgpa_val . "'";
        }
      }
      // Check for Honours
      else if (strpos($specialization_name, 'honour') !== false || strpos($specialization_name, 'honor') !== false) {
        if (!empty($_POST['cgpa']) && is_numeric($_POST['cgpa'])) {
          $cgpa_val = floatval($_POST['cgpa']);
          $cgpa_val = number_format($cgpa_val, 2);
          $cgpa = "'" . $cgpa_val . "'";
        }
      }
    }
  }

  // Handle minor_course_id
  $minor_course_id = 'NULL';
  if (!empty($_POST['minor_course_id']) && $_POST['minor_course_id'] != '') {
    $minor_course_id = "'" . mysqli_real_escape_string($conn, $_POST['minor_course_id']) . "'";
  }

  // Handle minor_subject_id
  $minor_subject_id = 'NULL';
  if (!empty($_POST['minor_subject_id']) && $_POST['minor_subject_id'] != '') {
    $minor_subject_id = "'" . mysqli_real_escape_string($conn, $_POST['minor_subject_id']) . "'";
  }

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

  // Build the INSERT query - USING PROPERLY QUOTED VALUES
  $sql = "INSERT INTO `st_student_master`(
    `academic_year_id`,
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
    `created_at`,
    `current_semester_id`
) VALUES (
    '$academic_year_id',
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
    NOW(),
    '$current_semester_id'
)";
  // Debug - print the query to see what's wrong
  // echo "<pre>" . htmlspecialchars($sql) . "</pre>";
  // exit;

  $result = mysqli_query($conn, $sql);

  if ($result === TRUE) {
    $student_id = mysqli_insert_id($conn);
    echo '<script type="text/javascript">alert("Student registered successfully! Student ID: ' . $student_id . '");</script>';
    echo "<script>window.open('student-info.php','_self')</script>";
  } else {
    echo "Error: " . mysqli_error($conn);
    echo "<br><br>SQL Query: <pre>" . htmlspecialchars($sql) . "</pre>";
  }
  exit;
}
?>
<?php include "header/header.php"; ?>
