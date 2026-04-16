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

if (file_exists(__DIR__ . '/logger.php')) {
    require_once __DIR__ . '/logger.php';
}

// Load database credentials from environment or sql/config.php, with sensible defaults
$envHost = getenv('DB_HOST');
$DB_HOST = $envHost ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'Ripal-Design';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_PORT = getenv('DB_PORT') ?: '3306';

$projectRoot = defined('PROJECT_ROOT') ? rtrim((string)PROJECT_ROOT, '/\\') : dirname(__DIR__, 3);
$sqlConfigPath = $projectRoot . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'config.php';
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
    ];

    // PHP 8.5 deprecates PDO::MYSQL_ATTR_INIT_COMMAND in favor of Pdo\Mysql::ATTR_INIT_COMMAND.
    if (class_exists('Pdo\\Mysql') && defined('Pdo\\Mysql::ATTR_INIT_COMMAND')) {
        $options[\Pdo\Mysql::ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
    } else {
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
    }

    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    // Log the error securely (don't expose credentials in logs)
    app_log('error', 'Database connection failed', ['exception' => $e->getMessage()]);

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
