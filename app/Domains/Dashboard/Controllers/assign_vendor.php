<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
// assign_vendor.php
// Receives POST: project_id, vendor_id
header('Content-Type: application/json; charset=utf-8');
session_start();
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// Respond to preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
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
$vendor_id = isset($data['vendor_id']) ? (int) $data['vendor_id'] : 0;

$csrfToken = $data['csrf_token'] ?? '';
if ($csrfToken === '' && !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
    $csrfToken = (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
}
if (!function_exists('csrf_validate') || !csrf_validate($csrfToken)) {
    http_response_code(419);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

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

if($project_id <= 0 || $vendor_id <= 0){
    echo json_encode(['success'=>false, 'message'=>'Missing project or vendor id']);
    exit;
}

try{
    $stmt = $pdo->prepare('INSERT INTO project_vendors (project_id, vendor_id, assigned_by) VALUES (:project_id, :vendor_id, :assigned_by)');
    $stmt->execute(['project_id' => $project_id, 'vendor_id' => $vendor_id, 'assigned_by' => current_user_username()]);

    $actorId = current_user_id();
    $project = db_fetch('SELECT name, client_id FROM projects WHERE id = ? LIMIT 1', [$project_id]);
    $projectName = (string)($project['name'] ?? ('Project #' . $project_id));

    // Notify vendor contact if vendor has email in vendors table
    $vendor = db_fetch('SELECT id, name, contact_name, email FROM vendors WHERE id = ? LIMIT 1', [$vendor_id]);
    if (!empty($vendor['email'])) {
        // notifications_insert may expect a user id, but vendors are not users; store as system notification for admin only
        notifications_insert(
            $actorId,
            'vendor',
            'Vendor Assigned',
            'Vendor ' . ($vendor['name'] ?? 'Vendor') . ' assigned to project ' . $projectName . '.',
            [
                'actor_user_id' => $actorId,
                'project_id' => $project_id,
                'vendor_id' => $vendor_id,
                'action_key' => 'project.vendor.assigned'
            ]
        );
    }

    echo json_encode(['success'=>true, 'message'=>'Vendor assigned successfully.']);
    exit;
} catch (Exception $e){
    echo json_encode(['success'=>false, 'message'=>'Server error while assigning vendor.']);
    exit;
}

?>
