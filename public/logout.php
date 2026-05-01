<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// If a user is logged in, clear their remember tokens
$userId = (int)($_SESSION['user_id'] ?? $_SESSION['user']['id'] ?? 0);
if ($userId > 0 && function_exists('auth_clear_remember_tokens_for_user')) {
    auth_clear_remember_tokens_for_user($userId);
}

if (function_exists('auth_clear_remember_cookie')) {
    auth_clear_remember_cookie();
}

// Log logout event
$clientIp = function_exists('auth_request_ip') ? auth_request_ip() : (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
if ($userId > 0 && function_exists('app_log')) {
    app_log('info', 'User logged out', ['user_id' => $userId, 'ip' => $clientIp]);
}

// Destroy session and redirect to home
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    $cookieOptions = [
        'expires' => time() - 42000,
        'path' => $params['path'] ?? '/',
        'secure' => (bool)($params['secure'] ?? false),
        'httponly' => (bool)($params['httponly'] ?? true),
        'samesite' => 'Strict',
    ];
    if (!empty($params['domain'])) {
        $cookieOptions['domain'] = (string)$params['domain'];
    }
    setcookie(session_name(), '', $cookieOptions);
}
session_destroy();
header('Location: ../public/index.php');
exit;
?>
