<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';

$projectSlug = trim((string)($_GET['project_slug'] ?? ''));
$modelSlug = trim((string)($_GET['model_slug'] ?? ''));

$db = get_db();
if (!($db instanceof PDO) || $projectSlug === '' || $modelSlug === '' || !function_exists('db_table_exists') || !db_table_exists('project_3d_models')) {
    http_response_code(404);
    echo 'Model not found.';
    exit;
}

try {
    $projectSql = 'SELECT id FROM projects WHERE (slug = ? OR id = ?)' . projects_soft_delete_sql('projects', ' AND ') . ' LIMIT 1';
    $projectStmt = $db->prepare($projectSql);
    $projectStmt->execute([$projectSlug, (int)$projectSlug]);
    $project = $projectStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$project) {
        http_response_code(404);
        echo 'Project not found.';
        exit;
    }

    $stmt = $db->prepare('SELECT title, original_name, stored_path FROM project_3d_models WHERE project_id = ? AND slug = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([(int)$project['id'], $modelSlug]);
    $model = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$model) {
        http_response_code(404);
        echo 'Model not found.';
        exit;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Unable to load model.';
    exit;
}

$absolute = (string)($model['stored_path'] ?? '');
if (!is_file($absolute) || !is_readable($absolute)) {
    http_response_code(404);
    echo 'Model file missing.';
    exit;
}

while (ob_get_level() > 0) {
    @ob_end_clean();
}

$downloadName = basename((string)($model['original_name'] ?: ($model['title'] . '.glb')));
if (!preg_match('/\.glb$/i', $downloadName)) {
    $downloadName .= '.glb';
}

header('Content-Type: model/gltf-binary');
header('X-Content-Type-Options: nosniff');
header('Accept-Ranges: bytes');
header('Cache-Control: public, max-age=86400');
header('Content-Length: ' . (string)filesize($absolute));
header('Content-Disposition: inline; filename="' . str_replace('"', '', $downloadName) . '"');

$handle = @fopen($absolute, 'rb');
if ($handle === false) {
    http_response_code(500);
    exit;
}
@fpassthru($handle);
@fclose($handle);
exit;
