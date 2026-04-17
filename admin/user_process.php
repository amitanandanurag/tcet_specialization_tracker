<?php
session_start();
require "../database/db_connect.php";
$db_handle = new DBController();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: user_register.php');
  exit;
}

$roleId = intval($_POST['role_id'] ?? 0);
$roleKey = strtolower(trim($_POST['role_key'] ?? 'admin'));
$userName = trim($_POST['user_name'] ?? '');
$emailId = trim($_POST['email_id'] ?? '');
$phoneNumber = trim($_POST['phone_number'] ?? '');
$departmentId = intval($_POST['department_id'] ?? 0);

if ($roleId <= 0 || $userName === '' || $emailId === '' || $departmentId <= 0) {
  echo "<script>alert('Please fill all required fields.'); window.history.back();</script>";
  exit;
}

$allowedRoles = array(2, 3, 4);
if (!in_array($roleId, $allowedRoles, true)) {
  echo "<script>alert('Invalid role selected.'); window.history.back();</script>";
  exit;
}

$userNameEsc = mysqli_real_escape_string($db_handle->conn, $userName);
$emailEsc = mysqli_real_escape_string($db_handle->conn, $emailId);
$phoneEsc = mysqli_real_escape_string($db_handle->conn, $phoneNumber);

$checkSql = "SELECT user_id FROM st_user_master WHERE email_id = '$emailEsc' LIMIT 1";
$checkResult = $db_handle->query($checkSql);
if ($checkResult && $checkResult->num_rows > 0) {
  echo "<script>alert('Email already exists.'); window.history.back();</script>";
  exit;
}

$insertSql = "INSERT INTO st_user_master (user_name, email_id, phone_number, department_id, role_id, student_id) VALUES ('$userNameEsc', '$emailEsc', '$phoneEsc', $departmentId, $roleId, 0)";
$db_handle->query($insertSql);

header('Location: user-info.php?role=' . urlencode($roleKey));
exit;
?>
