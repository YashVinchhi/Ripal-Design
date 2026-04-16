<?php
/**
 * Role-aware WebMCP action catalog and execution helpers.
 */

if (!function_exists('wmcp_current_role')) {
    function wmcp_current_role(): string
    {
        if (!function_exists('current_user')) {
            return '';
        }

        $user = current_user();
        if (!is_array($user)) {
            return '';
        }

        return strtolower(trim((string)($user['role'] ?? '')));
    }
}

if (!function_exists('wmcp_action_catalog')) {
    function wmcp_action_catalog(): array
    {
        return [
            'project.assign_worker' => [
                'description' => 'Assign a worker to a project.',
                'endpoint' => '/dashboard/assign_worker.php',
                'method' => 'POST',
                'encoding' => 'json',
                'allowed_roles' => ['admin'],
                'allowed_params' => ['project_id', 'worker_id'],
                'default_params' => [],
                'requires_confirmation' => true,
            ],
            'project.add_team_member' => [
                'description' => 'Add a team member entry to a project.',
                'endpoint' => '/dashboard/api/project_files.php',
                'method' => 'POST',
                'encoding' => 'json',
                'allowed_roles' => ['admin', 'employee', 'worker'],
                'allowed_params' => ['project_id', 'worker_name', 'worker_role', 'worker_contact', 'worker_user_id'],
                'default_params' => ['action' => 'add_team_member'],
                'requires_confirmation' => true,
            ],
            'project.log_activity' => [
                'description' => 'Log a project activity entry.',
                'endpoint' => '/dashboard/api/project_files.php',
                'method' => 'POST',
                'encoding' => 'json',
                'allowed_roles' => ['admin', 'employee', 'worker'],
                'allowed_params' => ['project_id', 'activity_action', 'item'],
                'default_params' => ['action' => 'log_activity'],
                'requires_confirmation' => true,
            ],
            'project.contact_via_signal' => [
                'description' => 'Send an internal signal message to a team member.',
                'endpoint' => '/dashboard/api/project_files.php',
                'method' => 'POST',
                'encoding' => 'json',
                'allowed_roles' => ['admin', 'employee', 'worker'],
                'allowed_params' => ['project_id', 'worker_id', 'message'],
                'default_params' => ['action' => 'contact_via_signal'],
                'requires_confirmation' => true,
            ],
            'notifications.mark_read' => [
                'description' => 'Mark one notification as read.',
                'endpoint' => '/dashboard/api/notifications.php',
                'method' => 'POST',
                'encoding' => 'json',
                'allowed_roles' => ['admin', 'employee', 'worker', 'client'],
                'allowed_params' => ['id'],
                'default_params' => ['action' => 'mark_read'],
                'requires_confirmation' => true,
            ],
            'notifications.mark_all_read' => [
                'description' => 'Mark all notifications as read.',
                'endpoint' => '/dashboard/api/notifications.php',
                'method' => 'POST',
                'encoding' => 'json',
                'allowed_roles' => ['admin', 'employee', 'worker', 'client'],
                'allowed_params' => [],
                'default_params' => ['action' => 'mark_all_read'],
                'requires_confirmation' => true,
            ],
            'payments.create_paypal_order' => [
                'description' => 'Create a PayPal order for invoice or project payment.',
                'endpoint' => '/admin/api/paypal_create_order.php',
                'method' => 'POST',
                'encoding' => 'json',
                'allowed_roles' => ['admin', 'client'],
                'allowed_params' => ['amount', 'currency', 'project_id', 'invoice_id'],
                'default_params' => [],
                'requires_confirmation' => true,
            ],
            'payments.capture_paypal_order' => [
                'description' => 'Capture a PayPal order after approval.',
                'endpoint' => '/admin/api/paypal_capture_order.php',
                'method' => 'POST',
                'encoding' => 'json',
                'allowed_roles' => ['admin', 'client'],
                'allowed_params' => ['order_id'],
                'default_params' => [],
                'requires_confirmation' => true,
            ],
            'users.update_status' => [
                'description' => 'Update a user account status.',
                'endpoint' => '/admin/user_management.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin'],
                'allowed_params' => ['user_id', 'new_status'],
                'default_params' => ['action' => 'update_status'],
                'requires_confirmation' => true,
            ],
            'leave.update_status' => [
                'description' => 'Update leave request status.',
                'endpoint' => '/admin/leave_management.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin'],
                'allowed_params' => ['leave_id', 'status'],
                'default_params' => [],
                'requires_confirmation' => true,
            ],
            'reviews.update_status' => [
                'description' => 'Update a pending review request status.',
                'endpoint' => '/dashboard/review_requests.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin', 'employee', 'worker'],
                'allowed_params' => ['request_id', 'status'],
                'default_params' => ['update_status' => '1'],
                'requires_confirmation' => true,
            ],
            'drawings.client_review_action' => [
                'description' => 'Authorize or request redline for a client drawing review.',
                'endpoint' => '/client/client_files.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin', 'employee', 'client'],
                'allowed_params' => ['project_id', 'drawing_id', 'client_action'],
                'query_params' => ['project_id'],
                'default_params' => [],
                'requires_confirmation' => true,
            ],
            'worker.ratings.submit' => [
                'description' => 'Submit a worker rating entry.',
                'endpoint' => '/worker/worker_rating.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin', 'employee', 'worker', 'client'],
                'allowed_params' => ['worker_id', 'rating', 'comment'],
                'default_params' => ['submit_rating' => '1'],
                'requires_confirmation' => true,
            ],
            'worker.review_requests.submit' => [
                'description' => 'Submit a project review request from worker project detail flow.',
                'endpoint' => '/worker/project_details.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin', 'employee', 'client'],
                'allowed_params' => ['id', 'request_subject', 'request_details', 'request_urgency', 'request_trade'],
                'query_params' => ['id'],
                'default_params' => [],
                'requires_confirmation' => true,
            ],
            'users.create' => [
                'description' => 'Create a new user account.',
                'endpoint' => '/admin/add_user.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin'],
                'allowed_params' => ['firstName', 'lastName', 'email', 'password', 'role'],
                'default_params' => [],
                'requires_confirmation' => true,
            ],
            'users.update' => [
                'description' => 'Update an existing user account.',
                'endpoint' => '/admin/add_user.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin'],
                'allowed_params' => ['id', 'firstName', 'lastName', 'email', 'password', 'role'],
                'default_params' => [],
                'requires_confirmation' => true,
            ],
            'content.save' => [
                'description' => 'Save admin-managed public content fields.',
                'endpoint' => '/admin/content_management.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin'],
                'allowed_params' => ['page_slug', 'content', 'remove_image'],
                'default_params' => ['action' => 'save'],
                'requires_confirmation' => true,
            ],
            'content.seed_defaults' => [
                'description' => 'Seed missing default public content fields.',
                'endpoint' => '/admin/content_management.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin'],
                'allowed_params' => ['page_slug'],
                'default_params' => ['action' => 'seed_defaults'],
                'requires_confirmation' => true,
            ],
            'projects.create' => [
                'description' => 'Create a project from project details flow.',
                'endpoint' => '/dashboard/project_details.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin', 'employee', 'worker'],
                'allowed_params' => ['name', 'status', 'budget', 'progress', 'due', 'location', 'address', 'map_link', 'owner_name', 'owner_contact', 'owner_email'],
                'default_params' => [],
                'requires_confirmation' => true,
            ],
            'projects.update' => [
                'description' => 'Update an existing project from project details flow.',
                'endpoint' => '/dashboard/project_details.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin', 'employee', 'worker'],
                'allowed_params' => ['id', 'name', 'status', 'budget', 'progress', 'due', 'location', 'address', 'map_link', 'owner_name', 'owner_contact', 'owner_email'],
                'query_params' => ['id'],
                'default_params' => [],
                'requires_confirmation' => true,
            ],
            'projects.assign_owner' => [
                'description' => 'Assign or unassign owner for a project.',
                'endpoint' => '/dashboard/project_details.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin', 'employee', 'worker'],
                'allowed_params' => ['id', 'assign_owner_id'],
                'query_params' => ['id'],
                'default_params' => [],
                'requires_confirmation' => true,
            ],
            'projects.milestone_upsert' => [
                'description' => 'Add or update a project milestone via AJAX flow.',
                'endpoint' => '/dashboard/project_details.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin', 'employee', 'worker'],
                'allowed_params' => ['id', 'milestone_id', 'title', 'target_date'],
                'query_params' => ['id'],
                'default_params' => ['ajax_milestone' => '1'],
                'requires_confirmation' => true,
            ],
            'profile.update' => [
                'description' => 'Update profile details.',
                'endpoint' => '/dashboard/profile.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin', 'employee', 'worker', 'client'],
                'allowed_params' => ['id', 'full_name', 'email', 'phone', 'address', 'city', 'state', 'zip', 'avatar_only'],
                'query_params' => ['id'],
                'default_params' => ['update_profile' => '1'],
                'requires_confirmation' => true,
            ],
            'profile.change_password' => [
                'description' => 'Change account password.',
                'endpoint' => '/dashboard/profile.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin', 'employee', 'worker', 'client'],
                'allowed_params' => ['id', 'new_password', 'confirm_password'],
                'query_params' => ['id'],
                'default_params' => ['change_password' => '1'],
                'requires_confirmation' => true,
            ],
            'profile.submit_client_rating' => [
                'description' => 'Submit a client rating for a project member.',
                'endpoint' => '/dashboard/profile.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['client'],
                'allowed_params' => ['member_id', 'rating', 'comment'],
                'default_params' => ['submit_client_rating' => '1'],
                'requires_confirmation' => true,
            ],
            'billing.create_invoice' => [
                'description' => 'Create a billing invoice.',
                'endpoint' => '/admin/payment_gateway.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin'],
                'allowed_params' => ['project_id', 'tax_rate', 'discount_amount', 'due_date', 'notes'],
                'default_params' => ['action' => 'create_invoice'],
                'requires_confirmation' => true,
            ],
            'billing.record_manual_payment' => [
                'description' => 'Record a manual payment for an invoice.',
                'endpoint' => '/admin/payment_gateway.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin'],
                'allowed_params' => ['invoice_id', 'manual_amount'],
                'default_params' => ['action' => 'record_manual_payment'],
                'requires_confirmation' => true,
            ],
            'billing.set_invoice_status' => [
                'description' => 'Set invoice status in billing workspace.',
                'endpoint' => '/admin/payment_gateway.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin'],
                'allowed_params' => ['invoice_id', 'status'],
                'default_params' => ['action' => 'set_invoice_status'],
                'requires_confirmation' => true,
            ],
            'billing.send_invoice_email' => [
                'description' => 'Send an invoice email from billing workspace.',
                'endpoint' => '/admin/payment_gateway.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin'],
                'allowed_params' => ['invoice_id'],
                'default_params' => ['action' => 'send_invoice_email'],
                'requires_confirmation' => true,
            ],
            'invoice.add_item' => [
                'description' => 'Add an item to goods invoice.',
                'endpoint' => '/dashboard/goods_invoice.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin', 'employee', 'worker'],
                'allowed_params' => ['project_id', 'sku', 'name', 'description', 'unit', 'quantity', 'unit_price'],
                'query_params' => ['project_id'],
                'default_params' => ['action' => 'add_item'],
                'requires_confirmation' => true,
            ],
            'invoice.save_meta' => [
                'description' => 'Save invoice meta information.',
                'endpoint' => '/dashboard/goods_invoice.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin', 'employee', 'worker'],
                'allowed_params' => ['project_id', 'client_name', 'worker_name'],
                'query_params' => ['project_id'],
                'default_params' => ['action' => 'save_meta'],
                'requires_confirmation' => true,
            ],
            'invoice.request_edit' => [
                'description' => 'Submit an invoice edit request.',
                'endpoint' => '/dashboard/goods_invoice.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin', 'employee', 'worker'],
                'allowed_params' => ['project_id', 'request_details'],
                'query_params' => ['project_id'],
                'default_params' => ['action' => 'request_invoice_edit'],
                'requires_confirmation' => true,
            ],
            'invoice.email' => [
                'description' => 'Send invoice email from goods invoice page.',
                'endpoint' => '/dashboard/goods_invoice.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin', 'employee', 'worker'],
                'allowed_params' => ['project_id', 'to_email', 'message'],
                'query_params' => ['project_id'],
                'default_params' => ['action' => 'email_invoice'],
                'requires_confirmation' => true,
            ],
            'goods.add_item' => [
                'description' => 'Add an item from goods management workflow.',
                'endpoint' => '/dashboard/goods_manage.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin'],
                'allowed_params' => ['project_id', 'sku', 'name', 'description', 'unit', 'quantity', 'unit_price'],
                'query_params' => ['project_id'],
                'default_params' => ['action' => 'add'],
                'requires_confirmation' => true,
            ],
            'file_viewer.save_vr_settings' => [
                'description' => 'Save VR viewer settings.',
                'endpoint' => '/admin/file_viewer.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin', 'employee', 'worker', 'client'],
                'allowed_params' => ['project_id', 'file', 'vr_device', 'vr_screensize', 'vr_ipd', 'vr_headset', 'vr_custom'],
                'default_params' => ['vr_action' => 'save_settings'],
                'requires_confirmation' => true,
            ],
            'file_viewer.generate_cad_preview' => [
                'description' => 'Generate CAD preview for supported CAD files.',
                'endpoint' => '/admin/file_viewer.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin', 'employee', 'worker', 'client'],
                'allowed_params' => ['project_id', 'file', 'kind', 'id', 'ext', 'source_raw_path', 'source_ext'],
                'default_params' => ['test_action' => 'generate_cad_preview'],
                'requires_confirmation' => true,
            ],
            'project_management.update_cover' => [
                'description' => 'Update project cover metadata trigger (file upload handled separately in UI).',
                'endpoint' => '/admin/project_management.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin'],
                'allowed_params' => ['project_id'],
                'default_params' => ['update_project_cover' => '1'],
                'requires_confirmation' => true,
            ],
            'temp_user.provision' => [
                'description' => 'Provision a temporary identity credential set.',
                'endpoint' => '/admin/provision_temp_user.php',
                'method' => 'POST',
                'encoding' => 'form',
                'allowed_roles' => ['admin'],
                'allowed_params' => ['firstName', 'lastName', 'email', 'role', 'subrole', 'subrole_other'],
                'default_params' => [],
                'requires_confirmation' => true,
            ],
        ];
    }
}

