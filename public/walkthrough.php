<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';
require_once __DIR__ . '/../Common/public_shell.php';

$projectSlug = trim((string)($_GET['project_slug'] ?? $_GET['project'] ?? ''));
$modelSlug = trim((string)($_GET['model_slug'] ?? $_GET['model'] ?? ''));

$db = get_db();
if (!($db instanceof PDO) || $projectSlug === '' || $modelSlug === '' || !function_exists('db_table_exists') || !db_table_exists('project_3d_models')) {
    show_404();
}

$projectSql = 'SELECT id, name, slug, location FROM projects WHERE (slug = ? OR id = ?)' . projects_soft_delete_sql('projects', ' AND ') . ' LIMIT 1';
$projectStmt = $db->prepare($projectSql);
$projectStmt->execute([$projectSlug, (int)$projectSlug]);
$project = $projectStmt->fetch(PDO::FETCH_ASSOC) ?: null;
if (!$project) {
    show_404();
}

$modelStmt = $db->prepare('SELECT * FROM project_3d_models WHERE project_id = ? AND slug = ? AND is_active = 1 LIMIT 1');
$modelStmt->execute([(int)$project['id'], $modelSlug]);
$model = $modelStmt->fetch(PDO::FETCH_ASSOC) ?: null;
if (!$model) {
    show_404();
}

$canonicalProjectSlug = trim((string)($project['slug'] ?? '')) !== '' ? (string)$project['slug'] : (string)$project['id'];
$canonicalPath = 'walkthroughs/' . rawurlencode($canonicalProjectSlug) . '/' . rawurlencode((string)$model['slug']);
$canonicalUrl = base_url($canonicalPath);
$currentPath = trim($projectSlug, '/') . '/' . trim($modelSlug, '/');
$expectedPath = $canonicalProjectSlug . '/' . (string)$model['slug'];
if ($currentPath !== $expectedPath && !headers_sent()) {
    header('Location: ' . base_path($canonicalPath), true, 301);
    exit;
}

$modelAssetUrl = base_path('walkthrough-assets/' . rawurlencode($canonicalProjectSlug) . '/' . rawurlencode((string)$model['slug']) . '/model.glb');
$hotspots = [];
if (db_table_exists('project_3d_hotspots')) {
    $hotspotsStmt = $db->prepare('SELECT id, label, description, position_x, position_y, position_z FROM project_3d_hotspots WHERE model_id = ? AND is_active = 1 ORDER BY id ASC');
    $hotspotsStmt->execute([(int)$model['id']]);
    foreach (($hotspotsStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
        $hotspots[] = [
            'id' => (int)$row['id'],
            'label' => (string)$row['label'],
            'description' => (string)($row['description'] ?? ''),
            'position' => [(float)$row['position_x'], (float)$row['position_y'], (float)$row['position_z']],
        ];
    }
}

$title = trim((string)($model['title'] ?? '3D Walkthrough'));
$projectName = trim((string)($project['name'] ?? 'Project'));
$pageTitle = $title . ' | ' . $projectName . ' 3D Walkthrough | Ripal Design';
$description = trim((string)($model['description'] ?? ''));
if ($description === '') {
    $description = 'Explore the ' . $projectName . ' architectural walkthrough in an interactive 3D model viewer.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo esc($pageTitle); ?></title>
    <meta name="description" content="<?php echo esc_attr($description); ?>">
    <link rel="canonical" href="<?php echo esc_attr($canonicalUrl); ?>">
    <link rel="icon" href="<?php echo esc_attr(BASE_PATH); ?>/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo esc_attr(base_path('assets/css/model-walkthrough.css')); ?>">
    <script type="application/ld+json"><?php echo json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'CreativeWork',
        'name' => $title,
        'description' => $description,
        'url' => $canonicalUrl,
        'associatedMedia' => [
            '@type' => 'MediaObject',
            'encodingFormat' => 'model/gltf-binary',
            'contentUrl' => base_url('walkthrough-assets/' . rawurlencode($canonicalProjectSlug) . '/' . rawurlencode((string)$model['slug']) . '/model.glb'),
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
</head>
<body>
<main class="walkthrough-page">
    <section class="walkthrough-bar" aria-label="Walkthrough controls">
        <div>
            <p class="walkthrough-kicker"><?php echo esc($projectName); ?></p>
            <h1><?php echo esc($title); ?></h1>
        </div>
        <div class="walkthrough-actions">
            <button type="button" id="walkReset">Reset</button>
            <button type="button" id="walkFullscreen">Fullscreen</button>
        </div>
    </section>
    <section class="walkthrough-stage">
        <canvas id="walkthroughCanvas" aria-label="<?php echo esc_attr($title); ?> interactive 3D walkthrough"></canvas>
        <div id="walkthroughStatus" class="walkthrough-status">Loading 3D model...</div>
        <div class="walkthrough-help">Click to lock mouse. Look 360 degrees, WASD to move around the model, Shift to move faster, Esc to release.</div>
    </section>
</main>
<script>
window.RD_WALKTHROUGH = <?php echo json_encode([
    'title' => $title,
    'projectName' => $projectName,
    'modelUrl' => $modelAssetUrl,
    'hotspots' => $hotspots,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="https://cdn.babylonjs.com/babylon.js"></script>
<script src="https://cdn.babylonjs.com/loaders/babylonjs.loaders.min.js"></script>
<script src="<?php echo esc_attr(base_path('assets/js/model-walkthrough.js')); ?>"></script>
</body>
</html>
