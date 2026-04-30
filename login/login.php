<?php
session_start();

include_once("../database/db_connect.php");

if (!isset($_POST['login_button'])) {
    exit();
}

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

	$sql = "SELECT l.login_id, l.username, l.password, l.user_id, u.role_id
	        FROM st_login l
	        INNER JOIN st_user_master u ON u.user_id = l.user_id
	        WHERE l.username=?";
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

/* ---------------- USER NOT FOUND ---------------- */
if (!$row) {
    echo "email or password does not exist.";
    exit();
}

/* ---------------- WRONG PASSWORD ---------------- */
if ($row['password'] != $user_password) {
    echo "email or password does not exist.";
    exit();
}

/* ---------------- LOGIN SUCCESS ---------------- */
$_SESSION['user_session'] = $row['login_id'];
$_SESSION['user_login_id'] = $row['login_id'];
$_SESSION['user_id'] = $row['user_id'];
$_SESSION['user_type'] = $row['role_id'];
$_SESSION['role_id'] = $row['role_id'];
$_SESSION['login_time'] = time();

/* ---------------- FIRST LOGIN CHECK ---------------- */
if ($row['role_id'] == 5) {

    $user_id = $row['user_id'];

    $checkFirst = $db_handle->query(
        "SELECT is_first_login FROM st_user_master WHERE user_id='$user_id'"
    );

    if ($checkFirst && mysqli_num_rows($checkFirst) > 0) {

        $firstRow = mysqli_fetch_assoc($checkFirst);

        if (!empty($firstRow['is_first_login']) && $firstRow['is_first_login'] == 1) {
            echo "change_password";
            exit(); 
        }
    }
}

/* ---------------- ROLE RESPONSE ---------------- */
switch ($row['role_id']) {
    case "1":
        echo "ok";
        break;
    case "2":
        echo "ok1";
        break;
    case "3":
        echo "ok2";
        break;
    case "4":
        echo "ok3";
        break;
    case "5":
        echo "ok4";
        break;
    default:
        echo "email or password does not exist.";
        break;
}

exit();
?>
