<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
include_once("../database/db_connect.php");
if (!isset($_SESSION['user_session'])) {
  header("location: ../index.php");
  exit();
}

$db = new DBController();

function resolve_system_menu_id($db)
{
  $menuSql = "SELECT menu_id, menu_name FROM st_menu_master WHERE LOWER(TRIM(menu_name)) IN ('settings', 'admin') ORDER BY CASE WHEN LOWER(TRIM(menu_name)) = 'settings' THEN 0 ELSE 1 END, menu_id ASC LIMIT 1";
  $menuRows = $db->runQuery($menuSql) ?? array();
  if (!empty($menuRows) && isset($menuRows[0]['menu_id'])) {
    return (int) $menuRows[0]['menu_id'];
  }

  $routeSql = "SELECT sm.menu_id
               FROM st_sub_menu_master sm
               WHERE sm.sub_menu_route IN ('allocation_master.php', 'profile.php', 'change_password.php')
               ORDER BY sm.menu_id ASC, sm.sort_order ASC, sm.sub_menu_id ASC
               LIMIT 1";
  $routeRows = $db->runQuery($routeSql) ?? array();
  if (!empty($routeRows) && isset($routeRows[0]['menu_id'])) {
    return (int) $routeRows[0]['menu_id'];
  }

  return 0;
}

function ensure_system_setting_submenus($db, $systemMenuId)
{
  $systemMenuId = (int) $systemMenuId;
  if ($systemMenuId <= 0) {
    return;
  }

  $defaults = array(
    array('Profile', 'fa fa-user', 'profile.php'),
    array('Update Password', 'fa fa-lock', 'change_password.php'),
    array('Manage Section', 'fa fa-list-alt', 'class_crud_new.php?tab=section-list'),
    array('Menu Master', 'fa fa-folder-open', 'class_crud_new.php?tab=menu-list'),
    array('Sub Menu Master', 'fa fa-sitemap', 'class_crud_new.php?tab=sub-menu-list'),
    array('Side Menu Allocation', 'fa fa-check-square-o', 'allocation_master.php')
  );

  foreach ($defaults as $item) {
    $name = mysqli_real_escape_string($db->conn, $item[0]);
    $icon = mysqli_real_escape_string($db->conn, $item[1]);
    $route = mysqli_real_escape_string($db->conn, $item[2]);

    $existsSql = "SELECT sub_menu_id FROM st_sub_menu_master WHERE menu_id = {$systemMenuId} AND (sub_menu_route = '$route' OR sub_menu_name = '$name') LIMIT 1";
    if ($db->numRows($existsSql) === 0) {
      $orderRow = $db->runQuery("SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM st_sub_menu_master WHERE menu_id = {$systemMenuId}") ?? array();
      $order = !empty($orderRow[0]['next_order']) ? (int) $orderRow[0]['next_order'] : 1;
      $insertSql = "INSERT INTO st_sub_menu_master (menu_id, sort_order, sub_menu_name, sub_menu_icon, sub_menu_route) VALUES ({$systemMenuId}, $order, '$name', '$icon', '$route')";
      $db->executeInsert($insertSql);
    }
  }

  $settingSubIds = array();
  $settingRows = $db->runQuery("SELECT sub_menu_id FROM st_sub_menu_master WHERE menu_id = {$systemMenuId} ORDER BY sort_order ASC, sub_menu_id ASC") ?? array();
  foreach ($settingRows as $settingRow) {
    $settingSubIds[] = (int) ($settingRow['sub_menu_id'] ?? 0);
  }

  if (!empty($settingSubIds)) {
    $hasParentSql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 1 AND menu_id = {$systemMenuId} AND sub_menu_id IS NULL LIMIT 1";
    if ($db->numRows($hasParentSql) === 0) {
      $db->executeInsert("INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 1, {$systemMenuId}, NULL)");
    }

    foreach ($settingSubIds as $subMenuId) {
      if ($subMenuId <= 0) {
        continue;
      }

      $allocExistsSql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = 1 AND menu_id = {$systemMenuId} AND sub_menu_id = {$subMenuId} LIMIT 1";
      if ($db->numRows($allocExistsSql) === 0) {
        $db->executeInsert("INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, 1, {$systemMenuId}, {$subMenuId})");
      }
    }
  }
}

