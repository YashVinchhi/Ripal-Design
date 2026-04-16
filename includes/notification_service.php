<?php

require_once dirname(__DIR__) . '/app/Domains/Notifications/Services/notification_service.php';
return;

if (!function_exists('notifications_db')) {
    function notifications_db() {
        return function_exists('get_db') ? get_db() : null;
    }
}

if (!function_exists('notifications_table_exists')) {
    function notifications_table_exists() {
        return function_exists('db_connected') && db_connected() && function_exists('db_table_exists') && db_table_exists('notifications');
    }
}

if (!function_exists('notifications_has_column')) {
    function notifications_has_column($column) {
        static $cache = [];
        $column = (string)$column;
        if ($column === '') {
            return false;
        }
        if (array_key_exists($column, $cache)) {
            return $cache[$column];
        }
        $db = notifications_db();
        if (!($db instanceof PDO) || !notifications_table_exists()) {
            $cache[$column] = false;
            return false;
        }

        try {
            $stmt = $db->prepare(
                'SELECT COUNT(*) AS c
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute(['notifications', $column]);
            $cache[$column] = ((int)$stmt->fetchColumn() > 0);
        } catch (Exception $e) {
            $cache[$column] = false;
        }

        return $cache[$column];
    }
}

if (!function_exists('notifications_insert')) {
    function notifications_insert($userId, $type, $title, $body, array $meta = []) {
        $userId = (int)$userId;
        if ($userId <= 0 || !notifications_table_exists()) {
            return false;
        }

        $db = notifications_db();
        if (!($db instanceof PDO)) {
            return false;
        }

        $columns = ['user_id', 'type', 'title', 'body', 'is_read'];
        $values = [$userId, (string)$type, (string)$title, (string)$body, 0];

        if (notifications_has_column('actor_user_id')) {
            $columns[] = 'actor_user_id';
            $values[] = isset($meta['actor_user_id']) ? (int)$meta['actor_user_id'] : null;
        }
        if (notifications_has_column('project_id')) {
            $columns[] = 'project_id';
            $values[] = isset($meta['project_id']) ? (int)$meta['project_id'] : null;
        }
        if (notifications_has_column('entity_type')) {
            $columns[] = 'entity_type';
            $values[] = isset($meta['entity_type']) ? (string)$meta['entity_type'] : null;
        }
        if (notifications_has_column('entity_id')) {
            $columns[] = 'entity_id';
            $values[] = isset($meta['entity_id']) ? (int)$meta['entity_id'] : null;
        }
        if (notifications_has_column('action_key')) {
            $columns[] = 'action_key';
            $values[] = isset($meta['action_key']) ? (string)$meta['action_key'] : null;
        }
        if (notifications_has_column('deep_link')) {
            $columns[] = 'deep_link';
            $values[] = isset($meta['deep_link']) ? (string)$meta['deep_link'] : null;
        }
        if (notifications_has_column('metadata_json')) {
            $columns[] = 'metadata_json';
            $values[] = isset($meta['metadata']) ? json_encode($meta['metadata'], JSON_UNESCAPED_SLASHES) : null;
        }

        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $sql = 'INSERT INTO notifications (' . implode(',', $columns) . ') VALUES (' . $placeholders . ')';

        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute($values);
        } catch (Exception $e) {
            if (function_exists('app_log')) {
                app_log('warning', 'notifications_insert failed', ['exception' => $e->getMessage(), 'user_id' => (int)$userId]);
            }
            return false;
        }
    }
}

if (!function_exists('notifications_insert_bulk')) {
    function notifications_insert_bulk(array $userIds, $type, $title, $body, array $meta = []) {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (empty($userIds)) {
            return 0;
        }

        $actorId = isset($meta['actor_user_id']) ? (int)$meta['actor_user_id'] : 0;
        $created = 0;
        foreach ($userIds as $uid) {
            if ($uid <= 0 || ($actorId > 0 && $uid === $actorId)) {
                continue;
            }
            if (notifications_insert($uid, $type, $title, $body, $meta)) {
                $created++;
            }
        }
        return $created;
    }
}

if (!function_exists('notifications_get_admin_ids')) {
    function notifications_get_admin_ids() {
        if (!function_exists('db_connected') || !db_connected() || !function_exists('db_table_exists') || !db_table_exists('users')) {
            return [];
        }
        $rows = db_fetch_all("SELECT id FROM users WHERE role = 'admin'");
        return array_values(array_unique(array_map(static function ($r) {
            return (int)($r['id'] ?? 0);
        }, $rows)));
    }
}

