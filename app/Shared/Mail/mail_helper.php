<?php
/**
 * Minimal mail transport configurator.
 *
 * Loads the bundled PHPMailer classes directly so production mail flows do not
 * depend on Composer autoload files being present on the server.
 */

if (!defined('PROJECT_ROOT')) {
    $bootstrapPath = dirname(__DIR__, 2) . '/Core/Bootstrap/init.php';
    if (file_exists($bootstrapPath)) {
        require_once $bootstrapPath;
    }
}

$phpMailerBase = rtrim((string)(defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 3)), '/\\') . '/src';
foreach (['Exception.php', 'OAuthTokenProvider.php', 'SMTP.php', 'PHPMailer.php'] as $phpMailerFile) {
    $phpMailerPath = $phpMailerBase . '/' . $phpMailerFile;
    if (file_exists($phpMailerPath)) {
        require_once $phpMailerPath;
    }
}

if (!function_exists('configure_mailer')) {
    function configure_mailer($mail): bool
    {
        $smtpHost = getenv('MAIL_HOST') ?: getenv('SMTP_HOST');
        if (!$smtpHost) {
            return false;
        }

        $smtpUser = getenv('MAIL_USERNAME') ?: getenv('SMTP_USER') ?: '';
        $smtpPass = getenv('MAIL_PASSWORD') ?: getenv('SMTP_PASS') ?: '';
        // Gmail app passwords are often stored with spaces for readability.
        // Strip all whitespace so the SMTP client receives the actual token.
        $smtpPass = preg_replace('/\s+/', '', (string)$smtpPass);

        $mail->isSMTP();
        $mail->Host = trim((string)$smtpHost);
        $mail->SMTPAuth = true;
        $mail->Username = trim((string)$smtpUser);
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = getenv('MAIL_ENCRYPTION') ?: 'tls';
        $mail->Port = (int)(getenv('MAIL_PORT') ?: 587);

        return true;
    }
}
