<?php include "header/header.php"; ?>
<script>
<<<<<<< HEAD
function validateform(){

var academic=document.myform.academic.value;

var class1=document.myform.class.value;

var registration_no=document.myform.registration_no.value;

var batch=document.myform.batch.value;

var join_date=document.myform.join_date.value;

var fname=document.myform.fname.value;

var dob=document.myform.dob.value;

var cat=document.myform.cat.value;

var gender=document.myform.gender.value;

var lname=document.myform.lname.value;

var present=document.myform.present.value;

var pname=document.myform.pname.value;

var pmobile=document.myform.pmobile.value;

var pjob=document.myform.pjob.value;

var pincode=document.myform.pincode.value;

var country=document.myform.country.value;

var state=document.myform.state.value;

var mobile=document.myform.mobile.value;
var specializationSelect = document.getElementById("specialization_select");
var specializationText = "";
var cgpaInput = document.getElementById("cgpa");
var cgpaValue = "";

if (specializationSelect && specializationSelect.selectedIndex >= 0) {
  specializationText = specializationSelect.options[specializationSelect.selectedIndex].text.toLowerCase();
}

if (cgpaInput) {
  cgpaValue = cgpaInput.value;
}


if (academic==null || academic==""){
  alert("Academic Year can't be blank.");
  return false;
}
if(class1==null || class1==""){
  alert("Class can't be blank.");
  return false;
  }
if(registration_no==null || registration_no==""){
  alert("Registration Number can't be blank.");
  return false;
  }
if(batch==null || batch==""){
  alert("Batch  can't be blank.");
  return false;
  }
if(join_date==null || join_date==""){
  alert("Joining Date can't be blank.");
  return false;
  }
if(fname==null || fname==""){
  alert("First Name can't be blank.");
  return false;
  }
if (specializationText.indexOf("minor") !== -1) {
  if ($('#specialization_subject_select').prop('selectedIndex') <= 0) {
    alert("Please select Specialization Subject for Minor Degree.");
    return false;
  }
}
if (specializationText.indexOf("honours") !== -1 || specializationText.indexOf("honors") !== -1) {
  if (cgpaValue == null || cgpaValue === "") {
    alert("Please enter CGPA for Honours.");
    return false;
  }
  if (isNaN(cgpaValue)) {
    alert("Please enter valid numeric CGPA.");
    return false;
  }
  if (parseFloat(cgpaValue) <= 7) {
    alert("Not eligible to register in Honours. CGPA must be above 7.");
    return false;
  }
  if ($('#specialization_subject_select').prop('selectedIndex') <= 0) {
    alert("Please select Specialization Subject for Honours.");
    return false;
  }
}
else if (isNaN(mobile)){
  alert("Enter only numeric value in mobile field.");
  return false;
}else{
  return true;
  }
}
</script>

<script type="text/javascript">
  function display2(){
    
var class1 = $('#class4').val();
var section = $('#batch4').val();

$.ajax({
        type: 'POST',
        url: 'getroll_no.php',
        data:{"class":class1,"section":section},
       
        success: function (response) {
            $("#data421").val(response);
            console.log(response);
        },
       
     });
  }

</script>

<script>
  function ckeck_reg()
  {

    var register = document.getElementById("registration_no").value;
    

if(register)
 {
  $.ajax({
  type: 'post',
  url: 'reg_no.php',
  data: {
   "register1":register,
  },
  success: function (response) {

    console.log(response);
 
   if(response==1) 
   {
    
    $('#registration_no').val("");
    

    alert("the Register number is already exist!!");
   }
  
  }
  });

 }

}


