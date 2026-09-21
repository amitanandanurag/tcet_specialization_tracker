<?php
require "header/header.php";

if ((int)($usertype ?? 0) !== 1 && (int)($usertype ?? 0) !== 2 && (int)($usertype ?? 0) !== 3) {
    header("location: index.php");
    exit;
}

$successMsg = '';
$errorMsg = '';
$rowErrors = array();

// Sample CSV Download Handling
if (isset($_GET['download_sample'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sample_marks_upload.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, array('Registration No', 'Semester', 'Course Name', 'NPTEL Status', 'NPTEL Exam Score', 'NPTEL Assignment Raw', 'ISE 1', 'ISE 2', 'ESE Written', 'Remarks'));
    fputcsv($out, array('REG12345', '7', 'Technical Communication', 'Pass', '82.50', '', '', '', '', 'Passed exam'));
    fputcsv($out, array('REG67890', '7', 'Technical Communication', 'Fail', '', '75.00', '18.00', '16.50', '32.00', 'Failed exam, offline components entered'));
    fclose($out);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_csv'])) {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $errorMsg = 'Please select a valid CSV file to upload.';
    } else {
        $file = $_FILES['csv_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($ext !== 'csv') {
            $errorMsg = 'Only CSV file format is supported.';
        } else {
            $handle = fopen($file['tmp_name'], 'r');
            if ($handle !== false) {
                // Read and validate header row
                $headers = fgetcsv($handle);
                $expectedHeaders = array('Registration No', 'Semester', 'Course Name', 'NPTEL Status', 'NPTEL Exam Score', 'NPTEL Assignment Raw', 'ISE 1', 'ISE 2', 'ESE Written', 'Remarks');
                
                $headerValid = true;
                if (!$headers || count($headers) < count($expectedHeaders)) {
                    $headerValid = false;
                } else {
                    for ($i = 0; $i < count($expectedHeaders); $i++) {
                        if (trim(strtolower($headers[$i])) !== trim(strtolower($expectedHeaders[$i]))) {
                            $headerValid = false;
                            break;
                        }
                    }
                }
                
                if (!$headerValid) {
                    $errorMsg = 'Invalid CSV layout. The header row must exactly match: Registration No, Semester, Course Name, NPTEL Status, NPTEL Exam Score, NPTEL Assignment Raw, ISE 1, ISE 2, ESE Written, Remarks';
                    fclose($handle);
                } else {
                    $rowNo = 1; // 1 represents the header
                    $validData = array();
                    
                    while (($row = fgetcsv($handle)) !== false) {
                        $rowNo++;
                        // Skip empty rows
                        if (count($row) === 1 && empty(trim($row[0]))) {
                            continue;
                        }
                        
                        // Parse columns
                        $regNo = trim($row[0] ?? '');
                        $semInput = trim($row[1] ?? '');
                        $courseName = trim($row[2] ?? '');
                        $nptelStatus = trim($row[3] ?? '');
                        $nptelScoreRaw = trim($row[4] ?? '');
                        $nptelAssignRaw = trim($row[5] ?? '');
                        $ise1Raw = trim($row[6] ?? '');
                        $ise2Raw = trim($row[7] ?? '');
                        $eseRaw = trim($row[8] ?? '');
                        $remarks = trim($row[9] ?? '');
                        
                        $rowErrorsForThisRow = array();
                        
                        if ($regNo === '') {
                            $rowErrorsForThisRow[] = 'Registration number is required.';
                        }
                        if ($semInput === '' || !is_numeric($semInput)) {
                            $rowErrorsForThisRow[] = 'Valid Semester is required.';
                        }
                        if ($courseName === '') {
                            $rowErrorsForThisRow[] = 'Course name is required.';
                        }
                        if (strtolower($nptelStatus) !== 'pass' && strtolower($nptelStatus) !== 'fail') {
                            $rowErrorsForThisRow[] = 'NPTEL Status must be either "Pass" or "Fail".';
                        }
                        
                        // 1. Look up student
                        $studentId = 0;
                        $studentCurrentSemester = 0;
                        $studentAcadYear = 0;
                        $studentSpecId = 0;
                        
                        if ($regNo !== '') {
                            $stdSql = "SELECT student_id, current_semester_id, academic_year_id, specialization_id FROM st_student_master WHERE registration_no = ?";
                            $stdStmt = mysqli_prepare($db_handle->conn, $stdSql);
                            if ($stdStmt) {
                                mysqli_stmt_bind_param($stdStmt, 's', $regNo);
                                mysqli_stmt_execute($stdStmt);
                                $stdRes = mysqli_stmt_get_result($stdStmt);
                                if ($stdRes && $stdRow = mysqli_fetch_assoc($stdRes)) {
                                    $studentId = intval($stdRow['student_id']);
                                    $studentCurrentSemester = intval($stdRow['current_semester_id']);
                                    $studentAcadYear = intval($stdRow['academic_year_id']);
                                    $studentSpecId = intval($stdRow['specialization_id']);
                                } else {
                                    $rowErrorsForThisRow[] = "Student with Registration No '{$regNo}' not found.";
                                }
                                mysqli_stmt_close($stdStmt);
                            }
                        }
                        
                        // 2. Validate locks
                        $semesterId = intval($semInput);
                        if ($studentCurrentSemester > 0 && $semesterId < $studentCurrentSemester) {
                            $rowErrorsForThisRow[] = "Semester {$semesterId} is locked. Cannot enter marks for previous semesters (Student current semester: {$studentCurrentSemester}).";
                        }
                        
                        // 3. Validate subject existence and specialization mapping
                        if ($courseName !== '' && $studentSpecId > 0) {
                            $subSql = "SELECT subject_id, specialization_id FROM st_specialization_subject_master WHERE subject_name = ?";
                            $subStmt = mysqli_prepare($db_handle->conn, $subSql);
                            if ($subStmt) {
                                mysqli_stmt_bind_param($subStmt, 's', $courseName);
                                mysqli_stmt_execute($subStmt);
                                $subRes = mysqli_stmt_get_result($subStmt);
                                if ($subRes && $subRow = mysqli_fetch_assoc($subRes)) {
                                    $subSpecId = intval($subRow['specialization_id']);
                                    if ($subSpecId !== $studentSpecId) {
                                        $rowErrorsForThisRow[] = "Subject '{$courseName}' does not belong to the student's assigned specialization.";
                                    }
                                } else {
                                    $rowErrorsForThisRow[] = "Subject '{$courseName}' not found in Subject Master Data.";
                                }
                                mysqli_stmt_close($subStmt);
                            }
                        }
                        
                        // 4. Validate Marks
                        $nptelStatusNorm = (strtolower($nptelStatus) === 'pass') ? 'Pass' : 'Fail';
                        $nptelExamScore = null;
                        $nptelAssignmentRaw = null;
                        $nptelAssignmentConverted = null;
                        $ise1Marks = null;
                        $ise2Marks = null;
                        $eseWrittenMarks = null;
                        $collegeTotalScore = null;
                        $finalScore = null;
                        
                        if ($nptelStatusNorm === 'Pass') {
                            if ($nptelScoreRaw === '' || !is_numeric($nptelScoreRaw)) {
                                $rowErrorsForThisRow[] = 'NPTEL Exam Score is required and must be numeric for Pass status.';
                            } else {
                                $nptelExamScore = floatval($nptelScoreRaw);
                                if ($nptelExamScore < 0 || $nptelExamScore > 100) {
                                    $rowErrorsForThisRow[] = 'NPTEL Exam Score must be between 0 and 100.';
                                } else {
                                    $finalScore = $nptelExamScore;
                                }
                            }
                        } else {
                            // Fail case
                            if ($nptelAssignRaw === '' || !is_numeric($nptelAssignRaw) || 
                                $ise1Raw === '' || !is_numeric($ise1Raw) || 
                                $ise2Raw === '' || !is_numeric($ise2Raw) || 
                                $eseRaw === '' || !is_numeric($eseRaw)) {
                                $rowErrorsForThisRow[] = 'For failed NPTEL status, NPTEL Assignment Raw, ISE 1, ISE 2, and ESE Written marks are all required.';
                            } else {
                                $nptelAssignmentRaw = floatval($nptelAssignRaw);
                                $ise1Marks = floatval($ise1Raw);
                                $ise2Marks = floatval($ise2Raw);
                                $eseWrittenMarks = floatval($eseRaw);
                                
                                if ($nptelAssignmentRaw < 0 || $nptelAssignmentRaw > 100) {
                                    $rowErrorsForThisRow[] = 'NPTEL Assignment Raw must be between 0 and 100.';
                                }
                                if ($ise1Marks < 0 || $ise1Marks > 20) {
                                    $rowErrorsForThisRow[] = 'ISE 1 marks must be between 0 and 20.';
                                }
                                if ($ise2Marks < 0 || $ise2Marks > 20) {
                                    $rowErrorsForThisRow[] = 'ISE 2 marks must be between 0 and 20.';
                                }
                                if ($eseWrittenMarks < 0 || $eseWrittenMarks > 40) {
                                    $rowErrorsForThisRow[] = 'ESE Written marks must be between 0 and 40.';
                                }
                                
                                if (empty($rowErrorsForThisRow)) {
                                    $nptelAssignmentConverted = round(($nptelAssignmentRaw / 100) * 20, 2);
                                    $collegeTotalScore = round($ise1Marks + $ise2Marks + $nptelAssignmentConverted + $eseWrittenMarks, 2);
                                    $finalScore = $collegeTotalScore;
                                }
                            }
                        }
                        
                        if (!empty($rowErrorsForThisRow)) {
                            $rowErrors[$rowNo] = $rowErrorsForThisRow;
                        } else {
                            $validData[] = array(
                                'student_id' => $studentId,
                                'semester_id' => $semesterId,
                                'course_name' => $courseName,
                                'nptel_status' => $nptelStatusNorm,
                                'nptel_exam_score' => $nptelExamScore,
                                'nptel_assignment_raw' => $nptelAssignmentRaw,
                                'nptel_assignment_converted' => $nptelAssignmentConverted,
                                'ise1_marks' => $ise1Marks,
                                'ise2_marks' => $ise2Marks,
                                'ese_written_marks' => $eseWrittenMarks,
                                'college_total_score' => $collegeTotalScore,
                                'final_score' => $finalScore,
                                'remarks' => $remarks
                            );
                        }
                    }
                    fclose($handle);
                    
                    if (!empty($rowErrors)) {
                        $errorMsg = 'Failed to upload marks. There are validation errors in the uploaded file. No marks were saved.';
                    } elseif (empty($validData)) {
                        $errorMsg = 'The uploaded CSV file does not contain any valid data rows.';
                    } else {
                        // All rows valid! Perform transactional save
                        mysqli_begin_transaction($db_handle->conn);
                        $transactionOk = true;
                        
                        foreach ($validData as $d) {
                            $studentId = $d['student_id'];
                            $semesterId = $d['semester_id'];
                            $courseName = $d['course_name'];
                            
                            // Find enrollment ID
                            $enrollmentId = 0;
                            $enRes = mysqli_query($db_handle->conn, "SELECT enrollment_id FROM st_enrollment WHERE student_id = $studentId AND semester_id = $semesterId ORDER BY enrollment_id DESC LIMIT 1");
                            if ($enRes && $enRow = mysqli_fetch_assoc($enRes)) {
                                $enrollmentId = intval($enRow['enrollment_id']);
                            }
                            
                            $offlineFlag = ($d['nptel_status'] === 'Fail') ? 1 : 0;
                            $offlineScore = ($d['nptel_status'] === 'Fail') ? $d['college_total_score'] : null;
                            $offlineDate = ($d['nptel_status'] === 'Fail') ? date('Y-m-d') : null;
                            
                            // A. Check NPTEL record
                            $nptelCheckSql = "SELECT nptel_id FROM st_nptel_records WHERE student_id = ? AND semester_id = ? AND course_name = ? LIMIT 1";
                            $nptelCheckStmt = mysqli_prepare($db_handle->conn, $nptelCheckSql);
                            $existingNptelId = 0;
                            if ($nptelCheckStmt) {
                                mysqli_stmt_bind_param($nptelCheckStmt, 'iis', $studentId, $semesterId, $courseName);
                                mysqli_stmt_execute($nptelCheckStmt);
                                $nptelCheckResult = mysqli_stmt_get_result($nptelCheckStmt);
                                if ($nptelCheckResult && mysqli_num_rows($nptelCheckResult) > 0) {
                                    $nptelRow = mysqli_fetch_assoc($nptelCheckResult);
                                    $existingNptelId = intval($nptelRow['nptel_id'] ?? 0);
                                }
                                mysqli_stmt_close($nptelCheckStmt);
                            } else {
                                $transactionOk = false;
                                break;
                            }
                            
                            if ($existingNptelId > 0) {
                                $updateNptelSql = "UPDATE st_nptel_records
                                                   SET enrollment_id = ?, score = ?, pass_fail = ?, offline_exam_flag = ?, offline_exam_score = ?, offline_exam_date = ?
                                                   WHERE nptel_id = ?";
                                $updateNptelStmt = mysqli_prepare($db_handle->conn, $updateNptelSql);
                                if ($updateNptelStmt) {
                                    mysqli_stmt_bind_param($updateNptelStmt, 'idsidsi', $enrollmentId, $d['nptel_exam_score'], $d['nptel_status'], $offlineFlag, $offlineScore, $offlineDate, $existingNptelId);
                                    $transactionOk = $transactionOk && mysqli_stmt_execute($updateNptelStmt);
                                    mysqli_stmt_close($updateNptelStmt);
                                } else {
                                    $transactionOk = false;
                                    break;
                                }
                            } else {
                                $insertNptelSql = "INSERT INTO st_nptel_records (enrollment_id, student_id, semester_id, course_name, score, pass_fail, offline_exam_flag, offline_exam_score, offline_exam_date)
                                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                $insertNptelStmt = mysqli_prepare($db_handle->conn, $insertNptelSql);
                                if ($insertNptelStmt) {
                                    mysqli_stmt_bind_param($insertNptelStmt, 'iiisdsids', $enrollmentId, $studentId, $semesterId, $courseName, $d['nptel_exam_score'], $d['nptel_status'], $offlineFlag, $offlineScore, $offlineDate);
                                    $transactionOk = $transactionOk && mysqli_stmt_execute($insertNptelStmt);
                                    mysqli_stmt_close($insertNptelStmt);
                                } else {
                                    $transactionOk = false;
                                    break;
                                }
                            }
                            
                            // B. Save to st_offline_marks_entry
                            $saveOfflineSql = "INSERT INTO st_offline_marks_entry
                                (student_id, semester_id, course_name, nptel_status, nptel_exam_score, nptel_assignment_raw, nptel_assignment_converted, ise1_marks, ise2_marks, ese_written_marks, college_total_score, final_score, remarks, created_by, updated_by)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE
                                    nptel_status = VALUES(nptel_status),
                                    nptel_exam_score = VALUES(nptel_exam_score),
                                    nptel_assignment_raw = VALUES(nptel_assignment_raw),
                                    nptel_assignment_converted = VALUES(nptel_assignment_converted),
                                    ise1_marks = VALUES(ise1_marks),
                                    ise2_marks = VALUES(ise2_marks),
                                    ese_written_marks = VALUES(ese_written_marks),
                                    college_total_score = VALUES(college_total_score),
                                    final_score = VALUES(final_score),
                                    remarks = VALUES(remarks),
                                    updated_by = VALUES(updated_by)";
                            
                            $saveOfflineStmt = mysqli_prepare($db_handle->conn, $saveOfflineSql);
                            if ($saveOfflineStmt) {
                                $creatorId = intval($userid);
                                mysqli_stmt_bind_param(
                                    $saveOfflineStmt,
                                    'iissddddddddsii',
                                    $studentId,
                                    $semesterId,
                                    $courseName,
                                    $d['nptel_status'],
                                    $d['nptel_exam_score'],
                                    $d['nptel_assignment_raw'],
                                    $d['nptel_assignment_converted'],
                                    $d['ise1_marks'],
                                    $d['ise2_marks'],
                                    $d['ese_written_marks'],
                                    $d['college_total_score'],
                                    $d['final_score'],
                                    $d['remarks'],
                                    $creatorId,
                                    $creatorId
                                );
                                $transactionOk = $transactionOk && mysqli_stmt_execute($saveOfflineStmt);
                                mysqli_stmt_close($saveOfflineStmt);
                            } else {
                                $transactionOk = false;
                                break;
                            }
                        }
                        
                        if ($transactionOk) {
                            mysqli_commit($db_handle->conn);
                            $successMsg = 'Successfully imported marks for ' . count($validData) . ' students!';
                        } else {
                            mysqli_rollback($db_handle->conn);
                            $errorMsg = 'Error during database save. Upload rolled back.';
                        }
                    }
                }
            } else {
                $errorMsg = 'Failed to open the uploaded file.';
            }
        }
    }
}
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><i class="fa fa-file-excel-o"></i> Excel Marks Upload</h1>
        <ol class="breadcrumb">
            <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Excel Marks Upload</li>
        </ol>
    </section>

    <section class="content">
        <?php if ($successMsg !== ''): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="icon fa fa-check"></i> <?php echo htmlspecialchars($successMsg); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMsg !== ''): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="icon fa fa-ban"></i> <?php echo htmlspecialchars($errorMsg); ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title">Upload Marks CSV</h3>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="box-body">
                            <div class="form-group">
                                <label for="csv_file">Select CSV File</label>
                                <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                                <p class="help-block">Upload CSV file matching our required schema.</p>
                            </div>
                            <div style="margin-top: 15px;">
                                <a href="excel_marks_upload.php?download_sample=1" class="btn btn-default btn-sm">
                                    <i class="fa fa-download"></i> Download Sample CSV
                                </a>
                            </div>
                        </div>
                        <div class="box-footer">
                            <button type="submit" name="upload_csv" class="btn btn-success"><i class="fa fa-upload"></i> Upload & Import</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">Instructions & Validation Rules</h3>
                    </div>
                    <div class="box-body">
                        <ol style="padding-left: 18px;">
                            <li>Download the sample CSV template to ensure correct header formatting.</li>
                            <li><strong>Registration No</strong> must exist in the portal database.</li>
                            <li><strong>Semester</strong> must be the student's current or subsequent semester. Past semesters are locked.</li>
                            <li><strong>Course Name</strong> must be mapped as a valid specialization subject.</li>
                            <li><strong>NPTEL Status</strong> must be <em>Pass</em> or <em>Fail</em>.</li>
                            <li>If <em>Pass</em>, fill the <strong>NPTEL Exam Score</strong> (0 to 100).</li>
                            <li>If <em>Fail</em>, leave NPTEL Exam Score blank and fill:
                                <ul>
                                    <li>NPTEL Assignment Raw (0 to 100)</li>
                                    <li>ISE 1 (0 to 20)</li>
                                    <li>ISE 2 (0 to 20)</li>
                                    <li>ESE Written (0 to 40)</li>
                                </ul>
                            </li>
                            <li>Transactional Processing: If any row fails validation, no changes will be saved to the database.</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($rowErrors)): ?>
            <div class="row">
                <div class="col-xs-12">
                    <div class="box box-danger">
                        <div class="box-header with-border">
                            <h3 class="box-title"><i class="fa fa-warning"></i> Row-by-Row Validation Failures</h3>
                        </div>
                        <div class="box-body no-padding">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">Row Number</th>
                                        <th>Error Descriptions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rowErrors as $rnum => $errors): ?>
                                        <tr>
                                            <td><strong>Row <?php echo $rnum; ?></strong></td>
                                            <td>
                                                <ul style="margin: 0; padding-left: 18px; color: #dd4b39;">
                                                    <?php foreach ($errors as $err): ?>
                                                        <li><?php echo htmlspecialchars($err); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php include "header/footer.php"; ?>
