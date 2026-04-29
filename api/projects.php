<?php
// Public API for projects: list, detail, appreciate toggle
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';
header('Content-Type: application/json; charset=utf-8');

$db = get_db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ============================================================
// ENDPOINT: GET /api/projects.php?id=N
// Returns single project detail with media
// ============================================================
// ============================================================
// ENDPOINT: GET /api/projects.php?limit=N&offset=N
// Returns paginated published project list
// ============================================================
if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id > 0) {
        $stmt = $db->prepare('SELECT id, name, status, COALESCE(progress,0) AS progress, budget, location, owner_name, is_published, published_at FROM projects WHERE id = ? AND is_published = 1 LIMIT 1');
        $stmt->execute([$id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$project) {
            http_response_code(404);
            echo json_encode(['error' => 'Project not found or not published']);
            exit;
        }

        $filesStmt = $db->prepare('SELECT id, name, type, size, file_path, media_type, meta, sort_order FROM project_files WHERE project_id = ? AND is_public = 1 ORDER BY sort_order ASC, uploaded_at DESC');
        $filesStmt->execute([$id]);
        $files = $filesStmt->fetchAll(PDO::FETCH_ASSOC);

        $likes = 0;
        if (function_exists('db_table_exists') && db_table_exists('project_likes')) {
            $likesStmt = $db->prepare('SELECT COUNT(*) FROM project_likes WHERE project_id = ?');
            $likesStmt->execute([$id]);
            $likes = (int)$likesStmt->fetchColumn();
        }

        echo json_encode(['project' => $project, 'files' => $files, 'likes' => $likes]);
        exit;
    }

    // list published projects
    $limit = isset($_GET['limit']) ? min(200, (int)$_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $stmt = $db->prepare("SELECT id, name, status, COALESCE(progress,0) AS progress, budget, location, owner_name, is_published, published_at FROM projects WHERE is_published = 1 AND LOWER(name) NOT LIKE '%test%' ORDER BY published_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($projects as &$p) {
        $pf = $db->prepare('SELECT id, file_path FROM project_files WHERE project_id = ? AND is_public = 1 AND media_type IN (\'IMAGE\', \'PANORAMA\') ORDER BY sort_order ASC, uploaded_at DESC LIMIT 1');
        $pf->execute([(int)$p['id']]);
        $row = $pf->fetch(PDO::FETCH_ASSOC);
        $cover = $row['file_path'] ?? null;
        if ($cover !== null) {
            $normalized = str_replace('\\', '/', (string)$cover);
            $normalized = ltrim($normalized, '/');

            // If the stored path is under uploads/, ensure a public copy exists
            if (strpos($normalized, 'uploads/') === 0) {
                $publicRelative = $normalized; // e.g. uploads/project/12/files/xyz.png
                $publicAbsolute = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $publicRelative);
                if (!is_file($publicAbsolute)) {
                    $relativeAfterUploads = substr($normalized, strlen('uploads/'));
                    $privateAbs = rtrim((string)UPLOAD_STORAGE_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeAfterUploads);
                    if (is_file($privateAbs)) {
                        $publicDir = dirname($publicAbsolute);
                        if (!is_dir($publicDir)) @mkdir($publicDir, 0775, true);
                        @copy($privateAbs, $publicAbsolute);
                    }
                }
                $p['cover_image'] = rtrim((string)BASE_PATH, '/') . '/' . ltrim($publicRelative, '/');
            } elseif (preg_match('#^(https?:)?//#', $normalized)) {
                // absolute URL — use as-is
                $p['cover_image'] = $cover;
            } else {
                // relative or assets path — prefix site base
                $p['cover_image'] = rtrim((string)BASE_PATH, '/') . '/' . ltrim($normalized, '/');
            }
        } else {
            $p['cover_image'] = null;
        }
    }

    echo json_encode(['projects' => $projects]);
    exit;
}

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'appreciate') {
        require_login();
        require_csrf();
        $projectId = (int)($_POST['project_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if ($projectId <= 0 || $userId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request']);
            exit;
        }
        if (!function_exists('db_table_exists') || !db_table_exists('project_likes')) {
            http_response_code(500);
            echo json_encode(['error' => 'Feature not available']);
            exit;
        }

        $exists = $db->prepare('SELECT id FROM project_likes WHERE project_id = ? AND user_id = ? LIMIT 1');
        $exists->execute([$projectId, $userId]);
        $row = $exists->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $db->prepare('DELETE FROM project_likes WHERE id = ?')->execute([(int)$row['id']]);
            $actionRes = 'removed';
        } else {
            $db->prepare('INSERT INTO project_likes (project_id, user_id) VALUES (?, ?)')->execute([$projectId, $userId]);
            $actionRes = 'added';
        }

        $countStmt = $db->prepare('SELECT COUNT(*) FROM project_likes WHERE project_id = ?');
        $countStmt->execute([$projectId]);
        $count = (int)$countStmt->fetchColumn();

        echo json_encode(['success' => true, 'action' => $actionRes, 'count' => $count]);
        exit;
    }

    // ============================================================
    // ENDPOINT: POST /api/projects.php
    // Creates a new project
    // ============================================================
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (empty($input['name']) || empty($input['budget']) || empty($input['owner_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: name, budget, owner_name']);
        exit;
    }

    // Insert project into database
    $stmt = $db->prepare('INSERT INTO projects (name, budget, owner_name, status, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([
        $input['name'],
        $input['budget'],
        $input['owner_name'],
        $input['status'] ?? 'new'
    ]);

    $projectId = $db->lastInsertId();

    http_response_code(201);
    echo json_encode(['success' => true, 'project_id' => $projectId]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Unsupported method']);

// ============================================================
// WebMCP compatibility endpoint (category/public-id based responses)
// Kept in this file for backward compatibility with existing callers.
// Safe split to separate files is deferred to avoid endpoint breakage.
// ============================================================
/**
 * WebMCP projects endpoint.
 *
 * Supports:
 * - GET /api/projects.php?category={residential|commercial|interior|urban|all}
 * - GET /api/projects.php?id={public_project_id_or_slug}
 */

require_once __DIR__ . '/_webmcp_common.php';

wmcp_require_https();
wmcp_handle_options(true, 'GET, OPTIONS');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
    wmcp_error('Method not allowed.', 405, true);
}

