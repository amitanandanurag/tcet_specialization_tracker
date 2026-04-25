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
		array('Offline Marks Entry', 'fa fa-pencil-square-o', 'offline_marks_entry.php'),
		array('Manage Section', 'fa fa-list-alt', 'class_crud_new.php?tab=section-list'),
		array('Menu Master', 'fa fa-folder-open', 'class_crud_new.php?tab=menu-list'),
		array('Sub Menu Master', 'fa fa-sitemap', 'class_crud_new.php?tab=sub-menu-list'),
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
}

function sidebar_seed_mentor_allocation_menu($db_handle)
{
	$mentorMenuId = 0;
	$mentorMenuResult = mysqli_query($db_handle->conn, "SELECT menu_id FROM st_menu_master WHERE LOWER(TRIM(menu_name)) = 'mentor' ORDER BY menu_id ASC LIMIT 1");
	if ($mentorMenuResult && mysqli_num_rows($mentorMenuResult) > 0) {
		$mentorMenuRow = mysqli_fetch_assoc($mentorMenuResult);
		$mentorMenuId = (int) ($mentorMenuRow['menu_id'] ?? 0);
	}

	if ($mentorMenuId <= 0) {
		$mentorMenuId = sidebar_resolve_system_menu_id($db_handle->conn);
	}

	if ($mentorMenuId <= 0) {
		return;
	}

	$name = mysqli_real_escape_string($db_handle->conn, 'Mentor Allocation');
	$icon = mysqli_real_escape_string($db_handle->conn, 'fa fa-exchange');
	$route = mysqli_real_escape_string($db_handle->conn, 'mentor_allocation.php');

	$subSql = "SELECT sub_menu_id FROM st_sub_menu_master WHERE menu_id = {$mentorMenuId} AND (sub_menu_route = '$route' OR sub_menu_name = '$name') LIMIT 1";
	$subResult = mysqli_query($db_handle->conn, $subSql);
	$subMenuId = 0;

	if ($subResult && mysqli_num_rows($subResult) > 0) {
		$subRow = mysqli_fetch_assoc($subResult);
		$subMenuId = (int) ($subRow['sub_menu_id'] ?? 0);
	} else {
		$orderSql = "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM st_sub_menu_master WHERE menu_id = {$mentorMenuId}";
		$orderResult = mysqli_query($db_handle->conn, $orderSql);
		$nextOrder = 1;
		if ($orderResult && mysqli_num_rows($orderResult) > 0) {
			$orderRow = mysqli_fetch_assoc($orderResult);
			$nextOrder = (int) ($orderRow['next_order'] ?? 1);
		}

		$insertSql = "INSERT INTO st_sub_menu_master (menu_id, sort_order, sub_menu_name, sub_menu_icon, sub_menu_route) VALUES ({$mentorMenuId}, {$nextOrder}, '$name', '$icon', '$route')";
		if (mysqli_query($db_handle->conn, $insertSql)) {
			$subMenuId = (int) mysqli_insert_id($db_handle->conn);
		}
	}

	if ($subMenuId <= 0) {
		return;
	}

	$parentSql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 1 AND menu_id = {$mentorMenuId} AND sub_menu_id IS NULL LIMIT 1";
	$parentResult = mysqli_query($db_handle->conn, $parentSql);
	if (!$parentResult || mysqli_num_rows($parentResult) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 1, {$mentorMenuId}, NULL)");
	}

	$allocSql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 1 AND menu_id = {$mentorMenuId} AND sub_menu_id = {$subMenuId} LIMIT 1";
	$allocResult = mysqli_query($db_handle->conn, $allocSql);
	if (!$allocResult || mysqli_num_rows($allocResult) === 0) {
		mysqli_query($db_handle->conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 1, {$mentorMenuId}, {$subMenuId})");
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

if ((int) $usertype === 1) {
	sidebar_seed_super_admin_settings($db_handle);
	sidebar_seed_mentor_allocation_menu($db_handle);
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
<li class="active"><a href="index.php"><i class="fa fa-user"></i><span><?php echo htmlspecialchars($role_name); ?></span></a></li>

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
