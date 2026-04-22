<?php
require "header/header.php";

// ========================
// GET TYPE PARAMETER
// ========================
$type = isset($_GET['type']) ? $_GET['type'] : '';
$page_title = '';
$data = [];
$columns = [];

// ========================
// DUMMY DATA ARRAYS
// ========================

// Students data
$students = [
    ['id' => 1, 'name' => 'Rahul Sharma', 'email' => 'rahul.sharma@example.com', 'department' => 'Computer Science', 'year' => '3rd Year'],
    ['id' => 2, 'name' => 'Priya Patel', 'email' => 'priya.patel@example.com', 'department' => 'Information Technology', 'year' => '2nd Year'],
    ['id' => 3, 'name' => 'Amit Kumar', 'email' => 'amit.kumar@example.com', 'department' => 'Computer Science', 'year' => '4th Year'],
    ['id' => 4, 'name' => 'Sneha Reddy', 'email' => 'sneha.reddy@example.com', 'department' => 'AI & ML', 'year' => '3rd Year'],
    ['id' => 5, 'name' => 'Vijay Singh', 'email' => 'vijay.singh@example.com', 'department' => 'Mechanical Engineering', 'year' => '2nd Year'],
    ['id' => 6, 'name' => 'Anjali Gupta', 'email' => 'anjali.gupta@example.com', 'department' => 'Electrical Engineering', 'year' => '4th Year'],
    ['id' => 7, 'name' => 'Rohit Verma', 'email' => 'rohit.verma@example.com', 'department' => 'Civil Engineering', 'year' => '3rd Year'],
    ['id' => 8, 'name' => 'Kavita Nair', 'email' => 'kavita.nair@example.com', 'department' => 'Information Technology', 'year' => '2nd Year']
];

// Users data
$users = [
    ['id' => 1, 'name' => 'Dr. Arvind Kumar', 'email' => 'arvind.kumar@tcet.edu', 'role' => 'Super Admin', 'department' => 'Computer Science'],
    ['id' => 2, 'name' => 'Prof. Meera Sharma', 'email' => 'meera.sharma@tcet.edu', 'role' => 'Admin', 'department' => 'Information Technology'],
    ['id' => 3, 'name' => 'Dr. Rahul Verma', 'email' => 'rahul.verma@tcet.edu', 'role' => 'Coordinator', 'department' => 'AI & ML'],
    ['id' => 4, 'name' => 'Prof. Sneha Patil', 'email' => 'sneha.patil@tcet.edu', 'role' => 'Mentor', 'department' => 'Computer Science'],
    ['id' => 5, 'name' => 'John Doe', 'email' => 'john.doe@tcet.edu', 'role' => 'Student', 'department' => 'Information Technology']
];

// Branches data
$branches = [
    ['id' => 1, 'name' => 'Computer Science', 'code' => 'CSE', 'hod' => 'Dr. Arvind Kumar', 'students_count' => 150],
    ['id' => 2, 'name' => 'Information Technology', 'code' => 'IT', 'hod' => 'Prof. Meera Sharma', 'students_count' => 120],
    ['id' => 3, 'name' => 'Artificial Intelligence & ML', 'code' => 'AIML', 'hod' => 'Dr. Rahul Verma', 'students_count' => 90],
    ['id' => 4, 'name' => 'AI & Data Science', 'code' => 'AIDS', 'hod' => 'Prof. Sneha Patil', 'students_count' => 80],
    ['id' => 5, 'name' => 'Mechanical Engineering', 'code' => 'ME', 'hod' => 'Dr. Suresh Nair', 'students_count' => 40],
    ['id' => 6, 'name' => 'Civil Engineering', 'code' => 'CE', 'hod' => 'Prof. Anita Desai', 'students_count' => 20],
    ['id' => 7, 'name' => 'Electrical Engineering', 'code' => 'EE', 'hod' => 'Dr. Manoj Gupta', 'students_count' => 20]
];

