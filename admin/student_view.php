<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once "../database/db_connect.php";
$db_handle = new DBController();

if (!isset($_REQUEST['id'])) {
    echo "<div class='alert alert-danger'>No student ID provided.</div>";
    exit;
}

$student_id = intval($_REQUEST['id']);

$sql = "SELECT
    sm.*,
    IFNULL(rsi.subject_name, '') AS research_component_i_name,
    IFNULL(rsii.subject_name, '') AS research_component_ii_name,
    IFNULL(cl.class_name, '') AS class_name,
    IFNULL(sec.sections, '') AS section_name,
    IFNULL(dep.department_name, '') AS department_name,
    IFNULL(sp.specialization_name, '') AS specialization_name,
    IFNULL(ssb.subject_name, '') AS specialization_subject_name,
    current_history.specialization_subject_id AS current_history_subject_id,
    IFNULL(mc.course_name, '') AS minor_course_name,
    IFNULL(ms.subject_name, '') AS minor_subject_name,
    IFNULL(sess.session_name, '') AS academic_year_name,
    IFNULL(sem.semester_name, '') AS semester_name
FROM st_student_master sm
LEFT JOIN st_class_master cl ON cl.class_id = sm.class_id
LEFT JOIN st_section_master sec ON sec.id = sm.division_id
LEFT JOIN st_department_master dep ON dep.department_id = sm.department_id
LEFT JOIN st_specialization_master sp ON sp.specialization_id = sm.specialization_id
LEFT JOIN st_student_semester_history current_history ON current_history.student_id = sm.student_id AND current_history.semester_id = sm.current_semester_id
LEFT JOIN st_specialization_subject_master ssb ON ssb.subject_id = COALESCE(NULLIF(sm.specialization_subject_id, 0), current_history.specialization_subject_id)
LEFT JOIN st_minorcourse mc ON mc.course_id = sm.minor_course_id
LEFT JOIN st_minorsubject ms ON ms.subject_id = sm.minor_subject_id
LEFT JOIN st_session_master sess ON sess.session_id = sm.academic_year_id
LEFT JOIN st_semester_master sem ON sem.semester_id = sm.current_semester_id
LEFT JOIN st_specialization_subject_master rsi ON rsi.subject_id = sm.research_component_i_id
LEFT JOIN st_specialization_subject_master rsii ON rsii.subject_id = sm.research_component_ii_id
WHERE sm.student_id = $student_id";

$result = $db_handle->query($sql);
$row = ($result && mysqli_num_rows($result) > 0) ? $result->fetch_assoc() : null;

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

<!-- ACADEMIC PROGRESSION TIMELINE & HISTORY -->
<?php
$academicHistory = $db_handle->getStudentAcademicHistory($student_id);
$currentSemNum = intval($row['current_semester_id'] ?? 5);
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
        <span class="text-muted" style="font-size: 11px;">Current: <strong><?= htmlspecialchars($row['semester_name'] ?? ('Semester ' . $currentSemNum)) ?></strong></span>
    </div>
    <div class="erp-progression-track">
        <?php foreach ($allSemesters as $semNum => $semLabel): 
            $isPast = $semNum < $currentSemNum;
            $isCurrent = $semNum === $currentSemNum;
            $isFuture = $semNum > $currentSemNum;
            $hasData = isset($historyBySem[$semNum]);
            
            $stepClass = $isCurrent ? 'is-current' : ($isPast ? 'is-completed' : '');
            $stepBadge = $isCurrent ? 'Current' : ($isPast ? 'Completed' : 'Upcoming');
            $stepBadgeClass = $isCurrent ? 'label-primary' : ($isPast ? 'label-success' : 'label-default');
        ?>
            <div class="erp-progression-step <?= $stepClass ?>">
                <div class="erp-progression-step-title"><?= $semLabel ?></div>
                <span class="label <?= $stepBadgeClass ?>" style="font-size: 9px; padding: 1px 4px;"><?= $stepBadge ?></span>
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

<!-- SEMESTER REGISTRATION & COURSE HISTORY -->
<div class="erp-card" style="margin-top: 15px; margin-bottom: 15px;">
    <div class="erp-card-header">
        <div>
            <h3 class="erp-card-title"><i class="fa fa-history text-primary" style="margin-right: 6px;"></i> Semester & Specialization History Ledger</h3>
            <p class="erp-card-subtitle">Complete chronological record of academic semesters, subjects, and mentors</p>
        </div>
    </div>
    <div class="erp-card-body table-responsive" style="padding: 0;">
        <table class="erp-table">
            <thead>
                <tr>
                    <th style="width: 100px;">Semester</th>
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
                        $hStatusBadge = ($hrow['history_status'] === 'Active' || $isCurrentRow) ? 'erp-badge-purple' : 'erp-badge-success';
                        $hStatusText = ($hrow['history_status'] === 'Active' || $isCurrentRow) ? 'Active (Current)' : 'Completed';
                    ?>
                        <tr class="<?= $isCurrentRow ? 'info' : '' ?>">
                            <td>
                                <strong style="color: #0f172a;"><?= htmlspecialchars($hrow['semester_name']) ?></strong>
                            </td>
                            <td><span class="text-muted" style="font-size: 12px;"><?= htmlspecialchars($hrow['academic_year_name']) ?></span></td>
                            <td><?= htmlspecialchars($hrow['division_name']) ?></td>
                            <td><span class="text-mono"><?= htmlspecialchars($hrow['roll_no'] ?: ($row['roll_no'] ?? 'N/A')) ?></span></td>
                            <td><span style="font-size: 12px;"><?= htmlspecialchars($hrow['specialization_name']) ?></span></td>
                            <td><strong><?= htmlspecialchars($hrow['subject_name']) ?></strong></td>
                            <td>
                                <?php if ($hrow['mentor_name'] && $hrow['mentor_name'] !== 'Not Assigned'): ?>
                                    <span class="erp-badge erp-badge-success">
                                        <i class="fa fa-user"></i> <?= htmlspecialchars($hrow['mentor_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="erp-badge erp-badge-danger">Not Assigned</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-num font-weight-bold"><?= htmlspecialchars($hrow['cgpa'] ?? 'N/A') ?></td>
                            <td class="col-center"><span class="erp-badge <?= $hStatusBadge ?>"><?= $hStatusText ?></span></td>
                            <td><span class="text-muted" style="font-size: 12px;"><?= htmlspecialchars($hrow['enrolled_at']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted" style="padding: 20px;">No historical semester registrations recorded for this student.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
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