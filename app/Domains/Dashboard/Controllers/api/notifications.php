<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 5) . '/app/Core/Bootstrap/init.php'; }
require_once PROJECT_ROOT . '/app/Core/Bootstrap/init.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

function notif_api_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

$userId = current_user_id();
if ($userId <= 0) {
    notif_api_json(['success' => false, 'message' => 'Unauthorized.'], 401);
}

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($method === 'GET') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $items = notifications_get_for_user($userId, $limit);
    $unread = notifications_get_unread_count($userId);
    notif_api_json([
        'success' => true,
        'data' => [
            'items' => $items,
            'unread' => $unread,
        ],
    ]);
}

if ($method === 'POST') {
    $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
    $isJson = strpos($contentType, 'application/json') !== false;

    $body = [];
    if ($isJson) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode((string)$raw, true);
        $body = is_array($decoded) ? $decoded : [];
    }

    $action = (string)($body['action'] ?? $_POST['action'] ?? '');
    $csrfToken = (string)($body['csrf_token'] ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

    if (!csrf_validate($csrfToken)) {
        notif_api_json(['success' => false, 'message' => 'Invalid CSRF token.'], 419);
    }

    if ($action === 'mark_read') {
        $id = (int)($body['id'] ?? $_POST['id'] ?? 0);
        if ($id <= 0) {
            notif_api_json(['success' => false, 'message' => 'Invalid notification id.'], 400);
        }

        $ok = notifications_mark_read($userId, $id);
        notif_api_json([
            'success' => $ok,
            'unread' => notifications_get_unread_count($userId),
        ], $ok ? 200 : 500);
    }

    if ($action === 'mark_all_read') {
        $ok = notifications_mark_all_read($userId);
        notif_api_json([
            'success' => $ok,
            'unread' => notifications_get_unread_count($userId),
        ], $ok ? 200 : 500);
    }

    notif_api_json(['success' => false, 'message' => 'Unknown action.'], 400);
}

notif_api_json(['success' => false, 'message' => 'Method not allowed.'], 405);
