<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';

header('Content-Type: application/json; charset=utf-8');
if (function_exists('db_connection_diagnostics')) {
    echo json_encode(db_connection_diagnostics(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['error' => 'Diagnostics function unavailable'], JSON_PRETTY_PRINT);
}

exit;
