<?php ob_start(); include "header/header.php"; ?>
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

function clean_sort_order($value)
{
  $value = trim((string) $value);
  return ($value === '') ? 0 : max(0, intval($value));
}

function ensure_parent_menu_allocation_for_roles($conn, $menuId)
{
  $roleIds = array(1, 2, 3, 4);
  foreach ($roleIds as $roleId) {
    $checkSql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = ? AND menu_id = ? AND sub_menu_id IS NULL LIMIT 1";
    $checkStmt = mysqli_prepare($conn, $checkSql);

    if ($checkStmt) {
      mysqli_stmt_bind_param($checkStmt, 'ii', $roleId, $menuId);
      mysqli_stmt_execute($checkStmt);
      $checkResult = mysqli_stmt_get_result($checkStmt);
      $exists = ($checkResult && mysqli_num_rows($checkResult) > 0);
      mysqli_stmt_close($checkStmt);

      if (!$exists) {
        $insertSql = "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, ?, ?, NULL)";
        $insertStmt = mysqli_prepare($conn, $insertSql);
        if ($insertStmt) {
          mysqli_stmt_bind_param($insertStmt, 'ii', $roleId, $menuId);
          mysqli_stmt_execute($insertStmt);
          mysqli_stmt_close($insertStmt);
        }
      }
    }
  }
}

function ensure_sub_menu_allocation_for_roles($conn, $menuId, $subMenuId)
{
  $roleIds = array(1, 2, 3, 4);
  ensure_parent_menu_allocation_for_roles($conn, $menuId);

  foreach ($roleIds as $roleId) {
    $checkSql = "SELECT 1 FROM st_menu_allocation_master WHERE user_id = 0 AND role_id = ? AND menu_id = ? AND sub_menu_id = ? LIMIT 1";
    $checkStmt = mysqli_prepare($conn, $checkSql);

    if ($checkStmt) {
      mysqli_stmt_bind_param($checkStmt, 'iii', $roleId, $menuId, $subMenuId);
      mysqli_stmt_execute($checkStmt);
      $checkResult = mysqli_stmt_get_result($checkStmt);
      $exists = ($checkResult && mysqli_num_rows($checkResult) > 0);
      mysqli_stmt_close($checkStmt);

      if (!$exists) {
        $insertSql = "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (0, ?, ?, ?)";
        $insertStmt = mysqli_prepare($conn, $insertSql);
        if ($insertStmt) {
          mysqli_stmt_bind_param($insertStmt, 'iii', $roleId, $menuId, $subMenuId);
          mysqli_stmt_execute($insertStmt);
          mysqli_stmt_close($insertStmt);
        }
      }
    }
  }
}

$activeTab = 'class-list';
$alertType = '';
$alertMessage = '';
$openAddModalType = '';
$openAddSubMenuModal = false;
$shouldSyncSidebar = false;
$ajaxResponse = null;
$isAjaxRequest = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function get_menu_icon_class($menuName)
{
  $menuName = strtolower(trim((string) $menuName));
  $map = array(
    'students' => 'fa fa-graduation-cap',
    'admin' => 'fa fa-user-secret',
    'coordinator' => 'fa fa-users',
    'mentor' => 'fa fa-user',
    'settings' => 'fa fa-book'
  );

  return isset($map[$menuName]) ? $map[$menuName] : 'fa fa-folder';
}

function get_submenu_icon_class($subMenuName)
{
  $subMenuName = strtolower(trim((string) $subMenuName));
  $map = array(
    'register students' => 'fa fa-plus',
    'list of students' => 'fa fa-info-circle',
    'concise details' => 'fa fa-info-circle',
    'left students' => 'fa fa-minus-circle',
    'previous students' => 'fa fa-history',
    'register admin' => 'fa fa-plus',
    'admin info' => 'fa fa-info-circle',
    'register coordinator' => 'fa fa-plus',
    'coordinator info' => 'fa fa-info-circle',
    'register mentor' => 'fa fa-plus',
    'mentor info' => 'fa fa-info-circle',
    'manage class' => 'fa fa-cogs',
    'manage section' => 'fa fa-list-alt'
  );

  return isset($map[$subMenuName]) ? $map[$subMenuName] : 'fa fa-angle-double-right';
}

