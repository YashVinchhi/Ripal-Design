<?php
/**
 * Shared helpers for WebMCP-compatible API endpoints.
 *
 * Keeps endpoint behavior consistent: HTTPS-only, JSON responses,
 * standardized error payloads, and safe input handling.
 */

require_once __DIR__ . '/../app/Core/Config/config.php';
require_once __DIR__ . '/../app/Core/Database/db.php';
require_once __DIR__ . '/../app/Core/Support/util.php';

if (!function_exists('wmcp_set_json_headers')) {
    function wmcp_set_json_headers($readOnly = true, $allowMethods = 'GET, OPTIONS')
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Methods: ' . $allowMethods);
        header('Access-Control-Allow-Headers: Content-Type, Accept');

        if ($readOnly) {
            header('Access-Control-Allow-Origin: *');
        }
    }
}

if (!function_exists('wmcp_require_https')) {
    function wmcp_require_https()
    {
        $isHttps = function_exists('app_is_https')
            ? app_is_https()
            : (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off');

        if ($isHttps) {
            return;
        }

        wmcp_set_json_headers(false);
        http_response_code(403);
        echo json_encode(['error' => 'HTTPS is required for this endpoint.']);
        exit;
    }
}

if (!function_exists('wmcp_handle_options')) {
    function wmcp_handle_options($readOnly = true, $allowMethods = 'GET, OPTIONS')
    {
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'OPTIONS') {
            return;
        }

        wmcp_set_json_headers($readOnly, $allowMethods);
        http_response_code(204);
        exit;
    }
}

if (!function_exists('wmcp_error')) {
    function wmcp_error($message, $statusCode = 400, $readOnly = true)
    {
        wmcp_set_json_headers($readOnly);
        http_response_code((int)$statusCode);
        echo json_encode(['error' => (string)$message]);
        exit;
    }
}

if (!function_exists('wmcp_output')) {
    function wmcp_output($payload, $statusCode = 200, $readOnly = true)
    {
        wmcp_set_json_headers($readOnly);
        http_response_code((int)$statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('wmcp_clean_text')) {
    function wmcp_clean_text($value)
    {
        return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('wmcp_clean_int')) {
    function wmcp_clean_int($value)
    {
        return intval($value);
    }
}

if (!function_exists('wmcp_project_public_id')) {
    function wmcp_project_public_id($projectId)
    {
        $id = intval($projectId);
        return 'prj_' . substr(sha1('ripal-webmcp-' . $id), 0, 16);
    }
}

if (!function_exists('wmcp_slugify')) {
    function wmcp_slugify($text)
    {
        $text = strtolower(trim((string)$text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim((string)$text, '-');

        if ($text === '') {
            return 'project';
        }

        return $text;
    }
}

if (!function_exists('wmcp_project_slug')) {
    function wmcp_project_slug($name, $projectId)
    {
        $slugBase = wmcp_slugify($name);
        $suffix = substr(sha1('slug-' . intval($projectId)), 0, 8);
        return $slugBase . '-' . $suffix;
    }
}

if (!function_exists('wmcp_resolve_project_identifier')) {
    function wmcp_resolve_project_identifier($identifier, $pdo)
    {
        $identifier = wmcp_clean_text($identifier);
        if ($identifier === '') {
            return 0;
        }

        if (ctype_digit($identifier)) {
            return intval($identifier);
        }

        $rows = db_fetch_all('SELECT id, name FROM projects ORDER BY id ASC');
        foreach ($rows as $row) {
            $id = intval($row['id'] ?? 0);
            $name = (string)($row['name'] ?? '');

            if ($identifier === wmcp_project_public_id($id)) {
                return $id;
            }

            if ($identifier === wmcp_project_slug($name, $id)) {
                return $id;
            }
        }

        return 0;
    }
}

if (!function_exists('wmcp_detect_project_category')) {
    function wmcp_detect_project_category($projectType)
    {
        $type = strtolower(trim((string)$projectType));
        if ($type === '') {
            return 'residential';
        }

        if (strpos($type, 'urban') !== false || strpos($type, 'municipal') !== false || strpos($type, 'infrastructure') !== false) {
            return 'urban';
        }
        if (strpos($type, 'interior') !== false) {
            return 'interior';
        }
        if (strpos($type, 'commercial') !== false || strpos($type, 'office') !== false || strpos($type, 'retail') !== false) {
            return 'commercial';
        }
        if (strpos($type, 'residential') !== false || strpos($type, 'home') !== false || strpos($type, 'villa') !== false) {
            return 'residential';
        }

        return 'residential';
    }
}