=======
  function validateform() {

    var academic = document.myform.academic.value;

    var class1 = document.myform.class.value;

    var registration_no = document.myform.registration_no.value;

    var batch = document.myform.batch.value;

    var join_date = document.myform.join_date.value;

    var fname = document.myform.fname.value;

    var dob = document.myform.dob.value;

    var cat = document.myform.cat.value;

    var gender = document.myform.gender.value;

    var lname = document.myform.lname.value;

    var present = document.myform.present.value;

    var pname = document.myform.pname.value;

    var pmobile = document.myform.pmobile.value;

    var pjob = document.myform.pjob.value;

    var pincode = document.myform.pincode.value;

    var country = document.myform.country.value;

    var state = document.myform.state.value;

    var mobile = document.myform.mobile.value;
    var specializationSelect = document.getElementById("specialization_select");
    var specializationText = "";
    var cgpaInput = document.getElementById("cgpa");
    var cgpaValue = "";

    if (specializationSelect && specializationSelect.selectedIndex >= 0) {
      specializationText = specializationSelect.options[specializationSelect.selectedIndex].text.toLowerCase();
    }

    if (cgpaInput) {
      cgpaValue = cgpaInput.value;
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
      alert("Batch  can't be blank.");
      return false;
    }
    if (join_date == null || join_date == "") {
      alert("Joining Date can't be blank.");
      return false;
    }
    if (fname == null || fname == "") {
      alert("First Name can't be blank.");
      return false;
    }
    if (specializationText.indexOf("minor") !== -1) {
      if ($('#specialization_subject_select').prop('selectedIndex') <= 0) {
        alert("Please select Specialization Subject for Minor Degree.");
        return false;
      }
    }
    if (specializationText.indexOf("honours") !== -1 || specializationText.indexOf("honors") !== -1) {
      if (cgpaValue == null || cgpaValue === "") {
        alert("Please enter CGPA for Honours.");
        return false;
      }
      if (isNaN(cgpaValue)) {
        alert("Please enter valid numeric CGPA.");
        return false;
      }
      if (parseFloat(cgpaValue) <= 7) {
        alert("Not eligible to register in Honours. CGPA must be above 7.");
        return false;
      }
      if ($('#specialization_subject_select').prop('selectedIndex') <= 0) {
        alert("Please select Specialization Subject for Honours.");
        return false;
      }
    } else if (isNaN(mobile)) {
      alert("Enter only numeric value in mobile field.");
      return false;
    } else {
      return true;
    }
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
        success: function(response) {

          console.log(response);

          if (response == 1) {

            $('#registration_no').val("");


            alert("the Register number is already exist!!");
          }

        }
      });

    }

  }
>>>>>>> bc3b82053a8c25ccabcc37e1cf379f56d6dae6a5
</script>

<script type="text/javascript">
  function setAdmissionDetailSectionsVisible(isVisible) {
    if (isVisible) {
      $('#below_eligibility_sections').show();
      $('#personal_details_section').show();
      $('#contact_details_section').show();
      $('#upload_documents_section').show();
    } else {
      $('#below_eligibility_sections').hide();
      $('#personal_details_section').hide();
      $('#contact_details_section').hide();
      $('#upload_documents_section').hide();
    }
  }

  function resetSpecializationConditionalUI() {
    $('#cgpa').val('');
    $('#specialization_subject_select').prop('selectedIndex', 0);
    $('#specialization_subject_wrapper').hide();
    $('#cgpa_section').hide();
    $('#honours_not_eligible').hide();
    setAdmissionDetailSectionsVisible(false);
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
    var isMinor = specializationText.indexOf('minor') !== -1;
    var isHonours = specializationText.indexOf('honours') !== -1 || specializationText.indexOf('honors') !== -1;

    resetSpecializationConditionalUI();

    if (isMinor) {
      $('#specialization_subject_wrapper').show();
      setAdmissionDetailSectionsVisible(true);
      return;
    }

    if (isHonours) {
      $('#cgpa_section').show();
    }
  }

  $(document).ready(function() {
    handleSpecializationSelection();

    $('#specialization_select').on('change', function() {
      handleSpecializationSelection();
    });

    $('#cgpa').on('input keyup change blur', function() {
      updateHonoursEligibility();
    });
  });
</script>

<<<<<<< HEAD
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Student Registration
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="#">student</a></li>
        <li class="active">Admission</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">

    <!-- new formset-->
  <form action="student.php" name="myform" method="POST" onsubmit="return validateform()" enctype="multipart/form-data">
    <div class="box box-default" style="padding: 10px;">
=======
<style>
  /* Modern form styling */
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


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <h1>
      Student Registration
    </h1>
    <ol class="breadcrumb">
      <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
      <li><a href="#">student</a></li>
      <li class="active">Admission</li>
    </ol>
  </section>

  <!-- Main content -->
  <section class="content">

    <!-- new formset-->
    <form action="student.php" name="myform" method="POST" onsubmit="return validateform()" enctype="multipart/form-data">
      <div class="box box-default" style="padding: 10px;">
>>>>>>> bc3b82053a8c25ccabcc37e1cf379f56d6dae6a5
        <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
          <h3 class="box-title">OFFICIAL DETAILS:- </h3>

          <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>

          </div>
        </div>
        <!-- /.box-header -->
        <div class="box-body">
<<<<<<< HEAD
        <div class="row">
          <div class="col-md-12">
            <div class="col-md-4"> 
               <div class="form-group">
                <label>Academic Year</label>
                <select class="form-control select" name="academic" id="academic" style="width: 100%;">
                  <option value="">Select Academic Year</option>
                  <option value="2024 - 2025">2024 - 2025</option>
                  <option value="2025 - 2026" >2025 - 2026</option>
                  <option value="2026 - 2027" selected>2026 - 2027</option>
                </select>
            </div>
            </div>
             <div class="col-md-4">
              <div class="form-group">
                <label>Registration Number <span style="color: red;">*</span></label>
                <input type="text" name="registration_no" value="" id="registration_no"  onblur="ckeck_reg()"  class="form-control"  style="width: 100%;">
              </div>
              </div>
              <div class="col-md-4">
              <div class="form-group">
                <label>Joining Date <span style="color: red;">*</span></label>
                <div class="input-group date">
                  <div class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                  </div>
                  <input type="date" name="join_date" id="join_date" class="form-control pull-right" placeholder="DD-MM-YYYY">
                </div>
              </div>
