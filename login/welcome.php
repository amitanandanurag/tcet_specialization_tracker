<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['user_session'])) {
    header("Location: ../index.php");
    exit();
}
require_once("../database/db_connect.php");
$db_handle = new DBController();

$loginId = intval($_SESSION['user_login_id'] ?? $_SESSION['user_session'] ?? 0);
$username = 'Member';

if ($loginId > 0 && $db_handle && ($db_handle->conn instanceof mysqli)) {
    $stmt = mysqli_prepare($db_handle->conn, "SELECT username FROM st_login WHERE login_id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $loginId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            $username = htmlspecialchars($row['username'] ?? 'Member', ENT_QUOTES, 'UTF-8');
        }
        mysqli_stmt_close($stmt);
    }
}
header("Location: ../admin/index.php");
exit();
?>