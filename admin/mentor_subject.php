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

$loginRole = intval($userData[0]['role_id'] ?? 0);
$loginDepartment = intval($userData[0]['department_id'] ?? 0);

// Access control: Super Admin (1), Admin (2), Coordinator/HOD (3)
if (!in_array($loginRole, array(1, 2, 3), true)) {
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
    $departmentId = intval($_POST['department_id'] ?? 0);
    $subjectId = intval($_POST['subject_id'] ?? 0);
    $semesterId = intval($_POST['semester_id'] ?? 0);
    $academicYearId = intval($_POST['academic_year_id'] ?? 0);
    $isActive = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

    if ($mentorId <= 0 || $subjectId <= 0 || $semesterId <= 0 || $academicYearId <= 0) {
      $message = "Please fill all required allocation fields: Mentor, Specialization Subject, Semester, and Academic Year.";
      $messageType = "danger";
    } else {
      // Validate subject
      $subjectCheck = mysqli_query($db_handle->conn, "SELECT subject_id, subject_name, department_id FROM st_specialization_subject_master WHERE subject_id = {$subjectId} AND is_active = 1 LIMIT 1");
      if (!$subjectCheck || mysqli_num_rows($subjectCheck) === 0) {
        $message = "Selected subject is invalid or inactive.";
        $messageType = "danger";
      } else {
        $subRow = mysqli_fetch_assoc($subjectCheck);
        if ($departmentId <= 0 && !empty($subRow['department_id'])) {
          $departmentId = intval($subRow['department_id']);
        }
        
        // Department validation
        if (!empty($subRow['department_id']) && $departmentId > 0 && intval($subRow['department_id']) !== $departmentId) {
          $message = "This subject does not belong to the selected department.";
          $messageType = "danger";
        } else {
          // Validate mentor exists
          $mQuery = mysqli_query($db_handle->conn, "SELECT user_id, COALESCE(NULLIF(TRIM(user_name), ''), email_id) AS mentor_name, department_id FROM st_user_master WHERE user_id = $mentorId AND role_id = 4 LIMIT 1");
          if (!$mQuery || mysqli_num_rows($mQuery) === 0) {
            $message = "Selected faculty mentor is invalid.";
            $messageType = "danger";
          } else {
            $mRow = mysqli_fetch_assoc($mQuery);
            $mentorName = $mRow['mentor_name'];
            if ($departmentId <= 0) {
              $departmentId = intval($mRow['department_id']);
            }

            // Validate semester
            $semCheck = mysqli_query($db_handle->conn, "SELECT semester_id, semester_name FROM st_semester_master WHERE semester_id = $semesterId LIMIT 1");
            if (!$semCheck || mysqli_num_rows($semCheck) === 0) {
              $message = "Selected semester is invalid.";
              $messageType = "danger";
            } else {
              $semRow = mysqli_fetch_assoc($semCheck);
              $semesterName = $semRow['semester_name'];

              // Validate Academic Year
              $ayCheck = mysqli_query($db_handle->conn, "SELECT session_id, session_name FROM st_session_master WHERE session_id = $academicYearId LIMIT 1");
              if (!$ayCheck || mysqli_num_rows($ayCheck) === 0) {
                $message = "Selected academic year is invalid.";
                $messageType = "danger";
              } else {
                $ayRow = mysqli_fetch_assoc($ayCheck);
                $ayName = $ayRow['session_name'];

                mysqli_begin_transaction($db_handle->conn);
                try {
                  $insertSql = "INSERT INTO st_mentor_subject_mapping (mentor_id, subject_id, department_id, semester_id, academic_year_id, is_active)
                                VALUES (?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE department_id = VALUES(department_id), is_active = VALUES(is_active), updated_at = CURRENT_TIMESTAMP";
                  $stmt = mysqli_prepare($db_handle->conn, $insertSql);
                  if (!$stmt) {
                    throw new Exception("Database error preparing statement: " . mysqli_error($db_handle->conn));
                  }
                  mysqli_stmt_bind_param($stmt, "iiiiii", $mentorId, $subjectId, $departmentId, $semesterId, $academicYearId, $isActive);
                  $ok = mysqli_stmt_execute($stmt);
                  mysqli_stmt_close($stmt);

                  if (!$ok) {
                    throw new Exception("Failed to save allocation mapping.");
                  }

                  // Recalculate student allocations
                  $recalc = $db_handle->recalculateMentorStudents($mentorId);

                  mysqli_commit($db_handle->conn);

                  $statusText = ($isActive === 1) ? 'Active' : 'Inactive';
                  $message = "<strong>Mentor Allocation Saved Successfully</strong><br><br>";
                  $message .= "<strong>Mentor:</strong> " . htmlspecialchars($mentorName) . "<br>";
                  $message .= "<strong>Subject:</strong> " . htmlspecialchars($subRow['subject_name']) . "<br>";
                  $message .= "<strong>Semester:</strong> " . htmlspecialchars($semesterName) . "<br>";
                  $message .= "<strong>Academic Year:</strong> " . htmlspecialchars($ayName) . "<br>";
                  $message .= "<strong>Status:</strong> " . htmlspecialchars($statusText) . "<br>";
                  $message .= "<strong>Students Removed (Mismatched):</strong> " . intval($recalc['removed']) . "<br>";
                  $message .= "<strong>Students Allocated (Matching):</strong> " . intval($recalc['allocated']) . "<br>";
                  $message .= "<strong>Total Active Students:</strong> " . intval($recalc['current']);
                  $messageType = "success";

                  if (method_exists($db_handle, 'writeAuditLog')) {
                    $db_handle->writeAuditLog($loginUserId, 'MENTOR_ALLOCATION_SAVED', 'st_mentor_subject_mapping', $mentorId, "Configured allocation for {$mentorName}: {$subRow['subject_name']} (Sem {$semesterId}, AY {$academicYearId}, Status {$statusText})");
                  }
                } catch (Throwable $e) {
                  mysqli_rollback($db_handle->conn);
                  $message = "Error saving mentor allocation: " . htmlspecialchars($e->getMessage());
                  $messageType = "danger";
                }
              }
            }
          }
        }
      }
    }
  }
}