function get_submenu_route($subMenuName)
{
  $subMenuName = strtolower(trim((string) $subMenuName));
  $map = array(
    'register students' => 'student_admission.php',
    'list of students' => 'student-info.php',
    'concise details' => 'student_concise_details.php',
    'left students' => '#',
    'previous students' => '#',
    'register admin' => 'admin_register.php',
    'admin info' => 'admin_info.php',
    'register coordinator' => 'coordinator_register.php',
    'coordinator info' => 'coordinator_info.php',
    'register mentor' => 'mentor_register.php',
    'mentor info' => 'mentor_info.php',
    'manage class' => 'class_crud_new.php',
    'manage section' => 'class_crud_new.php#section-list'
  );

  return isset($map[$subMenuName]) ? $map[$subMenuName] : '#';
}

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
                $shouldSyncSidebar = true;
                $insertedId = mysqli_insert_id($db_handle->conn);

                if ($masterType === 'menu') {
                  if ($insertedId > 0) {
                    ensure_parent_menu_allocation_for_roles($db_handle->conn, $insertedId);
                  }
                }

                if ($isAjaxRequest) {
                  $ajaxResponse = array(
                    'status' => 'success',
                    'message' => $alertMessage,
                    'master_type' => $masterType,
                    'master_id' => $insertedId,
                    'master_name' => $name,
                    'menu_icon' => get_menu_icon_class($name)
                  );
                }
              } else {
                $alertType = 'danger';
                $alertMessage = 'Unable to add ' . strtolower($title) . '.';
                if ($isAjaxRequest) {
                  $ajaxResponse = array('status' => 'error', 'message' => $alertMessage);
                }
              }
            } else {
              $alertType = 'danger';
              $alertMessage = 'Unable to prepare add statement for ' . strtolower($title) . '.';
              if ($isAjaxRequest) {
                $ajaxResponse = array('status' => 'error', 'message' => $alertMessage);
              }
            }
          }
        } else {
          $alertType = 'danger';
          $alertMessage = 'Unable to validate duplicate ' . strtolower($title) . '.';
          if ($isAjaxRequest) {
            $ajaxResponse = array('status' => 'error', 'message' => $alertMessage);
          }
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
                $shouldSyncSidebar = true;
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
        if ($masterType === 'menu') {
          mysqli_begin_transaction($db_handle->conn);

          $ok = true;

          $deleteAllocBySubSql = "DELETE ma
                                 FROM st_menu_allocation_master ma
                                 INNER JOIN st_sub_menu_master sm ON sm.sub_menu_id = ma.sub_menu_id
                                 WHERE sm.menu_id = ?";
          $deleteAllocBySubStmt = mysqli_prepare($db_handle->conn, $deleteAllocBySubSql);
          if ($deleteAllocBySubStmt) {
            mysqli_stmt_bind_param($deleteAllocBySubStmt, 'i', $id);
            $ok = $ok && mysqli_stmt_execute($deleteAllocBySubStmt);
            mysqli_stmt_close($deleteAllocBySubStmt);
          } else {
            $ok = false;
          }

          if ($ok) {
            $deleteAllocSql = "DELETE FROM st_menu_allocation_master WHERE menu_id = ?";
            $deleteAllocStmt = mysqli_prepare($db_handle->conn, $deleteAllocSql);
            if ($deleteAllocStmt) {
              mysqli_stmt_bind_param($deleteAllocStmt, 'i', $id);
              $ok = $ok && mysqli_stmt_execute($deleteAllocStmt);
              mysqli_stmt_close($deleteAllocStmt);
            } else {
              $ok = false;
            }
          }

          if ($ok) {
            $deleteSubMenuSql = "DELETE FROM st_sub_menu_master WHERE menu_id = ?";
            $deleteSubMenuStmt = mysqli_prepare($db_handle->conn, $deleteSubMenuSql);
            if ($deleteSubMenuStmt) {
              mysqli_stmt_bind_param($deleteSubMenuStmt, 'i', $id);
              $ok = $ok && mysqli_stmt_execute($deleteSubMenuStmt);
              mysqli_stmt_close($deleteSubMenuStmt);
            } else {
              $ok = false;
            }
          }

          if ($ok) {
            $deleteMenuSql = "DELETE FROM st_menu_master WHERE menu_id = ?";
            $deleteMenuStmt = mysqli_prepare($db_handle->conn, $deleteMenuSql);
            if ($deleteMenuStmt) {
              mysqli_stmt_bind_param($deleteMenuStmt, 'i', $id);
              $ok = $ok && mysqli_stmt_execute($deleteMenuStmt);
              mysqli_stmt_close($deleteMenuStmt);
            } else {
              $ok = false;
            }
          }

          if ($ok) {
            mysqli_commit($db_handle->conn);
            $alertType = 'success';
            $alertMessage = $title . ' deleted successfully.';
            $shouldSyncSidebar = true;
            if ($isAjaxRequest) {
              $ajaxResponse = array('status' => 'success', 'message' => $alertMessage, 'master_type' => $masterType, 'master_id' => $id);
            }
          } else {
            mysqli_rollback($db_handle->conn);
            $alertType = 'danger';
            $alertMessage = 'Unable to delete ' . strtolower($title) . '. It may be in use.';
            if ($isAjaxRequest) {
              $ajaxResponse = array('status' => 'error', 'message' => $alertMessage);
            }
          }
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
              $shouldSyncSidebar = true;
              if ($isAjaxRequest) {
                $ajaxResponse = array('status' => 'success', 'message' => $alertMessage, 'master_type' => $masterType, 'master_id' => $id);
              }
            } else {
              $alertType = 'danger';
              $alertMessage = 'Unable to delete ' . strtolower($title) . '. It may be in use.';
              if ($isAjaxRequest) {
                $ajaxResponse = array('status' => 'error', 'message' => $alertMessage);
              }
            }
          } else {
            $alertType = 'danger';
            $alertMessage = 'Unable to prepare delete statement for ' . strtolower($title) . '.';
            if ($isAjaxRequest) {
              $ajaxResponse = array('status' => 'error', 'message' => $alertMessage);
            }
          }
        }
      }
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sub_menu_action'])) {
  $subMenuAction = $_POST['sub_menu_action'];
  $activeTab = 'sub-menu-list';

  if ($subMenuAction === 'add') {
    $menuId = intval($_POST['menu_id'] ?? 0);
    $subMenuName = clean_master_value($_POST['sub_menu_name'] ?? '');
    $sortOrder = clean_sort_order($_POST['sort_order'] ?? 0);
    $openAddSubMenuModal = true;

    if ($menuId <= 0 || $subMenuName === '') {
      $alertType = 'warning';
      $alertMessage = 'Menu and sub menu name are required.';
    } else {
      $dupSql = "SELECT COUNT(*) AS cnt FROM st_sub_menu_master WHERE menu_id = ? AND LOWER(TRIM(sub_menu_name)) = LOWER(TRIM(?))";
      $dupStmt = mysqli_prepare($db_handle->conn, $dupSql);

      if ($dupStmt) {
        mysqli_stmt_bind_param($dupStmt, 'is', $menuId, $subMenuName);
        mysqli_stmt_execute($dupStmt);
        $dupResult = mysqli_stmt_get_result($dupStmt);
        $dupRow = $dupResult ? mysqli_fetch_assoc($dupResult) : array('cnt' => 0);
        mysqli_stmt_close($dupStmt);

        if (!empty($dupRow) && intval($dupRow['cnt']) > 0) {
          $alertType = 'warning';
          $alertMessage = 'Sub menu already exists under selected menu.';
        } else {
          if ($sortOrder <= 0) {
            $sortSql = "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order FROM st_sub_menu_master WHERE menu_id = ?";
            $sortStmt = mysqli_prepare($db_handle->conn, $sortSql);
            if ($sortStmt) {
              mysqli_stmt_bind_param($sortStmt, 'i', $menuId);
              mysqli_stmt_execute($sortStmt);
              $sortResult = mysqli_stmt_get_result($sortStmt);
              $sortRow = $sortResult ? mysqli_fetch_assoc($sortResult) : array('next_order' => 1);
              mysqli_stmt_close($sortStmt);
              $sortOrder = intval($sortRow['next_order']);
            }
          }

          $insertSql = "INSERT INTO st_sub_menu_master (menu_id, sub_menu_name, sort_order) VALUES (?, ?, ?)";
          $insertStmt = mysqli_prepare($db_handle->conn, $insertSql);

          if ($insertStmt) {
            mysqli_stmt_bind_param($insertStmt, 'isi', $menuId, $subMenuName, $sortOrder);
            $ok = mysqli_stmt_execute($insertStmt);
            mysqli_stmt_close($insertStmt);

            if ($ok) {
              $newSubMenuId = mysqli_insert_id($db_handle->conn);
              ensure_sub_menu_allocation_for_roles($db_handle->conn, $menuId, $newSubMenuId);

              $alertType = 'success';
              $alertMessage = 'Sub menu added successfully.';
              $openAddSubMenuModal = false;
              $shouldSyncSidebar = true;
              if ($isAjaxRequest) {
                $menuNameSql = $db_handle->conn->prepare("SELECT menu_name FROM st_menu_master WHERE menu_id = ?");
                $menuName = '';
                if ($menuNameSql) {
                  $menuNameSql->bind_param('i', $menuId);
                  $menuNameSql->execute();
                  $menuNameResult = $menuNameSql->get_result();
                  if ($menuNameResult && ($menuNameRow = $menuNameResult->fetch_assoc())) {
                    $menuName = $menuNameRow['menu_name'];
                  }
                  $menuNameSql->close();
                }

                $ajaxResponse = array(
                  'status' => 'success',
                  'message' => $alertMessage,
                  'sub_menu_id' => $newSubMenuId,
                  'menu_id' => $menuId,
                  'menu_name' => $menuName,
                  'sub_menu_name' => $subMenuName,
                  'sort_order' => $sortOrder,
                  'sub_menu_icon' => get_submenu_icon_class($subMenuName),
                  'sub_menu_route' => get_submenu_route($subMenuName)
                );
              }
            } else {
              $alertType = 'danger';
              $alertMessage = 'Unable to add sub menu.';
              if ($isAjaxRequest) {
                $ajaxResponse = array('status' => 'error', 'message' => $alertMessage);
              }
            }
          } else {
            $alertType = 'danger';
            $alertMessage = 'Unable to prepare add statement for sub menu.';
            if ($isAjaxRequest) {
              $ajaxResponse = array('status' => 'error', 'message' => $alertMessage);
            }
          }
        }
      } else {
        $alertType = 'danger';
        $alertMessage = 'Unable to validate duplicate sub menu.';
        if ($isAjaxRequest) {
          $ajaxResponse = array('status' => 'error', 'message' => $alertMessage);
        }
      }
    }
  } elseif ($subMenuAction === 'update') {
    $subMenuId = intval($_POST['sub_menu_id'] ?? 0);
    $menuId = intval($_POST['menu_id'] ?? 0);
    $subMenuName = clean_master_value($_POST['sub_menu_name'] ?? '');
    $sortOrder = clean_sort_order($_POST['sort_order'] ?? 0);

    if ($subMenuId <= 0 || $menuId <= 0 || $subMenuName === '') {
      $alertType = 'warning';
      $alertMessage = 'Valid sub menu details are required for update.';
    } else {
      $dupSql = "SELECT COUNT(*) AS cnt FROM st_sub_menu_master WHERE menu_id = ? AND LOWER(TRIM(sub_menu_name)) = LOWER(TRIM(?)) AND sub_menu_id <> ?";
      $dupStmt = mysqli_prepare($db_handle->conn, $dupSql);

      if ($dupStmt) {
        mysqli_stmt_bind_param($dupStmt, 'isi', $menuId, $subMenuName, $subMenuId);
        mysqli_stmt_execute($dupStmt);
        $dupResult = mysqli_stmt_get_result($dupStmt);
        $dupRow = $dupResult ? mysqli_fetch_assoc($dupResult) : array('cnt' => 0);
        mysqli_stmt_close($dupStmt);

        if (!empty($dupRow) && intval($dupRow['cnt']) > 0) {
          $alertType = 'warning';
          $alertMessage = 'Sub menu already exists under selected menu.';
        } else {
          $updateSql = "UPDATE st_sub_menu_master SET menu_id = ?, sub_menu_name = ?, sort_order = ? WHERE sub_menu_id = ?";
          $updateStmt = mysqli_prepare($db_handle->conn, $updateSql);

          if ($updateStmt) {
            mysqli_stmt_bind_param($updateStmt, 'isii', $menuId, $subMenuName, $sortOrder, $subMenuId);
            $ok = mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);

            if ($ok) {
              $syncSql = "UPDATE st_menu_allocation_master SET menu_id = ? WHERE sub_menu_id = ?";
              $syncStmt = mysqli_prepare($db_handle->conn, $syncSql);
              if ($syncStmt) {
                mysqli_stmt_bind_param($syncStmt, 'ii', $menuId, $subMenuId);
                mysqli_stmt_execute($syncStmt);
                mysqli_stmt_close($syncStmt);
              }

              ensure_sub_menu_allocation_for_roles($db_handle->conn, $menuId, $subMenuId);
              $alertType = 'success';
              $alertMessage = 'Sub menu updated successfully.';
              $shouldSyncSidebar = true;
            } else {
              $alertType = 'danger';
              $alertMessage = 'Unable to update sub menu.';
            }
          } else {
            $alertType = 'danger';
            $alertMessage = 'Unable to prepare update statement for sub menu.';
          }
        }
      } else {
        $alertType = 'danger';
        $alertMessage = 'Unable to validate duplicate sub menu before update.';
      }
    }
  } elseif ($subMenuAction === 'delete') {
    $subMenuId = intval($_POST['sub_menu_id'] ?? 0);

    if ($subMenuId <= 0) {
      $alertType = 'warning';
      $alertMessage = 'Invalid sub menu selected for delete.';
        if ($isAjaxRequest) {
          $ajaxResponse = array('status' => 'error', 'message' => $alertMessage);
        }
    } else {
      $allocDeleteSql = "DELETE FROM st_menu_allocation_master WHERE sub_menu_id = ?";
      $allocDeleteStmt = mysqli_prepare($db_handle->conn, $allocDeleteSql);
      if ($allocDeleteStmt) {
        mysqli_stmt_bind_param($allocDeleteStmt, 'i', $subMenuId);
        mysqli_stmt_execute($allocDeleteStmt);
        mysqli_stmt_close($allocDeleteStmt);
      }

      $deleteSql = "DELETE FROM st_sub_menu_master WHERE sub_menu_id = ?";
      $deleteStmt = mysqli_prepare($db_handle->conn, $deleteSql);

      if ($deleteStmt) {
        mysqli_stmt_bind_param($deleteStmt, 'i', $subMenuId);
        $ok = mysqli_stmt_execute($deleteStmt);
        mysqli_stmt_close($deleteStmt);

        if ($ok) {
          $alertType = 'success';
          $alertMessage = 'Sub menu deleted successfully.';
          $shouldSyncSidebar = true;
            if ($isAjaxRequest) {
              $ajaxResponse = array('status' => 'success', 'message' => $alertMessage, 'sub_menu_id' => $subMenuId);
            }
        } else {
          $alertType = 'danger';
          $alertMessage = 'Unable to delete sub menu.';
            if ($isAjaxRequest) {
              $ajaxResponse = array('status' => 'error', 'message' => $alertMessage);
            }
        }
      } else {
        $alertType = 'danger';
        $alertMessage = 'Unable to prepare delete statement for sub menu.';
          if ($isAjaxRequest) {
            $ajaxResponse = array('status' => 'error', 'message' => $alertMessage);
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

if ($isAjaxRequest && $ajaxResponse !== null) {
  if (ob_get_length()) {
    ob_clean();
  }
  header('Content-Type: application/json');
  echo json_encode($ajaxResponse);
  exit();
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

$menuOptions = array();
$menuResult = $db_handle->conn->query("SELECT menu_id, menu_name FROM st_menu_master ORDER BY menu_name ASC");
if ($menuResult) {
  while ($menuRow = $menuResult->fetch_assoc()) {
    $menuOptions[] = $menuRow;
  }
}

$subMenuRows = array();
$subMenuResult = $db_handle->conn->query("SELECT sm.sub_menu_id, sm.menu_id, sm.sub_menu_name, sm.sort_order, m.menu_name FROM st_sub_menu_master sm INNER JOIN st_menu_master m ON m.menu_id = sm.menu_id ORDER BY m.menu_name ASC, sm.sort_order ASC, sm.sub_menu_id ASC");
if ($subMenuResult) {
  while ($subMenuRow = $subMenuResult->fetch_assoc()) {
    $subMenuRows[] = $subMenuRow;
  }
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
      <h3><i class="fa fa-cogs"></i> Class, Section, Department, Menu And Sub Menu Management</h3>

      <?php if ($alertMessage !== '') { ?>
        <div class="alert alert-<?php echo htmlspecialchars($alertType); ?> alert-dismissible" style="margin-top: 15px;">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
          <?php echo htmlspecialchars($alertMessage); ?>
        </div>
      <?php } ?>

      <div id="ajax-status-message" style="margin-top: 15px;"></div>

      <ul class="nav nav-tabs" style="margin-top: 20px;">
        <li class="<?php echo ($activeTab === 'class-list') ? 'active' : ''; ?>"><a data-toggle="tab" href="#class-list">Class List</a></li>

        <li class="<?php echo ($activeTab === 'section-list') ? 'active' : ''; ?>"><a data-toggle="tab" href="#section-list">Section List</a></li>

        <li class="<?php echo ($activeTab === 'department-list') ? 'active' : ''; ?>"><a data-toggle="tab" href="#department-list">Department List</a></li>

        <li class="<?php echo ($activeTab === 'menu-list') ? 'active' : ''; ?>"><a data-toggle="tab" href="#menu-list">Menu List</a></li>

        <li class="<?php echo ($activeTab === 'sub-menu-list') ? 'active' : ''; ?>"><a data-toggle="tab" href="#sub-menu-list">Sub Menu List</a></li>
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
                        data-master-name="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>"
                        data-master-title="<?php echo htmlspecialchars($title, ENT_QUOTES); ?>"
                      >
                        <i class="fa fa-pencil"></i>
                      </button>
                    </td>
                    <td>
                      <form method="POST" class="ajax-delete-form" style="display:inline;" onsubmit="return confirmMasterDelete(<?php echo json_encode($name); ?>);">
                        <input type="hidden" name="master_action" value="delete">
                        <input type="hidden" name="master_type" value="<?php echo htmlspecialchars($type, ENT_QUOTES); ?>">
                        <input type="hidden" name="master_id" value="<?php echo $id; ?>">
                        <button type="submit" class="btn btn-sm btn-danger">
                          <i class="fa fa-trash"></i>
                        </button>
                      </form>
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

        <div id="sub-menu-list" class="tab-pane fade <?php echo ($activeTab === 'sub-menu-list') ? 'in active' : ''; ?>">
          <div class="clearfix" style="margin-bottom: 15px;">
            <button type="button" class="btn btn-success pull-right" data-toggle="modal" data-target="#addSubMenuModal">
              <i class="fa fa-plus"></i>
            </button>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-striped text-center">
              <thead>
                <tr>
                  <th style="width: 80px;">No.</th>
                  <th>Menu Name</th>
                  <th style="width: 100px;">Sequence</th>
                  <th>Sub Menu Name</th>
                  <th style="width: 100px;">Edit</th>
                  <th style="width: 100px;">Delete</th>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($subMenuRows)) { ?>
                <tr>
                  <td colspan="5">No sub menu found.</td>
                </tr>
              <?php } else {
                $subSerial = 1;
                foreach ($subMenuRows as $subRow) {
                  $subId = intval($subRow['sub_menu_id']);
                  $subMenuIdValue = intval($subRow['menu_id']);
                  $sortOrderValue = intval($subRow['sort_order']);
                  $menuNameValue = (string) $subRow['menu_name'];
                  $subNameValue = (string) $subRow['sub_menu_name'];
              ?>
                <tr>
                  <td><?php echo $subSerial; ?></td>
                  <td><?php echo htmlspecialchars($menuNameValue); ?></td>
                  <td><?php echo $sortOrderValue; ?></td>
                  <td><?php echo htmlspecialchars($subNameValue); ?></td>
                  <td>
                    <button
                      type="button"
                      class="btn btn-sm btn-primary open-submenu-edit-modal"
                      data-toggle="modal"
                      data-target="#editSubMenuModal"
                      data-sub-menu-id="<?php echo $subId; ?>"
                      data-menu-id="<?php echo $subMenuIdValue; ?>"
                      data-sub-menu-name="<?php echo htmlspecialchars($subNameValue, ENT_QUOTES); ?>"
                      data-sort-order="<?php echo $sortOrderValue; ?>"
                    >
                      <i class="fa fa-pencil"></i>
                    </button>
                  </td>
                  <td>
                    <form method="POST" class="ajax-delete-form" style="display:inline;" onsubmit="return confirmSubMenuDelete(<?php echo json_encode($subNameValue); ?>);">
                      <input type="hidden" name="sub_menu_action" value="delete">
                      <input type="hidden" name="sub_menu_id" value="<?php echo $subId; ?>">
                      <button type="submit" class="btn btn-sm btn-danger">
                        <i class="fa fa-trash"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php
                  $subSerial++;
                }
              }
              ?>
              </tbody>
            </table>
          </div>
        </div>
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
      <form method="POST" class="ajax-add-form">
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

<div id="addSubMenuModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Add Sub Menu</h4>
      </div>
      <form method="POST" class="ajax-add-form">
        <div class="modal-body">
          <input type="hidden" name="sub_menu_action" value="add">

          <div class="form-group">
            <label for="add_sub_menu_parent" class="control-label">Menu</label>
            <select name="menu_id" id="add_sub_menu_parent" class="form-control" required>
              <option value="">Select Menu</option>
              <?php foreach ($menuOptions as $menuOption) { ?>
                <option value="<?php echo intval($menuOption['menu_id']); ?>"><?php echo htmlspecialchars($menuOption['menu_name']); ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="form-group">
            <label for="add_sub_menu_name" class="control-label">Sub Menu Name</label>
            <input type="text" name="sub_menu_name" id="add_sub_menu_name" class="form-control" placeholder="Enter Sub Menu Name" required>
          </div>

          <div class="form-group">
            <label for="add_sort_order" class="control-label">Sequence</label>
            <input type="number" name="sort_order" id="add_sort_order" class="form-control" min="1" placeholder="Auto if blank">
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

<form id="delete-submenu-form" method="POST" style="display:none;">
  <input type="hidden" name="sub_menu_action" value="delete">
  <input type="hidden" name="sub_menu_id" id="delete_sub_menu_id" value="">
</form>

<div id="editSubMenuModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Edit Sub Menu</h4>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="sub_menu_action" value="update">
          <input type="hidden" name="sub_menu_id" id="edit_sub_menu_id" value="">

          <div class="form-group">
            <label for="edit_sub_menu_parent" class="control-label">Menu</label>
            <select name="menu_id" id="edit_sub_menu_parent" class="form-control" required>
              <option value="">Select Menu</option>
              <?php foreach ($menuOptions as $menuOption) { ?>
                <option value="<?php echo intval($menuOption['menu_id']); ?>"><?php echo htmlspecialchars($menuOption['menu_name']); ?></option>
              <?php } ?>
            </select>
          </div>

          <div class="form-group">
            <label for="edit_sub_menu_name" class="control-label">Sub Menu Name</label>
            <input type="text" name="sub_menu_name" id="edit_sub_menu_name" class="form-control" required>
          </div>

          <div class="form-group">
            <label for="edit_sort_order" class="control-label">Sequence</label>
            <input type="number" name="sort_order" id="edit_sort_order" class="form-control" min="1" required>
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

    $('#editSubMenuModal').on('show.bs.modal', function(event) {
      var button = $(event.relatedTarget);
      var subMenuId = button.data('sub-menu-id');
      var menuId = button.data('menu-id');
      var subMenuName = button.data('sub-menu-name');
      var sortOrder = button.data('sort-order');

      $('#edit_sub_menu_id').val(subMenuId);
      $('#edit_sub_menu_parent').val(menuId);
      $('#edit_sub_menu_name').val(subMenuName);
      $('#edit_sort_order').val(sortOrder);
    });

    $(document).on('submit', '.ajax-add-form', function(event) {
      event.preventDefault();

      var form = $(this);
      var button = form.find('button[type="submit"]');
      var originalHtml = button.html();
      var data = form.serialize();

      button.prop('disabled', true);

      $.ajax({
        type: 'POST',
        url: 'class_crud_new.php?tab=<?php echo urlencode($activeTab); ?>',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response && response.status === 'success') {
            $('#addMasterModal').modal('hide');
            $('#addSubMenuModal').modal('hide');

            $('#ajax-status-message').html('<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' + response.message + '</div>');

            if (form.find('input[name="master_action"]').length) {
              var masterType = form.find('input[name="master_type"]').val();
              var masterName = form.find('input[name="master_name"]').val();
              var targetTable = $('#' + masterType + '-list tbody');
                var rowCount = targetTable.find('tr').length + 1;
              var noRows = targetTable.find('tr td[colspan]').first();

              if (noRows.length) {
                noRows.closest('tr').remove();
              }

              var newRow = '';
              if (masterType === 'menu') {
                  var safeMenuName = $('<div/>').text(masterName).html();
                newRow = '<tr>' +
                  '<td>' + rowCount + '</td>' +
                  '<td>' + safeMenuName + '</td>' +
                  '<td><button type="button" class="btn btn-sm btn-primary open-edit-modal" data-toggle="modal" data-target="#editMasterModal" data-master-type="menu" data-master-id="' + response.master_id + '" data-master-name="' + safeMenuName + '" data-master-title="Menu"><i class="fa fa-pencil"></i></button></td>' +
                  '<td><form method="POST" class="ajax-delete-form" style="display:inline;" onsubmit="return confirmMasterDelete(' + JSON.stringify(masterName) + ');"><input type="hidden" name="master_action" value="delete"><input type="hidden" name="master_type" value="menu"><input type="hidden" name="master_id" value="' + response.master_id + '"><button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button></form></td>' +
                  '</tr>';

                targetTable.append(newRow);
                appendSidebarMenu(response.master_id, masterName, response.menu_icon);

                // Keep submenu menu dropdowns in sync without refresh.
                var optionExists = $('#add_sub_menu_parent option[value="' + response.master_id + '"]').length > 0;
                if (!optionExists) {
                  var newOption = $('<option/>', {
                    value: response.master_id,
                    text: masterName
                  });
                  $('#add_sub_menu_parent').append(newOption.clone());
                  $('#edit_sub_menu_parent').append(newOption.clone());
                }
              }
            } else if (form.find('input[name="sub_menu_action"]').length) {
              var menuId = form.find('select[name="menu_id"]').val();
              var menuName = response.menu_name || '';
              var subMenuName = form.find('input[name="sub_menu_name"]').val();
              var sortOrder = response.sort_order || form.find('input[name="sort_order"]').val();
              var targetTable = $('#sub-menu-list tbody');
              var rowCount = targetTable.find('tr').length + 1;
              var noRows = targetTable.find('tr td[colspan]').first();

              if (noRows.length) {
                noRows.closest('tr').remove();
              }

              var safeMenuName = $('<div/>').text(menuName).html();
              var safeSubMenuName = $('<div/>').text(subMenuName).html();
              targetTable.append(
                '<tr>' +
                  '<td>' + rowCount + '</td>' +
                  '<td>' + safeMenuName + '</td>' +
                  '<td>' + $('<div/>').text(String(sortOrder)).html() + '</td>' +
                  '<td>' + safeSubMenuName + '</td>' +
                  '<td><button type="button" class="btn btn-sm btn-primary open-submenu-edit-modal" data-toggle="modal" data-target="#editSubMenuModal" data-sub-menu-id="' + response.sub_menu_id + '" data-menu-id="' + menuId + '" data-sub-menu-name="' + $('<div/>').text(subMenuName).html() + '" data-sort-order="' + sortOrder + '"><i class="fa fa-pencil"></i></button></td>' +
                  '<td><form method="POST" class="ajax-delete-form" style="display:inline;" onsubmit="return confirmSubMenuDelete(' + JSON.stringify(subMenuName) + ');"><input type="hidden" name="sub_menu_action" value="delete"><input type="hidden" name="sub_menu_id" value="' + response.sub_menu_id + '"><button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button></form></td>' +
                '</tr>'
              );

              appendSidebarSubMenu(menuId, menuName, response.sub_menu_id, subMenuName, response.sub_menu_route, response.sub_menu_icon, sortOrder);
            }
          } else {
            alert((response && response.message) ? response.message : 'Unable to save record.');
          }
        },
        error: function() {
          alert('Unable to save record.');
        },
        complete: function() {
          button.prop('disabled', false).html(originalHtml);
        }
      });
    });

    $(document).on('submit', '.ajax-delete-form', function(event) {
      event.preventDefault();

      var form = $(this);
      var row = form.closest('tr');
      var table = row.closest('table');
      var columnCount = table.find('thead th').length;
      var button = form.find('button[type="submit"]');
      var originalHtml = button.html();

      button.prop('disabled', true);

      $.ajax({
        type: 'POST',
        url: 'class_crud_new.php?tab=<?php echo urlencode($activeTab); ?>',
        data: form.serialize(),
        dataType: 'json',
        success: function(response) {
          if (response && response.status === 'success') {
            row.fadeOut(200, function() {
              $(this).remove();
            });

            var tableBody = row.closest('tbody');
            if (tableBody.find('tr').length === 1) {
              tableBody.append('<tr><td colspan="' + columnCount + '">No records found.</td></tr>');
            }

            if (response.master_type === 'menu' && response.master_id) {
              $('#sidebar-menu-' + response.master_id).remove();

              // Remove deleted menu from submenu menu dropdowns immediately.
              $('#add_sub_menu_parent option[value="' + response.master_id + '"]').remove();
              $('#edit_sub_menu_parent option[value="' + response.master_id + '"]').remove();
            }
            if (response.sub_menu_id) {
              var sidebarSub = $('#sidebar-submenu-item-' + response.sub_menu_id);
              var sidebarList = sidebarSub.closest('ul.treeview-menu');
              sidebarSub.remove();

              if (sidebarList.length && sidebarList.find('li').length === 0) {
                // keep empty menu container to allow future add without refresh
              }
            }

            $('#ajax-status-message').html('<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' + response.message + '</div>');
          } else {
            alert((response && response.message) ? response.message : 'Unable to delete record.');
          }
        },
        error: function() {
          alert('Unable to delete record.');
        },
        complete: function() {
          button.prop('disabled', false).html(originalHtml);
        }
      });
    });

    <?php if ($openAddModalType !== '') { ?>
      $('#add_master_type').val('<?php echo htmlspecialchars($openAddModalType, ENT_QUOTES); ?>');
      $('#add-master-title').text('Add <?php echo htmlspecialchars($masters[$openAddModalType]['title'], ENT_QUOTES); ?>');
      $('#add_master_name').attr('placeholder', 'Enter <?php echo htmlspecialchars($masters[$openAddModalType]['title'], ENT_QUOTES); ?> Name');
      $('#addMasterModal').modal('show');
    <?php } ?>

    <?php if ($openAddSubMenuModal) { ?>
      $('#addSubMenuModal').modal('show');
    <?php } ?>

  });

  function confirmMasterDelete(masterName) {
    return confirm('Delete "' + masterName + '"?');
  }

  function confirmSubMenuDelete(subMenuName) {
    return confirm('Delete sub menu "' + subMenuName + '"?');
  }

  function appendSidebarMenu(menuId, menuName, menuIcon) {
    if ($('#sidebar-menu-' + menuId).length) {
      return;
    }

    var html = '';
    html += '<li class="treeview" data-menu-id="' + menuId + '" id="sidebar-menu-' + menuId + '">';
    html += '<a href="#">';
    html += '<i class="' + menuIcon + '" aria-hidden="true"></i> <span>' + menuName.toUpperCase() + '</span>';
    html += '<span class="pull-right-container"><i class="fa fa-angle-right pull-right"></i></span>';
    html += '</a>';
    html += '<ul class="treeview-menu" id="sidebar-submenu-' + menuId + '"></ul>';
    html += '</li>';
    $('#sidebar-dynamic-menu').append(html);
  }

  function appendSidebarSubMenu(menuId, menuName, subMenuId, subMenuName, subMenuRoute, subMenuIcon, sortOrder) {
    var target = $('#sidebar-submenu-' + menuId);
    if (!target.length) {
      appendSidebarMenu(menuId, menuName || 'Menu', 'fa fa-folder');
      target = $('#sidebar-submenu-' + menuId);
    }

    if ($('#sidebar-submenu-item-' + subMenuId).length) {
      return;
    }

    target.append('<li data-sub-menu-id="' + subMenuId + '" id="sidebar-submenu-item-' + subMenuId + '"><a href="' + subMenuRoute + '"><i class="' + subMenuIcon + '"></i>' + subMenuName.toUpperCase() + '</a></li>');
  }
</script>

<?php include "header/footer.php"; ?>
