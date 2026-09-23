<?php
require_once __DIR__ . '/../database/db_connect.php';

$db = new DBController();
if (!$db->conn) {
    die("Database connection failed: " . $db->last_error . "\n");
}

echo "=== TCET SPECIALIZATION TRACKER - COMPREHENSIVE ARCHITECTURE INTROSPECTION ===\n\n";

// 1. Database Tables & Row Counts
echo "--- 1. DATABASE TABLES & RECORD COUNTS ---\n";
$tablesResult = mysqli_query($db->conn, "SHOW TABLES");
$tables = [];
while ($tRow = mysqli_fetch_array($tablesResult)) {
    $tableName = $tRow[0];
    $cResult = mysqli_query($db->conn, "SELECT COUNT(*) as cnt FROM `$tableName`");
    $cnt = $cResult ? mysqli_fetch_assoc($cResult)['cnt'] : 'ERR';
    $tables[$tableName] = $cnt;
    echo sprintf("%-35s : %d records\n", $tableName, $cnt);
}

// 2. Roles in st_role_master
echo "\n--- 2. ROLES (st_role_master) ---\n";
$rolesRes = mysqli_query($db->conn, "SELECT * FROM st_role_master ORDER BY role_id ASC");
while ($r = mysqli_fetch_assoc($rolesRes)) {
    echo "Role ID: {$r['role_id']} | Name: {$r['role_name']}\n";
}

// 3. User distribution by role
echo "\n--- 3. USER DISTRIBUTION BY ROLE (st_user_master) ---\n";
$userDist = mysqli_query($db->conn, "SELECT u.role_id, r.role_name, COUNT(*) as cnt FROM st_user_master u LEFT JOIN st_role_master r ON u.role_id = r.role_id GROUP BY u.role_id ORDER BY u.role_id");
while ($ud = mysqli_fetch_assoc($userDist)) {
    echo "Role {$ud['role_id']} ({$ud['role_name']}): {$ud['cnt']} users\n";
}

// 4. Menu & Allocation Structure
echo "\n--- 4. MENU & ACCESS MATRIX (st_menu_master & st_menu_allocation_master) ---\n";
$menuRes = mysqli_query($db->conn, "SELECT m.menu_id, m.menu_name, COUNT(DISTINCT sm.sub_menu_id) as sub_cnt FROM st_menu_master m LEFT JOIN st_sub_menu_master sm ON m.menu_id = sm.menu_id GROUP BY m.menu_id, m.menu_name ORDER BY m.menu_id");
if ($menuRes) {
    while ($m = mysqli_fetch_assoc($menuRes)) {
        echo "Menu #{$m['menu_id']}: {$m['menu_name']} (Submenus: {$m['sub_cnt']})\n";
    }
}

// 5. Specialization & Academic Masters
echo "\n--- 5. ACADEMIC STRUCTURE ---\n";
$deptRes = mysqli_query($db->conn, "SELECT department_id, department_name FROM st_department_master ORDER BY department_id");
echo "Departments:\n";
while ($d = mysqli_fetch_assoc($deptRes)) {
    echo "  - Dept {$d['department_id']}: {$d['department_name']}\n";
}

$semRes = mysqli_query($db->conn, "SELECT semester_id, semester_name FROM st_semester_master ORDER BY semester_id");
echo "Semesters:\n";
while ($s = mysqli_fetch_assoc($semRes)) {
    echo "  - Sem {$s['semester_id']}: {$s['semester_name']}\n";
}

$sessRes = mysqli_query($db->conn, "SELECT session_id, session_name FROM st_session_master ORDER BY session_id");
echo "Academic Years / Sessions:\n";
while ($ses = mysqli_fetch_assoc($sessRes)) {
    echo "  - Session {$ses['session_id']}: {$ses['session_name']}\n";
}

$specRes = mysqli_query($db->conn, "SELECT specialization_id, specialization_name, department_id FROM st_specialization_master ORDER BY specialization_id");
echo "Specialization Tracks:\n";
while ($sp = mysqli_fetch_assoc($specRes)) {
    echo "  - Spec {$sp['specialization_id']}: {$sp['specialization_name']} (Dept: {$sp['department_id']})\n";
}

// 6. Check Table Schemas & Foreign Keys
echo "\n--- 6. FOREIGN KEYS & INDEXES ---\n";
foreach (array_keys($tables) as $tbl) {
    $idxRes = mysqli_query($db->conn, "SHOW INDEX FROM `$tbl`");
    $indexes = [];
    while ($idx = mysqli_fetch_assoc($idxRes)) {
        $indexes[$idx['Key_name']][] = $idx['Column_name'];
    }
    echo "Table `$tbl` Indexes:\n";
    foreach ($indexes as $kName => $cols) {
        echo "  - $kName (" . implode(', ', $cols) . ")\n";
    }
}
