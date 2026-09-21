<?php
require "header/header.php";

$student = null;
$linkedStudentId = 0;

if ((int) ($usertype ?? 0) !== 5) {
    header("location: index.php");
    exit;
}

if (!empty($userid)) {
    $userCheckSql = "SELECT student_id FROM st_user_master WHERE user_id = ? AND student_id > 0 LIMIT 1";
    $userCheckStmt = mysqli_prepare($db_handle->conn, $userCheckSql);

    if ($userCheckStmt) {
        mysqli_stmt_bind_param($userCheckStmt, 'i', $userid);
        mysqli_stmt_execute($userCheckStmt);
        $userCheckResult = mysqli_stmt_get_result($userCheckStmt);
        if ($userCheckResult && ($userRow = mysqli_fetch_assoc($userCheckResult))) {
            $linkedStudentId = intval($userRow['student_id']);
        }
        mysqli_stmt_close($userCheckStmt);
    }
}

if ($linkedStudentId > 0) {
    $studentSql = "SELECT s.student_id, s.academic_year_id, s.registration_no, s.roll_no, s.grad_year,
                          s.cgpa, s.fname, s.mobile, s.email, s.status, s.created_at,
                          c.class_name, sec.sections AS division_name, d.department_name,
                          sp.specialization_name, sub.subject_name, current_history_subject.subject_name AS current_history_subject_name, ay.session_name,
                          sem.semester_name AS current_semester_name,
                          COALESCE(
                              (SELECT NULLIF(TRIM(um.user_name), '') 
                               FROM st_mentor_subject_mapping msm 
                               JOIN st_user_master um ON um.user_id = msm.mentor_id 
                               WHERE msm.subject_id = COALESCE(NULLIF(current_history.specialization_subject_id, 0), NULLIF(s.specialization_subject_id, 0)) 
                               LIMIT 1),
                              (SELECT um.user_name FROM st_mentor_student_mapping mm
                               INNER JOIN st_user_master um ON um.user_id = mm.mentor_id
                               WHERE mm.student_id = s.student_id AND mm.semester_id = s.current_semester_id
                               ORDER BY mm.mapping_id DESC LIMIT 1)
                          ) AS current_mentor_name,
                          (SELECT h.progress_percent FROM st_student_semester_history h
                           WHERE h.student_id = s.student_id AND h.semester_id = s.current_semester_id
                           LIMIT 1) AS current_progress
                   FROM st_student_master s
                   LEFT JOIN st_class_master c ON c.class_id = s.class_id
                   LEFT JOIN st_section_master sec ON sec.id = s.division_id
                   LEFT JOIN st_department_master d ON d.department_id = s.department_id
                   LEFT JOIN st_specialization_master sp ON sp.specialization_id = s.specialization_id
                   LEFT JOIN st_student_semester_history current_history ON current_history.student_id = s.student_id AND current_history.semester_id = s.current_semester_id
                   LEFT JOIN st_specialization_subject_master sub ON sub.subject_id = s.specialization_subject_id
                   LEFT JOIN st_specialization_subject_master current_history_subject ON current_history_subject.subject_id = current_history.specialization_subject_id
                   LEFT JOIN st_session_master ay ON ay.session_id = s.academic_year_id
                   LEFT JOIN st_semester_master sem ON sem.semester_id = s.current_semester_id
                   WHERE s.student_id = ?
                   LIMIT 1";

    $studentStmt = mysqli_prepare($db_handle->conn, $studentSql);
    if ($studentStmt) {
        mysqli_stmt_bind_param($studentStmt, 'i', $linkedStudentId);
        mysqli_stmt_execute($studentStmt);
        $studentResult = mysqli_stmt_get_result($studentStmt);
        if ($studentResult) {
            $student = mysqli_fetch_assoc($studentResult);
        }
        mysqli_stmt_close($studentStmt);
    }
}

$statusText = 'Pending Approval';
$statusClass = 'label-warning';
if ($student && (int) $student['status'] === 1) {
    $statusText = 'Approved / Active';
    $statusClass = 'label-success';
}

