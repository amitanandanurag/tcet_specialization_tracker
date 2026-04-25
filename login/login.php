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
		echo "email or password does not exist.";
		exit();
	}

	if($row['password']==$user_password){

	    $_SESSION['user_session'] = $row['user_id'];
		$_SESSION['user_type'] = $row['role_id'];

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
		echo "email or password does not exist."; // wrong details
	}
}
?>

