<?php
require_once __DIR__ . '/../database/db_connect.php';

$db = new DBController();
if (!$db->conn) {
    die("Database connection failed: " . $db->last_error . "\n");
}

echo "=== TCET SPECIALIZATION TRACKER - COMPREHENSIVE SCHEMA & ARCHITECTURE REPORT ===\n\n";

// 1. ALL TABLES, COLUMNS, TYPES, NULLABLE, DEFAULT, KEYS
$tablesResult = mysqli_query($db->conn, "SHOW TABLES");
$allTables = [];
while ($tRow = mysqli_fetch_array($tablesResult)) {
    $tbl = $tRow[0];
    $countRes = mysqli_query($db->conn, "SELECT COUNT(*) as cnt FROM `$tbl`");
    $cnt = $countRes ? mysqli_fetch_assoc($countRes)['cnt'] : 0;
    
    echo "======================================================================\n";
    echo "TABLE: $tbl ($cnt rows)\n";
    echo "======================================================================\n";
    
    $colsRes = mysqli_query($db->conn, "SHOW FULL COLUMNS FROM `$tbl`");
    echo sprintf("%-30s | %-20s | %-6s | %-5s | %-15s | %s\n", "Field", "Type", "Null", "Key", "Default", "Comment");
    echo str_repeat("-", 90) . "\n";
    while ($col = mysqli_fetch_assoc($colsRes)) {
        echo sprintf("%-30s | %-20s | %-6s | %-5s | %-15s | %s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'], 
            $col['Key'], 
            var_export($col['Default'], true), 
            $col['Comment'] ?? ''
        );
    }
    
    $idxRes = mysqli_query($db->conn, "SHOW INDEX FROM `$tbl`");
    $indexes = [];
    while ($idx = mysqli_fetch_assoc($idxRes)) {
        $indexes[$idx['Key_name']]['unique'] = ($idx['Non_unique'] == 0);
        $indexes[$idx['Key_name']]['cols'][] = $idx['Column_name'];
    }
    if (!empty($indexes)) {
        echo "\nINDEXES:\n";
        foreach ($indexes as $name => $info) {
            $u = $info['unique'] ? '[UNIQUE]' : '[INDEX]';
            echo "  $u $name (" . implode(', ', $info['cols']) . ")\n";
        }
    }
    echo "\n";
}

// 2. SUBMENUS & ALLOCATIONS PER ROLE
echo "\n======================================================================\n";
echo "ROLE-BASED MENU ALLOCATION MATRIX (st_menu_allocation_master)\n";
echo "======================================================================\n";
$allocRes = mysqli_query($db->conn, "
    SELECT 
        r.role_id, 
        r.role_name, 
        m.menu_id, 
        m.menu_name, 
        sm.sub_menu_id, 
        sm.sub_menu_name, 
        sm.url
    FROM st_menu_allocation_master a
    JOIN st_role_master r ON a.role_id = r.role_id
    JOIN st_menu_master m ON a.menu_id = m.menu_id
    JOIN st_sub_menu_master sm ON a.sub_menu_id = sm.sub_menu_id
    WHERE a.status = 1
    ORDER BY r.role_id, m.menu_id, sm.sub_menu_id
");
$currentRole = null;
$currentMenu = null;
while ($row = mysqli_fetch_assoc($allocRes)) {
    if ($currentRole !== $row['role_name']) {
        $currentRole = $row['role_name'];
        echo "\n>>> ROLE [{$row['role_id']}]: {$row['role_name']}\n";
    }
    if ($currentMenu !== $row['menu_name']) {
        $currentMenu = $row['menu_name'];
        echo "  + Menu: {$row['menu_name']}\n";
    }
    echo "    - Submenu: {$row['sub_menu_name']} -> {$row['url']}\n";
}
