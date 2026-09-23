<?php
require_once __DIR__ . '/../database/db_connect.php';
$db = new DBController();

$res = mysqli_query($db->conn, "
    SELECT sm.sub_menu_id, sm.sub_menu_name, sm.sub_menu_route, mam.role_id, r.role_name
    FROM st_sub_menu_master sm
    LEFT JOIN st_menu_allocation_master mam ON mam.sub_menu_id = sm.sub_menu_id
    LEFT JOIN st_role_master r ON r.role_id = mam.role_id
    WHERE sm.sub_menu_route LIKE '%nptel%'
");
while ($r = mysqli_fetch_assoc($res)) {
    print_r($r);
}
