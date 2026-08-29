<?php
function sidebar_has_column($conn, $table, $column)
{
	$escapedColumn = mysqli_real_escape_string($conn, $column);
	$result = mysqli_query($conn, "SHOW COLUMNS FROM $table LIKE '$escapedColumn'");
	$exists = ($result && mysqli_num_rows($result) > 0);

	if ($result) {
		mysqli_free_result($result);
	}

	return $exists;
}

$menuHasIconColumn = sidebar_has_column($db_handle->conn, 'st_menu_master', 'menu_icon');
$subMenuHasIconColumn = sidebar_has_column($db_handle->conn, 'st_sub_menu_master', 'sub_menu_icon');
$subMenuHasRouteColumn = sidebar_has_column($db_handle->conn, 'st_sub_menu_master', 'sub_menu_route');

$menuTree = array();
$studentAdmissionRoute = 'student_admission.php';

if ((int) ($usertype ?? 0) === 5 && !empty($userid)) {
	$studentRouteSql = "SELECT student_id FROM st_user_master WHERE user_id = ? AND student_id > 0 LIMIT 1";
	$studentRouteStmt = mysqli_prepare($db_handle->conn, $studentRouteSql);
	if ($studentRouteStmt) {
		mysqli_stmt_bind_param($studentRouteStmt, 'i', $userid);
		mysqli_stmt_execute($studentRouteStmt);
		$studentRouteResult = mysqli_stmt_get_result($studentRouteStmt);
		if ($studentRouteResult && ($studentRouteRow = mysqli_fetch_assoc($studentRouteResult))) {
			$studentAdmissionRoute = 'student_admission_view.php?id=' . intval($studentRouteRow['student_id']);
		}
		mysqli_stmt_close($studentRouteStmt);
	}
}

$menuIconSelect = $menuHasIconColumn
	? "COALESCE(NULLIF(TRIM(m.menu_icon), ''), 'fa fa-folder') AS menu_icon"
	: "'fa fa-folder' AS menu_icon";
$subMenuRouteSelect = $subMenuHasRouteColumn
	? "COALESCE(NULLIF(TRIM(sm.sub_menu_route), ''), '#') AS sub_menu_route"
	: "'#' AS sub_menu_route";
$subMenuIconSelect = $subMenuHasIconColumn
	? "COALESCE(NULLIF(TRIM(sm.sub_menu_icon), ''), 'fa fa-angle-double-right') AS sub_menu_icon"
	: "'fa fa-angle-double-right' AS sub_menu_icon";

function sidebar_seed_super_admin_settings($db_handle)
{
	$menuId = sidebar_resolve_system_menu_id($db_handle->conn);
	if ($menuId <= 0) {
		return;
	}

	$defaultSettings = array(
		array('Profile', 'fa fa-user', 'profile.php'),
		array('Update Password', 'fa fa-lock', 'change_password.php'),
		array('Side Menu Allocation', 'fa fa-check-square-o', 'allocation_master.php')
	);

	foreach ($defaultSettings as $item) {
		$name = mysqli_real_escape_string($db_handle->conn, $item[0]);
		$icon = mysqli_real_escape_string($db_handle->conn, $item[1]);
		$route = mysqli_real_escape_string($db_handle->conn, $item[2]);

		$subSql = "SELECT sub_menu_id FROM st_sub_menu_master WHERE menu_id = {$menuId} AND (sub_menu_route = '$route' OR sub_menu_name = '$name') LIMIT 1";
		$subResult = mysqli_query($db_handle->conn, $subSql);
		if (!$subResult || mysqli_num_rows($subResult) === 0) {
			$nextOrder = 1;
			$orderSql = "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM st_sub_menu_master WHERE menu_id = {$menuId}";
			$orderResult = mysqli_query($db_handle->conn, $orderSql);
			if ($orderResult && mysqli_num_rows($orderResult) > 0) {
				$orderRow = mysqli_fetch_assoc($orderResult);
				$nextOrder = (int) ($orderRow['next_order'] ?? 1);
			}
			$insertSql = "INSERT INTO st_sub_menu_master (menu_id, sort_order, sub_menu_name, sub_menu_icon, sub_menu_route) VALUES ({$menuId}, {$nextOrder}, '$name', '$icon', '$route')";
			mysqli_query($db_handle->conn, $insertSql);
		}
	}

	$parentSql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 1 AND menu_id = {$menuId} AND sub_menu_id IS NULL LIMIT 1";
	$parentResult = mysqli_query($db_handle->conn, $parentSql);
	if (!$parentResult || mysqli_num_rows($parentResult) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 1, {$menuId}, NULL)");
	}

	$settingsRows = mysqli_query($db_handle->conn, "SELECT sub_menu_id FROM st_sub_menu_master WHERE menu_id = {$menuId}");
	if ($settingsRows) {
		while ($settingRow = mysqli_fetch_assoc($settingsRows)) {
			$subMenuId = (int) ($settingRow['sub_menu_id'] ?? 0);
			if ($subMenuId > 0) {
				$allocSql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 1 AND menu_id = {$menuId} AND sub_menu_id = {$subMenuId} LIMIT 1";
				$allocResult = mysqli_query($db_handle->conn, $allocSql);
				if (!$allocResult || mysqli_num_rows($allocResult) === 0) {
					mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 1, {$menuId}, {$subMenuId})");
				}
			}
		}
	}

	// Allocate Profile, Update Password to role 5 (Student)
	$studentRoutes = array('profile.php', 'change_password.php');
	foreach ($studentRoutes as $route) {
		$studentSubSql = "SELECT sub_menu_id FROM st_sub_menu_master WHERE menu_id = {$menuId} AND sub_menu_route = '$route' LIMIT 1";
		$studentSubRes = mysqli_query($db_handle->conn, $studentSubSql);
		if ($studentSubRes && mysqli_num_rows($studentSubRes) > 0) {
			$studentSubRow = mysqli_fetch_assoc($studentSubRes);
			$studentSubMenuId = intval($studentSubRow['sub_menu_id']);
			
			$parentSql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 5 AND menu_id = {$menuId} AND sub_menu_id IS NULL LIMIT 1";
			if (mysqli_num_rows(mysqli_query($db_handle->conn, $parentSql)) === 0) {
				mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 5, {$menuId}, NULL)");
			}
			
			$allocSql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 5 AND menu_id = {$menuId} AND sub_menu_id = {$studentSubMenuId} LIMIT 1";
			if (mysqli_num_rows(mysqli_query($db_handle->conn, $allocSql)) === 0) {
				mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 5, {$menuId}, {$studentSubMenuId})");
			}
		}
	}
}

