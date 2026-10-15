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

if ($userName === '' || $emailId === '' || $departmentId <= 0) {
  echo "<script>alert('Please fill all required fields.'); window.history.back();</script>";
  exit;
}

$userNameEsc = mysqli_real_escape_string($db_handle->conn, $userName);
$emailEsc = mysqli_real_escape_string($db_handle->conn, $emailId);
$phoneEsc = mysqli_real_escape_string($db_handle->conn, $phoneNumber);

if ($userId > 0) {
  $dupSql = "SELECT user_id FROM st_user_master WHERE email_id = '$emailEsc' AND user_id != $userId LIMIT 1";
} else {
  $dupSql = "SELECT user_id FROM st_user_master WHERE email_id = '$emailEsc' LIMIT 1";
}
$dupResult = $db_handle->query($dupSql);
if ($dupResult && $dupResult->num_rows > 0) {
  echo "<script>alert('Email already exists.'); window.history.back();</script>";
  exit;
}

$defaultHashedPassword = DBController::hashPassword('Tcet@1234');

if ($userId > 0) {
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
} else {
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

if (intval($roleId) === 4 && isset($_POST['subject_id'])) {
  $subjectId = intval($_POST['subject_id']);
  if ($subjectId > 0 && $userId > 0) {
    mysqli_begin_transaction($db_handle->conn);
    try {
      mysqli_query($db_handle->conn, "DELETE FROM st_mentor_subject_mapping WHERE mentor_id = $userId");
      $insert = mysqli_query($db_handle->conn, "INSERT INTO st_mentor_subject_mapping (mentor_id, subject_id) VALUES ($userId, $subjectId)");
      if (!$insert) {
        throw new Exception("Database error mapping subject.");
      }
      
      $db_handle->recalculateMentorStudents($userId);
      mysqli_commit($db_handle->conn);
    } catch (Throwable $e) {
      mysqli_rollback($db_handle->conn);
    }
  }
}

header('Location: ' . $infoFile);
exit;
?>
