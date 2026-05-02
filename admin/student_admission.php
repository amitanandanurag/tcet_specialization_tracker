<?php include "header/header.php"; ?>
<?php
$admissionForm = $_SESSION['student_admission_form'] ?? [];
$admissionSuccess = $_SESSION['student_admission_success'] ?? '';
unset($_SESSION['student_admission_success']);

$isEditMode = isset($_GET['edit']) && $_GET['edit'] === '1';
$linkedStudentId = 0;
if ((int) ($usertype ?? 0) === 5 && !empty($userid)) {
  $linkedRes = $db_handle->query("SELECT student_id FROM st_user_master WHERE user_id = " . intval($userid) . " AND student_id > 0 LIMIT 1");
  if ($linkedRes && ($linkedRow = mysqli_fetch_assoc($linkedRes))) {
    $linkedStudentId = intval($linkedRow['student_id']);
  }
}

if ($linkedStudentId > 0 && !$isEditMode) {
  echo "<script>window.location.replace('student_admission_view.php?id=" . $linkedStudentId . "');</script>";
  exit;
}

// If admission form not in session, attempt to prefill from linked student record
if (($isEditMode || empty($admissionForm)) && !empty($userid)) {
  $mapRes = $db_handle->query("SELECT student_id FROM st_user_master WHERE user_id = " . intval($userid) . " LIMIT 1");
  if ($mapRes && ($mapRow = mysqli_fetch_assoc($mapRes))) {
    $sid = intval($mapRow['student_id']);
    if ($sid > 0) {
      $sres = $db_handle->query("SELECT * FROM st_student_master WHERE student_id = " . $sid . " LIMIT 1");
      if ($sres && ($srow = mysqli_fetch_assoc($sres))) {
        $admissionForm['registration_no'] = $srow['registration_no'] ?? '';
        $admissionForm['roll_no'] = $srow['roll_no'] ?? '';
        $admissionForm['class'] = $srow['class_id'] ?? '';
        $admissionForm['current_semester_id'] = $srow['current_semester_id'] ?? '';
        $admissionForm['batch'] = $srow['academic_year_id'] ?? '';
        $admissionForm['graduation_year'] = $srow['grad_year'] ?? '';
        $admissionForm['department_id'] = $srow['department_id'] ?? '';
        $admissionForm['specialization_id'] = $srow['specialization_id'] ?? '';
        $admissionForm['cgpa'] = $srow['cgpa'] ?? '';
        $admissionForm['minor_course_id'] = $srow['minor_course_id'] ?? '';
        $admissionForm['minor_subject_id'] = $srow['minor_subject_id'] ?? '';
        $admissionForm['minor_cgpa'] = '';
        $admissionForm['unaided_subject'] = $srow['specialization_subject_id'] ?? '';
        $admissionForm['fname'] = $srow['fname'] ?? '';
        $admissionForm['email'] = $srow['email'] ?? '';
        $admissionForm['mobile'] = $srow['mobile'] ?? '';
      }
    }
  }
}
?>

<script>
  function validateform() {
    var academic = document.myform.academic.value;
    var class1 = document.myform.class.value;
    var registration_no = document.myform.registration_no.value;
    var batch = document.myform.batch.value;
    var fname = document.myform.fname.value;
    var specializationSelect = document.getElementById("specialization_select");
    var specializationText = "";
    var cgpaValue = document.getElementById("cgpa") ? document.getElementById("cgpa").value : "";
    var minorCgpaValue = document.getElementById("minor_cgpa") ? document.getElementById("minor_cgpa").value : "";

    if (specializationSelect && specializationSelect.selectedIndex >= 0) {
      specializationText = specializationSelect.options[specializationSelect.selectedIndex].text.toLowerCase();
    }

    if (academic == null || academic == "") {
      alert("Academic Year can't be blank.");
      return false;
    }
    if (class1 == null || class1 == "") {
      alert("Class can't be blank.");
      return false;
    }
    if (registration_no == null || registration_no == "") {
      alert("Registration Number can't be blank.");
      return false;
    }
    if (batch == null || batch == "") {
      alert("Batch can't be blank.");
      return false;
    }
    if (fname == null || fname == "") {
      alert("First Name can't be blank.");
      return false;
    }

    var isMinorMultidisciplinary = specializationText.indexOf("minor multidisciplinary") !== -1;
    var isMinor = specializationText.indexOf("minor") !== -1 && !isMinorMultidisciplinary;
    var isHonours = specializationText.indexOf("honours") !== -1 || specializationText.indexOf("honors") !== -1;

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
    } else if (isMinorMultidisciplinary) {
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
      if (minorCgpaValue == null || minorCgpaValue === "") {
        alert("Please enter Minor CGPA.");
        return false;
      }
      if (isNaN(minorCgpaValue)) {
        alert("Please enter valid numeric CGPA for Minor.");
        return false;
      }
      var minorCgpa = parseFloat(minorCgpaValue);
      if (minorCgpa < 0 || minorCgpa > 10) {
        alert("CGPA must be between 0 and 10.");
        return false;
      }
    }

    var mobile = document.myform.mobile.value;
    if (mobile && isNaN(mobile)) {
      alert("Enter only numeric value in mobile field.");
      return false;
    }

    return true;
  }
