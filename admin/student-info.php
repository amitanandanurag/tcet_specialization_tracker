<?php include "header/header.php"; ?>  
<script
  src="https://code.jquery.com/jquery-3.3.1.js"
  integrity="sha256-2Kok7MbOyxpgUVvAk/HJ2jigOSYS2auK4Pfzbm7uH60="
  crossorigin="anonymous"></script>
  <div class="content-wrapper">   
    <section class="content-header">
      <h1>
         <i class="fa fa-list"></i> AIDED STUDENT DETAILS
      <button type="button" name="btnExport" id="btnExport" onclick = "window.location.href='student-birth-info.php';" class="btn btn-primary"><i class="fa fa-birthday-cake" aria-hidden="true"></i> BIRTHDAY</button></h1>
    </section>

    <section class="content">
    <div class="row">
    <div class="col-md-12">
     <div class="wrapper2 box box-primary">
     <div class="div2 box-header with-border">
      <table id="#" class="text-center table table-striped table-bordered" width="100%">
          <thead>
          <tr>
              <th bgcolor="#95CF0F">
              <label for="select_all" type="button" class="btn btn-default"> <input type="checkbox" id="select_all" > SELECT ALL </label>
              </th>
            
               <th bgcolor="0FCFA9">
                <button type="button" name="btnExport" id="btnExport" onclick = "window.location.href='student_admission.php';" class="btn btn-primary"><i class="fa fa-plus" aria-hidden="true"></i> NEW ADMISSION</button>
              </th>
           
               <!--<th bgcolor="0FCFA9">
                <button type="button" name="btnExport" id="btnExport" onclick = "window.location.href='student-birth-info.php';" class="btn btn-primary"><i class="fa fa-birthday-cake" aria-hidden="true"></i> BIRTHDAY</button>
              </th>-->
              <th bgcolor="0FCFA9">
              <button type="button" name="btnExport" id="btnExport" onclick="fnExcelReport();" class="btn btn-primary"><i class="fa fa-print" aria-hidden="true"></i> Data In Excel</button>
              </th>
        
              <th bgcolor="#95CF0F">
              <select class="form-control" style="width: 100%;" id="select_class" name="select_class" required>
                <option value="" >Class</option>
                <?php   
                $result = $db_handle->query("SELECT class_id AS id, class_name AS class FROM `st_class_master`");
                while($row = $result->fetch_assoc()) {
                    $class_id = $row['id'];
                    $class_name = $row['class'];
                ?>
                    <option value="<?php echo $class_id;?>"><?php echo $class_name; ?></option>
                <?php }?>
              </select>
              </th>

              <th bgcolor="#95CF0F">
                <select class="form-control" style="width: 150%;" id="select_section" name="select_section" required>
                  <option value="" disabled selected>Section</option>
                  <?php               
                  $result = $db_handle->query("SELECT * FROM `st_section_master`");
                  while ($row = $result->fetch_assoc()) {
                      $section_id = $row['id'];
                      $sections = $row['sections'];
                  ?>
                      <option value="<?php echo $section_id; ?>"><?php echo $sections; ?></option>
                  <?php } ?>              
              </select>
              </th>

             <th bgcolor="#95CF0F">
              <select class="form-control" style="width: 150%;" id="select_session" name="select_session" required>
                  <option value="" disabled selected>Select Session</option>
                  <?php               
                  $result = $db_handle->query("SELECT * FROM `session_master`");
                  while ($row = $result->fetch_assoc()) {
                      $session_id  = $row['session_id']; // Remove the space after 'session_id'
                      $session = $row['session'];
                  ?>
                  <option value="<?php echo $session_id; ?>" <?php if ($session_id == 6) echo "selected"; ?>><?php echo $session; ?></option>
                  <?php } ?>              
              </select>
              </th>
              <th bgcolor="#95CF0F"><button type="button" name="search" id="search" class="btn btn-primary"> <i class="fa fa-arrow-circle-right fa-lg"></i> SUBMIT</button></th>
      </tr>
    </thead>
  </table>


