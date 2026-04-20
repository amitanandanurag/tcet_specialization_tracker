<?php include "header/header.php"; ?>
<?php
$masters = array(
  'class' => array(
    'title' => 'Class',
    'table' => 'st_class_master',
    'pk' => 'class_id',
    'name' => 'class_name'
  ),
  'section' => array(
    'title' => 'Section',
    'table' => 'st_section_master',
    'pk' => 'id',
    'name' => 'sections'
  ),
  'department' => array(
    'title' => 'Department',
    'table' => 'st_department_master',
    'pk' => 'department_id',
    'name' => 'department_name'
  ),
  'menu' => array(
    'title' => 'Menu',
    'table' => 'st_menu_master',
    'pk' => 'menu_id',
    'name' => 'menu_name'
  )
);

function clean_master_value($value)
{
  $value = trim((string) $value);
  $value = preg_replace('/\s+/', ' ', $value);
  return $value;
}

$activeTab = 'class-list';
$alertType = '';
$alertMessage = '';
$openAddModalType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['master_action'], $_POST['master_type'])) {
  $masterType = $_POST['master_type'];
  $action = $_POST['master_action'];

  if (isset($masters[$masterType])) {
    $meta = $masters[$masterType];
    $table = $meta['table'];
    $pk = $meta['pk'];
    $nameCol = $meta['name'];
    $title = $meta['title'];

    if ($action === 'add') {
      $name = clean_master_value($_POST['master_name'] ?? '');
      $activeTab = $masterType . '-add';
      $openAddModalType = $masterType;

      if ($name === '') {
        $alertType = 'warning';
        $alertMessage = $title . ' name is required.';
      } else {
        $dupSql = "SELECT COUNT(*) AS cnt FROM $table WHERE LOWER(TRIM($nameCol)) = LOWER(TRIM(?))";
        $dupStmt = mysqli_prepare($db_handle->conn, $dupSql);

        if ($dupStmt) {
          mysqli_stmt_bind_param($dupStmt, 's', $name);
          mysqli_stmt_execute($dupStmt);
          $dupResult = mysqli_stmt_get_result($dupStmt);
          $dupRow = $dupResult ? mysqli_fetch_assoc($dupResult) : array('cnt' => 0);
          mysqli_stmt_close($dupStmt);

          if (!empty($dupRow) && intval($dupRow['cnt']) > 0) {
            $alertType = 'warning';
            $alertMessage = $title . ' already exists.';
          } else {
            $insertSql = "INSERT INTO $table ($nameCol) VALUES (?)";
            $insertStmt = mysqli_prepare($db_handle->conn, $insertSql);

            if ($insertStmt) {
              mysqli_stmt_bind_param($insertStmt, 's', $name);
              $ok = mysqli_stmt_execute($insertStmt);
              mysqli_stmt_close($insertStmt);

              if ($ok) {
                $alertType = 'success';
                $alertMessage = $title . ' added successfully.';
                $activeTab = $masterType . '-list';
                $openAddModalType = '';
              } else {
                $alertType = 'danger';
                $alertMessage = 'Unable to add ' . strtolower($title) . '.';
              }
            } else {
              $alertType = 'danger';
              $alertMessage = 'Unable to prepare add statement for ' . strtolower($title) . '.';
            }
          }
        } else {
          $alertType = 'danger';
          $alertMessage = 'Unable to validate duplicate ' . strtolower($title) . '.';
        }
      }
    } elseif ($action === 'update') {
      $id = intval($_POST['master_id'] ?? 0);
      $name = clean_master_value($_POST['master_name'] ?? '');
      $activeTab = $masterType . '-list';

      if ($id <= 0 || $name === '') {
        $alertType = 'warning';
        $alertMessage = 'Valid ' . strtolower($title) . ' details are required for update.';
      } else {
        $dupSql = "SELECT COUNT(*) AS cnt FROM $table WHERE LOWER(TRIM($nameCol)) = LOWER(TRIM(?)) AND $pk <> ?";
        $dupStmt = mysqli_prepare($db_handle->conn, $dupSql);

        if ($dupStmt) {
          mysqli_stmt_bind_param($dupStmt, 'si', $name, $id);
          mysqli_stmt_execute($dupStmt);
          $dupResult = mysqli_stmt_get_result($dupStmt);
          $dupRow = $dupResult ? mysqli_fetch_assoc($dupResult) : array('cnt' => 0);
          mysqli_stmt_close($dupStmt);

          if (!empty($dupRow) && intval($dupRow['cnt']) > 0) {
            $alertType = 'warning';
            $alertMessage = $title . ' already exists.';
          } else {
            $updateSql = "UPDATE $table SET $nameCol = ? WHERE $pk = ?";
            $updateStmt = mysqli_prepare($db_handle->conn, $updateSql);

            if ($updateStmt) {
              mysqli_stmt_bind_param($updateStmt, 'si', $name, $id);
              $ok = mysqli_stmt_execute($updateStmt);
              mysqli_stmt_close($updateStmt);

              if ($ok) {
                $alertType = 'success';
                $alertMessage = $title . ' updated successfully.';
              } else {
                $alertType = 'danger';
                $alertMessage = 'Unable to update ' . strtolower($title) . '.';
              }
            } else {
              $alertType = 'danger';
              $alertMessage = 'Unable to prepare update statement for ' . strtolower($title) . '.';
            }
          }
        } else {
          $alertType = 'danger';
          $alertMessage = 'Unable to validate duplicate ' . strtolower($title) . ' before update.';
        }
      }
    } elseif ($action === 'delete') {
      $id = intval($_POST['master_id'] ?? 0);
      $activeTab = $masterType . '-list';

      if ($id <= 0) {
        $alertType = 'warning';
        $alertMessage = 'Invalid ' . strtolower($title) . ' selected for delete.';
      } else {
        $deleteSql = "DELETE FROM $table WHERE $pk = ?";
        $deleteStmt = mysqli_prepare($db_handle->conn, $deleteSql);

        if ($deleteStmt) {
          mysqli_stmt_bind_param($deleteStmt, 'i', $id);
          $ok = mysqli_stmt_execute($deleteStmt);
          mysqli_stmt_close($deleteStmt);

          if ($ok) {
            $alertType = 'success';
            $alertMessage = $title . ' deleted successfully.';
          } else {
            $alertType = 'danger';
            $alertMessage = 'Unable to delete ' . strtolower($title) . '. It may be in use.';
          }
        } else {
          $alertType = 'danger';
          $alertMessage = 'Unable to prepare delete statement for ' . strtolower($title) . '.';
        }
      }
    }
  }
}

