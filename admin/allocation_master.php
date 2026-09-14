<?php
include "header/header.php";
include_once("../database/db_connect.php");
if (!isset($_SESSION['user_session'])) {
    header("location: ../index.php");
    exit();
}
require_once '../database/db_connect.php'; //  db class file name
 
$db = new DBController();
 

// FETCH ROLES FROM DATABASE
$roles_raw = $db->runQuery("SELECT role_id, role_name FROM st_role_master") ?? [];
 
$roles = [];
foreach ($roles_raw as $r) {
    $roles[$r['role_id']] = $r['role_name'];
}
 
$success_msg = '';
$error_msg   = '';
 

// HANDLE FORM SUBMIT

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role_id'])) {
 
    $role_id        = (int) $_POST['role_id'];
    $selected_menus = $_POST['menu_ids'] ?? [];
    $selected_subs  = $_POST['sub_menu_ids'] ?? [];
 
    // 1. Delete old allocations
    $db->executeUpdate("DELETE FROM st_menu_allocation_master WHERE role_id = $role_id AND user_id = 0");
 
    // 2. Insert submenus FIRST
    foreach ($selected_subs as $sub_menu_id) {
        $sub_menu_id = (int)$sub_menu_id;
 
        $db->executeUpdate("
            INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id)
            SELECT 0, $role_id, menu_id, sub_menu_id
            FROM st_sub_menu_master
            WHERE sub_menu_id = $sub_menu_id
        ");
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
 

// FETCH EXISTING ALLOCATIONS for JS pre-select

$alloc_raw   = $db->runQuery("SELECT role_id, menu_id, sub_menu_id FROM st_menu_allocation_master WHERE user_id = 0") ?? [];
$allocations = [];
foreach ($alloc_raw as $row) {
    $rid = $row['role_id'];
    if ($row['sub_menu_id'] === null) {
        $allocations[$rid]['menus'][] = $row['menu_id'];
    } else {
        $allocations[$rid]['subs'][]  = $row['sub_menu_id'];
    }
}
$allocations_json = json_encode($allocations);
?>
<style>
  /* ── Menu tree structure ── */
  .menu-tree { list-style: none; padding: 0; margin: 0; }
  .menu-tree .tree-item {
    border: 1px solid var(--erp-border, #e2e8f0);
    border-radius: var(--erp-radius-sm, 4px);
    margin-bottom: 8px;
    background: #ffffff;
    transition: border-color .15s;
  }
  .menu-tree .tree-item.active-menu { border-color: var(--erp-primary, #423cbc); background: var(--erp-primary-light, #f5f3ff); }
  .tree-header {
    padding: 10px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    user-select: none;
  }
  .tree-header:hover { background: #f8fafc; border-radius: 4px 4px 0 0; }
  .tree-header .menu-label { font-size: 13px; font-weight: 700; flex: 1; color: var(--erp-text-main, #0f172a); margin: 0; }
  .tree-toggle { font-size: 12px; color: #94a3b8; transition: transform .2s; }
  .tree-toggle.open { transform: rotate(90deg); }

  /* ── Sub-menu grid ── */
  .sub-menu-grid {
    display: none;
    padding: 10px 14px 14px 46px;
    border-top: 1px solid var(--erp-border, #e2e8f0);
    background: #f8fafc;
    grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
    gap: 8px;
  }
  .sub-menu-grid.open { display: grid; }
  .sub-item {
    border: 1px solid var(--erp-border, #e2e8f0);
    border-radius: var(--erp-radius-sm, 4px);
    padding: 8px 12px;
    background: #ffffff;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    transition: border-color .15s, background .15s;
  }
  .sub-item.active-sub { border-color: var(--erp-primary, #423cbc); background: var(--erp-primary-light, #f5f3ff); }

  /* ── Info note ── */
  .alloc-info { font-size: 12px; color: var(--erp-text-secondary, #64748b); margin-top: 10px; }
  .alloc-info i { color: var(--erp-primary, #423cbc); margin-right: 4px; }
</style>

<div class="content-wrapper">
  <!-- Page Header -->
  <section class="content-header">
    <h1><i class="fa fa-sitemap"></i> Allocation Master <small style="font-size:12px; color:#64748b;">Assign menus to roles</small></h1>
    <ol class="breadcrumb">
      <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
      <li>Super Admin</li>
      <li class="active">Allocation Master</li>
    </ol>
  </section>

  <!-- Main Content -->
  <section class="content">

    <?php if ($success_msg): ?>
      <div class="alert alert-success alert-dismissible" style="border-radius:4px;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fa fa-check"></i> <?= htmlspecialchars($success_msg) ?>
      </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
      <div class="alert alert-danger alert-dismissible" style="border-radius:4px;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fa fa-ban"></i> <?= htmlspecialchars($error_msg) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">

      <div class="row">
        <div class="col-md-8 col-md-offset-2">

          <!-- ── Step 1: Role ── -->
          <div class="erp-card" style="margin-bottom:20px;">
            <div class="erp-card-header">
              <h3 class="erp-card-title"><i class="fa fa-users"></i> Step 01 — Select Role</h3>
            </div>
            <div class="erp-card-body">
              <div class="form-group" style="margin:0;">
                <label for="role_select" style="font-weight:600; font-size:12px; color:var(--erp-text-secondary);">Role</label>
                <select name="role_id" id="role_select" class="form-control" required>
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
          <div class="erp-card" id="menu-container">
            <div class="erp-card-header">
              <h3 class="erp-card-title"><i class="fa fa-sitemap"></i> Step 02 — Assign Menus &amp; Sub Menus</h3>
            </div>
            <div class="erp-card-body">

              <ul class="menu-tree">
                <?php foreach ($all_menus as $menu): ?>
                  <?php $mid = $menu['menu_id']; ?>
                  <li class="tree-item" id="tree_item_<?= $mid ?>">

                    <div class="tree-header">
                      <input type="checkbox"
                             class="main-check flat-blue"
                             name="menu_ids[]"
                             value="<?= $mid ?>"
                             id="menu_<?= $mid ?>"/>

                      <label class="menu-label" for="menu_<?= $mid ?>">
                        <?= htmlspecialchars($menu['menu_name']) ?>
                      </label>

                      <?php if (!empty($all_subs[$mid])): ?>
                        <i class="fa fa-angle-right tree-toggle" id="arrow_<?= $mid ?>"></i>
                      <?php endif; ?>
                    </div>

                    <?php if (!empty($all_subs[$mid])): ?>
                      <div class="sub-menu-grid" id="sub_grid_<?= $mid ?>">
                        <?php foreach ($all_subs[$mid] as $sub): ?>
                          <div class="sub-item" id="sub_item_<?= $sub['sub_menu_id'] ?>">
                            <input type="checkbox"
                                   class="sub-check flat-blue"
                                   name="sub_menu_ids[]"
                                   value="<?= $sub['sub_menu_id'] ?>"
                                   data-menu="<?= $mid ?>"
                                   id="sub_<?= $sub['sub_menu_id'] ?>"/>
                            <label for="sub_<?= $sub['sub_menu_id'] ?>" style="margin:0;font-weight:400;cursor:pointer;">
                              <?= htmlspecialchars($sub['sub_menu_name']) ?>
                            </label>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>

                  </li>
                <?php endforeach; ?>
              </ul>

            </div>

            <div class="erp-card-body" style="border-top: 1px solid var(--erp-border); background:#f8fafc;">
              <button type="submit" class="btn-erp-primary" style="width:100%; justify-content:center; padding:10px 16px; font-size:14px;">
                <i class="fa fa-save"></i> Save Menu Allocation
              </button>
              <p class="alloc-info" style="margin-bottom:0; text-align:center;">
                <i class="fa fa-info-circle"></i>
                Changes apply to all users with the selected role on next login.
              </p>
            </div>
          </div>

        </div><!-- /.col -->
      </div><!-- /.row -->

    </form>

  </section>
</div>
 
<!-- ══ SCRIPTS ══ -->
<!--<script src="plugins/jQuery/jquery-2.2.3.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script src="dist/js/app.min.js"></script>-->
<!-- iCheck -->
<script src="plugins/iCheck/icheck.min.js"></script>
 
<script>
const allocations = <?= $allocations_json ?>;
const roleSelect  = document.getElementById('role_select');
 
// ── iCheck init ──────────────────────────────
$(document).ready(function () {
  initIcheck();
  bindToggleArrows();
 
  roleSelect.addEventListener('change', function () {
    if (this.value) preSelectForRole(this.value);
  });
 
  <?php if (!empty($_POST['role_id'])): ?>
    roleSelect.dispatchEvent(new Event('change'));
  <?php endif; ?>
});
 
function initIcheck() {
  // Main menu checkboxes
  $('input.main-check').iCheck({
    checkboxClass: 'icheckbox_flat-blue'
  }).on('ifChanged', function () {
    const mid = this.value;
    if (this.checked) {
      openSubGrid(mid);
      updateTreeItem(mid, true);
    } else {
      closeSubGrid(mid);
      updateTreeItem(mid, false);
      // Uncheck all subs
      $('input.sub-check[data-menu="' + mid + '"]').iCheck('uncheck');
      $('input.sub-check[data-menu="' + mid + '"]').each(function () {
        updateSubItem(this.value, false);
      });
    }
  });
 
  // Sub-menu checkboxes
  $('input.sub-check').iCheck({
    checkboxClass: 'icheckbox_flat-blue'
  }).on('ifChanged', function () {
    const sid  = this.value;
    const pmid = this.dataset.menu;
    if (this.checked) {
      // Auto-check parent
      $('#menu_' + pmid).iCheck('check');
      openSubGrid(pmid);
      updateTreeItem(pmid, true);
      updateSubItem(sid, true);
    } else {
      updateSubItem(sid, false);
    }
  });
}
 
// ── Toggle arrows (click to open/close sub-grid) ──
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
 
// ── Pre-select for role ──────────────────────
function preSelectForRole(roleId) {
  // Reset all
  $('input.main-check').iCheck('uncheck');
  $('input.sub-check').iCheck('uncheck');
  document.querySelectorAll('.sub-menu-grid').forEach(function (g) { g.classList.remove('open'); });
  document.querySelectorAll('.tree-toggle').forEach(function (a) { a.classList.remove('open'); });
  document.querySelectorAll('.tree-item').forEach(function (t) { t.classList.remove('active-menu'); });
  document.querySelectorAll('.sub-item').forEach(function (s) { s.classList.remove('active-sub'); });
 
  const data = allocations[String(roleId)];
  if (!data) return;
 
  (data.menus || []).forEach(function (mid) {
    const el = document.getElementById('menu_' + mid);
    if (el) {
      $(el).iCheck('check');
      openSubGrid(mid);
      updateTreeItem(mid, true);
    }
  });
 
  (data.subs || []).forEach(function (sid) {
    const el = document.getElementById('sub_' + sid);
    if (el) {
      $(el).iCheck('check');
      const pmid = el.dataset.menu;
      openSubGrid(pmid);
      const pm = document.getElementById('menu_' + pmid);
      if (pm) { $(pm).iCheck('check'); updateTreeItem(pmid, true); }
      updateSubItem(sid, true);
    }
  });
}
 
// ── Helpers ──────────────────────────────────
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
 
</body>
</html>
 <?php include "header/footer.php" ?>
