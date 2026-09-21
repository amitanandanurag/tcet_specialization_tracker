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

$sessionLoginId = intval($_SESSION['user_login_id'] ?? $_SESSION['user_session'] ?? 0);
$sessionUserId = intval($_SESSION['user_id'] ?? $_SESSION['user_session'] ?? 0);
$sessionRoleId = intval($_SESSION['user_type'] ?? 0);

$username = '';
$userid = 0;
$usertype = 0;
$name = '';

$sql = "SELECT l.login_id, l.username, l.user_id, u.role_id, u.user_name, r.role_name
	    FROM st_login l
	    LEFT JOIN st_user_master u ON u.user_id = l.user_id
	    LEFT JOIN st_role_master r ON r.role_id = u.role_id
	    WHERE l.login_id='" . $sessionLoginId . "' LIMIT 1";
$result = mysqli_query($db_handle->conn, $sql);

if ($result && $result->num_rows > 0) {
	$row = $result->fetch_assoc();
	$username = $row['username'];
	$userid = intval($row['user_id']);
	$usertype = intval($row['role_id']);
	$name = $row['username'];
} else {
	$fallbackSql = "SELECT l.login_id, l.username, l.user_id, u.role_id, u.user_name, r.role_name
				FROM st_login l
				LEFT JOIN st_user_master u ON u.user_id = l.user_id
				LEFT JOIN st_role_master r ON r.role_id = u.role_id
				WHERE l.user_id='" . $sessionUserId . "' ORDER BY l.login_id DESC LIMIT 1";

	$fallbackResult = mysqli_query($db_handle->conn, $fallbackSql);
	if ($fallbackResult && $fallbackResult->num_rows > 0) {
		$row = $fallbackResult->fetch_assoc();
		$username = $row['username'];
		$userid = intval($row['user_id']);
		$usertype = intval($row['role_id']);
		$name = $row['username'];

		$_SESSION['user_session'] = $row['login_id'];
		$_SESSION['user_login_id'] = $row['login_id'];
		$_SESSION['user_id'] = $row['user_id'];
		$_SESSION['user_type'] = $row['role_id'];
	} else {
		header("location: ../index.php");
		exit();
	}
}

$name = $username;
$profileSql = "SELECT COALESCE(NULLIF(TRIM(u.user_name), ''), l.username) AS user_name
			   FROM st_login l
			   LEFT JOIN st_user_master u ON u.user_id = l.user_id
			   WHERE l.user_id = $userid LIMIT 1";
