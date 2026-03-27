<?php
// Prefer environment variables, fall back to sensible defaults for local dev
$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$database = getenv('DB_NAME') ?: 'Ripal-Design';
$port = (int) (getenv('DB_PORT') ?: 3306);

// Do not instantiate connections at include-time if extensions are missing.
$conn = new mysqli($host, $username, $password, $database) or die("Connection failed: " . mysqli_connect_error());

// Must be called after every $stmt->execute() that uses CALL ProcedureName()
function flush_stored_results($conn)
{
    global $host, $username, $password, $database, $port;

    if (class_exists('mysqli')) {
        $conn = @new mysqli($host, $username, $password, $database, $port);
        if (!($conn->connect_error ?? false)) {
            return $conn;
        }
    }
}
?>