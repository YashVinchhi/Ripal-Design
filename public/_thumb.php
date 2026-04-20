<?php
/**
 * Thumb endpoint: /public/_thumb.php?src=/uploads/...&w=400
 * Serves resized images with caching headers.
 */
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';
require_once __DIR__ . '/../includes/image_resize.php';

$src = $_GET['src'] ?? '';
$w = isset($_GET['w']) ? (int)$_GET['w'] : 400;
if ($src === '' || $w <= 0) {
    http_response_code(400);
    echo 'Bad Request';
    exit;
}

// Normalize and prevent directory traversal
$src = preg_replace('#^/*#', '', $src);
if (strpos($src, '..') !== false) {
    http_response_code(400);
    echo 'Invalid source';
    exit;
}

$thumb = ensure_thumb_cached($src, $w);
if (!$thumb || !file_exists($thumb)) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$mime = mime_content_type($thumb) ?: 'image/jpeg';
header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=2592000'); // 30 days
readfile($thumb);
exit;
