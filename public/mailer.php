<?php
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../app/Shared/Mail/mail_helper.php';

$mail = null;
if (function_exists('app_mailer')) {
	$mail = app_mailer();
}

if (!($mail instanceof PHPMailer)) {
	$mail = new PHPMailer(true);
	$mail->isHTML(true);
	$mail->CharSet = 'UTF-8';
	@configure_mailer($mail);
}

return $mail;
