<?php
// assign_worker.php
// Receives POST: project_id, worker_id
header('Content-Type: application/json; charset=utf-8');
session_start();
if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    echo json_encode(['success'=>false, 'message'=>'Invalid method']);
    exit;
}

require_once __DIR__ . '/../includes/db.php';

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
