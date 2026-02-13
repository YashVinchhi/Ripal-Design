<?php
// init.php - central bootstrapping include
// Loads configuration, DB connection, auth helpers and utilities.
// Include this at the top of pages to ensure shared globals are available.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
// utilities may depend on config and db
if (file_exists(__DIR__ . '/util.php')) {
    require_once __DIR__ . '/util.php';
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Make $pdo available as a global for legacy code that expects it
global $pdo;

?>
