<?php
$mailToken = isset($token) ? (string)$token : '';
$link = 'https://localhost/ripal-design/public/reset_password.php?token=' . urlencode($mailToken);
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$ct = static function ($key, $default = '') {
	if (function_exists('public_content_get')) {
		return public_content_get('mailer', $key, $default);
	}
	return (string)$default;
};

$renderTemplate = static function ($template, array $vars = []) {
	return strtr((string)$template, $vars);
};

$mail = new PHPMailer(true);

$mail->isSMTP();
$mail->isHTML(true);
$mail->Body = $renderTemplate(
	function_exists('public_content_get_html')
		? public_content_get_html('mailer', 'reset_body_html', '<h3>Password reset request</h3><p>Click link below to reset your password</p><a href="{{reset_link}}">here</a>')
		: '<h3>Password reset request</h3><p>Click link below to reset your password</p><a href="{{reset_link}}">here</a>',
	['{{reset_link}}' => htmlspecialchars($link, ENT_QUOTES, 'UTF-8')]
);
$mail->AltBody = $renderTemplate(
	$ct('reset_body_text', 'Reset link: {{reset_link}}'),
	['{{reset_link}}' => $link]
);
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username ='dudhaiyarachit45@gmail.com';
$mail->Password = 'mvwy brdi luvt wecd';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
$mail->setFrom('dudhaiyarachit45@gmail.com', $ct('reset_from_name', 'Reset Password'));
if (!empty($email)) {
	$mail->addAddress($email);
}

$mail->Subject = $ct('reset_subject', 'Password Reset Request');
// if (!$mail->send()) {
//     echo "Error: " . $mail->ErrorInfo;
// } else {
//     echo "Success!";
// }

return $mail;
?>