function sidebar_seed_mentor_menu($db_handle)
{
	$menuId = 0;
	$menuResult = mysqli_query($db_handle->conn, "SELECT menu_id FROM st_menu_master WHERE LOWER(TRIM(menu_name)) = 'mentor' ORDER BY menu_id ASC LIMIT 1");
	if ($menuResult && mysqli_num_rows($menuResult) > 0) {
		$menuRow = mysqli_fetch_assoc($menuResult);
		$menuId = (int) ($menuRow['menu_id'] ?? 0);
	}

	if ($menuId <= 0) {
		return;
	}

	// First, remove Offline Marks Entry from Settings menu (menu ID 5)
	$settingsMenuId = sidebar_resolve_system_menu_id($db_handle->conn);
	if ($settingsMenuId > 0) {
		$oldSubRes = mysqli_query($db_handle->conn, "SELECT sub_menu_id FROM st_sub_menu_master WHERE menu_id = {$settingsMenuId} AND sub_menu_route = 'offline_marks_entry.php' LIMIT 1");
		if ($oldSubRes && mysqli_num_rows($oldSubRes) > 0) {
			$oldSubRow = mysqli_fetch_assoc($oldSubRes);
			$oldSubId = intval($oldSubRow['sub_menu_id']);
			mysqli_query($db_handle->conn, "DELETE FROM st_menu_allocation_master WHERE menu_id = {$settingsMenuId} AND sub_menu_id = {$oldSubId}");
			mysqli_query($db_handle->conn, "DELETE FROM st_sub_menu_master WHERE sub_menu_id = {$oldSubId}");
		}
	}

	// Submenus to seed under MENTOR category
	$submenus = array(
		array('Register Mentor', 'fa fa-plus', 'mentor_register.php', array(1, 2)),
		array('Mentor Info', 'fa fa-info-circle', 'mentor_info.php', array(1, 2)),
		array('Mentor Allocation', 'fa fa-exchange', 'mentor_allocation.php', array(1, 2, 3)),
		array('Offline Marks Entry', 'fa fa-pencil-square-o', 'offline_marks_entry.php', array(1, 2, 3, 4))
	);

	foreach ($submenus as $item) {
		$name = mysqli_real_escape_string($db_handle->conn, $item[0]);
		$icon = mysqli_real_escape_string($db_handle->conn, $item[1]);
		$route = mysqli_real_escape_string($db_handle->conn, $item[2]);
		$roles = $item[3];

		$subSql = "SELECT sub_menu_id FROM st_sub_menu_master WHERE menu_id = {$menuId} AND (sub_menu_route = '$route' OR sub_menu_name = '$name') LIMIT 1";
		$subResult = mysqli_query($db_handle->conn, $subSql);
		$subMenuId = 0;

		if ($subResult && mysqli_num_rows($subResult) > 0) {
			$subRow = mysqli_fetch_assoc($subResult);
			$subMenuId = (int) ($subRow['sub_menu_id'] ?? 0);
			mysqli_query($db_handle->conn, "UPDATE st_sub_menu_master SET menu_id = {$menuId}, sub_menu_name = '$name', sub_menu_icon = '$icon', sub_menu_route = '$route' WHERE sub_menu_id = {$subMenuId}");
		} else {
			$orderSql = "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM st_sub_menu_master WHERE menu_id = {$menuId}";
			$orderResult = mysqli_query($db_handle->conn, $orderSql);
			$nextOrder = 1;
			if ($orderResult && mysqli_num_rows($orderResult) > 0) {
				$orderRow = mysqli_fetch_assoc($orderResult);
				$nextOrder = (int) ($orderRow['next_order'] ?? 1);
			}

			$insertSql = "INSERT INTO st_sub_menu_master (menu_id, sort_order, sub_menu_name, sub_menu_icon, sub_menu_route) VALUES ({$menuId}, {$nextOrder}, '$name', '$icon', '$route')";
			if (mysqli_query($db_handle->conn, $insertSql)) {
				$subMenuId = (int) mysqli_insert_id($db_handle->conn);
			}
		}

		if ($subMenuId > 0) {
			foreach ($roles as $roleId) {
				$parentSql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = {$roleId} AND menu_id = {$menuId} AND sub_menu_id IS NULL LIMIT 1";
				if (mysqli_num_rows(mysqli_query($db_handle->conn, $parentSql)) === 0) {
					mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, {$roleId}, {$menuId}, NULL)");
				}
				$allocSql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = {$roleId} AND menu_id = {$menuId} AND sub_menu_id = {$subMenuId} LIMIT 1";
				if (mysqli_num_rows(mysqli_query($db_handle->conn, $allocSql)) === 0) {
					mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, {$roleId}, {$menuId}, {$subMenuId})");
				}
			}
			$rolesStr = implode(',', $roles);
			mysqli_query($db_handle->conn, "DELETE FROM st_menu_allocation_master WHERE sub_menu_id = {$subMenuId} AND role_id NOT IN ($rolesStr)");
		}
	}
}

