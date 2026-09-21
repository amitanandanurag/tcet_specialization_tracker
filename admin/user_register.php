<?php include "header/header.php"; ?>
<?php
$roleKey = strtolower(trim($_GET['role'] ?? 'admin'));
$roleMap = array(
  'admin' => array('id' => 2, 'label' => 'Admin'),
  'coordinator' => array('id' => 3, 'label' => 'Coordinator / HOD'),
  'mentor' => array('id' => 4, 'label' => 'Mentor')
);
if (!isset($roleMap[$roleKey])) {
  $roleKey = 'admin';
}
$roleId = $roleMap[$roleKey]['id'];
$roleLabel = $roleMap[$roleKey]['label'];
?>
<div class="content-wrapper">
  <section class="content-header">
    <h1><i class="fa fa-user-plus"></i> Register <?php echo htmlspecialchars($roleLabel); ?></h1>
    <ol class="breadcrumb">
      <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
      <li><a href="user-info.php?role=<?php echo urlencode($roleKey); ?>"><?php echo htmlspecialchars($roleLabel); ?> Info</a></li>
      <li class="active">Register</li>
    </ol>
  </section>

  <section class="content">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <div class="erp-card">
          <div class="erp-card-header">
            <h3 class="erp-card-title"><i class="fa fa-user-plus"></i> New <?php echo htmlspecialchars($roleLabel); ?></h3>
          </div>
          <form method="post" action="user_process.php" autocomplete="off">
            <div class="erp-card-body">
              <input type="hidden" name="role_id" value="<?php echo intval($roleId); ?>">
              <input type="hidden" name="role_key" value="<?php echo htmlspecialchars($roleKey); ?>">

              <div class="form-group">
                <label>Name <span style="color:var(--erp-danger);">*</span></label>
                <input type="text" name="user_name" class="form-control" required>
              </div>

              <div class="form-group">
                <label>Email <span style="color:var(--erp-danger);">*</span></label>
                <input type="email" name="email_id" class="form-control" required>
              </div>

              <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" class="form-control" maxlength="15">
              </div>

              <div class="form-group">
                <label>Department <span style="color:var(--erp-danger);">*</span></label>
                <select name="department_id" class="form-control" required>
                  <option value="">Select Department</option>
                  <?php
                  $deptSql = "SELECT department_id, department_name FROM st_department_master ORDER BY department_name ASC";
                  $deptResult = $db_handle->query($deptSql);
                  while ($dept = $deptResult->fetch_assoc()) {
                  ?>
                    <option value="<?php echo intval($dept['department_id']); ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                  <?php } ?>
                </select>
              </div>
            </div>
            <div class="erp-card-body" style="border-top:1px solid var(--erp-border); display:flex; gap:10px;">
              <button type="submit" class="btn-erp-primary"><i class="fa fa-save"></i> Save</button>
              <a href="user-info.php?role=<?php echo urlencode($roleKey); ?>" class="btn-erp-secondary">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>
<?php include "header/footer.php"; ?>
