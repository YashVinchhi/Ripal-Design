<?php
require_once __DIR__ . '/../includes/init.php';

$db = function_exists('get_db') ? get_db() : null;
if (!($db instanceof PDO)) {
    fwrite(STDERR, "DB unavailable\n");
    exit(1);
}

$sqlFile = __DIR__ . '/../sql/migrations/20260410_notifications_enhancement.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "Migration file missing: {$sqlFile}\n");
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false || trim($sql) === '') {
    fwrite(STDERR, "Migration SQL empty\n");
    exit(1);
}

try {
    $db->exec($sql);
    echo "migration_status=ok\n";
} catch (Throwable $e) {
    fwrite(STDERR, "migration_status=failed\n" . $e->getMessage() . "\n");
    exit(1);
}
