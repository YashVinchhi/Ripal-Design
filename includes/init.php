<?php
/**
 * Application Bootstrap/Initialization
 * 
 * Central bootstrapping file that loads all core dependencies.
 * Include this file at the top of every page to ensure the application
 * is properly initialized.
 * 
 * Loads in order:
 * 1. Configuration (paths, URLs, environment)
 * 2. Database connection
 * 3. Authentication helpers
 * 4. Utility functions
 * 
 * Also initializes session management.
 * 
 * Usage:
 * require_once __DIR__ . '/../includes/init.php';
 * 
 * @package RipalDesign
 * @subpackage Core
 */

// Prevent direct access
if (!defined('PROJECT_ROOT')) {
    // Load configuration first (defines PROJECT_ROOT)
    require_once __DIR__ . '/config.php';
}

// Load database connection
require_once __DIR__ . '/db.php';

// Load authentication helpers
require_once __DIR__ . '/auth.php';

// Load utility functions (depends on config and db)
if (file_exists(__DIR__ . '/util.php')) {
    require_once __DIR__ . '/util.php';
}

// Start session if not already started
// Use @ to suppress warnings if session already started
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Make $pdo available as a global for legacy code that expects it
global $pdo;

// Set timezone (adjust as needed for your location)
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}
?>
