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
    
    // Detect scheme (http or https)
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Get the directory of the current script
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptPath = dirname($scriptName);
    
    // Normalize path separators to forward slashes
    $scriptPath = str_replace('\\', '/', $scriptPath);
    $scriptPath = trim($scriptPath, '/');
    
    // Detect if we're in a subdirectory (public/dashboard/admin/etc)
    // If so, remove that folder from the path to get the app root
    if (!empty($scriptPath)) {
        $parts = explode('/', $scriptPath);
        $lastPart = $parts[count($parts) - 1];
        
        // List of known application subfolders
        $appFolders = ['public', 'dashboard', 'admin', 'client', 'worker'];
        
        if (in_array($lastPart, $appFolders)) {
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
    
    // Detect if we're in a subdirectory
    if (!empty($scriptPath)) {
        $parts = explode('/', $scriptPath);
        $lastPart = $parts[count($parts) - 1];
        
        // List of known application subfolders
        $appFolders = ['public', 'dashboard', 'admin', 'client', 'worker'];
        
        if (in_array($lastPart, $appFolders)) {
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