function sidebar_seed_coordinator_allocation_menu($db_handle)
{
	$menuId = 0;
	$menuResult = mysqli_query($db_handle->conn, "SELECT menu_id FROM st_menu_master WHERE LOWER(TRIM(menu_name)) = 'coordinator' ORDER BY menu_id ASC LIMIT 1");
	if ($menuResult && mysqli_num_rows($menuResult) > 0) {
		$menuRow = mysqli_fetch_assoc($menuResult);
		$menuId = (int) ($menuRow['menu_id'] ?? 0);
	}

	if ($menuId <= 0) {
		return;
	}

	$name = mysqli_real_escape_string($db_handle->conn, 'Coordinator Allocation');
	$icon = mysqli_real_escape_string($db_handle->conn, 'fa fa-exchange');
	$route = mysqli_real_escape_string($db_handle->conn, 'coordinator_allocation.php');

	$subSql = "SELECT sub_menu_id FROM st_sub_menu_master WHERE menu_id = {$menuId} AND (sub_menu_route = '$route' OR sub_menu_name = '$name') LIMIT 1";
	$subResult = mysqli_query($db_handle->conn, $subSql);
	$subMenuId = 0;

	if ($subResult && mysqli_num_rows($subResult) > 0) {
		$subRow = mysqli_fetch_assoc($subResult);
		$subMenuId = (int) ($subRow['sub_menu_id'] ?? 0);
	} else {
		$orderSql = "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM st_sub_menu_master WHERE menu_id = {$menuId}";
		$orderResult = mysqli_query($db_handle->conn, $orderSql);
		$nextOrder = 1;
		if ($orderResult && mysqli_num_rows($orderResult) > 0) {
			$orderRow = mysqli_fetch_assoc($orderResult);
			$nextOrder = (int) ($orderRow['next_order'] ?? 1);
		}

		$insertSql = "INSERT INTO st_sub_menu_master (menu_id, sort_order, sub_menu_name, sub_menu_icon, sub_menu_route) VALUES ({$menuId}, {$nextOrder}, '$name', '$icon', '$route')";
		if (mysqli_query($db_handle->conn, $insertSql)) {
			$subMenuId = (int) mysqli_insert_id($db_handle->conn);
		}
	}

	if ($subMenuId <= 0) {
		return;
	}

	$parentSql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 1 AND menu_id = {$menuId} AND sub_menu_id IS NULL LIMIT 1";
	$parentResult = mysqli_query($db_handle->conn, $parentSql);
	if (!$parentResult || mysqli_num_rows($parentResult) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 1, {$menuId}, NULL)");
	}

	$allocSql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 1 AND menu_id = {$menuId} AND sub_menu_id = {$subMenuId} LIMIT 1";
	$allocResult = mysqli_query($db_handle->conn, $allocSql);
	if (!$allocResult || mysqli_num_rows($allocResult) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 1, {$menuId}, {$subMenuId})");
	}
}

