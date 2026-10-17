<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json');

require_once "../database/db_connect.php";
$db_handle = new DBController();

$class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : (isset($_GET['class_id']) ? intval($_GET['class_id']) : 0);
$semester_id = isset($_POST['semester_id']) ? intval($_POST['semester_id']) : (isset($_GET['semester_id']) ? intval($_GET['semester_id']) : 0);
$department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : (isset($_GET['department_id']) ? intval($_GET['department_id']) : 0);

$tracks = $db_handle->getEligibleTracks($class_id, $semester_id, $department_id);

echo json_encode([
    'success' => true,
    'data' => $tracks
]);
