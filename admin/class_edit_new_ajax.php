<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require "../database/db_connect.php";
$db_handle = new DBController();

// Authorization check: Admin only (Role 1 or 2)
if (empty($_SESSION['user_id']) || intval($_SESSION['role_id'] ?? 0) > 2) {
    http_response_code(403);
    echo "0";
    exit();
}

$id = intval($_POST["class_id"] ?? 0);
$class_name = ucwords(trim($_POST["class_name"] ?? ''));

if ($id <= 0 || empty($class_name)) {
    echo "2";
    exit();
}

$stmt = mysqli_prepare($db_handle->conn, "UPDATE `st_class_master` SET `class_name` = ? WHERE `class_id` = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "si", $class_name, $id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    if ($result) {
        if (method_exists($db_handle, 'writeAuditLog')) {
            $db_handle->writeAuditLog($_SESSION['user_id'], 'CLASS_UPDATED', 'st_class_master', $id, "Updated class ID {$id} name to {$class_name}");
        }
        echo "1";
    } else {
        echo "2";
    }
} else {
    echo "2";
}
?>