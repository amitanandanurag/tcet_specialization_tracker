<?php
session_start();
require "../database/db_connect.php";
$db_handle = new DBController();

if (isset($_REQUEST['id'])) {
  $student_id = intval($_REQUEST['id']);
  $table = 'st_student_master';

  $sql = "SELECT 
    sm.*,
    IFNULL(cl.class_name, '') AS class_name,
    IFNULL(sec.sections, '') AS section_name,
    IFNULL(dep.department_name, '') AS department_name,
    IFNULL(sp.specialization_name, '') AS specialization_name,
    IFNULL(ssb.subject_name, '') AS specialization_subject_name,
    IFNULL(sess.session_name, '') AS academic_year_name,
    IFNULL(sem.semester_name, '') AS semester_name
  FROM $table sm
  LEFT JOIN st_class_master cl ON cl.class_id = sm.class_id
  LEFT JOIN st_section_master sec ON sec.id = sm.division_id
  LEFT JOIN st_department_master dep ON dep.department_id = sm.department_id
  LEFT JOIN st_specialization_master sp ON sp.specialization_id = sm.specialization_id
  LEFT JOIN st_specialization_subject_master ssb ON ssb.subject_id = sm.specialization_subject_id
  LEFT JOIN st_session_master sess ON sess.session_id = sm.academic_year_id
  LEFT JOIN st_semester_master sem ON sem.semester_id = sm.current_semester_id
  WHERE sm.student_id = $student_id AND sm.status = '1'";
  
  $result = $db_handle->query($sql);
  $row = $result->fetch_assoc();

  if (!$row) {
    echo "<div class='alert alert-danger'>Student record not found.</div>";
    exit;
  }
  
  // Determine specialization type
  $specialization_name = strtolower($row['specialization_name'] ?? '');
  $is_minor_multidisciplinary = strpos($specialization_name, 'minor multidisciplinary') !== false;
  $is_minor = strpos($specialization_name, 'minor') !== false && !$is_minor_multidisciplinary;
  $is_honours = strpos($specialization_name, 'honour') !== false || strpos($specialization_name, 'honor') !== false;
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        .form-group .required-field {
            border-color: #ef4444;
        }
        .form-group .error-message {
            color: #ef4444;
            font-size: 11px;
            margin-top: 4px;
            display: block;
        }
    </style>
</head>
<body>
<form action="edit_process.php" name="editform" method="POST" onsubmit="return validateform()" enctype="multipart/form-data">
    <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
    
    <div class="box box-default" style="padding: 10px;">
        <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
            <h3 class="box-title">OFFICIAL DETAILS:- </h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
            </div>
        </div>

        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Academic Year <span style="color: red;">*</span></label>
                        <select class="form-control select" name="academic_year_id" id="academic_year_id" style="width: 100%;" required>
                            <option value="">Select Academic Year</option>
                            <?php
                            $sessionResult = $db_handle->query("SELECT session_id, session_name FROM st_session_master ORDER BY session_id DESC");
                            while ($sessionRow = $sessionResult->fetch_assoc()) {
                                $selected = ($row['academic_year_id'] == $sessionRow['session_id']) ? 'selected' : '';
                                echo "<option value='{$sessionRow['session_id']}' $selected>{$sessionRow['session_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Current Semester <span style="color: red;">*</span></label>
                        <select class="form-control select" name="current_semester_id" id="current_semester_id" style="width: 100%;" required>
                            <option value="">Select Semester</option>
                            <?php
                            $semesterResult = $db_handle->query("SELECT semester_id, semester_name FROM st_semester_master ORDER BY semester_id");
                            while ($semesterRow = $semesterResult->fetch_assoc()) {
                                $selected = ($row['current_semester_id'] == $semesterRow['semester_id']) ? 'selected' : '';
                                echo "<option value='{$semesterRow['semester_id']}' $selected>{$semesterRow['semester_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Registration Number <span style="color: red;">*</span></label>
                        <input type="text" name="registration_no" value="<?php echo htmlspecialchars($row['registration_no'] ?? ''); ?>" id="registration_no" class="form-control" style="width: 100%;" readonly>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Class <span style="color: red;">*</span></label>
                        <select class="form-control select" name="class_id" id="class_id" style="width: 100%;" required>
                            <option value="">Select Class</option>
                            <?php
                            $classResult = $db_handle->query("SELECT class_id, class_name FROM st_class_master");
                            while ($classRow = $classResult->fetch_assoc()) {
                                $selected = ($row['class_id'] == $classRow['class_id']) ? 'selected' : '';
                                echo "<option value='{$classRow['class_id']}' $selected>{$classRow['class_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>Division <span style="color: red;">*</span></label>
                        <select class="form-control select" name="division_id" id="division_id" style="width: 100%;" required>
                            <option value="">Select Division</option>
                            <?php
                            $sectionResult = $db_handle->query("SELECT id, sections FROM st_section_master");
                            while ($sectionRow = $sectionResult->fetch_assoc()) {
                                $selected = ($row['division_id'] == $sectionRow['id']) ? 'selected' : '';
                                echo "<option value='{$sectionRow['id']}' $selected>{$sectionRow['sections']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>Roll No.</label>
                        <input type="text" name="roll_no" class="form-control" value="<?php echo htmlspecialchars($row['roll_no'] ?? ''); ?>" style="width: 100%;">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Department <span style="color: red;">*</span></label>
                        <select class="form-control select" name="department_id" id="department_select" style="width: 100%;" required>
                            <option value="">Select Department</option>
                            <?php
                            $deptResult = $db_handle->query("SELECT department_id, department_name FROM st_department_master");
                            while ($deptRow = $deptResult->fetch_assoc()) {
                                $selected = ($row['department_id'] == $deptRow['department_id']) ? 'selected' : '';
                                echo "<option value='{$deptRow['department_id']}' $selected>{$deptRow['department_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>Specialization</label>
                        <select class="form-control select" name="specialization_id" id="specialization_select" style="width: 100%;">
                            <option value="">Select Specialization</option>
                            <?php
                            $specResult = $db_handle->query("SELECT specialization_id, specialization_name FROM st_specialization_master");
                            while ($specRow = $specResult->fetch_assoc()) {
                                $selected = ($row['specialization_id'] == $specRow['specialization_id']) ? 'selected' : '';
                                echo "<option value='{$specRow['specialization_id']}' $selected>{$specRow['specialization_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>CGPA (Aggregate)</label>
                        <input type="number" step="0.01" min="0" max="10" name="cgpa" id="cgpa" class="form-control" value="<?php echo htmlspecialchars($row['cgpa'] ?? ''); ?>" style="width: 100%;">
                    </div>
                </div>
            </div>

            <!-- Minor Multidisciplinary Section -->
            <div class="row" id="minor_multidisciplinary_section" style="<?php echo $is_minor_multidisciplinary ? 'display: flex;' : 'display: none;'; ?>">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Minor Course <span style="color: red;">*</span></label>
                        <select class="form-control select" name="minor_course_id" id="minor_course_select" style="width: 100%;">
                            <option value="">Select Minor Course</option>
                            <?php
                            $courseResult = $db_handle->conn->query("SELECT * FROM st_minorcourse ORDER BY course_name");
                            if ($courseResult && $courseResult->num_rows > 0) {
                                while ($courseRow = $courseResult->fetch_assoc()) {
                                    $course_id = $courseRow['course_id'];
                                    $course_name = $courseRow['course_name'];
                                    echo '<option value="' . $course_id . '">' . $course_name . '</option>';
                                }
                            } else {
                                echo '<option value="">No courses available</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-6" id="minor_subject_section">
                    <div class="form-group">
                        <label>Minor Subject <span style="color: red;">*</span></label>
                        <select class="form-control select" name="minor_subject_id" id="minor_subject_select" style="width: 100%;">
                            <option value="">Select Minor Subject</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Honors Specialization Subject Section -->
            <div class="row" id="specialization_subject_wrapper" style="<?php echo ($is_honours && !$is_minor_multidisciplinary) ? 'display: flex;' : 'display: none;'; ?>">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Specialization Subject <span style="color: red;">*</span></label>
                        <select class="form-control select" name="specialization_subject_id" id="specialization_subject_select" style="width: 100%;">
                            <option value="">Select Specialization Subject</option>
                            <?php
                            $subjResult = $db_handle->conn->query("SELECT subject_id, subject_name FROM st_specialization_subject_master");
                            while ($subjRow = $subjResult->fetch_assoc()) {
                                $selected = ($row['specialization_subject_id'] == $subjRow['subject_id']) ? 'selected' : '';
                                echo "<option value='{$subjRow['subject_id']}' $selected>{$subjRow['subject_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Graduation Year</label>
                        <input type="number" name="grad_year" class="form-control" value="<?php echo htmlspecialchars($row['grad_year'] ?? ''); ?>" style="width: 100%;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PERSONAL DETAILS -->
    <div class="box box-default" style="padding: 10px;">
        <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
            <h3 class="box-title">PERSONAL DETAILS:- </h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
            </div>
        </div>

        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Full Name <span style="color: red;">*</span></label>
                        <input type="text" name="fname" class="form-control" value="<?php echo htmlspecialchars($row['fname'] ?? ''); ?>" style="width: 100%;" required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Mobile Number <span style="color: red;">*</span></label>
                        <input type="text" pattern="^\d{10}$" class="form-control" name="mobile" value="<?php echo htmlspecialchars($row['mobile'] ?? ''); ?>" minlength="10" maxlength="10" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($row['email'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MARKSHEETS SECTION -->
    <div class="box box-default" style="padding: 10px;">
        <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
            <h3 class="box-title">MARKSHEETS (Previous Semester Results):- </h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
            </div>
        </div>

        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Semester 1 Marksheet</label>
                        <textarea class="form-control" name="m_sem1" rows="3" placeholder="Enter Semester 1 marks details"><?php echo htmlspecialchars($row['m_sem1'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Semester 2 Marksheet</label>
                        <textarea class="form-control" name="m_sem2" rows="3" placeholder="Enter Semester 2 marks details"><?php echo htmlspecialchars($row['m_sem2'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Semester 3 Marksheet</label>
                        <textarea class="form-control" name="m_sem3" rows="3" placeholder="Enter Semester 3 marks details"><?php echo htmlspecialchars($row['m_sem3'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- STATUS SECTION -->
    <div class="box box-default" style="padding: 10px;">
        <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
            <h3 class="box-title">STATUS:- </h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
            </div>
        </div>

        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control select" name="status" style="width: 100%;">
                            <option value="1" <?php echo ($row['status'] == '1') ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo ($row['status'] == '0') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 text-center">
            <input type="submit" name="save" value="UPDATE" class="btn btn-primary" />
            <input type="reset" name="reset" value="RESET" class="btn btn-default" />
        </div>
    </div>

</form>

<script>
function validateform() {
    var academic_year_id = document.editform.academic_year_id.value;
    var current_semester_id = document.editform.current_semester_id.value;
    var class_id = document.editform.class_id.value;
    var division_id = document.editform.division_id.value;
    var fname = document.editform.fname.value;
    var mobile = document.editform.mobile.value;
    var specializationText = $('#specialization_select option:selected').text().toLowerCase();
    var cgpaValue = document.getElementById("cgpa") ? document.getElementById("cgpa").value : "";
    
    var isMinorMultidisciplinary = specializationText.indexOf("minor multidisciplinary") !== -1;
    var isMinor = specializationText.indexOf("minor") !== -1 && !isMinorMultidisciplinary;
    var isHonours = specializationText.indexOf("honours") !== -1 || specializationText.indexOf("honors") !== -1;
    
    if (academic_year_id == null || academic_year_id == "") {
        alert("Academic Year can't be blank.");
        return false;
    }
    if (current_semester_id == null || current_semester_id == "") {
        alert("Current Semester can't be blank.");
        return false;
    }
    if (class_id == null || class_id == "") {
        alert("Class can't be blank.");
        return false;
    }
    if (division_id == null || division_id == "") {
        alert("Division can't be blank.");
        return false;
    }
    if (fname == null || fname == "") {
        alert("Student Name can't be blank.");
        return false;
    }
    if (mobile == null || mobile == "") {
        alert("Mobile Number can't be blank.");
        return false;
    }
    if (mobile.length != 10) {
        alert("Mobile Number must be 10 digits.");
        return false;
    }
    
    if (isMinorMultidisciplinary) {
        var minorCourse = $('#minor_course_select').val();
        if (!minorCourse || minorCourse == "") {
            alert("Please select Minor Course.");
            return false;
        }
        var minorSubject = $('#minor_subject_select').val();
        if (!minorSubject || minorSubject == "") {
            alert("Please select Minor Subject.");
            return false;
        }
    }
    
    if (isHonours) {
        if (cgpaValue == null || cgpaValue === "") {
            alert("Please enter CGPA for Honours.");
            return false;
        }
        if (isNaN(cgpaValue)) {
            alert("Please enter valid numeric CGPA.");
            return false;
        }
        if (parseFloat(cgpaValue) <= 7) {
            alert("Not eligible for Honours. CGPA must be above 7.");
            return false;
        }
        if ($('#specialization_subject_select').prop('selectedIndex') <= 0) {
            alert("Please select Specialization Subject for Honours.");
            return false;
        }
    }
    
    return true;
}

function loadMinorSubjectsByCourse(courseId) {
    if (courseId && courseId != "") {
        $('#minor_subject_select').empty().append('<option value="">Loading subjects...</option>');
        
        $.ajax({
            url: 'get_minor_subject.php',
            type: 'POST',
            data: { course_id: courseId },
            dataType: 'json',
            success: function(data) {
                $('#minor_subject_select').empty().append('<option value="">Select Minor Subject</option>');
                if (data && data.length > 0) {
                    $.each(data, function(key, value) {
                        $('#minor_subject_select').append('<option value="' + value.subject_id + '">' + value.subject_name + '</option>');
                    });
                    // Preselect current minor subject if editing
                    <?php if ($is_minor_multidisciplinary && !empty($row['specialization_subject_id'])): ?>
                    $('#minor_subject_select').val('<?php echo $row['specialization_subject_id']; ?>');
                    <?php endif; ?>
                } else {
                    $('#minor_subject_select').append('<option value="">No subjects available</option>');
                }
            },
            error: function(xhr, status, error) {
                $('#minor_subject_select').empty().append('<option value="">Error loading subjects</option>');
            }
        });
    } else {
        $('#minor_subject_section').hide();
        $('#minor_subject_select').empty().append('<option value="">Select Minor Subject</option>');
    }
}

$(document).ready(function() {
    // Handle specialization change
    $('#specialization_select').change(function() {
        var specText = $('#specialization_select option:selected').text().toLowerCase();
        var isMinorMultidisciplinary = specText.indexOf("minor multidisciplinary") !== -1;
        var isHonours = specText.indexOf("honours") !== -1 || specText.indexOf("honors") !== -1;
        
        if (isMinorMultidisciplinary) {
            $('#minor_multidisciplinary_section').show();
            $('#specialization_subject_wrapper').hide();
        } else if (isHonours) {
            $('#minor_multidisciplinary_section').hide();
            $('#specialization_subject_wrapper').show();
        } else {
            $('#minor_multidisciplinary_section').hide();
            $('#specialization_subject_wrapper').hide();
        }
    });
    
    // Handle minor course change
    $('#minor_course_select').change(function() {
        var courseId = $(this).val();
        if (courseId) {
            $('#minor_subject_section').show();
            loadMinorSubjectsByCourse(courseId);
        } else {
            $('#minor_subject_section').hide();
        }
    });
    
    // Load subjects if minor course is already selected (for edit mode)
    <?php if ($is_minor_multidisciplinary && !empty($row['specialization_subject_id'])): ?>
    // Need to load course first, then subject
    // You might need to store minor_course_id in a separate field or fetch it
    <?php endif; ?>
});
</script>
</body>
</html>
<?php
} else {
  echo "<div class='alert alert-danger'>No student ID provided.</div>";
}
?>