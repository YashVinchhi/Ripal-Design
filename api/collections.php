<?php
// API for user collections (create, add item)
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';
header('Content-Type: application/json; charset=utf-8');

$db = get_db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Unsupported method']);
    exit;
}

$action = $_POST['action'] ?? '';
if ($action === 'create_and_add') {
    require_login();
    require_csrf();
    $title = trim((string)($_POST['title'] ?? ''));
    $visibility = in_array(trim((string)($_POST['visibility'] ?? 'private')), ['private','shared','public'], true) ? trim((string)$_POST['visibility']) : 'private';
    $projectFileId = (int)($_POST['project_file_id'] ?? 0);
    if ($title === '' || $projectFileId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing title or project file']);
        exit;
    }

    // create collection
    $stmt = $db->prepare('INSERT INTO collections (user_id, title, visibility, created_at) VALUES (?, ?, ?, NOW())');
    $userId = (int)($_SESSION['user']['id'] ?? 0);
    $stmt->execute([$userId, $title, $visibility]);
    $collectionId = (int)$db->lastInsertId();

    // add item
    $stmt2 = $db->prepare('INSERT INTO collection_items (collection_id, project_file_id, added_by, added_at) VALUES (?, ?, ?, NOW())');
    $stmt2->execute([$collectionId, $projectFileId, $userId]);

    // Optional: create in-app notifications for designers (best-effort)
    if (function_exists('db_table_exists') && db_table_exists('users') && db_table_exists('notifications')) {
        try {
            $designersStmt = $db->prepare('SELECT id FROM users WHERE role = ?');
            $designersStmt->execute(['designer']);
            $designers = $designersStmt->fetchAll(PDO::FETCH_ASSOC);
            $notifStmt = $db->prepare('INSERT INTO notifications (user_id, actor_id, verb, data, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())');
            $payload = json_encode(['collection_id' => $collectionId, 'project_file_id' => $projectFileId, 'added_by' => $userId]);
            foreach ($designers as $d) {
                $notifStmt->execute([(int)$d['id'], $userId, 'collection_item_added', $payload]);
            }
        } catch (Throwable $e) {
            // swallow notification errors (non-critical)
        }
    }

    echo json_encode(['success' => true, 'collection_id' => $collectionId]);
    exit;
}

if ($action === 'add_item') {
    require_login();
    require_csrf();
    $collectionId = (int)($_POST['collection_id'] ?? 0);
    $projectFileId = (int)($_POST['project_file_id'] ?? 0);
    if ($collectionId <= 0 || $projectFileId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing collection or item']);
        exit;
    }
    $userId = (int)($_SESSION['user']['id'] ?? 0);
    $stmt = $db->prepare('INSERT INTO collection_items (collection_id, project_file_id, added_by, added_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$collectionId, $projectFileId, $userId]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
