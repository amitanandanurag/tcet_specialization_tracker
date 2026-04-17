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

$deleteSql = "DELETE FROM st_user_master WHERE user_id = $userId";
$ok = $db_handle->query($deleteSql);

if ($ok) {
  echo json_encode(array('success' => true));
} else {
  echo json_encode(array('success' => false, 'message' => 'Unable to delete record.'));
}
?>