$systemMenuId = resolve_system_menu_id($db);
ensure_system_setting_submenus($db, $systemMenuId);

if (isset($_GET['action']) && $_GET['action'] === 'load_role_allocated_items') {
  header('Content-Type: application/json');

  $roleId = (int) ($_GET['role_id'] ?? 0);
  $rows = array();

  if ($roleId > 0) {
    $sql = "SELECT ma.menu_allocation_id, ma.menu_id, ma.sub_menu_id,
                   COALESCE(m.menu_name, '') AS menu_name,
                   COALESCE(sm.sub_menu_name, '') AS sub_menu_name,
                   COALESCE(sm.sub_menu_route, '#') AS sub_menu_route
            FROM st_menu_allocation_master ma
            LEFT JOIN st_menu_master m ON m.menu_id = ma.menu_id
            LEFT JOIN st_sub_menu_master sm ON sm.sub_menu_id = ma.sub_menu_id
            WHERE ma.user_id = 0 AND ma.role_id = {$roleId}
            ORDER BY m.menu_name ASC, sm.sort_order ASC, sm.sub_menu_name ASC, ma.menu_allocation_id ASC";
    $rows = $db->runQuery($sql) ?? array();
  }

  echo json_encode(array('rows' => $rows));
  exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'load_role_allocation') {
  header('Content-Type: application/json');

  $roleId = (int) ($_GET['role_id'] ?? 0);
  $payload = array(
    'menus' => array(),
    'subs' => array()
  );

  if ($roleId > 0) {
    $allocRows = $db->runQuery("SELECT menu_id, sub_menu_id FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = {$roleId}") ?? array();
    foreach ($allocRows as $row) {
      if ($row['sub_menu_id'] === null) {
        $payload['menus'][] = (int) $row['menu_id'];
      } else {
        $payload['subs'][] = (int) $row['sub_menu_id'];
      }
    }
  }

  echo json_encode($payload);
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_allocation_item') {
  header('Content-Type: application/json');
  $allocationId = (int) ($_POST['menu_allocation_id'] ?? 0);

  if ($allocationId <= 0) {
    echo json_encode(array('success' => false, 'message' => 'Invalid allocation id.'));
    exit();
  }

  $ok = $db->executeUpdate("DELETE FROM st_menu_allocation_master WHERE menu_allocation_id = {$allocationId} AND user_id = 0");
  echo json_encode(array('success' => (bool) $ok));
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_role_allocation') {
  header('Content-Type: application/json');
  $roleId = (int) ($_POST['role_id'] ?? 0);

  if ($roleId <= 0) {
    echo json_encode(array('success' => false, 'message' => 'Invalid role id.'));
    exit();
  }

  $ok = $db->executeUpdate("DELETE FROM st_menu_allocation_master WHERE role_id = {$roleId} AND user_id = 0");
  echo json_encode(array('success' => (bool) $ok));
  exit();
}

include "header/header.php";
require_once '../database/db_connect.php'; //  db class file name
 

// FETCH ROLES FROM DATABASE
$roles_raw = $db->runQuery("SELECT role_id, role_name FROM st_role_master") ?? [];
 
$roles = [];
foreach ($roles_raw as $r) {
    $roles[$r['role_id']] = $r['role_name'];
}
 
$success_msg = '';
$error_msg   = '';

