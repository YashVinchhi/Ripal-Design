<?php
/**
 * One-time script to backfill project slugs
 * Run from CLI: php scripts/backfill_project_slugs.php
 */
chdir(__DIR__ . '/..');
require_once __DIR__ . '/../includes/slug.php';

// Bootstrap DB - adapt to your project's DB bootstrap
if (file_exists(__DIR__ . '/../includes/db.php')) {
    require_once __DIR__ . '/../includes/db.php';
}

if (!isset($db) || !($db instanceof PDO)) {
    // Try common function names used in this project
    if (function_exists('db_get_pdo')) {
        $db = db_get_pdo();
    } elseif (function_exists('get_db')) {
        $db = get_db();
    } elseif (function_exists('get_pdo')) {
        $db = get_pdo();
    }
}

if (!($db instanceof PDO)) {
    echo "DB connection not found. Ensure includes/db.php exposes \$db (PDO) or db_get_pdo().\n";
    exit(1);
}

// Fetch projects
$stmt = $db->prepare('SELECT id, name, slug FROM projects');
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($projects as $p) {
    $id = (int)$p['id'];
    $name = trim((string)$p['name']);
    $existing = trim((string)($p['slug'] ?? ''));
    if ($existing !== '') {
        echo "Skipped #$id (already has slug: $existing)\n";
        continue;
    }
    $base = generate_slug($name ?: ('project-' . $id));
    $unique = make_unique_project_slug($db, $base, $id);
    $uStmt = $db->prepare('UPDATE projects SET slug = ? WHERE id = ?');
    $uStmt->execute([$unique, $id]);
    echo "Backfilled #$id => $unique\n";
}

echo "Done.\n";
