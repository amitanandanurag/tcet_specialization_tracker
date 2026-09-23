<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/../database/db_connect.php";

echo "Testing DBController...\n";
$db = new DBController();
if ($db->conn) {
    echo "DBController connected successfully to database: " . $db->database . "\n";
    $tablesRes = mysqli_query($db->conn, "SHOW TABLES");
    if ($tablesRes) {
        $tables = [];
        while ($row = mysqli_fetch_row($tablesRes)) {
            $tables[] = $row[0];
        }
        echo "Found " . count($tables) . " tables in database:\n";
        print_r($tables);
    } else {
        echo "SHOW TABLES failed: " . mysqli_error($db->conn) . "\n";
    }
} else {
    echo "DBController connection FAILED. Last error: " . $db->last_error . "\n";
    echo "Testing direct connection to MySQL server at localhost:3306 (user 'root', no password)...\n";
    $serverConn = @mysqli_connect('localhost', 'root', '');
    if (!$serverConn) {
        echo "Direct connection to MySQL server FAILED: " . mysqli_connect_error() . " (Code: " . mysqli_connect_errno() . ")\n";
        
        // Also check if MySQL service / port is reachable
        echo "Testing socket connection to 127.0.0.1:3306...\n";
        $fp = @fsockopen('127.0.0.1', 3306, $errno, $errstr, 2);
        if ($fp) {
            echo "Port 3306 is OPEN.\n";
            fclose($fp);
        } else {
            echo "Port 3306 is CLOSED or UNREACHABLE ($errstr, code $errno). MySQL service might NOT be running.\n";
        }
    } else {
        echo "Direct connection to MySQL server SUCCEEDED!\n";
        echo "Listing databases on server:\n";
        $dbRes = mysqli_query($serverConn, "SHOW DATABASES");
        while ($row = mysqli_fetch_row($dbRes)) {
            echo " - " . $row[0] . "\n";
        }
    }
}