<div class="text-center table table-striped table-bordered" style="overflow-x:auto;">
 <table id="myTable" class="text-center table table-striped table-bordered" width="100%">
  <thead>
  <tr>    
    <th style="background-color: #0DF387" data-orderable="false"><input type="checkbox" id="select_all" ></th>
          <th style="background-color: #0DF387">SR. NO</th>
          <th style="background-color: #0DF387">MESSAGE</th>
          <th style="background-color: #0DF387">Photo</th>
          <th style="background-color: #0DF387">Reg. No</th>
          <th style="background-color: #0DF387">Name</th>
          <th style="background-color: #0DF387">Class</th>
          <th style="background-color: #0DF387">section</th>
          <th style="background-color: #0DF387">Department</th>
          <th style="background-color: #0DF387">Specialization</th>
          <th style="background-color: #0DF387">Specialization Subject</th>
          <th style="background-color: #0DF387">CGPA</th>
          <th style="background-color: #0DF387">Mobile No</th>
          <th style="background-color: #0DF387">Academic Year</th>
          <th style="background-color: #0DF387">Roll No</th>
          <th style="background-color: #0DF387">Joining Date</th>
          <th style="background-color: #0DF387">DOB</th>
          <th style="background-color: #0DF387">Gender</th>
          <th style="background-color: #0DF387">Email</th>
          <th style="background-color: #0DF387">City</th>
          <th style="background-color: #0DF387">Address</th>
         <!-- <th style="background-color: #F97161">Option</th>-->
          <th style="background-color: #F97161">View</th>
          <th style="background-color: #F97161">Edit</th>
          <th style="background-color: #F97161">Remove</th>
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

                           <!-- content will be load here -->
                           <div id="dynamic-content"></div>

                        </div>
                        <div class="modal-footer">
                              <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>

                 </div>
              </div>
       </div><!-- /.modal -->

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

                           <!-- content will be load here -->
                           <div id="edit-dynamic-content"></div>

                        </div>
                        <div class="modal-footer">
                              <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>

                 </div>
              </div>
       </div><!-- /.edit-modal -->


<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>

<!-- Include jQuery and SweetAlert CDN -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script type="text/javascript" language="javascript">
function delete_user(id,table) {
    var form_data = new FormData();
    console.log(id);
    form_data.append("id", id);
    form_data.append("table", table);
    // Using SweetAlert2 for the confirmation dialog
    Swal.fire({
        title: "Are you sure?",
        text: "Once deleted, Students will be moved to left students!",
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
                dataType: "json",
                cache: false,
                contentType: false,
                processData: false,
                data: form_data,
                success: function (data) {
                    Swal.fire(
                        'Deleted!',
                        'Student details have been moved successfully!',
                        'success'
                    ).then(() => {
                        // Refresh the page after deletion
                        window.location.href = window.location.href;
                    });
                },
                error: function (error) {
                    Swal.fire(
                        'Error!',
                        'There was a problem deleting the student.',
                        'error'
                    );
                }
            });
        } else {
            Swal.fire(
                'Cancelled',
                'Delete operation has been cancelled!',
                'info'
            );
        }
    });
}
</script>


<script>
$(document).ready(function(){

  $(document).on('click', '#student_view', function(e){

    e.preventDefault();

    var uid = $(this).data('id');   // it will get id of clicked row

    $('#dynamic-content').html(''); // leave it blank before ajax call
    $('#modal-loader').show();      // load ajax loader
    $('#view').modal('show');       // show modal

  $.ajax({
      url: 'student_view.php',
      type: 'POST',
      data: 'id='+uid,
      dataType: 'html'
    })
    .done(function(data){
      console.log(data);
      $('#dynamic-content').html('');
      $('#dynamic-content').html(data); // load response
      $('#modal-loader').hide();      // hide ajax loader
    })
    .fail(function(){
      $('#dynamic-content').html('<i class="glyphicon glyphicon-info-sign"></i> Something went wrong, Please try again...');
      $('#modal-loader').hide();
    });

  });

  $(document).on('click', '#student_edit', function(e){

    e.preventDefault();

    var uid = $(this).data('id');   // it will get id of clicked row

    $('#edit-dynamic-content').html(''); // leave it blank before ajax call
    $('#edit-modal-loader').show();      // load ajax loader
    $('#edit').modal('show');            // show modal

    $.ajax({
      url: 'student-edit.php',
      type: 'POST',
      data: 'id='+uid,
      dataType: 'html'
    })
    .done(function(data){
      console.log(data);
      $('#edit-dynamic-content').html('');
      $('#edit-dynamic-content').html(data); // load response
      $('#edit-modal-loader').hide();      // hide ajax loader
    })
    .fail(function(){
      $('#edit-dynamic-content').html('<i class="glyphicon glyphicon-info-sign"></i> Something went wrong, Please try again...');
      $('#edit-modal-loader').hide();
    });

  });

});

