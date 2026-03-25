<?php

require_once __DIR__ . '/../sql/config.php';
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

$token_hash = hash("sha256", $token);


$sql = "SELECT * FROM signup WHERE token_reset = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token_hash);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if ($user === null){
    reset_redirect('Token not found.', 'error', $token);
}
if (strtotime($user['reset_token_expires']) <= time()) {
    reset_redirect('Token has expired.', 'error', $token);
}
$plainPassword = $_POST['password'] ?? '';
if ($plainPassword === '') {
    reset_redirect('Password is required.', 'error', $token);
}
$password = password_hash($plainPassword, PASSWORD_DEFAULT);
$sql = "UPDATE signup SET password = ?, token_reset = NULL, reset_token_expires = NULL WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $password, $user['email']);
$stmt->execute();
reset_redirect('Password updated successfully. You can now login.', 'success');

?>