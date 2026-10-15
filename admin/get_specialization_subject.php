<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    require_once "../database/db_connect.php";
    $database = new DBController();

    $specialization_id = isset($_POST['specialization_id']) ? intval($_POST['specialization_id']) : (isset($_GET['specialization_id']) ? intval($_GET['specialization_id']) : 0);
    $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : (isset($_GET['department_id']) ? intval($_GET['department_id']) : 0);
    $semester_id = isset($_POST['semester_id']) ? intval($_POST['semester_id']) : (isset($_GET['semester_id']) ? intval($_GET['semester_id']) : 0);
    $track_id = isset($_POST['track_id']) ? intval($_POST['track_id']) : (isset($_POST['specialization_track_id']) ? intval($_POST['specialization_track_id']) : (isset($_GET['track_id']) ? intval($_GET['track_id']) : 0));

    if ($specialization_id <= 0 || $semester_id <= 0) {
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'data' => [],
            'error' => 'Specialization and semester must be selected.'
        ]);
        exit;
    }

    $subjects = $database->getEligibleSubjects($specialization_id, $semester_id, $department_id, $track_id);

    // If no subjects found via official mapping, fallback to check legacy subjects
    if (empty($subjects) && $specialization_id <= 4) {
        $legacySql = "SELECT subject_id, subject_name 
                      FROM st_specialization_subject_master 
                      WHERE specialization_id = ? AND is_active = 1 
                      ORDER BY subject_name";
        $lStmt = $database->conn->prepare($legacySql);
        if ($lStmt) {
            $lStmt->bind_param("i", $specialization_id);
            $lStmt->execute();
            $lRes = $lStmt->get_result();
            if ($lRes) {
                while ($lRow = $lRes->fetch_assoc()) {
                    $subjects[] = [
                        'subject_id' => intval($lRow['subject_id']),
                        'subject_code' => '',
                        'subject_name' => $lRow['subject_name']
                    ];
                }
            }
            $lStmt->close();
        }
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'data' => $subjects,
        'error' => null
    ]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'data' => [],
        'error' => $e->getMessage()
    ]);
}
exit;
