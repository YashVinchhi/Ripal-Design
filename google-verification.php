<?php
/**
 * Simple endpoint to surface Google Search Console verification meta tag.
 * Usage: include URL in GSC as HTML verification file (page must be publicly accessible)
 * Replace the placeholder value in config or set via environment.
 */
require_once __DIR__ . '/app/Core/Bootstrap/init.php';

$verification = defined('GOOGLE_SITE_VERIFICATION') ? GOOGLE_SITE_VERIFICATION : '';
// Optionally allow a runtime override via config file
if (file_exists(PROJECT_ROOT . '/config/google_verification.php')) {
    $cfg = include PROJECT_ROOT . '/config/google_verification.php';
    if (!empty($cfg['code'])) {
        $verification = $cfg['code'];
    }
}

header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Google Verification</title>
    <?php if ($verification !== ''): ?>
        <meta name="google-site-verification" content="<?php echo htmlspecialchars($verification, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>">
    <?php else: ?>
        <!-- Placeholder meta: replace with your verification code. -->
        <!-- Example: <meta name="google-site-verification" content="your_google_code_here"> -->
    <?php endif; ?>
</head>
<body>
    <h1>Google Site Verification</h1>
    <p>This page is used to verify the site with Google Search Console. Add your verification code to <code>config/google_verification.php</code> or define <code>GOOGLE_SITE_VERIFICATION</code> in your environment.</p>
</body>
</html>
