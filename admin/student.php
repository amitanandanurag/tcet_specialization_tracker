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

  $track_id = intval($_POST['specialization_track_id'] ?? 0);
  $spec_id = intval($_POST['specialization_id'] ?? 0);
  $dept_id = intval($_POST['department_id'] ?? 0);
  $sem_id = intval($_POST['current_semester_id'] ?? 0);

  if ($track_id <= 0 && $spec_id > 0 && $spec_id <= 4) {
    $track_id = $spec_id;
  }

  if ($track_id === 2) {
    $subClass = intval($_POST['class'] ?? 0);
    $subSem = intval($_POST['current_semester_id'] ?? 0);
    $subDept = intval($_POST['department_id'] ?? 0);
    if (!$database->isTrackEligible(2, $subClass, $subSem, $subDept)) {
      echo '<script type="text/javascript">alert("Error: Honours with Research is available only to BE students in Semester 7 and Semester 8.");</script>';
      echo "<script>window.open('student_admission.php','_self')</script>";
      exit;
    }
  }

  $specialization_track_id = ($track_id > 0) ? $track_id : 'NULL';
  $specialization_id = 'NULL';
  $specialization_subject_id = 'NULL';
  $cgpa = 'NULL';

  if ($track_id === 4) {
    $specialization_id = 4;
    if (!empty($_POST['minor_cgpa']) && is_numeric($_POST['minor_cgpa'])) {
      $cgpa_val = floatval($_POST['minor_cgpa']);
      $cgpa = "'" . number_format($cgpa_val, 2) . "'";
    }
  } elseif ($track_id === 1 || $track_id === 2) {
    if ($spec_id > 0 && $database->isDepartmentEligibleForSpecialization($dept_id, $spec_id, $track_id)) {
      $specialization_id = $spec_id;
    }
    if (!empty($_POST['cgpa']) && is_numeric($_POST['cgpa'])) {
      $cgpa_val = floatval($_POST['cgpa']);
      if ($cgpa_val >= 7.5) {
        $cgpa = "'" . number_format($cgpa_val, 2) . "'";
      }
    }
    if (!empty($_POST['unaided_subject']) && is_numeric($_POST['unaided_subject'])) {
      $specialization_subject_id = intval($_POST['unaided_subject']);
    }
  } elseif ($track_id === 3) {
    if ($spec_id > 0 && $database->isDepartmentEligibleForSpecialization($dept_id, $spec_id, $track_id)) {
      $specialization_id = $spec_id;
    }
    if (!empty($_POST['cgpa']) && is_numeric($_POST['cgpa'])) {
      $cgpa_val = floatval($_POST['cgpa']);
      $cgpa = "'" . number_format($cgpa_val, 2) . "'";
    }
    if (!empty($_POST['unaided_subject']) && is_numeric($_POST['unaided_subject'])) {
      $specialization_subject_id = intval($_POST['unaided_subject']);
    }
  } else {
    if ($spec_id > 0) {
      $specialization_id = $spec_id;
    }
    if (!empty($_POST['cgpa']) && is_numeric($_POST['cgpa'])) {
      $cgpa_val = floatval($_POST['cgpa']);
      $cgpa = "'" . number_format($cgpa_val, 2) . "'";
    }
    if (!empty($_POST['unaided_subject']) && is_numeric($_POST['unaided_subject'])) {
      $specialization_subject_id = intval($_POST['unaided_subject']);
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
    `specialization_track_id`,
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
    $specialization_track_id,
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
    if ($specialization_subject_id !== 'NULL' && !$database->isSubjectAvailable(
      intval(trim($specialization_subject_id, "'")), intval($_POST['department_id'] ?? 0),
      intval($current_semester_id), intval($_POST['specialization_id'] ?? 0))) {
      mysqli_query($conn, "DELETE FROM st_student_master WHERE student_id = " . intval($student_id));
      echo 'Selected specialization subject is not active for this department and semester.';
      exit;
    }
    $database->removeInvalidStudentMentorAllocation($student_id);
    $database->syncStudentSemesterHistory($student_id, $current_semester_id);
    if (isset($specialization_subject_id) && $specialization_subject_id !== 'NULL') {
      $clean_subject_id = intval(trim($specialization_subject_id, "'"));
      $clean_semester_id = intval($current_semester_id);
      if ($clean_subject_id > 0 && $clean_semester_id > 0) {
        $database->autoAllocateMentor($student_id, $clean_subject_id, $clean_semester_id);
        $database->syncStudentSemesterHistory($student_id, $clean_semester_id);
      }
    }
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
