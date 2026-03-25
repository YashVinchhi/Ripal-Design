<?php
// Simple DB connection test with fallbacks and clearer error messages
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain');

function fail($msg, $code = 1) {
    echo $msg . PHP_EOL;
    exit($code);
}

// Try MySQLi first
if (class_exists('mysqli')) {
    // use $port from config.php if present (defaults to 3306)
    $portVal = isset($port) ? (int)$port : 3306;
    $testConn = @new mysqli($host, $username, $password, $database, $portVal);
    if ($testConn->connect_error) {
        fail("MySQLi connection failed: (" . $testConn->connect_errno . ") " . $testConn->connect_error);
    }
    echo "Connected successfully via MySQLi to database '{$database}' on host '{$host}:{$portVal}' as user '{$username}'." . PHP_EOL;
    $testConn->close();
    exit(0);
}

// Fallback to PDO MySQL if available
if (extension_loaded('pdo') && in_array('mysql', PDO::getAvailableDrivers())) {
    try {
        $portVal = isset($port) ? (int)$port : 3306;
        $dsn = "mysql:host={$host};port={$portVal};dbname={$database};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "Connected successfully via PDO to database '{$database}' on host '{$host}:{$portVal}' as user '{$username}'." . PHP_EOL;
        exit(0);
    } catch (PDOException $e) {
        fail("PDO connection failed: ({$e->getCode()}) {$e->getMessage()}");
    }
}

// Neither driver available
fail("No supported MySQL drivers available (mysqli class not found, PDO MySQL driver unavailable).\nEnable the mysqli extension or install/configure PDO MySQL in your PHP configuration (php.ini) and restart your web server.", 2);

?>