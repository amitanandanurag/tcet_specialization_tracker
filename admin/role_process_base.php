<?php
session_start();
require "../database/db_connect.php";
$db_handle = new DBController();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . $infoFile);
  exit;
}

if (!DBController::validateCsrfToken()) {
  echo "<script>alert('Invalid security token (CSRF). Please refresh and try again.'); window.history.back();</script>";
  exit;
}

$userId = intval($_POST['user_id'] ?? 0);
$userName = trim($_POST['user_name'] ?? '');
$emailId = trim($_POST['email_id'] ?? '');
$phoneNumber = trim($_POST['phone_number'] ?? '');
$departmentId = intval($_POST['department_id'] ?? 0);
$roleId = intval($roleId ?? ($_POST['role_id'] ?? 0));

if ($userName === '' || $emailId === '' || $departmentId <= 0) {
  echo "<script>alert('Please fill all required fields.'); window.history.back();</script>";
  exit;
}

$userNameEsc = mysqli_real_escape_string($db_handle->conn, $userName);
$emailEsc = mysqli_real_escape_string($db_handle->conn, $emailId);
$phoneEsc = mysqli_real_escape_string($db_handle->conn, $phoneNumber);

$classId = intval($_POST['class_id'] ?? 0);
$subjectId = intval($_POST['subject_id'] ?? 0);
$semesterId = intval($_POST['semester_id'] ?? 0);
$academicYearId = intval($_POST['academic_year_id'] ?? 0);

// Strict server-side validation for Mentors (Role 4)
if ($roleId === 4) {
  if ($subjectId <= 0 || $semesterId <= 0 || $academicYearId <= 0) {
    echo "<script>alert('Department, Specialization Subject, Semester, and Academic Year are all mandatory for mentor allocation.'); window.history.back();</script>";
    exit;
  }

  // Validate Class if provided
  if ($classId > 0) {
    $classCheck = mysqli_query($db_handle->conn, "SELECT class_id, UPPER(class_name) AS class_name FROM st_class_master WHERE class_id = $classId LIMIT 1");
    if (!$classCheck || mysqli_num_rows($classCheck) === 0) {
      echo "<script>alert('Selected class does not exist.'); window.history.back();</script>";
      exit;
    }
    $cRow = mysqli_fetch_assoc($classCheck);
    $cName = $cRow['class_name'];
    $allowedSems = [];
    if ($cName === 'FY' || $cName === 'FE') {
      $allowedSems = [1, 2];
    } elseif ($cName === 'SY' || $cName === 'SE') {
      $allowedSems = [3, 4];
    } elseif ($cName === 'TY' || $cName === 'TE') {
      $allowedSems = [5, 6];
    } elseif ($cName === 'BE') {
      $allowedSems = [7, 8];
    }

    if (!empty($allowedSems) && !in_array($semesterId, $allowedSems, true)) {
      echo "<script>alert('Selected semester does not belong to the selected class.'); window.history.back();</script>";
      exit;
    }
  }

  // Validate subject belongs to department and semester where relationships are defined
  $subjCheck = mysqli_query($db_handle->conn, "SELECT subject_id, department_id, semester_id FROM st_specialization_subject_master WHERE subject_id = $subjectId AND is_active = 1 LIMIT 1");
  if (!$subjCheck || mysqli_num_rows($subjCheck) === 0) {
    echo "<script>alert('Selected subject does not exist or is inactive.'); window.history.back();</script>";
    exit;
  }
  $subjData = mysqli_fetch_assoc($subjCheck);
  if (!empty($subjData['department_id']) && intval($subjData['department_id']) > 0 && intval($subjData['department_id']) !== $departmentId) {
    echo "<script>alert('This subject does not belong to the selected department.'); window.history.back();</script>";
    exit;
  }
  if (!empty($subjData['semester_id']) && intval($subjData['semester_id']) > 0 && intval($subjData['semester_id']) !== $semesterId) {
    echo "<script>alert('This subject does not belong to the selected semester.'); window.history.back();</script>";
    exit;
  }

  // Validate semester exists
  $semCheck = mysqli_query($db_handle->conn, "SELECT semester_id FROM st_semester_master WHERE semester_id = $semesterId LIMIT 1");
  if (!$semCheck || mysqli_num_rows($semCheck) === 0) {
    echo "<script>alert('Invalid semester selected.'); window.history.back();</script>";
    exit;
  }

  // Validate academic year exists
  $ayCheck = mysqli_query($db_handle->conn, "SELECT session_id FROM st_session_master WHERE session_id = $academicYearId LIMIT 1");
  if (!$ayCheck || mysqli_num_rows($ayCheck) === 0) {
    echo "<script>alert('Invalid academic year selected.'); window.history.back();</script>";
    exit;
  }
}

