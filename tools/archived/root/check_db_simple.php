<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbUser = getenv('DB_USER') ?: '';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'Ripal-Design';
$dbPort = (int)(getenv('DB_PORT') ?: 3306);

echo "Testing configured database connection\n";
$conn = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
if ($conn) {
    echo "✓ Connection SUCCESS\n\n";
    
    $result = mysqli_query($conn, "SHOW TABLES;");
    if ($result) {
        echo "Tables in Ripal-Design:\n";
        while ($row = mysqli_fetch_array($result)) {
            echo "  - " . $row[0] . "\n";
        }
    }
    
    // Check signup table
    $result = mysqli_query($conn, "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='Ripal-Design' AND TABLE_NAME='signup'");
    $row = mysqli_fetch_array($result);
    if ($row[0] > 0) {
        echo "\n✓ 'signup' table EXISTS\n";
        
        $result = mysqli_query($conn, "DESCRIBE signup");
        echo "\nColumns:\n";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "  - {$row['Field']}: {$row['Type']}\n";
        }
    } else {
        echo "\n✗ 'signup' table NOT FOUND\n";
    }
    
    mysqli_close($conn);
} else {
    echo "✗ Connection FAILED: " . mysqli_connect_error() . "\n";
}
?>