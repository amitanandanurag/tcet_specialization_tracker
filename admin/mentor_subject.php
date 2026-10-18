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

// Check database schema capabilities for st_mentor_subject_mapping
$hasDeptCol = false;
$hasIsActiveCol = false;
$colCheck = mysqli_query($db_handle->conn, "SHOW COLUMNS FROM st_mentor_subject_mapping");
if ($colCheck) {
  while ($c = mysqli_fetch_assoc($colCheck)) {
    if ($c['Field'] === 'department_id') $hasDeptCol = true;
    if ($c['Field'] === 'is_active') $hasIsActiveCol = true;
  }
}

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
                  if ($hasDeptCol && $hasIsActiveCol) {
                    $insertSql = "INSERT INTO st_mentor_subject_mapping (mentor_id, subject_id, department_id, semester_id, academic_year_id, is_active)
                                  VALUES (?, ?, ?, ?, ?, ?)
                                  ON DUPLICATE KEY UPDATE department_id = VALUES(department_id), is_active = VALUES(is_active), updated_at = CURRENT_TIMESTAMP";
                    $stmt = mysqli_prepare($db_handle->conn, $insertSql);
                    if (!$stmt) {
                      throw new Exception("Database error preparing statement: " . mysqli_error($db_handle->conn));
                    }
                    mysqli_stmt_bind_param($stmt, "iiiiii", $mentorId, $subjectId, $departmentId, $semesterId, $academicYearId, $isActive);
                  } else {
                    $insertSql = "INSERT INTO st_mentor_subject_mapping (mentor_id, subject_id, semester_id, academic_year_id)
                                  VALUES (?, ?, ?, ?)
                                  ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP";
                    $stmt = mysqli_prepare($db_handle->conn, $insertSql);
                    if (!$stmt) {
                      throw new Exception("Database error preparing statement: " . mysqli_error($db_handle->conn));
                    }
                    mysqli_stmt_bind_param($stmt, "iiii", $mentorId, $subjectId, $semesterId, $academicYearId);
                  }
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
      if (!$hasIsActiveCol) {
        $message = "Status toggling is not available under the current database schema.";
        $messageType = "warning";
      } else {
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
$rawMentors = $db_handle->runQuery($mentorsSql);
$mentors = is_array($rawMentors) ? $rawMentors : [];

$rawDepts = $db_handle->runQuery("SELECT department_id, department_name FROM st_department_master ORDER BY department_name ASC");
$departments = is_array($rawDepts) ? $rawDepts : [];

$rawSems = $db_handle->runQuery("SELECT semester_id, semester_name FROM st_semester_master ORDER BY semester_id ASC");
$semesters = is_array($rawSems) ? $rawSems : [];

$rawAys = $db_handle->runQuery("SELECT session_id, session_name FROM st_session_master ORDER BY session_name DESC");
$academicYears = is_array($rawAys) ? $rawAys : [];

$rawSubs = $db_handle->runQuery("SELECT subject_id, subject_name, department_id, semester_id FROM st_specialization_subject_master WHERE is_active = 1 ORDER BY subject_name ASC");
$subjects = is_array($rawSubs) ? $rawSubs : [];

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
  if ($hasDeptCol) {
    $filterWhere[] = "(msm.department_id = $fDept OR u.department_id = $fDept)";
  } else {
    $filterWhere[] = "(ssm.department_id = $fDept OR u.department_id = $fDept)";
  }
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
  $filterWhere[] = $hasIsActiveCol ? "msm.is_active = 1" : "ssm.is_active = 1";
} elseif ($fStatus === '0') {
  $filterWhere[] = $hasIsActiveCol ? "msm.is_active = 0" : "ssm.is_active = 0";
}

$whereClause = !empty($filterWhere) ? "WHERE " . implode(" AND ", $filterWhere) : "";

// Build schema-adaptive column selections and joins
$deptColSelect = $hasDeptCol
  ? "COALESCE(d.department_name, d_alloc.department_name, '-') AS department_name,"
  : "COALESCE(d.department_name, d_sub.department_name, '-') AS department_name,";

