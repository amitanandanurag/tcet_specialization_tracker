<?php
session_start();

include_once("../database/db_connect.php");
if(isset($_POST['login_button'])) {
$db_handle = new DBController();

	$username = trim($_POST['username']);
	$user_password = trim($_POST['password']);

	$sql = "SELECT * FROM st_login WHERE username='$username'";
	$resultset = mysqli_query($db_handle->conn, $sql) or die("database error:". mysqli_error($db_handle->conn));
	$row = mysqli_fetch_assoc($resultset);
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
