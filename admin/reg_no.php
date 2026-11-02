<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once "../database/db_connect.php";

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_session']) && empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['exists' => false, 'error' => 'Unauthorized']);
    exit;
}

$db_handle = new DBController();
$registerNo = trim($_POST['register1'] ?? '');

if ($registerNo === '') {
    echo json_encode([
        'exists' => false,
    ]);
    exit;
}

$stmt = mysqli_prepare($db_handle->conn, "SELECT fname, email, mobile FROM st_student_master WHERE registration_no = ? LIMIT 1");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 's', $registerNo);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && ($row = mysqli_fetch_assoc($result))) {
        mysqli_stmt_close($stmt);
        echo json_encode([
            'exists' => true,
            'fname' => $row['fname'] ?? '',
            'email' => $row['email'] ?? '',
            'mobile' => $row['mobile'] ?? '',
        ]);
        exit;
    }
    mysqli_stmt_close($stmt);
}

echo json_encode([
    'exists' => false,
]);
