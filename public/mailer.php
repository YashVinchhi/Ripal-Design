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
$mail->Username = $email;
$mail->Password = 'mvwy brdi luvt wecd';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
$mail->setFrom('your_email@gmail.com', 'Test');
$mail->addAddress('receiver_email@gmail.com');

$mail->Subject = 'Password Reset Request';
$mail->Body = '<h3>Password Reset</h3>
    <p>Click the button below to reset your password:</p>
    <a href="https://localhost/ripal-design/public/reset_password.php?token=' . urlencode($token) . '" style="display:inline-block; padding:10px 20px;
        background-color:blue;
        color:white;
        text-decoration:none;
        border-radius:5px;">Reset Password</a>
    
    <p>If you did not request this, ignore this email.</p>';

if (!$mail->send()) {
    echo "Error: " . $mail->ErrorInfo;
} else {
    echo "Success!";
}

return $mail;
?>