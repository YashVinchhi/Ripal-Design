<?php
/**
 * API Endpoint for Project Files, Drawings, and Activity Management
 * Handles AJAX requests for file uploads, deletions, and activity logging
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/init.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$response = ['success' => false, 'message' => 'Invalid request'];

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new Exception('Database connection not available');
    }

    // Handle POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? ($_GET['action'] ?? null);
        
        // Handle JSON requests
        if (empty($action)) {
            $json = json_decode(file_get_contents('php://input'), true);
            $action = $json['action'] ?? null;
        }

        switch ($action) {
            case 'upload_file':
                $response = handleFileUpload($pdo);
                break;
                
            case 'upload_drawing':
                $response = handleDrawingUpload($pdo);
                break;
                
            case 'delete_file':
                $response = handleFileDelete($pdo);
                break;
                
            case 'delete_drawing':
                $response = handleDrawingDelete($pdo);
                break;
                
            case 'log_activity':
                $response = handleActivityLog($pdo);
                break;
                
            case 'add_team_member':
                $response = handleAddTeamMember($pdo);
                break;
                
            default:
                $response = ['success' => false, 'message' => 'Unknown action'];
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? null;
        
        switch ($action) {
            case 'get_files':
                $response = getProjectFiles($pdo, $_GET['project_id'] ?? null);
                break;
                
            case 'get_activities':
                $response = getProjectActivities($pdo, $_GET['project_id'] ?? null);
                break;
                
            case 'get_drawings':
                $response = getProjectDrawings($pdo, $_GET['project_id'] ?? null);
                break;
                
            default:
                $response = ['success' => false, 'message' => 'Unknown action'];
        }
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
exit;

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Handle file upload
 */
function handleFileUpload($pdo) {
    if (!isset($_FILES['file']) || !isset($_POST['project_id'])) {
        return ['success' => false, 'message' => 'Missing file or project ID'];
    }
    
    $projectId = intval($_POST['project_id']);
    $file = $_FILES['file'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/../../uploads/projects/' . $projectId . '/files/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $file['name'];
    $filepath = $uploadDir . $filename;
    
    // Handle duplicate filenames
    $counter = 1;
    while (file_exists($filepath)) {
        $filename = pathinfo($file['name'], PATHINFO_FILENAME) . '_' . $counter . '.' . $extension;
        $filepath = $uploadDir . $filename;
        $counter++;
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'Failed to save file'];
    }
    
    // Get file size
    $fileSize = formatFileSize(filesize($filepath));
    
    // Determine file type
    $fileType = strtoupper($extension);
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        $fileType = 'Image';
    } elseif (in_array($extension, ['pdf'])) {
        $fileType = 'PDF';
    } elseif (in_array($extension, ['doc', 'docx'])) {
        $fileType = 'Word';
    } elseif (in_array($extension, ['xls', 'xlsx', 'csv'])) {
        $fileType = 'Excel';
    } elseif (in_array($extension, ['zip', 'rar', '7z'])) {
        $fileType = 'Archive';
    }
    
    // Store in database
    $stmt = $pdo->prepare('
        INSERT INTO project_files (project_id, name, type, size, file_path, uploaded_by, uploaded_at)
        VALUES (:project_id, :name, :type, :size, :file_path, :uploaded_by, NOW())
    ');
    
    $relativePath = 'uploads/projects/' . $projectId . '/files/' . $filename;
    
    $stmt->execute([
        'project_id' => $projectId,
        'name' => $file['name'],
        'type' => $fileType,
        'size' => $fileSize,
        'file_path' => $relativePath,
        'uploaded_by' => $_SESSION['user_name'] ?? 'User'
    ]);
    
    return ['success' => true, 'message' => 'File uploaded successfully', 'file_id' => $pdo->lastInsertId()];
}

/**
 * Handle drawing upload
 */
