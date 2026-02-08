<?php
// db.php - placeholder for database connection
// Replace with real credentials and use environment variables in production.
$DB_HOST = 'localhost';
$DB_NAME = 'ripal_db';
$DB_USER = 'dbuser';
$DB_PASS = 'dbpass';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Exception $e) {
    // In production, log the error and set $pdo to null so pages can fall back to demo data.
    error_log('DB connection failed: ' . $e->getMessage());
    $pdo = null;
}
?>