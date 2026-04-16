<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';
header('Content-Type: text/plain');

echo "db_connected(): ";
var_export(db_connected());
echo "\n";

if (db_connected()) {
    $rows = db_fetch_all('SELECT id, name FROM projects LIMIT 2');
    echo "Sample rows:\n";
    var_export($rows);
} else {
    echo "No DB connection.\n";
}
