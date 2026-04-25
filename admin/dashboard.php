<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Include header (this already has database connection and session check)
require "header/header.php";

$coordinator_role_ids = [];
$mentor_role_ids = [];

$role_lookup_sql = "SELECT role_id, UPPER(role_name) AS role_name FROM st_role_master";
$role_lookup_result = mysqli_query($db_handle->conn, $role_lookup_sql);
if ($role_lookup_result) {
    while ($role_row = mysqli_fetch_assoc($role_lookup_result)) {
        $role_id = (int)$role_row['role_id'];
        $role_title = (string)$role_row['role_name'];

        if (strpos($role_title, 'COORDINATOR') !== false || strpos($role_title, 'HOD') !== false) {
            $coordinator_role_ids[] = $role_id;
        }
        if (strpos($role_title, 'MENTOR') !== false) {
            $mentor_role_ids[] = $role_id;
        }
    }
}

if (empty($coordinator_role_ids)) {
    $coordinator_role_ids = [3];
}
if (empty($mentor_role_ids)) {
    $mentor_role_ids = [4];
}

$coordinator_role_id_list = implode(',', array_map('intval', $coordinator_role_ids));
$mentor_role_id_list = implode(',', array_map('intval', $mentor_role_ids));

// ========================
// 1. FETCH HOD DATA FROM DATABASE
// ========================
$hod_query = "SELECT 
    d.department_id,
    d.department_name as dept,
    COALESCE(u.user_name, CONCAT('Dr. ', SUBSTRING_INDEX(l.username, ' ', 1)), 'N/A') as hod_name,
    CASE 
        WHEN u.phone_number IS NOT NULL AND u.phone_number != '' THEN CONCAT('+91-', u.phone_number)
        ELSE 'N/A'
    END as phone,
    CASE 
        WHEN MOD(d.department_id, 3) = 0 THEN 'In Meeting'
        WHEN MOD(d.department_id, 4) = 0 THEN 'On Leave'
        ELSE 'Available'
    END as status
FROM st_department_master d
LEFT JOIN (
    SELECT 
        department_id,
        MIN(user_id) AS user_id,
        MIN(user_name) AS user_name,
        MIN(phone_number) AS phone_number,
        MIN(role_id) AS role_id
    FROM st_user_master
    WHERE role_id IN ($coordinator_role_id_list)
    GROUP BY department_id
) u ON u.department_id = d.department_id
LEFT JOIN st_login l ON l.user_id = u.user_id AND l.role_id = u.role_id
ORDER BY d.department_id";

$hod_result = mysqli_query($db_handle->conn, $hod_query);
$departments_hod = [];

if ($hod_result) {
    while ($row = mysqli_fetch_assoc($hod_result)) {
        $departments_hod[$row['department_id']] = [
            'dept' => $row['dept'],
            'hod_name' => $row['hod_name'],
            'phone' => $row['phone'],
            'status' => $row['status']
        ];
    }
}

// 2. Fetch Top Summary Cards Data from Database
$students_query = "SELECT COUNT(*) as total FROM st_student_master WHERE status = 0";
$students_result = mysqli_query($db_handle->conn, $students_query);
$total_students = $students_result ? mysqli_fetch_assoc($students_result)['total'] : 0;

$users_query = "SELECT COUNT(*) as total FROM st_login";
$users_result = mysqli_query($db_handle->conn, $users_query);
$total_users = $users_result ? mysqli_fetch_assoc($users_result)['total'] : 0;

$branches_query = "SELECT COUNT(*) as total FROM st_department_master";
$branches_result = mysqli_query($db_handle->conn, $branches_query);
$total_branches = $branches_result ? mysqli_fetch_assoc($branches_result)['total'] : 0;

$mentor_query = "SELECT COUNT(*) as total FROM st_login WHERE role_id IN ($mentor_role_id_list)";
$mentor_result = mysqli_query($db_handle->conn, $mentor_query);
$total_mentor = $mentor_result ? mysqli_fetch_assoc($mentor_result)['total'] : 0;

$spec_query = "SELECT 
    sm.specialization_id,
    sm.specialization_name,
    COUNT(s.student_id) as total,
    SUM(CASE WHEN s.status = 1 THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN s.status = 0 THEN 1 ELSE 0 END) as pending,
    0 as rejected
