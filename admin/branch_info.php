<?php
require "header/header.php";

// Database connection
$db_handle = new DBController();

$selectedDepartmentId = intval($_GET['department_id'] ?? 0);

// Fetch all branches from database
$branches_query = "SELECT 
    d.department_id as id,
    d.department_name as name,
    CASE 
        WHEN LOCATE(' ', d.department_name) > 0 
        THEN UPPER(SUBSTRING(d.department_name, 1, LOCATE(' ', d.department_name) - 1))
        ELSE UPPER(d.department_name)
    END as code,
    (SELECT COUNT(*) FROM st_student_master WHERE department_id = d.department_id AND status = 0
    ) as total_students,
    (SELECT COUNT(*) FROM st_user_master WHERE department_id = d.department_id AND role_id = 2) as total_hods,
    (SELECT COUNT(*) FROM st_user_master WHERE department_id = d.department_id AND role_id IN (3,4)) as total_staff
FROM st_department_master d
" . ($selectedDepartmentId > 0 ? "WHERE d.department_id = $selectedDepartmentId " : "") . "
ORDER BY d.department_id";

$branches_result = mysqli_query($db_handle->conn, $branches_query);
$branches = [];

if ($branches_result) {
    while ($row = mysqli_fetch_assoc($branches_result)) {
        $branches[] = $row;
    }
} else {
    error_log("Branches query failed: " . mysqli_error($db_handle->conn));
}

// Get total branches count
$total_branches = count($branches);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Branch Information</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
    <style>
        :root {
            --primary: #423cbc;
            --primary-dark: #352fa1;
            --success: #16a34a;
            --warning: #d97706;
            --danger: #dc2626;
            --info: #0284c7;
            --bg-light: #f8fafc;
            --card-shadow: 0 1px 3px rgba(0,0,0,0.03);
            --hover-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }

        .content-wrapper { background: var(--bg-light); min-height: 100vh; padding: 20px; }

        /* Page Header */
        .page-header {
            background: #ffffff;
            color: #0f172a;
            padding: 16px 20px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--card-shadow);
        }

        .page-header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
        }

        .page-header p {
            margin: 3px 0 0 0;
            font-size: 12px;
            color: #64748b;
        }

        .back-btn {
            background: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 6px 14px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.15s ease;
        }

        .back-btn:hover {
            background: #f8fafc;
            color: #0f172a;
            text-decoration: none;
        }

        /* Stats Bar */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-item {
            background: white;
            padding: 16px;
            border-radius: 6px;
            border: 1px solid var(--erp-border, #e2e8f0);
            box-shadow: var(--card-shadow);
            text-align: center;
            transition: all 0.15s ease;
        }

        .stat-item:hover { transform: translateY(-2px); }

        .stat-number {
            font-size: 26px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 2px;
        }

        .stat-label {
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            font-weight: 600;
        }

        /* Branch Cards Grid */
        .branches-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
        }

        .branch-card {
            background: white;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid var(--erp-border, #e2e8f0);
            box-shadow: var(--card-shadow);
            transition: all 0.15s ease;
        }

        .branch-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .branch-header {
            background: #f8fafc;
            color: #0f172a;
            padding: 14px 18px;
            position: relative;
            border-bottom: 1px solid var(--erp-border, #e2e8f0);
        }

        .branch-header .branch-code {
            position: absolute;
            top: 12px;
            right: 14px;
            background: var(--erp-primary-light, #eef2ff);
            color: var(--primary);
            border: 1px solid var(--erp-primary-border, #c7d2fe);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .branch-header h3 {
            margin: 0 0 2px 0;
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
        }

        .branch-header .branch-id {
            font-size: 11px;
            color: #64748b;
        }

        .branch-body {
            padding: 16px 18px;
        }

        .metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .metric-row:last-child { border-bottom: none; }

        .metric-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #475569;
            font-size: 13px;
        }

        .metric-label i {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .metric-value {
            font-weight: 700;
            font-size: 16px;
        }

        .metric-value.students { color: var(--success); }
        .metric-value.hods { color: var(--warning); }
        .metric-value.staff { color: var(--info); }

        /* Status Badge */
        .status-badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-active { background: #e8f5e9; color: #2e7d32; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header { flex-direction: column; text-align: center; gap: 15px; }
            .branches-grid { grid-template-columns: 1fr; }
            .stats-bar { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1><i class="fa fa-building"></i> Branch Information</h1>
                <p><?php echo $selectedDepartmentId > 0 ? 'Viewing your branch only' : 'View all departments and their details'; ?></p>
            </div>
            <a href="index.php" class="back-btn">
                <i class="fa fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Stats Bar -->
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-number"><?php echo $total_branches; ?></div>
                <div class="stat-label">Total Branches</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo array_sum(array_column($branches, 'total_students')); ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo array_sum(array_column($branches, 'total_hods')); ?></div>
                <div class="stat-label">Total HODs</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo array_sum(array_column($branches, 'total_staff')); ?></div>
                <div class="stat-label">Total Staff</div>
            </div>
        </div>

        <!-- Branches Grid -->
        <?php if (empty($branches)): ?>
            <div class="empty-state">
                <i class="fa fa-building"></i>
                <h3>No Branches Found</h3>
                <p>No branch data available in the database.</p>
            </div>
        <?php else: ?>
            <div class="branches-grid">
                <?php foreach ($branches as $branch): ?>
                    <div class="branch-card">
                        <div class="branch-header">
                            <span class="branch-code"><?php echo htmlspecialchars($branch['code']); ?></span>
                            <h3><?php echo htmlspecialchars($branch['name']); ?></h3>
                            <span class="branch-id">ID: <?php echo htmlspecialchars($branch['id']); ?></span>
                        </div>
                        <div class="branch-body">
                            <div class="metric-row">
                                <div class="metric-label">
                                    <i class="fa fa-graduation-cap" style="background: #e8f5e9; color: #2e7d32;"></i>
                                    Total Students
                                </div>
                                <div class="metric-value students">
                                    <?php echo (int)$branch['total_students']; ?>
                                </div>
                            </div>
                            <div class="metric-row">
                                <div class="metric-label">
                                    <i class="fa fa-user" style="background: #fff3e0; color: #ef6c00;"></i>
                                    HODs
                                </div>
                                <div class="metric-value hods">
                                    <?php echo (int)$branch['total_hods']; ?>
                                </div>
                            </div>
                            <div class="metric-row">
                                <div class="metric-label">
                                    <i class="fa fa-users" style="background: #e3f2fd; color: #1565c0;"></i>
                                    Staff (Mentors/Coordinators)
                                </div>
                                <div class="metric-value staff">
                                    <?php echo (int)$branch['total_staff']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php include "header/footer.php"; ?>
</body>
</html>
