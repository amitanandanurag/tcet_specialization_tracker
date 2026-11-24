<?php
/**
 * TCET Specialization Tracker (TCET_ST)
 * Department & HOD Monitoring Module — Professional Institutional ERP Edition
 *
 * Provides institutional overview of:
 * 1. Summary KPIs: Total departments, active HODs, active mentors, unassigned mentors, departments without HOD.
 * 2. Department-wise Table: Department name, code, HOD name, status, assigned mentors, unassigned mentors, active students, actions.
 * 3. HOD-wise Mentor Details: HOD info, account status, assigned mentor list, assignment dates, student counts, unassigned list.
 *
 * RBAC: Strictly restricted to Super Admin (role_id = 1) and Admin (role_id = 2).
 */

if (session_status() !== PHP_SESSION_ACTIVE && php_sapi_name() !== 'cli') {
    session_start();
}

require_once __DIR__ . "/header/header.php";

// Strict RBAC Enforcement
if ((int)($usertype ?? 0) !== 1 && (int)($usertype ?? 0) !== 2) {
    http_response_code(403);
    echo '<div class="content-wrapper"><section class="content"><div class="alert alert-danger" style="margin: 30px;"><i class="fa fa-ban"></i> <strong>403 Access Denied:</strong> Administrative privileges are required to view Department & HOD Monitoring.</div></section></div>';
    include "header/footer.php";
    exit;
}

$conn = $db_handle->conn;

// 1. Check if institutional HOD appointment table exists
$hasDeptHodTable = false;
$tCheck = mysqli_query($conn, "SHOW TABLES LIKE 'st_department_hod'");
if ($tCheck && mysqli_num_rows($tCheck) > 0) {
    $hasDeptHodTable = true;
}

// Support incoming department filter parameter from Branch Information
$filterDepartmentId = intval($_GET['department_id'] ?? 0);

// 2. Fetch all recognized institutional academic departments (excluding non-academic ID 15)
$departments = array();
$deptSql = "SELECT department_id, department_name, program_name FROM st_department_master WHERE department_id != 15 ORDER BY department_id ASC";
$deptRes = mysqli_query($conn, $deptSql);
if ($deptRes) {
    while ($dRow = mysqli_fetch_assoc($deptRes)) {
        $departments[$dRow['department_id']] = array(
            'id' => (int)$dRow['department_id'],
            'name' => $dRow['department_name'],
            'program' => $dRow['program_name'] ?: $dRow['department_name'],
            'hods' => array(),
            'total_mentors' => 0,
            'assigned_mentors' => 0,
            'unassigned_mentors' => 0,
            'active_students' => 0,
            'registered_students' => 0
        );
    }
}

// Fetch active student counts per department
$stuSql = "SELECT department_id, 
                  COUNT(*) as registered_students, 
                  SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_students 
           FROM st_student_master 
           WHERE department_id != 15
           GROUP BY department_id";
$stuRes = mysqli_query($conn, $stuSql);
if ($stuRes) {
    while ($sRow = mysqli_fetch_assoc($stuRes)) {
        $dId = (int)$sRow['department_id'];
        if (isset($departments[$dId])) {
            $departments[$dId]['registered_students'] = (int)$sRow['registered_students'];
            $departments[$dId]['active_students'] = (int)$sRow['active_students'];
        }
    }
}

// 3. Fetch all HODs / Coordinators (role_id = 3)
$hods = array();
$hodSql = "
    SELECT 
        u.user_id,
        COALESCE(NULLIF(TRIM(u.user_name), ''), l.username) AS hod_name,
        u.email_id,
        u.phone_number,
        u.department_id,
        d.department_name,
        l.username,
        " . ($hasDeptHodTable ? "
        CASE WHEN h.hod_id IS NOT NULL AND h.is_active = 1 THEN 1 ELSE 0 END AS is_verified_hod,
        h.appointment_reference
        " : "
        0 AS is_verified_hod,
        NULL AS appointment_reference
        ") . "
    FROM st_user_master u
    LEFT JOIN st_login l ON l.user_id = u.user_id
    LEFT JOIN st_department_master d ON d.department_id = u.department_id
    " . ($hasDeptHodTable ? "LEFT JOIN st_department_hod h ON h.user_id = u.user_id AND h.department_id = u.department_id AND h.is_active = 1" : "") . "
    WHERE u.role_id = 3
    ORDER BY d.department_id ASC, is_verified_hod DESC, hod_name ASC
";
$hodRes = mysqli_query($conn, $hodSql);
if ($hodRes) {
    while ($hRow = mysqli_fetch_assoc($hodRes)) {
        $hId = (int)$hRow['user_id'];
        $hods[$hId] = array(
            'user_id' => $hId,
            'name' => $hRow['hod_name'] ?? 'Unknown Staff',
            'username' => $hRow['username'] ?? '',
            'email' => $hRow['email_id'] ?? '',
            'phone' => $hRow['phone_number'] ?? '',
            'department_id' => (int)$hRow['department_id'],
            'department_name' => $hRow['department_name'] ?? 'Unassigned',
            'is_verified_hod' => (int)$hRow['is_verified_hod'],
            'appointment_reference' => $hRow['appointment_reference'] ?? '',
            'assigned_mentors' => array()
        );
    }
}

// 4. Fetch Mentor assignments from st_coordinator_mentor
$cmSql = "
    SELECT 
        cm.id,
        cm.coordinator_id,
        cm.mentor_id,
        cm.created_at,
        COALESCE(NULLIF(TRIM(mu.user_name), ''), ml.username) AS mentor_name,
        mu.email_id AS mentor_email,
        mu.phone_number AS mentor_phone,
        mu.department_id AS mentor_department_id,
        md.department_name AS mentor_department_name,
        ml.username AS mentor_username,
        (SELECT COUNT(*) FROM st_mentor_student_mapping msm WHERE msm.mentor_id = mu.user_id) AS student_count,
        (SELECT COUNT(*) FROM st_mentor_subject_mapping msub WHERE msub.mentor_id = mu.user_id AND msub.is_active = 1) AS subject_count
    FROM st_coordinator_mentor cm
    JOIN st_user_master mu ON mu.user_id = cm.mentor_id AND mu.role_id = 4
    LEFT JOIN st_login ml ON ml.user_id = mu.user_id
    LEFT JOIN st_department_master md ON md.department_id = mu.department_id
    ORDER BY mentor_name ASC
