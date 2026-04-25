<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
include_once("../database/db_connect.php");
if (isset($_SESSION['user_session'])) {
} else {
	header("location: ../index.php");
	exit;
}

$db_handle = new DBController();

$sessionUserId = (int) ($_SESSION['user_session'] ?? 0);
$sessionRoleId = (int) ($_SESSION['user_type'] ?? 0);
$username = '';
$userid = 0;
$usertype = 0;
$name = '';
$role_name = '';

$sql = "SELECT l.username, l.user_id, l.role_id,
			   COALESCE(NULLIF(TRIM(s.fname), ''), NULLIF(TRIM(u.user_name), ''), l.username) AS display_name
		FROM st_login l
		LEFT JOIN st_user_master u ON u.user_id = l.user_id AND u.role_id = l.role_id
		LEFT JOIN st_student_master s ON s.student_id = l.user_id AND l.role_id = 5
		WHERE l.user_id = ?" . ($sessionRoleId > 0 ? " AND l.role_id = ?" : "") . "
		LIMIT 1";
$stmt = mysqli_prepare($db_handle->conn, $sql);
if ($stmt) {
	if ($sessionRoleId > 0) {
		mysqli_stmt_bind_param($stmt, 'ii', $sessionUserId, $sessionRoleId);
	} else {
		mysqli_stmt_bind_param($stmt, 'i', $sessionUserId);
	}
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	if ($row = mysqli_fetch_assoc($result)) {
		$username = $row['username'];
		$userid = (int) $row['user_id'];
		$usertype = (int) $row['role_id'];
		$name = $row['display_name'];
		$_SESSION['user_type'] = $usertype;
	}
	mysqli_stmt_close($stmt);
}

if ($userid <= 0 || $usertype <= 0) {
	header("location: ../index.php");
	exit;
}

$sql = "SELECT role_name FROM st_role_master WHERE role_id = ? LIMIT 1";
$stmt = mysqli_prepare($db_handle->conn, $sql);
if ($stmt) {
	mysqli_stmt_bind_param($stmt, 'i', $usertype);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	if ($row = mysqli_fetch_assoc($result)) {
		$role_name = $row['role_name'];
	}
	mysqli_stmt_close($stmt);
}

$dashboardRoute = ($usertype === 5) ? 'student_dashboard.php' : 'index.php';
?>
<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>TCET | Dashboard</title>
	<!-- Tell the browser to be responsive to screen width -->
	<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
	<!-- Bootstrap 3.3.6 -->
	<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
	<!-- Font Awesome -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
	<!-- Ionicons -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
	<!-- Theme style -->
	<link rel="stylesheet" href="dist/css/AdminLTE.min.css">
	<!-- AdminLTE Skins. Choose a skin from the css/skins folder instead of downloading all of them to reduce the load. -->
	<link rel="stylesheet" href="dist/css/skins/_all-skins.min.css">
	<!-- iCheck -->
	<link rel="stylesheet" href="plugins/iCheck/flat/blue.css">
	<!-- Morris chart -->
	<link rel="stylesheet" href="plugins/morris/morris.css">
	<!-- jvectormap -->
	<link rel="stylesheet" href="plugins/jvectormap/jquery-jvectormap-1.2.2.css">
	<!-- Date Picker -->
	<link rel="stylesheet" href="plugins/datepicker/datepicker3.css">
	<!-- Daterange picker -->
	<link rel="stylesheet" href="plugins/daterangepicker/daterangepicker.css">
	<!-- bootstrap wysihtml5 - text editor -->
	<link rel="stylesheet" href="plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">
	<script
		src="https://code.jquery.com/jquery-3.3.1.js"
		integrity="sha256-2Kok7MbOyxpgUVvAk/HJ2jigOSYS2auK4Pfzbm7uH60="
		crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.7.1/jszip.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.2/FileSaver.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.13/jspdf.plugin.autotable.min.js"></script>
</head>

<body class="hold-transition skin-blue sidebar-mini">
	<div class="wrapper">

		<header class="main-header">
			<!-- Logo -->
			<a href="<?php echo $dashboardRoute; ?>" class="logo">
				<!-- mini logo for sidebar mini 50x50 pixels -->
				<span class="logo-mini"><img src="images/booklogo.webp" class="py-2" height="40px" /></span>
				<!-- logo for regular state and mobile devices -->
				<span class="logo-lg"><img src="images/booklogo.webp" class="py-2" height="40px" /> <small>TCET</small></span>
			</a>
			<!-- Header Navbar: style can be found in header.less -->
			<nav class="navbar navbar-static-top">
				<!-- Sidebar toggle button-->
				<a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
					<span class="sr-only">Toggle navigation</span>
				</a>

				<div class="navbar-custom-menu">
					<ul class="nav navbar-nav">

						<li class="dropdown user user-menu">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown">
								<img src="dist/img/user2-160x160.jpg" class="user-image" alt="User Image">
								<span class="hidden-xs">&nbsp;Welcome, <?php echo $name; ?> &nbsp;</span>
							</a>
							<ul class="dropdown-menu">
								<li class="user-header text-center" style="padding: 14px; background-color: #423cbc;">
									<img src="dist/img/user2-160x160.jpg" class="img-circle" alt="User Image"
										style="width: 90px; height: 90px; border: 2px solid rgba(255,255,255,0.2);">

									<p class="text-white" style="margin-top: 10px; color: #fff; font-size: 17px;">
										<?php echo $username; ?>
										<br>
										<small>Role: <span class="badge"><?php echo $role_name; ?></span></small>
									</p>
								</li>

								<li class="user-footer" style="background-color: #f9f9f9; padding: 10px;">
									<div class="pull-left">
										<a href="profile.php" class="btn btn-default btn-flat">Profile</a>
									</div>
									<div class="pull-right">
										<a href="../login/logout.php" class="btn btn-danger btn-flat">Sign out</a>
									</div>
									<div class="clearfix"></div>
								</li>
							</ul>
						</li>
						<!-- Control Sidebar Toggle Button -->
						<li>
							<a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a>
						</li>
					</ul>
				</div>
			</nav>
		</header>
		<!-- Left side column. contains the logo and sidebar -->
		<aside class="main-sidebar">
			<!-- sidebar: style can be found in sidebar.less -->
			<section class="sidebar">
				<!-- Sidebar user panel -->
				<div class="user-panel">
					<div class="pull-left image">
						<img src="dist/img/user2-160x160.jpg" class="img-circle" alt="User Image">
					</div>
					<div class="pull-left info">
						<p><?php echo $name;  ?></p>
						<a class="badge" href="#" style="background:white; color: green;">
							<i class="fa fa-circle text-success"></i> Online
						</a>
					</div>
				</div>
				<br />
				<?php include "side_menu.php"; ?>
				<!-- /.sidebar -->
		</aside>
