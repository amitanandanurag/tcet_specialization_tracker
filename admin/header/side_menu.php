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

$menuSql = "SELECT m.menu_id, m.menu_name, $menuIconSelect
			FROM st_menu_master m
			WHERE EXISTS (
				SELECT 1
				FROM st_menu_allocation_master mar
				WHERE mar.menu_id = m.menu_id
				  AND mar.role_id = ?
				  AND mar.sub_menu_id IS NULL
			)
			OR NOT EXISTS (
				SELECT 1
				FROM st_menu_allocation_master maa
				WHERE maa.menu_id = m.menu_id
				  AND maa.sub_menu_id IS NULL
			)
			ORDER BY m.menu_id";
$menuStmt = mysqli_prepare($db_handle->conn, $menuSql);

if ($menuStmt) {
	mysqli_stmt_bind_param($menuStmt, 'i', $usertype);
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
					   OR NOT EXISTS (
						   SELECT 1
						   FROM st_menu_allocation_master maa
						   WHERE maa.sub_menu_id = sm.sub_menu_id
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
</section>