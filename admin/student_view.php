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
    sm.minor_course_id,
    sm.minor_subject_id,
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
    sm.research_component_i_id,
    sm.research_core_vii,
    sm.research_component_ii_id,
    sm.research_core_viii,
    IFNULL(rsi.subject_name, '') AS research_component_i_name,
    IFNULL(rsii.subject_name, '') AS research_component_ii_name,
    IFNULL(cl.class_name, '') AS class_name,
    IFNULL(sec.sections, '') AS section_name,
    IFNULL(dep.department_name, '') AS department_name,
    IFNULL(sp.specialization_name, '') AS specialization_name,
    IFNULL(ssb.subject_name, '') AS specialization_subject_name,
    IFNULL(mc.course_name, '') AS minor_course_name,
    IFNULL(ms.subject_name, '') AS minor_subject_name,
    IFNULL(sess.session_name, '') AS academic_year_name,
    IFNULL(sem.semester_name, '') AS semester_name
FROM st_student_master sm
LEFT JOIN st_class_master cl ON cl.class_id = sm.class_id
LEFT JOIN st_section_master sec ON sec.id = sm.division_id
LEFT JOIN st_department_master dep ON dep.department_id = sm.department_id
LEFT JOIN st_specialization_master sp ON sp.specialization_id = sm.specialization_id
LEFT JOIN st_specialization_subject_master ssb ON ssb.subject_id = sm.specialization_subject_id
LEFT JOIN st_minorcourse mc ON mc.course_id = sm.minor_course_id
LEFT JOIN st_minorsubject ms ON ms.subject_id = sm.minor_subject_id
LEFT JOIN st_session_master sess ON sess.session_id = sm.academic_year_id
LEFT JOIN st_semester_master sem ON sem.semester_id = sm.current_semester_id
LEFT JOIN st_specialization_subject_master rsi ON rsi.subject_id = sm.research_component_i_id
LEFT JOIN st_specialization_subject_master rsii ON rsii.subject_id = sm.research_component_ii_id
WHERE sm.student_id = $student_id";

$result = $db_handle->query($sql);
$row = $result ? $result->fetch_assoc() : null;

if (!$row) {
    echo "<div class='alert alert-danger'>Student record not found.</div>";
    exit;
}

