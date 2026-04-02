<?php

require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

function api_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function readable_size(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1024 * 1024) {
        return round($bytes / 1024, 1) . ' KB';
    }
    return round($bytes / (1024 * 1024), 1) . ' MB';
}

$db = get_db();
if (!($db instanceof PDO)) {
    api_json(['success' => false, 'message' => 'Database connection unavailable.'], 500);
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method !== 'POST') {
    api_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
$isJson = strpos($contentType, 'application/json') !== false;
$body = [];

if ($isJson) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode((string)$raw, true);
    $body = is_array($decoded) ? $decoded : [];
}

$action = (string)($body['action'] ?? $_POST['action'] ?? '');
$projectId = (int)($body['project_id'] ?? $_POST['project_id'] ?? 0);

$sessionRole = strtolower(trim((string)($_SESSION['user']['role'] ?? '')));
$clientBlockedActions = [
    'upload_file',
    'upload_drawing',
    'add_team_member',
    'remove_team_member',
    'delete_file',
    'delete_drawing',
    'log_activity',
    'contact_via_signal',
];
if ($sessionRole === 'client' && in_array($action, $clientBlockedActions, true)) {
    api_json(['success' => false, 'message' => 'Client accounts have view-only access.'], 403);
}

if ($projectId <= 0) {
    api_json(['success' => false, 'message' => 'Invalid project ID.'], 400);
}

try {
    $checkProject = $db->prepare('SELECT id FROM projects WHERE id = ? LIMIT 1');
    $checkProject->execute([$projectId]);
    if (!$checkProject->fetch(PDO::FETCH_ASSOC)) {
        api_json(['success' => false, 'message' => 'Project not found.'], 404);
    }
} catch (Exception $e) {
    api_json(['success' => false, 'message' => 'Unable to verify project.'], 500);
}

$currentUser = (string)(
    $_SESSION['user']['name']
    ?? $_SESSION['user']['username']
    ?? $_SESSION['user']['email']
    ?? 'System'
);
$currentRole = strtolower(trim((string)(
    $_SESSION['user']['role']
    ?? $_SESSION['role']
    ?? 'unknown'
)));
$uploaderLabel = $currentUser;
if ($currentRole !== '') {
    $uploaderLabel .= ' (' . $currentRole . ')';
}

if ($action === 'add_team_member') {
    $name = trim((string)($_POST['worker_name'] ?? ''));
    $role = trim((string)($_POST['worker_role'] ?? ''));
    $contact = trim((string)($_POST['worker_contact'] ?? ''));

    if ($name === '' || $role === '' || $contact === '') {
        api_json(['success' => false, 'message' => 'All team member fields are required.'], 400);
    }

    try {
        $stmt = $db->prepare('INSERT INTO project_workers (project_id, worker_name, worker_role, worker_contact) VALUES (?, ?, ?, ?)');
        $stmt->execute([$projectId, $name, $role, $contact]);

        $act = $db->prepare('INSERT INTO project_activity (project_id, user, action, item, created_at) VALUES (?, ?, ?, ?, NOW())');
        $act->execute([$projectId, $currentUser, 'added team member', $name]);

        api_json(['success' => true, 'message' => 'Team member added successfully.']);
    } catch (Exception $e) {
        api_json(['success' => false, 'message' => 'Failed to add team member.'], 500);
    }
}

if ($action === 'remove_team_member') {
    $workerId = (int)($body['worker_id'] ?? $_POST['worker_id'] ?? 0);
    if ($workerId <= 0) {
        api_json(['success' => false, 'message' => 'Invalid worker ID.'], 400);
    }

    try {
        $find = $db->prepare('SELECT id, worker_name FROM project_workers WHERE id = ? AND project_id = ? LIMIT 1');
        $find->execute([$workerId, $projectId]);
        $row = $find->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            api_json(['success' => false, 'message' => 'Team member not found.'], 404);
        }

        $del = $db->prepare('DELETE FROM project_workers WHERE id = ? AND project_id = ? LIMIT 1');
        $del->execute([$workerId, $projectId]);

        $item = (string)($row['worker_name'] ?? 'member');
        $act = $db->prepare('INSERT INTO project_activity (project_id, user, action, item, created_at) VALUES (?, ?, ?, ?, NOW())');
        $act->execute([$projectId, $currentUser, 'removed team member', $item]);

        api_json(['success' => true, 'message' => 'Team member removed successfully.']);
    } catch (Exception $e) {
        api_json(['success' => false, 'message' => 'Failed to remove team member.'], 500);
    }
}

