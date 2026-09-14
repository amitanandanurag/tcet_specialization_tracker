<?php include "header/header.php"; ?>
<div class="content-wrapper">
  <section class="content-header">
    <div class="erp-page-header">
      <div class="erp-page-title-wrap">
        <h1 class="erp-page-title"><i class="fa fa-user-plus"></i> Register <?php echo htmlspecialchars($roleLabel); ?></h1>
        <div class="erp-breadcrumb">
          <a href="index.php"><i class="fa fa-dashboard"></i> Dashboard</a>
          <span class="sep">&rsaquo;</span>
          <a href="<?php echo htmlspecialchars($infoFile); ?>"><?php echo htmlspecialchars($roleLabel); ?> Directory</a>
          <span class="sep">&rsaquo;</span>
          <span class="active-item">Registration</span>
        </div>
      </div>
    </div>
  </section>

  <section class="content" style="padding-top: 0;">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <div class="erp-card">
          <div class="erp-card-header">
            <h3 class="erp-card-title">New <?php echo htmlspecialchars($roleLabel); ?> Account Details</h3>
          </div>
          <form method="post" action="<?php echo htmlspecialchars($processFile); ?>" autocomplete="off">
            <div style="padding: 20px;">
              <input type="hidden" name="role_id" value="<?php echo intval($roleId); ?>">

              <div class="form-group">
                <label>Full Name <span style="color:red;">*</span></label>
                <input type="text" name="user_name" class="form-control" placeholder="Enter full name" required>
              </div>

              <div class="form-group">
                <label>Institute Email Address <span style="color:red;">*</span></label>
                <input type="email" name="email_id" class="form-control" placeholder="name@tcetmumbai.in" required>
              </div>

              <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" class="form-control" placeholder="10 digit phone number" maxlength="15">
              </div>

              <div class="form-group">
                <label>Department <span style="color:red;">*</span></label>
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

              <?php if (intval($roleId) === 4) { ?>
                <div class="form-group">
                  <label>Specialization Subject <span style="color:red;">*</span></label>
                  <select name="subject_id" class="form-control" required>
                    <option value="">Select Specialization Subject</option>
                    <?php
                    $subSql = "SELECT subject_id, subject_name FROM st_specialization_subject_master ORDER BY subject_name ASC";
                    $subResult = $db_handle->query($subSql);
                    while ($sub = $subResult->fetch_assoc()) {
                    ?>
                      <option value="<?php echo intval($sub['subject_id']); ?>"><?php echo htmlspecialchars($sub['subject_name']); ?></option>
                    <?php } ?>
                  </select>
                </div>
              <?php } ?>
            </div>
            <div style="padding: 12px 20px; background-color: #f8fafc; border-top: 1px solid var(--erp-border); display: flex; align-items: center; gap: 8px;">
              <button type="submit" class="btn-erp-primary"><i class="fa fa-save"></i> Save <?php echo htmlspecialchars($roleLabel); ?></button>
              <button type="reset" class="btn-erp-secondary">Reset</button>
              <a href="<?php echo htmlspecialchars($infoFile); ?>" class="btn-erp-secondary" style="margin-left: auto;">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>
<?php include "header/footer.php"; ?>
