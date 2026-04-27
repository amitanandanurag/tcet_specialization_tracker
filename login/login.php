<?php
ob_start();
session_start();

include_once("../database/db_connect.php");

if (isset($_POST['login_button'])) {

    try {
        $db_handle = new DBController();
    } catch (Throwable $e) {
        echo "Unable to connect with database";
        exit();
    }

    if (!$db_handle || !($db_handle->conn instanceof mysqli)) {
        echo "Unable to connect with database";
        exit();
    }

    $username = trim($_POST['username']);
    $user_password = trim($_POST['password']);
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $sql = "SELECT * FROM st_login WHERE username=?";
    $stmt = mysqli_prepare($db_handle->conn, $sql);

    if (!$stmt) {
        echo "Unable to connect with database";
        exit();
    }

    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $resultset = mysqli_stmt_get_result($stmt);

    if (!$resultset) {
        echo "Unable to connect with database";
        exit();
    }

    $row = mysqli_fetch_assoc($resultset);
    mysqli_stmt_close($stmt);

    if (!$row) {
        $db_handle->writeAuditLog(
            0,
            'LOGIN_FAILED',
            'st_login',
            null,
            "Login failed for username '{$username}' from IP {$ipAddress}. Browser: {$userAgent}",
            $username,
            $ipAddress,
            $userAgent
        );
        echo "email or password does not exist.";
        exit();
    }

    if ($row['password'] == $user_password) {

        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['user_type'] = $row['role_id'];

        $userCheck = $db_handle->query("SELECT is_first_login FROM st_user_master WHERE user_id='" . $row['user_id'] . "'");
        $userData = $userCheck->fetch_assoc();

        if ($userData && $userData['is_first_login'] == 1) {
            header("Location: change_password.php");
            exit();
        }

        $auditId = $db_handle->writeAuditLog(
            intval($row['user_id']),
            'LOGIN_SUCCESS',
            'st_login',
            intval($row['login_id'] ?? 0),
            "User '{$username}' logged in successfully with role {$row['role_id']} from IP {$ipAddress}. Browser: {$userAgent}",
            $username,
            $ipAddress,
            $userAgent
        );

        if ($auditId) {
            $_SESSION['audit_login_id'] = $auditId;
        }

        if ($_SESSION['user_type'] == "1") {
            header("Location: ../admin/dashboard.php");
        }
        else if ($_SESSION['user_type'] == "2") {
            header("Location: ../admin/admin_dashboard.php");
        }
        else if ($_SESSION['user_type'] == "3") {
            header("Location: ../admin/coordinator_dashboard.php");
        }
        else if ($_SESSION['user_type'] == "4") {
            header("Location: ../admin/mentor_dashboard.php");
        }
        else if ($_SESSION['user_type'] == "5") {
            header("Location: ../admin/student_dashboard.php");
        }

        exit();
    }
    else {

        $db_handle->writeAuditLog(
            intval($row['user_id'] ?? 0),
            'LOGIN_FAILED',
            'st_login',
            intval($row['login_id'] ?? 0),
            "Invalid password for username '{$username}' from IP {$ipAddress}. Browser: {$userAgent}",
            $username,
            $ipAddress,
            $userAgent
        );

        echo "email or password does not exist.";
    }
}
?>