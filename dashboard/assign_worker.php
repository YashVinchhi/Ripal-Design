<?php
// assign_worker.php
// Receives POST: project_id, worker_id
header('Content-Type: application/json; charset=utf-8');
session_start();
// Allow CORS preflight and POST from same origin; adjust as needed for production
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Respond to preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // no body for preflight
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
    $method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
    $raw = @file_get_contents('php://input');
    echo json_encode(['success'=>false, 'message'=>'Invalid method', 'method'=>$method, 'raw'=>$raw]);
    exit;
}

require_once __DIR__ . '/../includes/init.php';

$project_id = isset($_POST['project_id']) ? (int) $_POST['project_id'] : 0;
$worker_id = isset($_POST['worker_id']) ? (int) $_POST['worker_id'] : 0;

if($project_id <= 0 || $worker_id <= 0){
    echo json_encode(['success'=>false, 'message'=>'Missing project or worker id']);
    exit;
}

try{
    // ensure table exists (safe to run repeatedly)
    $pdo->exec("CREATE TABLE IF NOT EXISTS project_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        worker_id INT NOT NULL,
        assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX(project_id),
        INDEX(worker_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // insert assignment
    $stmt = $pdo->prepare('INSERT INTO project_assignments (project_id, worker_id) VALUES (:project_id, :worker_id)');
    $stmt->execute(['project_id' => $project_id, 'worker_id' => $worker_id]);

    echo json_encode(['success'=>true, 'message'=>'Worker assigned successfully.']);
    exit;
} catch (Exception $e){
    // In production, log the exception. Return safe message to client.
    echo json_encode(['success'=>false, 'message'=>'Server error while assigning worker.']);
    exit;
}

?>
