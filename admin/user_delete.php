<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require "../database/db_connect.php";
header('Content-Type: application/json');

$db_handle = new DBController();

// Authentication and Authorization Check (Admin only: Role 1 or 2)
if (empty($_SESSION['user_id']) || intval($_SESSION['role_id'] ?? 0) > 2) {
    http_response_code(403);
    echo json_encode(array('success' => false, 'message' => 'Unauthorized access.'));
    exit();
}

// CSRF validation
if (!DBController::validateCsrfToken()) {
    http_response_code(403);
    echo json_encode(array('success' => false, 'message' => 'Invalid or missing CSRF token.'));
    exit();
}

$userId = intval($_POST['user_id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(array('success' => false, 'message' => 'Invalid user id.'));
    exit();
}

mysqli_begin_transaction($db_handle->conn);
try {
    mysqli_query($db_handle->conn, "DELETE FROM st_login WHERE user_id = $userId");
    mysqli_query($db_handle->conn, "DELETE FROM st_mentor_subject_mapping WHERE mentor_id = $userId");
    mysqli_query($db_handle->conn, "DELETE FROM st_mentor_student_mapping WHERE mentor_id = $userId");
    mysqli_query($db_handle->conn, "DELETE FROM st_coordinator_mentor WHERE mentor_id = $userId OR coordinator_id = $userId");
    mysqli_query($db_handle->conn, "DELETE FROM st_user_master WHERE user_id = $userId");
    mysqli_commit($db_handle->conn);

    if (method_exists($db_handle, 'writeAuditLog')) {
        $db_handle->writeAuditLog($_SESSION['user_id'], 'USER_DELETED', 'st_user_master', $userId, "Deleted user record ID {$userId}");
    }
    echo json_encode(array('success' => true));
} catch (Throwable $e) {
    mysqli_rollback($db_handle->conn);
    echo json_encode(array('success' => false, 'message' => 'Unable to delete record.'));
}
?>