</script>

<script type="text/javascript">
  function display2() {
    var class1 = $('#class4').val();
    var section = $('#batch4').val();

    $.ajax({
      type: 'POST',
      url: 'getroll_no.php',
      data: {
        "class": class1,
        "section": section
      },
      success: function(response) {
        $("#data421").val(response);
        console.log(response);
      },
    });
  }
</script>

<script>
  function ckeck_reg() {
    var register = document.getElementById("registration_no").value;
    if (register) {
      $.ajax({
        type: 'post',
        url: 'reg_no.php',
        data: {
          "register1": register,
        },
        dataType: 'json',
        success: function(response) {
          console.log(response);
          if (response && response.exists) {
            var fullNameField = document.getElementById('full_name');
            var emailField = document.getElementById('college_email');
            var mobileField = document.getElementById('mobile');

            if (fullNameField && response.fname) {
              fullNameField.value = response.fname;
            }

            if (emailField && response.email) {
              emailField.value = response.email;
            }

            if (mobileField && response.mobile) {
              mobileField.value = response.mobile;
            }
          }
        }
      });
    }
  }
</script>

<script type="text/javascript">
  function setAdmissionDetailSectionsVisible(isVisible) {
    if (isVisible) {
      $('#below_eligibility_sections').show();
      $('#personal_details_section').show();
      $('#upload_documents_section').show();
    } else {
      $('#below_eligibility_sections').hide();
      $('#personal_details_section').hide();
      $('#upload_documents_section').hide();
    }
  }

  function resetSpecializationConditionalUI() {
    $('#cgpa').val('');
    $('#specialization_subject_select').prop('selectedIndex', 0);
    $('#specialization_subject_wrapper').hide();
    $('#cgpa_section').hide();
    $('#honours_not_eligible').hide();
    $('#minor_multidisciplinary_section').hide();
    $('#minor_course_select').val('');
    $('#minor_subject_section').hide();
    $('#minor_subject_select').empty().append('<option value="">Select Minor Subject</option>');
    setAdmissionDetailSectionsVisible(false);
  }

  function loadMinorSubjectsByCourse(courseId) {
    console.log("Loading subjects for course ID:", courseId);

    if (courseId && courseId != "") {
      $('#minor_subject_select').empty().append('<option value="">Loading subjects...</option>');
      $('#minor_subject_section').show();

      $.ajax({
        url: 'get_minor_subject.php',
        type: 'POST',
        data: {
          course_id: courseId
        },
        dataType: 'json',
        success: function(data) {
          console.log("Subjects received:", data);
          $('#minor_subject_select').empty().append('<option value="">Select Minor Subject</option>');

          if (data && data.length > 0) {
            $.each(data, function(key, value) {
              $('#minor_subject_select').append('<option value="' + value.subject_id + '">' + value.subject_name + '</option>');
            });
            $('#minor_subject_section').show();
          } else {
            $('#minor_subject_select').append('<option value="">No subjects available for this course</option>');
            $('#minor_subject_section').show();
          }
        },
        error: function(xhr, status, error) {
          console.log("AJAX Error - Status:", status);
          console.log("AJAX Error - Response:", xhr.responseText);
          $('#minor_subject_select').empty().append('<option value="">Error loading subjects</option>');
          $('#minor_subject_section').show();
        }
      });
    } else {
      $('#minor_subject_section').hide();
      $('#minor_subject_select').empty().append('<option value="">Select Minor Subject</option>');
    }
  }

  function updateHonoursEligibility() {
    var specializationText = $('#specialization_select option:selected').text().toLowerCase();
    var cgpaRaw = $('#cgpa').val();
    var cgpa = parseFloat(cgpaRaw);
    var isHonours = specializationText.indexOf('honours') !== -1 || specializationText.indexOf('honors') !== -1;

    if (!isHonours) {
      $('#specialization_subject_wrapper').hide();
      $('#honours_not_eligible').hide();
      setAdmissionDetailSectionsVisible(false);
      return;
    }

    if (cgpaRaw !== '' && !isNaN(cgpa) && cgpa > 7) {
      $('#specialization_subject_wrapper').show();
      $('#honours_not_eligible').hide();
      setAdmissionDetailSectionsVisible(true);
    } else if (cgpaRaw !== '') {
      $('#specialization_subject_wrapper').hide();
      $('#specialization_subject_select').prop('selectedIndex', 0);
      $('#honours_not_eligible').show();
      setAdmissionDetailSectionsVisible(false);
    } else {
      $('#specialization_subject_wrapper').hide();
      $('#honours_not_eligible').hide();
      setAdmissionDetailSectionsVisible(false);
    }
  }

  function handleSpecializationSelection() {
    var specializationText = $('#specialization_select option:selected').text().toLowerCase();
    var isMinorMultidisciplinary = specializationText.indexOf("minor multidisciplinary") !== -1;
    var isMinorDegree = specializationText.indexOf("minor") !== -1 && !isMinorMultidisciplinary;
    var isHonours = specializationText.indexOf('honours') !== -1 || specializationText.indexOf('honors') !== -1;

    resetSpecializationConditionalUI();

    if (isMinorDegree) {
      $('#cgpa_section').show();
      setAdmissionDetailSectionsVisible(true);
      return;
    }

    if (isMinorMultidisciplinary) {
      $('#minor_multidisciplinary_section').show();
      setAdmissionDetailSectionsVisible(true);
      return;
    }

    if (isHonours) {
      $('#cgpa_section').show();
      setAdmissionDetailSectionsVisible(true);
      return;
    }

    // For regular specializations, show admission detail sections
    setAdmissionDetailSectionsVisible(true);
  }

  $(document).ready(function() {
    var originalOptions = $('#specialization_select').html();

    $('#class4').on('change', function() {
      var selectedClassText = $("#class4 option:selected").text().toLowerCase();
      $('#specialization_select').html('<option value="">Select Specialization</option>');

      if (selectedClassText.indexOf('sy') !== -1) {
        $(originalOptions).filter('option').each(function() {
          var val = $(this).val();
          if (val == "1" || val == "3" || val == "4") {
            $('#specialization_select').append($(this).clone());
          }
        });
      } else {
        $('#specialization_select').html(originalOptions);
      }

      $('#specialization_select').val("");
      // Keep sections visible when class changes - don't reset UI visibility
    });

    handleSpecializationSelection();

    $('#specialization_select').on('change', function() {
      handleSpecializationSelection();
    });

    $('#cgpa').on('input keyup change blur', function() {
      var specializationText = $('#specialization_select option:selected').text().toLowerCase();
      var isMinorMultidisciplinary = specializationText.indexOf("minor multidisciplinary") !== -1;
      var isMinorDegree = specializationText.indexOf("minor") !== -1 && !isMinorMultidisciplinary;
      var isHonours = specializationText.indexOf('honours') !== -1 || specializationText.indexOf('honors') !== -1;

      if (isMinorMultidisciplinary) {
        setAdmissionDetailSectionsVisible(true);
      } else if (isMinorDegree) {
        setAdmissionDetailSectionsVisible(true);
      } else if (isHonours) {
        updateHonoursEligibility();
      }
    });

    $('#minor_course_select').on('change', function() {
      var courseId = $(this).val();
      loadMinorSubjectsByCourse(courseId);
    });
  });