function allocation_has_menu_link($db, $roleId, $menuId, $subMenuId = null)
{
  $roleId = (int) $roleId;
  $menuId = (int) $menuId;

  if ($subMenuId === null) {
    $sql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = {$roleId} AND menu_id = {$menuId} AND sub_menu_id IS NULL LIMIT 1";
  } else {
    $subMenuId = (int) $subMenuId;
    $sql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = {$roleId} AND menu_id = {$menuId} AND sub_menu_id = {$subMenuId} LIMIT 1";
  }

  return $db->numRows($sql) > 0;
}
 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_id'])) {
 
    $role_id        = (int) $_POST['role_id'];
    $selected_menus = $_POST['menu_ids'] ?? [];
    $selected_subs  = $_POST['sub_menu_ids'] ?? [];
 
    // 1. Delete old allocations
    $db->executeUpdate("DELETE FROM st_menu_allocation_master WHERE role_id = $role_id AND user_id = 0");
 
    // 2. Insert submenus FIRST
    foreach ($selected_subs as $sub_menu_id) {
        $sub_menu_id = (int)$sub_menu_id;
      $parentMenuRow = $db->runQuery("SELECT menu_id FROM st_sub_menu_master WHERE sub_menu_id = $sub_menu_id LIMIT 1") ?? array();
      $parentMenuId = !empty($parentMenuRow[0]['menu_id']) ? (int) $parentMenuRow[0]['menu_id'] : 0;
 
        $db->executeUpdate("
            INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id)
            SELECT 0, $role_id, menu_id, sub_menu_id
            FROM st_sub_menu_master
            WHERE sub_menu_id = $sub_menu_id
        ");

      if ($parentMenuId > 0 && !allocation_has_menu_link($db, $role_id, $parentMenuId, null)) {
        $db->executeInsert("INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, $role_id, $parentMenuId, NULL)");
      }
    }
 
    // 3. Insert menus only if no submenu already inserted
    foreach ($selected_menus as $menu_id) {
        $menu_id = (int)$menu_id;
 
        $exists = $db->numRows("
            SELECT * FROM st_menu_allocation_master 
            WHERE role_id = $role_id 
            AND menu_id = $menu_id 
            AND user_id = 0
        ");
 
        if ($exists == 0) {
            $db->executeInsert("
                INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id)
                VALUES (0, $role_id, $menu_id, NULL)
            ");
        }
    }
 
    $success_msg = "Saved successfully!";
}
// ─────────────────────────────────────────────
// FETCH ALL MENUS
// ─────────────────────────────────────────────
$all_menus = $db->runQuery("SELECT * FROM st_menu_master ORDER BY menu_id") ?? [];
 
// ─────────────────────────────────────────────
// FETCH ALL SUB MENUS grouped by menu_id
// ─────────────────────────────────────────────
$all_subs  = [];
$subs_raw  = $db->runQuery("SELECT * FROM st_sub_menu_master ORDER BY menu_id, sub_menu_id") ?? [];
foreach ($subs_raw as $sub) {
    $all_subs[$sub['menu_id']][] = $sub;
}
 