if (!function_exists('notifications_get_project_participants')) {
    function notifications_get_project_participants($projectId) {
        $projectId = (int)$projectId;
        $out = [
            'client_id' => 0,
            'created_by' => 0,
            'worker_ids' => [],
            'user_ids' => [],
        ];

        if ($projectId <= 0 || !function_exists('db_connected') || !db_connected() || !function_exists('db_table_exists')) {
            return $out;
        }

        if (db_table_exists('projects')) {
            $p = db_fetch('SELECT client_id, created_by FROM projects WHERE id = ? LIMIT 1', [$projectId]);
            if ($p) {
                $out['client_id'] = (int)($p['client_id'] ?? 0);
                $out['created_by'] = (int)($p['created_by'] ?? 0);
            }
        }

        if (db_table_exists('project_assignments')) {
            $rows = db_fetch_all('SELECT worker_id FROM project_assignments WHERE project_id = ?', [$projectId]);
            $out['worker_ids'] = array_values(array_unique(array_map(static function ($r) {
                return (int)($r['worker_id'] ?? 0);
            }, $rows)));
        }

        $all = [];
        if ($out['client_id'] > 0) {
            $all[] = $out['client_id'];
        }
        if ($out['created_by'] > 0) {
            $all[] = $out['created_by'];
        }
        $all = array_merge($all, $out['worker_ids']);
        $out['user_ids'] = array_values(array_unique(array_filter(array_map('intval', $all))));

        return $out;
    }
}

if (!function_exists('notifications_get_user_ids_by_roles')) {
    function notifications_get_user_ids_by_roles(array $roles) {
        if (!function_exists('db_connected') || !db_connected() || !function_exists('db_table_exists') || !db_table_exists('users')) {
            return [];
        }
        $roles = array_values(array_filter(array_map(static function ($r) {
            return strtolower(trim((string)$r));
        }, $roles)));
        if (empty($roles)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $rows = db_fetch_all("SELECT id FROM users WHERE LOWER(role) IN ($placeholders)", $roles);
        return array_values(array_unique(array_map(static function ($r) {
            return (int)($r['id'] ?? 0);
        }, $rows)));
    }
}

if (!function_exists('notifications_get_for_user')) {
    function notifications_get_for_user($userId, $limit = 20) {
        $userId = (int)$userId;
        $limit = max(1, min(100, (int)$limit));
        if ($userId <= 0 || !notifications_table_exists()) {
            return [];
        }

        $sql = 'SELECT id, type, title, body, is_read, created_at';
        if (notifications_has_column('action_key')) {
            $sql .= ', action_key';
        } else {
            $sql .= ', NULL AS action_key';
        }
        if (notifications_has_column('project_id')) {
            $sql .= ', project_id';
        } else {
            $sql .= ', NULL AS project_id';
        }
        if (notifications_has_column('deep_link')) {
            $sql .= ', deep_link';
        } else {
            $sql .= ', NULL AS deep_link';
        }
        $sql .= " FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT {$limit}";

        return db_fetch_all($sql, [$userId]);
    }
}

if (!function_exists('notifications_get_unread_count')) {
    function notifications_get_unread_count($userId) {
        $userId = (int)$userId;
        if ($userId <= 0 || !notifications_table_exists()) {
            return 0;
        }

        $row = db_fetch('SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0', [$userId]);
        return (int)($row['c'] ?? 0);
    }
}

if (!function_exists('notifications_mark_read')) {
    function notifications_mark_read($userId, $notificationId) {
        $userId = (int)$userId;
        $notificationId = (int)$notificationId;
        if ($userId <= 0 || $notificationId <= 0 || !notifications_table_exists()) {
            return false;
        }

        $sql = 'UPDATE notifications SET is_read = 1';
        if (notifications_has_column('read_at')) {
            $sql .= ', read_at = NOW()';
        }
        $sql .= ' WHERE id = ? AND user_id = ?';

        try {
            db_query($sql, [$notificationId, $userId]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('notifications_mark_all_read')) {
    function notifications_mark_all_read($userId) {
        $userId = (int)$userId;
        if ($userId <= 0 || !notifications_table_exists()) {
            return false;
        }

        $sql = 'UPDATE notifications SET is_read = 1';
        if (notifications_has_column('read_at')) {
            $sql .= ', read_at = NOW()';
        }
        $sql .= ' WHERE user_id = ? AND is_read = 0';

        try {
            db_query($sql, [$userId]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('notifications_notify_admins')) {
    function notifications_notify_admins($type, $title, $body, array $meta = []) {
        $adminIds = notifications_get_admin_ids();
        return notifications_insert_bulk($adminIds, $type, $title, $body, $meta);
    }
}