// Handle status toggle (Activate / Deactivate)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
  if (!DBController::validateCsrfToken()) {
    $message = "Security validation failed. Please refresh the page.";
    $messageType = "danger";
  } else {
    $mappingId = intval($_POST['mapping_id'] ?? 0);
    if ($mappingId > 0) {
      $mQuery = mysqli_query($db_handle->conn, "SELECT mentor_id, is_active FROM st_mentor_subject_mapping WHERE mapping_id = $mappingId LIMIT 1");
      if ($mQuery && ($row = mysqli_fetch_assoc($mQuery))) {
        $mId = intval($row['mentor_id']);
        $newStatus = (intval($row['is_active']) === 1) ? 0 : 1;
        
        mysqli_begin_transaction($db_handle->conn);
        try {
          $up = mysqli_query($db_handle->conn, "UPDATE st_mentor_subject_mapping SET is_active = $newStatus WHERE mapping_id = $mappingId");
          if (!$up) {
            throw new Exception("Unable to update allocation status.");
          }
          $recalc = $db_handle->recalculateMentorStudents($mId);
          mysqli_commit($db_handle->conn);

          $statusLabel = ($newStatus === 1) ? 'Activated' : 'Deactivated';
          $message = "Allocation successfully {$statusLabel}. Students recalculated (Removed: {$recalc['removed']}, Current: {$recalc['current']}).";
          $messageType = "success";

          if (method_exists($db_handle, 'writeAuditLog')) {
            $db_handle->writeAuditLog($loginUserId, 'MENTOR_ALLOCATION_STATUS_TOGGLED', 'st_mentor_subject_mapping', $mappingId, "Allocation ID {$mappingId} toggled to {$statusLabel}");
          }
        } catch (Throwable $e) {
          mysqli_rollback($db_handle->conn);
          $message = "Error toggling status: " . htmlspecialchars($e->getMessage());
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
          $recalc = $db_handle->recalculateMentorStudents($mId);
          $removedCount = $recalc['removed'];
        }
        
        mysqli_commit($db_handle->conn);
        $message = "Allocation mapping deleted successfully. Invalid student assignments removed: " . intval($removedCount);
        $messageType = "success";

        if (method_exists($db_handle, 'writeAuditLog')) {
          $db_handle->writeAuditLog($loginUserId, 'MENTOR_ALLOCATION_DELETED', 'st_mentor_subject_mapping', $mappingId, "Deleted allocation ID {$mappingId}");
        }
      } catch (Throwable $e) {
        mysqli_rollback($db_handle->conn);
        $message = "Error deleting allocation: " . htmlspecialchars($e->getMessage());
        $messageType = "danger";
      }
    }
  }
}

