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

if (!function_exists('app_mailer')) {
    /**
     * Return a configured PHPMailer instance or null when unavailable.
     * Uses `configure_mailer()` to apply SMTP settings when present.
     *
     * @return \PHPMailer\PHPMailer\PHPMailer|null
     */
    function app_mailer()
    {
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return null;
        }

        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            @configure_mailer($mail);
            return $mail;
        } catch (Throwable $e) {
            if (function_exists('app_log')) {
                app_log('error', 'app_mailer initialization failed', ['exception' => $e->getMessage()]);
            }
            return null;
        }
    }
}

if (!function_exists('app_send_mail')) {
    /**
     * Send a PHPMailer instance and log outcome (without logging message body).
     * Returns true on success, false on failure.
     *
     * @param object $mail PHPMailer instance
     * @param array $context Optional context to include in logs
     * @return bool
     */
    function app_send_mail($mail, array $context = []): bool
    {
        if (!is_object($mail)) {
            return false;
        }

        try {
            $ok = $mail->send();
            if (function_exists('app_log')) {
                $to = [];
                if (method_exists($mail, 'getAllRecipientAddresses')) {
                    $to = $mail->getAllRecipientAddresses();
                } elseif (method_exists($mail, 'getToAddresses')) {
                    $to = $mail->getToAddresses();
                }
                app_log('info', 'Email sent', array_merge($context, ['to' => $to, 'subject' => $mail->Subject ?? '']));
            }
            return (bool)$ok;
        } catch (Throwable $e) {
            if (function_exists('app_log')) {
                $to = [];
                if (method_exists($mail, 'getAllRecipientAddresses')) {
                    $to = $mail->getAllRecipientAddresses();
                } elseif (method_exists($mail, 'getToAddresses')) {
                    $to = $mail->getToAddresses();
                }
                app_log('error', 'Email send failed', array_merge($context, ['exception' => $e->getMessage(), 'to' => $to, 'subject' => $mail->Subject ?? '', 'mailer_error' => $mail->ErrorInfo ?? '']));
            }
            return false;
        }
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

        // If no SMTP host configured, do not configure SMTP transport.
        if (!$smtpHost) {
            if (function_exists('app_log')) {
                app_log('warning', 'SMTP not configured: MAIL_HOST / SMTP_HOST not set', []);
            }
            return false;
        }

        $smtpUser = getenv('MAIL_USERNAME') ?: getenv('SMTP_USER') ?: '';
        $smtpPass = getenv('MAIL_PASSWORD') ?: getenv('SMTP_PASS') ?: '';
        $smtpPass = preg_replace('/\s+/', '', (string)$smtpPass);

        $mail->isSMTP();
        $mail->Host = trim((string)$smtpHost);
        $mail->SMTPAuth = true;
        $mail->Username = trim((string)$smtpUser);
        $mail->Password = $smtpPass;
        $mail->SMTPSecure = getenv('MAIL_ENCRYPTION') ?: (getenv('SMTP_SECURE') ?: 'tls');
        $mail->Port = (int)(getenv('MAIL_PORT') ?: getenv('SMTP_PORT') ?: 587);

        // Optional: enable debug output when explicitly requested via env
        $debug = getenv('MAIL_DEBUG') ?: getenv('SMTP_DEBUG');
        if ($debug !== false && $debug !== null && (int)$debug > 0) {
            $mail->SMTPDebug = (int)$debug;
        }

        // Set a sensible default "From" if not later overridden by callers.
        $defaultFrom = getenv('MAIL_FROM_ADDRESS') ?: getenv('MAIL_FROM') ?: $smtpUser;
        $defaultFromName = getenv('MAIL_FROM_NAME') ?: getenv('MAIL_FROM_NAME') ?: '';
        if (!empty($defaultFrom)) {
            try {
                if (method_exists($mail, 'setFrom')) {
                    $mail->setFrom($defaultFrom, $defaultFromName ?: '');
                }
            } catch (Throwable $e) {
                // swallow - mailers may setFrom later
            }
        }

        // Add a default Reply-To if provided
        $replyTo = getenv('MAIL_REPLY_TO') ?: getenv('REPLY_TO_ADDRESS');
        if (!empty($replyTo) && method_exists($mail, 'addReplyTo')) {
            try {
                $mail->addReplyTo($replyTo);
            } catch (Throwable $e) {
                // ignore
            }
        }

        return true;
    }
}
