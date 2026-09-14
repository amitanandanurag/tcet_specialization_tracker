<?php
require_once __DIR__ . "/../database/db_connect.php";
$db = new DBController();
$conn = $db->conn;

echo "--- Student Semesters in st_student_master ---\n";
$res = mysqli_query($conn, "SELECT current_semester_id, COUNT(*) as c FROM st_student_master GROUP BY current_semester_id");
while ($r = mysqli_fetch_assoc($res)) {
    echo "Semester: " . var_export($r['current_semester_id'], true) . " -> Count: {$r['c']}\n";
}

echo "\n--- st_student_semester_history by semester ---\n";
$res2 = mysqli_query($conn, "SELECT semester_id, COUNT(*) as c FROM st_student_semester_history GROUP BY semester_id");
while ($r = mysqli_fetch_assoc($res2)) {
    echo "Semester: " . var_export($r['semester_id'], true) . " -> Count: {$r['c']}\n";
}

echo "\n--- Students missing history records ---\n";
$res3 = mysqli_query($conn, "SELECT s.student_id, s.fname, s.current_semester_id FROM st_student_master s LEFT JOIN st_student_semester_history h ON h.student_id = s.student_id WHERE h.student_id IS NULL LIMIT 10");
while ($r = mysqli_fetch_assoc($res3)) {
    echo "Missing: Student #{$r['student_id']} - {$r['fname']} (Sem: " . var_export($r['current_semester_id'], true) . ")\n";
}
