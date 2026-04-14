<?php
require_once __DIR__ . '/vendor/autoload.php';
$files = ['src/Exception.php','src/OAuthTokenProvider.php','src/OAuth.php','src/POP3.php','src/SMTP.php','src/PHPMailer.php','src/DSNConfigurator.php'];
foreach ($files as $f) {
    $p = __DIR__ . '/' . $f;
    if (file_exists($p)) require_once $p;
}
if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    echo "PHPMailer loaded\n";
} else {
    echo "PHPMailer NOT loaded\n";
}
