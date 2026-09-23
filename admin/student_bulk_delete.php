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
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

// CSRF validation
if (!DBController::validateCsrfToken()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing CSRF security token.']);
    exit();
}

$response = ['status' => 'error', 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids']) && is_array($_POST['ids'])) {
    $ids = array_values(array_filter(array_map('intval', $_POST['ids'])));
    
    if (!empty($ids)) {
        $idList = implode(',', $ids);
        mysqli_begin_transaction($db_handle->conn);
        try {
            mysqli_query($db_handle->conn, "DELETE FROM st_mentor_student_mapping WHERE student_id IN ($idList)");
            mysqli_query($db_handle->conn, "DELETE FROM st_student_semester_history WHERE student_id IN ($idList)");
            mysqli_query($db_handle->conn, "DELETE FROM st_student_master WHERE student_id IN ($idList)");
            mysqli_commit($db_handle->conn);

            if (method_exists($db_handle, 'writeAuditLog')) {
                $db_handle->writeAuditLog($_SESSION['user_id'], 'STUDENT_BULK_DELETED', 'st_student_master', null, "Bulk deleted " . count($ids) . " students: " . $idList);
            }
            $response = ['status' => 'success', 'message' => count($ids) . ' student(s) deleted successfully.'];
        } catch (Throwable $e) {
            mysqli_rollback($db_handle->conn);
            $response = ['status' => 'error', 'message' => 'Failed to bulk delete student records.'];
        }
    } else {
        $response = ['status' => 'error', 'message' => 'No valid student IDs provided.'];
    }
}

echo json_encode($response);
exit;
?>