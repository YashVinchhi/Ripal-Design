<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';

$pdo = get_db();
if (!$pdo) {
    echo "DB connection not available.\n";
    exit(1);
}

echo "Querying DB for published projects...\n";
$stmt = $pdo->prepare('SELECT id, name, is_published, published_at FROM projects WHERE is_published = 1 ORDER BY published_at DESC LIMIT 100');
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

echo "Calling API /api/projects.php?limit=50 ...\n";
$apiUrl = (getenv('API_BASE_URL') ?: 'http://localhost') . '/api/projects.php?limit=50';
echo "API URL: $apiUrl\n";
$ctx = stream_context_create(['http' => ['timeout' => 5]]);
$apiResp = @file_get_contents($apiUrl, false, $ctx);
if ($apiResp === false) {
    $err = error_get_last();
    echo "API request failed: " . ($err['message'] ?? 'unknown') . "\n";
    exit(1);
}

echo "API response:\n" . $apiResp . "\n";

exit(0);