// Fetch master data for dropdowns and filters
if ($loginRole == 3) {
  $mentorsSql = "
      SELECT u.user_id AS mentor_id,
             COALESCE(NULLIF(TRIM(u.user_name), ''), l.username) AS mentor_name,
             u.email_id,
             u.department_id,
             COALESCE(d.department_name, '') AS department_name
      FROM st_user_master u
      INNER JOIN st_coordinator_mentor cm ON cm.mentor_id = u.user_id
      LEFT JOIN st_login l ON l.user_id = u.user_id
      LEFT JOIN st_department_master d ON d.department_id = u.department_id
      WHERE cm.coordinator_id = $loginUserId AND u.role_id = 4
      ORDER BY mentor_name ASC
  ";
} else {
  $mentorsSql = "
      SELECT u.user_id AS mentor_id,
             COALESCE(NULLIF(TRIM(u.user_name), ''), l.username) AS mentor_name,
             u.email_id,
             u.department_id,
             COALESCE(d.department_name, '') AS department_name
      FROM st_user_master u
      LEFT JOIN st_login l ON l.user_id = u.user_id
      LEFT JOIN st_department_master d ON d.department_id = u.department_id
      WHERE u.role_id = 4
      ORDER BY mentor_name ASC
  ";
}
$mentors = $db_handle->runQuery($mentorsSql) ?? [];

$departments = $db_handle->runQuery("SELECT department_id, department_name FROM st_department_master ORDER BY department_name ASC") ?? [];
$semesters = $db_handle->runQuery("SELECT semester_id, semester_name FROM st_semester_master ORDER BY semester_id ASC") ?? [];
$academicYears = $db_handle->runQuery("SELECT session_id, session_name FROM st_session_master ORDER BY session_name DESC") ?? [];
$subjects = $db_handle->runQuery("SELECT subject_id, subject_name, department_id, semester_id FROM st_specialization_subject_master WHERE is_active = 1 ORDER BY subject_name ASC") ?? [];

// Filters from request
$fAY = isset($_GET['filter_ay']) ? intval($_GET['filter_ay']) : 0;
$fDept = isset($_GET['filter_dept']) ? intval($_GET['filter_dept']) : 0;
$fSem = isset($_GET['filter_sem']) ? intval($_GET['filter_sem']) : 0;
$fSub = isset($_GET['filter_sub']) ? intval($_GET['filter_sub']) : 0;
$fMentor = isset($_GET['filter_mentor']) ? intval($_GET['filter_mentor']) : 0;
$fStatus = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';

$filterWhere = [];
if ($loginRole == 3) {
  $filterWhere[] = "cm.coordinator_id = $loginUserId";
}
if ($fAY > 0) {
  $filterWhere[] = "msm.academic_year_id = $fAY";
}
if ($fDept > 0) {
  $filterWhere[] = "(msm.department_id = $fDept OR u.department_id = $fDept)";
}
if ($fSem > 0) {
  $filterWhere[] = "msm.semester_id = $fSem";
}
if ($fSub > 0) {
  $filterWhere[] = "msm.subject_id = $fSub";
}
if ($fMentor > 0) {
  $filterWhere[] = "msm.mentor_id = $fMentor";
}
if ($fStatus === '1') {
  $filterWhere[] = "msm.is_active = 1";
} elseif ($fStatus === '0') {
  $filterWhere[] = "msm.is_active = 0";
}

$whereClause = !empty($filterWhere) ? "WHERE " . implode(" AND ", $filterWhere) : "";

