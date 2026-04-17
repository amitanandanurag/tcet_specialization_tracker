<?php include "header/header.php"; ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
   <section class="content-header">
      
      <ol class="breadcrumb"> 
        <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
        <li class="active">Class</li>
      </ol>
    </section>
 <section class="content" style="margin-top: 30px">
  <div class="box" style=" padding: 10px;">

  <h2><i class="fa fa-cog" aria-hidden="true"></i> Settings</h2>

  <ul class="nav nav-tabs">
    <li class="active">
      <a data-toggle="tab" href="#home"><i class="fa fa-bars" aria-hidden="true"></i> Class Lists</a>
    </li>

    <li><a data-toggle="tab" href="#menu1"><i class="fa fa-plus-circle"></i> Add Class</a></li>

     <li><a data-toggle="tab" href="#listsection"><i class="fa fa-bars" aria-hidden="true"></i> Section Lists</a></li>

    <li><a data-toggle="tab" href="#addsection"><i class="fa fa-plus-circle"></i> Add Section</a></li>
  </ul> 

  <div class="tab-content">

   <!--To List class names-->
    <div id="home" class="tab-pane fade in active">
     <div class="box-body" style="margin-top: 30px;">
          <table id="all_admin" class="text-center table table-striped table-bordered" width="100%">
              <thead>
               <tr>
                <th>ID</th>
                <th>Class</th>
                <th>Edit</th>
                <th>Delete</th>
              </tr>
            </thead>
          </table>
     </div>     
    </div>
 

   <!--To add class-->

    <div id="menu1" class="tab-pane fade">
    <div class="box-body" style="margin-top: 30px;">
      <form action="" method="POST" class="form-horizontal" role="form" style="align-content: center;">
        <div class="form-group">
          <label class="col-xs-4 control-label" for="name"> Class Name </label>
          <div class="col-xs-4">
              <input type="text" class="form-control" 
              id="class_name" name="class_name" placeholder="Give Class Name" required="required"/>
          </div>
        </div>
                  
            <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10" align="center">
              <button type="submit" class="btn btn-success" name="add">Add Class</button>
            </div>
          </div>
      </form>
      </div>
    </div>

   <!--To List Section Names-->

    <div id="listsection" class="tab-pane fade">
     <div class="box-body" style="margin-top: 30px;">
             <table id="all_section" class="text-center table table-striped table-bordered" width="100%">
              <thead>
               <tr>
                <th>ID</th>
                <th>Sections Name</th>           
                <th>Edit</th>
                <th>Delete</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>

  <!--To add Section Name -->

  <div id="addsection" class="tab-pane fade">
    <div class="box-body" style="margin-top: 30px;">
      <form action="" method="POST" class="form-horizontal" role="form" style="align-content: center;">
        <div class="form-group">
        <label class="col-xs-4 control-label"
                  for="name">Sections Name</label>
        <div class="col-xs-4">
            <input type="text" class="form-control" 
            id="section_name" name="section_name" placeholder="Give Sections Name" required="required" onkeyup="check_subject();" />
        </div>
       </div>
                  
         <div class="form-group">
          <div class="col-sm-offset-2 col-sm-10" align="center">
            <button type="submit" class="btn btn-success" name="add_section">Add Sections</button>
          </div>
        </div>
      </form>
      </div>
    </div>

   </div>
</div>
</section> 

<!-- start add class php code -->

<?php  

if(isset($_POST['add']))
{
  
  $class_name = $_POST['class_name']; 

    $result=$db_handle->numRows("select * from class_master where class='$class_name'");

  if ($result>=1) {
     echo '<script type="text/javascript">swal("Oops...", "Class Name Already Exist..!", "error");</script>';
  }


else{
$sql = "INSERT INTO class_master(class) VALUES ('$class_name')";  // Insert query

if($db_handle->conn->query($sql) === TRUE)
{
    echo '<script type="text/javascript">swal({title: "ADDED", text: "Class Added Successfully", type: 
    "success"}).then(function()
       { 
        window.location.href ="class_crud_new.php";
       }
    );</script>';
}
else
{
echo("Error description: " . mysqli_error($db_handle->conn));
}

}

$db_handle->conn->close();
}

?>

<?php  

/*if(isset($_POST['add_section']))
{
  
  $section_name = $_POST['section_name']; 
  $result=$db_handle->numRows("select * from section_master where sections='$section_name'");
  if ($result>=1) {
     echo '<script type="text/javascript">swal("Oops...", "Subject Name Already Exist..!", "error");</script>';
  }

else{
$sql = "INSERT INTO section_master(sections) VALUES ('$section_name')";  // Insert query

if($db_handle->conn->query($sql) === TRUE)
{

  echo '<script type="text/javascript">swal({title: "ADDED", text: "Section Added Successfully", type: 
  "success"}).then(function()
     { 
      window.location.href ="class_crud_new.php";
     }
  );</script>';
}
else
{
echo("Error description: " . mysqli_error($db_handle->conn));
}

}

 $db_handle->conn->close();
}*/

