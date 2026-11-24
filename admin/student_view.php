<?php
/**
 * TCET Specialization Tracker — Student Profile & Academic Progression View
 * Canonical implementation querying st_student_master, st_student_semester_history,
 * st_enrollment, st_user_master, st_login, and document repositories.
 */
if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    @session_start();
}
require_once __DIR__ . "/../database/db_connect.php";
$db_handle = new DBController();

if (!isset($_REQUEST['id'])) {
    echo "<div class='alert alert-danger'>No student ID provided.</div>";
    exit;
}

$student_id = intval($_REQUEST['id']);
$sessionUserId = intval($_SESSION['user_id'] ?? $_SESSION['user_session'] ?? 0);
$sessionRoleId = intval($_SESSION['user_type'] ?? $_SESSION['role_id'] ?? 0);

if ($sessionUserId <= 0 || $sessionRoleId <= 0) {
    http_response_code(403);
    echo "<div class='alert alert-danger' style='border-radius: 4px; padding: 15px; margin: 15px;'><i class='fa fa-ban'></i> Please log in to view student records.</div>";
    exit;
}

if (!$db_handle->canAccessStudent($student_id, $sessionUserId, $sessionRoleId)) {
    http_response_code(403);
    echo "<div class='alert alert-danger' style='border-radius: 4px; padding: 15px; margin: 15px;'><i class='fa fa-ban'></i> Access Denied: You do not have permission to view this student record.</div>";
    exit;
}

// 1. Fetch Student Master and Relational Metadata
$sql = "SELECT s.*,
               c.class_name,
               COALESCE(NULLIF(TRIM(sec.sections), ''), NULLIF(TRIM(divm.division_name), '')) AS section_name,
               d.department_name,
               sp.specialization_name,
               sub.subject_name AS specialization_subject_name,
               sub.subject_code AS specialization_subject_code,
               current_history.specialization_subject_id AS current_history_subject_id,
               mc.course_name AS minor_course_name,
               ms.subject_name AS minor_subject_name,
               sess.session_name AS academic_year_name,
               sem.semester_name AS semester_name
        FROM st_student_master s
        LEFT JOIN st_class_master c ON c.class_id = s.class_id
        LEFT JOIN st_section_master sec ON sec.id = s.division_id
        LEFT JOIN st_division_master divm ON divm.division_id = s.division_id
        LEFT JOIN st_department_master d ON d.department_id = s.department_id
        LEFT JOIN st_specialization_master sp ON sp.specialization_id = s.specialization_id
        LEFT JOIN st_student_semester_history current_history ON current_history.student_id = s.student_id AND current_history.semester_id = s.current_semester_id
        LEFT JOIN st_specialization_subject_master sub ON sub.subject_id = COALESCE(NULLIF(s.specialization_subject_id, 0), current_history.specialization_subject_id)
        LEFT JOIN st_minorcourse mc ON mc.course_id = s.minor_course_id
        LEFT JOIN st_minorsubject ms ON ms.subject_id = s.minor_subject_id
        LEFT JOIN st_session_master sess ON sess.session_id = s.academic_year_id
        LEFT JOIN st_semester_master sem ON sem.semester_id = s.current_semester_id
        WHERE s.student_id = ?
        LIMIT 1";

$row = null;
$stmt = mysqli_prepare($db_handle->conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $student_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
    }
    mysqli_stmt_close($stmt);
}

if (!$row) {
    echo "<div class='alert alert-danger' style='border-radius: 4px; padding: 15px; margin: 15px;'><i class='fa fa-exclamation-triangle'></i> Student record not found (ID: " . htmlspecialchars($student_id) . ").</div>";
    exit;
}

// Fallback for subject details if stored in history
if (empty($row['specialization_subject_name']) && !empty($row['current_history_subject_id'])) {
    $hSubRes = mysqli_query($db_handle->conn, "SELECT subject_name, subject_code FROM st_specialization_subject_master WHERE subject_id = " . intval($row['current_history_subject_id']));
    if ($hSubRes && ($hSub = mysqli_fetch_assoc($hSubRes))) {
        $row['specialization_subject_name'] = $hSub['subject_name'];
        $row['specialization_subject_code'] = $hSub['subject_code'] ?? '';
    }
}

