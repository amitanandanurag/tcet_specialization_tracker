<?php
session_start();
require "../database/db_connect.php";
$db_handle = new DBController();

if (!isset($_REQUEST['id'])) {
    echo "<div class='alert alert-danger'>No student ID provided.</div>";
    exit;
}

$student_id = intval($_REQUEST['id']);

$sql = "SELECT
    sm.student_id,
    sm.registration_no,
    sm.class_id,
    sm.division_id,
    sm.grad_year,
    sm.roll_no,
    sm.department_id,
    sm.specialization_id,
    sm.specialization_subject_id,
    sm.cgpa,
    sm.fname,
    sm.mobile,
    sm.email,
    sm.mark_list,
    sm.status,
    sm.m_sem1,
    sm.m_sem2,
    sm.m_sem3,
    sm.created_at,
    sm.academic_year_id,
    sm.current_semester_id,
    IFNULL(cl.class_name, '') AS class_name,
    IFNULL(sec.sections, '') AS section_name,
    IFNULL(dep.department_name, '') AS department_name,
    IFNULL(sp.specialization_name, '') AS specialization_name,
    IFNULL(ssb.subject_name, '') AS specialization_subject_name,
    IFNULL(sess.session_name, '') AS academic_year_name,
    IFNULL(sem.semester_name, '') AS semester_name
FROM st_student_master sm
LEFT JOIN st_class_master cl ON cl.class_id = sm.class_id
LEFT JOIN st_section_master sec ON sec.id = sm.division_id
LEFT JOIN st_department_master dep ON dep.department_id = sm.department_id
LEFT JOIN st_specialization_master sp ON sp.specialization_id = sm.specialization_id
LEFT JOIN st_specialization_subject_master ssb ON ssb.subject_id = sm.specialization_subject_id
LEFT JOIN st_session_master sess ON sess.session_id = sm.academic_year_id
LEFT JOIN st_semester_master sem ON sem.semester_id = sm.current_semester_id
WHERE sm.student_id = $student_id AND sm.status = '1'";

$result = $db_handle->query($sql);
$row = $result ? $result->fetch_assoc() : null;

