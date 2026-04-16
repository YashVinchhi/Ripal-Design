<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
// assign_worker.php
// Receives POST: project_id, worker_id
header('Content-Type: application/json; charset=utf-8');
session_start();
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Respond to preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // no body for preflight
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
    $method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
    echo json_encode(['success'=>false, 'message'=>'Invalid method', 'method'=>$method]);
    exit;
}

require_once PROJECT_ROOT . '/app/Core/Bootstrap/init.php';
require_login();
require_role('admin');

// Support both form-encoded and JSON request bodies
$rawInput = file_get_contents('php://input');
$data = [];
if (!empty($_POST)) {
    $data = $_POST;
} elseif (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $data = $decoded;
    }
}

$project_id = isset($data['project_id']) ? (int) $data['project_id'] : 0;
$worker_id = isset($data['worker_id']) ? (int) $data['worker_id'] : 0;

$csrfToken = $data['csrf_token'] ?? '';
if ($csrfToken === '' && !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
    $csrfToken = (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
}
if (!function_exists('csrf_validate') || !csrf_validate($csrfToken)) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

// Ensure we have a valid PDO instance
if (!function_exists('get_db')) {
    echo json_encode(['success' => false, 'message' => 'Server misconfiguration: database helper missing.']);
    exit;
}
$pdo = get_db();
if ($pdo === null) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
    exit;
}

if($project_id <= 0 || $worker_id <= 0){
    echo json_encode(['success'=>false, 'message'=>'Missing project or worker id']);
    exit;
}

try{
    // insert assignment
    $stmt = $pdo->prepare('INSERT INTO project_assignments (project_id, worker_id) VALUES (:project_id, :worker_id)');
    $stmt->execute(['project_id' => $project_id, 'worker_id' => $worker_id]);

    $actorId = current_user_id();
    $project = db_fetch('SELECT name, client_id FROM projects WHERE id = ? LIMIT 1', [$project_id]);
    $projectName = (string)($project['name'] ?? ('Project #' . $project_id));

    notifications_insert(
        $worker_id,
        'project',
        'New Project Assigned',
        'New project ' . $projectName . ' assigned.',
        [
            'actor_user_id' => $actorId,
            'project_id' => $project_id,
            'action_key' => 'project.assigned',
            'deep_link' => rtrim((string)BASE_PATH, '/') . '/worker/project_details.php?id=' . $project_id,
        ]
    );

    $clientId = (int)($project['client_id'] ?? 0);
    if ($clientId > 0) {
        notifications_insert(
            $clientId,
            'project',
            'Team Assignment Updated',
            'A worker was assigned to ' . $projectName . '.',
            [
                'actor_user_id' => $actorId,
                'project_id' => $project_id,
                'action_key' => 'project.assignment.updated',
                'deep_link' => rtrim((string)BASE_PATH, '/') . '/client/client_files.php?project_id=' . $project_id,
            ]
        );
    }

    echo json_encode(['success'=>true, 'message'=>'Worker assigned successfully.']);
    exit;
} catch (Exception $e){
    // In production, log the exception. Return safe message to client.
    echo json_encode(['success'=>false, 'message'=>'Server error while assigning worker.']);
    exit;
}

?>
