<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
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
        $detected = '';
        if (function_exists('finfo_open') && function_exists('finfo_file')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = (string)@finfo_file($finfo, $absolutePath);
                @finfo_close($finfo);
            }
        }
        if ($detected === '' && function_exists('mime_content_type')) {
            $detected = (string)mime_content_type($absolutePath);
        }
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
        $stmt = $db->prepare('SELECT project_id, name, file_path FROM project_drawings WHERE id = ? LIMIT 1');
    } else {
        if (function_exists('db_column_exists') && db_column_exists('project_files', 'storage_path')) {
            $stmt = $db->prepare('SELECT project_id, name, COALESCE(NULLIF(storage_path,\'\'), file_path) AS file_path FROM project_files WHERE id = ? LIMIT 1');
        } else {
            $stmt = $db->prepare('SELECT project_id, name, file_path FROM project_files WHERE id = ? LIMIT 1');
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

$projectId = (int)($row['project_id'] ?? 0);
if ($projectId <= 0 || !function_exists('auth_user_can_access_project') || !auth_user_can_access_project($projectId, current_user_id(), (string)(current_user()['role'] ?? ''))) {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

$storedPath = trim((string)($row['file_path'] ?? ''));
if ($storedPath === '') {
    http_response_code(404);
    echo 'File path missing.';
    exit;
}

$normalized = str_replace('\\', '/', $storedPath);
$relative = ltrim($normalized, '/');
if (strpos($relative, 'uploads/') === 0) {
    $relative = substr($relative, strlen('uploads/'));
}

$absolute = rtrim((string)UPLOAD_STORAGE_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
if (!is_file($absolute) || !is_readable($absolute)) {
    $legacyRelative = ltrim($normalized, '/');
    $legacyAbsolute = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $legacyRelative);
    if (is_file($legacyAbsolute) && is_readable($legacyAbsolute)) {
        $absolute = $legacyAbsolute;
    }
}

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
