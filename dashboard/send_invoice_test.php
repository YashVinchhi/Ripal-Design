<?php
// dashboard/send_invoice_test.php
// Usage (from workspace root): php dashboard/send_invoice_test.php recipient@example.com
$to = $argv[1] ?? 'behappywithyash@gmail.com';
chdir(__DIR__ . '/../'); // ensure relative includes work from repo root

// Load Composer autoload if present
$composer = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composer)) require_once $composer;

// If PHPMailer not available via Composer, load bundled src files in dependency order
if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    $localSrc = __DIR__ . '/../src';
    $parts = ['Exception.php','OAuthTokenProvider.php','OAuth.php','POP3.php','SMTP.php','PHPMailer.php','DSNConfigurator.php'];
    foreach ($parts as $p) {
        $f = $localSrc . '/' . $p;
        if (file_exists($f)) require_once $f;
    }
}

// Try to get a pre-configured PHPMailer from public/mailer.php if present
$mail = null;
$pub = __DIR__ . '/../public/mailer.php';
if (file_exists($pub)) {
    // public/mailer.php returns a PHPMailer instance
    $mail = require $pub;
}

if (!($mail instanceof PHPMailer\PHPMailer\PHPMailer)) {
    // Build a minimal PHPMailer instance
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    // Try environment SMTP or fallback to gmail credentials seen in public/mailer.php
    $envHost = getenv('MAIL_HOST') ?: getenv('SMTP_HOST');
    if ($envHost) {
        $mail->isSMTP();
        $mail->Host = $envHost;
        $mail->SMTPAuth = true;
        $mail->Username = getenv('MAIL_USERNAME') ?: getenv('SMTP_USER');
        $mail->Password = getenv('MAIL_PASSWORD') ?: getenv('SMTP_PASS');
        $mail->SMTPSecure = getenv('MAIL_ENCRYPTION') ?: PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('MAIL_PORT') ?: 587;
    } else {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'yashhvinchhi@gmail.com';
        $mail->Password = 'odoc sctf jtuf ejvv';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
    }
    $mail->setFrom('yashhvinchhi@gmail.com', 'Ripal Design (Test)');
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
