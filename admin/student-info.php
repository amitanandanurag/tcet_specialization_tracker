<?php include "header/header.php"; ?>

<div class="content-wrapper">
  <section class="content-header">
    <h1>
      <i class="fa fa-list"></i> AIDED STUDENT DETAILS
    </h1>
  </section>

  <section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="wrapper2 box box-primary">
          <div class="div2 box-header with-border">
            <div class="row" style="margin-bottom: 20px;">
              <div class="col-md-1">
                <label for="select_all_main" class="btn btn-default" id="select_all_btn" style="width: 100%;">
                  <input type="checkbox" id="select_all_main"> ALL
                </label>
              </div>

              <div class="col-md-2">
                <button type="button" onclick="window.location.href='student_admission.php';" class="btn btn-primary btn-block">
                  <i class="fa fa-plus"></i> NEW ADMISSION
                </button>
              </div>

              <div class="col-md-2">
                <button type="button" onclick="fnExcelReport();" class="btn btn-success btn-block">
                  <i class="fa fa-print"></i> EXCEL
                </button>
              </div>
              <div class="col-md-2">  
              <select class="form-control" id="select_class" style="border:2px solid #0FCFA9; border-radius:6px;">
                    <option value="" hidden>Class</option>
                    <?php
                    $result = $db_handle->query("SELECT * FROM st_class_master");
                    while ($row = $result->fetch_assoc()) {
                      ?>
                      <option value="<?php echo $row['class_id']; ?>">
                        <?php echo $row['class_name']; ?>
                      </option>
                    <?php } ?>
                  </select>
                  </div>

              <div class="col-md-2">
                <select class="form-control" id="select_section" name="select_section">
                  <option value="">Select Division</option>
                  <?php
                  $result = $db_handle->query("SELECT * FROM `st_section_master`");
                  while ($row = $result->fetch_assoc()) {
                    $section_id = $row['id'];
                    $sections = $row['sections'];
                  ?>
                    <option value="<?php echo $section_id; ?>"><?php echo $sections; ?></option>
                  <?php } ?>
                </select>
              </div>

              <div class="col-md-2">
                <select class="form-control" id="select_session" name="select_session">
                  <option value="">Select Session/Academic Year</option>
                  <?php
                  // Get unique academic years from student master
                  $sessionQuery = "SELECT DISTINCT academic_year FROM `st_student_master` WHERE academic_year IS NOT NULL AND academic_year != '' ORDER BY academic_year DESC";
                  $sessionResult = $db_handle->query($sessionQuery);
                  if ($sessionResult) {
                      while ($sessionRow = $sessionResult->fetch_assoc()) {
                          $academic_year = $sessionRow['academic_year'];
                          ?>
                          <option value="<?php echo htmlspecialchars($academic_year); ?>"><?php echo htmlspecialchars($academic_year); ?></option>
                          <?php 
                      }
                  }
                  ?>
                </select>
              </div>


              <div class="col-md-1">
                <button type="button" id="search" class="btn btn-primary btn-block">
                  <i class="fa fa-arrow-circle-right"></i>
                </button>
              </div>
            </div>

            <div class="text-center table table-striped table-bordered" style="overflow-x:auto;">
              <table id="myTable" class="text-center table table-striped table-bordered" width="100%">
                <thead>
                  <tr>
                    <th style="background-color: #423cbc; color: white; padding: 16px" data-orderable="false"><input type="checkbox" id="select_all_header"></th>
                    <th style="background-color: #423cbc; color: white; padding: 16px">SR. NO</th>
                    <th style="background-color: #423cbc; color: white; padding: 16px">MESSAGE</th>
                    <th style="background-color: #423cbc; color: white; padding: 16px">Reg. No</th>
                    <th style="background-color: #423cbc; color: white; padding: 16px">Academic year</th>
                    <th style="background-color: #423cbc; color: white; padding: 16px">Name</th>
                    <th style="background-color: #423cbc; color: white; padding: 16px">Class</th>
                    <th style="background-color: #423cbc; color: white; padding: 16px">Division</th>
                    <th style="background-color: #423cbc; color: white; padding: 16px">Department</th>
                    <th style="background-color: #423cbc; color: white; padding: 16px">Specialization</th>
                    <th style="background-color: #423cbc; color: white; padding: 16px">Specialization Subject</th>
                    <th style="background-color: #423cbc; color: white; padding: 16px">CGPA</th>
                    <th style="background-color: #423cbc; color: white; padding: 16px">Mobile No</th>
                    <th style="background-color: #423cbc; color: white; padding: 16px">Session</th>
                    <th style="background-color: #423cbc; color: white; padding: 16px">Roll No</th>
                    <th style="background-color: #423cbc; color: white; padding: 16px">Email</th>
                    <th style="background-color: #F97161; padding: 16px">View</th>
                    <th style="background-color: #F97161; padding: 16px">Edit</th>
                    <th style="background-color: #F97161; padding: 16px">Remove</th>
                  </tr>
                </thead>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Modals -->