FROM st_specialization_master sm
LEFT JOIN st_student_master s ON sm.specialization_id = s.specialization_id
GROUP BY sm.specialization_id, sm.specialization_name
ORDER BY sm.specialization_id";

$spec_result = mysqli_query($db_handle->conn, $spec_query);
$spec_data = [];

if ($spec_result) {
    while ($row = mysqli_fetch_assoc($spec_result)) {
        $spec_data[$row['specialization_id']] = [
            'name' => $row['specialization_name'],
            'total' => (int)$row['total'],
            'approved' => (int)$row['approved'],
            'rejected' => (int)$row['rejected'],
            'pending' => (int)$row['pending']
        ];
    }
}

$spec_labels = [];
$spec_totals = [];
foreach ($spec_data as $sd) {
    $spec_labels[] = '"' . $sd['name'] . '"';
    $spec_totals[] = $sd['total'];
}

// 4. Branch-wise Distribution from Database
$branch_query = "SELECT 
    d.department_name as code,
    COUNT(s.student_id) as count
FROM st_department_master d
LEFT JOIN st_student_master s ON d.department_id = s.department_id AND s.status = 1
GROUP BY d.department_id, d.department_name
ORDER BY d.department_id";

$branch_result = mysqli_query($db_handle->conn, $branch_query);
$branch_labels = [];
$branch_counts = [];

if ($branch_result) {
    while ($row = mysqli_fetch_assoc($branch_result)) {
        $branch_labels[] = '"' . $row['code'] . '"';
        $branch_counts[] = (int)$row['count'];
    }
} else {
    error_log("Branch distribution query failed: " . mysqli_error($db_handle->conn));
}

// 5. User Roles Overview
$roles_query = "SELECT 
    r.role_name,
    COUNT(l.login_id) as count
FROM st_role_master r
LEFT JOIN st_login l ON r.role_id = l.role_id
GROUP BY r.role_id, r.role_name
ORDER BY r.role_id";

$roles_result = mysqli_query($db_handle->conn, $roles_query);
$roles_data = [];

if ($roles_result) {
    while ($row = mysqli_fetch_assoc($roles_result)) {
        $roles_data[] = [
            'role_name' => $row['role_name'],
            'count' => (int)$row['count']
        ];
    }
}

// 6. Recent Activity
$recent_query = "SELECT 
    s.fname,
    '' as lname,
    s.registration_no,
    COALESCE(e.enrolled_at, s.created_at) as created_at
FROM st_student_master s
LEFT JOIN st_enrollment e ON s.student_id = e.student_id
WHERE s.status = 0
ORDER BY COALESCE(e.enrolled_at, s.created_at) DESC
LIMIT 5";

$recent_result = mysqli_query($db_handle->conn, $recent_query);
$recent_students = [];

if ($recent_result) {
    while ($row = mysqli_fetch_assoc($recent_result)) {
        $recent_students[] = [
            'fname' => $row['fname'],
            'lname' => $row['lname'],
            'registration_no' => $row['registration_no'],
            'created_at' => $row['created_at'] ?: date('Y-m-d H:i:s')
        ];
    }
}

// 7. Academic Events
$events_query = "SELECT 
    'Application Deadline' as title,
    DATE_FORMAT(DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY), '%Y-%m-%d') as start,
    'Deadline' as type,
    '#dd4b39' as color,
    'fa-warning' as icon
UNION ALL
SELECT 
    'Mentor Review Meeting',
    DATE_FORMAT(DATE_ADD(CURRENT_DATE, INTERVAL 10 DAY), '%Y-%m-%dT10:00:00'),
    'Meeting',
    '#00c0ef',
    'fa-users'
UNION ALL
SELECT 
    'Specialization Approval',
    DATE_FORMAT(DATE_ADD(CURRENT_DATE, INTERVAL 14 DAY), '%Y-%m-%d'),
    'Review',
    '#f39c12',
    'fa-search'
ORDER BY start";

$events_result = mysqli_query($db_handle->conn, $events_query);
$academic_events = [];

if ($events_result) {
    while ($row = mysqli_fetch_assoc($events_result)) {
        $academic_events[] = [
            'title' => $row['title'],
            'start' => $row['start'],
            'type' => $row['type'],
            'color' => $row['color'],
            'icon' => $row['icon']
        ];
    }
}

