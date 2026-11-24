<?php
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once __DIR__ . "/../database/db_connect.php";
if (class_exists('DBController')) {
    DBController::startSecureSession();
} elseif (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    @session_start();
}

$db_handle = new DBController();

$requestData = $_REQUEST;
$roleId = intval($_POST['role_id'] ?? $_GET['role_id'] ?? 0);
if (!in_array($roleId, array(0, 1, 2, 3, 4), true)) {
  $roleId = 0;
}

$columns = array(
  0 => 'u.user_id',
  1 => 'u.user_name',
  2 => 'u.email_id',
  3 => 'u.phone_number',
  4 => 'd.department_name',
  5 => 'r.role_name',
  6 => 'u.user_id'
);

$whereRole = ($roleId === 0) ? " WHERE u.role_id IN (1, 2, 3, 4) " : " WHERE u.role_id = $roleId ";
$baseSql = "SELECT u.user_id, u.user_name, u.email_id, u.phone_number, u.role_id, d.department_name, r.role_name FROM st_user_master u LEFT JOIN st_department_master d ON d.department_id = u.department_id LEFT JOIN st_role_master r ON r.role_id = u.role_id" . $whereRole;

if (!empty($requestData['search']['value'])) {
  $search = mysqli_real_escape_string($db_handle->conn, $requestData['search']['value']);
  $baseSql .= " AND (u.user_name LIKE '%$search%' OR u.email_id LIKE '%$search%' OR u.phone_number LIKE '%$search%' OR d.department_name LIKE '%$search%' OR r.role_name LIKE '%$search%')";
}

$totalData = $db_handle->numRows("SELECT u.user_id FROM st_user_master u" . $whereRole);
$totalFiltered = $db_handle->numRows($baseSql);

$orderColumnIndex = isset($requestData['order'][0]['column']) ? intval($requestData['order'][0]['column']) : 0;
$orderColumn = $columns[$orderColumnIndex] ?? 'u.user_id';
$orderDir = (isset($requestData['order'][0]['dir']) && strtolower($requestData['order'][0]['dir']) === 'desc') ? 'DESC' : 'ASC';
$start = isset($requestData['start']) ? intval($requestData['start']) : 0;
$length = isset($requestData['length']) ? intval($requestData['length']) : 15;

$dataSql = $baseSql . " ORDER BY $orderColumn $orderDir LIMIT $start, $length";
$result = $db_handle->query($dataSql);

$roleKeyMap = array(1 => 'superadmin', 2 => 'admin', 3 => 'coordinator', 4 => 'mentor');

$data = array();
$srNo = $start + 1;
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $nestedData = array();
    $nestedData[] = $srNo++;
    $nestedData[] = htmlspecialchars($row['user_name'] ?? '');
    $nestedData[] = htmlspecialchars($row['email_id'] ?? '');
    $nestedData[] = htmlspecialchars($row['phone_number'] ?? '');
    $nestedData[] = htmlspecialchars($row['department_name'] ?? 'N/A');

    $rId = (int)($row['role_id'] ?? 0);
    $rName = htmlspecialchars($row['role_name'] ?? 'User');
    if ($rId === 1) {
      $roleBadge = "<span class='erp-role-pill role-pill-superadmin'><i class='fa fa-shield'></i> " . $rName . "</span>";
    } elseif ($rId === 2) {
      $roleBadge = "<span class='erp-role-pill role-pill-admin'><i class='fa fa-user-circle'></i> " . $rName . "</span>";
    } elseif ($rId === 3) {
      $roleBadge = "<span class='erp-role-pill role-pill-coordinator'><i class='fa fa-user-tie'></i> " . $rName . "</span>";
    } else {
      $roleBadge = "<span class='erp-role-pill role-pill-mentor'><i class='fa fa-users'></i> " . $rName . "</span>";
    }
    $nestedData[] = $roleBadge;

    $uRoleKey = $roleKeyMap[$rId] ?? 'admin';
    if ($rId === 1) {
      $actionBtn = "<span class='erp-protected-badge' title='Protected System User'><i class='fa fa-lock'></i> Protected</span>";
    } else {
      $actionBtn = "<button type='button' class='btn-erp-table-action btn-erp-action-danger' title='Remove User' onclick=\"deleteUser('" . intval($row['user_id']) . "','" . $uRoleKey . "')\"><i class='fa fa-trash-o'></i> Delete</button>";
    }
    $nestedData[] = $actionBtn;
    $data[] = $nestedData;
  }
}

$draw = intval($requestData['draw'] ?? 1);

if (ob_get_length()) {
    ob_clean();
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
  'draw' => $draw,
  'recordsTotal' => intval($totalData),
  'recordsFiltered' => intval($totalFiltered),
  'data' => $data
));
exit;