// 2. Fetch User Account & Login Credentials
$userAccount = null;
$userLogins = [];
$uStmt = mysqli_prepare($db_handle->conn, "SELECT u.user_id, u.user_name, u.email_id, u.role_id, u.is_first_login, r.role_name, u.created_at FROM st_user_master u LEFT JOIN st_role_master r ON r.role_id = u.role_id WHERE u.student_id = ? LIMIT 1");
if ($uStmt) {
    mysqli_stmt_bind_param($uStmt, 'i', $student_id);
    mysqli_stmt_execute($uStmt);
    $uRes = mysqli_stmt_get_result($uStmt);
    if ($uRes && ($uRow = mysqli_fetch_assoc($uRes))) {
        $userAccount = $uRow;
        $lRes = mysqli_query($db_handle->conn, "SELECT login_id, username, created_at FROM st_login WHERE user_id = " . intval($uRow['user_id']) . " ORDER BY login_id ASC");
        while ($lRow = mysqli_fetch_assoc($lRes)) {
            $userLogins[] = $lRow;
        }
    }
    mysqli_stmt_close($uStmt);
}

// 3. Fetch Resolved Mentor Information
$currentMentor = $db_handle->getResolvedMentorForStudent($student_id, $row['current_semester_id'], $row['academic_year_id']);

// 4. Fetch Multi-Semester Academic Progression
$academicHistory = $db_handle->getStudentAcademicHistory($student_id);
$currentSemNum = intval($row['current_semester_id'] ?? 0);

// 5. Fetch Uploaded Documents
$uploadedDocuments = [];
// Admission marklist files
if (!empty($row['mark_list'])) {
    $mlFiles = explode(',', $row['mark_list']);
    foreach ($mlFiles as $f) {
        $f = trim($f);
        if (!empty($f)) {
            $uploadedDocuments[] = [
                'name' => $f,
                'category' => 'Admission Marksheet',
                'date' => !empty($row['created_at']) ? date('d-m-Y', strtotime($row['created_at'])) : 'Recorded',
                'status' => 'Verified on Admission',
                'path' => 'document_view.php?type=marklist&student_id=' . $student_id . '&file=' . urlencode($f)
            ];
        }
    }
}
// Minor course certificates
$certRes = mysqli_query($db_handle->conn, "SELECT certificate_id, course_name, issuing_institution, completion_date, file_path, verification_status, submitted_at FROM st_minor_certificates WHERE student_id = {$student_id}");
if ($certRes) {
    while ($cRow = mysqli_fetch_assoc($certRes)) {
        $uploadedDocuments[] = [
            'name' => $cRow['course_name'] . ' Certificate (' . $cRow['issuing_institution'] . ')',
            'category' => 'Minor / Specialization Certificate',
            'date' => !empty($cRow['completion_date']) ? date('d-m-Y', strtotime($cRow['completion_date'])) : date('d-m-Y', strtotime($cRow['submitted_at'])),
            'status' => $cRow['verification_status'] ?? 'Pending Verification',
            'path' => !empty($cRow['file_path']) ? 'document_view.php?type=certificate&student_id=' . $student_id . '&file=' . urlencode(basename($cRow['file_path'])) : ''
        ];
    }
}
// NPTEL exam records
$nptelRes = mysqli_query($db_handle->conn, "SELECT nptel_id, course_name, score, pass_fail, recorded_at FROM st_nptel_records WHERE student_id = {$student_id}");
if ($nptelRes) {
    while ($nRow = mysqli_fetch_assoc($nptelRes)) {
        $uploadedDocuments[] = [
            'name' => $nRow['course_name'] . ' (Score: ' . $nRow['score'] . ')',
            'category' => 'NPTEL Certification',
            'date' => date('d-m-Y', strtotime($nRow['recorded_at'])),
            'status' => $nRow['pass_fail'],
            'path' => ''
        ];
    }
}