</script>
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

  input:invalid {
    border-color: #ef4444;
  }
</style>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header">
    <h1>Student Registration</h1>
    <ol class="breadcrumb">
      <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
      <li><a href="#">student</a></li>
      <li class="active">Admission</li>
    </ol>
  </section>

  <section class="content">
    <?php if (!empty($admissionSuccess)) { ?>
      <div class="alert alert-success" style="margin-bottom: 15px;">
        <?php echo htmlspecialchars($admissionSuccess); ?>
      </div>
    <?php } ?>
    <form action="student_process.php" name="myform" method="POST" onsubmit="return validateform()" enctype="multipart/form-data">
      <div class="box box-default" style="padding: 10px;">
        <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
          <h3 class="box-title">OFFICIAL DETAILS:- </h3>
          <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
          </div>
        </div>
        <div class="box-body">
          <div class="row">
            <div class="col-md-12">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Academic Year</label>
                  <select class="form-control select" name="academic" id="academic" style="width: 100%;" required>
                    <option value="">Select Academic Year</option>
                    <?php
                    // Fetch all batches from the database
                    $batch_result = $db_handle->query("SELECT session_id, session_name FROM `st_session_master` ORDER BY session_id DESC");

                    while ($row = $batch_result->fetch_assoc()) {
                      $id = $row['session_id'];
                      $name = $row['session_name'];

                      // This makes the latest batch selected by default
                      $selected = (!empty($admissionForm['academic']) && (string)$admissionForm['academic'] === (string)$id) ? 'selected' : (($id == 1 && empty($admissionForm['academic'])) ? 'selected' : '');

                      echo "<option value='{$id}' {$selected}>{$name}</option>";
                    }
                    ?>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>ERP ID <span style="color: red;">*</span></label>
                  <input type="text" name="registration_no" value="<?php echo htmlspecialchars($admissionForm['registration_no'] ?? ''); ?>" id="registration_no" onblur="ckeck_reg()" class="form-control" style="width: 100%;" required>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Roll No.</label>
                  <input type="text" name="roll_no" value="<?php echo htmlspecialchars($admissionForm['roll_no'] ?? ''); ?>" class="form-control" id="data421" style="width: 100%;" required>
                </div>
              </div>
            </div>

            <div class="col-md-12">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Class <span style="color: red;">*</span></label>
                  <select class="form-control select" name="class" id="class4" class="class" style="width: 100%;" required>
                    <option value="">Select Class</option>
                    <?php
                    $result = $db_handle->conn->query("select * from st_class_master");
                    while ($row = $result->fetch_assoc()) {
                      $class_name = $row['class_name'];
                      $class_id = $row['class_id'];
                      $selected = (!empty($admissionForm['class']) && (string)$admissionForm['class'] === (string)$class_id) ? 'selected' : '';
                    ?>
                      <option value="<?php echo $class_id; ?>" <?php echo $selected; ?>><?php echo $class_name; ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label>Semester <span style="color: red;">*</span></label>
                  <select class="form-control" name="current_semester_id" id="semester_select" required>
                    <option value="">Select Semester</option>
                    <?php
                    // Make sure to use the correct table name: st_semester_master (not st_semester)
                    $result = $db_handle->conn->query("SELECT semester_id, semester_name FROM st_semester_master ORDER BY semester_id ASC");
                    if ($result && $result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                        $selected = (!empty($admissionForm['current_semester_id']) && (string)$admissionForm['current_semester_id'] === (string)$row['semester_id']) ? 'selected' : '';
                        echo "<option value='{$row['semester_id']}' {$selected}>{$row['semester_name']}</option>";
                      }
                    } else {
                      // Fallback if table name is different
                      $result = $db_handle->conn->query("SELECT semester_id, semester_name FROM st_semester ORDER BY semester_id ASC");
                      while ($row = $result->fetch_assoc()) {
                        $selected = (!empty($admissionForm['current_semester_id']) && (string)$admissionForm['current_semester_id'] === (string)$row['semester_id']) ? 'selected' : '';
                        echo "<option value='{$row['semester_id']}' {$selected}>{$row['semester_name']}</option>";
                      }
                    }
                    ?>
                  </select>
                </div>
              </div>
              <div class="col-md-2">
                <div class="form-group">
                  <label>Division<span style="color: red;">*</span></label>
                  <select class="form-control select" name="batch" id="batch4" class="batch" onchange="display2()" style="width: 100%;" required>
                    <option value="">Select Division</option>
                    <?php
                    $result = $db_handle->conn->query("SELECT * from st_section_master");
                    while ($row = $result->fetch_assoc()) {
                      $section_name = $row['sections'];
                      $s_id = $row['id'];
                      $selected = (!empty($admissionForm['batch']) && (string)$admissionForm['batch'] === (string)$s_id) ? 'selected' : '';
                    ?>
                      <option value="<?php echo $s_id;  ?>" <?php echo $selected; ?>><?php echo $section_name;  ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
             <div class="col-md-4">
    <div class="form-group">
        <label>Graduating Year</label>
        <select class="form-control select" name="graduation_year" id="graduation_year" class="graduation_year" style="width: 100%;">
            <option value="">Select Year</option>
            <?php
            // Assuming batch_name contains the year (like "2024", "2025")
            $result = $db_handle->conn->query("SELECT * from st_batch_master ORDER BY batch_name");
            while ($row = $result->fetch_assoc()) {
                $batch_name = $row['batch_name'];
                $batch_id = $row['batch_id']; // or academic_year_id
                
                // Extract year from batch_name if it contains the year
                // Or use academic_year_id if it represents the year
              $selected = (!empty($admissionForm['graduation_year']) && (string)$admissionForm['graduation_year'] === (string)$batch_name) ? 'selected' : '';
              echo "<option value='{$batch_name}' {$selected}>{$batch_name}</option>";
            }
            ?>
        </select>
    </div>
</div>
            </div>

            <div class="col-md-12">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Department<span style="color: red;">*</span></label>
                  <select class="form-control select" name="department_id" id="department_select" class="batch" style="width: 100%;" required>
                    <option value="">Select Department</option>
                    <?php
                    $result = $db_handle->conn->query("SELECT * from st_department_master");
                    while ($row = $result->fetch_assoc()) {
                      $department_name = $row['department_name'];
                      $department_id = $row['department_id'];
                      $selected = (!empty($admissionForm['department_id']) && (string)$admissionForm['department_id'] === (string)$department_id) ? 'selected' : '';
                    ?>
                      <option value="<?php echo $department_id;  ?>" <?php echo $selected; ?>><?php echo $department_name;  ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group">
                  <label>Specialization<span style="color: red;">*</span></label>
                  <select class="form-control select" name="specialization_id" id="specialization_select" class="batch" style="width: 100%;" required>
                    <option value="">Select Specialization</option>
                    <?php
                    $result = $db_handle->conn->query("SELECT * from st_specialization_master");
                    while ($row = $result->fetch_assoc()) {
                      $specialization_name = $row['specialization_name'];
                      $specialization_id = $row['specialization_id'];
                      $selected = (!empty($admissionForm['specialization_id']) && (string)$admissionForm['specialization_id'] === (string)$specialization_id) ? 'selected' : '';
                    ?>
                      <option value="<?php echo $specialization_id;  ?>" <?php echo $selected; ?>><?php echo $specialization_name;  ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <!-- CGPA Field -->
              <div class="col-md-4" id="cgpa_section" style="display: none;">
                <div class="form-group">
                  <label>Enter Your CGPA (Aggregate)<span style="color: red;">*</span></label>
                  <input type="text" name="cgpa" id="cgpa" class="form-control" value="<?php echo htmlspecialchars($admissionForm['cgpa'] ?? ''); ?>" placeholder="Enter CGPA" style="width: 100%;">
                </div>
              </div>
            </div>

            <!-- Minor Multidisciplinary Section -->
            <div class="col-md-12" id="minor_multidisciplinary_section" style="display: none;">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Minor Course <span style="color: red;">*</span></label>
                  <select class="form-control select" name="minor_course_id" id="minor_course_select" style="width: 100%;">
                    <option value="">Select Minor Course</option>
                    <?php
                    $result = $db_handle->conn->query("SELECT * FROM st_minorcourse ORDER BY course_name");
                    if ($result && $result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                        $course_id = $row['course_id'];
                        $course_name = $row['course_name'];
                        $selected = (!empty($admissionForm['minor_course_id']) && (string)$admissionForm['minor_course_id'] === (string)$course_id) ? 'selected' : '';
                        echo '<option value="' . $course_id . '" ' . $selected . '>' . $course_name . '</option>';
                      }
                    } else {
                      echo '<option value="">No courses available</option>';
                    }
                    ?>
                  </select>
                </div>
              </div>

              <div class="col-md-4" id="minor_subject_section" style="display: none;">
                <div class="form-group">
                  <label>Minor Subject <span style="color: red;">*</span></label>
                  <select class="form-control select" name="minor_subject_id" id="minor_subject_select" style="width: 100%;">
                    <option value="">Select Minor Subject</option>
                    <?php if (!empty($admissionForm['minor_subject_id'])) { ?>
                      <option value="<?php echo htmlspecialchars($admissionForm['minor_subject_id']); ?>" selected>
                        <?php echo htmlspecialchars($admissionForm['minor_subject_id']); ?>
                      </option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <!-- ADD THIS MISSING CGPA FIELD -->
              <div class="col-md-4">
                <div class="form-group">
                  <label>Minor CGPA <span style="color: red;">*</span></label>
                  <input type="text" name="minor_cgpa" id="minor_cgpa" class="form-control" value="<?php echo htmlspecialchars($admissionForm['minor_cgpa'] ?? ''); ?>" placeholder="Enter Minor CGPA (0-10)" style="width: 100%;">
                </div>
              </div>
            </div>

            <div class="col-md-12">
              <div class="col-md-4" id="specialization_subject_wrapper" style="display: none;">
                <div class="form-group">
                  <label>Specialization Subject<span style="color: red;">*</span></label>
                  <select class="form-control select" name="unaided_subject" id="specialization_subject_select" class="batch" style="width: 100%;">
                    <option>Select Specialization Subject</option>
                    <?php
                    $result = $db_handle->conn->query("SELECT * from st_specialization_subject_master");
                    while ($row = $result->fetch_assoc()) {
                      $subject_name = $row['subject_name'];
                      $subject_id = $row['subject_id'];
                      $selected = (!empty($admissionForm['unaided_subject']) && (string)$admissionForm['unaided_subject'] === (string)$subject_id) ? 'selected' : '';
                    ?>
                      <option value="<?php echo $subject_id;  ?>" <?php echo $selected; ?>><?php echo $subject_name;  ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-md-8" id="honours_not_eligible" style="display: none; margin-top: 30px; color: #d9534f; font-weight: 600;">
                Not eligible to register in Honours. CGPA must be above 7.
              </div>
            </div>
          </div>
        </div>
      </div>

      <div id="below_eligibility_sections" style="display: none;">
        <div class="box box-default" id="personal_details_section" style="padding: 10px;">
          <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
            <h3 class="box-title">PERSONAL DETAILS:- </h3>
            <div class="box-tools pull-right">
              <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
            </div>
          </div>
          <div class="box-body">
            <div class="row">
              <div class="col-md-12">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fname" id="full_name" value="<?php echo htmlspecialchars($admissionForm['fname'] ?? ''); ?>" class="form-control" autocomplete="name" style="width: 100%;" required>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>College Email <span style="color: red;">*</span></label>
                    <input type="email" name="email" id="college_email" class="form-control"
                      value="<?php echo htmlspecialchars($admissionForm['email'] ?? ''); ?>"
                      autocomplete="email"
                      placeholder="example@tcetmumbai.in" style="width: 100%;"
                      pattern="[a-zA-Z0-9._%+\-]+@tcetmumbai\.in"
                      title="Email must end with @tcetmumbai.in" required>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Mobile</label>
                    <input type="text" pattern="^\d{10}$" class="form-control"
                      id="mobile" name="mobile" value="<?php echo htmlspecialchars($admissionForm['mobile'] ?? ''); ?>" autocomplete="tel" minlength="10" maxlength="10"
                      oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                      placeholder="Mobile No." style="width: 100%;" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="box box-default" id="upload_documents_section" style="padding: 10px;">
          <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
            <h3 class="box-title"><i class="fa fa-file-pdf-o"></i> Upload MarkSheets:- </h3>
            <p style="margin: 8px 0 0; color: #999; font-size: 12px;">Upload marksheets for all completed semesters</p>
            <div class="box-tools pull-right">
              <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
            </div>
          </div>
          <div class="box-body">
            <div id="semester_uploads_container" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
              <!-- Dynamic semester uploads will be inserted here -->
            </div>
            <div class="row" style="margin: 30px 0 0 0;">
              <div style="margin-top: 20px; text-align: center;">
                <input type="submit" name="save" value="Save Changes" class="btn-submit">
                <input type="reset" name="reset" value="Reset" class="btn-reset">
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </section>
</div>

<style>
  .semester-upload-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 14px;
    transition: all 0.3s ease;
  }

  .semester-upload-card:hover {
    border-color: #2563eb;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
  }

  .semester-upload-card.completed {
    border-color: #10b981;
    background: #f0fdf4;
  }

  .semester-badge {
    display: inline-block;
    background: linear-gradient(135deg, #2563eb, #0ea5e9);
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    margin-right: 10px;
    min-width: 80px;
    text-align: center;
  }

  .semester-badge.completed {
    background: linear-gradient(135deg, #10b981, #059669);
  }

  .semester-label {
    font-weight: 600;
    color: #2f3b45;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .file-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .file-input-wrapper input[type="file"] {
    flex: 1;
    padding: 10px 12px;
    border: 2px dashed #cbd5e1;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .file-input-wrapper input[type="file"]:hover {
    border-color: #2563eb;
    background: #f0f9ff;
  }

  .file-input-label {
    color: #64748b;
    font-size: 12px;
    margin-top: 6px;
  }

  .upload-icon {
    color: #2563eb;
    font-size: 18px;
  }
</style>

<script>
  // Show all previous semester marksheets with improved UI
  function updateAllPreviousMarksUpload() {
    var selText = $('#semester_select option:selected').text() || '';
    var numMatch = selText.match(/(\d+)/);
    var currentSem = numMatch ? parseInt(numMatch[1], 10) : parseInt($('#semester_select').val(), 10);

    // Clear previous dynamic content
    $('#semester_uploads_container').empty();

    if (!currentSem || currentSem <= 1) {
      $('#semester_uploads_container').html('<p style="color: #999; text-align: center; padding: 20px;">No previous semesters to upload</p>');
      return;
    }

    // Create rows for all previous semesters (1 to currentSem - 1)
    var html = '';
    for (var i = 1; i < currentSem; i++) {
      var inputName = 'mark-list' + i;
      var cardClass = 'semester-upload-card';
      var badgeClass = 'semester-badge';

      html += '<div class="' + cardClass + '" id="sem_card_' + i + '">';
      html += '<div class="semester-label">';
      html += '<span class="' + badgeClass + '">Semester ' + i + '</span>';
      html += '<span style="color: #94a3b8; font-weight: 400;">Upload marksheet</span>';
      html += '</div>';
      html += '<div class="file-input-wrapper">';
      html += '<i class="fa fa-cloud-upload upload-icon"></i>';
      html += '<input type="file" class="form-control semester-file-input" name="' + inputName + '" accept=".pdf,.jpg,.jpeg,.png">';
      html += '</div>';
      html += '<p class="file-input-label">Accepted formats: PDF, JPG, JPEG, PNG (Max 10MB)</p>';
      html += '</div>';
    }

    $('#semester_uploads_container').html(html);

    // Add event listener for file selection to update card styling
    $('.semester-file-input').on('change', function() {
      var cardId = $(this).closest('.semester-upload-card').attr('id');
      if ($(this).val() !== '') {
        $(this).closest('.semester-upload-card').addClass('completed').find('.semester-badge').addClass('completed');
      } else {
        $(this).closest('.semester-upload-card').removeClass('completed').find('.semester-badge').removeClass('completed');
      }
    });
  }

  $(document).ready(function() {
    // Bind change event
    $('#semester_select').on('change', function() {
      updateAllPreviousMarksUpload();
    });

    // Initialize on page load if semester already selected
    updateAllPreviousMarksUpload();
  });
</script>

</div>
<?php include "header/footer.php"; ?>
