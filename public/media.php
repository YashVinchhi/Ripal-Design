<?php
// media.php?f=relative/path.jpg
// Serves files from the Content directory securely
$baseDir = 'D:\\WP\\www\\Ripal Design\\Content';

if (!isset($_GET['f'])) {
    http_response_code(400);
    exit('Missing file parameter');
}

$rel = $_GET['f'];
// Normalize and prevent traversal
$rel = str_replace(['..', "\\\0"], '', $rel);
$rel = str_replace('\\', '/', $rel);

$full = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);

// Resolve realpath and ensure it stays inside base directory
$real = realpath($full);
if ($real === false || stripos($real, realpath($baseDir)) !== 0) {
    http_response_code(404);
    exit('File not found');
}

if (!is_file($real) || !is_readable($real)) {
    http_response_code(404);
    exit('File not found');
}

$mime = mime_content_type($real) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($real));
// Optional caching
header('Cache-Control: public, max-age=86400');
readfile($real);
exit;
