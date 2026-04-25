<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

if ((int) ($_SESSION['user_type'] ?? 0) === 5) {
	header("location: student_dashboard.php");
	exit;
}


include "Dashboard.php";
?>
