<?php
/**
 * TCET ERP — Comprehensive Functional & RBAC Automated Probe Suite
 * Tests every major page and endpoint under:
 * - Super Admin (role 1)
 * - Admin (role 2)
 * - Coordinator / HOD (role 3)
 * - Mentor (role 4)
 * - Student (role 5)
 * - Unauthenticated guest
 */

require_once __DIR__ . '/../database/db_connect.php';

$db = new DBController();
$conn = $db->conn;

$baseUrl = 'http://127.0.0.1/st';

function probeUrl($url, $cookie = null, $method = 'GET', $postData = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($cookie) {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($postData) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'headers' => $headers,
        'body' => $body
    ];
}

function getSessionCookieForRole($roleName) {
    global $baseUrl;
    $bridgeUrl = "$baseUrl/tests/uat_session_bridge.php?user=$roleName&db=tcet_st";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $bridgeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch);
    curl_close($ch);

    if (preg_match('/Set-Cookie:\s*([^;]+)/i', $res, $matches)) {
        return $matches[1];
    }
    return null;
}

$roles = ['superadmin', 'admin', 'coordinator', 'mentor', 'student'];
$sessionCookies = [];
foreach ($roles as $r) {
    $sessionCookies[$r] = getSessionCookieForRole($r);
}

// Matrix of routes: Allowed roles should get 200. Unauthorized roles MUST get 302 or 403 (never 200).
$pageMatrix = [
    // [Page, Allowed Roles]
    ['admin/index.php', ['superadmin', 'admin']],
    ['admin/student-info.php', ['superadmin', 'admin']],
    ['admin/student_view.php?id=9016', ['superadmin', 'admin', 'coordinator']],
    ['admin/student_bulk_import.php', ['superadmin', 'admin']],
    ['admin/class_manage.php', ['superadmin', 'admin']],
    ['admin/branch_info.php', ['superadmin', 'admin']],
    ['admin/hod_mentor_allocation.php', ['superadmin', 'admin']],
    ['admin/mentor_assignment.php', ['superadmin', 'admin', 'coordinator']],
    ['admin/user-info.php', ['superadmin', 'admin']],
    ['admin/menu_submenu_allocation.php', ['superadmin', 'admin']],
    ['admin/document_view.php?type=marklist&student_id=9016&file=HISTORICAL_MIGRATION_REF_SEM4-0001', ['superadmin', 'admin', 'coordinator']],
    // Protected Directories (NO ROLE ALLOWED -> MUST BE 403)
    ['admin/uploads/marklists/index.php', []],
    ['database/.htaccess', []]
];

$results = [];
$totalChecks = 0;
$passedChecks = 0;
$failedChecks = 0;

foreach ($pageMatrix as $item) {
    $path = $item[0];
    $allowed = $item[1];

    $pageResult = ['path' => $path, 'allowed_roles' => $allowed, 'checks' => []];

    // Check each role
    foreach ($roles as $role) {
        $cookie = $sessionCookies[$role];
        $probe = probeUrl("$baseUrl/$path", $cookie);
        $code = $probe['code'];
        $isAllowed = in_array($role, $allowed, true);

        if ($isAllowed) {
            $ok = ($code === 200);
            $expectedStr = '200';
        } else {
            $ok = in_array($code, [302, 403], true);
            $expectedStr = '302 or 403';
        }

        $totalChecks++;
        if ($ok) $passedChecks++; else $failedChecks++;

        $pageResult['checks'][$role] = [
            'expected' => $expectedStr,
            'actual' => $code,
            'pass' => $ok
        ];
    }

    // Check guest
    $probeGuest = probeUrl("$baseUrl/$path", null);
    $codeGuest = $probeGuest['code'];
    $okGuest = in_array($codeGuest, [302, 403], true);
    $totalChecks++;
    if ($okGuest) $passedChecks++; else $failedChecks++;

    $pageResult['checks']['guest'] = [
        'expected' => '302 or 403',
        'actual' => $codeGuest,
        'pass' => $okGuest
    ];

    $results[] = $pageResult;
}

// 2. Functional & Specific Security Unit Tests
$functionalTests = [];

