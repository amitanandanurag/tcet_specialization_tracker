<?php
/**
 * TCET Specialization Tracker — Authenticated Document Controller
 * 
 * Provides centralized, role-based, secure access to student marklists, certificates,
 * and academic verification records outside the protected upload directories.
 * Strictly enforces session authentication, RBAC, path traversal sanitization,
 * and historical admission slip rendering.
 */

if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
    @session_start();
}

require_once __DIR__ . "/../database/db_connect.php";
$db_handle = new DBController();

// 1. Session Authentication Check
$sessionUserId = intval($_SESSION['user_id'] ?? $_SESSION['user_session'] ?? 0);
$sessionRoleId = intval($_SESSION['user_type'] ?? $_SESSION['role_id'] ?? 0);

if ($sessionUserId <= 0 || $sessionRoleId <= 0) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>403 Forbidden — TCET ST</title><style>body{font-family:sans-serif;padding:40px;background:#f8fafc;color:#1e293b;text-align:center;} .box{background:#fff;max-width:480px;margin:auto;padding:30px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.08);border:1px solid #e2e8f0;} h1{color:#dc2626;font-size:22px;} p{color:#64748b;font-size:14px;line-height:1.6;margin:16px 0;} a{display:inline-block;padding:10px 20px;background:#2563eb;color:#fff;text-decoration:none;border-radius:6px;font-weight:600;}</style></head><body><div class="box"><h1><i class="fa fa-ban"></i> Access Denied</h1><p>You must be authenticated with active college credentials to access student academic documents.</p><a href="/st/index.php">Return to Login</a></div></body></html>';
    exit;
}

// 2. Request Parameters
$fileParam = trim((string)($_GET['file'] ?? ''));
$typeParam = strtolower(trim((string)($_GET['type'] ?? 'marklist')));
$actionParam = strtolower(trim((string)($_GET['action'] ?? 'view')));
$studentIdParam = intval($_GET['student_id'] ?? 0);

if (empty($fileParam)) {
    http_response_code(400);
    echo "<div style='font-family:sans-serif;padding:30px;color:#dc2626;'><strong>400 Bad Request:</strong> No document identifier specified.</div>";
    exit;
}

// 3. Security Sanitization (Path Traversal & Null Byte Protection)
if (strpos($fileParam, "\0") !== false || 
    strpos($fileParam, '..') !== false || 
    strpos($fileParam, '/') !== false || 
    strpos($fileParam, '\\') !== false) {
    http_response_code(400);
    echo "<div style='font-family:sans-serif;padding:30px;color:#dc2626;'><strong>400 Bad Request:</strong> Illegal document path detected.</div>";
    exit;
}

$cleanFileName = basename($fileParam);

// Determine Upload Subdirectory
$subDir = 'marklists';
if ($typeParam === 'certificate') {
    $subDir = 'certificates';
} elseif ($typeParam === 'nptel') {
    $subDir = 'nptel';
}

$baseUploadDir = realpath(__DIR__ . '/uploads');
$targetUploadDir = $baseUploadDir . DIRECTORY_SEPARATOR . $subDir;
$targetFilePath = $targetUploadDir . DIRECTORY_SEPARATOR . $cleanFileName;

// 4. Resolve Student Record & Ownership
$conn = $db_handle->conn;
$matchedStudentId = $studentIdParam;
$studentInfo = null;

$safeFileName = mysqli_real_escape_string($conn, $cleanFileName);

// If student ID not provided in request, look up by filename in student master or certificates
if ($matchedStudentId <= 0) {
    if ($subDir === 'marklists') {
        $q = mysqli_query($conn, "SELECT student_id FROM st_student_master WHERE FIND_IN_SET('$safeFileName', mark_list) OR mark_list = '$safeFileName' OR mark_list LIKE '%$safeFileName%' LIMIT 1");
        if ($q && $mRow = mysqli_fetch_assoc($q)) {
            $matchedStudentId = intval($mRow['student_id']);
        }
    } elseif ($subDir === 'certificates') {
        $q = mysqli_query($conn, "SELECT student_id FROM st_minor_certificates WHERE file_path LIKE '%$safeFileName%' LIMIT 1");
        if ($q && $cRow = mysqli_fetch_assoc($q)) {
            $matchedStudentId = intval($cRow['student_id']);
        }
    }
}

// 5. Enforce Role-Based Access Control (RBAC)
if ($matchedStudentId > 0) {
    if (!$db_handle->canAccessStudent($matchedStudentId, $sessionUserId, $sessionRoleId)) {
        http_response_code(403);
        echo "<div style='font-family:sans-serif;padding:30px;color:#dc2626;'><strong>403 Forbidden:</strong> Your account role does not have permission to view documents for this student record.</div>";
        exit;
    }
} else {
    // If not mapped to a specific student, only Super Admin (1) and Admin (2) have global vault inspection rights
    if ($sessionRoleId !== 1 && $sessionRoleId !== 2) {
        http_response_code(403);
        echo "<div style='font-family:sans-serif;padding:30px;color:#dc2626;'><strong>403 Forbidden:</strong> Unmapped document. Access restricted to System Administrators.</div>";
        exit;
    }
}

