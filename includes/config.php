<?php
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
function load_project_env_file() {
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

/**
 * Detect and return the base URL for the application
 * 
 * Automatically detects the scheme, host, and path where the application is installed.
 * Handles subdirectory installations and subfolder navigation.
 * 
 * @return string Base URL with scheme and host
 */
function getBaseUrl() {
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
function getBasePath() {
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
    define('APP_ENV', getenv('APP_ENV') ?: 'production');
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
?>