";
$cmRes = mysqli_query($conn, $cmSql);
$assignedMentorIds = array();
if ($cmRes) {
    while ($cmRow = mysqli_fetch_assoc($cmRes)) {
        $coordId = (int)$cmRow['coordinator_id'];
        $mentorId = (int)$cmRow['mentor_id'];
        $assignedMentorIds[$mentorId] = true;

        $mentorData = array(
            'id' => (int)$cmRow['id'],
            'mentor_id' => $mentorId,
            'name' => $cmRow['mentor_name'] ?? 'Faculty Mentor',
            'username' => $cmRow['mentor_username'] ?? '',
            'email' => $cmRow['mentor_email'] ?? '',
            'phone' => $cmRow['mentor_phone'] ?? '',
            'department_id' => (int)$cmRow['mentor_department_id'],
            'department_name' => $cmRow['mentor_department_name'] ?? 'Unassigned',
            'student_count' => (int)$cmRow['student_count'],
            'subject_count' => (int)$cmRow['subject_count'],
            'assigned_at' => $cmRow['created_at']
        );

        if (isset($hods[$coordId])) {
            $hods[$coordId]['assigned_mentors'][] = $mentorData;
        }
    }
}

// Attach HODs to department buckets
foreach ($hods as $hId => $hInfo) {
    $deptId = $hInfo['department_id'];
    if (isset($departments[$deptId])) {
        $departments[$deptId]['hods'][] = $hInfo;
        $departments[$deptId]['assigned_mentors'] += count($hInfo['assigned_mentors']);
    }
}

// 5. Fetch all mentors and identify unassigned
$allMentorsSql = "
    SELECT 
        u.user_id AS mentor_id,
        COALESCE(NULLIF(TRIM(u.user_name), ''), l.username) AS mentor_name,
        u.email_id AS mentor_email,
        u.phone_number AS mentor_phone,
        u.department_id,
        d.department_name,
        l.username,
        (SELECT COUNT(*) FROM st_mentor_student_mapping msm WHERE msm.mentor_id = u.user_id) AS student_count,
        (SELECT COUNT(*) FROM st_mentor_subject_mapping msub WHERE msub.mentor_id = u.user_id AND msub.is_active = 1) AS subject_count
    FROM st_user_master u
    LEFT JOIN st_login l ON l.user_id = u.user_id
    LEFT JOIN st_department_master d ON d.department_id = u.department_id
    WHERE u.role_id = 4
    ORDER BY d.department_id ASC, mentor_name ASC
";
$mRes = mysqli_query($conn, $allMentorsSql);
$unassignedMentors = array();
$totalMentors = 0;
if ($mRes) {
    while ($mRow = mysqli_fetch_assoc($mRes)) {
        $totalMentors++;
        $mId = (int)$mRow['mentor_id'];
        $deptId = (int)$mRow['department_id'];
        if (isset($departments[$deptId])) {
            $departments[$deptId]['total_mentors']++;
        }
        if (!isset($assignedMentorIds[$mId])) {
            $unassignedMentors[] = array(
                'mentor_id' => $mId,
                'name' => $mRow['mentor_name'] ?? 'Faculty Mentor',
                'username' => $mRow['username'] ?? '',
                'email' => $mRow['mentor_email'] ?? '',
                'phone' => $mRow['mentor_phone'] ?? '',
                'department_id' => $deptId,
                'department_name' => $mRow['department_name'] ?? 'Unassigned',
                'student_count' => (int)$mRow['student_count'],
                'subject_count' => (int)$mRow['subject_count']
            );
        }
    }
}

// Update unassigned mentor count per department
foreach ($departments as $deptId => &$deptInfo) {
    $deptInfo['unassigned_mentors'] = max(0, $deptInfo['total_mentors'] - $deptInfo['assigned_mentors']);
}
unset($deptInfo);

// 6. Calculate Institutional KPIs
$totalHods = count($hods);
$totalAssignedMentors = count($assignedMentorIds);
$totalUnassignedMentors = count($unassignedMentors);
$zeroMentorHodsCount = 0;
foreach ($hods as $h) {
    if (empty($h['assigned_mentors'])) {
        $zeroMentorHodsCount++;
    }
}

$deptsWithoutHodCount = 0;
$deptsWithoutMentorsCount = 0;
$coveredDepartmentsCount = 0;
foreach ($departments as $d) {
    if (empty($d['hods'])) {
        $deptsWithoutHodCount++;
    }
    if ($d['assigned_mentors'] === 0) {
        $deptsWithoutMentorsCount++;
    }
    if ($d['assigned_mentors'] > 0 || !empty($d['hods'])) {
        $coveredDepartmentsCount++;
    }
}
?>

<!-- Scoped Institutional Design System Styles -->
<style>
/* Page Scope Container */
.erp-hod-monitor-wrap {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  color: #263445;
  background-color: #F5F7FA;
  padding-bottom: 30px;
}

/* Page Header */
.erp-header-panel {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 18px 24px;
  background: #FFFFFF;
  border-bottom: 1px solid #E1E7EF;
  margin-bottom: 20px;
}
.erp-header-title-block {
  max-width: 65%;
}
.erp-breadcrumb-nav {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #68778A;
  margin-bottom: 6px;
}
.erp-breadcrumb-nav a {
  color: #68778A;
  text-decoration: none;
  transition: color 0.15s;
}
.erp-breadcrumb-nav a:hover {
  color: #243B64;
}
.erp-breadcrumb-nav .sep {
  color: #CBD5E1;
}
.erp-breadcrumb-nav .current {
  color: #243B64;
  font-weight: 600;
}
.erp-main-title {
  font-size: 20px;
  font-weight: 700;
  color: #263445;
  margin: 0 0 4px 0;
  display: flex;
  align-items: center;
  gap: 8px;
}
.erp-main-title i {
  color: #243B64;
  font-size: 19px;
}
.erp-main-subtitle {
  font-size: 13px;
  color: #68778A;
  margin: 0;
  line-height: 1.4;
}
.erp-header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}
.btn-erp-primary {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background-color: #243B64 !important;
  color: #FFFFFF !important;
  border: 1px solid #1E3152 !important;
  border-radius: 6px;
  padding: 8px 16px;
  font-size: 13px;
  font-weight: 600;
  line-height: 1.4;
  text-decoration: none !important;
  transition: background-color 0.15s, border-color 0.15s;
  box-shadow: 0 1px 2px rgba(16, 24, 40, 0.05);
  cursor: pointer;
}
.btn-erp-primary:hover {
  background-color: #1A2B49 !important;
  border-color: #15233B !important;
  color: #FFFFFF !important;
}
.btn-erp-neutral {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background-color: #FFFFFF !important;
  color: #263445 !important;
  border: 1px solid #D0D5DD !important;
  border-radius: 6px;
  padding: 8px 14px;
  font-size: 13px;
  font-weight: 500;
  line-height: 1.4;
  text-decoration: none !important;
  transition: background-color 0.15s, border-color 0.15s;
  box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
  cursor: pointer;
}
.btn-erp-neutral:hover {
  background-color: #F8FAFC !important;
  border-color: #CBD5E1 !important;
  color: #0F172A !important;
}

