<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . "/../database/db_connect.php";
$db_handle = new DBController();

if (!isset($_REQUEST['id'])) {
    echo "<div class='alert alert-danger'>No student ID provided.</div>";
    exit;
}

$student_id = intval($_REQUEST['id']);

$sql = "SELECT s.*,
               c.class_name,
               COALESCE(NULLIF(TRIM(sec.sections), ''), NULLIF(TRIM(divm.division_name), '')) AS section_name,
               d.department_name,
               sp.specialization_name,
               sub.subject_name AS specialization_subject_name,
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

if (empty($row['specialization_subject_name']) && !empty($row['current_history_subject_id'])) {
    $hSubRes = mysqli_query($db_handle->conn, "SELECT subject_name FROM st_specialization_subject_master WHERE subject_id = " . intval($row['current_history_subject_id']));
    if ($hSubRes && ($hSub = mysqli_fetch_assoc($hSubRes))) {
        $row['specialization_subject_name'] = $hSub['subject_name'];
    }
}

$specialization_name = strtolower($row['specialization_name'] ?? '');
$is_minor_multidisciplinary = strpos($specialization_name, 'minor multidisciplinary') !== false;
$is_honours = strpos($specialization_name, 'honour') !== false || strpos($specialization_name, 'honor') !== false;
$is_research = strpos($specialization_name, 'research') !== false;

$statusBadge = ($row['status'] == '1') ? 'label-success' : 'label-warning';
$statusText = ($row['status'] == '1') ? 'Active' : 'Pending';

if (!function_exists('fmt_val')) {
    function fmt_val($val) {
        $v = trim((string)($val ?? ''));
        return $v !== '' ? htmlspecialchars($v) : 'N/A';
    }
}
?>

<!-- STUDENT BASIC INFORMATION -->
<div class="erp-detail-card">
    <div class="erp-detail-card-header" style="justify-content: space-between;">
        <div>
            <i class="fa fa-user-circle"></i> Student Profile &mdash; <?= fmt_val($row['fname']) ?>
        </div>
        <div>
            <span class="label <?= $statusBadge ?>"><?= $statusText ?></span>
        </div>
    </div>
    <div class="erp-detail-grid">
        <div class="erp-detail-item">
            <div class="erp-detail-label">ERP ID / Registration No</div>
            <div class="erp-detail-value text-mono"><?= fmt_val($row['registration_no']) ?></div>
        </div>
        <div class="erp-detail-item">
            <div class="erp-detail-label">Full Name</div>
            <div class="erp-detail-value"><?= fmt_val($row['fname']) ?></div>
        </div>
        <div class="erp-detail-item">
            <div class="erp-detail-label">Roll Number</div>
            <div class="erp-detail-value text-mono"><?= fmt_val($row['roll_no']) ?></div>
        </div>
        <div class="erp-detail-item">
            <div class="erp-detail-label">Created Date</div>
            <div class="erp-detail-value"><?= !empty($row['created_at']) ? date('d-m-Y H:i', strtotime($row['created_at'])) : 'N/A' ?></div>
        </div>
    </div>
</div>

<!-- ACADEMIC DETAILS -->
<div class="erp-detail-card">
    <div class="erp-detail-card-header">
        <i class="fa fa-graduation-cap"></i> Current Academic Details
    </div>
    <div class="erp-detail-grid">
        <div class="erp-detail-item">
            <div class="erp-detail-label">Academic Year</div>
            <div class="erp-detail-value"><?= fmt_val($row['academic_year_name']) ?></div>
        </div>
        <div class="erp-detail-item">
            <div class="erp-detail-label">Current Semester</div>
            <div class="erp-detail-value"><?= fmt_val($row['semester_name']) ?></div>
        </div>
        <div class="erp-detail-item">
            <div class="erp-detail-label">Class & Division</div>
            <div class="erp-detail-value"><?= fmt_val($row['class_name']) ?> &bull; Div <?= fmt_val($row['section_name']) ?></div>
        </div>
        <div class="erp-detail-item">
            <div class="erp-detail-label">Department</div>
            <div class="erp-detail-value"><?= fmt_val($row['department_name']) ?></div>
        </div>
        <div class="erp-detail-item">
            <div class="erp-detail-label">Specialization</div>
            <div class="erp-detail-value">
                <?= fmt_val($row['specialization_name']) ?>
                <?php if ($is_minor_multidisciplinary): ?>
                    <span class="label label-default" style="font-size: 10px; margin-left: 4px;">Minor Multi</span>
                <?php elseif ($is_honours): ?>
                    <span class="label label-primary" style="font-size: 10px; margin-left: 4px;">Honours</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="erp-detail-item">
            <div class="erp-detail-label">Specialization Subject</div>
            <div class="erp-detail-value"><?= fmt_val($row['specialization_subject_name']) ?></div>
        </div>
        <div class="erp-detail-item">
            <div class="erp-detail-label">Aggregate CGPA</div>
            <div class="erp-detail-value"><?= !empty($row['cgpa']) ? number_format($row['cgpa'], 2) : 'N/A' ?></div>
        </div>
        <div class="erp-detail-item">
            <div class="erp-detail-label">Graduation Year</div>
            <div class="erp-detail-value"><?= fmt_val($row['grad_year']) ?></div>
        </div>
    </div>
</div>

<!-- CONTACT DETAILS -->
<div class="erp-detail-card">
    <div class="erp-detail-card-header">
        <i class="fa fa-phone"></i> Contact Details
    </div>
    <div class="erp-detail-grid">
        <div class="erp-detail-item">
            <div class="erp-detail-label">Mobile Number</div>
            <div class="erp-detail-value"><?= fmt_val($row['mobile']) ?></div>
        </div>
        <div class="erp-detail-item">
            <div class="erp-detail-label">Email Address</div>
            <div class="erp-detail-value"><?= !empty($row['email']) ? '<a href="mailto:' . htmlspecialchars($row['email']) . '">' . htmlspecialchars($row['email']) . '</a>' : 'N/A' ?></div>
        </div>
    </div>
</div>

<!-- ACADEMIC PROGRESSION & SEMESTER HISTORY -->
<?php
$academicHistory = $db_handle->getStudentAcademicHistory($student_id);
$currentSemNum = intval($row['current_semester_id'] ?? 0);
?>
<div class="box box-solid" style="margin-top: 15px; margin-bottom: 15px;">
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
                            <td><span class="text-mono"><?= htmlspecialchars($hrow['roll_no'] ?: ($row['roll_no'] ?? 'N/A')) ?></span></td>
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

<!-- DOCUMENTS -->
<?php if (!empty($row['mark_list'])): ?>
<div class="erp-detail-card">
    <div class="erp-detail-card-header">
        <i class="fa fa-file-pdf-o"></i> Uploaded Documents
    </div>
    <div style="padding: 14px;">
        <?php
        $mark_list_files = explode(',', $row['mark_list']);
        foreach ($mark_list_files as $file):
            $file = trim($file);
            if (!empty($file)):
        ?>
            <a href="uploads/marklists/<?= htmlspecialchars($file) ?>" target="_blank" class="btn btn-default btn-sm" style="margin-right: 6px; margin-bottom: 6px;">
                <i class="fa fa-download text-primary"></i> <?= htmlspecialchars($file) ?>
            </a>
        <?php 
            endif;
        endforeach; 
        ?>
    </div>
</div>
<?php endif; ?>