=======
          <div class="row">
            <div class="col-md-12">
              <div class="col-md-4">
                <div class="form-group">
                  <label>Academic Year</label>
                  <select class="form-control select" name="academic" id="academic" style="width: 100%;" required>
                    <option value="">Select Academic Year</option>
                    <option value="2024 - 2025">2024 - 2025</option>
                    <option value="2025 - 2026">2025 - 2026</option>
                    <option value="2026 - 2027" selected>2026 - 2027</option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Registration Number <span style="color: red;">*</span></label>
                  <input type="text" name="registration_no" value="" id="registration_no" onblur="ckeck_reg()" class="form-control" style="width: 100%;" required>
                  <small class="text-muted">Unique registration number (letters, numbers, hyphens only)</small>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label>Joining Date <span style="color: red;">*</span></label>
                  <div class="input-group date">
                    <div class="input-group-addon">
                      <i class="fa fa-calendar"></i>
                    </div>
                    <input type="date" name="join_date" id="join_date" class="form-control pull-right" placeholder="DD-MM-YYYY" required>
                  </div>
                </div>
>>>>>>> bc3b82053a8c25ccabcc37e1cf379f56d6dae6a5
              </div>
            </div><!--1 Section End->
             /.col -->

            <div class="col-md-12">
<<<<<<< HEAD
            <div class="col-md-4">     
             <div class="form-group">
                <label>Class <span style="color: red;">*</span></label>
                <select class="form-control select" name="class" id="class4"  class="class" style="width: 100%;">
                   <option>Select Class</option>
                        <?php
                          $result=$db_handle->conn->query("select * from st_class_master");

                          while($row=$result->fetch_assoc()){
                           $class_name = $row['class_name'];
                           $class_id = $row['class_id'];

                           ?>
                          <option value="<?php echo $class_id;?>"><?php echo $class_name;?></option>
                          <?php } ?>
                  </select>
              </div>
              </div> 
              <!-- /.form-group -->
              <div class="col-md-2">
              <div class="form-group">
                <label>Division<span style="color: red;">*</span></label>
                <select class="form-control select" name="batch" id="batch4" class="batch" onchange="display2()"  style="width: 100%;">
                   <option>Select Division</option>
                   <?php
                              $result=$db_handle->conn->query("SELECT * from st_section_master");

                               while($row=$result->fetch_assoc()){
                               $section_name = $row['sections'];
                               $s_id = $row['id'];

                           ?>
                           <option value="<?php echo $s_id;  ?>"><?php echo $section_name;  ?></option>
                          <?php } ?>

                </select>
              </div>
              </div>

              <div class="col-md-2">
              <div class="form-group">
                <label>Batch</label>
                <select class="form-control select" name="batch_id" id="batch_id" class="batch_id" style="width: 100%;">
                   <option>Select Batch</option>
                   <?php
                          $result=$db_handle->conn->query("SELECT * from st_batch_master");

                           while($row=$result->fetch_assoc()){
                           $batch_name = $row['batch_name'];
                           $batch_id = $row['batch_id'];
                           ?>
                           <option value="<?php echo $batch_id;  ?>"><?php echo $batch_name;  ?></option>
                          <?php } ?>

                </select>
              </div>
              </div>

               <div class="col-md-4">
              <div class="form-group">
                <label>Roll No.</label>
                <input type="text" name="roll_no" class="form-control" id="data421" data-placeholder="" style="width: 100%;">
              </div>
              </div>
           
          </div> <!-- /.col 12 -->
