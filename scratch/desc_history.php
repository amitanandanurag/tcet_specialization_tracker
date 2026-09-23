<?php
require_once __DIR__ . "/../database/db_connect.php";
$db = new DBController();
$conn = $db->conn;

$res = mysqli_query($conn, "DESCRIBE st_student_semester_history");
while ($r = mysqli_fetch_assoc($res)) {
    echo "{$r['Field']} ({$r['Type']})\n";
}
