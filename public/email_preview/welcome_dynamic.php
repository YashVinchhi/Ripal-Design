<?php
// Server-side dynamic preview for the welcome template (verifies template used for sending)
$templatePath = __DIR__ . '/welcome_preview.html';
if (!is_readable($templatePath)) {
    echo "Template not found: " . htmlspecialchars($templatePath);
    exit;
}
$tpl = file_get_contents($templatePath);
$marker = '<!-- Begin rendered email -->';
if (false !== ($pos = strpos($tpl, $marker))) {
    $emailHtml = substr($tpl, $pos + strlen($marker));
} else {
    $emailHtml = $tpl;
}
$vars = [
    '{{first_name}}' => 'Jane Doe',
    '{{full_name}}' => 'Jane Doe',
    '{{login_link}}' => 'http://localhost/login.php',
];
// Simple replacement using strtr semantics
echo strtr($emailHtml, $vars);