$deptJoin = $hasDeptCol
  ? "LEFT JOIN st_department_master d_alloc ON d_alloc.department_id = msm.department_id"
  : "LEFT JOIN st_department_master d_sub ON d_sub.department_id = ssm.department_id";

$isActiveSelect = $hasIsActiveCol
  ? "msm.is_active,"
  : "COALESCE(ssm.is_active, 1) AS is_active,";

// Fetch list of current allocations
$mappingsSql = "
    SELECT msm.mapping_id, 
           msm.mentor_id,
           COALESCE(NULLIF(TRIM(u.user_name), ''), l.username) AS mentor_name,
           u.email_id,
           {$deptColSelect}
           ssm.subject_id,
           ssm.subject_name,
           msm.semester_id,
           COALESCE(sem.semester_name, CONCAT('Semester ', msm.semester_id)) AS semester_name,
           msm.academic_year_id,
           COALESCE(ay.session_name, CONCAT('AY ', msm.academic_year_id)) AS academic_year_name,
           {$isActiveSelect}
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
    JOIN st_specialization_subject_master ssm ON ssm.subject_id = msm.subject_id
    {$deptJoin}
    LEFT JOIN st_semester_master sem ON sem.semester_id = msm.semester_id
    LEFT JOIN st_session_master ay ON ay.session_id = msm.academic_year_id
    {$whereClause}
    ORDER BY ay.session_name DESC, msm.semester_id ASC, ssm.subject_name ASC, mentor_name ASC
";
$rawMappings = $db_handle->runQuery($mappingsSql);
$mappingQueryFailed = ($rawMappings === false);
$mappings = is_array($rawMappings) ? $rawMappings : [];

if ($mappingQueryFailed) {
  error_log("mentor_subject.php: Failed to execute allocations query: " . ($db_handle->last_error ?: mysqli_error($db_handle->conn)));
  if (empty($message)) {
    $message = "Unable to retrieve mentor allocations at this time. Please contact your system administrator.";
    $messageType = "danger";
  }
}

include "header/header.php";
?>

