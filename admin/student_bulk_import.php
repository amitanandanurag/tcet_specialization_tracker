<?php
/**
 * TCET Specialization Tracker — Centralized Excel Mass Student Upload Module
 * Industrial-grade administrative import interface with:
 * - Direct spreadsheet parsing (.xlsx, .xls, .csv)
 * - Anti-CSRF and strict Super Admin RBAC protection
 * - Staged 4-category validation preview (Valid, Duplicate, Missing, Invalid)
 * - Downloadable error reports and official template
 * - Idempotent, atomic transactional database insertion
 * - Automatic student login credential creation (st_user_master & st_login)
 */

if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    session_start();
}

require_once __DIR__ . '/../database/db_connect.php';
require_once __DIR__ . '/../database/student_bulk_import_service.php';

$db_handle = new DBController();
$conn = $db_handle->conn;

// 1. Template Download Handler (Early exit before header HTML)
if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
    $templatePath = __DIR__ . '/../STUDENT_EXCEL_IMPORT_TEMPLATE.xlsx';
    if (!file_exists($templatePath)) {
        require_once __DIR__ . '/../tests/generate_template_and_design.php';
    }
    if (file_exists($templatePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="TCET_Student_Import_Template.xlsx"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($templatePath));
        readfile($templatePath);
        exit;
    } else {
        die("Error: Template file could not be generated.");
    }
}

// 2. Download Validation / Error Report Handler
if (isset($_GET['action']) && $_GET['action'] === 'download_errors') {
    $staged = $_SESSION['bulk_import_staged'] ?? null;
    if ($staged) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Student_Import_Validation_Report_' . date('Ymd_His') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Category', 'Row Number', 'ERP Registration ID', 'Student Full Name', 'Department', 'Class', 'Semester', 'Division', 'Issues / Reasons']);

        foreach ($staged['duplicate'] as $r) {
            fputcsv($out, ['DUPLICATE', $r['excel_row_num'], $r['erp_registration_id'], $r['student_full_name'], $r['department_code'], $r['class'], $r['semester'], $r['division'], implode('; ', $r['reasons'] ?? [])]);
        }
        foreach ($staged['missing_info'] as $r) {
            fputcsv($out, ['MISSING_INFO', $r['excel_row_num'], $r['erp_registration_id'], $r['student_full_name'], $r['department_code'], $r['class'], $r['semester'], $r['division'], implode('; ', $r['reasons'] ?? [])]);
        }
        foreach ($staged['invalid_data'] as $r) {
            fputcsv($out, ['INVALID_DATA', $r['excel_row_num'], $r['erp_registration_id'], $r['student_full_name'], $r['department_code'], $r['class'], $r['semester'], $r['division'], implode('; ', $r['reasons'] ?? [])]);
        }
        fclose($out);
        exit;
    }
}

// 3. Include Header & Enforce RBAC
require_once "header/header.php";

$currentAdminId = intval($userid ?? $_SESSION['user_id'] ?? 0);
$currentRole = intval($usertype ?? $_SESSION['role_id'] ?? 0);

if ($currentRole !== 1) {
    http_response_code(403);
    echo '<div class="content-wrapper"><section class="content"><div class="alert alert-danger" style="margin: 20px;"><i class="fa fa-lock"></i> <strong>Access Restricted:</strong> Bulk Student Upload is exclusively authorized for Super Admin.</div></section></div>';
    require_once "header/footer.php";
    exit;
}

$importService = new StudentBulkImportService($conn);
$flashSuccess = '';
$flashError = '';

// 4. Form Action: Reset Staged Session
if (isset($_POST['reset_staging'])) {
    unset($_SESSION['bulk_import_staged']);
    unset($_SESSION['bulk_import_result']);
    $flashSuccess = "Import workspace has been cleared.";
}

