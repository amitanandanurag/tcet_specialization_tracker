<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require "../database/db_connect.php";
header('Content-Type: application/json');
$db_handle = new DBController();

// Enforce session authentication
if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
    http_response_code(403);
    echo json_encode(array('success' => false, 'message' => 'Unauthorized access. Please login.'));
    exit();
}

$userRole = intval($_SESSION['role_id']);
$targetRoleId = intval($roleId ?? 0);

// Only Super Admin (1) and Admin (2) can delete Admin/Coordinator users.
// Coordinator (3) can only delete Mentors (4).
if ($userRole > 2) {
    if (!($userRole === 3 && $targetRoleId === 4)) {
        http_response_code(403);
        echo json_encode(array('success' => false, 'message' => 'Forbidden access for current role.'));
        exit();
    }
}

// CSRF validation
if (!DBController::validateCsrfToken()) {
    http_response_code(403);
    echo json_encode(array('success' => false, 'message' => 'Invalid or missing security token.'));
    exit();
}

$userId = intval($_POST['user_id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(array('success' => false, 'message' => 'Invalid user id.'));
    exit();
}

// Fetch user name for audit log
$userName = '';
$userStmt = mysqli_prepare($db_handle->conn, "SELECT user_name FROM st_user_master WHERE user_id = ? AND role_id = ?");
if ($userStmt) {
    mysqli_stmt_bind_param($userStmt, "ii", $userId, $targetRoleId);
    mysqli_stmt_execute($userStmt);
    $userRes = mysqli_stmt_get_result($userStmt);
    if ($userRes && ($uRow = mysqli_fetch_assoc($userRes))) {
        $userName = $uRow['user_name'] ?? '';
    }
    mysqli_stmt_close($userStmt);
}

mysqli_begin_transaction($db_handle->conn);
try {
    mysqli_query($db_handle->conn, "DELETE FROM st_login WHERE user_id = $userId");
    if ($targetRoleId === 4) {
        mysqli_query($db_handle->conn, "DELETE FROM st_mentor_subject_mapping WHERE mentor_id = $userId");
        mysqli_query($db_handle->conn, "DELETE FROM st_mentor_student_mapping WHERE mentor_id = $userId");
        mysqli_query($db_handle->conn, "DELETE FROM st_coordinator_mentor WHERE mentor_id = $userId");
    } elseif ($targetRoleId === 3) {
        mysqli_query($db_handle->conn, "DELETE FROM st_coordinator_mentor WHERE coordinator_id = $userId");
    }
    mysqli_query($db_handle->conn, "DELETE FROM st_user_master WHERE user_id = $userId AND role_id = $targetRoleId");
    mysqli_commit($db_handle->conn);

    if (method_exists($db_handle, 'writeAuditLog')) {
        $db_handle->writeAuditLog($_SESSION['user_id'], 'USER_DELETED', 'st_user_master', $userId, "Deleted user {$userName} (ID: {$userId}, Role: {$targetRoleId})");
    }
    echo json_encode(array('success' => true));
} catch (Throwable $e) {
    mysqli_rollback($db_handle->conn);
    echo json_encode(array('success' => false, 'message' => 'Unable to delete user record.'));
}
?>