function sidebar_seed_student_monitor_menu($db_handle)
{
	$menuId = 0;
	$menuResult = mysqli_query($db_handle->conn, "SELECT menu_id FROM st_menu_master WHERE LOWER(TRIM(menu_name)) = 'students' ORDER BY menu_id ASC LIMIT 1");
	if ($menuResult && mysqli_num_rows($menuResult) > 0) {
		$menuRow = mysqli_fetch_assoc($menuResult);
		$menuId = (int) ($menuRow['menu_id'] ?? 0);
	}

	if ($menuId <= 0) {
		$menuId = 1;
	}

	$name = mysqli_real_escape_string($db_handle->conn, 'Monitor');
	$icon = mysqli_real_escape_string($db_handle->conn, 'fa fa-desktop');
	$route = mysqli_real_escape_string($db_handle->conn, 'student_monitor.php');

	$subSql = "SELECT sub_menu_id FROM st_sub_menu_master WHERE menu_id = {$menuId} AND (sub_menu_route = '$route' OR sub_menu_name = '$name') LIMIT 1";
	$subResult = mysqli_query($db_handle->conn, $subSql);
	$subMenuId = 0;

	if ($subResult && mysqli_num_rows($subResult) > 0) {
		$subRow = mysqli_fetch_assoc($subResult);
		$subMenuId = (int) ($subRow['sub_menu_id'] ?? 0);
	} else {
		$orderSql = "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM st_sub_menu_master WHERE menu_id = {$menuId}";
		$orderResult = mysqli_query($db_handle->conn, $orderSql);
		$nextOrder = 1;
		if ($orderResult && mysqli_num_rows($orderResult) > 0) {
			$orderRow = mysqli_fetch_assoc($orderResult);
			$nextOrder = (int) ($orderRow['next_order'] ?? 1);
		}

		$insertSql = "INSERT INTO st_sub_menu_master (menu_id, sort_order, sub_menu_name, sub_menu_icon, sub_menu_route) VALUES ({$menuId}, {$nextOrder}, '$name', '$icon', '$route')";
		if (mysqli_query($db_handle->conn, $insertSql)) {
			$subMenuId = (int) mysqli_insert_id($db_handle->conn);
		}
	}

	if ($subMenuId <= 0) {
		return;
	}

	$parentSql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 1 AND menu_id = {$menuId} AND sub_menu_id IS NULL LIMIT 1";
	$parentResult = mysqli_query($db_handle->conn, $parentSql);
	if (!$parentResult || mysqli_num_rows($parentResult) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 1, {$menuId}, NULL)");
	}

	$allocSql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 1 AND menu_id = {$menuId} AND sub_menu_id = {$subMenuId} LIMIT 1";
	$allocResult = mysqli_query($db_handle->conn, $allocSql);
	if (!$allocResult || mysqli_num_rows($allocResult) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 1, {$menuId}, {$subMenuId})");
	}

	$parentSql2 = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 2 AND menu_id = {$menuId} AND sub_menu_id IS NULL LIMIT 1";
	$parentResult2 = mysqli_query($db_handle->conn, $parentSql2);
	if (!$parentResult2 || mysqli_num_rows($parentResult2) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 2, {$menuId}, NULL)");
	}

	$allocSql2 = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 2 AND menu_id = {$menuId} AND sub_menu_id = {$subMenuId} LIMIT 1";
	$allocResult2 = mysqli_query($db_handle->conn, $allocSql2);
	if (!$allocResult2 || mysqli_num_rows($allocResult2) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 2, {$menuId}, {$subMenuId})");
	}
}

function sidebar_resolve_system_menu_id($conn)
{
	$menuSql = "SELECT menu_id FROM st_menu_master WHERE LOWER(TRIM(menu_name)) IN ('settings', 'admin') ORDER BY CASE WHEN LOWER(TRIM(menu_name)) = 'settings' THEN 0 ELSE 1 END, menu_id ASC LIMIT 1";
	$menuResult = mysqli_query($conn, $menuSql);
	if ($menuResult && mysqli_num_rows($menuResult) > 0) {
		$menuRow = mysqli_fetch_assoc($menuResult);
		return (int) ($menuRow['menu_id'] ?? 0);
	}

	$routeSql = "SELECT menu_id FROM st_sub_menu_master WHERE sub_menu_route IN ('allocation_master.php', 'profile.php', 'change_password.php') ORDER BY menu_id ASC, sort_order ASC, sub_menu_id ASC LIMIT 1";
	$routeResult = mysqli_query($conn, $routeSql);
	if ($routeResult && mysqli_num_rows($routeResult) > 0) {
		$routeRow = mysqli_fetch_assoc($routeResult);
		return (int) ($routeRow['menu_id'] ?? 0);
	}

	return 0;
}

