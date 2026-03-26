<?php
$link = "https://localhost/ripal-design/public/reset_password.php?token=$token";
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$mail = new PHPMailer(true);

$mail->isSMTP();
$mail->Body="<h3>password reset request</h3><p>click link below to reset your password</p><a href='$link'>here</a>";
$mail->AltBody="Reset link: $link";
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username ='dudhaiyarachit45@gmail.com';
$mail->Password = 'mvwy brdi luvt wecd';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
$mail->setFrom('dudhaiyarachit45@gmail.com', 'Reset Password');
$mail->addAddress($email);

$mail->Subject = 'Password Reset Request';
// if (!$mail->send()) {
//     echo "Error: " . $mail->ErrorInfo;
// } else {
//     echo "Success!";
// }

return $mail;
?>