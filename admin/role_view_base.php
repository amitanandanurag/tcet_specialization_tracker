<?php
session_start();
require "../database/db_connect.php";
$db_handle = new DBController();

$userId = intval($_POST['id'] ?? 0);
if ($userId <= 0) {
  echo "<div class='alert alert-danger'>Invalid user id.</div>";
  exit;
}

$sql = "SELECT u.user_name, u.email_id, u.phone_number, d.department_name, r.role_name FROM st_user_master u LEFT JOIN st_department_master d ON d.department_id = u.department_id LEFT JOIN st_role_master r ON r.role_id = u.role_id WHERE u.user_id = $userId AND u.role_id = " . intval($roleId) . " LIMIT 1";
$result = $db_handle->query($sql);
$row = $result ? $result->fetch_assoc() : null;

if (!$row) {
  echo "<div class='alert alert-danger'>Record not found.</div>";
  exit;
}
?>
<div class="erp-detail-grid">
  <div class="erp-detail-item">
    <div class="erp-detail-label">Full Name</div>
    <div class="erp-detail-value"><?php echo htmlspecialchars($row['user_name'] ?? ''); ?></div>
  </div>
  <div class="erp-detail-item">
    <div class="erp-detail-label">Institute Email</div>
    <div class="erp-detail-value"><?php echo htmlspecialchars($row['email_id'] ?? ''); ?></div>
  </div>
  <div class="erp-detail-item">
    <div class="erp-detail-label">Phone Number</div>
    <div class="erp-detail-value"><?php echo htmlspecialchars($row['phone_number'] ?? 'Not provided'); ?></div>
  </div>
  <div class="erp-detail-item">
    <div class="erp-detail-label">Department</div>
    <div class="erp-detail-value"><?php echo htmlspecialchars($row['department_name'] ?? ''); ?></div>
  </div>
  <div class="erp-detail-item">
    <div class="erp-detail-label">Role</div>
    <div class="erp-detail-value"><?php echo htmlspecialchars($row['role_name'] ?? ''); ?></div>
  </div>
  <?php if (intval($roleId) === 4) {
    $allocRes = $db_handle->query("
        SELECT ssm.subject_name,
               COALESCE(sem.semester_name, CONCAT('Semester ', msm.semester_id)) AS semester_name,
               COALESCE(ay.session_name, CONCAT('AY ', msm.academic_year_id)) AS academic_year_name,
               msm.is_active
        FROM st_mentor_subject_mapping msm 
        JOIN st_specialization_subject_master ssm ON ssm.subject_id = msm.subject_id 
        LEFT JOIN st_semester_master sem ON sem.semester_id = msm.semester_id
        LEFT JOIN st_session_master ay ON ay.session_id = msm.academic_year_id
        WHERE msm.mentor_id = $userId
        ORDER BY msm.academic_year_id DESC, msm.semester_id ASC
    ");
  ?>
  <div class="erp-detail-item" style="grid-column: 1 / -1;">
    <div class="erp-detail-label">Active Academic Allocations (Subject, Semester, AY)</div>
    <div class="erp-detail-value">
      <?php if ($allocRes && $allocRes->num_rows > 0) { ?>
        <table class="table table-condensed table-bordered" style="margin-top: 5px; font-size: 12px;">
          <thead>
            <tr>
              <th>Subject</th>
              <th>Semester</th>
              <th>Academic Year</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($aRow = $allocRes->fetch_assoc()) { ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($aRow['subject_name']); ?></strong></td>
                <td><?php echo htmlspecialchars($aRow['semester_name']); ?></td>
                <td><?php echo htmlspecialchars($aRow['academic_year_name']); ?></td>
                <td>
                  <?php if (intval($aRow['is_active']) === 1) { ?>
                    <span class="label label-success">Active</span>
                  <?php } else { ?>
                    <span class="label label-default">Inactive</span>
                  <?php } ?>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      <?php } else { ?>
        <span class="text-muted">No subject allocations configured.</span>
      <?php } ?>
    </div>
  </div>
  <?php } ?>
</div>
