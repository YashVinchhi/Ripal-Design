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

// Destroy session and redirect to home
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
header('Location: ../public/index.php');
exit;
?>