function student_val($value)
{
    $value = trim((string) ($value ?? ''));
    return $value !== '' ? htmlspecialchars($value) : 'N/A';
}
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <section class="content-header">
        <h1>
            Student Portal
            <small>Academic Record & Specialization Progression</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="student_dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Student Portal</li>
        </ol>
    </section>

    <section class="content">
        <?php if (!$student): ?>
            <div class="alert alert-warning" style="background-color: #fffbeb !important; color: #9a3412 !important; border: 1px solid #fed7aa !important; padding: 15px;">
                <i class="fa fa-info-circle"></i> 
                <strong>No student admission record found.</strong> Please complete your registration form to activate your academic dashboard.
                <div style="margin-top: 10px;">
                    <a href="student_admission.php" class="btn-erp-primary">
                        <i class="fa fa-arrow-right"></i> Complete Admission Form
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Institutional Student Banner -->
            <div class="erp-detail-card" style="margin-bottom: 16px;">
                <div class="erp-detail-card-header" style="justify-content: space-between;">
                    <div>
                        <i class="fa fa-user-circle"></i>
                        <span><?= student_val($student['fname']) ?></span>
                        <span class="text-muted" style="font-weight: 400; margin-left: 8px;">(ERP ID: <span class="text-mono"><?= student_val($student['registration_no']) ?></span>)</span>
                    </div>
                    <div>
                        <span class="label <?= $statusClass ?>" style="margin-right: 8px;"><?= $statusText ?></span>
                        <a href="student_admission.php?edit=1" class="btn btn-default btn-xs">
                            <i class="fa fa-edit"></i> Edit Details
                        </a>
                    </div>
                </div>
                <div class="erp-summary-bar" style="border: none; margin-bottom: 0; box-shadow: none; border-radius: 0;">
                    <div class="erp-metric-item">
                        <span class="erp-metric-label">Roll Number</span>
                        <span class="erp-metric-value text-mono"><?= student_val($student['roll_no']) ?></span>
                        <span class="erp-metric-sub">Division <?= student_val($student['division_name']) ?></span>
                    </div>
                    <div class="erp-metric-item">
                        <span class="erp-metric-label">Class</span>
                        <span class="erp-metric-value"><?= student_val($student['class_name']) ?></span>
                        <span class="erp-metric-sub"><?= student_val($student['department_name']) ?></span>
                    </div>
                    <div class="erp-metric-item">
                        <span class="erp-metric-label">Current Semester</span>
                        <span class="erp-metric-value"><?= student_val($student['current_semester_name'] ?? ('Semester ' . ($student['current_semester_id'] ?? ''))) ?></span>
                        <span class="erp-metric-sub"><?= student_val($student['session_name'] ?? '2026-27') ?></span>
                    </div>
                    <div class="erp-metric-item">
                        <span class="erp-metric-label">Aggregate CGPA</span>
                        <span class="erp-metric-value"><?= student_val($student['cgpa']) ?></span>
                        <span class="erp-metric-sub">Academic Scale</span>
                    </div>
                </div>
            </div>

            <!-- Structured Student Details Grid -->
            <div class="row">
                <div class="col-md-6">
                    <div class="erp-detail-card">
                        <div class="erp-detail-card-header">
                            <i class="fa fa-graduation-cap"></i> Current Specialization & Mentor
                        </div>
                        <div class="erp-detail-grid">
                            <div class="erp-detail-item">
                                <div class="erp-detail-label">Specialization Track</div>
                                <div class="erp-detail-value"><?= student_val($student['specialization_name']) ?></div>
                            </div>
                            <div class="erp-detail-item">
                                <div class="erp-detail-label">Enrolled Subject</div>
                                <div class="erp-detail-value"><?= student_val($student['subject_name'] ?: ($student['current_history_subject_name'] ?? '')) ?></div>
                            </div>
                            <div class="erp-detail-item">
                                <div class="erp-detail-label">Assigned Faculty Mentor</div>
                                <div class="erp-detail-value">
                                    <?php 
                                    $mentorName = student_val($student['current_mentor_name']);
                                    if ($mentorName !== 'N/A' && $mentorName !== 'Not Assigned') {
                                        echo '<span class="erp-mentor-faculty"><i class="fa fa-user"></i> ' . $mentorName . '</span>';
                                    } else {
                                        echo '<span class="erp-mentor-unassigned"><i class="fa fa-exclamation-circle"></i> Not Assigned</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="erp-detail-item">
                                <div class="erp-detail-label">Graduation Year</div>
                                <div class="erp-detail-value"><?= student_val($student['grad_year']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="erp-detail-card">
                        <div class="erp-detail-card-header">
                            <i class="fa fa-id-card-o"></i> Contact & Enrollment Information
                        </div>
                        <div class="erp-detail-grid">
                            <div class="erp-detail-item">
                                <div class="erp-detail-label">Email Address</div>
                                <div class="erp-detail-value"><?= student_val($student['email']) ?></div>
                            </div>
                            <div class="erp-detail-item">
                                <div class="erp-detail-label">Mobile Number</div>
                                <div class="erp-detail-value"><?= student_val($student['mobile']) ?></div>
                            </div>
                            <div class="erp-detail-item">
                                <div class="erp-detail-label">Department</div>
                                <div class="erp-detail-value"><?= student_val($student['department_name']) ?></div>
                            </div>
                            <div class="erp-detail-item">
                                <div class="erp-detail-label">Registered On</div>
                                <div class="erp-detail-value"><?= !empty($student['created_at']) ? date('d-m-Y', strtotime($student['created_at'])) : 'N/A' ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ACADEMIC PROGRESSION JOURNEY MILESTONE STRIP -->
            <?php
            $studentId = intval($student['student_id']);
            $academicHistory = $db_handle->getStudentAcademicHistory($studentId);
            $currentSemNum = intval($student['current_semester_id'] ?? 5);
            $allSemesters = [3 => 'SEM III', 4 => 'SEM IV', 5 => 'SEM V', 6 => 'SEM VI', 7 => 'SEM VII', 8 => 'SEM VIII'];
            $historyBySem = [];
            foreach ($academicHistory as $h) {
                $historyBySem[intval($h['semester_id'])] = $h;
            }
            ?>
            <div class="erp-detail-card">
                <div class="erp-detail-card-header" style="justify-content: space-between;">
                    <div>
                        <i class="fa fa-road"></i> Academic Progression Journey
                    </div>
                    <span class="text-muted" style="font-size: 11px; font-weight: normal;">
                        Current Stage: <strong><?= htmlspecialchars($student['current_semester_name'] ?? ('Semester ' . $currentSemNum)) ?></strong>
                    </span>
                </div>
                <div class="erp-progression-track">
                    <?php foreach ($allSemesters as $semNum => $semLabel): 
                        $isPast = $semNum < $currentSemNum;
                        $isCurrent = $semNum === $currentSemNum;
                        $isFuture = $semNum > $currentSemNum;
                        $hasData = isset($historyBySem[$semNum]);
                        
                        $stepClass = $isCurrent ? 'is-current' : ($isPast ? 'is-completed' : '');
                        $badgeText = $isCurrent ? 'Current' : ($isPast ? 'Completed' : 'Upcoming');
                        $badgeClass = $isCurrent ? 'label-primary' : ($isPast ? 'label-success' : 'label-default');
                    ?>
                        <div class="erp-progression-step <?= $stepClass ?>">
                            <div class="erp-progression-step-title"><?= $semLabel ?></div>
                            <span class="label <?= $badgeClass ?>" style="font-size: 9px; padding: 1px 5px; margin-bottom: 4px;"><?= $badgeText ?></span>
                            <span class="erp-progression-step-subject" title="<?= htmlspecialchars($hasData ? ($historyBySem[$semNum]['subject_name'] ?: 'Enrolled') : '') ?>">
                                <?php if ($hasData): ?>
                                    <?= htmlspecialchars($historyBySem[$semNum]['subject_name'] ?: 'Enrolled') ?>
                                <?php else: ?>
                                    <?= $isFuture ? 'Future Stage' : 'Not Enrolled' ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- SEMESTER ACADEMIC HISTORY LEDGER -->
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-history text-muted"></i> Semester Registration & Mentor Ledger</h3>
                    <div class="box-tools pull-right">
                        <span class="text-muted" style="font-size: 12px;"><?= count($academicHistory) ?> Records</span>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-bordered table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Semester</th>
                                <th>Academic Year</th>
                                <th>Division</th>
                                <th>Roll No</th>
                                <th>Specialization</th>
                                <th>Enrolled Subject</th>
                                <th>Assigned Mentor</th>
                                <th class="col-num">CGPA</th>
                                <th class="col-center">Status</th>
                                <th>Enrolled Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($academicHistory)): ?>
                                <?php foreach ($academicHistory as $hrow): 
                                    $isCurrentRow = intval($hrow['semester_id']) === $currentSemNum;
                                    $statusBadge = ($hrow['history_status'] === 'Active' || $isCurrentRow) ? 'label-primary' : 'label-success';
                                    $statusText = ($hrow['history_status'] === 'Active' || $isCurrentRow) ? 'Active (Current)' : 'Completed';
                                ?>
                                    <tr class="<?= $isCurrentRow ? 'info' : '' ?>">
                                        <td>
                                            <strong><?= htmlspecialchars($hrow['semester_name']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($hrow['academic_year_name']) ?></td>
                                        <td><?= htmlspecialchars($hrow['division_name']) ?></td>
                                        <td><span class="text-mono"><?= htmlspecialchars($hrow['roll_no'] ?: ($student['roll_no'] ?? 'N/A')) ?></span></td>
                                        <td><?= htmlspecialchars($hrow['specialization_name']) ?></td>
                                        <td><strong><?= htmlspecialchars($hrow['subject_name']) ?></strong></td>
                                        <td>
                                            <?php if ($hrow['mentor_name'] && $hrow['mentor_name'] !== 'Not Assigned'): ?>
                                                <span class="erp-mentor-faculty">
                                                    <i class="fa fa-user"></i> <?= htmlspecialchars($hrow['mentor_name']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="erp-mentor-unassigned">Not Assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="col-num font-weight-bold"><?= htmlspecialchars($hrow['cgpa'] ?? 'N/A') ?></td>
                                        <td class="col-center"><span class="label <?= $statusBadge ?>"><?= $statusText ?></span></td>
                                        <td><span class="text-muted"><?= htmlspecialchars($hrow['enrolled_at']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted" style="padding: 20px;">No historical semester records recorded.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include "header/footer.php"; ?>
