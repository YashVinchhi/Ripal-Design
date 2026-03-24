<?php
$host = "localhost";
$username = "root";
$password = "Ro0t1234";
$database = "Ripal-Design";

$conn = new mysqli($host, $username, $password, $database);

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