if (!function_exists('fmt_val')) {
    function fmt_val($val) {
        $v = trim((string)($val ?? ''));
        return $v !== '' ? htmlspecialchars($v) : '<span class="text-muted">Not Available</span>';
    }
}

$specialization_name = strtolower($row['specialization_name'] ?? '');
$is_minor = strpos($specialization_name, 'minor') !== false;
$is_honours = strpos($specialization_name, 'honour') !== false || strpos($specialization_name, 'honor') !== false;
$trackLabel = $is_minor ? 'Minor Multidisciplinary' : ($is_honours ? 'Honours Track' : 'Standard Specialization');

$statusBadge = ($row['status'] == '1') ? 'label-success' : 'label-warning';
$statusText = ($row['status'] == '1') ? 'Active' : 'Pending';
?>

<style>
/* Scoped styles for Student Profile & Academic Progression View */
.erp-student-profile-wrapper {
  padding: 2px;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

.erp-detail-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  margin-bottom: 16px;
  overflow: hidden;
}

.erp-detail-card-header {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  padding: 10px 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.erp-detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 12px;
  padding: 14px 16px;
}

.erp-detail-item {
  min-width: 0;
  overflow: hidden;
}

.erp-detail-label {
  font-size: 11px;
  text-transform: uppercase;
  color: #64748b;
  font-weight: 600;
  letter-spacing: 0.3px;
  margin-bottom: 3px;
}

.erp-detail-value {
  font-size: 13px;
  color: #1e293b;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.erp-progression-table-wrap {
  width: 100%;
  max-width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  margin: 0;
  padding: 0;
  border: none;
}

.erp-progression-table-wrap table {
  min-width: 760px;
  margin-bottom: 0;
}

.erp-doc-table-wrap {
  width: 100% !important;
  max-width: 100% !important;
  overflow-x: auto !important;
  overflow-y: hidden !important;
  -webkit-overflow-scrolling: touch;
  margin: 0;
  padding: 0;
  border: none;
  display: block;
  scrollbar-width: thin;
}

.erp-doc-table-wrap::-webkit-scrollbar {
  height: 6px;
}

.erp-doc-table-wrap::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.erp-doc-table-wrap::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.erp-doc-table-wrap::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.erp-doc-table-wrap table {
  width: 100% !important;
  min-width: 500px;
  margin-bottom: 0 !important;
  font-size: 12px;
  border-collapse: collapse;
}

