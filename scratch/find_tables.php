<?php
require_once __DIR__ . '/../database/db_connect.php';
$db = new DBController();

$res = mysqli_query($db->conn, "SHOW TABLES");
while ($r = mysqli_fetch_array($res)) {
    $t = $r[0];
    if (preg_match('/(nptel|cert|marks|minor|student)/i', $t)) {
        echo "Table: $t\n";
    }
}