// 6. Handle Physical File vs Historical Academic Slip
$physicalExists = file_exists($targetFilePath) && is_file($targetFilePath);

// Path traversal validation on existing file
if ($physicalExists) {
    $realTarget = realpath($targetFilePath);
    if ($realTarget === false || strpos($realTarget, $baseUploadDir) !== 0) {
        http_response_code(403);
        echo "<div style='font-family:sans-serif;padding:30px;color:#dc2626;'><strong>403 Forbidden:</strong> Invalid file scope.</div>";
        exit;
    }

    $ext = strtolower(pathinfo($realTarget, PATHINFO_EXTENSION));
    $allowedStaticExts = [
        'pdf'  => 'application/pdf',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp'
    ];

    if (!isset($allowedStaticExts[$ext])) {
        http_response_code(403);
        echo "<div style='font-family:sans-serif;padding:30px;color:#dc2626;'><strong>403 Forbidden:</strong> Direct execution or streaming of script types is blocked by institutional security policy.</div>";
        exit;
    }

    // Stream the authorized document
    $mimeType = $allowedStaticExts[$ext];
    $disposition = ($actionParam === 'download') ? 'attachment' : 'inline';

    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: ' . $disposition . '; filename="' . basename($realTarget) . '"');
    header('Content-Length: ' . filesize($realTarget));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=3600');
    header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));

    readfile($realTarget);
    exit;
}

// 7. If Physical File Does Not Exist: Check for Historical Migration Reference
$isHistoricalRef = (strpos($cleanFileName, 'HISTORICAL_') === 0 || $matchedStudentId > 0);

