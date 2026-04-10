<?php

require_once __DIR__ . '/../includes/init.php';
require_login();

@ini_set('display_errors', '0');

if (!function_exists('file_stream_resolve_mime')) {
    function file_stream_resolve_mime($absolutePath) {
        $ext = strtolower((string)pathinfo((string)$absolutePath, PATHINFO_EXTENSION));
        $map = [
            'glb' => 'model/gltf-binary',
            'gltf' => 'model/gltf+json',
            'obj' => 'model/obj',
            'dwg' => 'image/vnd.dwg',
            'skp' => 'application/vnd.sketchup.skp',
            'webp' => 'image/webp',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
        ];
        if (isset($map[$ext])) {
            return $map[$ext];
        }
        $detected = function_exists('mime_content_type') ? (string)mime_content_type($absolutePath) : '';
        return $detected !== '' ? $detected : 'application/octet-stream';
    }
}

$kind = strtolower(trim((string)($_GET['kind'] ?? 'file')));
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0 || !in_array($kind, ['file', 'drawing'], true)) {
    http_response_code(400);
    echo 'Invalid request.';
    exit;
}

$db = get_db();
if (!($db instanceof PDO)) {
    http_response_code(500);
    echo 'Database unavailable.';
    exit;
}

try {
    if ($kind === 'drawing') {
        $stmt = $db->prepare('SELECT name, file_path FROM project_drawings WHERE id = ? LIMIT 1');
    } else {
        if (function_exists('db_column_exists') && db_column_exists('project_files', 'storage_path')) {
            $stmt = $db->prepare('SELECT name, COALESCE(NULLIF(storage_path,\'\'), file_path) AS file_path FROM project_files WHERE id = ? LIMIT 1');
        } else {
            $stmt = $db->prepare('SELECT name, file_path FROM project_files WHERE id = ? LIMIT 1');
        }
    }
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    http_response_code(500);
    echo 'Unable to load file metadata.';
    exit;
}

if (!$row) {
    http_response_code(404);
    echo 'File not found.';
    exit;
}

$storedPath = trim((string)($row['file_path'] ?? ''));
if ($storedPath === '') {
    http_response_code(404);
    echo 'File path missing.';
    exit;
}

$normalized = str_replace('\\', '/', $storedPath);
$uploadsPos = strpos($normalized, '/uploads/');
if ($uploadsPos === false && strpos($normalized, 'uploads/') === 0) {
    $uploadsPos = 0;
}

if ($uploadsPos === false) {
    http_response_code(404);
    echo 'Unsupported stored path.';
    exit;
}

$relative = $uploadsPos === 0 ? $normalized : substr($normalized, $uploadsPos + 1);
$relative = ltrim($relative, '/');
$absolute = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);

if (!is_file($absolute) || !is_readable($absolute)) {
    http_response_code(404);
    echo 'Requested file was not found on disk.';
    exit;
}

$filename = basename((string)($row['name'] ?? 'file'));

while (ob_get_level() > 0) {
    @ob_end_clean();
}

$mime = file_stream_resolve_mime($absolute);

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . (string)filesize($absolute));
header('Content-Disposition: inline; filename="' . str_replace('"', '', $filename) . '"');
$handle = @fopen($absolute, 'rb');
if ($handle === false) {
    http_response_code(500);
    exit;
}
@fpassthru($handle);
@fclose($handle);
exit;
