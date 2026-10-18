<?php
/**
 * Isolated session probe runner for tests
 */
$mode = $argv[1] ?? 'http';

if ($mode === 'http') {
    $_SERVER['HTTPS'] = '';
    $_SERVER['SERVER_PORT'] = '80';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
    putenv('TCET_TRUSTED_PROXIES=');
} elseif ($mode === 'https') {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = '443';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
    putenv('TCET_TRUSTED_PROXIES=');
} elseif ($mode === 'spoofed_proxy') {
    // Untrusted client spoofing header over plain HTTP
    $_SERVER['HTTPS'] = '';
    $_SERVER['SERVER_PORT'] = '80';
    $_SERVER['REMOTE_ADDR'] = '198.51.100.25'; // External untrusted client IP
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
    putenv('TCET_TRUSTED_PROXIES=10.0.0.1,127.0.0.1'); // Proxy list does not include 198.51.100.25
} elseif ($mode === 'trusted_proxy') {
    // Request from trusted reverse proxy
    $_SERVER['HTTPS'] = '';
    $_SERVER['SERVER_PORT'] = '80';
    $_SERVER['REMOTE_ADDR'] = '10.0.0.1'; // Matches trusted proxy
    $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
    putenv('TCET_TRUSTED_PROXIES=10.0.0.1');
}

require_once __DIR__ . '/../database/db_connect.php';

if ($mode === 'regen') {
    DBController::startSecureSession();
    $id1 = session_id();
    $_SESSION['user_id'] = 9102;
    $_SESSION['role_id'] = 2;
    session_regenerate_id(true);
    $id2 = session_id();
    echo json_encode([
        'id1' => $id1,
        'id2' => $id2,
        'user_id' => $_SESSION['user_id'] ?? null,
    ]);
    exit(0);
}

if ($mode === 'logout') {
    DBController::startSecureSession();
    $_SESSION['user_id'] = 9102;
    $_SESSION = array();
    $cookieCleared = false;
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        $cookieCleared = true;
    }
    session_destroy();
    echo json_encode([
        'cookie_cleared' => $cookieCleared,
        'status_none' => (session_status() === PHP_SESSION_NONE),
        'session_empty' => empty($_SESSION),
    ]);
    exit(0);
}

DBController::startSecureSession();
$params = session_get_cookie_params();
echo json_encode([
    'httponly' => $params['httponly'],
    'secure' => $params['secure'],
    'samesite' => $params['samesite'],
    'use_strict_mode' => ini_get('session.use_strict_mode'),
    'use_only_cookies' => ini_get('session.use_only_cookies'),
    'session_id' => session_id(),
]);