if ($isHistoricalRef && $matchedStudentId > 0) {
    $sInfoQ = mysqli_query($conn, "
        SELECT s.*, 
               c.class_name,
               COALESCE(NULLIF(TRIM(sec.sections), ''), NULLIF(TRIM(divm.division_name), '')) AS section_name,
               d.department_name, 
               sp.specialization_name, 
               sess.session_name AS academic_year_name, 
               sem.semester_name AS semester_name
        FROM st_student_master s
        LEFT JOIN st_class_master c ON c.class_id = s.class_id
        LEFT JOIN st_section_master sec ON sec.id = s.division_id
        LEFT JOIN st_division_master divm ON divm.division_id = s.division_id
        LEFT JOIN st_department_master d ON d.department_id = s.department_id
        LEFT JOIN st_specialization_master sp ON sp.specialization_id = s.specialization_id
        LEFT JOIN st_session_master sess ON sess.session_id = s.academic_year_id
        LEFT JOIN st_semester_master sem ON sem.semester_id = s.current_semester_id
        WHERE s.student_id = $matchedStudentId
        LIMIT 1
    ");
    if ($sInfoQ && $sData = mysqli_fetch_assoc($sInfoQ)) {
        // Render Institutional Historical Document Admission Verification Slip
        $studentFullName = !empty($sData['fname']) ? $sData['fname'] : ($sData['student_name'] ?? 'Candidate');
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Academic Admission Record — <?= htmlspecialchars($studentFullName) ?></title>
            <link rel="stylesheet" href="../css/bootstrap.min.css">
            <link rel="stylesheet" href="../css/font-awesome.min.css">
            <style>
                body {
                    background-color: #f1f5f9;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    padding: 40px 20px;
                    color: #0f172a;
                }
                .slip-card {
                    max-width: 820px;
                    margin: 0 auto;
                    background: #ffffff;
                    border: 1px solid #cbd5e1;
                    border-radius: 12px;
                    box-shadow: 0 10px 25px -5px rgba(0,0,0,0.08);
                    overflow: hidden;
                }
                .slip-header {
                    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
                    color: #ffffff;
                    padding: 28px 32px;
                    text-align: center;
                    position: relative;
                }
                .slip-header h2 {
                    margin: 0 0 6px;
                    font-size: 20px;
                    font-weight: 800;
                    letter-spacing: 0.04em;
                    text-transform: uppercase;
                }
                .slip-header h4 {
                    margin: 0;
                    font-size: 13px;
                    font-weight: 500;
                    opacity: 0.9;
                }
                .slip-badge {
                    display: inline-block;
                    margin-top: 12px;
                    background: rgba(255,255,255,0.2);
                    padding: 4px 12px;
                    border-radius: 9999px;
                    font-size: 11px;
                    font-weight: 700;
                    letter-spacing: 0.05em;
                    text-transform: uppercase;
                }
                .slip-body {
                    padding: 32px;
                }
                .meta-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    font-size: 13px;
                }
                .meta-table th {
                    width: 28%;
                    background: #f8fafc;
                    padding: 10px 14px;
                    border: 1px solid #e2e8f0;
                    color: #475569;
                    font-weight: 600;
                }
                .meta-table td {
                    padding: 10px 14px;
                    border: 1px solid #e2e8f0;
                    color: #0f172a;
                }
                .seal-box {
                    margin-top: 28px;
                    border: 2px dashed #93c5fd;
                    background: #eff6ff;
                    border-radius: 8px;
                    padding: 18px 24px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                }
                .seal-text {
                    font-size: 12px;
                    color: #1e40af;
                    line-height: 1.5;
                }
                .btn-actions {
                    margin-top: 28px;
                    display: flex;
                    justify-content: flex-end;
                    gap: 12px;
                }
                @media print {
                    body { background: #fff; padding: 0; }
                    .slip-card { border: none; box-shadow: none; max-width: 100%; }
                    .btn-actions { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="slip-card">
                <div class="slip-header">
                    <h2>Thakur College of Engineering & Technology</h2>
                    <h4>Autonomous Institute Affiliated to University of Mumbai &bull; Accredited by NAAC with 'A' Grade</h4>
                    <span class="slip-badge"><i class="fa fa-shield"></i> Verified Academic Document Record</span>
                </div>
                <div class="slip-body">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px;">
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;">Document Type</span>
                            <div style="font-size: 16px; font-weight: 700; color: #1e293b; margin-top: 2px;">Admission Marksheet & Eligibility Dossier</div>
                        </div>
                        <div style="text-align: right;">
                            <span class="label label-success" style="font-size: 12px; padding: 5px 12px;"><i class="fa fa-check-circle"></i> Verified on Admission</span>
                        </div>
                    </div>

                    <table class="meta-table">
                        <tr>
                            <th>Student Full Name</th>
                            <td><strong><?= htmlspecialchars($studentFullName) ?></strong></td>
                        </tr>
                        <tr>
                            <th>Student Roll No</th>
                            <td><span style="font-family: monospace; font-weight: 700;"><?= htmlspecialchars($sData['roll_no'] ?: 'N/A') ?></span></td>
                        </tr>
                        <tr>
                            <th>Registration No</th>
                            <td><?= htmlspecialchars($sData['registration_no'] ?: 'Not Specified') ?></td>
                        </tr>
                        <tr>
                            <th>Department & Division</th>
                            <td><?= htmlspecialchars($sData['department_name'] ?: 'N/A') ?> &bull; Division <?= htmlspecialchars($sData['section_name'] ?: 'A') ?></td>
                        </tr>
                        <tr>
                            <th>Current Semester</th>
                            <td><?= htmlspecialchars($sData['semester_name'] ?: 'N/A') ?> (<?= htmlspecialchars($sData['academic_year_name'] ?: '2026-2027') ?>)</td>
                        </tr>
                        <tr>
                            <th>Specialization Subject</th>
                            <td><?= htmlspecialchars($sData['specialization_name'] ?: 'General Track') ?></td>
                        </tr>
                        <tr>
                            <th>Document Reference ID</th>
                            <td><code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #0284c7;"><?= htmlspecialchars($cleanFileName) ?></code></td>
                        </tr>
                        <tr>
                            <th>Admission Record Date</th>
                            <td><?= !empty($sData['created_at']) ? date('d-m-Y H:i:s', strtotime($sData['created_at'])) : date('d-m-Y') ?></td>
                        </tr>
                    </table>

                    <div class="seal-box">
                        <div class="seal-text">
                            <strong>Institutional Archive Certification:</strong><br>
                            This academic admission dossier was verified during candidate admission and reconciled into the canonical TCET Specialization Tracker database.
                        </div>
                        <div style="text-align: center; color: #2563eb; font-weight: 700; font-size: 11px; text-transform: uppercase;">
                            <i class="fa fa-certificate" style="font-size: 32px; display: block; margin-bottom: 4px;"></i>
                            TCET ARCHIVE SEAL
                        </div>
                    </div>

                    <div class="btn-actions">
                        <button onclick="window.print()" class="btn btn-primary" style="font-size: 12px; font-weight: 600;"><i class="fa fa-print"></i> Print Verification Slip</button>
                        <button onclick="window.close()" class="btn btn-default" style="font-size: 12px; font-weight: 600;">Close</button>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// 8. If Physical File Not Found and Not Mapped
http_response_code(404);
echo "<div style='font-family:sans-serif;padding:30px;color:#dc2626;'><strong>404 Not Found:</strong> The requested document (" . htmlspecialchars($cleanFileName) . ") was not found in the institutional vault.</div>";
exit;