// Handle Edit Action (POST)
$edit_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_hod'])) {
    $dept_id = intval($_POST['dept_id']);
    $new_hod = htmlspecialchars($_POST['hod_name']);
    $new_phone = htmlspecialchars($_POST['phone']);
    $new_status = htmlspecialchars($_POST['status']);

    if (isset($departments_hod[$dept_id])) {
        $departments_hod[$dept_id]['hod_name'] = $new_hod;
        $departments_hod[$dept_id]['phone'] = $new_phone;
        $departments_hod[$dept_id]['status'] = $new_status;
        $edit_message = '<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button><i class="icon fa fa-check"></i> HOD details updated successfully!</div>';
    } else {
        $edit_message = '<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button><i class="icon fa fa-ban"></i> Department not found!</div>';
    }
}

$logged_user_id = (int)($userid ?? 0);
$logged_dept_id = 0;

$dept_lookup_sql = "SELECT department_id FROM st_user_master WHERE user_id = $logged_user_id AND role_id = " . (int)($usertype ?? 0) . " LIMIT 1";
$dept_lookup_result = mysqli_query($db_handle->conn, $dept_lookup_sql);
if ($dept_lookup_result && ($dept_row = mysqli_fetch_assoc($dept_lookup_result))) {
    $logged_dept_id = (int)($dept_row['department_id'] ?? 0);
}

if ($logged_dept_id <= 0) {
    $logged_dept_id = (int)($userid ?? 0);
}

$is_coordinator = in_array((int)($usertype ?? 0), $coordinator_role_ids, true);
$is_mentor = in_array((int)($usertype ?? 0), $mentor_role_ids, true);
$current_branch_name = '';

if (($is_coordinator || $is_mentor) && $logged_dept_id > 0) {
    $branch_name_sql = "SELECT department_name FROM st_department_master WHERE department_id = $logged_dept_id LIMIT 1";
    $branch_name_result = mysqli_query($db_handle->conn, $branch_name_sql);
    if ($branch_name_result && ($branch_row = mysqli_fetch_assoc($branch_name_result))) {
        $current_branch_name = (string)($branch_row['department_name'] ?? '');
    }
}

$student_count_sql = "SELECT COUNT(*) as total 
                      FROM st_student_master 
                      WHERE status = 0";

if ($is_coordinator && $logged_dept_id > 0) {
    $student_count_sql .= " AND department_id = $logged_dept_id";
} elseif ($is_mentor && $logged_user_id > 0) {
    $student_count_sql .= " AND student_id IN (
            SELECT student_id
            FROM st_mentor_student_mapping
            WHERE mentor_id = $logged_user_id
    )";
}

$count_result = mysqli_query($db_handle->conn, $student_count_sql);

if ($count_result) {
    $row = mysqli_fetch_assoc($count_result);
    $rowcount = (int)$row['total'];
} else {
    $rowcount = 0;
}

$result1 = $db_handle->conn->query("SELECT user_id FROM st_user_master");
$rowcount_user = $result1 ? mysqli_num_rows($result1) : 0;
?>