// Mentors data
$mentors = [
    ['id' => 1, 'name' => 'Prof. Sneha Patil', 'email' => 'sneha.patil@tcet.edu', 'department' => 'Computer Science', 'students_assigned' => 25],
    ['id' => 2, 'name' => 'Dr. Rahul Verma', 'email' => 'rahul.verma@tcet.edu', 'department' => 'AI & ML', 'students_assigned' => 18],
    ['id' => 3, 'name' => 'Prof. Anita Desai', 'email' => 'anita.desai@tcet.edu', 'department' => 'Civil Engineering', 'students_assigned' => 8],
    ['id' => 4, 'name' => 'Dr. Manoj Gupta', 'email' => 'manoj.gupta@tcet.edu', 'department' => 'Electrical Engineering', 'students_assigned' => 10],
    ['id' => 5, 'name' => 'Dr. Suresh Nair', 'email' => 'suresh.nair@tcet.edu', 'department' => 'Mechanical Engineering', 'students_assigned' => 15]
];

// Rejected students data
$rejected_students = [
    ['id' => 101, 'name' => 'Karan Malhotra', 'email' => 'karan.malhotra@example.com', 'status' => 'Rejected', 'reason' => 'Incomplete Documents'],
    ['id' => 102, 'name' => 'Neha Singh', 'email' => 'neha.singh@example.com', 'status' => 'Rejected', 'reason' => 'Low Score'],
    ['id' => 103, 'name' => 'Rohit Joshi', 'email' => 'rohit.joshi@example.com', 'status' => 'Rejected', 'reason' => 'Invalid Details'],
    ['id' => 104, 'name' => 'Pooja Sharma', 'email' => 'pooja.sharma@example.com', 'status' => 'Rejected', 'reason' => 'Missing Requirements'],
    ['id' => 105, 'name' => 'Amit Patel', 'email' => 'amit.patel@example.com', 'status' => 'Rejected', 'reason' => 'Incomplete Documents'],
    ['id' => 106, 'name' => 'Sneha Reddy', 'email' => 'sneha.reddy@example.com', 'status' => 'Rejected', 'reason' => 'Low Score'],
    ['id' => 107, 'name' => 'Vikram Singh', 'email' => 'vikram.singh@example.com', 'status' => 'Rejected', 'reason' => 'Invalid Details'],
    ['id' => 108, 'name' => 'Anjali Gupta', 'email' => 'anjali.gupta@example.com', 'status' => 'Rejected', 'reason' => 'Missing Requirements']
];

// ========================
// SWITCH BASED ON TYPE
// ========================
switch ($type) {
    case 'students':
        $page_title = 'All Students';
        $data = $students;
        $columns = ['ID', 'Name', 'Email', 'Department', 'Year'];
        break;
        
    case 'users':
        $page_title = 'All Users';
        $data = $users;
        $columns = ['ID', 'Name', 'Email', 'Role', 'Department'];
        break;
        
    case 'branches':
        $page_title = 'All Branches';
        $data = $branches;
        $columns = ['ID', 'Name', 'Code', 'HOD', 'Students Count'];
        break;
        
    case 'mentors':
        $page_title = 'All Mentors';
        $data = $mentors;
        $columns = ['ID', 'Name', 'Email', 'Department', 'Students Assigned'];
        break;
        
    case 'rejected':
        $page_title = 'Rejected Students';
        $data = $rejected_students;
        $columns = ['ID', 'Name', 'Email', 'Status', 'Rejection Reason'];
        break;
        
    default:
        $page_title = 'Unknown Type';
        $data = [];
        $columns = [];
        break;
}

?>

