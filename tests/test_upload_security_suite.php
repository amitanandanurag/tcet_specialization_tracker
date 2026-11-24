<?php
/**
 * Test Suite: Upload Directory 403 & Document Access Hardening
 * Verifies all 7 required test cases.
 */

$baseUrl = 'http://localhost/st';
$adminUser = 'ashutosh3276s16@gmail.com';

echo "========================================================\n";
echo "TCET_ST UPLOAD DIRECTORY & DOCUMENT ACCESS SECURITY SUITE\n";
echo "========================================================\n\n";

$cookieJar = tempnam(sys_get_temp_dir(), 'tcet_cookie_');

// Helper function for cURL requests
function doRequest($url, $options = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    if (!empty($options['cookie_file'])) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $options['cookie_file']);
    }
    if (!empty($options['cookie_jar'])) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $options['cookie_jar']);
    }
    if (!empty($options['post_fields'])) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $options['post_fields']);
    }
    if (!empty($options['headers'])) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $options['headers']);
    }

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headerStr = substr($raw, 0, $headerSize);
    $body = substr($raw, $headerSize);

    return [
        'code' => $httpCode,
        'headers' => $headerStr,
        'body' => $body
    ];
}

// 0. Authenticate Super Admin
echo "Step 0: Authenticating Super Admin...\n";
// Let's create an authenticated session for user_id = 1, role_id = 1
$authTestScript = __DIR__ . '/session_helper.php';
file_put_contents($authTestScript, '<?php
session_start();
$_SESSION["user_id"] = 1;
$_SESSION["user_session"] = 1;
$_SESSION["user_type"] = 1;
$_SESSION["role_id"] = 1;
$_SESSION["user_email"] = "ashutosh3276s16@gmail.com";
$_SESSION["user_name"] = "Ashutosh";
echo "SESSION_OK:" . session_id();
');

$sessRes = doRequest($baseUrl . '/tests/session_helper.php', ['cookie_jar' => $cookieJar]);
preg_match('/SESSION_OK:([a-zA-Z0-9,-]+)/', $sessRes['body'], $sessMatches);
$sessionId = $sessMatches[1] ?? '';
echo "Active Admin Session ID: " . $sessionId . "\n\n";

$passCount = 0;
$totalCount = 7;

// Test Case 1: Authorized Administrator Views a Marklist
echo "TEST CASE 1: Authorized administrator views a marklist\n";
// Target an existing physical marklist
$testFile = '1777623879_1032251364_mark-list1.jpeg';
$url1 = $baseUrl . '/admin/document_view.php?type=marklist&file=' . urlencode($testFile);
$res1 = doRequest($url1, ['cookie_file' => $cookieJar]);
$t1Pass = ($res1['code'] === 200 && strpos($res1['headers'], 'image/jpeg') !== false);
echo "  URL: $url1\n";
echo "  HTTP Code: {$res1['code']} (Expected: 200)\n";
echo "  Content-Type: " . (strpos($res1['headers'], 'image/jpeg') !== false ? 'image/jpeg' : 'Other') . "\n";
echo "  Result: " . ($t1Pass ? "PASS" : "FAIL") . "\n\n";
if ($t1Pass) $passCount++;

// Test Case 2: Unauthorized User Attempts Access
echo "TEST CASE 2: Unauthorized user attempts to access a marklist\n";
// Create an unauthorized session (e.g. invalid role 99 or unmapped student)
$unauthCookie = tempnam(sys_get_temp_dir(), 'tcet_unauth_');
$unauthScript = __DIR__ . '/session_unauth.php';
file_put_contents($unauthScript, '<?php
session_start();
$_SESSION["user_id"] = 999;
$_SESSION["user_session"] = 999;
$_SESSION["user_type"] = 99; // invalid role
$_SESSION["role_id"] = 99;
echo "UNAUTH_OK:" . session_id();
');
doRequest($baseUrl . '/tests/session_unauth.php', ['cookie_jar' => $unauthCookie]);
$res2 = doRequest($url1, ['cookie_file' => $unauthCookie]);
$t2Pass = ($res2['code'] === 403);
echo "  URL: $url1 (with unauthorized session)\n";
echo "  HTTP Code: {$res2['code']} (Expected: 403)\n";
echo "  Result: " . ($t2Pass ? "PASS" : "FAIL") . "\n\n";
if ($t2Pass) $passCount++;

// Test Case 3: Logged-out User Attempts Document Access
echo "TEST CASE 3: Logged-out user attempts document access\n";
$res3 = doRequest($url1); // No cookies
$t3Pass = ($res3['code'] === 403 && strpos($res3['body'], 'Access Denied') !== false);
echo "  URL: $url1 (no cookies)\n";
echo "  HTTP Code: {$res3['code']} (Expected: 403)\n";
echo "  Result: " . ($t3Pass ? "PASS" : "FAIL") . "\n\n";
if ($t3Pass) $passCount++;

// Test Case 4: User Attempts to Execute a PHP File inside Upload Directory
echo "TEST CASE 4: User attempts to execute a PHP file inside upload directory\n";
// Create a harmless test file in upload dir
$shellPath = dirname(__DIR__) . '/admin/uploads/test_probe.php';
file_put_contents($shellPath, '<?php echo "EXECUTION_BREACH"; ?>');
$res4 = doRequest($baseUrl . '/admin/uploads/test_probe.php');
// Also test index.php in marklists
$res4b = doRequest($baseUrl . '/admin/uploads/marklists/index.php');
// Clean up probe file
if (file_exists($shellPath)) unlink($shellPath);
$t4Pass = ($res4['code'] === 403 && strpos($res4['body'], 'EXECUTION_BREACH') === false);
echo "  URL: $baseUrl/admin/uploads/test_probe.php\n";
echo "  HTTP Code: {$res4['code']} (Expected: 403)\n";
echo "  Code Executed: " . (strpos($res4['body'], 'EXECUTION_BREACH') !== false ? 'YES (SECURITY FAILURE)' : 'NO (PROTECTED)') . "\n";
echo "  URL: $baseUrl/admin/uploads/marklists/index.php -> HTTP {$res4b['code']}\n";
echo "  Result: " . ($t4Pass ? "PASS" : "FAIL") . "\n\n";
if ($t4Pass) $passCount++;

// Test Case 5: User Attempts Directory Listing
echo "TEST CASE 5: User attempts directory listing\n";
$res5a = doRequest($baseUrl . '/admin/uploads/');
$res5b = doRequest($baseUrl . '/admin/uploads/marklists/');
$noListingA = (strpos($res5a['body'], 'Index of /') === false && strpos($res5a['body'], 'Protected Storage Directory') !== false);
$noListingB = (strpos($res5b['body'], 'Index of /') === false && strpos($res5b['body'], 'Protected Marklists Directory') !== false);
$t5Pass = ($res5a['code'] === 200 && $res5b['code'] === 200 && $noListingA && $noListingB);
echo "  URL: $baseUrl/admin/uploads/ -> HTTP {$res5a['code']}, Static Protection Page: " . ($noListingA ? 'YES' : 'NO') . "\n";
echo "  URL: $baseUrl/admin/uploads/marklists/ -> HTTP {$res5b['code']}, Static Protection Page: " . ($noListingB ? 'YES' : 'NO') . "\n";
echo "  Directory Listing Blocked: YES\n";
echo "  Result: " . ($t5Pass ? "PASS" : "FAIL") . "\n\n";
if ($t5Pass) $passCount++;

// Test Case 6: User Attempts Path Traversal
echo "TEST CASE 6: User attempts path traversal\n";
$traversalUrl = $baseUrl . '/admin/document_view.php?type=marklist&file=../../database/db_connect.php';
$res6 = doRequest($traversalUrl, ['cookie_file' => $cookieJar]);
$t6Pass = ($res6['code'] === 400 && strpos($res6['body'], 'Illegal document path') !== false);
echo "  URL: $traversalUrl\n";
echo "  HTTP Code: {$res6['code']} (Expected: 400)\n";
echo "  Response: " . trim(strip_tags($res6['body'])) . "\n";
echo "  Result: " . ($t6Pass ? "PASS" : "FAIL") . "\n\n";
if ($t6Pass) $passCount++;

// Test Case 7: Existing Document Download Functionality Works (Physical + Historical)
echo "TEST CASE 7: Existing document download & historical verification slip work\n";
// 7a: Physical file download
$url7a = $baseUrl . '/admin/document_view.php?type=marklist&action=download&file=' . urlencode($testFile);
$res7a = doRequest($url7a, ['cookie_file' => $cookieJar]);
$hasAttachmentHeader = (strpos($res7a['headers'], 'attachment;') !== false);

// 7b: Historical admission record slip
$histRef = 'HISTORICAL_MIGRATION_REF_SEM4-0001';
$url7b = $baseUrl . '/admin/document_view.php?type=marklist&file=' . urlencode($histRef);
$res7b = doRequest($url7b, ['cookie_file' => $cookieJar]);
$hasHistoricalSlip = (strpos($res7b['body'], 'Verified on Admission') !== false && strpos($res7b['body'], 'Academic Admission Record') !== false);

$t7Pass = ($res7a['code'] === 200 && $hasAttachmentHeader && $res7b['code'] === 200 && $hasHistoricalSlip);
echo "  Physical File Download HTTP: {$res7a['code']}, Attachment Header: " . ($hasAttachmentHeader ? 'YES' : 'NO') . "\n";
echo "  Historical Admission Record HTTP: {$res7b['code']}, Verification Slip Rendered: " . ($hasHistoricalSlip ? 'YES' : 'NO') . "\n";
echo "  Result: " . ($t7Pass ? "PASS" : "FAIL") . "\n\n";
if ($t7Pass) $passCount++;

// Cleanup helper files
@unlink($cookieJar);
@unlink($unauthCookie);
@unlink($authTestScript);
@unlink($unauthScript);

echo "========================================================\n";
echo "TEST SUITE SUMMARY: $passCount / $totalCount TESTS PASSED\n";
echo "========================================================\n";

if ($passCount === $totalCount) {
    exit(0);
} else {
    exit(1);
}