// Determine specialization type for display
$specialization_name = strtolower($row['specialization_name'] ?? '');
$is_minor_multidisciplinary = strpos($specialization_name, 'minor multidisciplinary') !== false;
$is_honours = strpos($specialization_name, 'honour') !== false || strpos($specialization_name, 'honor') !== false;
$is_research = strpos($specialization_name, 'research') !== false;
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
    .badge-minor {
        background-color: #ff9800;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        margin-left: 8px;
    }
    .badge-honours {
        background-color: #9c27b0;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        margin-left: 8px;
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
                <div class="view-value">
                    <?php 
                    $grad_year = $row['grad_year'] ?? '';
                    if (!empty($grad_year) && $grad_year > 0) {
                        echo htmlspecialchars($grad_year);
                    } else {
                        echo 'Not Specified';
                    }
                    ?>
                </div>
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
        <div class="col-md-8">
            <div class="view-field">
                <div class="view-label">
                    Specialization:
                    <?php if ($is_minor_multidisciplinary): ?>
                        <span class="badge-minor">Minor Multidisciplinary</span>
                    <?php elseif ($is_honours): ?>
                        <span class="badge-honours">Honours</span>
                    <?php endif; ?>
                </div>
                <div class="view-value"><?php echo htmlspecialchars($row['specialization_name'] ?? 'N/A'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- For Minor Multidisciplinary: Show Minor Course and Minor Subject -->
    <?php if ($is_minor_multidisciplinary): ?>
    <div class="row" style="padding: 15px;">
        <div class="col-md-6">
            <div class="view-field">
                <div class="view-label">Minor Course:</div>
                <div class="view-value">
                    <?php 
                    if (!empty($row['minor_course_name'])) {
                        echo htmlspecialchars($row['minor_course_name']);
                    } elseif (!empty($row['minor_course_id'])) {
                        echo "Course ID: " . htmlspecialchars($row['minor_course_id']);
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="view-field">
                <div class="view-label">Minor Subject:</div>
                <div class="view-value">
                    <?php 
                    if (!empty($row['minor_subject_name'])) {
                        echo htmlspecialchars($row['minor_subject_name']);
                    } elseif (!empty($row['minor_subject_id'])) {
                        echo "Subject ID: " . htmlspecialchars($row['minor_subject_id']);
                    } else {
                        echo 'N/A';
                    }
                    ?>
                </div>
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
    <?php endif; ?>
    
    <!-- For Honours: Show Specialization Subject and CGPA -->
    <?php if ($is_honours && !$is_minor_multidisciplinary): ?>
    <div class="row" style="padding: 15px;">
        <div class="col-md-6">
            <div class="view-field">
                <div class="view-label">Specialization Subject:</div>
                <div class="view-value"><?php echo htmlspecialchars($row['specialization_subject_name'] ?? 'N/A'); ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="view-field">
                <div class="view-label">CGPA (Aggregate):</div>
                <div class="view-value"><?php echo !empty($row['cgpa']) ? number_format($row['cgpa'], 2) : 'N/A'; ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- For Research: Show Research Components -->
    <?php if ($is_research): ?>
    <div class="row" style="padding: 15px; border-top: 1px dashed #ddd; margin-top: 10px;">
        <div class="col-md-6">
            <div class="view-field">
                <div class="view-label">Research Component I (Open Elective II):</div>
                <div class="view-value"><?php echo htmlspecialchars($row['research_component_i_name'] ?? 'N/A'); ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="view-field">
                <div class="view-label">Research Core Component - VII Sem:</div>
                <div class="view-value"><?php echo htmlspecialchars($row['research_core_vii'] ?? 'N/A'); ?></div>
            </div>
        </div>
    </div>
    <div class="row" style="padding: 15px;">
        <div class="col-md-6">
            <div class="view-field">
                <div class="view-label">Research Component II (Open Elective III):</div>
                <div class="view-value"><?php echo htmlspecialchars($row['research_component_ii_name'] ?? 'N/A'); ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="view-field">
                <div class="view-label">Research Core Component - VIII Sem:</div>
                <div class="view-value"><?php echo htmlspecialchars($row['research_core_viii'] ?? 'N/A'); ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- For Regular / Other: Show CGPA if not already shown -->
    <?php if (!$is_honours && !$is_minor_multidisciplinary): ?>
    <div class="row" style="padding: 15px;">
        <div class="col-md-4">
            <div class="view-field">
                <div class="view-label">CGPA (Aggregate):</div>
                <div class="view-value"><?php echo !empty($row['cgpa']) ? number_format($row['cgpa'], 2) : 'N/A'; ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- CONTACT DETAILS -->
<div class="view-section">
    <div class="view-section-header">
        <i class="fa fa-phone"></i> CONTACT DETAILS
    </div>
    <div class="row" style="padding: 15px;">
        <div class="col-md-6">
            <div class="view-field">
                <div class="view-label">Mobile Number:</div>
                <div class="view-value" style="display: flex; gap: 4px">
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
        <div class="col-md-6">
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
                <?php if (!empty($row['m_sem1']) && $row['m_sem1'] != '[]'): ?>
                <tr>
                    <td>Semester 1 Marksheet:</td>
                    <td><?php echo nl2br(htmlspecialchars($row['m_sem1'])); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($row['m_sem2']) && $row['m_sem2'] != '[]'): ?>
                <tr>
                    <td>Semester 2 Marksheet:</td>
                    <td><?php echo nl2br(htmlspecialchars($row['m_sem2'])); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($row['m_sem3']) && $row['m_sem3'] != '[]'): ?>
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

<!-- SEMESTER REGISTRATION & COURSE HISTORY -->
<div class="view-section">
    <div class="view-section-header">
        <i class="fa fa-history"></i> SEMESTER & SPECIALIZATION HISTORY
    </div>
    <div class="row" style="padding: 15px;">
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr style="background-color: #f4f4f4;">
                            <th>Semester</th>
                            <th>Academic Year</th>
                            <th>Class</th>
                            <th>Division</th>
                            <th>Specialization</th>
                            <th>Course / Subject</th>
                            <th>CGPA</th>
                            <th>Research Components</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $histSql = "SELECT
                            sh.semester_id,
                            sh.cgpa,
                            sh.research_core_vii,
                            sh.research_core_viii,
                            IFNULL(cl.class_name, 'N/A') AS class_name,
                            IFNULL(sec.sections, 'N/A') AS section_name,
                            IFNULL(sp.specialization_name, 'N/A') AS specialization_name,
                            IFNULL(ssb.subject_name, '') AS specialization_subject_name,
                            IFNULL(mc.course_name, '') AS minor_course_name,
                            IFNULL(ms.subject_name, '') AS minor_subject_name,
                            IFNULL(sess.session_name, 'N/A') AS academic_year_name,
                            IFNULL(sem.semester_name, 'N/A') AS semester_name,
                            IFNULL(rsi.subject_name, '') AS research_component_i_name,
                            IFNULL(rsii.subject_name, '') AS research_component_ii_name
                        FROM st_student_semester_history sh
                        LEFT JOIN st_class_master cl ON cl.class_id = sh.class_id
                        LEFT JOIN st_section_master sec ON sec.id = sh.division_id
                        LEFT JOIN st_specialization_master sp ON sp.specialization_id = sh.specialization_id
                        LEFT JOIN st_specialization_subject_master ssb ON ssb.subject_id = sh.specialization_subject_id
                        LEFT JOIN st_minorcourse mc ON mc.course_id = sh.minor_course_id
                        LEFT JOIN st_minorsubject ms ON ms.subject_id = sh.minor_subject_id
                        LEFT JOIN st_session_master sess ON sess.session_id = sh.academic_year_id
                        LEFT JOIN st_semester_master sem ON sem.semester_id = sh.semester_id
                        LEFT JOIN st_specialization_subject_master rsi ON rsi.subject_id = sh.research_component_i_id
                        LEFT JOIN st_specialization_subject_master rsii ON rsii.subject_id = sh.research_component_ii_id
                        WHERE sh.student_id = $student_id
                        ORDER BY sem.semester_name ASC, sh.semester_id ASC";
                        
                        $histRes = $db_handle->query($histSql);
                        if ($histRes && $histRes->num_rows > 0) {
                            while ($hrow = $histRes->fetch_assoc()) {
                                $h_spec = strtolower($hrow['specialization_name']);
                                $h_is_minor = strpos($h_spec, 'minor multidisciplinary') !== false;
                                $h_is_research = strpos($h_spec, 'research') !== false;
                                
                                // Format Course/Subject details
                                $course_details = 'N/A';
                                if ($h_is_minor) {
                                    $course_details = "<strong>Course:</strong> " . htmlspecialchars($hrow['minor_course_name'] ?: 'N/A') . "<br><strong>Subject:</strong> " . htmlspecialchars($hrow['minor_subject_name'] ?: 'N/A');
                                } else if (!empty($hrow['specialization_subject_name'])) {
                                    $course_details = htmlspecialchars($hrow['specialization_subject_name']);
                                }
                                
                                // Format Research Details
                                $research_details = 'N/A';
                                if ($h_is_research) {
                                    $research_details = "<strong>Comp I (OE II):</strong> " . htmlspecialchars($hrow['research_component_i_name'] ?: 'N/A') . "<br>"
                                                      . "<strong>Core VII:</strong> " . htmlspecialchars($hrow['research_core_vii'] ?: 'N/A') . "<br>"
                                                      . "<strong>Comp II (OE III):</strong> " . htmlspecialchars($hrow['research_component_ii_name'] ?: 'N/A') . "<br>"
                                                      . "<strong>Core VIII:</strong> " . htmlspecialchars($hrow['research_core_viii'] ?: 'N/A');
                                }
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($hrow['semester_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($hrow['academic_year_name']); ?></td>
                                <td><?php echo htmlspecialchars($hrow['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($hrow['section_name']); ?></td>
                                <td><?php echo htmlspecialchars($hrow['specialization_name']); ?></td>
                                <td><?php echo $course_details; ?></td>
                                <td><strong><?php echo htmlspecialchars($hrow['cgpa'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo $research_details; ?></td>
                            </tr>
                        <?php
                            }
                        } else {
                        ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No historical semester registrations recorded for this student.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

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
                    <?php
                    $mark_list_files = explode(',', $row['mark_list']);
                    foreach ($mark_list_files as $file):
                        $file = trim($file);
                        if (!empty($file)):
                    ?>
                        <div style="margin-bottom: 5px;">
                            <a href="uploads/marklists/<?php echo htmlspecialchars($file); ?>" target="_blank" class="btn btn-primary btn-sm">
                                <i class="fa fa-file-pdf-o"></i> <?php echo htmlspecialchars($file); ?>
                            </a>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    $(document).ready(function() {
        console.log("Student view loaded for ID: <?php echo $student_id; ?>");
    });
</script>