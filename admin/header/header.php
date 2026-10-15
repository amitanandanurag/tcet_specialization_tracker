<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}
require_once __DIR__ . "/../../database/db_connect.php";
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

	// =========================================================================
	// CENTRALIZED RBAC ROUTE GUARD
	// =========================================================================
	if (!function_exists('checkUserRouteAuthorization')) {
		function checkUserRouteAuthorization($conn, $userId, $roleId, $scriptName) {
			if ($roleId === 1) {
				return true;
			}
			$universal = array(
				'index.php', 'dashboard.php', 'student_dashboard.php',
				'profile.php', 'setting_profile.php', 'change_password.php',
				'logout.php', 'branch_info.php'
			);
			if (in_array($scriptName, $universal)) {
				return true;
			}
			$routeAliases = array(
				'student-edit.php' => 'student-info.php',
				'student-update.php' => 'student-info.php',
				'student_process.php' => 'student_admission.php',
				'student_view.php' => 'student-info.php',
				'student_admission_view.php' => 'student-info.php',
				'student_delete.php' => 'student-info.php',
				'student_bulk_delete.php' => 'student-info.php',
				'student_info_ajax.php' => 'student-info.php',
				'student_monitor_ajax.php' => 'student_monitor.php',
				'student_concise_details_ajax.php' => 'student_concise_details.php',
				'offline_marks_student_ajax.php' => 'offline_marks_entry.php',
				'admin_edit.php' => 'admin_info.php',
				'admin_process.php' => 'admin_register.php',
				'admin_view.php' => 'admin_info.php',
				'admin_delete.php' => 'admin_info.php',
				'admin_info_ajax.php' => 'admin_info.php',
				'coordinator_edit.php' => 'coordinator_info.php',
				'coordinator_process.php' => 'coordinator_register.php',
				'coordinator_view.php' => 'coordinator_info.php',
				'coordinator_delete.php' => 'coordinator_info.php',
				'coordinator_info_ajax.php' => 'coordinator_info.php',
				'mentor_edit.php' => 'mentor_info.php',
				'mentor_process.php' => 'mentor_register.php',
				'mentor_view.php' => 'mentor_info.php',
				'mentor_delete.php' => 'mentor_info.php',
				'mentor_info_ajax.php' => 'mentor_info.php',
				'class_edit_new.php' => 'class_crud_new.php',
				'class_edit_new_ajax.php' => 'class_crud_new.php',
				'class_manage.php' => 'class_crud_new.php',
				'class_manage_new.php' => 'class_crud_new.php',
				'user_register.php' => 'user-info.php',
				'user_edit.php' => 'user-info.php',
				'user_delete.php' => 'user-info.php',
				'user_info_ajax.php' => 'user-info.php',
				'user_process.php' => 'user-info.php',
				'batch_promotion.php' => 'student_monitor.php',
				'export_service.php' => 'student-info.php'
			);
			if ($roleId === 5 && ($scriptName === 'student_admission_view.php' || $scriptName === 'student.php')) {
				return true;
			}
			$checkScript = $routeAliases[$scriptName] ?? $scriptName;
			$sql = "SELECT 1 
					FROM st_menu_allocation_master a
					JOIN st_sub_menu_master sm ON a.sub_menu_id = sm.sub_menu_id
					WHERE (a.role_id = ? OR a.user_id = ?)
					  AND (sm.sub_menu_route LIKE ? OR sm.sub_menu_route LIKE ?)
					LIMIT 1";
			$stmt = mysqli_prepare($conn, $sql);
			if ($stmt) {
				$pattern1 = $checkScript . '%';
				$pattern2 = '%' . $checkScript . '%';
				mysqli_stmt_bind_param($stmt, 'iiss', $roleId, $userId, $pattern1, $pattern2);
				mysqli_stmt_execute($stmt);
				$res = mysqli_stmt_get_result($stmt);
				$hasPerm = ($res && mysqli_num_rows($res) > 0);
				mysqli_stmt_close($stmt);
				return $hasPerm;
			}
			return false;
		}
	}

	$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');
	if (!checkUserRouteAuthorization($db_handle->conn, $userid, $usertype, $currentScript)) {
		if (method_exists($db_handle, 'writeAuditLog')) {
			$db_handle->writeAuditLog($userid, 'UNAUTHORIZED_ACCESS_ATTEMPT', null, null, "User role {$usertype} attempted to access restricted route: {$currentScript}");
		}
		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			http_response_code(403);
			header('Content-Type: application/json');
			echo json_encode(array('status' => 'error', 'message' => 'Access Denied: You do not have permission to access this resource.'));
			exit;
		}
		echo '<!DOCTYPE html>
		<html lang="en">
		<head>
			<meta charset="utf-8">
			<title>403 Access Denied - TCET ERP</title>
			<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
			<link rel="stylesheet" href="css/erp-theme.css">
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
			<style>
				body { background: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
				.denied-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 40px; max-width: 480px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
				.denied-icon { font-size: 48px; color: #dc2626; margin-bottom: 16px; }
				.denied-title { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 8px; }
				.denied-text { font-size: 13px; color: #64748b; margin-bottom: 24px; line-height: 1.5; }
			</style>
		</head>
		<body>
			<div class="denied-card">
				<div class="denied-icon"><i class="fa fa-shield"></i></div>
				<h1 class="denied-title">Access Restricted</h1>
				<p class="denied-text">You do not have administrative permission to access <strong>' . htmlspecialchars($currentScript) . '</strong>. If you believe this is an error, please contact your ERP administrator.</p>
				<a href="' . htmlspecialchars($dashboardRoute) . '" class="btn-erp-primary"><i class="fa fa-arrow-left"></i> Return to Dashboard</a>
			</div>
		</body>
		</html>';
		exit;
	}
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
	<meta name="csrf-token" content="<?php echo DBController::getCsrfToken(); ?>">
	<script>
		if (window.jQuery) {
			$.ajaxSetup({
				headers: {
					'X-CSRF-Token': '<?php echo DBController::getCsrfToken(); ?>'
				}
			});
		}
	</script>
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
		}
	</style>
</head>

<body class="hold-transition skin-blue sidebar-mini main-content-responsive">
	<div class="wrapper">

		<header class="main-header">
			<!-- Logo -->
			<a href="<?php echo $dashboardRoute; ?>" class="logo" style="text-decoration: none;">
				<!-- mini logo for sidebar mini 50x50 pixels: ONLY logo image -->
				<span class="logo-mini">
					<img src="images/tcet_logo.png" height="32" width="32" style="border-radius: 4px; background: #ffffff; padding: 2px; object-fit: contain;" alt="TCET" />
				</span>
				<!-- logo for regular state and expanded sidebar -->
				<span class="logo-lg">
					<img src="images/tcet_logo.png" height="32" width="32" style="border-radius: 4px; background: #ffffff; padding: 2px; object-fit: contain;" alt="TCET" />
					<span style="font-weight: 700; font-size: 15px; letter-spacing: 0.5px;">TCET <span style="font-weight: 400; opacity: 0.85; font-size: 13px;">ERP</span></span>
				</span>
			</a>
			<!-- Header Navbar -->
			<nav class="navbar navbar-static-top" role="navigation">
				<!-- Sidebar toggle button-->
				<a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button" title="Toggle Navigation">
					<span class="sr-only">Toggle navigation</span>
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
				<?php include_once "side_menu.php"; ?>
				<!-- /.sidebar -->
			</section>
		</aside>
