<?php
// Central auth guard for legacy includes. Prefer using app/Core/Bootstrap/init.php
// and its built-in enforcement, but this file provides an easy one-line guard
// that legacy pages can require at top to ensure session-based auth.

if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.use_strict_mode', '1');
    @session_start();
}

// If already authenticated, nothing to do
if (!empty($_SESSION['user_id']) || !empty($_SESSION['user'])) {
    return;
}

// Determine whether to return JSON (API/AJAX) or redirect to login page.
$isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
$loginPath = (defined('PUBLIC_PATH_PREFIX') ? rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/login.php' : '/public/login.php');

if ($isAjax || strpos($accept, 'application/json') !== false) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
    }
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

// Otherwise redirect to login page for interactive browser requests
if (!headers_sent()) {
    header('Location: ' . $loginPath);
}
exit;