// 5. Form Action: Process Uploaded Spreadsheet
if (isset($_POST['upload_spreadsheet'])) {
    if (!DBController::validateCsrfToken()) {
        $flashError = "Invalid or expired security token (CSRF). Please re-submit.";
    } elseif (empty($_FILES['student_excel']['name']) || $_FILES['student_excel']['error'] !== UPLOAD_ERR_OK) {
        $flashError = "Please select a valid .xlsx, .xls, or .csv spreadsheet file to upload.";
    } else {
        $fileTmp = $_FILES['student_excel']['tmp_name'];
        $fileName = $_FILES['student_excel']['name'];
        $fileSize = $_FILES['student_excel']['size'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExt, ['xlsx', 'xls', 'csv'], true)) {
            $flashError = "Unsupported file type (.$fileExt). Allowed formats: .xlsx, .xls, .csv.";
        } elseif ($fileSize > 15 * 1024 * 1024) {
            $flashError = "File size exceeds the 15MB limit.";
        } else {
            try {
                $parsedRows = $importService->parseUploadedFile($fileTmp, $fileName);
                $validationResults = $importService->validateBatch($parsedRows);
                $validationResults['file_name'] = $fileName;
                $validationResults['uploaded_at'] = date('Y-m-d H:i:s');
                $_SESSION['bulk_import_staged'] = $validationResults;
                unset($_SESSION['bulk_import_result']);
                $flashSuccess = "Spreadsheet successfully analyzed! " . $validationResults['summary']['total_rows'] . " records evaluated.";
            } catch (Throwable $e) {
                $flashError = "Spreadsheet parsing error: " . $e->getMessage();
            }
        }
    }
}

// 6. Form Action: Execute Real Database Import
if (isset($_POST['execute_import'])) {
    if (!DBController::validateCsrfToken()) {
        $flashError = "Invalid or expired security token (CSRF). Please re-submit.";
    } else {
        $staged = $_SESSION['bulk_import_staged'] ?? null;
        if (!$staged || empty($staged['valid'])) {
            $flashError = "No valid records available to import. Please upload and validate a spreadsheet first.";
        } else {
            $defaultPwd = trim($_POST['default_password'] ?? 'Tcet@123');
            if ($defaultPwd === '') $defaultPwd = 'Tcet@123';

            $importResult = $importService->executeImport($staged['valid'], $currentAdminId, ['default_password' => $defaultPwd]);
            $_SESSION['bulk_import_result'] = $importResult;
            unset($_SESSION['bulk_import_staged']);
            $flashSuccess = "Import completed! Successfully admitted {$importResult['students_created']} students and provisioned {$importResult['logins_created']} login credentials.";
        }
    }
}

$stagedData = $_SESSION['bulk_import_staged'] ?? null;
$importResult = $_SESSION['bulk_import_result'] ?? null;
?>

