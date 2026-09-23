<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require "../database/db_connect.php";
$db_handle = new DBController();

if (!isset($_SESSION['user_session'])) {
  header("location: ../index.php");
  exit();
}

$loginUserId = intval($_SESSION['user_id'] ?? 0);

$userData = $db_handle->runQuery("
    SELECT role_id, department_id
    FROM st_user_master
    WHERE user_id='$loginUserId'
");

$loginRole = $userData[0]['role_id'] ?? 0;
$loginDepartment = $userData[0]['department_id'] ?? 0;

// Access control: Super Admin, Admin, Coordinator/HOD, or Mentor.
if (!in_array((int) $loginRole, array(1, 2, 3, 4), true)) {
  echo "<script>alert('Access Denied'); window.location.href='index.php';</script>";
  exit();
}

$message = '';
$messageType = '';

// Handle mapping creation/updating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_mapping') {
  if (!DBController::validateCsrfToken()) {
    $message = "Security validation failed. Please refresh the page.";
    $messageType = "danger";
  } else {
    $mentorId = intval($_POST['mentor_id'] ?? 0);
    $subjectId = intval($_POST['subject_id'] ?? 0);

  if ($mentorId <= 0 || $subjectId <= 0) {
    $message = "Please select both a mentor and a specialization subject.";
    $messageType = "danger";
  } else {
    $subjectCheck = mysqli_query($db_handle->conn, "SELECT subject_id FROM st_specialization_subject_master WHERE subject_id = {$subjectId} AND subject_id > 0 AND is_active = 1 LIMIT 1");
    if (!$subjectCheck || mysqli_num_rows($subjectCheck) === 0) {
      $message = "Selected subject is invalid or inactive.";
      $messageType = "danger";
    } else {
      mysqli_begin_transaction($db_handle->conn);
      try {
        // Get mentor name
        $mentorName = '';
        $mQuery = mysqli_query($db_handle->conn, "SELECT COALESCE(NULLIF(TRIM(user_name), ''), email_id) AS mentor_name FROM st_user_master WHERE user_id = $mentorId");
        if ($mQuery && ($mRow = mysqli_fetch_assoc($mQuery))) {
          $mentorName = $mRow['mentor_name'];
        }

        // Get old subject name
        $oldSubjectName = 'None';
        $oldSubQuery = mysqli_query($db_handle->conn, "
          SELECT ssm.subject_name 
          FROM st_mentor_subject_mapping msm
          JOIN st_specialization_subject_master ssm ON ssm.subject_id = msm.subject_id
          WHERE msm.mentor_id = $mentorId
          LIMIT 1
        ");
        if ($oldSubQuery && ($oldRow = mysqli_fetch_assoc($oldSubQuery))) {
          $oldSubjectName = $oldRow['subject_name'];
        }

        // Get new subject name
        $newSubjectName = '';
        $newSubQuery = mysqli_query($db_handle->conn, "SELECT subject_name FROM st_specialization_subject_master WHERE subject_id = $subjectId");
        if ($newSubQuery && ($newRow = mysqli_fetch_assoc($newSubQuery))) {
          $newSubjectName = $newRow['subject_name'];
        }

        // Update st_mentor_subject_mapping
        mysqli_query($db_handle->conn, "DELETE FROM st_mentor_subject_mapping WHERE mentor_id = $mentorId");
        $insert = mysqli_query($db_handle->conn, "INSERT INTO st_mentor_subject_mapping (mentor_id, subject_id) VALUES ($mentorId, $subjectId)");
        
        if (!$insert) {
          throw new Exception("Database error mapping subject.");
        }

        // Recalculate student allocations
        $recalc = $db_handle->recalculateMentorStudents($mentorId);

        mysqli_commit($db_handle->conn);

        $message = "<strong>Mentor Subject Updated Successfully</strong><br><br>";
        $message .= "<strong>Mentor:</strong> " . htmlspecialchars($mentorName) . "<br>";
        $message .= "<strong>Previous Subject:</strong> " . htmlspecialchars($oldSubjectName) . "<br>";
        $message .= "<strong>New Subject:</strong> " . htmlspecialchars($newSubjectName) . "<br>";
        $message .= "<strong>Students Removed:</strong> " . intval($recalc['removed']) . "<br>";
        $message .= "<strong>Students Newly Allocated:</strong> " . intval($recalc['allocated']) . "<br>";
        $message .= "<strong>Current Students:</strong> " . intval($recalc['current']);
        $messageType = "success";

        if (method_exists($db_handle, 'writeAuditLog')) {
          $db_handle->writeAuditLog($loginUserId, 'MENTOR_SUBJECT_MAPPED', 'st_mentor_subject_mapping', $mentorId, "Assigned subject ID {$subjectId} to mentor ID {$mentorId}");
        }
      } catch (Throwable $e) {
        mysqli_rollback($db_handle->conn);
        $message = "Error assigning subject to mentor: " . htmlspecialchars($e->getMessage());
        $messageType = "danger";
      }
    }
  }
}
}

// Handle mapping deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_mapping') {
  if (!DBController::validateCsrfToken()) {
    $message = "Security validation failed. Please refresh the page.";
    $messageType = "danger";
  } else {
    $mappingId = intval($_POST['mapping_id'] ?? 0);
    if ($mappingId > 0) {
      mysqli_begin_transaction($db_handle->conn);
      try {
        // Find mentor_id first
        $mQuery = mysqli_query($db_handle->conn, "SELECT mentor_id FROM st_mentor_subject_mapping WHERE mapping_id = $mappingId");
        $mId = 0;
        if ($mQuery && ($mRow = mysqli_fetch_assoc($mQuery))) {
          $mId = intval($mRow['mentor_id']);
        }
        
        $del = mysqli_query($db_handle->conn, "DELETE FROM st_mentor_subject_mapping WHERE mapping_id = $mappingId");
        if (!$del) {
          throw new Exception("Database error deleting mapping.");
        }
        
        $removedCount = 0;
        if ($mId > 0) {
          // recalculateMentorStudents will remove all active allocations because the mapping is now deleted
          $recalc = $db_handle->recalculateMentorStudents($mId);
          $removedCount = $recalc['removed'];
        }
        
        mysqli_commit($db_handle->conn);
        $message = "Mapping deleted successfully! All current allocations for this mentor were removed (Total: $removedCount).";
        $messageType = "success";
      } catch (Throwable $e) {
        mysqli_rollback($db_handle->conn);
        $message = "Error deleting mapping: " . htmlspecialchars($e->getMessage());
        $messageType = "danger";
      }
    }
  }
}

