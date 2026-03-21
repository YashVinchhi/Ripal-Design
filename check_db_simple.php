<?php
echo "Testing localhost with root:Ro0t1234\n";
$conn = @mysqli_connect("localhost", "root", "Ro0t1234", "ripal_db");
if ($conn) {
    echo "✓ Connection SUCCESS\n\n";
    
    $result = mysqli_query($conn, "SHOW TABLES;");
    if ($result) {
        echo "Tables in ripal_db:\n";
        while ($row = mysqli_fetch_array($result)) {
            echo "  - " . $row[0] . "\n";
        }
    }
    
    // Check signup table
    $result = mysqli_query($conn, "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='ripal_db' AND TABLE_NAME='signup'");
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
