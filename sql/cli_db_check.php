<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';
header('Content-Type: text/plain');

echo "db_connected(): ";
var_export(db_connected());
echo "\n";

if (function_exists('db_connection_diagnostics')) {
    echo "Diagnostics:\n";
    foreach (db_connection_diagnostics() as $key => $value) {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif ($value === null) {
            $value = '(none)';
        }
        echo $key . ': ' . $value . "\n";
    }
}

if (db_connected()) {
    $rows = db_fetch_all('SELECT id, name FROM projects' . (function_exists('projects_soft_delete_sql') ? projects_soft_delete_sql('projects', ' WHERE ') : '') . ' LIMIT 2');
    echo "Sample rows:\n";
    var_export($rows);
} else {
    echo "No DB connection.\n";
}