if (!function_exists('wmcp_is_delete_like')) {
    function wmcp_is_delete_like(string $value): bool
    {
        $v = strtolower(trim($value));
        if ($v === '') {
            return false;
        }

        return strpos($v, 'delete') !== false
            || strpos($v, 'remove') !== false
            || strpos($v, 'destroy') !== false
            || strpos($v, 'drop') !== false;
    }
}

if (!function_exists('wmcp_action_allowed_for_role')) {
    function wmcp_action_allowed_for_role(array $action, string $role): bool
    {
        $allowed = array_map('strtolower', (array)($action['allowed_roles'] ?? []));
        if (empty($allowed)) {
            return false;
        }

        if ($role === 'admin') {
            return true;
        }

        return in_array($role, $allowed, true);
    }
}

if (!function_exists('wmcp_payload_has_delete_semantics')) {
    function wmcp_payload_has_delete_semantics($value, $key = ''): bool
    {
        if ($key !== '' && wmcp_is_delete_like((string)$key)) {
            return true;
        }

        if (is_scalar($value) || $value === null) {
            return is_scalar($value) && wmcp_is_delete_like((string)$value);
        }

        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $k => $v) {
            if (wmcp_payload_has_delete_semantics($v, (string)$k)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('wmcp_normalize_action_value')) {
    function wmcp_normalize_action_value($value)
    {
        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if (!is_array($value)) {
            return null;
        }

        $normalized = [];
        foreach ($value as $k => $v) {
            $child = wmcp_normalize_action_value($v);
            if (is_scalar($child) || $child === null || is_array($child)) {
                $normalized[$k] = $child;
            }
        }
        return $normalized;
    }
}

if (!function_exists('wmcp_filter_action_params')) {
    function wmcp_filter_action_params(array $params, array $allowedKeys): array
    {
        $out = [];
        foreach ($allowedKeys as $key) {
            $k = (string)$key;
            if (!array_key_exists($k, $params)) {
                continue;
            }

            $normalized = wmcp_normalize_action_value($params[$k]);
            if (is_scalar($normalized) || $normalized === null || is_array($normalized)) {
                $out[$k] = $normalized;
            }
        }

        return $out;
    }
}

if (!function_exists('wmcp_internal_http_request')) {
    function wmcp_internal_http_request(string $method, string $endpoint, array $payload, string $encoding = 'json'): array
    {
        $url = rtrim((string)BASE_URL, '/') . $endpoint;
        $method = strtoupper($method);
        $encoding = strtolower(trim($encoding));
        if ($encoding !== 'form') {
            $encoding = 'json';
        }

        $csrf = function_exists('csrf_token') ? csrf_token() : '';
        if ($csrf !== '') {
            $payload['csrf_token'] = $csrf;
        }

        $cookiePairs = [];
        foreach ($_COOKIE as $name => $value) {
            $cookiePairs[] = rawurlencode((string)$name) . '=' . rawurlencode((string)$value);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $sessionName = session_name();
            $sessionId = session_id();
            if ($sessionName !== '' && $sessionId !== '') {
                $cookiePairs[] = rawurlencode($sessionName) . '=' . rawurlencode($sessionId);
            }
        }
        $cookieHeader = implode('; ', array_unique(array_filter($cookiePairs)));

        $body = $encoding === 'form'
            ? http_build_query($payload)
            : json_encode($payload);

        $headers = [
            'Accept: application/json',
            'X-Requested-With: XMLHttpRequest',
        ];
        if ($csrf !== '') {
            $headers[] = 'X-CSRF-Token: ' . $csrf;
        }
        if ($cookieHeader !== '') {
            $headers[] = 'Cookie: ' . $cookieHeader;
        }
        if ($method === 'POST') {
            $headers[] = $encoding === 'form'
                ? 'Content-Type: application/x-www-form-urlencoded'
                : 'Content-Type: application/json';
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            // Local/self-signed friendly.
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            $raw = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if (!is_string($raw)) {
                return [
                    'ok' => false,
                    'status' => $status > 0 ? $status : 502,
                    'error' => $curlError !== '' ? $curlError : 'Upstream request failed.',
                    'body' => '',
                ];
            }

            $respBody = substr($raw, $headerSize);
            return [
                'ok' => $status >= 200 && $status < 400,
                'status' => $status,
                'error' => '',
                'body' => (string)$respBody,
            ];
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $method === 'POST' ? $body : '',
                'ignore_errors' => true,
                'timeout' => 30,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $respBody = @file_get_contents($url, false, $context);
        $status = 0;
        if (!empty($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $line) {
                if (preg_match('/^HTTP\/\d+(?:\.\d+)?\s+(\d+)/i', (string)$line, $m)) {
                    $status = (int)$m[1];
                    break;
                }
            }
        }

        return [
            'ok' => $status >= 200 && $status < 400,
            'status' => $status > 0 ? $status : 502,
            'error' => $respBody === false ? 'Upstream request failed.' : '',
            'body' => $respBody === false ? '' : (string)$respBody,
        ];
    }
}
