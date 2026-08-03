<?php
session_start();
require "../database/db_connect.php";
$db_handle = new DBController();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . $infoFile);
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

if ($userId > 0) {
  $sql = "UPDATE st_user_master SET user_name='$userNameEsc', email_id='$emailEsc', phone_number='$phoneEsc', department_id=$departmentId WHERE user_id=$userId AND role_id=" . intval($roleId);
  $db_handle->query($sql);
  
  // Also update the login username
  mysqli_query($db_handle->conn, "UPDATE st_login SET username = '$emailEsc' WHERE user_id = $userId");
} else {
  $sql = "INSERT INTO st_user_master (user_name, email_id, phone_number, department_id, role_id, student_id) VALUES ('$userNameEsc', '$emailEsc', '$phoneEsc', $departmentId, " . intval($roleId) . ", 0)";
  $db_handle->query($sql);
  $userId = mysqli_insert_id($db_handle->conn);
  
  // Automatically create a login row
  $checkLogin = mysqli_query($db_handle->conn, "SELECT login_id FROM st_login WHERE username = '$emailEsc' LIMIT 1");
  if ($checkLogin && mysqli_num_rows($checkLogin) === 0) {
      mysqli_query($db_handle->conn, "INSERT INTO st_login (username, password, user_id) VALUES ('$emailEsc', 'Amit@1234', $userId)");
  }
}

if (intval($roleId) === 4 && isset($_POST['subject_id'])) {
  $subjectId = intval($_POST['subject_id']);
  if ($subjectId > 0 && $userId > 0) {
    mysqli_query($db_handle->conn, "DELETE FROM st_mentor_subject_mapping WHERE mentor_id = $userId");
    mysqli_query($db_handle->conn, "INSERT INTO st_mentor_subject_mapping (mentor_id, subject_id) VALUES ($userId, $subjectId)");
    
    // Auto-allocate this mentor to any student who has selected this subject but does not have a mentor assigned for their current semester
    $unassignedSql = "
        SELECT sm.student_id, sm.current_semester_id 
        FROM st_student_master sm
        LEFT JOIN st_mentor_student_mapping msm ON msm.student_id = sm.student_id AND msm.semester_id = sm.current_semester_id
        WHERE sm.specialization_subject_id = $subjectId AND msm.mentor_id IS NULL
    ";
    $unassignedResult = mysqli_query($db_handle->conn, $unassignedSql);
    if ($unassignedResult) {
        while ($studRow = mysqli_fetch_assoc($unassignedResult)) {
            $sId = intval($studRow['student_id']);
            $semId = intval($studRow['current_semester_id'] ?? 1);
            mysqli_query($db_handle->conn, "INSERT INTO st_mentor_student_mapping (mentor_id, student_id, semester_id) VALUES ($userId, $sId, $semId)");
        }
    }
  }
}

header('Location: ' . $infoFile);
exit;
?>
