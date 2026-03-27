<?php

require_once __DIR__ . '/../includes/init.php';
$token = $_POST['token'] ?? '';

function reset_redirect($message, $type = 'error', $token = '') {
    $location = './reset_password.php?type=' . urlencode($type) . '&message=' . urlencode($message);
    if ($token !== '') {
        $location .= '&token=' . urlencode($token);
    }
    header('Location: ' . $location);
    exit;
}

if ($token === '') {
    reset_redirect('Invalid reset token.');
}

$db = get_db();
if (!($db instanceof PDO)) {
    reset_redirect('Database connection unavailable. Please try later.');
}

$token_hash = hash("sha256", $token);

$stmt = $db->prepare('SELECT id, email, reset_token_expires FROM users WHERE token_reset = ? LIMIT 1');
$stmt->execute([$token_hash]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user === false){
    reset_redirect('Token not found.', 'error', $token);
}
if (strtotime($user['reset_token_expires']) <= time()) {
    reset_redirect('Token has expired.', 'error', $token);
}
$plainPassword = $_POST['password'] ?? '';
if ($plainPassword === '') {
    reset_redirect('Password is required.', 'error', $token);
}

if (strlen($plainPassword) < 8) {
    reset_redirect('Password must be at least 8 characters.', 'error', $token);
}

$password = password_hash($plainPassword, PASSWORD_DEFAULT);
$stmt = $db->prepare('UPDATE users SET password_hash = ?, token_reset = NULL, reset_token_expires = NULL WHERE id = ? LIMIT 1');
$stmt->execute([$password, (int) $user['id']]);

if ($stmt->rowCount() <= 0) {
    reset_redirect('Unable to update password. Please try again.', 'error', $token);
}

reset_redirect('Password updated successfully. You can now login.', 'success');

?>