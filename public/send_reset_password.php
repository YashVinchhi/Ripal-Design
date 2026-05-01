<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';

$ct = static function ($key, $default = '') {
	if (function_exists('public_content_get')) {
		return public_content_get('send_reset_password', $key, $default);
	}
	return (string)$default;
};

function reset_generic_message(): string
{
	return 'If an account exists for that email, a reset link has been sent.';
}

$renderTemplate = static function ($template, array $vars = []) {
	return strtr((string)$template, $vars);
};

function redirect_with_message($message, $type = 'error')
{
	$location = './forgot.php?type=' . urlencode($type) . '&message=' . urlencode($message);
	header('Location: ' . $location);
	exit;
}

function redirect_with_flash_cookie($message, $type = 'error')
{
	$secure = function_exists('app_is_https') ? app_is_https() : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
	$cookieOptions = [
		'expires' => time() + 5,
		'path' => '/',
		'secure' => $secure,
		'httponly' => true,
		'samesite' => 'Strict'
	];

	setcookie('flash_message', $message, $cookieOptions);
	setcookie('flash_type', $type, $cookieOptions);
	header('Location: ./forgot.php');
	exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	redirect_with_message($ct('method_not_allowed', 'Method not allowed.'));
}

// Enforce CSRF for reset requests
if (!function_exists('validate_csrf_token') || !validate_csrf_token()) {
	if (function_exists('app_log')) {
		app_log('warning', 'CSRF validation failed on reset request', ['ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 'post_keys' => array_keys($_POST ?? [])]);
	}
	redirect_with_message($ct('security_token_mismatch', 'Security token mismatch. Please refresh and try again.'), 'error');
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

$clientIp = function_exists('auth_request_ip') ? auth_request_ip() : (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
$resetIpLimit = function_exists('auth_rate_limit_consume')
	? auth_rate_limit_consume('reset:ip:' . $clientIp, 10, 3600, 1800)
	: ['allowed' => true, 'retry_after' => 0];
$resetEmailLimit = function_exists('auth_rate_limit_consume')
	? auth_rate_limit_consume('reset:email:' . strtolower($email), 5, 3600, 1800)
	: ['allowed' => true, 'retry_after' => 0];

if (empty($resetIpLimit['allowed']) || empty($resetEmailLimit['allowed'])) {
	redirect_with_flash_cookie(reset_generic_message(), 'success');
}

$token = bin2hex(random_bytes(16));

$token_hash = hash("sha256", $token);
$expiry = date("Y-m-d H:i:s", time() + 60 * 30);

// Runtime schema migration is intentionally disabled; schema must be provisioned via migration scripts.
$hasTokenReset = function_exists('db_column_exists') ? db_column_exists('users', 'token_reset') : true;
$hasTokenExpiry = function_exists('db_column_exists') ? db_column_exists('users', 'reset_token_expires') : true;
if (!$hasTokenReset || !$hasTokenExpiry) {
	if (function_exists('app_log')) {
		app_log('error', 'Reset password schema missing required columns', ['token_reset' => $hasTokenReset ? 1 : 0, 'reset_token_expires' => $hasTokenExpiry ? 1 : 0]);
	}
	redirect_with_message($ct('db_migration_error', 'Password reset is temporarily unavailable. Please contact support.'));
}

$sql = 'UPDATE users SET token_reset = ?, reset_token_expires = ? WHERE email = ? LIMIT 1';

$stmt = $db->prepare($sql);
$stmt->execute([$token_hash, $expiry, $email]);

if ($stmt->rowCount() > 0) {
	$mail = require __DIR__ . '/mailer.php';
	$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
	$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
	$resetPath = rtrim(BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/reset_password.php';
	$resetLink = $scheme . '://' . $host . $resetPath . '?token=' . urlencode($token);

	$mail->setFrom(getenv('MAIL_FROM_ADDRESS') ?: getenv('MAIL_FROM') ?: 'no-reply@ripaldesign.studio', $ct('mail_from_name', 'Ripal Design'));
	$mail->addAddress($email);
	$mail->isHTML(true);
	$mail->Subject = $ct('mail_subject', 'Password Reset Request');

	// Try to get a friendly user name for the template (fallback to email)
	$userName = $email;
	try {
		$stmtUser = $db->prepare('SELECT full_name, first_name, username FROM users WHERE email = ? LIMIT 1');
		$stmtUser->execute([$email]);
		$userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
		if ($userRow) {
			if (!empty($userRow['full_name'])) {
				$userName = $userRow['full_name'];
			} elseif (!empty($userRow['first_name'])) {
				$userName = $userRow['first_name'];
			} elseif (!empty($userRow['username'])) {
				$userName = $userRow['username'];
			}
		}
	} catch (\Throwable $e) {
		// ignore and use fallback
	}

	$resetHtmlTemplate = function_exists('public_content_get_html')
		? public_content_get_html('send_reset_password', 'mail_body_html', 'Click <a href="{{reset_link}}">here</a> to reset your password. This link will expire in 30 minutes.')
		: $ct('mail_body_html', 'Click <a href="{{reset_link}}">here</a> to reset your password. This link will expire in 30 minutes.');

	$mail->Body = $renderTemplate($resetHtmlTemplate, [
		'{{reset_link}}' => htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8'),
		'{{user_name}}' => htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'),
		'[User Name]' => htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'),
	]);

	$resetTextTemplate = $ct('mail_body_text', 'Reset link: {{reset_link}}');
	$mail->AltBody = $renderTemplate($resetTextTemplate, [
		'{{reset_link}}' => $resetLink,
		'{{user_name}}' => $userName,
		'[User Name]' => $userName,
	]);
	$sent = function_exists('app_send_mail')
		? app_send_mail($mail, ['mail_type' => 'reset_password', 'email' => $email])
		: (bool)@$mail->send();

	if ($sent) {
		redirect_with_flash_cookie(reset_generic_message(), 'success');
	} else {
		if (function_exists('app_log')) {
			app_log('error', 'PHPMailer reset email send failed', ['mailer_error' => $mail->ErrorInfo ?? '', 'email' => $email]);
		}
		redirect_with_flash_cookie(reset_generic_message(), 'success');
	}
} else {
	redirect_with_flash_cookie(reset_generic_message(), 'success');
}