// A. User Directory AJAX (SuperAdmin)
$userAjaxProbe = probeUrl("$baseUrl/admin/user_info_ajax.php", $sessionCookies['superadmin'], 'POST', 'draw=1&start=0&length=10&role_id=0');
$userAjaxOk = ($userAjaxProbe['code'] === 200 && strpos($userAjaxProbe['body'], '"recordsTotal":2') !== false);
$totalChecks++; if ($userAjaxOk) $passedChecks++; else $failedChecks++;
$functionalTests['User Directory AJAX Output'] = [
    'pass' => $userAjaxOk,
    'code' => $userAjaxProbe['code'],
    'details' => $userAjaxOk ? 'Verified exactly 2 administrative accounts returned in valid JSON' : 'Mismatch'
];

// B. Student Info AJAX (SuperAdmin)
$studentAjaxProbe = probeUrl("$baseUrl/admin/student_info_ajax.php", $sessionCookies['superadmin'], 'POST', 'draw=1&start=0&length=10');
$studentAjaxOk = ($studentAjaxProbe['code'] === 200 && strpos($studentAjaxProbe['body'], '"recordsTotal":877') !== false);
$totalChecks++; if ($studentAjaxOk) $passedChecks++; else $failedChecks++;
$functionalTests['Student Directory AJAX Pagination'] = [
    'pass' => $studentAjaxOk,
    'code' => $studentAjaxProbe['code'],
    'details' => $studentAjaxOk ? 'Verified exactly 877 registered students returned' : 'Mismatch'
];

// C. Document Viewer Security Proxy
$docViewerProbe = probeUrl("$baseUrl/admin/document_view.php?type=marklist&student_id=9016&file=HISTORICAL_MIGRATION_REF_SEM4-0001", $sessionCookies['superadmin']);
$docViewerOk = ($docViewerProbe['code'] === 200 && (strpos($docViewerProbe['headers'], 'application/pdf') !== false || strpos($docViewerProbe['headers'], 'image/') !== false || strpos($docViewerProbe['body'], 'HISTORICAL') !== false));
$totalChecks++; if ($docViewerOk) $passedChecks++; else $failedChecks++;
$functionalTests['Document Viewer Proxy Delivery'] = [
    'pass' => $docViewerOk,
    'code' => $docViewerProbe['code'],
    'details' => $docViewerOk ? 'Document served via authorized application streaming proxy' : 'Failed'
];

// D. Path Traversal Guard
$docTraversalProbe = probeUrl("$baseUrl/admin/document_view.php?type=marklist&student_id=9016&file=../../../../../../windows/win.ini", $sessionCookies['superadmin']);
$traversalBlocked = in_array($docTraversalProbe['code'], [400, 403, 404], true);
$totalChecks++; if ($traversalBlocked) $passedChecks++; else $failedChecks++;
$functionalTests['Path Traversal Protection'] = [
    'pass' => $traversalBlocked,
    'code' => $docTraversalProbe['code'],
    'details' => $traversalBlocked ? 'Traversal request rejected with HTTP 400/403' : 'Path traversal vulnerability!'
];

// E. SuperAdmin Protection
$res = mysqli_query($conn, "SELECT user_id FROM st_user_master WHERE role_id = 1 LIMIT 1");
$superAdminId = mysqli_fetch_assoc($res)['user_id'];
$delProbe = probeUrl("$baseUrl/admin/user_delete.php", $sessionCookies['superadmin'], 'POST', "user_id=$superAdminId");
$delBlocked = (strpos($delProbe['body'], 'cannot') !== false || strpos($delProbe['body'], 'Protected') !== false || strpos($delProbe['body'], 'false') !== false || $delProbe['code'] === 403);
$totalChecks++; if ($delBlocked) $passedChecks++; else $failedChecks++;
$functionalTests['Protected SuperAdmin Immutability'] = [
    'pass' => $delBlocked,
    'code' => $delProbe['code'],
    'details' => $delBlocked ? 'Protected user account deletion safely blocked' : 'SuperAdmin was vulnerable to deletion!'
];

$summary = [
    'totalChecks' => $totalChecks,
    'passedChecks' => $passedChecks,
    'failedChecks' => $failedChecks,
    'status' => ($failedChecks === 0) ? 'ALL_PASSED' : 'DEFECTS_FOUND',
    'pageMatrixResults' => $results,
    'functionalTests' => $functionalTests
];

echo json_encode($summary, JSON_PRETTY_PRINT);
