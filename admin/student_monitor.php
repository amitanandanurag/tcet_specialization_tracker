<?php
require "header/header.php";

if ((int) ($usertype ?? 0) !== 1 && (int) ($usertype ?? 0) !== 2) {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}
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

    .search-input-group {
        position: relative;
        display: flex;
        width: 100%;
        max-width: 600px;
        gap: 10px;
    }

    .search-input {
        flex: 1;
        height: 38px;
        border-radius: var(--erp-radius-sm, 4px);
        border: 1px solid var(--erp-border-dark, #cbd5e1);
        padding: 6px 14px;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.15s ease;
        outline: none;
        color: var(--erp-text-main, #0f172a);
    }

    .search-input:focus {
        border-color: var(--erp-primary, #423cbc);
        box-shadow: 0 0 0 3px rgba(66, 60, 188, 0.12);
    }

    .search-btn {
        height: 38px;
        padding: 0 16px;
        border-radius: var(--erp-radius-sm, 4px);
        background: var(--erp-primary, #423cbc);
        color: white;
        border: 1px solid var(--erp-primary-hover, #352fa1);
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
    }

    .search-btn:hover {
        background: var(--erp-primary-hover, #352fa1);
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

        <!-- Search Bar Card -->
        <div class="search-card">
            <div class="search-title">
                <i class="fa fa-search" style="color: var(--erp-primary, #423cbc);"></i> Student Profile
            </div>
            <form id="searchForm" onsubmit="event.preventDefault(); searchStudent();">
                <div class="search-input-group">
                    <input type="text" id="regNoInput" class="search-input" placeholder="Search by Reg No." required autocomplete="off">
                    <button type="submit" class="search-btn">
                        <i class="fa fa-arrow-right"></i> Search
                    </button>
                </div>
            </form>
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
    function searchStudent() {
        var regNo = $('#regNoInput').val().trim();
        if (regNo === '') {
            return;
        }

        // Reset views
        $('#errorBanner').hide();
        $('#studentProfile').hide();
        $('#progressReportSection').hide();
        $('#loadingSpinner').show();

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