?>

<!--Start Edit Modal -->

<div id="edit" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
             <div class="modal-dialog"> 
                  <div class="modal-content"> 
                  
                       <div class="modal-header"> 
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button> 
                            <h4 class="modal-title">
                              <i class="glyphicon glyphicon-user"></i> Edit Class Details
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
    
    </div> 

 
<!--script src="../assets/jquery-1.12.4.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script-->
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>

<script>
 $(document).ready(function(){
  
  $(document).on('click', '#class_edit', function(e){
    
    e.preventDefault();
     
    var uid = $(this).data('id');   // it will get id of clicked row
    
    $('#dynamic-content').html(''); // leave it blank before ajax call
    $('#modal-loader').show();      // load ajax loader
    
    $.ajax({
      url: 'class_edit_new.php',  
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
  
});


/*$(document).ready(function(){
  
  $(document).on('click', '#section_edit_new', function(e){
    
    e.preventDefault();
    
    var uid = $(this).data('id');   // it will get id of clicked row
    
    $('#dynamic-content').html(''); // leave it blank before ajax call
    $('#modal-loader').show();      // load ajax loader
    
    $.ajax({
      url: 'section_edit_new.php', 
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
  
});*/

</script>


<script type="text/javascript" language="javascript">

  /*  function delete_class(id)
    {
      var form_data = new FormData();
     
     form_data.append("id", id)
    swal({
    title: "Are you sure?",
    text: "Once deleted, you will not be able to recover this Class details!",
    icon: "warning",
    buttons: true,
    dangerMode: true,
    })
    .then((willDelete) => {
    if (willDelete) {
    $.ajax({
        url: 'delete_class.php',
        type: 'post',
        dataType: "json",
        cache: false,
        contentType: false, 
        processData: false,
        data: form_data,
        success: function (data)
        {


            swal('Class has been deleted!')
           .then((value) => {
            location.reload();
            });
        }
    });
  }
  else
  {
    swal("Delete Operation has been cancel!");
  }
});

}*/


      $(document).ready(function() {
        var dataTable = $('#all_admin').DataTable( {
         "dom": 'Bfrtip',
         "paging": true,
         "searching": true,
         "select": true,
         "aaSorting" : [[0, 'asc']],
         "lengthMenu": [
            [ 10, 25, 50, 100, 500],
            [ '10', '25', '50', '100', '500']
        ],
        "columnDefs": [
                         {
                          "orderable": false, "targets": 3
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
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                },
            },
             {
                extend: 'csv',
                text: "CSV",
                title: "",
                exportOptions: {
                   columns: [ 0, 1, 2, 3, 4, 5 ]
                }
            },
             {
                extend: 'pdf',
                text: "PDF",
                title: "",
                orientation: 'landscape',
                pageSize: 'LEGAL',
                exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }
            },
             {
                extend: 'print',
                title: "",
                text: "print",
                exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
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
            url :"all_class_ajax.php",
            type: "post",
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
      });




  /* function delete_section(id)
    {
      var form_data = new FormData();
     
     form_data.append("id", id)
    swal({
    title: "Are you sure?",
    text: "Once deleted, you will not be able to recover this Section details!",
    icon: "warning",
    buttons: true,
    dangerMode: true,
    })
    .then((willDelete) => {
    if (willDelete) {
    $.ajax({
        url: 'delete_section_new.php',
        type: 'post',
        dataType: "json",
        cache: false,
        contentType: false,
        processData: false,
        data: form_data,
        success: function (data)
        {


            swal('Section has been deleted!')
           .then((value) => {
             location.reload();
            });
        }
    });
  }
  else
  {
    swal("Delete Operation has been cancel!");
  }
});

}

      $(document).ready(function() {
        var dataTable = $('#all_section').DataTable( {
         "dom": 'Bfrtip',
         "paging": true,
         "searching": true,
         "select": true,
         "aaSorting" : [[0, 'asc']],
         "lengthMenu": [
            [ 10, 25, 50, 100, 500],
            [ '10', '25', '50', '100', '500']
        ],
        "columnDefs": [
                         {
                          "orderable": false, "targets": 4
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
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                },
            },
             {
                extend: 'csv',
                text: "CSV",
                title: "",
                exportOptions: {
                   columns: [ 0, 1, 2, 3, 4, 5 ]
                }
            },
             {
                extend: 'pdf',
                text: "PDF",
                title: "",
                orientation: 'landscape',
                pageSize: 'LEGAL',
                exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
                }
            },
             {
                extend: 'print',
                title: "",
                text: "print",
                exportOptions: {
                    columns: [ 0, 1, 2, 3, 4, 5 ]
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
            url :"all_section_ajax.php",
            type: "post",
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
      });*/

</script>

<?php include "header/footer.php"; ?>