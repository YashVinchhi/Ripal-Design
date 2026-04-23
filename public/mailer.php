<?php
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../app/Shared/Mail/mail_helper.php';

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->isHTML(true);
$mail->CharSet = 'UTF-8';
// configure_mailer will apply SMTP settings when MAIL_HOST is provided in env;
// otherwise PHPMailer will use the default mail() transport.
@configure_mailer($mail);

return $mail;
