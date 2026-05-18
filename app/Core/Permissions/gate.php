<?php

require_once __DIR__ . '/PermissionService.php';

/**
 * Check whether the current user can access a UI component action.
 *
 * @param string $page Page identifier.
 * @param string $component Component identifier.
 * @param string $action Requested action: view, edit, or delete.
 * @return bool True when access is allowed.
 */
function can(string $page, string $component, string $action = 'view'): bool
{
    $permissionService = \App\Core\Permissions\PermissionService::class;

    if (!class_exists($permissionService) || !is_callable([$permissionService, 'can'])) {
        if (function_exists('app_log')) {
            app_log('error', 'Permission service unavailable', [
                'page' => $page,
                'component' => $component,
                'action' => $action,
            ]);
        }

        return false;
    }

    return $permissionService::can($page, $component, $action);
}

/**
 * Render HTML only when the current user can access a UI component action.
 *
 * @param string $page Page identifier.
 * @param string $component Component identifier.
 * @param callable $html Closure that echoes HTML when invoked.
 * @param string $action Requested action: view, edit, or delete.
 * @return void
 */
function render_if(string $page, string $component, callable $html, string $action = 'view'): void
{
    if (can($page, $component, $action)) {
        $html();
    }
}

/**
 * Enforce page-level access control for a page/component/action combination.
 *
 * @param string $page Page identifier.
 * @param string $component Component identifier.
 * @param string $action Requested action: view, edit, or delete.
 * @return void
 */
function gate(string $page, string $component, string $action = 'view'): void
{
    if (can($page, $component, $action)) {
        return;
    }

    if (function_exists('app_log')) {
        app_log('warning', 'Access denied', [
            'page' => $page,
            'component' => $component,
            'user_id' => function_exists('current_user') && is_array(current_user()) ? (int)(current_user()['id'] ?? 0) : 0,
            'request_id' => function_exists('request_id') ? request_id() : 'no-id',
        ]);
    }

    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
    $isJsonRequest = str_contains($accept, 'application/json') || str_contains($scriptName, '/api/') || str_contains($requestUri, '/api/');

    if ($isJsonRequest) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Forbidden'], JSON_UNESCAPED_SLASHES);
        exit;
    }

    if (function_exists('show_404')) {
        show_404();
    }

    header('Location: index.php');
    exit;
}
