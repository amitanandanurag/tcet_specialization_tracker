<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json');

require "../database/db_connect.php";
$db_handle = new DBController();

// Authentication and Authorization Check
if (empty($_SESSION['user_id']) || empty($_SESSION['role_id'])) {
    http_response_code(403);
    echo json_encode(['recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Unauthorized access. Please login.']);
    exit();
}

$userRole = intval($_SESSION['role_id']);
if ($userRole > 2) {
    http_response_code(403);
    echo json_encode(['recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Forbidden access for current role.']);
    exit();
}

$requestData = $_REQUEST;

$columns = array(
    0 => 'class_id',
    1 => 'class_name',
    2 => 'class_id',
    3 => 'class_id',
);

$sql = "SELECT class_id, class_name FROM st_class_master WHERE 1=1";

if (!empty($requestData['search']['value'])) {
    $search = mysqli_real_escape_string($db_handle->conn, trim($requestData['search']['value']));
    $sql .= " AND (class_id LIKE '" . $search . "%' OR class_name LIKE '%" . $search . "%')";
}

$totalRowsResult = $db_handle->conn->query("SELECT COUNT(*) AS total FROM st_class_master");
$totalData = $totalRowsResult ? intval($totalRowsResult->fetch_assoc()['total'] ?? 0) : 0;

$filteredResult = $db_handle->query($sql);
$totalFiltered = $filteredResult ? $filteredResult->num_rows : 0;

$orderColIdx = intval($requestData['order'][0]['column'] ?? 0);
$orderColumn = $columns[$orderColIdx] ?? 'class_id';
$orderDir = (isset($requestData['order'][0]['dir']) && strtolower($requestData['order'][0]['dir']) === 'desc') ? 'DESC' : 'ASC';
$start = max(0, intval($requestData['start'] ?? 0));
$length = max(1, min(100, intval($requestData['length'] ?? 10)));

$sql .= " ORDER BY " . $orderColumn . " " . $orderDir . " LIMIT " . $start . ", " . $length;
$result = $db_handle->query($sql);

$data = array();
$srNo = $start + 1;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $classId = intval($row["class_id"]);
        $className = htmlspecialchars($row["class_name"] ?? '', ENT_QUOTES, 'UTF-8');
        
        $nestedData = array();
        $nestedData[] = $srNo++;
        $nestedData[] = $className;
        $nestedData[] = "<a data-toggle='modal' data-target='#edit' data-id='" . $classId . "' id='class_edit'><button class='btn-erp-icon edit-btn' type='button' title='Edit'><i class='fa fa-pencil'></i></button></a>";
        $nestedData[] = "<a onclick='delete_class(" . $classId . ")'><button class='btn-erp-icon del-btn' type='button' title='Delete'><i class='fa fa-trash'></i></button></a>";
        $data[] = $nestedData;
    }
}

$json_data = array(
    "recordsTotal" => intval($totalData),
    "recordsFiltered" => intval($totalFiltered),
    "data" => $data
);

echo json_encode($json_data);
?>