<div id="view" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h4 class="modal-title">
          <i class="glyphicon glyphicon-user"></i> Student Details
        </h4>
      </div>
      <div class="modal-body">
        <div id="modal-loader" style="display: none; text-align: center;">
          <img src="ajax-loader.gif">
        </div>
        <div id="dynamic-content"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="edit" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true" style="display: none;">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h4 class="modal-title">
          <i class="fa fa-pencil"></i> Edit Student Details
        </h4>
      </div>
      <div class="modal-body">
        <div id="edit-modal-loader" style="display: none; text-align: center;">
          <img src="ajax-loader.gif">
        </div>
        <div id="edit-dynamic-content"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>

<style>
  #myTable th,
  #myTable td {
    vertical-align: middle;
    white-space: nowrap;
    text-align: center;
  }

  #myTable td:nth-child(5) {
    text-align: left;
    white-space: normal;
    min-width: 150px;
  }

  .select-all-active {
    background-color: #5cb85c !important;
    color: white !important;
  }
</style>

<script type="text/javascript" language="javascript">
  function delete_user(id, table) {
    Swal.fire({
      title: "Are you sure?",
      text: "Once deleted, Student will be moved to left students!",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Yes, delete it!",
      cancelButtonText: "No, cancel!",
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: 'student_delete.php',
          type: "POST",
          data: {
            id: id,
            table: table
          },
          dataType: "json",
          success: function(data) {
            Swal.fire('Deleted!', 'Student details have been moved successfully!', 'success')
              .then(() => {
                $('#myTable').DataTable().ajax.reload();
              });
          },
          error: function(error) {
            Swal.fire('Error!', 'There was a problem deleting the student.', 'error');
          }
        });
      }
    });
  }

  $(document).ready(function() {
    // Initialize DataTable
    var dataTable = $('#myTable').DataTable({
      "processing": true,
      "serverSide": true,
      "ajax": {
        "url": "student_info_ajax.php",
        "type": "POST",
        "data": function(d) {
          d.select_class = $('#select_class').val();
          d.select_section = $('#select_section').val();
          d.select_session = $('#select_session').val();
          d.search_value = d.search.value;
        }
      },
      "lengthMenu": [[15, 25, 50, 100, 500], ['15', '25', '50', '100', '500']],
      "pageLength": 15,
      "autoWidth": false,
      "scrollX": true,
      "columnDefs": [
        { "orderable": false, "targets": [0, 2, 15, 16, 17] },
        { "className": "text-left", "targets": [4] },
        { "className": "text-center", "targets": "_all" }
      ],
      "language": {
        "processing": "<span style='color:#8b0000;font-size:20px;'> Processing data.. <i class='fa fa-spinner fa-spin'></i> </span>",
        "search": "Search:",
        "searchPlaceholder": "Search by Reg No, Name, Roll No, Email, Mobile...",
        "paginate": {
          "previous": '<i class="fa fa-angle-double-left"></i> Previous',
          "next": 'Next <i class="fa fa-angle-double-right"></i>'
        }
      }
    });

    // Style search box
    $('div.dataTables_filter input').addClass('form-control');
    $('div.dataTables_filter input').attr('placeholder', 'Search by Reg No, Name, Roll No, Email, Mobile...');

    // Search button click
    $('#search').click(function() {
      dataTable.ajax.reload();
    });

    // Filter changes
    $('#select_class, #select_section, #select_session').change(function() {
      dataTable.ajax.reload();
    });

    // Select All functionality
    $('#select_all_btn').click(function() {
      var isChecked = $(this).hasClass('active');
      $('.selectRow').each(function() {
        $(this).prop('checked', !isChecked);
      });
      $(this).toggleClass('active');
      if ($(this).hasClass('active')) {
        $(this).addClass('select-all-active');
      } else {
        $(this).removeClass('select-all-active');
      }
    });

    $('#select_all_header').click(function() {
      var status = this.checked;
      $('.selectRow').each(function() {
        $(this).prop('checked', status);
      });
      if (status) {
        $('#select_all_btn').addClass('active select-all-active');
      } else {
        $('#select_all_btn').removeClass('active select-all-active');
      }
    });

    $('#select_all_main, #select_all_header').click(function() {
      var isChecked = $(this).prop('checked');
      $('#select_all_main, #select_all_header').prop('checked', isChecked);
      $('.selectRow').each(function() {
        $(this).prop('checked', isChecked);
      });
      if (isChecked) {
        $('#select_all_btn').addClass('active select-all-active');
      } else {
        $('#select_all_btn').removeClass('active select-all-active');
      }
    });

    // Custom search with debounce
    $('div.dataTables_filter input').unbind().bind('keyup', function() {
      clearTimeout($.data(this, 'timer'));
      $(this).data('timer', setTimeout(() => {
        dataTable.search(this.value).draw();
      }, 300));
    });
  });

  // View and Edit modals
  $(document).on('click', '#student_view', function(e) {
    e.preventDefault();
    var uid = $(this).data('id');
    $('#dynamic-content').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i></div>');
    $('#view').modal('show');
    $.ajax({
      url: 'student_view.php',
      type: 'POST',
      data: 'id=' + uid,
      dataType: 'html'
    }).done(function(data) {
      $('#dynamic-content').html(data);
    }).fail(function() {
      $('#dynamic-content').html('<div class="alert alert-danger">Something went wrong, Please try again...</div>');
    });
  });

  $(document).on('click', '#student_edit', function(e) {
    e.preventDefault();
    var uid = $(this).data('id');
    $('#edit-dynamic-content').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i></div>');
    $('#edit').modal('show');
    $.ajax({
      url: 'student-edit.php',
      type: 'POST',
      data: 'id=' + uid,
      dataType: 'html'
    }).done(function(data) {
      $('#edit-dynamic-content').html(data);
    }).fail(function() {
      $('#edit-dynamic-content').html('<div class="alert alert-danger">Something went wrong, Please try again...</div>');
    });
  });

  // Excel Export
  function fnExcelReport() {
    var table = document.getElementById("myTable");
    var excludeCols = [0, 2, 15, 16, 17];
    var tableHTML = "<table border='1' style='border-collapse:collapse;'>";

    for (var i = 0; i < table.rows.length; i++) {
      tableHTML += "<tr>";
      var row = table.rows[i];
      for (var j = 0; j < row.cells.length; j++) {
        if (excludeCols.includes(j)) continue;
        var cell = row.cells[j];
        var tag = (i === 0) ? "th" : "td";
        var cellText = cell.innerText.trim();
        tableHTML += `<${tag} style="padding:5px;text-align:left;vertical-align:middle;">${cellText}</${tag}>`;
      }
      tableHTML += "</tr>";
    }
    tableHTML += "</table>";

    tableHTML = tableHTML.replace(/<a[^>]*>|<\/a>/gi, "");
    tableHTML = tableHTML.replace(/<img[^>]*>/gi, "");
    tableHTML = tableHTML.replace(/<input[^>]*>/gi, "");

    var blob = new Blob(['\ufeff', tableHTML], {
      type: 'application/vnd.ms-excel;charset=utf-8;'
    });
    var url = URL.createObjectURL(blob);
    var link = document.createElement("a");
    link.href = url;
    link.download = "student_details.xls";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  }
</script>

<div class="modal fade" id="send_sms" tabindex="-1" role="dialog" aria-labelledby="edit" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h4 class="modal-title custom_align" id="Heading"> <i class="fa fa-send"></i> Send SMS </h4>
      </div>
      <form method="post" onsubmit="send_sms(); return false;">
        <div class="modal-body">
          <div class="form-horizontal">
            <textarea class="form-control" required="required" maxlength="160" placeholder="Enter your Message (maximum words length is: 160)" id="sms_content"></textarea>
          </div>
        </div>
        <div class="modal-footer ">
          <button type="reset" class="btn btn-default"><span class="fa fa-times-circle"></span> Reset </button>
          <button type="submit" class="btn btn-primary"><span class="fa fa-check-circle"></span> Send </button>
        </div>
        <div id="sms_response" class="modal-footer text-center"></div>
      </form>
    </div>
  </div>
</div>
</div>

<?php include "header/footer.php"; ?>