// Fetch mentors based on login role
if ($loginRole == 3) {
  // Coordinator: show only mentors assigned to them
  $mentorsSql = "
      SELECT u.user_id AS mentor_id,
             COALESCE(NULLIF(TRIM(u.user_name), ''), l.username) AS mentor_name,
             COALESCE(d.department_name, '') AS department_name
      FROM st_user_master u
      INNER JOIN st_coordinator_mentor cm ON cm.mentor_id = u.user_id
      LEFT JOIN st_login l ON l.user_id = u.user_id
      LEFT JOIN st_department_master d ON d.department_id = u.department_id
      WHERE cm.coordinator_id = $loginUserId AND u.role_id = 4
      ORDER BY mentor_name ASC
  ";
} else {
  // Admin/Super Admin: show all mentors
  $mentorsSql = "
      SELECT u.user_id AS mentor_id,
             COALESCE(NULLIF(TRIM(u.user_name), ''), l.username) AS mentor_name,
             COALESCE(d.department_name, '') AS department_name
      FROM st_user_master u
      LEFT JOIN st_login l ON l.user_id = u.user_id
      LEFT JOIN st_department_master d ON d.department_id = u.department_id
      WHERE u.role_id = 4
      ORDER BY mentor_name ASC
  ";
}
$mentors = $db_handle->runQuery($mentorsSql) ?? [];

// Fetch specialization subjects
$subjects = $db_handle->runQuery("SELECT subject_id, subject_name FROM st_specialization_subject_master WHERE subject_id > 0 AND is_active = 1 ORDER BY subject_name ASC") ?? [];