<style>
    .content-wrapper {
        background: #f8f9fc;
    }
    
    .box {
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: none;
    }
    
    .box-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 8px 8px 0 0;
        padding: 15px 20px;
    }
    
    .box-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }
    
    .table {
        background: white;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .table thead tr th {
        background: #f8f9fa;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
        padding: 15px;
        border: none;
    }
    
    .table tbody tr {
        transition: background-color 0.2s ease;
    }
    
    .table tbody tr:hover {
        background-color: #f8f9fc;
    }
    
    .table td {
        padding: 15px;
        vertical-align: middle;
        border-top: 1px solid #e9ecef;
    }
    
    .btn-back {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border: none;
        padding: 8px 20px;
        border-radius: 25px;
        color: white;
        font-weight: 500;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-back:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .no-data {
        text-align: center;
        padding: 40px;
        color: #6c757d;
    }
    
    .no-data i {
        font-size: 48px;
        margin-bottom: 15px;
        color: #dee2e6;
    }
    
    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    
    .badge-student { background: #007bff; }
    .badge-admin { background: #28a745; }
    .badge-coordinator { background: #ffc107; color: #212529; }
    .badge-mentor { background: #17a2b8; }
    .badge-super-admin { background: #dc3545; }
    .badge-danger { background: #dc3545; }
</style>

<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <i class="fa fa-list"></i> <span><strong><?php echo htmlspecialchars($page_title); ?></strong></span>
        </h1>
        <ol class="breadcrumb">
            <li><a href="index.php"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?php echo htmlspecialchars($page_title); ?></li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <h3 class="box-title">
                            <i class="fa fa-users"></i> 
                            <?php echo htmlspecialchars($page_title); ?>
                            <small class="pull-right">
                                Total: <span class="badge"><?php echo count($data); ?></span>
                            </small>
                        </h3>
                        <div class="box-tools pull-right">
                            <a href="index.php" class="btn-back">
                                <i class="fa fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                    
                    <div class="box-body table-responsive no-padding">
                        <?php if (!empty($data)): ?>
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <?php foreach ($columns as $column): ?>
                                            <th><?php echo htmlspecialchars($column); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data as $item): ?>
                                        <tr>
                                            <?php 
                                            // Display data based on type
                                            switch ($type) {
                                                case 'students':
                                                    echo '<td>' . $item['id'] . '</td>';
                                                    echo '<td><strong>' . htmlspecialchars($item['name']) . '</strong></td>';
                                                    echo '<td>' . htmlspecialchars($item['email']) . '</td>';
                                                    echo '<td><span class="badge badge-student">' . htmlspecialchars($item['department']) . '</span></td>';
                                                    echo '<td>' . htmlspecialchars($item['year']) . '</td>';
                                                    break;
                                                    
                                                case 'users':
                                                    echo '<td>' . $item['id'] . '</td>';
                                                    echo '<td><strong>' . htmlspecialchars($item['name']) . '</strong></td>';
                                                    echo '<td>' . htmlspecialchars($item['email']) . '</td>';
                                                    $role_class = 'badge-' . str_replace(' ', '-', strtolower($item['role']));
                                                    echo '<td><span class="badge ' . $role_class . '">' . htmlspecialchars($item['role']) . '</span></td>';
                                                    echo '<td>' . htmlspecialchars($item['department']) . '</td>';
                                                    break;
                                                    
                                                case 'branches':
                                                    echo '<td>' . $item['id'] . '</td>';
                                                    echo '<td><strong>' . htmlspecialchars($item['name']) . '</strong></td>';
                                                    echo '<td><code>' . htmlspecialchars($item['code']) . '</code></td>';
                                                    echo '<td>' . htmlspecialchars($item['hod']) . '</td>';
                                                    echo '<td><span class="badge badge-primary">' . $item['students_count'] . '</span></td>';
                                                    break;
                                                    
                                                case 'mentors':
                                                    echo '<td>' . $item['id'] . '</td>';
                                                    echo '<td><strong>' . htmlspecialchars($item['name']) . '</strong></td>';
                                                    echo '<td>' . htmlspecialchars($item['email']) . '</td>';
                                                    echo '<td><span class="badge badge-mentor">' . htmlspecialchars($item['department']) . '</span></td>';
                                                    echo '<td><span class="badge badge-info">' . $item['students_assigned'] . '</span></td>';
                                                    break;
                                                    
                                                case 'rejected':
                                                    echo '<td>' . $item['id'] . '</td>';
                                                    echo '<td><strong>' . htmlspecialchars($item['name']) . '</strong></td>';
                                                    echo '<td>' . htmlspecialchars($item['email']) . '</td>';
                                                    echo '<td><span class="badge badge-danger">' . htmlspecialchars($item['status']) . '</span></td>';
                                                    echo '<td><em>' . htmlspecialchars($item['reason']) . '</em></td>';
                                                    break;
                                                    
                                                default:
                                                    foreach ($item as $value) {
                                                        echo '<td>' . htmlspecialchars($value) . '</td>';
                                                    }
                                                    break;
                                            }
                                            ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fa fa-inbox"></i>
                                <h4>No data available</h4>
                                <p>There are no <?php echo htmlspecialchars($page_title); ?> to display.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include "header/footer.php"; ?>
