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
    $subjRes = $db_handle->query("
        SELECT ssm.subject_name 
        FROM st_mentor_subject_mapping msm 
        JOIN st_specialization_subject_master ssm ON ssm.subject_id = msm.subject_id 
        WHERE msm.mentor_id = $userId 
        LIMIT 1
    ");
    $subjName = ($subjRes && $subjRow = $subjRes->fetch_assoc()) ? $subjRow['subject_name'] : 'None assigned';
  ?>
  <div class="erp-detail-item" style="grid-column: 1 / -1;">
    <div class="erp-detail-label">Specialization Subject</div>
    <div class="erp-detail-value"><?php echo htmlspecialchars($subjName); ?></div>
  </div>
  <?php } ?>
</div>
