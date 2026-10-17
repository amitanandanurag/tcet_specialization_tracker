<?php
require "header/header.php";

$student = null;
$linkedStudentId = 0;

if ((int) ($usertype ?? 0) !== 5) {
    header("location: index.php");
    exit;
}

// Server-side authenticated identity lookup: strictly bound to session $userid
if (!empty($userid)) {
    $userCheckSql = "SELECT student_id FROM st_user_master WHERE user_id = ? LIMIT 1";
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

    // Fallback: If st_user_master.student_id was 0 or unlinked, check if st_student_master has matching student_id
    if ($linkedStudentId <= 0) {
        $directCheckSql = "SELECT student_id FROM st_student_master WHERE student_id = ? LIMIT 1";
        $directCheckStmt = mysqli_prepare($db_handle->conn, $directCheckSql);
        if ($directCheckStmt) {
            mysqli_stmt_bind_param($directCheckStmt, 'i', $userid);
            mysqli_stmt_execute($directCheckStmt);
            $directCheckResult = mysqli_stmt_get_result($directCheckStmt);
            if ($directCheckResult && ($directRow = mysqli_fetch_assoc($directCheckResult))) {
                $linkedStudentId = intval($directRow['student_id']);
            }
            mysqli_stmt_close($directCheckStmt);
        }
    }
}

if ($linkedStudentId > 0) {
    $studentSql = "SELECT s.student_id, s.academic_year_id, s.registration_no, s.roll_no, s.grad_year,
                          s.cgpa, s.fname, s.mobile, s.email, s.status, s.created_at,
                          s.class_id, s.division_id, s.department_id, s.specialization_id, s.specialization_subject_id, s.current_semester_id,
                          c.class_name,
                          COALESCE(NULLIF(TRIM(divm.division_name), ''), NULLIF(TRIM(sec.sections), '')) AS division_name,
                          d.department_name,
                          sp.specialization_name, sub.subject_name, current_history_subject.subject_name AS current_history_subject_name,
                          ay.session_name,
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
                   LEFT JOIN st_division_master divm ON divm.division_id = s.division_id
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

$alertMsg = '';
$alertType = '';

// Handle Student Self-Service POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!DBController::validateCsrfToken()) {
        $alertMsg = 'Security validation failed (invalid CSRF token). Please refresh and try again.';
        $alertType = 'danger';
    } elseif ($linkedStudentId <= 0) {
        $alertMsg = 'No linked student record found for your user account.';
        $alertType = 'danger';
    } else {
        $action = trim($_POST['action']);

        // 1. Self-Service Contact Information Update
        if ($action === 'update_contact') {
            $mobile = trim($_POST['mobile'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if ($mobile === '' || !preg_match('/^[0-9]{10,15}$/', $mobile)) {
                $alertMsg = 'Please enter a valid mobile number (10 to 15 digits).';
                $alertType = 'warning';
            } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $alertMsg = 'Please enter a valid email address.';
                $alertType = 'warning';
            } else {
                $stmtUpd = mysqli_prepare($db_handle->conn, "UPDATE st_student_master SET mobile = ?, email = ? WHERE student_id = ?");
                if ($stmtUpd) {
                    mysqli_stmt_bind_param($stmtUpd, 'ssi', $mobile, $email, $linkedStudentId);
                    if (mysqli_stmt_execute($stmtUpd)) {
                        $userSync = mysqli_prepare($db_handle->conn, "UPDATE st_user_master SET phone_number = ?, email_id = ? WHERE student_id = ?");
                        if ($userSync) {
                            mysqli_stmt_bind_param($userSync, 'ssi', $mobile, $email, $linkedStudentId);
                            mysqli_stmt_execute($userSync);
                            mysqli_stmt_close($userSync);
                        }

                        if (method_exists($db_handle, 'writeAuditLog')) {
                            $db_handle->writeAuditLog($userid, 'STUDENT_CONTACT_UPDATE', 'st_student_master', $linkedStudentId, "Student updated contact details: mobile {$mobile}, email {$email}");
                        }
                        $alertMsg = 'Your contact information was updated successfully.';
                        $alertType = 'success';
                    } else {
                        $alertMsg = 'Failed to update contact details. Please try again.';
                        $alertType = 'danger';
                    }
                    mysqli_stmt_close($stmtUpd);
                }
            }
        }

        // 2. Validated Semester Progression Workflow (Sequential: N -> N + 1)
        elseif ($action === 'progress_semester') {
            $curSem = intval($_POST['current_semester_id'] ?? 0);
            $reqSem = intval($_POST['target_semester_id'] ?? 0);

            if ($reqSem !== ($curSem + 1)) {
                $alertMsg = "Invalid progression: You can only transition sequentially from Semester {$curSem} to Semester " . ($curSem + 1) . ". Skipping or reversing semesters is not permitted.";
                $alertType = 'danger';
            } elseif ($reqSem > 8) {
                $alertMsg = "You have already completed the final academic semester (Semester 8). No further progression is available.";
                $alertType = 'info';
            } else {
                $promoted = $db_handle->promoteStudentSemester(
                    $linkedStudentId,
                    $reqSem,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    $userid
                );

                if ($promoted) {
                    $alertMsg = "Congratulations! You have successfully progressed to Semester {$reqSem}. Your academic history has been recorded and mentor allocations have been revalidated.";
                    $alertType = 'success';
                } else {
                    $alertMsg = "Failed to process semester progression. Please contact your department coordinator.";
                    $alertType = 'danger';
                }
            }
        }
    }
}

