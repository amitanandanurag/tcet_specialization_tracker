<?php
require_once __DIR__ . "/../database/db_connect.php";
$db = new DBController();
$conn = $db->conn;

echo "--- Schema of st_login ---\n";
$res = mysqli_query($conn, "DESCRIBE st_login");
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        echo "{$r['Field']} - {$r['Type']}\n";
    }
} else {
    echo "DESCRIBE st_login failed: " . mysqli_error($conn) . "\n";
}

echo "\n--- Data in st_login ---\n";
$res2 = mysqli_query($conn, "SELECT * FROM st_login LIMIT 5");
if ($res2) {
    while ($r = mysqli_fetch_assoc($res2)) {
        print_r($r);
    }
} else {
    echo "SELECT * FROM st_login failed: " . mysqli_error($conn) . "\n";
}

echo "\n--- Schema of st_student_master ---\n";
$res3 = mysqli_query($conn, "DESCRIBE st_student_master");
if ($res3) {
    while ($r = mysqli_fetch_assoc($res3)) {
        echo "{$r['Field']} - {$r['Type']}\n";
    }
} else {
    echo "DESCRIBE st_student_master failed: " . mysqli_error($conn) . "\n";
}

echo "\n--- Count in st_student_master ---\n";
$res4 = mysqli_query($conn, "SELECT COUNT(*) as c FROM st_student_master");
if ($res4) {
    $r = mysqli_fetch_assoc($res4);
    echo "Total students in st_student_master: " . $r['c'] . "\n";
} else {
    echo "SELECT COUNT failed: " . mysqli_error($conn) . "\n";
}

echo "\n--- Count in st_students ---\n";
$res5 = mysqli_query($conn, "SELECT COUNT(*) as c FROM st_students");
if ($res5) {
    $r = mysqli_fetch_assoc($res5);
    echo "Total in st_students: " . $r['c'] . "\n";
} else {
    echo "SELECT COUNT st_students failed: " . mysqli_error($conn) . "\n";
}