if (isset($_GET['tab'])) {
  $requestedTab = trim($_GET['tab']);
  if ($requestedTab !== '') {
    $activeTab = $requestedTab;
  }
}

$masterRows = array();
foreach ($masters as $type => $meta) {
  $table = $meta['table'];
  $pk = $meta['pk'];
  $nameCol = $meta['name'];

  $rows = array();
  $result = $db_handle->conn->query("SELECT $pk AS master_id, $nameCol AS master_name FROM $table ORDER BY $nameCol ASC");
  if ($result) {
    while ($row = $result->fetch_assoc()) {
      $rows[] = $row;
    }
  }
  $masterRows[$type] = $rows;
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <h1>Master CRUD</h1>
    <ol class="breadcrumb">
      <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
      <li class="active">Master CRUD</li>
    </ol>
  </section>

  <section class="content" style="margin-top: 20px;">
    <div class="box" style="padding: 10px;">
      <h3><i class="fa fa-cogs"></i> Class, Section, Department, Menu Master Management</h3>

      <?php if ($alertMessage !== '') { ?>
        <div class="alert alert-<?php echo htmlspecialchars($alertType); ?> alert-dismissible" style="margin-top: 15px;">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
          <?php echo htmlspecialchars($alertMessage); ?>
        </div>
      <?php } ?>

      <ul class="nav nav-tabs" style="margin-top: 20px;">
        <li class="<?php echo ($activeTab === 'class-list') ? 'active' : ''; ?>"><a data-toggle="tab" href="#class-list">Class List</a></li>

        <li class="<?php echo ($activeTab === 'section-list') ? 'active' : ''; ?>"><a data-toggle="tab" href="#section-list">Section List</a></li>

        <li class="<?php echo ($activeTab === 'department-list') ? 'active' : ''; ?>"><a data-toggle="tab" href="#department-list">Department List</a></li>

        <li class="<?php echo ($activeTab === 'menu-list') ? 'active' : ''; ?>"><a data-toggle="tab" href="#menu-list">Menu List</a></li>
      </ul>

      <div class="tab-content" style="padding-top: 20px;">
        <?php foreach ($masters as $type => $meta) {
          $listTabId = $type . '-list';
          $addTabId = $type . '-add';
          $title = $meta['title'];
          $rows = $masterRows[$type];
        ?>

          <div id="<?php echo $listTabId; ?>" class="tab-pane fade <?php echo ($activeTab === $listTabId) ? 'in active' : ''; ?>">
            <div class="clearfix" style="margin-bottom: 15px;">
              <button
                type="button"
                class="btn btn-success pull-right open-add-modal"
                data-toggle="modal"
                data-target="#addMasterModal"
                data-master-type="<?php echo htmlspecialchars($type); ?>"
                data-master-title="<?php echo htmlspecialchars($title); ?>"
              >
                <i class="fa fa-plus"></i>
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-striped text-center">
                <thead>
                  <tr>
                    <th style="width: 80px;">No.</th>
                    <th><?php echo htmlspecialchars($title); ?> Name</th>
                    <th style="width: 100px;">Edit</th>
                    <th style="width: 100px;">Delete</th>
                  </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)) { ?>
                  <tr>
                    <td colspan="4">No <?php echo htmlspecialchars(strtolower($title)); ?> found.</td>
                  </tr>
                <?php } else {
                  $serialNumber = 1;
                  foreach ($rows as $row) {
                    $id = intval($row['master_id']);
                    $name = (string) $row['master_name'];
                ?>
                  <tr>
                    <td><?php echo $serialNumber; ?></td>
                    <td><?php echo htmlspecialchars($name); ?></td>
                    <td>
                      <button
                        type="button"
                        class="btn btn-sm btn-primary open-edit-modal"
                        data-toggle="modal"
                        data-target="#editMasterModal"
                        data-master-type="<?php echo htmlspecialchars($type); ?>"
                        data-master-id="<?php echo $id; ?>"
                        data-master-name="<?php echo htmlspecialchars($name); ?>"
                        data-master-title="<?php echo htmlspecialchars($title); ?>"
                      >
                        <i class="fa fa-pencil"></i>
                      </button>
                    </td>
                    <td>
                      <button type="button" class="btn btn-sm btn-danger" onclick="confirmMasterDelete('<?php echo htmlspecialchars($type); ?>', <?php echo $id; ?>, '<?php echo htmlspecialchars(addslashes($name)); ?>')">
                        <i class="fa fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                <?php
                    $serialNumber++;
                  }
                }
                ?>
                </tbody>
              </table>
            </div>
          </div>

        <?php } ?>
      </div>
    </div>
  </section>
