<?php 
session_start();
require "../database/db_connect.php";
$db_handle = new DBController();

if (isset($_REQUEST['id'])) {
    $student_id = intval($_REQUEST['id']);
    $table = $_REQUEST['st_student_master'];
    
    $sql = "SELECT
        sm.student_id,
        sm.photo,
        sm.mobile,
        sm.registration_no,
        sm.academic_year,
        sm.roll_no,
        sm.fname,
        sm.mname,
        sm.lname,
        sm.dob,
        sm.gender,
        sm.joining_date,
        sm.permanent_address,
        sm.email,
        sm.city,
        sm.phone,
        sm.pincode,
        sm.country,
        sm.state,
        sm.present_address,
        sm.nationality,
        sm.appar,
        sm.uan,
        sm.pan,
        sm.cgpa,
        cl.class_name,
        sec.sections,
        dep.department_name,
        sp.specialization_name,
        ssb.subject_name
    FROM $table sm
    LEFT JOIN st_class_master cl ON cl.class_id = sm.class_id
    LEFT JOIN st_section_master sec ON sec.id = sm.division_id
    LEFT JOIN st_department_master dep ON dep.department_id = sm.department_id
    LEFT JOIN st_specialization_master sp ON sp.specialization_id = sm.specialization_id
    LEFT JOIN st_specialization_subject_master ssb ON ssb.subject_id = sm.specialization_subject_id
    WHERE sm.student_id = $student_id AND sm.status = '1'";
    
    $result = $db_handle->query($sql);
    $row = $result->fetch_assoc();
    
    if (!$row) {
        echo "<div class='alert alert-danger'>Student record not found.</div>";
        exit;
    }
    
    $photo = !empty($row['photo']) ? $row['photo'] : 'student.JPG';
?>
<style>
    .view-field { margin-bottom: 15px; }
    .view-label { font-weight: bold; color: #333; margin-bottom: 5px; }
    .view-value { color: #666; padding: 8px; background-color: #f9f9f9; border-radius: 3px; }
    .view-photo { text-align: center; margin-bottom: 20px; }
    .view-photo img { max-width: 120px; border-radius: 50%; border: 2px solid #ddd; }
</style>

<div class="view-photo">
    <img src="student_photo/<?php echo htmlspecialchars($photo); ?>" alt="Student Photo">
</div>

<div class="row">
    <div class="col-md-6">
        <div class="view-field">
            <div class="view-label">Registration Number:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['registration_no']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">First Name:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['fname']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">Middle Name:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['mname']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">Last Name:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['lname']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">Date of Birth:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['dob']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">Gender:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['gender']); ?></div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="view-field">
            <div class="view-label">Academic Year:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['academic_year']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">Class:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['class_name']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">Section:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['sections']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">Roll No:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['roll_no']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">Joining Date:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['joining_date']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">Department:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['department_name']); ?></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="view-field">
            <div class="view-label">Specialization:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['specialization_name']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">Specialization Subject:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['subject_name']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">CGPA:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['cgpa']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">Nationality:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['nationality']); ?></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="view-field">
            <div class="view-label">Mobile:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['mobile']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">Phone:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['phone']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">Email:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['email']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">City:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['city']); ?></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="view-field">
            <div class="view-label">Permanent Address:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['permanent_address']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">Present Address:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['present_address']); ?></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="view-field">
            <div class="view-label">Pincode:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['pincode']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">State:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['state']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">Country:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['country']); ?></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="view-field">
            <div class="view-label">APAAR ID:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['appar']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">UAN:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['uan']); ?></div>
        </div>
        <div class="view-field">
            <div class="view-label">PAN:</div>
            <div class="view-value"><?php echo htmlspecialchars($row['pan']); ?></div>
        </div>
    </div>
</div>
<?php
} else {
    echo "<div class='alert alert-danger'>No student ID provided.</div>";
}
?> 

   if (isset($_REQUEST['id'])) {
       
     $id = intval($_REQUEST['id']);
     $dsms_student_master = $_REQUEST['dsms_student_master'];
     $result=mysqli_query($db_handel->conn,"SELECT * FROM $dsms_student_master WHERE std_id='$id'");
   
   while($row=$result->fetch_assoc()){
                     $std_id = $row['std_id'];
                     $medium = $row['medium'];
                     $academic_year = $row['academic_year'];
                     $register_number = $row['register_number'];
                     $gr_no  = $row['gr_no'];
                     $aadhar_no  = $row['aadhar_no'];
                     $joining_date = $row['joining_date'];
                     $class1 = $row['class'];
                     $batch = $row['batch'];
                     $roll_no = $row['roll_no'];
                     $fname = $row['fname'];
                     $mname = $row['mname'];
                     $lname = $row['lname'];
                     $dob = $row['dob'];
                     $gender = $row['gender'];
                     $blood_group = $row['blood_group'];
                     $birth_place = $row['birth_place'];
                     $nationality = $row['nationality'];
                     $mother_tongue = $row['mother_tongue'];
                     $category = $row['category'];
                     $religion = $row['religion'];
                     $caste = $row['caste'];
                     $permanent_address = $row['permanent_address'];
                     $present_address = $row['present_address'];
                     $city = $row['city'];
                     $pincode = $row['pincode'];
                     $country = $row['country'];
                     $state = $row['state'];
                     $phone = $row['phone'];
                     $mobile = $row['mobile'];
                     $email_id = $row['email_id'];
                     $photo = $row['photo'];
                     $parent_name = $row['parent_name'];
                     $parent_mobile = $row['parent_mobile'];
                     $parent_email = $row['parent_email'];
                     $parent_job = $row['parent_job'];
                     $mother_name = $row['mother_name'];
                     $mother_mobile = $row['mother_mobile'];
                     $mother_email = $row['mother_email'];
                     $mother_job = $row['mother_job'];
                     $qualification = $row['qualification'];
                     $school_name = $row['school_name'];
                     $school_address = $row['school_address'];
                     $marklist = $row['marklist'];
                     $birth_certificate = $row['birth_certificate'];
                     $transfer_certificate = $row['transfer_certificate'];
                     $caste_certificate = $row['caste_certificate'];
                     $migration_certificate = $row['migration_certificate'];
                     $affidavit = $row['affidavit'];
        
                     
           }
           }          
   
   ?>
<!--end code -->
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
<section class="content-header">
   <h1>
      Student Details View
   </h1>
   <ol class="breadcrumb">
      <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
      <li><a href="#">student</a></li>
      <li class="active">View</li>
   </ol>
</section>
<!-- Main content -->
<section class="content">
   <!-- new formset-->
   <form action="student-update.php" name="myform" method="POST" onsubmit="return validateform()" enctype="multipart/form-data">
      <div class="box box-default" style="padding: 10px;">
         <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
            <h3 class="box-title">OFFICIAL DETAILS:- </h3>
            <div class="box-tools pull-right">
               <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
            </div>
         </div>
         <!-- /.box-header -->
         <div class="box-body">
            <div class="row">
               <div class="col-md-4">

               <div class="form-group">
                <label> Medium <span style="color: red;">*</span> </label>
                <select class="form-control select" name="medium" id="medium" style="width: 100%;" readonly>
                        <option value="">Select Your Medium</option>
                        <option value="HINDI" <?php if($medium=="HINDI") echo 'selected="selected"'; ?>>HINDI</option>
                        <option value="ENGLISH" <?php if($medium=="ENGLISH") echo 'selected="selected"'; ?>>ENGLISH</option>
                     </select>
              </div>

                  <div class="form-group">
                     <label>Academic Year <span style="color: red;">*</span> </label>
                     <select class="form-control select" name="academic" id="academic" style="width: 100%;" readonly>
                        <option value="">Select Academic Year</option>
                        <option value="2021 - 2022" <?php if($academic_year=="2021 - 2022") echo 'selected="selected"'; ?>>2021-2022</option>
                        <option value="2022 - 2023" <?php if($academic_year=="2022 - 2023") echo 'selected="selected"'; ?>>2022-2023</option>
                        <option value="2023 - 2024" <?php if($academic_year=="2023 - 2024") echo 'selected="selected"'; ?>>2023-2024</option>
                        <option value="2024 - 2025" <?php if($academic_year=="2024 - 2025") echo 'selected="selected"'; ?>>2024-2025</option>
                        <option value="2025 - 2026" <?php if($academic_year=="2025 - 2026") echo 'selected="selected"'; ?>>2025-2026</option>
                     </select>
                  </div>
               
                  <div class="form-group">
                     <label>Class <span style="color: red;">*</span></label>
                     <select class="form-control select" name="class" id="class" class="class" style="width: 100%;" readonly>
                        <option>Select Class</option>
                        <?php
                           $result=mysqli_query($db_handel->conn,("select * from class_master"));
                           
                           while($row=$result->fetch_assoc()){
                            $c_name = $row['class'];
                            $c_id = $row['id'];
                                               
                           ?>
                        <option value="<?php echo  $c_id;  ?>" <?php if($c_id=="$class1") echo 'selected="selected"'; ?>><?php echo $c_name;  ?></option>
                        <?php } ?>
                     </select>
                  </div>
                  <!-- /.form-group -->
               </div>
               <!-- /.col -->
               <div class="col-md-4">
                  <div class="form-group">
                     <label>Registration Number <span style="color: red;">*</span></label>
                     <input type="text" name="registration_no" id="registration_no" value="<?php echo $register_number; ?>" class="form-control"  data-placeholder="" style="width: 100%;" readonly>
                     <input type="hidden" name="table_name" id="table_name" value="<?php echo $dsms_student_master; ?>" class="form-control"  data-placeholder="" style="width: 100%;">
                  </div>
                  <!-- /.form-group -->
                  <div class="form-group">
                     <label>Batch <span style="color: red;">*</span></label>
                     <select class="form-control select" name="batch" id="batch" class="batch" style="width: 100%;" readonly>
                        <option>Select Batch</option>
                        <?php
                            $result=mysqli_query($db_handel->conn,("select * from section_master"));
                           
                           while($row=$result->fetch_assoc()){
                            $section_name = $row['sections'];
                            $s_id = $row['id'];
                                               
                           ?>
                        <option value="<?php echo $s_id; ?>" <?php if($s_id=="$batch"){ echo 'selected="selected"';} ?>><?php echo $section_name;  ?></option>
                        <?php } ?>
                     </select>
                  </div>

                   <div class="form-group">
                <label>GR Number </label>
                <input type="text" name="gr_no" value="<?php echo $gr_no; ?>" id="gr_no"  class="form-control" style="width: 100%;" readonly>
              </div>

                  <!-- /.form-group -->
               </div>
               <!-- /.col -->
               <div class="col-md-4">
                  <div class="form-group">
                     <label>Joining Date <span style="color: red;">*</span></label>
                     <div class="input-group date">
                        <div class="input-group-addon">
                           <i class="fa fa-calendar"></i>
                        </div>
                        <input type="text" name="join_date" id="join_date" placeholder="DD-MM-YYYY" value="<?php echo $joining_date; ?> " class="form-control pull-right" readonly>
                     </div>
                  </div>
                  <!-- /.form-group -->
                  <div class="form-group">
                     <label>Roll No.</label>
                     <input type="text" name="roll_no" class="form-control" value="<?php echo $roll_no; ?>"  data-placeholder="" style="width: 100%;" readonly>
                     <input type="hidden" name="std_id" value="<?php echo $std_id; ?>" />
                  </div>

                   <div class="form-group">
                <label>Aadhar Card Number</label>
                <input type="text" name="aadhar_no" value="<?php echo $aadhar_no; ?>" id="aadhar_no"  class="form-control" style="width: 100%;" readonly>
              </div>

                  <!-- /.form-group -->
               </div>
               <!-- /.col -->
            </div>
            <!-- /.row -->
         </div>
         <!-- /.box-body -->
      </div>
      <!--end formset-->
      <!-- new formset2-->
      <div class="box box-default" style="padding: 10px;">
         <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
            <h3 class="box-title">PERSONAL DETAILS:- </h3>
            <div class="box-tools pull-right">
               <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
            </div>
         </div>
         <!-- /.box-header -->
         <div class="box-body">
            <div class="row">
               <div class="col-md-4">
                  <div class="form-group">
                     <label>First Name <span style="color: red;">*</span> </label>
                     <input type="text" name="fname" value="<?php echo $fname; ?>" class="form-control"   data-placeholder="" style="width: 100%;" readonly>
                  </div>
                  <!-- /.form-group -->
                  <div class="form-group">
                     <label>Date of Birth <span style="color: red;">*</span></label>
                     <div class="input-group date">
                        <div class="input-group-addon">
                           <i class="fa fa-calendar"></i>
                        </div>
                        <input type="text" name="dob" id="dob" value="<?php echo $dob; ?>" class="form-control pull-right" placeholder="DD-MM-YYYY" readonly>
                     </div>
                  </div>
                  <div class="form-group">
                     <label>Birth Place</label>
                     <input type="text" name="birth_place" value="<?php echo $birth_place; ?>" class="form-control"  data-placeholder="" style="width: 100%;" readonly>
                  </div>

                  <div class="form-group">
                     <label>Caste</label>
                     <input type="text" name="cat" value="<?php echo $category; ?>" class="form-control"  data-placeholder="" style="width: 100%;" readonly>
                  </div>

               </div>
               <!-- /.col -->
               <div class="col-md-4">
                  <div class="form-group">
                     <label>Middle Name</label>
                     <input type="text" name="mname" value="<?php echo $mname; ?>" class="form-control"  data-placeholder="" style="width: 100%;" readonly>
                  </div>
                  <!-- /.form-group -->
                  <div class="form-group">
                     <label>Gender <span style="color: red;">*</span></label>
                     <select class="form-control select" name="gender" style="width: 100%;" readonly>
                        <option value="">Please Select</option>
                        <option value="MALE" <?php if($gender=="MALE") echo 'selected="selected"'; ?>>Male</option>
                        <option value="FEMALE" <?php if($gender=="FEMALE") echo 'selected="selected"'; ?>>FEMALE</option>
                     </select>
                  </div>
                  <div class="form-group">
                     <label>Nationality</label>
                     <select class="form-control select" name="nation" style="width: 100%;" readonly>
                        <option value="">Select Country</option>
                        <option value="INDIAN" <?php if($nationality=="INDIAN") echo 'selected="selected"'; ?>>INDIAN</option>
                     </select>
                  </div>
                  <div class="form-group">
                     <label>Religion</label>
                     <select class="form-control select" name="religion" style="width: 100%;" readonly>
                        <option value="">Select Religion</option>
                        <option value="HINDU" <?php if($religion=="HINDU") echo 'selected="selected"'; ?>>HINDU</option>
                        <option value="ISLAM" <?php if($religion=="ISLAM") echo 'selected="selected"'; ?>>ISLAM</option>
                        <option value="MUSLIM" <?php if($religion=="MUSLIM") echo 'selected="selected"'; ?>>MUSLIM</option>
                        <option value="CHRISTIAN" <?php if($religion=="CHRISTIAN") echo 'selected="CHRISTIAN"'; ?>>CHRISTIAN</option>
                        <option value="SIKH" <?php if($religion=="SIKH") echo 'selected="selected"'; ?>>SIKH</option>
                        <option value="BUDDHIST" <?php if($religion=="BUDDHIST") echo 'selected="selected"'; ?>>BUDDHIST</option>
                        <option value="JAIN" <?php if($religion=="JAIN") echo 'selected="selected"'; ?>>JAIN</option>
                        <option value="OTHER" <?php if($religion=="OTHER") echo 'selected="selected"'; ?>>OTHER</option>
                     </select>
                  </div>
                  <!-- /.form-group -->
               </div>
               <!-- /.col -->
               <!-- /.col -->
               <div class="col-md-4">
                  <div class="form-group">
                     <label>Last Name <span style="color: red;">*</span> </label>
                     <input type="text" name="lname" value="<?php echo $lname; ?>" class="form-control"  data-placeholder="" style="width: 100%;" readonly>
                  </div>
                  <!-- /.form-group -->
                  <div class="form-group">
                     <label>Blood Group</label>
                     <select class="form-control select" name="blood" style="width: 100%;" readonly>
                        <option value="">Please Select</option>
                        <option value="A+" <?php if($blood_group=="A+") echo 'selected="selected"'; ?>>A+</option>
                        <option value="A-" <?php if($blood_group=="A-") echo 'selected="selected"'; ?>>A-</option>
                        <option value="B+" <?php if($blood_group=="B+") echo 'selected="selected"'; ?>>B+</option>
                        <option value="B-" <?php if($blood_group=="B-") echo 'selected="selected"'; ?>>B-</option>
                        <option value="AB+" <?php if($blood_group=="AB+") echo 'selected="selected"'; ?>>AB+</option>
                        <option value="AB_" <?php if($blood_group=="AB-") echo 'selected="selected"'; ?>>AB-</option>
                        <option value="O" <?php if($blood_group=="O") echo 'selected="selected"'; ?>>O</option>
                     </select>
                  </div>
                  <div class="form-group">
                     <label>Mother Tongue </label>
                     <input type="text" name="mother_tongue" value="<?php echo $mother_tongue; ?>" class="form-control"  data-placeholder="" style="width: 100%;" readonly>
                  </div>
                  <div class="form-group">
                     <label>Sub-Caste</label>
                     <input type="text" name="caste" value="<?php echo $caste; ?>" class="form-control"  data-placeholder="" style="width: 100%;" readonly>
                  </div>
                  <!-- /.form-group -->
               </div>
               <!-- /.col -->
            </div>
            <!-- /.row -->
         </div>
         <!-- /.box-body -->
      </div>
      <!--end formset2-->
      <!-- new formset3-->
      <div class="box box-default" style="padding: 10px;">
         <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
            <h3 class="box-title">CONTACT DETAILS:- </h3>
            <div class="box-tools pull-right">
               <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
            </div>
         </div>
         <!-- /.box-header -->
         <div class="box-body">
            <div class="row">
               <div class="col-md-6">
                  <div class="form-group">
                     <label>Permanent Address</label>
                     <textarea class="form-control" name="permanent"  data-placeholder="" style="width: 100%;" readonly><?php echo $permanent_address; ?>
                     </textarea>
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="form-group">
                     <label>Present Address <span style="color: red;">*</span> </label>
                     <textarea class="form-control" name="present"  data-placeholder="" style="width: 100%;" readonly><?php echo $present_address; ?>
                     </textarea>
                  </div>
               </div>
               <!-- /.col -->
            </div>
            <div class="row">
               <div class="col-md-4">
                  <div class="form-group">
                     <label>City</label>
                     <input type="text" class="form-control" name="city" value="<?php echo $city; ?>"  data-placeholder="" style="width: 100%;" readonly>
                  </div>
               </div>
               <div class="col-md-4">
                  <div class="form-group">
                     <label>Pincode</label>
                     <input type="text" class="form-control" name="pincode" value="<?php echo $pincode; ?>"  data-placeholder="" style="width: 100%;" readonly>
                  </div>
               </div>
               <div class="col-md-4">
                  <div class="form-group">
                     <label>Country</label>                
                     <input type="text" class="form-control" name="country" value="<?php echo $country; ?>"  data-placeholder="" style="width: 100%;" readonly>
                  </div>
               </div>
            </div>
            <div class="row">
               <div class="col-md-4">
                  <div class="form-group">
                     <label>State</label>
                     <input type="text" class="form-control" name="state" value="<?php echo $state; ?>"  data-placeholder="" style="width: 100%;" readonly>
                  </div>
               </div>
               <div class="col-md-4">
                  <div class="form-group">
                     <label>Phone</label>
                     <input type="text" class="form-control" name="phone" value="<?php echo $phone; ?>"  data-placeholder="" style="width: 100%;" readonly>
                  </div>
               </div>
               <div class="col-md-4">
                  <div class="form-group">
                     <label>Mobile</label>
                     <input type="text" class="form-control" name="mobile" value="<?php echo $mobile; ?>"  data-placeholder="" style="width: 100%;" readonly>
                  </div>
               </div>
            </div>
            <div class="row">
               <div class="col-md-6">
                  <div class="form-group">
                     <label>Email</label>
                     <input type="email" class="form-control" name="email" value="<?php echo $email_id; ?>"  data-placeholder="" style="width: 100%;" readonly>
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="form-group">
                    <label>Upload Photo</label>
                    <div class="col-sm-8">
                        <img src="../manage/pdf/<?php echo $photo; ?>" width="100" />
                        <input type='file' id="photo" name="photo" onchange="show(this)" readonly/> 
                  </div>
				   
                  </div>
               </div>
            </div>
            <!-- /.row -->
         </div>
         <!-- /.box-body -->
      </div>
      <!-- new formset3-->
      <div class="box box-default" style="padding: 10px;">
         <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
            <h3 class="box-title">FATHER DETAILS:- </h3>
            <div class="box-tools pull-right">
               <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
            </div>
         </div>
         <!-- /.box-header -->
         <div class="box-body">
            <div class="row">
               <div class="col-md-6">
                  <div class="form-group">
                     <label>Name <span style="color: red;">*</span> </label>
                     <input type="text" class="form-control" name="pname" value="<?php echo $parent_name; ?>" data-placeholder="" style="width: 100%;" readonly>
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="form-group">
                     <label>Mobile <span style="color: red;">*</span> </label>
                     <input type="text" class="form-control" name="pmobile" value="<?php echo $parent_mobile; ?>" data-placeholder="" style="width: 100%;" readonly>
                  </div>
               </div>
            </div>
            <div class="row">
               <div class="col-md-6">
                  <div class="form-group">
                     <label>Email</label>
                     <input type="email" class="form-control" name="pemail" value="<?php echo $parent_email; ?>"  data-placeholder="" style="width: 100%;" readonly>
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="form-group">
                     <label>Job <span style="color: red;">*</span> </label>
                     <input type="text" class="form-control" name="pjob" value="<?php echo $parent_job; ?>"  data-placeholder="" style="width: 100%;" readonly>
                  </div>
               </div>
            </div>
         </div>
      </div>

         <div class="box box-default" style="padding: 10px;">
         <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
            <h3 class="box-title">MOTHER DETAILS:- </h3>
            <div class="box-tools pull-right">
               <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
            </div>
         </div>
         <!-- /.box-header -->
         <div class="box-body">
            <div class="row">
               <div class="col-md-6">
                  <div class="form-group">
                     <label>Name <span style="color: red;">*</span> </label>
                     <input type="text" class="form-control" name="m_name" value="<?php echo $mother_name; ?>" data-placeholder="" style="width: 100%;" readonly>
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="form-group">
                     <label>Mobile <span style="color: red;">*</span> </label>
                     <input type="text" class="form-control" name="m_mobile" value="<?php echo $mother_mobile; ?>" data-placeholder="" style="width: 100%;" readonly>
                  </div>
               </div>
            </div>
            <div class="row">
               <div class="col-md-6">
                  <div class="form-group">
                     <label>Email</label>
                     <input type="email" class="form-control" name="memail" value="<?php echo $mother_email; ?>"  data-placeholder="" style="width: 100%;" readonly>
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="form-group">
                     <label>Job <span style="color: red;">*</span> </label>
                     <input type="text" class="form-control" name="mjob" value="<?php echo $mother_job; ?>"  data-placeholder="" style="width: 100%;" readonly>
                  </div>
               </div>
            </div>
         </div>
      </div>


      <div class="box box-default" style="padding: 10px;">
      <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
         <h3 class="box-title">PREVIOUS QUALIFICATION DETAILS:- </h3>
         <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
         </div>
      </div>
      <!-- /.box-header -->
<div class="box-body">
<div class="row">
   <div class="col-md-4">

      <div class="form-group">
         <label>Qualification <span style="color: red;">*</span></label>
         <select class="form-control select" name="education" id="education" class="class" style="width: 100%;" readonly>
                        <option>Select Qualification</option>
                          <?php
                           $result=mysqli_query($db_handel->conn,("select * from class_master"));
                           while($row=$result->fetch_assoc()){
                            $c_name = $row['class'];
                            $c_id = $row['id'];                
                           ?>
                       <option value="<?php echo  $c_id;  ?>" <?php if($c_id=="$qualification") echo 'selected="selected"'; ?>><?php echo $c_name;  ?></option>
                        <?php } ?>
            </select>
            </div>
         </div>

         <div class="col-md-4">
            <div class="form-group">
               <label>School Name</label>
               <input type="text" class="form-control" name="school" value="<?php echo $school_name; ?>"  data-placeholder="" style="width: 100%;" readonly>
            </div>
         </div>
         <div class="col-md-4">
            <div class="form-group">
               <label>School Address</label>
               <textarea class="form-control" name="saddress"  data-placeholder="" style="width: 100%;" readonly><?php echo $school_address; ?>
               </textarea>
            </div>
         </div>
      </div>

      <div class="row">
      <div class="col-md-6">
         <div class="form-group">
            <?php if($marklist != "") { ?>
            <input type="checkbox" id="checkbox1" checked="checked" />
            <?php } else { ?>
            <input type="checkbox" id="checkbox1"/>
            <?php } ?>
            <label>Mark List</label>
         </div>
      </div>

      <?php if ($marklist != "") { ?>           
      <div id="autoUpdate" class="autoUpdate">
         <?php } else { ?>
         <div id="autoUpdate" class="autoUpdate" style="display:none">
            <?php } ?>
            <?php if($marklist != "") { ?>
            <a href="documents/<?php echo $marklist; ?>"><img src="documents/<?php echo $marklist; ?>" class="img-responsive" width="50" /></a> 
            <?php } ?>
            <input type="file" name="mark-list">
         </div>
         <script type="text/javascript">
            $(document).ready(function(){
              $('#checkbox1').change(function(){
              if(this.checked)
              $('#autoUpdate').fadeIn('slow');
              else
              $('#autoUpdate').fadeOut('slow');
            
              });
              });
         </script>
      </div>
      <div class="row">
      <div class="col-md-6">
         <div class="form-group">
            <?php if($birth_certificate != "") { ?>
            <input type="checkbox" id="checkbox2" checked="checked" />
            <?php } else { ?>
            <input type="checkbox" id="checkbox2"/>
            <?php } ?>
            <label>Birth Certificate</label>
         </div>
      </div>
      <?php if ($birth_certificate != "") { ?>           
      <div id="autoUpdate1" class="autoUpdate1">
         <?php } else { ?>
         <div id="autoUpdate1" class="autoUpdate1" style="display:none">
            <?php } ?>
            <?php if($birth_certificate != "") { ?>
            <a href="documents/<?php echo $birth_certificate; ?>"><img src="documents/<?php echo $birth_certificate; ?>" class="img-responsive" width="50" /></a>
            <?php } ?>
            <input type="file" name="bc">
         </div>
         <script type="text/javascript">
            $(document).ready(function(){
              $('#checkbox2').change(function(){
              if(this.checked)
              $('#autoUpdate1').fadeIn('slow');
              else
              $('#autoUpdate1').fadeOut('slow');
            
                });
                });
         </script>
      </div>
      <div class="row">
      <div class="col-md-6">
         <div class="form-group">
            <?php if($transfer_certificate != "") { ?>
            <input type="checkbox" id="checkbox3" checked="checked" />
            <?php } else { ?>
            <input type="checkbox" id="checkbox3"/>
            <?php } ?>
            <label>Transfer Certificate</label>
         </div>
      </div>
      <?php if ($transfer_certificate != "") { ?>           
      <div id="autoUpdate2" class="autoUpdate2">
         <?php } else { ?>
         <div id="autoUpdate2" class="autoUpdate2" style="display:none">
            <?php } ?>
            <?php if($transfer_certificate != "") { ?>
            <a href="documents/<?php echo $transfer_certificate; ?>"><img src="documents/<?php echo $transfer_certificate; ?>" class="img-responsive" width="50" /></a>
            <?php } ?>
            <input type="file" name="tc">
         </div>
         <script type="text/javascript">
            $(document).ready(function(){
              $('#checkbox3').change(function(){
              if(this.checked)
              $('#autoUpdate2').fadeIn('slow');
              else
              $('#autoUpdate2').fadeOut('slow');
            
                });
                });
         </script>
      </div>
      <div class="row">
         <div class="col-md-6">
            <div class="form-group">
               <?php if($caste_certificate != "") { ?>
               <input type="checkbox" id="checkbox4" checked="checked" />
               <?php } else { ?>
               <input type="checkbox" id="checkbox4"/>
               <?php } ?>
               <label>Caste Certificate</label>
            </div>
         </div>
         <?php if ($caste_certificate != "") { ?>           
         <div id="autoUpdate3" class="autoUpdate3">
            <?php } else { ?>
            <div id="autoUpdate3" class="autoUpdate3" style="display:none">
               <?php } ?>
               <?php if($caste_certificate != "") { ?>
               <a href="documents/<?php echo $caste_certificate; ?>"><img src="documents/<?php echo $caste_certificate; ?>" class="img-responsive" width="50" /></a>
               <?php } ?>
               <input type="file" name="cc">
            </div>
            <script type="text/javascript">
               $(document).ready(function(){
                 $('#checkbox4').change(function(){
                 if(this.checked)
                 $('#autoUpdate3').fadeIn('slow');
                 else
                 $('#autoUpdate3').fadeOut('slow');
               
                 });
                 });
            </script>
         </div>
         <div class="row">
            <div class="col-md-6">
               <div class="form-group">
                  <?php if($migration_certificate != "") { ?>
                  <input type="checkbox" id="checkbox5" checked="checked" />
                  <?php } else { ?>
                  <input type="checkbox" id="checkbox5"/>
                  <?php } ?>
                  <label>Migration Certificate</label>
               </div>
            </div>
            <?php if ($migration_certificate != "") { ?>           
            <div id="autoUpdate4" class="autoUpdate4">
               <?php } else { ?>
               <div id="autoUpdate4" class="autoUpdate4" style="display:none">
                  <?php } ?>
                  <?php if($migration_certificate != "") { ?>
                  <a href="documents/<?php echo $migration_certificate; ?>"><img src="documents/<?php echo $migration_certificate; ?>" class="img-responsive" width="50" /></a>
                  <?php } ?>
                  <input type="file" name="migration">
               </div>
               <script type="text/javascript">
                  $(document).ready(function(){
                    $('#checkbox5').change(function(){
                    if(this.checked)
                    $('#autoUpdate4').fadeIn('slow');
                    else
                    $('#autoUpdate4').fadeOut('slow');
                  
                      });
                      });
               </script>
            </div>
            <div class="row">
               <div class="col-md-6">
                  <div class="form-group">
                     <?php if($affidavit != "") { ?>
                     <input type="checkbox" id="checkbox6" checked="checked" />
                     <?php } else { ?>
                     <input type="checkbox" id="checkbox6"/>
                     <?php } ?>
                     <label>Affidavit</label>
                  </div>
               </div>
               <?php if ($affidavit != "") { ?>           
               <div id="autoUpdate5" class="autoUpdate5">
                  <?php } else { ?>
                  <div id="autoUpdate5" class="autoUpdate5" style="display:none">
                     <?php } ?>
                     <?php if($affidavit != "") { ?>
                     <a href="documents/<?php echo $affidavit; ?>"><img src="documents/<?php echo $affidavit; ?>" class="img-responsive" width="50" /></a>
                     <?php  } ?>
                     <input type="file" name="affidavit">
                  </div>
                  <script type="text/javascript">
                     $(document).ready(function(){
                       $('#checkbox6').change(function(){
                       if(this.checked)
                       $('#autoUpdate5').fadeIn('slow');
                       else
                       $('#autoUpdate5').fadeOut('slow');
                     
                         });
                         });
                  </script>
               </div>
            </div>
            <br>
         </div>
   </form>
   <!--end formset3-->
</section>
<!-- /.content -->

<script type="text/javascript">
   function show(input) {
        debugger;
        var validExtensions = ['jpg','png','jpeg']; //array of valid extensions
        var fileName = input.files[0].name;
        var fileNameExt = fileName.substr(fileName.lastIndexOf('.') + 1);
        if ($.inArray(fileNameExt, validExtensions) == -1) {
            input.type = ''
            input.type = 'file'
            $('#user_img').attr('src',"");
            alert("Only these file types are accepted : "+validExtensions.join(', '));
        }
        else
        {
        if (input.files && input.files[0]) {
            var filerdr = new FileReader();
            filerdr.onload = function (e) {
                $('#user_img').attr('src', e.target.result);
            }
            filerdr.readAsDataURL(input.files[0]);
        }
        }
    }
  </script>
</div>
<!-- /.content-wrapper -->
<?php include "header/footer.php" ?>