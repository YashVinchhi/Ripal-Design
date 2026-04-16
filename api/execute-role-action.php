<?php
/**
 * WebMCP role-aware action executor endpoint.
 *
 * Supports:
 * - POST /api/execute-role-action.php
 */

require_once __DIR__ . '/_webmcp_common.php';
require_once __DIR__ . '/_webmcp_actions.php';

wmcp_require_https();
wmcp_handle_options(false, 'POST, OPTIONS');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    wmcp_error('Method not allowed.', 405, false);
}

if (!function_exists('require_login')) {
    wmcp_error('Authentication is unavailable.', 503, false);
}
require_login();

$rawBody = file_get_contents('php://input');
$payload = json_decode((string)$rawBody, true);
if (!is_array($payload)) {
    $payload = [];
}

$actionKey = wmcp_clean_text($payload['action_key'] ?? '');
if ($actionKey === '') {
    wmcp_error('action_key is required.', 422, false);
}

$catalog = wmcp_action_catalog();
if (!isset($catalog[$actionKey])) {
    wmcp_error('Unknown action.', 404, false);
}

$action = (array)$catalog[$actionKey];
$role = wmcp_current_role();
if (!wmcp_action_allowed_for_role($action, $role)) {
    wmcp_error('Action is not allowed for current role.', 403, false);
}

if (wmcp_is_delete_like($actionKey) || wmcp_is_delete_like((string)($action['endpoint'] ?? ''))) {
    wmcp_error('Delete/remove actions are blocked for AI execution.', 403, false);
}

$requiresConfirmation = !empty($action['requires_confirmation']);
$confirmed = !empty($payload['confirmed']) && ($payload['confirmed'] === true || $payload['confirmed'] === 1 || $payload['confirmed'] === '1');
if ($requiresConfirmation && !$confirmed) {
    wmcp_error('Manual confirmation is required before executing this action.', 428, false);
}

$inputParams = isset($payload['params']) && is_array($payload['params']) ? $payload['params'] : [];
$allowedParams = (array)($action['allowed_params'] ?? []);
$params = wmcp_filter_action_params($inputParams, $allowedParams);
$defaults = (array)($action['default_params'] ?? []);
$merged = array_merge($defaults, $params);

// Block delete/remove semantics in any payload keys/values (including nested arrays).
if (wmcp_payload_has_delete_semantics($merged)) {
    wmcp_error('Delete/remove semantics are blocked for AI execution.', 403, false);
}

$method = strtoupper((string)($action['method'] ?? 'POST'));
$endpoint = (string)($action['endpoint'] ?? '');
$encoding = strtolower((string)($action['encoding'] ?? 'json'));

if ($endpoint === '' || $endpoint[0] !== '/') {
    wmcp_error('Action endpoint is misconfigured.', 500, false);
}

$queryKeys = (array)($action['query_params'] ?? []);
if (!empty($queryKeys)) {
    $queryParams = wmcp_filter_action_params($merged, $queryKeys);
    if (!empty($queryParams)) {
        $qs = http_build_query($queryParams);
        if ($qs !== '') {
            $endpoint .= (strpos($endpoint, '?') === false ? '?' : '&') . $qs;
        }
    }
}

$resp = wmcp_internal_http_request($method, $endpoint, $merged, $encoding);
$body = (string)($resp['body'] ?? '');
$decoded = json_decode($body, true);
$upstreamPayload = is_array($decoded) ? $decoded : ['raw' => $body];

wmcp_output([
    'ok' => !empty($resp['ok']),
    'status' => (int)($resp['status'] ?? 0),
    'action_key' => $actionKey,
    'role' => $role,
    'endpoint' => $endpoint,
    'result' => $upstreamPayload,
    'error' => (string)($resp['error'] ?? ''),
], !empty($resp['ok']) ? 200 : 502, false);
