<?php
$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: '';
$password = getenv('DB_PASS') ?: '';
$database = getenv('DB_NAME') ?: 'Ripal-Design';
$port = (int) (getenv('DB_PORT') ?: 3306);

// Do not instantiate connections at include-time if extensions are missing.
$conn = null;
if (class_exists('mysqli')) {
    try {
        $tmp = @new mysqli($host, $username, $password, $database, $port);
        if (!($tmp->connect_error ?? false)) {
            $conn = $tmp;
        } else {
            $conn = null;
        }
    } catch (Throwable $e) {
        $conn = null;
    }
} else {
    $conn = null;
}

/**
 * get_db_connection(): returns a mysqli object, a PDO object, or null.
 * Callers should check the returned value and handle accordingly.
 */
function get_db_connection()
{
    global $host, $username, $password, $database, $port;

    if (class_exists('mysqli')) {
        $conn = @new mysqli($host, $username, $password, $database, $port);
        if (!($conn->connect_error ?? false)) {
            return $conn;
        }
        return null;
    }

    if (extension_loaded('pdo') && in_array('mysql', PDO::getAvailableDrivers())) {
        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            return $pdo;
        } catch (PDOException $e) {
            return null;
        }
    }

    return null;
}
