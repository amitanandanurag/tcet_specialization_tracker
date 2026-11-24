<?php
/**
 * TCET Specialization Tracker (TCET_ST)
 * Centralized Super Admin Menu & Submenu Allocation Management
 *
 * Consolidated single module controlling menu visibility, submenu visibility,
 * role allocations, display ordering, and independent server-side RBAC.
 *
 * Exclusively restricted to Super Admin (role_id = 1).
 */

if (session_status() !== PHP_SESSION_ACTIVE && php_sapi_name() !== 'cli') {
    session_start();
}

require_once __DIR__ . "/header/header.php";

// Strict Super Admin Access Guard
if ((int)($usertype ?? 0) !== 1) {
    http_response_code(403);
    echo '<div class="content-wrapper"><section class="content"><div class="alert alert-danger" style="margin: 30px;"><i class="fa fa-ban"></i> <strong>403 Access Denied:</strong> This page is strictly restricted to Super Admin.</div></section></div>';
    include "header/footer.php";
    exit;
}

$conn = $db_handle->conn;

// Ensure schema columns exist
if (!function_exists('menu_alloc_ensure_schema')) {
function menu_alloc_ensure_schema($conn) {
    $res = mysqli_query($conn, "SHOW COLUMNS FROM st_menu_master LIKE 'is_active'");
    if ($res && mysqli_num_rows($res) === 0) {
        mysqli_query($conn, "ALTER TABLE st_menu_master ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER menu_icon");
    }
    $res = mysqli_query($conn, "SHOW COLUMNS FROM st_menu_master LIKE 'sort_order'");
    if ($res && mysqli_num_rows($res) === 0) {
        mysqli_query($conn, "ALTER TABLE st_menu_master ADD COLUMN sort_order INT(11) NOT NULL DEFAULT 1 AFTER menu_id");
    }
    $res = mysqli_query($conn, "SHOW COLUMNS FROM st_sub_menu_master LIKE 'is_active'");
    if ($res && mysqli_num_rows($res) === 0) {
        mysqli_query($conn, "ALTER TABLE st_sub_menu_master ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER sub_menu_route");
    }
}
}
menu_alloc_ensure_schema($conn);

// Protected critical Super Admin submenus (Cannot be de-allocated from Role 1)
$protectedSuperAdminRoutes = array(
    'menu_submenu_allocation.php',
    'menu_submenu_management.php',
    'class_crud_new.php',
    'audit_log.php',
    'allocation_master.php',
    'profile.php',
    'change_password.php'
);

// Decommissioned & retired historical modules (Permanently excluded from operational navigation & allocations)
$retiredHistoricalRoutes = array(
    'historical_students.php',
    'superadmin_approval_monitoring.php',
    'hod_historical_approvals.php'
);

$success_msg = '';
$error_msg = '';
$info_msg = '';

// Active Tab determination
$activeTab = $_GET['tab'] ?? 'role_allocations';
$allowedTabs = array('role_allocations', 'menu_master', 'submenu_master', 'audit_history');
if (!in_array($activeTab, $allowedTabs, true)) {
    $activeTab = 'role_allocations';
}

// Selected Role Profile
$selectedProfile = $_GET['profile'] ?? '1';
$allowedProfiles = array('1', '2', '3_coord', '3_hod', '4', '5');
if (!in_array($selectedProfile, $allowedProfiles, true)) {
    $selectedProfile = '1';
}

// Helper to resolve profile identifier to role_id and user_id in st_menu_allocation_master
if (!function_exists('resolveAllocProfileTarget')) {
function resolveAllocProfileTarget($profile) {
    switch ($profile) {
        case '1': return array('role_id' => 1, 'user_id' => 0, 'name' => 'Super Admin');
        case '2': return array('role_id' => 2, 'user_id' => 0, 'name' => 'Admin');
        case '3_coord': return array('role_id' => 3, 'user_id' => 0, 'name' => 'Coordinator (Generic)');
        case '3_hod': return array('role_id' => 3, 'user_id' => -3, 'name' => 'Authorized HOD (Verified)');
        case '4': return array('role_id' => 4, 'user_id' => 0, 'name' => 'Faculty Mentor');
        case '5': return array('role_id' => 5, 'user_id' => 0, 'name' => 'Student');
        default: return array('role_id' => 1, 'user_id' => 0, 'name' => 'Super Admin');
    }
}
}

// Role-based applicability rules helper
if (!function_exists('isSubmenuAllowedForRoleProfile')) {
function isSubmenuAllowedForRoleProfile($subRoute, $subId, $profileKey, $isGloballyDisabled) {
    global $retiredHistoricalRoutes;

    if ($isGloballyDisabled) {
        return array('allowed' => false, 'reason' => 'Globally Disabled');
    }

    // Consolidated tabs in Masters (51, 52, 53) are redundant
    if (in_array((int)$subId, array(51, 52, 53), true)) {
        return array('allowed' => false, 'reason' => 'Merged in Masters');
    }

    // Decommissioned historical routes are permanently retired from operational allocation
    foreach ($retiredHistoricalRoutes as $rr) {
        if (strpos($subRoute, $rr) !== false) {
            return array('allowed' => false, 'reason' => 'Retired Module');
        }
    }

    // Bulk Student Upload: Exclusively restricted to Super Admin (Role 1)
    if (strpos($subRoute, 'student_bulk_import.php') !== false) {
        if ($profileKey === '1') {
            return array('allowed' => true, 'reason' => '');
        }
        return array('allowed' => false, 'reason' => 'Super Admin Only');
    }

    // Super Admin Exclusive Management Tools
    $superAdminOnlyRoutes = array(
        'student_bulk_import.php',
        'menu_submenu_allocation.php',
        'menu_submenu_management.php',
        'allocation_master.php',
        'audit_log.php',
        'class_crud_new.php'
    );
    foreach ($superAdminOnlyRoutes as $sar) {
        if (strpos($subRoute, $sar) !== false) {
            if ($profileKey === '1') {
                return array('allowed' => true, 'reason' => '');
            }
            return array('allowed' => false, 'reason' => 'Super Admin Only');
        }
    }

    // Department & HOD Monitoring: Restricted to Super Admin (1) and Admin (2)
    if (strpos($subRoute, 'hod_mentor_allocation.php') !== false || strpos($subRoute, 'department_hod_monitoring.php') !== false) {
        if ($profileKey === '1' || $profileKey === '2') {
            return array('allowed' => true, 'reason' => '');
        }
        return array('allowed' => false, 'reason' => 'Admin Only');
    }

    // Student (Profile 5): strictly self-service only
    if ($profileKey === '5') {
        $studentAllowed = array('student_admission.php', 'nptel_certificate.php', 'profile.php', 'change_password.php');
        $isOk = false;
        foreach ($studentAllowed as $sa) {
            if (strpos($subRoute, $sa) !== false) {
                $isOk = true;
                break;
            }
        }
        if ($isOk) {
            return array('allowed' => true, 'reason' => '');
        }
        return array('allowed' => false, 'reason' => 'Staff Only');
    }

    // Mentor (Profile 4): blocked from administrative management tools
    if ($profileKey === '4') {
        $mentorBlocked = array('admin_register.php', 'admin_info.php', 'coordinator_register.php', 'coordinator_info.php', 'coordinator_allocation.php', 'student_admission.php', 'specialization_subject_manage.php');
        foreach ($mentorBlocked as $mb) {
            if (strpos($subRoute, $mb) !== false) {
                return array('allowed' => false, 'reason' => 'Admin/Coord Only');
            }
        }
    }

    // Coordinator (Profile 3_coord): cannot access Admin registration
    if ($profileKey === '3_coord') {
        $coordBlocked = array('admin_register.php', 'admin_info.php');
        foreach ($coordBlocked as $cb) {
            if (strpos($subRoute, $cb) !== false) {
                return array('allowed' => false, 'reason' => 'Admin Only');
            }
        }
    }

    return array('allowed' => true, 'reason' => '');
}
}