</div>

<div id="addMasterModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="add-master-title">Add</h4>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="master_action" value="add">
          <input type="hidden" name="master_type" id="add_master_type" value="">

          <div class="form-group">
            <label for="add_master_name" class="control-label">Name</label>
            <input type="text" name="master_name" id="add_master_name" class="form-control" placeholder="Enter name" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<form id="delete-master-form" method="POST" style="display:none;">
  <input type="hidden" name="master_action" value="delete">
  <input type="hidden" name="master_type" id="delete_master_type" value="">
  <input type="hidden" name="master_id" id="delete_master_id" value="">
</form>

<div id="editMasterModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="edit-master-title">Edit</h4>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="master_action" value="update">
          <input type="hidden" name="master_type" id="edit_master_type" value="">
          <input type="hidden" name="master_id" id="edit_master_id" value="">

          <div class="form-group">
            <label for="edit_master_name" class="control-label">Name</label>
            <input type="text" name="master_name" id="edit_master_name" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  $(document).ready(function() {
    $('#addMasterModal').on('show.bs.modal', function(event) {
      var button = $(event.relatedTarget);
      var masterType = button.data('master-type');
      var masterTitle = button.data('master-title');

      $('#add_master_type').val(masterType);
      $('#add_master_name').val('');
      $('#add-master-title').text('Add ' + masterTitle);
      $('#add_master_name').attr('placeholder', 'Enter ' + masterTitle + ' Name');
    });

    $('#editMasterModal').on('show.bs.modal', function(event) {
      var button = $(event.relatedTarget);
      var masterType = button.data('master-type');
      var masterId = button.data('master-id');
      var masterName = button.data('master-name');
      var masterTitle = button.data('master-title');

      $('#edit_master_type').val(masterType);
      $('#edit_master_id').val(masterId);
      $('#edit_master_name').val(masterName);
      $('#edit-master-title').text('Edit ' + masterTitle);
    });

    <?php if ($openAddModalType !== '') { ?>
      $('#add_master_type').val('<?php echo htmlspecialchars($openAddModalType, ENT_QUOTES); ?>');
      $('#add-master-title').text('Add <?php echo htmlspecialchars($masters[$openAddModalType]['title'], ENT_QUOTES); ?>');
      $('#add_master_name').attr('placeholder', 'Enter <?php echo htmlspecialchars($masters[$openAddModalType]['title'], ENT_QUOTES); ?> Name');
      $('#addMasterModal').modal('show');
    <?php } ?>
  });

  function confirmMasterDelete(masterType, masterId, masterName) {
    var message = 'Delete "' + masterName + '"?';

    if (typeof swal === 'function') {
      swal({
        title: 'Are you sure?',
        text: message,
        icon: 'warning',
        buttons: true,
        dangerMode: true
      }).then(function(willDelete) {
        if (willDelete) {
          $('#delete_master_type').val(masterType);
          $('#delete_master_id').val(masterId);
          $('#delete-master-form').submit();
        }
      });
    } else if (confirm(message)) {
      $('#delete_master_type').val(masterType);
      $('#delete_master_id').val(masterId);
      $('#delete-master-form').submit();
    }
  }
</script>

<?php include "header/footer.php"; ?>
