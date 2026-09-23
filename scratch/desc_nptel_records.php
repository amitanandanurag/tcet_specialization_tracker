<?php
require_once __DIR__ . '/../database/db_connect.php';
$db = new DBController();
$res = mysqli_query($db->conn, "DESC st_nptel_records");
while ($r = mysqli_fetch_assoc($res)) {
    print_r($r);
}
