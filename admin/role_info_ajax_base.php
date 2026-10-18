<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require "../database/db_connect.php";
$db_handle = new DBController();

// Enforce session authentication
$currentUserId = intval($_SESSION['user_id'] ?? $_SESSION['user_session'] ?? 0);
$userRole = intval($_SESSION['role_id'] ?? $_SESSION['user_type'] ?? 0);

if ($currentUserId <= 0 || $userRole <= 0) {
    http_response_code(403);
    echo json_encode(['recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Unauthorized access. Please login.']);
    exit();
}

$targetRoleId = intval($roleId ?? 0);

// Role 1 (Super Admin) & Role 2 (Admin) can access any role table
// Role 3 (Coordinator) can only view Role 4 (Mentors)
if ($userRole > 2) {
    if (!($userRole === 3 && $targetRoleId === 4)) {
        http_response_code(403);
        echo json_encode(['recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Forbidden access for current role.']);
        exit();
    }
}

$userScope = $db_handle->getUserScope($currentUserId, $userRole);
$requestData = $_REQUEST;
$isMentorTable = ($targetRoleId === 4);

if ($isMentorTable) {
    $columns = array(
        0 => 'u.user_id',
        1 => 'u.user_name',
        2 => 'u.email_id',
        3 => 'u.phone_number',
        4 => 'd.department_name',
        5 => 'cu.user_name',
        6 => 'r.role_name',
        7 => 'u.user_id',
        8 => 'u.user_id',
        9 => 'u.user_id'
    );
    $baseSql = "SELECT u.user_id, u.user_name, u.email_id, u.phone_number, d.department_name, r.role_name,\n" .
               "       COALESCE(NULLIF(TRIM(cu.user_name), ''), 'Unassigned') AS coordinator_name\n" .
               "FROM st_user_master u\n" .
               "LEFT JOIN st_department_master d ON d.department_id = u.department_id\n" .
               "LEFT JOIN st_role_master r ON r.role_id = u.role_id\n" .
               "LEFT JOIN st_coordinator_mentor cm ON cm.mentor_id = u.user_id\n" .
               "LEFT JOIN st_user_master cu ON cu.user_id = cm.coordinator_id AND cu.role_id = 3\n" .
               "WHERE u.role_id = " . intval($roleId);
} else {
    $columns = array(
        0 => 'u.user_id',
        1 => 'u.user_name',
        2 => 'u.email_id',
        3 => 'u.phone_number',
        4 => 'd.department_name',
        5 => 'r.role_name',
        6 => 'u.user_id',
        7 => 'u.user_id',
        8 => 'u.user_id'
    );
    $baseSql = "SELECT u.user_id, u.user_name, u.email_id, u.phone_number, d.department_name, r.role_name\n" .
               "FROM st_user_master u\n" .
               "LEFT JOIN st_department_master d ON d.department_id = u.department_id\n" .
               "LEFT JOIN st_role_master r ON r.role_id = u.role_id\n" .
               "WHERE u.role_id = " . intval($roleId);
}

// Role Scoping for HOD / Coordinator:
$scopeWhere = "";
if ($userRole === 3 && $isMentorTable) {
    $callerDept = intval($userScope['department_id'] ?? 0);
    if ($callerDept > 0) {
        $scopeWhere = " AND (u.department_id = $callerDept OR cm.coordinator_id = $currentUserId)";
    } else {
        $scopeWhere = " AND cm.coordinator_id = $currentUserId";
    }
}
$baseSql .= $scopeWhere;

if (!empty($requestData['search']['value'])) {
    $search = mysqli_real_escape_string($db_handle->conn, $requestData['search']['value']);
    if ($isMentorTable) {
        $baseSql .= " AND (u.user_name LIKE '%$search%' OR u.email_id LIKE '%$search%' OR u.phone_number LIKE '%$search%' OR d.department_name LIKE '%$search%' OR cu.user_name LIKE '%$search%')";
    } else {
        $baseSql .= " AND (u.user_name LIKE '%$search%' OR u.email_id LIKE '%$search%' OR u.phone_number LIKE '%$search%' OR d.department_name LIKE '%$search%')";
    }
}

$countSql = ($isMentorTable)
    ? "SELECT COUNT(DISTINCT u.user_id) AS cnt FROM st_user_master u LEFT JOIN st_coordinator_mentor cm ON cm.mentor_id = u.user_id WHERE u.role_id = " . intval($roleId) . $scopeWhere
    : "SELECT COUNT(DISTINCT u.user_id) AS cnt FROM st_user_master u WHERE u.role_id = " . intval($roleId);

$totalDataRes = $db_handle->query($countSql);
$totalData = ($totalDataRes && ($cRow = $totalDataRes->fetch_assoc())) ? intval($cRow['cnt']) : 0;
$totalFiltered = $db_handle->numRows($baseSql);

$orderColumnIndex = isset($requestData['order'][0]['column']) ? intval($requestData['order'][0]['column']) : 0;
$orderColumn = $columns[$orderColumnIndex] ?? 'u.user_id';
$orderDir = (isset($requestData['order'][0]['dir']) && strtolower($requestData['order'][0]['dir']) === 'desc') ? 'DESC' : 'ASC';
$start = isset($requestData['start']) ? intval($requestData['start']) : 0;
$length = isset($requestData['length']) ? intval($requestData['length']) : 15;

$dataSql = $baseSql . " ORDER BY $orderColumn $orderDir LIMIT $start, $length";
$result = $db_handle->query($dataSql);

$data = array();
$srNo = $start + 1;
while ($row = $result->fetch_assoc()) {
    $userId = intval($row['user_id']);
    $nestedData = array();
    $nestedData[] = $srNo++;
    $nestedData[] = htmlspecialchars($row['user_name'] ?? '');
    $nestedData[] = htmlspecialchars($row['email_id'] ?? '');
    $nestedData[] = htmlspecialchars($row['phone_number'] ?? '');
    $nestedData[] = htmlspecialchars($row['department_name'] ?? '');
    if ($isMentorTable) {
        $coordName = htmlspecialchars($row['coordinator_name'] ?? 'Unassigned');
        $coordBadge = ($coordName !== 'Unassigned') ? "<span class='label label-info'>{$coordName}</span>" : "<span class='label label-default'>Unassigned</span>";
        $nestedData[] = $coordBadge;
    }
    $nestedData[] = htmlspecialchars($row['role_name'] ?? '');
    $nestedData[] = "<button class='btn-erp-icon view-btn role-view-btn' data-id='" . $userId . "' title='View details'><i class='fa fa-eye'></i></button>";
    $nestedData[] = "<button class='btn-erp-icon edit-btn role-edit-btn' data-id='" . $userId . "' title='Edit record'><i class='fa fa-pencil'></i></button>";
    $nestedData[] = "<button class='btn-erp-icon del-btn' onclick='deleteRoleUser(" . $userId . ")' title='Remove record'><i class='fa fa-trash'></i></button>";
    $data[] = $nestedData;
}

echo json_encode(array(
    'recordsTotal' => intval($totalData),
    'recordsFiltered' => intval($totalFiltered),
    'data' => $data
));
?>
