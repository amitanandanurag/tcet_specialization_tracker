<?php
require "header/header.php";

$allowedRoles = array(1, 2, 3, 4);
if (!in_array((int) ($usertype ?? 0), $allowedRoles, true)) {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

// Fetch filter master data with RBAC scoping
$scope = $db_handle->getUserScope($userid, $usertype);

$deptWhere = ($scope['is_hod'] && $scope['department_id'] > 0) ? "WHERE department_id = " . intval($scope['department_id']) : "WHERE status = 1";
$filterDepts = $db_handle->runQuery("SELECT department_id, department_name FROM st_department_master $deptWhere ORDER BY department_name ASC") ?? [];
$filterSessions = $db_handle->runQuery("SELECT session_id, session_name FROM st_session_master ORDER BY session_id DESC") ?? [];
$filterClasses = $db_handle->runQuery("SELECT class_id, class_name FROM st_class_master WHERE status = 1 ORDER BY class_id ASC") ?? [];
$filterSemesters = $db_handle->runQuery("SELECT semester_id, semester_name FROM st_semester_master ORDER BY semester_id ASC") ?? [];
$filterSections = $db_handle->runQuery("SELECT id, sections FROM st_section_master WHERE status = 1 ORDER BY sections ASC") ?? [];
$filterSpecs = $db_handle->runQuery("SELECT specialization_id, specialization_name FROM st_specialization_master WHERE status = 1 ORDER BY specialization_name ASC") ?? [];

$mentorWhere = ($scope['is_hod'] && $scope['department_id'] > 0) ? "AND department_id = " . intval($scope['department_id']) : "";
$filterMentors = $db_handle->runQuery("SELECT user_id, user_name FROM st_user_master WHERE role_id = 4 $mentorWhere ORDER BY user_name ASC") ?? [];
$filterHods = $db_handle->runQuery("SELECT user_id, user_name, department_id FROM st_user_master WHERE role_id = 3 ORDER BY user_name ASC") ?? [];
?>

<style>
    /* Premium Design System Styling */
    .monitor-container {
        padding: 10px;
    }

    .search-card {
        background: #ffffff;
        border-radius: var(--erp-radius-md, 6px);
        border: 1px solid var(--erp-border, #e2e8f0);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        padding: 20px;
        margin-bottom: 20px;
    }

    .search-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--erp-text-main, #0f172a);
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .unified-student-search-container {
        position: relative;
        width: 100%;
        max-width: 680px;
    }
    .student-search-box-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
    }
    .search-leading-icon {
        position: absolute;
        left: 14px;
        color: #94a3b8;
        font-size: 14px;
        pointer-events: none;
        z-index: 2;
    }
    .unified-search-input {
        width: 100%;
        height: 42px;
        padding: 6px 68px 6px 38px;
        border-radius: var(--erp-radius-sm, 4px);
        border: 1px solid var(--erp-border-dark, #cbd5e1);
        font-size: 13px;
        font-weight: 500;
        color: var(--erp-text-main, #0f172a);
        background: #ffffff;
        transition: all 0.15s ease;
        outline: none;
    }
    .unified-search-input:focus {
        border-color: var(--erp-primary, #423cbc);
        box-shadow: 0 0 0 3px rgba(66, 60, 188, 0.12);
    }
    .search-clear-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 16px;
        cursor: pointer;
        padding: 4px;
        line-height: 1;
        transition: color 0.15s ease;
        z-index: 3;
    }
    .search-clear-btn:hover {
        color: #ef4444;
    }
    .search-inline-spinner {
        position: absolute;
        right: 36px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--erp-primary, #423cbc);
        font-size: 13px;
        z-index: 3;
    }
    .student-autocomplete-dropdown {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.08);
        max-height: 360px;
        overflow-y: auto;
        z-index: 1050;
    }
    .autocomplete-item {
        padding: 10px 14px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .autocomplete-item:last-child {
        border-bottom: none;
    }
    .autocomplete-item:hover,
    .autocomplete-item.active {
        background-color: #f8fafc;
        border-left: 3px solid var(--erp-primary, #423cbc);
        padding-left: 11px;
    }
    .autocomplete-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        margin-bottom: 3px;
    }
    .autocomplete-item-name {
        font-size: 13px;
        font-weight: 600;
        color: #0f172a;
    }
    .autocomplete-item-reg {
        font-size: 11px;
        font-weight: 700;
        font-family: monospace;
        color: var(--erp-primary, #423cbc);
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        border-radius: 4px;
        padding: 1px 6px;
        white-space: nowrap;
    }
    .autocomplete-item-details {
        font-size: 12px;
        color: #64748b;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: center;
    }
    .autocomplete-item-details span {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .autocomplete-state-msg {
        padding: 16px 14px;
        text-align: center;
        font-size: 13px;
    }
    .search-match-highlight {
        background-color: #fef08a;
        color: #854d0e;
        font-weight: 700;
        border-radius: 2px;
        padding: 0 1px;
    }

    /* Filter & Roster Styling */
    .monitor-filter-card {
        background: #ffffff;
        border-radius: var(--erp-radius-md, 6px);
        border: 1px solid var(--erp-border, #e2e8f0);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        padding: 16px 20px;
        margin-bottom: 20px;
    }
    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
        margin-bottom: 14px;
    }
    .filter-item label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 4px;
    }
    .filter-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        justify-content: flex-end;
        padding-top: 10px;
        border-top: 1px solid #f1f5f9;
    }
    .roster-card {
        background: #ffffff;
        border-radius: var(--erp-radius-md, 6px);
        border: 1px solid var(--erp-border, #e2e8f0);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .roster-header {
        padding: 14px 20px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .roster-header h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .roster-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .roster-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 10px 14px;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
    }
    .roster-table td {
        padding: 10px 14px;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
        vertical-align: middle;
    }
    .roster-table tbody tr:hover {
        background-color: #f8fafc;
    }
    .roster-pagination {
        padding: 12px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        font-size: 13px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .badge-mentor {
        background: #f1f5f9;
        color: #334155;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }


    /* Student Profile Styling */
    .profile-card {
        background: #ffffff;
        border-radius: var(--erp-radius-md, 6px);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        border: 1px solid var(--erp-border, #e2e8f0);
        overflow: hidden;
        margin-bottom: 20px;
        display: none;
    }

    .profile-hero {
        background: #f8fafc;
        color: var(--erp-text-main, #0f172a);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        position: relative;
        border-bottom: 1px solid var(--erp-border, #e2e8f0);
    }

    .avatar-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--erp-primary-light, #eef2ff);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        font-weight: 700;
        color: var(--erp-primary, #423cbc);
        border: 1.5px solid var(--erp-primary-border, #c7d2fe);
    }

    .profile-meta h2 {
        margin: 0 0 4px;
        font-size: 18px;
        font-weight: 700;
        color: var(--erp-text-main, #0f172a);
    }

    .profile-meta p {
        margin: 0;
        font-size: 13px;
        color: var(--erp-text-secondary, #475569);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .badge-status {
        background: var(--erp-primary-light, #eef2ff);
        color: var(--erp-primary, #423cbc);
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        border: 1px solid var(--erp-primary-border, #c7d2fe);
    }

    .profile-details-grid {
        padding: 24px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .detail-label {
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-value {
        font-size: 15px;
        font-weight: 600;
        color: #0f172a;
    }

    .action-row {
        padding: 16px 24px;
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        background: #ffffff;
    }

    .action-btn {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .btn-email {
        background-color: #f1f5f9;
        color: #334155;
    }
    .btn-email:hover {
        background-color: #e2e8f0;
    }

    .btn-call {
        background-color: #f0fdf4;
        color: #166534;
        border: 1px solid #bbf7d0;
    }
    .btn-call:hover {
        background-color: #dcfce7;
    }

    .btn-print {
        background-color: #f0fdf4;
        color: #166534;
        border: 1px solid #bbf7d0;
        cursor: pointer;
    }
    .btn-print:hover {
        background-color: #dcfce7;
    }

    /* Semester Progress Grid Styling */
    .report-title {
        font-size: 20px;
        font-weight: 800;
        color: #0f172a;
        margin: 32px 0 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .semester-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 16px;
        margin-bottom: 30px;
    }

    .semester-card {
        background: #ffffff;
        border-radius: var(--erp-radius-md, 6px);
        box-shadow: 0 1px 3px rgba(30, 41, 59, 0.04);
        border: 1px solid var(--erp-border, #e2e8f0);
        overflow: hidden;
        transition: all 0.15s ease;
        display: flex;
        flex-direction: column;
    }

    .semester-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(30, 41, 59, 0.06);
    }

    .semester-header {
        padding: 12px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--erp-border, #e2e8f0);
        background: #f8fafc;
    }

    .semester-name {
        font-size: 14px;
        font-weight: 700;
        color: var(--erp-text-main, #0f172a);
    }

    .status-badge {
        font-size: 11px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .status-completed {
        background-color: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }

    .status-current {
        background-color: #e0e7ff;
        color: #4338ca;
        border: 1px solid #c7d2fe;
    }

    .status-failed {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }

    .status-notstarted {
        background-color: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    @keyframes pulse-border {
        0% {
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
        }
        70% {
            box-shadow: 0 0 0 6px rgba(59, 130, 246, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
        }
    }

    .semester-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        flex: 1;
    }

    .subject-info {
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .subject-icon {
        font-size: 16px;
        color: var(--erp-primary, #423cbc);
        background: var(--erp-primary-light, #eef2ff);
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        border: 1px solid var(--erp-primary-border, #c7d2fe);
    }

    .subject-details {
        flex: 1;
    }

    .subject-label {
        font-size: 11px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
    }

    .subject-title {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        margin-top: 2px;
        line-height: 1.4;
    }

    .semester-meta-row {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        border-top: 1px solid #f1f5f9;
        padding-top: 12px;
    }

    .meta-col {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .meta-lbl {
        font-size: 10px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
    }

    .meta-val {
        font-weight: 600;
        color: #334155;
    }

    /* Marks Table Styling */
    .marks-summary-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 12px;
        font-size: 12px;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        overflow: hidden;
    }

    .marks-summary-table th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        padding: 6px 10px;
        text-align: center;
        border-bottom: 1px solid #e2e8f0;
        text-transform: uppercase;
        font-size: 9px;
    }

    .marks-summary-table td {
        padding: 6px 10px;
        text-align: center;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
        font-weight: 600;
    }

    .marks-summary-table tr:last-child td {
        border-bottom: none;
    }

    .marks-summary-table td.highlight-total {
        background-color: #f0fdf4;
        color: #166534;
        font-weight: 700;
    }

    /* Loading and Alerts */
    .loading-container {
        display: none;
        text-align: center;
        padding: 40px;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid var(--erp-primary, #423cbc);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 12px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .alert-banner {
        background-color: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
        padding: 14px 16px;
        border-radius: 4px;
        margin-bottom: 20px;
        display: none;
        align-items: center;
        gap: 12px;
        font-weight: 500;
    }

    /* Print styles */
    .print-logo-section {
        display: none;
        text-align: center;
        margin-bottom: 24px;
        border-bottom: 2px double var(--erp-primary, #423cbc);
        padding-bottom: 16px;
    }

    .print-logo-section h2 {
        margin: 0;
        font-size: 24px;
        font-weight: 800;
        color: var(--erp-primary, #423cbc);
    }

    .print-logo-section p {
        margin: 4px 0 0;
        font-size: 14px;
        color: #475569;
        font-weight: 600;
    }

    @media print {
        body {
            background: #ffffff !important;
            color: #000000 !important;
        }

        .main-header, .main-sidebar, .content-header, .search-card, .action-row, .main-footer, .breadcrumb {
            display: none !important;
        }

        .content-wrapper {
            margin-left: 0 !important;
            padding: 0 !important;
            background: #ffffff !important;
        }

        .profile-card {
            border: 1px solid #000000 !important;
            box-shadow: none !important;
            display: block !important;
            page-break-inside: avoid;
        }

        .profile-hero {
            background: #f8fafc !important;
            color: #0f172a !important;
            border-bottom: 1px solid #000000 !important;
            padding: 16px !important;
        }

        .avatar-circle {
            background: #e2e8f0 !important;
            color: #0f172a !important;
            border: 1px solid #000000 !important;
        }

        .profile-details-grid {
            background: #ffffff !important;
            border-bottom: 1px solid #000000 !important;
            padding: 16px !important;
            grid-template-columns: repeat(3, 1fr) !important;
        }

        .detail-value {
            color: #000000 !important;
        }

        .semester-grid {
            display: block !important;
        }

        .semester-card {
            page-break-inside: avoid;
            margin-bottom: 24px !important;
            border: 1px solid #000000 !important;
            box-shadow: none !important;
            background: #ffffff !important;
        }

        .semester-header {
            background: #f8fafc !important;
            border-bottom: 1px solid #000000 !important;
        }

        .print-logo-section {
            display: block !important;
        }

        .status-completed {
            background: none !important;
            color: #000000 !important;
            border: 1px solid #000000 !important;
        }
        
        .status-current {
            background: none !important;
            color: #000000 !important;
            border: 1px dashed #000000 !important;
        }
        
        .status-failed {
            background: none !important;
            color: #000000 !important;
            border: 1px solid #000000 !important;
        }

        .status-notstarted {
            background: none !important;
            color: #64748b !important;
        }
    }
</style>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            <i class="fa fa-desktop text-primary"></i> Student Monitor
        </h1>
        <ol class="breadcrumb">
            <li><a href="index.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
            <li><a href="student-info.php">Students</a></li>
            <li class="active">Monitor</li>
        </ol>
    </section>

    <!-- Main content -->
    <section class="content monitor-container">
        <!-- Print Letterhead / Header -->
        <div class="print-logo-section">
            <img src="images/tcet_logo.png" alt="TCET Logo" style="height: 60px; margin-bottom: 8px; object-fit: contain;">
            <h2>THAKUR COLLEGE OF ENGINEERING & TECHNOLOGY</h2>
            <p>An Autonomous College Affiliated to University of Mumbai</p>
            <p style="font-size: 15px; margin-top: 8px; color: var(--erp-primary, #423cbc); text-transform: uppercase; font-weight: 700;">
                Student Domain Specialization Progress Report
            </p>
        </div>

        <!-- Search & Student Selection Card -->
        <div class="search-card">
            <div class="search-title">
                <i class="fa fa-search" style="color: var(--erp-primary, #423cbc);"></i> Student Search & Direct Selection
            </div>
            <div class="unified-student-search-container">
                <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 6px; display: block;">
                    Select Student to Monitor
                </label>
                <div class="student-search-box-wrapper">
                    <i class="fa fa-search search-leading-icon"></i>
                    <input type="text"
                           id="studentSearchInput"
                           class="unified-search-input"
                           placeholder="Search by student name or registration number..."
                           autocomplete="off"
                           spellcheck="false"
                           aria-label="Search by student name or registration number"
                           aria-autocomplete="list"
                           aria-expanded="false">
                    <button type="button" id="btnClearStudentSearch" class="search-clear-btn" title="Clear search" style="display: none;" aria-label="Clear student search">
                        <i class="fa fa-times-circle"></i>
                    </button>
                    <div id="searchSpinner" class="search-inline-spinner" style="display: none;">
                        <i class="fa fa-spinner fa-spin"></i>
                    </div>
                    <div id="studentAutocompleteMenu" class="student-autocomplete-dropdown" style="display: none;" role="listbox"></div>
                </div>
                <input type="hidden" id="regNoInput" value="">
                <div style="margin-top: 6px; font-size: 11px; color: #94a3b8;">
                    <i class="fa fa-info-circle"></i> Type student name or registration number. Use <span style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 3px; padding: 1px 4px; font-family: monospace;">&uarr;</span> <span style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 3px; padding: 1px 4px; font-family: monospace;">&darr;</span> keys to navigate, <span style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 3px; padding: 1px 4px; font-family: monospace;">Enter</span> to select, <span style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 3px; padding: 1px 4px; font-family: monospace;">Esc</span> to close.
                </div>
            </div>
        </div>

        <!-- Advanced Filters & Sorting Card -->
        <div class="monitor-filter-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; cursor: pointer;" onclick="$('#filterGridWrapper').slideToggle(200);">
                <div style="font-size: 14px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <i class="fa fa-filter text-primary"></i> Advanced Filters & Sorting
                </div>
                <div class="text-muted" style="font-size: 12px;">
                    <span id="filterToggleIcon"><i class="fa fa-chevron-down"></i></span>
                </div>
            </div>

            <div id="filterGridWrapper">
                <div class="filter-grid">
                    <?php if (!$scope['is_hod']): ?>
                    <div class="filter-item">
                        <label>Department</label>
                        <select id="filterDept" class="form-control input-sm">
                            <option value="0">All Departments</option>
                            <?php foreach ($filterDepts as $d): ?>
                                <option value="<?= $d['department_id'] ?>"><?= htmlspecialchars($d['department_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <div class="filter-item">
                        <label>Department</label>
                        <select id="filterDept" class="form-control input-sm" disabled>
                            <?php foreach ($filterDepts as $d): ?>
                                <option value="<?= $d['department_id'] ?>" selected><?= htmlspecialchars($d['department_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="filter-item">
                        <label>Academic Year</label>
                        <select id="filterSession" class="form-control input-sm">
                            <option value="0">All Academic Years</option>
                            <?php foreach ($filterSessions as $s): ?>
                                <option value="<?= $s['session_id'] ?>"><?= htmlspecialchars($s['session_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (!$scope['is_mentor']): ?>
                    <div class="filter-item">
                        <label>Under Mentor</label>
                        <select id="filterMentor" class="form-control input-sm">
                            <option value="0">All Mentors</option>
                            <?php foreach ($filterMentors as $m): ?>
                                <option value="<?= $m['user_id'] ?>"><?= htmlspecialchars($m['user_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="filter-item">
                        <label>Under HOD</label>
                        <select id="filterHod" class="form-control input-sm">
                            <option value="0">All HODs</option>
                            <?php foreach ($filterHods as $h): ?>
                                <option value="<?= $h['user_id'] ?>"><?= htmlspecialchars($h['user_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label>Class</label>
                        <select id="filterClass" class="form-control input-sm">
                            <option value="0">All Classes</option>
                            <?php foreach ($filterClasses as $c): ?>
                                <option value="<?= $c['class_id'] ?>"><?= htmlspecialchars($c['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label>Semester</label>
                        <select id="filterSemester" class="form-control input-sm">
                            <option value="0">All Semesters</option>
                            <?php foreach ($filterSemesters as $sem): ?>
                                <option value="<?= $sem['semester_id'] ?>"><?= htmlspecialchars($sem['semester_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label>Division</label>
                        <select id="filterSection" class="form-control input-sm">
                            <option value="0">All Divisions</option>
                            <?php foreach ($filterSections as $sec): ?>
                                <option value="<?= $sec['id'] ?>"><?= htmlspecialchars($sec['sections']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label>Specialization Track</label>
                        <select id="filterSpec" class="form-control input-sm">
                            <option value="0">All Specializations</option>
                            <?php foreach ($filterSpecs as $sp): ?>
                                <option value="<?= $sp['specialization_id'] ?>"><?= htmlspecialchars($sp['specialization_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label>Sort By</label>
                        <select id="filterSortBy" class="form-control input-sm">
                            <option value="name">Student Name</option>
                            <option value="registration_no">Registration Number</option>
                            <option value="academic_year">Academic Year</option>
                            <option value="department">Department</option>
                            <option value="class">Class</option>
                            <option value="semester">Semester</option>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label>Order</label>
                        <select id="filterSortDir" class="form-control input-sm">
                            <option value="ASC">Ascending (A-Z)</option>
                            <option value="DESC">Descending (Z-A)</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="button" class="btn btn-sm btn-default" onclick="resetFilters();">
                        <i class="fa fa-undo"></i> Reset Filters
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="loadStudentTable(1);">
                        <i class="fa fa-search"></i> Apply Filters & Sort
                    </button>
                </div>
            </div>
        </div>

        <!-- Student Roster Directory Card -->
        <div class="roster-card" id="studentRosterSection">
            <div class="roster-header">
                <h3><i class="fa fa-users text-primary"></i> Student Directory Roster</h3>
                <div style="font-size: 12px; color: #64748b;">
                    <span id="rosterCountText">Loading student records...</span>
                </div>
            </div>

            <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                <table class="roster-table" id="rosterTable">
                    <thead>
                        <tr>
                            <th style="width: 120px;">Reg No</th>
                            <th>Student Name</th>
                            <th>Department</th>
                            <th>AY</th>
                            <th>Class / Sem</th>
                            <th>Div</th>
                            <th>Specialization</th>
                            <th>Assigned Mentor</th>
                            <th style="width: 110px; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="rosterTableBody">
                        <tr>
                            <td colspan="9" class="text-center text-muted" style="padding: 24px;">Loading student directory...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="roster-pagination">
                <div id="paginationInfo" class="text-muted">Page 1 of 1</div>
                <div class="btn-group" id="paginationControls">
                    <button type="button" class="btn btn-xs btn-default" id="btnPrevPage" onclick="changePage(-1);" disabled>
                        <i class="fa fa-chevron-left"></i> Previous
                    </button>
                    <button type="button" class="btn btn-xs btn-default" id="btnNextPage" onclick="changePage(1);" disabled>
                        Next <i class="fa fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading spinner -->
        <div class="loading-container" id="loadingSpinner">
            <div class="spinner"></div>
            <p class="text-muted" style="font-weight: 500;">Retrieving student profile, enrollment details and progress marks...</p>
        </div>

        <!-- Error alert banner -->
        <div class="alert-banner" id="errorBanner">
            <i class="fa fa-exclamation-circle" style="font-size: 20px;"></i>
            <span id="errorMessage"></span>
        </div>

        <!-- Student Profile Card Area -->
        <div class="profile-card" id="studentProfile">
            <div class="profile-hero">
                <div class="avatar-circle" id="profileInitials">JD</div>
                <div class="profile-meta">
                    <h2 id="studentName">John Doe</h2>
                    <p>
                        <span>Reg No: <strong id="studentRegNo">S1032241059</strong></span>
                        <span class="badge-status" id="studentStatusBadge">Active</span>
                    </p>
                </div>
            </div>
            
            <div class="profile-details-grid">
                <div class="detail-item">
                    <span class="detail-label">Roll Number</span>
                    <span class="detail-value" id="profileRoll">12</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Class</span>
                    <span class="detail-value" id="profileClass">TE</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Division</span>
                    <span class="detail-value" id="profileDivision">A</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Department</span>
                    <span class="detail-value" id="profileDept">Information Technology</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Academic Session</span>
                    <span class="detail-value" id="profileSession">2026 - 2027</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Current Sem</span>
                    <span class="detail-value" id="profileCurrentSem">Semester V</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Specialization</span>
                    <span class="detail-value" id="profileSpec">Honours Degree</span>
                </div>
                <div class="detail-item" id="specializationSubjectWrapper">
                    <span class="detail-label">Domain Subject</span>
                    <span class="detail-value" id="profileSpecSubject">Data Science</span>
                </div>
                <div class="detail-item" id="minorCourseWrapper" style="display: none;">
                    <span class="detail-label">Minor Course</span>
                    <span class="detail-value" id="profileMinorCourse">N/A</span>
                </div>
                <div class="detail-item" id="minorSubjectWrapper" style="display: none;">
                    <span class="detail-label">Minor Subject</span>
                    <span class="detail-value" id="profileMinorSubject">N/A</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Aggregate CGPA</span>
                    <span class="detail-value" id="profileCgpa" style="font-size: 16px; color: var(--erp-primary, #423cbc); font-weight: 700;">9.23</span>
                </div>
            </div>

            <div class="action-row">
                <a href="#" class="action-btn btn-email" id="actionEmail" target="_blank">
                    <i class="fa fa-envelope"></i> Email
                </a>
                <a href="#" class="action-btn btn-call" id="actionCall" target="_blank">
                    <i class="fa fa-whatsapp"></i> WhatsApp
                </a>
                <button type="button" class="action-btn btn-print" onclick="window.print();">
                    <i class="fa fa-print"></i> Print Progress Report
                </button>
            </div>
        </div>

        <!-- Semester Progress Report Timeline/Grid -->
        <div id="progressReportSection" style="display: none;">
            <div class="report-title">
                <i class="fa fa-th-list text-blue"></i> Semester-wise Domain Subject Tracker
            </div>
            
            <div class="semester-grid" id="semestersGrid">
                <!-- Semester cards will be injected here via Javascript -->
            </div>
        </div>
    </section>
</div>

<?php require "header/footer.php"; ?>

<script>
    var currentRosterPage = 1;
    var totalRosterPages = 1;

    $(document).ready(function() {
        loadStudentTable(1);
        initStudentAutocomplete();
    });

    function initStudentAutocomplete() {
        var $input = $('#studentSearchInput');
        var $clearBtn = $('#btnClearStudentSearch');
        var $spinner = $('#searchSpinner');
        var $menu = $('#studentAutocompleteMenu');
        var currentSearchXhr = null;
        var debounceTimer = null;
        var currentItems = [];
        var selectedIndex = -1;

        function escapeHtml(text) {
            return $('<div>').text(text || '').html();
        }

        function highlightMatch(text, query) {
            if (!text) return '';
            var escapedText = escapeHtml(text);
            if (!query || !query.trim()) return escapedText;
            var escapedQuery = query.trim().replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            return escapedText.replace(new RegExp('(' + escapedQuery + ')', 'gi'), '<span class="search-match-highlight">$1</span>');
        }

        function closeMenu() {
            $menu.hide().empty();
            $input.attr('aria-expanded', 'false');
            selectedIndex = -1;
            currentItems = [];
        }

        function updateActiveItem() {
            var items = $menu.find('.autocomplete-item');
            items.removeClass('active').attr('aria-selected', 'false');
            if (selectedIndex >= 0 && selectedIndex < items.length) {
                var activeItem = items.eq(selectedIndex);
                activeItem.addClass('active').attr('aria-selected', 'true');
                var menuEl = $menu[0];
                var itemEl = activeItem[0];
                if (itemEl.offsetTop < menuEl.scrollTop) {
                    menuEl.scrollTop = itemEl.offsetTop;
                } else if (itemEl.offsetTop + itemEl.offsetHeight > menuEl.scrollTop + menuEl.clientHeight) {
                    menuEl.scrollTop = itemEl.offsetTop + itemEl.offsetHeight - menuEl.clientHeight;
                }
            }
        }

        function selectItem(student) {
            if (!student) return;
            var displayName = student.name ? (student.name + ' (' + student.registration_no + ')') : student.registration_no;
            $input.val(displayName);
            $('#regNoInput').val(student.registration_no);
            $clearBtn.show();
            closeMenu();
            loadStudentProfile(student.registration_no);
        }

        function fetchSuggestions(query) {
            if (currentSearchXhr && currentSearchXhr.readyState !== 4) {
                currentSearchXhr.abort();
            }
            $spinner.show();
            $menu.html('<div class="autocomplete-state-msg"><i class="fa fa-spinner fa-spin text-primary"></i> Searching matching students...</div>').show();
            $input.attr('aria-expanded', 'true');

            var deptId = ($('#filterDept').length && $('#filterDept').val()) ? $('#filterDept').val() : 0;

            currentSearchXhr = $.ajax({
                url: 'student_monitor_ajax.php',
                type: 'GET',
                data: {
                    action: 'autocomplete_students',
                    q: query,
                    department_id: deptId,
                    limit: 25
                },
                dataType: 'json',
                success: function(response) {
                    $spinner.hide();
                    if (response && response.success) {
                        currentItems = response.students || [];
                        selectedIndex = -1;
                        if (currentItems.length === 0) {
                            $menu.html('<div class="autocomplete-state-msg text-muted"><i class="fa fa-info-circle"></i> No students found matching "<strong>' + escapeHtml(query) + '</strong>"</div>').show();
                        } else {
                            var html = '';
                            $.each(currentItems, function(idx, s) {
                                var nameHtml = highlightMatch(s.name, query);
                                var regHtml = highlightMatch(s.registration_no, query);
                                html += '<div class="autocomplete-item" role="option" data-index="' + idx + '">' +
                                        '  <div class="autocomplete-item-header">' +
                                        '    <span class="autocomplete-item-name">' + nameHtml + '</span>' +
                                        '    <span class="autocomplete-item-reg">' + regHtml + '</span>' +
                                        '  </div>' +
                                        '  <div class="autocomplete-item-details">' +
                                        '    <span class="student-item-dept"><i class="fa fa-building-o"></i> ' + escapeHtml(s.department || 'N/A') + '</span>' +
                                        '    <span class="student-item-class"><i class="fa fa-graduation-cap"></i> ' + escapeHtml(s.class || 'N/A') + '</span>' +
                                        (s.roll_no ? '    <span class="student-item-roll"><i class="fa fa-id-badge"></i> Roll ' + escapeHtml(s.roll_no) + '</span>' : '') +
                                        '  </div>' +
                                        '</div>';
                            });
                            $menu.html(html).show();
                        }
                    } else {
                        var errMsg = (response && response.message) ? response.message : 'Unable to load matching students.';
                        $menu.html('<div class="autocomplete-state-msg text-danger"><i class="fa fa-exclamation-circle"></i> ' + escapeHtml(errMsg) + '</div>').show();
                    }
                },
                error: function(xhr, status) {
                    if (status === 'abort') return;
                    $spinner.hide();
                    $menu.html('<div class="autocomplete-state-msg text-danger"><i class="fa fa-exclamation-triangle"></i> Network or server error communicating with student monitor.</div>').show();
                }
            });
        }

        $input.on('input', function() {
            var val = $(this).val();
            $clearBtn.toggle(val.length > 0);
            clearTimeout(debounceTimer);
            var query = val.trim();
            if (query.length === 0) {
                if (currentSearchXhr && currentSearchXhr.readyState !== 4) {
                    currentSearchXhr.abort();
                }
                $spinner.hide();
                closeMenu();
                return;
            }
            debounceTimer = setTimeout(function() {
                fetchSuggestions(query);
            }, 250);
        });

        $input.on('keydown', function(e) {
            if (!$menu.is(':visible') && (e.keyCode === 40 || e.keyCode === 38)) {
                var query = $input.val().trim();
                if (query.length > 0) {
                    fetchSuggestions(query);
                    e.preventDefault();
                    return;
                }
            }

            if (e.keyCode === 40) { // Arrow Down
                e.preventDefault();
                if (currentItems.length > 0) {
                    selectedIndex = (selectedIndex + 1) % currentItems.length;
                    updateActiveItem();
                }
            } else if (e.keyCode === 38) { // Arrow Up
                e.preventDefault();
                if (currentItems.length > 0) {
                    selectedIndex = (selectedIndex <= 0) ? (currentItems.length - 1) : (selectedIndex - 1);
                    updateActiveItem();
                }
            } else if (e.keyCode === 13) { // Enter
                e.preventDefault();
                if (selectedIndex >= 0 && selectedIndex < currentItems.length) {
                    selectItem(currentItems[selectedIndex]);
                } else if (currentItems.length === 1) {
                    selectItem(currentItems[0]);
                } else {
                    var rawVal = $input.val().trim();
                    if (rawVal !== '') {
                        closeMenu();
                        loadStudentProfile(rawVal);
                    }
                }
            } else if (e.keyCode === 27) { // Escape
                e.preventDefault();
                closeMenu();
            }
        });

        $menu.on('click', '.autocomplete-item', function() {
            var idx = $(this).data('index');
            if (typeof idx !== 'undefined' && currentItems[idx]) {
                selectItem(currentItems[idx]);
            }
        });

        $menu.on('mouseenter', '.autocomplete-item', function() {
            var idx = $(this).data('index');
            if (typeof idx !== 'undefined') {
                selectedIndex = idx;
                updateActiveItem();
            }
        });

        $clearBtn.on('click', function() {
            $input.val('');
            $('#regNoInput').val('');
            $clearBtn.hide();
            if (currentSearchXhr && currentSearchXhr.readyState !== 4) {
                currentSearchXhr.abort();
            }
            $spinner.hide();
            closeMenu();
            $input.focus();
        });

        $(document).on('mousedown', function(e) {
            if (!$(e.target).closest('.student-search-box-wrapper').length) {
                closeMenu();
            }
        });

        $input.on('focus', function() {
            var query = $input.val().trim();
            if (query.length > 0 && currentItems.length > 0) {
                $menu.show();
                $input.attr('aria-expanded', 'true');
            }
        });
    }

    function loadStudentTable(page) {
        currentRosterPage = page || 1;
        var deptId = $('#filterDept').val() || 0;
        var sessionId = $('#filterSession').val() || 0;
        var mentorId = $('#filterMentor').length ? ($('#filterMentor').val() || 0) : 0;
        var hodId = $('#filterHod').val() || 0;
        var classId = $('#filterClass').val() || 0;
        var semId = $('#filterSemester').val() || 0;
        var divId = $('#filterSection').val() || 0;
        var specId = $('#filterSpec').val() || 0;
        var sortBy = $('#filterSortBy').val() || 'name';
        var sortDir = $('#filterSortDir').val() || 'ASC';
        var search = $('#regNoInput').val().trim();

        $('#rosterTableBody').html('<tr><td colspan="9" class="text-center text-muted" style="padding: 24px;"><i class="fa fa-spinner fa-spin"></i> Loading student records...</td></tr>');

        $.ajax({
            url: 'student_monitor_ajax.php',
            type: 'GET',
            data: {
                action: 'list_students',
                department_id: deptId,
                academic_year_id: sessionId,
                mentor_id: mentorId,
                hod_id: hodId,
                class_id: classId,
                semester_id: semId,
                division_id: divId,
                specialization_id: specId,
                sort_by: sortBy,
                sort_dir: sortDir,
                search: search,
                page: currentRosterPage,
                per_page: 10
            },
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    renderRosterTable(response.students, response.pagination);
                } else {
                    var msg = response && response.message ? response.message : 'Failed to load student directory.';
                    $('#rosterTableBody').html('<tr><td colspan="9" class="text-center text-danger" style="padding: 24px;"><i class="fa fa-exclamation-circle"></i> ' + $('<div>').text(msg).html() + '</td></tr>');
                    $('#rosterCountText').text('0 records found');
                }
            },
            error: function() {
                $('#rosterTableBody').html('<tr><td colspan="9" class="text-center text-danger" style="padding: 24px;"><i class="fa fa-exclamation-triangle"></i> Network or server error communicating with student monitor endpoint.</td></tr>');
                $('#rosterCountText').text('Error loading data');
            }
        });
    }

    function renderRosterTable(students, pagination) {
        var tbody = $('#rosterTableBody');
        tbody.empty();

        if (!students || students.length === 0) {
            tbody.html('<tr><td colspan="9" class="text-center text-muted" style="padding: 24px;"><i class="fa fa-info-circle"></i> No student records found matching the specified filters.</td></tr>');
            $('#rosterCountText').text('0 records found');
            $('#paginationInfo').text('Page 1 of 1 (0 total)');
            $('#btnPrevPage').prop('disabled', true);
            $('#btnNextPage').prop('disabled', true);
            return;
        }

        totalRosterPages = pagination.total_pages || 1;
        currentRosterPage = pagination.page || 1;
        var startIdx = ((currentRosterPage - 1) * pagination.per_page) + 1;
        var endIdx = Math.min(startIdx + students.length - 1, pagination.total);

        $('#rosterCountText').text('Showing ' + startIdx + ' - ' + endIdx + ' of ' + pagination.total + ' students');
        $('#paginationInfo').text('Page ' + currentRosterPage + ' of ' + totalRosterPages + ' (' + pagination.total + ' total)');
        $('#btnPrevPage').prop('disabled', currentRosterPage <= 1);
        $('#btnNextPage').prop('disabled', currentRosterPage >= totalRosterPages);

        $.each(students, function(i, s) {
            var tr = $('<tr></tr>');
            tr.append('<td><strong style="color: var(--erp-primary, #423cbc);">' + $('<div>').text(s.registration_no).html() + '</strong></td>');
            tr.append('<td><div style="font-weight: 600; color: #0f172a;">' + $('<div>').text(s.name).html() + '</div><small class="text-muted">Roll: ' + $('<div>').text(s.roll_no).html() + '</small></td>');
            tr.append('<td>' + $('<div>').text(s.department).html() + '</td>');
            tr.append('<td><span class="badge" style="background: #e2e8f0; color: #334155;">' + $('<div>').text(s.academic_year).html() + '</span></td>');
            tr.append('<td>' + $('<div>').text(s.class).html() + ' &bull; ' + $('<div>').text(s.semester).html() + '</td>');
            tr.append('<td>' + $('<div>').text(s.division).html() + '</td>');
            tr.append('<td><span style="font-size: 12px; color: #475569;">' + $('<div>').text(s.specialization).html() + '</span></td>');
            tr.append('<td><span class="badge-mentor"><i class="fa fa-user"></i> ' + $('<div>').text(s.mentor).html() + '</span></td>');

            var actionTd = $('<td style="text-align: center;"></td>');
            var btn = $('<button type="button" class="btn btn-xs btn-primary" style="font-weight: 600; border-radius: 3px;"><i class="fa fa-line-chart"></i> Monitor</button>');
            btn.on('click', function() {
                loadStudentProfile(s.registration_no);
            });
            actionTd.append(btn);
            tr.append(actionTd);

            tbody.append(tr);
        });
    }

    function changePage(delta) {
        var target = currentRosterPage + delta;
        if (target >= 1 && target <= totalRosterPages) {
            loadStudentTable(target);
        }
    }

    function resetFilters() {
        if ($('#filterDept').length && !$('#filterDept').prop('disabled')) {
            $('#filterDept').val('0');
        }
        $('#filterSession').val('0');
        if ($('#filterMentor').length) {
            $('#filterMentor').val('0');
        }
        $('#filterHod').val('0');
        $('#filterClass').val('0');
        $('#filterSemester').val('0');
        $('#filterSection').val('0');
        $('#filterSpec').val('0');
        $('#filterSortBy').val('name');
        $('#filterSortDir').val('ASC');
        $('#regNoInput').val('');
        $('#studentSearchInput').val('');
        $('#btnClearStudentSearch').hide();
        $('#studentAutocompleteMenu').hide().empty();
        loadStudentTable(1);
    }

    function loadStudentProfile(regNo, studentName) {
        if (!regNo) return;
        $('#regNoInput').val(regNo);
        if (studentName) {
            $('#studentSearchInput').val(studentName + ' (' + regNo + ')');
        } else {
            $('#studentSearchInput').val(regNo);
        }
        $('#btnClearStudentSearch').show();
        $('#studentAutocompleteMenu').hide().empty();
        searchStudent(regNo);
    }

    function searchStudent(customRegNo) {
        var regNo = (customRegNo || $('#regNoInput').val() || '').trim();
        if (regNo === '') {
            return;
        }

        // Reset views
        $('#errorBanner').hide();
        $('#studentProfile').hide();
        $('#progressReportSection').hide();
        $('#loadingSpinner').show();

        // Smooth scroll to loading section
        $('html, body').animate({
            scrollTop: $('#loadingSpinner').offset().top - 80
        }, 300);

        $.ajax({
            url: 'student_monitor_ajax.php',
            type: 'GET',
            data: { reg_no: regNo },
            dataType: 'json',
            success: function(response) {
                $('#loadingSpinner').hide();
                if (response.success) {
                    renderStudentProfile(response.student);
                    renderSemesterProgress(response.semesters);
                    // Scroll to profile
                    $('html, body').animate({
                        scrollTop: $('#studentProfile').offset().top - 70
                    }, 400);
                } else {
                    $('#errorMessage').text(response.message);
                    $('#errorBanner').css('display', 'flex');
                }
            },
            error: function(xhr, status, error) {
                $('#loadingSpinner').hide();
                $('#errorMessage').text('An error occurred while communication with server. Please try again.');
                $('#errorBanner').css('display', 'flex');
            }
        });
    }

    function renderStudentProfile(student) {
        // Initials
        var names = student.name.split(' ');
        var initials = names[0].charAt(0).toUpperCase();
        if (names.length > 1) {
            initials += names[names.length - 1].charAt(0).toUpperCase();
        } else {
            initials += student.name.charAt(1).toUpperCase();
        }
        
        if (student && student.name && student.registration_no) {
            $('#studentSearchInput').val(student.name + ' (' + student.registration_no + ')');
            $('#regNoInput').val(student.registration_no);
            $('#btnClearStudentSearch').show();
        }

        $('#profileInitials').text(initials);
        $('#studentName').text(student.name);
        $('#studentRegNo').text(student.registration_no);
        $('#profileRoll').text(student.roll_no);
        $('#profileClass').text(student.class);
        $('#profileDivision').text(student.division);
        $('#profileDept').text(student.department);
        $('#profileSession').text(student.academic_year);
        $('#profileCurrentSem').text(student.current_semester);
        $('#profileSpec').text(student.specialization);
        $('#profileCgpa').text(student.cgpa);

        // Status Badge
        var statusBadge = $('#studentStatusBadge');
        statusBadge.text(student.status);
        if (student.status.toLowerCase() === 'active') {
            statusBadge.css({ 'background-color': '#dcfce7', 'color': '#166534', 'border': '1px solid #bbf7d0' });
        } else {
            statusBadge.css({ 'background-color': '#fef2f2', 'color': '#991b1b', 'border': '1px solid #fecaca' });
        }

        // Toggle specializations fields
        var isMinor = student.specialization.toLowerCase().indexOf('minor') !== -1;
        var isMinorMulti = student.specialization.toLowerCase().indexOf('minor multidisciplinary') !== -1;
        var isHonours = student.specialization.toLowerCase().indexOf('honour') !== -1 || student.specialization.toLowerCase().indexOf('honor') !== -1;

        if (isMinorMulti) {
            $('#minorCourseWrapper').show();
            $('#profileMinorCourse').text(student.minor_course);
            $('#minorSubjectWrapper').show();
            $('#profileMinorSubject').text(student.minor_subject);
            $('#specializationSubjectWrapper').hide();
        } else if (isHonours) {
            $('#specializationSubjectWrapper').show();
            $('#profileSpecSubject').text(student.specialization_subject);
            $('#minorCourseWrapper').hide();
            $('#minorSubjectWrapper').hide();
        } else {
            $('#specializationSubjectWrapper').show();
            $('#profileSpecSubject').text(student.specialization_subject);
            $('#minorCourseWrapper').hide();
            $('#minorSubjectWrapper').hide();
        }

        // Actions
        $('#actionEmail').attr('href', 'mailto:' + student.email);
        if (student.mobile !== 'N/A' && student.mobile !== '') {
            $('#actionCall').attr('href', 'https://wa.me/91' + student.mobile.replace(/[^0-9]/g, ''));
            $('#actionCall').show();
        } else {
            $('#actionCall').hide();
        }

        $('#studentProfile').fadeIn(400);
    }

    function renderSemesterProgress(semesters) {
        var grid = $('#semestersGrid');
        grid.empty();

        semesters.forEach(function(sem) {
            // Determine status class and styling
            var statusClass = 'status-notstarted';
            var statusLabel = 'Not Enrolled';

            if (sem.status === 'Completed') {
                statusClass = 'status-completed';
                statusLabel = 'Completed';
            } else if (sem.status === 'Current Semester') {
                statusClass = 'status-current';
                statusLabel = 'Current';
            } else if (sem.status === 'Failed') {
                statusClass = 'status-failed';
                statusLabel = 'Failed';
            }

            var card = $('<div class="semester-card"></div>');
            
            // Header
            var header = $('<div class="semester-header">' +
                                '<span class="semester-name">' + sem.semester_name + '</span>' +
                                '<span class="status-badge ' + statusClass + '">' + statusLabel + '</span>' +
                           '</div>');
            card.append(header);

            // Body
            var body = $('<div class="semester-body"></div>');
            
            // Subject Info
            var subInfo = $('<div class="subject-info">' +
                                '<div class="subject-icon"><i class="fa fa-book"></i></div>' +
                                '<div class="subject-details">' +
                                    '<div class="subject-label">Domain Specialization / Course</div>' +
                                    '<div class="subject-title">' + sem.subject + '</div>' +
                                '</div>' +
                            '</div>');
            body.append(subInfo);

            // Specialization type if available
            var typeRow = $('<div style="font-size: 13px; color: #475569; margin-top: 4px;">' +
                                '<strong>Specialization:</strong> ' + sem.specialization +
                            '</div>');
            body.append(typeRow);

            body.append($('<div style="font-size: 13px; color: #475569; margin-top: 4px;">' +
                          '<strong>Mentor:</strong> ' + (sem.mentor || 'N/A') +
                      '</div>'));

            // Marks details (Offline marks entry)
            if (sem.marks_entry) {
                var m = sem.marks_entry;
                var table = $('<table class="marks-summary-table">' +
                                '<thead>' +
                                    '<tr>' +
                                        '<th>ISE 1</th>' +
                                        '<th>ISE 2</th>' +
                                        '<th>ESE (Written)</th>' +
                                        '<th>Total Score</th>' +
                                    '</tr>' +
                                '</thead>' +
                                '<tbody>' +
                                    '<tr>' +
                                        '<td>' + (m.ise1_marks !== null ? m.ise1_marks : '-') + '</td>' +
                                        '<td>' + (m.ise2_marks !== null ? m.ise2_marks : '-') + '</td>' +
                                        '<td>' + (m.ese_written_marks !== null ? m.ese_written_marks : '-') + '</td>' +
                                        '<td class="highlight-total">' + (m.final_score !== null ? m.final_score : '-') + '</td>' +
                                    '</tr>' +
                                '</tbody>' +
                            '</table>');
                body.append(table);

                if (m.remarks) {
                    body.append($('<div style="font-size: 11px; color: #64748b; font-style: italic; margin-top: 8px;">' +
                                    '<strong>Remarks:</strong> ' + m.remarks +
                                  '</div>'));
                }
            } else if (sem.nptel_entry) {
                // If only NPTEL is available
                var n = sem.nptel_entry;
                var table = $('<table class="marks-summary-table">' +
                                '<thead>' +
                                    '<tr>' +
                                        '<th>Course Type</th>' +
                                        '<th>Exam Score</th>' +
                                        '<th>Status</th>' +
                                    '</tr>' +
                                '</thead>' +
                                '<tbody>' +
                                    '<tr>' +
                                        '<td>NPTEL Certification</td>' +
                                        '<td>' + (n.score !== null ? n.score : '-') + '</td>' +
                                        '<td class="highlight-total">' + n.pass_fail + '</td>' +
                                    '</tr>' +
                                '</tbody>' +
                            '</table>');
                body.append(table);
            } else if (sem.status === 'Current Semester') {
                body.append($('<div style="background-color: #f0fdf4; border: 1px dashed #bbf7d0; color: #166534; font-size: 13px; padding: 10px; border-radius: 8px; text-align: center; margin-top: 8px; font-weight: 500;">' +
                                '<i class="fa fa-info-circle"></i> Currently pursuing in this semester. Marks not yet submitted.' +
                              '</div>'));
            } else {
                body.append($('<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; color: #94a3b8; font-size: 13px; padding: 10px; border-radius: 8px; text-align: center; margin-top: 8px; font-style: italic;">' +
                                'No course records / exam marks found.' +
                              '</div>'));
            }

            // Credits row
            var metaRow = $('<div class="semester-meta-row">' +
                                '<div class="meta-col">' +
                                    '<span class="meta-lbl">Credits Earned</span>' +
                                    '<span class="meta-val">' + sem.credits.toFixed(2) + '</span>' +
                                '</div>' +
                                '<div class="meta-col" style="text-align: right;">' +
                                    '<span class="meta-lbl">Progress</span>' +
                                    '<span class="meta-val" style="color: ' + (sem.status === 'Completed' ? '#166534' : (sem.status === 'Current Semester' ? '#423cbc' : '#64748b')) + '">' +
                                        sem.status +
                                    '</span>' +
                                '</div>' +
                            '</div>');
            body.append(metaRow);
            if (sem.progress !== null && sem.progress !== undefined) {
                body.append($('<div style="font-size: 13px; color: #475569;"><strong>Semester Progress:</strong> ' + sem.progress + '%</div>'));
            }

            card.append(body);
            grid.append(card);
        });

        $('#progressReportSection').fadeIn(500);
    }
</script>