// Fetch list of current allocations
$mappingsSql = "
    SELECT msm.mapping_id, 
           msm.mentor_id,
           COALESCE(NULLIF(TRIM(u.user_name), ''), l.username) AS mentor_name,
           u.email_id,
           COALESCE(d.department_name, d_alloc.department_name, '-') AS department_name,
           ssm.subject_id,
           ssm.subject_name,
           msm.semester_id,
           COALESCE(sem.semester_name, CONCAT('Semester ', msm.semester_id)) AS semester_name,
           msm.academic_year_id,
           COALESCE(ay.session_name, CONCAT('AY ', msm.academic_year_id)) AS academic_year_name,
           msm.is_active,
           (
             SELECT COUNT(*) 
             FROM st_mentor_student_mapping stud_map
             JOIN st_student_master sm ON sm.student_id = stud_map.student_id AND sm.current_semester_id = stud_map.semester_id
             WHERE stud_map.mentor_id = msm.mentor_id 
               AND stud_map.semester_id = msm.semester_id 
               AND stud_map.academic_year_id = msm.academic_year_id
               AND sm.status = 1
           ) AS assigned_student_count
    FROM st_mentor_subject_mapping msm
    JOIN st_user_master u ON u.user_id = msm.mentor_id
    " . ($loginRole == 3 ? "INNER JOIN st_coordinator_mentor cm ON cm.mentor_id = u.user_id" : "") . "
    LEFT JOIN st_login l ON l.user_id = u.user_id
    LEFT JOIN st_department_master d ON d.department_id = u.department_id
    LEFT JOIN st_department_master d_alloc ON d_alloc.department_id = msm.department_id
    JOIN st_specialization_subject_master ssm ON ssm.subject_id = msm.subject_id
    LEFT JOIN st_semester_master sem ON sem.semester_id = msm.semester_id
    LEFT JOIN st_session_master ay ON ay.session_id = msm.academic_year_id
    {$whereClause}
    ORDER BY ay.session_name DESC, msm.semester_id ASC, ssm.subject_name ASC, mentor_name ASC
";
$mappings = $db_handle->runQuery($mappingsSql) ?? [];

include "header/header.php";
?>

