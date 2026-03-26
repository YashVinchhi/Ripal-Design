<?php
require_once __DIR__ . '/../includes/init.php';

if (APP_ENV !== 'development') {
    http_response_code(404);
    exit('Not Found');
}

require_login();
require_role('admin');

$uid = current_user_id();
$username = current_username();
$role = (string)(current_user()['role'] ?? 'unknown');

$dbUser = null;
if ($uid > 0 && function_exists('db_connected') && db_connected() && function_exists('db_table_exists') && db_table_exists('users')) {
    $dbUser = db_fetch('SELECT id, username, role FROM users WHERE id = ? LIMIT 1', [$uid]);
}

header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html><head><meta charset="utf-8"><title>Debug Session</title></head><body style="font-family:Arial,Helvetica,sans-serif;padding:20px">';
echo '<h1>Development Debug Session</h1>';
echo '<p><strong>Session user id:</strong> ' . (int)$uid . '</p>';
echo '<p><strong>Session username:</strong> ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '</p>';
echo '<p><strong>Session role:</strong> ' . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . '</p>';
if ($dbUser) {
    echo '<h2>Database user</h2>';
    echo '<p><strong>id:</strong> ' . (int)($dbUser['id'] ?? 0) . '</p>';
    echo '<p><strong>username:</strong> ' . htmlspecialchars((string)($dbUser['username'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p><strong>role:</strong> ' . htmlspecialchars((string)($dbUser['role'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>';
} else {
    echo '<p>Database user details unavailable.</p>';
}
echo '</body></html>';