function sidebar_seed_mentor_subject_menu($db_handle)
{
	$menuId = 0;
	$menuResult = mysqli_query($db_handle->conn, "SELECT menu_id FROM st_menu_master WHERE LOWER(TRIM(menu_name)) = 'coordinator' ORDER BY menu_id ASC LIMIT 1");
	if ($menuResult && mysqli_num_rows($menuResult) > 0) {
		$menuRow = mysqli_fetch_assoc($menuResult);
		$menuId = (int) ($menuRow['menu_id'] ?? 0);
	}

	if ($menuId <= 0) {
		return;
	}

	$name = mysqli_real_escape_string($db_handle->conn, 'Mentor Subject');
	$icon = mysqli_real_escape_string($db_handle->conn, 'fa fa-book');
	$route = mysqli_real_escape_string($db_handle->conn, 'mentor_subject.php');

	$subSql = "SELECT sub_menu_id FROM st_sub_menu_master WHERE menu_id = {$menuId} AND (sub_menu_route = '$route' OR sub_menu_name = '$name') LIMIT 1";
	$subResult = mysqli_query($db_handle->conn, $subSql);
	$subMenuId = 0;

	if ($subResult && mysqli_num_rows($subResult) > 0) {
		$subRow = mysqli_fetch_assoc($subResult);
		$subMenuId = (int) ($subRow['sub_menu_id'] ?? 0);
	} else {
		$orderSql = "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM st_sub_menu_master WHERE menu_id = {$menuId}";
		$orderResult = mysqli_query($db_handle->conn, $orderSql);
		$nextOrder = 1;
		if ($orderResult && mysqli_num_rows($orderResult) > 0) {
			$orderRow = mysqli_fetch_assoc($orderResult);
			$nextOrder = (int) ($orderRow['next_order'] ?? 1);
		}

		$insertSql = "INSERT INTO st_sub_menu_master (menu_id, sort_order, sub_menu_name, sub_menu_icon, sub_menu_route) VALUES ({$menuId}, {$nextOrder}, '$name', '$icon', '$route')";
		if (mysqli_query($db_handle->conn, $insertSql)) {
			$subMenuId = (int) mysqli_insert_id($db_handle->conn);
		}
	}

	if ($subMenuId <= 0) {
		return;
	}

	// Allocate to role 1 (Super Admin)
	$parentSql1 = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 1 AND menu_id = {$menuId} AND sub_menu_id IS NULL LIMIT 1";
	if (mysqli_num_rows(mysqli_query($db_handle->conn, $parentSql1)) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 1, {$menuId}, NULL)");
	}
	$allocSql1 = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 1 AND menu_id = {$menuId} AND sub_menu_id = {$subMenuId} LIMIT 1";
	if (mysqli_num_rows(mysqli_query($db_handle->conn, $allocSql1)) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 1, {$menuId}, {$subMenuId})");
	}

	// Allocate to role 2 (Admin)
	$parentSql2 = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 2 AND menu_id = {$menuId} AND sub_menu_id IS NULL LIMIT 1";
	if (mysqli_num_rows(mysqli_query($db_handle->conn, $parentSql2)) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 2, {$menuId}, NULL)");
	}
	$allocSql2 = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 2 AND menu_id = {$menuId} AND sub_menu_id = {$subMenuId} LIMIT 1";
	if (mysqli_num_rows(mysqli_query($db_handle->conn, $allocSql2)) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 2, {$menuId}, {$subMenuId})");
	}

	// Allocate to role 3 (Coordinator)
	$parentSql3 = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 3 AND menu_id = {$menuId} AND sub_menu_id IS NULL LIMIT 1";
	if (mysqli_num_rows(mysqli_query($db_handle->conn, $parentSql3)) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 3, {$menuId}, NULL)");
	}
	$allocSql3 = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 3 AND menu_id = {$menuId} AND sub_menu_id = {$subMenuId} LIMIT 1";
	if (mysqli_num_rows(mysqli_query($db_handle->conn, $allocSql3)) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 3, {$menuId}, {$subMenuId})");
	}
}