// Re-fetch student record if an update occurred
if ($linkedStudentId > 0) {
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

// Profile Completeness Calculation
$completenessFields = [
    'Full Name' => !empty($student['fname']),
    'Roll Number' => !empty($student['roll_no']),
    'Contact Mobile' => !empty($student['mobile']),
    'Email Address' => !empty($student['email']),
    'Class' => !empty($student['class_id']),
    'Division' => !empty($student['division_id']),
    'Department' => !empty($student['department_id']),
    'Academic Year' => !empty($student['academic_year_id']),
    'Current Semester' => !empty($student['current_semester_id']),
    'Specialization' => !empty($student['specialization_id']),
    'CGPA' => floatval($student['cgpa'] ?? 0) > 0
];
$completedCount = count(array_filter($completenessFields));
$totalFieldsCount = count($completenessFields);
$completenessPercent = round(($completedCount / $totalFieldsCount) * 100);
$missingFields = array_keys(array_filter($completenessFields, function($v) { return !$v; }));

$statusText = 'Pending Approval';
$statusClass = 'label-warning';
if ($student && (int) $student['status'] === 1) {
    $statusText = 'Approved / Active';
    $statusClass = 'label-success';
}

if (!function_exists('student_val')) {
    function student_val($value, $fallback = 'Not Available')
    {
        $valStr = trim((string) ($value ?? ''));
        return $valStr !== '' ? htmlspecialchars($valStr) : $fallback;
    }
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
        <?php if (!empty($alertMsg)): ?>
            <div class="alert alert-<?= htmlspecialchars($alertType) ?> alert-dismissible" style="border-radius: 4px; margin-bottom: 16px;">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="fa <?= $alertType === 'success' ? 'fa-check-circle' : ($alertType === 'danger' ? 'fa-ban' : 'fa-info-circle') ?>"></i>
                <?= htmlspecialchars($alertMsg) ?>
            </div>
        <?php endif; ?>

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
                        <button type="button" class="btn btn-default btn-xs" data-toggle="modal" data-target="#updateContactModal">
                            <i class="fa fa-phone"></i> Update Contact
                        </button>
                    </div>
                </div>
                <div class="erp-summary-bar" style="border: none; margin-bottom: 0; box-shadow: none; border-radius: 0;">
                    <div class="erp-metric-item">
                        <span class="erp-metric-label">Roll Number</span>
                        <span class="erp-metric-value text-mono"><?= student_val($student['roll_no']) ?></span>
                        <span class="erp-metric-sub">Division: <?= student_val($student['division_name']) ?></span>
                    </div>
                    <div class="erp-metric-item">
                        <span class="erp-metric-label">Class</span>
                        <span class="erp-metric-value"><?= student_val($student['class_name']) ?></span>
                        <span class="erp-metric-sub">Dept: <?= student_val($student['department_name']) ?></span>
                    </div>
                    <div class="erp-metric-item">
                        <span class="erp-metric-label">Current Semester</span>
                        <span class="erp-metric-value"><?= !empty($student['current_semester_name']) ? htmlspecialchars($student['current_semester_name']) : (!empty($student['current_semester_id']) ? 'Semester ' . htmlspecialchars($student['current_semester_id']) : 'Not Available') ?></span>
                        <span class="erp-metric-sub"><?= !empty($student['session_name']) ? 'AY ' . htmlspecialchars($student['session_name']) : 'Academic Year' ?></span>
                    </div>
                    <div class="erp-metric-item">
                        <span class="erp-metric-label">Academic Year</span>
                        <span class="erp-metric-value"><?= student_val($student['session_name']) ?></span>
                        <span class="erp-metric-sub">Academic Session</span>
                    </div>
                    <div class="erp-metric-item">
                        <span class="erp-metric-label">Aggregate CGPA</span>
                        <span class="erp-metric-value"><?= student_val($student['cgpa']) ?></span>
                        <span class="erp-metric-sub">Academic Scale</span>
                    </div>
                </div>
            </div>

            <!-- Profile Completeness & Semester Progression Actions -->
            <div class="row" style="margin-bottom: 16px;">
                <div class="col-md-7">
                    <div class="erp-detail-card" style="height: 100%;">
                        <div class="erp-detail-card-header" style="justify-content: space-between;">
                            <div>
                                <i class="fa fa-tasks text-primary"></i> Profile Completeness
                            </div>
                            <span class="badge <?= $completenessPercent === 100 ? 'bg-green' : 'bg-blue' ?>"><?= $completenessPercent ?>% Completed</span>
                        </div>
                        <div style="padding: 16px;">
                            <div class="progress progress-sm active" style="border-radius: 4px; height: 10px; margin-bottom: 12px; background: #e2e8f0;">
                                <div class="progress-bar progress-bar-<?= $completenessPercent === 100 ? 'success' : 'primary' ?>" role="progressbar" aria-valuenow="<?= $completenessPercent ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?= $completenessPercent ?>%;"></div>
                            </div>
                            <?php if (empty($missingFields)): ?>
                                <p class="text-success" style="font-size: 12px; margin-bottom: 0;">
                                    <i class="fa fa-check-circle"></i> All mandatory ERP profile fields are complete and verified.
                                </p>
                            <?php else: ?>
                                <p class="text-warning" style="font-size: 12px; margin-bottom: 4px;">
                                    <i class="fa fa-exclamation-triangle"></i> Pending profile fields: <strong><?= implode(', ', array_map('htmlspecialchars', $missingFields)) ?></strong>
                                </p>
                                <span class="text-muted" style="font-size: 11px;">Update your details to ensure accurate grade sheet generation and mentor reporting.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="erp-detail-card" style="height: 100%;">
                        <div class="erp-detail-card-header" style="justify-content: space-between;">
                            <div>
                                <i class="fa fa-step-forward text-primary"></i> Semester Progression
                            </div>
                            <span class="label label-info">Semester <?= intval($student['current_semester_id'] ?? 1) ?> Active</span>
                        </div>
                        <div style="padding: 16px; display: flex; flex-direction: column; justify-content: space-between;">
                            <div>
                                <p style="font-size: 12px; color: #475569; margin-bottom: 8px;">
                                    Current Term: <strong>Semester <?= intval($student['current_semester_id'] ?? 1) ?></strong>
                                </p>
                                <p class="text-muted" style="font-size: 11px; margin-bottom: 12px;">
                                    Academic progression advances you sequentially to the next semester and updates mentor assignments.
                                </p>
                            </div>
                            <div>
                                <?php if (intval($student['current_semester_id'] ?? 1) < 8): ?>
                                    <button type="button" class="btn btn-erp-primary btn-sm" data-toggle="modal" data-target="#progressionModal">
                                        <i class="fa fa-arrow-circle-right"></i> Advance to Semester <?= intval($student['current_semester_id'] ?? 1) + 1 ?>
                                    </button>
                                <?php else: ?>
                                    <span class="badge bg-green" style="padding: 6px 12px;"><i class="fa fa-check"></i> Final Semester Completed</span>
                                <?php endif; ?>
                            </div>
                        </div>
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
                                    $mentorName = student_val($student['current_mentor_name'], 'Not Assigned');
                                    if ($mentorName !== 'Not Available' && $mentorName !== 'Not Assigned') {
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
                                <div class="erp-detail-value"><?= !empty($student['created_at']) && $student['created_at'] !== '0000-00-00 00:00:00' ? date('d-m-Y', strtotime($student['created_at'])) : 'Not Available' ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ACADEMIC PROGRESSION & SEMESTER HISTORY -->
            <?php
            $studentId = intval($student['student_id']);
            $academicHistory = $db_handle->getStudentAcademicHistory($studentId);
            $currentSemNum = intval($student['current_semester_id'] ?? 0);
            ?>
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-history text-muted"></i> Academic Progression & Semester History</h3>
                    <div class="box-tools pull-right">
                        <span class="text-muted" style="font-size: 12px;"><?= count($academicHistory) ?> <?= count($academicHistory) === 1 ? 'Record' : 'Records' ?></span>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <?php if (!empty($academicHistory)): ?>
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
                                <?php foreach ($academicHistory as $hrow): 
                                    $isCurrentRow = ($currentSemNum > 0 && intval($hrow['semester_id']) === $currentSemNum);
                                    $statusBadge = ($hrow['history_status'] === 'Active' || $isCurrentRow) ? 'label-primary' : 'label-success';
                                    $statusText = ($hrow['history_status'] === 'Active' || $isCurrentRow) ? 'Active (Current)' : 'Completed';
                                ?>
                                    <tr class="<?= $isCurrentRow ? 'info' : '' ?>">
                                        <td>
                                            <strong><?= htmlspecialchars($hrow['semester_name']) ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($hrow['academic_year_name']) ?></td>
                                        <td><?= htmlspecialchars($hrow['division_name']) ?></td>
                                        <td><span class="text-mono"><?= htmlspecialchars($hrow['roll_no'] ?: ($student['roll_no'] ?? 'Not Available')) ?></span></td>
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
                                        <td class="col-num font-weight-bold"><?= !empty($hrow['cgpa']) ? htmlspecialchars($hrow['cgpa']) : 'Not Available' ?></td>
                                        <td class="col-center"><span class="label <?= $statusBadge ?>"><?= $statusText ?></span></td>
                                        <td><span class="text-muted"><?= !empty($hrow['enrolled_at']) && $hrow['enrolled_at'] !== '0000-00-00 00:00:00' ? htmlspecialchars($hrow['enrolled_at']) : 'Not Available' ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center" style="padding: 32px 16px;">
                            <p style="font-size: 13px; color: #475569; margin-bottom: 4px; font-weight: 500;">No semester progression records are available for this student.</p>
                            <span class="text-muted" style="font-size: 12px;">Semester history will appear here once academic enrollment and progression data is recorded.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- Contact Information Update Modal -->
<div class="modal fade" id="updateContactModal" tabindex="-1" role="dialog" aria-labelledby="updateContactModalLabel">
    <div class="modal-dialog" role="document" style="max-width: 480px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="updateContactModalLabel"><i class="fa fa-phone"></i> Update Contact Details</h4>
            </div>
            <form action="student_dashboard.php" method="POST">
                <div class="modal-body">
                    <?= DBController::getCsrfInputField() ?>
                    <input type="hidden" name="action" value="update_contact">

                    <div class="form-group">
                        <label for="contact_mobile">Mobile Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="contact_mobile" name="mobile" value="<?= htmlspecialchars($student['mobile'] ?? '') ?>" required pattern="[0-9]{10,15}" placeholder="10-digit mobile number">
                        <span class="help-block" style="font-size: 11px;">Enter your primary 10-digit active phone number for ERP communications.</span>
                    </div>

                    <div class="form-group">
                        <label for="contact_email">Institute Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="contact_email" name="email" value="<?= htmlspecialchars($student['email'] ?? '') ?>" required placeholder="name@tcetmumbai.in">
                        <span class="help-block" style="font-size: 11px;">Your registered email address for notifications and submissions.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-erp-primary btn-sm"><i class="fa fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Semester Progression Confirmation Modal -->
<?php if ($student && intval($student['current_semester_id'] ?? 1) < 8): 
    $targetSemester = intval($student['current_semester_id'] ?? 1) + 1;
?>
<div class="modal fade" id="progressionModal" tabindex="-1" role="dialog" aria-labelledby="progressionModalLabel">
    <div class="modal-dialog" role="document" style="max-width: 500px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="progressionModalLabel"><i class="fa fa-step-forward"></i> Confirm Semester Progression</h4>
            </div>
            <form action="student_dashboard.php" method="POST">
                <div class="modal-body">
                    <?= DBController::getCsrfInputField() ?>
                    <input type="hidden" name="action" value="progress_semester">
                    <input type="hidden" name="current_semester_id" value="<?= intval($student['current_semester_id'] ?? 1) ?>">
                    <input type="hidden" name="target_semester_id" value="<?= $targetSemester ?>">

                    <div class="alert alert-info" style="font-size: 12px; margin-bottom: 16px;">
                        <i class="fa fa-info-circle"></i> You are requesting advancement from <strong>Semester <?= intval($student['current_semester_id'] ?? 1) ?></strong> to <strong>Semester <?= $targetSemester ?></strong>.
                    </div>

                    <p style="font-size: 13px; color: #334155; line-height: 1.5;">
                        Academic Progression Rules:
                    </p>
                    <ul style="font-size: 12px; color: #64748b; padding-left: 20px;">
                        <li>Your previous term records will be finalized and marked as completed in your permanent academic transcript.</li>
                        <li>Your specialization subject and faculty mentor assignment will be automatically linked to Semester <?= $targetSemester ?>.</li>
                        <li>Progression is non-destructive and preserves all historical academic logs.</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-erp-primary btn-sm"><i class="fa fa-check"></i> Confirm Progression to Semester <?= $targetSemester ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include "header/footer.php"; ?>