if ($action === 'upload_file' || $action === 'upload_drawing') {
    if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
        api_json(['success' => false, 'message' => 'No file uploaded.'], 400);
    }

    $uploaded = $_FILES['file'];
    if ((int)($uploaded['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        api_json(['success' => false, 'message' => 'File upload failed.'], 400);
    }

    $originalName = (string)($uploaded['name'] ?? 'upload');
    $tmpPath = (string)($uploaded['tmp_name'] ?? '');
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $safeBaseName = preg_replace('/[^A-Za-z0-9._-]+/', '_', pathinfo($originalName, PATHINFO_FILENAME));
    $safeBaseName = $safeBaseName !== '' ? $safeBaseName : 'file';

    if ($action === 'upload_drawing') {
        $allowed = ['pdf', 'dwg', 'dxf', 'jpg', 'jpeg', 'png', 'webp'];
        if ($ext !== '' && !in_array($ext, $allowed, true)) {
            api_json(['success' => false, 'message' => 'Unsupported drawing file type.'], 400);
        }
    }

    $folderType = $action === 'upload_drawing' ? 'drawings' : 'files';
    $relativeDir = 'uploads/projects/' . $projectId . '/' . $folderType;
    $absoluteDir = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);

    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
        api_json(['success' => false, 'message' => 'Unable to create upload directory.'], 500);
    }

    $storedName = $safeBaseName . '_' . time() . '_' . bin2hex(random_bytes(4));
    if ($ext !== '') {
        $storedName .= '.' . $ext;
    }

    $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $storedName;
    if (!move_uploaded_file($tmpPath, $absolutePath)) {
        api_json(['success' => false, 'message' => 'Unable to save uploaded file.'], 500);
    }

    $publicPath = rtrim((string)BASE_PATH, '/') . '/' . $relativeDir . '/' . $storedName;
    $sizeLabel = readable_size((int)($uploaded['size'] ?? 0));

    try {
        if ($action === 'upload_drawing') {
            // Backfill-safe: add uploaded_by column once for environments with older schema.
            $hasUploadedBy = false;
            try {
                $colStmt = $db->prepare("SHOW COLUMNS FROM project_drawings LIKE 'uploaded_by'");
                $colStmt->execute();
                $hasUploadedBy = (bool)$colStmt->fetch(PDO::FETCH_ASSOC);
                if (!$hasUploadedBy) {
                    $db->exec("ALTER TABLE project_drawings ADD COLUMN uploaded_by VARCHAR(255) DEFAULT NULL");
                    $hasUploadedBy = true;
                }
            } catch (Exception $e) {
                $hasUploadedBy = false;
            }

            if ($hasUploadedBy) {
                $stmt = $db->prepare('INSERT INTO project_drawings (project_id, name, version, status, file_path, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$projectId, $originalName, 'v1.0', 'Under Review', $publicPath, $uploaderLabel]);
            } else {
                $stmt = $db->prepare('INSERT INTO project_drawings (project_id, name, version, status, file_path, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$projectId, $originalName, 'v1.0', 'Under Review', $publicPath]);
            }
            $newId = (int)$db->lastInsertId();

            $act = $db->prepare('INSERT INTO project_activity (project_id, user, action, item, created_at) VALUES (?, ?, ?, ?, NOW())');
            $act->execute([$projectId, $currentUser, 'uploaded drawing', $originalName]);

            $viewUrl = rtrim((string)BASE_PATH, '/') . '/dashboard/file_stream.php?kind=drawing&id=' . $newId;
        } else {
            $typeLabel = $ext !== '' ? strtoupper($ext) : 'FILE';
            $stmt = $db->prepare('INSERT INTO project_files (project_id, name, type, size, file_path, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([$projectId, $originalName, $typeLabel, $sizeLabel, $publicPath, $currentUser]);
            $newId = (int)$db->lastInsertId();

            $act = $db->prepare('INSERT INTO project_activity (project_id, user, action, item, created_at) VALUES (?, ?, ?, ?, NOW())');
            $act->execute([$projectId, $currentUser, 'uploaded file', $originalName]);

            $viewUrl = rtrim((string)BASE_PATH, '/') . '/dashboard/file_stream.php?kind=file&id=' . $newId;
        }

        api_json(['success' => true, 'message' => 'Upload successful.', 'file_path' => $publicPath, 'view_url' => $viewUrl]);
    } catch (Exception $e) {
        @unlink($absolutePath);
        api_json(['success' => false, 'message' => 'Failed to store file metadata.'], 500);
    }
}

if ($action === 'delete_file') {
    $fileId = (int)($body['file_id'] ?? 0);
    if ($fileId <= 0) {
        api_json(['success' => false, 'message' => 'Invalid file ID.'], 400);
    }

    try {
        $find = $db->prepare('SELECT id, file_path, name FROM project_files WHERE id = ? AND project_id = ? LIMIT 1');
        $find->execute([$fileId, $projectId]);
        $row = $find->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            api_json(['success' => false, 'message' => 'File not found.'], 404);
        }

        $del = $db->prepare('DELETE FROM project_files WHERE id = ? AND project_id = ? LIMIT 1');
        $del->execute([$fileId, $projectId]);

        $item = (string)($row['name'] ?? 'file');
        $act = $db->prepare('INSERT INTO project_activity (project_id, user, action, item, created_at) VALUES (?, ?, ?, ?, NOW())');
        $act->execute([$projectId, $currentUser, 'deleted file', $item]);

        api_json(['success' => true, 'message' => 'File deleted successfully.']);
    } catch (Exception $e) {
        api_json(['success' => false, 'message' => 'Failed to delete file.'], 500);
    }
}

if ($action === 'delete_drawing') {
    $drawingId = (int)($body['drawing_id'] ?? 0);
    if ($drawingId <= 0) {
        api_json(['success' => false, 'message' => 'Invalid drawing ID.'], 400);
    }

    try {
        $find = $db->prepare('SELECT id, name FROM project_drawings WHERE id = ? AND project_id = ? LIMIT 1');
        $find->execute([$drawingId, $projectId]);
        $row = $find->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            api_json(['success' => false, 'message' => 'Drawing not found.'], 404);
        }

        $del = $db->prepare('DELETE FROM project_drawings WHERE id = ? AND project_id = ? LIMIT 1');
        $del->execute([$drawingId, $projectId]);

        $item = (string)($row['name'] ?? 'drawing');
        $act = $db->prepare('INSERT INTO project_activity (project_id, user, action, item, created_at) VALUES (?, ?, ?, ?, NOW())');
        $act->execute([$projectId, $currentUser, 'deleted drawing', $item]);

        api_json(['success' => true, 'message' => 'Drawing deleted successfully.']);
    } catch (Exception $e) {
        api_json(['success' => false, 'message' => 'Failed to delete drawing.'], 500);
    }
}

