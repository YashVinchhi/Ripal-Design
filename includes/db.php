<?php

/**
 * Database Connection Configuration
 * 
 * Establishes PDO connection to MySQL database with proper error handling.
 * Uses environment variables when available for security.
 * Falls back to demo mode if connection fails.
 * 
 * @package RipalDesign
 * @subpackage Database
 */

// Load database credentials from environment or use defaults
// In production, set these in your web server config or .env file
$envHost = getenv('DB_HOST');
$DB_HOST = $envHost ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'Ripal-Design';
$DB_USER = getenv('DB_USER') ?: '';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_PORT = getenv('DB_PORT') ?: '3306';

// If environment vars are not set (or host is still localhost), prefer project sql/config.php
// This lets the local webapp pick up the remote DB credential file used by CLI scripts.
$sqlConfigPath = __DIR__ . '/../sql/config.php';
if (file_exists($sqlConfigPath) && (!$envHost || $DB_HOST === 'localhost')) {
    // sql/config.php defines $host, $username, $password, $database, and $port
    /** @noinspection PhpIncludeInspection */
    require_once $sqlConfigPath;
    if (!empty($host)) {
        $DB_HOST = $host;
    }
    if (!empty($database)) {
        $DB_NAME = $database;
    }
    if (!empty($username)) {
        $DB_USER = $username;
    }
    if (!empty($password)) {
        $DB_PASS = $password;
    }
    if (!empty($port)) {
        $DB_PORT = (string)$port;
    }
}

// Initialize PDO connection
$pdo = null;

try {
    $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    // Log the error securely (don't expose credentials in logs)
    error_log('Database connection failed: ' . $e->getMessage());

    // In development, you might want to see the error
    if (getenv('APP_ENV') === 'development') {
        trigger_error('Database Error: ' . $e->getMessage(), E_USER_WARNING);
    }

    // Set $pdo to null so pages can fall back to demo/offline data
    $pdo = null;
}

/**
 * Check if database connection is available
 * 
 * @return bool True if connected, false otherwise
 */
function db_connected()
{
    global $pdo;
    return $pdo !== null;
}

/**
 * Get the PDO instance
 * 
 * @return PDO|null PDO instance or null if not connected
 */
function get_db()
{
    global $pdo;
    return $pdo;
}