function handleDrawingUpload($pdo) {
    if (!isset($_FILES['file']) || !isset($_POST['project_id'])) {
        return ['success' => false, 'message' => 'Missing file or project ID'];
    }
    
    $projectId = intval($_POST['project_id']);
    $file = $_FILES['file'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/../../uploads/projects/' . $projectId . '/drawings/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $file['name'];
    $filepath = $uploadDir . $filename;
    
    // Handle duplicate filenames
    $counter = 1;
    while (file_exists($filepath)) {
        $filename = pathinfo($file['name'], PATHINFO_FILENAME) . '_' . $counter . '.' . $extension;
        $filepath = $uploadDir . $filename;
        $counter++;
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'Failed to save file'];
    }
    
    // Store in database
    $stmt = $pdo->prepare('
        INSERT INTO project_drawings (project_id, name, version, status, file_path, uploaded_at)
        VALUES (:project_id, :name, :version, :status, :file_path, NOW())
    ');
    
    $relativePath = 'uploads/projects/' . $projectId . '/drawings/' . $filename;
    $version = 'v1.0'; // Default version
    
    $stmt->execute([
        'project_id' => $projectId,
        'name' => pathinfo($file['name'], PATHINFO_FILENAME),
        'version' => $version,
        'status' => 'Under Review',
        'file_path' => $relativePath
    ]);
    
    return ['success' => true, 'message' => 'Drawing uploaded successfully', 'drawing_id' => $pdo->lastInsertId()];
}

/**
 * Handle file deletion
 */
function handleFileDelete($pdo) {
    $json = json_decode(file_get_contents('php://input'), true);
    $fileId = intval($json['file_id'] ?? 0);
    
    if (!$fileId) {
        return ['success' => false, 'message' => 'Invalid file ID'];
    }
    
    // Get file path before deletion
    $stmt = $pdo->prepare('SELECT file_path FROM project_files WHERE id = :id');
    $stmt->execute(['id' => $fileId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($file && !empty($file['file_path'])) {
        $fullPath = __DIR__ . '/../../' . $file['file_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
    
    // Delete from database
    $stmt = $pdo->prepare('DELETE FROM project_files WHERE id = :id');
    $stmt->execute(['id' => $fileId]);
    
    return ['success' => true, 'message' => 'File deleted successfully'];
}

/**
 * Handle drawing deletion
 */
function handleDrawingDelete($pdo) {
    $json = json_decode(file_get_contents('php://input'), true);
    $drawingId = intval($json['drawing_id'] ?? 0);
    
    if (!$drawingId) {
        return ['success' => false, 'message' => 'Invalid drawing ID'];
    }
    
    // Get file path before deletion
    $stmt = $pdo->prepare('SELECT file_path FROM project_drawings WHERE id = :id');
    $stmt->execute(['id' => $drawingId]);
    $drawing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($drawing && !empty($drawing['file_path'])) {
        $fullPath = __DIR__ . '/../../' . $drawing['file_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
    
    // Delete from database
    $stmt = $pdo->prepare('DELETE FROM project_drawings WHERE id = :id');
    $stmt->execute(['id' => $drawingId]);
    
    return ['success' => true, 'message' => 'Drawing deleted successfully'];
}

/**
 * Handle activity logging
 */
function handleActivityLog($pdo) {
    $json = json_decode(file_get_contents('php://input'), true);
    $projectId = intval($json['project_id'] ?? 0);
    $action = $json['activity_action'] ?? '';
    $item = $json['item'] ?? '';
    
    if (!$projectId || !$action) {
        return ['success' => false, 'message' => 'Missing required fields'];
    }
    
    $stmt = $pdo->prepare('
        INSERT INTO project_activity (project_id, user, action, item, created_at)
        VALUES (:project_id, :user, :action, :item, NOW())
    ');
    
    $stmt->execute([
        'project_id' => $projectId,
        'user' => $_SESSION['user_name'] ?? 'User',
        'action' => $action,
        'item' => $item
    ]);
    
    return ['success' => true, 'message' => 'Activity logged'];
}

/**
 * Handle adding team member
 */
function handleAddTeamMember($pdo) {
    $projectId = intval($_POST['project_id'] ?? 0);
    $workerName = $_POST['worker_name'] ?? '';
    $workerRole = $_POST['worker_role'] ?? '';
    $workerContact = $_POST['worker_contact'] ?? '';
    
    if (!$projectId || !$workerName || !$workerRole || !$workerContact) {
        return ['success' => false, 'message' => 'All fields are required'];
    }
    
    $stmt = $pdo->prepare('
        INSERT INTO project_workers (project_id, worker_name, worker_role, worker_contact)
        VALUES (:project_id, :worker_name, :worker_role, :worker_contact)
    ');
    
    $stmt->execute([
        'project_id' => $projectId,
        'worker_name' => $workerName,
        'worker_role' => $workerRole,
        'worker_contact' => $workerContact
    ]);
    
    return ['success' => true, 'message' => 'Team member added successfully', 'worker_id' => $pdo->lastInsertId()];
}

/**
 * Get project files
 */
function getProjectFiles($pdo, $projectId) {
    if (!$projectId) {
        return ['success' => false, 'message' => 'Missing project ID'];
    }
    
    $stmt = $pdo->prepare('SELECT * FROM project_files WHERE project_id = :id ORDER BY uploaded_at DESC');
    $stmt->execute(['id' => $projectId]);
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return ['success' => true, 'files' => $files];
}

/**
 * Get project activities
 */
function getProjectActivities($pdo, $projectId) {
    if (!$projectId) {
        return ['success' => false, 'message' => 'Missing project ID'];
    }
    
    $stmt = $pdo->prepare('SELECT * FROM project_activity WHERE project_id = :id ORDER BY created_at DESC LIMIT 20');
    $stmt->execute(['id' => $projectId]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return ['success' => true, 'activities' => $activities];
}

/**
 * Get project drawings
 */
function getProjectDrawings($pdo, $projectId) {
    if (!$projectId) {
        return ['success' => false, 'message' => 'Missing project ID'];
    }
    
    $stmt = $pdo->prepare('SELECT * FROM project_drawings WHERE project_id = :id ORDER BY uploaded_at DESC');
    $stmt->execute(['id' => $projectId]);
    $drawings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return ['success' => true, 'drawings' => $drawings];
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    return $bytes;
}
