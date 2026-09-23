<?php
require_once __DIR__ . "/../database/db_connect.php";
$db = new DBController();
$conn = $db->conn;

$res = mysqli_query($conn, "SHOW TABLES");
$tables = [];
while ($r = mysqli_fetch_row($res)) {
    $tables[] = $r[0];
}

$working = [];
$broken = [];

foreach ($tables as $t) {
    $q = mysqli_query($conn, "SELECT 1 FROM `$t` LIMIT 1");
    if ($q) {
        $working[] = $t;
    } else {
        $broken[$t] = mysqli_error($conn);
    }
}

echo "Working tables (" . count($working) . "):\n";
print_r($working);

echo "\nBroken tables (" . count($broken) . "):\n";
print_r($broken);
