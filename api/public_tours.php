<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';

header('Content-Type: application/json; charset=utf-8');

$db = get_db();
if (!($db instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection unavailable.']);
    exit;
}

if (!function_exists('public_tours_json')) {
    function public_tours_json(int $status, array $payload): void
    {
        http_response_code($status);
        echo json_encode($payload);
        exit;
    }
}

if (!function_exists('public_tours_media_url')) {
    function public_tours_media_url(string $rawPath): string
    {
        $path = trim($rawPath);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^(https?:)?//#i', $path)) {
            return $path;
        }

        $normalized = str_replace('\\', '/', $path);
        $normalized = ltrim($normalized, '/');

        if (strpos($normalized, 'uploads/') === 0) {
            $publicRelative = $normalized;
            $publicAbsolute = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $publicRelative);
            if (!is_file($publicAbsolute)) {
                $relativeAfterUploads = substr($normalized, strlen('uploads/'));
                $privateAbs = rtrim((string)UPLOAD_STORAGE_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeAfterUploads);
                if (is_file($privateAbs)) {
                    $publicDir = dirname($publicAbsolute);
                    if (!is_dir($publicDir)) {
                        @mkdir($publicDir, 0775, true);
                    }
                    @copy($privateAbs, $publicAbsolute);
                }
            }
            return rtrim((string)BASE_PATH, '/') . '/' . $publicRelative;
        }

        return rtrim((string)BASE_PATH, '/') . '/' . ltrim($normalized, '/');
    }
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'GET') {
    public_tours_json(405, ['error' => 'Method not allowed.']);
}

if (!function_exists('db_table_exists')
    || !db_table_exists('project_tours')
    || !db_table_exists('tour_scenes')
    || !db_table_exists('tour_hotspots')) {
    public_tours_json(503, ['error' => 'Tours schema is unavailable.']);
}

$tourId = (int)($_GET['tour_id'] ?? 0);
$projectId = (int)($_GET['project_id'] ?? 0);
if ($tourId <= 0 && $projectId <= 0) {
    public_tours_json(422, ['error' => 'tour_id or project_id is required.']);
}

$projectToursHasStartScene = function_exists('db_column_exists') ? db_column_exists('project_tours', 'start_scene_id') : false;
$hasProjectFilesMediaType = function_exists('db_column_exists') ? db_column_exists('project_files', 'media_type') : false;
$startSceneSelect = $projectToursHasStartScene ? 't.start_scene_id AS start_scene_id' : 'NULL AS start_scene_id';

