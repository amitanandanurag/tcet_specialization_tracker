<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../database/db_connect.php";
$database = new DBController();

// Handle AJAX request for loading minor subjects
if(isset($_POST['course_id']) && !empty($_POST['course_id'])) {
    $course_id = mysqli_real_escape_string($database->conn, $_POST['course_id']);
    
    $query = "SELECT subject_id, subject_name FROM st_minorsubject WHERE course_id = '$course_id' ORDER BY subject_name";
    $result = $database->conn->query($query);
    $data = array();
    
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $data[] = array(
                'subject_id' => $row['subject_id'],
                'subject_name' => $row['subject_name']
            );
        }
    }
    
    echo json_encode($data);
    exit;
}
?>