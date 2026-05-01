<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';

$relative = rawurldecode((string)($_GET['path'] ?? ''));
$relative = str_replace('\\', '/', $relative);
$relative = ltrim($relative, '/');

if ($relative === '' || strpos($relative, 'uploads/content/') !== 0) {
    http_response_code(404);
    exit;
}

$absolute = rtrim((string)UPLOAD_STORAGE_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
$realFile = realpath($absolute);
$managedRoot = realpath(rtrim((string)UPLOAD_STORAGE_ROOT, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content');

if ($realFile === false || $managedRoot === false || strpos($realFile, $managedRoot . DIRECTORY_SEPARATOR) !== 0 || !is_file($realFile)) {
    http_response_code(404);
    exit;
}

$mime = 'application/octet-stream';
if (function_exists('finfo_open') && function_exists('finfo_file')) {
    $finfo = @finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo !== false) {
        $detected = (string)@finfo_file($finfo, $realFile);
        @finfo_close($finfo);
        if ($detected !== '') {
            $mime = $detected;
        }
    }
}
if ($mime === 'application/octet-stream' && function_exists('mime_content_type')) {
    $detected = (string)@mime_content_type($realFile);
    if ($detected !== '') {
        $mime = $detected;
    }
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($realFile));
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($realFile);
