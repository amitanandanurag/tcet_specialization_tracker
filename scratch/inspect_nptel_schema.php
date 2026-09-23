<?php
require_once __DIR__ . '/../database/db_connect.php';
$db = new DBController();

$tables = ['st_minor_certificates', 'st_nptel_cancellations', 'st_offline_marks_entry', 'st_student_master'];
foreach ($tables as $t) {
    echo "=== Table: $t ===\n";
    $res = mysqli_query($db->conn, "DESC $t");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            echo "  {$row['Field']} ({$row['Type']}) Null:{$row['Null']} Key:{$row['Key']}\n";
        }
    } else {
        echo "  Table not found or error: " . mysqli_error($db->conn) . "\n";
    }
    echo "\n";
}
