<?php
/**
 * TCET ERP - Session Security & Cookie Configuration Regression Suite
 * Tests session cookie parameters under local HTTP, production HTTPS,
 * spoofed proxy header rejection, trusted proxy acceptance,
 * session regeneration on login, session invalidation on logout, and strict session mode.
 */

$passCount = 0;
$failCount = 0;

function assertSessionTest($condition, $description) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] {$description}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$description}\n";
        $failCount++;
    }
}

echo "=======================================================\n";
echo "TCET ERP - SESSION SECURITY & COOKIE CONFIGURATION TEST\n";
echo "=======================================================\n\n";

$phpBin = 'C:\\xampp\\php\\php.exe';
$probeScript = __DIR__ . '/session_probe.php';

function runProbe($mode) {
    global $phpBin, $probeScript;
    $cmd = "\"{$phpBin}\" \"{$probeScript}\" {$mode}";
    $out = shell_exec($cmd);
    return json_decode(trim($out), true);
}

// 1. Local HTTP Simulation (Localhost development)
$localRes = runProbe('http');

assertSessionTest(($localRes['httponly'] ?? false) === true, "Local HTTP: HttpOnly cookie flag is strictly enabled (protects against XSS session theft)");
assertSessionTest(($localRes['samesite'] ?? '') === 'Lax', "Local HTTP: SameSite policy is set to 'Lax' (protects against CSRF)");
assertSessionTest(($localRes['secure'] ?? null) === false, "Local HTTP: Secure flag is false on HTTP localhost to allow local developer testing");
assertSessionTest(($localRes['use_strict_mode'] ?? '') == '1', "Local HTTP: Strict session mode is enabled (prevents session fixation)");
assertSessionTest(($localRes['use_only_cookies'] ?? '') == '1', "Local HTTP: use_only_cookies is enabled (disallows session ID in URLs)");

// 2. Production HTTPS Simulation (Standard direct HTTPS)
$httpsRes = runProbe('https');

assertSessionTest(($httpsRes['httponly'] ?? false) === true, "Production HTTPS: HttpOnly cookie flag is strictly enabled");
assertSessionTest(($httpsRes['samesite'] ?? '') === 'Lax', "Production HTTPS: SameSite policy is set to 'Lax'");
assertSessionTest(($httpsRes['secure'] ?? false) === true, "Production HTTPS: Secure flag is strictly enabled over direct HTTPS (port 443)");
assertSessionTest(($httpsRes['use_strict_mode'] ?? '') == '1', "Production HTTPS: Strict session mode is enabled");
assertSessionTest(($httpsRes['use_only_cookies'] ?? '') == '1', "Production HTTPS: use_only_cookies is enabled");

// 3. Spoofed Reverse-Proxy Header Defense
$spoofRes = runProbe('spoofed_proxy');
assertSessionTest(($spoofRes['secure'] ?? null) === false, "Reverse-Proxy Defense: Client-spoofed X-Forwarded-Proto from untrusted IP is safely REJECTED");
assertSessionTest(($spoofRes['httponly'] ?? false) === true, "Reverse-Proxy Defense: HttpOnly flag remains active despite spoofing attempt");

// 4. Valid Reverse-Proxy HTTPS via Designated Trusted Proxy
$trustedRes = runProbe('trusted_proxy');
assertSessionTest(($trustedRes['secure'] ?? false) === true, "Reverse-Proxy Trusted: Secure flag is enabled when originating from designated trusted proxy IP");
assertSessionTest(($trustedRes['httponly'] ?? false) === true, "Reverse-Proxy Trusted: HttpOnly flag strictly enabled");
assertSessionTest(($trustedRes['samesite'] ?? '') === 'Lax', "Reverse-Proxy Trusted: SameSite=Lax preserved behind trusted proxy");

// 5. Session ID Regeneration Verification (Simulate Authentication Login)
$regenRes = runProbe('regen');

assertSessionTest(!empty($regenRes['id1']) && !empty($regenRes['id2']), "Session IDs are generated and non-empty");
assertSessionTest($regenRes['id1'] !== $regenRes['id2'], "Authentication: session_regenerate_id(true) issues new session ID, preventing fixation");
assertSessionTest($regenRes['user_id'] === 9102, "Authentication: Session state and role variables correctly preserved across regeneration");

// 6. Session Destruction & Cookie Invalidation on Logout
$logoutRes = runProbe('logout');

assertSessionTest(($logoutRes['cookie_cleared'] ?? false) === true, "Logout: Session cookie parameters retrieved for deletion");
assertSessionTest(($logoutRes['status_none'] ?? false) === true, "Logout: session_destroy() invalidates active session state");
assertSessionTest(($logoutRes['session_empty'] ?? false) === true, "Logout: \$_SESSION superglobal array completely emptied");

echo "\n=======================================================\n";
echo "SESSION SECURITY SUMMARY: {$passCount} Passed, {$failCount} Failed\n";
echo "=======================================================\n";

exit($failCount > 0 ? 1 : 0);
