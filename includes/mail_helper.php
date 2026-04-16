<?php

require_once dirname(__DIR__) . '/app/Shared/Mail/mail_helper.php';
return;
/**
 * Minimal mail transport configurator.
 *
 * Reads SMTP configuration from environment variables and applies them to
 * a PHPMailer instance. Returns true if SMTP transport was configured,
 * false if no SMTP host was found.
 */
if (!function_exists('configure_mailer')) {
    function configure_mailer($mail): bool
    {
        $smtpHost = getenv('MAIL_HOST') ?: getenv('SMTP_HOST');
        if (!$smtpHost) {
            return false;
        }

        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = getenv('MAIL_USERNAME') ?: getenv('SMTP_USER') ?: '';
        $mail->Password = getenv('MAIL_PASSWORD') ?: getenv('SMTP_PASS') ?: '';
        $mail->SMTPSecure = getenv('MAIL_ENCRYPTION') ?: 'tls';
        $mail->Port = (int)(getenv('MAIL_PORT') ?: 587);

        return true;
    }
}
