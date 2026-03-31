<?php
require_once __DIR__ . '/../includes/init.php';

$ct = static function ($key, $default = '') {
	if (function_exists('public_content_get')) {
		return public_content_get('send_reset_password', $key, $default);
	}
	return (string)$default;
};

$renderTemplate = static function ($template, array $vars = []) {
	return strtr((string)$template, $vars);
};

function redirect_with_message($message, $type = 'error') {
	$location = './forgot.php?type=' . urlencode($type) . '&message=' . urlencode($message);
	header('Location: ' . $location);
	exit;
}

function redirect_with_flash_cookie($message, $type = 'error') {
	$cookieOptions = [
		'expires' => time() + 5,
		'path' => '/',
		'httponly' => true,
		'samesite' => 'Lax'
	];

	setcookie('flash_message', $message, $cookieOptions);
	setcookie('flash_type', $type, $cookieOptions);
	header('Location: ./forgot.php');
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	redirect_with_message($ct('method_not_allowed', 'Method not allowed.'));
}

$db = get_db();
if (!($db instanceof PDO)) {
	redirect_with_message($ct('db_unavailable', 'Database connection unavailable. Please try later.'));
}

$email = trim($_POST['email'] ?? '');
if ($email === '') {
	redirect_with_message($ct('email_required', 'Email is required.'));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	redirect_with_message($ct('invalid_email', 'Please enter a valid email address.'));
}

$token = bin2hex(random_bytes(16));

$token_hash = hash("sha256", $token);   
$expiry = date("Y-m-d H:i:s", time() + 60 * 30);


$sql = 'UPDATE users SET token_reset = ?, reset_token_expires = ? WHERE email = ? LIMIT 1';

$stmt = $db->prepare($sql);
$stmt->execute([$token_hash, $expiry, $email]);

if ($stmt->rowCount() > 0) {
	$mail = require __DIR__ . '/mailer.php';
	$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	$resetPath = rtrim(BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/reset_password.php';
	$resetLink = $scheme . '://' . $host . $resetPath . '?token=' . urlencode($token);

	$mail->setFrom('noreply@example.com', $ct('mail_from_name', 'Ripal Design'));
	$mail->addAddress($email);
	$mail->isHTML(true);
	$mail->Subject = $ct('mail_subject', 'Password Reset Request');
	$mailBody = function_exists('public_content_get_html')
		? public_content_get_html('send_reset_password', 'mail_body_html', 'Click <a href="{{reset_link}}">here</a> to reset your password. This link will expire in 30 minutes.')
		: 'Click <a href="{{reset_link}}">here</a> to reset your password. This link will expire in 30 minutes.';
	$mail->Body = $renderTemplate($mailBody, ['{{reset_link}}' => htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8')]);
	$mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $mail->Body));
	try {
		$mail->send();
		redirect_with_flash_cookie($ct('flash_sent_success', 'Reset link sent. Please check your email.'), 'success');

	} catch (\Throwable $e) {
		error_log("PHPMailer error: {$mail->ErrorInfo} - {$e->getMessage()}\n", 3, __DIR__ . '/mail_errors.log');
		redirect_with_flash_cookie($ct('flash_sent_failed', 'Failed to send reset link.'), 'error');
	}
} else {
	redirect_with_message($ct('email_not_found', 'Email not found.'));
}
?>