// Fetch list of current mappings to display
if ($loginRole == 3) {
  $mappingsSql = "
      SELECT msm.mapping_id, 
             COALESCE(NULLIF(TRIM(u.user_name), ''), l.username) AS mentor_name,
             d.department_name,
             ssm.subject_name
      FROM st_mentor_subject_mapping msm
      JOIN st_user_master u ON u.user_id = msm.mentor_id
      INNER JOIN st_coordinator_mentor cm ON cm.mentor_id = u.user_id
      LEFT JOIN st_login l ON l.user_id = u.user_id
      LEFT JOIN st_department_master d ON d.department_id = u.department_id
      JOIN st_specialization_subject_master ssm ON ssm.subject_id = msm.subject_id
      WHERE cm.coordinator_id = $loginUserId
      ORDER BY mentor_name ASC, ssm.subject_name ASC
  ";
} else {
  $mappingsSql = "
      SELECT msm.mapping_id, 
             COALESCE(NULLIF(TRIM(u.user_name), ''), l.username) AS mentor_name,
             d.department_name,
             ssm.subject_name
      FROM st_mentor_subject_mapping msm
      JOIN st_user_master u ON u.user_id = msm.mentor_id
      LEFT JOIN st_login l ON l.user_id = u.user_id
      LEFT JOIN st_department_master d ON d.department_id = u.department_id
      JOIN st_specialization_subject_master ssm ON ssm.subject_id = msm.subject_id
      ORDER BY mentor_name ASC, ssm.subject_name ASC
  ";
}
$mappings = $db_handle->runQuery($mappingsSql) ?? [];

include "header/header.php";
?>