function sidebar_seed_subject_management_menu($db_handle)
{
	$menuResult = mysqli_query($db_handle->conn, "SELECT menu_id FROM st_menu_master WHERE LOWER(TRIM(menu_name)) = 'coordinator' LIMIT 1");
	if (!$menuResult || !($menuRow = mysqli_fetch_assoc($menuResult))) {
		return;
	}
	$menuId = intval($menuRow['menu_id']);
	$name = mysqli_real_escape_string($db_handle->conn, 'Subject Management');
	$route = mysqli_real_escape_string($db_handle->conn, 'specialization_subject_manage.php');
	$icon = mysqli_real_escape_string($db_handle->conn, 'fa fa-book');
	$subResult = mysqli_query($db_handle->conn, "SELECT sub_menu_id FROM st_sub_menu_master WHERE menu_id = {$menuId} AND sub_menu_route = '{$route}' LIMIT 1");
	if ($subResult && ($subRow = mysqli_fetch_assoc($subResult))) {
		$subMenuId = intval($subRow['sub_menu_id']);
	} else {
		$orderResult = mysqli_query($db_handle->conn, "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM st_sub_menu_master WHERE menu_id = {$menuId}");
		$orderRow = $orderResult ? mysqli_fetch_assoc($orderResult) : array();
		$nextOrder = intval($orderRow['next_order'] ?? 1);
		mysqli_query($db_handle->conn, "INSERT INTO st_sub_menu_master (menu_id, sort_order, sub_menu_name, sub_menu_icon, sub_menu_route) VALUES ({$menuId}, {$nextOrder}, '{$name}', '{$icon}', '{$route}')");
		$subMenuId = intval(mysqli_insert_id($db_handle->conn));
	}
	if ($subMenuId <= 0) return;
	foreach (array(1, 2, 3, 4) as $roleId) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) SELECT 0, {$roleId}, {$menuId}, {$subMenuId} WHERE NOT EXISTS (SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = {$roleId} AND sub_menu_id = {$subMenuId})");
	}
}

