<?php
/**
 * WebMCP project search endpoint.
 */

require_once __DIR__ . '/_webmcp_common.php';

wmcp_require_https();
wmcp_handle_options(true, 'GET, OPTIONS');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
    wmcp_error('Method not allowed.', 405, true);
}

if (!db_connected() || !db_table_exists('projects')) {
    wmcp_error('Search source is unavailable.', 503, true);
}

$query = wmcp_clean_text($_GET['q'] ?? $_GET['query'] ?? '');
$location = wmcp_clean_text($_GET['location'] ?? '');
$type = wmcp_clean_text($_GET['type'] ?? '');
$yearFrom = wmcp_clean_int($_GET['year_from'] ?? 0);
$yearTo = wmcp_clean_int($_GET['year_to'] ?? 0);

$sql = 'SELECT id, name, project_type, location, address, created_at FROM projects WHERE 1=1';
$params = [];

if ($query !== '') {
    $sql .= ' AND (name LIKE :query OR location LIKE :query OR address LIKE :query OR project_type LIKE :query OR owner_name LIKE :query)';
    $params[':query'] = '%' . $query . '%';
}

if ($location !== '') {
    $sql .= ' AND location LIKE :location';
    $params[':location'] = '%' . $location . '%';
}

if ($type !== '') {
    $sql .= ' AND project_type LIKE :type';
    $params[':type'] = '%' . $type . '%';
}

if ($yearFrom > 0) {
    $sql .= ' AND YEAR(created_at) >= :year_from';
    $params[':year_from'] = $yearFrom;
}

if ($yearTo > 0) {
    $sql .= ' AND YEAR(created_at) <= :year_to';
    $params[':year_to'] = $yearTo;
}

$sql .= ' ORDER BY created_at DESC LIMIT 200';

$stmt = get_db()->prepare($sql);
foreach ($params as $key => $value) {
    if (is_int($value)) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
}
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$results = [];
foreach ($rows as $row) {
    $projectId = intval($row['id'] ?? 0);
    if ($projectId <= 0) {
        continue;
    }

    $title = (string)($row['name'] ?? 'Untitled Project');
    $createdAt = (string)($row['created_at'] ?? '');
    $year = $createdAt !== '' ? date('Y', strtotime($createdAt)) : '';

    $results[] = [
        'id' => wmcp_project_public_id($projectId),
        'title' => $title,
        'category' => wmcp_detect_project_category((string)($row['project_type'] ?? '')),
        'location' => (string)($row['location'] ?? ''),
        'year' => $year,
        'description' => (string)($row['address'] ?? ''),
        'slug' => wmcp_project_slug($title, $projectId),
    ];
}

wmcp_output($results, 200, true);
