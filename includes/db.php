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

// Load database credentials from environment or sql/config.php, with sensible defaults
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'Ripal-Design';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_PORT = getenv('DB_PORT') ?: '3306';

$sqlConfigPath = __DIR__ . '/../sql/config.php';
if (file_exists($sqlConfigPath)) {
    // sql/config.php defines $host, $username, $password, $database and optional $port
    /** @noinspection PhpIncludeInspection */
    require_once $sqlConfigPath;
    if (!empty($host)) {
        $DB_HOST = (string) $host;
    }
    if (!empty($database)) {
        $DB_NAME = (string) $database;
    }
    if (!empty($username)) {
        $DB_USER = (string) $username;
    }
    if (isset($password)) {
        $DB_PASS = (string) $password;
    }
    if (!empty($port)) {
        $DB_PORT = (string) $port;
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
