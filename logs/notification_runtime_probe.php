<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';

$db = function_exists('get_db') ? get_db() : null;
if (!($db instanceof PDO)) {
    echo "probe=db_unavailable\n";
    exit(1);
}

$userRow = db_fetch('SELECT id FROM users ORDER BY id ASC LIMIT 1');
$userId = (int)($userRow['id'] ?? 0);
if ($userId <= 0) {
    echo "probe=no_users\n";
    exit(1);
}

echo "probe_user_id={$userId}\n";

try {
    $db->beginTransaction();

    $beforeUnread = notifications_get_unread_count($userId);

    $okInsert = notifications_insert(
        $userId,
        'system',
        'Notification Runtime Probe',
        'Temporary probe notification. Will be rolled back.',
        [
            'action_key' => 'probe.test',
            'deep_link' => rtrim((string)BASE_PATH, '/') . '/dashboard/dashboard.php',
            'project_id' => null,
            'metadata' => ['source' => 'runtime_probe']
        ]
    );

    echo 'insert_ok=' . ($okInsert ? '1' : '0') . "\n";

    $item = db_fetch(
        'SELECT id, action_key, deep_link, is_read FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 1',
        [$userId]
    );

    if (!$item) {
        echo "probe_item=missing\n";
        $db->rollBack();
        exit(1);
    }

    $probeId = (int)$item['id'];
    echo "probe_item_id={$probeId}\n";
    echo 'probe_action_key=' . (string)($item['action_key'] ?? '') . "\n";
    echo 'probe_deep_link=' . (string)($item['deep_link'] ?? '') . "\n";
    echo 'probe_is_read_before=' . (int)($item['is_read'] ?? 0) . "\n";

    $okMarkRead = notifications_mark_read($userId, $probeId);
    echo 'mark_read_ok=' . ($okMarkRead ? '1' : '0') . "\n";

    $afterRow = db_fetch('SELECT is_read FROM notifications WHERE id = ? LIMIT 1', [$probeId]);
    echo 'probe_is_read_after=' . (int)($afterRow['is_read'] ?? 0) . "\n";

    $afterUnread = notifications_get_unread_count($userId);
    echo "unread_before={$beforeUnread}\n";
    echo "unread_after={$afterUnread}\n";

    $db->rollBack();
    echo "probe_rollback=1\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo 'probe_error=' . $e->getMessage() . "\n";
    exit(1);
}
