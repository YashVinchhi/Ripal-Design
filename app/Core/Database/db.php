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

require_once __DIR__ . '/QueryLogger.php';

// Load database credentials from environment or sql/config.php, with sensible defaults
$envHost = getenv('DB_HOST');
$DB_HOST = $envHost ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: (getenv('DB_DATABASE') ?: 'Ripal-Design');
$DB_USER = getenv('DB_USER') ?: (getenv('DB_USERNAME') ?: 'root');
$DB_PASS = getenv('DB_PASS') ?: (getenv('DB_PASSWORD') ?: '');
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
$dbLastError = null;
$dbConnectionInfo = [
    'host' => (string)$DB_HOST,
    'port' => (string)$DB_PORT,
    'database' => (string)$DB_NAME,
    'user' => (string)$DB_USER,
    'driver_loaded' => extension_loaded('pdo_mysql'),
];

try {
    $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 2,
    ];

    // PHP 8.5 deprecates PDO::MYSQL_ATTR_INIT_COMMAND in favor of Pdo\Mysql::ATTR_INIT_COMMAND.
    if (class_exists('Pdo\\Mysql') && defined('Pdo\\Mysql::ATTR_INIT_COMMAND')) {
        $options[\Pdo\Mysql::ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
    } elseif (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
    }

    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
    \App\Core\Database\QueryLogger::init();
} catch (Throwable $e) {
    $dbLastError = $e->getMessage();

    // Log the error securely (don't expose credentials in logs)
    if (function_exists('app_log')) {
        app_log('error', 'Database connection failed', [
            'exception' => $e->getMessage(),
            'host' => $dbConnectionInfo['host'],
            'port' => $dbConnectionInfo['port'],
            'database' => $dbConnectionInfo['database'],
            'user' => $dbConnectionInfo['user'],
            'driver_loaded' => $dbConnectionInfo['driver_loaded'],
        ]);
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

/**
 * Get masked database connection diagnostics for CLI/server checks.
 *
 * @return array<string, mixed>
 */
function db_connection_diagnostics(): array
{
    global $dbConnectionInfo, $dbLastError;

    if (!is_array($dbConnectionInfo ?? null) || empty($dbConnectionInfo)) {
        $dbConnectionInfo = [
            'host' => (string)(getenv('DB_HOST') ?: 'localhost'),
            'port' => (string)(getenv('DB_PORT') ?: '3306'),
            'database' => (string)(getenv('DB_NAME') ?: (getenv('DB_DATABASE') ?: 'Ripal-Design')),
            'user' => (string)(getenv('DB_USER') ?: (getenv('DB_USERNAME') ?: 'root')),
            'driver_loaded' => extension_loaded('pdo_mysql'),
        ];
    }

    return [
        'connected' => db_connected(),
        'host' => (string)($dbConnectionInfo['host'] ?? ''),
        'port' => (string)($dbConnectionInfo['port'] ?? ''),
        'database' => (string)($dbConnectionInfo['database'] ?? ''),
        'user' => (string)($dbConnectionInfo['user'] ?? ''),
        'pdo_mysql_loaded' => (bool)($dbConnectionInfo['driver_loaded'] ?? false),
        'last_error' => $dbLastError,
    ];
}
