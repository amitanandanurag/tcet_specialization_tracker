<?php 
session_start();
require "../database/db_connect.php";
$db_handle = new DBController();

if (isset($_REQUEST['id'])) {
    $student_id = intval($_REQUEST['id']);
    $table = 'st_student_master';
    
    $sql = "SELECT * FROM $table WHERE student_id = $student_id";
    $result = $db_handle->query($sql);
    $row = $result->fetch_assoc();
    
    if (!$row) {
        echo "<div class='alert alert-danger'>Student record not found.</div>";
        exit;
    }
?>
<form action="edit_process.php" name="editform" method="POST" onsubmit="return validateform()" enctype="multipart/form-data">
        
        <div class="box box-default" style="padding: 10px;">
            <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
              <h3 class="box-title">OFFICIAL DETAILS:- </h3>
              <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
              </div>
            </div>
            
            <div class="box-body">
            <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
            <input type="hidden" name="table_name" value="<?php echo htmlspecialchars($table); ?>">
            
              <div class="row">
                <div class="col-md-4"> 
                   <div class="form-group">
                    <label>Academic Year</label>
                    <select class="form-control select" name="academic" id="academic" style="width: 100%;">
                      <option value="">Select Academic Year</option>
                      <option value="2024 - 2025" <?php if(($row['academic_year'] ?? '') == '2024 - 2025') echo 'selected'; ?>>2024 - 2025</option>
                      <option value="2025 - 2026" <?php if(($row['academic_year'] ?? '') == '2025 - 2026') echo 'selected'; ?>>2025 - 2026</option>
                      <option value="2026 - 2027" <?php if(($row['academic_year'] ?? '') == '2026 - 2027') echo 'selected'; ?>>2026 - 2027</option>
                    </select>
                </div>
                </div>
                 <div class="col-md-4">
                  <div class="form-group">
                    <label>Registration Number <span style="color: red;">*</span></label>
                    <input type="text" name="registration_no" value="<?php echo htmlspecialchars($row['registration_no'] ?? ''); ?>" id="registration_no" class="form-control" style="width: 100%;" readonly>
                  </div>
                  </div>
                  <div class="col-md-4">
                  <div class="form-group">
                    <label>Joining Date <span style="color: red;">*</span></label>
                    <div class="input-group date">
                      <div class="input-group-addon">
                        <i class="fa fa-calendar"></i>
                      </div>
                      <input type="date" name="join_date" id="join_date" value="<?php echo htmlspecialchars($row['joining_date'] ?? ''); ?>" class="form-control pull-right">
                    </div>
                  </div>
                  </div>
                </div>

                <div class="row">
                <div class="col-md-4">     
                 <div class="form-group">
                    <label>Class <span style="color: red;">*</span></label>
                    <select class="form-control select" name="class" id="class4" style="width: 100%;">
                       <option>Select Class</option>
                        <?php
                          $classResult=$db_handle->conn->query("select * from st_class_master");
                          while($classRow=$classResult->fetch_assoc()){
                           $class_name = $classRow['class_name'];
                           $class_id = $classRow['class_id'];
                           $selected = (($row['class_id'] ?? '') == $class_id) ? 'selected' : '';
                        ?>
                          <option value="<?php echo $class_id;?>" <?php echo $selected; ?>><?php echo $class_name;?></option>
                        <?php } ?>
                    </select>
                </div>
                </div> 
                
                <div class="col-md-2">
                <div class="form-group">
                  <label>Division<span style="color: red;">*</span></label>
                  <select class="form-control select" name="batch" id="batch4" style="width: 100%;">
                     <option>Select Division</option>
                     <?php
                        $sectionResult=$db_handle->conn->query("SELECT * from st_section_master");
                         while($sectionRow=$sectionResult->fetch_assoc()){
                         $section_name = $sectionRow['sections'];
                         $s_id = $sectionRow['id'];
                         $selected = (($row['division_id'] ?? '') == $s_id) ? 'selected' : '';
                     ?>
                         <option value="<?php echo $s_id;  ?>" <?php echo $selected; ?>><?php echo $section_name;  ?></option>
                      <?php } ?>
                  </select>
                </div>
                </div>

                <div class="col-md-2">
                <div class="form-group">
                  <label>Batch</label>
                  <select class="form-control select" name="batch_id" id="batch_id" style="width: 100%;">
                     <option>Select Batch</option>
                     <?php
                            $batchResult=$db_handle->conn->query("SELECT * from st_batch_master");
                             while($batchRow=$batchResult->fetch_assoc()){
                             $batch_name = $batchRow['batch_name'];
                             $batch_id = $batchRow['batch_id'];
                             $selected = (($row['batch_id'] ?? '') == $batch_id) ? 'selected' : '';
                             ?>
                             <option value="<?php echo $batch_id;  ?>" <?php echo $selected; ?>><?php echo $batch_name;  ?></option>
                          <?php } ?>
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
                <label>Department<span style="color: red;">*</span></label>
                 <select class="form-control select" name="department_id" id="department_select" style="width: 100%;">
                   <option>Select Department</option>
                      <?php
                         $deptResult=$db_handle->conn->query("SELECT * from st_department_master");
                         while($deptRow=$deptResult->fetch_assoc()){
                         $department_name = $deptRow['department_name'];
                         $department_id = $deptRow['department_id'];
                         $selected = (($row['department_id'] ?? '') == $department_id) ? 'selected' : '';
                       ?>
                       <option value="<?php echo $department_id;  ?>" <?php echo $selected; ?>><?php echo $department_name;  ?></option>
                      <?php } ?>
                </select>
              </div>
              </div>

             <div class="col-md-4">
              <div class="form-group">
                <label>Specialization<span style="color: red;">*</span></label>
               <select class="form-control select" name="specialization_id" id="specialization_select" style="width: 100%;">
                   <option>Select Specialization</option>
                   <?php
                            $specResult=$db_handle->conn->query("SELECT * from st_specialization_master");
                             while($specRow=$specResult->fetch_assoc()){
                             $specialization_name = $specRow['specialization_name'];
                             $specialization_id = $specRow['specialization_id'];
                             $selected = (($row['specialization_id'] ?? '') == $specialization_id) ? 'selected' : '';
                           ?>
                           <option value="<?php echo $specialization_id;  ?>" <?php echo $selected; ?>><?php echo $specialization_name;  ?></option>
                          <?php } ?>
                </select>
              </div>
              </div>

            <div class="col-md-4" id="specialization_subject_wrapper" style="display: none;">
              <div class="form-group">
                <label>Specialization Subject<span style="color: red;">*</span></label>
                <select class="form-control select" name="unaided_subject" id="specialization_subject_select" style="width: 100%;">
                   <option>Select Specialization Subject</option>
                   <?php
                            $subjResult=$db_handle->conn->query("SELECT * from st_specialization_subject_master");
                             while($subjRow=$subjResult->fetch_assoc()){
                             $subject_name = $subjRow['subject_name'];
                             $subject_id = $subjRow['subject_id'];
                             $selected = (($row['specialization_subject_id'] ?? '') == $subject_id) ? 'selected' : '';
                           ?>
                           <option value="<?php echo $subject_id;  ?>" <?php echo $selected; ?>><?php echo $subject_name;  ?></option>
                          <?php } ?>
                </select>
              </div>
              </div>
          
            </div>

            <div class="col-md-12" id="cgpa_section" style="display: none;">
              <div class="col-md-4">
              <div class="form-group">
                <label>Enter Your CGPA(Aggregate)<span style="color: red;">*</span></label>
               <input type="text" name="cgpa" id="cgpa" class="form-control" value="<?php echo htmlspecialchars($row['cgpa'] ?? ''); ?>" style="width: 100%;">
              </div>
              </div>
            </div>

            </div>
            </div>
        </div>

        <!-- PERSONAL DETAILS -->
        <div class="box box-default" id="personal_details_section" style="padding: 10px; display: none;">
            <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
              <h3 class="box-title">PERSONAL DETAILS:- </h3>
              <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
              </div>
            </div>
            
            <div class="box-body">
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>First Name</label>
                  <input type="text" name="fname" class="form-control" value="<?php echo htmlspecialchars($row['fname'] ?? ''); ?>" style="width: 100%;">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Middle Name</label>
                  <input type="text" name="mname" class="form-control" value="<?php echo htmlspecialchars($row['mname'] ?? ''); ?>" style="width: 100%;">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Last Name</label>
                  <input type="text" name="lname" class="form-control" value="<?php echo htmlspecialchars($row['lname'] ?? ''); ?>" style="width: 100%;">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Date of Birth</label>
                  <input type="date" name="dob" id="dob" class="form-control" value="<?php echo htmlspecialchars($row['dob'] ?? ''); ?>">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Gender</label>
                  <select class="form-control select" name="gender" style="width: 100%;">
                     <option value="">Please Select</option>
                     <option value="Male" <?php if(($row['gender'] ?? '') == 'Male') echo 'selected'; ?>>Male</option>
                     <option value="Female" <?php if(($row['gender'] ?? '') == 'Female') echo 'selected'; ?>>Female</option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Nationality</label>
                  <select class="form-control select" name="nation" style="width: 100%;">
                      <option value="INDIAN" <?php if(($row['nationality'] ?? '') == 'INDIAN') echo 'selected'; ?>>Indian</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>APAAR ID</label>
                  <input type="text" name="appar" id="appar" class="form-control" value="<?php echo htmlspecialchars($row['appar'] ?? ''); ?>" style="width: 100%;">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>UAN</label>
                  <input type="text" name="uan" id="uan" class="form-control" value="<?php echo htmlspecialchars($row['uan'] ?? ''); ?>" style="width: 100%;">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>PAN </label>
                  <input type="text" name="pan" id="pan" class="form-control" value="<?php echo htmlspecialchars($row['pan'] ?? ''); ?>" style="width: 100%;">
                </div>
              </div>
            </div>
            </div>
        </div>

        <!-- CONTACT DETAILS -->
        <div class="box box-default" id="contact_details_section" style="padding: 10px; display: none;">
            <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
              <h3 class="box-title">CONTACT DETAILS:- </h3>
              <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
              </div>
            </div>
            
            <div class="box-body">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Permanent Address</label>
                  <textarea class="form-control" name="permanent" style="width: 100%;"><?php echo htmlspecialchars($row['permanent_address'] ?? ''); ?></textarea>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Present Address</label>
                  <textarea class="form-control" name="present" style="width: 100%;"><?php echo htmlspecialchars($row['present_address'] ?? ''); ?></textarea>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>City</label>
                  <input type="text" class="form-control" name="city" value="<?php echo htmlspecialchars($row['city'] ?? ''); ?>" style="width: 100%;">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Pincode</label>
                  <input type="text" pattern="^\d{6}$" class="form-control" name="pincode" value="<?php echo htmlspecialchars($row['pincode'] ?? ''); ?>" minlength="6" maxlength="6" style="width: 100%;">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Country</label>
                  <select id="country" name="country" class="form-control select2" style="width: 100%;">
                   <option value="India" <?php if(($row['country'] ?? '') == 'India') echo 'selected'; ?>>India</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label>State</label>
                  <select name="state" id="state" class="form-control select" style="width: 100%;">
                    <option value="Maharashtra" <?php if(($row['state'] ?? '') == 'Maharashtra') echo 'selected'; ?>>Maharashtra</option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Phone</label>
                  <input type="text" pattern="^\d{10}$" class="form-control" name="phone" value="<?php echo htmlspecialchars($row['phone'] ?? ''); ?>" minlength="10" maxlength="10" style="width: 100%;">
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Mobile</label>
                  <input type="text" pattern="^\d{10}$" class="form-control" name="mobile" value="<?php echo htmlspecialchars($row['mobile'] ?? ''); ?>" minlength="10" maxlength="10" style="width: 100%;">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Email</label>
                  <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($row['email'] ?? ''); ?>" style="width: 100%;">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Upload Photo</label>
                  <input type="file" class="form-control" name="photo" id="photo" onchange="show(this)" style="width: 100%;">
                  <?php if (!empty($row['photo'])): ?>
                    <img id="user_img_preview" src="student_photo/<?php echo htmlspecialchars($row['photo'] ?? ''); ?>" style="height:100px; width:100px; border-radius: 50%; margin-top: 10px;" />
                  <?php endif; ?>
                </div>
              </div>
            </div>
            </div>
        </div>

        <!-- UPLOAD DOCUMENTS -->
        <div class="box box-default" id="upload_documents_section" style="padding: 10px; display: none;">
            <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
              <h3 class="box-title">Upload Documents:- </h3>
              <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
              </div>
            </div>
            
            <div class="box-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <input type="checkbox" id="checkbox1"/>
                    <label>Mark List</label>
                  </div>
                </div>
                <div id="autoUpdate" class="autoUpdate" style="display:none">
                   <input type="file" name="mark-list">
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <input type="checkbox" id="checkbox6"/>
                    <label>Affidavit</label>
                  </div>
                </div>
                <div id="autoUpdate5" class="autoUpdate5" style="display:none">
                   <input type="file" name="affidavit">
                </div>
              </div>
            </div>
        </div>

        <input type="submit" name="save" value="UPDATE" style="background-color: #009688; color: #FFEB3B; font-size: 20px; padding: 5px; width: 100px;" />
        <input type="reset" name="reset" value="RESET" style="background-color: #009688; color: #FFEB3B; font-size: 20px; padding: 5px; width: 100px;" />

      </form>

<script>
function validateform(){
  var academic = document.editform.academic.value;
  var class1 = document.editform.class.value;
  var batch = document.editform.batch.value;
  var join_date = document.editform.join_date.value;
  var fname = document.editform.fname.value;

  if (academic==null || academic==""){  
     alert("Academic Year can't be blank.");  
     return false;  
  }
  if(class1==null || class1==""){  
     alert("Class can't be blank.");  
     return false;  
  }
  if(batch==null || batch==""){  
     alert("Division can't be blank.");  
     return false;  
  }
  if(join_date==null || join_date==""){  
     alert("Joining Date can't be blank.");  
     return false;  
  }
  return true;
}

function show(input) {
  var validExtensions = ['jpg','png','jpeg'];
  var fileName = input.files[0].name;
  var fileNameExt = fileName.substr(fileName.lastIndexOf('.') + 1);
  if ($.inArray(fileNameExt, validExtensions) == -1) {
      input.type = ''
      input.type = 'file'
      alert("Only these file types are accepted : "+validExtensions.join(', '));
  } else {
      if (input.files && input.files[0]) {
          var filerdr = new FileReader();
          filerdr.onload = function (e) {
              $('#user_img_preview').attr('src', e.target.result);
          }
          filerdr.readAsDataURL(input.files[0]);
      }
  }
}

$(document).ready(function() {
  var specializationText = $('#specialization_select option:selected').text().toLowerCase();
  var isMinor = specializationText.indexOf('minor') !== -1;
  var isHonours = specializationText.indexOf('honours') !== -1 || specializationText.indexOf('honors') !== -1;
  var cgpaValue = $('#cgpa').val();
  var cgpa = parseFloat(cgpaValue);

  if (isMinor) {
    $('#specialization_subject_wrapper').show();
    $('#personal_details_section').show();
    $('#contact_details_section').show();
    $('#upload_documents_section').show();
  } else if (isHonours) {
    $('#cgpa_section').show();
    if (cgpaValue !== '' && !isNaN(cgpa) && cgpa > 7) {
      $('#personal_details_section').show();
      $('#contact_details_section').show();
      $('#upload_documents_section').show();
    }
  }

  $('#checkbox1').change(function(){
    if(this.checked) $('#autoUpdate').fadeIn('slow');
    else $('#autoUpdate').fadeOut('slow');
  });

  $('#checkbox6').change(function(){
    if(this.checked) $('#autoUpdate5').fadeIn('slow');
    else $('#autoUpdate5').fadeOut('slow');
  });
});
</script>

<?php
} else {
    echo "<div class='alert alert-danger'>No student ID provided.</div>";
}
?>
