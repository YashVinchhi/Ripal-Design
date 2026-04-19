<?php
// CLI migration runner for local development
if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

// Show errors in CLI runs
@ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../app/Core/Bootstrap/init.php';

$files = array_slice($argv, 1);
if (empty($files)) {
    $glob = __DIR__ . '/../sql/migrations/*.sql';
    $files = glob($glob);
}

if (empty($files)) {
    echo "No migration files provided or found in sql/migrations/.\n";
    exit(0);
}

$pdo = get_db();
if (!$pdo) {
    echo "Database connection not available. Check DB config or environment variables.\n";
    exit(1);
}

echo "Connected to DB. Running migrations...\n";

foreach ($files as $file) {
    $path = $file;
    if (!file_exists($path)) {
        echo "File not found: $path\n";
        continue;
    }
    echo "\nApplying migration: $path\n";
    $sql = file_get_contents($path);
    if ($sql === false) {
        echo "Failed to read $path\n";
        continue;
    }

    // Normalize line endings
    $sql = str_replace(["\r\n", "\r"], "\n", $sql);

    // Split statements on semicolon followed by newline (simple but works for our DDL)
    $stmts = preg_split('/;\s*\n/', $sql);
    if ($stmts === false) $stmts = [$sql];

    foreach ($stmts as $raw) {
        $stmt = trim($raw);
        if ($stmt === '') continue;

        // Handle ALTER TABLE with multiple ADD COLUMNs intelligently
        if (preg_match('/ALTER\s+TABLE\s+`?([A-Za-z0-9_]+)`?/i', $stmt, $mTable)) {
            $table = $mTable[1];
            if (preg_match_all('/ADD\s+COLUMN\s+`([^`]+)`/i', $stmt, $colMatches)) {
                $cols = $colMatches[1];
                $missing = [];
                foreach ($cols as $c) {
                    if (!function_exists('db_column_exists') || !db_column_exists($table, $c)) {
                        $missing[] = $c;
                    }
                }
                if (empty($missing)) {
                    echo " - Skipping ALTER TABLE `$table`: columns already exist\n";
                    continue;
                }

                // Extract ADD COLUMN fragments and keep only missing ones
                if (preg_match_all('/ADD\s+COLUMN\s+`([^`]+)`\s+([^,;]+)/i', $stmt, $fragMatches, PREG_SET_ORDER)) {
                    $frags = [];
                    foreach ($fragMatches as $frag) {
                        $cname = $frag[1];
                        $def = trim($frag[2]);
                        if (in_array($cname, $missing, true)) {
                            $frags[] = 'ADD COLUMN `' . $cname . '` ' . $def;
                        }
                    }
                    if (!empty($frags)) {
                        $stmt2 = 'ALTER TABLE `' . $table . '` ' . implode(', ', $frags) . ';';
                        try {
                            $pdo->exec($stmt2);
                            echo " - Executed ALTER TABLE `$table` (added: " . implode(', ', $missing) . ")\n";
                        } catch (PDOException $e) {
                            echo " - ERROR executing ALTER TABLE $table: " . $e->getMessage() . "\n";
                        }
                        continue;
                    }
                }
            }
        }

        // Default execution path
        try {
            $pdo->exec($stmt . ';');
            echo " - OK\n";
        } catch (PDOException $e) {
            echo " - ERROR: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nAll migrations processed.\n";
exit(0);
