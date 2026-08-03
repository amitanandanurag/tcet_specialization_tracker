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

$loginUserId = $_SESSION['user_session'];

$userData = $db_handle->runQuery("
    SELECT role_id, department_id
    FROM st_user_master
    WHERE user_id='$loginUserId'
");

$loginRole = $userData[0]['role_id'] ?? 0;
$loginDepartment = $userData[0]['department_id'] ?? 0;

// Access control: only Roles 1 (Super Admin), 2 (Admin), or 3 (Coordinator)
if ($loginRole != 1 && $loginRole != 2 && $loginRole != 3) {
  echo "<script>alert('Access Denied'); window.location.href='index.php';</script>";
  exit();
}

$message = '';
$messageType = '';

// Handle mapping creation/updating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_mapping') {
  $mentorId = intval($_POST['mentor_id'] ?? 0);
  $subjectId = intval($_POST['subject_id'] ?? 0);

  if ($mentorId <= 0 || $subjectId <= 0) {
    $message = "Please select both a mentor and a specialization subject.";
    $messageType = "danger";
  } else {
    // Save to st_mentor_subject_mapping
    mysqli_query($db_handle->conn, "DELETE FROM st_mentor_subject_mapping WHERE mentor_id = $mentorId");
    $insert = mysqli_query($db_handle->conn, "INSERT INTO st_mentor_subject_mapping (mentor_id, subject_id) VALUES ($mentorId, $subjectId)");

    if ($insert) {
      $message = "Subject assigned to mentor successfully!";
      $messageType = "success";

      // Proactive Auto-Allocation: find unassigned students for this subject and assign them this mentor
      $unassignedSql = "
          SELECT sm.student_id, sm.current_semester_id 
          FROM st_student_master sm
          LEFT JOIN st_mentor_student_mapping msm ON msm.student_id = sm.student_id AND msm.semester_id = sm.current_semester_id
          WHERE sm.specialization_subject_id = $subjectId AND msm.mentor_id IS NULL
      ";
      $unassignedResult = mysqli_query($db_handle->conn, $unassignedSql);
      $allocCount = 0;
      if ($unassignedResult) {
        while ($studRow = mysqli_fetch_assoc($unassignedResult)) {
          $sId = intval($studRow['student_id']);
          $semId = intval($studRow['current_semester_id'] ?? 1);
          mysqli_query($db_handle->conn, "INSERT INTO st_mentor_student_mapping (mentor_id, student_id, semester_id) VALUES ($mentorId, $sId, $semId)");
          $allocCount++;
        }
      }
      if ($allocCount > 0) {
        $message .= " Automatically allocated this mentor to $allocCount student(s) who selected this subject.";
      }

      if (method_exists($db_handle, 'writeAuditLog')) {
        $db_handle->writeAuditLog($loginUserId, 'MENTOR_SUBJECT_MAPPED', 'st_mentor_subject_mapping', $mentorId, "Assigned subject ID {$subjectId} to mentor ID {$mentorId}");
      }
    } else {
      $message = "Error assigning subject to mentor: " . mysqli_error($db_handle->conn);
      $messageType = "danger";
    }
  }
}

// Handle mapping deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_mapping') {
  $mappingId = intval($_POST['mapping_id'] ?? 0);
  if ($mappingId > 0) {
    $del = mysqli_query($db_handle->conn, "DELETE FROM st_mentor_subject_mapping WHERE mapping_id = $mappingId");
    if ($del) {
      $message = "Mapping deleted successfully!";
      $messageType = "success";
    } else {
      $message = "Error deleting mapping: " . mysqli_error($db_handle->conn);
      $messageType = "danger";
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
$subjects = $db_handle->runQuery("SELECT subject_id, subject_name FROM st_specialization_subject_master ORDER BY subject_name ASC") ?? [];

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
  <section class="content-header">
    <h1><i class="fa fa-book"></i> Mentor Specialization Subject Assignment</h1>
    <ol class="breadcrumb">
      <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
      <li class="active">Mentor Subject Assignment</li>
    </ol>
  </section>

  <section class="content">
    <?php if ($message !== '') { ?>
      <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php } ?>

    <div class="row">
      <!-- Assignment Form Box -->
      <div class="col-md-4">
        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title">Assign Subject to Mentor</h3>
          </div>
          <form method="POST" action="mentor_subject.php">
            <input type="hidden" name="action" value="save_mapping">
            <div class="box-body">
              <div class="form-group">
                <label>Mentor <span style="color:red;">*</span></label>
                <select class="form-control select2" name="mentor_id" style="width: 100%;" required>
                  <option value="">Select Mentor</option>
                  <?php foreach ($mentors as $mentor) { ?>
                    <option value="<?php echo intval($mentor['mentor_id']); ?>">
                      <?php echo htmlspecialchars($mentor['mentor_name'] . ($mentor['department_name'] ? ' - ' . $mentor['department_name'] : '')); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>

              <div class="form-group">
                <label>Specialization Subject <span style="color:red;">*</span></label>
                <select class="form-control select2" name="subject_id" style="width: 100%;" required>
                  <option value="">Select Subject</option>
                  <?php foreach ($subjects as $subj) { ?>
                    <option value="<?php echo intval($subj['subject_id']); ?>">
                      <?php echo htmlspecialchars($subj['subject_name']); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>
            </div>
            <div class="box-footer">
              <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> SAVE ASSIGNMENT</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Current Assignments Table -->
      <div class="col-md-8">
        <div class="box box-default">
          <div class="box-header with-border">
            <h3 class="box-title">Current Mentor-Subject Mapping</h3>
          </div>
          <div class="box-body table-responsive">
            <table class="table table-bordered table-striped" id="mappingsTable">
              <thead>
                <tr>
                  <th style="width: 10px">#</th>
                  <th>Mentor Name</th>
                  <th>Department</th>
                  <th>Assigned Specialization Subject</th>
                  <th style="width: 100px">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $sr = 1;
                foreach ($mappings as $map) {
                ?>
                  <tr>
                    <td><?php echo $sr++; ?></td>
                    <td><strong><?php echo htmlspecialchars($map['mentor_name'] ?? ''); ?></strong></td>
                    <td><?php echo htmlspecialchars($map['department_name'] ?? ''); ?></td>
                    <td><span class="label label-info" style="font-size: 13px;"><?php echo htmlspecialchars($map['subject_name'] ?? ''); ?></span></td>
                    <td>
                      <form method="POST" action="mentor_subject.php" onsubmit="return confirm('Are you sure you want to remove this mapping?');" style="display:inline;">
                        <input type="hidden" name="action" value="delete_mapping">
                        <input type="hidden" name="mapping_id" value="<?php echo intval($map['mapping_id']); ?>">
                        <button type="submit" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php } ?>
                <?php if (empty($mappings)) { ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted">No mappings defined yet.</td>
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
  if ($.fn.DataTable) {
    $('#mappingsTable').DataTable({
      pageLength: 15,
      order: [[1, 'asc']]
    });
  }
});
</script>

<?php include "header/footer.php"; ?>
