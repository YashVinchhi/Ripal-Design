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

function redirect_with_message($message, $type = 'error')
{
	$location = './forgot.php?type=' . urlencode($type) . '&message=' . urlencode($message);
	header('Location: ' . $location);
	exit;
}

function redirect_with_flash_cookie($message, $type = 'error')
{
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

// Ensure the users table has the expected reset columns. If missing, attempt to add them.
$needsAdd = false;
try {
	$colCheck = $db->prepare("SHOW COLUMNS FROM users LIKE 'token_reset'");
	$colCheck->execute();
	$needsAdd = ($colCheck->fetch(PDO::FETCH_ASSOC) === false);
} catch (\Throwable $e) {
	// If we can't run SHOW COLUMNS, avoid altering DB and continue to attempt the update (will fail later)
	$needsAdd = false;
}

if ($needsAdd) {
	try {
		// Add the token and expiry columns and an index for the token
		$db->beginTransaction();
		$db->exec("ALTER TABLE users ADD COLUMN token_reset CHAR(64) DEFAULT NULL, ADD COLUMN reset_token_expires DATETIME DEFAULT NULL");
		$db->exec("CREATE INDEX idx_users_reset_token ON users(token_reset)");
		$db->commit();
	} catch (\Throwable $e) {
		if ($db->inTransaction()) {
			$db->rollBack();
		}
		if (function_exists('app_log')) {
			app_log('error', 'Failed to add reset columns', ['exception' => $e->getMessage()]);
		}
		redirect_with_message($ct('db_migration_error', 'Password reset is temporarily unavailable. Please contact support.'));
	}
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

	$mail->setFrom('noreply@example.com', $ct('mail_from_name', 'Ripal Design'));
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

	// Default HTML template (can be overridden via public content)
	$defaultHtml = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Reset Your Password - Ripal Design</title>
	<style>
		/* Base reset for email clients */
		body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
		table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
		img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        
		/* Responsive styles */
		@media screen and (max-width: 600px) {
			.email-container {
				width: 100% !important;
				margin: auto !important;
			}
			.content-padding {
				padding: 20px !important;
			}
		}
	</style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">

	<!-- 100% background wrapper -->
	<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f4f5; padding: 40px 0;">
		<tr>
			<td align="center">
				<!-- Main Email Container -->
				<table border="0" cellpadding="0" cellspacing="0" width="600" class="email-container" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    
					<!-- Header/Logo Area -->
					<tr>
						<td align="center" style="padding: 40px 0 20px 0; background-color: #ffffff;">
							<h1 style="margin: 0; color: #111827; font-size: 28px; font-weight: 700; letter-spacing: -0.5px;">
								<span style="color: #731209;">Ripal</span> Design
							</h1>
						</td>
					</tr>

					<!-- Main Content Area -->
					<tr>
						<td class="content-padding" style="padding: 20px 40px 40px 40px; color: #4b5563; line-height: 1.6; font-size: 16px;">
							<p style="margin-top: 0; margin-bottom: 20px; font-weight: 600; color: #111827;">Hello {{user_name}},</p>
                            
							<p style="margin-top: 0; margin-bottom: 24px;">
								We received a request to reset the password for your Ripal Design account. If you made this request, you can reset your password by clicking the button below.
							</p>

							<!-- Call to Action Button -->
							<table border="0" cellpadding="0" cellspacing="0" width="100%">
								<tr>
									<td align="center" style="padding: 10px 0 30px 0;">
										<table border="0" cellpadding="0" cellspacing="0">
											<tr>
												<td align="center" style="border-radius: 6px;" bgcolor="#731209">
													<a href="{{reset_link}}" target="_blank" style="display: inline-block; padding: 14px 30px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 16px; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">Reset Password</a>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>

							<p style="margin-top: 0; margin-bottom: 20px;">
								If you did not request a password reset, you can safely ignore this email. Your password will remain unchanged and your account is secure.
							</p>

							<p style="margin-top: 0; margin-bottom: 0;">
								Best regards,<br>
								<span style="font-weight: 600; color: #111827;">The Ripal Design Team</span>
							</p>
						</td>
					</tr>
                    
					<!-- Fallback Link Section -->
					<tr>
						<td class="content-padding" style="padding: 0 40px 30px 40px; background-color: #ffffff;">
							<hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 0 0 20px 0;">
							<p style="margin: 0; font-size: 13px; color: #6b7280; line-height: 1.5;">
								If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:
								<br><br>
								<a href="{{reset_link}}" style="color: #731209; text-decoration: underline; word-break: break-all;">{{reset_link}}</a>
							</p>
						</td>
					</tr>

				</table>

				<!-- Footer -->
				<table border="0" cellpadding="0" cellspacing="0" width="600" class="email-container">
					<tr>
						<td align="center" style="padding: 20px 0 0 0; font-size: 13px; color: #9ca3af; line-height: 1.5;">
							<p style="margin: 0 0 10px 0;">
								&copy; 2026 Ripal Design. All rights reserved.
							</p>
							<p style="margin: 0;">
								<a href="#" style="color: #9ca3af; text-decoration: underline;">Privacy Policy</a> | 
								<a href="#" style="color: #9ca3af; text-decoration: underline;">Contact Support</a>
							</p>
						</td>
					</tr>
				</table>

			</td>
		</tr>
	</table>
</body>
</html>
HTML;

	// Strictly enforce the approved forgot-password HTML template.
	$mail->Body = $renderTemplate($defaultHtml, [
		'{{reset_link}}' => htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8'),
		'{{user_name}}' => htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'),
		'[User Name]' => htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'),
	]);

	$mail->AltBody = '';
	try {
		$mail->send();
		redirect_with_flash_cookie($ct('flash_sent_success', 'Reset link sent. Please check your email.'), 'success');
	} catch (\Throwable $e) {
		if (function_exists('app_log')) {
			app_log('error', 'PHPMailer reset email send failed', ['mailer_error' => $mail->ErrorInfo, 'exception' => $e->getMessage(), 'email' => $email]);
		}
		redirect_with_flash_cookie($ct('flash_sent_failed', 'Failed to send reset link.'), 'error');
	}
} else {
	redirect_with_message($ct('email_not_found', 'Email not found.'));
}
