<?php

require_once dirname(__DIR__) . '/app/Core/Config/config.php';
return;

/**
 * Application Configuration
 * 
 * Defines base paths and URLs for the application.
 * Automatically detects the application location and configures paths accordingly.
 * 
 * Constants Defined:
 * - BASE_URL: Full URL including scheme and host
 * - BASE_PATH: Path portion only (for relative URLs)
 * - PROJECT_ROOT: Absolute filesystem path to project root
 * 
 * @package RipalDesign
 * @subpackage Configuration
 */

/**
 * Load environment variables from project .env file.
 * Existing process-level environment variables are preserved.
 *
 * @return void
 */
function load_project_env_file()
{
    static $loaded = false;
    if ($loaded) {
        return;
    }
    $loaded = true;

    $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    if (!is_file($envPath) || !is_readable($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim((string)$line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if ($key === '' || !preg_match('/^[A-Z0-9_]+$/', $key)) {
            continue;
        }

        // Do not override env vars already provided by OS/web server.
        if (getenv($key) !== false) {
            continue;
        }

        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

load_project_env_file();

if (!function_exists('env_bool')) {
    /**
     * Read boolean-like environment values safely.
     */
    function env_bool(string $key, bool $default = false): bool
    {
        $raw = getenv($key);
        if ($raw === false || $raw === null || $raw === '') {
            return $default;
        }
        $value = strtolower(trim((string)$raw));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('app_is_https')) {
    /**
     * Best-effort HTTPS detection including reverse-proxy headers.
     */
    function app_is_https(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if ((string)($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        $forwardedProto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwardedProto === 'https') {
            return true;
        }
        $forwardedSsl = strtolower((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? ''));
        return $forwardedSsl === 'on';
    }
}

if (!function_exists('webmcp_is_enabled')) {
    /**
     * Determine if WebMCP discovery should be exposed for this request.
     */
    function webmcp_is_enabled(): bool
    {
        return function_exists('app_is_https') ? app_is_https() : (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off');
    }
}

if (!function_exists('webmcp_manifest_url')) {
    /**
     * Absolute-in-site URL to the WebMCP manifest.
     */
    function webmcp_manifest_url(): string
    {
        return rtrim((string)BASE_PATH, '/') . '/.well-known/webmcp.json';
    }
}

if (!function_exists('webmcp_script_url')) {
    /**
     * Absolute-in-site URL to the WebMCP tool registration script.
     */
    function webmcp_script_url(): string
    {
        return rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/js/webmcp-tools.js';
    }
}

if (!function_exists('webmcp_discovery_markup')) {
    /**
     * Discovery meta + script tags used by WebMCP-aware clients.
     */
    function webmcp_discovery_markup(): string
    {
        $manifest = json_encode(webmcp_manifest_url(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $scriptUrl = htmlspecialchars(webmcp_script_url(), ENT_QUOTES, 'UTF-8');

        return '<script>(function(){if(!document.head){return;}if(!document.head.querySelector("meta[name=\'webmcp-manifest\']")){var m1=document.createElement("meta");m1.setAttribute("name","webmcp-manifest");m1.setAttribute("content",' . $manifest . ');document.head.appendChild(m1);}if(!document.head.querySelector("meta[name=\'mcp-compatible\']")){var m2=document.createElement("meta");m2.setAttribute("name","mcp-compatible");m2.setAttribute("content","true");document.head.appendChild(m2);}})();</script>'
            . "\n"
            . '<script src="' . $scriptUrl . '" defer></script>';
    }
}

if (!function_exists('webmcp_render_bootstrap_once')) {
    /**
     * Emit WebMCP discovery tags once per request.
     */
    function webmcp_render_bootstrap_once(): void
    {
        if (!webmcp_is_enabled() || !empty($GLOBALS['webmcp_script_rendered'])) {
            return;
        }

        echo webmcp_discovery_markup();
        $GLOBALS['webmcp_script_rendered'] = true;
    }
}

/**
 * Detect and return the base URL for the application
 * 
 * Automatically detects the scheme, host, and path where the application is installed.
 * Handles subdirectory installations and subfolder navigation.
 * 
 * @return string Base URL with scheme and host
 */
function getBaseUrl()
{
    static $baseUrl = null;

    // Return cached value if already computed
    if ($baseUrl !== null) {
        return $baseUrl;
    }

    $configuredBaseUrl = trim((string)(getenv('APP_BASE_URL') ?: ''));
    if ($configuredBaseUrl !== '') {
        $baseUrl = rtrim($configuredBaseUrl, '/');
        return $baseUrl;
    }

    // Detect scheme (http or https)
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!preg_match('/^[a-z0-9.-]+(?::[0-9]{1,5})?$/i', $host)) {
        $host = 'localhost';
    }

    // Get the directory of the current script
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptPath = dirname($scriptName);

    // Normalize path separators to forward slashes
    $scriptPath = str_replace('\\', '/', $scriptPath);
    $scriptPath = trim($scriptPath, '/');

    // Detect if we're in a subdirectory (public/dashboard/admin/client/worker/api)
    // Remove trailing app folders to get the application root path.
    if (!empty($scriptPath)) {
        $parts = explode('/', $scriptPath);
        $appFolders = ['public', 'dashboard', 'admin', 'client', 'worker', 'api'];

        while (!empty($parts)) {
            $lastPart = $parts[count($parts) - 1];
            if (!in_array($lastPart, $appFolders, true)) {
                break;
            }
            array_pop($parts);
        }

        $appPath = !empty($parts) ? '/' . implode('/', $parts) : '';
    } else {
        $appPath = '';
    }

    $baseUrl = $scheme . '://' . $host . $appPath;
    return $baseUrl;
}

/**
 * Get just the path portion of the base URL (without scheme and host)
 * 
 * Useful for generating relative URLs in HTML.
 * 
 * @return string Base path (may be empty for root installation)
 */
function getBasePath()
{
    static $basePath = null;

    // Return cached value if already computed
    if ($basePath !== null) {
        return $basePath;
    }

    // Get the directory of the current script
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptPath = dirname($scriptName);

    // Normalize path separators to forward slashes
    $scriptPath = str_replace('\\', '/', $scriptPath);
    $scriptPath = trim($scriptPath, '/');

    // Detect if we're in a subdirectory and remove trailing app folders
    if (!empty($scriptPath)) {
        $parts = explode('/', $scriptPath);
        $appFolders = ['public', 'dashboard', 'admin', 'client', 'worker', 'api'];

        while (!empty($parts)) {
            $lastPart = $parts[count($parts) - 1];
            if (!in_array($lastPart, $appFolders, true)) {
                break;
            }
            array_pop($parts);
        }

        $basePath = !empty($parts) ? '/' . implode('/', $parts) : '';
    } else {
        $basePath = '';
    }

    return $basePath;
}

// Define application constants
define('BASE_URL', getBaseUrl());
define('BASE_PATH', getBasePath());
define('PROJECT_ROOT', dirname(__DIR__));

// Public entry path prefix:
// - '' when Apache DocumentRoot points to /public
// - '/public' when Apache DocumentRoot points to project root
if (!defined('PUBLIC_PATH_PREFIX')) {
    $documentRoot = str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $documentRoot = rtrim($documentRoot, '/');
    $isPublicDocRoot = (bool) preg_match('~/public$~i', $documentRoot);
    define('PUBLIC_PATH_PREFIX', $isPublicDocRoot ? '' : '/public');
}

// Application environment (development, staging, production)
// Set this via environment variable or web server config
if (!defined('APP_ENV')) {
    define('APP_ENV', strtolower((string)(getenv('APP_ENV') ?: 'production')));
}

// Security feature flags (non-destructive defaults)
if (!defined('APP_STRICT_SECURITY')) {
    define('APP_STRICT_SECURITY', env_bool('APP_STRICT_SECURITY', false));
}

if (!defined('SECURITY_REQUIRE_CSRF_FOR_API')) {
    $csrfDefault = APP_STRICT_SECURITY || APP_ENV !== 'development';
    define('SECURITY_REQUIRE_CSRF_FOR_API', env_bool('SECURITY_REQUIRE_CSRF_FOR_API', $csrfDefault));
}

if (!defined('SECURITY_ENFORCE_UPLOAD_ALLOWLIST')) {
    define('SECURITY_ENFORCE_UPLOAD_ALLOWLIST', env_bool('SECURITY_ENFORCE_UPLOAD_ALLOWLIST', APP_STRICT_SECURITY));
}

if (!defined('SECURITY_ALLOWED_UPLOAD_EXTS')) {
    $defaultAllowed = 'pdf,dwg,dxf,jpg,jpeg,png,webp,gif,txt,csv,doc,docx,xls,xlsx,ppt,pptx,zip,rar';
    define('SECURITY_ALLOWED_UPLOAD_EXTS', strtolower((string)(getenv('SECURITY_ALLOWED_UPLOAD_EXTS') ?: $defaultAllowed)));
}

if (!defined('SECURITY_ENABLE_HSTS')) {
    define('SECURITY_ENABLE_HSTS', env_bool('SECURITY_ENABLE_HSTS', false));
}

if (!defined('SECURITY_ENABLE_CSP')) {
    define('SECURITY_ENABLE_CSP', env_bool('SECURITY_ENABLE_CSP', true));
}

if (!defined('SECURITY_CSP_POLICY')) {
    $defaultCsp = "default-src 'self'; base-uri 'self'; frame-ancestors 'self'; object-src 'none'; img-src 'self' data: blob:; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdnjs.cloudflare.com; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com https://cdn.tailwindcss.com https://code.jquery.com https://cdnjs.cloudflare.com; font-src 'self' data: https://cdn.jsdelivr.net https://fonts.gstatic.com; connect-src 'self' https://cdn.jsdelivr.net; form-action 'self'";
    define('SECURITY_CSP_POLICY', (string)(getenv('SECURITY_CSP_POLICY') ?: $defaultCsp));
}

if (!defined('UPLOAD_STORAGE_ROOT')) {
    $configuredUploadRoot = trim((string)(getenv('UPLOAD_STORAGE_ROOT') ?: ''));
    if ($configuredUploadRoot !== '') {
        define('UPLOAD_STORAGE_ROOT', $configuredUploadRoot);
    } else {
        define('UPLOAD_STORAGE_ROOT', rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'private_uploads');
    }
}

// Enable error display in development mode only
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}