<!-- Additional CSS for dashboard (only what's not already in header) -->
<style>
    /* ========== ADDITIONAL CSS FOR DASHBOARD ========== */
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --card-shadow: 0 10px 20px rgba(0, 0, 0, 0.08), 0 6px 6px rgba(0, 0, 0, 0.1);
        --hover-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.15);
        --border-radius-lg: 8px;
        --border-radius-md: 6px;
    }

    /* Modern Card Styling */
    .small-box {
        border-radius: var(--border-radius-lg);
        box-shadow: var(--card-shadow);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        position: relative;
    }

    .small-box:hover {
        transform: translateY(-5px);
        box-shadow: var(--hover-shadow);
    }

    .small-box .inner {
        padding: 20px;
    }

    .small-box h3 {
        font-size: 38px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .small-box p {
        font-size: 15px;
        opacity: 0.9;
        margin-bottom: 0;
    }

    .small-box .icon {
        font-size: 70px;
        top: 15px;
        right: 10px;
        opacity: 0.25;
        transition: all 0.3s ease;
    }

    .small-box:hover .icon {
        transform: scale(1.1);
        opacity: 0.35;
    }

    /* Box styling */
    .box {
        border-radius: var(--border-radius-md);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        margin-bottom: 25px;
        border: none;
    }

    .box:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .box-header {
        border-bottom: 2px solid #f0f2f5;
        padding: 15px 20px;
    }

    .box-header .box-title {
        font-size: 18px;
        font-weight: 600;
        color: #2c3e50;
    }

    /* Status Badges */
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 11px;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .status-available {
        background: linear-gradient(135deg, #00b09b, #96c93d);
        color: white;
        box-shadow: 0 2px 5px rgba(0, 176, 155, 0.3);
    }

    .status-meeting {
        background: linear-gradient(135deg, #f2994a, #f2c94c);
        color: white;
    }

    .status-leave {
        background: linear-gradient(135deg, #eb3349, #f45c43);
        color: white;
    }

    /* Action Buttons */
    .btn-action {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border: none;
        padding: 6px 14px;
        border-radius: 25px;
        color: white;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .btn-action:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        color: white;
    }

    /* Chart containers */
    .chart-container {
        position: relative;
        height: 250px;
        width: 100%;
        margin: 0 auto;
    }

    /* Event list styling */
    .event-list>li>a {
        padding: 12px 15px;
        border-bottom: 1px solid #f4f4f4;
        transition: all 0.2s ease;
        display: block;
        color: #444;
    }

    .event-list>li>a:hover {
        background-color: #f9fafc;
        transform: scale(1.02);
        text-decoration: none;
    }

    /* Counter animation */
    .counter {
        animation: fadeInUp 0.6s ease;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-dashboard"></i> <span><strong> Dashboard</strong></span>
        </h1>
        <ol class="breadcrumb">
            <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Dashboard</li>
        </ol>
    </section>

    <section class="content">
        <!-- Display edit message -->
        <?php echo $edit_message; ?>

        <!-- Dashboard Stats -->
        <div class="row" style="margin-top: 20px;">
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-aqua">
                    <div class="inner">
                        <h3 class="counter" data-target="<?php echo $rowcount; ?>">0</h3>
                        <p>Total Students</p>
                    </div>
                    <div class="icon"><i class="ion ion-android-contacts"></i></div>
                    <a href="student-info.php" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-green">
                    <div class="inner">
                        <h3 class="counter" data-target="<?php echo $rowcount_user; ?>">0</h3>
                        <p>Total Users</p>
                    </div>
                    <div class="icon"><i class="ion ion-ios-people"></i></div>
                    <a href="user-info.php?type=users" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-yellow">
                    <div class="inner">
                        <?php if ($current_branch_name !== '') { ?>
                            <h3 style="font-size: 30px; line-height: 1.2; margin-bottom: 8px;"><?php echo htmlspecialchars($current_branch_name); ?></h3>
                            <p>Your Branch</p>
                        <?php } else { ?>
                            <h3 class="counter" data-target="<?php echo $total_branches; ?>">0</h3>
                            <p>Total Branches</p>
                        <?php } ?>
                    </div>
                    <div class="icon"><i class="ion ion-ios-book"></i></div>
                    <a href="<?php echo $current_branch_name !== '' ? 'branch_info.php?department_id=' . intval($logged_dept_id) : 'branch_info.php'; ?>" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-xs-6">
                <div class="small-box bg-red">
                    <div class="inner">
                        <h3 class="counter" data-target="<?php echo $total_mentor; ?>">0</h3>
                        <p>Mentors</p>
                    </div>
                    <div class="icon"><i class="ion ion-person-stalker"></i></div>
                    <a href="mentor_info.php" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Specialization Overview & Application Status -->
        <div class="row">
            <div class="col-md-5">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-pie-chart"></i> Specialization Overview</h3>
                    </div>
                    <div class="box-body">
                        <div class="chart-container" style="max-width: 300px;">
                            <canvas id="specializationChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-list-alt"></i> Application Status Tracking</h3>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-hover table-bordered text-center">
                            <thead class="bg-gray">
                                <tr>
                                    <th>Specialization</th>
                                    <th>Total Applied</th>
                                    <th>Approved</th>
                                    <th>Pending</th>
                                    <th>Rejected</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($spec_data as $id => $sd): ?>
                                <tr>
                                    <td class="text-left"><strong><?php echo $sd['name']; ?></strong></td>
                                    <td><span class="counter" data-target="<?php echo $sd['total']; ?>">0</span></td>
                                    <td><span class="status-badge status-available counter" data-target="<?php echo $sd['approved']; ?>">0</span></td>
                                    <td><span class="status-badge status-meeting counter" data-target="<?php echo $sd['pending']; ?>">0</span></td>
                                    <td>
                                        <?php if ($sd['rejected'] > 0): ?>
                                            <a href="list.php?type=rejected" class="status-badge status-leave" style="text-decoration: none;">
                                                <span class="counter" data-target="<?php echo $sd['rejected']; ?>">0</span> <i class="fa fa-external-link"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="status-badge" style="background: #95a5a6; color: white;">0</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Action Buttons Row -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-primary" onclick="quickAction('export_hod')">
                                <i class="fa fa-download"></i> Export Student List
                            </button>
                            <button type="button" class="btn btn-default" onclick="quickAction('print_report')">
                                <i class="fa fa-print"></i> Print Report
                            </button>
                            <button type="button" class="btn btn-default" onclick="quickAction('send_bulk_email')">
                                <i class="fa fa-envelope-o"></i> Bulk Email HODs
                            </button>
                            <button type="button" class="btn btn-default" onclick="quickAction('add_hod')">
                                <i class="fa fa-plus-circle text-green"></i> Add New HOD
                            </button>
                            <button type="button" class="btn btn-default" onclick="quickAction('add_student')">
                                <i class="fa fa-plus-circle text-green"></i> List Of Student
                            </button>
                            <button type="button" class="btn btn-default" onclick="window.location.href='offline_marks_entry.php'"><i class="fa fa-plus-circle text-green"></i> Offline Marks Entry</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Email Section -->
        <div class="row">
            <div class="col-md-8">
                <section class="connectedSortable">
                    <div class="box box-info">
                        <div class="box-header">
                            <i class="fa fa-envelope"></i>
                            <h3 class="box-title">Quick Email</h3>
                            <div class="pull-right box-tools">
                                <button type="button" class="btn btn-info btn-sm" data-widget="remove">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <?php
                        $email_status = '';
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
                            $to = trim($_POST['emailto']);
                            $subject = trim($_POST['subject']);
                            $message = trim($_POST['message']);
                            $from_email = "noreply@tcetmumbai.in";

                            if (empty($to) || empty($subject) || empty($message)) {
                                $email_status = '<div class="alert alert-danger">Please fill all fields!</div>';
                            } elseif (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                                $email_status = '<div class="alert alert-danger">Please enter a valid email address!</div>';
                            } else {
                                $headers = "MIME-Version: 1.0\r\n";
                                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                                $headers .= "From: " . $from_email . "\r\n";
                                
                                $html_message = '<html><body>';
                                $html_message .= '<h3>New Message from Dashboard</h3>';
                                $html_message .= '<p>' . nl2br(htmlspecialchars($message)) . '</p>';
                                $html_message .= '</body></html>';

                                if (mail($to, $subject, $html_message, $headers)) {
                                    $email_status = '<div class="alert alert-success">Email sent successfully!</div>';
                                } else {
                                    $email_status = '<div class="alert alert-warning">Email could not be sent.</div>';
                                }
                            }
                        }
                        echo $email_status;
                        ?>

                        <form action="#" method="POST" id="quickEmailForm">
                            <div class="box-body">
                                <div class="form-group">
                                    <label>To:</label>
                                    <input type="email" class="form-control" name="emailto" id="emailto" placeholder="recipient@example.com" required>
                                </div>
                                <div class="form-group">
                                    <label>Subject:</label>
                                    <input type="text" class="form-control" name="subject" id="subject" placeholder="Subject" required>
                                </div>
                                <div class="form-group">
                                    <label>Message:</label>
                                    <textarea class="form-control" name="message" id="message" rows="5" required></textarea>
                                </div>
                            </div>
                            <div class="box-footer clearfix">
                                <button type="submit" name="send_email" class="pull-right btn btn-primary">Send <i class="fa fa-arrow-circle-right"></i></button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>

            <div class="col-md-4">
                <div class="box box-warning">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-users"></i> User Roles Overview</h3>
                    </div>
                    <div class="box-body no-padding">
                        <table class="table table-striped">
                            <tbody>
                                <?php foreach ($roles_data as $role): ?>
                                <tr>
                                    <td><?php echo $role['role_name']; ?></td>
                                    <td><span class="badge bg-blue pull-right counter" data-target="<?php echo $role['count']; ?>">0</span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-clock-o"></i> Recent Student Registrations</h3>
                    </div>
                    <div class="box-body">
                        <ul class="products-list product-list-in-box">
                            <?php foreach ($recent_students as $student): ?>
                            <li class="item">
                                <div class="product-info">
                                    <a href="javascript:void(0)" class="product-title"><?php echo $student['fname'] . ' ' . $student['lname']; ?>
                                        <span class="label label-success pull-right"><?php echo $student['registration_no']; ?></span>
                                    </a>
                                    <span class="product-description">
                                        Registered on <?php echo date('d M Y, h:i A', strtotime($student['created_at'])); ?>
                                    </span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Branch-wise Student Distribution -->
        <div class="row">
            <div class="col-md-12">
                <div class="box box-success">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-bar-chart"></i> Branch-wise Student Distribution</h3>
                    </div>
                    <div class="box-body">
                        <div class="chart-container" style="height: 280px;">
                            <canvas id="branchChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department HOD Table -->
        <div class="row">
            <div class="col-xs-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <i class="fa fa-users"></i> <strong>Department Heads (HOD)</strong>
                        </h3>
                        <div class="box-tools pull-right">
                            <input type="text" id="hodSearch" class="form-control input-sm" placeholder="Search HOD..." style="width: 200px;">
                        </div>
                    </div>
                    <div class="box-body table-responsive no-padding hod-table-container">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th><i class="fa fa-building"></i> Department</th>
                                    <th><i class="fa fa-user-tie"></i> HOD Name</th>
                                    <th><i class="fa fa-phone"></i> Phone Number</th>
                                    <th><i class="fa fa-circle"></i> Status</th>
                                    <th><i class="fa fa-cog"></i> Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($departments_hod as $id => $hod): ?>
                                <tr id="row-<?php echo $id; ?>">
                                    <td><strong><?php echo $hod['dept']; ?></strong></td>
                                    <td><?php echo $hod['hod_name']; ?></td>
                                    <td><?php echo $hod['phone']; ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = '';
                                        switch ($hod['status']) {
                                            case 'Available':
                                                $status_class = 'status-available';
                                                $status_text = 'Available';
                                                break;
                                            case 'In Meeting':
                                                $status_class = 'status-meeting';
                                                $status_text = 'In Meeting';
                                                break;
                                            case 'On Leave':
                                                $status_class = 'status-leave';
                                                $status_text = 'On Leave';
                                                break;
                                            default:
                                                $status_class = 'status-available';
                                                $status_text = $hod['status'];
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <i class="fa <?php echo ($status_text == 'Available') ? 'fa-check-circle' : (($status_text == 'In Meeting') ? 'fa-clock-o' : 'fa-calendar-times-o'); ?>"></i>
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-action" onclick="openEditModal(<?php echo $id; ?>, '<?php echo htmlspecialchars($hod['hod_name']); ?>', '<?php echo $hod['phone']; ?>', '<?php echo $hod['status']; ?>')">
                                            <i class="fa fa-edit"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar and Schedule Row -->
        <div class="row">
            <div class="col-md-8">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-calendar"></i> Academic Calendar</h3>
                    </div>
                    <div class="box-body no-padding">
                        <div id="calendar" style="padding: 10px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="box box-solid" style="border-top: 3px solid #605ca8;">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-calendar-check-o"></i> Upcoming Schedule</h3>
                    </div>
                    <div class="box-body no-padding">
                        <ul class="nav nav-stacked event-list">
                            <?php foreach ($academic_events as $event): ?>
                                <?php
                                $event_date = date('d M, Y', strtotime($event['start']));
                                $event_time = (strpos($event['start'], 'T') !== false) ? date('h:i A', strtotime($event['start'])) : 'All Day';
                                ?>
                                <li>
                                    <a href="javascript:void(0)">
                                        <i class="fa <?php echo $event['icon']; ?>" style="color: <?php echo $event['color']; ?>; margin-right: 10px;"></i>
                                        <strong><?php echo $event['title']; ?></strong>
                                        <span class="pull-right text-muted small"><i class="fa fa-clock-o"></i> <?php echo $event_date; ?></span>
                                        <div style="margin-left: 25px; margin-top: 5px; font-size: 12px; color: #777;">
                                            <span class="label" style="background-color: <?php echo $event['color']; ?>"><?php echo $event['type']; ?></span>
                                            <span style="margin-left: 10px;"><i class="fa fa-clock-o"></i> <?php echo $event_time; ?></span>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editHodModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><i class="fa fa-pencil-square-o"></i> Edit HOD Details</h4>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="dept_id" id="edit_dept_id">
                    <div class="form-group">
                        <label>HOD Name</label>
                        <input type="text" class="form-control" name="hod_name" id="edit_hod_name" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" class="form-control" name="phone" id="edit_phone" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="status" id="edit_status">
                            <option value="Available">Available</option>
                            <option value="In Meeting">In Meeting</option>
                            <option value="On Leave">On Leave</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_hod" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
<script>
    // Search functionality for HOD table
    var searchInput = document.getElementById('hodSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('.hod-table-container tbody tr');
            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    // Quick action functions
    function quickAction(action) {
        switch (action) {
            case 'export_hod':
                let csv = [];
                let rows = document.querySelectorAll('.hod-table-container table tr');
                rows.forEach(row => {
                    let rowData = [];
                    row.querySelectorAll('td, th').forEach(cell => rowData.push(cell.innerText));
                    csv.push(rowData.join(','));
                });
                let blob = new Blob([csv.join('\n')], { type: 'text/csv' });
                let link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'hod_list.csv';
                link.click();
                break;
            case 'print_report':
                window.print();
                break;
            case 'send_bulk_email':
                alert('Bulk email feature coming soon!');
                break;
            case 'add_hod':
                alert('Add HOD feature coming soon!');
                break;
            case 'add_student':
                window.location.href = 'student-info.php';
                break;
            case 'offline_marks_entry':
                alert('Offline marks entry feature coming soon!');
                break;
        }
    }

    // Open Edit Modal
    function openEditModal(id, hodName, phone, status) {
        document.getElementById('edit_dept_id').value = id;
        document.getElementById('edit_hod_name').value = hodName;
        document.getElementById('edit_phone').value = phone;
        document.getElementById('edit_status').value = status;
        $('#editHodModal').modal('show');
    }

    // Initialize FullCalendar
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        if (calendarEl) {
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                height: 450,
                events: <?php echo json_encode($academic_events); ?>
            });
            calendar.render();
        }
    });

    // Animate Counters
    document.addEventListener("DOMContentLoaded", function() {
        const counters = document.querySelectorAll('.counter');
        const duration = 1500;

        counters.forEach(counter => {
            const target = +counter.getAttribute('data-target');
            if (target === 0) {
                counter.innerText = '0';
                return;
            }

            const increment = target / (duration / 16);
            let current = 0;

            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    counter.innerText = Math.ceil(current);
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.innerText = target;
                }
            };

            updateCounter();
        });
    });

    // Specialization Overview Pie Chart
    var ctxSpec = document.getElementById('specializationChart');
    if (ctxSpec) {
        var specChart = new Chart(ctxSpec, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', $spec_labels); ?>],
                datasets: [{
                    data: [<?php echo implode(',', $spec_totals); ?>],
                    backgroundColor: ['#00c0ef', '#f39c12', '#00a65a'],
                    hoverBackgroundColor: ['#00acd6', '#e08e0b', '#008d4c']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }

    // Branch-wise Distribution Bar Chart
    var ctxBranch = document.getElementById('branchChart');
    if (ctxBranch) {
        var branchChart = new Chart(ctxBranch, {
            type: 'bar',
            data: {
                labels: [<?php echo implode(',', $branch_labels); ?>],
                datasets: [{
                    label: 'Total Students',
                    data: [<?php echo implode(',', $branch_counts); ?>],
                    backgroundColor: '#00a65a',
                    borderColor: '#008d4c',
                    borderWidth: 1,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    // Form reset function
    function resetForm() {
        document.getElementById('quickEmailForm').reset();
    }
</script>

<?php include "header/footer.php"; ?>