<div class="content-wrapper">
  <!-- Institutional ERP Page Header -->
  <div class="erp-page-header">
    <div>
      <h1 class="erp-page-title">Mentor Subject Assignment</h1>
      <p class="erp-page-subtitle">Map faculty mentors to specialization subject courses for student cohort allocation</p>
    </div>
    <div class="erp-page-actions">
      <a href="mentor_assignment.php" class="btn btn-erp-primary"><i class="fa fa-users"></i> Mentor Allocation Matrix</a>
      <a href="specialization_subject_manage.php" class="btn btn-erp-secondary"><i class="fa fa-book"></i> Subject Directory</a>
    </div>
  </div>

  <section class="content" style="padding-top: 0;">
    <?php if ($message !== '') { ?>
      <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible" style="border-radius: 4px; margin-bottom: 16px;">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <?php echo $message; ?>
      </div>
    <?php } ?>

    <!-- Summary strip -->
    <div class="erp-summary-bar">
      <div class="erp-metric-item">
        <span class="erp-metric-label">Available Mentors</span>
        <span class="erp-metric-value"><?php echo count($mentors); ?></span>
        <span class="erp-metric-sub">Faculty Members</span>
      </div>
      <div class="erp-metric-item">
        <span class="erp-metric-label">Specialization Subjects</span>
        <span class="erp-metric-value"><?php echo count($subjects); ?></span>
        <span class="erp-metric-sub">Active Courses</span>
      </div>
      <div class="erp-metric-item">
        <span class="erp-metric-label">Active Mappings</span>
        <span class="erp-metric-value" style="color: #16a34a;"><?php echo count($mappings); ?></span>
        <span class="erp-metric-sub">Configured</span>
      </div>
    </div>

    <div class="row">
      <!-- Assignment Form Box -->
      <div class="col-md-4">
        <div class="erp-card">
          <div class="erp-card-header">
            <div>
              <h3 class="erp-card-title"><i class="fa fa-link text-primary" style="margin-right: 6px;"></i> Assign Subject</h3>
              <p class="erp-card-subtitle">Connect faculty member to a subject</p>
            </div>
          </div>
          <form method="POST" action="mentor_subject.php">
            <?php echo DBController::getCsrfInputField(); ?>
            <input type="hidden" name="action" value="save_mapping">
            <div class="erp-card-body" style="padding: 16px;">
              <div class="form-group">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Faculty Mentor <span style="color:#dc2626;">*</span></label>
                <select class="form-control select2 input-sm" name="mentor_id" style="width: 100%; border-radius: 3px;" required>
                  <option value="">Select Mentor</option>
                  <?php foreach ($mentors as $mentor) { ?>
                    <option value="<?php echo intval($mentor['mentor_id']); ?>">
                      <?php echo htmlspecialchars($mentor['mentor_name'] . ($mentor['department_name'] ? ' (' . $mentor['department_name'] . ')' : '')); ?>
                    </option>
                  <?php } ?>
                </select>
                <span class="help-block" style="font-size: 11px; color: #64748b; margin-top: 4px;">Faculty member assigned as specialization mentor.</span>
              </div>

              <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Specialization Subject <span style="color:#dc2626;">*</span></label>
                <select class="form-control select2 input-sm" name="subject_id" style="width: 100%; border-radius: 3px;" required>
                  <option value="">Select Subject</option>
                  <?php foreach ($subjects as $subj) { ?>
                    <option value="<?php echo intval($subj['subject_id']); ?>">
                      <?php echo htmlspecialchars($subj['subject_name']); ?>
                    </option>
                  <?php } ?>
                </select>
                <span class="help-block" style="font-size: 11px; color: #64748b; margin-top: 4px;">Subject determining student cohort routing.</span>
              </div>
            </div>
            <div class="erp-card-footer" style="padding: 12px 16px;">
              <button type="submit" class="btn btn-erp-primary btn-block">
                <i class="fa fa-save" style="margin-right: 6px;"></i> Save Assignment
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Current Assignments Table -->
      <div class="col-md-8">
        <div class="erp-card">
          <div class="erp-card-header">
            <div>
              <h3 class="erp-card-title"><i class="fa fa-list text-primary" style="margin-right: 6px;"></i> Current Mappings</h3>
              <p class="erp-card-subtitle">Active mentor-subject associations</p>
            </div>
            <div class="pull-right">
              <span class="erp-badge erp-badge-secondary" style="font-size: 12px; padding: 4px 10px;"><?php echo count($mappings); ?> active</span>
            </div>
          </div>
          <div class="erp-card-body table-responsive" style="padding: 0;">
            <table class="erp-table" id="mappingsTable">
              <thead>
                <tr>
                  <th style="width: 45px;" class="col-center">#</th>
                  <th>Mentor Name</th>
                  <th>Department</th>
                  <th>Assigned Specialization Subject</th>
                  <th style="width: 90px;" class="col-center">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $sr = 1;
                foreach ($mappings as $map) {
                ?>
                  <tr>
                    <td class="col-center text-muted"><?php echo $sr++; ?></td>
                    <td>
                      <strong style="color: #0f172a;">
                        <i class="fa fa-user-circle-o text-primary" style="margin-right: 4px;"></i>
                        <?php echo htmlspecialchars($map['mentor_name'] ?? ''); ?>
                      </strong>
                    </td>
                    <td>
                      <span class="text-muted" style="font-size: 12px;"><?php echo htmlspecialchars($map['department_name'] ?? '-'); ?></span>
                    </td>
                    <td>
                      <span class="erp-badge erp-badge-purple">
                        <?php echo htmlspecialchars($map['subject_name'] ?? ''); ?>
                      </span>
                    </td>
                    <td class="col-center">
                      <form method="POST" action="mentor_subject.php" onsubmit="return confirm('Are you sure you want to remove this mapping? Any active allocations will be recalculated.');" style="display:inline;">
                        <?php echo DBController::getCsrfInputField(); ?>
                        <input type="hidden" name="action" value="delete_mapping">
                        <input type="hidden" name="mapping_id" value="<?php echo intval($map['mapping_id']); ?>">
                        <button type="submit" class="btn btn-erp-danger btn-xs" title="Remove Mapping">
                          <i class="fa fa-trash"></i> Delete
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php } ?>
                <?php if (empty($mappings)) { ?>
                  <tr>
                    <td class="text-center text-muted" colspan="5" style="padding: 24px;">No mentor-subject mappings defined yet.</td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
$(document).ready(function() {
  if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#mappingsTable')) {
    $('#mappingsTable').DataTable({
      pageLength: 15,
      order: [[1, 'asc']],
      language: {
        search: "",
        searchPlaceholder: "Search mappings...",
        lengthMenu: "Show _MENU_ entries"
      }
    });
  }
});
</script>

<?php include "header/footer.php"; ?>
