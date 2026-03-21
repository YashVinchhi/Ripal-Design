<?php
ini_set('default_socket_timeout', 2);
echo "=== Checking MySQL Connectivity ===\n\n";

echo "1. Testing 192.168.1.64 with devadmin:Ro0t1234\n";
$conn1 = @mysqli_connect("192.168.1.64", "devadmin", "Ro0t1234", "ripal_db_user", 3306);
if ($conn1) {
    echo "   ✓ Connection SUCCESS\n";

    $result = mysqli_query($conn1, "SHOW TABLES;");
    if ($result) {
        $tables = [];
        while ($row = mysqli_fetch_array($result)) {
            $tables[] = $row[0];
        }
        if (in_array('signup', $tables)) {
            echo "   ✓ signup table EXISTS\n";

            echo "\n   Columns in signup table:\n";
            $result = mysqli_query($conn1, "DESCRIBE signup;");
            if ($result) {
                $columns = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $columns[$row['Field']] = $row['Type'];
                    echo "      - {$row['Field']}: {$row['Type']}\n";
                }

                $required = ['id', 'first_name', 'last_name', 'email', 'password', 'phone_number'];
                $missing = array_diff($required, array_keys($columns));
                if (empty($missing)) {
                    echo "   ✓ All required columns PRESENT\n";
                } else {
                    echo "   ✗ MISSING columns: " . implode(', ', $missing) . "\n";
                }
            }
        } else {
            echo "   ✗ signup table NOT FOUND\n";
            echo "   Available: " . implode(', ', $tables) . "\n";
        }
    }
    mysqli_close($conn1);
} else {
    echo "   ✗ Connection FAILED: " . mysqli_connect_error() . "\n";
}

echo "\n\n2. Testing localhost with root:Ro0t1234\n";
$conn2 = @mysqli_connect("localhost", "root", "Ro0t1234", "ripal_db");
if ($conn2) {
    echo "   ✓ Connection SUCCESS\n";
    $result = mysqli_query($conn2, "SHOW TABLES;");
    if ($result) {
        $tables = [];
        while ($row = mysqli_fetch_array($result)) {
            $tables[] = $row[0];
        }
        echo "   Tables: " . (count($tables) > 0 ? implode(', ', $tables) : "NONE") . "\n";
    }
    mysqli_close($conn2);
} else {
    echo "   ✗ Connection FAILED: " . mysqli_connect_error() . "\n";
}