?>
<style>
    :root {
      --alloc-primary: #1f4c8f;
      --alloc-accent: #19a1b8;
      --alloc-soft: #eef5ff;
      --alloc-border: #d9e4f3;
      --alloc-text: #1f2a3d;
    }

    .content-wrapper {
      background: radial-gradient(circle at top left, #eff5ff 0%, #f7fbff 52%, #eef8ff 100%);
      min-height: calc(100vh - 50px);
    }

    .alloc-page-head {
      background: linear-gradient(110deg, #1f4c8f 0%, #2367a8 52%, #19a1b8 100%);
      border-radius: 14px;
      color: #fff;
      padding: 20px;
      box-shadow: 0 18px 40px rgba(22, 62, 123, 0.26);
      margin-top: 10px;
    }

    .alloc-page-head h1 {
      margin: 0;
      font-size: 30px;
      font-weight: 700;
      letter-spacing: 0.2px;
    }

    .alloc-page-head .head-sub {
      margin-top: 8px;
      font-size: 14px;
      opacity: 0.93;
    }

    .alloc-page-head .head-tags {
      margin-top: 14px;
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .alloc-page-head .head-tag {
      display: inline-block;
      padding: 5px 10px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.16);
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 0.3px;
      border: 1px solid rgba(255, 255, 255, 0.25);
    }

    .alloc-panel {
      border: 1px solid var(--alloc-border);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 10px 28px rgba(19, 52, 106, 0.1);
      margin-top: 14px;
    }

    .alloc-panel > .box-header {
      background: #fff;
      border-bottom: 1px solid var(--alloc-border);
      padding: 14px 16px;
    }

    .alloc-panel > .box-header .box-title {
      color: var(--alloc-text);
      font-weight: 700;
      font-size: 16px;
    }

    .alloc-panel > .box-body {
      background: #fff;
      padding: 16px;
    }

    .alloc-panel > .box-footer {
      background: #fbfdff;
      border-top: 1px solid var(--alloc-border);
      padding: 14px 16px;
    }

    .alloc-role-select {
      height: 42px;
      border-radius: 10px;
      border: 1px solid #c8d8ee;
      box-shadow: none;
    }

    .alloc-toolbar {
      display: flex;
      gap: 12px;
      align-items: center;
      flex-wrap: wrap;
      margin-bottom: 14px;
      padding: 12px;
      border-radius: 10px;
      border: 1px solid var(--alloc-border);
      background: var(--alloc-soft);
    }

    .alloc-search-wrap {
      position: relative;
      min-width: 260px;
      flex: 1;
    }

    .alloc-search-wrap i {
      position: absolute;
      top: 12px;
      left: 11px;
      color: #6083ad;
      font-size: 13px;
    }

    .alloc-search-input {
      width: 100%;
      height: 38px;
      border: 1px solid #bdd0ea;
      border-radius: 8px;
      padding: 8px 10px 8px 33px;
      outline: none;
      font-size: 13px;
    }

    .alloc-search-input:focus {
      border-color: #4d88c9;
      box-shadow: 0 0 0 3px rgba(43, 118, 192, 0.13);
    }

    .alloc-badges {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .alloc-badge {
      display: inline-block;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      background: #fff;
      border: 1px solid #c7d8ef;
      color: #294a74;
    }

    .menu-tree {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .menu-tree .tree-item {
      border: 1px solid #d8e2f2;
      border-radius: 10px;
      margin-bottom: 10px;
      background: #fdfefe;
      transition: border-color .2s, box-shadow .2s, transform .12s;
    }

    .menu-tree .tree-item.active-menu {
      border-color: #5d8fc8;
      box-shadow: 0 10px 18px rgba(50, 105, 173, 0.12);
      background: #f8fbff;
    }

    .tree-header {
      padding: 11px 14px;
      display: flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
      user-select: none;
      border-radius: 10px 10px 0 0;
    }

    .tree-header:hover {
      background: rgba(35, 103, 168, 0.05);
    }

    .tree-header .menu-label {
      font-size: 14px;
      font-weight: 700;
      flex: 1;
      color: #223552;
      margin-bottom: 0;
      text-transform: uppercase;
      letter-spacing: 0.2px;
    }

    .tree-toggle {
      font-size: 12px;
      color: #6d88aa;
      transition: transform .2s;
    }

    .tree-toggle.open {
      transform: rotate(90deg);
    }

    .sub-menu-grid {
      display: grid;
      padding: 11px 13px 13px 46px;
      border-top: 1px solid #e2ebf7;
      background: #fff;
      grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
      gap: 8px;
    }

    .sub-menu-grid.open {
      display: grid;
    }

    .sub-item {
      border: 1px solid #dbe6f5;
      border-radius: 8px;
      padding: 8px 10px;
      background: #f8fbff;
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      transition: border-color .2s, background .2s;
    }

    .sub-item.active-sub {
      border-color: #3a9ccf;
      background: #e8f7ff;
    }

    .alloc-sub-label {
      margin: 0;
      font-weight: 600;
      cursor: pointer;
      color: #2a405f;
    }

    .tree-item.hidden-by-search {
      display: none;
    }

    .sub-item.hidden-by-search {
      display: none;
    }

    .alloc-save-btn {
      border-radius: 10px;
      font-size: 15px;
      font-weight: 700;
      letter-spacing: 0.3px;
      background: linear-gradient(100deg, var(--alloc-primary) 0%, #2367a8 55%, var(--alloc-accent) 100%);
      border: 0;
      box-shadow: 0 12px 24px rgba(21, 66, 122, 0.24);
      padding-top: 11px;
      padding-bottom: 11px;
    }

    .alloc-save-btn:hover,
    .alloc-save-btn:focus,
    .alloc-save-btn:active {
      background: linear-gradient(100deg, #193f78 0%, #1f5f9b 55%, #14889b 100%);
    }

    .alloc-info {
      font-size: 12px;
      color: #5b6e87;
      margin-top: 10px;
    }

    .alloc-info i {
      color: #2d6bab;
      margin-right: 4px;
    }

  .alloc-crud-table-wrap {
    border: 1px solid #cfe0f7;
    border-radius: 10px;
    overflow: hidden;
    background: #fff;
  }

  .alloc-crud-table {
    margin-bottom: 0;
  }

  .alloc-crud-table thead tr {
    background: #eef5ff;
  }

  .alloc-crud-table th,
  .alloc-crud-table td {
    border-color: #d8e5f7 !important;
    vertical-align: middle !important;
  }

  .alloc-empty-state {
    padding: 14px;
    text-align: center;
    color: #5d6f88;
    font-weight: 600;
  }
</style>

 
  <!-- ══ CONTENT WRAPPER ══ -->
  <div class="content-wrapper">
 
    <!-- Page Header -->
    <section class="content-header">
      <div class="alloc-page-head">
        <h1>Allocation Master</h1>
        <div class="head-sub">Assign menu and sub menu access role-wise from one clean panel.</div>
        <div class="head-tags">
          <span class="head-tag"><i class="fa fa-users"></i> Role Based</span>
          <span class="head-tag"><i class="fa fa-lock"></i> Access Control</span>
          <span class="head-tag"><i class="fa fa-sitemap"></i> Menu Visibility</span>
        </div>
      </div>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-home"></i> Home</a></li>
        <li>Super Admin</li>
        <li class="active">Allocation Master</li>
      </ol>
    </section>
 
    <!-- Main Content -->
    <section class="content">
 
      <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <i class="fa fa-check"></i> <?= $success_msg ?>
        </div>
      <?php endif; ?>
 
      <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <i class="fa fa-ban"></i> <?= $error_msg ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
 
        <div class="row">
          <div class="col-md-8 col-md-offset-2">
 
            <!-- ── Step 1: Role ── -->
            <div class="box box-primary alloc-panel">
              <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-users"></i> Step 01 — Select Role</h3>
              </div>
              <div class="box-body">
                <div class="form-group">
                  <label for="role_select">Role</label>
                  <select name="role_id" id="role_select" class="form-control alloc-role-select" required>
                    <option value="">— Choose a role —</option>
                    <?php foreach ($roles as $rid => $rname): ?>
                      <option value="<?= $rid ?>" <?= (isset($_POST['role_id']) && $_POST['role_id'] == $rid) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($rname) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
            </div>
 
            <!-- ── Step 2: Menus ── -->
            <div class="box box-primary alloc-panel" id="menu-container">
              <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-sitemap"></i> Step 02 — Assign Menus &amp; Sub Menus</h3>
              </div>
              <div class="box-body">
                <div class="alloc-toolbar">
                  <div class="alloc-search-wrap">
                    <i class="fa fa-search"></i>
                    <input type="text" id="menu_search" class="alloc-search-input" placeholder="Search menu or sub menu">
                  </div>
                  <div class="alloc-badges">
                    <span class="alloc-badge" id="selected_menu_count">Menus: 0</span>
                    <span class="alloc-badge" id="selected_sub_count">Sub Menus: 0</span>
                  </div>
                </div>
 
                <ul class="menu-tree">
                  <?php foreach ($all_menus as $menu): ?>
                    <?php $mid = $menu['menu_id']; ?>
                    <li class="tree-item" id="tree_item_<?= $mid ?>" data-menu-name="<?= htmlspecialchars(strtolower($menu['menu_name'])) ?>">
 
                      <div class="tree-header">
                        <!-- iCheck checkbox (hidden real input, iCheck styles the label) -->
                        <input type="checkbox"
                               class="main-check flat-blue"
                               name="menu_ids[]"
                               value="<?= $mid ?>"
                               id="menu_<?= $mid ?>"/>
 
                        <label class="menu-label" for="menu_<?= $mid ?>">
                          <?= htmlspecialchars($menu['menu_name']) ?>
                        </label>
 
                        <?php if (!empty($all_subs[$mid])): ?>
                          <i class="fa fa-angle-right tree-toggle open" id="arrow_<?= $mid ?>"></i>
                        <?php endif; ?>
                      </div>
 
                      <?php if (!empty($all_subs[$mid])): ?>
                        <div class="sub-menu-grid open" id="sub_grid_<?= $mid ?>">
                          <?php foreach ($all_subs[$mid] as $sub): ?>
                            <div class="sub-item" id="sub_item_<?= $sub['sub_menu_id'] ?>">
                              <input type="checkbox"
                                     class="sub-check flat-blue"
                                     name="sub_menu_ids[]"
                                     value="<?= $sub['sub_menu_id'] ?>"
                                     data-menu="<?= $mid ?>"
                                     id="sub_<?= $sub['sub_menu_id'] ?>"/>
                              <label for="sub_<?= $sub['sub_menu_id'] ?>" class="alloc-sub-label" data-sub-name="<?= htmlspecialchars(strtolower($sub['sub_menu_name'])) ?>">
                                <?= htmlspecialchars($sub['sub_menu_name']) ?>
                              </label>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      <?php endif; ?>
 
                    </li>
                  <?php endforeach; ?>
                </ul>
 
              </div><!-- /.box-body -->
 
              <div class="box-footer">
                <button type="submit" class="btn btn-primary btn-lg btn-block alloc-save-btn">
                  <i class="fa fa-save"></i> &nbsp;Save Menu Allocation
                </button>
                <p class="alloc-info">
                  <i class="fa fa-info-circle"></i>
                  Changes apply to all users with the selected role on next login.
                </p>
              </div>
            </div><!-- /.box -->
            <div class="box box-primary alloc-panel" id="allocation-crud-box">
              <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-database"></i> Step 03 — Allocated Items (CRUD)</h3>
                <div class="box-tools pull-right">
                  <button type="button" class="btn btn-danger btn-sm" id="clear_role_allocation_btn">
                    <i class="fa fa-trash"></i> Clear Role Allocation
                  </button>
                </div>
              </div>
              <div class="box-body">
                <div class="alloc-crud-table-wrap">
                  <table class="table table-bordered table-striped alloc-crud-table">
                    <thead>
                      <tr>
                        <th style="width:70px;">#</th>
                        <th>Menu</th>
                        <th>Sub Menu</th>
                        <th>Route</th>
                        <th style="width:120px;">Action</th>
                      </tr>
                    </thead>
                    <tbody id="allocated-items-tbody">
                      <tr><td colspan="5" class="alloc-empty-state">Select a role to view allocated items.</td></tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
 
          </div><!-- /.col -->
        </div><!-- /.row -->
 
      </form>
 
    </section><!-- /.content -->
  </div><!-- /.content-wrapper -->
 
<script src="plugins/iCheck/icheck.min.js"></script>
 
<script>
const roleSelect  = document.getElementById('role_select');
const roleAllocationUrl = 'allocation_master.php?action=load_role_allocation';
const roleAllocatedItemsUrl = 'allocation_master.php?action=load_role_allocated_items';
const allocatedItemsTbody = document.getElementById('allocated-items-tbody');
const clearRoleAllocationBtn = document.getElementById('clear_role_allocation_btn');
const menuSearchInput = document.getElementById('menu_search');
const selectedMenuCount = document.getElementById('selected_menu_count');
const selectedSubCount = document.getElementById('selected_sub_count');
 
$(document).ready(function () {
  initIcheck();
  bindToggleArrows();

  roleSelect.addEventListener('change', function () {
    if (this.value) {
      loadRoleAllocation(this.value);
      loadRoleAllocatedItems(this.value);
    } else {
      resetSelectionState();
      resetAllocatedItemsTable();
    }
  });

  if (roleSelect.value) {
    loadRoleAllocation(roleSelect.value);
    loadRoleAllocatedItems(roleSelect.value);
  }

  if (clearRoleAllocationBtn) {
    clearRoleAllocationBtn.addEventListener('click', function () {
      const roleId = roleSelect.value;
      if (!roleId) {
        alert('Please select a role first.');
        return;
      }
      if (!confirm('Clear all allocations for selected role?')) {
        return;
      }

      $.ajax({
        type: 'POST',
        url: 'allocation_master.php',
        dataType: 'json',
        data: { action: 'clear_role_allocation', role_id: roleId },
        success: function (resp) {
          if (resp && resp.success) {
            resetSelectionState();
            loadRoleAllocatedItems(roleId);
          } else {
            alert((resp && resp.message) ? resp.message : 'Unable to clear allocation.');
          }
        },
        error: function () {
          alert('Unable to clear allocation right now.');
        }
      });
    });
  }

  $(document).on('click', '.delete-allocation-item', function () {
    const allocationId = this.getAttribute('data-id');
    const roleId = roleSelect.value;

    if (!allocationId || !roleId) {
      return;
    }
    if (!confirm('Delete this allocated item?')) {
      return;
    }

    $.ajax({
      type: 'POST',
      url: 'allocation_master.php',
      dataType: 'json',
      data: { action: 'delete_allocation_item', menu_allocation_id: allocationId },
      success: function (resp) {
        if (resp && resp.success) {
          loadRoleAllocation(roleId);
          loadRoleAllocatedItems(roleId);
        } else {
          alert((resp && resp.message) ? resp.message : 'Unable to delete allocation item.');
        }
      },
      error: function () {
        alert('Unable to delete allocation item right now.');
      }
    });
  });

  if (menuSearchInput) {
    menuSearchInput.addEventListener('input', function () {
      applySearchFilter(this.value || '');
    });
  }

  updateAllocationSummary();
});

function initIcheck() {
  $('input.main-check').off('change.alloc').on('change.alloc', function () {
    const mid = this.value;
    if (this.checked) {
      openSubGrid(mid);
      updateTreeItem(mid, true);
    } else {
      updateTreeItem(mid, false);
      $('input.sub-check[data-menu="' + mid + '"]').each(function () {
        this.checked = false;
        updateSubItem(this.value, false);
      });
    }
    updateAllocationSummary();
  });

  $('input.sub-check').off('change.alloc').on('change.alloc', function () {
    const sid  = this.value;
    const pmid = this.dataset.menu;
    if (this.checked) {
      const parent = document.getElementById('menu_' + pmid);
      if (parent) parent.checked = true;
      openSubGrid(pmid);
      updateTreeItem(pmid, true);
      updateSubItem(sid, true);
    } else {
      updateSubItem(sid, false);
    }
    updateAllocationSummary();
  });
}

function bindToggleArrows() {
  document.querySelectorAll('.tree-toggle').forEach(function (arrow) {
    arrow.addEventListener('click', function (e) {
      e.stopPropagation();
      const mid = this.id.replace('arrow_', '');
      const grid = document.getElementById('sub_grid_' + mid);
      if (!grid) return;
      grid.classList.contains('open') ? closeSubGrid(mid) : openSubGrid(mid);
    });
  });
}

function loadRoleAllocation(roleId) {
  resetSelectionState();

  $.ajax({
    type: 'GET',
    url: roleAllocationUrl,
    dataType: 'json',
    data: { role_id: roleId },
    success: function (data) {
      const menuIds = Array.isArray(data && data.menus) ? data.menus : [];
      const subIds  = Array.isArray(data && data.subs)  ? data.subs  : [];

      menuIds.forEach(function (mid) {
        const el = document.getElementById('menu_' + mid);
        if (el) {
          el.checked = true;
          openSubGrid(mid);
          updateTreeItem(mid, true);
        }
      });

      subIds.forEach(function (sid) {
        const el = document.getElementById('sub_' + sid);
        if (el) {
          el.checked = true;
          const pmid = el.dataset.menu;
          if (pmid) {
            const parent = document.getElementById('menu_' + pmid);
            if (parent) {
              parent.checked = true;
              openSubGrid(pmid);
              updateTreeItem(pmid, true);
            }
          }
          updateSubItem(sid, true);
        }
      });
    },
    error: function () {
      console.log('Unable to load role allocation from backend.');
    }
  });
}

function loadRoleAllocatedItems(roleId) {
  if (!allocatedItemsTbody) {
    return;
  }

  allocatedItemsTbody.innerHTML = '<tr><td colspan="5" class="alloc-empty-state">Loading allocation items...</td></tr>';

  $.ajax({
    type: 'GET',
    url: roleAllocatedItemsUrl,
    dataType: 'json',
    data: { role_id: roleId },
    success: function (resp) {
      const rows = Array.isArray(resp && resp.rows) ? resp.rows : [];

      if (rows.length === 0) {
        allocatedItemsTbody.innerHTML = '<tr><td colspan="5" class="alloc-empty-state">No allocated items found for this role.</td></tr>';
        return;
      }

      const html = rows.map(function (row, index) {
        const isSub = row.sub_menu_id !== null;
        const subName = isSub ? escapeHtml(row.sub_menu_name || '') : '<span class="label label-default">Menu Level</span>';
        const route = isSub ? escapeHtml(row.sub_menu_route || '#') : '-';

        return '' +
          '<tr>' +
            '<td>' + (index + 1) + '</td>' +
            '<td>' + escapeHtml(row.menu_name || '') + '</td>' +
            '<td>' + subName + '</td>' +
            '<td>' + route + '</td>' +
            '<td><button type="button" class="btn btn-danger btn-xs delete-allocation-item" data-id="' + row.menu_allocation_id + '"><i class="fa fa-trash"></i> Delete</button></td>' +
          '</tr>';
      }).join('');

      allocatedItemsTbody.innerHTML = html;
    },
    error: function () {
      allocatedItemsTbody.innerHTML = '<tr><td colspan="5" class="alloc-empty-state">Unable to load allocated items.</td></tr>';
    }
  });
}

function resetSelectionState() {
  $('input.main-check, input.sub-check').prop('checked', false);
  document.querySelectorAll('.tree-item').forEach(function (t) { t.classList.remove('active-menu'); });
  document.querySelectorAll('.sub-item').forEach(function (s) { s.classList.remove('active-sub'); });
  updateAllocationSummary();
}

function resetAllocatedItemsTable() {
  if (!allocatedItemsTbody) {
    return;
  }
  allocatedItemsTbody.innerHTML = '<tr><td colspan="5" class="alloc-empty-state">Select a role to view allocated items.</td></tr>';
}

function updateAllocationSummary() {
  if (!selectedMenuCount || !selectedSubCount) {
    return;
  }
  const menuCount = document.querySelectorAll('input.main-check:checked').length;
  const subCount = document.querySelectorAll('input.sub-check:checked').length;
  selectedMenuCount.textContent = 'Menus: ' + menuCount;
  selectedSubCount.textContent = 'Sub Menus: ' + subCount;
}

function applySearchFilter(rawText) {
  const term = String(rawText).toLowerCase().trim();
  const trees = document.querySelectorAll('.menu-tree .tree-item');

  trees.forEach(function (tree) {
    const menuLabel = tree.getAttribute('data-menu-name') || '';
    const subItems = tree.querySelectorAll('.sub-item');
    let hasVisibleSub = false;

    subItems.forEach(function (subItem) {
      const labelEl = subItem.querySelector('label');
      const subName = (labelEl && labelEl.getAttribute('data-sub-name')) ? labelEl.getAttribute('data-sub-name') : '';
      const match = term === '' || subName.indexOf(term) !== -1 || menuLabel.indexOf(term) !== -1;
      subItem.classList.toggle('hidden-by-search', !match);
      if (match) {
        hasVisibleSub = true;
      }
    });

    const menuMatch = term === '' || menuLabel.indexOf(term) !== -1;
    const showTree = menuMatch || hasVisibleSub;
    tree.classList.toggle('hidden-by-search', !showTree);

    if (showTree && term !== '' && hasVisibleSub) {
      const id = tree.id.replace('tree_item_', '');
      openSubGrid(id);
    }
  });
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function openSubGrid(mid) {
  const grid  = document.getElementById('sub_grid_' + mid);
  const arrow = document.getElementById('arrow_' + mid);
  if (grid)  grid.classList.add('open');
  if (arrow) arrow.classList.add('open');
}

function closeSubGrid(mid) {
  const grid  = document.getElementById('sub_grid_' + mid);
  const arrow = document.getElementById('arrow_' + mid);
  if (grid)  grid.classList.remove('open');
  if (arrow) arrow.classList.remove('open');
}

function updateTreeItem(mid, active) {
  const el = document.getElementById('tree_item_' + mid);
  if (el) active ? el.classList.add('active-menu') : el.classList.remove('active-menu');
}

function updateSubItem(sid, active) {
  const el = document.getElementById('sub_item_' + sid);
  if (el) active ? el.classList.add('active-sub') : el.classList.remove('active-sub');
}
</script>
 

<?php include "header/footer.php" ?>
