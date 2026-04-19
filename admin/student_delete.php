<?php
session_start();
header('Content-Type: application/json');
require "../database/db_connect.php";
$db_handle = new DBController();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = intval($_POST['id']);
    $table = $_POST['table'] ?? 'st_student_master';
    
    // Validate table name to prevent SQL injection
    if ($table !== 'st_student_master') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid table name']);
        exit;
    }
    
    // Instead of deleting, we mark the record as inactive (status = '0')
    $update_sql = "UPDATE $table SET status = '0' WHERE student_id = $student_id";
    
    if ($db_handle->conn->query($update_sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Student record moved to inactive']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error deleting record: ' . $db_handle->conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