try {
    if ($tourId > 0) {
        $sql = 'SELECT t.id, t.project_id, t.title, t.description, ' . $startSceneSelect . ', p.name AS project_name '
            . 'FROM project_tours t '
            . 'LEFT JOIN projects p ON p.id = t.project_id '
            . 'WHERE t.id = ? AND t.is_active = 1 '
            . 'LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute([$tourId]);
    } else {
        $sql = 'SELECT t.id, t.project_id, t.title, t.description, ' . $startSceneSelect . ', p.name AS project_name '
            . 'FROM project_tours t '
            . 'LEFT JOIN projects p ON p.id = t.project_id '
            . 'WHERE t.project_id = ? AND t.is_active = 1 '
            . 'ORDER BY t.updated_at DESC, t.id DESC '
            . 'LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute([$projectId]);
    }

    $tour = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$tour) {
        public_tours_json(404, ['error' => 'Tour not found.']);
    }

    $mediaTypeSelect = $hasProjectFilesMediaType ? 'pf.media_type AS media_type' : 'NULL AS media_type';
    $sceneSql = 'SELECT s.id, s.tour_id, s.project_file_id, s.name, s.initial_yaw, s.initial_pitch, s.initial_hfov, s.sort_order, '
        . 'pf.name AS file_name, pf.file_path AS file_path, ' . $mediaTypeSelect . ' '
        . 'FROM tour_scenes s '
        . 'LEFT JOIN project_files pf ON pf.id = s.project_file_id '
        . 'WHERE s.tour_id = ? AND s.is_active = 1 '
        . 'ORDER BY s.sort_order ASC, s.id ASC';

    $sceneStmt = $db->prepare($sceneSql);
    $sceneStmt->execute([(int)$tour['id']]);
    $sceneRows = $sceneStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $sceneIds = [];
    $scenes = [];
    foreach ($sceneRows as $sceneRow) {
        $sceneId = (int)($sceneRow['id'] ?? 0);
        if ($sceneId <= 0) {
            continue;
        }

        $sceneIds[] = $sceneId;
        $scenes[$sceneId] = [
            'id' => $sceneId,
            'name' => (string)($sceneRow['name'] ?? ('Scene #' . $sceneId)),
            'project_file_id' => (int)($sceneRow['project_file_id'] ?? 0),
            'image_url' => public_tours_media_url((string)($sceneRow['file_path'] ?? '')),
            'initial_yaw' => (float)($sceneRow['initial_yaw'] ?? 0),
            'initial_pitch' => (float)($sceneRow['initial_pitch'] ?? 0),
            'initial_hfov' => (float)($sceneRow['initial_hfov'] ?? 100),
            'sort_order' => (int)($sceneRow['sort_order'] ?? 0),
            'file_name' => (string)($sceneRow['file_name'] ?? ''),
            'media_type' => (string)($sceneRow['media_type'] ?? ''),
            'hotspots' => [],
        ];
    }

    if (!empty($sceneIds)) {
        $inClause = implode(',', array_fill(0, count($sceneIds), '?'));
        $hotspotSql = 'SELECT h.id, h.scene_id, h.yaw, h.pitch, h.target_scene_id, h.title, h.hotspot_type, ts.name AS target_scene_name '
            . 'FROM tour_hotspots h '
            . 'LEFT JOIN tour_scenes ts ON ts.id = h.target_scene_id '
            . 'WHERE h.scene_id IN (' . $inClause . ') '
            . 'ORDER BY h.id ASC';

        $hotspotStmt = $db->prepare($hotspotSql);
        $hotspotStmt->execute($sceneIds);
        $hotspotRows = $hotspotStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($hotspotRows as $row) {
            $sceneId = (int)($row['scene_id'] ?? 0);
            if (!isset($scenes[$sceneId])) {
                continue;
            }

            $scenes[$sceneId]['hotspots'][] = [
                'id' => (int)($row['id'] ?? 0),
                'scene_id' => $sceneId,
                'yaw' => (float)($row['yaw'] ?? 0),
                'pitch' => (float)($row['pitch'] ?? 0),
                'target_scene_id' => (int)($row['target_scene_id'] ?? 0),
                'target_scene_name' => (string)($row['target_scene_name'] ?? ''),
                'title' => (string)($row['title'] ?? ''),
                'hotspot_type' => (string)($row['hotspot_type'] ?? 'link'),
            ];
        }
    }

    $startSceneId = (int)($tour['start_scene_id'] ?? 0);
    if ($startSceneId <= 0 || !isset($scenes[$startSceneId])) {
        $firstScene = reset($scenes);
        $startSceneId = is_array($firstScene) ? (int)($firstScene['id'] ?? 0) : 0;
    }

    public_tours_json(200, [
        'success' => true,
        'tour' => [
            'id' => (int)($tour['id'] ?? 0),
            'project_id' => (int)($tour['project_id'] ?? 0),
            'project_name' => (string)($tour['project_name'] ?? ''),
            'title' => (string)($tour['title'] ?? ''),
            'description' => (string)($tour['description'] ?? ''),
            'start_scene_id' => $startSceneId,
        ],
        'scenes' => array_values($scenes),
    ]);
} catch (Throwable $e) {
    if (function_exists('app_log')) {
        app_log('error', 'Public tours API failed', [
            'exception' => $e->getMessage(),
            'tour_id' => $tourId,
            'project_id' => $projectId,
        ]);
    }
    public_tours_json(500, ['error' => 'Unexpected server error.']);
}