<div class="content-wrapper">
  <!-- Institutional ERP Page Header -->
  <div class="erp-page-header">
    <div>
      <h1 class="erp-page-title"><i class="fa fa-graduation-cap"></i> Semester & Subject-Wise Mentor Allocation</h1>
      <p class="erp-page-subtitle">Configure distinct faculty mentor assignments per subject, semester, and academic year</p>
    </div>
    <div class="erp-page-actions">
      <a href="mentor_allocation.php" class="btn btn-erp-primary"><i class="fa fa-users"></i> Student Allocation Matrix</a>
      <a href="mentor_register.php" class="btn btn-erp-secondary"><i class="fa fa-user-plus"></i> Register Faculty Mentor</a>
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
        <span class="erp-metric-label">Total Allocations</span>
        <span class="erp-metric-value" style="color: #2563eb;"><?php echo count($mappings); ?></span>
        <span class="erp-metric-sub">Semester-Wise</span>
      </div>
    </div>

    <!-- Filter Card -->
    <div class="erp-card" style="margin-bottom: 16px;">
      <div class="erp-card-body" style="padding: 14px 16px;">
        <form method="GET" action="mentor_subject.php" class="form-inline" style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
          <div class="form-group">
            <label style="font-size:12px; margin-right:4px;">Academic Year:</label>
            <select name="filter_ay" class="form-control input-sm">
              <option value="">All Academic Years</option>
              <?php foreach ($academicYears as $ay) { ?>
                <option value="<?php echo intval($ay['session_id']); ?>" <?php echo ($fAY === intval($ay['session_id'])) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($ay['session_name']); ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <div class="form-group">
            <label style="font-size:12px; margin-right:4px;">Department:</label>
            <select name="filter_dept" class="form-control input-sm">
              <option value="">All Departments</option>
              <?php foreach ($departments as $d) { ?>
                <option value="<?php echo intval($d['department_id']); ?>" <?php echo ($fDept === intval($d['department_id'])) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($d['department_name']); ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <div class="form-group">
            <label style="font-size:12px; margin-right:4px;">Semester:</label>
            <select name="filter_sem" class="form-control input-sm">
              <option value="">All Semesters</option>
              <?php foreach ($semesters as $s) { ?>
                <option value="<?php echo intval($s['semester_id']); ?>" <?php echo ($fSem === intval($s['semester_id'])) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($s['semester_name']); ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <div class="form-group">
            <label style="font-size:12px; margin-right:4px;">Mentor:</label>
            <select name="filter_mentor" class="form-control input-sm">
              <option value="">All Mentors</option>
              <?php foreach ($mentors as $m) { ?>
                <option value="<?php echo intval($m['mentor_id']); ?>" <?php echo ($fMentor === intval($m['mentor_id'])) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($m['mentor_name']); ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <div class="form-group">
            <label style="font-size:12px; margin-right:4px;">Status:</label>
            <select name="filter_status" class="form-control input-sm">
              <option value="">All Statuses</option>
              <option value="1" <?php echo ($fStatus === '1') ? 'selected' : ''; ?>>Active</option>
              <option value="0" <?php echo ($fStatus === '0') ? 'selected' : ''; ?>>Inactive</option>
            </select>
          </div>

          <div class="form-group" style="margin-left: auto;">
            <button type="submit" class="btn btn-erp-primary btn-sm"><i class="fa fa-filter"></i> Apply Filters</button>
            <a href="mentor_subject.php" class="btn btn-erp-secondary btn-sm">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <div class="row">
      <!-- Allocation Form Box -->
      <div class="col-md-4">
        <div class="erp-card">
          <div class="erp-card-header">
            <div>
              <h3 class="erp-card-title"><i class="fa fa-link text-primary" style="margin-right: 6px;"></i> Configure Allocation</h3>
              <p class="erp-card-subtitle">Allocate faculty to exact Academic Year, Semester & Subject</p>
            </div>
          </div>
          <form method="POST" action="mentor_subject.php" id="allocationForm">
            <?php echo DBController::getCsrfInputField(); ?>
            <input type="hidden" name="action" value="save_mapping">
            <div class="erp-card-body" style="padding: 16px;">
              <div class="form-group">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Faculty Mentor <span style="color:#dc2626;">*</span></label>
                <select class="form-control select2 input-sm" name="mentor_id" id="form_mentor_id" style="width: 100%;" required>
                  <option value="">Select Mentor</option>
                  <?php foreach ($mentors as $mentor) { ?>
                    <option value="<?php echo intval($mentor['mentor_id']); ?>" data-dept-id="<?php echo intval($mentor['department_id']); ?>">
                      <?php echo htmlspecialchars($mentor['mentor_name'] . ($mentor['department_name'] ? ' (' . $mentor['department_name'] . ')' : '')); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>

              <div class="form-group">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Department <span style="color:#dc2626;">*</span></label>
                <select class="form-control select2 input-sm" name="department_id" id="form_department_id" style="width: 100%;" required>
                  <option value="">Select Department</option>
                  <?php foreach ($departments as $dept) { ?>
                    <option value="<?php echo intval($dept['department_id']); ?>">
                      <?php echo htmlspecialchars($dept['department_name']); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>

              <div class="row">
                <div class="col-xs-6">
                  <div class="form-group">
                    <label style="font-size: 12px; font-weight: 600; color: #475569;">Academic Year <span style="color:#dc2626;">*</span></label>
                    <select class="form-control input-sm" name="academic_year_id" id="form_ay_id" required>
                      <option value="">Select AY</option>
                      <?php foreach ($academicYears as $ay) { ?>
                        <option value="<?php echo intval($ay['session_id']); ?>">
                          <?php echo htmlspecialchars($ay['session_name']); ?>
                        </option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="col-xs-6">
                  <div class="form-group">
                    <label style="font-size: 12px; font-weight: 600; color: #475569;">Semester <span style="color:#dc2626;">*</span></label>
                    <select class="form-control input-sm" name="semester_id" id="form_semester_id" required>
                      <option value="">Select Sem</option>
                      <?php foreach ($semesters as $sem) { ?>
                        <option value="<?php echo intval($sem['semester_id']); ?>">
                          <?php echo htmlspecialchars($sem['semester_name']); ?>
                        </option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Specialization Subject <span style="color:#dc2626;">*</span></label>
                <select class="form-control select2 input-sm" name="subject_id" id="form_subject_id" style="width: 100%;" required>
                  <option value="">Select Subject</option>
                  <?php foreach ($subjects as $subj) { ?>
                    <option value="<?php echo intval($subj['subject_id']); ?>"
                            data-department-id="<?php echo intval($subj['department_id'] ?? 0); ?>"
                            data-semester-id="<?php echo intval($subj['semester_id'] ?? 0); ?>">
                      <?php echo htmlspecialchars($subj['subject_name']); ?>
                    </option>
                  <?php } ?>
                </select>
                <small class="text-muted" id="form_subject_filter_hint" style="display:block; margin-top: 4px;">Filtered by department & semester.</small>
              </div>

              <div class="form-group" style="margin-bottom: 0;">
                <label style="font-size: 12px; font-weight: 600; color: #475569;">Allocation Status</label>
                <select class="form-control input-sm" name="is_active">
                  <option value="1">Active (Students will be routed to this mentor)</option>
                  <option value="0">Inactive (Disabled allocation)</option>
                </select>
              </div>
            </div>
            <div class="erp-card-footer" style="padding: 12px 16px;">
              <button type="submit" class="btn btn-erp-primary btn-block">
                <i class="fa fa-save" style="margin-right: 6px;"></i> Save Allocation
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Current Allocations Table -->
      <div class="col-md-8">
        <div class="erp-card">
          <div class="erp-card-header">
            <div>
              <h3 class="erp-card-title"><i class="fa fa-list text-primary" style="margin-right: 6px;"></i> Configured Allocations</h3>
              <p class="erp-card-subtitle">Exact Academic Year + Department + Semester + Subject mappings</p>
            </div>
            <div class="pull-right">
              <span class="erp-badge erp-badge-secondary" style="font-size: 12px; padding: 4px 10px;"><?php echo count($mappings); ?> matching records</span>
            </div>
          </div>
          <div class="erp-card-body table-responsive" style="padding: 0;">
            <table class="erp-table" id="mappingsTable">
              <thead>
                <tr>
                  <th style="width: 35px;" class="col-center">#</th>
                  <th>Faculty Mentor</th>
                  <th>Academic Context</th>
                  <th>Specialization Subject</th>
                  <th class="col-center">Students</th>
                  <th class="col-center">Status</th>
                  <th style="width: 140px;" class="col-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $sr = 1;
                foreach ($mappings as $map) {
                  $isActive = (intval($map['is_active']) === 1);
                ?>
                  <tr>
                    <td class="col-center text-muted"><?php echo $sr++; ?></td>
                    <td>
                      <strong style="color: #0f172a; display:block;">
                        <i class="fa fa-user-circle-o text-primary" style="margin-right: 4px;"></i>
                        <?php echo htmlspecialchars($map['mentor_name'] ?? ''); ?>
                      </strong>
                      <small class="text-muted"><?php echo htmlspecialchars($map['email_id'] ?? ''); ?></small>
                    </td>
                    <td>
                      <div style="font-size:12px;">
                        <span class="label label-primary" style="font-weight:600; margin-right:2px;"><?php echo htmlspecialchars($map['academic_year_name'] ?? ''); ?></span>
                        <span class="label label-info" style="font-weight:600;"><?php echo htmlspecialchars($map['semester_name'] ?? ''); ?></span>
                      </div>
                      <small class="text-muted" style="display:block; margin-top:3px;"><?php echo htmlspecialchars($map['department_name'] ?? '-'); ?></small>
                    </td>
                    <td>
                      <span class="erp-badge erp-badge-purple" style="white-space: normal; text-align:left;">
                        <?php echo htmlspecialchars($map['subject_name'] ?? ''); ?>
                      </span>
                    </td>
                    <td class="col-center">
                      <span class="badge" style="background:#0f766e;"><?php echo intval($map['assigned_student_count']); ?></span>
                    </td>
                    <td class="col-center">
                      <?php if ($isActive) { ?>
                        <span class="label label-success" style="font-size: 11px;">Active</span>
                      <?php } else { ?>
                        <span class="label label-default" style="font-size: 11px;">Inactive</span>
                      <?php } ?>
                    </td>
                    <td class="col-center" style="white-space: nowrap;">
                      <!-- Toggle Status Form -->
                      <form method="POST" action="mentor_subject.php" style="display:inline;" onsubmit="return confirm('Toggle this allocation status?');">
                        <?php echo DBController::getCsrfInputField(); ?>
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="mapping_id" value="<?php echo intval($map['mapping_id']); ?>">
                        <button type="submit" class="btn btn-xs <?php echo $isActive ? 'btn-default' : 'btn-success'; ?>" title="<?php echo $isActive ? 'Deactivate' : 'Activate'; ?>">
                          <i class="fa <?php echo $isActive ? 'fa-pause' : 'fa-play'; ?>"></i>
                          <?php echo $isActive ? 'Pause' : 'Activate'; ?>
                        </button>
                      </form>

                      <!-- Delete Form -->
                      <form method="POST" action="mentor_subject.php" onsubmit="return confirm('Delete this allocation mapping? Any students assigned exclusively through this allocation will be unlinked.');" style="display:inline;">
                        <?php echo DBController::getCsrfInputField(); ?>
                        <input type="hidden" name="action" value="delete_mapping">
                        <input type="hidden" name="mapping_id" value="<?php echo intval($map['mapping_id']); ?>">
                        <button type="submit" class="btn btn-erp-danger btn-xs" title="Remove Allocation">
                          <i class="fa fa-trash"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php } ?>
                <?php if (empty($mappings)) { ?>
                  <tr>
                    <td class="text-center text-muted" colspan="7" style="padding: 24px;">No mentor allocations match the selected criteria.</td>
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
document.addEventListener('DOMContentLoaded', function() {
  var mentorSelect = document.getElementById('form_mentor_id');
  var deptSelect = document.getElementById('form_department_id');
  var semSelect = document.getElementById('form_semester_id');
  var subjSelect = document.getElementById('form_subject_id');

  if (deptSelect && subjSelect) {
    var allSubOptions = Array.from(subjSelect.options).slice(1);

    function filterSubjectsInForm() {
      var selDept = parseInt(deptSelect.value) || 0;
      var selSem = semSelect ? (parseInt(semSelect.value) || 0) : 0;
      var currentVal = subjSelect.value;
      var count = 0;

      subjSelect.innerHTML = '<option value="">Select Subject</option>';

      allSubOptions.forEach(function(opt) {
        var optDept = parseInt(opt.getAttribute('data-department-id')) || 0;
        var optSem = parseInt(opt.getAttribute('data-semester-id')) || 0;

        var dMatch = (selDept === 0 || optDept === 0 || optDept === selDept);
        var sMatch = (selSem === 0 || optSem === 0 || optSem === selSem);

        if (dMatch && sMatch) {
          subjSelect.appendChild(opt.cloneNode(true));
          count++;
        }
      });

      if (currentVal) {
        subjSelect.value = currentVal;
      }

      var hint = document.getElementById('form_subject_filter_hint');
      if (hint) {
        hint.textContent = 'Showing ' + count + ' subjects matching Department & Semester.';
      }
    }

    deptSelect.addEventListener('change', filterSubjectsInForm);
    if (semSelect) {
      semSelect.addEventListener('change', filterSubjectsInForm);
    }

    // Auto-select department when mentor is selected
    if (mentorSelect) {
      mentorSelect.addEventListener('change', function() {
        var selectedOpt = mentorSelect.options[mentorSelect.selectedIndex];
        if (selectedOpt) {
          var mDept = parseInt(selectedOpt.getAttribute('data-dept-id')) || 0;
          if (mDept > 0) {
            deptSelect.value = mDept;
            filterSubjectsInForm();
          }
        }
      });
    }
  }

  if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#mappingsTable')) {
    $('#mappingsTable').DataTable({
      pageLength: 15,
      order: [[2, 'desc'], [1, 'asc']],
      language: {
        search: "",
        searchPlaceholder: "Search allocations...",
        lengthMenu: "Show _MENU_ entries"
      }
    });
  }
});
</script>

<?php include "header/footer.php"; ?>
