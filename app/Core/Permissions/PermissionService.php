<?php

namespace App\Core\Permissions;

use PDO;
use Throwable;

require_once dirname(__DIR__, 3) . '/includes/db.php';
require_once dirname(__DIR__, 3) . '/includes/auth.php';

/**
 * Permission cache and lookup service for UI-level access control.
 */
class PermissionService
{
    /**
     * Cached permission rows keyed by "page_key.component_key".
     *
     * @var array<string, array{can_view: int, can_edit: int, can_delete: int}>
     */
    private static array $cache = [];

    /**
     * Track the role used to populate the cache.
     *
     * @var string
     */
    private static string $role = '';

    /**
     * Load all permission rows for the given role into the static cache.
     *
     * @param int $userId Current user identifier.
     * @param string $role Current user role.
     * @return void
     */
    public static function load(int $userId, string $role): void
    {
        self::$cache = [];
        self::$role = $role;

        if ($role === 'admin') {
            return;
        }

        $db = get_db();
        if (!$db instanceof PDO) {
            return;
        }

        try {
            $statement = $db->prepare(
                'SELECT page_key, component_key, can_view, can_edit, can_delete
                 FROM ui_permissions
                 WHERE role = :role'
            );
            $statement->execute(['role' => $role]);

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                if (!is_array($row)) {
                    continue;
                }

                $key = (string)($row['page_key'] ?? '') . '.' . (string)($row['component_key'] ?? '');
                self::$cache[$key] = [
                    'can_view' => (int)($row['can_view'] ?? 0),
                    'can_edit' => (int)($row['can_edit'] ?? 0),
                    'can_delete' => (int)($row['can_delete'] ?? 0),
                ];
            }
        } catch (Throwable $throwable) {
            self::$cache = [];
        }
    }

    /**
     * Check whether the current user can perform an action on a component.
     *
     * @param string $page_key Page identifier.
     * @param string $component_key Component identifier.
     * @param string $action Requested action: view, edit, or delete.
     * @return bool True when access is allowed.
     */
    public static function can(string $page_key, string $component_key, string $action = 'view'): bool
    {
        $action = strtolower($action);

        $user = current_user();
        $role = is_array($user) ? (string)($user['role'] ?? '') : self::$role;

        if ($role === 'admin') {
            return true;
        }

        if (self::$role !== $role) {
            $userId = is_array($user) ? (int)($user['id'] ?? 0) : 0;
            self::load($userId, $role);
        }

        $key = $page_key . '.' . $component_key;
        if (!isset(self::$cache[$key])) {
            return false;
        }

        return match ($action) {
            'view' => (bool) self::$cache[$key]['can_view'],
            'edit' => (bool) self::$cache[$key]['can_edit'],
            'delete' => (bool) self::$cache[$key]['can_delete'],
            default => false,
        };
    }

    /**
     * Preload the permission cache for the current logged-in user.
     *
     * @return void
     */
    public static function preload(): void
    {
        $user = current_user();
        if (!is_array($user)) {
            self::$cache = [];
            self::$role = '';
            return;
        }

        self::load((int)($user['id'] ?? 0), (string)($user['role'] ?? ''));
    }
}
