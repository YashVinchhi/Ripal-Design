<?php

require_once __DIR__ . '/../includes/init.php';
if (file_exists(__DIR__ . '/../includes/validation.php')) {
    require_once __DIR__ . '/../includes/validation.php';
}
$ct = static function ($key, $default = '') {
    if (function_exists('public_content_get')) {
        return public_content_get('update_password', $key, $default);
    }
    return (string)$default;
};
$token = $_POST['token'] ?? '';

if (!csrf_validate((string)($_POST['csrf_token'] ?? ''))) {
    reset_redirect($ct('csrf_invalid', 'Invalid request token. Please refresh and try again.'), 'error', (string)$token);
}

function reset_redirect($message, $type = 'error', $token = '') {
    $location = './reset_password.php?type=' . urlencode($type) . '&message=' . urlencode($message);
    if ($token !== '') {
        $location .= '&token=' . urlencode($token);
    }
    header('Location: ' . $location);
    exit;
}

if ($token === '') {
    reset_redirect($ct('invalid_token', 'Invalid reset token.'));
}

$db = get_db();
if (!($db instanceof PDO)) {
    reset_redirect($ct('db_unavailable', 'Database connection unavailable. Please try later.'));
}

$token_hash = hash("sha256", $token);

$stmt = $db->prepare('SELECT id, email, reset_token_expires FROM users WHERE token_reset = ? LIMIT 1');
$stmt->execute([$token_hash]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user === false){
    reset_redirect($ct('token_not_found', 'Token not found.'), 'error', $token);
}
if (strtotime($user['reset_token_expires']) <= time()) {
    reset_redirect($ct('token_expired', 'Token has expired.'), 'error', $token);
}
$plainPassword = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
if ($plainPassword === '') {
    reset_redirect($ct('password_required', 'Password is required.'), 'error', $token);
}

if ($confirmPassword !== '' && $plainPassword !== $confirmPassword) {
    reset_redirect($ct('password_mismatch', 'Password and confirm password do not match.'), 'error', $token);
}

if (!function_exists('validate_password_strength') || !validate_password_strength((string)$plainPassword)) {
    reset_redirect($ct('password_strength', 'Password must be at least 8 characters with 1 uppercase, 1 lowercase, and 1 number.'), 'error', $token);
}

$password = password_hash($plainPassword, PASSWORD_DEFAULT);
$stmt = $db->prepare('UPDATE users SET password_hash = ?, token_reset = NULL, reset_token_expires = NULL WHERE id = ? LIMIT 1');
$stmt->execute([$password, (int) $user['id']]);

if ($stmt->rowCount() <= 0) {
    reset_redirect($ct('update_failed', 'Unable to update password. Please try again.'), 'error', $token);
}

reset_redirect($ct('update_success', 'Password updated successfully. You can now login.'), 'success');

?>