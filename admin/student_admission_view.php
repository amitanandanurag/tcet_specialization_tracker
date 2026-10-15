<?php include "header/header.php"; ?>
<?php

$student_id = intval($_REQUEST['id'] ?? 0);
$student = null;
$uploadedFiles = [];

if ($student_id > 0) {
    $sql = "SELECT s.student_id, s.academic_year_id, s.registration_no, s.roll_no, s.grad_year,
                   s.cgpa, s.fname, s.mobile, s.email, s.status, s.created_at, s.mark_list,
                   s.class_id, s.division_id, s.department_id, s.specialization_id, 
                   s.specialization_subject_id, s.minor_course_id, s.minor_subject_id,
                   s.current_semester_id,
                   c.class_name, sec.sections AS division_name, d.department_name,
                   sp.specialization_name, sub.subject_name, current_subject.subject_name AS history_subject_name,
                   ay.session_name, sem.semester_name
            FROM st_student_master s
            LEFT JOIN st_class_master c ON c.class_id = s.class_id
            LEFT JOIN st_section_master sec ON sec.id = s.division_id
            LEFT JOIN st_department_master d ON d.department_id = s.department_id
            LEFT JOIN st_specialization_master sp ON sp.specialization_id = s.specialization_id
            LEFT JOIN st_specialization_subject_master sub ON sub.subject_id = s.specialization_subject_id
            LEFT JOIN st_student_semester_history current_history ON current_history.student_id = s.student_id AND current_history.semester_id = s.current_semester_id
            LEFT JOIN st_specialization_subject_master current_subject ON current_subject.subject_id = current_history.specialization_subject_id
            LEFT JOIN st_session_master ay ON ay.session_id = s.academic_year_id
            LEFT JOIN st_semester_master sem ON sem.semester_id = s.current_semester_id
            WHERE s.student_id = ?
            LIMIT 1";
    
    $stmt = mysqli_prepare($db_handle->conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $student = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        // Parse uploaded files
        if ($student && !empty($student['mark_list'])) {
            $uploadedFiles = explode(',', $student['mark_list']);
        }
    }
}

if (!$student) {
    echo '<div class="content-wrapper"><section class="content"><div class="alert alert-danger">Student admission record not found.</div></section></div>';
    include "header/footer.php";
    exit;
}

$statusText = 'Pending Approval';
$statusClass = 'label-warning';
if ((int)$student['status'] === 1) {
    $statusText = 'Approved / Active';
    $statusClass = 'label-success';
}

if (!function_exists('formatValue')) {
    function formatValue($value) {
        $val = trim((string)($value ?? ''));
        return $val !== '' ? htmlspecialchars($val) : 'N/A';
    }
}
?>

