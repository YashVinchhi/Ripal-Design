<?php

require_once __DIR__ . '/../app/Core/Database/db.php';

// Validate CLI arguments
if ($argc < 4) {
    echo "Usage: php create_project.php <name> <budget> <owner_name>\n";
    exit(1);
}

$name = $argv[1];
$budget = $argv[2];
$ownerName = $argv[3];
$status = $argv[4] ?? 'new';

try {
    $db = get_db();
    $stmt = $db->prepare('INSERT INTO projects (name, budget, owner_name, status, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$name, $budget, $ownerName, $status]);

    $projectId = $db->lastInsertId();
    echo "Project created successfully with ID: $projectId\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}