/* Statistics Grid */
.erp-stats-grid {
  display: grid;
  grid-template-columns: repeat(6, minmax(130px, 1fr));
  gap: 12px;
  margin-bottom: 20px;
  padding: 0 24px;
}
@media (max-width: 1280px) {
  .erp-stats-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
  }
}
@media (max-width: 768px) {
  .erp-stats-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    padding: 0 16px;
  }
}
@media (max-width: 480px) {
  .erp-stats-grid {
    grid-template-columns: 1fr;
  }
}
.erp-stat-card {
  background: #FFFFFF;
  border: 1px solid #E1E7EF;
  border-radius: 8px;
  padding: 14px 16px;
  box-shadow: 0 1px 2px rgba(16, 24, 40, 0.03);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 120px;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.erp-stat-card:hover {
  border-color: #CBD5E1;
  box-shadow: 0 2px 4px rgba(16, 24, 40, 0.05);
}
.erp-stat-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}
.erp-stat-icon-wrap {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  background-color: #F0F4F8;
  color: #243B64;
}
.erp-stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #263445;
  line-height: 1.1;
  letter-spacing: -0.02em;
  margin-bottom: 4px;
}
.erp-stat-label {
  font-size: 12px;
  font-weight: 600;
  color: #475467;
  margin: 0;
  line-height: 1.35;
  white-space: normal !important;
  word-break: normal !important;
  overflow: visible !important;
  text-overflow: clip !important;
  text-transform: none !important;
}
.erp-stat-subtext {
  font-size: 11px;
  color: #94A3B8;
  margin-top: 4px;
  display: flex;
  align-items: center;
  gap: 4px;
  white-space: normal !important;
  line-height: 1.3;
}
.erp-stat-subtext.text-accent-success { color: #027A48; }
.erp-stat-subtext.text-accent-warning { color: #B54708; }
.erp-stat-subtext.text-accent-danger { color: #B42318; }

/* Filter Toolbar */
.erp-filter-panel {
  background: #FFFFFF;
  border: 1px solid #E1E7EF;
  border-radius: 8px;
  padding: 16px 20px;
  margin: 0 24px 20px 24px;
  box-shadow: 0 1px 2px rgba(16, 24, 40, 0.03);
}
.erp-filter-grid {
  display: grid;
  grid-template-columns: 2.2fr 2fr 1.8fr 2fr;
  gap: 16px;
  align-items: flex-end;
}
.erp-filter-item {
  display: flex;
  flex-direction: column;
}
.erp-filter-label {
  font-size: 12px;
  font-weight: 600;
  color: #344054;
  margin-bottom: 6px;
}
.erp-form-control {
  height: 38px;
  padding: 8px 12px;
  font-size: 13px;
  color: #263445;
  background-color: #FFFFFF;
  border: 1px solid #D0D5DD;
  border-radius: 6px;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  width: 100%;
  box-sizing: border-box;
}
.erp-form-control:focus {
  border-color: #315A91;
  box-shadow: 0 0 0 3px rgba(49, 90, 145, 0.12);
}
.erp-search-group {
  display: flex;
  position: relative;
  width: 100%;
}
.erp-search-group .search-icon {
  position: absolute;
  left: 12px;
  top: 11px;
  color: #98A2B3;
  font-size: 14px;
  pointer-events: none;
  z-index: 2;
}
.erp-search-group input {
  padding-left: 34px !important;
  border-radius: 6px 0 0 6px !important;
}
.erp-search-group .btn-clear {
  border-radius: 0 6px 6px 0 !important;
  border-left: 0 !important;
  background: #FFFFFF;
  border: 1px solid #D0D5DD;
  color: #68778A;
  padding: 0 12px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
}
.erp-search-group .btn-clear:hover {
  background: #F8FAFC;
  color: #263445;
}

/* Registry Card & Table */
.erp-table-card {
  background: #FFFFFF;
  border: 1px solid #E1E7EF;
  border-radius: 8px;
  margin: 0 24px 24px 24px;
  box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
  overflow: hidden;
}
.erp-table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 20px;
  border-bottom: 1px solid #E1E7EF;
  background: #FFFFFF;
}
.erp-table-title {
  font-size: 15px;
  font-weight: 700;
  color: #263445;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
}
.erp-table-title i {
  color: #243B64;
}
.erp-table-count-badge {
  background: #F2F4F7;
  color: #344054;
  border: 1px solid #E4E7EC;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  padding: 3px 10px;
}
.erp-registry-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 13px;
  margin-bottom: 0;
}
.erp-registry-table thead th {
  background: #F8FAFC;
  color: #475467;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 12px 14px;
  border-bottom: 1px solid #E1E7EF;
  border-top: none;
  white-space: nowrap;
}
.erp-registry-table tbody tr {
  transition: background-color 0.12s;
}
.erp-registry-table tbody tr:hover {
  background-color: #F8FAFC;
}
.erp-registry-table tbody td {
  padding: 12px 14px;
  border-bottom: 1px solid #F2F4F7;
  border-top: none;
  color: #263445;
  vertical-align: middle;
}

/* Status Badges */
.erp-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 4px;
  line-height: 1.3;
}
.erp-badge-success {
  background-color: #ECFDF3;
  color: #027A48;
  border: 1px solid #A6F4C5;
}
.erp-badge-warning {
  background-color: #FFFAEB;
  color: #B54708;
  border: 1px solid #FEDF89;
}
.erp-badge-neutral {
  background-color: #F2F4F7;
  color: #344054;
  border: 1px solid #E4E7EC;
}
.erp-badge-info {
  background-color: #EFF8FF;
  color: #175CD3;
  border: 1px solid #B2DDFF;
}

.dept-code-tag {
  display: inline-block;
  background: #F2F4F7;
  color: #344054;
  border: 1px solid #E4E7EC;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  padding: 2px 6px;
}