$targetMeta = resolveAllocProfileTarget($selectedProfile);

// =========================================================================
// POST ACTION HANDLERS
// =========================================================================
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!DBController::validateCsrfToken()) {
        $error_msg = "Security token validation failed. Please refresh and try again.";
    } else {
        $action = $_POST['action'] ?? '';

        // 1. SAVE ROLE ALLOCATIONS (TRANSACTIONAL & DATABASE-VERIFIED)
        if ($action === 'save_role_allocations') {
            $postProfile = $_POST['profile'] ?? '1';
            $meta = resolveAllocProfileTarget($postProfile);
            $targetRoleId = $meta['role_id'];
            $targetUserId = $meta['user_id'];
            $profileName = $meta['name'];

            $rawMenus = $_POST['menu_ids'] ?? array();
            $rawSubs = $_POST['sub_menu_ids'] ?? array();

            $selectedMenus = array_values(array_unique(array_filter(array_map('intval', (array)$rawMenus), fn($id) => $id > 0)));
            $selectedSubs = array_values(array_unique(array_filter(array_map('intval', (array)$rawSubs), fn($id) => $id > 0)));

            // Fetch all valid submenus and their routes from DB
            $dbSubRes = mysqli_query($conn, "SELECT sub_menu_id, menu_id, sub_menu_route, is_active FROM st_sub_menu_master");
            $dbSubData = array();
            while ($sRow = mysqli_fetch_assoc($dbSubRes)) {
                $dbSubData[(int)$sRow['sub_menu_id']] = $sRow;
            }

            // Filter out any submenus that are role-ineligible or globally disabled
            $validSubs = array();
            foreach ($selectedSubs as $sId) {
                if (isset($dbSubData[$sId])) {
                    $sInfo = $dbSubData[$sId];
                    // Skip retired historical routes
                    $isRet = false;
                    foreach ($retiredHistoricalRoutes as $rr) {
                        if (strpos($sInfo['sub_menu_route'], $rr) !== false) {
                            $isRet = true;
                            break;
                        }
                    }
                    if ($isRet) {
                        continue;
                    }
                    $isGlobDisabled = ((int)($sInfo['is_active'] ?? 1) === 0);
                    $chk = isSubmenuAllowedForRoleProfile($sInfo['sub_menu_route'], $sId, $postProfile, $isGlobDisabled);
                    if ($chk['allowed']) {
                        $validSubs[$sId] = (int)$sInfo['menu_id'];
                    }
                }
            }

            // Lockout Prevention for Super Admin (Role 1)
            if ($targetRoleId === 1) {
                // Settings Menu must always be included
                $settingsMenuRes = mysqli_query($conn, "SELECT menu_id FROM st_menu_master WHERE LOWER(TRIM(menu_name)) IN ('settings', 'admin') ORDER BY CASE WHEN LOWER(TRIM(menu_name)) = 'settings' THEN 0 ELSE 1 END LIMIT 1");
                $settingsMenuRow = mysqli_fetch_assoc($settingsMenuRes);
                $settingsMenuId = (int)($settingsMenuRow['menu_id'] ?? 5);
                if (!in_array($settingsMenuId, $selectedMenus, true)) {
                    $selectedMenus[] = $settingsMenuId;
                }

                // Protected submenus must always be included
                foreach ($protectedSuperAdminRoutes as $pRoute) {
                    foreach ($dbSubData as $sId => $sInfo) {
                        if (strpos($sInfo['sub_menu_route'], $pRoute) !== false && ((int)($sInfo['is_active'] ?? 1) === 1)) {
                            $validSubs[$sId] = (int)$sInfo['menu_id'];
                            if (!in_array((int)$sInfo['menu_id'], $selectedMenus, true)) {
                                $selectedMenus[] = (int)$sInfo['menu_id'];
                            }
                        }
                    }
                }
            }

            // Automatically ensure parent menus exist for all valid submenus
            foreach ($validSubs as $sId => $pMId) {
                if (!in_array($pMId, $selectedMenus, true)) {
                    $selectedMenus[] = $pMId;
                }
            }

            // Filter out parent menus that have no valid submenus (unless explicitly standalone)
            $finalMenus = array();
            foreach ($selectedMenus as $mId) {
                $hasSub = false;
                foreach ($validSubs as $sId => $pMId) {
                    if ($pMId === $mId) {
                        $hasSub = true;
                        break;
                    }
                }
                if ($hasSub) {
                    $finalMenus[] = $mId;
                }
            }

            // Capture previous state for audit log
            $prevSubsSql = "SELECT sub_menu_id FROM st_menu_allocation_master WHERE role_id = {$targetRoleId} AND user_id = {$targetUserId} AND sub_menu_id IS NOT NULL";
            $prevSubsRes = mysqli_query($conn, $prevSubsSql);
            $prevSubIds = array();
            if ($prevSubsRes) {
                while ($psr = mysqli_fetch_assoc($prevSubsRes)) {
                    $prevSubIds[] = (int)$psr['sub_menu_id'];
                }
            }

            // EXECUTE TRANSACTIONAL SAVE
            mysqli_begin_transaction($conn);
            try {
                // 1. Delete previous allocations for this specific profile
                $delStmt = mysqli_prepare($conn, "DELETE FROM st_menu_allocation_master WHERE role_id = ? AND user_id = ?");
                mysqli_stmt_bind_param($delStmt, 'ii', $targetRoleId, $targetUserId);
                if (!mysqli_stmt_execute($delStmt)) {
                    throw new Exception("Failed to clear previous permissions: " . mysqli_stmt_error($delStmt));
                }
                mysqli_stmt_close($delStmt);

                // 2. Insert parent menus (sub_menu_id IS NULL)
                if (!empty($finalMenus)) {
                    $insParentStmt = mysqli_prepare($conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (?, ?, ?, NULL)");
                    foreach ($finalMenus as $mId) {
                        mysqli_stmt_bind_param($insParentStmt, 'iii', $targetUserId, $targetRoleId, $mId);
                        if (!mysqli_stmt_execute($insParentStmt)) {
                            throw new Exception("Failed to insert parent menu #$mId: " . mysqli_stmt_error($insParentStmt));
                        }
                    }
                    mysqli_stmt_close($insParentStmt);
                }

                // 3. Insert submenus
                if (!empty($validSubs)) {
                    $insSubStmt = mysqli_prepare($conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES (?, ?, ?, ?)");
                    foreach ($validSubs as $sId => $pMId) {
                        mysqli_stmt_bind_param($insSubStmt, 'iiii', $targetUserId, $targetRoleId, $pMId, $sId);
                        if (!mysqli_stmt_execute($insSubStmt)) {
                            throw new Exception("Failed to insert submenu #$sId: " . mysqli_stmt_error($insSubStmt));
                        }
                    }
                    mysqli_stmt_close($insSubStmt);
                }

                // 4. Commit transaction
                mysqli_commit($conn);

                // 5. Query verified saved counts from database
                $cntRes = mysqli_query($conn, "SELECT COUNT(DISTINCT menu_id) as m_cnt, COUNT(sub_menu_id) as s_cnt FROM st_menu_allocation_master WHERE role_id = {$targetRoleId} AND user_id = {$targetUserId}");
                $cntRow = $cntRes ? mysqli_fetch_assoc($cntRes) : array();
                $savedMenuCount = (int)($cntRow['m_cnt'] ?? 0);
                $savedSubCount = (int)($cntRow['s_cnt'] ?? 0);

                // 6. Write Audit Log
                $desc = "Super Admin {$name} (ID {$userid}) updated navigation allocations for profile '{$profileName}'. Database verified: {$savedMenuCount} parent menus, {$savedSubCount} submenus saved. Previous submenus count: " . count($prevSubIds);
                if (method_exists($db_handle, 'writeAuditLog')) {
                    $db_handle->writeAuditLog($userid, 'MENU_ROLE_ALLOCATION_UPDATED', 'st_menu_allocation_master', $targetRoleId, $desc, $username, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $_SERVER['HTTP_USER_AGENT'] ?? 'CLI');
                }

                $success_msg = "Navigation permissions for <strong>" . htmlspecialchars($profileName) . "</strong> were updated successfully! (" . $savedMenuCount . " menus, " . $savedSubCount . " submenus saved)";
                $selectedProfile = $postProfile;
                $targetMeta = resolveAllocProfileTarget($selectedProfile);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error_msg = "Failed to update navigation permissions: " . htmlspecialchars($e->getMessage()) . ". All changes were safely rolled back.";
            }
        }

        // 2. TOGGLE GLOBAL MENU STATUS
        elseif ($action === 'toggle_menu_status') {
            $menuId = (int)($_POST['menu_id'] ?? 0);
            $newStatus = (int)($_POST['is_active'] ?? 1);
            if ($menuId > 0) {
                mysqli_query($conn, "UPDATE st_menu_master SET is_active = {$newStatus} WHERE menu_id = {$menuId}");
                $statusWord = ($newStatus === 1) ? 'ENABLED' : 'DISABLED';
                $desc = "Super Admin {$name} changed global status of Menu ID {$menuId} to {$statusWord}.";
                if (method_exists($db_handle, 'writeAuditLog')) {
                    $db_handle->writeAuditLog($userid, 'MENU_TOGGLE_MENU_STATUS', 'st_menu_master', $menuId, $desc, $username, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $_SERVER['HTTP_USER_AGENT'] ?? 'CLI');
                }
                $success_msg = "Global status for Menu ID {$menuId} updated to {$statusWord}.";
            }
        }

        // 3. TOGGLE GLOBAL SUBMENU STATUS
        elseif ($action === 'toggle_submenu_status') {
            $subId = (int)($_POST['sub_menu_id'] ?? 0);
            $newStatus = (int)($_POST['is_active'] ?? 1);
            if ($subId > 0) {
                // Check if protected
                $chkRouteRes = mysqli_query($conn, "SELECT sub_menu_route, sub_menu_name FROM st_sub_menu_master WHERE sub_menu_id = {$subId} LIMIT 1");
                $chkRouteRow = mysqli_fetch_assoc($chkRouteRes);
                $route = $chkRouteRow['sub_menu_route'] ?? '';
                if ($newStatus === 0 && in_array($route, $protectedSuperAdminRoutes, true)) {
                    $error_msg = "Cannot globally disable critical Super Admin administration submenu: " . htmlspecialchars($chkRouteRow['sub_menu_name'] ?? '');
                } elseif ($newStatus === 1 && in_array($route, $retiredHistoricalRoutes, true)) {
                    $error_msg = "Cannot enable retired historical module: " . htmlspecialchars($chkRouteRow['sub_menu_name'] ?? '') . ". This module has been permanently retired.";
                } else {
                    mysqli_query($conn, "UPDATE st_sub_menu_master SET is_active = {$newStatus} WHERE sub_menu_id = {$subId}");
                    $statusWord = ($newStatus === 1) ? 'ENABLED' : 'DISABLED';
                    $desc = "Super Admin {$name} changed global status of Submenu ID {$subId} ({$chkRouteRow['sub_menu_name']}) to {$statusWord}.";
                    if (method_exists($db_handle, 'writeAuditLog')) {
                        $db_handle->writeAuditLog($userid, 'MENU_TOGGLE_SUBMENU_STATUS', 'st_sub_menu_master', $subId, $desc, $username, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $_SERVER['HTTP_USER_AGENT'] ?? 'CLI');
                    }
                    $success_msg = "Global status for Submenu '{$chkRouteRow['sub_menu_name']}' updated to {$statusWord}.";
                }
            }
        }

        // 4. UPDATE DISPLAY ORDER
        elseif ($action === 'update_sort_order') {
            $targetType = $_POST['target_type'] ?? '';
            $targetId = (int)($_POST['target_id'] ?? 0);
            $newOrder = (int)($_POST['sort_order'] ?? 1);

            if ($targetType === 'menu' && $targetId > 0) {
                mysqli_query($conn, "UPDATE st_menu_master SET sort_order = {$newOrder} WHERE menu_id = {$targetId}");
                $success_msg = "Display order for Menu ID {$targetId} updated to {$newOrder}.";
            } elseif ($targetType === 'submenu' && $targetId > 0) {
                mysqli_query($conn, "UPDATE st_sub_menu_master SET sort_order = {$newOrder} WHERE sub_menu_id = {$targetId}");
                $success_msg = "Display order for Submenu ID {$targetId} updated to {$newOrder}.";
            }
        }

        // 5. RESET TO INSTITUTIONAL DEFAULTS
        elseif ($action === 'reset_to_defaults') {
            // Define institutional default profiles (excluding retired historical modules)
            $defaultProfiles = array(
                '1' => array(
                    'menus' => array(1, 2, 3, 4, 5),
                    'subs' => array(1, 2, 5, 54, 58, 65, 7, 8, 63, 9, 10, 50, 55, 59, 11, 12, 48, 56, 57, 13, 20, 21, 25, 49)
                ),
                '2' => array(
                    'menus' => array(1, 2, 3, 4, 5),
                    'subs' => array(1, 2, 5, 54, 58, 7, 8, 63, 10, 55, 59, 11, 12, 48, 56, 57, 20, 21)
                ),
                '3_coord' => array(
                    'menus' => array(1, 3, 4, 5),
                    'subs' => array(1, 2, 5, 54, 58, 10, 55, 59, 56, 57, 20, 21)
                ),
                '3_hod' => array(
                    'menus' => array(1, 3, 4, 5),
                    'subs' => array(1, 2, 5, 54, 58, 10, 55, 59, 56, 57, 20, 21)
                ),
                '4' => array(
                    'menus' => array(1, 4, 5),
                    'subs' => array(2, 5, 54, 58, 56, 57, 20, 21)
                ),
                '5' => array(
                    'menus' => array(1, 5),
                    'subs' => array(1, 58, 20, 21)
                )
            );

            foreach ($defaultProfiles as $profKey => $pData) {
                $meta = resolveAllocProfileTarget($profKey);
                $rId = $meta['role_id'];
                $uId = $meta['user_id'];

                mysqli_query($conn, "DELETE FROM st_menu_allocation_master WHERE role_id = {$rId} AND user_id = {$uId}");

                foreach ($pData['menus'] as $mId) {
                    mysqli_query($conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES ({$uId}, {$rId}, {$mId}, NULL)");
                }
                foreach ($pData['subs'] as $sId) {
                    $mRes = mysqli_query($conn, "SELECT menu_id FROM st_sub_menu_master WHERE sub_menu_id = {$sId} LIMIT 1");
                    if ($mRes && ($mRow = mysqli_fetch_assoc($mRes))) {
                        $pMId = (int)$mRow['menu_id'];
                        mysqli_query($conn, "INSERT INTO st_menu_allocation_master (user_id, role_id, menu_id, sub_menu_id) VALUES ({$uId}, {$rId}, {$pMId}, {$sId})");
                    }
                }
            }

            // Ensure all parent menus and active submenus are active (leaving redundant/retired submenus disabled)
            mysqli_query($conn, "UPDATE st_menu_master SET is_active = 1");
            mysqli_query($conn, "UPDATE st_sub_menu_master SET is_active = 1 WHERE sub_menu_id NOT IN (51, 52, 53, 60, 61, 62)");

            $desc = "Super Admin {$name} restored institutional baseline default allocations across all role profiles.";
            if (method_exists($db_handle, 'writeAuditLog')) {
                $db_handle->writeAuditLog($userid, 'MENU_RESET_TO_DEFAULTS', 'st_menu_allocation_master', 0, $desc, $username, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $_SERVER['HTTP_USER_AGENT'] ?? 'CLI');
            }

            $success_msg = "Institutional default navigation permissions have been successfully restored across all role profiles!";
        }
    }
}

// =========================================================================
// QUERY DATA FOR DISPLAY
// =========================================================================

// Fetch all parent menus
$allMenusQuery = "SELECT menu_id, menu_name, menu_icon, is_active, sort_order FROM st_menu_master ORDER BY sort_order ASC, menu_id ASC";
$allMenusRes = mysqli_query($conn, $allMenusQuery);
$menus = array();
while ($m = mysqli_fetch_assoc($allMenusRes)) {
    $menus[$m['menu_id']] = $m;
}

// Fetch all submenus grouped by parent
$allSubsQuery = "SELECT sm.sub_menu_id, sm.menu_id, sm.sort_order, sm.sub_menu_name, sm.sub_menu_icon, sm.sub_menu_route, sm.is_active, m.menu_name 
                 FROM st_sub_menu_master sm 
                 JOIN st_menu_master m ON m.menu_id = sm.menu_id 
                 ORDER BY m.sort_order ASC, sm.sort_order ASC, sm.sub_menu_id ASC";
$allSubsRes = mysqli_query($conn, $allSubsQuery);
$submenus = array();
$submenusGrouped = array();
while ($s = mysqli_fetch_assoc($allSubsRes)) {
    $submenus[$s['sub_menu_id']] = $s;
    $submenusGrouped[$s['menu_id']][] = $s;
}

// Current allocations for selected role
$currentRoleAllocQuery = "SELECT menu_id, sub_menu_id 
                          FROM st_menu_allocation_master 
                          WHERE role_id = {$targetMeta['role_id']} AND user_id = {$targetMeta['user_id']}";
$currentRoleAllocRes = mysqli_query($conn, $currentRoleAllocQuery);
$allocatedMenus = array();
$allocatedSubs = array();
while ($ar = mysqli_fetch_assoc($currentRoleAllocRes)) {
    if ($ar['sub_menu_id'] === null) {
        $allocatedMenus[] = (int)$ar['menu_id'];
    } else {
        $allocatedSubs[] = (int)$ar['sub_menu_id'];
    }
}

// Count assigned roles per menu and submenu for overview tabs
$menuRoleCountQuery = "SELECT menu_id, COUNT(DISTINCT CONCAT(role_id, '_', user_id)) as role_count FROM st_menu_allocation_master WHERE sub_menu_id IS NULL GROUP BY menu_id";
$menuRoleCounts = array();
$mrcRes = mysqli_query($conn, $menuRoleCountQuery);
if ($mrcRes) {
    while ($r = mysqli_fetch_assoc($mrcRes)) {
        $menuRoleCounts[$r['menu_id']] = (int)$r['role_count'];
    }
}

$subRoleCountQuery = "SELECT sub_menu_id, COUNT(DISTINCT CONCAT(role_id, '_', user_id)) as role_count FROM st_menu_allocation_master WHERE sub_menu_id IS NOT NULL GROUP BY sub_menu_id";
$subRoleCounts = array();
$srcRes = mysqli_query($conn, $subRoleCountQuery);
if ($srcRes) {
    while ($r = mysqli_fetch_assoc($srcRes)) {
        $subRoleCounts[$r['sub_menu_id']] = (int)$r['role_count'];
    }
}

// Fetch Audit log records for Tab 4
$auditRecords = array();
if ($activeTab === 'audit_history') {
    $auditSql = "SELECT audit_id, user_id, action_type, affected_table, affected_record, description, username, ip_address, performed_at 
                 FROM st_audit_log 
                 WHERE action_type LIKE 'MENU_%' OR affected_table IN ('st_menu_master', 'st_sub_menu_master', 'st_menu_allocation_master')
                 ORDER BY audit_id DESC LIMIT 50";
    $auditRes = mysqli_query($conn, $auditSql);
    if ($auditRes) {
        while ($ar = mysqli_fetch_assoc($auditRes)) {
            $auditRecords[] = $ar;
        }
    }
}
?>

<div class="content-wrapper" style="min-height: 850px; background-color: #f4f6f9;">
    <!-- Content Header -->
    <section class="content-header" style="padding: 20px 25px 10px 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1 style="font-size: 24px; font-weight: 700; color: #1e293b; margin: 0;">
                    <i class="fa fa-sliders text-primary" style="margin-right: 8px;"></i> Menu &amp; Submenu Allocation
                </h1>
                <p style="color: #64748b; margin: 4px 0 0 0; font-size: 13px;">
                    Centralized role-based access control, navigation visibility, and master permission management.
                </p>
            </div>
            <div>
                <button type="button" class="btn btn-outline-danger btn-sm" data-toggle="modal" data-target="#modalResetDefaults" style="border-radius: 4px; font-weight: 600; padding: 6px 14px;">
                    <i class="fa fa-history"></i> Reset to Defaults
                </button>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content" style="padding: 15px 25px;">
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible" style="border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="fa fa-check-circle" style="margin-right: 6px;"></i> <?php echo $success_msg; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible" style="border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="fa fa-exclamation-triangle" style="margin-right: 6px;"></i> <?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <!-- Nav Tabs -->
        <div class="nav-tabs-custom" style="border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); background: #ffffff;">
            <ul class="nav nav-tabs" style="border-bottom: 2px solid #e2e8f0; font-size: 14px; font-weight: 600;">
                <li class="<?php echo ($activeTab === 'role_allocations') ? 'active' : ''; ?>">
                    <a href="?tab=role_allocations&profile=<?php echo urlencode($selectedProfile); ?>">
                        <i class="fa fa-shield" style="margin-right: 6px;"></i> Role Permissions &amp; Live Preview
                    </a>
                </li>
                <li class="<?php echo ($activeTab === 'menu_master') ? 'active' : ''; ?>">
                    <a href="?tab=menu_master">
                        <i class="fa fa-folder-open" style="margin-right: 6px;"></i> Parent Menus Master (<?php echo count($menus); ?>)
                    </a>
                </li>
                <li class="<?php echo ($activeTab === 'submenu_master') ? 'active' : ''; ?>">
                    <a href="?tab=submenu_master">
                        <i class="fa fa-list" style="margin-right: 6px;"></i> Submenus Master (<?php echo count($submenus); ?>)
                    </a>
                </li>
                <li class="<?php echo ($activeTab === 'audit_history') ? 'active' : ''; ?>">
                    <a href="?tab=audit_history">
                        <i class="fa fa-history" style="margin-right: 6px;"></i> Audit Change Ledger
                    </a>
                </li>
            </ul>

            <div class="tab-content" style="padding: 24px;">

                <!-- ==================================================================================== -->
                <!-- TAB 1: ROLE MENU PERMISSIONS & LIVE PREVIEW -->
                <!-- ==================================================================================== -->
                <?php if ($activeTab === 'role_allocations'): ?>
                    <div class="tab-pane active" id="tab_role_alloc">
                        <!-- Profile Selector Header -->
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 16px 20px; margin-bottom: 20px;">
                            <form method="GET" action="" id="profileSelectForm" class="form-inline" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                                <input type="hidden" name="tab" value="role_allocations">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="profileSelector" style="font-size: 14px; font-weight: 700; color: #334155; margin-right: 12px;">
                                        <i class="fa fa-user-circle text-primary"></i> Target Role Permission Profile:
                                    </label>
                                    <select name="profile" id="profileSelector" class="form-control" style="font-weight: 600; min-width: 260px; height: 38px; border-radius: 4px;" onchange="document.getElementById('profileSelectForm').submit();">
                                        <option value="1" <?php echo ($selectedProfile === '1') ? 'selected' : ''; ?>>Super Admin (Role 1 - Unrestricted Root)</option>
                                        <option value="2" <?php echo ($selectedProfile === '2') ? 'selected' : ''; ?>>Admin (Role 2 - Academic Administration)</option>
                                        <option value="3_coord" <?php echo ($selectedProfile === '3_coord') ? 'selected' : ''; ?>>Coordinator (Role 3 - Batch Management)</option>
                                        <option value="3_hod" <?php echo ($selectedProfile === '3_hod') ? 'selected' : ''; ?>>Authorized HOD (Role 3 - Verified Department Sign-Off)</option>
                                        <option value="4" <?php echo ($selectedProfile === '4') ? 'selected' : ''; ?>>Faculty Mentor (Role 4 - Mentoring &amp; Marks)</option>
                                        <option value="5" <?php echo ($selectedProfile === '5') ? 'selected' : ''; ?>>Student (Role 5 - Self Service)</option>
                                    </select>
                                </div>
                                <div>
                                    <span class="badge" style="background-color: #3b82f6; font-size: 13px; padding: 8px 12px; border-radius: 4px;">
                                        Active Profile: <strong><?php echo htmlspecialchars($targetMeta['name']); ?></strong> (Role ID: <?php echo $targetMeta['role_id']; ?>)
                                    </span>
                                </div>
                            </form>
                        </div>

                        <!-- 2-Column Layout: Left (Permissions Matrix), Right (Live Sidebar Preview) -->
                        <div class="row">
                            <!-- Left Column: Checkbox Allocation Tree -->
                            <div class="col-lg-7 col-md-12">
                                <form method="POST" action="" id="allocationsForm">
                                    <?php echo DBController::getCsrfInputField(); ?>
                                    <input type="hidden" name="action" value="save_role_allocations">
                                    <input type="hidden" name="profile" value="<?php echo htmlspecialchars($selectedProfile); ?>">

                                    <!-- Bulk Action Buttons -->
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 8px;">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-default" onclick="bulkCheckAll(true)"><i class="fa fa-check-square"></i> Select All</button>
                                            <button type="button" class="btn btn-default" onclick="bulkCheckAll(false)"><i class="fa fa-square-o"></i> Deselect All</button>
                                            <button type="button" class="btn btn-default" onclick="expandAllCategories(true)"><i class="fa fa-plus-square"></i> Expand All</button>
                                            <button type="button" class="btn btn-default" onclick="expandAllCategories(false)"><i class="fa fa-minus-square"></i> Collapse All</button>
                                        </div>
                                        <button type="submit" class="btn btn-primary" style="font-weight: 700; padding: 7px 22px; border-radius: 4px;">
                                            <i class="fa fa-save" style="margin-right: 6px;"></i> Save Changes
                                        </button>
                                    </div>

                                    <?php if ($targetMeta['role_id'] === 1): ?>
                                        <div class="alert alert-info" style="font-size: 12px; padding: 10px 14px; border-radius: 4px; margin-bottom: 16px;">
                                            <i class="fa fa-lock"></i> <strong>Super Admin Lockout Safeguard:</strong> Essential Settings and Menu Allocation modules cannot be deselected or disabled to protect system administration.
                                        </div>
                                    <?php endif; ?>

                                    <!-- Parent Menu Accordions -->
                                    <?php foreach ($menus as $mId => $mInfo): 
                                        $parentChecked = in_array((int)$mId, $allocatedMenus, true);
                                        $childSubs = array_values(array_filter($submenusGrouped[$mId] ?? array(), function($sub) use ($retiredHistoricalRoutes) {
                                            foreach ($retiredHistoricalRoutes as $rr) {
                                                if (strpos($sub['sub_menu_route'], $rr) !== false) {
                                                    return false;
                                                }
                                            }
                                            return true;
                                        }));
                                        $isMenuGloballyDisabled = ((int)($mInfo['is_active'] ?? 1) === 0);
                                    ?>
                                        <div class="box box-solid parent-menu-box" style="border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 14px; box-shadow: none;">
                                            <div class="box-header with-border" style="background: <?php echo $isMenuGloballyDisabled ? '#fff1f2' : '#f8fafc'; ?>; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between;">
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <input type="checkbox" name="menu_ids[]" value="<?php echo $mId; ?>" id="menu_chk_<?php echo $mId; ?>" 
                                                           class="parent-menu-checkbox" data-menu-id="<?php echo $mId; ?>"
                                                           <?php echo $parentChecked ? 'checked' : ''; ?>
                                                           <?php echo ($targetMeta['role_id'] === 1 && strtolower(trim($mInfo['menu_name'])) === 'settings') ? 'disabled checked' : ''; ?>
                                                           style="transform: scale(1.2); cursor: pointer;"
                                                           onchange="onParentMenuCheckboxChange(this, <?php echo $mId; ?>)">
                                                    
                                                    <label for="menu_chk_<?php echo $mId; ?>" style="font-size: 15px; font-weight: 700; margin: 0; color: #1e293b; cursor: pointer;">
                                                        <i class="<?php echo htmlspecialchars($mInfo['menu_icon']); ?>" style="color: #6366f1; margin-right: 6px; width: 18px;"></i>
                                                        <?php echo htmlspecialchars($mInfo['menu_name']); ?>
                                                    </label>

                                                    <?php if ($isMenuGloballyDisabled): ?>
                                                        <span class="badge" style="background-color: #ef4444; font-size: 10px; margin-left: 6px;">Globally Disabled</span>
                                                    <?php endif; ?>

                                                    <?php if ($targetMeta['role_id'] === 1 && strtolower(trim($mInfo['menu_name'])) === 'settings'): ?>
                                                        <input type="hidden" name="menu_ids[]" value="<?php echo $mId; ?>">
                                                        <span class="badge" style="background-color: #f59e0b; font-size: 10px; margin-left: 6px;"><i class="fa fa-lock"></i> Protected</span>
                                                    <?php endif; ?>
                                                </div>

                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <span class="badge" style="background: #e2e8f0; color: #475569; font-weight: 600;">
                                                        <span id="child_count_badge_<?php echo $mId; ?>">
                                                            <?php 
                                                            $selChildCount = 0;
                                                            foreach ($childSubs as $cs) {
                                                                if (in_array((int)$cs['sub_menu_id'], $allocatedSubs, true)) {
                                                                    $selChildCount++;
                                                                }
                                                            }
                                                            echo $selChildCount . " / " . count($childSubs) . " active";
                                                            ?>
                                                        </span>
                                                    </span>
                                                    <button type="button" class="btn btn-box-tool" data-widget="collapse" style="color: #64748b;">
                                                        <i class="fa fa-minus"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="box-body" style="padding: 14px 20px;">
                                                <?php if (empty($childSubs)): ?>
                                                    <p style="color: #94a3b8; font-style: italic; margin: 0;">No submenus configured under this menu.</p>
                                                <?php else: ?>
                                                    <div class="row">
                                                        <?php foreach ($childSubs as $sub): 
                                                            $sId = (int)$sub['sub_menu_id'];
                                                            $subChecked = in_array($sId, $allocatedSubs, true);
                                                            $isSubGloballyDisabled = ((int)($sub['is_active'] ?? 1) === 0);
                                                            $isLocked = ($targetMeta['role_id'] === 1 && in_array($sub['sub_menu_route'], $protectedSuperAdminRoutes, true));

                                                            // Enforce role-profile applicability
                                                            $eligibility = isSubmenuAllowedForRoleProfile($sub['sub_menu_route'], $sId, $selectedProfile, $isSubGloballyDisabled);
                                                            $isRoleIneligible = !$eligibility['allowed'];
                                                            $ineligibleReason = $eligibility['reason'];

                                                            if ($isRoleIneligible) {
                                                                $subChecked = false;
                                                            }
                                                        ?>
                                                            <div class="col-md-6 col-sm-12" style="margin-bottom: 8px;">
                                                                <div style="display: flex; align-items: center; padding: 6px 10px; border-radius: 4px; background: <?php echo ($isSubGloballyDisabled || $isRoleIneligible) ? '#f8fafc' : '#ffffff'; ?>; border: 1px solid #e2e8f0; opacity: <?php echo ($isSubGloballyDisabled || $isRoleIneligible) ? '0.7' : '1'; ?>;">
                                                                    <input type="checkbox" name="sub_menu_ids[]" value="<?php echo $sub['sub_menu_id']; ?>" 
                                                                           id="sub_chk_<?php echo $sub['sub_menu_id']; ?>"
                                                                           class="sub-menu-checkbox sub-menu-parent-<?php echo $mId; ?>"
                                                                           data-parent-id="<?php echo $mId; ?>"
                                                                           data-sub-name="<?php echo htmlspecialchars($sub['sub_menu_name']); ?>"
                                                                           data-sub-icon="<?php echo htmlspecialchars($sub['sub_menu_icon']); ?>"
                                                                           data-sub-route="<?php echo htmlspecialchars($sub['sub_menu_route']); ?>"
                                                                           data-globally-disabled="<?php echo $isSubGloballyDisabled ? '1' : '0'; ?>"
                                                                           data-role-ineligible="<?php echo $isRoleIneligible ? '1' : '0'; ?>"
                                                                           <?php echo ($subChecked || $isLocked) ? 'checked' : ''; ?>
                                                                           <?php echo ($isLocked || $isRoleIneligible || $isSubGloballyDisabled) ? 'disabled' : ''; ?>
                                                                           style="margin-right: 8px; cursor: <?php echo ($isLocked || $isRoleIneligible || $isSubGloballyDisabled) ? 'not-allowed' : 'pointer'; ?>;"
                                                                           onchange="onSubMenuCheckboxChange(this, <?php echo $mId; ?>)">
                                                                    
                                                                    <label for="sub_chk_<?php echo $sub['sub_menu_id']; ?>" style="font-weight: 500; font-size: 13px; margin: 0; color: #334155; cursor: pointer; flex: 1;">
                                                                        <i class="<?php echo htmlspecialchars($sub['sub_menu_icon']); ?>" style="color: #8b5cf6; margin-right: 5px; width: 16px;"></i>
                                                                        <?php echo htmlspecialchars($sub['sub_menu_name']); ?>
                                                                    </label>

                                                                    <?php if ($isSubGloballyDisabled): ?>
                                                                        <span class="badge" style="background-color: #ef4444; font-size: 9px;">Disabled</span>
                                                                    <?php elseif ($isRoleIneligible): ?>
                                                                        <span class="badge" style="background-color: #64748b; font-size: 9px;"><?php echo htmlspecialchars($ineligibleReason); ?></span>
                                                                    <?php elseif ($isLocked): ?>
                                                                        <input type="hidden" name="sub_menu_ids[]" value="<?php echo $sub['sub_menu_id']; ?>">
                                                                        <span class="badge" style="background-color: #f59e0b; font-size: 9px;"><i class="fa fa-lock"></i> Locked</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <div style="margin-top: 20px; text-align: right;">
                                        <button type="submit" class="btn btn-primary btn-lg" style="font-weight: 700; padding: 10px 32px; border-radius: 4px;">
                                            <i class="fa fa-save" style="margin-right: 8px;"></i> Save Role Allocations
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Right Column: Interactive Live Sidebar Preview -->
                            <div class="col-lg-5 col-md-12">
                                <div class="box box-solid" style="border: 1px solid #cbd5e1; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); position: sticky; top: 20px;">
                                    <div class="box-header with-border" style="background: #1e293b; color: #ffffff; padding: 12px 18px; border-radius: 6px 6px 0 0;">
                                        <h3 class="box-title" style="font-size: 14px; font-weight: 700; color: #ffffff;">
                                            <i class="fa fa-eye" style="margin-right: 6px; color: #38bdf8;"></i> Live Sidebar Preview
                                        </h3>
                                        <span class="pull-right badge" style="background-color: #38bdf8; color: #0f172a; font-weight: 700;">
                                            <?php echo htmlspecialchars($targetMeta['name']); ?>
                                        </span>
                                    </div>
                                    <div class="box-body" style="padding: 0; background: #1e1e2d; min-height: 480px; max-height: 700px; overflow-y: auto;">
                                        <div style="padding: 12px 16px; border-bottom: 1px solid #2d2d3f; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px;">
                                            NAVIGATION PREVIEW
                                        </div>
                                        
                                        <!-- Mock Dashboard item -->
                                        <div style="padding: 10px 18px; color: #94a3b8; font-size: 13px; font-weight: 600; display: flex; align-items: center; border-left: 3px solid #3b82f6; background: #26273b;">
                                            <i class="fa fa-tachometer" style="margin-right: 10px; color: #3b82f6; width: 16px;"></i> Dashboard
                                        </div>

                                        <!-- Dynamic Preview Container -->
                                        <div id="preview_sidebar_container">
                                            <!-- Dynamically filled by JavaScript below -->
                                        </div>
                                    </div>
                                    <div class="box-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; padding: 10px 16px;">
                                        <i class="fa fa-info-circle text-primary"></i> Preview reflects exactly what users with this role will see in their left sidebar.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ==================================================================================== -->
                <!-- TAB 2: PARENT MENUS MASTER (GLOBAL ENABLE / DISABLE / SORT) -->
                <!-- ==================================================================================== -->
                <?php if ($activeTab === 'menu_master'): ?>
                    <div class="tab-pane active" id="tab_parent_menus">
                        <div style="margin-bottom: 18px;">
                            <h3 style="font-size: 18px; font-weight: 700; color: #1e293b; margin: 0 0 6px 0;">Top-Level Menu Categories</h3>
                            <p style="color: #64748b; font-size: 13px; margin: 0;">Globally activate, deactivate, or change the ordering of parent navigation menus across all roles.</p>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" style="background: #ffffff; border-radius: 4px;">
                                <thead style="background: #f1f5f9; color: #334155; font-size: 13px;">
                                    <tr>
                                        <th style="width: 70px; text-align: center;">ID</th>
                                        <th style="width: 90px; text-align: center;">Icon</th>
                                        <th>Menu Category Name</th>
                                        <th style="width: 140px; text-align: center;">Display Order</th>
                                        <th style="width: 130px; text-align: center;">Child Submenus</th>
                                        <th style="width: 130px; text-align: center;">Global Status</th>
                                        <th style="width: 140px; text-align: center;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody style="font-size: 13px;">
                                    <?php foreach ($menus as $mId => $mInfo): 
                                        $isActive = ((int)($mInfo['is_active'] ?? 1) === 1);
                                        $childCount = count($submenusGrouped[$mId] ?? array());
                                    ?>
                                        <tr>
                                            <td style="text-align: center; font-weight: 700; color: #64748b;"><?php echo $mId; ?></td>
                                            <td style="text-align: center; font-size: 16px; color: #6366f1;">
                                                <i class="<?php echo htmlspecialchars($mInfo['menu_icon']); ?>"></i>
                                            </td>
                                            <td>
                                                <span style="font-weight: 700; font-size: 14px; color: #1e293b;"><?php echo htmlspecialchars($mInfo['menu_name']); ?></span>
                                            </td>
                                            <td style="text-align: center;">
                                                <form method="POST" action="" class="form-inline" style="display: inline-block;">
                                                    <?php echo DBController::getCsrfInputField(); ?>
                                                    <input type="hidden" name="action" value="update_sort_order">
                                                    <input type="hidden" name="target_type" value="menu">
                                                    <input type="hidden" name="target_id" value="<?php echo $mId; ?>">
                                                    <input type="number" name="sort_order" value="<?php echo (int)($mInfo['sort_order'] ?? 1); ?>" min="1" max="99" style="width: 60px; height: 30px; padding: 2px 6px; border: 1px solid #cbd5e1; border-radius: 4px; text-align: center;">
                                                    <button type="submit" class="btn btn-default btn-xs" title="Save Order"><i class="fa fa-check"></i></button>
                                                </form>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge" style="background: #e2e8f0; color: #475569;"><?php echo $childCount; ?> Submenus</span>
                                            </td>
                                            <td style="text-align: center;">
                                                <?php if ($isActive): ?>
                                                    <span class="badge" style="background-color: #22c55e; padding: 5px 10px;"><i class="fa fa-check-circle"></i> Active</span>
                                                <?php else: ?>
                                                    <span class="badge" style="background-color: #ef4444; padding: 5px 10px;"><i class="fa fa-ban"></i> Disabled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <form method="POST" action="" style="display: inline;">
                                                    <?php echo DBController::getCsrfInputField(); ?>
                                                    <input type="hidden" name="action" value="toggle_menu_status">
                                                    <input type="hidden" name="menu_id" value="<?php echo $mId; ?>">
                                                    <input type="hidden" name="is_active" value="<?php echo $isActive ? '0' : '1'; ?>">
                                                    <button type="submit" class="btn btn-<?php echo $isActive ? 'danger' : 'success'; ?> btn-xs" style="padding: 4px 10px; font-weight: 600;">
                                                        <i class="fa fa-<?php echo $isActive ? 'eye-slash' : 'eye'; ?>"></i> <?php echo $isActive ? 'Disable' : 'Enable'; ?>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ==================================================================================== -->
                <!-- TAB 3: SUBMENUS MASTER (GLOBAL ENABLE / DISABLE / SORT) -->
                <!-- ==================================================================================== -->
                <?php if ($activeTab === 'submenu_master'): ?>
                    <div class="tab-pane active" id="tab_sub_menus">
                        <div style="margin-bottom: 18px;">
                            <h3 style="font-size: 18px; font-weight: 700; color: #1e293b; margin: 0 0 6px 0;">Submenu Master Registry</h3>
                            <p style="color: #64748b; font-size: 13px; margin: 0;">Comprehensive catalog of all application routes, parent categories, and global availability status.</p>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" style="background: #ffffff; border-radius: 4px;">
                                <thead style="background: #f1f5f9; color: #334155; font-size: 13px;">
                                    <tr>
                                        <th style="width: 60px; text-align: center;">ID</th>
                                        <th>Submenu Title</th>
                                        <th>Parent Category</th>
                                        <th>Route / Script Path</th>
                                        <th style="width: 120px; text-align: center;">Display Order</th>
                                        <th style="width: 110px; text-align: center;">Roles Granted</th>
                                        <th style="width: 120px; text-align: center;">Global Status</th>
                                        <th style="width: 120px; text-align: center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody style="font-size: 13px;">
                                    <?php foreach ($submenus as $sId => $sInfo): 
                                        $isActive = ((int)($sInfo['is_active'] ?? 1) === 1);
                                        $rolesCount = $subRoleCounts[$sId] ?? 0;
                                        $isProtected = in_array($sInfo['sub_menu_route'], $protectedSuperAdminRoutes, true);
                                        $isRetired = false;
                                        foreach ($retiredHistoricalRoutes as $rr) {
                                            if (strpos($sInfo['sub_menu_route'], $rr) !== false) {
                                                $isRetired = true;
                                                break;
                                            }
                                        }
                                    ?>
                                        <tr>
                                            <td style="text-align: center; font-weight: 700; color: #64748b;"><?php echo $sId; ?></td>
                                            <td>
                                                <i class="<?php echo htmlspecialchars($sInfo['sub_menu_icon']); ?>" style="color: #8b5cf6; margin-right: 6px; width: 16px;"></i>
                                                <strong style="color: #1e293b;"><?php echo htmlspecialchars($sInfo['sub_menu_name']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge" style="background: #e0e7ff; color: #4338ca; font-weight: 600;">
                                                    <?php echo htmlspecialchars($sInfo['menu_name']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <code style="color: #0284c7; background: #f0f9ff; padding: 2px 6px; border-radius: 3px;"><?php echo htmlspecialchars($sInfo['sub_menu_route']); ?></code>
                                            </td>
                                            <td style="text-align: center;">
                                                <form method="POST" action="" class="form-inline" style="display: inline-block;">
                                                    <?php echo DBController::getCsrfInputField(); ?>
                                                    <input type="hidden" name="action" value="update_sort_order">
                                                    <input type="hidden" name="target_type" value="submenu">
                                                    <input type="hidden" name="target_id" value="<?php echo $sId; ?>">
                                                    <input type="number" name="sort_order" value="<?php echo (int)($sInfo['sort_order'] ?? 1); ?>" min="1" max="99" style="width: 55px; height: 28px; padding: 2px 4px; border: 1px solid #cbd5e1; border-radius: 4px; text-align: center;">
                                                    <button type="submit" class="btn btn-default btn-xs" title="Save Order"><i class="fa fa-check"></i></button>
                                                </form>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge" style="background: #e2e8f0; color: #334155;"><?php echo $rolesCount; ?> Profiles</span>
                                            </td>
                                            <td style="text-align: center;">
                                                <?php if ($isRetired): ?>
                                                    <span class="badge" style="background-color: #64748b; padding: 4px 8px;"><i class="fa fa-archive"></i> Retired</span>
                                                <?php elseif ($isActive): ?>
                                                    <span class="badge" style="background-color: #22c55e; padding: 4px 8px;"><i class="fa fa-check-circle"></i> Active</span>
                                                <?php else: ?>
                                                    <span class="badge" style="background-color: #ef4444; padding: 4px 8px;"><i class="fa fa-ban"></i> Disabled</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="text-align: center;">
                                                <?php if ($isRetired): ?>
                                                    <span class="badge" style="background: #cbd5e1; color: #475569; padding: 4px 8px;"><i class="fa fa-lock"></i> Decommissioned</span>
                                                <?php elseif ($isProtected): ?>
                                                    <span class="badge" style="background: #f59e0b; color: #ffffff;"><i class="fa fa-lock"></i> Locked</span>
                                                <?php else: ?>
                                                    <form method="POST" action="" style="display: inline;">
                                                        <?php echo DBController::getCsrfInputField(); ?>
                                                        <input type="hidden" name="action" value="toggle_submenu_status">
                                                        <input type="hidden" name="sub_menu_id" value="<?php echo $sId; ?>">
                                                        <input type="hidden" name="is_active" value="<?php echo $isActive ? '0' : '1'; ?>">
                                                        <button type="submit" class="btn btn-<?php echo $isActive ? 'danger' : 'success'; ?> btn-xs" style="padding: 3px 8px; font-weight: 600;">
                                                            <i class="fa fa-<?php echo $isActive ? 'eye-slash' : 'eye'; ?>"></i> <?php echo $isActive ? 'Disable' : 'Enable'; ?>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ==================================================================================== -->
                <!-- TAB 4: AUDIT CHANGE LEDGER -->
                <!-- ==================================================================================== -->
                <?php if ($activeTab === 'audit_history'): ?>
                    <div class="tab-pane active" id="tab_audit_log">
                        <div style="margin-bottom: 18px;">
                            <h3 style="font-size: 18px; font-weight: 700; color: #1e293b; margin: 0 0 6px 0;">Permission Change Audit Ledger</h3>
                            <p style="color: #64748b; font-size: 13px; margin: 0;">Immutable institutional audit record tracking all menu allocation and status updates.</p>
                        </div>

                        <?php if (empty($auditRecords)): ?>
                            <div class="alert alert-info" style="border-radius: 4px;">
                                <i class="fa fa-info-circle"></i> No menu modification audit records found in the recent ledger.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" style="background: #ffffff; border-radius: 4px;">
                                    <thead style="background: #f1f5f9; color: #334155; font-size: 13px;">
                                        <tr>
                                            <th style="width: 80px; text-align: center;">Log ID</th>
                                            <th style="width: 160px;">Timestamp</th>
                                            <th style="width: 200px;">Action Type</th>
                                            <th style="width: 140px;">Administrator</th>
                                            <th>Description / State Details</th>
                                            <th style="width: 120px;">IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody style="font-size: 12px;">
                                        <?php foreach ($auditRecords as $ar): ?>
                                            <tr>
                                                <td style="text-align: center; font-weight: 700; color: #64748b;"><?php echo $ar['audit_id']; ?></td>
                                                <td><?php echo htmlspecialchars($ar['performed_at']); ?></td>
                                                <td>
                                                    <span class="badge" style="background: #475569; font-weight: 600; font-family: monospace;">
                                                        <?php echo htmlspecialchars($ar['action_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <i class="fa fa-user" style="color: #3b82f6; margin-right: 4px;"></i>
                                                    <strong><?php echo htmlspecialchars($ar['username'] ?? 'Super Admin'); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($ar['description']); ?></td>
                                                <td style="color: #64748b; font-family: monospace;"><?php echo htmlspecialchars($ar['ip_address'] ?? '127.0.0.1'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </section>
</div>

<!-- Reset to Defaults Confirmation Modal -->
<div class="modal fade" id="modalResetDefaults" tabindex="-1" role="dialog" aria-labelledby="modalResetDefaultsLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: 8px; overflow: hidden; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: #ef4444; color: #ffffff; padding: 16px 20px;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #ffffff; opacity: 0.9;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modalResetDefaultsLabel" style="font-size: 16px; font-weight: 700;">
                    <i class="fa fa-exclamation-triangle" style="margin-right: 6px;"></i> Confirm Restore Institutional Defaults
                </h4>
            </div>
            <form method="POST" action="">
                <?php echo DBController::getCsrfInputField(); ?>
                <input type="hidden" name="action" value="reset_to_defaults">
                <div class="modal-body" style="padding: 22px 24px; font-size: 14px; color: #334155;">
                    <p style="margin-bottom: 12px;"><strong>Warning:</strong> You are about to reset all navigation permissions across <strong>all role profiles</strong> (Super Admin, Admin, Coordinator, HOD, Mentor, and Student) to standard institutional defaults.</p>
                    <p style="margin-bottom: 16px; color: #64748b; font-size: 13px;">This will overwrite custom role allocations and reactivate all parent menus and submenus. This action will be immutably recorded in the institutional audit log.</p>
                    <div class="alert alert-warning" style="margin-bottom: 0; font-size: 13px; border-radius: 4px;">
                        <i class="fa fa-info-circle"></i> Super Admin settings access will be permanently preserved.
                    </div>
                </div>
                <div class="modal-footer" style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 14px 20px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal" style="font-weight: 600;">Cancel</button>
                    <button type="submit" class="btn btn-danger" style="font-weight: 700; padding: 6px 18px;">
                        <i class="fa fa-history"></i> Confirm &amp; Restore Institutional Defaults
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// =========================================================================
// INTERACTIVE PREVIEW & ALLOCATION MANAGEMENT JAVASCRIPT
// =========================================================================

function onParentMenuCheckboxChange(parentCheckbox, menuId) {
    var isChecked = parentCheckbox.checked;
    var childCheckboxes = document.querySelectorAll('.sub-menu-parent-' + menuId);
    childCheckboxes.forEach(function(cb) {
        if (!cb.disabled && cb.getAttribute('data-role-ineligible') !== '1' && cb.getAttribute('data-globally-disabled') !== '1') {
            cb.checked = isChecked;
        }
    });
    updateChildCountBadge(menuId);
    updateSidebarPreview();
}

function onSubMenuCheckboxChange(subCheckbox, menuId) {
    var parentCheckbox = document.getElementById('menu_chk_' + menuId);
    var childCheckboxes = document.querySelectorAll('.sub-menu-parent-' + menuId);
    var anyChecked = false;
    childCheckboxes.forEach(function(cb) {
        if (cb.checked) {
            anyChecked = true;
        }
    });

    if (parentCheckbox && !parentCheckbox.disabled) {
        parentCheckbox.checked = anyChecked;
    }
    updateChildCountBadge(menuId);
    updateSidebarPreview();
}

function updateChildCountBadge(menuId) {
    var badge = document.getElementById('child_count_badge_' + menuId);
    if (!badge) return;
    var childCheckboxes = document.querySelectorAll('.sub-menu-parent-' + menuId);
    var checkedCount = 0;
    childCheckboxes.forEach(function(cb) {
        if (cb.checked) checkedCount++;
    });
    badge.textContent = checkedCount + " / " + childCheckboxes.length + " active";
}

function bulkCheckAll(checkState) {
    var parentCheckboxes = document.querySelectorAll('.parent-menu-checkbox');
    parentCheckboxes.forEach(function(pb) {
        if (!pb.disabled) {
            pb.checked = checkState;
        }
        var mId = pb.getAttribute('data-menu-id');
        var childCheckboxes = document.querySelectorAll('.sub-menu-parent-' + mId);
        childCheckboxes.forEach(function(cb) {
            if (!cb.disabled && cb.getAttribute('data-role-ineligible') !== '1' && cb.getAttribute('data-globally-disabled') !== '1') {
                cb.checked = checkState;
            }
        });
        updateChildCountBadge(mId);
    });
    updateSidebarPreview();
}

function expandAllCategories(expand) {
    $('.parent-menu-box').each(function() {
        var box = $(this);
        var body = box.find('.box-body');
        var icon = box.find('[data-widget="collapse"] i');
        if (expand) {
            body.slideDown();
            icon.removeClass('fa-plus').addClass('fa-minus');
        } else {
            body.slideUp();
            icon.removeClass('fa-minus').addClass('fa-plus');
        }
    });
}

function updateSidebarPreview() {
    var previewContainer = document.getElementById('preview_sidebar_container');
    if (!previewContainer) return;

    var html = '';
    var parentCheckboxes = document.querySelectorAll('.parent-menu-checkbox');

    parentCheckboxes.forEach(function(pb) {
        if (!pb.checked) return;

        var mId = pb.getAttribute('data-menu-id');
        var menuBox = pb.closest('.parent-menu-box');
        var menuLabel = menuBox ? menuBox.querySelector('.box-header label') : null;
        var menuName = menuLabel ? menuLabel.textContent.trim() : '';
        var menuIconEl = menuBox ? menuBox.querySelector('.box-header label i') : null;
        var menuIcon = menuIconEl ? menuIconEl.className : 'fa fa-folder';

        var childCheckboxes = document.querySelectorAll('.sub-menu-parent-' + mId);
        var activeSubs = [];
        childCheckboxes.forEach(function(cb) {
            if (cb.checked && cb.getAttribute('data-role-ineligible') !== '1' && cb.getAttribute('data-globally-disabled') !== '1') {
                activeSubs.push({
                    name: cb.getAttribute('data-sub-name'),
                    icon: cb.getAttribute('data-sub-icon'),
                    route: cb.getAttribute('data-sub-route')
                });
            }
        });

        if (activeSubs.length === 0) return;

        html += '<div class="preview-menu-group" style="border-bottom: 1px solid #2a2b3d;">';
        html += '  <div style="padding: 10px 18px; color: #cbd5e1; font-size: 13px; font-weight: 600; display: flex; align-items: center; justify-content: space-between; cursor: pointer;">';
        html += '    <span><i class="' + menuIcon + '" style="margin-right: 10px; color: #818cf8; width: 16px;"></i> ' + $('<div>').text(menuName).html() + '</span>';
        html += '    <i class="fa fa-angle-down" style="color: #64748b; font-size: 12px;"></i>';
        html += '  </div>';
        html += '  <div style="background: #181824; padding: 4px 0 8px 0;">';
        activeSubs.forEach(function(sub) {
            html += '    <div style="padding: 6px 18px 6px 36px; color: #94a3b8; font-size: 12px; display: flex; align-items: center;">';
            html += '      <i class="' + sub.icon + '" style="margin-right: 8px; color: #a78bfa; font-size: 11px; width: 14px;"></i>';
            html += '      <span>' + $('<div>').text(sub.name).html() + '</span>';
            html += '    </div>';
        });
        html += '  </div>';
        html += '</div>';
    });

    if (html === '') {
        html = '<div style="padding: 30px 20px; text-align: center; color: #64748b; font-size: 13px;"><i class="fa fa-eye-slash" style="font-size: 24px; margin-bottom: 8px; display: block;"></i> No navigation items selected for this role.</div>';
    }

    previewContainer.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    updateSidebarPreview();
});
</script>

<?php
include "header/footer.php";
?>