<div class="content-wrapper" style="min-height: 900px; background-color: #f8fafc;">
    <!-- Content Header -->
    <section class="content-header" style="padding: 20px 24px 10px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 style="font-size: 22px; font-weight: 700; color: #0f172a; margin: 0;">
                    <i class="fa fa-file-excel-o" style="color: #10b981; margin-right: 8px;"></i> Bulk Student Upload & Automatic Login Provisioning
                </h1>
                <p style="font-size: 13px; color: #64748b; margin: 4px 0 0;">
                    Upload institutional Excel/CSV student rosters, validate academic data, create official student records, and auto-provision login accounts.
                </p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="student_bulk_import.php?action=download_template" class="btn btn-default" style="background: #ffffff; border: 1px solid #cbd5e1; font-weight: 600; font-size: 13px;">
                    <i class="fa fa-download" style="color: #2563eb; margin-right: 4px;"></i> Download Official Template (.xlsx)
                </a>
                <a href="student-info.php" class="btn btn-primary" style="background: #4f46e5; border-color: #4338ca; font-weight: 600; font-size: 13px;">
                    <i class="fa fa-users" style="margin-right: 4px;"></i> View Student Directory
                </a>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content" style="padding: 10px 24px 40px;">

        <?php if (!empty($flashSuccess)) { ?>
            <div class="alert alert-success alert-dismissible" style="border-radius: 6px; font-size: 13px; margin-bottom: 20px;">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="icon fa fa-check"></i> <?php echo htmlspecialchars($flashSuccess); ?>
            </div>
        <?php } ?>

        <?php if (!empty($flashError)) { ?>
            <div class="alert alert-danger alert-dismissible" style="border-radius: 6px; font-size: 13px; margin-bottom: 20px;">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="icon fa fa-ban"></i> <?php echo htmlspecialchars($flashError); ?>
            </div>
        <?php } ?>

        <!-- STEP 1: UPLOAD CARD -->
        <div class="box box-solid" style="border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 24px;">
            <div class="box-header with-border" style="padding: 14px 20px; background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                <h3 class="box-title" style="font-size: 15px; font-weight: 700; color: #1e293b;">
                    <i class="fa fa-cloud-upload" style="color: #4f46e5; margin-right: 6px;"></i> Step 1: Upload Student Roster Spreadsheet
                </h3>
            </div>
            <div class="box-body" style="padding: 20px;">
                <form action="student_bulk_import.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo DBController::getCsrfToken(); ?>">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group">
                                <label style="font-size: 13px; font-weight: 600; color: #334155;">Select Spreadsheet File (.xlsx, .xls, .csv)</label>
                                <input type="file" name="student_excel" id="student_excel" accept=".xlsx,.xls,.csv" class="form-control" style="height: auto; padding: 10px; border: 1px dashed #94a3b8; border-radius: 6px; background: #f8fafc;" required>
                                <p class="help-block" style="font-size: 12px; color: #64748b; margin-top: 6px;">
                                    Max file size: 15MB. Ensure headers match the official template. Sample rows in the template will be automatically skipped or flagged.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-5" style="display: flex; align-items: flex-end; padding-bottom: 15px; gap: 10px;">
                            <button type="submit" name="upload_spreadsheet" class="btn btn-primary" style="background: #4f46e5; border-color: #4338ca; padding: 10px 22px; font-weight: 600; border-radius: 6px;">
                                <i class="fa fa-search"></i> Upload & Validate Spreadsheet
                            </button>
                            <?php if ($stagedData) { ?>
                                <button type="submit" name="reset_staging" class="btn btn-default" style="padding: 10px 16px; border-radius: 6px; color: #64748b;">
                                    <i class="fa fa-refresh"></i> Reset Workspace
                                </button>
                            <?php } ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- RECENT IMPORT RESULT SUMMARY (IF JUST EXECUTED) -->
        <?php if ($importResult) { ?>
            <div class="box box-solid" style="border-radius: 8px; border: 1px solid #10b981; background: #f0fdf4; margin-bottom: 24px;">
                <div class="box-body" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <h4 style="margin: 0 0 6px; font-size: 16px; font-weight: 700; color: #065f46;">
                                <i class="fa fa-check-circle" style="color: #10b981;"></i> Batch Import Completed Successfully! (Batch ID: <?php echo htmlspecialchars($importResult['batch_id']); ?>)
                            </h4>
                            <p style="margin: 0; font-size: 13px; color: #047857;">
                                <strong><?php echo $importResult['students_created']; ?></strong> student records created in student master &bull;
                                <strong><?php echo $importResult['logins_created']; ?></strong> login credentials provisioned &bull;
                                <strong><?php echo $importResult['reconciled_hist']; ?></strong> historical candidates marked imported &bull;
                                <strong><?php echo count($importResult['errors']); ?></strong> failed rows.
                            </p>
                        </div>
                        <div>
                            <a href="student-info.php" class="btn btn-success" style="background: #10b981; border-color: #059669; font-weight: 600;">
                                <i class="fa fa-list"></i> Open Student List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>

        <!-- STEP 2: STAGED VALIDATION & PREVIEW MATRIX -->
        <?php if ($stagedData) {
            $sum = $stagedData['summary'];
        ?>
            <!-- Status Metric Tiles -->
            <div class="row" style="margin-bottom: 20px;">
                <div class="col-md-3 col-sm-6">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b;">Total Evaluated</span>
                            <div style="font-size: 24px; font-weight: 700; color: #1e293b;"><?php echo $sum['total_rows']; ?></div>
                        </div>
                        <div style="width: 44px; height: 44px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #475569;">
                            <i class="fa fa-table"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div style="background: #ffffff; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; color: #166534;">Valid (Ready)</span>
                            <div style="font-size: 24px; font-weight: 700; color: #15803d;"><?php echo $sum['valid_count']; ?></div>
                        </div>
                        <div style="width: 44px; height: 44px; border-radius: 8px; background: #dcfce7; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #16a34a;">
                            <i class="fa fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div style="background: #ffffff; border: 1px solid #fef08a; border-radius: 8px; padding: 16px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; color: #854d0e;">Duplicates (Skip)</span>
                            <div style="font-size: 24px; font-weight: 700; color: #a16207;"><?php echo $sum['duplicate_count']; ?></div>
                        </div>
                        <div style="width: 44px; height: 44px; border-radius: 8px; background: #fef9c3; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #ca8a04;">
                            <i class="fa fa-copy"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div style="background: #ffffff; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <span style="font-size: 12px; font-weight: 600; text-transform: uppercase; color: #991b1b;">Invalid / Missing</span>
                            <div style="font-size: 24px; font-weight: 700; color: #b91c1c;"><?php echo $sum['missing_count'] + $sum['invalid_count']; ?></div>
                        </div>
                        <div style="width: 44px; height: 44px; border-radius: 8px; background: #fee2e2; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #dc2626;">
                            <i class="fa fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Execution Action Bar -->
            <div class="box box-solid" style="border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 24px; background: #ffffff;">
                <div class="box-body" style="padding: 16px 20px;">
                    <form action="student_bulk_import.php" method="POST" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
                        <input type="hidden" name="csrf_token" value="<?php echo DBController::getCsrfToken(); ?>">
                        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                            <div>
                                <label style="font-size: 12px; font-weight: 600; color: #475569; margin: 0 0 4px; display: block;">Default Initial Password</label>
                                <input type="text" name="default_password" value="Tcet@123" class="form-control input-sm" style="width: 160px; font-family: monospace; font-weight: bold; border-radius: 4px;" required>
                            </div>
                            <span style="font-size: 12px; color: #64748b; max-width: 320px; line-height: 1.3;">
                                Configurable default credential. Students will be required to change their password on first sign-in.
                            </span>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <?php if ($sum['duplicate_count'] + $sum['missing_count'] + $sum['invalid_count'] > 0) { ?>
                                <a href="student_bulk_import.php?action=download_errors" class="btn btn-default" style="border-color: #cbd5e1; font-weight: 600; font-size: 13px;">
                                    <i class="fa fa-file-text-o" style="color: #ea580c; margin-right: 4px;"></i> Download Validation Ledger (.csv)
                                </a>
                            <?php } ?>
                            <?php if ($sum['valid_count'] > 0) { ?>
                                <button type="submit" name="execute_import" class="btn btn-success" style="background: #16a34a; border-color: #15803d; font-weight: 700; font-size: 13px; padding: 7px 20px;" onclick="return confirm('Confirm import of <?php echo $sum['valid_count']; ?> valid student records? This will create real student master records and login accounts.');">
                                    <i class="fa fa-check"></i> Import <?php echo $sum['valid_count']; ?> Valid Records Now
                                </button>
                            <?php } else { ?>
                                <button type="button" class="btn btn-default" disabled style="font-weight: 600;">
                                    <i class="fa fa-ban"></i> No Valid Records to Import
                                </button>
                            <?php } ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TABBED PREVIEW CARDS -->
            <div class="nav-tabs-custom" style="border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); background: #ffffff;">
                <ul class="nav nav-tabs" style="border-bottom: 1px solid #e2e8f0; background: #f8fafc; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                    <li class="active">
                        <a href="#tab_valid" data-toggle="tab" style="font-weight: 600; font-size: 13px;">
                            <span class="label label-success" style="margin-right: 6px;"><?php echo $sum['valid_count']; ?></span> Valid Records
                        </a>
                    </li>
                    <li>
                        <a href="#tab_duplicate" data-toggle="tab" style="font-weight: 600; font-size: 13px;">
                            <span class="label label-warning" style="margin-right: 6px;"><?php echo $sum['duplicate_count']; ?></span> Duplicates / Existing (<?php echo $sum['duplicate_count']; ?>)
                        </a>
                    </li>
                    <li>
                        <a href="#tab_missing" data-toggle="tab" style="font-weight: 600; font-size: 13px;">
                            <span class="label label-warning" style="margin-right: 6px; background-color: #f97316;"><?php echo $sum['missing_count']; ?></span> Missing Info (<?php echo $sum['missing_count']; ?>)
                        </a>
                    </li>
                    <li>
                        <a href="#tab_invalid" data-toggle="tab" style="font-weight: 600; font-size: 13px;">
                            <span class="label label-danger" style="margin-right: 6px;"><?php echo $sum['invalid_count']; ?></span> Invalid Data (<?php echo $sum['invalid_count']; ?>)
                        </a>
                    </li>
                </ul>

                <div class="tab-content" style="padding: 16px; overflow-x: auto;">
                    <!-- TAB 1: VALID RECORDS -->
                    <div class="tab-pane active" id="tab_valid">
                        <?php if (empty($stagedData['valid'])) { ?>
                            <div style="padding: 30px; text-align: center; color: #94a3b8;">
                                <i class="fa fa-info-circle" style="font-size: 24px; margin-bottom: 8px;"></i>
                                <p style="margin: 0; font-size: 13px;">No valid records identified in this upload.</p>
                            </div>
                        <?php } else { ?>
                            <table class="table table-bordered table-striped" style="font-size: 12px; margin-bottom: 0;">
                                <thead style="background: #f1f5f9;">
                                    <tr>
                                        <th style="width: 50px;">Row</th>
                                        <th>ERP ID</th>
                                        <th>Student Name</th>
                                        <th>Email</th>
                                        <th>Dept</th>
                                        <th>Class / Sem / Div</th>
                                        <th>Track</th>
                                        <th>Specialization</th>
                                        <th>CGPA</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($stagedData['valid'], 0, 100) as $vr) { ?>
                                        <tr>
                                            <td><?php echo $vr['excel_row_num']; ?></td>
                                            <td><span class="label label-default" style="font-family: monospace; font-size: 11px;"><?php echo htmlspecialchars($vr['erp_registration_id']); ?></span></td>
                                            <td><strong><?php echo htmlspecialchars($vr['student_full_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($vr['resolved_email']); ?></td>
                                            <td><?php echo htmlspecialchars($vr['department_code']); ?></td>
                                            <td><?php echo htmlspecialchars($vr['class'] . ' / Sem ' . $vr['semester'] . ' / ' . $vr['division']); ?></td>
                                            <td><?php echo htmlspecialchars($vr['specialization_track']); ?></td>
                                            <td><?php echo htmlspecialchars($vr['specialization_name']); ?></td>
                                            <td><?php echo htmlspecialchars($vr['cgpa'] ?: '—'); ?></td>
                                            <td><span class="badge" style="background-color: #10b981; font-size: 10px;">READY</span></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                            <?php if (count($stagedData['valid']) > 100) { ?>
                                <p style="font-size: 12px; color: #64748b; margin-top: 10px; text-align: center;">Showing first 100 of <?php echo count($stagedData['valid']); ?> valid records.</p>
                            <?php } ?>
                        <?php } ?>
                    </div>

                    <!-- TAB 2: DUPLICATE RECORDS -->
                    <div class="tab-pane" id="tab_duplicate">
                        <?php if (empty($stagedData['duplicate'])) { ?>
                            <div style="padding: 30px; text-align: center; color: #16a34a;">
                                <i class="fa fa-check-circle" style="font-size: 24px; margin-bottom: 8px;"></i>
                                <p style="margin: 0; font-size: 13px;">Zero duplicates detected. All ERP IDs are distinct!</p>
                            </div>
                        <?php } else { ?>
                            <table class="table table-bordered table-striped" style="font-size: 12px; margin-bottom: 0;">
                                <thead style="background: #fef9c3;">
                                    <tr>
                                        <th style="width: 50px;">Row</th>
                                        <th>ERP ID</th>
                                        <th>Student Name</th>
                                        <th>Department</th>
                                        <th>Conflict Explanation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stagedData['duplicate'] as $dr) { ?>
                                        <tr>
                                            <td><?php echo $dr['excel_row_num']; ?></td>
                                            <td><span class="label label-warning" style="font-family: monospace;"><?php echo htmlspecialchars($dr['erp_registration_id']); ?></span></td>
                                            <td><?php echo htmlspecialchars($dr['student_full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($dr['department_code']); ?></td>
                                            <td style="color: #b45309;"><?php echo htmlspecialchars(implode(', ', $dr['reasons'] ?? [])); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>
                    </div>

                    <!-- TAB 3: MISSING INFORMATION -->
                    <div class="tab-pane" id="tab_missing">
                        <?php if (empty($stagedData['missing_info'])) { ?>
                            <div style="padding: 30px; text-align: center; color: #16a34a;">
                                <i class="fa fa-check-circle" style="font-size: 24px; margin-bottom: 8px;"></i>
                                <p style="margin: 0; font-size: 13px;">No records are missing mandatory information.</p>
                            </div>
                        <?php } else { ?>
                            <table class="table table-bordered table-striped" style="font-size: 12px; margin-bottom: 0;">
                                <thead style="background: #ffedd5;">
                                    <tr>
                                        <th style="width: 50px;">Row</th>
                                        <th>ERP ID</th>
                                        <th>Student Name</th>
                                        <th>Department</th>
                                        <th>Missing Fields</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stagedData['missing_info'] as $mr) { ?>
                                        <tr>
                                            <td><?php echo $mr['excel_row_num']; ?></td>
                                            <td><?php echo htmlspecialchars($mr['erp_registration_id'] ?: '—'); ?></td>
                                            <td><?php echo htmlspecialchars($mr['student_full_name'] ?: '—'); ?></td>
                                            <td><?php echo htmlspecialchars($mr['department_code'] ?: '—'); ?></td>
                                            <td style="color: #c2410c;"><?php echo htmlspecialchars(implode(', ', $mr['reasons'] ?? [])); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>
                    </div>

                    <!-- TAB 4: INVALID DATA -->
                    <div class="tab-pane" id="tab_invalid">
                        <?php if (empty($stagedData['invalid_data'])) { ?>
                            <div style="padding: 30px; text-align: center; color: #16a34a;">
                                <i class="fa fa-check-circle" style="font-size: 24px; margin-bottom: 8px;"></i>
                                <p style="margin: 0; font-size: 13px;">Zero data validation errors found!</p>
                            </div>
                        <?php } else { ?>
                            <table class="table table-bordered table-striped" style="font-size: 12px; margin-bottom: 0;">
                                <thead style="background: #fee2e2;">
                                    <tr>
                                        <th style="width: 50px;">Row</th>
                                        <th>ERP ID</th>
                                        <th>Student Name</th>
                                        <th>Input Department / Class</th>
                                        <th>Validation Failure Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stagedData['invalid_data'] as $ir) { ?>
                                        <tr>
                                            <td><?php echo $ir['excel_row_num']; ?></td>
                                            <td><?php echo htmlspecialchars($ir['erp_registration_id']); ?></td>
                                            <td><?php echo htmlspecialchars($ir['student_full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($ir['department_code'] . ' / ' . $ir['class']); ?></td>
                                            <td style="color: #b91c1c;"><?php echo htmlspecialchars(implode('; ', $ir['reasons'] ?? [])); ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php } ?>

    </section>
</div>

<?php require_once "header/footer.php"; ?>