=======
              <div class="col-md-4">
                <div class="form-group">
                  <label>Class <span style="color: red;">*</span></label>
                  <select class="form-control select" name="class" id="class4" class="class" style="width: 100%;" required>
                    <option>Select Class</option>
                    <?php
                    $result = $db_handle->conn->query("select * from st_class_master");

                    while ($row = $result->fetch_assoc()) {
                      $class_name = $row['class_name'];
                      $class_id = $row['class_id'];

                    ?>
                      <option value="<?php echo $class_id; ?>"><?php echo $class_name; ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <!-- /.form-group -->
              <div class="col-md-2">
                <div class="form-group">
                  <label>Division<span style="color: red;">*</span></label>
                  <select class="form-control select" name="batch" id="batch4" class="batch" onchange="display2()" style="width: 100%;" required>
                    <option>Select Division</option>
                    <?php
                    $result = $db_handle->conn->query("SELECT * from st_section_master");

                    while ($row = $result->fetch_assoc()) {
                      $section_name = $row['sections'];
                      $s_id = $row['id'];

                    ?>
                      <option value="<?php echo $s_id;  ?>"><?php echo $section_name;  ?></option>
                    <?php } ?>

                  </select>
                </div>
              </div>

              <div class="col-md-2">
                <div class="form-group">
                  <label>Batch</label>
                  <select class="form-control select" name="batch_id" id="batch_id" class="batch_id" style="width: 100%;">
                    <option>Select Batch</option>
                    <?php
                    $result = $db_handle->conn->query("SELECT * from st_batch_master");

                    while ($row = $result->fetch_assoc()) {
                      $batch_name = $row['batch_name'];
                      $batch_id = $row['batch_id'];
                    ?>
                      <option value="<?php echo $batch_id;  ?>"><?php echo $batch_name;  ?></option>
                    <?php } ?>

                  </select>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group">
                  <label>Roll No.</label>
                  <input type="text" name="roll_no" class="form-control" id="data421" data-placeholder="" style="width: 100%;" required>
                </div>
              </div>

            </div> <!-- /.col 12 -->
>>>>>>> bc3b82053a8c25ccabcc37e1cf379f56d6dae6a5
            <!-- /.col -->

            <div class="col-md-12">

              <div class="col-md-4">
<<<<<<< HEAD
              <div class="form-group">
                <label>Department<span style="color: red;">*</span></label>
                 <select class="form-control select" name="department_id" id="department_select" class="batch" style="width: 100%;">
                   <option>Select Department</option>
                      <?php
                         $result=$db_handle->conn->query("SELECT * from st_department_master");
                         while($row=$result->fetch_assoc()){
                         $department_name = $row['department_name'];
                         $department_id = $row['department_id'];
                       ?>
                       <option value="<?php echo $department_id;  ?>"><?php echo $department_name;  ?></option>
                      <?php } ?>
                </select>
              </div>
              </div>

             <div class="col-md-4">
              <div class="form-group">
                <label>Specialization<span style="color: red;">*</span></label>
               <select class="form-control select" name="specialization_id" id="specialization_select" class="batch" style="width: 100%;">
                   <option>Select Specialization</option>
                   <?php
                            $result=$db_handle->conn->query("SELECT * from st_specialization_master");

                             while($row=$result->fetch_assoc()){
                             $specialization_name = $row['specialization_name'];
                             $specialization_id = $row['specialization_id'];
                           ?>
                           <option value="<?php echo $specialization_id;  ?>"><?php echo $specialization_name;  ?></option>
                          <?php } ?>

                </select>
              </div>
              </div>

            <div class="col-md-4" id="specialization_subject_wrapper" style="display: none;">
              <div class="form-group">
                <label>Specialization Subject<span style="color: red;">*</span></label>
                <select class="form-control select" name="unaided_subject" id="specialization_subject_select" class="batch" style="width: 100%;">
                   <option>Select Specialization Subject</option>
                   <?php
                            $result=$db_handle->conn->query("SELECT * from st_specialization_subject_master");

                             while($row=$result->fetch_assoc()){
                             $subject_name = $row['subject_name'];
                             $subject_id = $row['subject_id'];
                           ?>
                           <option value="<?php echo $subject_id;  ?>"><?php echo $subject_name;  ?></option>
                          <?php } ?>

                </select>
              </div>
              </div>
          
          </div>

                    <div class="col-md-12" id="cgpa_section" style="display: none;">

              <div class="col-md-4">
              <div class="form-group">
                <label>Enter Your CGPA(Aggregate)<span style="color: red;">*</span></label>
               <input type="text" name="cgpa" id="cgpa" class="form-control" data-placeholder="" style="width: 100%;">
              </div>
=======
                <div class="form-group">
                  <label>Department<span style="color: red;">*</span></label>
                  <select class="form-control select" name="department_id" id="department_select" class="batch" style="width: 100%;" required>
                    <option>Select Department</option>
                    <?php
                    $result = $db_handle->conn->query("SELECT * from st_department_master");
                    while ($row = $result->fetch_assoc()) {
                      $department_name = $row['department_name'];
                      $department_id = $row['department_id'];
                    ?>
                      <option value="<?php echo $department_id;  ?>"><?php echo $department_name;  ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-group">
                  <label>Specialization<span style="color: red;">*</span></label>
                  <select class="form-control select" name="specialization_id" id="specialization_select" class="batch" style="width: 100%;" required>
                    <option>Select Specialization</option>
                    <?php
                    $result = $db_handle->conn->query("SELECT * from st_specialization_master");

                    while ($row = $result->fetch_assoc()) {
                      $specialization_name = $row['specialization_name'];
                      $specialization_id = $row['specialization_id'];
                    ?>
                      <option value="<?php echo $specialization_id;  ?>"><?php echo $specialization_name;  ?></option>
                    <?php } ?>

                  </select>
                </div>
              </div>

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
                    ?>
                      <option value="<?php echo $subject_id;  ?>"><?php echo $subject_name;  ?></option>
                    <?php } ?>

                  </select>
                </div>
              </div>

            </div>

            <div class="col-md-12" id="cgpa_section" style="display: none;">

              <div class="col-md-4">
                <div class="form-group">
                  <label>Enter Your CGPA(Aggregate)<span style="color: red;">*</span></label>
                  <input type="text" name="cgpa" id="cgpa" class="form-control" data-placeholder="" style="width: 100%;">
                </div>
>>>>>>> bc3b82053a8c25ccabcc37e1cf379f56d6dae6a5
              </div>

              <div class="col-md-8" id="honours_not_eligible" style="display: none; margin-top: 30px; color: #d9534f; font-weight: 600;">
                Not eligible to register in Honours. CGPA must be above 7.
              </div>
<<<<<<< HEAD
          
          </div>


        </div>
=======

            </div>


          </div>
>>>>>>> bc3b82053a8c25ccabcc37e1cf379f56d6dae6a5
          <!-- /.row -->
        </div>
        <!-- /.box-body -->

<<<<<<< HEAD
    </div>


    <!--end formset-->

      <div id="below_eligibility_sections">

      <!-- new formset2-->

    <div class="box box-default" id="personal_details_section" style="padding: 10px; display: none;">
        <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
          <h3 class="box-title">PERSONAL DETAILS:- </h3>

          <div class="box-tools pull-right">
          <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>

          </div>
        </div>
        <!-- /.box-header -->
        <div class="box-body">
        <div class="row">
            <div class="col-md-12">
              <div class="col-md-4">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="fname" class="form-control"  data-placeholder="" style="width: 100%;">
            </div>
            </div>


            <div class="col-md-4">
            <div class="form-group">
                <label>Middle Name</label>
                <input type="text" name="mname" class="form-control" data-placeholder="" style="width: 100%;">
            </div>
            </div>


             <div class="col-md-4">
              <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="lname" class="form-control" data-placeholder="" style="width: 100%;">
              </div>
            </div>     
          </div>

         <div class="col-md-12">
            <div class="col-md-4">
            <div class="form-group">
                <label>Date of Birth</label>
                <div class="input-group date">
                  <div class="input-group-addon">
                    <i class="fa fa-calendar"></i>
                  </div>
                  <input type="text" name="dob" id="dob" class="form-control pull-right" placeholder="DD-MM-YYYY">
                </div>
            </div>
            </div>


              <!-- /.form-group -->
              <div class="col-md-4">
            <div class="form-group">
                <label>Gender</label>
                <select class="form-control select" name="gender" style="width: 100%;">
                   <option value="">Please Select</option>
                   <option value="Male" selected="Male">Male</option>
                   <option value="Female">Female</option>
                </select>
            </div>
            </div>


            <div class="col-md-4">
            <div class="form-group">
                <label>Nationality</label>
                <select class="form-control select" name="nation" style="width: 100%;">
                    <option selected="selected" value="INDIAN">Indian</option>
                </select>
            </div>
            </div>

            </div>

            <!-- /.col -->
            <!-- /.col -->
            <div class="col-md-12">
              
                <div class="col-md-4">
          <div class="form-group">
                <label>APAAR ID</label>
                <input type="text" name="appar" id="apaar" class="form-control"  data-placeholder="" style="width: 100%;">
            </div>
             </div>


            <div class="col-md-4">
            <div class="form-group">
                <label>UAN</label>
              <input type="text" name="uan" id="uan" class="form-control"  data-placeholder="" style="width: 100%;">
            </div>
            </div>
              <!-- /.form-group -->
         

              <div class="col-md-4">
              <div class="form-group">
                <label>PAN </label>
                <input type="text" name="pan" id="pan" class="form-control"  data-placeholder="" style="width: 100%;">
            </div>
              </div>

            </div>  <!--col 12 close-->
 
        </div>
          <!-- /.row -->
        </div>
        <!-- /.box-body -->

    </div>


    <!--end formset2-->

    </div>

    <!-- new formset3-->

    <div class="box box-default" id="contact_details_section" style="padding: 10px; display: none;">
=======
      </div>


      <!--end formset-->

      <div id="below_eligibility_sections">

        <!-- new formset2-->

        <div class="box box-default" id="personal_details_section" style="padding: 10px; display: none;">
          <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
            <h3 class="box-title">PERSONAL DETAILS:- </h3>

            <div class="box-tools pull-right">
              <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>

            </div>
          </div>
          <!-- /.box-header -->
          <div class="box-body">
            <div class="row">
              <div class="col-md-12">

                <div class="col-md-4">
                  <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="fname" class="form-control" data-placeholder="" style="width: 100%;" required>
                  </div>
                </div>


                <div class="col-md-4">
                  <div class="form-group">
                    <label>Middle Name</label>
                    <input type="text" name="mname" class="form-control" data-placeholder="" style="width: 100%;">
                  </div>
                </div>


                <div class="col-md-4">
                  <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="lname" class="form-control" data-placeholder="" style="width: 100%;" required>
                  </div>
                </div>

              </div>

              <div class="col-md-12">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Date of Birth</label>
                    <div class="input-group date">
                      <div class="input-group-addon">
                        <i class="fa fa-calendar"></i>
                      </div>
                      <input type="date" name="dob" id="dob" class="form-control pull-right" placeholder="DD-MM-YYYY">
                    </div>
                  </div>
                </div>


                <!-- /.form-group -->
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Gender</label>
                    <select class="form-control select" name="gender" style="width: 100%;">
                      <option value="">Please Select</option>
                      <option value="Male" selected="Male">Male</option>
                      <option value="Female">Female</option>
                    </select>
                  </div>
                </div>


                <div class="col-md-4">
                  <div class="form-group">
                    <label>Nationality</label>
                    <select class="form-control select" name="nation" style="width: 100%;">
                      <option selected="selected" value="INDIAN">Indian</option>
                    </select>
                  </div>
                </div>

              </div>

              <!-- /.col -->
              <!-- /.col -->
              <div class="col-md-12">

                <div class="col-md-4">
                  <div class="form-group">
                    <label>APAAR ID</label>
                    <input type="text" name="appar" id="apaar" class="form-control" data-placeholder="" style="width: 100%;">
                  </div>
                </div>


                <div class="col-md-4">
                  <div class="form-group">
                    <label>UAN</label>
                    <input type="text" name="uan" id="uan" class="form-control" data-placeholder="" style="width: 100%;">
                  </div>
                </div>
                <!-- /.form-group -->


                <div class="col-md-4">
                  <div class="form-group">
                    <label>PAN </label>
                    <input type="text" name="pan" id="pan" class="form-control" data-placeholder="" style="width: 100%;">
                  </div>
                </div>

              </div> <!--col 12 close-->

            </div>
            <!-- /.row -->
          </div>
          <!-- /.box-body -->

        </div>


        <!--end formset2-->

      </div>

      <!-- new formset3-->

      <div class="box box-default" id="contact_details_section" style="padding: 10px; display: none;">
>>>>>>> bc3b82053a8c25ccabcc37e1cf379f56d6dae6a5
        <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
          <h3 class="box-title">CONTACT DETAILS:- </h3>

          <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>

          </div>
        </div>
        <!-- /.box-header -->
        <div class="box-body">
<<<<<<< HEAD
        <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Permanent Address</label>
                <textarea class="form-control" name="permanent" value="12345678bhgj"  data-placeholder="" style="width: 100%;">
                </textarea>
              </div>
              </div>
              <div class="col-md-6">
              <div class="form-group">
                <label>Present Address</label>
                <textarea class="form-control" name="present"  data-placeholder="" style="width: 100%;">
                </textarea>
              </div>
              </div>
            <!-- /.col -->
        </div>

        <div class="row">
          <div class="col-md-4">
              <div class="form-group">
                <label>City</label>
                <input type="text" class="form-control" name="city" pattern="[a-zA-Z]{1,}"  data-placeholder="" style="width: 100%;">
              </div>

          </div>
          <div class="col-md-4">
              <div class="form-group">
                <label>Pincode</label>
                <input type="text" pattern="^\d{6}$" class="form-control" 
                        id="pincode" name="pincode" minlength="6" maxlength="6" oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" placeholder="Pincode" style="width: 100%;"/>
                <!-- <input type="number" class="form-control" name="pincode"  data-placeholder="" style="width: 100%;"> -->
              </div>

          </div>
          <script type= "text/javascript" src = "countries.js"></script>
          <div class="col-md-4">
              <div class="form-group">
                <label>Country</label>
                <select id="country" name ="country"  class="form-control select2" style="width: 100%;">
                 <option selected="selected" value="India">India</option>
                </select>
              </div>
          </div>
          </div>
          <div class="row">
          <div class="col-md-4">
              <div class="form-group">
                <label>State</label>
                <select name ="state" id ="state"  class="form-control select" style="width: 100%;">
                  <option selected="selected" value="Maharashtra">Maharashtra</option>
                </select>
              </div>
             </div>
             <div class="col-md-4">
              <div class="form-group">
                <label>Phone</label>
                <input type="text" pattern="^\d{10}$" class="form-control" 
                        id="phone" name="phone" minlength="10" maxlength="10" oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" placeholder="phone" style="width: 100%;" />
                <!-- <input type="text" class="form-control" name="phone"  data-placeholder="" style="width: 100%;"> -->
              </div>

              </div>
          <div class="col-md-4">
              <div class="form-group">
                <label>Mobile</label>
                <input type="text" pattern="^\d{10}$" class="form-control" 
                        id="mobile" name="mobile" minlength="10" maxlength="10" oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" placeholder="phone" style="width: 100%;" />
                <!-- <input type="text" class="form-control" name="mobile"  data-placeholder="" style="width: 100%;" required="required"> -->
              </div>

          </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" name="email"  data-placeholder="" style="width: 100%;">
              </div>
            </div>
            <div class="col-md-6">
              <div>
                            <img id="user_img"
                                 
                                 style="border:solid; height:100px;
                                 width:100px" />
                        </div>
              <div class="form-group">
                <label>Upload Photo</label>
                <input type="file" class="form-control" name="photo" id="photo" onchange="show(this)"  data-placeholder="" style="width: 100%;">
              </div>
            </div>
          </div>
               <!-- /.row -->
        </div>
        <!-- /.box-body -->

    </div>

    <div class="box box-default" id="upload_documents_section" style="padding: 10px; display: none;">
=======
          <div class="row">
            <div class="col-md-6">
              <div>
                <img id="user_img" style="border:solid; height:100px; width:100px" />
              </div>
              <div class="form-group">
                <label>Upload Photo</label>
                <input type="file" class="form-control" name="photo" id="photo" onchange="show(this)" data-placeholder="" style="width: 100%;">
                <small class="text-muted d-block mt-1">JPG, PNG up to 2MB</small>
              </div>

              <div class="form-group">
                <label>College Email <span style="color: red;">*</span></label>
                <input type="email" name="email" id="college_email" class="form-control"
                  placeholder="example@tcetmumbai.in" style="width: 100%;"
                  pattern="[a-zA-Z0-9._%+\-]+@tcetmumbai\.in"
                  title="Email must end with @tcetmumbai.in" required>
              </div>
            </div>

            <div class="col-md-6">
              <div class="form-group">
                <label>Permanent Address</label>
                <textarea class="form-control" name="permanent" value="12345678bhgj" data-placeholder="" rows="4" style="width: 100%;">
                </textarea>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Present Address</label>
                <textarea class="form-control" name="present" data-placeholder="" rows="4" style="width: 100%;">
                </textarea>
              </div>
            </div>
            <!-- /.col -->
          </div>

          <hr />


          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>City</label>
                <input type="text" class="form-control" name="city" pattern="[a-zA-Z]{1,}" data-placeholder="" placeholder="e.g. Mumbai" style="width: 100%;">
              </div>

            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Pincode</label>
                <input type="text" pattern="^\d{6}$" class="form-control"
                  id="pincode" name="pincode" minlength="6" maxlength="6" oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" placeholder="Pincode" style="width: 100%;" />
                <!-- <input type="number" class="form-control" name="pincode"  data-placeholder="" style="width: 100%;"> -->
              </div>

            </div>
            <script type="text/javascript" src="countries.js"></script>
            <div class="col-md-4">
              <div class="form-group">
                <label>Country</label>
                <select id="country" name="country" class="form-control select2" style="width: 100%;">
                  <option selected="selected" value="India">India</option>
                </select>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label>State</label>
                <select name="state" id="state" class="form-control select" style="width: 100%;">
                  <option selected="selected" value="Maharashtra">Maharashtra</option>
                </select>
              </div>
            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Phone</label>
                <input type="text" pattern="^\d{10}$" class="form-control"
                  id="phone" name="phone" minlength="10" maxlength="10" oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" placeholder="phone" style="width: 100%;" />
                <!-- <input type="text" class="form-control" name="phone"  data-placeholder="" style="width: 100%;"> -->
              </div>

            </div>
            <div class="col-md-4">
              <div class="form-group">
                <label>Mobile</label>
                <input type="text" pattern="^\d{10}$" class="form-control"
                  id="mobile" name="mobile" minlength="10" maxlength="10" oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);" placeholder="phone" style="width: 100%;" />
                <!-- <input type="text" class="form-control" name="mobile"  data-placeholder="" style="width: 100%;" required="required"> -->
              </div>

            </div>
          </div>
          <div class="row">



          </div>
          <!-- /.row -->
        </div>
        <!-- /.box-body -->

      </div>

      <div class="box box-default" id="upload_documents_section" style="padding: 10px; display: none;">