</script>




<script type="text/javascript">

function send_sms()
   {
       if( $('.selectRow:checked').length > 0 )
         {
          if( $('.selectRow:checked').length == 0 )
          {
            $("#sms_response").html("<div class='alert alert-danger text-center alert-dismissible fade in'><a href='#' class='close' data-dismiss='alert' aria-label='close'><i class='fa fa-times'></i></a> <strong> Please Select less than 10 Associates </strong></div>");
          }
          else
          {
           var ids = [];
           var sms_content = $("#sms_content").val();
           $('.selectRow').each(function()
            {
              if($(this).is(':checked'))
              {
                ids.push($(this).val());
              }
           });
          var ids_string = ids;  // array to string conversion
          var j=0;
             
          (function theLoop (i) {    
         setTimeout(function () {
        var idsint=ids_string[j];
            j++;


            $.ajax({
            type: "POST",
            url: "send_sms.php",
            data: {data_ids:idsint,sms_content:sms_content,count:j,lenght:ids_string.length},
            beforeSend: function()
           {
            $("#sms_response").html("<div class='progress'><div class='progress-bar active progress-bar-success progress-bar-striped' role='progressbar' aria-valuenow='100' aria-valuemin='0' aria-valuemax='100' style='width:100%'>Processing</div>");
           },
           success: function(response)
           {
            $("#sms_response").html(response);
           }
          });


       if (--i) {          // If i > 0, keep going
      theLoop(i);       // Call the loop again, and pass it the current value of i
    }
  }, 3000);
})(ids_string.length);
          
        }
      }
       else
      {
           $("#sms_response").html("<div class='alert alert-danger text-center alert-dismissible fade in'><a href='#' class='close' data-dismiss='alert' aria-label='close'><i class='fa fa-times'></i></a> <strong> Please Select at least one Associate </strong></div>");
      }
   }


    $("#select_all").click(function(){
                    var i = 0;
                    var status = this.checked;
                    $(".selectRow").each( function()
                    {
                      if(i < 3000)
                        {
                        $(this).prop("checked",status);
                        }
                        i++;
                    });

         });

  
</script>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
<style>
  #myTable th,
  #myTable td {
    vertical-align: middle;
    white-space: nowrap;
    text-align: center;
  }

  #myTable td:nth-child(6),
  #myTable td:nth-child(21) {
    text-align: left;
    white-space: normal;
  }
</style>
  
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
  
<script type="text/javascript" language="javascript">

 

      $(document).ready(function() {

        fetch_data('no');
        function fetch_data(select_class='',select_section='',select_session='')
        {
     // $.fn.dataTable.ext.errMode = 'none';


        var dataTable = $('#myTable').DataTable( {
         // "dom": 'Bfrtip',
         // "paging": true,
         // "searching": true,
         // "select": true,
         // "aaSorting" : [[0, 'asc']],
         "lengthMenu": [
            [ 15, 25, 50, 100, 500,1000,2000,3000],
            [ '15', '25', '50', '100', '500','1000','2000','3000']
        ],
        "autoWidth": false,
        "scrollX": true,


        "columnDefs": [
                         {
                          "orderable": false,
                          "targets": [0, 2, 3, 21, 22, 23]
                         },
                         {
                          "className": "text-left",
                          "targets": [5, 20]
                         },
                         {
                          "className": "text-center",
                          "targets": "_all"
                         } 
                        ],
        "buttons": [
           {
            extend: 'colvis',
            text: "Columns"
           },
           {
            extend: 'pageLength',
            text: 'Show'
           },
             {
                extend: 'excel',
                text: "Excel",
                title: "",
                exportOptions:
                {
                  columns: [1, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20]
                },
            },
             {
                extend: 'csv',
                text: "CSV",
                title: "",
                exportOptions: {
                   columns: [1, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20]
                }
            },
             {
                extend: 'pdf',
                text: "PDF",
                title: "",
                orientation: 'landscape',
                pageSize: 'LEGAL',
                exportOptions: {
                  columns: [1, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20]
                }
            },
             {
                extend: 'print',
                title: "",
                text: "print",
                exportOptions: {
                  columns: [1, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20]
                }


                
            },
        ], 
          "processing": true,
          "serverSide": true,
          "language": {
                        "processing": "<span style='color:#8b0000;font-size:20px;back'> Processing data.. <i class='fa fa-spinner fa-spin'></i> </span>",
                        "search": '',
                        "searchPlaceholder": "search",
                        "paginate": {
                        "previous": '<i class="fa fa-angle-double-left"></i> Previous',
                        "next": 'Next <i class="fa fa-angle-double-right"></i>'
            }
                        },  
          "ajax":{  
            url :"student_info_ajax.php",  
            type: "POST",
           // data:'select_month='+$("#select_month").val(),
            data: {select_class: select_class,select_section: select_section,select_session: select_session},
             //data: { 
             // select_month:select_month
             //   },
  
              
            error: function(response){
              console.log(response);

             
              $(".all_admin_ajax-error").html("");
              $("#all_admin_ajax").append('<tbody class="all_admin_ajax-error"><tr><th colspan="3">No data found in the server </th></tr></tbody>');
              $("#all_admin_ajax_processing").css("display","none");
            }
          }
        });
        $('input[type=search]').addClass('form-control');
        $('#all_admin_length').addClass('hidden');
        $('.sidebar-mini').addClass('sidebar-collapse');


         $('#search').click(function(){
        var select_class = $('#select_class').val();
        var select_section = $('#select_section').val();
        var select_session = $('#select_session').val();
        
     
        if(select_class != null || select_section != null || select_session != null)
        {

        $('#myTable').DataTable().destroy();

         fetch_data(select_class,select_section,select_session);
         $('input[type=search]').addClass('form-control');
        }
        else
        {
         alert("Select All To See The Result");
        }

        });
      }
    });

 </script>


