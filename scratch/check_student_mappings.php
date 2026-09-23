<?php
require_once __DIR__ . '/../database/db_connect.php';
$db = new DBController();

echo "=== Student users in st_user_master (role_id = 5) ===\n";
$res = mysqli_query($db->conn, "SELECT u.user_id, u.user_name, u.role_id, u.student_id, s.fname, s.roll_no, s.registration_no, s.current_semester_id, s.department_id 
                                FROM st_user_master u 
                                LEFT JOIN st_student_master s ON s.student_id = u.student_id 
                                WHERE u.role_id = 5 LIMIT 10");
while ($r = mysqli_fetch_assoc($res)) {
    print_r($r);
}

echo "=== Direct check in st_student_master ===\n";
$res2 = mysqli_query($db->conn, "SELECT student_id, fname, roll_no, registration_no, current_semester_id, department_id, specialization_subject_id FROM st_student_master LIMIT 5");
while ($r = mysqli_fetch_assoc($res2)) {
    print_r($r);
}
