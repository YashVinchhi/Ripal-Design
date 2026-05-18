<?php
// Prefer environment variables, fall back to sensible defaults for local dev
$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: (getenv('DB_USERNAME') ?: 'root');
$password = getenv('DB_PASS') ?: (getenv('DB_PASSWORD') ?: '');
$database = getenv('DB_NAME') ?: (getenv('DB_DATABASE') ?: 'Ripal-Design');
$port = (int) (getenv('DB_PORT') ?: 3306);

function sql_get_connection()
{
    global $host, $username, $password, $database, $port;

    if (!class_exists('mysqli')) {
        return null;
    }

    try {
        $conn = mysqli_init();
        if ($conn === false) {
            return null;
        }

        @mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 2);
        @mysqli_options($conn, MYSQLI_INIT_COMMAND, 'SET NAMES utf8mb4');
        if (!@mysqli_real_connect($conn, $host, $username, $password, $database, $port)) {
            @mysqli_close($conn);
            return null;
        }

        return $conn;
    } catch (Throwable $e) {
        return null;
    }
}

// Keep a best-effort legacy connection available, but never fail hard on include.
$conn = sql_get_connection();

// Must be called after every $stmt->execute() that uses CALL ProcedureName()
function flush_stored_results($conn = null)
{
    global $host, $username, $password, $database, $port;

    if (class_exists('mysqli')) {
        try {
            if ($conn instanceof mysqli) {
                $conn->next_result();
                while ($conn->more_results() && $conn->next_result()) {
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                }
                return $conn;
            }

            $fresh = sql_get_connection();
            if ($fresh instanceof mysqli) {
                return $fresh;
            }
        } catch (Throwable $e) {
            return null;
        }
    }

    return null;
}
?>
