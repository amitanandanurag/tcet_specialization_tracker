<?php
require_once __DIR__ . "/../database/db_connect.php";
$db = new DBController();
$conn = $db->conn;

echo "Adding department_id and roll_no to st_student_semester_history...\n";
$q1 = mysqli_query($conn, "ALTER TABLE st_student_semester_history ADD COLUMN IF NOT EXISTS department_id INT(11) DEFAULT NULL AFTER semester_id");
if (!$q1) echo "Error q1: " . mysqli_error($conn) . "\n";

$q2 = mysqli_query($conn, "ALTER TABLE st_student_semester_history ADD COLUMN IF NOT EXISTS roll_no VARCHAR(50) DEFAULT NULL AFTER division_id");
if (!$q2) echo "Error q2: " . mysqli_error($conn) . "\n";

echo "Backfilling existing history records with department_id and roll_no from st_student_master...\n";
$q3 = mysqli_query($conn, "
    UPDATE st_student_semester_history h
    JOIN st_student_master s ON s.student_id = h.student_id
    SET h.department_id = COALESCE(h.department_id, s.department_id),
        h.roll_no = COALESCE(h.roll_no, s.roll_no)
");
if (!$q3) echo "Error q3: " . mysqli_error($conn) . "\n";

echo "Done!\n";
