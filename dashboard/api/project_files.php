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

// Detect oversized requests early: when the request body exceeds PHP's
// `post_max_size`, PHP will discard `$_POST`/`$_FILES` and leave us with
// empty input. This often manifests as "Invalid project ID." — provide a
// clear error instead so callers know to increase server limits.
function parse_ini_size_to_bytes(string $val): int {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1] ?? '');
    $num = (int)$val;
    switch ($last) {
        case 'g':
            return $num * 1024 * 1024 * 1024;
        case 'm':
            return $num * 1024 * 1024;
        case 'k':
            return $num * 1024;
        default:
            return $num;
    }
}

$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
$postMax = parse_ini_size_to_bytes((string)ini_get('post_max_size'));
$uploadMax = parse_ini_size_to_bytes((string)ini_get('upload_max_filesize'));
if ($contentLength > 0 && $postMax > 0 && $contentLength > $postMax) {
    api_json([
        'success' => false,
        'message' => 'Request body too large. Increase PHP post_max_size (' . ini_get('post_max_size') . ') and upload_max_filesize (' . ini_get('upload_max_filesize') . ').'
    ], 413);
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
$currentUserId = current_user_id();
$uploaderLabel = $currentUser;
if ($currentRole !== '') {
    $uploaderLabel .= ' (' . $currentRole . ')';
}

if ($action === 'add_team_member') {
    $name = trim((string)($_POST['worker_name'] ?? ''));
    $role = trim((string)($_POST['worker_role'] ?? ''));
    $contact = trim((string)($_POST['worker_contact'] ?? ''));
    $workerUserId = (int)($_POST['worker_user_id'] ?? $body['worker_user_id'] ?? 0);

    if ($name === '' || $role === '' || $contact === '') {
        api_json(['success' => false, 'message' => 'All team member fields are required.'], 400);
    }

    try {
        $stmt = $db->prepare('INSERT INTO project_workers (project_id, worker_name, worker_role, worker_contact) VALUES (?, ?, ?, ?)');
        $stmt->execute([$projectId, $name, $role, $contact]);

        $assignmentInserted = false;
        if ($workerUserId > 0 && db_table_exists('project_assignments')) {
            $existsStmt = $db->prepare('SELECT id FROM project_assignments WHERE project_id = ? AND worker_id = ? LIMIT 1');
            $existsStmt->execute([$projectId, $workerUserId]);
            $assignmentExists = (bool)$existsStmt->fetch(PDO::FETCH_ASSOC);

            if (!$assignmentExists) {
                $assignStmt = $db->prepare('INSERT INTO project_assignments (project_id, worker_id) VALUES (?, ?)');
                $assignStmt->execute([$projectId, $workerUserId]);
                $assignmentInserted = true;
            }
        }

        $act = $db->prepare('INSERT INTO project_activity (project_id, user, action, item, created_at) VALUES (?, ?, ?, ?, NOW())');
        $act->execute([$projectId, $currentUser, 'added team member', $name]);

        if ($assignmentInserted) {
            $actorId = current_user_id();
            $project = db_fetch('SELECT name, client_id FROM projects WHERE id = ? LIMIT 1', [$projectId]);
            $projectName = (string)($project['name'] ?? ('Project #' . $projectId));

            notifications_insert(
                $workerUserId,
                'project',
                'New Project Assigned',
                'New project ' . $projectName . ' assigned.',
                [
                    'actor_user_id' => $actorId,
                    'project_id' => $projectId,
                    'action_key' => 'project.assigned',
                    'deep_link' => rtrim((string)BASE_PATH, '/') . '/worker/project_details.php?id=' . $projectId,
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
                        'project_id' => $projectId,
                        'action_key' => 'project.assignment.updated',
                        'deep_link' => rtrim((string)BASE_PATH, '/') . '/client/client_files.php?project_id=' . $projectId,
                    ]
                );
            }
        }

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
    $fileError = (int)($uploaded['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($fileError !== UPLOAD_ERR_OK) {
        switch ($fileError) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $maxUpload = ini_get('upload_max_filesize') ?: 'unknown';
                $postMax = ini_get('post_max_size') ?: 'unknown';
                api_json([
                    'success' => false,
                    'message' => 'Uploaded file exceeds server allowed size. Increase PHP upload_max_filesize/post_max_size (current: upload_max_filesize=' . $maxUpload . ', post_max_size=' . $postMax . ').'
                ], 400);
            case UPLOAD_ERR_PARTIAL:
                api_json(['success' => false, 'message' => 'File was only partially uploaded.'], 400);
            case UPLOAD_ERR_NO_FILE:
                api_json(['success' => false, 'message' => 'No file uploaded.'], 400);
            case UPLOAD_ERR_NO_TMP_DIR:
                api_json(['success' => false, 'message' => 'Missing temporary folder on server.'], 500);
            case UPLOAD_ERR_CANT_WRITE:
                api_json(['success' => false, 'message' => 'Failed to write uploaded file to disk.'], 500);
            case UPLOAD_ERR_EXTENSION:
                api_json(['success' => false, 'message' => 'File upload stopped by PHP extension.'], 500);
            default:
                api_json(['success' => false, 'message' => 'File upload failed (error code: ' . $fileError . ').'], 400);
        }
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
    $hasProjectFilesStoragePath = function_exists('db_column_exists') ? db_column_exists('project_files', 'storage_path') : false;

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

            $participants = notifications_get_project_participants($projectId);
            $clientId = (int)($participants['client_id'] ?? 0);
            $project = db_fetch('SELECT name FROM projects WHERE id = ? LIMIT 1', [$projectId]);
            $projectName = (string)($project['name'] ?? ('Project #' . $projectId));
            if ($clientId > 0) {
                notifications_insert(
                    $clientId,
                    'drawing',
                    'Design Approval Required',
                    'A new design drawing was uploaded for ' . $projectName . '. Please review it.',
                    [
                        'actor_user_id' => $currentUserId,
                        'project_id' => $projectId,
                        'entity_type' => 'drawing',
                        'entity_id' => $newId,
                        'action_key' => 'drawing.uploaded',
                        'deep_link' => rtrim((string)BASE_PATH, '/') . '/client/client_files.php?project_id=' . $projectId,
                    ]
                );
            }

            $viewUrl = file_viewer_url([
                'kind' => 'drawing',
                'id' => $newId,
                'project_id' => $projectId,
                'ext' => strtolower((string)$ext),
            ]);
        } else {
            $typeLabel = $ext !== '' ? strtoupper($ext) : 'FILE';
            if ($hasProjectFilesStoragePath) {
                $stmt = $db->prepare('INSERT INTO project_files (project_id, name, type, size, file_path, storage_path, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$projectId, $originalName, $typeLabel, $sizeLabel, $publicPath, $publicPath, $currentUser]);
            } else {
                $stmt = $db->prepare('INSERT INTO project_files (project_id, name, type, size, file_path, uploaded_by, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                $stmt->execute([$projectId, $originalName, $typeLabel, $sizeLabel, $publicPath, $currentUser]);
            }
            $newId = (int)$db->lastInsertId();

            $act = $db->prepare('INSERT INTO project_activity (project_id, user, action, item, created_at) VALUES (?, ?, ?, ?, NOW())');
            $act->execute([$projectId, $currentUser, 'uploaded file', $originalName]);

            $participants = notifications_get_project_participants($projectId);
            $recipientIds = array_values(array_unique(array_filter(array_merge(
                [(int)($participants['client_id'] ?? 0)],
                array_map('intval', (array)($participants['worker_ids'] ?? []))
            ))));
            $project = db_fetch('SELECT name FROM projects WHERE id = ? LIMIT 1', [$projectId]);
            $projectName = (string)($project['name'] ?? ('Project #' . $projectId));
            notifications_insert_bulk(
                $recipientIds,
                'file',
                'New Plan/File Uploaded',
                $currentUser . ' uploaded ' . $originalName . ' in ' . $projectName . '.',
                [
                    'actor_user_id' => $currentUserId,
                    'project_id' => $projectId,
                    'entity_type' => 'file',
                    'entity_id' => $newId,
                    'action_key' => 'file.uploaded',
                    'deep_link' => rtrim((string)BASE_PATH, '/') . '/dashboard/project_details.php?id=' . $projectId,
                ]
            );

            $viewUrl = file_viewer_url([
                'kind' => 'file',
                'id' => $newId,
                'project_id' => $projectId,
                'ext' => strtolower((string)$ext),
            ]);
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
