<?php
/**
 * Configuration file for Ripal Design
 * Defines base paths and URLs for the application
 */

// Detect the base URL for the application
function getBaseUrl() {
    static $baseUrl = null;
    
    if ($baseUrl !== null) {
        return $baseUrl;
    }
    
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Get the directory of the current script
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptPath = dirname($scriptName);
    
    // Normalize the path
    $scriptPath = str_replace('\\', '/', $scriptPath);
    $scriptPath = trim($scriptPath, '/');
    
    // Detect if we're in a subdirectory
    if (!empty($scriptPath)) {
        $parts = explode('/', $scriptPath);
        // If in public/dashboard/admin/etc, go up one level
        if (in_array($parts[count($parts) - 1], ['public', 'dashboard', 'admin', 'client', 'worker'])) {
            array_pop($parts);
        }
        $appPath = !empty($parts) ? '/' . implode('/', $parts) : '';
    } else {
        $appPath = '';
    }
    
    $baseUrl = $scheme . '://' . $host . $appPath;
    return $baseUrl;
}

// Get just the path portion (without scheme and host)
function getBasePath() {
    static $basePath = null;
    
    if ($basePath !== null) {
        return $basePath;
    }
    
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptPath = dirname($scriptName);
    
    // Normalize the path
    $scriptPath = str_replace('\\', '/', $scriptPath);
    $scriptPath = trim($scriptPath, '/');
    
    // Detect if we're in a subdirectory
    if (!empty($scriptPath)) {
        $parts = explode('/', $scriptPath);
        // If in public/dashboard/admin/etc, go up one level
        if (in_array($parts[count($parts) - 1], ['public', 'dashboard', 'admin', 'client', 'worker'])) {
            array_pop($parts);
        }
        $basePath = !empty($parts) ? '/' . implode('/', $parts) : '';
    } else {
        $basePath = '';
    }
    
    return $basePath;
}

// Define constants
define('BASE_URL', getBaseUrl());
define('BASE_PATH', getBasePath());
define('PROJECT_ROOT', dirname(__DIR__));
?>
