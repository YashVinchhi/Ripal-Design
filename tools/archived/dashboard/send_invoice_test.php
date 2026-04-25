<?php
// dashboard/send_invoice_test.php
// Usage (from workspace root): php dashboard/send_invoice_test.php recipient@example.com
$to = $argv[1] ?? 'behappywithyash@gmail.com';
chdir(__DIR__ . '/../../'); // ensure relative includes work from repo root

// Load Composer autoload if present
$composer = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($composer)) require_once $composer;

// If PHPMailer not available via Composer, load bundled src files in dependency order
if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    $localSrc = __DIR__ . '/../../src';
    $parts = ['Exception.php','OAuthTokenProvider.php','OAuth.php','POP3.php','SMTP.php','PHPMailer.php','DSNConfigurator.php'];
    foreach ($parts as $p) {
        $f = $localSrc . '/' . $p;
        if (file_exists($f)) require_once $f;
    }
}

// Prefer the app mailer wrapper when available; fall back to public/mailer.php and
// finally to manual PHPMailer construction configured via configure_mailer().
require_once __DIR__ . '/../../app/Shared/Mail/mail_helper.php';

$mail = null;
$pub = __DIR__ . '/../../public/mailer.php';
if (file_exists($pub)) {
    $maybe = require $pub;
    if ($maybe instanceof \PHPMailer\PHPMailer\PHPMailer) {
        $mail = $maybe;
    }
}

if (!($mail instanceof \PHPMailer\PHPMailer\PHPMailer) && function_exists('app_mailer')) {
    $mail = app_mailer();
}

if (!($mail instanceof \PHPMailer\PHPMailer\PHPMailer)) {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    if (!@configure_mailer($mail)) {
        fwrite(STDERR, "No SMTP configuration found. Set MAIL_HOST, MAIL_USERNAME and MAIL_PASSWORD in environment or .env\n");
        exit(1);
    }
    $from = getenv('MAIL_FROM_ADDRESS') ?: getenv('MAIL_FROM') ?: (getenv('SMTP_FROM') ?: 'no-reply@ripaldesign.studio');
    $fromName = getenv('MAIL_FROM_NAME') ?: 'Ripal Design (Test)';
    try { $mail->setFrom($from, $fromName); } catch (Throwable $e) { }
}

// Prepare debug output
$mail->SMTPDebug = 3;
$mail->Debugoutput = function($str, $level) {
    echo trim($str) . PHP_EOL;
};

// Clear any recipients and set test recipient
try {
    if (method_exists($mail, 'clearAllRecipients')) {
        $mail->clearAllRecipients();
    } else {
        if (method_exists($mail, 'clearAddresses')) $mail->clearAddresses();
        if (method_exists($mail, 'clearCCs')) $mail->clearCCs();
        if (method_exists($mail, 'clearBCCs')) $mail->clearBCCs();
    }

    $mail->addAddress($to);
    $mail->Subject = 'TEST: Invoice send at ' . date('c');
    $mail->Body = 'This is an automated test email (invoice send) to ' . $to;
    echo "Sending to: $to\n";

    if (function_exists('app_send_mail')) {
        $ok = app_send_mail($mail, ['mail_type' => 'archived_invoice_test', 'to' => $to]);
    } else {
        $ok = $mail->send();
    }

    if ($ok) {
        echo "SENT\n";
    } else {
        echo "FAILED: " . ($mail->ErrorInfo ?? 'unknown') . PHP_EOL;
    }
} catch (Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . PHP_EOL;
}
