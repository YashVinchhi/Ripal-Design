<?php
require_once __DIR__ . '/../includes/init.php';

$ct = static function ($key, $default = '') {
    if (function_exists('public_content_get')) {
        return public_content_get('debug_session', $key, $default);
    }
    return (string)$default;
};

if (APP_ENV !== 'development') {
    http_response_code(404);
    exit($ct('not_found', 'Not Found'));
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
echo '<!doctype html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($ct('page_title', 'Debug Session'), ENT_QUOTES, 'UTF-8') . '</title></head><body style="font-family:Arial,Helvetica,sans-serif;padding:20px">';
echo '<h1>' . htmlspecialchars($ct('heading', 'Development Debug Session'), ENT_QUOTES, 'UTF-8') . '</h1>';
echo '<p><strong>' . htmlspecialchars($ct('label_session_user_id', 'Session user id:'), ENT_QUOTES, 'UTF-8') . '</strong> ' . (int)$uid . '</p>';
echo '<p><strong>' . htmlspecialchars($ct('label_session_username', 'Session username:'), ENT_QUOTES, 'UTF-8') . '</strong> ' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '</p>';
echo '<p><strong>' . htmlspecialchars($ct('label_session_role', 'Session role:'), ENT_QUOTES, 'UTF-8') . '</strong> ' . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . '</p>';
if ($dbUser) {
    echo '<h2>' . htmlspecialchars($ct('db_section_heading', 'Database user'), ENT_QUOTES, 'UTF-8') . '</h2>';
    echo '<p><strong>' . htmlspecialchars($ct('db_label_id', 'id:'), ENT_QUOTES, 'UTF-8') . '</strong> ' . (int)($dbUser['id'] ?? 0) . '</p>';
    echo '<p><strong>' . htmlspecialchars($ct('db_label_username', 'username:'), ENT_QUOTES, 'UTF-8') . '</strong> ' . htmlspecialchars((string)($dbUser['username'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p><strong>' . htmlspecialchars($ct('db_label_role', 'role:'), ENT_QUOTES, 'UTF-8') . '</strong> ' . htmlspecialchars((string)($dbUser['role'] ?? ''), ENT_QUOTES, 'UTF-8') . '</p>';
} else {
    echo '<p>' . htmlspecialchars($ct('db_unavailable', 'Database user details unavailable.'), ENT_QUOTES, 'UTF-8') . '</p>';
}
echo '</body></html>';