<script type="text/javascript">
function fnExcelReport() {

    var table = document.getElementById("myTable");

    // Columns to exclude (0-based index)
    var excludeCols = [0, 2, 3, 21, 22, 23];

    var colWidths = [];
    var tableHTML = "<table border='1' style='border-collapse:collapse;'>";

    /* ===============================
       FIRST PASS – Calculate Widths
    =============================== */
    for (var i = 0; i < table.rows.length; i++) {
        var row = table.rows[i];
        for (var j = 0; j < row.cells.length; j++) {

            if (excludeCols.includes(j)) continue;

            var cellText = row.cells[j].innerText.trim();
            var width = Math.max(cellText.length * 7, 80); // min width 80px

            colWidths[j] = Math.max(colWidths[j] || 0, width);
        }
    }

    /* ===============================
       SECOND PASS – Build Excel Table
    =============================== */
    for (var i = 0; i < table.rows.length; i++) {
        tableHTML += "<tr>";
        var row = table.rows[i];

        for (var j = 0; j < row.cells.length; j++) {

            if (excludeCols.includes(j)) continue;

            var cell = row.cells[j];
            var tag = (i === 0) ? "th" : "td";

            tableHTML += `<${tag} style="
                width:${colWidths[j]}px;
                padding:5px;
                text-align:left;
                vertical-align:middle;
                white-space:wrap;
            ">${cell.innerText}</${tag}>`;
        }
        tableHTML += "</tr>";
    }

    tableHTML += "</table>";

    /* ===============================
       CLEANUP
    =============================== */
    tableHTML = tableHTML.replace(/<a[^>]*>|<\/a>/gi, "");
    tableHTML = tableHTML.replace(/<img[^>]*>/gi, "");
    tableHTML = tableHTML.replace(/<input[^>]*>/gi, "");

    /* ===============================
       DOWNLOAD AS EXCEL
    =============================== */
    var blob = new Blob(
        ['\ufeff', tableHTML],
        { type: 'application/vnd.ms-excel;charset=utf-8;' }
    );

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
      <form method="post"  onsubmit="send_sms(); return false;">
      <div class="modal-body">

       <div class="form-horizontal">
       <textarea class="form-control" required="required" maxlength="160" placeholder="Enter your Message (maximumn words length is: 160)" id="sms_content"></textarea>
       </div>

        </div>
        <div class="modal-footer ">
        <button type="reset" class="btn btn-default"><span class="fa fa-times-circle"></span> Reset </button>
        <button type="submit" class="btn btn-primary" ><span class="fa fa-check-circle"></span> Send </button>
        </div>
        <div id="sms_response" class="modal-footer text-center"></div>
        </form>
        </div>
    </div>
  </div>


<?php include "header/footer.php"; ?>  