function sidebar_seed_student_nptel_menu($db_handle)
{
	$menuId = 0;
	$menuResult = mysqli_query($db_handle->conn, "SELECT menu_id FROM st_menu_master WHERE LOWER(TRIM(menu_name)) = 'students' ORDER BY menu_id ASC LIMIT 1");
	if ($menuResult && mysqli_num_rows($menuResult) > 0) {
		$menuRow = mysqli_fetch_assoc($menuResult);
		$menuId = (int) ($menuRow['menu_id'] ?? 0);
	}

	if ($menuId <= 0) {
		$menuId = 1;
	}

	$name = mysqli_real_escape_string($db_handle->conn, 'NPTEL Pass or Fail');
	$icon = mysqli_real_escape_string($db_handle->conn, 'fa fa-certificate');
	$route = mysqli_real_escape_string($db_handle->conn, 'nptel_certificate.php');

	$subSql = "SELECT sub_menu_id FROM st_sub_menu_master WHERE menu_id = {$menuId} AND (sub_menu_route = '$route' OR sub_menu_name = '$name') LIMIT 1";
	$subResult = mysqli_query($db_handle->conn, $subSql);
	$subMenuId = 0;

	if ($subResult && mysqli_num_rows($subResult) > 0) {
		$subRow = mysqli_fetch_assoc($subResult);
		$subMenuId = (int) ($subRow['sub_menu_id'] ?? 0);
	} else {
		// Clean up any old duplicate settings menu entry first (just in case)
		$settingsMenuId = sidebar_resolve_system_menu_id($db_handle->conn);
		if ($settingsMenuId > 0) {
			mysqli_query($db_handle->conn, "DELETE FROM st_sub_menu_master WHERE menu_id = {$settingsMenuId} AND sub_menu_route = 'nptel_certificate.php'");
		}

		$orderSql = "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM st_sub_menu_master WHERE menu_id = {$menuId}";
		$orderResult = mysqli_query($db_handle->conn, $orderSql);
		$nextOrder = 1;
		if ($orderResult && mysqli_num_rows($orderResult) > 0) {
			$orderRow = mysqli_fetch_assoc($orderResult);
			$nextOrder = (int) ($orderRow['next_order'] ?? 1);
		}

		$insertSql = "INSERT INTO st_sub_menu_master (menu_id, sort_order, sub_menu_name, sub_menu_icon, sub_menu_route) VALUES ({$menuId}, {$nextOrder}, '$name', '$icon', '$route')";
		if (mysqli_query($db_handle->conn, $insertSql)) {
			$subMenuId = (int) mysqli_insert_id($db_handle->conn);
		}
	}

	if ($subMenuId <= 0) {
		return;
	}

	// Clean up old allocations of this submenu to role 5 under settings menu
	$settingsMenuId = sidebar_resolve_system_menu_id($db_handle->conn);
	if ($settingsMenuId > 0) {
		mysqli_query($db_handle->conn, "DELETE FROM st_menu_allocation_master WHERE menu_id = {$settingsMenuId} AND role_id = 5 AND sub_menu_id = {$subMenuId}");
	}

	// Allocate to role 1 (Super Admin)
	$parentSql1 = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 1 AND menu_id = {$menuId} AND sub_menu_id IS NULL LIMIT 1";
	if (mysqli_num_rows(mysqli_query($db_handle->conn, $parentSql1)) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 1, {$menuId}, NULL)");
	}
	$allocSql1 = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 1 AND menu_id = {$menuId} AND sub_menu_id = {$subMenuId} LIMIT 1";
	if (mysqli_num_rows(mysqli_query($db_handle->conn, $allocSql1)) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 1, {$menuId}, {$subMenuId})");
	}

	// Allocate to role 2 (Admin)
	$parentSql2 = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 2 AND menu_id = {$menuId} AND sub_menu_id IS NULL LIMIT 1";
	if (mysqli_num_rows(mysqli_query($db_handle->conn, $parentSql2)) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 2, {$menuId}, NULL)");
	}
	$allocSql2 = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 2 AND menu_id = {$menuId} AND sub_menu_id = {$subMenuId} LIMIT 1";
	if (mysqli_num_rows(mysqli_query($db_handle->conn, $allocSql2)) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 2, {$menuId}, {$subMenuId})");
	}

	// Allocate to role 3 (Coordinator)
	$parentSql3 = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 3 AND menu_id = {$menuId} AND sub_menu_id IS NULL LIMIT 1";
	if (mysqli_num_rows(mysqli_query($db_handle->conn, $parentSql3)) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 3, {$menuId}, NULL)");
	}
	$allocSql3 = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 3 AND menu_id = {$menuId} AND sub_menu_id = {$subMenuId} LIMIT 1";
	if (mysqli_num_rows(mysqli_query($db_handle->conn, $allocSql3)) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 3, {$menuId}, {$subMenuId})");
	}

	// Allocate to role 5 (Student)
	$parentSql5 = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 5 AND menu_id = {$menuId} AND sub_menu_id IS NULL LIMIT 1";
	if (mysqli_num_rows(mysqli_query($db_handle->conn, $parentSql5)) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 5, {$menuId}, NULL)");
	}
	$allocSql5 = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 5 AND menu_id = {$menuId} AND sub_menu_id = {$subMenuId} LIMIT 1";
	if (mysqli_num_rows(mysqli_query($db_handle->conn, $allocSql5)) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 5, {$menuId}, {$subMenuId})");
	}

	// Also allocate student_admission.php (Enroll) submenu to role 5
	$admRes = mysqli_query($db_handle->conn, "SELECT sub_menu_id FROM st_sub_menu_master WHERE sub_menu_route = 'student_admission.php' LIMIT 1");
	if ($admRes && mysqli_num_rows($admRes) > 0) {
		$admRow = mysqli_fetch_assoc($admRes);
		$admSubId = intval($admRow['sub_menu_id']);
		$allocSqlAdm = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 5 AND menu_id = {$menuId} AND sub_menu_id = {$admSubId} LIMIT 1";
		if (mysqli_num_rows(mysqli_query($db_handle->conn, $allocSqlAdm)) === 0) {
			mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 5, {$menuId}, {$admSubId})");
		}
	}
}

if ((int) $usertype === 1) {
	sidebar_seed_super_admin_settings($db_handle);
	sidebar_seed_mentor_menu($db_handle);
	sidebar_seed_coordinator_allocation_menu($db_handle);
	sidebar_seed_student_monitor_menu($db_handle);
	sidebar_seed_student_nptel_menu($db_handle);
	sidebar_seed_mentor_subject_menu($db_handle);
}

if (in_array((int) $usertype, array(1, 2, 3), true)) {
	sidebar_seed_subject_management_menu($db_handle);
}

$menuSql = "SELECT m.menu_id, m.menu_name, $menuIconSelect
			FROM st_menu_master m
			WHERE (
				EXISTS (
					SELECT 1
					FROM st_menu_allocation_master mar
					WHERE mar.menu_id = m.menu_id
					  AND mar.role_id = ?
					  AND mar.sub_menu_id IS NULL
				)
				OR EXISTS (
					SELECT 1
					FROM st_menu_allocation_master maa
					WHERE maa.menu_id = m.menu_id
					  AND maa.role_id = ?
					  AND maa.sub_menu_id IS NOT NULL
				)
			)
			ORDER BY m.menu_id";
$menuStmt = mysqli_prepare($db_handle->conn, $menuSql);