// Account lookup / duplicate check
$isExistingMentorMultiAlloc = false;
if ($userId > 0) {
  $dupSql = "SELECT user_id FROM st_user_master WHERE email_id = '$emailEsc' AND user_id != $userId LIMIT 1";
  $dupResult = $db_handle->query($dupSql);
  if ($dupResult && $dupResult->num_rows > 0) {
    echo "<script>alert('Email already exists for another user.'); window.history.back();</script>";
    exit;
  }
} else {
  $dupSql = "SELECT user_id, role_id, user_name, department_id FROM st_user_master WHERE email_id = '$emailEsc' LIMIT 1";
  $dupResult = $db_handle->query($dupSql);
  if ($dupResult && ($existingRow = $dupResult->fetch_assoc())) {
    $existingRoleId = intval($existingRow['role_id']);
    $existingUserId = intval($existingRow['user_id']);

    if ($roleId === 4 && $existingRoleId === 4) {
      // Support multiple allocation records for existing mentor account
      $checkDupAlloc = mysqli_query($db_handle->conn, "
        SELECT mapping_id 
        FROM st_mentor_subject_mapping 
        WHERE mentor_id = $existingUserId 
          AND subject_id = $subjectId 
          AND semester_id = $semesterId 
          AND academic_year_id = $academicYearId
        LIMIT 1
      ");
      if ($checkDupAlloc && mysqli_num_rows($checkDupAlloc) > 0) {
        echo "<script>alert('Mentor account exists and is already allocated to this Subject, Semester, and Academic Year.'); window.history.back();</script>";
        exit;
      }
      $userId = $existingUserId;
      $isExistingMentorMultiAlloc = true;
    } else {
      echo "<script>alert('Email already exists.'); window.history.back();</script>";
      exit;
    }
  }
}

$defaultHashedPassword = DBController::hashPassword('Tcet@1234');

if ($userId > 0 && !$isExistingMentorMultiAlloc) {
  $sql = "UPDATE st_user_master SET user_name='$userNameEsc', email_id='$emailEsc', phone_number='$phoneEsc', department_id=$departmentId WHERE user_id=$userId AND role_id=" . intval($roleId);
  $db_handle->query($sql);
  
  // Also update the login username or create if not exists
  $checkLogin = mysqli_query($db_handle->conn, "SELECT login_id FROM st_login WHERE user_id = $userId LIMIT 1");
  if ($checkLogin && mysqli_num_rows($checkLogin) > 0) {
      mysqli_query($db_handle->conn, "UPDATE st_login SET username = '$emailEsc' WHERE user_id = $userId");
  } else {
      mysqli_query($db_handle->conn, "INSERT INTO st_login (username, password, user_id) VALUES ('$emailEsc', '$defaultHashedPassword', $userId)");
  }
  if (method_exists($db_handle, 'writeAuditLog')) {
    $db_handle->writeAuditLog($_SESSION['user_id'] ?? 0, 'USER_UPDATED', 'st_user_master', $userId, "Updated {$roleLabel} user {$userNameEsc} ({$emailEsc})");
  }
} elseif ($userId === 0) {
  $sql = "INSERT INTO st_user_master (user_name, email_id, phone_number, department_id, role_id, student_id) VALUES ('$userNameEsc', '$emailEsc', '$phoneEsc', $departmentId, " . intval($roleId) . ", 0)";
  $db_handle->query($sql);
  $userId = mysqli_insert_id($db_handle->conn);
  
  // Automatically create a login row with hashed password
  $checkLogin = mysqli_query($db_handle->conn, "SELECT login_id FROM st_login WHERE user_id = $userId LIMIT 1");
  if ($checkLogin && mysqli_num_rows($checkLogin) === 0) {
      mysqli_query($db_handle->conn, "INSERT INTO st_login (username, password, user_id) VALUES ('$emailEsc', '$defaultHashedPassword', $userId)");
  }
  if (method_exists($db_handle, 'writeAuditLog')) {
    $db_handle->writeAuditLog($_SESSION['user_id'] ?? 0, 'USER_CREATED', 'st_user_master', $userId, "Created new {$roleLabel} user {$userNameEsc} ({$emailEsc})");
  }
}

// Mentor Allocation Mapping (Non-destructive: preserves existing allocations across semesters/AYs)
if ($roleId === 4 && $subjectId > 0 && $userId > 0 && $semesterId > 0 && $academicYearId > 0) {
  mysqli_begin_transaction($db_handle->conn);
  try {
    $insertSql = "INSERT INTO st_mentor_subject_mapping (mentor_id, subject_id, department_id, semester_id, academic_year_id, is_active)
                  VALUES ($userId, $subjectId, $departmentId, $semesterId, $academicYearId, 1)
                  ON DUPLICATE KEY UPDATE department_id = VALUES(department_id), is_active = 1, updated_at = CURRENT_TIMESTAMP";
    $insert = mysqli_query($db_handle->conn, $insertSql);
    if (!$insert) {
      throw new Exception("Database error mapping subject.");
    }
    
    $db_handle->recalculateMentorStudents($userId);
    mysqli_commit($db_handle->conn);

    if (method_exists($db_handle, 'writeAuditLog')) {
      $db_handle->writeAuditLog($_SESSION['user_id'] ?? 0, 'MENTOR_ALLOCATION_CREATED', 'st_mentor_subject_mapping', $userId, "Allocated mentor ID {$userId} to Subject ID {$subjectId}, Semester {$semesterId}, AY {$academicYearId}");
    }
  } catch (Throwable $e) {
    mysqli_rollback($db_handle->conn);
  }
}

header('Location: ' . $infoFile);
exit;
?>