if (!$row) {
    echo "<div class='alert alert-danger'>Student record not found.</div>";
    exit;
}
?>
<style>
    .view-section {
        margin-bottom: 25px;
        border: 1px solid #ddd;
        border-radius: 5px;
        overflow: hidden;
    }
    .view-section-header {
        background-color: #423cbc;
        color: white;
        padding: 10px 15px;
        font-size: 16px;
        font-weight: bold;
    }
    .view-field {
        margin-bottom: 15px;
        padding: 0 15px;
    }
    .view-label {
        font-weight: bold;
        color: #333;
        margin-bottom: 5px;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .view-value {
        color: #666;
        padding: 8px 12px;
        background-color: #f9f9f9;
        border-radius: 4px;
        font-size: 14px;
        word-break: break-word;
    }
    .status-active {
        color: green;
        font-weight: bold;
    }
    .status-inactive {
        color: red;
        font-weight: bold;
    }
    .table-marks {
        width: 100%;
        background-color: #f9f9f9;
        border-collapse: collapse;
    }
    .table-marks td {
        padding: 8px;
        border: 1px solid #ddd;
        vertical-align: top;
    }
    .table-marks td:first-child {
        font-weight: bold;
        width: 30%;
        background-color: #e9ecef;
    }
</style>

<!-- STUDENT BASIC INFORMATION -->
<div class="view-section">
    <div class="view-section-header">
        <i class="fa fa-graduation-cap"></i> STUDENT BASIC INFORMATION
    </div>
    <div class="row" style="padding: 15px;">
        <div class="col-md-6">
            <div class="view-field">
                <div class="view-label">Registration Number:</div>
                <div class="view-value"><strong><?php echo htmlspecialchars($row['registration_no'] ?? 'N/A'); ?></strong></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="view-field">
                <div class="view-label">Student Name:</div>
                <div class="view-value"><?php echo htmlspecialchars($row['fname'] ?? 'N/A'); ?></div>
            </div>
        </div>
    </div>
    <div class="row" style="padding: 15px;">
        <div class="col-md-6">
            <div class="view-field">
                <div class="view-label">Status:</div>
                <div class="view-value <?php echo ($row['status'] == '1') ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo ($row['status'] == '1') ? 'Active' : 'Inactive'; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="view-field">
                <div class="view-label">Created Date:</div>
                <div class="view-value"><?php echo date('d-m-Y H:i:s', strtotime($row['created_at'] ?? 'now')); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ACADEMIC DETAILS -->
<div class="view-section">
    <div class="view-section-header">
        <i class="fa fa-book"></i> ACADEMIC DETAILS
    </div>
    <div class="row" style="padding: 15px;">
        <div class="col-md-4">
            <div class="view-field">
                <div class="view-label">Academic Year:</div>
                <div class="view-value"><?php echo htmlspecialchars($row['academic_year_name'] ?? 'N/A'); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="view-field">
                <div class="view-label">Current Semester:</div>
                <div class="view-value"><?php echo htmlspecialchars($row['semester_name'] ?? 'N/A'); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="view-field">
                <div class="view-label">Graduation Year:</div>
                <div class="view-value"><?php echo htmlspecialchars($row['grad_year'] ?? 'N/A'); ?></div>
            </div>
        </div>
    </div>
    <div class="row" style="padding: 15px;">
        <div class="col-md-4">
            <div class="view-field">
                <div class="view-label">Class:</div>
                <div class="view-value"><?php echo htmlspecialchars($row['class_name'] ?? 'N/A'); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="view-field">
                <div class="view-label">Division:</div>
                <div class="view-value"><?php echo htmlspecialchars($row['section_name'] ?? 'N/A'); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="view-field">
                <div class="view-label">Roll Number:</div>
                <div class="view-value"><?php echo htmlspecialchars($row['roll_no'] ?? 'N/A'); ?></div>
            </div>
        </div>
    </div>
    <div class="row" style="padding: 15px;">
        <div class="col-md-4">
            <div class="view-field">
                <div class="view-label">Department:</div>
                <div class="view-value"><?php echo htmlspecialchars($row['department_name'] ?? 'N/A'); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="view-field">
                <div class="view-label">Specialization:</div>
                <div class="view-value"><?php echo htmlspecialchars($row['specialization_name'] ?? 'N/A'); ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="view-field">
                <div class="view-label">Specialization Subject:</div>
                <div class="view-value"><?php echo htmlspecialchars($row['specialization_subject_name'] ?? 'N/A'); ?></div>
            </div>
        </div>
    </div>
    <div class="row" style="padding: 15px;">
        <div class="col-md-4">
            <div class="view-field">
                <div class="view-label">CGPA (Aggregate):</div>
                <div class="view-value"><?php echo !empty($row['cgpa']) ? number_format($row['cgpa'], 2) : 'N/A'; ?></div>
            </div>
        </div>
    </div>
</div>

<!-- CONTACT DETAILS -->
<div class="view-section">
    <div class="view-section-header">
        <i class="fa fa-phone"></i> CONTACT DETAILS
    </div>
    <div class="row" style="padding: 15px;">
        <div class="col-md-4">
            <div class="view-field">
                <div class="view-label">Mobile Number:</div>
                <div class="view-value">
                    <?php echo htmlspecialchars($row['mobile'] ?? 'N/A'); ?>
                    <?php if (!empty($row['mobile'])): ?>
                        <a href="tel:<?php echo $row['mobile']; ?>" class="btn btn-xs btn-info" style="margin-left: 10px;">
                            <i class="fa fa-phone"></i> Call
                        </a>
                        <a href="https://wa.me/91<?php echo $row['mobile']; ?>" target="_blank" class="btn btn-xs btn-success">
                            <i class="fa fa-whatsapp"></i> WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="view-field">
                <div class="view-label">Email:</div>
                <div class="view-value">
                    <?php echo !empty($row['email']) ? '<a href="mailto:' . htmlspecialchars($row['email']) . '">' . htmlspecialchars($row['email']) . '</a>' : 'N/A'; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MARKSHEETS / SEMESTER RESULTS -->
<?php if (!empty($row['m_sem1']) || !empty($row['m_sem2']) || !empty($row['m_sem3'])): ?>
<div class="view-section">
    <div class="view-section-header">
        <i class="fa fa-file-text-o"></i> SEMESTER MARKSHEETS
    </div>
    <div class="row" style="padding: 15px;">
        <div class="col-md-12">
            <table class="table-marks">
                <?php if (!empty($row['m_sem1'])): ?>
                <tr>
                    <td>Semester 1 Marksheet:</td>
                    <td><?php echo nl2br(htmlspecialchars($row['m_sem1'])); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($row['m_sem2'])): ?>
                <tr>
                    <td>Semester 2 Marksheet:</td>
                    <td><?php echo nl2br(htmlspecialchars($row['m_sem2'])); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($row['m_sem3'])): ?>
                <tr>
                    <td>Semester 3 Marksheet:</td>
                    <td><?php echo nl2br(htmlspecialchars($row['m_sem3'])); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MARK LIST DOCUMENT -->
<?php if (!empty($row['mark_list'])): ?>
<div class="view-section">
    <div class="view-section-header">
        <i class="fa fa-file-pdf-o"></i> DOCUMENTS
    </div>
    <div class="row" style="padding: 15px;">
        <div class="col-md-12">
            <div class="view-field">
                <div class="view-label">Mark List Document:</div>
                <div class="view-value">
                    <a href="uploads/<?php echo htmlspecialchars($row['mark_list']); ?>" target="_blank" class="btn btn-primary btn-sm">
                        <i class="fa fa-download"></i> View/Download Mark List
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    $(document).ready(function() {
        // Add any additional initialization if needed
        console.log("Student view loaded for ID: <?php echo $student_id; ?>");
    });
</script>