if ($menuStmt) {
	mysqli_stmt_bind_param($menuStmt, 'ii', $usertype, $usertype);
	mysqli_stmt_execute($menuStmt);
	$menuResult = mysqli_stmt_get_result($menuStmt);

	while ($menuRow = mysqli_fetch_assoc($menuResult)) {
		$menuId = (int) $menuRow['menu_id'];
		$menuTree[$menuId] = array(
			'menu_name' => $menuRow['menu_name'],
			'menu_icon' => $menuRow['menu_icon'],
			'submenus' => array()
		);

		$subSql = "SELECT sm.sub_menu_id, sm.sub_menu_name, $subMenuRouteSelect, $subMenuIconSelect
				   FROM st_sub_menu_master sm
				   WHERE sm.menu_id = ?
				   AND (
					   EXISTS (
						   SELECT 1
						   FROM st_menu_allocation_master mar
						   WHERE mar.sub_menu_id = sm.sub_menu_id
							 AND mar.role_id = ?
					   )
				   )
				   ORDER BY sm.sort_order ASC, sm.sub_menu_id ASC";
		$subStmt = mysqli_prepare($db_handle->conn, $subSql);

		if ($subStmt) {
			mysqli_stmt_bind_param($subStmt, 'ii', $menuId, $usertype);
			mysqli_stmt_execute($subStmt);
			$subResult = mysqli_stmt_get_result($subStmt);

			while ($subRow = mysqli_fetch_assoc($subResult)) {
				$menuTree[$menuId]['submenus'][] = $subRow;
			}

			mysqli_stmt_close($subStmt);
		}
	}

	mysqli_stmt_close($menuStmt);
}
?>

<ul class="sidebar-menu" id="sidebar-dynamic-menu">
<?php
$sidebarHomeRoute = ((int) ($usertype ?? 0) === 5) ? 'student_dashboard.php' : 'index.php';
$sidebarHomeLabel = ((int) ($usertype ?? 0) === 5) ? 'DASHBOARD' : strtoupper((string) ($role_name ?? 'Dashboard'));
$sidebarHomeIcon = ((int) ($usertype ?? 0) === 5) ? 'fa fa-dashboard' : 'fa fa-user';
?>
<li class="active"><a href="<?php echo htmlspecialchars($sidebarHomeRoute); ?>"><i class="<?php echo htmlspecialchars($sidebarHomeIcon); ?>"></i><span><?php echo htmlspecialchars($sidebarHomeLabel); ?></span></a></li>

<?php foreach ($menuTree as $menuId => $menuData) {
	$menuName = trim((string) $menuData['menu_name']);
	$menuIcon = trim((string) $menuData['menu_icon']);
	if ($menuIcon === '') {
		$menuIcon = 'fa fa-folder';
	}
?>
<li class="treeview" data-menu-id="<?php echo $menuId; ?>" id="sidebar-menu-<?php echo $menuId; ?>">
<a href="#">
<i class="<?php echo htmlspecialchars($menuIcon); ?>" aria-hidden="true"></i> <span><?php echo strtoupper(htmlspecialchars($menuName)); ?></span>
<span class="pull-right-container">
<i class="fa fa-angle-right pull-right"></i>
</span>
</a>
<ul class="treeview-menu" id="sidebar-submenu-<?php echo $menuId; ?>">
<?php if (!empty($menuData['submenus'])) {
	foreach ($menuData['submenus'] as $subMenu) {
		$subId = intval($subMenu['sub_menu_id']);
		$subName = trim((string) $subMenu['sub_menu_name']);
		$subRoute = trim((string) ($subMenu['sub_menu_route'] ?? '#'));
		$subIcon = trim((string) ($subMenu['sub_menu_icon'] ?? 'fa fa-angle-double-right'));
		if ($subRoute === '') {
			$subRoute = '#';
		}
		if ((int) ($usertype ?? 0) === 5 && $subRoute === 'student_admission.php') {
			$subRoute = $studentAdmissionRoute;
		}
		if ($subIcon === '') {
			$subIcon = 'fa fa-angle-double-right';
		}
?>
<li data-sub-menu-id="<?php echo $subId; ?>" id="sidebar-submenu-item-<?php echo $subId; ?>"><a href="<?php echo htmlspecialchars($subRoute); ?>"><i class="<?php echo htmlspecialchars($subIcon); ?>"></i><?php echo strtoupper(htmlspecialchars($subName)); ?></a></li>
<?php }
} ?>
</ul>
</li>
<?php } ?>

</ul>

<?php if (empty($menuTree)) { ?>
<div class="text-muted" style="padding: 10px 15px;">No menu items are assigned to this role.</div>
<?php } ?>

</section>