if (!db_connected() || !db_table_exists('projects')) {
    wmcp_error('Projects data source is unavailable.', 503, true);
}

$rawId = $_GET['id'] ?? '';
$rawCategory = $_GET['category'] ?? 'all';

if ($rawId !== '') {
    $resolvedId = wmcp_resolve_project_identifier($rawId, get_db());
    if ($resolvedId <= 0) {
        wmcp_error('Project not found.', 404, true);
    }

    $project = db_fetch('SELECT * FROM projects WHERE id = ? LIMIT 1', [$resolvedId]);
    if (!$project) {
        wmcp_error('Project not found.', 404, true);
    }

    $images = [];
    if (db_table_exists('project_files')) {
        $imageRows = db_fetch_all(
            "SELECT file_path FROM project_files WHERE project_id = ? AND type IN ('JPG','JPEG','PNG','WEBP','GIF') ORDER BY uploaded_at DESC LIMIT 20",
            [$resolvedId]
        );
        foreach ($imageRows as $row) {
            $path = (string)($row['file_path'] ?? '');
            if ($path !== '') {
                $images[] = $path;
            }
        }
    }

    if (empty($images) && db_table_exists('project_drawings')) {
        $drawingRows = db_fetch_all('SELECT file_path FROM project_drawings WHERE project_id = ? ORDER BY uploaded_at DESC LIMIT 20', [$resolvedId]);
        foreach ($drawingRows as $row) {
            $path = (string)($row['file_path'] ?? '');
            if ($path !== '') {
                $images[] = $path;
            }
        }
    }

    $materials = [];
    if (db_table_exists('project_goods')) {
        $materialRows = db_fetch_all('SELECT name FROM project_goods WHERE project_id = ? ORDER BY id DESC LIMIT 30', [$resolvedId]);
        foreach ($materialRows as $row) {
            $name = trim((string)($row['name'] ?? ''));
            if ($name !== '') {
                $materials[] = $name;
            }
        }
    }

    $year = '';
    $createdAt = (string)($project['created_at'] ?? '');
    if ($createdAt !== '') {
        $year = date('Y', strtotime($createdAt));
    }

    $response = [
        'id' => wmcp_project_public_id($resolvedId),
        'title' => (string)($project['name'] ?? ''),
        'images' => $images,
        'description' => (string)($project['address'] ?? $project['location'] ?? ''),
        'materials' => $materials,
        'area_sqft' => null,
        'location' => (string)($project['location'] ?? ''),
        'year' => $year,
        'architect_name' => (string)($project['owner_name'] ?? 'Ripal Design Team'),
        'completion_date' => (string)($project['due'] ?? ''),
        'client_brief' => (string)($project['project_type'] ?? ''),
        'slug' => wmcp_project_slug((string)($project['name'] ?? ''), $resolvedId),
    ];

    wmcp_output($response, 200, true);
}

$category = strtolower(wmcp_clean_text($rawCategory));
$allowedCategories = ['residential', 'commercial', 'interior', 'urban', 'all'];
if (!in_array($category, $allowedCategories, true)) {
    $category = 'all';
}

$rows = db_fetch_all('SELECT id, name, project_type, location, address, created_at FROM projects ORDER BY id DESC LIMIT 500');
$output = [];

foreach ($rows as $row) {
    $projectId = intval($row['id'] ?? 0);
    if ($projectId <= 0) {
        continue;
    }

    $detectedCategory = wmcp_detect_project_category((string)($row['project_type'] ?? ''));
    if ($category !== 'all' && $detectedCategory !== $category) {
        continue;
    }

    $thumbnailUrl = '';
    if (db_table_exists('project_files')) {
        $thumb = db_fetch(
            "SELECT file_path FROM project_files WHERE project_id = ? AND type IN ('JPG','JPEG','PNG','WEBP','GIF') ORDER BY uploaded_at DESC LIMIT 1",
            [$projectId]
        );
        $thumbnailUrl = (string)($thumb['file_path'] ?? '');
    }

    if ($thumbnailUrl === '' && db_table_exists('project_drawings')) {
        $thumbDrawing = db_fetch('SELECT file_path FROM project_drawings WHERE project_id = ? ORDER BY uploaded_at DESC LIMIT 1', [$projectId]);
        $thumbnailUrl = (string)($thumbDrawing['file_path'] ?? '');
    }

    $year = '';
    $createdAt = (string)($row['created_at'] ?? '');
    if ($createdAt !== '') {
        $year = date('Y', strtotime($createdAt));
    }

    $title = (string)($row['name'] ?? 'Untitled Project');

    $output[] = [
        'id' => wmcp_project_public_id($projectId),
        'title' => $title,
        'category' => $detectedCategory,
        'year' => $year,
        'thumbnail_url' => $thumbnailUrl,
        'description' => (string)($row['address'] ?? $row['location'] ?? ''),
        'slug' => wmcp_project_slug($title, $projectId),
    ];
}

wmcp_output($output, 200, true);
