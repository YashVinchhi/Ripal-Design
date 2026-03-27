<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: '';
$password = getenv('DB_PASS') ?: '';
$database = getenv('DB_NAME') ?: 'Ripal-Design';
$port = (int)(getenv('DB_PORT') ?: 3306);

$conn = new mysqli($host, $username, $password, $database, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = file_get_contents(__DIR__ . '/database.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (!empty($statement)) {
        if ($conn->query($statement) === TRUE) {
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        } else {
            echo "Error: " . $conn->error . "\n";
        }
    }
}

$conn->close();
