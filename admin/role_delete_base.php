<?php
session_start();
require "../database/db_connect.php";
header('Content-Type: application/json');
$db_handle = new DBController();

$userId = intval($_POST['user_id'] ?? 0);
if ($userId <= 0) {
  echo json_encode(array('success' => false, 'message' => 'Invalid user id.'));
  exit;
}

$sql = "DELETE FROM st_user_master WHERE user_id = $userId AND role_id = " . intval($roleId);
$ok = $db_handle->query($sql);

if ($ok) {
  mysqli_query($db_handle->conn, "DELETE FROM st_login WHERE user_id = $userId");
  if (intval($roleId) === 4) {
    mysqli_query($db_handle->conn, "DELETE FROM st_mentor_subject_mapping WHERE mentor_id = $userId");
    mysqli_query($db_handle->conn, "DELETE FROM st_mentor_student_mapping WHERE mentor_id = $userId");
  }
  echo json_encode(array('success' => true));
} else {
  echo json_encode(array('success' => false, 'message' => 'Unable to delete record.'));
}
?>
