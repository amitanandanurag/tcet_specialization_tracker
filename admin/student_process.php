<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "../database/db_connect.php";
$database = new DBController();

// Get current user ID and role from session
$userid = intval($_SESSION['user_id'] ?? $_SESSION['user_session'] ?? 0);
$userRole = intval($_SESSION['user_type'] ?? $_SESSION['role_id'] ?? 0);

if ($userid <= 0) {
    http_response_code(401);
    die("Unauthorized: Please log in to perform this operation.");
}

// Only Super Admin (1), Admin (2), Coordinator (3), and Student (5) can submit admission forms
if (!in_array($userRole, [1, 2, 3, 5], true)) {
    http_response_code(403);
    die("Forbidden: Your role is not authorized to submit admissions.");
}

if (isset($_POST['save'])) {
    if (!DBController::validateCsrfToken()) {
        $_SESSION['student_admission_error'] = 'Invalid or expired security token. Please try again.';
        header('Location: student_admission.php');
        exit();
    }
    $conn = $database->conn;

    $_SESSION['student_admission_form'] = [
        'academic' => $_POST['academic'] ?? '',
        'registration_no' => $_POST['registration_no'] ?? '',
        'roll_no' => $_POST['roll_no'] ?? '',
        'class' => $_POST['class'] ?? '',
        'current_semester_id' => $_POST['current_semester_id'] ?? '',
        'batch' => $_POST['batch'] ?? '',
        'graduation_year' => $_POST['graduation_year'] ?? '',
        'department_id' => $_POST['department_id'] ?? '',
        'specialization_id' => $_POST['specialization_id'] ?? '',
        'cgpa' => $_POST['cgpa'] ?? '',
        'minor_course_id' => $_POST['minor_course_id'] ?? '',
        'minor_subject_id' => $_POST['minor_subject_id'] ?? '',
        'minor_cgpa' => $_POST['minor_cgpa'] ?? '',
        'unaided_subject' => $_POST['unaided_subject'] ?? '',
        'fname' => $_POST['fname'] ?? '',
        'email' => $_POST['email'] ?? '',
        'mobile' => $_POST['mobile'] ?? '',
    ];

    // Get form data
    $ayValId = intval($_POST['academic'] ?? 0);
    $ayCheck = mysqli_query($conn, "SELECT session_id FROM st_session_master WHERE session_id = $ayValId LIMIT 1");
    if (!$ayCheck || mysqli_num_rows($ayCheck) === 0) {
        echo '<script type="text/javascript">alert("Invalid or missing Academic Year selected.");</script>';
        echo "<script>window.open('student_admission.php','_self')</script>";
        exit;
    }
    $academic_year_id = strval($ayValId);
    $registration_no = mysqli_real_escape_string($conn, $_POST['registration_no'] ?? '');
    $class_id = mysqli_real_escape_string($conn, $_POST['class'] ?? '');
    $division_id = mysqli_real_escape_string($conn, $_POST['batch'] ?? '');
    $current_semester_id = mysqli_real_escape_string($conn, $_POST['current_semester_id'] ?? '');
    $fname = mysqli_real_escape_string($conn, $_POST['fname'] ?? '');
    
    // FIXED: Handle grad_year properly
    $grad_year = 'NULL';
    if (!empty($_POST['graduation_year']) && $_POST['graduation_year'] != 'Select Year' && $_POST['graduation_year'] != '') {
        // If it's numeric, use as is
        if (is_numeric($_POST['graduation_year'])) {
            $grad_year = $_POST['graduation_year'];
        } else {
            // If it's not numeric, try to extract year or use as string (but your DB expects INT)
            // Better to lookup the actual year from a reference table
            $grad_year = 0; // Default value
        }
    }
    
    // Convert to proper SQL value (NULL or the number)
    $grad_year_sql = ($grad_year !== 'NULL' && $grad_year > 0) ? $grad_year : 'NULL';
    
    // Handle roll_no
    $roll_no = 'NULL';
    if (!empty($_POST['roll_no'])) {
        $roll_no = "'" . mysqli_real_escape_string($conn, $_POST['roll_no']) . "'";
    }
    
    $_SESSION['student_admission_form']['specialization_track_id'] = $_POST['specialization_track_id'] ?? '';

    // Handle department_id
    $department_id = 'NULL';
    if (!empty($_POST['department_id']) && $_POST['department_id'] != 'Select Department' && is_numeric($_POST['department_id'])) {
        $department_id = "'" . mysqli_real_escape_string($conn, $_POST['department_id']) . "'";
    }

    $track_id = intval($_POST['specialization_track_id'] ?? 0);
    $spec_id = intval($_POST['specialization_id'] ?? 0);
    $dept_id = intval($_POST['department_id'] ?? 0);
    $sem_id = intval($_POST['current_semester_id'] ?? 0);

    // Check FY restriction
    $c_check = mysqli_query($conn, "SELECT class_name FROM st_class_master WHERE class_id = " . intval($_POST['class'] ?? 0) . " LIMIT 1");
    if ($c_check && ($c_row = mysqli_fetch_assoc($c_check))) {
        $c_name = strtoupper($c_row['class_name'] ?? '');
        if ($c_name === 'FY' || $c_name === 'FE') {
            echo '<script type="text/javascript">alert("Error: Specialization is not offered for First Year (FY) students.");</script>';
            echo "<script>window.open('student_admission.php','_self')</script>";
            exit;
        }
    }

    // Fallback if legacy form submission
    if ($track_id <= 0 && $spec_id > 0 && $spec_id <= 4) {
        $track_id = $spec_id;
    }

    if ($track_id <= 0) {
        echo '<script type="text/javascript">alert("Error: Specialization Track is required.");</script>';
        echo "<script>window.open('student_admission.php','_self')</script>";
        exit;
    }

    if ($track_id === 2) {
        $subClass = intval($_POST['class'] ?? 0);
        $subSem = intval($_POST['current_semester_id'] ?? 0);
        $subDept = intval($_POST['department_id'] ?? 0);
        if (!$database->isTrackEligible(2, $subClass, $subSem, $subDept)) {
            echo '<script type="text/javascript">alert("Error: Honours with Research is available only to BE students in Semester 7 and Semester 8.");</script>';
            echo "<script>window.open('student_admission.php','_self')</script>";
            exit;
        }
    }

    $specialization_track_id_sql = $track_id;
    $specialization_id = 'NULL';
    $specialization_subject_id = 'NULL';
    $cgpa = 'NULL';
    $minor_course_id = 'NULL';
    $minor_subject_id = 'NULL';

    if ($track_id === 4) {
        // Minor Multidisciplinary
        $specialization_id = 4;
        if (empty($_POST['minor_course_id']) || !is_numeric($_POST['minor_course_id'])) {
            echo '<script type="text/javascript">alert("Error: Minor Course is required for Minor Multidisciplinary.");</script>';
            echo "<script>window.open('student_admission.php','_self')</script>";
            exit;
        }
        if (empty($_POST['minor_subject_id']) || !is_numeric($_POST['minor_subject_id'])) {
            echo '<script type="text/javascript">alert("Error: Minor Subject is required for Minor Multidisciplinary.");</script>';
            echo "<script>window.open('student_admission.php','_self')</script>";
            exit;
        }
        $mc_id = intval($_POST['minor_course_id']);
        $ms_id = intval($_POST['minor_subject_id']);

        $ms_check = mysqli_query($conn, "SELECT subject_id FROM st_minorsubject WHERE subject_id = $ms_id AND course_id = $mc_id LIMIT 1");
        if (!$ms_check || mysqli_num_rows($ms_check) === 0) {
            echo '<script type="text/javascript">alert("Error: Selected Minor Subject does not belong to the selected Minor Course.");</script>';
            echo "<script>window.open('student_admission.php','_self')</script>";
            exit;
        }
        $minor_course_id = $mc_id;
        $minor_subject_id = $ms_id;

        if (empty($_POST['minor_cgpa']) || !is_numeric($_POST['minor_cgpa'])) {
            echo '<script type="text/javascript">alert("Error: Minor CGPA is required.");</script>';
            echo "<script>window.open('student_admission.php','_self')</script>";
            exit;
        }
        $cgpa_val = floatval($_POST['minor_cgpa']);
        if ($cgpa_val < 0 || $cgpa_val > 10) {
            echo '<script type="text/javascript">alert("Error: CGPA must be between 0 and 10.");</script>';
            echo "<script>window.open('student_admission.php','_self')</script>";
            exit;
        }
        $cgpa = "'" . number_format($cgpa_val, 2) . "'";

    } elseif ($track_id === 1 || $track_id === 2) {
        // Honours Degree (1) / Honours with Research (2)
        if ($spec_id <= 0) {
            echo '<script type="text/javascript">alert("Error: Specialization is required for Honours.");</script>';
            echo "<script>window.open('student_admission.php','_self')</script>";
            exit;
        }

        if (!$database->isDepartmentEligibleForSpecialization($dept_id, $spec_id, $track_id)) {
            echo '<script type="text/javascript">alert("Error: Selected Specialization is not eligible for your department under Honours Degree.");</script>';
            echo "<script>window.open('student_admission.php','_self')</script>";
            exit;
        }
        $specialization_id = $spec_id;

        if (empty($_POST['cgpa']) || !is_numeric($_POST['cgpa'])) {
            echo '<script type="text/javascript">alert("Error: CGPA is required for Honours.");</script>';
            echo "<script>window.open('student_admission.php','_self')</script>";
            exit;
        }
        $cgpa_val = floatval($_POST['cgpa']);
        if ($cgpa_val < 7.5) {
            echo '<script type="text/javascript">alert("Error: Student is not eligible for Honours. Minimum CGPA must be 7.5.");</script>';
            echo "<script>window.open('student_admission.php','_self')</script>";
            exit;
        }
        $cgpa = "'" . number_format($cgpa_val, 2) . "'";

        if (!empty($_POST['unaided_subject']) && is_numeric($_POST['unaided_subject'])) {
            $sub_id = intval($_POST['unaided_subject']);
            if (!$database->isSubjectAvailable($sub_id, $dept_id, $sem_id, $spec_id, $track_id)) {
                echo '<script type="text/javascript">alert("Error: Selected subject is not active for this specialization, department, and semester.");</script>';
                echo "<script>window.open('student_admission.php','_self')</script>";
                exit;
            }
            $specialization_subject_id = $sub_id;
        }

    } elseif ($track_id === 3) {
        // Minor Degree (3)
        if ($spec_id <= 0) {
            echo '<script type="text/javascript">alert("Error: Specialization is required for Minor Degree.");</script>';
            echo "<script>window.open('student_admission.php','_self')</script>";
            exit;
        }

        if (!$database->isDepartmentEligibleForSpecialization($dept_id, $spec_id, $track_id)) {
            echo '<script type="text/javascript">alert("Error: Selected Specialization is not eligible for your department under Minor Degree.");</script>';
            echo "<script>window.open('student_admission.php','_self')</script>";
            exit;
        }
        $specialization_id = $spec_id;

        if (empty($_POST['cgpa']) || !is_numeric($_POST['cgpa'])) {
            echo '<script type="text/javascript">alert("Error: CGPA is required for Minor Degree.");</script>';
            echo "<script>window.open('student_admission.php','_self')</script>";
            exit;
        }
        $cgpa_val = floatval($_POST['cgpa']);
        if ($cgpa_val < 0 || $cgpa_val > 10) {
            echo '<script type="text/javascript">alert("Error: CGPA must be between 0 and 10.");</script>';
            echo "<script>window.open('student_admission.php','_self')</script>";
            exit;
        }
        $cgpa = "'" . number_format($cgpa_val, 2) . "'";

        if (!empty($_POST['unaided_subject']) && is_numeric($_POST['unaided_subject'])) {
            $sub_id = intval($_POST['unaided_subject']);
            if (!$database->isSubjectAvailable($sub_id, $dept_id, $sem_id, $spec_id, $track_id)) {
                echo '<script type="text/javascript">alert("Error: Selected subject is not active for this specialization, department, and semester.");</script>';
                echo "<script>window.open('student_admission.php','_self')</script>";
                exit;
            }
            $specialization_subject_id = $sub_id;
        }
    }


    // Handle research components
    $research_component_i_id = 'NULL';
    if (!empty($_POST['research_component_i_id']) && is_numeric($_POST['research_component_i_id'])) {
        $research_component_i_id = intval($_POST['research_component_i_id']);
    }
    
    $research_core_vii = 'NULL';
    if (!empty($_POST['research_core_vii'])) {
        $research_core_vii = "'" . mysqli_real_escape_string($conn, $_POST['research_core_vii']) . "'";
    }
    
    $research_component_ii_id = 'NULL';
    if (!empty($_POST['research_component_ii_id']) && is_numeric($_POST['research_component_ii_id'])) {
        $research_component_ii_id = intval($_POST['research_component_ii_id']);
    }
    
    $research_core_viii = 'NULL';
    if (!empty($_POST['research_core_viii'])) {
        $research_core_viii = "'" . mysqli_real_escape_string($conn, $_POST['research_core_viii']) . "'";
    }
    
    // Handle mobile
    $mobile = 'NULL';
    if (!empty($_POST['mobile'])) {
        $mobile = "'" . mysqli_real_escape_string($conn, $_POST['mobile']) . "'";
    }
    
    // Handle email
    $email = 'NULL';
    if (!empty($_POST['email'])) {
        $email = "'" . mysqli_real_escape_string($conn, $_POST['email']) . "'";
    }
    
    // Handle file uploads
    $upload_dir = "uploads/marklists/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $mark_list_files = [];
    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
    $max_file_size = 5 * 1024 * 1024; // 5MB limit
    
    // Accept any uploaded file whose input name starts with 'mark-list'
    $semester_fields = [];
    foreach (array_keys($_FILES) as $f) {
        if (strpos($f, 'mark-list') === 0) {
            $semester_fields[] = $f;
        }
    }

    foreach ($semester_fields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] == UPLOAD_ERR_OK && !empty($_FILES[$field]['name'])) {
            $file_ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            $file_size = intval($_FILES[$field]['size'] ?? 0);
            
            if (in_array($file_ext, $allowed_extensions, true) && $file_size > 0 && $file_size <= $max_file_size) {
                $safe_reg_no = preg_replace('/[^a-zA-Z0-9]/', '_', $registration_no);
                $file_name = time() . '_' . $safe_reg_no . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $field) . '.' . $file_ext;
                $target_path = $upload_dir . $file_name;

                if (move_uploaded_file($_FILES[$field]['tmp_name'], $target_path)) {
                    $mark_list_files[] = $file_name;
                }
            }
        }
    }
    
    $mark_list = !empty($mark_list_files) ? "'" . implode(',', $mark_list_files) . "'" : 'NULL';
    
    // Determine if this is an update to an existing student record
    $existingStudentId = 0;
    $editStudentId = intval($_POST['edit_student_id'] ?? 0);

    if ($userRole === 5) {
        // For student self-admission (role 5), look up their own linked student record
        $checkUserSql = "SELECT student_id FROM st_user_master WHERE user_id = ? AND role_id = 5 LIMIT 1";
        $checkUserStmt = mysqli_prepare($conn, $checkUserSql);
        if ($checkUserStmt) {
            mysqli_stmt_bind_param($checkUserStmt, "i", $userid);
            mysqli_stmt_execute($checkUserStmt);
            $checkUserResult = mysqli_stmt_get_result($checkUserStmt);
            if ($checkUserResult && ($userRow = mysqli_fetch_assoc($checkUserResult))) {
                $existingStudentId = intval($userRow['student_id']);
            }
            mysqli_stmt_close($checkUserStmt);
        }
    } elseif ($editStudentId > 0 && in_array($userRole, [1, 2, 3], true)) {
        // For admin/coordinator, only treat as an update if an explicit edit ID is provided
        $checkExistStmt = mysqli_prepare($conn, "SELECT student_id FROM st_student_master WHERE student_id = ? LIMIT 1");
        if ($checkExistStmt) {
            mysqli_stmt_bind_param($checkExistStmt, "i", $editStudentId);
            mysqli_stmt_execute($checkExistStmt);
            $checkExistRes = mysqli_stmt_get_result($checkExistStmt);
            if ($checkExistRes && mysqli_num_rows($checkExistRes) > 0) {
                $existingStudentId = $editStudentId;
            }
            mysqli_stmt_close($checkExistStmt);
        }
    }

    // Semester marks data
    $m_sem1 = "'[]'";
    $m_sem2 = "'[]'";
    $m_sem3 = "'[]'";
    $status = 1;
    
    // Check if registration number exists
    $regNoVal = trim($registration_no);
    $check_sql = "SELECT student_id FROM st_student_master WHERE registration_no = ?";
    if ($existingStudentId > 0) {
        $check_sql .= " AND student_id != " . intval($existingStudentId);
    }
    $checkStmt = mysqli_prepare($conn, $check_sql);
    if ($checkStmt) {
        mysqli_stmt_bind_param($checkStmt, "s", $regNoVal);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            mysqli_stmt_close($checkStmt);
            echo '<script type="text/javascript">alert("Registration number already exists!");</script>';
            echo "<script>window.open('student_admission.php','_self')</script>";
            exit;
        }
        mysqli_stmt_close($checkStmt);
    }

    if ($specialization_subject_id !== 'NULL' && !$database->isSubjectAvailable(
        intval(trim($specialization_subject_id, "'")), intval($_POST['department_id'] ?? 0),
        intval($current_semester_id), intval($specialization_id), $track_id)) {
        echo 'Selected specialization subject is not active for this department and semester.';
        exit;
    }

    // Prepare values for database operations
    $ayIdVal = intval($academic_year_id);
    $classIdVal = intval($class_id);
    $divisionIdVal = intval($division_id);
    $gradYearVal = ($grad_year !== 'NULL' && intval($grad_year) > 0) ? intval($grad_year) : null;
    $rollNoVal = ($roll_no !== 'NULL') ? trim($_POST['roll_no']) : null;
    $deptIdVal = ($department_id !== 'NULL' && intval($_POST['department_id']) > 0) ? intval($_POST['department_id']) : null;
    $trackIdVal = ($track_id > 0) ? $track_id : null;
    $specIdVal = ($specialization_id !== 'NULL' && intval($specialization_id) > 0) ? intval($specialization_id) : null;
    $specSubIdVal = ($specialization_subject_id !== 'NULL' && intval($specialization_subject_id) > 0) ? intval($specialization_subject_id) : null;
    $minorCourseIdVal = ($minor_course_id !== 'NULL' && intval($minor_course_id) > 0) ? intval($minor_course_id) : null;
    $minorSubIdVal = ($minor_subject_id !== 'NULL' && intval($minor_subject_id) > 0) ? intval($minor_subject_id) : null;
    $cgpaVal = ($cgpa !== 'NULL') ? floatval(trim($cgpa, "'")) : null;
    $fnameVal = trim($fname);
    $mobileVal = ($mobile !== 'NULL') ? trim($_POST['mobile']) : null;
    $emailVal = ($email !== 'NULL') ? trim($_POST['email']) : null;
    $markListVal = !empty($mark_list_files) ? implode(',', $mark_list_files) : null;
    $statusVal = 1;
    $currSemVal = intval($current_semester_id);

    // If this is a promotion/edit of an existing student, resolve old semester
    $previousSemesterId = 0;
    if ($existingStudentId > 0) {
        $oldResult = mysqli_query($conn, "SELECT current_semester_id FROM st_student_master WHERE student_id = " . intval($existingStudentId) . " LIMIT 1");
        if ($oldResult && ($oldRow = mysqli_fetch_assoc($oldResult))) {
            $previousSemesterId = intval($oldRow['current_semester_id']);
        }
    }

    // Begin multi-table admission transaction
    mysqli_begin_transaction($conn);
    $transactionActive = true;

    try {
        if ($existingStudentId > 0) {
            // Preserve previous semester snapshot if semester is changing
            if ($previousSemesterId > 0 && $previousSemesterId !== $currSemVal) {
                $historyPrepared = $database->syncStudentSemesterHistory($existingStudentId, $previousSemesterId);
                $historyPrepared = $historyPrepared && $database->finalizeStudentSemester($existingStudentId, $previousSemesterId);
                if (!$historyPrepared) {
                    throw new Exception("Unable to preserve the previous semester history snapshot. Promotion cancelled.", 500);
                }
            }

            // Prepared UPDATE on st_student_master (without non-existent research columns)
            $upSql = "UPDATE `st_student_master` SET
                `academic_year_id` = ?,
                `registration_no` = ?,
                `class_id` = ?,
                `division_id` = ?,
                `grad_year` = ?,
                `roll_no` = ?,
                `department_id` = ?,
                `specialization_track_id` = ?,
                `specialization_id` = ?,
                `specialization_subject_id` = ?,
                `minor_course_id` = ?,
                `minor_subject_id` = ?,
                `cgpa` = ?,
                `fname` = ?,
                `mobile` = ?,
                `email` = ?,
                `mark_list` = ?,
                `status` = ?,
                `current_semester_id` = ?
                WHERE `student_id` = ?";
            $upStmt = mysqli_prepare($conn, $upSql);
            if (!$upStmt) {
                throw new Exception("Prepare failed for student update: " . mysqli_error($conn), mysqli_errno($conn));
            }
            $upParams = [
                $ayIdVal, $regNoVal, $classIdVal, $divisionIdVal, $gradYearVal, $rollNoVal,
                $deptIdVal, $trackIdVal, $specIdVal, $specSubIdVal, $minorCourseIdVal, $minorSubIdVal,
                $cgpaVal, $fnameVal, $mobileVal, $emailVal, $markListVal, $statusVal, $currSemVal,
                $existingStudentId
            ];
            if (!$upStmt->execute($upParams)) {
                $upErr = $upStmt->error;
                $upStmt->close();
                throw new Exception("Failed updating student record: " . $upErr, mysqli_errno($conn));
            }
            $upStmt->close();
            $student_id = $existingStudentId;
        } else {
            // Prepared INSERT into st_student_master (without non-existent research columns)
            $insSql = "INSERT INTO `st_student_master`(
                `academic_year_id`,
                `registration_no`,
                `class_id`,
                `division_id`,
                `grad_year`,
                `roll_no`,
                `department_id`,
                `specialization_track_id`,
                `specialization_id`,
                `specialization_subject_id`,
                `minor_course_id`,
                `minor_subject_id`,
                `cgpa`,
                `fname`,
                `mobile`,
                `email`,
                `mark_list`,
                `status`,
                `m_sem1`,
                `m_sem2`,
                `m_sem3`,
                `created_at`,
                `current_semester_id`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '[]', '[]', '[]', NOW(), ?)";
            $insStmt = mysqli_prepare($conn, $insSql);
            if (!$insStmt) {
                throw new Exception("Prepare failed for student insert: " . mysqli_error($conn), mysqli_errno($conn));
            }
            $insParams = [
                $ayIdVal, $regNoVal, $classIdVal, $divisionIdVal, $gradYearVal, $rollNoVal,
                $deptIdVal, $trackIdVal, $specIdVal, $specSubIdVal, $minorCourseIdVal, $minorSubIdVal,
                $cgpaVal, $fnameVal, $mobileVal, $emailVal, $markListVal, $statusVal, $currSemVal
            ];
            if (!$insStmt->execute($insParams)) {
                $insErr = $insStmt->error;
                $insStmt->close();
                throw new Exception("Failed inserting student record: " . $insErr, mysqli_errno($conn));
            }
            $student_id = mysqli_insert_id($conn);
            $insStmt->close();
        }

        // 1. Related table: Semester History sync
        $database->removeInvalidStudentMentorAllocation($student_id);
        $database->syncStudentSemesterHistory($student_id, $currSemVal);

        // 2. Related table: Research components in semester history (Honours with Research, Track 2)
        if ($trackIdVal === 2) {
            $upHistResSql = "UPDATE st_student_semester_history SET
                research_component_i_id = ?,
                research_core_vii = ?,
                research_component_ii_id = ?,
                research_core_viii = ?
                WHERE student_id = ? AND semester_id = ?";
            $upHistResStmt = mysqli_prepare($conn, $upHistResSql);
            if ($upHistResStmt) {
                $rComp1 = ($research_component_i_id !== 'NULL') ? intval($research_component_i_id) : null;
                $rCore7 = ($research_core_vii !== 'NULL') ? trim($research_core_vii, "'") : null;
                $rComp2 = ($research_component_ii_id !== 'NULL') ? intval($research_component_ii_id) : null;
                $rCore8 = ($research_core_viii !== 'NULL') ? trim($research_core_viii, "'") : null;
                $upHistResStmt->execute([$rComp1, $rCore7, $rComp2, $rCore8, $student_id, $currSemVal]);
                $upHistResStmt->close();
            }
        }

        // 3. Related table: Mentor auto-allocation
        if ($specSubIdVal !== null && $specSubIdVal > 0) {
            $database->autoAllocateMentor($student_id, $specSubIdVal, $currSemVal);
            $database->syncStudentSemesterHistory($student_id, $currSemVal);
        }

        // 4. Related table: st_enrollment
        if ($specIdVal !== null && $specIdVal > 0) {
            require_once __DIR__ . "/../database/enrollment_service.php";
            $enChk = mysqli_query($conn, "SELECT enrollment_id FROM st_enrollment WHERE student_id = $student_id AND semester_id = $currSemVal AND specialization_id = $specIdVal LIMIT 1");
            if (!$enChk || mysqli_num_rows($enChk) === 0) {
                EnrollmentService::createEnrollment($conn, $student_id, $specIdVal, $currSemVal, $ayIdVal, 'Active');
            }
        }

        // 5. User account linking
        if ($userRole === 5 && !empty($userid)) {
            $updateUserSql = "UPDATE st_user_master SET student_id = ? WHERE user_id = ? AND role_id = 5";
            $updateUserStmt = mysqli_prepare($conn, $updateUserSql);
            if ($updateUserStmt) {
                $updateUserStmt->execute([$student_id, $userid]);
                $updateUserStmt->close();
            }
        } elseif (!empty($_POST['email'])) {
            $studentEmail = trim($_POST['email']);
            $linkStudentUserStmt = mysqli_prepare($conn, "UPDATE st_user_master SET student_id = ? WHERE email_id = ? AND role_id = 5 AND (student_id = 0 OR student_id IS NULL)");
            if ($linkStudentUserStmt) {
                $linkStudentUserStmt->execute([$student_id, $studentEmail]);
                $linkStudentUserStmt->close();
            }
        }

        // Commit all operations atomically
        mysqli_commit($conn);
        $transactionActive = false;

        // Diagnostic audit logging
        if (method_exists($database, 'writeAuditLog')) {
            $actionType = ($existingStudentId > 0) ? 'STUDENT_ADMISSION_UPDATED' : 'STUDENT_ADMISSION_CREATED';
            $database->writeAuditLog($userid, $actionType, 'st_student_master', $student_id, "Student admission successfully processed for {$fnameVal} (Reg: {$regNoVal})");
        }

        $_SESSION['student_admission_success'] = 'Student admitted successfully! Student ID: ' . $student_id;
        $_SESSION['viewed_student_id'] = $student_id;
        unset($_SESSION['student_admission_form']);

        echo '<script type="text/javascript">alert("Student admitted successfully! Student ID: ' . $student_id . '");</script>';
        echo "<script>window.open('student_admission_view.php?id=" . $student_id . "','_self')</script>";
        exit;

    } catch (Throwable $e) {
        if ($transactionActive) {
            mysqli_rollback($conn);
        }

        $errCode = $e->getCode();
        $errMsg = $e->getMessage();
        $sqlState = mysqli_sqlstate($conn) ?: 'HY000';

        // Diagnostic logging (sanitized, no sensitive student info or passwords)
        error_log("[STUDENT_ADMISSION_FAILURE] Timestamp: " . date('Y-m-d H:i:s') . " | User ID: {$userid} | Operation: Student Admission | ErrCode: {$errCode} | SQLSTATE: {$sqlState} | Error: {$errMsg}");

        if (method_exists($database, 'writeAuditLog')) {
            $sanitizedDesc = "Student admission failed for registration_no: {$regNoVal}. Reason: " . substr($errMsg, 0, 150);
            $database->writeAuditLog($userid, 'STUDENT_ADMISSION_FAILED', 'st_student_master', null, $sanitizedDesc);
        }

        // Clean user-facing validation message
        $userFriendlyMsg = "Unable to save student admission record. Please check your inputs and try again.";
        if (strpos($errMsg, 'Duplicate') !== false || strpos($errMsg, 'already exists') !== false || $errCode == 1062) {
            $userFriendlyMsg = "A student with this Registration Number or ERP ID already exists in the system.";
        } elseif (strpos($errMsg, 'Specialization') !== false || strpos($errMsg, 'subject') !== false) {
            $userFriendlyMsg = $errMsg;
        }

        echo '<script type="text/javascript">alert("Error: ' . addslashes($userFriendlyMsg) . '");</script>';
        echo "<script>window.open('student_admission.php','_self')</script>";
        exit;
    }
    exit;
}
?>