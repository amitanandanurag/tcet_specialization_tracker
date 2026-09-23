<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json');
require "../database/db_connect.php";
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['id'] ?? 0);
    $delete_type = trim($_POST['delete_type'] ?? 'hard');

    if ($student_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid student ID.']);
        exit();
    }

    if ($delete_type === 'soft') {
        $stmt = mysqli_prepare($db_handle->conn, "UPDATE st_student_master SET status = '1' WHERE student_id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $student_id);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            if ($success) {
                if (method_exists($db_handle, 'writeAuditLog')) {
                    $db_handle->writeAuditLog($_SESSION['user_id'], 'STUDENT_DEACTIVATED', 'st_student_master', $student_id, "Deactivated student ID {$student_id}");
                }
                echo json_encode(['status' => 'success', 'message' => 'Student moved to inactive list']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Unable to update student status.']);
            }
        }
    } else {
        mysqli_begin_transaction($db_handle->conn);
        try {
            $stmt1 = mysqli_prepare($db_handle->conn, "DELETE FROM st_mentor_student_mapping WHERE student_id = ?");
            if ($stmt1) {
                mysqli_stmt_bind_param($stmt1, "i", $student_id);
                mysqli_stmt_execute($stmt1);
                mysqli_stmt_close($stmt1);
            }

            $stmt2 = mysqli_prepare($db_handle->conn, "DELETE FROM st_student_semester_history WHERE student_id = ?");
            if ($stmt2) {
                mysqli_stmt_bind_param($stmt2, "i", $student_id);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_close($stmt2);
            }

            $stmt3 = mysqli_prepare($db_handle->conn, "DELETE FROM st_student_master WHERE student_id = ?");
            if ($stmt3) {
                mysqli_stmt_bind_param($stmt3, "i", $student_id);
                mysqli_stmt_execute($stmt3);
                mysqli_stmt_close($stmt3);
            }

            mysqli_commit($db_handle->conn);
            if (method_exists($db_handle, 'writeAuditLog')) {
                $db_handle->writeAuditLog($_SESSION['user_id'], 'STUDENT_DELETED', 'st_student_master', $student_id, "Permanently deleted student ID {$student_id}");
            }
            echo json_encode(['status' => 'success', 'message' => 'Student deleted permanently']);
        } catch (Throwable $e) {
            mysqli_rollback($db_handle->conn);
            echo json_encode(['status' => 'error', 'message' => 'Unable to delete student record.']);
        }
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>