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

// Try to get a pre-configured PHPMailer from public/mailer.php if present
$mail = null;
$pub = __DIR__ . '/../../public/mailer.php';
if (file_exists($pub)) {
    // public/mailer.php returns a PHPMailer instance
    $mail = require $pub;
}

if (!($mail instanceof PHPMailer\\PHPMailer\\PHPMailer)) {
    // Build a minimal PHPMailer instance and configure SMTP from environment
    $mail = new PHPMailer\\PHPMailer\\PHPMailer(true);
    require_once __DIR__ . '/../../includes/mail_helper.php';
    if (!@configure_mailer($mail)) {
        fwrite(STDERR, "No SMTP configuration found. Set MAIL_HOST, MAIL_USERNAME and MAIL_PASSWORD in environment or .env\n");
        exit(1);
    }
    $from = getenv('MAIL_FROM') ?: (getenv('SMTP_FROM') ?: 'no-reply@ripaldesign.in');
    $fromName = getenv('MAIL_FROM_NAME') ?: 'Ripal Design (Test)';
    $mail->setFrom($from, $fromName);
}

// Prepare debug output
$mail->SMTPDebug = 3;
$mail->Debugoutput = function($str, $level) {
    echo trim($str) . PHP_EOL;
};

// Clear any recipients and set test recipient
try {
    $mail->clearAllRecipients();
    $mail->addAddress($to);
    $mail->Subject = 'TEST: Invoice send at ' . date('c');
    $mail->Body = 'This is an automated test email (invoice send) to ' . $to;
    echo "Sending to: $to\n";
    $ok = $mail->send();
    if ($ok) {
        echo "SENT\n";
    } else {
        echo "FAILED: " . ($mail->ErrorInfo ?? 'unknown') . PHP_EOL;
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . PHP_EOL;
}