.erp-doc-cell-name {
  min-width: 140px;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.erp-doc-cell-cat {
  min-width: 100px;
  overflow-wrap: anywhere;
  word-break: break-word;
}

.erp-doc-cell-action {
  white-space: nowrap !important;
  min-width: 115px !important;
  width: 115px !important;
  text-align: center !important;
}

@media (max-width: 768px) {
  .erp-detail-grid {
    grid-template-columns: repeat(2, 1fr) !important;
    gap: 10px;
    padding: 12px;
  }
}

@media (max-width: 576px) {
  .erp-detail-grid {
    grid-template-columns: 1fr !important;
    gap: 10px;
    padding: 10px 12px;
  }
  .erp-detail-card-header {
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 6px !important;
    padding: 8px 12px;
  }
}
</style>

<div class="erp-student-profile-wrapper">

    <!-- SECTION 1: STUDENT BASIC INFORMATION -->
    <div class="erp-detail-card">
        <div class="erp-detail-card-header">
            <div style="font-weight: 700; font-size: 15px; color: #1e293b;">
                <i class="fa fa-user-circle text-primary" style="margin-right: 6px;"></i> Student Basic Information &mdash; <?= htmlspecialchars($row['fname']) ?>
            </div>
            <div>
                <span class="label <?= $statusBadge ?>" style="font-size: 11px; padding: 4px 8px;"><?= $statusText ?></span>
            </div>
        </div>
        <div class="erp-detail-grid">
            <div class="erp-detail-item">
                <div class="erp-detail-label">Full Name</div>
                <div class="erp-detail-value" style="font-weight: 600; color: #0f172a; margin-top: 2px;"><?= fmt_val($row['fname']) ?></div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label">ERP Registration ID</div>
                <div class="erp-detail-value text-mono" style="font-weight: 700; color: #0284c7; margin-top: 2px;"><?= fmt_val($row['registration_no']) ?></div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label">Roll Number</div>
                <div class="erp-detail-value text-mono" style="margin-top: 2px;"><?= fmt_val($row['roll_no']) ?></div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label">Department</div>
                <div class="erp-detail-value" style="margin-top: 2px;"><?= fmt_val($row['department_name']) ?></div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label">Class & Division</div>
                <div class="erp-detail-value" style="margin-top: 2px;"><?= fmt_val($row['class_name']) ?> &bull; Div <?= fmt_val($row['section_name']) ?></div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label">Academic Year & Semester</div>
                <div class="erp-detail-value" style="margin-top: 2px;"><?= fmt_val($row['academic_year_name']) ?> &bull; <?= fmt_val($row['semester_name']) ?></div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label">Email Address</div>
                <div class="erp-detail-value" style="margin-top: 2px;">
                    <?= !empty($row['email']) ? '<a href="mailto:' . htmlspecialchars($row['email']) . '">' . htmlspecialchars($row['email']) . '</a>' : '<span class="text-muted">Not Available</span>' ?>
                </div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label">Mobile Number</div>
                <div class="erp-detail-value" style="margin-top: 2px;"><?= fmt_val($row['mobile']) ?></div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label">Admission Date</div>
                <div class="erp-detail-value" style="margin-top: 2px;"><?= !empty($row['created_at']) ? date('d-m-Y', strtotime($row['created_at'])) : 'N/A' ?></div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label">Graduation Year / Batch</div>
                <div class="erp-detail-value" style="margin-top: 2px;"><?= fmt_val($row['grad_year']) ?></div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label">Recorded CGPA</div>
                <div class="erp-detail-value" style="font-size: 14px; font-weight: 700; color: #166534; margin-top: 2px;"><?= !empty($row['cgpa']) ? number_format($row['cgpa'], 2) : '<span class="text-muted">N/A</span>' ?></div>
            </div>
        </div>
    </div>

    <!-- SECTION 2: ACADEMIC PROGRESSION & SEMESTER HISTORY -->
    <div class="erp-detail-card">
        <div class="erp-detail-card-header">
            <div style="font-weight: 700; font-size: 14px; color: #1e293b;">
                <i class="fa fa-history text-primary" style="margin-right: 6px;"></i> Academic Progression & Semester History
            </div>
            <div>
                <span class="badge" style="background-color: #f1f5f9; color: #475569; font-size: 11px;"><?= count($academicHistory) ?> <?= count($academicHistory) === 1 ? 'Period' : 'Periods' ?> Recorded</span>
            </div>
        </div>
        <div class="table-responsive erp-progression-table-wrap">
            <?php if (!empty($academicHistory)): ?>
                <table class="table table-bordered table-striped" style="margin-bottom: 0; font-size: 12px;">
                    <thead style="background-color: #f8fafc; color: #475569;">
                        <tr>
                            <th style="padding: 8px 10px;">Semester</th>
                            <th style="padding: 8px 10px;">Academic Year</th>
                            <th style="padding: 8px 10px;">Division</th>
                            <th style="padding: 8px 10px;">Roll No</th>
                            <th style="padding: 8px 10px;">Specialization</th>
                            <th style="padding: 8px 10px;">Enrolled Subject / Course</th>
                            <th style="padding: 8px 10px;">Assigned Mentor</th>
                            <th style="padding: 8px 10px; text-align: center;">CGPA / SGPA</th>
                            <th style="padding: 8px 10px; text-align: center;">Progression Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($academicHistory as $hrow): 
                            $isCurrent = ($currentSemNum > 0 && intval($hrow['semester_id']) === $currentSemNum);
                            $rowClass = $isCurrent ? 'style="background-color: #eff6ff;"' : '';
                        ?>
                            <tr <?= $rowClass ?>>
                                <td style="padding: 8px 10px;">
                                    <strong><?= htmlspecialchars($hrow['semester_name']) ?></strong>
                                    <?php if ($isCurrent): ?>
                                        <span class="label label-primary" style="font-size: 9px; margin-left: 4px;">CURRENT</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 8px 10px;"><?= htmlspecialchars($hrow['academic_year_name']) ?></td>
                                <td style="padding: 8px 10px;"><?= htmlspecialchars($hrow['division_name'] ?: 'N/A') ?></td>
                                <td style="padding: 8px 10px;" class="text-mono"><?= htmlspecialchars($hrow['roll_no'] ?: ($row['roll_no'] ?? 'N/A')) ?></td>
                                <td style="padding: 8px 10px;"><?= htmlspecialchars($hrow['specialization_name'] ?: 'Not Assigned') ?></td>
                                <td style="padding: 8px 10px;">
                                    <strong><?= htmlspecialchars($hrow['subject_name'] ?: 'Not Assigned') ?></strong>
                                    <?php if (!empty($hrow['subject_code'])): ?>
                                        <span class="text-muted" style="font-size: 11px;">(<?= htmlspecialchars($hrow['subject_code']) ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 8px 10px;">
                                    <?php if (!empty($hrow['mentor_name']) && $hrow['mentor_name'] !== 'Not Assigned'): ?>
                                        <span class="erp-mentor-faculty"><i class="fa fa-user"></i> <?= htmlspecialchars($hrow['mentor_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 8px 10px; text-align: center; font-weight: 700; color: #15803d;">
                                    <?= !empty($hrow['cgpa']) ? number_format($hrow['cgpa'], 2) : '<span class="text-muted">&mdash;</span>' ?>
                                </td>
                                <td style="padding: 8px 10px; text-align: center;">
                                    <?php if ($isCurrent): ?>
                                        <span class="label label-primary">Active</span>
                                    <?php else: ?>
                                        <span class="label label-default">Completed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 24px; text-align: center; color: #64748b;">
                    <i class="fa fa-info-circle text-muted" style="font-size: 20px; margin-bottom: 6px;"></i>
                    <p style="margin: 0; font-size: 13px;">No historical progression records available. Current semester information is displayed above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECTION 3: SPECIALIZATION & SUBJECT INFORMATION -->
    <div class="erp-detail-card" style="margin-bottom: 16px;">
        <div class="erp-detail-card-header" style="font-weight: 700; font-size: 14px; color: #1e293b;">
            <i class="fa fa-book text-primary" style="margin-right: 6px;"></i> Specialization & Subject Details
        </div>
        <div class="erp-detail-grid">
            <div class="erp-detail-item">
                <div class="erp-detail-label" style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Specialization Program</div>
                <div class="erp-detail-value" style="font-size: 13px; font-weight: 600; color: #0f172a; margin-top: 2px;"><?= fmt_val($row['specialization_name']) ?></div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label" style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Specialization Track</div>
                <div class="erp-detail-value" style="font-size: 13px; margin-top: 2px;">
                    <span class="label label-info" style="font-size: 11px;"><?= $trackLabel ?></span>
                </div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label" style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Enrolled Subject</div>
                <div class="erp-detail-value" style="font-size: 13px; font-weight: 600; color: #0284c7; margin-top: 2px;"><?= fmt_val($row['specialization_subject_name']) ?></div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label" style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Subject / Course Code</div>
                <div class="erp-detail-value text-mono" style="font-size: 13px; margin-top: 2px;"><?= fmt_val($row['specialization_subject_code'] ?? '') ?></div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label" style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Offering Department</div>
                <div class="erp-detail-value" style="font-size: 13px; margin-top: 2px;"><?= fmt_val($row['department_name']) ?></div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label" style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Enrollment Status</div>
                <div class="erp-detail-value" style="font-size: 13px; margin-top: 2px;">
                    <span class="label label-success" style="font-size: 11px;">Enrolled (Active)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 4: FACULTY MENTOR INFORMATION -->
    <div class="erp-detail-card" style="margin-bottom: 16px;">
        <div class="erp-detail-card-header" style="font-weight: 700; font-size: 14px; color: #1e293b;">
            <i class="fa fa-users text-primary" style="margin-right: 6px;"></i> Faculty Mentor Assignment
        </div>
        <div class="erp-detail-grid">
            <div class="erp-detail-item">
                <div class="erp-detail-label" style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Assigned Mentor</div>
                <div class="erp-detail-value" style="font-size: 13px; font-weight: 600; color: #0f172a; margin-top: 2px;">
                    <?= !empty($currentMentor['mentor_name']) ? htmlspecialchars($currentMentor['mentor_name']) : '<span class="text-muted">Not Assigned</span>' ?>
                </div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label" style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Mentor Department</div>
                <div class="erp-detail-value" style="font-size: 13px; margin-top: 2px;">
                    <?= !empty($currentMentor['department_name']) ? htmlspecialchars($currentMentor['department_name']) : fmt_val($row['department_name']) ?>
                </div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label" style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Mentor Email / Contact</div>
                <div class="erp-detail-value" style="font-size: 13px; margin-top: 2px;">
                    <?= !empty($currentMentor['email_id']) ? '<a href="mailto:' . htmlspecialchars($currentMentor['email_id']) . '">' . htmlspecialchars($currentMentor['email_id']) . '</a>' : '<span class="text-muted">Not Available</span>' ?>
                </div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label" style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Allocation Status</div>
                <div class="erp-detail-value" style="font-size: 13px; margin-top: 2px;">
                    <?php if (!empty($currentMentor['mentor_name']) && $currentMentor['mentor_name'] !== 'Not Assigned'): ?>
                        <span class="label label-success" style="font-size: 11px;">Active Assignment</span>
                    <?php else: ?>
                        <span class="label label-default" style="font-size: 11px;">Awaiting Mentor Mapping</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 5: UPLOADED DOCUMENTS & CERTIFICATES -->
    <div class="erp-detail-card" style="margin-bottom: 16px;">
        <div class="erp-detail-card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="font-weight: 700; font-size: 14px; color: #1e293b;">
                <i class="fa fa-folder-open text-primary" style="margin-right: 6px;"></i> Uploaded Documents & Academic Records
            </div>
            <div>
                <span class="badge" style="background-color: #f1f5f9; color: #475569; font-size: 11px;"><?= count($uploadedDocuments) ?> <?= count($uploadedDocuments) === 1 ? 'Document' : 'Documents' ?></span>
            </div>
        </div>
        <div class="erp-doc-table-wrap">
            <?php if (!empty($uploadedDocuments)): ?>
                <table class="table table-bordered table-striped" style="width: 100%; min-width: 500px; margin-bottom: 0; font-size: 12px; table-layout: auto;">
                    <thead style="background-color: #f8fafc; color: #475569;">
                        <tr>
                            <th style="min-width: 140px; padding: 8px 10px;">Document Name / File</th>
                            <th style="min-width: 100px; padding: 8px 10px;">Category</th>
                            <th style="min-width: 80px; padding: 8px 8px;">Date / Period</th>
                            <th style="min-width: 90px; padding: 8px 8px; text-align: center;">Status</th>
                            <th style="min-width: 115px; width: 115px; padding: 8px 8px; text-align: center;" class="erp-doc-cell-action">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($uploadedDocuments as $doc): ?>
                            <tr>
                                <td style="padding: 8px 10px; min-width: 140px; overflow-wrap: anywhere; word-break: break-word;">
                                    <i class="fa fa-file-text-o text-muted" style="margin-right: 6px;"></i>
                                    <strong><?= htmlspecialchars($doc['name']) ?></strong>
                                </td>
                                <td style="padding: 8px 10px; min-width: 100px; overflow-wrap: anywhere; word-break: break-word;"><?= htmlspecialchars($doc['category']) ?></td>
                                <td style="padding: 8px 8px; min-width: 80px; white-space: nowrap;"><?= htmlspecialchars($doc['date']) ?></td>
                                <td style="padding: 8px 8px; min-width: 90px; text-align: center;">
                                    <span class="label label-info" style="font-size: 10px; white-space: normal; display: inline-block; padding: 3px 6px;"><?= htmlspecialchars($doc['status']) ?></span>
                                </td>
                                <td style="padding: 8px 8px; text-align: center; white-space: nowrap; min-width: 115px; width: 115px;" class="erp-doc-cell-action">
                                    <?php if (!empty($doc['path'])): ?>
                                        <a href="<?= htmlspecialchars($doc['path']) ?>" target="_blank" class="btn btn-default btn-xs" style="color: #0284c7; border-color: #bae6fd; font-weight: 600; padding: 4px 8px; display: inline-flex; align-items: center; justify-content: center; gap: 4px; white-space: nowrap; font-size: 11px;">
                                            <i class="fa fa-download"></i> View / Download
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="padding: 24px; text-align: center; color: #64748b;">
                    <i class="fa fa-file-o text-muted" style="font-size: 20px; margin-bottom: 6px;"></i>
                    <p style="margin: 0; font-size: 13px;">No uploaded documents or external certificates found for this student.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECTION 6: STUDENT LOGIN & ACCOUNT STATUS -->
    <div class="erp-detail-card" style="margin-bottom: 0;">
        <div class="erp-detail-card-header" style="font-weight: 700; font-size: 14px; color: #1e293b;">
            <i class="fa fa-lock text-primary" style="margin-right: 6px;"></i> Student Login Account & Security Status
        </div>
        <div class="erp-detail-grid">
            <div class="erp-detail-item">
                <div class="erp-detail-label" style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">System User ID</div>
                <div class="erp-detail-value text-mono" style="font-size: 13px; font-weight: 700; color: #0284c7; margin-top: 2px;">
                    <?= !empty($userAccount['user_id']) ? '#' . htmlspecialchars($userAccount['user_id']) : '<span class="text-muted">No Account</span>' ?>
                </div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label" style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Assigned Role</div>
                <div class="erp-detail-value" style="font-size: 13px; margin-top: 2px;">
                    <span class="label label-primary" style="font-size: 11px;">Student (Role 5)</span>
                </div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label" style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Dual Login Credentials</div>
                <div class="erp-detail-value" style="font-size: 12px; margin-top: 2px;">
                    <?php if (!empty($userLogins)): ?>
                        <?php foreach ($userLogins as $l): ?>
                            <div class="text-mono" style="margin-bottom: 2px;"><i class="fa fa-key text-muted" style="margin-right: 4px;"></i><?= htmlspecialchars($l['username']) ?></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="text-muted">Not Provisioned</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label" style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">First-Login Policy</div>
                <div class="erp-detail-value" style="font-size: 13px; margin-top: 2px;">
                    <?php if (isset($userAccount['is_first_login']) && (int)$userAccount['is_first_login'] === 1): ?>
                        <span class="label label-warning" style="font-size: 11px;">Password Change Pending</span>
                    <?php else: ?>
                        <span class="label label-success" style="font-size: 11px;">Password Initialized</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="erp-detail-item">
                <div class="erp-detail-label" style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Encryption Standard</div>
                <div class="erp-detail-value" style="font-size: 13px; margin-top: 2px;">
                    <span class="label label-default" style="font-size: 11px;">Bcrypt (Cost 10) Enforced</span>
                </div>
            </div>
        </div>
    </div>

</div>
