<?php
$menuIcons = array(
	'students' => 'fa fa-graduation-cap',
	'admin' => 'fa fa-user-secret',
	'coordinator' => 'fa fa-user',
	'mentor' => 'fa fa-user',
	'settings' => 'fa fa-cog'
);

$subMenuRoutes = array(
	'register students' => 'student_admission.php',
	'list of students' => 'student-info.php',
	'concise details' => 'student_concise_details.php',
	'left students' => '#',
	'previous students' => '#',
	'register admin' => 'admin_register.php',
	'admin info' => 'admin_info.php',
	'register coordinator' => 'coordinator_register.php',
	'coordinator info' => 'coordinator_info.php',
	'register mentor' => 'mentor_register.php',
	'mentor info' => 'mentor_info.php',
	'manage class' => 'class_crud_new.php',
	'settings' => 'class_crud_new.php#section-list'
);

$subMenuIcons = array(
	'register students' => 'fa fa-plus',
	'list of students' => 'fa fa-info-circle',
	'concise details' => 'fa fa-info-circle',
	'left students' => 'fa fa-minus-circle',
	'previous students' => 'fa fa-history',
	'register admin' => 'fa fa-plus',
	'admin info' => 'fa fa-info-circle',
	'register coordinator' => 'fa fa-plus',
	'coordinator info' => 'fa fa-info-circle',
	'register mentor' => 'fa fa-plus',
	'mentor info' => 'fa fa-info-circle',
	'manage class' => 'fa fa-cogs',
	'manage section' => 'fa fa-list-alt'
);

$menuTree = array();

$menuSql = "SELECT m.menu_id, m.menu_name
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
			'submenus' => array()
		);

		$subSql = "SELECT sm.sub_menu_id, sm.sub_menu_name
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
	$menuKey = strtolower($menuName);
	$menuIcon = isset($menuIcons[$menuKey]) ? $menuIcons[$menuKey] : 'fa fa-folder';
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
		$subKey = strtolower($subName);
		$subRoute = isset($subMenuRoutes[$subKey]) ? $subMenuRoutes[$subKey] : '#';
		$subIcon = isset($subMenuIcons[$subKey]) ? $subMenuIcons[$subKey] : 'fa fa-angle-double-right';
?>
<li data-sub-menu-id="<?php echo $subId; ?>" id="sidebar-submenu-item-<?php echo $subId; ?>"><a href="<?php echo htmlspecialchars($subRoute); ?>"><i class="<?php echo htmlspecialchars($subIcon); ?>"></i><?php echo strtoupper(htmlspecialchars($subName)); ?></a></li>
<?php }
} ?>
</ul>
</li>
<?php } ?>

</ul>
</section>