<div class="content-wrapper">
    <!-- Page Header -->
    <section class="content-header">
        <h1>
            Student Admission Details
            <small>Institutional Student Record #<?= $student_id ?></small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="student_admission.php">Admission</a></li>
            <li class="active">Student #<?= $student_id ?></li>
        </ol>
    </section>

    <!-- Main Content -->
    <section class="content">
        <!-- Top Action & Status Strip -->
        <div style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; gap: 8px;">
                <a href="student_admission.php?edit=1&id=<?= $student_id ?>" class="btn-erp-primary">
                    <i class="fa fa-edit"></i> Edit Details / Promote
                </a>
                <a href="student-info.php" class="btn-erp-secondary">
                    <i class="fa fa-arrow-left"></i> Back to Student List
                </a>
            </div>
            <div>
                <span class="label <?= $statusClass ?>" style="padding: 4px 10px; font-size: 12px;">
                    <?= $statusText ?>
                </span>
            </div>
        </div>

        <!-- Official Details Card -->
        <div class="erp-detail-card">
            <div class="erp-detail-card-header">
                <i class="fa fa-building"></i> Official Enrollment Information
            </div>
            <div class="erp-detail-grid">
                <div class="erp-detail-item">
                    <div class="erp-detail-label">Academic Year</div>
                    <div class="erp-detail-value"><?= formatValue($student['session_name']) ?></div>
                </div>
                <div class="erp-detail-item">
                    <div class="erp-detail-label">ERP ID / Registration No</div>
                    <div class="erp-detail-value text-mono"><?= formatValue($student['registration_no']) ?></div>
                </div>
                <div class="erp-detail-item">
                    <div class="erp-detail-label">Roll Number</div>
                    <div class="erp-detail-value text-mono"><?= formatValue($student['roll_no']) ?></div>
                </div>
                <div class="erp-detail-item">
                    <div class="erp-detail-label">Class & Division</div>
                    <div class="erp-detail-value"><?= formatValue($student['class_name']) ?> &bull; Div <?= formatValue($student['division_name']) ?></div>
                </div>
                <div class="erp-detail-item">
                    <div class="erp-detail-label">Current Semester</div>
                    <div class="erp-detail-value"><?= formatValue($student['semester_name']) ?></div>
                </div>
                <div class="erp-detail-item">
                    <div class="erp-detail-label">Graduating Year</div>
                    <div class="erp-detail-value"><?= formatValue($student['grad_year']) ?></div>
                </div>
            </div>
        </div>

        <!-- Academic Details Card -->
        <div class="erp-detail-card">
            <div class="erp-detail-card-header">
                <i class="fa fa-graduation-cap"></i> Academic & Specialization Track
            </div>
            <div class="erp-detail-grid">
                <div class="erp-detail-item">
                    <div class="erp-detail-label">Department</div>
                    <div class="erp-detail-value"><?= formatValue($student['department_name']) ?></div>
                </div>
                <div class="erp-detail-item">
                    <div class="erp-detail-label">Specialization</div>
                    <div class="erp-detail-value"><?= formatValue($student['specialization_name']) ?></div>
                </div>
                <div class="erp-detail-item">
                    <div class="erp-detail-label">Enrolled Subject</div>
                    <div class="erp-detail-value"><?= formatValue($student['subject_name'] ?: ($student['history_subject_name'] ?? '')) ?></div>
                </div>
                <div class="erp-detail-item">
                    <div class="erp-detail-label">Assigned Faculty Mentor</div>
                    <div class="erp-detail-value">
                        <?php 
                            $resolvedMentor = $db_handle->getResolvedMentorForStudent($student_id);
                            if ($resolvedMentor && !empty($resolvedMentor['mentor_name'])) {
                                echo '<span class="erp-mentor-faculty"><i class="fa fa-user"></i> ' . htmlspecialchars($resolvedMentor['mentor_name']) . '</span>';
                            } else {
                                echo '<span class="erp-mentor-unassigned">Not Assigned</span>';
                            }
                        ?>
                    </div>
                </div>
                <div class="erp-detail-item">
                    <div class="erp-detail-label">Aggregate CGPA</div>
                    <div class="erp-detail-value"><?= formatValue($student['cgpa']) ?></div>
                </div>
                <div class="erp-detail-item">
                    <div class="erp-detail-label">Registration Date</div>
                    <div class="erp-detail-value"><?= !empty($student['created_at']) ? date('d-m-Y H:i', strtotime($student['created_at'])) : 'N/A' ?></div>
                </div>
            </div>
        </div>

        <!-- Personal Details Card -->
        <div class="erp-detail-card">
            <div class="erp-detail-card-header">
                <i class="fa fa-user"></i> Personal & Contact Details
            </div>
            <div class="erp-detail-grid">
                <div class="erp-detail-item">
                    <div class="erp-detail-label">Full Name</div>
                    <div class="erp-detail-value"><?= formatValue($student['fname']) ?></div>
                </div>
                <div class="erp-detail-item">
                    <div class="erp-detail-label">Email Address</div>
                    <div class="erp-detail-value"><?= !empty($student['email']) ? '<a href="mailto:' . htmlspecialchars($student['email']) . '">' . htmlspecialchars($student['email']) . '</a>' : 'N/A' ?></div>
                </div>
                <div class="erp-detail-item">
                    <div class="erp-detail-label">Mobile Number</div>
                    <div class="erp-detail-value"><?= formatValue($student['mobile']) ?></div>
                </div>
            </div>
        </div>

        <!-- ACADEMIC PROGRESSION & SEMESTER HISTORY -->
        <?php
        $academicHistory = $db_handle->getStudentAcademicHistory($student_id);
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
                                    <td class="col-num font-weight-bold"><?= !empty($hrow['cgpa']) ? htmlspecialchars($hrow['cgpa']) : 'N/A' ?></td>
                                    <td class="col-center"><span class="label <?= $statusBadge ?>"><?= $statusText ?></span></td>
                                    <td><span class="text-muted"><?= !empty($hrow['enrolled_at']) && $hrow['enrolled_at'] !== '0000-00-00 00:00:00' ? htmlspecialchars($hrow['enrolled_at']) : 'N/A' ?></span></td>
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

        <!-- Uploaded Documents -->
        <?php if (!empty($uploadedFiles)): ?>
        <div class="erp-detail-card">
            <div class="erp-detail-card-header">
                <i class="fa fa-file-pdf-o"></i> Uploaded Documents & Marksheets
            </div>
            <div style="padding: 14px;">
                <?php foreach ($uploadedFiles as $file): ?>
                    <?php
                    $filePath = "uploads/marklists/" . trim($file);
                    if (file_exists($filePath)):
                    ?>
                        <a href="<?= $filePath ?>" target="_blank" download class="btn btn-default btn-sm" style="margin-right: 6px; margin-bottom: 6px;">
                            <i class="fa fa-download text-primary"></i> <?= htmlspecialchars(trim($file)) ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </section>
</div>

<?php include "header/footer.php"; ?>
