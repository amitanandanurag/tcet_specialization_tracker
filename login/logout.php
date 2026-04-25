<?php
	session_start();
	unset($_SESSION['user_session']);
	unset($_SESSION['user_login_id']);
	unset($_SESSION['user_id']);
	unset($_SESSION['user_type']);
	if(session_destroy()) {
		header("Location: ../");
	}
	
?>