<style>
  /* TCET Institutional ERP - Mentor Allocation Design System */
  .mentor-content-area {
    padding: 18px 22px 30px;
    background-color: #f8fafc;
    min-height: calc(100vh - 100px);
  }

  /* Page Header */
  .mentor-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 16px;
    padding-bottom: 14px;
    border-bottom: 1px solid #e2e8f0;
  }
  .mentor-header-titles {
    display: flex;
    flex-direction: column;
    gap: 2px;
  }
  .mentor-title {
    margin: 0;
    font-size: 19px;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.02em;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .mentor-title i {
    color: var(--erp-primary, #423cbc);
    font-size: 18px;
  }
  .mentor-subtitle {
    margin: 0;
    font-size: 12px;
    color: #64748b;
  }
  .mentor-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }
  .btn-erp-compact {
    height: 32px;
    padding: 0 12px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 4px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none !important;
    transition: all 0.15s ease;
    cursor: pointer;
    line-height: 30px;
  }
  .btn-erp-primary-action {
    background-color: var(--erp-primary, #423cbc);
    color: #ffffff !important;
    border: 1px solid var(--erp-primary-hover, #352fa1);
  }
  .btn-erp-primary-action:hover {
    background-color: var(--erp-primary-hover, #352fa1);
    color: #ffffff !important;
  }
  .btn-erp-neutral-action {
    background-color: #ffffff;
    color: #334155 !important;
    border: 1px solid #cbd5e1;
  }
  .btn-erp-neutral-action:hover {
    background-color: #f8fafc;
    border-color: #94a3b8;
    color: #0f172a !important;
  }

  /* Summary Metric Bar */
  .mentor-summary-strip {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    margin-bottom: 16px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: hidden;
  }
  .mentor-metric-cell {
    padding: 12px 18px;
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
  .mentor-metric-cell:last-child {
    border-right: none;
  }
  .mentor-metric-label {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 3px;
  }
  .mentor-metric-value {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
  }
  .mentor-metric-sub {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 2px;
  }

  /* Academic Card Panel */
  .mentor-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    margin-bottom: 16px;
    overflow: hidden;
  }
  .mentor-card-header {
    padding: 12px 18px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
  }
  .mentor-card-title {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .mentor-card-title i {
    color: var(--erp-primary, #423cbc);
  }
  .mentor-card-subtitle {
    margin: 2px 0 0;
    font-size: 11px;
    color: #64748b;
  }
  .mentor-card-body {
    padding: 16px 18px;
  }
  .mentor-card-footer {
    padding: 12px 18px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  /* Labels & Inputs */
  .mentor-label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-bottom: 4px;
  }
  .mentor-label .req-star {
    color: #dc2626;
    margin-left: 2px;
  }
  .mentor-input, .mentor-select {
    width: 100%;
    height: 34px;
    padding: 5px 10px;
    font-size: 13px;
    color: #0f172a;
    background-color: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
  }
  .mentor-input:focus, .mentor-select:focus {
    border-color: var(--erp-primary, #423cbc);
    outline: none;
    box-shadow: 0 0 0 3px rgba(66, 60, 188, 0.12);
  }
  .mentor-input::placeholder {
    color: #94a3b8;
  }

  /* Filter Toolbar */
  .mentor-filter-row {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    flex-wrap: wrap;
  }
  .filter-col-ay { flex: 1 1 140px; }
  .filter-col-dept { flex: 1 1 180px; }
  .filter-col-sem { flex: 0 1 135px; }
  .filter-col-mentor { flex: 1 1 180px; }
  .filter-col-status { flex: 0 1 125px; }
  .filter-col-action {
    display: flex;
    gap: 6px;
    align-items: center;
  }
  .btn-filter-submit {
    height: 34px;
    padding: 0 14px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 4px;
    background: var(--erp-primary, #423cbc);
    color: #ffffff;
    border: 1px solid var(--erp-primary-hover, #352fa1);
    display: inline-flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: background-color 0.15s ease;
  }
  .btn-filter-submit:hover {
    background: var(--erp-primary-hover, #352fa1);
    color: #ffffff;
  }
  .btn-filter-reset {
    height: 34px;
    padding: 0 12px;
    font-size: 12px;
    font-weight: 600;
    border-radius: 4px;
    background: #ffffff;
    color: #475569;
    border: 1px solid #cbd5e1;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    text-decoration: none !important;
    transition: all 0.15s ease;
  }
  .btn-filter-reset:hover {
    background: #f1f5f9;
    color: #0f172a;
  }

  /* Form Layout inside Left Card */
  .mentor-form-group {
    margin-bottom: 13px;
  }
  .mentor-form-row-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
  }

  /* Mentor Table */
  .mentor-table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .mentor-table {
    width: 100%;
    min-width: 680px;
    border-collapse: collapse;
    font-size: 13px;
  }
  .mentor-table th {
    background: #f8fafc;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 10px 12px;
    border-top: 1px solid #e2e8f0;
    border-bottom: 1px solid #cbd5e1;
    white-space: nowrap;
    text-align: left;
  }
  .mentor-table th.th-center, .mentor-table td.td-center {
    text-align: center;
  }
  .mentor-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #f1f5f9;
    color: #1e293b;
    vertical-align: middle;
  }
  .mentor-table tr:hover td {
    background-color: #f8fafc;
  }

  /* Context Badges */
  .academic-tag-ay {
    display: inline-block;
    padding: 2px 7px;
    font-size: 11px;
    font-weight: 600;
    color: #334155;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 3px;
    margin-right: 4px;
  }
  .academic-tag-sem {
    display: inline-block;
    padding: 2px 7px;
    font-size: 11px;
    font-weight: 600;
    color: #4338ca;
    background: #e0e7ff;
    border-radius: 3px;
  }
  .subject-name-cell {
    font-weight: 600;
    color: #0f172a;
    line-height: 1.35;
  }
  .student-count-badge {
    display: inline-block;
    min-width: 24px;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: 700;
    color: #0f766e;
    background: #ccfbf1;
    border-radius: 12px;
    text-align: center;
  }
  .status-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 12px;
    white-space: nowrap;
  }
  .status-pill.status-active {
    background-color: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
  }
  .status-pill.status-inactive {
    background-color: #f1f5f9;
    color: #64748b;
    border: 1px solid #cbd5e1;
  }
  .status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
  }
  .status-active .status-dot { background-color: #10b981; }
  .status-inactive .status-dot { background-color: #94a3b8; }

  /* Compact Action Buttons */
  .table-action-group {
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }
  .btn-table-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    height: 28px;
    padding: 0 8px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 4px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    color: #334155;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none !important;
  }
  .btn-table-action:hover {
    background: #f1f5f9;
    color: #0f172a;
    border-color: #94a3b8;
  }
  .btn-table-action.btn-action-activate:hover {
    background: #ecfdf5;
    color: #059669;
    border-color: #a7f3d0;
  }
  .btn-table-action.btn-action-danger:hover {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
  }

  /* DataTables Styling Harmonization */
  .dataTables_wrapper .dataTables_length {
    padding: 12px 16px 8px;
    font-size: 12px;
    color: #475569;
    float: left;
  }
  .dataTables_wrapper .dataTables_length select {
    height: 30px;
    padding: 2px 6px;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    font-size: 12px;
    margin: 0 4px;
    outline: none;
  }
  .dataTables_wrapper .dataTables_filter {
    padding: 12px 16px 8px;
    font-size: 12px;
    color: #475569;
    float: right;
  }
  .dataTables_wrapper .dataTables_filter input {
    height: 30px;
    padding: 3px 8px;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    font-size: 12px;
    outline: none;
    margin-left: 6px;
  }
  .dataTables_wrapper .dataTables_filter input:focus,
  .dataTables_wrapper .dataTables_length select:focus {
    border-color: var(--erp-primary, #423cbc);
    box-shadow: 0 0 0 2px rgba(66, 60, 188, 0.12);
  }
  .dataTables_wrapper .dataTables_info {
    padding: 12px 16px 14px;
    font-size: 12px;
    color: #64748b;
    float: left;
  }
  .dataTables_wrapper .dataTables_paginate {
    padding: 10px 16px 14px;
    font-size: 12px;
    float: right;
  }

  @media (max-width: 768px) {
    .mentor-summary-strip {
      grid-template-columns: 1fr;
    }
    .mentor-metric-cell {
      border-right: none;
      border-bottom: 1px solid #e2e8f0;
    }
    .mentor-metric-cell:last-child {
      border-bottom: none;
    }
  }
</style>

<div class="content-wrapper mentor-content-area">
  <!-- Institutional ERP Page Header -->
  <div class="mentor-header">
    <div class="mentor-header-titles">
      <h1 class="mentor-title"><i class="fa fa-graduation-cap"></i> Semester & Subject-Wise Mentor Allocation</h1>
      <p class="mentor-subtitle">Configure distinct faculty mentor assignments per subject, semester, and academic year</p>
    </div>
    <div class="mentor-header-actions">
      <a href="mentor_allocation.php" class="btn-erp-compact btn-erp-primary-action"><i class="fa fa-users"></i> Student Allocation Matrix</a>
      <a href="mentor_register.php" class="btn-erp-compact btn-erp-neutral-action"><i class="fa fa-user-plus"></i> Register Faculty Mentor</a>
    </div>
  </div>

  <section class="content" style="padding: 0;">
    <?php if ($message !== '') { ?>
      <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible" style="border-radius: 4px; margin-bottom: 16px;">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <?php echo $message; ?>
      </div>
    <?php } ?>

    <!-- Summary strip -->
    <div class="mentor-summary-strip">
      <div class="mentor-metric-cell">
        <span class="mentor-metric-label">Available Mentors</span>
        <span class="mentor-metric-value"><?php echo count($mentors); ?></span>
        <span class="mentor-metric-sub">Faculty Members</span>
      </div>
      <div class="mentor-metric-cell">
        <span class="mentor-metric-label">Specialization Subjects</span>
        <span class="mentor-metric-value"><?php echo count($subjects); ?></span>
        <span class="mentor-metric-sub">Active Courses</span>
      </div>
      <div class="mentor-metric-cell">
        <span class="mentor-metric-label">Total Allocations</span>
        <span class="mentor-metric-value" style="color: <?php echo $mappingQueryFailed ? '#dc2626' : 'var(--erp-primary, #423cbc)'; ?>;">
          <?php echo $mappingQueryFailed ? '—' : count($mappings); ?>
        </span>
        <span class="mentor-metric-sub"><?php echo $mappingQueryFailed ? 'Query Error' : 'Semester-Wise'; ?></span>
      </div>
    </div>

    <!-- Filter Card -->
    <div class="mentor-card">
      <div class="mentor-card-body" style="padding: 12px 18px;">
        <form method="GET" action="mentor_subject.php" class="mentor-filter-row">
          <div class="filter-col-ay">
            <label class="mentor-label">Academic Year</label>
            <select name="filter_ay" class="mentor-select">
              <option value="">All Academic Years</option>
              <?php foreach ($academicYears as $ay) { ?>
                <option value="<?php echo intval($ay['session_id']); ?>" <?php echo ($fAY === intval($ay['session_id'])) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($ay['session_name']); ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-col-dept">
            <label class="mentor-label">Department</label>
            <select name="filter_dept" class="mentor-select">
              <option value="">All Departments</option>
              <?php foreach ($departments as $d) { ?>
                <option value="<?php echo intval($d['department_id']); ?>" <?php echo ($fDept === intval($d['department_id'])) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($d['department_name']); ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-col-sem">
            <label class="mentor-label">Semester</label>
            <select name="filter_sem" class="mentor-select">
              <option value="">All Semesters</option>
              <?php foreach ($semesters as $s) { ?>
                <option value="<?php echo intval($s['semester_id']); ?>" <?php echo ($fSem === intval($s['semester_id'])) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($s['semester_name']); ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-col-mentor">
            <label class="mentor-label">Mentor</label>
            <select name="filter_mentor" class="mentor-select">
              <option value="">All Mentors</option>
              <?php foreach ($mentors as $m) { ?>
                <option value="<?php echo intval($m['mentor_id']); ?>" <?php echo ($fMentor === intval($m['mentor_id'])) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($m['mentor_name']); ?>
                </option>
              <?php } ?>
            </select>
          </div>

          <div class="filter-col-status">
            <label class="mentor-label">Status</label>
            <select name="filter_status" class="mentor-select">
              <option value="">All Statuses</option>
              <option value="1" <?php echo ($fStatus === '1') ? 'selected' : ''; ?>>Active</option>
              <option value="0" <?php echo ($fStatus === '0') ? 'selected' : ''; ?>>Inactive</option>
            </select>
          </div>

          <div class="filter-col-action">
            <button type="submit" class="btn-filter-submit" title="Apply filters">
              <i class="fa fa-filter"></i> Apply Filters
            </button>
            <?php if ($fAY > 0 || $fDept > 0 || $fSem > 0 || $fMentor > 0 || $fStatus !== ''): ?>
              <a href="mentor_subject.php" class="btn-filter-reset" title="Reset all filters">
                <i class="fa fa-times"></i> Reset
              </a>
            <?php else: ?>
              <a href="mentor_subject.php" class="btn-filter-reset" title="Reset filters">Reset</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <div class="row">
      <!-- Allocation Form Box -->
      <div class="col-md-4">
        <div class="mentor-card">
          <div class="mentor-card-header">
            <div>
              <h3 class="mentor-card-title"><i class="fa fa-link"></i> Configure Allocation</h3>
              <p class="mentor-card-subtitle">Allocate faculty to exact Academic Year, Semester & Subject</p>
            </div>
          </div>
          <form method="POST" action="mentor_subject.php" id="allocationForm">
            <?php echo DBController::getCsrfInputField(); ?>
            <input type="hidden" name="action" value="save_mapping">
            <div class="mentor-card-body">
              <div class="mentor-form-group">
                <label class="mentor-label">Faculty Mentor <span class="req-star">*</span></label>
                <select class="mentor-select" name="mentor_id" id="form_mentor_id" required>
                  <option value="">Select Mentor</option>
                  <?php foreach ($mentors as $mentor) { ?>
                    <option value="<?php echo intval($mentor['mentor_id']); ?>" data-dept-id="<?php echo intval($mentor['department_id']); ?>">
                      <?php echo htmlspecialchars($mentor['mentor_name'] . ($mentor['department_name'] ? ' (' . $mentor['department_name'] . ')' : '')); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>

              <div class="mentor-form-group">
                <label class="mentor-label">Department <span class="req-star">*</span></label>
                <select class="mentor-select" name="department_id" id="form_department_id" required>
                  <option value="">Select Department</option>
                  <?php foreach ($departments as $dept) { ?>
                    <option value="<?php echo intval($dept['department_id']); ?>">
                      <?php echo htmlspecialchars($dept['department_name']); ?>
                    </option>
                  <?php } ?>
                </select>
              </div>

              <div class="mentor-form-row-split">
                <div class="mentor-form-group">
                  <label class="mentor-label">Academic Year <span class="req-star">*</span></label>
                  <select class="mentor-select" name="academic_year_id" id="form_ay_id" required>
                    <option value="">Select AY</option>
                    <?php foreach ($academicYears as $ay) { ?>
                      <option value="<?php echo intval($ay['session_id']); ?>">
                        <?php echo htmlspecialchars($ay['session_name']); ?>
                      </option>
                    <?php } ?>
                  </select>
                </div>
                <div class="mentor-form-group">
                  <label class="mentor-label">Semester <span class="req-star">*</span></label>
                  <select class="mentor-select" name="semester_id" id="form_semester_id" required>
                    <option value="">Select Sem</option>
                    <?php foreach ($semesters as $sem) { ?>
                      <option value="<?php echo intval($sem['semester_id']); ?>">
                        <?php echo htmlspecialchars($sem['semester_name']); ?>
                      </option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="mentor-form-group">
                <label class="mentor-label">Specialization Subject <span class="req-star">*</span></label>
                <select class="mentor-select" name="subject_id" id="form_subject_id" required>
                  <option value="">Select Subject</option>
                  <?php foreach ($subjects as $subj) { ?>
                    <option value="<?php echo intval($subj['subject_id']); ?>"
                            data-department-id="<?php echo intval($subj['department_id'] ?? 0); ?>"
                            data-semester-id="<?php echo intval($subj['semester_id'] ?? 0); ?>">
                      <?php echo htmlspecialchars($subj['subject_name']); ?>
                    </option>
                  <?php } ?>
                </select>
                <small class="text-muted" id="form_subject_filter_hint" style="display:block; margin-top: 4px; font-size: 11px;">Filtered by department & semester.</small>
              </div>

              <div class="mentor-form-group" style="margin-bottom: 0;">
                <label class="mentor-label">Allocation Status</label>
                <select class="mentor-select" name="is_active">
                  <option value="1">Active (Students will be routed to this mentor)</option>
                  <option value="0">Inactive (Disabled allocation)</option>
                </select>
              </div>
            </div>
            <div class="mentor-card-footer">
              <button type="submit" class="btn-erp-compact btn-erp-primary-action" style="width: 100%; justify-content: center;">
                <i class="fa fa-save"></i> Save Allocation
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Current Allocations Table -->
      <div class="col-md-8">
        <div class="mentor-card">
          <div class="mentor-card-header">
            <div>
              <h3 class="mentor-card-title"><i class="fa fa-list"></i> Configured Allocations</h3>
              <p class="mentor-card-subtitle">Exact Academic Year + Department + Semester + Subject mappings</p>
            </div>
            <div class="pull-right">
              <span class="status-pill status-active" style="background:#f1f5f9; color:#475569; border-color:#cbd5e1; font-weight:700;">
                <?php echo count($mappings); ?> matching records
              </span>
            </div>
          </div>
          <div class="mentor-card-body mentor-table-responsive" style="padding: 0;">
            <table class="mentor-table" id="mappingsTable">
              <thead>
                <tr>
                  <th style="width: 35px;" class="th-center">#</th>
                  <th>Faculty Mentor</th>
                  <th>Academic Context</th>
                  <th>Specialization Subject</th>
                  <th class="th-center">Students</th>
                  <th class="th-center">Status</th>
                  <th style="width: 130px;" class="th-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $sr = 1;
                foreach ($mappings as $map) {
                  $isActive = (intval($map['is_active']) === 1);
                ?>
                  <tr>
                    <td class="td-center text-muted" style="font-size: 12px;"><?php echo $sr++; ?></td>
                    <td>
                      <strong style="color: #0f172a; display:block; font-size: 13px;">
                        <i class="fa fa-user-circle-o" style="color: var(--erp-primary, #423cbc); margin-right: 4px;"></i>
                        <?php echo htmlspecialchars($map['mentor_name'] ?? ''); ?>
                      </strong>
                      <small class="text-muted" style="font-size: 11px;"><?php echo htmlspecialchars($map['email_id'] ?? ''); ?></small>
                    </td>
                    <td>
                      <div>
                        <span class="academic-tag-ay"><?php echo htmlspecialchars($map['academic_year_name'] ?? ''); ?></span>
                        <span class="academic-tag-sem"><?php echo htmlspecialchars($map['semester_name'] ?? ''); ?></span>
                      </div>
                      <small class="text-muted" style="display:block; margin-top:3px; font-size: 11px;"><?php echo htmlspecialchars($map['department_name'] ?? '-'); ?></small>
                    </td>
                    <td>
                      <span class="subject-name-cell">
                        <?php echo htmlspecialchars($map['subject_name'] ?? ''); ?>
                      </span>
                    </td>
                    <td class="td-center">
                      <span class="student-count-badge"><?php echo intval($map['assigned_student_count']); ?></span>
                    </td>
                    <td class="td-center">
                      <?php if ($isActive) { ?>
                        <span class="status-pill status-active">
                          <span class="status-dot"></span> Active
                        </span>
                      <?php } else { ?>
                        <span class="status-pill status-inactive">
                          <span class="status-dot"></span> Inactive
                        </span>
                      <?php } ?>
                    </td>
                    <td class="td-center" style="white-space: nowrap;">
                      <div class="table-action-group">
                        <!-- Toggle Status Form -->
                        <form method="POST" action="mentor_subject.php" style="display:inline;" onsubmit="return confirm('Toggle this allocation status?');">
                          <?php echo DBController::getCsrfInputField(); ?>
                          <input type="hidden" name="action" value="toggle_status">
                          <input type="hidden" name="mapping_id" value="<?php echo intval($map['mapping_id']); ?>">
                          <button type="submit" class="btn-table-action <?php echo $isActive ? '' : 'btn-action-activate'; ?>" title="<?php echo $isActive ? 'Deactivate' : 'Activate'; ?>">
                            <i class="fa <?php echo $isActive ? 'fa-pause' : 'fa-play'; ?>"></i>
                            <?php echo $isActive ? 'Pause' : 'Activate'; ?>
                          </button>
                        </form>

                        <!-- Delete Form -->
                        <form method="POST" action="mentor_subject.php" onsubmit="return confirm('Delete this allocation mapping? Any students assigned exclusively through this allocation will be unlinked.');" style="display:inline;">
                          <?php echo DBController::getCsrfInputField(); ?>
                          <input type="hidden" name="action" value="delete_mapping">
                          <input type="hidden" name="mapping_id" value="<?php echo intval($map['mapping_id']); ?>">
                          <button type="submit" class="btn-table-action btn-action-danger" title="Remove Allocation">
                            <i class="fa fa-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php } ?>
                <?php if ($mappingQueryFailed) { ?>
                  <tr>
                    <td class="text-center text-danger" colspan="7" style="padding: 24px;">
                      <i class="fa fa-exclamation-triangle" style="margin-right: 6px;"></i> An error occurred while retrieving allocation records. Please contact your system administrator.
                    </td>
                  </tr>
                <?php } elseif (empty($mappings)) { ?>
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

  <?php if (!empty($mappings)) { ?>
  if ($.fn.DataTable) {
    $.fn.dataTable.ext.errMode = 'none';
    if (!$.fn.DataTable.isDataTable('#mappingsTable')) {
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
  }
  <?php } ?>
});
</script>

<?php include "header/footer.php"; ?>
