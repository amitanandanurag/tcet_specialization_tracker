<?php
session_start();

include_once("../database/db_connect.php");
if(isset($_POST['login_button'])) {
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

	if($row['password']==$user_password){

	    $_SESSION['user_session'] = $row['login_id'];
	    $_SESSION['user_login_id'] = $row['login_id'];
	    $_SESSION['user_id'] = $row['user_id'];
		$_SESSION['user_type'] = $row['role_id'];
		$_SESSION['login_time'] = time();
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

		if($_SESSION['user_type']=="1"){
			echo "ok";
		}

		else if($_SESSION['user_type']=="2"){
			echo "ok1";
		}

		else if($_SESSION['user_type']=="3"){
			echo "ok2";
			}

		else if($_SESSION['user_type']=="4"){
			echo "ok3";
			}
		else if($_SESSION['user_type']=="5"){
			echo "ok4";
			}

		 else
		 {
		  echo "email or password does not exist.";
		 }

	} else {
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
		echo "email or password does not exist."; // wrong details
	}
}
?>