if ($action === 'log_activity') {
    $activityAction = trim((string)($body['activity_action'] ?? ''));
    $item = trim((string)($body['item'] ?? ''));

    if ($activityAction === '') {
        api_json(['success' => false, 'message' => 'Activity action is required.'], 400);
    }

    try {
        $act = $db->prepare('INSERT INTO project_activity (project_id, user, action, item, created_at) VALUES (?, ?, ?, ?, NOW())');
        $act->execute([$projectId, $currentUser, $activityAction, $item]);
        api_json(['success' => true, 'message' => 'Activity logged.']);
    } catch (Exception $e) {
        api_json(['success' => false, 'message' => 'Failed to log activity.'], 500);
    }
}

if ($action === 'contact_via_signal') {
    $workerId = (int)($body['worker_id'] ?? $_POST['worker_id'] ?? 0);
    $message = trim((string)($body['message'] ?? $_POST['message'] ?? ''));

    if ($workerId <= 0 || $message === '') {
        api_json(['success' => false, 'message' => 'Worker and message are required.'], 400);
    }

    try {
        // Optionally verify worker exists
        $find = $db->prepare('SELECT worker_name FROM project_workers WHERE id = ? AND project_id = ? LIMIT 1');
        $find->execute([$workerId, $projectId]);
        $row = $find->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            api_json(['success' => false, 'message' => 'Team member not found.'], 404);
        }

        // Store as activity (simple internal signal)
        $act = $db->prepare('INSERT INTO project_activity (project_id, user, action, item, created_at) VALUES (?, ?, ?, ?, NOW())');
        $actor = $currentUser;
        $item = sprintf('Signal to %s: %s', $row['worker_name'] ?? 'member', mb_substr($message, 0, 250));
        $act->execute([$projectId, $actor, 'sent internal signal', $item]);

        api_json(['success' => true, 'message' => 'Message sent via internal signal.']);
    } catch (Exception $e) {
        api_json(['success' => false, 'message' => 'Failed to send message.'], 500);
    }
}

api_json(['success' => false, 'message' => 'Unknown action.'], 400);