/* Action Buttons */
.erp-action-btn-group {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.btn-erp-action {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  font-size: 11px;
  font-weight: 500;
  border-radius: 4px;
  color: #344054;
  background: #FFFFFF;
  border: 1px solid #D0D5DD;
  text-decoration: none !important;
  transition: all 0.12s;
  cursor: pointer;
  line-height: 1.4;
}
.btn-erp-action:hover:not(:disabled) {
  background: #F8FAFC;
  color: #1D2939;
  border-color: #CBD5E1;
}
.btn-erp-action:disabled,
.btn-erp-action.disabled {
  opacity: 0.45;
  cursor: not-allowed;
  background: #F8FAFC;
  border-color: #EAECF0;
  color: #98A2B3;
}
.btn-erp-action-primary {
  background: #243B64 !important;
  color: #FFFFFF !important;
  border-color: #1E3152 !important;
  font-weight: 600;
}
.btn-erp-action-primary:hover {
  background: #1A2B49 !important;
  border-color: #15233B !important;
  color: #FFFFFF !important;
}

/* Modals */
.erp-modal-header {
  background: #243B64 !important;
  color: #FFFFFF !important;
  padding: 16px 20px !important;
  border-bottom: 1px solid #1E3152 !important;
}
.erp-modal-header .modal-title {
  font-size: 16px;
  font-weight: 600;
  color: #FFFFFF;
  margin: 0;
}
.erp-modal-header .close {
  color: #FFFFFF !important;
  opacity: 0.85;
  text-shadow: none;
}
.erp-modal-header .close:hover {
  opacity: 1;
}

/* Responsive Breakpoints */
@media (max-width: 1200px) {
  .erp-stats-grid {
    grid-template-columns: repeat(3, 1fr);
  }
  .erp-filter-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media (max-width: 768px) {
  .erp-header-panel {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 16px;
  }
  .erp-header-title-block {
    max-width: 100%;
  }
  .erp-stats-grid {
    grid-template-columns: repeat(2, 1fr);
    padding: 0 16px;
    gap: 10px;
  }
  .erp-filter-panel {
    margin: 0 16px 16px 16px;
    padding: 14px 16px;
  }
  .erp-filter-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  .erp-table-card {
    margin: 0 16px 20px 16px;
  }
}
@media (max-width: 480px) {
  .erp-stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="content-wrapper erp-hod-monitor-wrap">
  <!-- Redesigned Institutional Page Header -->
  <div class="erp-header-panel">
    <div class="erp-header-title-block">
      <div class="erp-breadcrumb-nav">
        <a href="index.php"><i class="fa fa-dashboard"></i> Dashboard</a>
        <span class="sep">&rsaquo;</span>
        <a href="branch_info.php">Administration</a>
        <span class="sep">&rsaquo;</span>
        <span class="current">Department & HOD Monitoring</span>
      </div>
      <h1 class="erp-main-title">
        <i class="fa fa-sitemap"></i> Department & HOD Monitoring
      </h1>
      <p class="erp-main-subtitle">
        Manage departmental leadership, faculty mentors and allocation status.
      </p>
    </div>
    <div class="erp-header-actions">
      <a href="coordinator_allocation.php" class="btn-erp-primary">
        <i class="fa fa-exchange"></i> Manage Allocations
      </a>
      <button type="button" class="btn-erp-neutral" onclick="window.location.reload();">
        <i class="fa fa-refresh"></i> Refresh
      </button>
    </div>
  </div>

  <?php if ($filterDepartmentId > 0 && isset($departments[$filterDepartmentId])): ?>
    <div style="padding: 0 24px; margin-bottom: 16px;">
      <div class="alert alert-info" style="border-radius: 6px; margin-bottom: 0; background-color: #EFF8FF; border-color: #B2DDFF; color: #175CD3;">
        <i class="fa fa-filter"></i> Currently filtered by department: 
        <strong><?php echo htmlspecialchars($departments[$filterDepartmentId]['name']); ?> (<?php echo htmlspecialchars($departments[$filterDepartmentId]['program']); ?>)</strong>
        <a href="hod_mentor_allocation.php" class="btn btn-xs btn-default pull-right" style="margin-top: -2px; border-color: #B2DDFF; color: #175CD3;">
          <i class="fa fa-times"></i> Clear Filter & View All Departments
        </a>
      </div>
    </div>
  <?php endif; ?>

  <!-- 6 Uniform Institutional Statistics Cards -->
  <div class="erp-stats-grid">
    <!-- 1. Total Departments -->
    <div class="erp-stat-card">
      <div class="erp-stat-top">
        <div class="erp-stat-icon-wrap"><i class="fa fa-university"></i></div>
        <span class="erp-badge erp-badge-neutral"><?php echo $coveredDepartmentsCount; ?>/<?php echo count($departments); ?> active</span>
      </div>
      <div class="erp-stat-value"><?php echo count($departments); ?></div>
      <div class="erp-stat-label">Total Departments</div>
      <div class="erp-stat-subtext">Recognized academic units</div>
    </div>

    <!-- 2. Assigned HODs -->
    <div class="erp-stat-card">
      <div class="erp-stat-top">
        <div class="erp-stat-icon-wrap"><i class="fa fa-user"></i></div>
        <?php if ($totalHods > 0): ?>
          <span class="erp-badge erp-badge-success"><i class="fa fa-check"></i> Assigned</span>
        <?php else: ?>
          <span class="erp-badge erp-badge-warning">0 Appointed</span>
        <?php endif; ?>
      </div>
      <div class="erp-stat-value"><?php echo number_format($totalHods); ?></div>
      <div class="erp-stat-label">Assigned HODs</div>
      <div class="erp-stat-subtext">Department leaders (Role 3)</div>
    </div>

    <!-- 3. Total Mentors -->
    <div class="erp-stat-card">
      <div class="erp-stat-top">
        <div class="erp-stat-icon-wrap"><i class="fa fa-users"></i></div>
        <span class="erp-badge erp-badge-neutral">Role 4</span>
      </div>
      <div class="erp-stat-value"><?php echo number_format($totalMentors); ?></div>
      <div class="erp-stat-label">Total Mentors</div>
      <div class="erp-stat-subtext">Registered faculty staff</div>
    </div>

    <!-- 4. Assigned Mentors -->
    <div class="erp-stat-card">
      <div class="erp-stat-top">
        <div class="erp-stat-icon-wrap"><i class="fa fa-check-circle"></i></div>
        <?php if ($totalAssignedMentors > 0): ?>
          <span class="erp-badge erp-badge-success">Mapped</span>
        <?php else: ?>
          <span class="erp-badge erp-badge-neutral">Pending</span>
        <?php endif; ?>
      </div>
      <div class="erp-stat-value"><?php echo number_format($totalAssignedMentors); ?></div>
      <div class="erp-stat-label">Assigned Mentors</div>
      <div class="erp-stat-subtext text-accent-success"><i class="fa fa-link"></i> Linked in mapping</div>
    </div>

    <!-- 5. Unassigned Mentors -->
    <div class="erp-stat-card">
      <div class="erp-stat-top">
        <div class="erp-stat-icon-wrap"><i class="fa fa-exclamation-circle"></i></div>
        <?php if ($totalUnassignedMentors > 0): ?>
          <span class="erp-badge erp-badge-warning">Action needed</span>
        <?php else: ?>
          <span class="erp-badge erp-badge-success">Optimal</span>
        <?php endif; ?>
      </div>
      <div class="erp-stat-value"><?php echo number_format($totalUnassignedMentors); ?></div>
      <div class="erp-stat-label">Unassigned Mentors</div>
      <div class="erp-stat-subtext text-accent-warning"><i class="fa fa-clock-o"></i> Awaiting allocation</div>
    </div>

    <!-- 6. Departments Without HOD -->
    <div class="erp-stat-card">
      <div class="erp-stat-top">
        <div class="erp-stat-icon-wrap"><i class="fa fa-user-times"></i></div>
        <?php if ($deptsWithoutHodCount > 0): ?>
          <span class="erp-badge erp-badge-warning">Vacant</span>
        <?php else: ?>
          <span class="erp-badge erp-badge-success">All filled</span>
        <?php endif; ?>
      </div>
      <div class="erp-stat-value"><?php echo number_format($deptsWithoutHodCount); ?></div>
      <div class="erp-stat-label">Departments Without HOD</div>
      <div class="erp-stat-subtext text-accent-warning">Requires HOD appointment</div>
    </div>
  </div>

  <!-- Redesigned Filter & Search Panel -->
  <div class="erp-filter-panel">
    <div class="erp-filter-grid">
      <!-- Department Filter -->
      <div class="erp-filter-item">
        <label for="filter_department" class="erp-filter-label">Department</label>
        <select id="filter_department" class="erp-form-control">
          <option value="">All Departments (<?php echo count($departments); ?>)</option>
          <?php foreach ($departments as $d): ?>
            <option value="<?php echo htmlspecialchars((string)$d['id']); ?>" <?php echo $filterDepartmentId === (int)$d['id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($d['name']); ?> (<?php echo count($d['hods']); ?> HODs, <?php echo $d['total_mentors']; ?> Mentors)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- HOD Filter -->
      <div class="erp-filter-item">
        <label for="filter_hod" class="erp-filter-label">HOD / Lead</label>
        <select id="filter_hod" class="erp-form-control">
          <option value="">All HODs / Leads</option>
          <?php foreach ($hods as $h): ?>
            <option value="<?php echo htmlspecialchars((string)$h['user_id']); ?>" data-dept="<?php echo htmlspecialchars((string)$h['department_id']); ?>">
              <?php echo htmlspecialchars($h['name']); ?> [<?php echo htmlspecialchars($h['department_name']); ?>]
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Allocation Status Filter -->
      <div class="erp-filter-item">
        <label for="filter_status" class="erp-filter-label">Allocation Status</label>
        <select id="filter_status" class="erp-form-control">
          <option value="">All Statuses</option>
          <option value="assigned">Assigned (Mentors &gt; 0)</option>
          <option value="zero">Zero Mentors Assigned</option>
          <option value="no_hod">Departments Without HOD</option>
        </select>
      </div>

      <!-- Global Live Search -->
      <div class="erp-filter-item">
        <label for="filter_search" class="erp-filter-label">Live Search</label>
        <div class="erp-search-group">
          <i class="fa fa-search search-icon"></i>
          <input type="text" id="filter_search" class="erp-form-control" placeholder="Search HOD, mentor, department...">
          <button id="btn_clear_filters" class="btn-clear" type="button" title="Clear all filters">
            <i class="fa fa-times"></i> Clear
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Redesigned Department & HOD Allocation Registry Table -->
  <div class="erp-table-card">
    <div class="erp-table-header">
      <h3 class="erp-table-title">
        <i class="fa fa-list"></i> Department & HOD Allocation Registry
      </h3>
      <span id="table_record_count" class="erp-table-count-badge">
        Showing <?php echo count($departments); ?> Departments
      </span>
    </div>

    <div class="table-responsive" style="padding: 0; overflow-x: auto;">
      <table id="hodAllocationTable" class="erp-registry-table">
        <thead>
          <tr>
            <th style="width: 44px; text-align: center;">#</th>
            <th style="width: 260px;">Department Name</th>
            <th style="width: 90px; text-align: center;">Code</th>
            <th style="width: 220px;">HOD / Lead</th>
            <th style="width: 130px; text-align: center;">HOD Status</th>
            <th style="width: 90px; text-align: center;">Assigned</th>
            <th style="width: 90px; text-align: center;">Unassigned</th>
            <th style="width: 140px; text-align: center;">Active Students</th>
            <th style="width: 200px; text-align: center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $rowIdx = 0;
          foreach ($departments as $deptId => $dept):
            $deptHods = $dept['hods'];
            if (empty($deptHods)):
              $rowIdx++;
          ?>
            <tr class="allocation-row" data-dept="<?php echo $deptId; ?>" data-hod="0" data-status="no_hod" data-search="<?php echo htmlspecialchars(strtolower($dept['name'] . ' ' . $dept['program'] . ' no hod vacant')); ?>">
              <td style="text-align: center; color: #98A2B3;"><?php echo $rowIdx; ?></td>
              <td>
                <div style="font-weight: 600; color: #263445; line-height: 1.4;"><?php echo htmlspecialchars($dept['program']); ?></div>
              </td>
              <td style="text-align: center;">
                <span class="dept-code-tag"><?php echo htmlspecialchars($dept['name']); ?></span>
              </td>
              <td>
                <span class="erp-badge erp-badge-warning">
                  <i class="fa fa-clock-o"></i> Vacant
                </span>
              </td>
              <td style="text-align: center;">
                <span class="erp-badge erp-badge-neutral">No HOD</span>
              </td>
              <td style="text-align: center; color: #98A2B3;">0</td>
              <td style="text-align: center;">
                <?php if ($dept['unassigned_mentors'] > 0): ?>
                  <span class="erp-badge erp-badge-warning"><?php echo $dept['unassigned_mentors']; ?></span>
                <?php else: ?>
                  <span style="color: #98A2B3;">0</span>
                <?php endif; ?>
              </td>
              <td style="text-align: center;">
                <strong style="color: #027A48; font-weight: 600;"><?php echo $dept['active_students']; ?></strong>
                <span style="font-size: 11px; color: #68778A;">(<?php echo $dept['registered_students']; ?> Reg)</span>
              </td>
              <td style="text-align: center; white-space: nowrap;">
                <div class="erp-action-btn-group">
                  <button type="button" class="btn-erp-action" data-toggle="modal" data-target="#deptModal_<?php echo $deptId; ?>" title="View Department Information">
                    <i class="fa fa-building-o"></i> Dept
                  </button>
                  <button type="button" class="btn-erp-action disabled" title="No HOD assigned" disabled>
                    <i class="fa fa-user-o"></i> HOD
                  </button>
                  <button type="button" class="btn-erp-action disabled" title="No mentors assigned" disabled>
                    <i class="fa fa-users"></i> Mentors
                  </button>
                  <a href="coordinator_register.php?department_id=<?php echo $deptId; ?>" class="btn-erp-action btn-erp-action-primary" title="Assign HOD / Coordinator">
                    <i class="fa fa-plus"></i> Add
                  </a>
                </div>
              </td>
            </tr>

          <?php
            else:
              foreach ($deptHods as $hod):
                $rowIdx++;
                $assignedCount = count($hod['assigned_mentors']);
                $statusKey = ($assignedCount > 0) ? 'assigned' : 'zero';
                $mentorNames = array_map(function($m) { return $m['name']; }, $hod['assigned_mentors']);
                $searchPayload = strtolower($dept['name'] . ' ' . $dept['program'] . ' ' . $hod['name'] . ' ' . $hod['username'] . ' ' . $hod['email'] . ' ' . implode(' ', $mentorNames));
          ?>
            <tr class="allocation-row" data-dept="<?php echo $deptId; ?>" data-hod="<?php echo $hod['user_id']; ?>" data-status="<?php echo $statusKey; ?>" data-search="<?php echo htmlspecialchars($searchPayload); ?>">
              <td style="text-align: center; color: #98A2B3;"><?php echo $rowIdx; ?></td>
              <td>
                <div style="font-weight: 600; color: #263445; line-height: 1.4;"><?php echo htmlspecialchars($dept['program']); ?></div>
              </td>
              <td style="text-align: center;">
                <span class="dept-code-tag"><?php echo htmlspecialchars($dept['name']); ?></span>
              </td>
              <td>
                <div style="font-weight: 600; color: #263445;"><?php echo htmlspecialchars($hod['name']); ?></div>
                <div style="font-size: 11px; color: #68778A;">@<?php echo htmlspecialchars($hod['username']); ?></div>
              </td>
              <td style="text-align: center;">
                <?php if ($hod['is_verified_hod']): ?>
                  <span class="erp-badge erp-badge-success"><i class="fa fa-check-circle"></i> Verified HOD</span>
                <?php else: ?>
                  <span class="erp-badge erp-badge-info"><i class="fa fa-user"></i> Coordinator</span>
                <?php endif; ?>
              </td>
              <td style="text-align: center;">
                <?php if ($assignedCount > 0): ?>
                  <strong style="color: #027A48; font-weight: 600;"><?php echo $assignedCount; ?></strong>
                <?php else: ?>
                  <span class="erp-badge erp-badge-warning">0</span>
                <?php endif; ?>
              </td>
              <td style="text-align: center;">
                <?php if ($dept['unassigned_mentors'] > 0): ?>
                  <span class="erp-badge erp-badge-warning"><?php echo $dept['unassigned_mentors']; ?></span>
                <?php else: ?>
                  <span style="color: #98A2B3;">0</span>
                <?php endif; ?>
              </td>
              <td style="text-align: center;">
                <strong style="color: #027A48; font-weight: 600;"><?php echo $dept['active_students']; ?></strong>
                <span style="font-size: 11px; color: #68778A;">(<?php echo $dept['registered_students']; ?> Reg)</span>
              </td>
              <td style="text-align: center; white-space: nowrap;">
                <div class="erp-action-btn-group">
                  <button type="button" class="btn-erp-action" data-toggle="modal" data-target="#deptModal_<?php echo $deptId; ?>" title="View Department Information">
                    <i class="fa fa-building-o"></i> Dept
                  </button>
                  <button type="button" class="btn-erp-action" data-toggle="modal" data-target="#hodDetailsModal_<?php echo $hod['user_id']; ?>" title="View HOD Details">
                    <i class="fa fa-user-o"></i> HOD
                  </button>
                  <button type="button" class="btn-erp-action" data-toggle="modal" data-target="#hodMentorsModal_<?php echo $hod['user_id']; ?>" title="View Assigned Mentors">
                    <i class="fa fa-users"></i> Mentors
                  </button>
                  <a href="coordinator_allocation.php?department_id=<?php echo $deptId; ?>" class="btn-erp-action btn-erp-action-primary" title="Manage Mentor Allocation">
                    <i class="fa fa-exchange"></i> Allocate
                  </a>
                </div>
              </td>
            </tr>
          <?php
              endforeach;
            endif;
          endforeach;
          ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Unassigned Mentors Section -->
  <div class="erp-table-card" style="margin-top: 24px;">
    <div class="erp-table-header" style="background: #FFFDF5; border-bottom: 1px solid #FEF08A;">
      <h3 class="erp-table-title" style="color: #854D0E;">
        <i class="fa fa-exclamation-triangle" style="color: #D97706;"></i> Unassigned Mentors Awaiting Allocation
      </h3>
      <span class="erp-badge erp-badge-warning" style="font-size: 12px;">
        <?php echo count($unassignedMentors); ?> Unassigned
      </span>
    </div>

    <div style="padding: 16px 20px;">
      <?php if (!empty($unassignedMentors)): ?>
        <p style="font-size: 13px; color: #78350F; margin: 0 0 16px 0;">
          The following faculty mentors are registered in <code>st_user_master</code> (Role 4) but currently have no department coordinator or HOD assigned in <code>st_coordinator_mentor</code>.
        </p>
        <div class="table-responsive" style="overflow-x: auto;">
          <table class="erp-registry-table">
            <thead>
              <tr>
                <th style="width: 44px; text-align: center;">#</th>
                <th>Mentor Name</th>
                <th>Contact Information</th>
                <th>Department</th>
                <th style="text-align: center;">Students Assigned</th>
                <th style="text-align: center;">Subjects Mapped</th>
                <th style="width: 220px; text-align: center;">Status</th>
                <th style="width: 110px; text-align: center;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($unassignedMentors as $uIdx => $uMentor): ?>
                <tr>
                  <td style="text-align: center; color: #98A2B3;"><?php echo ($uIdx + 1); ?></td>
                  <td>
                    <div style="font-weight: 600; color: #263445;"><?php echo htmlspecialchars($uMentor['name']); ?></div>
                    <div style="font-size: 11px; color: #68778A;">@<?php echo htmlspecialchars($uMentor['username']); ?></div>
                  </td>
                  <td>
                    <?php if (!empty($uMentor['email'])): ?>
                      <div style="font-size: 12px;"><i class="fa fa-envelope-o text-muted" style="margin-right: 4px;"></i> <?php echo htmlspecialchars($uMentor['email']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($uMentor['phone'])): ?>
                      <div style="font-size: 12px; color: #68778A;"><i class="fa fa-phone text-muted" style="margin-right: 4px;"></i> <?php echo htmlspecialchars($uMentor['phone']); ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="dept-code-tag"><?php echo htmlspecialchars($uMentor['department_name']); ?></span>
                  </td>
                  <td style="text-align: center; font-weight: 600; color: #263445;">
                    <?php echo $uMentor['student_count']; ?>
                  </td>
                  <td style="text-align: center; font-weight: 600; color: #263445;">
                    <?php echo $uMentor['subject_count']; ?>
                  </td>
                  <td style="text-align: center;">
                    <span class="erp-badge erp-badge-warning">
                      <i class="fa fa-unlink"></i> Awaiting HOD Link
                    </span>
                  </td>
                  <td style="text-align: center;">
                    <a href="coordinator_allocation.php" class="btn-erp-action btn-erp-action-primary">
                      <i class="fa fa-exchange"></i> Assign
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div style="text-align: center; padding: 28px 0; color: #027A48;">
          <i class="fa fa-check-circle" style="font-size: 32px; margin-bottom: 8px;"></i>
          <div style="font-size: 15px; font-weight: 600;">Zero Unassigned Mentors</div>
          <div style="font-size: 13px; color: #68778A; margin-top: 2px;">All registered faculty mentors have been successfully allocated to a department coordinator or HOD.</div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ============================================================= -->
  <!-- Modals Section (Rendered with Institutional ERP Styling)       -->
  <!-- ============================================================= -->
  <?php foreach ($departments as $deptId => $dept): ?>
    <!-- Modal for Department Details -->
    <div class="modal fade" id="deptModal_<?php echo $deptId; ?>" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: 8px; overflow: hidden; border: 1px solid #CBD5E1; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
          <div class="modal-header erp-modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title"><i class="fa fa-building-o" style="margin-right: 6px;"></i> Department Information: <?php echo htmlspecialchars($dept['name']); ?></h4>
          </div>
          <div class="modal-body" style="padding: 20px;">
            <table class="table table-bordered" style="font-size: 13px; margin-bottom: 0;">
              <tr><th style="width: 35%; background: #F8FAFC; color: #475467;">Department Code</th><td><span class="dept-code-tag"><?php echo htmlspecialchars($dept['name']); ?></span></td></tr>
              <tr><th style="background: #F8FAFC; color: #475467;">Program Name</th><td><strong><?php echo htmlspecialchars($dept['program']); ?></strong></td></tr>
              <tr><th style="background: #F8FAFC; color: #475467;">Department ID</th><td>#<?php echo $deptId; ?></td></tr>
              <tr><th style="background: #F8FAFC; color: #475467;">Active Students</th><td><strong style="color: #027A48;"><?php echo $dept['active_students']; ?></strong> (<?php echo $dept['registered_students']; ?> Registered)</td></tr>
              <tr>
                <th style="background: #F8FAFC; color: #475467;">HOD Status</th>
                <td>
                  <?php if (empty($dept['hods'])): ?>
                    <span class="erp-badge erp-badge-warning"><i class="fa fa-clock-o"></i> Vacant / Not Configured</span>
                  <?php else: ?>
                    <?php 
                      $hNames = array_map(function($h) { return $h['name']; }, $dept['hods']);
                      echo htmlspecialchars(implode(', ', $hNames));
                    ?>
                  <?php endif; ?>
                </td>
              </tr>
              <tr>
                <th style="background: #F8FAFC; color: #475467;">Total Mentors</th>
                <td><?php echo $dept['total_mentors']; ?> (Assigned: <?php echo array_sum(array_map(function($h) { return count($h['assigned_mentors']); }, $dept['hods'])); ?>, Unassigned: <?php echo $dept['unassigned_mentors']; ?>)</td>
              </tr>
            </table>
          </div>
          <div class="modal-footer" style="background: #F8FAFC; border-top: 1px solid #E1E7EF; padding: 12px 20px;">
            <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Close</button>
            <?php if (empty($dept['hods'])): ?>
              <a href="coordinator_register.php?department_id=<?php echo $deptId; ?>" class="btn-erp-primary" style="font-size: 12px; padding: 6px 14px;">Assign HOD</a>
            <?php else: ?>
              <a href="coordinator_allocation.php?department_id=<?php echo $deptId; ?>" class="btn-erp-primary" style="font-size: 12px; padding: 6px 14px;">Manage Allocation</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <?php if (!empty($dept['hods'])): ?>
      <?php foreach ($dept['hods'] as $hod): 
        $assignedCount = count($hod['assigned_mentors']);
      ?>
        <!-- Modal for HOD Details -->
        <div class="modal fade" id="hodDetailsModal_<?php echo $hod['user_id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content" style="border-radius: 8px; overflow: hidden; border: 1px solid #CBD5E1; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
              <div class="modal-header erp-modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-user-o" style="margin-right: 6px;"></i> HOD Profile: <?php echo htmlspecialchars($hod['name']); ?></h4>
              </div>
              <div class="modal-body" style="padding: 20px;">
                <table class="table table-bordered" style="font-size: 13px; margin-bottom: 0;">
                  <tr><th style="width: 35%; background: #F8FAFC; color: #475467;">Staff Name</th><td><strong><?php echo htmlspecialchars($hod['name']); ?></strong></td></tr>
                  <tr><th style="background: #F8FAFC; color: #475467;">Username</th><td>@<?php echo htmlspecialchars($hod['username']); ?></td></tr>
                  <tr><th style="background: #F8FAFC; color: #475467;">Department</th><td><?php echo htmlspecialchars($dept['name']); ?> (<?php echo htmlspecialchars($dept['program']); ?>)</td></tr>
                  <tr><th style="background: #F8FAFC; color: #475467;">Email</th><td><?php echo htmlspecialchars($hod['email'] ?: 'N/A'); ?></td></tr>
                  <tr><th style="background: #F8FAFC; color: #475467;">Phone</th><td><?php echo htmlspecialchars($hod['phone'] ?: 'N/A'); ?></td></tr>
                  <tr>
                    <th style="background: #F8FAFC; color: #475467;">Appointment Status</th>
                    <td>
                      <?php if ($hod['is_verified_hod']): ?>
                        <span class="erp-badge erp-badge-success"><i class="fa fa-check-circle"></i> Officially Appointed HOD</span>
                      <?php else: ?>
                        <span class="erp-badge erp-badge-info"><i class="fa fa-user"></i> Department Coordinator (Role 3)</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php if (!empty($hod['appointment_reference'])): ?>
                    <tr><th style="background: #F8FAFC; color: #475467;">Appointment Ref</th><td><code><?php echo htmlspecialchars($hod['appointment_reference']); ?></code></td></tr>
                  <?php endif; ?>
                  <tr><th style="background: #F8FAFC; color: #475467;">Total Mentors Supervised</th><td><strong><?php echo $assignedCount; ?></strong></td></tr>
                </table>
              </div>
              <div class="modal-footer" style="background: #F8FAFC; border-top: 1px solid #E1E7EF; padding: 12px 20px;">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal for HOD Assigned Mentors Details -->
        <div class="modal fade" id="hodMentorsModal_<?php echo $hod['user_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="modalLabel_<?php echo $hod['user_id']; ?>" aria-hidden="true">
          <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content" style="border-radius: 8px; overflow: hidden; border: 1px solid #CBD5E1; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
              <div class="modal-header erp-modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modalLabel_<?php echo $hod['user_id']; ?>">
                  <i class="fa fa-sitemap" style="margin-right: 6px;"></i>
                  Assigned Mentors for <?php echo htmlspecialchars($hod['name']); ?> (<?php echo htmlspecialchars($dept['name']); ?>)
                </h4>
              </div>
              <div class="modal-body" style="padding: 20px;">
                <!-- HOD Information Banner -->
                <div style="background: #F8FAFC; border: 1px solid #E1E7EF; border-radius: 6px; padding: 14px 18px; margin-bottom: 20px;">
                  <div class="row">
                    <div class="col-sm-4">
                      <div style="font-size: 11px; text-transform: uppercase; color: #68778A; font-weight: 600;">HOD / Academic Lead</div>
                      <div style="font-size: 14px; font-weight: 700; color: #263445; margin-top: 2px;"><?php echo htmlspecialchars($hod['name']); ?></div>
                      <div style="font-size: 12px; color: #68778A;">Username: @<?php echo htmlspecialchars($hod['username']); ?></div>
                    </div>
                    <div class="col-sm-4">
                      <div style="font-size: 11px; text-transform: uppercase; color: #68778A; font-weight: 600;">Department & Status</div>
                      <div style="font-size: 13px; font-weight: 600; color: #263445; margin-top: 2px;"><?php echo htmlspecialchars($dept['name']); ?> &bull; <?php echo htmlspecialchars($dept['program']); ?></div>
                      <div style="font-size: 12px; margin-top: 4px;">
                        <?php if ($hod['is_verified_hod']): ?>
                          <span class="erp-badge erp-badge-success">Institutional Verified HOD</span>
                        <?php else: ?>
                          <span class="erp-badge erp-badge-info">Department Coordinator (Role 3)</span>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="col-sm-4">
                      <div style="font-size: 11px; text-transform: uppercase; color: #68778A; font-weight: 600;">Allocation Summary</div>
                      <div style="font-size: 18px; font-weight: 700; color: <?php echo ($assignedCount > 0 ? '#027A48' : '#B42318'); ?>; margin-top: 2px;">
                        <?php echo $assignedCount; ?> Mentors Assigned
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Mentors Table -->
                <h5 style="font-size: 14px; font-weight: 700; color: #263445; margin-bottom: 12px;">
                  <i class="fa fa-users" style="margin-right: 6px; color: #243B64;"></i> Assigned Faculty Mentors
                </h5>
                <?php if ($assignedCount > 0): ?>
                  <div class="table-responsive" style="overflow-x: auto;">
                    <table class="erp-registry-table">
                      <thead>
                        <tr>
                          <th style="width: 40px; text-align: center;">#</th>
                          <th>Mentor Name</th>
                          <th>Contact Information</th>
                          <th>Department</th>
                          <th style="text-align: center;">Students Mentored</th>
                          <th style="text-align: center;">Subjects Mapped</th>
                          <th style="text-align: center;">Assigned Date</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($hod['assigned_mentors'] as $mIdx => $m): ?>
                          <tr>
                            <td style="text-align: center; color: #98A2B3;"><?php echo ($mIdx + 1); ?></td>
                            <td>
                              <div style="font-weight: 600; color: #263445;"><?php echo htmlspecialchars($m['name']); ?></div>
                              <div style="font-size: 11px; color: #68778A;">@<?php echo htmlspecialchars($m['username']); ?></div>
                            </td>
                            <td>
                              <?php if (!empty($m['email'])): ?>
                                <div style="font-size: 12px;"><i class="fa fa-envelope-o text-muted"></i> <?php echo htmlspecialchars($m['email']); ?></div>
                              <?php endif; ?>
                              <?php if (!empty($m['phone'])): ?>
                                <div style="font-size: 12px; color: #68778A;"><i class="fa fa-phone text-muted"></i> <?php echo htmlspecialchars($m['phone']); ?></div>
                              <?php endif; ?>
                            </td>
                            <td><span class="dept-code-tag"><?php echo htmlspecialchars($m['department_name']); ?></span></td>
                            <td style="text-align: center; font-weight: 600; color: #263445;">
                              <?php echo $m['student_count']; ?>
                            </td>
                            <td style="text-align: center; font-weight: 600; color: #263445;">
                              <?php echo $m['subject_count']; ?>
                            </td>
                            <td style="text-align: center; color: #68778A; font-size: 12px;">
                              <?php echo !empty($m['assigned_at']) ? htmlspecialchars(substr($m['assigned_at'], 0, 10)) : 'Recorded'; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="alert alert-warning" style="margin-bottom: 0; background-color: #FFFAEB; border-color: #FEDF89; color: #B54708;">
                    <i class="fa fa-exclamation-triangle"></i> No faculty mentors are currently assigned to this HOD in <code>st_coordinator_mentor</code>.
                    <div style="margin-top: 10px;">
                      <a href="coordinator_allocation.php" class="btn-erp-primary" style="font-size: 12px; padding: 6px 14px;">
                        <i class="fa fa-exchange"></i> Allocate Mentors Now
                      </a>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
              <div class="modal-footer" style="background: #F8FAFC; border-top: 1px solid #E1E7EF; padding: 12px 20px;">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Close</button>
                <a href="coordinator_allocation.php" class="btn-erp-primary" style="font-size: 12px; padding: 6px 14px;">
                  <i class="fa fa-pencil"></i> Modify Allocation
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php endforeach; ?>

</div>

<?php include "header/footer.php"; ?>

<script>
$(document).ready(function() {
  function applyFilters() {
    var deptFilter = $('#filter_department').val();
    var hodFilter = $('#filter_hod').val();
    var statusFilter = $('#filter_status').val();
    var searchFilter = $('#filter_search').val().toLowerCase().trim();

    var visibleRows = 0;

    $('.allocation-row').each(function() {
      var row = $(this);
      var rowDept = row.attr('data-dept');
      var rowHod = row.attr('data-hod');
      var rowStatus = row.attr('data-status');
      var rowSearch = row.attr('data-search') || '';

      var show = true;

      if (deptFilter && rowDept !== deptFilter) {
        show = false;
      }
      if (hodFilter && rowHod !== hodFilter) {
        show = false;
      }
      if (statusFilter && rowStatus !== statusFilter) {
        show = false;
      }
      if (searchFilter && rowSearch.indexOf(searchFilter) === -1) {
        show = false;
      }

      if (show) {
        row.show();
        visibleRows++;
      } else {
        row.hide();
      }
    });

    $('#table_record_count').text('Showing ' + visibleRows + ' Record(s)');
  }

  $('#filter_department, #filter_hod, #filter_status').on('change', applyFilters);
  $('#filter_search').on('keyup', applyFilters);

  $('#btn_clear_filters').on('click', function() {
    $('#filter_department').val('');
    $('#filter_hod').val('');
    $('#filter_status').val('');
    $('#filter_search').val('');
    applyFilters();
  });

  // If a department filter was passed via GET, trigger filter immediately
  if ($('#filter_department').val() !== '') {
    applyFilters();
  }
});
</script>
