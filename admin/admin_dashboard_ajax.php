<?php
session_start();
include_once("../database/db_connect.php");
$db_handle = new DBController();

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'get_rejected_students') {
        $spec_id = isset($_GET['spec_id']) ? intval($_GET['spec_id']) : 0;
        
        // DUMMY DATA FOR PREVIEW
        $data = [
            ['fname' => 'John', 'lname' => 'Doe', 'registration_no' => 'STU101', 'department_name' => 'CSE', 'rejection_reason' => 'Low CGPA'],
            ['fname' => 'Jane', 'lname' => 'Smith', 'registration_no' => 'STU102', 'department_name' => 'IT', 'rejection_reason' => 'Incomplete documents'],
            ['fname' => 'Raj', 'lname' => 'Kumar', 'registration_no' => 'STU103', 'department_name' => 'AIML', 'rejection_reason' => 'Prerequisite not met'],
            ['fname' => 'Simran', 'lname' => 'Kaur', 'registration_no' => 'STU104', 'department_name' => 'AIDS', 'rejection_reason' => 'Missed deadline'],
            ['fname' => 'Aditya', 'lname' => 'Verma', 'registration_no' => 'STU105', 'department_name' => 'Mechanical', 'rejection_reason' => 'Low Attendance'],
            ['fname' => 'Vikas', 'lname' => 'Rao', 'registration_no' => 'STU106', 'department_name' => 'Civil', 'rejection_reason' => 'Disciplinary issue']
        ];
        
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
?>