>>>>>>> bc3b82053a8c25ccabcc37e1cf379f56d6dae6a5
        <div class="box-header with-border" style="border-bottom: 2px solid #9C27B0;">
          <h3 class="box-title">Upload Documents:- </h3>
          <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
          </div>
        </div>
        <div class="box-body">
          <style>
            #upload_documents_section .doc-row {
              margin: 0;
              padding: 8px 0;
              border-bottom: 1px dashed #e5e5e5;
            }
<<<<<<< HEAD
            #upload_documents_section .doc-row:last-child {
              border-bottom: 0;
            }
=======

            #upload_documents_section .doc-row:last-child {
              border-bottom: 0;
            }

>>>>>>> bc3b82053a8c25ccabcc37e1cf379f56d6dae6a5
            #upload_documents_section .doc-label {
              display: inline-flex;
              align-items: center;
              gap: 8px;
              margin: 0;
              font-weight: 600;
            }
          </style>

          <div class="row doc-row">
            <div class="col-md-3 col-sm-4">
              <label class="doc-label" for="checkbox1">
                <input type="checkbox" id="checkbox1"> Mark List
              </label>
            </div>
            <div class="col-md-9 col-sm-8" id="autoUpdate" style="display:none;">
              <input type="file" class="form-control" name="mark-list" accept=".pdf,.jpg,.jpeg,.png">
            </div>
          </div>

          <div class="row doc-row">
            <div class="col-md-3 col-sm-4">
              <label class="doc-label" for="checkbox6">
                <input type="checkbox" id="checkbox6"> Affidavit
              </label>
            </div>
            <div class="col-md-9 col-sm-8" id="autoUpdate5" style="display:none;">
              <input type="file" class="form-control" name="affidavit" accept=".pdf,.jpg,.jpeg,.png">
            </div>
          </div>
        </div>