$profileResult = mysqli_query($db_handle->conn, $profileSql);
if ($profileResult && ($profileRow = mysqli_fetch_assoc($profileResult))) {
	$profileName = trim((string)($profileRow['user_name'] ?? ''));
	if ($profileName !== '') {
		$name = $profileName;
	}
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
	<!-- Institutional ERP Design System -->
	<link rel="stylesheet" href="css/erp-theme.css">
	<script
		src="https://code.jquery.com/jquery-3.3.1.js"
		integrity="sha256-2Kok7MbOyxpgUVvAk/HJ2jigOSYS2auK4Pfzbm7uH60="
		crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.7.1/jszip.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.2/FileSaver.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.13/jspdf.plugin.autotable.min.js"></script>
	<style>
		/* Global responsive helpers for admin pages */
		.main-content-responsive .box,
		.main-content-responsive .small-box,
		.main-content-responsive .info-box {
			max-width: 100%;
		}

		.main-content-responsive .box-body,
		.main-content-responsive .table-responsive {
			overflow-x: auto;
		}

		.main-content-responsive .content-wrapper img,
		.main-content-responsive .main-footer img {
			max-width: 100%;
			height: auto;
		}

		.main-content-responsive .main-header .logo img {
			height: 32px !important;
			width: auto !important;
			max-width: none;
			object-fit: contain;
		}

		@media (max-width: 991px) {
			.main-content-responsive .content {
				padding-left: 10px;
				padding-right: 10px;
			}

			.main-content-responsive .content-header {
				padding: 12px 10px;
			}

			.main-content-responsive .content-header h1 {
				font-size: 22px;
				line-height: 1.25;
			}

			.main-content-responsive .main-footer {
				margin-left: 0 !important;
			}
		}

		@media (max-width: 767px) {
			.main-content-responsive .main-header .logo img {
				height: 26px !important;
			}

			.main-content-responsive .content-header>.breadcrumb {
				position: static;
				float: none;
				display: block;
				margin-top: 8px;
				padding-left: 0;
			}

			.main-content-responsive .navbar-custom-menu>.navbar-nav>li>.dropdown-menu {
				right: 0;
				left: auto;
			}

			.main-content-responsive .form-horizontal .control-label {
				text-align: left;
				padding-top: 0;
				margin-bottom: 6px;
			}

			.main-content-responsive .btn {
				white-space: normal;
			}
		}

		@media (max-width: 480px) {
			.main-content-responsive .content {
				padding-left: 8px;
				padding-right: 8px;
			}

			.main-content-responsive .content-header h1 {
				font-size: 20px;
			}

			.main-content-responsive .main-header .logo {
				width: 160px;
			}

			.main-content-responsive .main-header .navbar {
				margin-left: 160px;
			}
		}
	</style>
</head>

<body class="hold-transition skin-blue sidebar-mini main-content-responsive">
	<div class="wrapper">

		<header class="main-header">
			<!-- Logo -->
			<a href="<?php echo $dashboardRoute; ?>" class="logo" style="text-decoration: none;">
				<!-- mini logo for sidebar mini 50x50 pixels -->
				<span class="logo-mini"><img src="images/tcet_logo.png" height="30px" style="border-radius: 4px; background: #ffffff; padding: 2px; object-fit: contain;" alt="TCET" /></span>
				<!-- logo for regular state and mobile devices -->
				<span class="logo-lg" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
					<img src="images/tcet_logo.png" height="32px" style="border-radius: 4px; background: #ffffff; padding: 2px; object-fit: contain;" alt="TCET" />
					<span style="font-weight: 700; font-size: 15px; letter-spacing: 0.5px;">TCET <span style="font-weight: 400; opacity: 0.85; font-size: 13px;">ERP</span></span>
				</span>
			</a>
			<!-- Header Navbar -->
			<nav class="navbar navbar-static-top" role="navigation">
				<!-- Sidebar toggle button-->
				<a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button" title="Toggle Navigation">
					<span class="sr-only">Toggle navigation</span>
					<i class="fa fa-bars"></i>
				</a>

				<div class="navbar-custom-menu">
					<ul class="nav navbar-nav">
						<!-- Reload Page Button -->
						<li>
							<a href="javascript:void(0);" onclick="window.location.reload();" title="Refresh Page" style="height: 50px; display: flex; align-items: center; padding: 0 14px; color: #ffffff; opacity: 0.9;">
								<i class="fa fa-refresh" style="font-size: 14px;"></i>
							</a>
						</li>

						<!-- Compact User Profile Menu -->
						<li class="dropdown user user-menu">
							<a href="#" class="dropdown-toggle erp-header-user-btn" data-toggle="dropdown">
								<img src="dist/img/user2-160x160.jpg" class="erp-header-avatar" alt="User Image">
								<div class="erp-header-user-meta hidden-xs">
									<span class="erp-header-user-name"><?php echo htmlspecialchars($name !== '' ? $name : $username); ?></span>
									<span class="erp-header-user-role"><?php echo htmlspecialchars($role_name ?? 'User'); ?></span>
								</div>
								<i class="fa fa-angle-down hidden-xs" style="font-size: 11px; opacity: 0.8; margin-left: 4px;"></i>
							</a>
							<ul class="dropdown-menu">
								<li class="erp-user-dropdown-header">
									<img src="dist/img/user2-160x160.jpg" alt="User Image">
									<p class="erp-user-dropdown-name"><?php echo htmlspecialchars($name !== '' ? $name : $username); ?></p>
									<div class="erp-user-dropdown-role"><?php echo htmlspecialchars($role_name ?? 'User'); ?></div>
								</li>
								<li class="erp-user-dropdown-body">
									<a href="profile.php"><i class="fa fa-user"></i> My Profile</a>
									<a href="change_password.php"><i class="fa fa-lock"></i> Update Password</a>
									<a href="../login/logout.php" class="logout-link"><i class="fa fa-sign-out"></i> Sign Out</a>
								</li>
							</ul>
						</li>
					</ul>
				</div>
			</nav>
		</header>
		<!-- Left side column. contains the logo and sidebar -->
		<aside class="main-sidebar">
			<!-- sidebar: style can be found in sidebar.less -->
			<section class="sidebar">
				<div class="erp-sidebar-brand hidden-xs">
					<img src="images/tcet_logo.png" alt="TCET Logo" style="width: 34px; height: 34px; border-radius: 4px; object-fit: contain; background: #ffffff; padding: 2px; border: 1px solid rgba(255,255,255,0.2);">
					<div>
						<div class="erp-sidebar-brand-title">TCET Mumbai</div>
						<span class="erp-sidebar-brand-sub">Academic ERP Portal</span>
					</div>
				</div>
				<?php include_once "side_menu.php"; ?>
				<!-- /.sidebar -->
			</section>
		</aside>
