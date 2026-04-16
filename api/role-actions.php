<?php
/**
 * WebMCP role-aware action catalog endpoint.
 *
 * Supports:
 * - GET /api/role-actions.php
 */

require_once __DIR__ . '/_webmcp_common.php';
require_once __DIR__ . '/_webmcp_actions.php';

wmcp_require_https();
wmcp_handle_options(true, 'GET, OPTIONS');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
    wmcp_error('Method not allowed.', 405, true);
}

if (!function_exists('require_login')) {
    wmcp_error('Authentication is unavailable.', 503, true);
}

require_login();

$role = wmcp_current_role();
if ($role === '') {
    wmcp_error('Unable to resolve current user role.', 403, true);
}

$catalog = wmcp_action_catalog();
$actions = [];
foreach ($catalog as $key => $action) {
    if (!wmcp_action_allowed_for_role($action, $role)) {
        continue;
    }

    // Never expose delete/remove actions through AI layer.
    if (wmcp_is_delete_like((string)$key)) {
        continue;
    }
    if (wmcp_is_delete_like((string)($action['endpoint'] ?? ''))) {
        continue;
    }
    $defaults = (array)($action['default_params'] ?? []);
    $deleteLikeDefault = false;
    foreach ($defaults as $dv) {
        if (is_scalar($dv) && wmcp_is_delete_like((string)$dv)) {
            $deleteLikeDefault = true;
            break;
        }
    }
    if ($deleteLikeDefault) {
        continue;
    }

    $actions[] = [
        'action_key' => (string)$key,
        'description' => (string)($action['description'] ?? ''),
        'method' => strtoupper((string)($action['method'] ?? 'POST')),
        'allowed_params' => array_values((array)($action['allowed_params'] ?? [])),
        'requires_confirmation' => !empty($action['requires_confirmation']),
    ];
}

wmcp_output([
    'role' => $role,
    'actions' => $actions,
    'count' => count($actions),
    'policy' => [
        'delete_blocked' => true,
        'manual_confirmation_required_for_writes' => true,
    ],
], 200, true);