<<<<<<< HEAD
    </div>

    <div class="row" style="margin: 10px 0 0 0;">
      <div class="col-md-12">
        <button type="submit" name="save" class="btn" style="background-color: #009688; color: #FFEB3B; font-size: 20px; padding: 5px 16px; min-width: 100px;">SAVE</button>
        <button type="reset" name="reset" class="btn" style="background-color: #009688; color: #FFEB3B; font-size: 20px; padding: 5px 16px; min-width: 100px;">RESET</button>
      </div>
    </div>

</form>
    <!--end formset3-->

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
<script type="text/javascript">
   function show(input) {

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
<script type="text/javascript">
  $(document).ready(function(){
    $('#checkbox1').on('change', function(){
=======
      </div>

      <div class="row" style="margin: 10px 0 0 0;">
        <div style="margin-top: 20px; text-align: center;">
          <input type="submit" name="save" value="Save Changes" class="btn-submit">
          <input type="reset" name="reset" value="Reset" class="btn-reset">
        </div>
      </div>

    </form>
    <!--end formset3-->

  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<script type="text/javascript">
  function show(input) {

    var validExtensions = ['jpg', 'png', 'jpeg']; //array of valid extensions
    var fileName = input.files[0].name;
    var fileNameExt = fileName.substr(fileName.lastIndexOf('.') + 1);
    if ($.inArray(fileNameExt, validExtensions) == -1) {
      input.type = ''
      input.type = 'file'
      $('#user_img').attr('src', "");
      alert("Only these file types are accepted : " + validExtensions.join(', '));
    } else {
      if (input.files && input.files[0]) {
        var filerdr = new FileReader();
        filerdr.onload = function(e) {
          $('#user_img').attr('src', e.target.result);
        }
        filerdr.readAsDataURL(input.files[0]);
      }
    }
  }
</script>
<script type="text/javascript">
  $(document).ready(function() {
    $('#checkbox1').on('change', function() {
>>>>>>> bc3b82053a8c25ccabcc37e1cf379f56d6dae6a5
      if (this.checked) {
        $('#autoUpdate').stop(true, true).slideDown('fast');
      } else {
        $('#autoUpdate').stop(true, true).slideUp('fast');
      }
    });

<<<<<<< HEAD
    $('#checkbox6').on('change', function(){
=======
    $('#checkbox6').on('change', function() {
>>>>>>> bc3b82053a8c25ccabcc37e1cf379f56d6dae6a5
      if (this.checked) {
        $('#autoUpdate5').stop(true, true).slideDown('fast');
      } else {
        $('#autoUpdate5').stop(true, true).slideUp('fast');
      }
    });
  });
</script>
<<<<<<< HEAD
 <?php include "header/footer.php" ?>


=======
<?php include "header/footer.php" ?>
>>>>>>> bc3b82053a8c25ccabcc37e1cf379f56d6dae6a5
