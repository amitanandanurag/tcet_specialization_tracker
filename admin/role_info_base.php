<?php include "header/header.php"; ?>
<div class="content-wrapper">
  <section class="content-header">
    <div class="erp-page-header">
      <div class="erp-page-title-wrap">
        <h1 class="erp-page-title"><i class="fa fa-users"></i> <?php echo htmlspecialchars($roleLabel); ?> Directory</h1>
        <div class="erp-breadcrumb">
          <a href="index.php"><i class="fa fa-dashboard"></i> Dashboard</a>
          <span class="sep">&rsaquo;</span>
          <span class="active-item"><?php echo htmlspecialchars($roleLabel); ?> Management</span>
        </div>
      </div>
      <div>
        <a href="<?php echo htmlspecialchars($registerFile); ?>" class="btn-erp-primary">
          <i class="fa fa-user-plus"></i> Register <?php echo htmlspecialchars($roleLabel); ?>
        </a>
      </div>
    </div>
  </section>

  <section class="content" style="padding-top: 0;">
    <div class="row">
      <div class="col-md-12">
        <div class="erp-card">
          <div class="erp-card-header">
            <div class="erp-card-title-group">
              <h3 class="erp-card-title"><?php echo htmlspecialchars($roleLabel); ?> Records</h3>
            </div>
          </div>
          <div class="table-responsive" style="padding: 12px 16px;">
            <table id="roleTable" class="erp-table table table-bordered table-striped" width="100%">
              <thead>
                <tr>
                  <th style="width: 50px;" class="col-center">#</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Department</th>
                  <th>Role</th>
                  <th style="width: 40px;" class="col-center">View</th>
                  <th style="width: 40px;" class="col-center">Edit</th>
                  <th style="width: 40px;" class="col-center">Delete</th>
                </tr>
              </thead>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div id="viewModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" style="display:none;">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
        <h4 class="modal-title"><i class="fa fa-eye"></i> View <?php echo htmlspecialchars($roleLabel); ?></h4>
      </div>
      <div class="modal-body">
        <div id="view-dynamic-content"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="editModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true" style="display:none;">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
        <h4 class="modal-title"><i class="fa fa-pencil"></i> Edit <?php echo htmlspecialchars($roleLabel); ?></h4>
      </div>
      <div class="modal-body">
        <div id="edit-dynamic-content"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
<script>
$(document).ready(function() {
  $('#roleTable').DataTable({
    processing: true,
    serverSide: true,
    pageLength: 15,
    order: [[0, 'asc']],
    ajax: {
      url: '<?php echo $ajaxFile; ?>',
      type: 'POST'
    },
    columnDefs: [
      { orderable: false, targets: [6, 7, 8] }
    ]
  });

  $(document).on('click', '.role-view-btn', function(e) {
    e.preventDefault();
    var id = $(this).data('id');
    $('#view-dynamic-content').html('Loading...');
    $('#viewModal').modal('show');
    $.post('<?php echo $viewFile; ?>', { id: id }, function(data) {
      $('#view-dynamic-content').html(data);
    }).fail(function() {
      $('#view-dynamic-content').html('Unable to load data.');
    });
  });

  $(document).on('click', '.role-edit-btn', function(e) {
    e.preventDefault();
    var id = $(this).data('id');
    $('#edit-dynamic-content').html('Loading...');
    $('#editModal').modal('show');
    $.post('<?php echo $editFile; ?>', { id: id }, function(data) {
      $('#edit-dynamic-content').html(data);
    }).fail(function() {
      $('#edit-dynamic-content').html('Unable to load data.');
    });
  });
});

function deleteRoleUser(userId) {
  if (!confirm('Are you sure you want to remove this record?')) {
    return;
  }
  $.ajax({
    url: '<?php echo $deleteFile; ?>',
    type: 'POST',
    dataType: 'json',
    data: { user_id: userId },
    success: function(resp) {
      if (resp.success) {
        $('#roleTable').DataTable().ajax.reload(null, false);
      } else {
        alert(resp.message || 'Delete failed.');
      }
    },
    error: function() {
      alert('Something went wrong while deleting.');
    }
  });
}
</script>
<?php include "header/footer.php"; ?>
