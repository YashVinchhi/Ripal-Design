<?php
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
