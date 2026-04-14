<?php

declare(strict_types=1);

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$decodedPath = rawurldecode($uriPath);
$normalizedPath = trim($decodedPath, '/');
$publicRoot = realpath(__DIR__ . '/../public');

if ($publicRoot === false) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Public directory not found.';
    exit;
}

if ($normalizedPath === '') {
    require $publicRoot . DIRECTORY_SEPARATOR . 'index.php';
    exit;
}

$target = realpath($publicRoot . DIRECTORY_SEPARATOR . str_replace(['..\\', '../'], '', $normalizedPath));

if ($target !== false && str_starts_with($target, $publicRoot . DIRECTORY_SEPARATOR) && is_file($target)) {
    $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));

    if ($ext === 'php') {
        require $target;
        exit;
    }

    $mime = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'map' => 'application/json; charset=utf-8',
    ][$ext] ?? 'application/octet-stream';

    header('Content-Type: ' . $mime);
    readfile($target);
    exit;
}

$fallbackPhp = $publicRoot . DIRECTORY_SEPARATOR . $normalizedPath . '.php';
if (is_file($fallbackPhp)) {
    require $fallbackPhp;
    exit;
}

http_response_code(404);
$notFound = $publicRoot . DIRECTORY_SEPARATOR . '404.php';
if (is_file($notFound)) {
    require $notFound;
    exit;
}

header('Content-Type: text/plain; charset=utf-8');
echo '404 Not Found';
