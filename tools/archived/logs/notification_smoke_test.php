<?php
require_once __DIR__ . '/../../includes/init.php';

header('Content-Type: text/plain; charset=utf-8');

$db = function_exists('get_db') ? get_db() : null;
if (!($db instanceof PDO)) {
    echo "db=unavailable\n";
    exit(1);
}

echo "db=ok\n";

$cols = [];
try {
    $stmt = $db->query('SHOW COLUMNS FROM notifications');
    $cols = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN, 0) : [];
} catch (Throwable $e) {
    echo "schema_error=" . $e->getMessage() . "\n";
    exit(1);
}

echo "columns=" . implode(',', $cols) . "\n";

$userRow = db_fetch('SELECT id FROM users ORDER BY id ASC LIMIT 1');
$userId = (int)($userRow['id'] ?? 0);

echo "sample_user_id=" . $userId . "\n";
if ($userId <= 0) {
    echo "payload_items=0\n";
    echo "unread=0\n";
    exit(0);
}

$items = notifications_get_for_user($userId, 3);
$unread = notifications_get_unread_count($userId);

echo "payload_items=" . count($items) . "\n";
echo "unread=" . $unread . "\n";

if (!empty($items)) {
    echo "first_item=" . json_encode($items[0], JSON_UNESCAPED_SLASHES) . "\n";
}
